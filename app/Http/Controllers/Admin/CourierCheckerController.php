<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierCheckResult;
use App\Models\FraudBlock;
use App\Models\Setting;
use App\Services\CourierHistoryCheckService;
use App\Services\FraudDetectionService;
use App\Support\FraudNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Single home for the cross-courier fraud-history check
 * (devrkb21/czbd-courier): an ad-hoc "search any phone number" tool, recent
 * history, and — on its own Settings tab, the same way SteadfastCourier's
 * own page splits Dashboard from API Settings — the merchant credentials
 * and automation thresholds that feed
 * FraudDetectionService::evaluateCourierHistory().
 */
class CourierCheckerController extends Controller
{
    private const CREDENTIALS_GROUP = 'courier_check';
    private const AUTOMATION_GROUP = 'fraud_blocks';

    public function index(Request $request)
    {
        $this->ensureDefaultCredentialSettings();

        $credentials = Setting::where('group', self::CREDENTIALS_GROUP)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');

        $automation = $this->getAutomationSettings();
        $recentChecks = CourierCheckResult::orderByDesc('checked_at')->limit(10)->get();

        $stats = [
            'total_checks' => CourierCheckResult::count(),
            'flagged' => FraudBlock::where('type', 'phone')->where('source', 'auto')->where('reason', 'like', '%other couriers%')->count(),
            'configured' => app(CourierHistoryCheckService::class)->hasAnyCredentialsConfigured(),
        ];

        return view('admin.orders.courier-checker', compact('credentials', 'automation', 'recentChecks', 'stats'));
    }

    /**
     * Ad-hoc lookup: admin types in any phone number (doesn't need to be
     * tied to an order) and gets a result back, same as the "Check"/
     * "Refresh" buttons on an order page. Serves a result already cached
     * within CourierHistoryCheckService::SEARCH_CACHE_HOURS unless
     * `refresh` is sent, in which case it bypasses the cache for a live
     * answer. Shares the same rendered result partial for a consistent
     * look, which shows a Cached/Fresh badge either way.
     */
    public function search(Request $request, CourierHistoryCheckService $courierHistoryCheckService, FraudDetectionService $fraudDetectionService): JsonResponse
    {
        // Logging into up to 5 courier portals (with multi-account failover
        // on each) can comfortably exceed PHP's default 30s execution limit.
        set_time_limit(180);

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);
        $forceRefresh = $request->boolean('refresh');

        if (!$courierHistoryCheckService->hasAnyCredentialsConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'No courier credentials are configured yet.',
                'settings_url' => route('admin.orders.courier-checker', ['tab' => 'settings']),
            ], 422);
        }

        $normalizedPhone = FraudNormalizer::phone($validated['phone']);
        if ($normalizedPhone === null) {
            return response()->json(['success' => false, 'message' => 'Could not parse a phone number from that input.'], 422);
        }

        ['result' => $courierCheckResult, 'from_cache' => $fromCache] = $courierHistoryCheckService->checkWithCache($normalizedPhone, $forceRefresh);

        // No order to attach this to — but if a matching phone has an
        // existing fraud_block entry or recent order, the same risk
        // evaluation still applies via the generic phone-based path.
        if (!$fromCache) {
            $fraudDetectionService->evaluateCourierHistoryForPhone($normalizedPhone, [
                'total_deliveries' => $courierCheckResult->total_deliveries,
                'success_ratio' => (float) $courierCheckResult->success_ratio,
            ]);
        }

        $html = view('admin.orders.partials.courier-history-card-body', [
            'courierCheckResult' => $courierCheckResult,
            'fromCache' => $fromCache,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'normalized_phone' => $normalizedPhone,
            'is_blocked' => FraudBlock::isBlocked('phone', $normalizedPhone),
            'from_cache' => $fromCache,
        ]);
    }

    /**
     * View a stored result from the Recent Checks list — no live courier
     * calls, just renders whatever was stored from the last run for that
     * phone number. Always shown as cached, regardless of age.
     */
    public function show(CourierCheckResult $courierCheckResult): JsonResponse
    {
        $html = view('admin.orders.partials.courier-history-card-body', [
            'courierCheckResult' => $courierCheckResult,
            'fromCache' => true,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'normalized_phone' => $courierCheckResult->normalized_phone,
            'is_blocked' => FraudBlock::isBlocked('phone', $courierCheckResult->normalized_phone),
        ]);
    }

    /**
     * Credentials are never echoed back to the browser in plain text once
     * saved (the Settings tab shows a masked placeholder instead) — so an
     * empty submission for a password field means "unchanged", not "clear
     * it out". Only a non-empty value overwrites what's stored.
     */
    public function updateCredentials(Request $request)
    {
        $this->ensureDefaultCredentialSettings();

        $validated = $request->validate([
            'pathao_users' => ['nullable', 'string'],
            'pathao_passwords' => ['nullable', 'string'],
            'steadfast_users' => ['nullable', 'string'],
            'steadfast_passwords' => ['nullable', 'string'],
            'redx_phones' => ['nullable', 'string'],
            'redx_passwords' => ['nullable', 'string'],
            'paperfly_users' => ['nullable', 'string'],
            'paperfly_passwords' => ['nullable', 'string'],
            'carrybee_phones' => ['nullable', 'string'],
            'carrybee_passwords' => ['nullable', 'string'],
            'proxy_all' => ['nullable', 'boolean'],
            'proxy_pathao' => ['nullable', 'boolean'],
            'proxy_steadfast' => ['nullable', 'boolean'],
            'proxy_redx' => ['nullable', 'boolean'],
            'proxy_paperfly' => ['nullable', 'boolean'],
            'proxy_carrybee' => ['nullable', 'boolean'],
            'proxy_address' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = Setting::where('group', self::CREDENTIALS_GROUP)->pluck('value', 'key');

        foreach ($this->credentialDefinitions() as $index => $definition) {
            $key = $definition['key'];

            if ($definition['type'] === 'boolean') {
                $value = $request->boolean($key) ? '1' : '0';
            } else {
                $submitted = trim((string) ($validated[$key] ?? ''));
                $value = ($definition['sensitive'] && $submitted === '')
                    ? (string) ($existing[$key] ?? '')
                    : $submitted;
            }

            Setting::setValue(self::CREDENTIALS_GROUP, $key, $value, [
                'type' => $definition['type'],
                'label' => $definition['label'],
                'description' => $definition['description'],
                'is_public' => false,
                'sort_order' => $index + 1,
            ]);
        }

        Setting::clearCache(self::CREDENTIALS_GROUP);

        return redirect()
            ->route('admin.orders.courier-checker', ['tab' => 'settings'])
            ->with('success', 'Courier credentials saved successfully.');
    }

    public function updateAutomation(Request $request)
    {
        $validated = $request->validate([
            'courier_check_enabled' => 'nullable|boolean',
            'courier_check_freshness_days' => 'required|integer|min:1|max:365',
            'courier_check_min_orders' => 'required|integer|min:1|max:100',
            'courier_check_max_cancel_ratio' => 'required|numeric|min:0|max:100',
            'courier_check_action' => 'required|in:flag,auto_block',
        ]);

        $values = [
            'courier_check_enabled' => $request->boolean('courier_check_enabled') ? '1' : '0',
            'courier_check_freshness_days' => (string) $validated['courier_check_freshness_days'],
            'courier_check_min_orders' => (string) $validated['courier_check_min_orders'],
            'courier_check_max_cancel_ratio' => (string) $validated['courier_check_max_cancel_ratio'],
            'courier_check_action' => $validated['courier_check_action'],
        ];

        foreach ($values as $key => $value) {
            Setting::setValue(self::AUTOMATION_GROUP, $key, $value, ['type' => 'string', 'is_public' => false]);
        }

        Setting::clearCache(self::AUTOMATION_GROUP);

        return redirect()
            ->route('admin.orders.courier-checker', ['tab' => 'settings'])
            ->with('success', 'Automation settings saved successfully.');
    }

    private function getAutomationSettings(): array
    {
        return [
            'courier_check_enabled' => filter_var(Setting::getValue(self::AUTOMATION_GROUP, 'courier_check_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'courier_check_freshness_days' => (int) Setting::getValue(self::AUTOMATION_GROUP, 'courier_check_freshness_days', 7),
            'courier_check_min_orders' => (int) Setting::getValue(self::AUTOMATION_GROUP, 'courier_check_min_orders', 3),
            'courier_check_max_cancel_ratio' => (float) Setting::getValue(self::AUTOMATION_GROUP, 'courier_check_max_cancel_ratio', 40),
            'courier_check_action' => Setting::getValue(self::AUTOMATION_GROUP, 'courier_check_action', 'flag'),
        ];
    }

    private function ensureDefaultCredentialSettings(): void
    {
        foreach ($this->credentialDefinitions() as $index => $definition) {
            Setting::firstOrCreate(
                ['group' => self::CREDENTIALS_GROUP, 'key' => $definition['key']],
                [
                    'type' => $definition['type'],
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'value' => $definition['default'],
                    'is_public' => false,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    /**
     * `textarea` fields accept comma- or newline-separated multi-account
     * lists, matching the package's own credential-list parsing exactly —
     * each courier's *_users/*_phones field must stay index-aligned with
     * its *_passwords field. `sensitive` fields are never echoed back to
     * the browser once saved (see updateCredentials()/the view).
     */
    private function credentialDefinitions(): array
    {
        return [
            ['key' => 'pathao_users', 'label' => 'Pathao — Users (email per line)', 'type' => 'textarea', 'default' => '', 'sensitive' => false, 'description' => 'One merchant login email per line. Multiple accounts give resilience against rate limits.'],
            ['key' => 'pathao_passwords', 'label' => 'Pathao — Passwords', 'type' => 'textarea', 'default' => '', 'sensitive' => true, 'description' => 'Passwords, same order as the users above.'],

            ['key' => 'steadfast_users', 'label' => 'Steadfast — Users (email per line)', 'type' => 'textarea', 'default' => '', 'sensitive' => false, 'description' => 'One merchant login email per line.'],
            ['key' => 'steadfast_passwords', 'label' => 'Steadfast — Passwords', 'type' => 'textarea', 'default' => '', 'sensitive' => true, 'description' => 'Passwords, same order as the users above.'],

            ['key' => 'redx_phones', 'label' => 'RedX — Phones (local 11-digit)', 'type' => 'textarea', 'default' => '', 'sensitive' => false, 'description' => 'One merchant login phone number per line, e.g. 01712345678.'],
            ['key' => 'redx_passwords', 'label' => 'RedX — Passwords', 'type' => 'textarea', 'default' => '', 'sensitive' => true, 'description' => 'Passwords, same order as the phones above.'],

            ['key' => 'paperfly_users', 'label' => 'Paperfly — Users', 'type' => 'textarea', 'default' => '', 'sensitive' => false, 'description' => 'One merchant login username per line.'],
            ['key' => 'paperfly_passwords', 'label' => 'Paperfly — Passwords', 'type' => 'textarea', 'default' => '', 'sensitive' => true, 'description' => 'Passwords, same order as the users above.'],

            ['key' => 'carrybee_phones', 'label' => 'Carrybee — Phones (local 11-digit)', 'type' => 'textarea', 'default' => '', 'sensitive' => false, 'description' => 'One merchant login phone number per line.'],
            ['key' => 'carrybee_passwords', 'label' => 'Carrybee — Passwords', 'type' => 'textarea', 'default' => '', 'sensitive' => true, 'description' => 'Passwords, same order as the phones above.'],

            ['key' => 'proxy_all', 'label' => 'Route all couriers through proxy', 'type' => 'boolean', 'default' => '0', 'sensitive' => false, 'description' => 'Overrides the per-courier proxy toggles below.'],
            ['key' => 'proxy_pathao', 'label' => 'Proxy — Pathao', 'type' => 'boolean', 'default' => '0', 'sensitive' => false, 'description' => ''],
            ['key' => 'proxy_steadfast', 'label' => 'Proxy — Steadfast', 'type' => 'boolean', 'default' => '0', 'sensitive' => false, 'description' => ''],
            ['key' => 'proxy_redx', 'label' => 'Proxy — RedX', 'type' => 'boolean', 'default' => '0', 'sensitive' => false, 'description' => ''],
            ['key' => 'proxy_paperfly', 'label' => 'Proxy — Paperfly', 'type' => 'boolean', 'default' => '0', 'sensitive' => false, 'description' => ''],
            ['key' => 'proxy_carrybee', 'label' => 'Proxy — Carrybee', 'type' => 'boolean', 'default' => '0', 'sensitive' => false, 'description' => ''],
            ['key' => 'proxy_address', 'label' => 'Proxy address', 'type' => 'text', 'default' => '', 'sensitive' => false, 'description' => 'e.g. http://127.0.0.1:8080'],
        ];
    }
}

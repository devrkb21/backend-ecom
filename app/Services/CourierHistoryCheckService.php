<?php

namespace App\Services;

use App\Models\CourierCheckResult;
use App\Models\Setting;
use Czbd\CourierChecker\Contracts\CourierServiceInterface;
use Czbd\CourierChecker\CourierCheckerManager;
use Czbd\CourierChecker\Services\CarrybeeService;
use Czbd\CourierChecker\Services\PaperflyService;
use Czbd\CourierChecker\Services\PathaoService;
use Czbd\CourierChecker\Services\RedxService;
use Czbd\CourierChecker\Services\SteadfastService;
use Czbd\CourierChecker\Services\UnavailableCourierService;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around devrkb21/czbd-courier — looks up a local-format BD
 * phone number across all 5 supported couriers and persists the result.
 *
 * Deliberately does NOT use the package's CourierChecker facade: that facade
 * resolves a container *singleton* (CourierCheckerServiceProvider::register())
 * whose constructor reads config('courier-checker.*') once, the first time
 * it's resolved in a process — and a queue worker process handles many jobs
 * without rebooting. Admin-edited credentials (Setting group `courier_check`)
 * would silently stop taking effect after the first run. Instead this
 * service applies the credential override and constructs a fresh
 * CourierCheckerManager (+ fresh per-courier services) on every call,
 * guaranteeing the current Settings values are what get used every time.
 *
 * Used by both CheckCourierHistoryJob (per-order, queued) and the
 * `courier:check-phone` console command (ad-hoc, synchronous).
 */
class CourierHistoryCheckService
{
    /**
     * How long an admin-initiated check (search box, order page "Check")
     * result is served from cache before a repeat check is treated as stale.
     * Separate from courier_check_freshness_days, which governs the
     * background per-order job's own cache window.
     */
    public const SEARCH_CACHE_HOURS = 6;

    /**
     * True once at least one courier has a complete (id + password) account
     * configured. Used to fail fast with a clear "not configured" error
     * instead of burning several seconds making 5 doomed HTTP round-trips.
     */
    public function hasAnyCredentialsConfigured(): bool
    {
        $idFieldByCourier = [
            'pathao' => 'pathao_users',
            'steadfast' => 'steadfast_users',
            'redx' => 'redx_phones',
            'paperfly' => 'paperfly_users',
            'carrybee' => 'carrybee_phones',
        ];

        foreach ($idFieldByCourier as $courier => $idField) {
            $ids = trim((string) Setting::getValue('courier_check', $idField, ''));
            $passwords = trim((string) Setting::getValue('courier_check', "{$courier}_passwords", ''));

            if ($ids !== '' && $passwords !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Admin-facing entry point (search box, order page "Check" button):
     * serves a result already stored within the last SEARCH_CACHE_HOURS
     * without touching any courier, unless $forceRefresh is set — that's
     * what the "Refresh" button uses to bypass the cache and get a live
     * answer regardless of age.
     *
     * @return array{result: CourierCheckResult, from_cache: bool}
     */
    public function checkWithCache(string $normalizedPhone, bool $forceRefresh = false, ?int $orderId = null): array
    {
        if (!$forceRefresh) {
            $existing = CourierCheckResult::where('normalized_phone', $normalizedPhone)->first();

            if ($existing && $existing->isFreshWithinHours(self::SEARCH_CACHE_HOURS)) {
                return ['result' => $existing, 'from_cache' => true];
            }
        }

        return ['result' => $this->check($normalizedPhone, $orderId), 'from_cache' => false];
    }

    public function check(string $normalizedPhone, ?int $orderId = null): CourierCheckResult
    {
        $this->applyCredentialOverrides();

        $manager = new CourierCheckerManager(
            $this->buildService('steadfast', SteadfastService::class),
            $this->buildService('pathao', PathaoService::class),
            $this->buildService('redx', RedxService::class),
            $this->buildService('paperfly', PaperflyService::class),
            $this->buildService('carrybee', CarrybeeService::class),
        );

        $result = $manager->check($normalizedPhone);
        $result = $this->normalizeCarrybeeNewCustomer($result);
        $aggregate = $result['aggregate'] ?? [];

        $couriersOk = 0;
        $couriersFailed = 0;
        foreach (['steadfast', 'pathao', 'redx', 'paperfly', 'carrybee'] as $key) {
            if (isset($result[$key]['error'])) {
                $couriersFailed++;
            } else {
                $couriersOk++;
            }
        }

        return CourierCheckResult::updateOrCreate(
            ['normalized_phone' => $normalizedPhone],
            [
                'raw_result' => $result,
                'total_success' => (int) ($aggregate['total_success'] ?? 0),
                'total_cancel' => (int) ($aggregate['total_cancel'] ?? 0),
                'total_deliveries' => (int) ($aggregate['total_deliveries'] ?? 0),
                'success_ratio' => (float) ($aggregate['success_ratio'] ?? 0),
                'couriers_ok' => $couriersOk,
                'couriers_failed' => $couriersFailed,
                'checked_at' => now(),
                'last_order_id' => $orderId,
            ]
        );
    }

    private function applyCredentialOverrides(): void
    {
        $get = fn (string $key) => Setting::getValue('courier_check', $key, '');
        $yesNo = fn (string $key) => filter_var(Setting::getValue('courier_check', $key, '0'), FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no';

        config([
            'courier-checker.pathao.users' => $get('pathao_users'),
            'courier-checker.pathao.passwords' => $get('pathao_passwords'),
            'courier-checker.steadfast.users' => $get('steadfast_users'),
            'courier-checker.steadfast.passwords' => $get('steadfast_passwords'),
            'courier-checker.redx.phones' => $get('redx_phones'),
            'courier-checker.redx.passwords' => $get('redx_passwords'),
            'courier-checker.paperfly.users' => $get('paperfly_users'),
            'courier-checker.paperfly.passwords' => $get('paperfly_passwords'),
            'courier-checker.carrybee.phones' => $get('carrybee_phones'),
            'courier-checker.carrybee.passwords' => $get('carrybee_passwords'),
            'courier-checker.proxy.all' => $yesNo('proxy_all'),
            'courier-checker.proxy.pathao' => $yesNo('proxy_pathao'),
            'courier-checker.proxy.steadfast' => $yesNo('proxy_steadfast'),
            'courier-checker.proxy.redx' => $yesNo('proxy_redx'),
            'courier-checker.proxy.paperfly' => $yesNo('proxy_paperfly'),
            'courier-checker.proxy.carrybee' => $yesNo('proxy_carrybee'),
            'courier-checker.proxy.address' => $get('proxy_address'),
        ]);
    }

    /**
     * Carrybee returns HTTP 404 (reported by the package as a generic
     * "Failed to fetch from Carrybee" error) when a phone number has no
     * order history at all — that's not a technical failure, it's a clean
     * "0 deliveries" result. Reclassify it so the UI shows 0/0/0% with a
     * "New customer" tag instead of an error row, and so it counts toward
     * couriers_ok rather than couriers_failed.
     */
    private function normalizeCarrybeeNewCustomer(array $result): array
    {
        $carrybee = $result['carrybee'] ?? null;

        if (is_array($carrybee) && isset($carrybee['error']) && (int) ($carrybee['status'] ?? 0) === 404) {
            unset($carrybee['error'], $carrybee['status']);
            $carrybee['success'] = $carrybee['success'] ?? 0;
            $carrybee['cancel'] = $carrybee['cancel'] ?? 0;
            $carrybee['total'] = $carrybee['total'] ?? 0;
            $carrybee['success_ratio'] = $carrybee['success_ratio'] ?? 0;
            $carrybee['new_customer'] = true;
            $result['carrybee'] = $carrybee;
        }

        return $result;
    }

    /**
     * Mirrors CourierCheckerServiceProvider::resolveCourierService() — a
     * courier with missing/misaligned credentials must not blow up the
     * whole check, it just reports itself as unconfigured.
     *
     * @param class-string<CourierServiceInterface> $class
     */
    private function buildService(string $courier, string $class): CourierServiceInterface
    {
        try {
            return new $class();
        } catch (\InvalidArgumentException $e) {
            Log::info("CourierHistoryCheckService: {$courier} not configured, skipping.", [
                'message' => $e->getMessage(),
            ]);

            return new UnavailableCourierService($courier, $e->getMessage());
        }
    }
}

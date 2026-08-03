<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudBlock;
use App\Models\Order;
use App\Support\FraudNormalizer;
use Illuminate\Http\Request;

class FraudBlockController extends Controller
{
    /**
     * Display fraud block list
     */
    public function index(Request $request)
    {
        $query = FraudBlock::with('blockedByUser', 'order')
            ->orderByDesc('created_at');

        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'inactive') {
                $query->where('is_active', false)->where('needs_review', false);
            } elseif ($status === 'needs_review') {
                $query->where('needs_review', true);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('value', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        $blocks = $query->paginate($perPage)->withQueryString();
        $summary = FraudBlock::getSummary();
        $needsReviewCount = FraudBlock::where('needs_review', true)->count();
        $defaults = \App\Models\Setting::getGroup('fraud_blocks', false);
        $automation = $this->getAutomationSettings();

        return view('admin.fraud-blocks.index', compact('blocks', 'summary', 'needsReviewCount', 'defaults', 'automation'));
    }

    /**
     * Store a new fraud block (manual add)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:phone,email,ip,device',
            'value' => 'required|string|max:500',
            'reason' => 'nullable|string|max:500',
            'custom_message' => 'nullable|string|max:1000',
        ]);

        // Duplicate check runs on the normalized form, not the raw string —
        // "+8801712345678" and "01712345678" are the same phone number and
        // must not create two rows that silently don't match each other.
        if (FraudBlock::getBlock($validated['type'], $validated['value']) !== null) {
            $message = 'This value is already blocked for this type.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['value' => $message])->withInput();
        }

        FraudBlock::create([
            'type' => $validated['type'],
            'value' => trim($validated['value']),
            'reason' => $validated['reason'] ?? null,
            'custom_message' => $validated['custom_message'] ?? null,
            'blocked_by' => auth()->id(),
            'is_active' => true,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => ucfirst($validated['type']) . ' blocked successfully.',
            ]);
        }

        return redirect()->route('admin.fraud-blocks.index')
            ->with('success', ucfirst($validated['type']) . ' blocked successfully.');
    }

    /**
     * Quick block from order page (AJAX)
     */
    public function quickBlock(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:phone,email,ip,device',
            'value' => 'required|string|max:500',
            'order_id' => 'nullable|exists:orders,id',
            'reason' => 'nullable|string|max:500',
            'custom_message' => 'nullable|string|max:1000',
        ]);

        $value = trim($validated['value']);
        $type = $validated['type'];
        $normalizedValue = FraudNormalizer::forType($type, $value);

        // Check if already blocked (normalized match, so format variance
        // on the same phone/email/device can't create a duplicate entry)
        $existing = $normalizedValue !== null
            ? FraudBlock::where('type', $type)->where('normalized_value', $normalizedValue)->first()
            : FraudBlock::where('type', $type)->where('value', $value)->first();

        if ($existing) {
            if (!$existing->is_active) {
                $existing->update(['is_active' => true, 'needs_review' => false, 'blocked_by' => auth()->id()]);
                return response()->json([
                    'success' => true,
                    'message' => 'Re-activated existing block.',
                    'action' => 'reactivated',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'This value is already blocked.',
                'action' => 'already_blocked',
            ], 422);
        }

        FraudBlock::create([
            'type' => $type,
            'value' => $value,
            'reason' => $validated['reason'] ?? ('Blocked from Order #' . ($validated['order_id'] ? Order::find($validated['order_id'])?->order_number : 'N/A')),
            'custom_message' => $validated['custom_message'] ?? null,
            'blocked_by' => auth()->id(),
            'order_id' => $validated['order_id'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' blocked successfully.',
            'action' => 'created',
        ]);
    }

    /**
     * Quick unblock (AJAX)
     */
    public function quickUnblock(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:phone,email,ip,device',
            'value' => 'required|string|max:500',
        ]);

        $normalizedValue = FraudNormalizer::forType($validated['type'], $validated['value']);

        $block = $normalizedValue !== null
            ? FraudBlock::where('type', $validated['type'])->where('normalized_value', $normalizedValue)->first()
            : FraudBlock::where('type', $validated['type'])->where('value', trim($validated['value']))->first();

        if ($block) {
            $block->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Unblocked successfully.',
        ]);
    }

    /**
     * Confirm an auto-flagged "needs review" entry: activates the block and
     * clears the review flag.
     */
    public function confirmReview(FraudBlock $fraudBlock)
    {
        $fraudBlock->update(['is_active' => true, 'needs_review' => false, 'blocked_by' => auth()->id()]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Block confirmed and activated.']);
        }

        return redirect()->back()->with('success', 'Block confirmed and activated.');
    }

    /**
     * Dismiss an auto-flagged "needs review" entry as a false positive.
     * Deletes it outright — if the phone crosses the threshold again later,
     * it will simply be re-flagged.
     */
    public function dismissReview(FraudBlock $fraudBlock)
    {
        $fraudBlock->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Flag dismissed.']);
        }

        return redirect()->back()->with('success', 'Flag dismissed.');
    }

    /**
     * Toggle block status
     */
    public function toggle(FraudBlock $fraudBlock)
    {
        $fraudBlock->update(['is_active' => !$fraudBlock->is_active]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $fraudBlock->is_active ? 'Block activated.' : 'Block deactivated.',
                'is_active' => $fraudBlock->is_active,
            ]);
        }

        return redirect()->back()->with('success', $fraudBlock->is_active ? 'Block activated.' : 'Block deactivated.');
    }

    /**
     * Delete a fraud block
     */
    public function destroy(FraudBlock $fraudBlock)
    {
        $fraudBlock->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Block removed successfully.',
            ]);
        }

        return redirect()->route('admin.fraud-blocks.index')
            ->with('success', 'Block removed successfully.');
    }

    /**
     * Check if values are blocked (AJAX, used by order page)
     */
    public function check(Request $request)
    {
        $phone = trim((string) $request->input('phone', ''));
        $email = trim((string) $request->input('email', ''));
        $ip = trim((string) $request->input('ip', ''));
        $device = trim((string) $request->input('device', ''));

        $results = [];
        if ($phone !== '') {
            $results['phone'] = FraudBlock::isBlocked('phone', $phone);
        }
        if ($email !== '') {
            $results['email'] = FraudBlock::isBlocked('email', $email);
        }
        if ($ip !== '') {
            $results['ip'] = FraudBlock::isBlocked('ip', $ip);
        }
        if ($device !== '') {
            $results['device'] = FraudBlock::isBlocked('device', $device);
        }

        return response()->json([
            'success' => true,
            'blocked' => $results,
            'has_any_block' => in_array(true, $results, true),
        ]);
    }

    /**
     * Save default messages settings
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'default_phone_msg' => 'nullable|string|max:1000',
            'default_email_msg' => 'nullable|string|max:1000',
            'default_ip_msg' => 'nullable|string|max:1000',
            'default_device_msg' => 'nullable|string|max:1000',
        ]);

        foreach ($validated as $key => $value) {
            \App\Models\Setting::setValue('fraud_blocks', $key, $value, ['type' => 'string', 'is_public' => false]);
        }

        return redirect()->back()->with('success', 'Default messages saved successfully.');
    }

    /**
     * Save automated fraud-detection settings (order velocity limits,
     * repeat-offender threshold/action).
     */
    public function saveAutomationSettings(Request $request)
    {
        $validated = $request->validate([
            'velocity_enabled' => 'nullable|boolean',
            'velocity_limit_count' => 'required|integer|min:1|max:1000',
            'velocity_limit_window_minutes' => 'required|integer|min:1|max:10080',
            'repeat_offender_enabled' => 'nullable|boolean',
            'repeat_offender_threshold' => 'required|integer|min:1|max:100',
            'repeat_offender_action' => 'required|in:flag,auto_block',
        ]);

        $values = [
            'velocity_enabled' => $request->boolean('velocity_enabled') ? '1' : '0',
            'velocity_limit_count' => (string) $validated['velocity_limit_count'],
            'velocity_limit_window_minutes' => (string) $validated['velocity_limit_window_minutes'],
            'repeat_offender_enabled' => $request->boolean('repeat_offender_enabled') ? '1' : '0',
            'repeat_offender_threshold' => (string) $validated['repeat_offender_threshold'],
            'repeat_offender_action' => $validated['repeat_offender_action'],
        ];

        foreach ($values as $key => $value) {
            \App\Models\Setting::setValue('fraud_blocks', $key, $value, ['type' => 'string', 'is_public' => false]);
        }

        \App\Models\Setting::clearCache('fraud_blocks');

        return redirect()->route('admin.fraud-blocks.index')->with('success', 'Automation settings saved successfully.');
    }

    private function getAutomationSettings(): array
    {
        return [
            'velocity_enabled' => filter_var(\App\Models\Setting::getValue('fraud_blocks', 'velocity_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            'velocity_limit_count' => (int) \App\Models\Setting::getValue('fraud_blocks', 'velocity_limit_count', 5),
            'velocity_limit_window_minutes' => (int) \App\Models\Setting::getValue('fraud_blocks', 'velocity_limit_window_minutes', 60),
            'repeat_offender_enabled' => filter_var(\App\Models\Setting::getValue('fraud_blocks', 'repeat_offender_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            'repeat_offender_threshold' => (int) \App\Models\Setting::getValue('fraud_blocks', 'repeat_offender_threshold', 3),
            'repeat_offender_action' => \App\Models\Setting::getValue('fraud_blocks', 'repeat_offender_action', 'flag'),
        ];
    }
}

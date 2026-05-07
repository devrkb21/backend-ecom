<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudBlock;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
                $query->where('is_active', false);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('value', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $blocks = $query->paginate(25)->withQueryString();
        $summary = FraudBlock::getSummary();
        $defaults = \App\Models\Setting::getGroup('fraud_blocks', false);

        return view('admin.fraud-blocks.index', compact('blocks', 'summary', 'defaults'));
    }

    /**
     * Store a new fraud block (manual add)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:phone,email,ip,device',
            'value' => [
                'required',
                'string',
                'max:500',
                Rule::unique('fraud_blocks')->where(function ($query) use ($request) {
                    return $query->where('type', $request->input('type'));
                }),
            ],
            'reason' => 'nullable|string|max:500',
            'custom_message' => 'nullable|string|max:1000',
        ], [
            'value.unique' => 'This value is already blocked for this type.',
        ]);

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

        // Check if already blocked
        $existing = FraudBlock::where('type', $type)->where('value', $value)->first();

        if ($existing) {
            if (!$existing->is_active) {
                $existing->update(['is_active' => true, 'blocked_by' => auth()->id()]);
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

        $block = FraudBlock::where('type', $validated['type'])
            ->where('value', trim($validated['value']))
            ->first();

        if ($block) {
            $block->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Unblocked successfully.',
        ]);
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
}

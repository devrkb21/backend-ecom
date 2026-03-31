<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    /**
     * Display all payment gateways
     */
    public function index()
    {
        $gateways = PaymentGateway::orderBy('sort_order')->get();
        
        return view('admin.settings.payment-gateways', compact('gateways'));
    }

    /**
     * Toggle gateway status
     */
    public function toggle(PaymentGateway $gateway)
    {
        // For gateways that require configuration, check if they're configured
        if (!$gateway->is_active && $this->requiresConfiguration($gateway)) {
            return back()->with('error', "Please configure {$gateway->name} settings before enabling.");
        }

        $gateway->update(['is_active' => !$gateway->is_active]);

        $status = $gateway->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "{$gateway->name} has been {$status}.");
    }

    /**
     * Show gateway settings form
     */
    public function edit(PaymentGateway $gateway)
    {
        return view('admin.settings.payment-gateway-edit', compact('gateway'));
    }

    /**
     * Update gateway settings
     */
    public function update(Request $request, PaymentGateway $gateway)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'instructions' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ]);

        // Handle gateway-specific settings
        $settings = $gateway->settings ?? [];
        
        switch ($gateway->code) {
            case 'cod':
                $settings['extra_charge'] = $request->input('settings.extra_charge', 0);
                $settings['extra_charge_type'] = $request->input('settings.extra_charge_type', 'fixed');
                break;
                
            case 'stripe':
                $settings['public_key'] = $request->input('settings.public_key', '');
                $settings['secret_key'] = $request->input('settings.secret_key', '');
                $settings['webhook_secret'] = $request->input('settings.webhook_secret', '');
                $settings['mode'] = $request->input('settings.mode', 'test');
                break;
                
            case 'bkash':
                $settings['app_key'] = $request->input('settings.app_key', '');
                $settings['app_secret'] = $request->input('settings.app_secret', '');
                $settings['username'] = $request->input('settings.username', '');
                $settings['password'] = $request->input('settings.password', '');
                $settings['mode'] = $request->input('settings.mode', 'sandbox');
                break;
        }

        $validated['settings'] = $settings;

        $gateway->update($validated);

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', "{$gateway->name} settings updated successfully.");
    }

    /**
     * Update sort order via AJAX
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'gateways' => 'required|array',
            'gateways.*.id' => 'required|exists:payment_gateways,id',
            'gateways.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->gateways as $item) {
            PaymentGateway::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Check if gateway requires configuration before enabling
     */
    private function requiresConfiguration(PaymentGateway $gateway): bool
    {
        switch ($gateway->code) {
            case 'stripe':
                return empty($gateway->getSetting('public_key')) || empty($gateway->getSetting('secret_key'));
            case 'bkash':
                return empty($gateway->getSetting('app_key')) || empty($gateway->getSetting('app_secret'));
            default:
                return false;
        }
    }
}

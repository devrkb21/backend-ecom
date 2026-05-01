<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\Setting;
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
        $currencySymbol = (string) Setting::getValue('general', 'currency_symbol', config('shop.currency_symbol', '৳'));

        return view('admin.settings.payment-gateway-edit', compact('gateway', 'currencySymbol'));
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
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'required|integer|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'settings.extra_charge' => 'nullable|numeric|min:0',
            'settings.extra_charge_type' => 'nullable|in:fixed,percentage',
            'settings.extra_charge_label' => 'nullable|string|max:120',
        ]);

        // Handle gateway-specific settings
        $settings = $gateway->settings ?? [];
        $settings['extra_charge'] = (float) $request->input('settings.extra_charge', 0);
        $settings['extra_charge_type'] = $request->input('settings.extra_charge_type', 'fixed');
        $settings['extra_charge_label'] = trim((string) $request->input('settings.extra_charge_label', ''));
        
        switch ($gateway->code) {
            case 'cod':
                break;
                
            case 'stripe':
                $settings['mode'] = $request->input('settings.mode', 'test');
                $settings['test'] = [
                    'public_key' => $request->input('settings.test.public_key', ''),
                    'secret_key' => $request->input('settings.test.secret_key', ''),
                    'webhook_secret' => $request->input('settings.test.webhook_secret', ''),
                ];
                $settings['live'] = [
                    'public_key' => $request->input('settings.live.public_key', ''),
                    'secret_key' => $request->input('settings.live.secret_key', ''),
                    'webhook_secret' => $request->input('settings.live.webhook_secret', ''),
                ];
                break;
                
            case 'bkash':
                $settings['mode'] = $request->input('settings.mode', 'sandbox');
                $settings['sandbox'] = [
                    'app_key' => $request->input('settings.sandbox.app_key', ''),
                    'app_secret' => $request->input('settings.sandbox.app_secret', ''),
                    'username' => $request->input('settings.sandbox.username', ''),
                    'password' => $request->input('settings.sandbox.password', ''),
                ];
                $settings['live'] = [
                    'app_key' => $request->input('settings.live.app_key', ''),
                    'app_secret' => $request->input('settings.live.app_secret', ''),
                    'username' => $request->input('settings.live.username', ''),
                    'password' => $request->input('settings.live.password', ''),
                ];
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
        $mode = $gateway->getSetting('mode', 'test');
        
        switch ($gateway->code) {
            case 'stripe':
                $env = $mode === 'test' ? 'test' : 'live';
                return empty($gateway->getSetting("{$env}.public_key")) || empty($gateway->getSetting("{$env}.secret_key"));
            case 'bkash':
                $env = $mode === 'sandbox' ? 'sandbox' : 'live';
                return empty($gateway->getSetting("{$env}.app_key")) || empty($gateway->getSetting("{$env}.app_secret"));
            default:
                return false;
        }
    }
}

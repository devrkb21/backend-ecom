<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    /**
     * Get all active payment gateways for checkout
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $amount = $request->query('amount');
        $currency = $request->query('currency', 'BDT');

        $gateways = PaymentGateway::getActive();
        
        // Only filter by amount/currency if amount is provided
        if ($amount !== null && $amount > 0) {
            $gateways = $gateways->filter(fn(PaymentGateway $gateway) => $gateway->isAvailableFor((float) $amount, $currency));
        }
        
        $gateways = $gateways->map(fn(PaymentGateway $gateway) => [
                'code' => $gateway->code,
                'name' => $gateway->name,
                'description' => $gateway->description,
                'instructions' => $gateway->instructions,
                'icon' => $gateway->icon,
                'requires_redirect' => $gateway->requiresRedirect(),
                'is_pay_on_delivery' => $gateway->isPayOnDelivery(),
                'min_amount' => $gateway->min_amount,
                'max_amount' => $gateway->max_amount,
                'extra_charge' => $amount ? $this->calculateExtraCharge($gateway, (float) $amount) : null,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $gateways,
        ]);
    }

    /**
     * Get a specific payment gateway details
     * 
     * @param string $code
     * @return JsonResponse
     */
    public function show(string $code): JsonResponse
    {
        $gateway = PaymentGateway::findByCode($code);

        if (!$gateway || !$gateway->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Payment gateway not found or inactive',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $gateway->code,
                'name' => $gateway->name,
                'description' => $gateway->description,
                'instructions' => $gateway->instructions,
                'icon' => $gateway->icon,
                'requires_redirect' => $gateway->requiresRedirect(),
                'is_pay_on_delivery' => $gateway->isPayOnDelivery(),
                'min_amount' => $gateway->min_amount,
                'max_amount' => $gateway->max_amount,
                'supported_currencies' => $gateway->supported_currencies,
            ],
        ]);
    }

    /**
     * Calculate gateway extra charge from payment gateway settings.
     */
    private function calculateExtraCharge(PaymentGateway $gateway, float $amount): ?array
    {
        $extraCharge = (float) $gateway->getSetting('extra_charge', 0);
        $chargeType = $gateway->getSetting('extra_charge_type', 'fixed');
        $customLabel = trim((string) $gateway->getSetting('extra_charge_label', ''));

        if ($extraCharge <= 0) {
            return null;
        }

        $label = $customLabel !== '' ? $customLabel : "{$gateway->name} gateway charge";

        if ($chargeType === 'percentage') {
            return [
                'type' => 'percentage',
                'value' => $extraCharge,
                'calculated' => round($amount * ($extraCharge / 100), 2),
                'label' => $label,
            ];
        }

        return [
            'type' => 'fixed',
            'value' => $extraCharge,
            'calculated' => $extraCharge,
            'label' => $label,
        ];
    }
}

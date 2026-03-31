<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    /**
     * Get available shipping methods
     */
    public function index(Request $request): JsonResponse
    {
        $methods = ShippingMethod::getActive();

        // Filter by order amount if provided
        $amount = $request->get('amount');
        $weight = $request->get('weight');
        $country = $request->get('country');
        $itemCount = $request->get('item_count', 1);

        if ($amount || $weight || $country) {
            $methods = $methods->filter(function ($method) use ($amount, $weight, $country) {
                return $method->isAvailableFor(
                    (float) ($amount ?? 0),
                    $weight ? (float) $weight : null,
                    $country
                );
            });
        }

        $data = $methods->map(function ($method) use ($amount, $itemCount, $weight) {
            $cost = $method->calculateCost(
                (float) ($amount ?? 0),
                (int) $itemCount,
                (float) ($weight ?? 0)
            );

            return [
                'code' => $method->code,
                'name' => $method->name,
                'description' => $method->description,
                'cost' => $cost,
                'formatted_cost' => $cost > 0 ? '৳' . number_format($cost, 2) : 'Free',
                'delivery_estimate' => $method->getDeliveryEstimate(),
                'min_delivery_days' => $method->min_delivery_days,
                'max_delivery_days' => $method->max_delivery_days,
                'free_shipping_threshold' => $method->free_shipping_threshold,
                'is_free' => $cost === 0.0,
            ];
        })->values();

        return $this->successResponse($data);
    }

    /**
     * Get specific shipping method details
     */
    public function show(string $code): JsonResponse
    {
        $method = ShippingMethod::findByCode($code);

        if (!$method) {
            return $this->errorResponse('Shipping method not found.', 404);
        }

        if (!$method->is_active) {
            return $this->errorResponse('This shipping method is not available.', 404);
        }

        return $this->successResponse([
            'code' => $method->code,
            'name' => $method->name,
            'description' => $method->description,
            'base_cost' => (float) $method->base_cost,
            'cost_per_item' => (float) $method->cost_per_item,
            'cost_per_kg' => (float) $method->cost_per_kg,
            'free_shipping_threshold' => $method->free_shipping_threshold ? (float) $method->free_shipping_threshold : null,
            'min_order_amount' => $method->min_order_amount ? (float) $method->min_order_amount : null,
            'max_order_amount' => $method->max_order_amount ? (float) $method->max_order_amount : null,
            'max_weight' => $method->max_weight ? (float) $method->max_weight : null,
            'delivery_estimate' => $method->getDeliveryEstimate(),
            'min_delivery_days' => $method->min_delivery_days,
            'max_delivery_days' => $method->max_delivery_days,
            'allowed_countries' => $method->allowed_countries,
            'excluded_countries' => $method->excluded_countries,
        ]);
    }

    /**
     * Calculate shipping cost
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'shipping_method' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'item_count' => ['nullable', 'integer', 'min:1'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $method = ShippingMethod::findByCode($request->shipping_method);

        if (!$method || !$method->is_active) {
            return $this->errorResponse('Shipping method not available.', 404);
        }

        // Check if available for this order
        if (!$method->isAvailableFor($request->amount, $request->weight, $request->country)) {
            return $this->errorResponse('This shipping method is not available for your order.', 400);
        }

        $cost = $method->calculateCost(
            (float) $request->amount,
            (int) ($request->item_count ?? 1),
            (float) ($request->weight ?? 0)
        );

        return $this->successResponse([
            'shipping_method' => $method->code,
            'cost' => $cost,
            'formatted_cost' => $cost > 0 ? '৳' . number_format($cost, 2) : 'Free',
            'is_free' => $cost === 0.0,
            'delivery_estimate' => $method->getDeliveryEstimate(),
        ]);
    }
}

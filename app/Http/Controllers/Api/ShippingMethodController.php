<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Services\BangladeshLocationResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    public function __construct(
        protected BangladeshLocationResolver $locationResolver
    ) {}

    /**
     * Get available shipping methods
     */
    public function index(Request $request): JsonResponse
    {
        $methods = ShippingMethod::query()
            ->with('locationRules')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'item_count' => ['nullable', 'integer', 'min:1'],
            'division_id' => ['nullable', 'integer', 'exists:bd_divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:bd_districts,id'],
            'upazila_id' => ['nullable', 'integer', 'exists:bd_upazilas,id'],
            'location_text' => ['nullable', 'string', 'max:255'],
        ]);

        // Filter by order amount if provided
        $amount = $validated['amount'] ?? null;
        $weight = $validated['weight'] ?? null;
        $itemCount = (int) ($validated['item_count'] ?? 1);
        $divisionId = isset($validated['division_id']) ? (int) $validated['division_id'] : null;
        $districtId = isset($validated['district_id']) ? (int) $validated['district_id'] : null;
        $upazilaId = isset($validated['upazila_id']) ? (int) $validated['upazila_id'] : null;
        $locationText = trim((string) ($validated['location_text'] ?? ''));

        $locationResolution = null;
        if ($locationText !== '') {
            $locationResolution = $this->locationResolver->resolve(
                $locationText,
                $divisionId,
                $districtId,
                $upazilaId
            );

            $divisionId = $divisionId ?? ($locationResolution['division_id'] ?? null);
            $districtId = $districtId ?? ($locationResolution['district_id'] ?? null);
            $upazilaId = $upazilaId ?? ($locationResolution['upazila_id'] ?? null);
        }

        $hasLocationInput = $divisionId || $districtId || $upazilaId;
        $hasLocationText = $locationText !== '';

        if ($amount || $weight || $hasLocationInput || $hasLocationText) {
            $methods = $methods->filter(function (ShippingMethod $method) use ($amount, $weight, $divisionId, $districtId, $upazilaId) {
                if (!$divisionId && !$districtId && !$upazilaId && $method->locationRules->isNotEmpty()) {
                    return false;
                }

                return $method->isAvailableFor(
                    (float) ($amount ?? 0),
                    $weight ? (float) $weight : null,
                    'BD',
                    $divisionId,
                    $districtId,
                    $upazilaId
                );
            });
        }

        $data = $methods->map(function (ShippingMethod $method) use ($amount, $itemCount, $weight, $divisionId, $districtId, $upazilaId, $locationResolution, $locationText) {
            $cost = $method->calculateCost(
                (float) ($amount ?? 0),
                (int) $itemCount,
                (float) ($weight ?? 0),
                $districtId
            );

            return [
                'code' => $method->code,
                'name' => $method->name,
                'description' => $method->description,
                'cost' => $cost,
                'formatted_cost' => $cost > 0 ? '৳' . number_format($cost, 2) : 'Free',
                'division_id' => $divisionId,
                'district_id' => $districtId,
                'upazila_id' => $upazilaId,
                'location_text' => $locationText !== '' ? $locationText : null,
                'location_resolution' => $locationResolution,
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
            'bangladesh_only' => true,
            'location_rules' => [
                'divisions' => $method->locationRules()->where('location_type', 'division')->pluck('location_id')->values(),
                'districts' => $method->locationRules()->where('location_type', 'district')->pluck('location_id')->values(),
                'upazilas' => $method->locationRules()->where('location_type', 'upazila')->pluck('location_id')->values(),
            ],
        ]);
    }

    /**
     * Calculate shipping cost
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_method' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'item_count' => ['nullable', 'integer', 'min:1'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'division_id' => ['nullable', 'integer', 'exists:bd_divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:bd_districts,id'],
            'upazila_id' => ['nullable', 'integer', 'exists:bd_upazilas,id'],
            'location_text' => ['nullable', 'string', 'max:255'],
        ]);

        $divisionId = isset($validated['division_id']) ? (int) $validated['division_id'] : null;
        $districtId = isset($validated['district_id']) ? (int) $validated['district_id'] : null;
        $upazilaId = isset($validated['upazila_id']) ? (int) $validated['upazila_id'] : null;
        $locationText = trim((string) ($validated['location_text'] ?? ''));

        $locationResolution = null;
        if ($locationText !== '') {
            $locationResolution = $this->locationResolver->resolve(
                $locationText,
                $divisionId,
                $districtId,
                $upazilaId
            );

            $divisionId = $divisionId ?? ($locationResolution['division_id'] ?? null);
            $districtId = $districtId ?? ($locationResolution['district_id'] ?? null);
            $upazilaId = $upazilaId ?? ($locationResolution['upazila_id'] ?? null);
        }

        $method = ShippingMethod::findByCode($validated['shipping_method']);

        if (!$method || !$method->is_active) {
            return $this->errorResponse('Shipping method not available.', 404);
        }

        if (!$divisionId && !$districtId && !$upazilaId && $method->locationRules()->exists()) {
            return $this->errorResponse('Could not resolve location from text. Please select division, district and upazila.', 422);
        }

        // Check if available for this order
        if (!$method->isAvailableFor(
            (float) $validated['amount'],
            !empty($validated['weight']) ? (float) $validated['weight'] : null,
            'BD',
            $divisionId,
            $districtId,
            $upazilaId
        )) {
            return $this->errorResponse('This shipping method is not available for your order.', 400);
        }

        $cost = $method->calculateCost(
            (float) $validated['amount'],
            (int) ($validated['item_count'] ?? 1),
            (float) ($validated['weight'] ?? 0),
            $districtId
        );

        return $this->successResponse([
            'shipping_method' => $method->code,
            'division_id' => $divisionId,
            'district_id' => $districtId,
            'upazila_id' => $upazilaId,
            'location_text' => $locationText !== '' ? $locationText : null,
            'location_resolution' => $locationResolution,
            'cost' => $cost,
            'formatted_cost' => $cost > 0 ? '৳' . number_format($cost, 2) : 'Free',
            'is_free' => $cost === 0.0,
            'delivery_estimate' => $method->getDeliveryEstimate(),
        ]);
    }
}

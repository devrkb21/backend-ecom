<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUpazila;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingMethodController extends Controller
{
    public function index(): View
    {
        $methods = ShippingMethod::withCount('locationRules')
            ->orderBy('sort_order')
            ->get();

        return view('admin.settings.shipping-methods', compact('methods'));
    }

    public function create(): View
    {
        return view('admin.settings.shipping-method-form', [
            'method' => null,
            'divisions' => $this->getDivisions(),
            'districts' => $this->getDistricts(),
            'upazilas' => $this->getUpazilas(),
            'selectedDivisionIds' => [],
            'selectedDistrictIds' => [],
            'selectedUpazilaIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:shipping_methods,code', 'regex:/^[a-z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_cost' => ['required', 'numeric', 'min:0'],
            'cost_per_item' => ['nullable', 'numeric', 'min:0'],
            'cost_per_kg' => ['nullable', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_weight' => ['nullable', 'numeric', 'min:0'],
            'min_delivery_days' => ['nullable', 'integer', 'min:0'],
            'max_delivery_days' => ['nullable', 'integer', 'min:0'],
            'allowed_division_ids' => ['nullable', 'array'],
            'allowed_division_ids.*' => ['integer', 'exists:bd_divisions,id'],
            'allowed_district_ids' => ['nullable', 'array'],
            'allowed_district_ids.*' => ['integer', 'exists:bd_districts,id'],
            'allowed_upazila_ids' => ['nullable', 'array'],
            'allowed_upazila_ids.*' => ['integer', 'exists:bd_upazilas,id'],
            'is_active' => ['nullable'],
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['cost_per_item'] = $validated['cost_per_item'] ?? 0;
        $validated['cost_per_kg'] = $validated['cost_per_kg'] ?? 0;
        $validated['allowed_countries'] = null;
        $validated['excluded_countries'] = null;

        $locationPayload = [
            'division' => array_map('intval', (array) ($validated['allowed_division_ids'] ?? [])),
            'district' => array_map('intval', (array) ($validated['allowed_district_ids'] ?? [])),
            'upazila' => array_map('intval', (array) ($validated['allowed_upazila_ids'] ?? [])),
        ];

        unset($validated['allowed_division_ids'], $validated['allowed_district_ids'], $validated['allowed_upazila_ids']);

        $method = ShippingMethod::create($validated);
        $this->syncLocationRules($method, $locationPayload);

        return redirect()
            ->route('admin.settings.shipping-methods')
            ->with('success', 'Shipping method created successfully.');
    }

    public function edit(ShippingMethod $method): View
    {
        [$selectedDivisionIds, $selectedDistrictIds, $selectedUpazilaIds] = $this->getSelectedLocationIds($method);

        return view('admin.settings.shipping-method-form', [
            'method' => $method,
            'divisions' => $this->getDivisions(),
            'districts' => $this->getDistricts(),
            'upazilas' => $this->getUpazilas(),
            'selectedDivisionIds' => $selectedDivisionIds,
            'selectedDistrictIds' => $selectedDistrictIds,
            'selectedUpazilaIds' => $selectedUpazilaIds,
        ]);
    }

    public function update(Request $request, ShippingMethod $method): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:shipping_methods,code,'.$method->id, 'regex:/^[a-z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_cost' => ['required', 'numeric', 'min:0'],
            'cost_per_item' => ['nullable', 'numeric', 'min:0'],
            'cost_per_kg' => ['nullable', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_weight' => ['nullable', 'numeric', 'min:0'],
            'min_delivery_days' => ['nullable', 'integer', 'min:0'],
            'max_delivery_days' => ['nullable', 'integer', 'min:0'],
            'allowed_division_ids' => ['nullable', 'array'],
            'allowed_division_ids.*' => ['integer', 'exists:bd_divisions,id'],
            'allowed_district_ids' => ['nullable', 'array'],
            'allowed_district_ids.*' => ['integer', 'exists:bd_districts,id'],
            'allowed_upazila_ids' => ['nullable', 'array'],
            'allowed_upazila_ids.*' => ['integer', 'exists:bd_upazilas,id'],
            'is_active' => ['nullable'],
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['cost_per_item'] = $validated['cost_per_item'] ?? 0;
        $validated['cost_per_kg'] = $validated['cost_per_kg'] ?? 0;
        $validated['allowed_countries'] = null;
        $validated['excluded_countries'] = null;

        $locationPayload = [
            'division' => array_map('intval', (array) ($validated['allowed_division_ids'] ?? [])),
            'district' => array_map('intval', (array) ($validated['allowed_district_ids'] ?? [])),
            'upazila' => array_map('intval', (array) ($validated['allowed_upazila_ids'] ?? [])),
        ];

        unset($validated['allowed_division_ids'], $validated['allowed_district_ids'], $validated['allowed_upazila_ids']);

        $method->update($validated);
        $this->syncLocationRules($method, $locationPayload);

        return redirect()
            ->route('admin.settings.shipping-methods')
            ->with('success', 'Shipping method updated successfully.');
    }

    public function destroy(ShippingMethod $method): RedirectResponse
    {
        $method->delete();

        return redirect()
            ->route('admin.settings.shipping-methods')
            ->with('success', 'Shipping method deleted successfully.');
    }

    public function toggle(ShippingMethod $method): RedirectResponse
    {
        $method->update(['is_active' => ! $method->is_active]);

        $status = $method->is_active ? 'enabled' : 'disabled';

        return redirect()
            ->route('admin.settings.shipping-methods')
            ->with('success', "{$method->name} has been {$status}.");
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:shipping_methods,id'],
        ]);

        foreach ($request->order as $index => $id) {
            ShippingMethod::where('id', $id)->update(['sort_order' => $index]);
        }

        return redirect()
            ->route('admin.settings.shipping-methods')
            ->with('success', 'Order updated successfully.');
    }

    protected function getDivisions()
    {
        return BdDivision::query()
            ->orderBy('name')
            ->get(['id', 'name', 'bn_name']);
    }

    protected function getDistricts()
    {
        return BdDistrict::query()
            ->with('division:id,name')
            ->orderBy('division_id')
            ->orderBy('name')
            ->get();
    }

    protected function getUpazilas()
    {
        return BdUpazila::query()
            ->with('district:id,name,division_id')
            ->orderBy('district_id')
            ->orderBy('name')
            ->get();
    }

    protected function getSelectedLocationIds(ShippingMethod $method): array
    {
        $rules = $method->locationRules()->get(['location_type', 'location_id']);

        return [
            $rules->where('location_type', 'division')->pluck('location_id')->map(fn ($id) => (int) $id)->values()->all(),
            $rules->where('location_type', 'district')->pluck('location_id')->map(fn ($id) => (int) $id)->values()->all(),
            $rules->where('location_type', 'upazila')->pluck('location_id')->map(fn ($id) => (int) $id)->values()->all(),
        ];
    }

    protected function syncLocationRules(ShippingMethod $method, array $locationPayload): void
    {
        $method->locationRules()->delete();

        $rows = [];
        $now = now();

        foreach (['division', 'district', 'upazila'] as $type) {
            foreach (array_unique($locationPayload[$type] ?? []) as $locationId) {
                if ($locationId <= 0) {
                    continue;
                }

                $rows[] = [
                    'shipping_method_id' => $method->id,
                    'location_type' => $type,
                    'location_id' => (int) $locationId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($rows)) {
            $method->locationRules()->insert($rows);
        }
    }
}

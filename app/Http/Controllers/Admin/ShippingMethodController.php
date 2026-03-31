<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShippingMethodController extends Controller
{
    public function index(): View
    {
        $methods = ShippingMethod::orderBy('sort_order')->get();

        return view('admin.settings.shipping-methods', compact('methods'));
    }

    public function create(): View
    {
        return view('admin.settings.shipping-method-form', [
            'method' => null,
            'countries' => $this->getCountryList(),
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
            'allowed_countries' => ['nullable', 'array'],
            'excluded_countries' => ['nullable', 'array'],
            'is_active' => ['nullable'],
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['cost_per_item'] = $validated['cost_per_item'] ?? 0;
        $validated['cost_per_kg'] = $validated['cost_per_kg'] ?? 0;

        ShippingMethod::create($validated);

        return redirect()
            ->route('admin.settings.shipping-methods')
            ->with('success', 'Shipping method created successfully.');
    }

    public function edit(ShippingMethod $method): View
    {
        return view('admin.settings.shipping-method-form', [
            'method' => $method,
            'countries' => $this->getCountryList(),
        ]);
    }

    public function update(Request $request, ShippingMethod $method): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:shipping_methods,code,' . $method->id, 'regex:/^[a-z0-9_-]+$/'],
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
            'allowed_countries' => ['nullable', 'array'],
            'excluded_countries' => ['nullable', 'array'],
            'is_active' => ['nullable'],
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['cost_per_item'] = $validated['cost_per_item'] ?? 0;
        $validated['cost_per_kg'] = $validated['cost_per_kg'] ?? 0;

        $method->update($validated);

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
        $method->update(['is_active' => !$method->is_active]);

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

    protected function getCountryList(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'AT' => 'Austria',
            'CH' => 'Switzerland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'IE' => 'Ireland',
            'NZ' => 'New Zealand',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'SG' => 'Singapore',
            'HK' => 'Hong Kong',
            'CN' => 'China',
            'IN' => 'India',
            'BD' => 'Bangladesh',
            'PK' => 'Pakistan',
            'BR' => 'Brazil',
            'MX' => 'Mexico',
            'AR' => 'Argentina',
            'CL' => 'Chile',
            'CO' => 'Colombia',
            'ZA' => 'South Africa',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'IL' => 'Israel',
            'TR' => 'Turkey',
            'RU' => 'Russia',
            'PL' => 'Poland',
            'CZ' => 'Czech Republic',
            'HU' => 'Hungary',
            'RO' => 'Romania',
            'GR' => 'Greece',
            'PT' => 'Portugal',
            'TH' => 'Thailand',
            'MY' => 'Malaysia',
            'PH' => 'Philippines',
            'ID' => 'Indonesia',
            'VN' => 'Vietnam',
        ];
    }
}

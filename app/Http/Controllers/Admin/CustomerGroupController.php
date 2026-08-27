<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function index()
    {
        $groups = CustomerGroup::orderBy('sort_order', 'asc')->get();

        return view('admin.customer-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.customer-groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:customer_groups',
            'description' => 'nullable|string',
            'min_order_count' => 'required|integer|min:0',
            'min_total_spent' => 'required|numeric|min:0',
            'manual_numbers' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'custom_message' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        CustomerGroup::create($validated);

        return redirect()->route('admin.customer-groups.index')
            ->with('success', 'Customer Group created successfully.');
    }

    public function edit(CustomerGroup $customerGroup)
    {
        return view('admin.customer-groups.edit', compact('customerGroup'));
    }

    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:customer_groups,name,'.$customerGroup->id,
            'description' => 'nullable|string',
            'min_order_count' => 'required|integer|min:0',
            'min_total_spent' => 'required|numeric|min:0',
            'manual_numbers' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'custom_message' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $customerGroup->update($validated);

        return redirect()->route('admin.customer-groups.index')
            ->with('success', 'Customer Group updated successfully.');
    }

    public function destroy(CustomerGroup $customerGroup)
    {
        $customerGroup->delete();

        return redirect()->route('admin.customer-groups.index')
            ->with('success', 'Customer Group deleted successfully.');
    }
}

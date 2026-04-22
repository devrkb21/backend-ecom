<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    /**
     * Display a listing of coupons
     */
    public function index(Request $request)
    {
        $query = Coupon::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            switch ($status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'expired':
                    $query->where('expires_at', '<', now());
                    break;
                case 'scheduled':
                    $query->where('starts_at', '>', now());
                    break;
            }
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by guest checkout eligibility
        if ($guestEligibility = $request->input('guest_eligibility')) {
            if ($guestEligibility === 'allowed') {
                $query->where('allow_guest_checkout', true);
            }

            if ($guestEligibility === 'login_required') {
                $query->where('allow_guest_checkout', false);
            }
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $coupons = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    /**
     * Show the form for creating a new coupon
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.coupons.create', compact('categories', 'products'));
    }

    /**
     * Store a newly created coupon
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0.01',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'free_shipping' => 'boolean',
            'allow_guest_checkout' => 'boolean',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'exists:categories,id',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'excluded_products' => 'nullable|array',
            'excluded_products.*' => 'exists:products,id',
        ]);

        // Uppercase the code
        $validated['code'] = strtoupper($validated['code']);

        // Set defaults for booleans
        $validated['is_active'] = $request->boolean('is_active');
        $validated['free_shipping'] = $request->boolean('free_shipping');
        $validated['allow_guest_checkout'] = $request->boolean('allow_guest_checkout');

        // Validate percentage max value
        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return back()->withInput()->withErrors(['value' => 'Percentage discount cannot exceed 100%.']);
        }

        Coupon::create($validated);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon created successfully.');
    }

    /**
     * Display the specified coupon
     */
    public function show(Request $request, Coupon $coupon)
    {
        $coupon->load(['usages.user', 'usages.order']);

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $usages = $coupon->usages()->with(['user', 'order'])->latest()->paginate($perPage)->withQueryString();
        
        return view('admin.coupons.show', compact('coupon', 'usages'));
    }

    /**
     * Show the form for editing the specified coupon
     */
    public function edit(Coupon $coupon)
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.coupons.edit', compact('coupon', 'categories', 'products'));
    }

    /**
     * Update the specified coupon
     */
    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons')->ignore($coupon->id)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0.01',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'free_shipping' => 'boolean',
            'allow_guest_checkout' => 'boolean',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'exists:categories,id',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'excluded_products' => 'nullable|array',
            'excluded_products.*' => 'exists:products,id',
        ]);

        // Uppercase the code
        $validated['code'] = strtoupper($validated['code']);

        // Set defaults for booleans
        $validated['is_active'] = $request->boolean('is_active');
        $validated['free_shipping'] = $request->boolean('free_shipping');
        $validated['allow_guest_checkout'] = $request->boolean('allow_guest_checkout');

        // Handle null arrays
        $validated['applicable_categories'] = $validated['applicable_categories'] ?? null;
        $validated['applicable_products'] = $validated['applicable_products'] ?? null;
        $validated['excluded_products'] = $validated['excluded_products'] ?? null;

        // Validate percentage max value
        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return back()->withInput()->withErrors(['value' => 'Percentage discount cannot exceed 100%.']);
        }

        $coupon->update($validated);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon updated successfully.');
    }

    /**
     * Remove the specified coupon
     */
    public function destroy(Coupon $coupon)
    {
        // Check if coupon has been used
        if ($coupon->used_count > 0) {
            // Soft delete by deactivating instead
            $coupon->update(['is_active' => false]);
            return redirect()
                ->route('admin.coupons.index')
                ->with('info', 'Coupon has been deactivated as it has existing usage records.');
        }

        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon deleted successfully.');
    }

    /**
     * Toggle coupon status
     */
    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update(['is_active' => !$coupon->is_active]);

        $status = $coupon->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Coupon {$status} successfully.");
    }

    /**
     * Duplicate a coupon
     */
    public function duplicate(Coupon $coupon)
    {
        $newCoupon = $coupon->replicate();
        $newCoupon->code = $coupon->code . '_COPY';
        $newCoupon->used_count = 0;
        $newCoupon->is_active = false;
        $newCoupon->save();

        return redirect()
            ->route('admin.coupons.edit', $newCoupon)
            ->with('success', 'Coupon duplicated. Please update the code and activate it.');
    }
}

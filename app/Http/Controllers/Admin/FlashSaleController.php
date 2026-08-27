<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Services\FlashSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FlashSaleController extends Controller
{
    public function __construct(
        protected FlashSaleService $flashSaleService
    ) {}

    public function index(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = FlashSale::with('flashSaleProducts')
            ->orderBy('created_at', 'desc');

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'upcoming' || $status === 'scheduled') {
            $query->upcoming();
        } elseif ($status === 'ended') {
            $query->where('ends_at', '<', now());
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $flashSales = $query->paginate($perPage)->withQueryString();

        // Calculate stats
        $stats = [
            'active' => FlashSale::active()->count(),
            'scheduled' => FlashSale::upcoming()->count(),
            'total_sold' => FlashSaleProduct::sum('sold_count'),
            'total_revenue' => FlashSaleProduct::selectRaw('SUM(sold_count * flash_price) as revenue')->value('revenue') ?? 0,
        ];

        return view('admin.flash-sales.index', compact('flashSales', 'status', 'stats'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.flash-sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:flash_sales,slug',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|string|max:500',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'priority' => 'integer|min:0',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.flash_price' => 'required|numeric|min:0',
            'products.*.quantity_limit' => 'nullable|integer|min:1',
            'products.*.per_user_limit' => 'integer|min:1',
        ]);

        $flashSale = $this->flashSaleService->createFlashSale($validated);

        return redirect()
            ->route('admin.flash-sales.show', $flashSale)
            ->with('success', 'Flash sale created successfully!');
    }

    public function show(FlashSale $flashSale)
    {
        $flashSale->load(['flashSaleProducts.product.images', 'flashSaleProducts.product.category']);

        // Get products not in this flash sale for add modal
        $existingProductIds = $flashSale->flashSaleProducts->pluck('product_id')->toArray();
        $availableProducts = Product::where('is_active', true)
            ->whereNotIn('id', $existingProductIds)
            ->orderBy('name')
            ->get();

        return view('admin.flash-sales.show', compact('flashSale', 'availableProducts'));
    }

    public function edit(FlashSale $flashSale)
    {
        $flashSale->load('flashSaleProducts');
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('admin.flash-sales.edit', compact('flashSale', 'products'));
    }

    public function update(Request $request, FlashSale $flashSale)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'priority' => 'integer|min:0',
        ]);

        $flashSale->update($validated);

        return redirect()
            ->route('admin.flash-sales.show', $flashSale)
            ->with('success', 'Flash sale updated successfully!');
    }

    public function destroy(FlashSale $flashSale)
    {
        $flashSale->delete();

        return redirect()
            ->route('admin.flash-sales.index')
            ->with('success', 'Flash sale deleted successfully!');
    }

    public function addProduct(Request $request, FlashSale $flashSale)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'flash_price' => 'required|numeric|min:0',
            'quantity_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'integer|min:1',
        ]);

        $product = Product::find($validated['product_id']);

        FlashSaleProduct::updateOrCreate(
            [
                'flash_sale_id' => $flashSale->id,
                'product_id' => $product->id,
            ],
            [
                'flash_price' => $validated['flash_price'],
                'original_price' => $product->regular_price,
                'discount_percentage' => round((($product->regular_price - $validated['flash_price']) / $product->regular_price) * 100),
                'quantity_limit' => $validated['quantity_limit'] ?? null,
                'per_user_limit' => $validated['per_user_limit'] ?? 1,
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Product added to flash sale!');
    }

    public function removeProduct(FlashSale $flashSale, int $productId)
    {
        FlashSaleProduct::where('flash_sale_id', $flashSale->id)
            ->where('product_id', $productId)
            ->delete();

        return back()->with('success', 'Product removed from flash sale!');
    }

    public function toggleProduct(FlashSale $flashSale, FlashSaleProduct $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', 'Product status updated!');
    }

    public function endEarly(FlashSale $flashSale)
    {
        $this->flashSaleService->endFlashSale($flashSale);

        return back()->with('success', 'Flash sale ended!');
    }

    public function extend(Request $request, FlashSale $flashSale)
    {
        $validated = $request->validate([
            'hours' => 'required|integer|min:1',
        ]);

        $newEndDate = $flashSale->ends_at->addHours($validated['hours']);
        $this->flashSaleService->extendFlashSale($flashSale, $newEndDate);

        return back()->with('success', 'Flash sale extended by '.$validated['hours'].' hours!');
    }

    public function duplicate(FlashSale $flashSale)
    {
        $newSale = $flashSale->replicate();
        $newSale->name = $flashSale->name.' (Copy)';
        $newSale->slug = Str::slug($newSale->name);
        $newSale->starts_at = now()->addDay();
        $newSale->ends_at = now()->addDays(2);
        $newSale->is_active = false;
        $newSale->save();

        // Copy products
        foreach ($flashSale->flashSaleProducts as $fsp) {
            $newSale->flashSaleProducts()->create([
                'product_id' => $fsp->product_id,
                'flash_price' => $fsp->flash_price,
                'original_price' => $fsp->original_price,
                'discount_percentage' => $fsp->discount_percentage,
                'quantity_limit' => $fsp->quantity_limit,
                'per_user_limit' => $fsp->per_user_limit,
                'is_active' => true,
                'sold_count' => 0,
            ]);
        }

        return redirect()
            ->route('admin.flash-sales.edit', $newSale)
            ->with('success', 'Flash sale duplicated! Please update the dates and activate.');
    }
}

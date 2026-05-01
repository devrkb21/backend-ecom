@extends('admin.layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-funnel me-2"></i>Search & Filters</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.products.index') }}" method="GET">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Name, SKU, description...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Category</label>
                    <select class="form-select form-select-sm" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select class="form-select form-select-sm" name="is_active">
                        <option value="">All</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Stock</label>
                    <select class="form-select form-select-sm" name="stock">
                        <option value="">All</option>
                        <option value="low" {{ request('stock') === 'low' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out" {{ request('stock') === 'out' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="form-check form-check-inline">
                        <input type="checkbox" class="form-check-input" name="is_featured" value="1" {{ request('is_featured') ? 'checked' : '' }}>
                        <label class="form-check-label"><i class="bi bi-star text-warning"></i> Featured</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="checkbox" class="form-check-input" name="is_new" value="1" {{ request('is_new') ? 'checked' : '' }}>
                        <label class="form-check-label"><i class="bi bi-tag text-success"></i> New</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="checkbox" class="form-check-input" name="is_bestseller" value="1" {{ request('is_bestseller') ? 'checked' : '' }}>
                        <label class="form-check-label"><i class="bi bi-trophy text-danger"></i> Bestseller</label>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2"></i>All Products ({{ $products->total() }})</h6>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus"></i> Add Product
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Flags</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                            $frontendBaseUrl = rtrim(config('app.frontend_url', url('/')), '/');
                            $frontendProductUrl = $frontendBaseUrl . '/products/' . rawurlencode($product->slug ?? (string) $product->id);
                            $pricing = $product->resolveGlobalPricingSnapshot();
                            $effectiveStock = $product->hasActiveVariants()
                                ? $product->getActiveVariantStockQuantity()
                                : (int) $product->stock_quantity;
                            $hasPriceRange = (bool) ($pricing['has_price_range'] ?? false);
                        @endphp
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>
                                @if($primaryImage)
                                    <img src="{{ $primaryImage->url }}" alt="{{ $product->name }}" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td style="max-width: 300px;">
                                <a href="{{ route('admin.products.show', $product) }}" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; white-space: normal;" title="{{ $product->name }}">
                                    {{ $product->name }}
                                </a>
                                <div class="small text-muted mt-1">{{ $product->sku }}</div>
                            </td>
                            <td>
                                @if($product->categories->isNotEmpty())
                                    {{ $product->categories->pluck('name')->join(', ') }}
                                @else
                                    {{ $product->category?->name ?? '-' }}
                                @endif
                            </td>
                            <td>
                                @if($hasPriceRange)
                                    <span>৳{{ number_format((float) ($pricing['price_range_min'] ?? 0), 2) }} - ৳{{ number_format((float) ($pricing['price_range_max'] ?? 0), 2) }}</span>
                                    <div class="small text-muted">Variant price range</div>
                                @elseif(($pricing['sale_price'] ?? null) !== null)
                                    <div class="text-decoration-line-through text-muted">৳{{ number_format((float) ($pricing['regular_price'] ?? 0), 2) }}</div>
                                    <div class="text-danger fw-bold">৳{{ number_format((float) $pricing['sale_price'], 2) }}</div>
                                @else
                                    ৳{{ number_format((float) ($pricing['current_price'] ?? 0), 2) }}
                                @endif
                            </td>
                            <td>
                                @if($effectiveStock <= 0)
                                    <span class="badge bg-danger">Out of Stock</span>
                                @elseif($effectiveStock <= 10)
                                    <span class="badge bg-warning text-dark">{{ $effectiveStock }}</span>
                                @else
                                    <span class="badge bg-success">{{ $effectiveStock }}</span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_featured)
                                    <span class="badge bg-warning" title="Featured"><i class="bi bi-star"></i></span>
                                @endif
                                @if($product->is_new)
                                    <span class="badge bg-success" title="New"><i class="bi bi-tag"></i></span>
                                @endif
                                @if($product->is_bestseller)
                                    <span class="badge bg-danger" title="Bestseller"><i class="bi bi-trophy"></i></span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ $frontendProductUrl }}" class="btn btn-sm btn-outline-success" title="View on Frontend" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $products])
    </div>
</div>
@endsection

@extends('admin.layouts.app')

@section('title', 'Product Details')
@section('page-title', 'Product Details')

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2"></i>{{ $product->name }}</h6>
                </div>
                <div>
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                </div>
            </div>
            <div class="card-body">
                @php
                    $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                    $galleryImages = $product->images->where('is_primary', false);
                @endphp
                <div class="row">
                    <div class="col-md-4">
                        @if($primaryImage)
                            <img src="{{ $primaryImage->url }}" alt="{{ $product->name }}" class="img-fluid rounded mb-3" id="main-image">
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 200px;">
                                <i class="bi bi-image text-muted fs-1"></i>
                            </div>
                        @endif
                        
                        @if($product->images->count() > 1)
                            <div class="row g-2">
                                @foreach($product->images as $image)
                                    <div class="col-3">
                                        <img src="{{ $image->url }}" alt="Gallery" class="img-thumbnail gallery-thumb {{ $image->is_primary ? 'border-primary' : '' }}" style="height: 60px; width: 100%; object-fit: cover; cursor: pointer;" onclick="document.getElementById('main-image').src=this.src">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="col-md-8">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th class="text-muted small text-uppercase" style="width: 150px;">ID</th>
                                <td>{{ $product->id }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">SKU</th>
                                <td><code>{{ $product->sku ?? '-' }}</code></td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Slug</th>
                                <td><code>{{ $product->slug }}</code></td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Categories</th>
                                <td>
                                    @if($product->categories->isNotEmpty())
                                        @foreach($product->categories as $cat)
                                            <a href="{{ route('admin.categories.edit', $cat) }}" class="text-decoration-none badge bg-light text-dark border">{{ $cat->name }}</a>
                                        @endforeach
                                    @elseif($product->category)
                                        <a href="{{ route('admin.categories.edit', $product->category) }}" class="text-decoration-none badge bg-light text-dark border">{{ $product->category->name }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Regular Price</th>
                                <td>
                                    @if($product->sale_price)
                                        <span class="text-decoration-line-through text-muted">৳{{ number_format($product->regular_price, 2) }}</span>
                                        <strong class="text-danger">৳{{ number_format($product->sale_price, 2) }}</strong>
                                        <span class="badge bg-danger ms-1">{{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}% OFF</span>
                                    @else
                                        <strong>৳{{ number_format($product->regular_price, 2) }}</strong>
                                    @endif
                                </td>
                            </tr>
                            @if($product->buy_price)
                            <tr>
                                <th class="text-muted small text-uppercase">Buy Price (Cost)</th>
                                <td>
                                    <span class="text-info">৳{{ number_format($product->buy_price, 2) }}</span>
                                    @php
                                        $sellPrice = $product->sale_price ?? $product->regular_price;
                                        $profit = $sellPrice - $product->buy_price;
                                        $margin = ($profit / $sellPrice) * 100;
                                    @endphp
                                    <small class="ms-2 text-success">
                                        <i class="bi bi-graph-up-arrow"></i> 
                                        Profit: ৳{{ number_format($profit, 2) }} ({{ number_format($margin, 1) }}%)
                                    </small>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <th class="text-muted small text-uppercase">Stock</th>
                                <td>
                                    @if($product->stock_quantity <= 0)
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @elseif($product->stock_quantity <= 10)
                                        <span class="badge bg-warning text-dark">{{ $product->stock_quantity }} (Low)</span>
                                    @else
                                        <span class="badge bg-success">{{ $product->stock_quantity }}</span>
                                    @endif
                                    @if($product->variants->isNotEmpty())
                                        <small class="text-muted">(Total: {{ $product->total_stock }})</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted small text-uppercase">Flags</th>
                                <td>
                                    @if($product->is_featured)
                                        <span class="badge bg-warning"><i class="bi bi-star"></i> Featured</span>
                                    @endif
                                    @if($product->is_new)
                                        <span class="badge bg-success"><i class="bi bi-tag"></i> New</span>
                                    @endif
                                    @if($product->is_bestseller)
                                        <span class="badge bg-danger"><i class="bi bi-trophy"></i> Bestseller</span>
                                    @endif
                                    @if(!$product->is_featured && !$product->is_new && !$product->is_bestseller)
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Sales</th>
                                <td>{{ $product->sales_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Created</th>
                                <td>{{ $product->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Updated</th>
                                <td>{{ $product->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($product->short_description)
                    <hr>
                    <h6>Short Description</h6>
                    <div class="text-muted">{!! $product->short_description !!}</div>
                @endif

                @if($product->description)
                    <hr>
                    <h6>Full Description</h6>
                    <div class="text-muted">{!! $product->description !!}</div>
                @endif
            </div>
        </div>

        @if($product->variants->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <i class="bi bi-diagram-3"></i> Product Variants ({{ $product->variants->count() }})
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Attributes</th>
                                <th>SKU</th>
                                <th>Price Adj.</th>
                                <th>Final Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->variants as $variant)
                                <tr>
                                    <td>
                                        @foreach($variant->attributeValues as $attrValue)
                                            <span class="badge bg-light text-dark border me-1">
                                                @if($attrValue->color_code)
                                                    <span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background-color: {{ $attrValue->color_code }}; border: 1px solid #ccc;"></span>
                                                @endif
                                                <small class="text-muted">{{ $attrValue->attribute->name }}:</small> {{ $attrValue->value }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td><code>{{ $variant->sku ?? '-' }}</code></td>
                                    <td>
                                        @if($variant->price_adjustment > 0)
                                            <span class="text-success">+৳{{ number_format($variant->price_adjustment, 2) }}</span>
                                        @elseif($variant->price_adjustment < 0)
                                            <span class="text-danger">৳{{ number_format($variant->price_adjustment, 2) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td><strong>৳{{ number_format($variant->final_price, 2) }}</strong></td>
                                    <td>
                                        @if($variant->stock_quantity <= 0)
                                            <span class="badge bg-danger">0</span>
                                        @elseif($variant->stock_quantity <= 5)
                                            <span class="badge bg-warning text-dark">{{ $variant->stock_quantity }}</span>
                                        @else
                                            <span class="badge bg-success">{{ $variant->stock_quantity }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($variant->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

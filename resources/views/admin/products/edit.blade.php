@extends('admin.layouts.app')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
@php
    $stockEnabled = $stockEnabled ?? true;
    $isVariableProduct = (bool) old('is_variable', $product->isVariableProduct());
@endphp
<form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" id="productEditForm" data-no-admin-ajax="1">
    @csrf
    @method('PUT')
    <input type="hidden" name="is_variable" id="is_variable" value="{{ $isVariableProduct ? 1 : 0 }}">
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-pencil-square me-2"></i>{{ $product->name }}</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label small text-muted">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="slug" class="form-label small text-muted">Slug</label>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $product->slug) }}">
                                <div class="form-text">Leave empty to auto-generate.</div>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sku" class="form-label small text-muted">SKU</label>
                                <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
                                @error('sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            @php
                                function renderEditCategoryOptions($categories, $selected = null, $prefix = '') {
                                    foreach ($categories as $category) {
                                        $isSelected = $selected == $category->id ? 'selected' : '';
                                        echo "<option value=\"{$category->id}\" {$isSelected}>{$prefix}{$category->name}</option>";
                                        if ($category->children->isNotEmpty()) {
                                            renderEditCategoryOptions($category->children, $selected, $prefix . '— ');
                                        }
                                    }
                                }
                                renderEditCategoryOptions($categories->whereNull('parent_id'), old('category_id', $product->category_id));
                            @endphp
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="short_description" class="form-label">Short Description</label>
                        <input type="text" class="form-control @error('short_description') is-invalid @enderror" id="short_description" name="short_description" value="{{ old('short_description', $product->short_description) }}" maxlength="255">
                        <div class="form-text">Brief description for listings (max 255 chars).</div>
                        @error('short_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Full Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-images"></i> Images
                </div>
                <div class="card-body">
                    @php
                        $primaryImage = $product->images->firstWhere('is_primary', true);
                    @endphp
                    {{-- Primary Image --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">Primary Image</label>
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div id="primary-image-preview" class="border rounded" style="width: 150px; height: 150px; overflow: hidden;">
                                    @if($primaryImage)
                                        <img src="{{ $primaryImage->url }}" alt="Primary" class="w-100 h-100" style="object-fit: cover;">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                            <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <input type="hidden" id="primary-image-input" name="image_path" value="{{ $primaryImage?->image }}">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openMediaPicker('primary-image-input', false, handlePrimaryImageSelect)">
                                    <i class="bi bi-images"></i> Select from Media Library
                                </button>
                                <small class="text-muted d-block mt-2">Upload images to Media Library first, then select here.</small>
                                @if($primaryImage)
                                    <div class="form-check mt-2">
                                        <input type="checkbox" class="form-check-input" name="remove_image" value="1" id="remove_primary">
                                        <label class="form-check-label small text-danger" for="remove_primary">Remove primary image</label>
                                    </div>
                                @endif
                                @error('image_path')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>

                    {{-- Gallery Images (non-primary) --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Gallery Images</label>

                        @php
                            $galleryImages = $product->images->where('is_primary', false);
                        @endphp

                        @if($galleryImages->isNotEmpty())
                            <div class="row g-2 mb-3" id="gallery-images-container">
                                @foreach($galleryImages as $image)
                                    <div class="col-4 col-md-3" id="gallery-image-{{ $image->id }}">
                                        <div class="position-relative border rounded overflow-hidden" style="padding-top: 100%;">
                                            <img src="{{ $image->url }}" alt="Gallery" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover;">
                                            <div class="position-absolute bottom-0 start-0 end-0 p-1 bg-dark bg-opacity-75 d-flex gap-1">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-light btn-sm w-100 py-0 js-set-primary-image-btn"
                                                    style="font-size: 9px;"
                                                    title="Set as Primary"
                                                    data-primary-url="{{ route('admin.products.images.primary', [$product, $image]) }}"
                                                >
                                                    <i class="bi bi-star"></i>
                                                </button>
                                                <div class="form-check form-check-inline m-0">
                                                    <input type="checkbox" class="form-check-input" name="delete_images[]" value="{{ $image->id }}" id="del-{{ $image->id }}" style="width: 14px; height: 14px;">
                                                    <label class="form-check-label text-white" for="del-{{ $image->id }}" style="font-size: 9px;">Del</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="row g-2">
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-secondary w-100" onclick="openMediaPicker('gallery-images-input', true, handleGalleryImagesSelect)">
                                    <i class="bi bi-images"></i> Add from Media Library
                                </button>
                            </div>
                        </div>

                        {{-- Hidden container for selected gallery paths --}}
                        <div id="selected-gallery-images"></div>
                        <small class="text-muted d-block mt-2">Upload images to Media Library first, then select here.</small>
                        @error('gallery_paths.*')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4 {{ $isVariableProduct ? 'd-none' : '' }}" id="basePricingStockCard">
                <div class="card-header">
                    <i class="bi bi-currency-dollar"></i> Pricing & Stock
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="regular_price" class="form-label">Regular Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('regular_price') is-invalid @enderror" id="regular_price" name="regular_price" value="{{ old('regular_price', $product->regular_price) }}" @if(!$isVariableProduct) required @endif>
                        </div>
                        @error('regular_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="sale_price" class="form-label">Sale Price</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('sale_price') is-invalid @enderror" id="sale_price" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}">
                        </div>
                        <div class="form-text">Leave empty if not on sale. Must be less than regular price.</div>
                        @error('sale_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="buy_price" class="form-label">Buy Price (Cost)</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('buy_price') is-invalid @enderror" id="buy_price" name="buy_price" value="{{ old('buy_price', $product->buy_price) }}">
                        </div>
                        <div class="form-text">Optional. Used for calculating profit/revenue reports.</div>
                        @error('buy_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="stock_quantity" class="form-label">
                            Stock Quantity
                            @if($stockEnabled)
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <input
                            type="number"
                            min="0"
                            class="form-control @error('stock_quantity') is-invalid @enderror"
                            id="stock_quantity"
                            name="stock_quantity"
                            value="{{ old('stock_quantity', $product->stock_quantity) }}"
                            @if($stockEnabled && !$isVariableProduct) required @endif
                        >
                        @if(!$stockEnabled)
                            <div class="form-text">Global stock tracking is disabled. This value is optional and ignored.</div>
                        @endif
                        @error('stock_quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card mb-4 {{ $isVariableProduct ? '' : 'd-none' }}" id="variantManagedNoticeCard">
                <div class="card-header">
                    <i class="bi bi-diagram-3"></i> Variant Managed
                </div>
                <div class="card-body">
                    <p class="mb-2 small text-muted">
                        Pricing and stock are managed from variant rows only. Base product pricing/stock is hidden for variable products.
                    </p>
                    <p class="mb-0 small text-muted">
                        @if($stockEnabled)
                            Current total variant stock: {{ $product->total_stock }}
                        @else
                            Global stock tracking is disabled.
                        @endif
                    </p>
                </div>
            </div>

            @php
                $dynamicTiers = old('dynamic_discount_tiers', $product->quantity_pricing_tiers);
                if (empty($dynamicTiers)) {
                    $dynamicTiers = [
                        ['min_quantity' => 1, 'unit_price' => ''],
                        ['min_quantity' => 3, 'unit_price' => ''],
                        ['min_quantity' => 5, 'unit_price' => ''],
                    ];
                }
            @endphp
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-lightning-charge"></i> Dynamic Discount
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-3">Set unit price by quantity. Example: 1 = 100, 3 = 90, 5 = 80.</div>

                    @foreach($dynamicTiers as $index => $tier)
                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <label class="form-label small text-muted mb-1">Min Qty</label>
                                <input
                                    type="number"
                                    min="1"
                                    class="form-control form-control-sm @error("dynamic_discount_tiers.$index.min_quantity") is-invalid @enderror"
                                    name="dynamic_discount_tiers[{{ $index }}][min_quantity]"
                                    value="{{ $tier['min_quantity'] ?? '' }}"
                                    placeholder="e.g. 3"
                                >
                                @error("dynamic_discount_tiers.$index.min_quantity")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-7">
                                <label class="form-label small text-muted mb-1">Unit Price</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">৳</span>
                                    <input
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        class="form-control @error("dynamic_discount_tiers.$index.unit_price") is-invalid @enderror"
                                        name="dynamic_discount_tiers[{{ $index }}][unit_price]"
                                        value="{{ $tier['unit_price'] ?? '' }}"
                                        placeholder="e.g. 90"
                                    >
                                    @error("dynamic_discount_tiers.$index.unit_price")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="form-check form-switch mt-3">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="free_delivery"
                            name="free_delivery"
                            value="1"
                            {{ old('free_delivery', $product->hasFreeDeliveryOffer()) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="free_delivery">Enable free delivery for this product</label>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-tags"></i> Flags & Status
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">
                                <i class="bi bi-star text-warning"></i> Featured
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="is_new" name="is_new" value="1" {{ old('is_new', $product->is_new) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_new">
                                <i class="bi bi-tag text-success"></i> New
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="is_bestseller" name="is_bestseller" value="1" {{ old('is_bestseller', $product->is_bestseller) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_bestseller">
                                <i class="bi bi-trophy text-danger"></i> Bestseller
                            </label>
                        </div>
                    </div>

                    @if($product->sales_count)
                        <div class="text-muted small">
                            <i class="bi bi-cart-check"></i> {{ $product->sales_count }} sales
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check"></i> Update Product
                </button>
            </div>
        </div>
    </div>
</form>

{{-- Variants Section - OUTSIDE the main product form to avoid nested form issues --}}
@if($attributes->isNotEmpty())
<div class="row mt-4">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-diagram-3 me-2"></i>Product Variants</span>
                <div>
                    <span class="badge bg-secondary" id="variantCountBadge">{{ $product->variants->count() }} variant(s)</span>
                </div>
            </div>
            <div class="card-body">
                @php
                    $variantAttributes = $product->variants
                        ->flatMap(fn($variant) => $variant->attributeValues->map(fn($value) => $value->attribute))
                        ->filter()
                        ->unique('id')
                        ->sortBy('id')
                        ->values();
                    $variantBasePurchasePrice = (float) ($product->buy_price ?? 0);
                    $variantBaseRegularPrice = (float) $product->regular_price;
                    $variantBaseDiscountPrice = (float) ($product->sale_price ?? $product->regular_price);

                    $variantAttributeDefinitions = $attributes
                        ->map(function ($attribute) {
                            return [
                                'id' => (int) $attribute->id,
                                'name' => $attribute->name,
                                'values' => $attribute->values
                                    ->map(function ($value) {
                                        return [
                                            'id' => (int) $value->id,
                                            'value' => $value->value,
                                            'color_code' => $value->color_code,
                                        ];
                                    })
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all();

                    $initialVariantGroups = $variantAttributes
                        ->map(function ($attribute) use ($product) {
                            $valueIds = $product->variants
                                ->flatMap(function ($variant) use ($attribute) {
                                    return $variant->attributeValues
                                        ->where('attribute_id', $attribute->id)
                                        ->pluck('id');
                                })
                                ->map(fn ($id) => (int) $id)
                                ->unique()
                                ->sort()
                                ->values()
                                ->all();

                            return [
                                'attribute_id' => (int) $attribute->id,
                                'value_ids' => $valueIds,
                            ];
                        })
                        ->values()
                        ->all();
                @endphp

                <div class="variation-manager-panel rounded-3 p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-0">Manage variation</h6>
                            <p class="text-muted small mb-0">{{ $product->name }}</p>
                        </div>
                        <small class="text-muted" id="variationAutoStatus">Select variation values to auto-add variants.</small>
                    </div>

                    <form action="{{ route('admin.products.variants.generate', $product) }}" method="POST" id="autoGenerateVariantForm" data-no-admin-ajax="1">
                        @csrf
                        <input type="hidden" name="default_price_adjustment" value="0">
                        <input type="hidden" name="default_stock" value="0">
                        <input type="hidden" name="sku_prefix" value="{{ $product->sku }}">

                        <div id="variationRowsContainer" class="d-flex flex-column gap-3"></div>
                    </form>

                    <small class="text-muted d-block mt-2">New combinations are generated automatically after selecting variation values.</small>
                </div>

                <div id="variantMatrixContainer">
                    @include('admin.products.partials.variant-matrix', [
                        'product' => $product,
                        'variantAttributes' => $variantAttributes,
                        'variantBasePurchasePrice' => $variantBasePurchasePrice,
                        'variantBaseRegularPrice' => $variantBaseRegularPrice,
                        'variantBaseDiscountPrice' => $variantBaseDiscountPrice,
                        'stockEnabled' => $stockEnabled,
                    ])
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Single Variant Modal --}}
<div class="modal fade" id="addVariantModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.products.variants.store', $product) }}" method="POST" id="addVariantForm" data-no-admin-ajax="1">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Variant(s)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">1. Select Attribute <span class="text-danger">*</span></label>
                        <select class="form-select" id="attributeSelect" onchange="showAttributeValues()">
                            <option value="">-- Choose Attribute --</option>
                            @foreach($attributes as $attribute)
                                <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3" id="attributeValuesContainer" style="display: none;">
                        <label class="form-label fw-semibold">2. Select Value(s) <span class="text-danger">*</span></label>
                        <p class="text-muted small mb-2">Click to select multiple values. Each selected value will create one variant.</p>
                        <div id="attributeValuesBox" class="border rounded p-3 bg-light">
                            <!-- Values will be populated by JavaScript -->
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllAttributeValues()">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllAttributeValues()">Clear All</button>
                        </div>
                    </div>

                    {{-- Hidden data for JavaScript --}}
                    <div id="attributeData" style="display: none;">
                        @foreach($attributes as $attribute)
                            <div data-attribute-id="{{ $attribute->id }}">
                                @foreach($attribute->values as $value)
                                    <span data-value-id="{{ $value->id }}"
                                          data-value-name="{{ $value->value }}"
                                          data-color-code="{{ $value->color_code }}"></span>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div id="selectedValuesContainer"></div>

                    <div class="alert alert-info py-2 mt-3" id="addVariantPreview">
                        <i class="bi bi-info-circle me-1"></i>
                        <span id="addVariantCount">Select attribute values to add variants</span>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price Adjustment (for all)</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" class="form-control" name="variant_price_adjustment" value="0" placeholder="0.00">
                            </div>
                            <small class="text-muted">Add/subtract from base price (৳{{ number_format($product->regular_price, 2) }})</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Stock Quantity (for each)</label>
                            <input type="number" min="0" class="form-control" name="variant_stock_quantity" value="0" @if($stockEnabled) required @endif>
                            @if(!$stockEnabled)
                                <small class="text-muted">Stock tracking is disabled. This value is optional and ignored.</small>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addVariantBtn" disabled>
                        <i class="bi bi-plus-circle me-1"></i> Add Variant(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Variant Modal --}}
<div class="modal fade" id="editVariantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editVariantForm" method="POST" enctype="multipart/form-data" data-no-admin-ajax="1">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Variant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Variant Image</label>
                            <div class="d-flex align-items-start gap-3">
                                <div id="edit_variant_image_preview" class="border rounded bg-light d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; overflow: hidden;">
                                    <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="hidden" name="variant_image_path" id="edit_variant_image_path">
                                    <button type="button" class="btn btn-sm btn-outline-primary mb-2" onclick="openVariantMediaPicker()">
                                        <i class="bi bi-collection me-1"></i> Select from Media Library
                                    </button>
                                    <small class="text-muted d-block">Upload images to Media Library first, then select here.</small>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_variant_image" id="remove_variant_image" value="1">
                                        <label class="form-check-label text-danger small" for="remove_variant_image">Remove current image</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">SKU</label>
                            <input type="text" class="form-control" name="sku" id="edit_variant_sku">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price Adjustment</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" class="form-control" name="price_adjustment" id="edit_variant_price">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Stock Quantity</label>
                            <input type="number" min="0" class="form-control" name="stock_quantity" id="edit_variant_stock" @if($stockEnabled) required @endif>
                            @if(!$stockEnabled)
                                <small class="text-muted">Stock tracking is disabled. This value is optional and ignored.</small>
                            @endif
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_variant_active" value="1">
                                <label class="form-check-label" for="edit_variant_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Generate All Variants Modal --}}
<div class="modal fade" id="generateVariantsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.products.variants.generate', $product) }}" method="POST" id="generateVariantsForm" data-no-admin-ajax="1">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-grid-3x3 me-2"></i>Generate Variants</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Select values per attribute to generate all possible variant combinations (e.g. Size: S,M and Color: Red,Blue = 4 variants).</p>

                    <div class="row g-4">
                        @foreach($attributes as $attribute)
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold">{{ $attribute->name }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllValues({{ $attribute->id }})">
                                            Select All
                                        </button>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row g-2">
                                            @foreach($attribute->values as $value)
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input class="form-check-input variant-value-checkbox"
                                                               type="checkbox"
                                                            name="attribute_groups[{{ $attribute->id }}][]"
                                                               value="{{ $value->id }}"
                                                               id="gen_val_{{ $value->id }}"
                                                               data-attribute="{{ $attribute->id }}"
                                                               onchange="updateVariantPreview()">
                                                        <label class="form-check-label" for="gen_val_{{ $value->id }}">
                                                            @if($value->color_code)
                                                                <span class="d-inline-block rounded-circle me-1" style="width: 14px; height: 14px; background-color: {{ $value->color_code }}; border: 1px solid #ccc; vertical-align: middle;"></span>
                                                            @endif
                                                            {{ $value->value }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Default Price Adjustment</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" class="form-control" name="default_price_adjustment" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Default Stock per Variant</label>
                            <input type="number" min="0" class="form-control" name="default_stock" value="10" @if($stockEnabled) required @endif>
                            @if(!$stockEnabled)
                                <small class="text-muted">Stock tracking is disabled. This value is optional and ignored.</small>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">SKU Prefix</label>
                            <input type="text" class="form-control" name="sku_prefix" value="{{ $product->sku }}" placeholder="e.g., PROD-001">
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-0" id="variant-preview">
                        <i class="bi bi-info-circle me-2"></i>
                        <span id="variant-count">Select attribute values above to preview variants</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="generateBtn" disabled>
                        <i class="bi bi-grid-3x3 me-1"></i> Generate Variants
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk Edit Modal --}}
<div class="modal fade" id="bulkEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.products.variants.bulk-update', $product) }}" method="POST" id="bulkEditModalForm" data-no-admin-ajax="1">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Bulk Edit Variants</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <span id="bulkEditCount">Select variants from the table first</span>
                    </div>

                    <p class="text-muted small">Leave fields empty to keep current values. Only filled fields will be updated.</p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Set Price Adjustment</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" class="form-control" name="bulk_price_adjustment" placeholder="Leave empty to keep current">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Set Stock Quantity</label>
                        <input type="number" min="0" class="form-control" name="bulk_stock_quantity" placeholder="Leave empty to keep current" @if(!$stockEnabled) disabled @endif>
                        @if(!$stockEnabled)
                            <small class="text-muted">Disabled because global stock tracking is off.</small>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Add Stock (increase by)</label>
                        <input type="number" min="0" class="form-control" name="bulk_add_stock" placeholder="e.g., 10 to add 10 to each" @if(!$stockEnabled) disabled @endif>
                        @if(!$stockEnabled)
                            <small class="text-muted">Disabled because global stock tracking is off.</small>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Set Status</label>
                        <select class="form-select" name="bulk_is_active">
                            <option value="">-- Keep current --</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <hr>

                    <div class="form-check text-danger">
                        <input class="form-check-input" type="checkbox" name="bulk_delete" value="1" id="bulkDeleteCheck" onchange="toggleBulkDeleteWarning()">
                        <label class="form-check-label" for="bulkDeleteCheck">
                            <strong>Delete selected variants</strong>
                        </label>
                    </div>
                    <div class="alert alert-danger mt-2 py-2 d-none" id="bulkDeleteWarning">
                        <i class="bi bi-exclamation-triangle me-1"></i> This will permanently delete the selected variants!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bulkEditSubmit" disabled>
                        <i class="bi bi-check me-1"></i> Apply Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Media Picker Modal --}}
@include('admin.media.picker')
@endsection

@push('styles')
<style>
.variation-manager-panel {
    border: 1px solid #dbe3ec;
    background: linear-gradient(180deg, #fafcff 0%, #f7f9fc 100%);
}

.variation-manager-row {
    border: 1px dashed #ced7e2;
    border-radius: 0.5rem;
    background-color: #ffffff;
    padding: 0.75rem;
}

.variation-values-dropdown {
    position: relative;
}

.variation-values-trigger {
    width: 100%;
    min-height: 42px;
    border: 1px solid #ced7e2;
    border-radius: 0.5rem;
    background-color: #fff;
    color: #64748b;
    padding: 0.45rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.variation-values-trigger.dropdown-toggle::after {
    display: none;
}

.variation-values-trigger-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.variation-values-trigger.has-selection {
    color: #1f2937;
}

.variation-values-trigger:hover {
    border-color: #b8c4d2;
}

.variation-values-trigger:focus,
.variation-values-trigger.show {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.variation-values-trigger:disabled {
    background-color: #edf1f5;
    color: #9aa6b2;
    cursor: not-allowed;
}

.variation-values-chevron {
    font-size: 0.9rem;
    transition: transform 0.2s ease;
}

.variation-values-dropdown.show .variation-values-chevron {
    transform: rotate(180deg);
}

.variation-values-menu {
    width: 100%;
    padding: 0.45rem;
    max-height: 220px;
    overflow-y: auto;
    border-color: #d9e1ea;
}

.variation-values-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.45rem;
    border-radius: 0.4rem;
    cursor: pointer;
    font-size: 0.95rem;
}

.variation-values-option:hover {
    background-color: #f1f5f9;
}

.variation-values-option .form-check-input {
    margin-top: 0;
}

.variation-values-option-color {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    border: 1px solid #cfd6de;
    flex-shrink: 0;
}

.variation-values-empty {
    padding: 0.35rem 0.45rem;
}

.variation-value-chips .badge {
    font-weight: 500;
    font-size: 0.73rem;
}

.variant-matrix-table thead th {
    letter-spacing: 0.03em;
    text-transform: uppercase;
    font-size: 0.72rem;
    white-space: nowrap;
    padding: 0.4rem 0.3rem !important;
}

.variant-matrix-table tbody td {
    padding: 0.35rem 0.3rem !important;
    white-space: nowrap;
}

/* Auto-sizing inputs: start compact, grow with content */
.variant-matrix-table .form-control,
.variant-matrix-table .form-control-sm {
    width: auto;
    min-width: 52px;
    max-width: 160px;
    padding: 0.2rem 0.35rem;
    font-size: 0.8rem;
}

.variant-matrix-table input[type="number"] {
    min-width: 48px;
    max-width: 110px;
    -moz-appearance: textfield;
}

.variant-matrix-table input[type="number"]::-webkit-outer-spin-button,
.variant-matrix-table input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Compact input groups (৳ prefix) */
.variant-matrix-table .input-group {
    flex-wrap: nowrap;
    width: auto;
}

.variant-matrix-table .input-group .input-group-text {
    padding: 0.2rem 0.3rem;
    font-size: 0.75rem;
    min-width: auto;
}

.variant-matrix-table .input-group .form-control {
    min-width: 48px;
    max-width: 100px;
}

/* Narrow copy button columns */
.variant-copy-column,
.variant-copy-cell {
    width: 28px;
    min-width: 28px;
    max-width: 28px;
    text-align: center;
    padding: 0.35rem 0 !important;
}

.variant-copy-field-btn {
    width: 22px;
    height: 22px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
}

/* Image cell compact */
.variant-matrix-table td:nth-child(3) .rounded {
    width: 36px !important;
    height: 36px !important;
}

@media (max-width: 992px) {
    .variation-manager-row {
        padding: 0.6rem;
    }
}

/* Variant Matrix Pagination */
.variant-matrix-pagination-bar {
    background: #f8f9fb;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.6rem 1rem;
}

.variant-matrix-pagination-bar .vm-page-btn {
    min-width: 32px;
    height: 32px;
    padding: 0 6px;
    border: 1px solid #dee2e6;
    background: #fff;
    color: #495057;
    font-size: 0.8rem;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.variant-matrix-pagination-bar .vm-page-btn:hover:not(.active):not(:disabled) {
    background: #e9ecef;
    border-color: #ced4da;
}

.variant-matrix-pagination-bar .vm-page-btn.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
    font-weight: 600;
}

.variant-matrix-pagination-bar .vm-page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.variant-matrix-pagination-bar select {
    height: 32px;
    font-size: 0.8rem;
    padding: 0 1.8rem 0 0.5rem;
    border-radius: 4px;
}

tr[data-variant-row].vm-hidden {
    display: none !important;
}
</style>
@endpush

@push('scripts')
<script>
@if($attributes->isNotEmpty())
const variantAttributeDefinitions = @json($variantAttributeDefinitions ?? []);
const initialVariantGroups = @json($initialVariantGroups ?? []);
const productStockEnabled = @json($stockEnabled);

let variationAutoSubmitTimer = null;
let variationAutoFormSubmitting = false;
let lastAutoGeneratedSignature = '';

function syncPricingStockVisibilityFromVariants() {
    var basePricingCard = document.getElementById('basePricingStockCard');
    var variantNoticeCard = document.getElementById('variantManagedNoticeCard');
    var regularPriceInput = document.getElementById('regular_price');
    var stockQuantityInput = document.getElementById('stock_quantity');
    var variableFlagInput = document.getElementById('is_variable');

    if (!basePricingCard || !variantNoticeCard) {
        return;
    }

    var badge = document.getElementById('variantCountBadge');
    var hasVariants = false;

    if (badge) {
        var countMatch = badge.textContent.match(/\d+/);
        hasVariants = countMatch ? Number(countMatch[0]) > 0 : false;
    }

    var isVariableMode = hasVariants || (variableFlagInput && variableFlagInput.value === '1');

    basePricingCard.classList.toggle('d-none', isVariableMode);
    variantNoticeCard.classList.toggle('d-none', !isVariableMode);

    if (regularPriceInput) {
        regularPriceInput.required = !isVariableMode;
    }

    if (stockQuantityInput) {
        stockQuantityInput.required = productStockEnabled && !isVariableMode;
    }

    if (variableFlagInput) {
        variableFlagInput.value = isVariableMode ? '1' : '0';
    }
}

function getVariationRows() {
    return Array.from(document.querySelectorAll('.variation-manager-row'));
}

function findVariationAttributeDefinition(attributeId) {
    return variantAttributeDefinitions.find(function(item) {
        return String(item.id) === String(attributeId);
    });
}

function getRowSelectedValueIds(row) {
    var valuesSelect = row.querySelector('.variation-values-select');
    if (!valuesSelect) {
        return [];
    }

    return Array.from(valuesSelect.selectedOptions).map(function(option) {
        return option.value;
    });
}

function updateVariationValueTriggerLabel(row) {
    var valuesSelect = row.querySelector('.variation-values-select');
    var valuesTrigger = row.querySelector('.variation-values-trigger');
    var triggerText = row.querySelector('.variation-values-trigger-text');

    if (!valuesSelect || !valuesTrigger || !triggerText) {
        return;
    }

    var selectedOptions = Array.from(valuesSelect.selectedOptions);
    if (selectedOptions.length === 0) {
        triggerText.textContent = 'Select...';
        valuesTrigger.classList.remove('has-selection');
        return;
    }

    valuesTrigger.classList.add('has-selection');

    if (selectedOptions.length === 1) {
        triggerText.textContent = selectedOptions[0].textContent;
        return;
    }

    triggerText.textContent = selectedOptions.length + ' selected';
}

function renderVariationValueDropdownOptions(row, values, selectedIds) {
    var optionsContainer = row.querySelector('.variation-values-options');
    if (!optionsContainer) {
        return;
    }

    optionsContainer.innerHTML = '';

    if (!Array.isArray(values) || values.length === 0) {
        var emptyState = document.createElement('div');
        emptyState.className = 'variation-values-empty text-muted small';
        emptyState.textContent = 'No values available.';
        optionsContainer.appendChild(emptyState);
        return;
    }

    values.forEach(function(value) {
        var optionLabel = document.createElement('label');
        optionLabel.className = 'variation-values-option';

        var checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'form-check-input variation-value-checkbox';
        checkbox.value = String(value.id);
        checkbox.checked = selectedIds.has(String(value.id));
        optionLabel.appendChild(checkbox);

        if (value.color_code) {
            var colorDot = document.createElement('span');
            colorDot.className = 'variation-values-option-color';
            colorDot.style.backgroundColor = value.color_code;
            optionLabel.appendChild(colorDot);
        }

        var text = document.createElement('span');
        text.textContent = value.value;
        optionLabel.appendChild(text);

        optionsContainer.appendChild(optionLabel);
    });
}

function syncVariationValueSelectFromDropdown(row) {
    var valuesSelect = row.querySelector('.variation-values-select');
    if (!valuesSelect) {
        return;
    }

    var selectedIds = new Set(
        Array.from(row.querySelectorAll('.variation-value-checkbox:checked')).map(function(input) {
            return String(input.value);
        })
    );

    Array.from(valuesSelect.options).forEach(function(option) {
        option.selected = selectedIds.has(String(option.value));
    });
}

function setVariationAutoStatus(message, tone) {
    var statusEl = document.getElementById('variationAutoStatus');
    if (!statusEl) {
        return;
    }

    statusEl.textContent = message;
    statusEl.classList.remove('text-muted', 'text-primary', 'text-success', 'text-danger');

    if (tone === 'primary') {
        statusEl.classList.add('text-primary');
    } else if (tone === 'success') {
        statusEl.classList.add('text-success');
    } else if (tone === 'danger') {
        statusEl.classList.add('text-danger');
    } else {
        statusEl.classList.add('text-muted');
    }
}

function renderVariationValueChips(row) {
    var chipsContainer = row.querySelector('.variation-value-chips');
    var valuesSelect = row.querySelector('.variation-values-select');

    chipsContainer.innerHTML = '';

    Array.from(valuesSelect.selectedOptions).forEach(function(option) {
        var badge = document.createElement('span');
        badge.className = 'badge bg-light text-dark border';

        var colorCode = option.dataset.colorCode;
        if (colorCode) {
            var colorDot = document.createElement('span');
            colorDot.className = 'd-inline-block rounded-circle me-1';
            colorDot.style.width = '10px';
            colorDot.style.height = '10px';
            colorDot.style.backgroundColor = colorCode;
            colorDot.style.border = '1px solid #ccc';
            colorDot.style.verticalAlign = 'middle';
            badge.appendChild(colorDot);
        }

        var textNode = document.createTextNode(option.textContent);
        badge.appendChild(textNode);
        chipsContainer.appendChild(badge);
    });

    updateVariationValueTriggerLabel(row);
}

function populateVariationValues(row, attributeId, selectedValueIds) {
    var valuesSelect = row.querySelector('.variation-values-select');
    var valuesTrigger = row.querySelector('.variation-values-trigger');
    var normalizedSelectedIds = new Set((selectedValueIds || []).map(function(id) {
        return String(id);
    }));

    valuesSelect.innerHTML = '';
    valuesSelect.name = '';
    valuesSelect.disabled = true;

    if (valuesTrigger) {
        valuesTrigger.disabled = true;
    }

    renderVariationValueDropdownOptions(row, [], new Set());

    if (!attributeId) {
        renderVariationValueChips(row);
        return;
    }

    var attribute = findVariationAttributeDefinition(attributeId);
    if (!attribute) {
        renderVariationValueChips(row);
        return;
    }

    valuesSelect.name = 'attribute_groups[' + attribute.id + '][]';
    valuesSelect.disabled = attribute.values.length === 0;

    if (valuesTrigger) {
        valuesTrigger.disabled = attribute.values.length === 0;
    }

    attribute.values.forEach(function(value) {
        var option = document.createElement('option');
        option.value = String(value.id);
        option.textContent = value.value;
        option.dataset.colorCode = value.color_code || '';

        if (normalizedSelectedIds.has(String(value.id))) {
            option.selected = true;
        }

        valuesSelect.appendChild(option);
    });

    renderVariationValueDropdownOptions(row, attribute.values, normalizedSelectedIds);

    renderVariationValueChips(row);
}

function refreshVariationAttributeSelections() {
    var rows = getVariationRows();
    var selectedAttributeIds = rows
        .map(function(row) {
            return row.querySelector('.variation-attribute-select').value;
        })
        .filter(function(value) {
            return value !== '';
        });

    rows.forEach(function(row) {
        var select = row.querySelector('.variation-attribute-select');
        var currentValue = select.value;

        Array.from(select.options).forEach(function(option) {
            if (!option.value) {
                option.disabled = false;
                return;
            }

            option.disabled = option.value !== currentValue && selectedAttributeIds.includes(option.value);
        });
    });
}

function refreshVariationRowButtons() {
    var rows = getVariationRows();

    rows.forEach(function(row, index) {
        var addButton = row.querySelector('.variation-add-row');
        var removeButton = row.querySelector('.variation-remove-row');

        addButton.classList.toggle('d-none', index !== 0);
        removeButton.classList.toggle('d-none', index === 0);
    });
}

function ensureTrailingEmptyVariationRow() {
    var rows = getVariationRows();
    if (rows.length === 0) {
        createVariationRow();
        return;
    }

    var selectedAttributeCount = 0;
    var hasEmptyRow = false;

    rows.forEach(function(row) {
        var attributeId = row.querySelector('.variation-attribute-select').value;
        var selectedValuesCount = getRowSelectedValueIds(row).length;

        if (!attributeId && selectedValuesCount === 0) {
            hasEmptyRow = true;
            return;
        }

        if (attributeId) {
            selectedAttributeCount++;
        }
    });

    if (selectedAttributeCount < variantAttributeDefinitions.length && !hasEmptyRow) {
        createVariationRow();
    }
}

function buildVariationSignature(grouped) {
    return Object.keys(grouped)
        .sort(function(a, b) {
            return Number(a) - Number(b);
        })
        .map(function(attributeId) {
            var values = grouped[attributeId].slice().sort(function(a, b) {
                return Number(a) - Number(b);
            });
            return attributeId + ':' + values.join(',');
        })
        .join('|');
}

function collectVariationGroupState() {
    var grouped = {};
    var hasIncompleteRow = false;

    getVariationRows().forEach(function(row) {
        var attributeSelect = row.querySelector('.variation-attribute-select');
        var valuesSelect = row.querySelector('.variation-values-select');
        var attributeId = attributeSelect.value;
        var selectedValues = getRowSelectedValueIds(row);

        valuesSelect.name = '';

        if (!attributeId && selectedValues.length === 0) {
            return;
        }

        if (!attributeId || selectedValues.length === 0) {
            hasIncompleteRow = true;
            return;
        }

        valuesSelect.name = 'attribute_groups[' + attributeId + '][]';

        if (!grouped[attributeId]) {
            grouped[attributeId] = [];
        }

        selectedValues.forEach(function(valueId) {
            if (!grouped[attributeId].includes(valueId)) {
                grouped[attributeId].push(valueId);
            }
        });
    });

    Object.keys(grouped).forEach(function(attributeId) {
        grouped[attributeId].sort(function(a, b) {
            return Number(a) - Number(b);
        });
    });

    var signature = buildVariationSignature(grouped);

    return {
        grouped: grouped,
        hasIncompleteRow: hasIncompleteRow,
        groupsCount: Object.keys(grouped).length,
        signature: signature,
    };
}

function updateVariantMatrixFromPayload(payload) {
    if (payload && payload.matrix_html) {
        var matrixContainer = document.getElementById('variantMatrixContainer');
        if (matrixContainer) {
            matrixContainer.innerHTML = payload.matrix_html;
        }
    }

    if (payload && typeof payload.variant_count !== 'undefined') {
        var countBadge = document.getElementById('variantCountBadge');
        if (countBadge) {
            countBadge.textContent = payload.variant_count + ' variant(s)';
        }
    }

    syncPricingStockVisibilityFromVariants();
}

function queueAutoGenerateVariants() {
    if (variationAutoFormSubmitting) {
        return;
    }

    clearTimeout(variationAutoSubmitTimer);
    variationAutoSubmitTimer = setTimeout(function() {
        autoGenerateVariantsFromSelection();
    }, 700);
}

async function autoGenerateVariantsFromSelection() {
    if (variationAutoFormSubmitting) {
        return;
    }

    var form = document.getElementById('autoGenerateVariantForm');
    if (!form) {
        return;
    }

    var state = collectVariationGroupState();

    if (state.groupsCount === 0) {
        setVariationAutoStatus('Select variation values to auto-add variants.', 'muted');
        return;
    }

    if (state.hasIncompleteRow) {
        setVariationAutoStatus('Select at least one value for each chosen variation name.', 'danger');
        return;
    }

    if (state.signature === lastAutoGeneratedSignature) {
        return;
    }

    variationAutoFormSubmitting = true;
    setVariationAutoStatus('Adding new variant combinations...', 'primary');

    try {
        var formData = new FormData(form);
        var response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: formData,
        });

        var payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Failed to generate variants.');
        }

        updateVariantMatrixFromPayload(payload);

        lastAutoGeneratedSignature = state.signature;

        var changedCount = Number(payload.changed_count || 0);
        if (changedCount > 0) {
            setVariationAutoStatus(payload.message || 'Variants synced successfully.', 'success');
            return;
        }

        setVariationAutoStatus(payload.message || 'No new combinations were created.', 'muted');
    } catch (error) {
        setVariationAutoStatus(error.message || 'Could not auto-add variants right now.', 'danger');
    } finally {
        variationAutoFormSubmitting = false;
    }
}

function bindVariationRowEvents(row) {
    var attributeSelect = row.querySelector('.variation-attribute-select');
    var valuesSelect = row.querySelector('.variation-values-select');
    var valuesOptions = row.querySelector('.variation-values-options');
    var addButton = row.querySelector('.variation-add-row');
    var removeButton = row.querySelector('.variation-remove-row');

    attributeSelect.addEventListener('change', function() {
        populateVariationValues(row, attributeSelect.value, []);
        ensureTrailingEmptyVariationRow();
        refreshVariationAttributeSelections();
        refreshVariationRowButtons();
        setVariationAutoStatus('Variation updated. Auto-adding combinations...', 'primary');
        queueAutoGenerateVariants();
    });

    valuesSelect.addEventListener('change', function() {
        renderVariationValueChips(row);
        ensureTrailingEmptyVariationRow();
        refreshVariationAttributeSelections();
        refreshVariationRowButtons();
        setVariationAutoStatus('Variation values selected. Auto-adding combinations...', 'primary');
        queueAutoGenerateVariants();
    });

    if (valuesOptions) {
        valuesOptions.addEventListener('change', function(event) {
            if (!event.target.classList.contains('variation-value-checkbox')) {
                return;
            }

            syncVariationValueSelectFromDropdown(row);
            valuesSelect.dispatchEvent(new Event('change'));
        });
    }

    addButton.addEventListener('click', function() {
        createVariationRow();
        refreshVariationAttributeSelections();
        refreshVariationRowButtons();
    });

    removeButton.addEventListener('click', function() {
        row.remove();
        if (getVariationRows().length === 0) {
            createVariationRow();
        }
        refreshVariationAttributeSelections();
        refreshVariationRowButtons();
        setVariationAutoStatus('Variation row removed. Update selections to auto-add variants.', 'muted');
    });
}

function createVariationRow(seed) {
    var rowSeed = seed || { attribute_id: '', value_ids: [] };
    var container = document.getElementById('variationRowsContainer');
    if (!container) {
        return;
    }

    var row = document.createElement('div');
    row.className = 'row g-2 align-items-start variation-manager-row';

    row.innerHTML = [
        '<div class="col-md-4">',
        '  <label class="form-label fw-semibold mb-1">Variation Name</label>',
        '  <select class="form-select variation-attribute-select"></select>',
        '</div>',
        '<div class="col-md-7">',
        '  <label class="form-label fw-semibold mb-1">Variation value</label>',
        '  <div class="dropdown variation-values-dropdown">',
        '      <button class="btn variation-values-trigger dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" disabled>',
        '          <span class="variation-values-trigger-text">Select...</span>',
        '          <i class="bi bi-chevron-down variation-values-chevron"></i>',
        '      </button>',
        '      <div class="dropdown-menu variation-values-menu">',
        '          <div class="variation-values-options"></div>',
        '      </div>',
        '      <select class="variation-values-select d-none" multiple disabled></select>',
        '  </div>',
        '  <div class="variation-value-chips mt-2 d-flex flex-wrap gap-1"></div>',
        '</div>',
        '<div class="col-md-1 d-flex gap-2 pt-md-4">',
        '  <button type="button" class="btn btn-success btn-sm variation-add-row" title="Add variation"><i class="bi bi-plus"></i></button>',
        '  <button type="button" class="btn btn-outline-danger btn-sm variation-remove-row" title="Remove variation"><i class="bi bi-dash"></i></button>',
        '</div>'
    ].join('');

    var attributeSelect = row.querySelector('.variation-attribute-select');
    var placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = 'Select variation name';
    attributeSelect.appendChild(placeholderOption);

    variantAttributeDefinitions.forEach(function(attribute) {
        var option = document.createElement('option');
        option.value = String(attribute.id);
        option.textContent = attribute.name;
        attributeSelect.appendChild(option);
    });

    if (rowSeed.attribute_id) {
        attributeSelect.value = String(rowSeed.attribute_id);
    }

    container.appendChild(row);
    populateVariationValues(row, attributeSelect.value, rowSeed.value_ids || []);
    bindVariationRowEvents(row);
}

function initializeVariationManager() {
    var container = document.getElementById('variationRowsContainer');
    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (Array.isArray(initialVariantGroups) && initialVariantGroups.length > 0) {
        initialVariantGroups.forEach(function(group) {
            createVariationRow(group);
        });
    } else {
        createVariationRow();
    }

    refreshVariationAttributeSelections();
    refreshVariationRowButtons();
    setVariationAutoStatus('Select variation values to auto-add variants.', 'muted');

    var initialState = collectVariationGroupState();
    lastAutoGeneratedSignature = initialState.signature;

    syncPricingStockVisibilityFromVariants();
}

initializeVariationManager();
@endif

const variantDestroyUrlTemplate = @json(route('admin.products.variants.destroy', [$product, '__VARIANT_ID__']));
const bulkVariantUpdateUrl = @json(route('admin.products.variants.bulk-update', $product));

function resolveVariantDestroyUrl(variantId) {
    return variantDestroyUrlTemplate.replace('__VARIANT_ID__', String(variantId));
}

function notify(message, type) {
    if (!message) {
        return;
    }

    if (typeof window.showAdminToast === 'function') {
        window.showAdminToast(message, type || 'info');
        return;
    }

    alert(message);
}

function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function createMethodFormData(method) {
    var formData = new FormData();
    var csrfToken = getCsrfToken();

    if (csrfToken) {
        formData.append('_token', csrfToken);
    }

    if (method && method.toUpperCase() !== 'POST') {
        formData.append('_method', method.toUpperCase());
    }

    return formData;
}

async function parseAjaxPayload(response) {
    var contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        return response.json();
    }

    var text = await response.text();
    return text ? { message: text } : {};
}

function extractAjaxErrorMessage(payload, fallbackMessage) {
    if (payload && typeof payload.message === 'string' && payload.message.trim() !== '') {
        return payload.message;
    }

    if (payload && payload.errors && typeof payload.errors === 'object') {
        var firstKey = Object.keys(payload.errors)[0];
        if (firstKey) {
            var firstError = payload.errors[firstKey];
            if (Array.isArray(firstError) && firstError.length > 0) {
                return firstError[0];
            }
            if (typeof firstError === 'string' && firstError.trim() !== '') {
                return firstError;
            }
        }
    }

    return fallbackMessage || 'Request failed. Please try again.';
}

async function requestJson(url, formData) {
    var response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
        body: formData,
    });

    var payload = await parseAjaxPayload(response);

    if (!response.ok || (payload && payload.success === false)) {
        var error = new Error(extractAjaxErrorMessage(payload, 'Request failed.'));
        error.payload = payload;
        error.status = response.status;
        throw error;
    }

    return payload || {};
}

async function submitAjaxForm(form) {
    var formData = new FormData(form);
    if (!formData.has('_token')) {
        var csrfToken = getCsrfToken();
        if (csrfToken) {
            formData.append('_token', csrfToken);
        }
    }

    return requestJson(form.action, formData);
}

function setFormSubmitting(form, isSubmitting) {
    var submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');

    submitButtons.forEach(function(button) {
        if (isSubmitting) {
            button.dataset.ajaxWasDisabled = button.disabled ? '1' : '0';
            button.disabled = true;
            return;
        }

        if (button.dataset.ajaxWasDisabled !== '1') {
            button.disabled = false;
        }
        delete button.dataset.ajaxWasDisabled;
    });
}

function hideModal(modalId) {
    var modalElement = document.getElementById(modalId);
    if (!modalElement || typeof bootstrap === 'undefined') {
        return;
    }

    var instance = bootstrap.Modal.getInstance(modalElement);
    if (instance) {
        instance.hide();
    }
}

function clearGalleryPendingSelections() {
    var selectedGallery = document.getElementById('selected-gallery-images');
    if (selectedGallery) {
        selectedGallery.innerHTML = '';
    }
}

// Handle primary image selection from media library
function handlePrimaryImageSelect(media) {
    const preview = document.getElementById('primary-image-preview');
    const input = document.getElementById('primary-image-input');

    input.value = media.path;
    preview.innerHTML = `<img src="${media.url}" alt="Primary" class="w-100 h-100" style="object-fit: cover;">`;
}

// Handle gallery images selection from media library
function handleGalleryImagesSelect(mediaItems) {
    const container = document.getElementById('selected-gallery-images');

    // Add hidden inputs for each selected image
    mediaItems.forEach((media, index) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'gallery_image_paths[]';
        input.value = media.path;
        container.appendChild(input);
    });

    // Show visual feedback
    const feedback = document.createElement('div');
    feedback.className = 'alert alert-success alert-sm mt-2 py-2';
    feedback.innerHTML = `<i class="bi bi-check"></i> ${mediaItems.length} image(s) selected. Save product to add them.`;
    container.appendChild(feedback);
}

// Edit variant
function editVariant(id, variant) {
    document.getElementById('editVariantForm').action = '/admin/products/{{ $product->id }}/variants/' + id;
    document.getElementById('edit_variant_sku').value = variant.sku || '';
    document.getElementById('edit_variant_price').value = variant.price_adjustment || 0;
    document.getElementById('edit_variant_stock').value = variant.stock_quantity || 0;
    document.getElementById('edit_variant_active').checked = variant.is_active;
    document.getElementById('remove_variant_image').checked = false;
    document.getElementById('edit_variant_image_path').value = '';

    // Show current image preview
    var preview = document.getElementById('edit_variant_image_preview');
    if (variant.image_url) {
        preview.innerHTML = '<img src="' + variant.image_url + '" alt="Variant" class="w-100 h-100" style="object-fit: cover;">';
    } else {
        preview.innerHTML = '<i class="bi bi-image text-muted" style="font-size: 2rem;"></i>';
    }

    new bootstrap.Modal(document.getElementById('editVariantModal')).show();
}

// Open media picker for variant image
function openVariantMediaPicker() {
    // Use the media picker's proper API with callback
    openMediaPicker('edit_variant_image_path', false, function(media) {
        var preview = document.getElementById('edit_variant_image_preview');
        preview.innerHTML = '<img src="' + media.url + '" alt="Variant" class="w-100 h-100" style="object-fit: cover;">';
        document.getElementById('edit_variant_image_path').value = media.path;
        document.getElementById('remove_variant_image').checked = false;
    });
}

function openVariantMatrixImagePicker(variantId) {
    if (typeof openMediaPicker !== 'function') {
        return;
    }

    var inputId = 'variant-image-input-' + variantId;
    var imageBox = document.getElementById('variant-row-image-box-' + variantId);

    openMediaPicker(inputId, false, function(media) {
        var targetInput = document.getElementById(inputId);
        if (targetInput) {
            targetInput.value = media.path || '';
        }

        if (imageBox) {
            imageBox.classList.remove('bg-light');
            imageBox.innerHTML = '<img src="' + media.url + '" alt="Variant" class="w-100 h-100" style="object-fit: cover;">';
        }
    });
}

// Show attribute values based on selected attribute
function showAttributeValues() {
    var select = document.getElementById('attributeSelect');
    var container = document.getElementById('attributeValuesContainer');
    var box = document.getElementById('attributeValuesBox');
    var addBtn = document.getElementById('addVariantBtn');

    var attributeId = select.value;

    // Clear previous selections
    document.getElementById('selectedValuesContainer').innerHTML = '';
    updateAddVariantCount();

    if (!attributeId) {
        container.style.display = 'none';
        addBtn.disabled = true;
        return;
    }

    // Get values for selected attribute
    var dataContainer = document.querySelector('#attributeData [data-attribute-id="' + attributeId + '"]');
    var values = dataContainer.querySelectorAll('[data-value-id]');

    // Build the value buttons (multi-select)
    var html = '<div class="d-flex flex-wrap gap-2">';
    values.forEach(function(val) {
        var valueId = val.getAttribute('data-value-id');
        var valueName = val.getAttribute('data-value-name');
        var colorCode = val.getAttribute('data-color-code');

        html += '<button type="button" class="btn btn-outline-secondary value-btn" data-value-id="' + valueId + '" data-value-name="' + valueName + '" onclick="toggleAttributeValue(this)">';
        if (colorCode) {
            html += '<span class="d-inline-block rounded-circle me-1" style="width: 16px; height: 16px; background-color: ' + colorCode + '; border: 1px solid #ccc; vertical-align: middle;"></span>';
        }
        html += valueName + '</button>';
    });
    html += '</div>';

    box.innerHTML = html;
    container.style.display = 'block';
    addBtn.disabled = true;
}

// Toggle attribute value selection (multi-select)
function toggleAttributeValue(btn) {
    var valueId = btn.getAttribute('data-value-id');
    var container = document.getElementById('selectedValuesContainer');
    var existingInput = container.querySelector('input[value="' + valueId + '"]');

    if (existingInput) {
        // Deselect
        existingInput.remove();
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-secondary');
    } else {
        // Select
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'attribute_value_ids[]';
        input.value = valueId;
        container.appendChild(input);
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-primary');
    }

    updateAddVariantCount();
}

// Select all attribute values
function selectAllAttributeValues() {
    document.querySelectorAll('#attributeValuesBox .value-btn').forEach(function(btn) {
        if (!btn.classList.contains('btn-primary')) {
            toggleAttributeValue(btn);
        }
    });
}

// Clear all attribute values
function clearAllAttributeValues() {
    document.querySelectorAll('#attributeValuesBox .value-btn.btn-primary').forEach(function(btn) {
        toggleAttributeValue(btn);
    });
}

// Update add variant count
function updateAddVariantCount() {
    var count = document.querySelectorAll('#selectedValuesContainer input').length;
    var countEl = document.getElementById('addVariantCount');
    var addBtn = document.getElementById('addVariantBtn');

    if (count > 0) {
        countEl.textContent = 'Will create ' + count + ' variant(s)';
        addBtn.disabled = false;
    } else {
        countEl.textContent = 'Select attribute values to add variants';
        addBtn.disabled = true;
    }
}

// Reset add variant modal when closed
document.getElementById('addVariantModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('attributeSelect').value = '';
    document.getElementById('attributeValuesContainer').style.display = 'none';
    document.getElementById('selectedValuesContainer').innerHTML = '';
    document.getElementById('addVariantBtn').disabled = true;
    updateAddVariantCount();
});

// Delete variant
async function deleteVariant(id) {
    if (!confirm('Delete this variant?')) {
        return;
    }

    try {
        var payload = await requestJson(resolveVariantDestroyUrl(id), createMethodFormData('DELETE'));
        updateVariantMatrixFromPayload(payload);
        updateBulkEditCount();
        notify(payload.message || 'Variant deleted successfully.', 'success');
    } catch (error) {
        notify(error.message || 'Failed to delete variant.', 'danger');
    }
}

async function submitBulkDeleteSelectedVariants() {
    var selectedIds = Array.from(document.querySelectorAll('.variant-checkbox:checked')).map(function(cb) {
        return cb.value;
    });

    if (selectedIds.length === 0) {
        alert('Select at least one variant to delete.');
        return;
    }

    if (!confirm('Delete ' + selectedIds.length + ' selected variant(s)? This action cannot be undone.')) {
        return;
    }

    var formData = createMethodFormData('PUT');
    formData.append('bulk_delete', '1');
    selectedIds.forEach(function(id) {
        formData.append('variant_ids[]', id);
    });

    try {
        var payload = await requestJson(bulkVariantUpdateUrl, formData);
        updateVariantMatrixFromPayload(payload);
        updateBulkEditCount();
        notify(payload.message || 'Selected variants deleted successfully.', 'success');
    } catch (error) {
        notify(error.message || 'Failed to delete selected variants.', 'danger');
    }
}

function copyVariantFieldFromRow(triggerButton, fieldName) {
    if (!fieldName) {
        return;
    }

    var matrixForm = document.getElementById('variantMatrixForm');
    if (!matrixForm) {
        return;
    }

    var sourceRow = triggerButton ? triggerButton.closest('tr') : matrixForm.querySelector('tbody tr');
    if (!sourceRow) {
        return;
    }

    var sourceInput = sourceRow.querySelector('input[name$="[' + fieldName + ']"]');
    if (!sourceInput) {
        return;
    }

    var targetRows = Array.from(matrixForm.querySelectorAll('tbody tr')).filter(function(row) {
        return row !== sourceRow;
    });

    if (targetRows.length === 0) {
        return;
    }

    targetRows.forEach(function(row) {
        var targetInput = row.querySelector('input[name$="[' + fieldName + ']"]');
        if (!targetInput) {
            return;
        }

        targetInput.value = sourceInput.value;
        targetInput.dispatchEvent(new Event('input', { bubbles: true }));
        targetInput.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

// Toggle all variants selection (only visible/paginated rows)
function toggleAllVariants(checkbox) {
    var rows = document.querySelectorAll('#variantMatrixForm tbody tr[data-variant-row]:not(.vm-hidden)');
    rows.forEach(function(row) {
        var cb = row.querySelector('.variant-checkbox');
        if (cb) cb.checked = checkbox.checked;
    });
    updateBulkEditCount();
}

// Update bulk edit count
function updateBulkEditCount() {
    var checkedCount = document.querySelectorAll('.variant-checkbox:checked').length;
    var countEl = document.getElementById('bulkEditCount');
    var submitBtn = document.getElementById('bulkEditSubmit');
    var bulkDeleteBtn = document.getElementById('bulkDeleteVariantsBtn');
    var allVariantCheckboxes = document.querySelectorAll('.variant-checkbox');
    var selectAllCheckbox = document.getElementById('selectAllVariants');

    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allVariantCheckboxes.length > 0 && checkedCount === allVariantCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < allVariantCheckboxes.length;
    }

    if (countEl) {
        if (checkedCount > 0) {
            countEl.textContent = checkedCount + ' variant(s) selected';
        } else {
            countEl.textContent = 'Select variants from the table first';
        }
    }

    if (submitBtn) {
        submitBtn.disabled = checkedCount === 0;
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.disabled = checkedCount === 0;
    }
}

// Toggle bulk delete warning
function toggleBulkDeleteWarning() {
    var checkbox = document.getElementById('bulkDeleteCheck');
    var warning = document.getElementById('bulkDeleteWarning');
    if (checkbox.checked) {
        warning.classList.remove('d-none');
    } else {
        warning.classList.add('d-none');
    }
}

// Copy selected variants to bulk edit modal form
document.getElementById('bulkEditModal').addEventListener('show.bs.modal', function() {
    var selectedIds = [];
    document.querySelectorAll('.variant-checkbox:checked').forEach(function(cb) {
        selectedIds.push(cb.value);
    });

    // Remove old hidden inputs
    var oldInputs = document.querySelectorAll('#bulkEditModalForm input[name="variant_ids[]"]');
    oldInputs.forEach(function(input) { input.remove(); });

    // Add new hidden inputs
    var form = document.getElementById('bulkEditModalForm');
    selectedIds.forEach(function(id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'variant_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    updateBulkEditCount();
});

// Listen for variant checkbox changes
document.querySelectorAll('.variant-checkbox').forEach(function(cb) {
    cb.addEventListener('change', updateBulkEditCount);
});

// Select all values for an attribute
function selectAllValues(attributeId) {
    var checkboxes = document.querySelectorAll('input[data-attribute="' + attributeId + '"]');
    var allChecked = true;

    checkboxes.forEach(function(cb) {
        if (!cb.checked) allChecked = false;
    });

    // If all are checked, uncheck all. Otherwise check all.
    checkboxes.forEach(function(cb) {
        cb.checked = !allChecked;
    });

    updateVariantPreview();
}

// Update variant preview count using Cartesian combinations across selected attributes.
function updateVariantPreview() {
    var groupedCounts = {};

    document.querySelectorAll('.variant-value-checkbox:checked').forEach(function(cb) {
        var attributeId = cb.getAttribute('data-attribute');
        groupedCounts[attributeId] = (groupedCounts[attributeId] || 0) + 1;
    });

    var attributeIds = Object.keys(groupedCounts);
    var combinationCount = 0;

    if (attributeIds.length > 0) {
        combinationCount = attributeIds.reduce(function(total, attributeId) {
            return total * groupedCounts[attributeId];
        }, 1);
    }

    var previewEl = document.getElementById('variant-count');
    var generateBtn = document.getElementById('generateBtn');

    if (combinationCount > 0) {
        var breakdown = attributeIds
            .map(function(attributeId) {
                return groupedCounts[attributeId] + ' value(s)';
            })
            .join(' × ');

        previewEl.textContent = 'This will generate ' + combinationCount + ' variant combination(s) from ' + attributeIds.length + ' attribute(s) (' + breakdown + ').';
        generateBtn.disabled = false;
    } else {
        previewEl.textContent = 'Select attribute values above to preview variants';
        generateBtn.disabled = true;
    }
}

document.getElementById('generateVariantsModal').addEventListener('hidden.bs.modal', function() {
    document.querySelectorAll('.variant-value-checkbox').forEach(function(cb) {
        cb.checked = false;
    });

    updateVariantPreview();
});

document.addEventListener('click', async function(event) {
    var primaryButton = event.target.closest('.js-set-primary-image-btn');
    if (!primaryButton) {
        return;
    }

    event.preventDefault();

    var primaryUrl = primaryButton.getAttribute('data-primary-url');
    if (!primaryUrl) {
        notify('Primary image route is missing.', 'danger');
        return;
    }

    primaryButton.disabled = true;

    try {
        var payload = await requestJson(primaryUrl, createMethodFormData('POST'));

        var preview = document.getElementById('primary-image-preview');
        var primaryUrlFromPayload = payload.primary_image && payload.primary_image.url ? payload.primary_image.url : '';

        if (!primaryUrlFromPayload) {
            var galleryCard = primaryButton.closest('[id^="gallery-image-"]');
            var galleryImage = galleryCard ? galleryCard.querySelector('img') : null;
            primaryUrlFromPayload = galleryImage ? galleryImage.getAttribute('src') : '';
        }

        if (preview && primaryUrlFromPayload) {
            preview.innerHTML = '<img src="' + primaryUrlFromPayload + '" alt="Primary" class="w-100 h-100" style="object-fit: cover;">';
        }

        var removePrimaryCheckbox = document.getElementById('remove_primary');
        if (removePrimaryCheckbox) {
            removePrimaryCheckbox.checked = false;
        }

        notify(payload.message || 'Primary image updated.', 'success');
    } catch (error) {
        notify(error.message || 'Failed to update primary image.', 'danger');
    } finally {
        primaryButton.disabled = false;
    }
});

document.addEventListener('submit', async function(event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    var shouldHandle = [
        'productEditForm',
        'addVariantForm',
        'editVariantForm',
        'generateVariantsForm',
        'bulkEditModalForm',
        'variantMatrixForm',
    ].includes(form.id);

    if (!shouldHandle) {
        return;
    }

    event.preventDefault();

    if (typeof window.clearAjaxValidationErrors === 'function') {
        window.clearAjaxValidationErrors(form);
    }

    setFormSubmitting(form, true);

    try {
        var payload = await submitAjaxForm(form);

        if (form.id === 'productEditForm') {
            clearGalleryPendingSelections();

            document.querySelectorAll('input[name="delete_images[]"]:checked').forEach(function(checkbox) {
                checkbox.checked = false;
            });

            var removePrimaryCheckbox = document.getElementById('remove_primary');
            if (removePrimaryCheckbox) {
                removePrimaryCheckbox.checked = false;
            }

            notify(payload.message || 'Product updated successfully.', 'success');
            return;
        }

        if (form.id === 'addVariantForm') {
            hideModal('addVariantModal');
        }

        if (form.id === 'editVariantForm') {
            hideModal('editVariantModal');
        }

        if (form.id === 'generateVariantsForm') {
            hideModal('generateVariantsModal');
        }

        if (form.id === 'bulkEditModalForm') {
            hideModal('bulkEditModal');
        }

        updateVariantMatrixFromPayload(payload);
        updateBulkEditCount();
        notify(payload.message || 'Saved successfully.', 'success');
    } catch (error) {
        if (error && error.status === 422 && error.payload && error.payload.errors && typeof window.renderAjaxValidationErrors === 'function') {
            window.renderAjaxValidationErrors(form, error.payload.errors);
        }

        notify(error.message || 'Failed to save changes.', 'danger');
    } finally {
        setFormSubmitting(form, false);
    }
});
// ==========================================
// Variant Matrix Input Auto-Resize
// ==========================================
(function() {
    var matrixForm = document.getElementById('variantMatrixForm');
    if (!matrixForm) return;

    // Create hidden measuring element
    var measurer = document.createElement('span');
    measurer.style.cssText = 'position:absolute;left:-9999px;top:-9999px;white-space:pre;font-size:0.8rem;font-family:inherit;padding:0;border:0;';
    document.body.appendChild(measurer);

    function autoResizeInput(input) {
        var val = input.value || input.placeholder || '';
        // Minimum 4 chars width
        if (val.length < 4) val = '0000';
        measurer.textContent = val;
        var measuredWidth = measurer.offsetWidth;
        // Add padding: 14px for regular inputs, 10px for inputs inside input-group
        var isInGroup = input.closest('.input-group');
        var pad = isInGroup ? 10 : 14;
        var newWidth = Math.max(48, Math.min(measuredWidth + pad, isInGroup ? 100 : 160));
        input.style.width = newWidth + 'px';
    }

    function autoResizeAll() {
        var inputs = matrixForm.querySelectorAll('.variant-matrix-table input[type="text"], .variant-matrix-table input[type="number"]');
        inputs.forEach(function(input) {
            if (input.type === 'hidden') return;
            autoResizeInput(input);
        });
    }

    // Bind live resize on input
    matrixForm.addEventListener('input', function(e) {
        var target = e.target;
        if (target.matches('.variant-matrix-table input[type="text"], .variant-matrix-table input[type="number"]')) {
            autoResizeInput(target);
        }
    });

    matrixForm.addEventListener('change', function(e) {
        var target = e.target;
        if (target.matches('.variant-matrix-table input[type="text"], .variant-matrix-table input[type="number"]')) {
            autoResizeInput(target);
        }
    });

    // Initial resize on page load
    autoResizeAll();
})();

// ==========================================
// Variant Matrix Client-Side Pagination
// ==========================================
(function() {
    var container = document.getElementById('variantMatrixPagination');
    var matrixForm = document.getElementById('variantMatrixForm');
    if (!container || !matrixForm) return;

    var allRows = Array.from(matrixForm.querySelectorAll('tbody tr[data-variant-row]'));
    var totalRows = allRows.length;

    // Don't show pagination for very small sets
    if (totalRows <= 20) {
        container.style.display = 'none !important';
        return;
    }

    var perPage = 20;
    var currentPage = 1;

    function totalPages() {
        if (perPage <= 0) return 1; // "All"
        return Math.max(1, Math.ceil(totalRows / perPage));
    }

    function applyPagination() {
        if (perPage <= 0) {
            // Show all
            allRows.forEach(function(row) { row.classList.remove('vm-hidden'); });
        } else {
            var start = (currentPage - 1) * perPage;
            var end = start + perPage;
            allRows.forEach(function(row, i) {
                if (i >= start && i < end) {
                    row.classList.remove('vm-hidden');
                } else {
                    row.classList.add('vm-hidden');
                }
            });
        }

        // Update serial numbers for visible rows
        var visibleIndex = 0;
        allRows.forEach(function(row, i) {
            if (!row.classList.contains('vm-hidden')) {
                var slCell = row.querySelector('td:nth-child(2)');
                if (slCell) {
                    var strong = slCell.querySelector('strong');
                    if (strong) strong.textContent = (i + 1);
                }
            }
        });

        // Update select-all checkbox state for visible rows
        var selectAll = document.getElementById('selectAllVariants');
        if (selectAll) {
            var visibleCbs = matrixForm.querySelectorAll('tbody tr[data-variant-row]:not(.vm-hidden) .variant-checkbox');
            var checkedVisible = matrixForm.querySelectorAll('tbody tr[data-variant-row]:not(.vm-hidden) .variant-checkbox:checked');
            selectAll.checked = visibleCbs.length > 0 && checkedVisible.length === visibleCbs.length;
            selectAll.indeterminate = checkedVisible.length > 0 && checkedVisible.length < visibleCbs.length;
        }

        renderPaginationUI();
    }

    function renderPaginationUI() {
        var tp = totalPages();
        var showingStart, showingEnd;

        if (perPage <= 0) {
            showingStart = 1;
            showingEnd = totalRows;
        } else {
            showingStart = Math.min(((currentPage - 1) * perPage) + 1, totalRows);
            showingEnd = Math.min(currentPage * perPage, totalRows);
        }

        var html = '';

        // Left: per-page selector + count
        html += '<div class="d-flex align-items-center gap-2 flex-wrap">';
        html += '<span class="text-muted" style="font-size:0.8rem;">Show</span>';
        html += '<select id="vmPerPageSelect" class="form-select form-select-sm" style="width:auto;">';
        [20, 50, 100].forEach(function(n) {
            html += '<option value="' + n + '"' + (perPage === n ? ' selected' : '') + '>' + n + '</option>';
        });
        html += '<option value="0"' + (perPage <= 0 ? ' selected' : '') + '>All</option>';
        html += '</select>';
        html += '<span class="text-muted" style="font-size:0.8rem;">Showing <strong>' + showingStart + '–' + showingEnd + '</strong> of <strong>' + totalRows + '</strong> variants</span>';
        html += '</div>';

        // Right: page navigation (only if not showing all)
        if (perPage > 0 && tp > 1) {
            html += '<div class="d-flex align-items-center gap-1">';

            // Previous
            html += '<button type="button" class="vm-page-btn" data-vm-page="' + (currentPage - 1) + '"' + (currentPage <= 1 ? ' disabled' : '') + '>&laquo;</button>';

            // Page numbers with ellipsis
            var pages = [];
            var delta = 2;
            var left = Math.max(2, currentPage - delta);
            var right = Math.min(tp - 1, currentPage + delta);

            pages.push(1);
            if (left > 2) pages.push('...');
            for (var p = left; p <= right; p++) pages.push(p);
            if (right < tp - 1) pages.push('...');
            if (tp > 1) pages.push(tp);

            pages.forEach(function(pg) {
                if (pg === '...') {
                    html += '<span class="text-muted px-1" style="font-size:0.8rem;">…</span>';
                } else {
                    html += '<button type="button" class="vm-page-btn' + (pg === currentPage ? ' active' : '') + '" data-vm-page="' + pg + '">' + pg + '</button>';
                }
            });

            // Next
            html += '<button type="button" class="vm-page-btn" data-vm-page="' + (currentPage + 1) + '"' + (currentPage >= tp ? ' disabled' : '') + '>&raquo;</button>';
            html += '</div>';
        }

        container.innerHTML = html;
        container.style.cssText = ''; // Remove the initial hide
        container.classList.add('variant-matrix-pagination-bar');

        // Bind events
        var perPageSelect = document.getElementById('vmPerPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                perPage = parseInt(this.value, 10);
                currentPage = 1;
                applyPagination();
            });
        }

        container.querySelectorAll('[data-vm-page]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var pg = parseInt(this.getAttribute('data-vm-page'), 10);
                if (pg >= 1 && pg <= tp && pg !== currentPage) {
                    currentPage = pg;
                    applyPagination();
                    // Scroll to variant table
                    var tableEl = matrixForm.querySelector('.variant-matrix-table');
                    if (tableEl) tableEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    // Initial render
    applyPagination();
})();
</script>
@endpush

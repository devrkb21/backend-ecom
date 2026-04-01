@extends('admin.layouts.app')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
<form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
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
                                                <form action="{{ route('admin.products.images.primary', [$product, $image]) }}" method="POST" class="flex-grow-1">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-light btn-sm w-100 py-0" style="font-size: 9px;" title="Set as Primary">
                                                        <i class="bi bi-star"></i>
                                                    </button>
                                                </form>
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
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-currency-dollar"></i> Pricing & Stock
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="regular_price" class="form-label">Regular Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('regular_price') is-invalid @enderror" id="regular_price" name="regular_price" value="{{ old('regular_price', $product->regular_price) }}" required>
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
                        <label for="stock_quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" min="0" class="form-control @error('stock_quantity') is-invalid @enderror" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required>
                        @error('stock_quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($product->variants->isNotEmpty())
                            <div class="form-text text-info">
                                Total from variants: {{ $product->total_stock }}
                            </div>
                        @endif
                    </div>
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
                    <span class="badge bg-secondary me-2">{{ $product->variants->count() }} variant(s)</span>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVariantModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Variant
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#generateVariantsModal">
                        <i class="bi bi-grid-3x3 me-1"></i> Generate All
                    </button>
                    @if($product->variants->isNotEmpty())
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bulkEditModal">
                        <i class="bi bi-pencil-square me-1"></i> Bulk Edit
                    </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($product->variants->isNotEmpty())
                    <form action="{{ route('admin.products.variants.bulk-update', $product) }}" method="POST" id="bulkEditForm">
                        @csrf
                        @method('PUT')
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">
                                            <input type="checkbox" class="form-check-input" id="selectAllVariants" onchange="toggleAllVariants(this)">
                                        </th>
                                        <th style="width: 60px;">Image</th>
                                        <th>Attribute</th>
                                        <th>SKU</th>
                                        <th>Adjustment</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->variants as $variant)
                                        <tr id="variant-row-{{ $variant->id }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input variant-checkbox" name="selected_variants[]" value="{{ $variant->id }}">
                                            </td>
                                            <td>
                                                @if($variant->image)
                                                    <img src="{{ asset('storage/' . $variant->image) }}" alt="Variant" class="rounded" style="width: 45px; height: 45px; object-fit: cover;">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($variant->attributeValues as $attrValue)
                                                        <span class="badge bg-light text-dark border">
                                                            @if($attrValue->color_code)
                                                                <span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background-color: {{ $attrValue->color_code }}; border: 1px solid #ccc; vertical-align: middle;"></span>
                                                            @endif
                                                            <small class="text-muted">{{ $attrValue->attribute->name }}:</small> {{ $attrValue->value }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>
                                                <code class="small">{{ $variant->sku ?? '-' }}</code>
                                            </td>
                                            <td>
                                                @if($variant->price_adjustment > 0)
                                                    <span class="text-success">+৳{{ number_format($variant->price_adjustment, 2) }}</span>
                                                @elseif($variant->price_adjustment < 0)
                                                    <span class="text-danger">-৳{{ number_format(abs($variant->price_adjustment), 2) }}</span>
                                                @else
                                                    <span class="text-muted">৳0</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($variant->stock_quantity <= 0)
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                @elseif($variant->stock_quantity <= 5)
                                                    <span class="badge bg-warning text-dark">{{ $variant->stock_quantity }} left</span>
                                                @else
                                                    <span class="badge bg-success">{{ $variant->stock_quantity }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $variant->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $variant->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editVariant({{ $variant->id }}, {{ json_encode(array_merge($variant->toArray(), ['image_url' => $variant->image_url])) }})">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteVariant({{ $variant->id }})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total Stock:</strong></td>
                                        <td colspan="3"><strong>{{ $product->variants->sum('stock_quantity') }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </form>

                    {{-- Hidden delete form --}}
                    <form id="deleteVariantForm" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-box text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 mb-0">No variants yet.</p>
                        <p class="text-muted small">Add variants to offer different options like size, color, etc.</p>
                        <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#generateVariantsModal">
                            <i class="bi bi-grid-3x3 me-1"></i> Generate Variants
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Add Single Variant Modal --}}
<div class="modal fade" id="addVariantModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.products.variants.store', $product) }}" method="POST">
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
                            <input type="number" min="0" class="form-control" name="variant_stock_quantity" value="0">
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
            <form id="editVariantForm" method="POST" enctype="multipart/form-data">
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
                            <input type="number" min="0" class="form-control" name="stock_quantity" id="edit_variant_stock">
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
            <form action="{{ route('admin.products.variants.generate', $product) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-grid-3x3 me-2"></i>Generate Variants</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Select attribute values to create variants. Each selected value will create one variant.</p>

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
                                                               name="attribute_values[]"
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
                            <input type="number" min="0" class="form-control" name="default_stock" value="10">
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
            <form action="{{ route('admin.products.variants.bulk-update', $product) }}" method="POST" id="bulkEditModalForm">
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
                        <input type="number" min="0" class="form-control" name="bulk_stock_quantity" placeholder="Leave empty to keep current">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Add Stock (increase by)</label>
                        <input type="number" min="0" class="form-control" name="bulk_add_stock" placeholder="e.g., 10 to add 10 to each">
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

@push('scripts')
<script>
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
function deleteVariant(id) {
    if (confirm('Delete this variant?')) {
        var form = document.getElementById('deleteVariantForm');
        form.action = '/admin/products/{{ $product->id }}/variants/' + id;
        form.submit();
    }
}

// Toggle all variants selection
function toggleAllVariants(checkbox) {
    var checkboxes = document.querySelectorAll('.variant-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = checkbox.checked;
    });
    updateBulkEditCount();
}

// Update bulk edit count
function updateBulkEditCount() {
    var checkedCount = document.querySelectorAll('.variant-checkbox:checked').length;
    var countEl = document.getElementById('bulkEditCount');
    var submitBtn = document.getElementById('bulkEditSubmit');

    if (checkedCount > 0) {
        countEl.textContent = checkedCount + ' variant(s) selected';
        submitBtn.disabled = false;
    } else {
        countEl.textContent = 'Select variants from the table first';
        submitBtn.disabled = true;
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

// Update variant preview count - each selected value = 1 variant
function updateVariantPreview() {
    var checkedCount = document.querySelectorAll('.variant-value-checkbox:checked').length;

    var previewEl = document.getElementById('variant-count');
    var generateBtn = document.getElementById('generateBtn');

    if (checkedCount > 0) {
        previewEl.textContent = 'This will generate ' + checkedCount + ' variant(s) - one for each selected value';
        generateBtn.disabled = false;
    } else {
        previewEl.textContent = 'Select attribute values above to preview variants';
        generateBtn.disabled = true;
    }
}
</script>
@endpush

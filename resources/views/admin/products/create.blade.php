@extends('admin.layouts.app')

@section('title', 'Create Product')
@section('page-title', 'Create Product')

@section('content')
@php
    $stockEnabled = $stockEnabled ?? true;
    $isVariableProduct = (bool) old('is_variable', false);
@endphp
<form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-circle me-2"></i>New Product</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label small text-muted">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="slug" class="form-label small text-muted">Slug</label>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug') }}">
                                <div class="form-text">Leave empty to auto-generate.</div>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sku" class="form-label small text-muted">SKU</label>
                                <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku') }}">
                                <div class="form-text">Leave empty to auto-generate numeric SKU.</div>
                                @error('sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label small text-muted">Category <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            @php
                                function renderCategoryOptions($categories, $selected = null, $prefix = '') {
                                    foreach ($categories as $category) {
                                        $isSelected = $selected == $category->id ? 'selected' : '';
                                        echo "<option value=\"{$category->id}\" {$isSelected}>{$prefix}{$category->name}</option>";
                                        if ($category->children->isNotEmpty()) {
                                            renderCategoryOptions($category->children, $selected, $prefix . '— ');
                                        }
                                    }
                                }
                                renderCategoryOptions($categories->whereNull('parent_id'), old('category_id'));
                            @endphp
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="short_description" class="form-label small text-muted">Short Description</label>
                        <input type="text" class="form-control @error('short_description') is-invalid @enderror" id="short_description" name="short_description" value="{{ old('short_description') }}" maxlength="255">
                        <div class="form-text">Brief description for listings (max 255 chars).</div>
                        @error('short_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Full Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5">{{ old('description') }}</textarea>
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
                    {{-- Primary Image --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">Primary Image</label>
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div id="primary-image-preview" class="border rounded d-flex align-items-center justify-content-center bg-light" style="width: 150px; height: 150px; overflow: hidden;">
                                    <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="col">
                                <input type="hidden" id="primary-image-input" name="image_path" value="">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openMediaPicker('primary-image-input', false, handlePrimaryImageSelect)">
                                    <i class="bi bi-images"></i> Select from Media Library
                                </button>
                                <small class="text-muted d-block mt-2">Upload images to Media Library first, then select here.</small>
                                @error('image_path')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>

                    {{-- Gallery Images --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Gallery Images</label>

                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="openMediaPicker('gallery-images-input', true, handleGalleryImagesSelect)">
                                <i class="bi bi-images"></i> Add from Media Library
                            </button>
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
                    <i class="bi bi-diagram-3"></i> Product Type
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="is_variable"
                            name="is_variable"
                            value="1"
                            {{ $isVariableProduct ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="is_variable">Variable product (managed by variants)</label>
                    </div>
                    <small class="text-muted d-block">
                        When enabled, base Pricing &amp; Stock is hidden. After saving, set pricing and stock from variant rows on the edit page.
                    </small>
                </div>
            </div>

            <div class="card mb-4 {{ $isVariableProduct ? '' : 'd-none' }}" id="variableManagedNoticeCard">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Variant Managed
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0 small">
                        This product is set as variable. Base pricing and stock are ignored, and variants will be the source of truth.
                    </p>
                </div>
            </div>

            <div class="card mb-4 {{ $isVariableProduct ? 'd-none' : '' }}" id="basePricingStockCard">
                <div class="card-header">
                    <i class="bi bi-currency-dollar"></i> Pricing & Stock
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="regular_price" class="form-label">Regular Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('regular_price') is-invalid @enderror" id="regular_price" name="regular_price" value="{{ old('regular_price') }}" @if(!$isVariableProduct) required @endif>
                        </div>
                        @error('regular_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="sale_price" class="form-label">Sale Price</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('sale_price') is-invalid @enderror" id="sale_price" name="sale_price" value="{{ old('sale_price') }}">
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
                            <input type="number" step="0.01" min="0" class="form-control @error('buy_price') is-invalid @enderror" id="buy_price" name="buy_price" value="{{ old('buy_price') }}">
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
                            value="{{ old('stock_quantity', 0) }}"
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

            @php
                $dynamicTiers = old('dynamic_discount_tiers', [
                    ['min_quantity' => 1, 'unit_price' => ''],
                    ['min_quantity' => 3, 'unit_price' => ''],
                    ['min_quantity' => 5, 'unit_price' => ''],
                ]);
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
                            {{ old('free_delivery') ? 'checked' : '' }}
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
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">
                                <i class="bi bi-star text-warning"></i> Featured
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="is_new" name="is_new" value="1" {{ old('is_new', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_new">
                                <i class="bi bi-tag text-success"></i> New
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="is_bestseller" name="is_bestseller" value="1" {{ old('is_bestseller') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_bestseller">
                                <i class="bi bi-trophy text-danger"></i> Bestseller
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check"></i> Create Product
                </button>
            </div>
        </div>
    </div>
</form>

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
    feedback.innerHTML = `<i class="bi bi-check"></i> ${mediaItems.length} image(s) selected.`;
    container.appendChild(feedback);
}

document.addEventListener('DOMContentLoaded', function () {
    var variableToggle = document.getElementById('is_variable');
    var basePricingStockCard = document.getElementById('basePricingStockCard');
    var variableManagedNoticeCard = document.getElementById('variableManagedNoticeCard');
    var regularPriceInput = document.getElementById('regular_price');
    var stockQuantityInput = document.getElementById('stock_quantity');
    var stockEnabled = @json($stockEnabled);

    if (!variableToggle) {
        return;
    }

    function syncVariableProductModeUI() {
        var isVariable = variableToggle.checked;

        if (basePricingStockCard) {
            basePricingStockCard.classList.toggle('d-none', isVariable);
        }

        if (variableManagedNoticeCard) {
            variableManagedNoticeCard.classList.toggle('d-none', !isVariable);
        }

        if (regularPriceInput) {
            regularPriceInput.required = !isVariable;
        }

        if (stockQuantityInput) {
            stockQuantityInput.required = stockEnabled && !isVariable;
        }
    }

    variableToggle.addEventListener('change', syncVariableProductModeUI);
    syncVariableProductModeUI();
});
</script>
@endpush

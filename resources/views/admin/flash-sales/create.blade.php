@extends('admin.layouts.app')

@section('title', 'Create Flash Sale')
@section('page-title', 'Create Flash Sale')

@section('content')
<form action="{{ route('admin.flash-sales.store') }}" method="POST">
    @csrf
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Sale Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               name="name" value="{{ old('name') }}" required placeholder="e.g., Weekend Flash Sale">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                               name="slug" value="{{ old('slug') }}" placeholder="Auto-generated if empty">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  name="description" rows="3" placeholder="Describe this flash sale...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" 
                                   name="starts_at" value="{{ old('starts_at') }}" required>
                            @error('starts_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" 
                                   name="ends_at" value="{{ old('ends_at') }}" required>
                            @error('ends_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Banner Image</label>
                        <div class="d-flex align-items-start gap-3">
                            <div id="banner-image-preview" class="border rounded d-flex align-items-center justify-content-center bg-light" style="width: 200px; height: 100px; overflow: hidden;">
                                <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                            </div>
                            <div>
                                <input type="hidden" id="banner-image-input" name="banner_image" value="{{ old('banner_image') }}">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openMediaPicker('banner-image-input', false, handleBannerImageSelect)">
                                    <i class="bi bi-images"></i> Select from Media Library
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm mt-2 d-none" id="remove-banner-btn" onclick="removeBannerImage()">
                                    <i class="bi bi-x"></i> Remove
                                </button>
                                @error('banner_image')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2"></i>Products</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        You can add products after creating the flash sale.
                    </div>
                    
                    <div id="products-container">
                        <!-- Products will be added dynamically -->
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary" id="add-product-btn">
                        <i class="bi bi-plus"></i> Add Product
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2"></i>Settings</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <small class="text-muted">Enable/disable this flash sale</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_featured" value="1" id="is_featured"
                                   {{ old('is_featured') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">Featured</label>
                        </div>
                        <small class="text-muted">Show on homepage</small>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <input type="number" class="form-control @error('priority') is-invalid @enderror" 
                               name="priority" value="{{ old('priority', 0) }}" min="0">
                        <small class="text-muted">Higher priority shows first</small>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Max Items Per User</label>
                        <input type="number" class="form-control @error('max_items_per_user') is-invalid @enderror" 
                               name="max_items_per_user" value="{{ old('max_items_per_user') }}" min="1" placeholder="Unlimited">
                        <small class="text-muted">Limit items per user (leave empty for unlimited)</small>
                        @error('max_items_per_user')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-lightning-charge me-2"></i>Create Flash Sale
                        </button>
                        <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="product-row-template">
    <div class="product-row border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-5 mb-2">
                <label class="form-label small">Product</label>
                <select class="form-select form-select-sm product-select" name="products[__INDEX__][product_id]">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->regular_price }}">
                            {{ $product->name }} - ৳{{ number_format($product->regular_price, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Flash Price</label>
                <input type="number" class="form-control form-control-sm" name="products[__INDEX__][flash_price]" step="0.01" min="0" placeholder="৳0.00">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Quantity</label>
                <input type="number" class="form-control form-control-sm" name="products[__INDEX__][quantity_limit]" min="1" placeholder="Limit">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Per User</label>
                <input type="number" class="form-control form-control-sm" name="products[__INDEX__][per_user_limit]" min="1" placeholder="Limit">
            </div>
            <div class="col-md-1 mb-2 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-product-btn">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    </div>
</template>
@endsection

@include('admin.media.picker')

@push('scripts')
<script>
// Banner image media picker handler
function handleBannerImageSelect(media) {
    const preview = document.getElementById('banner-image-preview');
    const removeBtn = document.getElementById('remove-banner-btn');
    
    preview.innerHTML = `<img src="${media.url}" alt="Banner" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">`;
    removeBtn.classList.remove('d-none');
}

function removeBannerImage() {
    const input = document.getElementById('banner-image-input');
    const preview = document.getElementById('banner-image-preview');
    const removeBtn = document.getElementById('remove-banner-btn');
    
    input.value = '';
    preview.innerHTML = '<i class="bi bi-image text-muted" style="font-size: 2rem;"></i>';
    removeBtn.classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', function() {
    let productIndex = 0;
    const container = document.getElementById('products-container');
    const template = document.getElementById('product-row-template');
    const addBtn = document.getElementById('add-product-btn');
    
    addBtn.addEventListener('click', function() {
        const html = template.innerHTML.replace(/__INDEX__/g, productIndex++);
        const div = document.createElement('div');
        div.innerHTML = html;
        container.appendChild(div.firstElementChild);
    });
    
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-product-btn')) {
            e.target.closest('.product-row').remove();
        }
    });
    
    // Initialize banner preview if old value exists
    const bannerInput = document.getElementById('banner-image-input');
    if (bannerInput.value) {
        handleBannerImageSelect({ url: bannerInput.value });
    }
});
</script>
@endpush

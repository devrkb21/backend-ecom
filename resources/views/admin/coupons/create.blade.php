@extends('admin.layouts.app')

@section('title', 'Create Coupon')
@section('page-title', 'Create Coupon')

@section('content')
<form action="{{ route('admin.coupons.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            {{-- Basic Info --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="code" class="form-label">Coupon Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror" 
                                   id="code" name="code" value="{{ old('code') }}" 
                                   placeholder="e.g., SUMMER2024" required>
                            <div class="form-text">Customers will enter this code at checkout</div>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Coupon Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" 
                                   placeholder="e.g., Summer Sale 20% Off" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="2" 
                                      placeholder="Optional description for internal reference">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Discount Settings --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-percent me-2"></i>Discount Settings</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="type" class="form-label">Discount Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="fixed" {{ old('type') == 'fixed' ? 'selected' : '' }}>Fixed Amount (৳)</option>
                                <option value="percentage" {{ old('type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="value" class="form-label">Discount Value <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" id="value-prefix">৳</span>
                                <input type="number" step="0.01" min="0.01" class="form-control @error('value') is-invalid @enderror" 
                                       id="value" name="value" value="{{ old('value') }}" required>
                            </div>
                            @error('value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="minimum_order_amount" class="form-label">Minimum Order Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" min="0" class="form-control @error('minimum_order_amount') is-invalid @enderror" 
                                       id="minimum_order_amount" name="minimum_order_amount" value="{{ old('minimum_order_amount') }}">
                            </div>
                            <div class="form-text">Leave empty for no minimum</div>
                            @error('minimum_order_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6" id="max-discount-field">
                            <label for="maximum_discount" class="form-label">Maximum Discount Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" min="0" class="form-control @error('maximum_discount') is-invalid @enderror" 
                                       id="maximum_discount" name="maximum_discount" value="{{ old('maximum_discount') }}">
                            </div>
                            <div class="form-text">Cap the discount for percentage coupons</div>
                            @error('maximum_discount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Usage Limits --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-hash me-2"></i>Usage Limits</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="usage_limit" class="form-label">Total Usage Limit</label>
                            <input type="number" min="1" class="form-control @error('usage_limit') is-invalid @enderror" 
                                   id="usage_limit" name="usage_limit" value="{{ old('usage_limit') }}"
                                   placeholder="Unlimited">
                            <div class="form-text">Maximum number of times this coupon can be used</div>
                            @error('usage_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="usage_limit_per_user" class="form-label">Usage Limit Per Customer</label>
                            <input type="number" min="1" class="form-control @error('usage_limit_per_user') is-invalid @enderror" 
                                   id="usage_limit_per_user" name="usage_limit_per_user" value="{{ old('usage_limit_per_user') }}"
                                   placeholder="Unlimited">
                            <div class="form-text">How many times each customer can use this coupon</div>
                            @error('usage_limit_per_user')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Restrictions --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Product Restrictions (Optional)</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="applicable_categories" class="form-label">Applicable Categories</label>
                            <select class="form-select @error('applicable_categories') is-invalid @enderror" 
                                    id="applicable_categories" name="applicable_categories[]" multiple>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ in_array($category->id, old('applicable_categories', [])) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Leave empty to apply to all categories</div>
                            @error('applicable_categories')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="applicable_products" class="form-label">Applicable Products</label>
                            <select class="form-select @error('applicable_products') is-invalid @enderror" 
                                    id="applicable_products" name="applicable_products[]" multiple>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ in_array($product->id, old('applicable_products', [])) ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Leave empty to apply to all products</div>
                            @error('applicable_products')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="excluded_products" class="form-label">Excluded Products</label>
                            <select class="form-select @error('excluded_products') is-invalid @enderror" 
                                    id="excluded_products" name="excluded_products[]" multiple>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ in_array($product->id, old('excluded_products', [])) ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Products that this coupon will never apply to</div>
                            @error('excluded_products')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Status & Options --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-toggles me-2"></i>Status & Options</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <div class="form-text">Coupon can only be used when active</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="free_shipping" name="free_shipping" value="1" {{ old('free_shipping') ? 'checked' : '' }}>
                            <label class="form-check-label" for="free_shipping">
                                <i class="bi bi-truck me-1"></i> Free Shipping
                            </label>
                        </div>
                        <div class="form-text">Waive shipping costs when this coupon is applied</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="allow_guest_checkout" name="allow_guest_checkout" value="1" {{ old('allow_guest_checkout') ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_guest_checkout">
                                <i class="bi bi-person me-1"></i> Allow Guest Checkout
                            </label>
                        </div>
                        <div class="form-text">Enable to let guests apply this coupon without login.</div>
                    </div>
                </div>
            </div>

            {{-- Validity Period --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-calendar-range me-2"></i>Validity Period</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="starts_at" class="form-label">Start Date</label>
                        <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" 
                               id="starts_at" name="starts_at" value="{{ old('starts_at') }}">
                        <div class="form-text">Leave empty to start immediately</div>
                        @error('starts_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Expiry Date</label>
                        <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror" 
                               id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                        <div class="form-text">Leave empty for no expiry</div>
                        @error('expires_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Create Coupon
                        </button>
                        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const valuePrefix = document.getElementById('value-prefix');
    const maxDiscountField = document.getElementById('max-discount-field');

    function updateDiscountUI() {
        if (typeSelect.value === 'percentage') {
            valuePrefix.textContent = '%';
            maxDiscountField.style.display = 'block';
        } else {
            valuePrefix.textContent = '৳';
            maxDiscountField.style.display = 'none';
        }
    }

    typeSelect.addEventListener('change', updateDiscountUI);
    updateDiscountUI();

    // Auto uppercase coupon code
    document.getElementById('code').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>
@endpush

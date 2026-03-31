@extends('admin.layouts.app')

@section('title', isset($reward) ? 'Edit Reward' : 'Create Reward')
@section('page-title', isset($reward) ? 'Edit Reward' : 'Create Reward')

@section('content')
<form action="{{ isset($reward) ? route('admin.loyalty.rewards.update', $reward) : route('admin.loyalty.rewards.store') }}" method="POST">
    @csrf
    @if(isset($reward))
        @method('PUT')
    @endif
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Reward Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               name="name" value="{{ old('name', $reward->name ?? '') }}" required placeholder="e.g., ৳100 Off Coupon">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  name="description" rows="3" placeholder="Describe this reward...">{{ old('description', $reward->description ?? '') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reward Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('reward_type') is-invalid @enderror" name="reward_type" required id="rewardType">
                                <option value="">Select Type</option>
                                <option value="discount_percentage" {{ old('reward_type', $reward->reward_type ?? '') === 'discount_percentage' ? 'selected' : '' }}>Discount Percentage</option>
                                <option value="discount_fixed" {{ old('reward_type', $reward->reward_type ?? '') === 'discount_fixed' ? 'selected' : '' }}>Fixed Discount</option>
                                <option value="free_shipping" {{ old('reward_type', $reward->reward_type ?? '') === 'free_shipping' ? 'selected' : '' }}>Free Shipping</option>
                                <option value="free_product" {{ old('reward_type', $reward->reward_type ?? '') === 'free_product' ? 'selected' : '' }}>Free Product</option>
                                <option value="coupon" {{ old('reward_type', $reward->reward_type ?? '') === 'coupon' ? 'selected' : '' }}>Coupon Code</option>
                            </select>
                            @error('reward_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Points Required <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('points_required') is-invalid @enderror" 
                                   name="points_required" value="{{ old('points_required', $reward->points_required ?? '') }}" min="1" required>
                            @error('points_required')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3" id="rewardValueField">
                        <label class="form-label">Reward Value <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('reward_value') is-invalid @enderror" 
                               name="reward_value" value="{{ old('reward_value', $reward->reward_value ?? '') }}" step="0.01" min="0">
                        <small class="text-muted" id="rewardValueHint">Enter the discount percentage or amount</small>
                        @error('reward_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3 d-none" id="productField">
                        <label class="form-label">Free Product</label>
                        <select class="form-select @error('product_id') is-invalid @enderror" name="product_id">
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id', $reward->product_id ?? '') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} - ৳{{ number_format($product->regular_price, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Order Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control @error('min_order_amount') is-invalid @enderror" 
                                       name="min_order_amount" value="{{ old('min_order_amount', $reward->min_order_amount ?? '') }}" step="0.01" min="0">
                            </div>
                            <small class="text-muted">Leave empty for no minimum</small>
                            @error('min_order_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Maximum Discount</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control @error('max_discount') is-invalid @enderror" 
                                       name="max_discount" value="{{ old('max_discount', $reward->max_discount ?? '') }}" step="0.01" min="0">
                            </div>
                            <small class="text-muted">Cap for percentage discounts</small>
                            @error('max_discount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
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
                                   {{ old('is_active', $reward->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label">Stock Limit</label>
                        <input type="number" class="form-control @error('stock_limit') is-invalid @enderror" 
                               name="stock_limit" value="{{ old('stock_limit', $reward->stock_limit ?? '') }}" min="1">
                        <small class="text-muted">Leave empty for unlimited</small>
                        @error('stock_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Per User Limit</label>
                        <input type="number" class="form-control @error('per_user_limit') is-invalid @enderror" 
                               name="per_user_limit" value="{{ old('per_user_limit', $reward->per_user_limit ?? '') }}" min="1">
                        <small class="text-muted">How many times a user can redeem</small>
                        @error('per_user_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Coupon Validity (Days)</label>
                        <input type="number" class="form-control @error('coupon_validity_days') is-invalid @enderror" 
                               name="coupon_validity_days" value="{{ old('coupon_validity_days', $reward->coupon_validity_days ?? 30) }}" min="1">
                        <small class="text-muted">How long the generated coupon is valid</small>
                        @error('coupon_validity_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Required Tier</label>
                        <select class="form-select @error('required_tier') is-invalid @enderror" name="required_tier">
                            <option value="">All Tiers</option>
                            @foreach($tiers as $tier)
                                <option value="{{ $tier->slug }}" {{ old('required_tier', $reward->required_tier ?? '') === $tier->slug ? 'selected' : '' }}>
                                    {{ $tier->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Minimum tier required to redeem</small>
                        @error('required_tier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check me-2"></i>{{ isset($reward) ? 'Update' : 'Create' }} Reward
                        </button>
                        <a href="{{ route('admin.loyalty.rewards.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Cancel
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
    const typeSelect = document.getElementById('rewardType');
    const valueField = document.getElementById('rewardValueField');
    const valueHint = document.getElementById('rewardValueHint');
    const productField = document.getElementById('productField');
    
    function updateFields() {
        const type = typeSelect.value;
        
        valueField.classList.remove('d-none');
        productField.classList.add('d-none');
        
        switch(type) {
            case 'discount_percentage':
                valueHint.textContent = 'Enter the discount percentage (e.g., 10 for 10%)';
                break;
            case 'discount_fixed':
                valueHint.textContent = 'Enter the fixed discount amount in Taka';
                break;
            case 'free_shipping':
                valueField.classList.add('d-none');
                break;
            case 'free_product':
                valueField.classList.add('d-none');
                productField.classList.remove('d-none');
                break;
            case 'coupon':
                valueHint.textContent = 'Enter the coupon discount amount';
                break;
        }
    }
    
    typeSelect.addEventListener('change', updateFields);
    updateFields();
});
</script>
@endpush

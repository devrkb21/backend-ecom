@extends('admin.layouts.app')

@section('title', isset($tier) ? 'Edit Tier' : 'Create Tier')
@section('page-title', isset($tier) ? 'Edit Tier: ' . $tier->name : 'Create Tier')

@section('content')
<form action="{{ isset($tier) ? route('admin.loyalty.tiers.update', $tier) : route('admin.loyalty.tiers.store') }}" method="POST">
    @csrf
    @if(isset($tier))
        @method('PUT')
    @endif
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Tier Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   name="name" value="{{ old('name', $tier->name ?? '') }}" required placeholder="e.g., Gold">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                   name="slug" value="{{ old('slug', $tier->slug ?? '') }}" required placeholder="e.g., gold"
                                   {{ isset($tier) && in_array($tier->slug, ['bronze', 'silver', 'gold', 'platinum']) ? 'readonly' : '' }}>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('min_points') is-invalid @enderror" 
                                   name="min_points" value="{{ old('min_points', $tier->min_points ?? 0) }}" min="0" required>
                            <small class="text-muted">Lifetime points required to reach this tier</small>
                            @error('min_points')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Points Multiplier <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('points_multiplier') is-invalid @enderror" 
                                   name="points_multiplier" value="{{ old('points_multiplier', $tier->points_multiplier ?? 1.00) }}" step="0.01" min="1" required>
                            <small class="text-muted">e.g., 1.5 = 50% more points per purchase</small>
                            @error('points_multiplier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Benefits Description</label>
                        <textarea class="form-control @error('benefits') is-invalid @enderror" 
                                  name="benefits" rows="3" placeholder="Describe the benefits of this tier...">{{ old('benefits', $tier->benefits ?? '') }}</textarea>
                        @error('benefits')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-gift me-2"></i>Benefits</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Birthday Bonus Points</label>
                        <input type="number" class="form-control @error('birthday_bonus') is-invalid @enderror" 
                               name="birthday_bonus" value="{{ old('birthday_bonus', $tier->birthday_bonus ?? 0) }}" min="0">
                        <small class="text-muted">Bonus points on birthday</small>
                        @error('birthday_bonus')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="free_shipping" value="1" id="free_shipping"
                                   {{ old('free_shipping', $tier->free_shipping ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="free_shipping">Free Shipping</label>
                        </div>
                        <small class="text-muted">Members get free shipping on all orders</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Exclusive Discount (%)</label>
                        <div class="input-group">
                            <input type="number" class="form-control @error('exclusive_discount') is-invalid @enderror" 
                                   name="exclusive_discount" value="{{ old('exclusive_discount', $tier->exclusive_discount ?? 0) }}" min="0" max="100" step="0.01">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Automatic discount on all orders</small>
                        @error('exclusive_discount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check me-2"></i>{{ isset($tier) ? 'Update' : 'Create' }} Tier
                        </button>
                        <a href="{{ route('admin.loyalty.tiers.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

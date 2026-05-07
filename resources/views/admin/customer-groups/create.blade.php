@extends('admin.layouts.app')

@section('title', isset($customerGroup) ? 'Edit Customer Group' : 'Create Customer Group')
@section('page-title', isset($customerGroup) ? 'Edit Customer Group' : 'Create Customer Group')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ isset($customerGroup) ? 'Edit Group: ' . $customerGroup->name : 'New Loyalty Group' }}</h5>
                <a href="{{ route('admin.customer-groups.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                <form action="{{ isset($customerGroup) ? route('admin.customer-groups.update', $customerGroup) : route('admin.customer-groups.store') }}" method="POST">
                    @csrf
                    @if(isset($customerGroup))
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Group Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customerGroup->name ?? '') }}" placeholder="e.g. Gold Member" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $customerGroup->description ?? '') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <h6 class="mt-4 mb-3 text-muted text-uppercase small border-bottom pb-2">Loyalty Rules (Auto-Assign)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Minimum Order Count</label>
                            <input type="number" name="min_order_count" class="form-control @error('min_order_count') is-invalid @enderror" value="{{ old('min_order_count', $customerGroup->min_order_count ?? 0) }}" min="0" required>
                            <div class="form-text">Set to 0 to ignore this rule.</div>
                            @error('min_order_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Minimum Total Spent (৳)</label>
                            <input type="number" step="0.01" name="min_total_spent" class="form-control @error('min_total_spent') is-invalid @enderror" value="{{ old('min_total_spent', $customerGroup->min_total_spent ?? 0) }}" min="0" required>
                            <div class="form-text">Set to 0 to ignore this rule.</div>
                            @error('min_total_spent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i> A customer is placed in this group if they meet <strong>EITHER</strong> the minimum order count <strong>OR</strong> the minimum total spent threshold.
                    </div>

                    <h6 class="mt-4 mb-3 text-muted text-uppercase small border-bottom pb-2">Benefits & Messaging</h6>
                    <div class="mb-3">
                        <label class="form-label">Discount Percentage (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="discount_percentage" class="form-control @error('discount_percentage') is-invalid @enderror" value="{{ old('discount_percentage', $customerGroup->discount_percentage ?? 0) }}" min="0" max="100" required>
                        @error('discount_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Custom Popup Message</label>
                        <textarea name="custom_message" class="form-control @error('custom_message') is-invalid @enderror" rows="3" placeholder="e.g. Congratulations! As a Gold Member, you get 10% off your order!">{{ old('custom_message', $customerGroup->custom_message ?? '') }}</textarea>
                        <div class="form-text">This message will popup on the checkout page when they enter their phone number.</div>
                        @error('custom_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <h6 class="mt-4 mb-3 text-muted text-uppercase small border-bottom pb-2">Settings</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Sort Order <span class="text-danger">*</span></label>
                            <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $customerGroup->sort_order ?? 0) }}" min="0" required>
                            <div class="form-text">Lower number = checked first. Use this to prioritize Platinum over Gold if thresholds overlap.</div>
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end mb-2">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActive" {{ old('is_active', $customerGroup->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fs-6 mt-1" for="isActive">Active Status</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> {{ isset($customerGroup) ? 'Update Group' : 'Save Group' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('admin.layouts.app')

@section('title', $method ? 'Edit Shipping Method' : 'Create Shipping Method')
@section('page-title', $method ? 'Edit Shipping Method' : 'Create Shipping Method')

@section('content')
<form action="{{ $method ? route('admin.settings.shipping-methods.update', $method) : route('admin.settings.shipping-methods.store') }}" method="POST">
    @csrf
    @if($method)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code', $method?->code) }}"
                                   placeholder="e.g., express, overnight"
                                   pattern="[a-z0-9_-]+"
                                   {{ $method ? 'readonly' : '' }}
                                   required>
                            <div class="form-text">Lowercase letters, numbers, hyphens, underscores only</div>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $method?->name) }}"
                                   placeholder="e.g., Express Shipping"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="2"
                                  placeholder="Brief description shown to customers">{{ old('description', $method?->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-currency-dollar me-2"></i>Pricing</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="base_cost" class="form-label">Base Cost <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control @error('base_cost') is-invalid @enderror" 
                                       id="base_cost" 
                                       name="base_cost" 
                                       value="{{ old('base_cost', $method?->base_cost ?? 0) }}"
                                       step="0.01"
                                       min="0"
                                       required>
                            </div>
                            <div class="form-text">Fixed shipping fee</div>
                            @error('base_cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cost_per_item" class="form-label">Cost Per Item</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control @error('cost_per_item') is-invalid @enderror" 
                                       id="cost_per_item" 
                                       name="cost_per_item" 
                                       value="{{ old('cost_per_item', $method?->cost_per_item ?? 0) }}"
                                       step="0.01"
                                       min="0">
                            </div>
                            <div class="form-text">Additional per item in cart</div>
                            @error('cost_per_item')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cost_per_kg" class="form-label">Cost Per KG</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control @error('cost_per_kg') is-invalid @enderror" 
                                       id="cost_per_kg" 
                                       name="cost_per_kg" 
                                       value="{{ old('cost_per_kg', $method?->cost_per_kg ?? 0) }}"
                                       step="0.01"
                                       min="0">
                            </div>
                            <div class="form-text">Additional based on weight</div>
                            @error('cost_per_kg')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control @error('free_shipping_threshold') is-invalid @enderror" 
                                       id="free_shipping_threshold" 
                                       name="free_shipping_threshold" 
                                       value="{{ old('free_shipping_threshold', $method?->free_shipping_threshold) }}"
                                       step="0.01"
                                       min="0">
                            </div>
                            <div class="form-text">Free shipping for orders above this amount</div>
                            @error('free_shipping_threshold')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2"></i>Restrictions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="min_order_amount" class="form-label">Min Order Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control @error('min_order_amount') is-invalid @enderror" 
                                       id="min_order_amount" 
                                       name="min_order_amount" 
                                       value="{{ old('min_order_amount', $method?->min_order_amount) }}"
                                       step="0.01"
                                       min="0">
                            </div>
                            @error('min_order_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="max_order_amount" class="form-label">Max Order Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control @error('max_order_amount') is-invalid @enderror" 
                                       id="max_order_amount" 
                                       name="max_order_amount" 
                                       value="{{ old('max_order_amount', $method?->max_order_amount) }}"
                                       step="0.01"
                                       min="0">
                            </div>
                            @error('max_order_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="max_weight" class="form-label">Max Weight (kg)</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('max_weight') is-invalid @enderror" 
                                       id="max_weight" 
                                       name="max_weight" 
                                       value="{{ old('max_weight', $method?->max_weight) }}"
                                       step="0.1"
                                       min="0">
                                <span class="input-group-text">kg</span>
                            </div>
                            @error('max_weight')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="min_delivery_days" class="form-label">Min Delivery Days</label>
                            <input type="number" 
                                   class="form-control @error('min_delivery_days') is-invalid @enderror" 
                                   id="min_delivery_days" 
                                   name="min_delivery_days" 
                                   value="{{ old('min_delivery_days', $method?->min_delivery_days) }}"
                                   min="0">
                            @error('min_delivery_days')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="max_delivery_days" class="form-label">Max Delivery Days</label>
                            <input type="number" 
                                   class="form-control @error('max_delivery_days') is-invalid @enderror" 
                                   id="max_delivery_days" 
                                   name="max_delivery_days" 
                                   value="{{ old('max_delivery_days', $method?->max_delivery_days) }}"
                                   min="0">
                            @error('max_delivery_days')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-globe me-2"></i>Country Restrictions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="allowed_countries" class="form-label">Allowed Countries</label>
                            <select class="form-select @error('allowed_countries') is-invalid @enderror" 
                                    id="allowed_countries" 
                                    name="allowed_countries[]"
                                    multiple
                                    size="8">
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}" {{ in_array($code, old('allowed_countries', $method?->allowed_countries ?? [])) ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Leave empty to allow all countries (hold Ctrl/Cmd to select multiple)</div>
                            @error('allowed_countries')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="excluded_countries" class="form-label">Excluded Countries</label>
                            <select class="form-select @error('excluded_countries') is-invalid @enderror" 
                                    id="excluded_countries" 
                                    name="excluded_countries[]"
                                    multiple
                                    size="8">
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}" {{ in_array($code, old('excluded_countries', $method?->excluded_countries ?? [])) ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Countries where this method is NOT available</div>
                            @error('excluded_countries')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-toggle-on me-2"></i>Status</h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="is_active" 
                               name="is_active"
                               {{ old('is_active', $method?->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                    <div class="form-text">Inactive methods won't be shown during checkout</div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-calculator me-2"></i>Cost Preview</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Example calculation for a ৳10,000 order with 3 items and 2kg weight:</p>
                    <div id="cost-preview" class="alert alert-light">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Base cost:</span>
                            <span id="preview-base">৳0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Per item (3 items):</span>
                            <span id="preview-items">৳0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Per kg (2kg):</span>
                            <span id="preview-weight">৳0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total:</span>
                            <span id="preview-total">৳0.00</span>
                        </div>
                        <div id="preview-free" class="text-success mt-2 d-none">
                            <i class="bi bi-check-circle"></i> Free shipping applies!
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>
                    {{ $method ? 'Update Method' : 'Create Method' }}
                </button>
                <a href="{{ route('admin.settings.shipping-methods') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const baseCost = document.getElementById('base_cost');
        const costPerItem = document.getElementById('cost_per_item');
        const costPerKg = document.getElementById('cost_per_kg');
        const freeThreshold = document.getElementById('free_shipping_threshold');
        
        function updatePreview() {
            const base = parseFloat(baseCost.value) || 0;
            const perItem = parseFloat(costPerItem.value) || 0;
            const perKg = parseFloat(costPerKg.value) || 0;
            const threshold = parseFloat(freeThreshold.value) || 0;
            
            const itemsCost = perItem * 3;
            const weightCost = perKg * 2;
            const total = base + itemsCost + weightCost;
            
            document.getElementById('preview-base').textContent = '৳' + base.toFixed(2);
            document.getElementById('preview-items').textContent = '৳' + itemsCost.toFixed(2);
            document.getElementById('preview-weight').textContent = '৳' + weightCost.toFixed(2);
            document.getElementById('preview-total').textContent = '৳' + total.toFixed(2);
            
            const freeDiv = document.getElementById('preview-free');
            if (threshold > 0 && 100 >= threshold) {
                freeDiv.classList.remove('d-none');
                document.getElementById('preview-total').textContent = '৳0.00';
            } else {
                freeDiv.classList.add('d-none');
            }
        }
        
        [baseCost, costPerItem, costPerKg, freeThreshold].forEach(el => {
            el.addEventListener('input', updatePreview);
        });
        
        updatePreview();
    });
</script>
@endpush

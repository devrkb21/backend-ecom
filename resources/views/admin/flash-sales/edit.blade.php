@extends('admin.layouts.app')

@section('title', 'Edit Flash Sale')
@section('page-title', 'Edit Flash Sale: ' . $flashSale->name)

@section('content')
<form action="{{ route('admin.flash-sales.update', $flashSale) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Sale Information</h6>
                    @if($flashSale->status === 'active')
                        <span class="badge bg-success"><i class="bi bi-lightning-charge"></i> Active Now</span>
                    @elseif($flashSale->status === 'scheduled')
                        <span class="badge bg-warning text-dark"><i class="bi bi-calendar-event"></i> Scheduled</span>
                    @else
                        <span class="badge bg-secondary"><i class="bi bi-clock-history"></i> Ended</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               name="name" value="{{ old('name', $flashSale->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                               name="slug" value="{{ old('slug', $flashSale->slug) }}">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  name="description" rows="3">{{ old('description', $flashSale->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" 
                                   name="starts_at" value="{{ old('starts_at', $flashSale->starts_at->format('Y-m-d\TH:i')) }}" required>
                            @error('starts_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" 
                                   name="ends_at" value="{{ old('ends_at', $flashSale->ends_at->format('Y-m-d\TH:i')) }}" required>
                            @error('ends_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Banner Image</label>
                        <div class="d-flex align-items-start gap-3">
                            <div id="banner-image-preview" class="border rounded d-flex align-items-center justify-content-center bg-light" style="width: 200px; height: 100px; overflow: hidden;">
                                @if($flashSale->banner_image)
                                    <img src="{{ $flashSale->banner_image }}" alt="Banner" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                @else
                                    <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                @endif
                            </div>
                            <div>
                                <input type="hidden" id="banner-image-input" name="banner_image" value="{{ old('banner_image', $flashSale->banner_image) }}">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openMediaPicker('banner-image-input', false, handleBannerImageSelect)">
                                    <i class="bi bi-images"></i> Select from Media Library
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm mt-2 {{ $flashSale->banner_image ? '' : 'd-none' }}" id="remove-banner-btn" onclick="removeBannerImage()">
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
                                   {{ old('is_active', $flashSale->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_featured" value="1" id="is_featured"
                                   {{ old('is_featured', $flashSale->is_featured) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">Featured</label>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <input type="number" class="form-control @error('priority') is-invalid @enderror" 
                               name="priority" value="{{ old('priority', $flashSale->priority) }}" min="0">
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Max Items Per User</label>
                        <input type="number" class="form-control @error('max_items_per_user') is-invalid @enderror" 
                               name="max_items_per_user" value="{{ old('max_items_per_user', $flashSale->max_items_per_user) }}" min="1">
                        @error('max_items_per_user')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2"></i>Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="fs-4 fw-bold text-primary">{{ $flashSale->flashSaleProducts->count() }}</div>
                            <small class="text-muted">Products</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="fs-4 fw-bold text-success">{{ number_format($flashSale->flashSaleProducts->sum('sold_count')) }}</div>
                            <small class="text-muted">Sold</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check me-2"></i>Update Flash Sale
                        </button>
                        <a href="{{ route('admin.flash-sales.show', $flashSale) }}" class="btn btn-outline-info">
                            <i class="bi bi-eye me-2"></i>View Details
                        </a>
                        <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
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
</script>
@endpush

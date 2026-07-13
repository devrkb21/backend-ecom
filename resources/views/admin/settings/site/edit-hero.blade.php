@extends('admin.layouts.app')

@section('title', 'Hero Banner Settings')
@section('page-title', 'Hero Banner Settings')

@push('css')
<style>
    .cursor-move { cursor: move; }
    .btn-xs { padding: 0.15rem 0.35rem; font-size: 0.75rem; border-radius: 0.2rem; }
    .slide-item .card:hover { border-color: var(--bs-primary); }
    .border-dashed { border-style: dashed !important; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-10 col-xl-9">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-images me-2 text-primary"></i>Homepage Banners Slider
                </h6>
                <a href="{{ route('admin.settings.site.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back Settings
                </a>
            </div>
            
            <form action="{{ route('admin.settings.site.update-group', 'hero') }}" method="POST" id="hero-settings-form">
                @csrf
                @method('PUT')
                
                <div class="card-body">
                    {{-- Hidden textarea to store the serialized JSON --}}
                    <textarea name="settings[banners]" id="banners_json_input" class="d-none">{{ json_encode($banners) }}</textarea>
                    
                    {{-- Enable hero section setting --}}
                    @php
                        $heroEnabledSetting = $settings->firstWhere('key', 'enabled');
                        $isEnabled = $heroEnabledSetting ? filter_var($heroEnabledSetting->value, FILTER_VALIDATE_BOOLEAN) : true;
                    @endphp
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-0 fw-semibold text-dark">Enable Banner Slider</h6>
                                <small class="text-muted">Turn on or off the homepage hero slider section entirely.</small>
                            </div>
                            <div class="form-check form-switch fs-4">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input" 
                                    name="settings[enabled]" 
                                    id="hero_enabled" 
                                    value="1" 
                                    {{ $isEnabled ? 'checked' : '' }}
                                    onchange="toggleSliderView()"
                                >
                            </div>
                        </div>
                    </div>

                    <div id="slider-content-area" style="display: {{ $isEnabled ? 'block' : 'none' }};">
                        <div class="alert alert-info py-2 small mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>You can add between <strong>1 to 4</strong> homepage banners. Active banners will auto-slide on the website homepage.
                        </div>

                        {{-- Banners Container --}}
                        <div id="banners-container" class="mb-4">
                            {{-- Dynamically populated via JS --}}
                        </div>

                        {{-- Add Slide Button --}}
                        <div class="text-center pt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm px-4" id="add-slide-btn" onclick="addSlide()">
                                <i class="bi bi-plus-lg me-1"></i> Add New Banner Slide
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center py-3">
                    <span class="text-muted small">Make sure to click save to write updates to DB.</span>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-save me-1"></i> Save Banner Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Global delete image form to avoid issues --}}
<form id="delete-image-form" method="POST" class="d-none">
    @csrf
    @method('POST')
</form>
@endsection

@push('scripts')
<script>
    // Initial banners array passed from PHP
    let banners = @json($banners);
    
    // Fallback if banners is empty
    if (!Array.isArray(banners) || banners.length === 0) {
        banners = [{
            title: 'Welcome to Our Store',
            subtitle: 'Discover amazing products',
            description: '',
            image: '',
            button_text: 'Shop Now',
            button_link: '/products',
            enabled: true
        }];
    }

    // Run on page load
    document.addEventListener('DOMContentLoaded', function() {
        renderBanners();
        toggleSliderView();
    });

    function toggleSliderView() {
        const isChecked = document.getElementById('hero_enabled').checked;
        document.getElementById('slider-content-area').style.display = isChecked ? 'block' : 'none';
    }

    // Render banners list to the DOM
    function renderBanners() {
        const container = document.getElementById('banners-container');
        container.innerHTML = '';

        if (banners.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5 text-muted border border-dashed rounded bg-light">
                    <i class="bi bi-images fs-1 d-block mb-2"></i>
                    No slides defined. Click "Add New Banner Slide" below to add one.
                </div>
            `;
            updateAddButtonState();
            serializeBanners();
            return;
        }

        banners.forEach((slide, index) => {
            const card = document.createElement('div');
            card.className = 'card mb-4 slide-item border border-light-subtle shadow-xs';
            card.dataset.index = index;

            // Generate full image preview URL
            let imagePreviewHtml = '';
            if (slide.image) {
                let imgPath = slide.image;
                if (!imgPath.startsWith('http://') && !imgPath.startsWith('https://')) {
                    if (imgPath.startsWith('media/') || imgPath.startsWith('storage/')) {
                        imgPath = '/' + imgPath.replace(/^\//, '');
                    } else {
                        imgPath = '/storage/' + imgPath.replace(/^\//, '');
                    }
                }
                imagePreviewHtml = `
                    <div class="position-relative d-inline-block">
                        <img src="${imgPath}" alt="Banner Image" class="img-thumbnail" style="max-height: 120px; max-width: 260px;">
                        <button type="button" class="btn btn-danger btn-xs position-absolute top-0 end-0 m-1 shadow-sm" onclick="removeSlideImage(${index})" title="Delete image">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                `;
            } else {
                imagePreviewHtml = `
                    <div class="py-3 px-4 bg-light text-center text-muted border rounded small" style="max-width: 260px;">
                        <i class="bi bi-image d-block mb-1 fs-5"></i>
                        No image selected
                    </div>
                `;
            }

            card.innerHTML = `
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold text-dark small">
                        <i class="bi bi-grip-vertical me-1 text-muted"></i> Slide #${index + 1}
                        ${slide.enabled ? '<span class="badge bg-success ms-2 px-2 py-1 small" style="font-size:0.65rem;">Active</span>' : '<span class="badge bg-secondary ms-2 px-2 py-1 small" style="font-size:0.65rem;">Disabled</span>'}
                    </span>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-xs" onclick="moveSlide(${index}, -1)" ${index === 0 ? 'disabled' : ''} title="Move Up">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-xs" onclick="moveSlide(${index}, 1)" ${index === banners.length - 1 ? 'disabled' : ''} title="Move Down">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-xs ms-2" onclick="deleteSlide(${index})" title="Delete Slide">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Title</label>
                            <input type="text" class="form-control form-control-sm slide-field" data-field="title" value="${escapeHtml(slide.title || '')}" oninput="updateSlideField(${index}, 'title', this.value)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Subtitle</label>
                            <input type="text" class="form-control form-control-sm slide-field" data-field="subtitle" value="${escapeHtml(slide.subtitle || '')}" oninput="updateSlideField(${index}, 'subtitle', this.value)">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted mb-1">Description (Optional)</label>
                            <textarea class="form-control form-control-sm slide-field" data-field="description" rows="2" oninput="updateSlideField(${index}, 'description', this.value)">${escapeHtml(slide.description || '')}</textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1 d-block">Background Image</label>
                            <div id="slide-image-preview-${index}" class="mb-2">
                                ${imagePreviewHtml}
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-xs" onclick="openSlideMediaPicker(${index})">
                                <i class="bi bi-images me-1"></i> Select Image
                            </button>
                            <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Optimal Size: 1920x600px (Desktop)</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Button Text</label>
                            <input type="text" class="form-control form-control-sm slide-field" data-field="button_text" value="${escapeHtml(slide.button_text || '')}" oninput="updateSlideField(${index}, 'button_text', this.value)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Button Link</label>
                            <input type="text" class="form-control form-control-sm slide-field" data-field="button_link" value="${escapeHtml(slide.button_link || '')}" oninput="updateSlideField(${index}, 'button_link', this.value)">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch py-1">
                                <input type="checkbox" class="form-check-input" id="slide-enabled-${index}" ${slide.enabled !== false ? 'checked' : ''} onchange="updateSlideField(${index}, 'enabled', this.checked)">
                                <label class="form-check-label small fw-semibold text-muted" for="slide-enabled-${index}">Show this slide</label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });

        updateAddButtonState();
        serializeBanners();
    }

    // Safe HTML escaper helper
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Enable/disable add slide button based on array length limit (1 to 4 slides)
    function updateAddButtonState() {
        const btn = document.getElementById('add-slide-btn');
        if (banners.length >= 4) {
            btn.disabled = true;
            btn.innerText = 'Max 4 slides reached';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i> Add New Banner Slide';
        }
    }

    // Update field values in slide object dynamically
    function updateSlideField(index, field, value) {
        if (banners[index]) {
            banners[index][field] = value;
            
            // If we toggled enabled field, re-render header badge dynamically without replacing inputs
            if (field === 'enabled') {
                renderBanners();
            } else {
                serializeBanners();
            }
        }
    }

    // Add new blank slide
    function addSlide() {
        if (banners.length >= 4) return;
        banners.push({
            title: 'Welcome to Our Store',
            subtitle: 'New arrivals incoming',
            description: '',
            image: '',
            button_text: 'Shop Now',
            button_link: '/products',
            enabled: true
        });
        renderBanners();
    }

    // Delete a slide
    function deleteSlide(index) {
        if (confirm('Are you sure you want to remove this slide?')) {
            banners.splice(index, 1);
            renderBanners();
        }
    }

    // Move slide up or down
    function moveSlide(index, direction) {
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= banners.length) return;
        
        // Swap slides
        const temp = banners[index];
        banners[index] = banners[targetIndex];
        banners[targetIndex] = temp;
        
        renderBanners();
    }

    // Open media picker for slide background image selection
    function openSlideMediaPicker(slideIndex) {
        openMediaPicker('banners_json_input', false, function(media) {
            if (banners[slideIndex]) {
                banners[slideIndex].image = media.path;
                renderBanners();
            }
        });
    }

    // Remove slide image
    function removeSlideImage(slideIndex) {
        if (banners[slideIndex]) {
            banners[slideIndex].image = '';
            renderBanners();
        }
    }

    // Serialize banners state to JSON input
    function serializeBanners() {
        document.getElementById('banners_json_input').value = JSON.stringify(banners);
    }
</script>
@endpush

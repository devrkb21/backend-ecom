@extends('admin.layouts.app')

@section('title', 'Edit Landing Page')
@section('page-title', 'Edit Landing Page')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-sm border">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
                <h6 class="mb-0 fw-semibold">Edit Landing Page Specifications</h6>
                <div class="d-flex gap-2">
                    @php
                        $frontendBaseUrl = rtrim(config('app.frontend_url', 'https://innercollection.com.bd'), '/');
                        $previewUrl = $frontendBaseUrl . '/l/' . $landingPage->slug;
                    @endphp
                    <a href="{{ $previewUrl }}" target="_blank" class="btn btn-sm btn-info text-white">
                        <i class="bi bi-box-arrow-up-right me-1"></i> View on Frontend
                    </a>
                    <a href="{{ route('admin.landing-pages.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.landing-pages.update', $landingPage) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-4">
                        
                        <!-- Core Details Block -->
                        <div class="col-12">
                            <h6 class="fw-semibold border-bottom pb-2 text-primary">Core Settings</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="titleInput" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $landingPage->title) }}" placeholder="e.g. Premium Mango Delights" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Frontend Custom URL (Slug)</label>
                            <input type="text" name="slug" id="slugInput" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $landingPage->slug) }}" placeholder="e.g. premium-mango">
                            <div class="form-text">
                                This will form the public page URL: 
                                <a href="#" id="frontendUrlPreviewLink" target="_blank" class="fw-semibold text-primary text-decoration-none">
                                    <span id="frontendUrlPreview">{{ rtrim(config('app.frontend_url', 'https://innercollection.com.bd'), '/') }}/l/{slug}</span>
                                    <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                </a>
                            </div>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Linked Product <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="" disabled>-- Select Product --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}" {{ old('product_id', $landingPage->product_id) == $prod->id ? 'selected' : '' }}>{{ $prod->name }}</option>
                                @endforeach
                            </select>
                            @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-medium">Template Type <span class="text-danger">*</span></label>
                            <select name="template_type" class="form-select @error('template_type') is-invalid @enderror" required>
                                <option value="default" {{ old('template_type', $landingPage->template_type) == 'default' ? 'selected' : '' }}>Default E-commerce</option>
                                <option value="clothing" {{ old('template_type', $landingPage->template_type) == 'clothing' ? 'selected' : '' }}>Clothing / Fashion</option>
                                <option value="am" {{ old('template_type', $landingPage->template_type) == 'am' ? 'selected' : '' }}>Mangoes (Am)</option>
                                <option value="khejur" {{ old('template_type', $landingPage->template_type) == 'khejur' ? 'selected' : '' }}>Dates (Khejur)</option>
                                <option value="digital_item" {{ old('template_type', $landingPage->template_type) == 'digital_item' ? 'selected' : '' }}>Digital Goods</option>
                                <option value="inner_item" {{ old('template_type', $landingPage->template_type) == 'inner_item' ? 'selected' : '' }}>Lingerie / Innerwear</option>
                                <option value="sexual_item" {{ old('template_type', $landingPage->template_type) == 'sexual_item' ? 'selected' : '' }}>Sensitive Health (Sexual)</option>
                            </select>
                            @error('template_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-medium">Theme Accent Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color border-end-0" id="themeColorPicker" value="{{ old('theme_color', $landingPage->theme_color) }}" title="Choose accent color" style="max-width: 60px;">
                                <input type="text" name="theme_color" id="themeColorText" class="form-control @error('theme_color') is-invalid @enderror" value="{{ old('theme_color', $landingPage->theme_color) }}" placeholder="#E83434" required>
                            </div>
                            @error('theme_color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Media Block -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-semibold border-bottom pb-2 text-primary">Media & Video Presentation</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Hero Banner Image</label>
                            <input type="file" name="banner_image" class="form-control @error('banner_image') is-invalid @enderror">
                            <div class="form-text">Supported formats: JPG, PNG, WEBP, SVG. Max: 4MB. Leave empty to keep existing image.</div>
                            @error('banner_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            
                            @if($landingPage->banner_image)
                                <div class="mt-2">
                                    <label class="form-label small text-muted d-block">Current Banner Preview</label>
                                    <img src="{{ $landingPage->banner_image }}" alt="Current Banner" class="img-thumbnail" style="max-height: 120px;">
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Video Embed Code (Iframe / Direct Link)</label>
                            <textarea name="video_embed_code" class="form-control @error('video_embed_code') is-invalid @enderror" rows="4" placeholder='e.g. <iframe width="560" height="315" src="https://www.youtube.com/embed/..."></iframe\>'>{{ old('video_embed_code', $landingPage->video_embed_code) }}</textarea>
                            @error('video_embed_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Dynamic Repeaters Block: Features -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-semibold border-bottom pb-2 text-primary d-flex justify-content-between align-items-center">
                                <span>Key Selling Features</span>
                                <button type="button" class="btn btn-primary btn-sm" onclick="addFeatureRow()">
                                    <i class="bi bi-plus-circle me-1"></i> Add Feature
                                </button>
                            </h6>
                            <p class="text-muted small">Specify 3-5 highlights to show on the page.</p>
                            <div id="features-container">
                                <!-- Feature items added dynamically -->
                            </div>
                        </div>

                        <!-- Dynamic Repeaters Block: Testimonials -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-semibold border-bottom pb-2 text-primary d-flex justify-content-between align-items-center">
                                <span>Customer Reviews / Testimonials</span>
                                <button type="button" class="btn btn-primary btn-sm" onclick="addTestimonialRow()">
                                    <i class="bi bi-plus-circle me-1"></i> Add Review
                                </button>
                            </h6>
                            <p class="text-muted small">Show positive social proof reviews to boost conversions.</p>
                            <div id="testimonials-container">
                                <!-- Testimonial items added dynamically -->
                            </div>
                        </div>

                        <!-- Custom Styles Override Block -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-semibold border-bottom pb-2 text-primary">Advanced Styling</h6>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Custom CSS Overrides</label>
                            <textarea name="custom_css" class="form-control @error('custom_css') is-invalid @enderror font-monospace" rows="4" placeholder="/* Custom styling overrides */">{{ old('custom_css', $landingPage->custom_css) }}</textarea>
                            @error('custom_css')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="isActive" name="is_active" value="1" {{ old('is_active', $landingPage->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="isActive">Publish page immediately</label>
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="showLocation" name="show_location" value="1" {{ old('show_location', $landingPage->show_location) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="showLocation">Require Division, District, & Upazila selection</label>
                            </div>
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn btn-success px-4 py-2">
                                <i class="bi bi-save me-1"></i> Update Landing Page
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Icon Picker Modal -->
<div class="modal fade" id="iconPickerModal" tabindex="-1" aria-labelledby="iconPickerModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom">
                <h6 class="modal-title fw-bold" id="iconPickerModalLabel">
                    <i class="bi bi-patch-question-fill me-2 text-primary"></i>Select Feature Icon
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group mb-3 shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="iconSearchInput" class="form-control border-start-0 ps-0" placeholder="Search popular icons...">
                </div>
                <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-3 overflow-y-auto" style="max-height: 380px; scrollbar-width: thin;" id="iconContainer">
                    <!-- Dynamic icons loaded by script -->
                </div>
            </div>
            <div class="modal-footer bg-light border-top p-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.hover-icon-btn {
    transition: all 0.2s ease;
    border-radius: 12px;
}
.hover-icon-btn:hover {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: #fff !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}
.hover-icon-btn:hover i {
    color: #fff !important;
}
.hover-icon-btn:hover span {
    color: rgba(255, 255, 255, 0.8) !important;
}
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle color picker linkage
        const themeColorPicker = document.getElementById('themeColorPicker');
        const themeColorText = document.getElementById('themeColorText');
        
        themeColorPicker.addEventListener('input', function() {
            themeColorText.value = themeColorPicker.value.toUpperCase();
        });
        
        themeColorText.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(themeColorText.value)) {
                themeColorPicker.value = themeColorText.value;
            }
        });

        // Prepopulate existing repeaters
        @if(!empty($landingPage->features))
            @foreach($landingPage->features as $feat)
                addFeatureRow("{{ addslashes($feat['title'] ?? '') }}", "{{ addslashes($feat['icon'] ?? 'bi-check-circle') }}", "{{ addslashes($feat['description'] ?? '') }}");
            @endforeach
        @else
            addFeatureRow('Fast Shipping', 'bi-truck', 'We deliver in 24-48 hours across Bangladesh.');
            addFeatureRow('Cash on Delivery', 'bi-wallet2', 'Inspect the product before paying.');
            addFeatureRow('100% Premium Quality', 'bi-shield-check', 'Satisfied or get a replacement/refund.');
        @endif

        @if(!empty($landingPage->testimonials))
            @foreach($landingPage->testimonials as $test)
                addTestimonialRow("{{ addslashes($test['name'] ?? '') }}", {{ (int)($test['rating'] ?? 5) }}, "{{ addslashes($test['comment'] ?? '') }}");
            @endforeach
        @endif

        // Live slug generation and URL preview logic
        const frontendBaseUrl = "{{ rtrim(config('app.frontend_url', 'https://innercollection.com.bd'), '/') }}/l/";
        const slugInput = document.getElementById('slugInput');
        const titleInput = document.getElementById('titleInput');
        const previewSpan = document.getElementById('frontendUrlPreview');
        const previewLink = document.getElementById('frontendUrlPreviewLink');

        let isSlugManuallyEdited = false;

        function slugify(text) {
            return text.toString().toLowerCase()
                .replace(/\s+/g, '-')           // Replace spaces with -
                .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
                .replace(/\-\-+/g, '-')         // Replace multiple - with single -
                .replace(/^-+/, '')             // Trim - from start of text
                .replace(/-+$/, '');            // Trim - from end of text
        }

        function updateUrlPreview(slug) {
            const cleanSlug = slug.trim() || '{slug}';
            previewSpan.textContent = frontendBaseUrl + cleanSlug;
            previewLink.href = slug.trim() ? (frontendBaseUrl + slug.trim()) : '#';
        }

        if (titleInput && slugInput) {
            // Check if slug is already filled
            if (slugInput.value.trim()) {
                isSlugManuallyEdited = true;
                updateUrlPreview(slugInput.value);
            }

            titleInput.addEventListener('input', function() {
                if (!isSlugManuallyEdited) {
                    const generatedSlug = slugify(titleInput.value);
                    slugInput.value = generatedSlug;
                    updateUrlPreview(generatedSlug);
                }
            });

            slugInput.addEventListener('input', function() {
                isSlugManuallyEdited = true;
                updateUrlPreview(slugInput.value);
            });
        }

        // Icon picker lists & handler
        const popularIcons = [
            // Trust, Quality & Awards
            'bi-patch-check-fill', 'bi-shield-check', 'bi-shield-lock-fill', 'bi-award-fill', 'bi-star-fill', 'bi-gem', 'bi-trophy-fill', 'bi-bookmark-star-fill', 'bi-check2-circle', 'bi-check-circle-fill', 'bi-heart-fill',
            // Delivery & Logistics
            'bi-truck', 'bi-box-seam', 'bi-speedometer2', 'bi-geo-alt-fill', 'bi-airplane-fill', 'bi-send-fill', 'bi-clock-fill', 'bi-calendar-check-fill', 'bi-arrow-repeat',
            // Commerce & Financial
            'bi-wallet2', 'bi-cash-coin', 'bi-credit-card-2-front-fill', 'bi-tags-fill', 'bi-percent', 'bi-gift-fill', 'bi-cart-check-fill', 'bi-bag-check-fill', 'bi-shop', 'bi-bag-heart-fill',
            // Customer Care & Contact
            'bi-headset', 'bi-telephone-fill', 'bi-chat-left-heart-fill', 'bi-envelope-heart-fill', 'bi-alarm-fill', 'bi-info-circle-fill', 'bi-question-circle-fill', 'bi-megaphone-fill',
            // Security & Privacy
            'bi-lock-fill', 'bi-eye-slash-fill', 'bi-key-fill', 'bi-shield-fill-exclamation',
            // Niche themes: Food, clothing, digital, health
            'bi-tree-fill', 'bi-flower1', 'bi-cup-hot-fill', 'bi-egg-fried', 'bi-basket-fill', 'bi-magic', 'bi-sparkles', 'bi-lightning-charge-fill', 'bi-fire', 'bi-sun-fill', 'bi-download', 'bi-laptop', 'bi-file-earmark-arrow-down-fill', 'bi-scissors', 'bi-emoji-smile-fill'
        ];

        window.filterIcons = function(query) {
            const container = document.getElementById('iconContainer');
            if (!container) return;
            container.innerHTML = '';
            
            const filtered = popularIcons.filter(icon => icon.toLowerCase().includes(query.toLowerCase()));
            
            filtered.forEach(icon => {
                const div = document.createElement('div');
                div.className = 'col text-center';
                div.innerHTML = `
                    <button type="button" class="btn btn-outline-light border text-dark w-100 py-3 d-flex flex-column align-items-center justify-content-center hover-icon-btn" onclick="selectIcon('${icon}')" style="min-height: 80px;">
                        <i class="bi ${icon} fs-3 mb-1 text-primary"></i>
                        <span class="text-muted d-block" style="font-size: 10px; word-break: break-all;">${icon.replace('bi-', '')}</span>
                    </button>
                `;
                container.appendChild(div);
            });
            
            if (filtered.length === 0) {
                container.innerHTML = `<div class="col-12 text-center text-muted py-3">No icons match your search.</div>`;
            }
        };

        window.openIconPicker = function(inputId, previewId) {
            window.activeIconInputId = inputId;
            window.activeIconPreviewId = previewId;
            
            document.getElementById('iconSearchInput').value = '';
            window.filterIcons('');
            
            const modalEl = document.getElementById('iconPickerModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        };

        window.selectIcon = function(iconClass) {
            if (window.activeIconInputId && window.activeIconPreviewId) {
                document.getElementById(window.activeIconInputId).value = iconClass;
                document.getElementById(window.activeIconPreviewId).innerHTML = `<i class="bi ${iconClass}"></i>`;
            }
            
            const modalEl = document.getElementById('iconPickerModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        };

        // Search listener
        document.getElementById('iconSearchInput').addEventListener('input', function(e) {
            window.filterIcons(e.target.value);
        });
    });

    // FEATURES REPEATER SCRIPT
    let featureIndex = 0;
    function addFeatureRow(title = '', icon = 'bi-check-circle', desc = '') {
        const container = document.getElementById('features-container');
        const html = `
            <div class="card border border-light-subtle shadow-sm mb-3 p-3 feature-row" id="feature-row-${featureIndex}" style="background-color: #fafbfc;">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label small mb-1 fw-medium text-muted">Feature Icon (Bootstrap Icon)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-primary fs-5" id="feature-icon-preview-${featureIndex}">
                                <i class="bi ${icon}"></i>
                            </span>
                            <input type="text" name="features[${featureIndex}][icon]" id="feature-icon-input-${featureIndex}" class="form-control form-control-sm icon-input-class" data-preview-id="feature-icon-preview-${featureIndex}" value="${icon}" placeholder="e.g. bi-truck" readonly style="background-color: #f8f9fa; cursor: pointer;" onclick="openIconPicker('feature-icon-input-${featureIndex}', 'feature-icon-preview-${featureIndex}')" required>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openIconPicker('feature-icon-input-${featureIndex}', 'feature-icon-preview-${featureIndex}')">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small mb-1 fw-medium text-muted">Feature Title</label>
                        <input type="text" name="features[${featureIndex}][title]" class="form-control form-control-sm" value="${title}" placeholder="e.g. Fast Shipping" required>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger mt-3" onclick="removeFeatureRow(${featureIndex})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1 fw-medium text-muted">Feature Description</label>
                        <textarea name="features[${featureIndex}][description]" class="form-control form-control-sm" rows="2" placeholder="e.g. We deliver within 24-48 hours across Bangladesh." required>${desc}</textarea>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        featureIndex++;
    }

    function removeFeatureRow(index) {
        document.getElementById(`feature-row-${index}`).remove();
    }

    // TESTIMONIALS REPEATER SCRIPT
    let testimonialIndex = 0;
    function addTestimonialRow(name = '', rating = 5, comment = '') {
        const container = document.getElementById('testimonials-container');
        const html = `
            <div class="card border border-light-subtle shadow-sm mb-3 p-3 testimonial-row" id="testimonial-row-${testimonialIndex}" style="background-color: #fafbfc;">
                <div class="row g-2 align-items-center">
                    <div class="col-md-8">
                        <label class="form-label small mb-1 fw-medium text-muted">Reviewer Name</label>
                        <input type="text" name="testimonials[${testimonialIndex}][name]" class="form-control form-control-sm" value="${name}" placeholder="e.g. Rahat Khan" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1 fw-medium text-muted">Rating (Stars)</label>
                        <select name="testimonials[${testimonialIndex}][rating]" class="form-select form-select-sm">
                            <option value="5" ${rating == 5 ? 'selected' : ''}>5 Stars</option>
                            <option value="4" ${rating == 4 ? 'selected' : ''}>4 Stars</option>
                            <option value="3" ${rating == 3 ? 'selected' : ''}>3 Stars</option>
                            <option value="2" ${rating == 2 ? 'selected' : ''}>2 Stars</option>
                            <option value="1" ${rating == 1 ? 'selected' : ''}>1 Star</option>
                        </select>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger mt-3" onclick="removeTestimonialRow(${testimonialIndex})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1 fw-medium text-muted">Review Comment</label>
                        <textarea name="testimonials[${testimonialIndex}][comment]" class="form-control form-control-sm" rows="2" placeholder="e.g. Exellent quality product! Perfect fit." required>${comment}</textarea>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        testimonialIndex++;
    }

    function removeTestimonialRow(index) {
        document.getElementById(`testimonial-row-${index}`).remove();
    }
</script>
@endpush

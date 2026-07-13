@extends('admin.layouts.app')

@section('title', 'Site Settings Dashboard')
@section('page-title', 'Site Settings Dashboard')

@push('styles')
<style>
    /* Settings layout and visual aesthetics */
    .settings-nav-card {
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }
    #settings-tabs .nav-link {
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        background-color: transparent;
    }
    #settings-tabs .nav-link:hover {
        background-color: #f1f5f9;
        border-color: #e2e8f0;
    }
    #settings-tabs .nav-link.active {
        background-color: #ffffff;
        border-color: var(--bs-primary);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    #settings-tabs .nav-link.active .bg-primary {
        background-color: var(--bs-primary) !important;
        color: #ffffff !important;
    }
    #settings-tabs .nav-link.active .bg-primary i {
        color: #ffffff !important;
    }
    
    /* Required section banner */
    .required-card {
        border-left: 4px solid #ef4444 !important;
    }
    
    /* Interactive field styles */
    .cursor-move { cursor: move; }
    .btn-xs { padding: 0.15rem 0.35rem; font-size: 0.75rem; border-radius: 0.2rem; }
    .border-dashed { border-style: dashed !important; }
    
    /* Checkout fields manager layout */
    .checkout-fields-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .field-item-card {
        transition: all 0.2s ease;
        border-radius: 0.375rem;
        user-select: none;
    }
    .field-item-card:hover {
        border-color: #cbd5e1 !important;
        background-color: #f8fafc;
    }
    .field-item-card.active {
        border-color: var(--bs-primary) !important;
        background-color: rgba(var(--bs-primary-rgb), 0.04);
        box-shadow: 0 0 0 1px var(--bs-primary);
    }
    .field-item-card.table-active {
        opacity: 0.4;
        background-color: #e2e8f0;
    }
    .drag-handle {
        color: #94a3b8;
        padding: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .drag-handle:hover {
        color: #475569;
    }
</style>
@endpush

@section('content')
@php
    $group = request()->query('group', 'general');
    if (!in_array($group, $groups)) {
        $group = 'general';
    }

    $groupIcons = [
        'hero' => 'bi-card-image',
        'general' => 'bi-house-gear',
        'social' => 'bi-share',
        'seo' => 'bi-search',
        'footer' => 'bi-layout-text-window-reverse',
        'banner' => 'bi-megaphone',
        'checkout' => 'bi-cart-check',
        'navigation' => 'bi-list',
        'appearance' => 'bi-palette',
        'invoice' => 'bi-receipt',
    ];

    $groupDescriptions = [
        'hero' => 'Homepage hero slider banners, titles, buttons and images',
        'general' => 'Site branding, logo heights, hotline and side-cart behavior',
        'social' => 'Social media profiles (Facebook, Instagram, WhatsApp, Messenger)',
        'seo' => 'Meta tags, descriptions, keywords and search optimization',
        'footer' => 'Footer texts, copyright and bottom section configuration',
        'banner' => 'Site-wide top header promotional alert bar setup',
        'checkout' => 'Tax percentage, guest checkout and custom fields manager',
        'navigation' => 'Configure header menu item titles and link structures',
        'appearance' => 'Branding identity primary and hover action colors',
        'invoice' => 'Invoice layout details, company signature and notes',
    ];

    $requiredKeys = [
        'general' => ['site_title'],
        'appearance' => ['primary_color', 'primary_hover_color'],
        'checkout' => ['tax_percentage'],
        'invoice' => ['invoice_prefix'],
        'seo' => ['meta_title', 'meta_description'],
    ];
@endphp

<div class="row g-4">
    <!-- Left Navigation Sidebar -->
    <div class="col-lg-4 col-xl-3">
        <div class="card settings-nav-card shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Settings Panels</h6>
            </div>
            <div class="card-body p-2 pt-0">
                <div class="nav flex-column nav-pills" id="settings-tabs" role="tablist">
                    @php
                        $storefrontGroups = ['hero', 'navigation', 'appearance', 'seo', 'social', 'banner', 'footer'];
                        $systemGroups = ['general', 'checkout', 'invoice'];
                    @endphp

                    <div class="text-muted small fw-bold px-3 pb-2 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.05em;">Storefront & SEO</div>
                    @foreach($groups as $g)
                        @if(in_array($g, $storefrontGroups))
                            <button 
                                class="nav-link text-start d-flex align-items-center gap-3 p-3 mb-1 {{ $g === $group ? 'active' : '' }}" 
                                id="tab-btn-{{ $g }}" 
                                data-bs-toggle="pill" 
                                data-bs-target="#pane-{{ $g }}" 
                                type="button" 
                                role="tab"
                                onclick="updateUrlGroup('{{ $g }}')"
                            >
                                <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi {{ $groupIcons[$g] ?? 'bi-gear' }} fs-5"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-dark text-truncate small">{{ $groupLabels[$g] ?? ucfirst($g) }}</div>
                                    <div class="text-muted small text-truncate" style="font-size: 0.65rem;">
                                        {{ $groupDescriptions[$g] ?? 'Configure ' . $g . ' settings' }}
                                    </div>
                                </div>
                            </button>
                        @endif
                    @endforeach

                    
                    @foreach($groups as $g)
                        @if(in_array($g, $systemGroups))
                            <button 
                                class="nav-link text-start d-flex align-items-center gap-3 p-3 mb-1 {{ $g === $group ? 'active' : '' }}" 
                                id="tab-btn-{{ $g }}" 
                                data-bs-toggle="pill" 
                                data-bs-target="#pane-{{ $g }}" 
                                type="button" 
                                role="tab"
                                onclick="updateUrlGroup('{{ $g }}')"
                            >
                                <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi {{ $groupIcons[$g] ?? 'bi-gear' }} fs-5"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-dark text-truncate small">{{ $groupLabels[$g] ?? ucfirst($g) }}</div>
                                    <div class="text-muted small text-truncate" style="font-size: 0.65rem;">
                                        {{ $groupDescriptions[$g] ?? 'Configure ' . $g . ' settings' }}
                                    </div>
                                </div>
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="card settings-nav-card shadow-sm">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-2 small text-dark"><i class="bi bi-lightning-charge me-1 text-warning"></i>Quick Actions</h6>
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.settings.site.create') }}" class="btn btn-outline-primary btn-sm text-start">
                        <i class="bi bi-plus-circle me-2"></i>Add Custom Variable
                    </a>
                    <a href="{{ config('app.url') }}" target="_blank" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Preview Storefront
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Settings Forms Pane -->
    <div class="col-lg-8 col-xl-9">
        <div class="tab-content" id="settings-panes">
            @foreach($groups as $g)
                <div class="tab-pane fade {{ $g === $group ? 'show active' : '' }}" id="pane-{{ $g }}" role="tabpanel">
                    
                    <!-- SPECIAL CASE: HOMEPAGE HERO SLIDER -->
                    @if($g === 'hero')
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-semibold text-dark">
                                    <i class="bi {{ $groupIcons[$g] }} me-2 text-primary"></i>{{ $groupLabels[$g] }}
                                </h6>
                            </div>
                            <form action="{{ route('admin.settings.site.update-group', 'hero') }}" method="POST" id="hero-settings-form" class="needs-validation" novalidate>
                                @csrf
                                @method('PUT')
                                
                                <div class="card-body">
                                    <textarea name="settings[banners]" id="banners_json_input" class="d-none">{{ json_encode($banners) }}</textarea>
                                    
                                    @php
                                        $heroEnabledSetting = $settings['hero']->firstWhere('key', 'enabled');
                                        $isHeroEnabled = $heroEnabledSetting ? filter_var($heroEnabledSetting->value, FILTER_VALIDATE_BOOLEAN) : true;
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
                                                    {{ $isHeroEnabled ? 'checked' : '' }}
                                                    onchange="toggleSliderView()"
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <div id="slider-content-area" style="display: {{ $isHeroEnabled ? 'block' : 'none' }};">
                                        <div class="alert alert-info py-2 small mb-4">
                                            <i class="bi bi-info-circle me-1"></i> You can add between <strong>1 to 4</strong> homepage banners. Active banners will auto-slide on the website homepage.
                                        </div>

                                        <div id="banners-container" class="mb-4">
                                            <!-- Populated dynamically via JS -->
                                        </div>

                                        <div class="text-center pt-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm px-4" id="add-slide-btn" onclick="addSlide()">
                                                <i class="bi bi-plus-lg me-1"></i> Add New Banner Slide
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top d-flex justify-content-between py-3">
                                    <span class="text-muted small">Make sure to save changes after editing slides.</span>
                                    <button type="submit" class="btn btn-primary btn-sm px-4">
                                        <i class="bi bi-check-lg me-1"></i> Save Hero Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                    <!-- SPECIAL CASE: HEADER MENU NAVIGATION BUILDER -->
                    @elseif($g === 'navigation')
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-semibold text-dark">
                                    <i class="bi {{ $groupIcons[$g] }} me-2 text-primary"></i>{{ $groupLabels[$g] }}
                                </h6>
                            </div>
                            <form action="{{ route('admin.settings.site.update-group', 'navigation') }}" method="POST" class="needs-validation" novalidate>
                                @csrf
                                @method('PUT')
                                
                                <div class="card-body">
                                    @php
                                        $navSetting = $settings['navigation']->firstWhere('key', 'header_menu');
                                        $navValue = $navSetting ? $navSetting->value : '[]';
                                    @endphp
                                    <textarea name="settings[header_menu]" id="header_menu_input" class="d-none">{{ old('settings.header_menu', $navValue) }}</textarea>
                                    
                                    <div class="alert alert-info py-2 small mb-3">
                                        <i class="bi bi-info-circle me-1"></i> Drag and drop menu items or use buttons to configure and reorder. Add sub-items to create dropdowns.
                                    </div>

                                    <div id="menu-items-container" class="mb-4">
                                        <!-- Populated dynamically via JS -->
                                    </div>

                                    <!-- Add new item block (Highlighted UI for inputting labels/selections) -->
                                    <div class="card bg-light border-dashed">
                                        <div class="card-body p-3">
                                            <h6 class="card-title small fw-bold mb-3 text-dark"><i class="bi bi-plus-circle me-1 text-primary"></i>Add New Menu Item</h6>
                                            <div class="row g-2">
                                                <div class="col-md-3">
                                                    <select id="new-menu-type" class="form-select form-select-sm" onchange="toggleNewMenuInputs()">
                                                        <option value="link">Custom Link</option>
                                                        <option value="category">Category Link</option>
                                                        <option value="page">CMS Page Link</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" id="new-menu-label" class="form-control form-control-sm" placeholder="e.g. Offers">
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" id="new-menu-url" class="form-control form-control-sm" placeholder="URL (e.g. /offers)">
                                                    
                                                    <select id="new-menu-category" class="form-select form-select-sm d-none">
                                                        <option value="">Select Category</option>
                                                        @foreach($allCategories ?? [] as $cat)
                                                            <option value="/categories/{{ $cat->slug }}">{{ $cat->name_with_indent }}</option>
                                                        @endforeach
                                                    </select>

                                                    <select id="new-menu-page" class="form-select form-select-sm d-none">
                                                        <option value="">Select Page</option>
                                                        @foreach($allPages ?? [] as $pg)
                                                            <option value="/{{ $pg->slug }}">{{ $pg->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="addMenuItem()">
                                                        <i class="bi bi-plus-lg me-1"></i> Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top d-flex justify-content-between py-3">
                                    <span class="text-muted small">Click Save to make changes live on frontend header menu.</span>
                                    <button type="submit" class="btn btn-primary btn-sm px-4">
                                        <i class="bi bi-check-lg me-1"></i> Save Menu Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                    <!-- GENERAL DYNAMIC SETTINGS FORM -->
                    @else
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-semibold text-dark">
                                    <i class="bi {{ $groupIcons[$g] ?? 'bi-gear' }} me-2 text-primary"></i>{{ $groupLabels[$g] ?? ucfirst($g) }}
                                </h6>
                            </div>
                            <form action="{{ route('admin.settings.site.update-group', $g) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                @csrf
                                @method('PUT')
                                
                                <div class="card-body">
                                    @php
                                        $groupSettings = $settings[$g] ?? collect();
                                        $reqKeys = $requiredKeys[$g] ?? [];
                                        $requiredSettings = $groupSettings->filter(fn($s) => in_array($s->key, $reqKeys));
                                        $optionalSettings = $groupSettings->filter(fn($s) => !in_array($s->key, $reqKeys));
                                    @endphp

                                    <!-- 1. Required Settings Card (Visually Isolated) -->
                                    @if($requiredSettings->isNotEmpty())
                                        <div class="card required-card border border-danger-subtle shadow-none mb-4 bg-danger bg-opacity-10 bg-gradient">
                                            <div class="card-header bg-transparent border-bottom-0 py-2 pt-3">
                                                <span class="text-danger fw-bold small"><i class="bi bi-exclamation-triangle-fill me-1"></i> Required Configurations</span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    @foreach($requiredSettings as $setting)
                                                        <div class="col-12">
                                                            <label class="form-label small fw-bold text-dark mb-1">
                                                                {{ $setting->label }} <span class="text-danger">*</span>
                                                                @if($setting->description)
                                                                    <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $setting->description }}"></i>
                                                                @endif
                                                            </label>
                                                            @include('admin.settings.site.partials.input-field', ['setting' => $setting, 'required' => true])
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- 2. Optional Settings Card -->
                                    @if($optionalSettings->isNotEmpty())
                                        <div class="card border border-light-subtle shadow-none bg-light bg-opacity-25">
                                            <div class="card-header bg-transparent border-bottom-0 py-2 pt-3">
                                                <span class="text-secondary fw-semibold small">Optional / Additional Settings</span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    @foreach($optionalSettings as $setting)
                                                        <div class="col-12">
                                                            <label class="form-label small fw-semibold text-dark mb-1">
                                                                {{ $setting->label }}
                                                                @if($setting->description)
                                                                    <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $setting->description }}"></i>
                                                                @endif
                                                            </label>
                                                            @include('admin.settings.site.partials.input-field', ['setting' => $setting, 'required' => false])
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="card-footer bg-white border-top d-flex justify-content-between py-3">
                                    <span class="text-muted small">Active variables are saved to application configurations cache.</span>
                                    <button type="submit" class="btn btn-primary btn-sm px-4">
                                        <i class="bi bi-check-lg me-1"></i> Save {{ $groupLabels[$g] ?? ucfirst($g) }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Forms & Modals for Image deletions -->
<form id="delete-image-form" method="POST" style="display: none;" data-no-admin-ajax="1">
    @csrf
    @method('DELETE')
</form>

<!-- Include General Media Picker modal -->
@include('admin.media.picker')
@endsection

@push('scripts')
<script>
    // 1. Navigation Panel active tab management via URL query parameter
    function updateUrlGroup(groupName) {
        const url = new URL(window.location);
        url.searchParams.set('group', groupName);
        window.history.pushState({}, '', url);
        
        // Reset checkouts/forms if necessary
        if (groupName !== 'checkout') {
            document.querySelectorAll('.field-item-card').forEach(c => c.classList.remove('active'));
            const editorActive = document.getElementById('editorActiveState');
            const editorEmpty = document.getElementById('editorEmptyState');
            if (editorActive && editorEmpty) {
                editorActive.classList.add('d-none');
                editorEmpty.classList.remove('d-none');
            }
        }
    }

    // Restore query param active tab on page reload
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const group = urlParams.get('group');
        if (group) {
            const btn = document.getElementById('tab-btn-' + group);
            const pane = document.getElementById('pane-' + group);
            if (btn && pane) {
                // Remove active classes
                document.querySelectorAll('#settings-tabs .nav-link').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('#settings-panes .tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                });
                // Add active classes
                btn.classList.add('active');
                pane.classList.add('show', 'active');
            }
        }
    });

    // Initialize Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Form Client-side Validation (Bootstrap 5 styles validation flow)
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                        alert('Required fields are missing content. Please input values for all fields marked with * before saving.');
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()

    // 2. Logo height controls & Sync slider/preview
    function updateLogoPreview(settingKey, val) {
        const maxVal = settingKey === 'logo_height_desktop' ? 300 : (settingKey === 'logo_height_mobile' ? 200 : 200);
        val = Math.max(10, Math.min(maxVal, parseInt(val, 10) || 40));
        
        var numberInput = document.getElementById('logoHeightNumber_' + settingKey);
        var previewImg = document.getElementById('logoPreviewImage_' + settingKey);
        var sizeLabel = document.getElementById('logoSizeLabel_' + settingKey);

        if (numberInput) numberInput.value = val;
        if (previewImg) previewImg.style.height = val + 'px';
        if (sizeLabel) sizeLabel.textContent = val + 'px';
    }

    function updateLogoPreviewFromNumber(settingKey, val) {
        const maxVal = settingKey === 'logo_height_desktop' ? 300 : (settingKey === 'logo_height_mobile' ? 200 : 200);
        val = Math.max(10, Math.min(maxVal, parseInt(val, 10) || 40));
        
        var slider = document.getElementById('logoHeightSlider_' + settingKey);
        var previewImg = document.getElementById('logoPreviewImage_' + settingKey);
        var sizeLabel = document.getElementById('logoSizeLabel_' + settingKey);

        if (slider) slider.value = val;
        if (previewImg) previewImg.style.height = val + 'px';
        if (sizeLabel) sizeLabel.textContent = val + 'px';
    }

    function resetLogoSize(settingKey) {
        const standardSize = settingKey === 'logo_height_mobile' ? 32 : 40;
        updateLogoPreview(settingKey, standardSize);
    }

    // Toggle Order custom format visibility under general setting
    const modeSelect = document.querySelector('select[name="settings[order_number_generation_mode]"]');
    if (modeSelect) {
        function toggleCustomFormatField() {
            const wrapper = document.getElementById('custom_format_wrapper');
            if (wrapper) {
                wrapper.style.display = modeSelect.value === 'custom_format' ? 'block' : 'none';
            }
        }
        modeSelect.addEventListener('change', toggleCustomFormatField);
        // initial run
        setTimeout(toggleCustomFormatField, 100);
    }

    // Toggle label update for switches
    document.querySelectorAll('.form-check-input').forEach(function(checkbox) {
        if (!checkbox.classList.contains('row-selector') && checkbox.id !== 'setting_tax_enabled' && checkbox.id !== 'setting_checkout_form_enabled' && checkbox.id !== 'setting_enable_guest_checkout') {
            checkbox.addEventListener('change', function() {
                var label = this.nextElementSibling;
                if (label && label.classList.contains('form-check-label')) {
                    label.textContent = this.checked ? 'Enabled' : 'Disabled';
                }
            });
        }
    });

    // Delete image function
    function deleteImage(group, key) {
        if (confirm('Are you sure you want to delete this image?')) {
            var form = document.getElementById('delete-image-form');
            form.action = '/admin/settings/site/' + group + '/' + key + '/delete-image';
            form.submit();
        }
    }

    // Open media picker for settings images
    function openSettingMediaPicker(settingKey) {
        openMediaPicker('setting-input-' + settingKey, false, function(media) {
            document.getElementById('setting-input-' + settingKey).value = media.path;
            
            var previewContainer = document.getElementById('setting-image-preview-' + settingKey);
            if (previewContainer) {
                previewContainer.innerHTML = '<div class="position-relative d-inline-block">' +
                    '<img src="' + media.url + '" alt="Preview" class="img-thumbnail" style="max-height: 150px; max-width: 300px;">' +
                    '</div>';
            }

            if (settingKey === 'site_logo') {
                ['logo_height', 'logo_height_desktop', 'logo_height_mobile'].forEach(key => {
                    var logoPreviewImg = document.getElementById('logoPreviewImage_' + key);
                    var logoPlaceholder = document.getElementById('logoPreviewPlaceholder_' + key);
                    
                    if (logoPreviewImg) {
                        logoPreviewImg.src = media.url;
                    } else if (logoPlaceholder) {
                        var slider = document.getElementById('logoHeightSlider_' + key);
                        var h = slider ? slider.value : 40;
                        logoPlaceholder.outerHTML = '<img src="' + media.url + '" alt="Logo Preview" id="logoPreviewImage_' + key + '" style="height: ' + h + 'px; width: auto; object-fit: contain;">';
                    }
                });
            }
        });
    }

    // 3. Navigation Menu Builder JavaScript logic
    window.toggleNewMenuInputs = function() {
        const type = document.getElementById('new-menu-type').value;
        document.getElementById('new-menu-url').classList.toggle('d-none', type !== 'link');
        document.getElementById('new-menu-category').classList.toggle('d-none', type !== 'category');
        document.getElementById('new-menu-page').classList.toggle('d-none', type !== 'page');
        
        if (type === 'category') document.getElementById('new-menu-category').selectedIndex = 0;
        if (type === 'page') document.getElementById('new-menu-page').selectedIndex = 0;
    };

    (function() {
        const menuInput = document.getElementById('header_menu_input');
        if (!menuInput) return;

        let menuItems = [];
        try {
            menuItems = JSON.parse(menuInput.value) || [];
        } catch (e) {
            menuItems = [];
        }

        function renderMenu() {
            const container = document.getElementById('menu-items-container');
            container.innerHTML = '';
            
            if (menuItems.length === 0) {
                container.innerHTML = '<div class="text-center py-4 bg-light border border-dashed rounded text-muted">No menu items yet. Add your first item below.</div>';
            }

            menuItems.forEach((item, index) => {
                container.appendChild(createMenuItemElement(item, [index]));
            });
            menuInput.value = JSON.stringify(menuItems);
        }

        function createMenuItemElement(item, path) {
            const wrapper = document.createElement('div');
            wrapper.className = 'menu-item-wrapper mb-2';
            
            const content = document.createElement('div');
            content.className = 'card shadow-sm border-gray-200';
            
            const pathStr = path.join(',');
            const isSubItem = path.includes('children');
            const sanitizedPathId = pathStr.replace(/,/g, '_');
            
            content.innerHTML = `
                <div class="card-body p-2">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-grip-vertical text-muted"></i>
                        <input type="text" class="form-control form-control-sm" style="width: 150px" value="${item.label}" onchange="updateItem('${pathStr}', 'label', this.value)" placeholder="Label">
                        <input type="text" class="form-control form-control-sm flex-grow-1" value="${item.url}" onchange="updateItem('${pathStr}', 'url', this.value)" placeholder="URL">
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-xs btn-outline-info" onclick="indentItem('${pathStr}')" title="Indent"><i class="bi bi-chevron-right"></i></button>
                            <button type="button" class="btn btn-xs btn-outline-info" onclick="outdentItem('${pathStr}')" title="Outdent"><i class="bi bi-chevron-left"></i></button>
                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="moveItem('${pathStr}', -1)" title="Move Up"><i class="bi bi-chevron-up"></i></button>
                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="moveItem('${pathStr}', 1)" title="Move Down"><i class="bi bi-chevron-down"></i></button>
                            <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeItem('${pathStr}')" title="Remove"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 ms-4 small text-muted">
                        <div class="form-check form-switch m-0 d-flex align-items-center gap-1">
                            <input class="form-check-input" type="checkbox" id="highlight_${sanitizedPathId}" ${item.highlight ? 'checked' : ''} onchange="updateItem('${pathStr}', 'highlight', this.checked); renderMenu();">
                            <label class="form-check-label" for="highlight_${sanitizedPathId}">Highlight Item</label>
                        </div>
                        <div class="d-flex align-items-center gap-2 ${item.highlight ? '' : 'd-none'}">
                            <span>Background:</span>
                            <input type="color" class="form-control form-control-color form-control-sm p-0 border-0" style="width:30px; height:24px; cursor:pointer;" value="${item.highlight_bg || '#1f1f1f'}" onchange="updateItem('${pathStr}', 'highlight_bg', this.value)">
                            <span class="ms-1">Text/Border:</span>
                            <input type="color" class="form-control form-control-color form-control-sm p-0 border-0" style="width:30px; height:24px; cursor:pointer;" value="${item.highlight_text || '#d4af37'}" onchange="updateItem('${pathStr}', 'highlight_text', this.value)">
                        </div>
                    </div>
                </div>
            `;
            
            wrapper.appendChild(content);
            
            if (item.children && item.children.length > 0) {
                const childrenContainer = document.createElement('div');
                childrenContainer.className = 'ms-5 mt-2 border-start ps-3';
                item.children.forEach((child, cIdx) => {
                    childrenContainer.appendChild(createMenuItemElement(child, [...path, 'children', cIdx]));
                });
                wrapper.appendChild(childrenContainer);
            }
            
            return wrapper;
        }

        window.addMenuItem = function() {
            const type = document.getElementById('new-menu-type').value;
            const labelInput = document.getElementById('new-menu-label');
            let url = '';
            
            if (type === 'link') url = document.getElementById('new-menu-url').value.trim();
            else if (type === 'category') url = document.getElementById('new-menu-category').value;
            else if (type === 'page') url = document.getElementById('new-menu-page').value;

            if (labelInput.value.trim() === '' || url === '') {
                alert('Please provide both a label and a URL/selection.');
                return;
            }

            menuItems.push({
                label: labelInput.value.trim(),
                url: url,
                type: type,
                highlight: false,
                highlight_bg: '#1f1f1f',
                highlight_text: '#d4af37',
                children: []
            });

            labelInput.value = '';
            document.getElementById('new-menu-url').value = '';
            renderMenu();
        };

        window.updateItem = function(pathStr, field, value) {
            const path = pathStr.split(',');
            let target = menuItems;
            for (let i = 0; i < path.length - 1; i++) {
                target = target[path[i]];
            }
            target[path[path.length - 1]][field] = value;
            menuInput.value = JSON.stringify(menuItems);
        };

        window.removeItem = function(pathStr) {
            if (!confirm('Are you sure you want to remove this item and all its sub-items?')) return;
            const path = pathStr.split(',');
            let target = menuItems;
            for (let i = 0; i < path.length - 1; i++) {
                target = target[path[i]];
            }
            target.splice(path[path.length - 1], 1);
            renderMenu();
        };

        window.indentItem = function(pathStr) {
            const path = pathStr.split(',');
            const index = parseInt(path[path.length - 1]);
            if (index === 0) return;

            let parentArray = menuItems;
            for (let i = 0; i < path.length - 1; i++) {
                parentArray = parentArray[path[i]];
            }

            const itemToMove = parentArray.splice(index, 1)[0];
            const prevItem = parentArray[index - 1];
            
            if (!prevItem.children) prevItem.children = [];
            prevItem.children.push(itemToMove);
            
            renderMenu();
        };

        window.outdentItem = function(pathStr) {
            const path = pathStr.split(',');
            if (!path.includes('children')) return;

            const index = parseInt(path[path.length - 1]);
            const parentPath = path.slice(0, -2);
            let parentArray = menuItems;
            for (let i = 0; i < parentPath.length - 1; i++) {
                parentArray = parentArray[parentPath[i]];
            }
            const parentIndex = parseInt(parentPath[parentPath.length - 1]);
            
            let currentArray = parentArray[parentIndex].children;
            const itemToMove = currentArray.splice(index, 1)[0];
            
            parentArray.splice(parentIndex + 1, 0, itemToMove);
            renderMenu();
        };

        window.moveItem = function(pathStr, direction) {
            const path = pathStr.split(',');
            const index = parseInt(path[path.length - 1]);
            let targetArray = menuItems;
            
            for (let i = 0; i < path.length - 1; i++) {
                targetArray = targetArray[path[i]];
            }
            
            if (index + direction < 0 || index + direction >= targetArray.length) return;
            
            const temp = targetArray[index];
            targetArray[index] = targetArray[index + direction];
            targetArray[index + direction] = temp;
            
            renderMenu();
        };

        renderMenu();
    })();

    // 4. Hero Slider Configurator JavaScript logic
    let banners = @json($banners);
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

    function toggleSliderView() {
        const isChecked = document.getElementById('hero_enabled').checked;
        const area = document.getElementById('slider-content-area');
        if (area) {
            area.style.display = isChecked ? 'block' : 'none';
        }
    }

    function renderBanners() {
        const container = document.getElementById('banners-container');
        if (!container) return;
        
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
            card.className = 'card mb-4 slide-item border border-light-subtle shadow-none';
            card.dataset.index = index;

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
                        ${slide.enabled ? '<span class="badge bg-success ms-2 px-2 py-0.5" style="font-size:0.6rem;">Active</span>' : '<span class="badge bg-secondary ms-2 px-2 py-0.5" style="font-size:0.6rem;">Disabled</span>'}
                    </span>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-xs" onclick="moveSlide(${index}, -1)" ${index === 0 ? 'disabled' : ''}>
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-xs" onclick="moveSlide(${index}, 1)" ${index === banners.length - 1 ? 'disabled' : ''}>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-xs ms-2" onclick="deleteSlide(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Title</label>
                            <input type="text" class="form-control form-control-sm" value="${escapeHtml(slide.title || '')}" oninput="updateSlideField(${index}, 'title', this.value)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Subtitle</label>
                            <input type="text" class="form-control form-control-sm" value="${escapeHtml(slide.subtitle || '')}" oninput="updateSlideField(${index}, 'subtitle', this.value)">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted mb-1">Description (Optional)</label>
                            <textarea class="form-control form-control-sm" rows="2" oninput="updateSlideField(${index}, 'description', this.value)">${escapeHtml(slide.description || '')}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1 d-block">Background Image</label>
                            <div id="slide-image-preview-${index}" class="mb-2">
                                ${imagePreviewHtml}
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-xs" onclick="openSlideMediaPicker(${index})">
                                <i class="bi bi-images me-1"></i> Select Image
                            </button>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Button Text</label>
                            <input type="text" class="form-control form-control-sm" value="${escapeHtml(slide.button_text || '')}" oninput="updateSlideField(${index}, 'button_text', this.value)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Button Link</label>
                            <input type="text" class="form-control form-control-sm" value="${escapeHtml(slide.button_link || '')}" oninput="updateSlideField(${index}, 'button_link', this.value)">
                        </div>
                        <div class="col-12 mt-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="slide-enabled-${index}" ${slide.enabled !== false ? 'checked' : ''} onchange="updateSlideField(${index}, 'enabled', this.checked)">
                                <label class="form-check-label small" for="slide-enabled-${index}">Show this slide</label>
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

    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function updateAddButtonState() {
        const btn = document.getElementById('add-slide-btn');
        if (!btn) return;
        if (banners.length >= 4) {
            btn.disabled = true;
            btn.innerText = 'Max 4 slides reached';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i> Add New Banner Slide';
        }
    }

    function updateSlideField(index, field, value) {
        if (banners[index]) {
            banners[index][field] = value;
            if (field === 'enabled') {
                renderBanners();
            } else {
                serializeBanners();
            }
        }
    }

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

    function deleteSlide(index) {
        if (confirm('Are you sure you want to remove this slide?')) {
            banners.splice(index, 1);
            renderBanners();
        }
    }

    function moveSlide(index, direction) {
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= banners.length) return;
        const temp = banners[index];
        banners[index] = banners[targetIndex];
        banners[targetIndex] = temp;
        renderBanners();
    }

    function openSlideMediaPicker(slideIndex) {
        openMediaPicker('banners_json_input', false, function(media) {
            if (banners[slideIndex]) {
                banners[slideIndex].image = media.path;
                renderBanners();
            }
        });
    }

    function removeSlideImage(slideIndex) {
        if (banners[slideIndex]) {
            banners[slideIndex].image = '';
            renderBanners();
        }
    }

    function serializeBanners() {
        const input = document.getElementById('banners_json_input');
        if (input) {
            input.value = JSON.stringify(banners);
        }
    }

    // Initialize Hero Slider if present
    if (document.getElementById('banners-container')) {
        renderBanners();
        toggleSliderView();
    }

    // 5. Checkout Field Manager JavaScript Logic
    const defaultSchema = @json($checkoutFieldSchema ?? null);
    const checkoutFieldRows = document.getElementById('checkoutFieldRows');
    
    if (checkoutFieldRows) {
        const schemaInput = document.getElementById('checkoutFieldSchemaInput');
        const selectAll = document.getElementById('selectAllVisible');
        const addFieldBtn = document.getElementById('addFieldBtn');
        const removeSelectedBtn = document.getElementById('removeSelectedBtn');
        const enableSelectedBtn = document.getElementById('enableSelectedBtn');
        const disableSelectedBtn = document.getElementById('disableSelectedBtn');
        const resetDefaultsBtn = document.getElementById('resetDefaultsBtn');
        const saveFieldBtn = document.getElementById('saveFieldBtn');
        const cancelFieldBtn = document.getElementById('cancelFieldBtn');
        const searchFieldInput = document.getElementById('searchFieldInput');

        const editorTitle = document.getElementById('checkoutFieldModalTitle');
        const editorSection = document.getElementById('fieldEditorSection');
        const editorType = document.getElementById('fieldEditorType');
        const editorKey = document.getElementById('fieldEditorKey');
        const editorLabel = document.getElementById('fieldEditorLabel');
        const editorPlaceholder = document.getElementById('fieldEditorPlaceholder');
        const editorValidations = document.getElementById('fieldEditorValidations');
        const editorRequired = document.getElementById('fieldEditorRequired');
        const editorEnabled = document.getElementById('fieldEditorEnabled');
        const editorOptionsWrapper = document.getElementById('fieldEditorOptionsWrapper');
        const editorOptions = document.getElementById('fieldEditorOptions');

        let activeSection = 'billing';
        let selectedIds = new Set();
        let dragStartIndex = null;
        let editingFieldId = null;

        const sanitizeFieldKey = (value) => {
            return String(value || '')
                .trim()
                .toLowerCase()
                .replace(/[^a-z0-9_]+/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_+|_+$/g, '');
        };

        const makeFieldId = () => {
            return `field_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
        };

        const normalizeOptions = (raw) => {
            if (!Array.isArray(raw)) return [];
            return raw
                .filter(item => item && typeof item === 'object')
                .map(item => {
                    const value = String(item.value || '').trim();
                    const label = String(item.label || '').trim();
                    return (!value || !label) ? null : { value, label };
                })
                .filter(Boolean);
        };

        const normalizeSchema = (rawSchema) => {
            const normalized = { billing: [], shipping: [], additional: [] };
            sectionOrder.forEach((section) => {
                const rows = Array.isArray(rawSchema?.[section]) ? rawSchema[section] : [];
                let sortOrder = 1;
                rows.forEach((field) => {
                    if (!field || typeof field !== 'object') return;
                    const key = sanitizeFieldKey(field.key || '');
                    if (!key) return;
                    normalized[section].push({
                        id: String(field.id || makeFieldId()),
                        section,
                        key,
                        type: String(field.type || 'text').trim(),
                        label: String(field.label || key).trim(),
                        placeholder: String(field.placeholder || '').trim(),
                        validations: Array.isArray(field.validations) ? field.validations.map(String).filter(Boolean) : [],
                        required: Boolean(field.required),
                        enabled: field.enabled === undefined ? true : Boolean(field.enabled),
                        options: normalizeOptions(field.options),
                        sort_order: sortOrder++,
                    });
                });
            });
            return normalized;
        };

        const sectionOrder = ['billing', 'shipping', 'additional'];
        const sectionLabels = { billing: 'Billing Fields', shipping: 'Shipping Fields', additional: 'Additional Fields' };
        
        let checkoutSchema = normalizeSchema(defaultSchema);
        const defaultNormalizedSchema = normalizeSchema(defaultSchema);

        const updateSchemaInput = () => {
            const payload = {
                billing: checkoutSchema.billing.map((field, index) => ({ ...field, sort_order: index + 1 })),
                shipping: checkoutSchema.shipping.map((field, index) => ({ ...field, sort_order: index + 1 })),
                additional: checkoutSchema.additional.map((field, index) => ({ ...field, sort_order: index + 1 })),
            };
            if (schemaInput) {
                schemaInput.value = JSON.stringify(payload);
            }
        };

        const renderRows = () => {
            const fields = checkoutSchema[activeSection] || [];
            const searchQuery = String(searchFieldInput?.value || '').trim().toLowerCase();
            const filteredFields = fields.filter(f => 
                f.key.toLowerCase().includes(searchQuery) || 
                f.label.toLowerCase().includes(searchQuery) ||
                f.type.toLowerCase().includes(searchQuery)
            );

            checkoutFieldRows.innerHTML = '';

            if (filteredFields.length === 0) {
                checkoutFieldRows.innerHTML = `
                    <div class="text-center text-muted py-4 bg-light border border-dashed rounded">
                        <i class="bi bi-search fs-4 d-block mb-1"></i>No fields found.
                    </div>
                `;
                selectAll.checked = false;
                updateSchemaInput();
                return;
            }

            filteredFields.forEach((field, index) => {
                const card = document.createElement('div');
                card.className = `card mb-2 field-item-card border border-light-subtle shadow-none ${editingFieldId === field.id ? 'active' : ''}`;
                card.dataset.fieldId = field.id;
                card.dataset.index = String(index);
                card.draggable = true;

                card.innerHTML = `
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2 flex-grow-1 select-field-trigger">
                            <div class="drag-handle text-muted" style="cursor: move;"><i class="bi bi-grip-vertical fs-5"></i></div>
                            <input type="checkbox" class="form-check-input row-selector m-0" data-id="${field.id}" ${selectedIds.has(field.id) ? 'checked' : ''} onclick="event.stopPropagation()">
                            <div class="ms-1">
                                <div class="fw-semibold text-dark small">${field.label}</div>
                                <div class="text-muted font-monospace" style="font-size: 0.6rem;"><code>${field.key}</code> • ${field.type}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1 select-field-trigger">
                            ${field.required ? '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10" style="font-size:0.55rem; padding: 0.15rem 0.3rem;">Req</span>' : ''}
                            ${field.enabled ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10" style="font-size:0.55rem; padding: 0.15rem 0.3rem;">Active</span>' : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10" style="font-size:0.55rem; padding: 0.15rem 0.3rem;">Off</span>'}
                            <i class="bi bi-chevron-right text-muted ms-1" style="font-size: 0.75rem;"></i>
                        </div>
                    </div>
                `;

                // Select card click handlers
                card.querySelectorAll('.select-field-trigger').forEach(trigger => {
                    trigger.addEventListener('click', (e) => {
                        e.stopPropagation();
                        document.querySelectorAll('.field-item-card').forEach(c => c.classList.remove('active'));
                        card.classList.add('active');
                        openEditor(activeSection, field);
                    });
                });

                // Drag & drop handlers
                card.addEventListener('dragstart', () => {
                    dragStartIndex = index;
                    card.classList.add('table-active');
                });
                card.addEventListener('dragend', () => {
                    dragStartIndex = null;
                    card.classList.remove('table-active');
                });
                card.addEventListener('dragover', (e) => e.preventDefault());
                card.addEventListener('drop', (e) => {
                    e.preventDefault();
                    const dropIndex = Number(card.dataset.index || -1);
                    if (dragStartIndex === null || dropIndex < 0 || dragStartIndex === dropIndex) return;

                    const sourceField = filteredFields[dragStartIndex];
                    const destField = filteredFields[dropIndex];
                    const sectionFields = checkoutSchema[activeSection] || [];
                    const actualDragIndex = sectionFields.findIndex(f => f.id === sourceField.id);
                    const actualDropIndex = sectionFields.findIndex(f => f.id === destField.id);

                    if (actualDragIndex >= 0 && actualDropIndex >= 0) {
                        const moved = sectionFields.splice(actualDragIndex, 1)[0];
                        sectionFields.splice(actualDropIndex, 0, moved);
                        checkoutSchema[activeSection] = sectionFields.map((item, itemIndex) => ({ ...item, sort_order: itemIndex + 1 }));
                        renderRows();
                    }
                });

                checkoutFieldRows.appendChild(card);
            });

            selectAll.checked = filteredFields.length > 0 && filteredFields.every(f => selectedIds.has(f.id));
            if (selectedCountText) {
                selectedCountText.textContent = `${selectedIds.size} field(s) selected`;
            }
            updateSchemaInput();
        };

        const openEditor = (section, field = null) => {
            editingFieldId = field ? field.id : null;
            document.getElementById('editorEmptyState').classList.add('d-none');
            document.getElementById('editorActiveState').classList.remove('d-none');

            editorKey.classList.remove('is-invalid');
            editorLabel.classList.remove('is-invalid');
            editorOptions.classList.remove('is-invalid');

            editorTitle.textContent = field ? 'Edit Field Settings' : 'Create New Field';
            document.getElementById('fieldSectionBadge').textContent = sectionLabels[section];
            editorSection.value = section;
            editorType.value = field?.type || 'text';
            editorKey.value = field?.key || '';
            editorLabel.value = field?.label || '';
            editorPlaceholder.value = field?.placeholder || '';
            editorValidations.value = Array.isArray(field?.validations) ? field.validations.join(',') : '';
            editorRequired.checked = Boolean(field?.required);
            editorEnabled.checked = field?.enabled === undefined ? true : Boolean(field?.enabled);
            editorOptions.value = Array.isArray(field?.options) ? field.options.map(o => `${o.value}|${o.label}`).join('\n') : '';

            editorOptionsWrapper.classList.toggle('d-none', editorType.value !== 'select');
            if (field) {
                editorLabel.focus();
            } else {
                editorKey.focus();
            }
        };

        const closeEditor = () => {
            editingFieldId = null;
            document.getElementById('editorActiveState').classList.add('d-none');
            document.getElementById('editorEmptyState').classList.remove('d-none');
            document.querySelectorAll('.field-item-card').forEach(c => c.classList.remove('active'));
        };

        const parseOptionsTextarea = () => {
            const lines = String(editorOptions.value || '').split('\n');
            const options = [];
            lines.forEach((line) => {
                const raw = line.trim();
                if (!raw) return;
                const parts = raw.split('|');
                const value = parts[0] ? parts[0].trim() : '';
                const label = parts[1] ? parts[1].trim() : value;
                if (value) options.push({ value, label });
            });
            return options;
        };

        const validateFieldForm = () => {
            let isValid = true;
            editorKey.classList.remove('is-invalid');
            editorLabel.classList.remove('is-invalid');
            editorOptions.classList.remove('is-invalid');

            const keyRaw = editorKey.value;
            const key = sanitizeFieldKey(keyRaw);
            const label = String(editorLabel.value || '').trim();
            const type = editorType.value;

            if (!keyRaw.trim()) {
                editorKey.classList.add('is-invalid');
                document.getElementById('fieldEditorKeyFeedback').textContent = 'Field name is required.';
                isValid = false;
            } else if (!key) {
                editorKey.classList.add('is-invalid');
                document.getElementById('fieldEditorKeyFeedback').textContent = 'Use lowercase letters, numbers, and underscores.';
                isValid = false;
            } else {
                let isDuplicate = false;
                sectionOrder.forEach((sec) => {
                    const list = checkoutSchema[sec] || [];
                    list.forEach((f) => {
                        if (f.key === key && f.id !== editingFieldId) isDuplicate = true;
                    });
                });
                if (isDuplicate) {
                    editorKey.classList.add('is-invalid');
                    document.getElementById('fieldEditorKeyFeedback').textContent = `Field key "${key}" is already in use.`;
                    isValid = false;
                }
            }

            if (!label) {
                editorLabel.classList.add('is-invalid');
                document.getElementById('fieldEditorLabelFeedback').textContent = 'Field label is required.';
                isValid = false;
            }

            if (type === 'select') {
                const options = parseOptionsTextarea();
                if (options.length === 0) {
                    editorOptions.classList.add('is-invalid');
                    document.getElementById('fieldEditorOptionsFeedback').textContent = 'Select dropdown requires options.';
                    isValid = false;
                }
            }
            return isValid;
        };

        const saveFieldFromEditor = () => {
            if (!validateFieldForm()) return;

            const section = editorSection.value;
            const key = sanitizeFieldKey(editorKey.value);
            const label = String(editorLabel.value || '').trim();
            const type = editorType.value;
            const validations = String(editorValidations.value || '').split(',').map(v => v.trim().toLowerCase()).filter(Boolean);
            const options = type === 'select' ? parseOptionsTextarea() : [];
            const targetSection = checkoutSchema[section] || [];

            if (editingFieldId) {
                const sourceSection = sectionOrder.find(s => (checkoutSchema[s] || []).some(f => f.id === editingFieldId));
                if (!sourceSection) return;

                const sourceRows = checkoutSchema[sourceSection] || [];
                const foundIndex = sourceRows.findIndex(f => f.id === editingFieldId);
                if (foundIndex < 0) return;

                const current = sourceRows[foundIndex];
                const updated = {
                    ...current,
                    section, key, type, label,
                    placeholder: String(editorPlaceholder.value || '').trim(),
                    validations,
                    required: editorRequired.checked,
                    enabled: editorEnabled.checked,
                    options,
                };

                sourceRows.splice(foundIndex, 1);
                checkoutSchema[sourceSection] = sourceRows.map((f, i) => ({ ...f, sort_order: i + 1 }));
                targetSection.push(updated);
                checkoutSchema[section] = targetSection.map((f, i) => ({ ...f, sort_order: i + 1 }));
                selectedIds.delete(editingFieldId);
            } else {
                targetSection.push({
                    id: makeFieldId(),
                    section, key, type, label,
                    placeholder: String(editorPlaceholder.value || '').trim(),
                    validations,
                    required: editorRequired.checked,
                    enabled: editorEnabled.checked,
                    options,
                    sort_order: targetSection.length + 1,
                });
                checkoutSchema[section] = targetSection;
            }

            activeSection = section;
            setActiveTab(section);
            closeEditor();
            renderRows();
        };

        const setActiveTab = (section) => {
            activeSection = section;
            document.querySelectorAll('#checkoutFieldTabs button').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.section === section);
            });
            if (searchFieldInput) searchFieldInput.value = '';
            renderRows();
        };

        // Bind events
        document.querySelectorAll('#checkoutFieldTabs button').forEach(tab => {
            tab.addEventListener('click', () => setActiveTab(tab.dataset.section || 'billing'));
        });

        checkoutFieldRows.addEventListener('change', (e) => {
            const target = e.target;
            if (target instanceof HTMLInputElement && target.classList.contains('row-selector')) {
                const id = target.dataset.id;
                if (target.checked) selectedIds.add(id); else selectedIds.delete(id);
                renderRows();
            }
        });

        selectAll.addEventListener('change', () => {
            const fields = checkoutSchema[activeSection] || [];
            const searchQuery = String(searchFieldInput?.value || '').trim().toLowerCase();
            const filteredFields = fields.filter(f => 
                f.key.toLowerCase().includes(searchQuery) || 
                f.label.toLowerCase().includes(searchQuery) ||
                f.type.toLowerCase().includes(searchQuery)
            );
            if (selectAll.checked) {
                filteredFields.forEach(f => selectedIds.add(f.id));
            } else {
                filteredFields.forEach(f => selectedIds.delete(f.id));
            }
            renderRows();
        });

        addFieldBtn.addEventListener('click', () => openEditor(activeSection, null));

        removeSelectedBtn.addEventListener('click', () => {
            if (selectedIds.size === 0) return;
            if (!confirm('Remove selected fields?')) return;
            sectionOrder.forEach(s => {
                checkoutSchema[s] = (checkoutSchema[s] || []).filter(f => !selectedIds.has(f.id)).map((f, i) => ({ ...f, sort_order: i + 1 }));
            });
            selectedIds = new Set();
            closeEditor();
            renderRows();
        });

        enableSelectedBtn.addEventListener('click', () => {
            if (selectedIds.size === 0) return;
            sectionOrder.forEach(s => {
                checkoutSchema[s] = (checkoutSchema[s] || []).map(f => selectedIds.has(f.id) ? { ...f, enabled: true } : f);
            });
            renderRows();
        });

        disableSelectedBtn.addEventListener('click', () => {
            if (selectedIds.size === 0) return;
            sectionOrder.forEach(s => {
                checkoutSchema[s] = (checkoutSchema[s] || []).map(f => selectedIds.has(f.id) ? { ...f, enabled: false, required: false } : f);
            });
            renderRows();
        });

        resetDefaultsBtn.addEventListener('click', () => {
            if (!confirm('Reset checkout fields to default schema?')) return;
            checkoutSchema = normalizeSchema(defaultNormalizedSchema);
            selectedIds = new Set();
            closeEditor();
            setActiveTab('billing');
        });

        saveFieldBtn.addEventListener('click', saveFieldFromEditor);
        if (cancelFieldBtn) cancelFieldBtn.addEventListener('click', closeEditor);
        if (searchFieldInput) searchFieldInput.addEventListener('input', renderRows);
        editorType.addEventListener('change', () => editorOptionsWrapper.classList.toggle('d-none', editorType.value !== 'select'));

        // Checkout settings form submit validation intercept
        const chkForm = document.getElementById('checkoutSettingsForm');
        chkForm.addEventListener('submit', (e) => {
            const activeStateVisible = !document.getElementById('editorActiveState').classList.contains('d-none');
            if (activeStateVisible) {
                if (validateFieldForm()) {
                    saveFieldFromEditor();
                } else {
                    e.preventDefault();
                    alert('Please resolve validation errors in the field properties editor before saving settings.');
                    const firstInvalid = document.querySelector('.is-invalid');
                    if (firstInvalid) firstInvalid.focus();
                    return;
                }
            }
            updateSchemaInput();
        });

        const taxToggle = document.getElementById('setting_tax_enabled');
        const taxInput = document.getElementById('setting_tax_percentage');
        const syncTaxState = () => { if (taxToggle && taxInput) taxInput.toggleAttribute('disabled', !taxToggle.checked); };
        if (taxToggle && taxInput) {
            taxToggle.addEventListener('change', syncTaxState);
            syncTaxState();
        }

        updateSchemaInput();
        renderRows();
    }
</script>
@endpush

@extends('admin.layouts.app')

@section('title', $groupLabel . ' Settings')
@section('page-title', $groupLabel . ' Settings')

@push('css')
<style>
    .cursor-move { cursor: move; }
    .btn-xs { padding: 0.15rem 0.35rem; font-size: 0.75rem; border-radius: 0.2rem; }
    .menu-item-wrapper .card:hover { border-color: var(--bs-primary); }
    .border-dashed { border-style: dashed !important; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-pencil-square me-2"></i>{{ $groupLabel }}
                </h6>
                <a href="{{ route('admin.settings.site.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
            <form action="{{ route('admin.settings.site.update-group', $group) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @foreach($settings as $setting)
                        <div class="mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}" 
                             @if($setting->key === 'order_number_custom_format')
                                 @php
                                     $currentMode = old('settings.order_number_generation_mode', $settings->firstWhere('key', 'order_number_generation_mode')?->value ?? 'timestamp_random');
                                 @endphp
                                 id="custom_format_wrapper" 
                                 style="display: {{ $currentMode === 'custom_format' ? 'block' : 'none' }};"
                             @endif
                        >
                            <label class="form-label fw-semibold">
                                {{ $setting->label }}
                                @if($setting->description)
                                    <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $setting->description }}"></i>
                                @endif
                            </label>

                            @switch($setting->type)
                                @case('textarea')
                                    <textarea 
                                        name="settings[{{ $setting->key }}]" 
                                        class="form-control" 
                                        rows="3"
                                        placeholder="{{ $setting->key === 'whatsapp_order_message' ? 'Example: Assalamu Alaikum, I want to order: {product_name}. Product URL: {product_url}. Quantity: {quantity}.' : 'Enter ' . strtolower($setting->label) }}"
                                    >{{ old("settings.{$setting->key}", $setting->value) }}</textarea>
                                    @break

                                @case('boolean')
                                    <div class="form-check form-switch">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            name="settings[{{ $setting->key }}]"
                                            id="setting_{{ $setting->key }}"
                                            value="1"
                                            {{ $setting->value ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="setting_{{ $setting->key }}">
                                            {{ $setting->value ? 'Enabled' : 'Disabled' }}
                                        </label>
                                    </div>
                                    @break

                                @case('number')
                                    @if($setting->key === 'product_grid_columns_desktop')
                                        <select
                                            name="settings[{{ $setting->key }}]"
                                            class="form-select"
                                        >
                                            @foreach([3, 4, 5, 6] as $columnCount)
                                                <option
                                                    value="{{ $columnCount }}"
                                                    {{ (string) old("settings.{$setting->key}", $setting->value ?: 5) === (string) $columnCount ? 'selected' : '' }}
                                                >
                                                    {{ $columnCount }} products per row
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">Number of columns on desktop screens.</small>
                                    @elseif($setting->key === 'product_grid_columns_mobile')
                                        <select
                                            name="settings[{{ $setting->key }}]"
                                            class="form-select"
                                        >
                                            @foreach([1, 2] as $columnCount)
                                                <option
                                                    value="{{ $columnCount }}"
                                                    {{ (string) old("settings.{$setting->key}", $setting->value ?: 2) === (string) $columnCount ? 'selected' : '' }}
                                                >
                                                    {{ $columnCount }} products per row
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">Number of columns on mobile devices.</small>
                                    @elseif($setting->key === 'logo_height' || $setting->key === 'logo_height_desktop' || $setting->key === 'logo_height_mobile')
                                        @php
                                            $currentVal = (int) old("settings.{$setting->key}", $setting->value ?: ($setting->key === 'logo_height_mobile' ? 32 : 40));
                                            $maxVal = match($setting->key) {
                                                'logo_height_desktop' => 300,
                                                'logo_height_mobile' => 200,
                                                default => 200,
                                            };
                                            $currentVal = max(10, min($maxVal, $currentVal));
                                            
                                            $logoSetting = $settings->firstWhere('key', 'site_logo');
                                            $logoPath = $logoSetting ? ltrim((string) $logoSetting->value, '/') : '';
                                            $logoPreviewUrl = '';
                                            if ($logoPath !== '') {
                                                $logoPreviewUrl = (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://'))
                                                    ? $logoSetting->value
                                                    : ((str_starts_with($logoPath, 'media/') || str_starts_with($logoPath, 'storage/'))
                                                        ? asset($logoPath)
                                                        : Storage::disk('public')->url($logoPath));
                                            }
                                            $deviceId = str_contains($setting->key, 'mobile') ? 'mobile' : 'desktop';
                                        @endphp
                                        <div class="logo-size-control" data-device="{{ $deviceId }}">
                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                <input
                                                    type="range"
                                                    class="form-range flex-grow-1"
                                                    id="logoHeightSlider_{{ $setting->key }}"
                                                    min="10"
                                                    max="{{ $maxVal }}"
                                                    step="1"
                                                    value="{{ $currentVal }}"
                                                    oninput="updateLogoPreview('{{ $setting->key }}', this.value)"
                                                >
                                                <div class="d-flex align-items-center gap-1">
                                                    <input
                                                        type="number"
                                                        class="form-control form-control-sm text-center"
                                                        style="width: 68px;"
                                                        id="logoHeightNumber_{{ $setting->key }}"
                                                        name="settings[{{ $setting->key }}]"
                                                        value="{{ $currentVal }}"
                                                        min="10"
                                                        max="{{ $maxVal }}"
                                                        oninput="updateLogoPreviewFromNumber('{{ $setting->key }}', this.value)"
                                                    >
                                                    <span class="text-muted small me-2">px</span>
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-outline-secondary btn-sm p-1 leading-none border-0" 
                                                        onclick="resetLogoSize('{{ $setting->key }}')"
                                                        title="Reset to standard size"
                                                        style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;"
                                                    >
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Live Preview --}}
                                            <div class="border rounded-3 p-3 bg-light">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="text-muted small fw-semibold">
                                                        <i class="bi bi-{{ $deviceId === 'mobile' ? 'phone' : 'display' }} me-1"></i>
                                                        {{ ucfirst($deviceId) }} Preview
                                                    </span>
                                                    <span class="badge bg-secondary" id="logoSizeLabel_{{ $setting->key }}">{{ $currentVal }}px</span>
                                                </div>
                                                <div class="bg-white border rounded-2 p-3 d-flex align-items-center justify-content-center" style="min-height: 80px;">
                                                    @if($logoPreviewUrl)
                                                        <img
                                                            src="{{ $logoPreviewUrl }}"
                                                            alt="Logo Preview"
                                                            id="logoPreviewImage_{{ $setting->key }}"
                                                            style="height: {{ $currentVal }}px; width: auto; object-fit: contain;"
                                                        >
                                                    @else
                                                        <div class="text-muted small" id="logoPreviewPlaceholder_{{ $setting->key }}">
                                                            <i class="bi bi-image me-1"></i>No logo uploaded.
                                                        </div>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    How the logo appears on {{ $deviceId }} devices. Range: 10px – {{ $maxVal }}px.
                                                </small>
                                            </div>
                                        </div>
                                    @else
                                        <input 
                                            type="number" 
                                            name="settings[{{ $setting->key }}]" 
                                            class="form-control" 
                                            value="{{ old("settings.{$setting->key}", $setting->value) }}"
                                            placeholder="Enter {{ strtolower($setting->label) }}"
                                        >
                                    @endif
                                    @break

                                @case('image')
                                    <div class="mb-2">
                                        <div id="setting-image-preview-{{ $setting->key }}" class="mb-2">
                                            @if($setting->value)
                                                @php
                                                    $settingImagePath = ltrim((string) $setting->value, '/');
                                                    $settingImageUrl = (str_starts_with($settingImagePath, 'http://') || str_starts_with($settingImagePath, 'https://'))
                                                        ? $setting->value
                                                        : ((str_starts_with($settingImagePath, 'media/') || str_starts_with($settingImagePath, 'storage/'))
                                                            ? asset($settingImagePath)
                                                            : Storage::disk('public')->url($settingImagePath));
                                                @endphp
                                                <div class="position-relative d-inline-block">
                                                    <img 
                                                        src="{{ $settingImageUrl }}" 
                                                        alt="{{ $setting->label }}" 
                                                        class="img-thumbnail"
                                                        style="max-height: 150px; max-width: 300px;"
                                                        id="setting-img-{{ $setting->key }}"
                                                    >
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                        onclick="deleteImage('{{ $group }}', '{{ $setting->key }}')"
                                                        title="Delete image"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                        <input type="hidden" name="settings[{{ $setting->key }}]" id="setting-input-{{ $setting->key }}" value="{{ $setting->value }}">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openSettingMediaPicker('{{ $setting->key }}')">
                                            <i class="bi bi-images me-1"></i> Select from Media Library
                                        </button>
                                        <small class="text-muted d-block mt-1">Upload images to Media Library first, then select here.</small>
                                    </div>
                                    @break

                                @case('json')
                                    @if($setting->key === 'header_menu')
                                        <div id="header-menu-builder">
                                            <textarea 
                                                name="settings[{{ $setting->key }}]" 
                                                id="header_menu_input"
                                                class="d-none"
                                            >{{ old("settings.{$setting->key}", $setting->value) }}</textarea>
                                            
                                            <div class="alert alert-info py-2 small mb-3">
                                                <i class="bi bi-info-circle me-1"></i> Drag and drop menu items to reorder. Add sub-items to create dropdowns.
                                            </div>

                                            <div id="menu-items-container" class="mb-4">
                                                <!-- JS will populate this -->
                                            </div>
                                            
                                            <div class="card bg-light border-dashed">
                                                <div class="card-body p-3">
                                                    <h6 class="card-title small fw-bold mb-3">Add New Menu Item</h6>
                                                    <div class="row g-2">
                                                        <div class="col-md-3">
                                                            <select id="new-menu-type" class="form-select form-select-sm" onchange="toggleNewMenuInputs()">
                                                                <option value="link">Custom Link</option>
                                                                <option value="category">Category</option>
                                                                <option value="page">CMS Page</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <input type="text" id="new-menu-label" class="form-control form-control-sm" placeholder="Label">
                                                        </div>
                                                        <div class="col-md-4">
                                                            {{-- Custom Link Input --}}
                                                            <input type="text" id="new-menu-url" class="form-control form-control-sm" placeholder="URL (e.g., /products)">
                                                            
                                                            {{-- Category Select (Hidden) --}}
                                                            <select id="new-menu-category" class="form-select form-select-sm d-none">
                                                                <option value="">Select Category</option>
                                                                @foreach($allCategories ?? [] as $cat)
                                                                    <option value="/categories/{{ $cat->slug }}">{{ $cat->name_with_indent }}</option>
                                                                @endforeach
                                                            </select>

                                                            {{-- Page Select (Hidden) --}}
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
                                    @else
                                        <textarea 
                                            name="settings[{{ $setting->key }}]" 
                                            class="form-control font-monospace" 
                                            rows="5"
                                            placeholder='{"key": "value"}'
                                        >{{ old("settings.{$setting->key}", $setting->value) }}</textarea>
                                        <small class="text-muted">Enter valid JSON format</small>
                                    @endif
                                    @break

                                @default
                                    @if($setting->key === 'order_number_generation_mode')
                                        <select name="settings[{{ $setting->key }}]" class="form-select">
                                            @php
                                                $selectedMode = (string) old("settings.{$setting->key}", $setting->value ?: 'timestamp_random');
                                            @endphp
                                            <option value="timestamp_random" {{ $selectedMode === 'timestamp_random' ? 'selected' : '' }}>
                                                Timestamp + Random (e.g., ORD-20260419123045-AB12)
                                            </option>
                                            <option value="date_sequence" {{ $selectedMode === 'date_sequence' ? 'selected' : '' }}>
                                                Date + Sequence (e.g., ORD-20260419-00001)
                                            </option>
                                            <option value="global_sequence" {{ $selectedMode === 'global_sequence' ? 'selected' : '' }}>
                                                Global Sequence (e.g., ORD-00000001)
                                            </option>
                                            <option value="custom_format" {{ $selectedMode === 'custom_format' ? 'selected' : '' }}>
                                                Custom Format (Use template)
                                            </option>
                                        </select>
                                    @elseif(str_contains($setting->key, 'color') || $setting->type === 'color')
                                        <div class="input-group advanced-color-picker">
                                            <div class="color-picker-init" data-target="setting_{{ $setting->key }}"></div>
                                            <input 
                                                type="text" 
                                                id="setting_{{ $setting->key }}"
                                                name="settings[{{ $setting->key }}]" 
                                                class="form-control hex-input" 
                                                value="{{ old("settings.{$setting->key}", $setting->value) ?: '#000000' }}"
                                                placeholder="#000000"
                                            >
                                        </div>
                                    @else
                                        <input 
                                            type="text" 
                                            name="settings[{{ $setting->key }}]" 
                                            class="form-control" 
                                            value="{{ old("settings.{$setting->key}", $setting->value) }}"
                                            placeholder="{{ in_array($setting->key, ['call_for_order_phone', 'whatsapp_order_phone']) ? '+8801XXXXXXXXX' : 'Enter ' . strtolower($setting->label) }}"
                                        >
                                    @endif
                            @endswitch

                            <small class="text-muted d-block mt-1">
                                Key: <code>{{ $setting->key }}</code>
                            </small>

                            @if(in_array($setting->key, ['call_for_order_phone', 'whatsapp_order_phone']))
                                <small class="text-muted d-block mt-1">
                                    Use international format (for example: +8801XXXXXXXXX).
                                </small>
                            @endif

                            @if($setting->key === 'whatsapp_order_message')
                                <small class="text-info d-block mt-1">
                                    Placeholders: <code>{product_name}</code>, <code>{product_url}</code>, <code>{quantity}</code>, <code>{price}</code>, <code>{sku}</code>
                                </small>
                            @endif

                            @if($setting->key === 'open_side_cart_on_add')
                                <small class="text-muted d-block mt-1">
                                    When enabled, the cart drawer will open automatically after adding a product.
                                </small>
                            @endif

                            @if($setting->key === 'order_number_prefix')
                                <small class="text-muted d-block mt-1">
                                    Allowed characters: letters, numbers, dash (-), underscore (_). Example: <code>ORD</code>.
                                </small>
                            @endif

                            @if($setting->key === 'order_number_generation_mode')
                                <small class="text-muted d-block mt-1">
                                    This controls how new order numbers are generated from checkout and admin recovery flows.
                                </small>
                            @endif

                            @if($setting->key === 'stock_enabled')
                                <small class="text-muted d-block mt-1">
                                    Enabled: simple products require base stock and variable products use variant stock for availability checks. Disabled: stock is optional and ignored globally.
                                </small>
                            @endif
                            @if($setting->key === 'order_number_custom_format')
                                <small class="text-muted d-block mt-1">
                                    Placeholders: <code>{PREFIX}</code>, <code>{YYYY}</code>, <code>{YY}</code>, <code>{MM}</code>, <code>{DD}</code>, <code>{SEQ:N}</code> (N=length), <code>{RAND:N}</code>. 
                                    Example: <code>{PREFIX}-{YYYY}{MM}{DD}-{SEQ:4}</code>
                                </small>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('admin.settings.site.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Settings Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>About This Section</h6>
            </div>
            <div class="card-body">
                @switch($group)
                    @case('hero')
                        <p class="text-muted small mb-0">
                            Configure your homepage hero section. This is the first thing visitors see when they land on your site.
                            Use compelling text and high-quality images to make a great first impression.
                        </p>
                        @break
                    @case('general')
                        <p class="text-muted small mb-0">
                            Basic site information including your brand name, logo, contact details, order hotline,
                            WhatsApp order template, side-cart behavior, and currency settings.
                            These settings appear throughout your site.
                        </p>
                        @break
                    @case('social')
                        <p class="text-muted small mb-0">
                            Add your social media profile URLs. These links will be displayed in the footer and can help 
                            customers connect with you on different platforms.
                        </p>
                        @break
                    @case('seo')
                        <p class="text-muted small mb-0">
                            Search engine optimization settings. These meta tags help search engines understand your site
                            and improve your visibility in search results.
                        </p>
                        @break
                    @case('footer')
                        <p class="text-muted small mb-0">
                            Customize your website footer including copyright text and newsletter subscription settings.
                        </p>
                        @break
                    @case('banner')
                        <p class="text-muted small mb-0">
                            Configure promotional banners that appear at the top of your site. Great for announcing sales,
                            free shipping thresholds, or special offers.
                        </p>
                        @break
                    @default
                        <p class="text-muted small mb-0">
                            Manage settings for the {{ $group }} section of your website.
                        </p>
                @endswitch
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.settings.site.create') }}?group={{ $group }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Add New Setting
                    </a>
                    <a href="{{ config('app.url') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-up-right me-1"></i> View Frontend
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Image Form (Hidden) -->
<form id="delete-image-form" method="POST" style="display: none;" data-no-admin-ajax="1">
    @csrf
    @method('DELETE')
</form>

<!-- Include Media Picker -->
@include('admin.media.picker')
@endsection

@push('scripts')
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });



    // Toggle label update for switches
    document.querySelectorAll('.form-check-input').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var label = this.nextElementSibling;
            label.textContent = this.checked ? 'Enabled' : 'Disabled';
        });
    });

    // Toggle custom format field visibility
    const modeSelect = document.querySelector('select[name="settings[order_number_generation_mode]"]');
    if (modeSelect) {
        function toggleCustomFormatField() {
            const wrapper = document.getElementById('custom_format_wrapper');
            if (wrapper) {
                wrapper.style.display = modeSelect.value === 'custom_format' ? 'block' : 'none';
            }
        }
        modeSelect.addEventListener('change', toggleCustomFormatField);
    }

    // Delete image function
    function deleteImage(group, key) {
        if (confirm('Are you sure you want to delete this image?')) {
            var form = document.getElementById('delete-image-form');
            form.action = '/admin/settings/site/' + group + '/' + key + '/delete-image';
            form.submit();
        }
    }    // Open media picker for settings image
    function openSettingMediaPicker(settingKey) {
        openMediaPicker('setting-input-' + settingKey, false, function(media) {
            // Update hidden input
            document.getElementById('setting-input-' + settingKey).value = media.path;
            
            // Update preview
            var previewContainer = document.getElementById('setting-image-preview-' + settingKey);
            if (previewContainer) {
                previewContainer.innerHTML = '<div class="position-relative d-inline-block">' +
                    '<img src="' + media.url + '" alt="Preview" class="img-thumbnail" style="max-height: 150px; max-width: 300px;">' +
                    '</div>';
            }

            // If the site_logo was updated, also update all logo height live previews
            if (settingKey === 'site_logo') {
                ['logo_height', 'logo_height_desktop', 'logo_height_mobile'].forEach(key => {
                    var logoPreviewImg = document.getElementById('logoPreviewImage_' + key) || document.getElementById('logoPreviewImage');
                    var logoPlaceholder = document.getElementById('logoPreviewPlaceholder_' + key) || document.getElementById('logoPreviewPlaceholder');
                    
                    if (logoPreviewImg) {
                        logoPreviewImg.src = media.url;
                    } else if (logoPlaceholder) {
                        var slider = document.getElementById('logoHeightSlider_' + key) || document.getElementById('logoHeightSlider');
                        var h = slider ? slider.value : 40;
                        logoPlaceholder.outerHTML = '<img src="' + media.url + '" alt="Logo Preview" id="logoPreviewImage_' + key + '" style="height: ' + h + 'px; width: auto; object-fit: contain;">';
                    }
                });
            }
        });
    }

    // Logo height slider + preview sync
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

    // Menu Builder Logic
    window.toggleNewMenuInputs = function() {
        const type = document.getElementById('new-menu-type').value;
        document.getElementById('new-menu-url').classList.toggle('d-none', type !== 'link');
        document.getElementById('new-menu-category').classList.toggle('d-none', type !== 'category');
        document.getElementById('new-menu-page').classList.toggle('d-none', type !== 'page');
        
        // Clear inputs when type changes
        if (type === 'category') document.getElementById('new-menu-category').selectedIndex = 0;
        if (type === 'page') document.getElementById('new-menu-page').selectedIndex = 0;
    };

    document.addEventListener('DOMContentLoaded', function() {
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
            
            content.innerHTML = `
                <div class="card-body p-2 d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2 flex-grow-1">
                        <i class="bi bi-grip-vertical text-muted"></i>
                        <input type="text" class="form-control form-control-sm" style="width: 150px" value="${item.label}" onchange="updateItem('${pathStr}', 'label', this.value)" placeholder="Label">
                        <input type="text" class="form-control form-control-sm flex-grow-1" value="${item.url}" onchange="updateItem('${pathStr}', 'url', this.value)" placeholder="URL">
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-xs btn-outline-info" onclick="indentItem('${pathStr}')" title="Indent (Make Sub-item)"><i class="bi bi-chevron-right"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-info" onclick="outdentItem('${pathStr}')" title="Outdent (Move to Main)"><i class="bi bi-chevron-left"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="moveItem('${pathStr}', -1)" title="Move Up"><i class="bi bi-chevron-up"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="moveItem('${pathStr}', 1)" title="Move Down"><i class="bi bi-chevron-down"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeItem('${pathStr}')" title="Remove"><i class="bi bi-trash"></i></button>
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
            if (index === 0) return; // Cannot indent the first item

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
            if (!path.includes('children')) return; // Already at top level

            const index = parseInt(path[path.length - 1]);
            
            // Find parent of the parent (to move item to)
            let grandparentArray = menuItems;
            let parentIndex = -1;
            
            // If path is [0, 'children', 1], grandparentArray is menuItems, parentIndex is 0
            // item is at menuItems[0].children[1]
            // we want to move it to menuItems[1]
            
            const parentPath = path.slice(0, -2); // Remove last 'children' and 'index'
            let parentArray = menuItems;
            for (let i = 0; i < parentPath.length - 1; i++) {
                parentArray = parentArray[parentPath[i]];
            }
            // Now parentArray is the array containing the parent item
            parentIndex = parseInt(parentPath[parentPath.length - 1]);
            
            // Get the item
            let currentArray = parentArray[parentIndex].children;
            const itemToMove = currentArray.splice(index, 1)[0];
            
            // Insert it after the parent
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
    });
</script>
@endpush

@extends('admin.layouts.app')

@section('title', $groupLabel . ' Settings')
@section('page-title', $groupLabel . ' Settings')

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
                                    @if($setting->key === 'product_grid_columns')
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
                                        <small class="text-muted d-block mt-1">Applies to homepage, product listing, category products, and related products grids.</small>
                                    @elseif($setting->key === 'logo_height')
                                        @php
                                            $currentLogoHeight = (int) old("settings.{$setting->key}", $setting->value ?: 40);
                                            $currentLogoHeight = max(20, min(120, $currentLogoHeight));
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
                                        @endphp
                                        <div class="logo-size-control">
                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                <input
                                                    type="range"
                                                    class="form-range flex-grow-1"
                                                    id="logoHeightSlider"
                                                    min="20"
                                                    max="120"
                                                    step="1"
                                                    value="{{ $currentLogoHeight }}"
                                                    oninput="updateLogoPreview(this.value)"
                                                >
                                                <div class="d-flex align-items-center gap-1">
                                                    <input
                                                        type="number"
                                                        class="form-control form-control-sm text-center"
                                                        style="width: 68px;"
                                                        id="logoHeightNumber"
                                                        name="settings[{{ $setting->key }}]"
                                                        value="{{ $currentLogoHeight }}"
                                                        min="20"
                                                        max="120"
                                                        oninput="updateLogoPreviewFromNumber(this.value)"
                                                    >
                                                    <span class="text-muted small">px</span>
                                                </div>
                                            </div>

                                            {{-- Live Preview --}}
                                            <div class="border rounded-3 p-3 bg-light">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="text-muted small fw-semibold"><i class="bi bi-eye me-1"></i>Live Preview</span>
                                                    <span class="badge bg-secondary" id="logoSizeLabel">{{ $currentLogoHeight }}px</span>
                                                </div>
                                                <div class="bg-white border rounded-2 p-3 d-flex align-items-center" style="min-height: 80px;">
                                                    @if($logoPreviewUrl)
                                                        <img
                                                            src="{{ $logoPreviewUrl }}"
                                                            alt="Logo Preview"
                                                            id="logoPreviewImage"
                                                            style="height: {{ $currentLogoHeight }}px; width: auto; object-fit: contain; transition: height 0.15s ease;"
                                                        >
                                                    @else
                                                        <div class="text-muted small" id="logoPreviewPlaceholder">
                                                            <i class="bi bi-image me-1"></i>No logo uploaded — upload a Site Logo above to see the preview.
                                                        </div>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    This is how your logo will appear in the frontend header. Height range: 20px – 120px.
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
                                            
                                            <div id="menu-items-container" class="list-group mb-3">
                                                <!-- JS will populate this -->
                                            </div>
                                            
                                            <div class="row g-2 align-items-center bg-light p-3 border rounded">
                                                <div class="col-md-5">
                                                    <input type="text" id="new-menu-label" class="form-control" placeholder="Menu Label (e.g., Home)">
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" id="new-menu-url" class="form-control" placeholder="URL (e.g., /products)">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-primary w-100" onclick="addMenuItem()">Add</button>
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
                                    @elseif(str_contains($setting->key, 'color'))
                                        <div class="input-group">
                                            <input 
                                                type="color" 
                                                class="form-control form-control-color" 
                                                name="settings[{{ $setting->key }}]" 
                                                value="{{ old("settings.{$setting->key}", $setting->value) ?: '#000000' }}"
                                            >
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                value="{{ old("settings.{$setting->key}", $setting->value) }}"
                                                placeholder="#000000"
                                                readonly
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

    // Sync color input with text display
    document.querySelectorAll('input[type="color"]').forEach(function(colorInput) {
        colorInput.addEventListener('input', function() {
            this.nextElementSibling.value = this.value;
        });
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
    }

    // Open media picker for settings image
    function openSettingMediaPicker(settingKey) {
        openMediaPicker('setting-input-' + settingKey, false, function(media) {
            // Update hidden input
            document.getElementById('setting-input-' + settingKey).value = media.path;
            
            // Update preview
            var previewContainer = document.getElementById('setting-image-preview-' + settingKey);
            previewContainer.innerHTML = '<div class="position-relative d-inline-block">' +
                '<img src="' + media.url + '" alt="Preview" class="img-thumbnail" style="max-height: 150px; max-width: 300px;">' +
                '</div>';

            // If the site_logo was updated, also update the logo height live preview
            if (settingKey === 'site_logo') {
                var logoPreviewImg = document.getElementById('logoPreviewImage');
                var logoPlaceholder = document.getElementById('logoPreviewPlaceholder');
                if (logoPreviewImg) {
                    logoPreviewImg.src = media.url;
                } else if (logoPlaceholder) {
                    var slider = document.getElementById('logoHeightSlider');
                    var h = slider ? slider.value : 40;
                    logoPlaceholder.outerHTML = '<img src="' + media.url + '" alt="Logo Preview" id="logoPreviewImage" style="height: ' + h + 'px; width: auto; object-fit: contain; transition: height 0.15s ease;">';
                }
            }
        });
    }

    // Logo height slider + preview sync
    function updateLogoPreview(val) {
        val = Math.max(20, Math.min(120, parseInt(val, 10) || 40));
        var numberInput = document.getElementById('logoHeightNumber');
        var previewImg = document.getElementById('logoPreviewImage');
        var sizeLabel = document.getElementById('logoSizeLabel');

        if (numberInput) numberInput.value = val;
        if (previewImg) previewImg.style.height = val + 'px';
        if (sizeLabel) sizeLabel.textContent = val + 'px';
    }

    function updateLogoPreviewFromNumber(val) {
        val = Math.max(20, Math.min(120, parseInt(val, 10) || 40));
        var slider = document.getElementById('logoHeightSlider');
        var previewImg = document.getElementById('logoPreviewImage');
        var sizeLabel = document.getElementById('logoSizeLabel');

        if (slider) slider.value = val;
        if (previewImg) previewImg.style.height = val + 'px';
        if (sizeLabel) sizeLabel.textContent = val + 'px';
    }

    // Menu Builder Logic
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
            
            menuItems.forEach((item, index) => {
                const row = document.createElement('div');
                row.className = 'list-group-item d-flex justify-content-between align-items-center';
                row.innerHTML = `
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <i class="bi bi-grip-vertical text-muted cursor-move"></i>
                        <div class="row g-2 flex-grow-1">
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm" value="${item.label}" onchange="updateMenuItem(${index}, 'label', this.value)">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm" value="${item.url}" onchange="updateMenuItem(${index}, 'url', this.value)">
                            </div>
                        </div>
                    </div>
                    <div class="ms-3 d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveMenuItem(${index}, -1)" ${index === 0 ? 'disabled' : ''}><i class="bi bi-arrow-up"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveMenuItem(${index}, 1)" ${index === menuItems.length - 1 ? 'disabled' : ''}><i class="bi bi-arrow-down"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeMenuItem(${index})"><i class="bi bi-trash"></i></button>
                    </div>
                `;
                container.appendChild(row);
            });
            menuInput.value = JSON.stringify(menuItems);
        }

        window.addMenuItem = function() {
            const labelInput = document.getElementById('new-menu-label');
            const urlInput = document.getElementById('new-menu-url');
            if (labelInput.value.trim() === '' || urlInput.value.trim() === '') return;

            menuItems.push({
                label: labelInput.value.trim(),
                url: urlInput.value.trim(),
                type: 'link'
            });

            labelInput.value = '';
            urlInput.value = '';
            renderMenu();
        };

        window.removeMenuItem = function(index) {
            menuItems.splice(index, 1);
            renderMenu();
        };

        window.updateMenuItem = function(index, field, value) {
            menuItems[index][field] = value;
            menuInput.value = JSON.stringify(menuItems);
        };

        window.moveMenuItem = function(index, direction) {
            if (index + direction < 0 || index + direction >= menuItems.length) return;
            const temp = menuItems[index];
            menuItems[index] = menuItems[index + direction];
            menuItems[index + direction] = temp;
            renderMenu();
        };

        renderMenu();
    });
</script>
@endpush

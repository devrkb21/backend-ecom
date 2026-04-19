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
                        <div class="mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
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
                                    <textarea 
                                        name="settings[{{ $setting->key }}]" 
                                        class="form-control font-monospace" 
                                        rows="5"
                                        placeholder='{"key": "value"}'
                                    >{{ old("settings.{$setting->key}", $setting->value) }}</textarea>
                                    <small class="text-muted">Enter valid JSON format</small>
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
<form id="delete-image-form" method="POST" style="display: none;">
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
        });
    }
</script>
@endpush

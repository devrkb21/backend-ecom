@switch($setting->type)
    @case('textarea')
        <textarea 
            name="settings[{{ $setting->key }}]" 
            class="form-control" 
            rows="3"
            @if($required) required @endif
            placeholder="{{ $setting->key === 'whatsapp_order_message' ? 'Example: Assalamu Alaikum, I want to order: {product_name}. Product URL: {product_url}. Quantity: {quantity}.' : 'Enter ' . strtolower($setting->label) }}"
        >{{ old("settings.{$setting->key}", $setting->value) }}</textarea>
        @if($required)
            <div class="invalid-feedback">This setting field is required.</div>
        @endif
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
                @if($required) required @endif
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
                @if($required) required @endif
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
                class="form-control form-control-sm" 
                @if($required) required @endif
                value="{{ old("settings.{$setting->key}", $setting->value) }}"
                placeholder="Enter {{ strtolower($setting->label) }}"
            >
            @if($required)
                <div class="invalid-feedback">Please enter a valid numeric value.</div>
            @endif
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
                            class="btn btn-danger btn-xs position-absolute top-0 end-0 m-1"
                            onclick="deleteImage('{{ $setting->group }}', '{{ $setting->key }}')"
                            title="Delete image"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                @endif
            </div>
            <input type="hidden" name="settings[{{ $setting->key }}]" id="setting-input-{{ $setting->key }}" value="{{ $setting->value }}" @if($required) required @endif>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openSettingMediaPicker('{{ $setting->key }}')">
                <i class="bi bi-images me-1"></i> Select from Media Library
            </button>
            <small class="text-muted d-block mt-1">Upload images to Media Library first, then select here.</small>
            @if($required)
                <div class="invalid-feedback">An image must be selected.</div>
            @endif
        </div>
        @break

    @case('json')
        <textarea 
            name="settings[{{ $setting->key }}]" 
            class="form-control font-monospace form-control-sm" 
            rows="5"
            @if($required) required @endif
            placeholder='{"key": "value"}'
        >{{ old("settings.{$setting->key}", $setting->value) }}</textarea>
        <small class="text-muted">Enter valid JSON format</small>
        @if($required)
            <div class="invalid-feedback">Please enter a valid JSON config.</div>
        @endif
        @break

    @default
        @if($setting->key === 'order_number_generation_mode')
            <select name="settings[{{ $setting->key }}]" class="form-select" @if($required) required @endif>
                @php
                    $selectedMode = (string) old("settings.{$setting->key}", $setting->value ?: 'timestamp_random');
                @endphp
                <option value="timestamp_random" {{ $selectedMode === 'timestamp_random' ? 'selected' : '' }}>
                    Timestamp + Random (e.g., ORD-20260419123045-AB12)
                </option>
                <option value="date_sequence" {{ $selectedMode === 'date_sequence' ? 'selected' : '' }}>
                    Date + Sequence (e.g., ORD-20260419-0001)
                </option>
                <option value="global_sequence" {{ $selectedMode === 'global_sequence' ? 'selected' : '' }}>
                    Global Sequence (e.g., ORD-0001)
                </option>
                <option value="custom_format" {{ $selectedMode === 'custom_format' ? 'selected' : '' }}>
                    Custom Format (Use template)
                </option>
            </select>
        @elseif($setting->key === 'invoice_footer_bg_color')
            @php
                $sitePrimaryColor = \App\Models\Setting::getValue('appearance', 'primary_color', '#db2777');
            @endphp
            <div class="input-group advanced-color-picker">
                <div class="color-picker-init" data-target="setting_{{ $setting->key }}"></div>
                <input 
                    type="text" 
                    id="setting_{{ $setting->key }}"
                    name="settings[{{ $setting->key }}]" 
                    class="form-control hex-input form-control-sm" 
                    @if($required) required @endif
                    value="{{ old("settings.{$setting->key}", $setting->value) }}"
                    placeholder="Auto ({{ $sitePrimaryColor }})"
                >
                <button 
                    type="button" 
                    class="btn btn-outline-secondary btn-sm px-3" 
                    onclick="document.getElementById('setting_{{ $setting->key }}').value = ''; if (typeof Pickr !== 'undefined' && window.pickrInstances && window.pickrInstances['setting_{{ $setting->key }}']) { window.pickrInstances['setting_{{ $setting->key }}'].setColor(null); }"
                    title="Reset color"
                >
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
                @if($required)
                    <div class="invalid-feedback">Please choose a color.</div>
                @endif
            </div>
        @elseif(str_contains($setting->key, 'color') || $setting->type === 'color')
            <div class="input-group advanced-color-picker">
                <div class="color-picker-init" data-target="setting_{{ $setting->key }}"></div>
                <input 
                    type="text" 
                    id="setting_{{ $setting->key }}"
                    name="settings[{{ $setting->key }}]" 
                    class="form-control hex-input form-control-sm" 
                    @if($required) required @endif
                    value="{{ old("settings.{$setting->key}", $setting->value) ?: '#000000' }}"
                    placeholder="#000000"
                >
                @if($required)
                    <div class="invalid-feedback">Please choose a valid color.</div>
                @endif
            </div>
        @else
            <input 
                type="text" 
                name="settings[{{ $setting->key }}]" 
                class="form-control form-control-sm" 
                @if($required) required @endif
                value="{{ old("settings.{$setting->key}", $setting->value) }}"
                placeholder="{{ in_array($setting->key, ['call_for_order_phone', 'whatsapp_order_phone']) ? '+8801XXXXXXXXX' : 'Enter ' . strtolower($setting->label) }}"
            >
            @if($required)
                <div class="invalid-feedback">This input is required.</div>
            @endif
        @endif
@endswitch

<small class="text-muted d-block mt-1" style="font-size: 0.65rem;">
    Key: <code>{{ $setting->key }}</code>
</small>

@if(in_array($setting->key, ['call_for_order_phone', 'whatsapp_order_phone']))
    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">
        Use international format (for example: +8801XXXXXXXXX).
    </small>
@endif

@if($setting->key === 'whatsapp_order_message')
    <small class="text-info d-block mt-1" style="font-size: 0.65rem;">
        Placeholders: <code>{product_name}</code>, <code>{product_url}</code>, <code>{quantity}</code>, <code>{price}</code>, <code>{sku}</code>
    </small>
@endif

@if($setting->key === 'open_side_cart_on_add')
    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">
        When enabled, the cart drawer will open automatically after adding a product.
    </small>
@endif

@if($setting->key === 'order_number_prefix')
    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">
        Allowed characters: letters, numbers, dash (-), underscore (_). Example: <code>ORD</code>.
    </small>
@endif

@if($setting->key === 'order_number_generation_mode')
    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">
        This controls how new order numbers are generated from checkout and admin recovery flows.
    </small>
@endif

@if($setting->key === 'stock_enabled')
    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">
        Enabled: simple products require base stock and variable products use variant stock.
    </small>
@endif
@if($setting->key === 'order_number_custom_format')
    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">
        Placeholders: <code>{PREFIX}</code>, <code>{YYYY}</code>, <code>{YY}</code>, <code>{MM}</code>, <code>{DD}</code>, <code>{SEQ:N}</code>, <code>{RAND:N}</code>.
    </small>
@endif

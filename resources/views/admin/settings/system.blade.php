@extends('admin.layouts.app')

@section('title', 'System Settings Dashboard')
@section('page-title', 'System Settings Dashboard')

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
    if (in_array($group, ['steadfast', 'pathao'])) {
        $group = 'couriers';
    }
    $validGroups = ['general', 'checkout', 'invoice', 'integrations', 'couriers', 'payment-gateways', 'shipping-methods', 'order-statuses', 'cancellation-reasons', 'sms-templates'];
    if (!in_array($group, $validGroups)) {
        $group = 'general';
    }

    $groupLabels = [
        'general' => 'General Settings',
        'checkout' => 'Checkout Settings',
        'invoice' => 'Invoice Settings',
        'integrations' => 'Integrations',
        'couriers' => 'Courier Settings',
        'payment-gateways' => 'Payment Gateways',
        'shipping-methods' => 'Shipping Methods',
        'order-statuses' => 'Order Statuses',
        'cancellation-reasons' => 'Cancellation Reasons',
        'sms-templates' => 'SMS Templates',
    ];

    $groupIcons = [
        'general' => 'bi-sliders',
        'checkout' => 'bi-cart-check',
        'invoice' => 'bi-receipt',
        'integrations' => 'bi-plug',
        'couriers' => 'bi-truck',
        'payment-gateways' => 'bi-credit-card',
        'shipping-methods' => 'bi-compass',
        'order-statuses' => 'bi-tags',
        'cancellation-reasons' => 'bi-x-octagon',
        'sms-templates' => 'bi-chat-dots',
    ];

    $groupDescriptions = [
        'general' => 'Branding title, logo heights, hotline and sidecart behavior',
        'checkout' => 'Tax percentage, guest checkout and custom fields manager',
        'invoice' => 'Invoice layout details, company signature and notes',
        'integrations' => 'Track scripts, Site verify, SMTP Mail, SMS, Live chat welcome widget',
        'couriers' => 'Configure Steadfast and Pathao Courier APIs',
        'payment-gateways' => 'bKash, Nagad, Rocket, SSLCommerz, COD active settings',
        'shipping-methods' => 'Inside/Outside Dhaka delivery cost rules',
        'order-statuses' => 'Custom order processing statuses and hex colors',
        'cancellation-reasons' => 'Options for order cancellation reason list dropdown',
        'sms-templates' => 'Dynamic notifications template content placeholders',
    ];

    $requiredKeys = [
        'general' => ['site_title'],
        'checkout' => ['tax_percentage'],
        'invoice' => ['invoice_prefix'],
    ];
@endphp

<div class="row g-4">
    <!-- Left Navigation Sidebar -->
    <div class="col-lg-4 col-xl-3">
        <div class="card settings-nav-card shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>System Settings</h6>
            </div>
            <div class="card-body p-2 pt-0">
                <div class="nav flex-column nav-pills" id="settings-tabs" role="tablist">
                    @foreach($validGroups as $g)
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
                                <div class="fw-semibold text-dark text-truncate small">{{ $groupLabels[$g] }}</div>
                                <div class="text-muted small text-truncate" style="font-size: 0.65rem;">
                                    {{ $groupDescriptions[$g] }}
                                </div>
                            </div>
                        </button>
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
            <!-- Dynamic Site Settings Panes (general, checkout, invoice) -->
            @foreach(['general', 'checkout', 'invoice'] as $g)
                <div class="tab-pane fade {{ $g === $group ? 'show active' : '' }}" id="pane-{{ $g }}" role="tabpanel">
                    
                    @if($g === 'checkout')
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-semibold text-dark">
                                    <i class="bi {{ $groupIcons[$g] }} me-2 text-primary"></i>{{ $groupLabels[$g] }}
                                </h6>
                            </div>
                            <form action="{{ route('admin.settings.site.update-group', 'checkout') }}" method="POST" id="checkoutSettingsForm" class="needs-validation" novalidate>
                                @csrf
                                @method('PUT')
                                
                                <div class="card-body">
                                    <!-- Checkout Access Section -->
                                    <div class="card border border-light-subtle shadow-none mb-4">
                                        <div class="card-header bg-light py-2">
                                            <span class="text-secondary fw-semibold small">Checkout Access Control</span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            id="setting_checkout_form_enabled"
                                                            name="settings[checkout_form_enabled]"
                                                            value="1"
                                                            @checked(($checkoutSettingValues['checkout_form_enabled'] ?? false) === true)
                                                        >
                                                        <label class="form-check-label fw-semibold text-dark small" for="setting_checkout_form_enabled">
                                                            Enable checkout form
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Disable this to block order placement from frontend checkout.</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            id="setting_enable_guest_checkout"
                                                            name="settings[enable_guest_checkout]"
                                                            value="1"
                                                            @checked(($checkoutSettingValues['enable_guest_checkout'] ?? false) === true)
                                                        >
                                                        <label class="form-check-label fw-semibold text-dark small" for="setting_enable_guest_checkout">
                                                            Enable guest checkout
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">If disabled, users must login before placing orders.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tax Settings Section (Required Inputs Isolated) -->
                                    <div class="card required-card border border-danger-subtle shadow-none mb-4">
                                        <div class="card-header bg-danger bg-opacity-10 py-2">
                                            <span class="text-danger fw-semibold small"><i class="bi bi-asterisk me-1"></i> Tax & Charge Settings</span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            id="setting_tax_enabled"
                                                            name="settings[tax_enabled]"
                                                            value="1"
                                                            @checked(($checkoutSettingValues['tax_enabled'] ?? false) === true)
                                                        >
                                                        <label class="form-check-label fw-semibold text-dark small" for="setting_tax_enabled">
                                                            Enable tax on checkout
                                                        </label>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">When enabled, tax percentage is calculated on subtotal.</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold text-dark mb-1" for="setting_tax_percentage">Tax Percentage <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <input
                                                            type="number"
                                                            class="form-control form-control-sm"
                                                            id="setting_tax_percentage"
                                                            name="settings[tax_percentage]"
                                                            value="{{ old('settings.tax_percentage', $checkoutSettingValues['tax_percentage'] ?? 0) }}"
                                                            step="0.01"
                                                            min="0"
                                                            max="100"
                                                            required
                                                        >
                                                        <span class="input-group-text">%</span>
                                                        <div class="invalid-feedback">Required. Must be between 0 and 100.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Checkout Field Manager Subsplit Section -->
                                    <div class="card border border-light-subtle shadow-none mb-3">
                                        <div class="card-header bg-light py-2">
                                            <span class="text-secondary fw-semibold small">Checkout Fields Configurator</span>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="mb-3">
                                                <ul class="nav nav-tabs border-bottom-0 bg-light p-1 rounded" id="checkoutFieldTabs">
                                                    <li class="nav-item">
                                                        <button class="nav-link active px-3 py-1.5 fw-semibold" type="button" data-section="billing">Billing Fields</button>
                                                    </li>
                                                    <li class="nav-item">
                                                        <button class="nav-link px-3 py-1.5 fw-semibold" type="button" data-section="shipping">Shipping Fields</button>
                                                    </li>
                                                    <li class="nav-item">
                                                        <button class="nav-link px-3 py-1.5 fw-semibold" type="button" data-section="additional">Additional Fields</button>
                                                    </li>
                                                </ul>
                                            </div>

                                            <div class="row g-3">
                                                <!-- Left Field List Column -->
                                                <div class="col-md-5">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-primary btn-xs" id="addFieldBtn">
                                                                <i class="bi bi-plus-lg me-1"></i> Add
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger btn-xs" id="removeSelectedBtn">Remove</button>
                                                            <button type="button" class="btn btn-outline-secondary btn-xs" id="enableSelectedBtn">Enable</button>
                                                            <button type="button" class="btn btn-outline-secondary btn-xs" id="disableSelectedBtn">Disable</button>
                                                        </div>
                                                        <button type="button" class="btn btn-outline-secondary btn-xs" id="resetDefaultsBtn">Reset</button>
                                                    </div>

                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                                        <input type="text" id="searchFieldInput" class="form-control border-start-0" placeholder="Search fields...">
                                                    </div>

                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="form-check me-2">
                                                            <input type="checkbox" id="selectAllVisible" class="form-check-input" />
                                                            <label class="form-check-label text-muted small fw-semibold" for="selectAllVisible">All</label>
                                                        </div>
                                                        <div class="text-muted small ms-auto" id="selectedCountText" style="font-size: 0.7rem;">0 fields selected</div>
                                                    </div>

                                                    <div id="checkoutFieldRows" class="checkout-fields-list overflow-auto pe-1" style="max-height: 480px; min-height: 200px;">
                                                        <!-- Populate via JS -->
                                                    </div>
                                                </div>

                                                <!-- Right Field Editor Form Column -->
                                                <div class="col-md-7">
                                                    <div class="card border border-light-subtle shadow-none h-100" id="liveFieldEditorCard" style="min-height: 380px;">
                                                        <div id="editorEmptyState" class="card-body d-flex flex-column align-items-center justify-content-center text-center py-5">
                                                            <div class="rounded-circle bg-light p-3 mb-2 text-muted">
                                                                <i class="bi bi-sliders2-vertical fs-3"></i>
                                                            </div>
                                                            <h6 class="fw-bold mb-1 text-dark">Field Properties</h6>
                                                            <p class="text-muted small px-3">Select a field from the list or click "Add" to configure details.</p>
                                                        </div>

                                                        <div id="editorActiveState" class="card-body p-3 d-none">
                                                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                                                <span class="fw-bold text-dark small" id="checkoutFieldModalTitle">Edit Field</span>
                                                                <span class="badge bg-primary" id="fieldSectionBadge">billing</span>
                                                            </div>
                                                            <div class="row g-2">
                                                                <input type="hidden" id="fieldEditorSection" value="billing">
                                                                
                                                                <div class="col-md-6">
                                                                    <label class="form-label small fw-semibold text-muted mb-1">Field Type</label>
                                                                    <select class="form-select form-select-sm" id="fieldEditorType">
                                                                        @foreach($fieldTypeOptions as $option)
                                                                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small fw-semibold text-muted mb-1">Field Name (Key) <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control form-control-sm font-monospace" id="fieldEditorKey" placeholder="shipping_city">
                                                                    <div class="invalid-feedback" id="fieldEditorKeyFeedback">Field name is required.</div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label small fw-semibold text-muted mb-1">Field Label <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control form-control-sm" id="fieldEditorLabel" placeholder="District / City">
                                                                    <div class="invalid-feedback" id="fieldEditorLabelFeedback">Field label is required.</div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small fw-semibold text-muted mb-1">Placeholder</label>
                                                                    <input type="text" class="form-control form-control-sm" id="fieldEditorPlaceholder" placeholder="Enter City">
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="form-label small fw-semibold text-muted mb-1">Validations</label>
                                                                    <input type="text" class="form-control form-control-sm" id="fieldEditorValidations" placeholder="required,phone">
                                                                </div>

                                                                <div class="col-12 d-none" id="fieldEditorOptionsWrapper">
                                                                    <label class="form-label small fw-semibold text-muted mb-1">Select Options (one per line: <code>value|label</code>) <span class="text-danger">*</span></label>
                                                                    <textarea class="form-control form-control-sm font-monospace" id="fieldEditorOptions" rows="3" placeholder="inside_dhaka|Inside Dhaka"></textarea>
                                                                    <div class="invalid-feedback" id="fieldEditorOptionsFeedback">Dropdown options are required.</div>
                                                                </div>

                                                                <div class="col-12 mt-2">
                                                                    <div class="form-check form-switch mb-1">
                                                                        <input type="checkbox" class="form-check-input" id="fieldEditorRequired">
                                                                        <label class="form-check-label small" for="fieldEditorRequired">Required on checkout page</label>
                                                                    </div>
                                                                    <div class="form-check form-switch">
                                                                        <input type="checkbox" class="form-check-input" id="fieldEditorEnabled" checked>
                                                                        <label class="form-check-label small" for="fieldEditorEnabled">Enabled</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-end gap-2 border-top pt-2 mt-3">
                                                                <button type="button" class="btn btn-outline-secondary btn-xs px-3" id="cancelFieldBtn">Discard</button>
                                                                <button type="button" class="btn btn-primary btn-xs px-3" id="saveFieldBtn">Apply</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="settings[checkout_fields_schema]" id="checkoutFieldSchemaInput" value="">
                                </div>
                                <div class="card-footer bg-white border-top d-flex justify-content-between py-3">
                                    <span class="text-muted small">Frontend checkout strictly follows this field schema. Drag rows to reorder.</span>
                                    <button type="submit" class="btn btn-primary btn-sm px-4">
                                        <i class="bi bi-check-lg me-1"></i> Save Checkout Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        <!-- General / Invoice Render Form -->
                        @php
                            $groupSettings = $settings->get($g, collect());
                            $requiredSettings = isset($requiredKeys[$g]) ? $groupSettings->whereIn('key', $requiredKeys[$g]) : collect();
                            $hasRequired = $requiredSettings->count() > 0;
                        @endphp

                        @if($hasRequired)
                            <!-- Required fields segment -->
                            <div class="card required-card border-danger-subtle shadow-sm mb-4">
                                <div class="card-header bg-danger bg-opacity-10 py-3">
                                    <h6 class="mb-0 fw-semibold text-danger">
                                        <i class="bi bi-exclamation-triangle me-2"></i>Required Brand Settings
                                    </h6>
                                    <p class="text-muted small mb-0 mt-1">These settings are required for the storefront to load correctly. Please make sure they are not left blank.</p>
                                </div>
                                <form action="{{ route('admin.settings.site.update-group', $g) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    @csrf
                                    @method('PUT')
                                    <div class="card-body">
                                        @foreach($requiredSettings as $setting)
                                            <div class="mb-4">
                                                @include('admin.settings.site.partials.input-field', ['setting' => $setting, 'required' => true])
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="card-footer bg-white border-top py-3 text-end">
                                        <button type="submit" class="btn btn-danger btn-sm px-4">
                                            <i class="bi bi-check-lg me-1"></i> Save Required Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        <!-- General fields segment -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="mb-0 fw-semibold text-dark">
                                    <i class="bi {{ $groupIcons[$g] ?? 'bi-gear' }} me-2 text-primary"></i>{{ $groupLabels[$g] }} Configuration
                                </h6>
                            </div>
                            <form action="{{ route('admin.settings.site.update-group', $g) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="card-body">
                                    @php
                                        $generalSettings = $hasRequired 
                                            ? $groupSettings->whereNotIn('key', $requiredKeys[$g])
                                            : $groupSettings;
                                    @endphp

                                    @forelse($generalSettings as $setting)
                                        <div class="mb-4">
                                            @include('admin.settings.site.partials.input-field', ['setting' => $setting, 'required' => false])
                                        </div>
                                    @empty
                                        <div class="text-center text-muted py-3">No additional parameters in this section.</div>
                                    @endforelse
                                </div>
                                @if($generalSettings->count() > 0)
                                    <div class="card-footer bg-white border-top py-3 text-end">
                                        <button type="submit" class="btn btn-primary btn-sm px-4">
                                            <i class="bi bi-check-lg me-1"></i> Save {{ $groupLabels[$g] }}
                                        </button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach

            <!-- Submenu Partial Panes (integrations, payment-gateways, shipping-methods, order-statuses, cancellation-reasons, sms-templates) -->
            @foreach(['integrations', 'couriers', 'payment-gateways', 'shipping-methods', 'order-statuses', 'cancellation-reasons', 'sms-templates'] as $g)
                <div class="tab-pane fade {{ $g === $group ? 'show active' : '' }}" id="pane-{{ $g }}" role="tabpanel">
                    @if($g === 'couriers')
                        @php
                            $subTab = request()->query('sub', 'steadfast');
                            if (!in_array($subTab, ['steadfast', 'pathao'])) {
                                $subTab = 'steadfast';
                            }
                        @endphp

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-semibold text-dark">
                                    <i class="bi bi-truck me-2 text-primary"></i>Courier Settings
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <ul class="nav nav-tabs border-bottom mb-4" id="couriersSubTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $subTab === 'steadfast' ? 'active' : '' }} fw-semibold" id="subtab-steadfast-btn" data-bs-toggle="tab" data-bs-target="#subtab-steadfast" type="button" role="tab" onclick="updateUrlSub('steadfast')">
                                            <i class="bi bi-truck me-2"></i>SteadFast Courier
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $subTab === 'pathao' ? 'active' : '' }} fw-semibold" id="subtab-pathao-btn" data-bs-toggle="tab" data-bs-target="#subtab-pathao" type="button" role="tab" onclick="updateUrlSub('pathao')">
                                            <i class="bi bi-send-dash me-2"></i>Pathao Courier
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="couriersSubTabsContent">
                                    <div class="tab-pane fade {{ $subTab === 'steadfast' ? 'show active' : '' }}" id="subtab-steadfast" role="tabpanel">
                                        @include('admin.settings.partials.steadfast')
                                    </div>
                                    <div class="tab-pane fade {{ $subTab === 'pathao' ? 'show active' : '' }}" id="subtab-pathao" role="tabpanel">
                                        @include('admin.settings.partials.pathao')
                                    </div>
                                </div>

                                <div class="alert alert-light border mt-3 mb-0 small">
                                    <i class="bi bi-signpost-split me-1"></i>
                                    Looking for the cross-courier fraud-history check? It's under
                                    <a href="{{ route('admin.orders.courier-checker') }}">Orders → Courier Checker</a>.
                                </div>
                            </div>
                        </div>
                    @else
                        @include('admin.settings.partials.' . $g)
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
<form id="delete-image-form" method="POST" style="display: none;" data-no-admin-ajax="1">
    @csrf
    @method('DELETE')
</form>
@include('admin.media.picker')
@endsection

@push('scripts')
<script>
    // 1. Navigation Panel active tab management via URL query parameter
    function updateUrlGroup(groupName) {
        const url = new URL(window.location);
        url.searchParams.set('group', groupName);
        if (groupName === 'couriers') {
            if (!url.searchParams.has('sub')) {
                url.searchParams.set('sub', 'steadfast');
            }
        } else {
            url.searchParams.delete('sub');
        }
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

    function updateUrlSub(subTab) {
        const url = new URL(window.location);
        url.searchParams.set('group', 'couriers');
        url.searchParams.set('sub', subTab);
        window.history.pushState({}, '', url);
    }

    // Restore query param active tab on page reload
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let group = urlParams.get('group');
        
        // Handle legacy group values: if group is steadfast or pathao, map to couriers + sub
        if (group === 'steadfast' || group === 'pathao') {
            const sub = group;
            group = 'couriers';
            
            const url = new URL(window.location);
            url.searchParams.set('group', 'couriers');
            url.searchParams.set('sub', sub);
            window.history.replaceState({}, '', url);
        }
        
        if (group) {
            const btn = document.getElementById('tab-btn-' + group);
            const pane = document.getElementById('pane-' + group);
            if (btn && pane) {
                // Remove active classes
                document.querySelectorAll('#settings-tabs .nav-link').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('#settings-panes > .tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                });
                // Add active classes
                btn.classList.add('active');
                pane.classList.add('show', 'active');
            }
        }

        // Handle sub tab activation
        const sub = urlParams.get('sub');
        if (sub && group === 'couriers') {
            const subBtn = document.getElementById('subtab-' + sub + '-btn');
            const subPane = document.getElementById('subtab-' + sub);
            if (subBtn && subPane) {
                // Deactivate default subtab
                document.querySelectorAll('#couriersSubTabs .nav-link').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('#couriersSubTabsContent > .tab-pane').forEach(p => p.classList.remove('show', 'active'));
                // Activate target subtab
                subBtn.classList.add('active');
                subPane.classList.add('show', 'active');
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
                        showAdminToast('Required fields are missing content. Please input values for all fields marked with * before saving.', 'warning');
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

    // 3. Image preview handling
    function previewImageInput(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var previewEl = document.getElementById(previewId);
                if (previewEl) {
                    previewEl.src = e.target.result;
                    previewEl.classList.remove('d-none');
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

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

    // 4. Checkout Field Manager JavaScript Logic
    const defaultSchema = @json($checkoutFieldSchema);
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
                normalized[section] = rows.map((row) => {
                    const id = String(row.id || makeFieldId());
                    const type = String(row.type || 'text');
                    const key = sanitizeFieldKey(row.key || '');
                    const label = String(row.label || '').trim();
                    const placeholder = String(row.placeholder || '').trim();
                    const required = filter_var_bool(row.required);
                    const enabled = filter_var_bool(row.enabled !== undefined ? row.enabled : true);
                    const validations = Array.isArray(row.validations) ? row.validations : [];
                    const options = type === 'select' ? normalizeOptions(row.options) : [];

                    return {
                        id,
                        type,
                        key,
                        label,
                        placeholder,
                        required,
                        enabled,
                        validations,
                        options,
                        sort_order: sortOrder++
                    };
                });
            });
            return normalized;
        };

        const filter_var_bool = (val) => {
            if (typeof val === 'boolean') return val;
            const s = String(val || '').trim().toLowerCase();
            return s === '1' || s === 'true' || s === 'on' || s === 'yes';
        };

        const sectionOrder = ['billing', 'shipping', 'additional'];
        const defaultNormalizedSchema = normalizeSchema(defaultSchema);
        let checkoutSchema = JSON.parse(JSON.stringify(defaultNormalizedSchema));

        const updateSchemaInput = () => {
            if (schemaInput) {
                schemaInput.value = JSON.stringify(checkoutSchema);
            }
        };

        const getActiveFields = () => {
            return checkoutSchema[activeSection] || [];
        };

        const renderRows = () => {
            const fields = getActiveFields();
            const searchQuery = String(searchFieldInput?.value || '').trim().toLowerCase();
            const filteredFields = fields.filter(f => 
                f.key.toLowerCase().includes(searchQuery) || 
                f.label.toLowerCase().includes(searchQuery) ||
                f.type.toLowerCase().includes(searchQuery)
            );

            checkoutFieldRows.innerHTML = '';
            
            if (filteredFields.length === 0) {
                checkoutFieldRows.innerHTML = `
                    <div class="text-center py-4 text-muted border border-dashed rounded bg-light">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        <span class="small">No fields match filters.</span>
                    </div>
                `;
                selectAll.checked = false;
                return;
            }

            let allSelected = filteredFields.length > 0;
            filteredFields.forEach((field) => {
                const isSelected = selectedIds.has(field.id);
                if (!isSelected) allSelected = false;

                const item = document.createElement('div');
                item.className = `field-item-card card shadow-none border p-2 d-flex flex-row align-items-center gap-2 ${isSelected ? 'active' : ''} ${!field.enabled ? 'table-active' : ''}`;
                item.setAttribute('draggable', 'true');
                item.dataset.id = field.id;

                item.innerHTML = `
                    <div class="drag-handle"><i class="bi bi-grip-vertical"></i></div>
                    <div class="form-check m-0">
                        <input type="checkbox" class="form-check-input row-select" data-id="${field.id}" ${isSelected ? 'checked' : ''} />
                    </div>
                    <div class="flex-grow-1 min-w-0" style="cursor: pointer;" onclick="document.dispatchEvent(new CustomEvent('edit-field', {detail: '${field.id}'}))">
                        <div class="d-flex align-items-center gap-1">
                            <span class="fw-semibold text-dark small text-truncate">${field.label}</span>
                            ${field.required ? '<span class="text-danger small">*</span>' : ''}
                        </div>
                        <div class="text-muted font-monospace" style="font-size: 0.65rem;">${field.key} (${field.type})</div>
                    </div>
                    <div class="d-flex align-items-center gap-1 ms-auto">
                        <span class="badge ${field.enabled ? 'bg-success' : 'bg-secondary'} px-1.5 py-0.5" style="font-size: 0.6rem;">
                            ${field.enabled ? 'Active' : 'Disabled'}
                        </span>
                    </div>
                `;

                // Drag and drop event listeners
                item.addEventListener('dragstart', handleDragStart);
                item.addEventListener('dragover', handleDragOver);
                item.addEventListener('drop', handleDrop);
                item.addEventListener('dragend', handleDragEnd);

                checkoutFieldRows.appendChild(item);
            });

            selectAll.checked = allSelected;
            document.getElementById('selectedCountText').textContent = `${selectedIds.size} field(s) selected`;
            updateSchemaInput();
        };

        // Edit event trigger
        document.addEventListener('edit-field', (e) => {
            openEditor(activeSection, e.detail);
        });

        // Tab Switching Setup
        const tabButtons = document.querySelectorAll('#checkoutFieldTabs button');
        const setActiveTab = (section) => {
            activeSection = section;
            tabButtons.forEach(btn => {
                const isActive = btn.dataset.section === section;
                btn.classList.toggle('active', isActive);
                btn.classList.toggle('bg-white', isActive);
                btn.classList.toggle('shadow-sm', isActive);
            });
            selectedIds.clear();
            closeEditor();
            renderRows();
        };

        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                setActiveTab(btn.dataset.section);
            });
        });

        // Editor Operations
        const openEditor = (section, fieldId) => {
            editingFieldId = fieldId;
            document.querySelectorAll('.field-item-card').forEach(c => {
                c.classList.toggle('active', c.dataset.id === fieldId);
            });

            document.getElementById('editorEmptyState').classList.add('d-none');
            const activeState = document.getElementById('editorActiveState');
            activeState.classList.remove('d-none');

            // Reset validations highlight
            editorKey.classList.remove('is-invalid');
            editorLabel.classList.remove('is-invalid');
            editorOptions.classList.remove('is-invalid');

            if (fieldId) {
                const field = (checkoutSchema[section] || []).find(f => f.id === fieldId);
                if (field) {
                    editorTitle.textContent = 'Edit Field Properties';
                    editorSection.value = section;
                    editorType.value = field.type;
                    editorKey.value = field.key;
                    editorKey.disabled = true; // Key should not be mutable
                    editorLabel.value = field.label;
                    editorPlaceholder.value = field.placeholder;
                    editorValidations.value = field.validations.join(',');
                    editorRequired.checked = field.required;
                    editorEnabled.checked = field.enabled;
                    
                    if (field.type === 'select') {
                        editorOptionsWrapper.classList.remove('d-none');
                        editorOptions.value = (field.options || []).map(o => `${o.value}|${o.label}`).join('\n');
                    } else {
                        editorOptionsWrapper.classList.add('d-none');
                        editorOptions.value = '';
                    }
                    document.getElementById('fieldSectionBadge').textContent = section;
                }
            } else {
                editorTitle.textContent = 'Add New Field';
                editorSection.value = section;
                editorType.value = 'text';
                editorKey.value = '';
                editorKey.disabled = false;
                editorLabel.value = '';
                editorPlaceholder.value = '';
                editorValidations.value = '';
                editorRequired.checked = false;
                editorEnabled.checked = true;
                editorOptionsWrapper.classList.add('d-none');
                editorOptions.value = '';
                document.getElementById('fieldSectionBadge').textContent = section;
            }
        };

        const closeEditor = () => {
            editingFieldId = null;
            document.getElementById('editorActiveState').classList.add('d-none');
            document.getElementById('editorEmptyState').classList.remove('d-none');
            document.querySelectorAll('.field-item-card').forEach(c => c.classList.remove('active'));
        };

        const validateFieldForm = () => {
            let isValid = true;

            const keyVal = editorKey.value.trim();
            if (!keyVal) {
                editorKey.classList.add('is-invalid');
                document.getElementById('fieldEditorKeyFeedback').textContent = 'Field name is required.';
                isValid = false;
            } else if (editingFieldId === null) {
                // Check uniqueness of key
                const sanitized = sanitizeFieldKey(keyVal);
                const allKeys = sectionOrder.flatMap(s => (checkoutSchema[s] || []).map(f => f.key));
                if (allKeys.includes(sanitized)) {
                    editorKey.classList.add('is-invalid');
                    document.getElementById('fieldEditorKeyFeedback').textContent = 'Field name already exists globally.';
                    isValid = false;
                } else {
                    editorKey.classList.remove('is-invalid');
                }
            } else {
                editorKey.classList.remove('is-invalid');
            }

            if (!editorLabel.value.trim()) {
                editorLabel.classList.add('is-invalid');
                isValid = false;
            } else {
                editorLabel.classList.remove('is-invalid');
            }

            if (editorType.value === 'select') {
                const optLines = editorOptions.value.trim();
                if (!optLines) {
                    editorOptions.classList.add('is-invalid');
                    isValid = false;
                } else {
                    editorOptions.classList.remove('is-invalid');
                }
            }

            return isValid;
        };

        const saveFieldFromEditor = () => {
            if (!validateFieldForm()) {
                return;
            }

            const section = editorSection.value;
            const type = editorType.value;
            const label = editorLabel.value.trim();
            const placeholder = editorPlaceholder.value.trim();
            const required = editorRequired.checked;
            const enabled = editorEnabled.checked;
            const validations = editorValidations.value.split(',')
                .map(v => v.trim())
                .filter(Boolean);

            let options = [];
            if (type === 'select') {
                options = editorOptions.value.split('\n')
                    .map(line => {
                        const parts = line.split('|');
                        const value = parts[0]?.trim() || '';
                        const label = parts[1]?.trim() || value;
                        return value ? { value, label } : null;
                    })
                    .filter(Boolean);
            }

            if (editingFieldId) {
                // Update
                const fields = checkoutSchema[section] || [];
                const idx = fields.findIndex(f => f.id === editingFieldId);
                if (idx !== -1) {
                    fields[idx] = {
                        ...fields[idx],
                        type,
                        label,
                        placeholder,
                        required,
                        enabled,
                        validations,
                        options
                    };
                }
            } else {
                // Create
                const key = sanitizeFieldKey(editorKey.value);
                const fields = checkoutSchema[section] || [];
                fields.push({
                    id: makeFieldId(),
                    type,
                    key,
                    label,
                    placeholder,
                    required,
                    enabled,
                    validations,
                    options,
                    sort_order: fields.length + 1
                });
            }

            closeEditor();
            renderRows();
        };

        // Drag and Drop implementation
        function handleDragStart(e) {
            dragStartIndex = getActiveFields().findIndex(f => f.id === this.dataset.id);
            this.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id);
        }

        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            return false;
        }

        function handleDrop(e) {
            e.stopPropagation();
            const id = e.dataTransfer.getData('text/plain');
            if (id !== this.dataset.id) {
                const fields = getActiveFields();
                const fromIdx = dragStartIndex;
                const toIdx = fields.findIndex(f => f.id === this.dataset.id);
                
                if (fromIdx !== -1 && toIdx !== -1) {
                    const row = fields.splice(fromIdx, 1)[0];
                    fields.splice(toIdx, 0, row);
                    fields.forEach((f, idx) => f.sort_order = idx + 1);
                    renderRows();
                }
            }
            return false;
        }

        function handleDragEnd() {
            this.style.opacity = '1';
            dragStartIndex = null;
        }

        // Selection Listener
        checkoutFieldRows.addEventListener('change', (e) => {
            const target = e.target;
            if (target && target.classList.contains('row-select')) {
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
        if (chkForm) {
            chkForm.addEventListener('submit', (e) => {
                const activeStateEl = document.getElementById('editorActiveState');
                const activeStateVisible = activeStateEl && !activeStateEl.classList.contains('d-none');
                if (activeStateVisible) {
                    if (validateFieldForm()) {
                        saveFieldFromEditor();
                    } else {
                        e.preventDefault();
                        showAdminToast('Please resolve validation errors in the field properties editor before saving settings.', 'warning');
                        const firstInvalid = document.querySelector('.is-invalid');
                        if (firstInvalid) firstInvalid.focus();
                        return;
                    }
                }
                updateSchemaInput();
            });
        }

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

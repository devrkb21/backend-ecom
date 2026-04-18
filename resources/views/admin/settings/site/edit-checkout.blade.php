@extends('admin.layouts.app')

@section('title', $groupLabel . ' Manager')
@section('page-title', $groupLabel . ' Manager')

@section('content')
@php
    $schemaValue = $checkoutFieldSchema ?? ['billing' => [], 'shipping' => [], 'additional' => []];
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-sliders me-2"></i>Checkout Field Manager
                </h6>
                <a href="{{ route('admin.settings.site.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <form action="{{ route('admin.settings.site.update-group', $group) }}" method="POST" id="checkoutSettingsForm">
                @csrf
                @method('PUT')

                <div class="card-body">
                    <div class="rounded border p-3 mb-4">
                        <h6 class="fw-semibold mb-3">Checkout Access</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="setting_checkout_form_enabled"
                                        name="settings[checkout_form_enabled]"
                                        value="1"
                                        @checked(($settingValues['checkout_form_enabled'] ?? false) === true)
                                    >
                                    <label class="form-check-label fw-semibold" for="setting_checkout_form_enabled">
                                        Enable checkout form
                                    </label>
                                </div>
                                <small class="text-muted">Disable this to block order placement from frontend checkout.</small>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="setting_enable_guest_checkout"
                                        name="settings[enable_guest_checkout]"
                                        value="1"
                                        @checked(($settingValues['enable_guest_checkout'] ?? false) === true)
                                    >
                                    <label class="form-check-label fw-semibold" for="setting_enable_guest_checkout">
                                        Enable guest checkout
                                    </label>
                                </div>
                                <small class="text-muted">If disabled, users must login before placing orders.</small>
                            </div>
                        </div>
                    </div>

                    <div class="rounded border p-3 mb-4">
                        <h6 class="fw-semibold mb-3">Tax Settings</h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="setting_tax_enabled"
                                        name="settings[tax_enabled]"
                                        value="1"
                                        @checked(($settingValues['tax_enabled'] ?? false) === true)
                                    >
                                    <label class="form-check-label fw-semibold" for="setting_tax_enabled">
                                        Enable tax on checkout
                                    </label>
                                </div>
                                <small class="text-muted">When enabled, tax is calculated on subtotal and reflected in checkout, payment, and order summaries.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="setting_tax_percentage">Tax Percentage</label>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="setting_tax_percentage"
                                        name="settings[tax_percentage]"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value="{{ old('settings.tax_percentage', $settingValues['tax_percentage'] ?? 0) }}"
                                    >
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Allowed range: 0 to 100.</small>
                            </div>
                        </div>
                    </div>

                    <div class="rounded border p-3">
                        <div class="mb-3">
                            <ul class="nav nav-tabs" id="checkoutFieldTabs">
                                <li class="nav-item">
                                    <button class="nav-link active" type="button" data-section="billing">Billing Fields</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" type="button" data-section="shipping">Shipping Fields</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" type="button" data-section="additional">Additional Fields</button>
                                </li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-primary btn-sm" id="addFieldBtn">
                                    <i class="bi bi-plus-lg me-1"></i> Add field
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="removeSelectedBtn">Remove</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="enableSelectedBtn">Enable</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="disableSelectedBtn">Disable</button>
                            </div>

                            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetDefaultsBtn">
                                Reset to default fields
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="checkoutFieldTable">
                                <thead>
                                    <tr>
                                        <th style="width: 36px;"></th>
                                        <th style="width: 44px;"><input type="checkbox" id="selectAllVisible" /></th>
                                        <th style="min-width: 180px;">Name</th>
                                        <th style="min-width: 120px;">Type</th>
                                        <th style="min-width: 180px;">Label</th>
                                        <th style="min-width: 220px;">Placeholder</th>
                                        <th style="min-width: 180px;">Validations</th>
                                        <th style="width: 90px;">Required</th>
                                        <th style="width: 90px;">Enabled</th>
                                        <th style="width: 90px;">Edit</th>
                                    </tr>
                                </thead>
                                <tbody id="checkoutFieldRows"></tbody>
                            </table>
                        </div>
                    </div>

                    <input type="hidden" name="settings[checkout_fields_schema]" id="checkoutFieldSchemaInput" value="">
                </div>

                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <small class="text-muted">Frontend checkout strictly follows this field schema. Drag rows to reorder.</small>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Checkout Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="checkoutFieldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkoutFieldModalTitle">Add Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Field Section</label>
                        <select class="form-select" id="fieldEditorSection">
                            <option value="billing">Billing Fields</option>
                            <option value="shipping">Shipping Fields</option>
                            <option value="additional">Additional Fields</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Field Type</label>
                        <select class="form-select" id="fieldEditorType">
                            @foreach($fieldTypeOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Field Name (Key)</label>
                        <input type="text" class="form-control" id="fieldEditorKey" placeholder="shipping_name">
                        <div class="form-text">Only lowercase letters, numbers and underscore are allowed.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Field Label</label>
                        <input type="text" class="form-control" id="fieldEditorLabel" placeholder="Full Name">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Placeholder</label>
                        <input type="text" class="form-control" id="fieldEditorPlaceholder" placeholder="Enter value">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Validations</label>
                        <input type="text" class="form-control" id="fieldEditorValidations" placeholder="email,phone">
                    </div>

                    <div class="col-12 d-none" id="fieldEditorOptionsWrapper">
                        <label class="form-label">Select Options (one per line: value|label)</label>
                        <textarea class="form-control" id="fieldEditorOptions" rows="4" placeholder="inside_dhaka|Inside Dhaka"></textarea>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mb-2">
                            <input type="checkbox" class="form-check-input" id="fieldEditorRequired">
                            <label class="form-check-label" for="fieldEditorRequired">Required</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="fieldEditorEnabled" checked>
                            <label class="form-check-label" for="fieldEditorEnabled">Enabled</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveFieldBtn">Save Field</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fieldTypeOptions = @json($fieldTypeOptions);
    const defaultSchema = @json($schemaValue);

    const sectionOrder = ['billing', 'shipping', 'additional'];
    const sectionLabels = {
        billing: 'Billing Fields',
        shipping: 'Shipping Fields',
        additional: 'Additional Fields',
    };

    const schemaInput = document.getElementById('checkoutFieldSchemaInput');
    const tableBody = document.getElementById('checkoutFieldRows');
    const selectAll = document.getElementById('selectAllVisible');
    const tabs = Array.from(document.querySelectorAll('#checkoutFieldTabs [data-section]'));
    const addFieldBtn = document.getElementById('addFieldBtn');
    const removeSelectedBtn = document.getElementById('removeSelectedBtn');
    const enableSelectedBtn = document.getElementById('enableSelectedBtn');
    const disableSelectedBtn = document.getElementById('disableSelectedBtn');
    const resetDefaultsBtn = document.getElementById('resetDefaultsBtn');
    const saveFieldBtn = document.getElementById('saveFieldBtn');

    const modalElement = document.getElementById('checkoutFieldModal');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;

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
        if (!Array.isArray(raw)) {
            return [];
        }

        return raw
            .filter((item) => item && typeof item === 'object')
            .map((item) => {
                const value = String(item.value || '').trim();
                const label = String(item.label || '').trim();
                if (!value || !label) {
                    return null;
                }

                return { value, label };
            })
            .filter(Boolean);
    };

    const normalizeSchema = (rawSchema) => {
        const normalized = {
            billing: [],
            shipping: [],
            additional: [],
        };

        sectionOrder.forEach((section) => {
            const rows = Array.isArray(rawSchema?.[section]) ? rawSchema[section] : [];
            let sortOrder = 1;

            rows.forEach((field) => {
                if (!field || typeof field !== 'object') {
                    return;
                }

                const key = sanitizeFieldKey(field.key || '');
                if (!key) {
                    return;
                }

                const type = String(field.type || 'text').trim();

                normalized[section].push({
                    id: String(field.id || makeFieldId()),
                    section,
                    key,
                    type,
                    label: String(field.label || key).trim(),
                    placeholder: String(field.placeholder || '').trim(),
                    validations: Array.isArray(field.validations)
                        ? field.validations.map((item) => String(item).trim()).filter(Boolean)
                        : [],
                    required: Boolean(field.required),
                    enabled: field.enabled === undefined ? true : Boolean(field.enabled),
                    options: normalizeOptions(field.options),
                    sort_order: sortOrder,
                });

                sortOrder += 1;
            });
        });

        return normalized;
    };

    const defaultNormalizedSchema = normalizeSchema(defaultSchema);
    let checkoutSchema = normalizeSchema(defaultSchema);

    const updateSchemaInput = () => {
        const payload = {
            billing: checkoutSchema.billing.map((field, index) => ({ ...field, sort_order: index + 1 })),
            shipping: checkoutSchema.shipping.map((field, index) => ({ ...field, sort_order: index + 1 })),
            additional: checkoutSchema.additional.map((field, index) => ({ ...field, sort_order: index + 1 })),
        };

        schemaInput.value = JSON.stringify(payload);
    };

    const renderRows = () => {
        const fields = checkoutSchema[activeSection] || [];
        tableBody.innerHTML = '';

        if (fields.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">No fields configured in ${sectionLabels[activeSection]}.</td>
                </tr>
            `;
            selectAll.checked = false;
            updateSchemaInput();
            return;
        }

        fields.forEach((field, index) => {
            const row = document.createElement('tr');
            row.dataset.fieldId = field.id;
            row.dataset.index = String(index);
            row.draggable = true;

            row.innerHTML = `
                <td class="text-muted" style="cursor: move;"><i class="bi bi-grip-vertical"></i></td>
                <td><input type="checkbox" class="form-check-input row-selector" data-id="${field.id}" ${selectedIds.has(field.id) ? 'checked' : ''}></td>
                <td><code>${field.key}</code></td>
                <td>${field.type}</td>
                <td>${field.label}</td>
                <td class="text-muted">${field.placeholder || '-'}</td>
                <td class="text-muted">${field.validations.length ? field.validations.join(', ') : '-'}</td>
                <td>${field.required ? '<i class="bi bi-check text-success"></i>' : '-'}</td>
                <td>${field.enabled ? '<i class="bi bi-check text-success"></i>' : '-'}</td>
                <td>
                    <button type="button" class="btn btn-outline-secondary btn-sm edit-row-btn" data-id="${field.id}">Edit</button>
                </td>
            `;

            row.addEventListener('dragstart', () => {
                dragStartIndex = index;
                row.classList.add('table-active');
            });

            row.addEventListener('dragend', () => {
                dragStartIndex = null;
                row.classList.remove('table-active');
            });

            row.addEventListener('dragover', (event) => {
                event.preventDefault();
            });

            row.addEventListener('drop', (event) => {
                event.preventDefault();

                const dropIndex = Number(row.dataset.index || -1);
                if (dragStartIndex === null || dropIndex < 0 || dragStartIndex === dropIndex) {
                    return;
                }

                const sectionFields = checkoutSchema[activeSection] || [];
                const moved = sectionFields.splice(dragStartIndex, 1)[0];
                sectionFields.splice(dropIndex, 0, moved);

                checkoutSchema[activeSection] = sectionFields.map((item, itemIndex) => ({
                    ...item,
                    sort_order: itemIndex + 1,
                }));

                renderRows();
            });

            tableBody.appendChild(row);
        });

        selectAll.checked = fields.length > 0 && fields.every((field) => selectedIds.has(field.id));
        updateSchemaInput();
    };

    const openEditor = (section, field = null) => {
        editingFieldId = field ? field.id : null;

        editorTitle.textContent = field ? 'Edit Field' : 'Add Field';
        editorSection.value = section;
        editorType.value = field?.type || 'text';
        editorKey.value = field?.key || '';
        editorLabel.value = field?.label || '';
        editorPlaceholder.value = field?.placeholder || '';
        editorValidations.value = Array.isArray(field?.validations) ? field.validations.join(',') : '';
        editorRequired.checked = Boolean(field?.required);
        editorEnabled.checked = field?.enabled === undefined ? true : Boolean(field?.enabled);
        editorOptions.value = Array.isArray(field?.options)
            ? field.options.map((item) => `${item.value}|${item.label}`).join('\n')
            : '';

        editorOptionsWrapper.classList.toggle('d-none', editorType.value !== 'select');

        modal?.show();
    };

    const parseOptionsTextarea = () => {
        const lines = String(editorOptions.value || '').split('\n');
        const options = [];

        lines.forEach((line) => {
            const raw = line.trim();
            if (!raw) {
                return;
            }

            const [value, label] = raw.split('|').map((part) => String(part || '').trim());
            if (!value || !label) {
                return;
            }

            options.push({ value, label });
        });

        return options;
    };

    const saveFieldFromEditor = () => {
        const section = editorSection.value;
        const key = sanitizeFieldKey(editorKey.value);
        const label = String(editorLabel.value || '').trim();

        if (!key) {
            alert('Field key is required.');
            return;
        }

        if (!label) {
            alert('Field label is required.');
            return;
        }

        const type = editorType.value;
        const validations = String(editorValidations.value || '')
            .split(',')
            .map((item) => item.trim().toLowerCase())
            .filter(Boolean);

        const options = type === 'select' ? parseOptionsTextarea() : [];

        if (type === 'select' && options.length === 0) {
            alert('Select field requires at least one option in value|label format.');
            return;
        }

        const targetSection = checkoutSchema[section] || [];

        if (editingFieldId) {
            const sourceSection = sectionOrder.find((candidate) =>
                (checkoutSchema[candidate] || []).some((field) => field.id === editingFieldId)
            );

            if (!sourceSection) {
                return;
            }

            const sourceRows = checkoutSchema[sourceSection] || [];
            const foundIndex = sourceRows.findIndex((field) => field.id === editingFieldId);
            if (foundIndex < 0) {
                return;
            }

            const current = sourceRows[foundIndex];
            const updated = {
                ...current,
                section,
                key,
                type,
                label,
                placeholder: String(editorPlaceholder.value || '').trim(),
                validations,
                required: editorRequired.checked,
                enabled: editorEnabled.checked,
                options,
            };

            sourceRows.splice(foundIndex, 1);
            checkoutSchema[sourceSection] = sourceRows.map((field, index) => ({ ...field, sort_order: index + 1 }));

            targetSection.push(updated);
            checkoutSchema[section] = targetSection.map((field, index) => ({ ...field, sort_order: index + 1 }));

            selectedIds.delete(editingFieldId);
        } else {
            targetSection.push({
                id: makeFieldId(),
                section,
                key,
                type,
                label,
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
        modal?.hide();
        renderRows();
    };

    const setActiveTab = (section) => {
        activeSection = section;
        tabs.forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.section === section);
        });
        renderRows();
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            setActiveTab(tab.dataset.section || 'billing');
        });
    });

    tableBody.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (!target.classList.contains('row-selector')) {
            return;
        }

        const id = target.dataset.id;
        if (!id) {
            return;
        }

        if (target.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }

        renderRows();
    });

    tableBody.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const editButton = target.closest('.edit-row-btn');
        if (!editButton) {
            return;
        }

        const id = editButton.getAttribute('data-id');
        if (!id) {
            return;
        }

        const allRows = (checkoutSchema[activeSection] || []);
        const field = allRows.find((item) => item.id === id);
        if (!field) {
            return;
        }

        openEditor(activeSection, field);
    });

    selectAll.addEventListener('change', () => {
        const fields = checkoutSchema[activeSection] || [];

        if (selectAll.checked) {
            fields.forEach((field) => selectedIds.add(field.id));
        } else {
            fields.forEach((field) => selectedIds.delete(field.id));
        }

        renderRows();
    });

    addFieldBtn.addEventListener('click', () => {
        openEditor(activeSection, null);
    });

    removeSelectedBtn.addEventListener('click', () => {
        if (selectedIds.size === 0) {
            return;
        }

        if (!confirm('Remove selected fields?')) {
            return;
        }

        sectionOrder.forEach((section) => {
            const filtered = (checkoutSchema[section] || [])
                .filter((field) => !selectedIds.has(field.id))
                .map((field, index) => ({ ...field, sort_order: index + 1 }));

            checkoutSchema[section] = filtered;
        });

        selectedIds = new Set();
        renderRows();
    });

    enableSelectedBtn.addEventListener('click', () => {
        if (selectedIds.size === 0) {
            return;
        }

        sectionOrder.forEach((section) => {
            checkoutSchema[section] = (checkoutSchema[section] || []).map((field) => {
                if (!selectedIds.has(field.id)) {
                    return field;
                }

                return { ...field, enabled: true };
            });
        });

        renderRows();
    });

    disableSelectedBtn.addEventListener('click', () => {
        if (selectedIds.size === 0) {
            return;
        }

        sectionOrder.forEach((section) => {
            checkoutSchema[section] = (checkoutSchema[section] || []).map((field) => {
                if (!selectedIds.has(field.id)) {
                    return field;
                }

                return { ...field, enabled: false, required: false };
            });
        });

        renderRows();
    });

    resetDefaultsBtn.addEventListener('click', () => {
        if (!confirm('Reset checkout fields to default schema?')) {
            return;
        }

        checkoutSchema = normalizeSchema(defaultNormalizedSchema);
        selectedIds = new Set();
        setActiveTab('billing');
    });

    saveFieldBtn.addEventListener('click', () => {
        saveFieldFromEditor();
    });

    editorType.addEventListener('change', () => {
        editorOptionsWrapper.classList.toggle('d-none', editorType.value !== 'select');
    });

    document.getElementById('checkoutSettingsForm').addEventListener('submit', () => {
        updateSchemaInput();
    });

    const taxEnabledToggle = document.getElementById('setting_tax_enabled');
    const taxPercentageInput = document.getElementById('setting_tax_percentage');

    const syncTaxInputState = () => {
        if (!taxEnabledToggle || !taxPercentageInput) {
            return;
        }

        taxPercentageInput.toggleAttribute('disabled', !taxEnabledToggle.checked);
    };

    if (taxEnabledToggle && taxPercentageInput) {
        taxEnabledToggle.addEventListener('change', syncTaxInputState);
        syncTaxInputState();
    }

    updateSchemaInput();
    setActiveTab('billing');
});
</script>
@endpush

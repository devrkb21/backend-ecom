@extends('admin.layouts.app')

@section('title', 'Add New Setting')
@section('page-title', 'Add New Setting')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-plus-circle me-2"></i>Add New Setting
                </h6>
                <a href="{{ route('admin.settings.site.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
            <form action="{{ route('admin.settings.site.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Group <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="group" class="form-select" id="group-select">
                                    <option value="">Select existing group...</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group }}" {{ request('group') == $group ? 'selected' : '' }}>
                                            {{ ucfirst($group) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleNewGroup()">
                                    <i class="bi bi-plus"></i> New
                                </button>
                            </div>
                            <input type="text" name="group" class="form-control mt-2 d-none" id="new-group-input" placeholder="Enter new group name">
                            @error('group')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Key <span class="text-danger">*</span></label>
                            <input type="text" name="key" class="form-control" value="{{ old('key') }}" 
                                   placeholder="e.g., hero_title" pattern="[a-z0-9_]+" required>
                            <small class="text-muted">Lowercase letters, numbers, and underscores only</small>
                            @error('key')
                                <span class="text-danger small d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Label <span class="text-danger">*</span></label>
                            <input type="text" name="label" class="form-control" value="{{ old('label') }}" 
                                   placeholder="e.g., Hero Title" required>
                            @error('label')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12" id="default-value-container">
                            <label class="form-label fw-semibold">Default Value</label>
                            <textarea name="value" id="default-value-textarea" class="form-control" rows="2" placeholder="Enter default value (optional)">{{ old('value') }}</textarea>
                            
                            <div id="color-picker-wrapper" class="d-none mt-2">
                                <div class="input-group advanced-color-picker">
                                    <div class="color-picker-init" data-target="default-value-textarea"></div>
                                    <div class="form-text ms-2 mt-2">Pick a default color or enter hex code above.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Enter description for admin reference (optional)">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_public" class="form-check-input" id="is_public" value="1" {{ old('is_public', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_public">
                                    Public (accessible via frontend API)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('admin.settings.site.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Create Setting
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-lightbulb me-2"></i>Tips</h6>
            </div>
            <div class="card-body">
                <ul class="small text-muted mb-0">
                    <li class="mb-2">
                        <strong>Group:</strong> Organizes settings (e.g., hero, general, footer)
                    </li>
                    <li class="mb-2">
                        <strong>Key:</strong> Unique identifier used in code. Use lowercase and underscores.
                    </li>
                    <li class="mb-2">
                        <strong>Type:</strong>
                        <ul class="mt-1">
                            <li><code>text</code> - Single line input</li>
                            <li><code>textarea</code> - Multi-line text</li>
                            <li><code>image</code> - File upload</li>
                            <li><code>boolean</code> - On/Off toggle</li>
                            <li><code>number</code> - Numeric value</li>
                            <li><code>json</code> - JSON data</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Public:</strong> If checked, setting is exposed via the frontend API.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let useNewGroup = false;
    
    function toggleNewGroup() {
        useNewGroup = !useNewGroup;
        const select = document.getElementById('group-select');
        const input = document.getElementById('new-group-input');
        
        if (useNewGroup) {
            select.classList.add('d-none');
            select.name = '';
            input.classList.remove('d-none');
            input.name = 'group';
            input.focus();
        } else {
            select.classList.remove('d-none');
            select.name = 'group';
            input.classList.add('d-none');
            input.name = '';
            input.value = '';
        }
    }

    // Handle type change to show/hide color picker
    document.querySelector('select[name="type"]').addEventListener('change', function() {
        const wrapper = document.getElementById('color-picker-wrapper');
        const textarea = document.getElementById('default-value-textarea');
        
        if (this.value === 'color') {
            wrapper.classList.remove('d-none');
            textarea.rows = 1;
            textarea.placeholder = '#000000';
            if (!textarea.value) textarea.value = '#000000';
            
            // Initialize if needed
            if (window.initGlobalColorPickers) {
                window.initGlobalColorPickers(wrapper);
            }
        } else {
            wrapper.classList.add('d-none');
            textarea.rows = 2;
            textarea.placeholder = 'Enter default value (optional)';
        }
    });

    // Initial check
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.querySelector('select[name="type"]');
        if (typeSelect && typeSelect.value === 'color') {
            typeSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush

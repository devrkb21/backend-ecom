@extends('admin.layouts.app')

@section('title', 'Edit Role')
@section('page-title', 'Edit Role')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-shield-lock me-2"></i>Edit Role: {{ $role->name }}</h6>
                    <small class="text-muted">Role key: {{ $role->key }}</small>
                </div>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Role Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="key">Role Key</label>
                            <input
                                type="text"
                                class="form-control @error('key') is-invalid @enderror"
                                id="key"
                                name="key"
                                value="{{ old('key', $role->key) }}"
                                {{ $role->is_system ? 'readonly' : '' }}
                            >
                            @if($role->is_system)
                                <div class="form-text">System role key cannot be changed.</div>
                            @endif
                            @error('key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @php
                        $canAccessAdminPanel = filter_var((string) old('can_access_admin_panel', $role->can_access_admin_panel ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
                    @endphp

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch border rounded p-2 ps-5">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="can_access_admin_panel"
                                    name="can_access_admin_panel"
                                    data-ui-toggle="can_access_admin_panel"
                                    value="1"
                                    {{ old('can_access_admin_panel', $role->can_access_admin_panel) ? 'checked' : '' }}
                                    {{ $role->key === 'admin' ? 'disabled' : '' }}
                                >
                                @if($role->key === 'admin')
                                    <input type="hidden" name="can_access_admin_panel" value="1">
                                @endif
                                <label class="form-check-label fw-semibold" for="can_access_admin_panel">Can Login Admin Panel</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch border rounded p-2 ps-5">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', $role->is_active) ? 'checked' : '' }}
                                    {{ $role->key === 'admin' ? 'disabled' : '' }}
                                >
                                @if($role->key === 'admin')
                                    <input type="hidden" name="is_active" value="1">
                                @endif
                                <label class="form-check-label fw-semibold" for="is_active">Role Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 {{ $role->key !== 'admin' && !$canAccessAdminPanel ? 'd-none' : '' }}" @if($role->key !== 'admin') data-ui-toggle-form="can_access_admin_panel" @endif>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Permissions</label>
                            @if($role->key !== 'admin')
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePermissionList(true)">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePermissionList(false)">Clear</button>
                                </div>
                            @endif
                        </div>

                        @if($role->key === 'admin')
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-shield-check me-1"></i>
                                Admin role always has full access.
                            </div>
                        @else
                            @php
                                $selectedPermissions = old('permissions', is_array($role->permissions) ? $role->permissions : []);
                            @endphp
                            <div class="border rounded p-3" style="max-height: 420px; overflow-y: auto;">
                                @foreach($permissionCatalog as $groupLabel => $permissions)
                                    <div class="mb-3">
                                        <h6 class="small text-uppercase fw-bold text-muted mb-2">{{ $groupLabel }}</h6>
                                        @foreach($permissions as $permissionKey => $permissionMeta)
                                            <div class="form-check mb-2">
                                                <input
                                                    class="form-check-input permission-edit-item"
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permissionKey }}"
                                                    id="perm_{{ $permissionKey }}"
                                                    {{ in_array($permissionKey, $selectedPermissions, true) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label" for="perm_{{ $permissionKey }}">
                                                    <span class="fw-semibold">{{ $permissionMeta['label'] }}</span>
                                                    <small class="text-muted d-block">{{ $permissionMeta['description'] }}</small>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @error('permissions')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @error('permissions.*')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($role->key !== 'admin')
                        <div class="small text-muted mb-3 {{ $canAccessAdminPanel ? 'd-none' : '' }}" data-ui-toggle-note="can_access_admin_panel">
                            Permissions form is hidden while admin panel access is disabled.
                        </div>
                    @endif

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePermissionList(checked) {
    document.querySelectorAll('.permission-edit-item').forEach(function (checkbox) {
        checkbox.checked = checked;
    });
}
</script>
@endpush

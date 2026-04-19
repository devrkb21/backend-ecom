@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')
@section('page-title', 'Roles & Permissions')

@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-circle me-2"></i>Create Role</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="name">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Warehouse Manager" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="key">Role Key (optional)</label>
                        <input type="text" class="form-control @error('key') is-invalid @enderror" id="key" name="key" value="{{ old('key') }}" placeholder="e.g. warehouse_manager">
                        <div class="form-text">Use lowercase letters, numbers, and underscore only.</div>
                        @error('key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="What this role is responsible for">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch border rounded p-2 ps-5">
                                <input class="form-check-input" type="checkbox" id="can_access_admin_panel" name="can_access_admin_panel" value="1" data-ui-toggle="can_access_admin_panel" {{ old('can_access_admin_panel') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="can_access_admin_panel">Can Login Admin</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch border rounded p-2 ps-5">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">Role Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 {{ old('can_access_admin_panel') ? '' : 'd-none' }}" data-ui-toggle-form="can_access_admin_panel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Permissions</label>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleAllPermissions(true)">Select All</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleAllPermissions(false)">Clear</button>
                            </div>
                        </div>

                        <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
                            @foreach($permissionCatalog as $groupLabel => $permissions)
                                <div class="mb-2">
                                    <div class="small fw-semibold text-uppercase text-muted mb-1">{{ $groupLabel }}</div>
                                    @foreach($permissions as $permissionKey => $permissionMeta)
                                        <div class="form-check mb-1">
                                            <input
                                                class="form-check-input permission-item"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permissionKey }}"
                                                id="perm_create_{{ $permissionKey }}"
                                                {{ in_array($permissionKey, old('permissions', []), true) ? 'checked' : '' }}
                                            >
                                            <label class="form-check-label" for="perm_create_{{ $permissionKey }}">
                                                <span class="fw-semibold">{{ $permissionMeta['label'] }}</span>
                                                <small class="text-muted d-block">{{ $permissionMeta['description'] }}</small>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        @error('permissions')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @error('permissions.*')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="small text-muted mb-3 {{ old('can_access_admin_panel') ? 'd-none' : '' }}" data-ui-toggle-note="can_access_admin_panel">
                        Permissions form is hidden while admin panel access is disabled.
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i> Create Role
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-person-gear me-2"></i>Role List</h6>
                <span class="badge bg-light text-dark">{{ $roles->count() }} roles</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Admin Login</th>
                                <th>Status</th>
                                <th>Users</th>
                                <th>Permissions</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                                @php
                                    $permissionCount = is_array($role->permissions) ? count($role->permissions) : 0;
                                    $fullAccess = in_array('*', (array) $role->permissions, true);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $role->name }}</div>
                                        <small class="text-muted">{{ $role->key }}</small>
                                        @if($role->is_system)
                                            <span class="badge bg-secondary ms-1">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($role->can_access_admin_panel)
                                            <span class="badge bg-success">Allowed</span>
                                        @else
                                            <span class="badge bg-danger">Blocked</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($role->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $role->users_count }}</td>
                                    <td>
                                        @if($fullAccess)
                                            <span class="badge bg-danger">Full Access</span>
                                        @else
                                            <span class="badge bg-info text-dark">{{ $permissionCount }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if(!$role->is_system)
                                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this role?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No roles found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleAllPermissions(checked) {
    document.querySelectorAll('.permission-item').forEach(function (checkbox) {
        checkbox.checked = checked;
    });
}
</script>
@endpush

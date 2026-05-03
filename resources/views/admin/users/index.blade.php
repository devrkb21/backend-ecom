@extends('admin.layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Total Users</small>
                <h4 class="mb-0">{{ $stats['total'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Active Users</small>
                <h4 class="mb-0 text-success">{{ $stats['active'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Inactive Users</small>
                <h4 class="mb-0 text-danger">{{ $stats['inactive'] }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2"></i>User List ({{ $users->total() }})</h6>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> Add User
        </a>
    </div>
    <div class="card-body border-bottom">
        <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 align-items-end" data-realtime-filter="1">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, email, phone" value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Role</label>
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    @foreach($roles as $roleKey => $roleLabel)
                        <option value="{{ $roleKey }}" {{ ($filters['role'] ?? '') === $roleKey ? 'selected' : '' }}>{{ $roleLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-center">Orders</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="{{ $user->trashed() ? 'table-light' : '' }}">
                            <td>{{ $user->id }}</td>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <form action="{{ route('admin.users.update-role', $user->id) }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="form-select form-select-sm" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                        @foreach($roles as $roleKey => $roleLabel)
                                            <option value="{{ $roleKey }}" {{ $user->role === $roleKey ? 'selected' : '' }}>{{ $roleLabel }}</option>
                                        @endforeach
                                    </select>
                                    @if(auth()->id() !== $user->id)
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                    @endif
                                </form>
                            </td>
                            <td>
                                @if($user->trashed())
                                    <span class="badge bg-danger">Inactive</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $user->orders_count ?? 0 }}</td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="d-flex gap-1">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" onsubmit="return confirm('{{ $user->trashed() ? 'Activate this user?' : 'Deactivate this user?' }}');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $user->trashed() ? 'btn-outline-success' : 'btn-outline-warning' }}" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                        <i class="bi {{ $user->trashed() ? 'bi-arrow-clockwise' : 'bi-slash-circle' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
            @include('admin.partials.pagination', ['paginator' => $users])
        </div>
</div>
@endsection

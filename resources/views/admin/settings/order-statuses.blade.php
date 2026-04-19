@extends('admin.layouts.app')

@section('title', 'Order Statuses')
@section('page-title', 'Order Statuses')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-circle me-2"></i>Add Order Status</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.order-statuses.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="status_key">Status Key <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('key') is-invalid @enderror"
                            id="status_key"
                            name="key"
                            value="{{ old('key') }}"
                            placeholder="e.g. ready_for_dispatch"
                            required
                        >
                        <small class="text-muted">Use lowercase letters, numbers, and underscore only.</small>
                        @error('key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="status_label">Status Label <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('label') is-invalid @enderror"
                            id="status_label"
                            name="label"
                            value="{{ old('label') }}"
                            placeholder="e.g. Ready for Dispatch"
                            required
                        >
                        @error('label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="status_color">Color</label>
                        <input
                            type="color"
                            class="form-control form-control-color @error('color') is-invalid @enderror"
                            id="status_color"
                            name="color"
                            value="{{ old('color', '#6C757D') }}"
                            title="Choose status color"
                        >
                        @error('color')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="status_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="status_active">Active</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i> Save Status
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-tags me-2"></i>Manage Statuses</h6>
                <span class="badge bg-light text-dark">{{ $statuses->count() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Key</th>
                                <th>Label</th>
                                <th>Color</th>
                                <th class="text-center">Orders</th>
                                <th style="width: 280px;">Edit</th>
                                <th class="text-end">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statuses as $status)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $status->key }}</div>
                                        @if($status->is_system)
                                            <small class="badge bg-secondary">System</small>
                                        @endif
                                    </td>
                                    <td>{{ $status->label }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $status->color }};">{{ $status->color }}</span>
                                    </td>
                                    <td class="text-center">{{ $status->orders_count }}</td>
                                    <td>
                                        <form action="{{ route('admin.settings.order-statuses.update', $status) }}" method="POST" class="row g-1">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-12">
                                                <input
                                                    type="text"
                                                    name="label"
                                                    class="form-control form-control-sm"
                                                    value="{{ $status->label }}"
                                                    required
                                                >
                                            </div>
                                            <div class="col-5">
                                                <input
                                                    type="text"
                                                    name="key"
                                                    class="form-control form-control-sm"
                                                    value="{{ $status->key }}"
                                                    {{ $status->is_system ? 'readonly' : '' }}
                                                    title="{{ $status->is_system ? 'System status key cannot be changed' : 'Status key' }}"
                                                >
                                            </div>
                                            <div class="col-3">
                                                <input
                                                    type="color"
                                                    name="color"
                                                    class="form-control form-control-color form-control-sm"
                                                    value="{{ $status->color }}"
                                                    title="Status color"
                                                >
                                            </div>
                                            <div class="col-2">
                                                <input
                                                    type="number"
                                                    name="sort_order"
                                                    class="form-control form-control-sm"
                                                    value="{{ $status->sort_order }}"
                                                    min="0"
                                                    max="65535"
                                                    title="Sort order"
                                                >
                                            </div>
                                            <div class="col-2 d-flex align-items-center">
                                                <div class="form-check form-switch m-0">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="is_active"
                                                        value="1"
                                                        {{ $status->is_active ? 'checked' : '' }}
                                                    >
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                                    <i class="bi bi-pencil-square me-1"></i> Update
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        @if($status->is_system || $status->orders_count > 0)
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="In use/system status cannot be deleted">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @else
                                            <form action="{{ route('admin.settings.order-statuses.destroy', $status) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this status?');">
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
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No order statuses found.
                                    </td>
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

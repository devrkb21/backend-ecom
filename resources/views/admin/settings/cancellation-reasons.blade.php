@extends('admin.layouts.app')

@section('title', 'Cancellation Reasons')
@section('page-title', 'Cancellation Reasons')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-circle me-2"></i>Add Cancellation Reason</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.cancellation-reasons.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="reason">Reason <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('reason') is-invalid @enderror"
                            id="reason"
                            name="reason"
                            value="{{ old('reason') }}"
                            placeholder="e.g. Changed my mind"
                            required
                        >
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i> Save Reason
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-x-octagon me-2"></i>Manage Reasons</h6>
                <span class="badge bg-light text-dark">{{ count($reasons) }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Reason</th>
                                <th style="width: 280px;">Edit</th>
                                <th class="text-end">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reasons as $index => $reason)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $reason }}</div>
                                        @if(strtolower($reason) === 'other')
                                            <small class="badge bg-secondary">System</small>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.settings.cancellation-reasons.update', $index) }}" method="POST" class="row g-1">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-8">
                                                <input
                                                    type="text"
                                                    name="reason"
                                                    class="form-control form-control-sm"
                                                    value="{{ $reason }}"
                                                    required
                                                    {{ strtolower($reason) === 'other' ? 'readonly' : '' }}
                                                >
                                            </div>
                                            <div class="col-4">
                                                <button type="submit" class="btn btn-sm btn-outline-primary w-100" {{ strtolower($reason) === 'other' ? 'disabled' : '' }}>
                                                    <i class="bi bi-pencil-square me-1"></i> Update
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        @if(strtolower($reason) === 'other')
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="System reason cannot be deleted">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @else
                                            <form action="{{ route('admin.settings.cancellation-reasons.destroy', $index) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this reason?');">
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
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        No cancellation reasons found.
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

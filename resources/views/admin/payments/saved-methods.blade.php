@extends('admin.layouts.app')

@section('title', 'Saved Payment methods')
@section('page-title', 'Saved Payment methods')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Saved Payment methods (Stripe)</span>
        <form method="GET" class="d-flex gap-2">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control form-control-sm"
                style="min-width: 240px;"
                placeholder="Search customer name/email"
            >
            <button class="btn btn-sm btn-outline-primary" type="submit">Search</button>
            @if($search !== '')
                <a href="{{ route('admin.payments.saved-methods') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Card</th>
                        <th>Expires</th>
                        <th>Default</th>
                        <th>Status</th>
                        <th>Last Used</th>
                        <th>Added</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($methods as $method)
                        <tr>
                            <td>#{{ $method->id }}</td>
                            <td>
                                @if($method->user)
                                    <div class="fw-semibold">
                                        <a href="{{ route('admin.users.show', $method->user_id) }}">{{ $method->user->name }}</a>
                                    </div>
                                    <div class="text-muted small">{{ $method->user->email }}</div>
                                @else
                                    <span class="text-muted">Deleted user</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-uppercase fw-semibold">{{ $method->card_brand ?: 'Card' }}</span>
                                <span class="text-muted">ending {{ $method->card_last_four ?: '----' }}</span>
                            </td>
                            <td>
                                @if($method->card_exp_month && $method->card_exp_year)
                                    {{ str_pad((string) $method->card_exp_month, 2, '0', STR_PAD_LEFT) }}/{{ $method->card_exp_year }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($method->is_default)
                                    <span class="badge bg-success">Default</span>
                                @else
                                    <span class="text-muted">No</span>
                                @endif
                            </td>
                            <td>
                                @if($method->is_active)
                                    <span class="badge bg-primary">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if($method->last_used_at)
                                    {{ $method->last_used_at->format('M d, Y H:i') }}
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>{{ $method->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No saved payment methods found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
            @include('admin.partials.pagination', ['paginator' => $methods])
        </div>
</div>
@endsection

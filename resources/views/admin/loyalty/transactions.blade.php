@extends('admin.layouts.app')

@section('title', 'Loyalty Transactions')
@section('page-title', 'Loyalty Transactions')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-funnel me-2"></i>Filters</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.loyalty.transactions') }}" method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search User</label>
                    <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Name, email...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Type</label>
                    <select class="form-select form-select-sm" name="type">
                        <option value="">All Types</option>
                        <option value="earned" {{ request('type') === 'earned' ? 'selected' : '' }}>Earned</option>
                        <option value="redeemed" {{ request('type') === 'redeemed' ? 'selected' : '' }}>Redeemed</option>
                        <option value="expired" {{ request('type') === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="adjusted" {{ request('type') === 'adjusted' ? 'selected' : '' }}>Adjusted</option>
                        <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>Bonus</option>
                        <option value="referral" {{ request('type') === 'referral' ? 'selected' : '' }}>Referral</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Date From</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Date To</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('admin.loyalty.transactions') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>All Transactions ({{ $transactions->total() }})</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Points</th>
                        <th>Balance After</th>
                        <th>Description</th>
                        <th>Order</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->id }}</td>
                            <td class="small">{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.loyalty.members.show', $transaction->user) }}">
                                    {{ $transaction->user->name }}
                                </a>
                                <div class="small text-muted">{{ $transaction->user->email }}</div>
                            </td>
                            <td>
                                @switch($transaction->type)
                                    @case('earned')
                                        <span class="badge bg-success">Earned</span>
                                        @break
                                    @case('redeemed')
                                        <span class="badge bg-warning text-dark">Redeemed</span>
                                        @break
                                    @case('expired')
                                        <span class="badge bg-secondary">Expired</span>
                                        @break
                                    @case('adjusted')
                                        <span class="badge bg-info">Adjusted</span>
                                        @break
                                    @case('bonus')
                                        <span class="badge bg-primary">Bonus</span>
                                        @break
                                    @case('referral')
                                        <span class="badge bg-purple text-white" style="background-color: #6f42c1;">Referral</span>
                                        @break
                                    @default
                                        <span class="badge bg-light text-dark">{{ $transaction->type }}</span>
                                @endswitch
                            </td>
                            <td class="{{ $transaction->points > 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                {{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points) }}
                            </td>
                            <td>{{ number_format($transaction->balance_after) }}</td>
                            <td class="small text-muted">{{ Str::limit($transaction->description, 40) }}</td>
                            <td>
                                @if($transaction->order_id)
                                    <a href="{{ route('admin.orders.show', $transaction->order_id) }}" class="btn btn-sm btn-outline-info">
                                        #{{ $transaction->order?->order_number ?? $transaction->order_id }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $transactions->links() }}
    </div>
</div>
@endsection

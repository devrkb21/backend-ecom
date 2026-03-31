@extends('admin.layouts.app')

@section('title', 'Loyalty Rewards')
@section('page-title', 'Loyalty Rewards')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-gift me-2"></i>All Rewards ({{ $rewards->total() }})</h6>
        <a href="{{ route('admin.loyalty.rewards.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus"></i> Add Reward
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Points Required</th>
                        <th>Value</th>
                        <th>Stock</th>
                        <th>Redeemed</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rewards as $reward)
                        <tr>
                            <td>{{ $reward->id }}</td>
                            <td>
                                <strong>{{ $reward->name }}</strong>
                                @if($reward->description)
                                    <div class="small text-muted">{{ Str::limit($reward->description, 50) }}</div>
                                @endif
                            </td>
                            <td>
                                @switch($reward->reward_type)
                                    @case('discount_percentage')
                                        <span class="badge bg-primary"><i class="bi bi-percent"></i> Discount %</span>
                                        @break
                                    @case('discount_fixed')
                                        <span class="badge bg-success"><i class="bi bi-cash"></i> Fixed Discount</span>
                                        @break
                                    @case('free_shipping')
                                        <span class="badge bg-info"><i class="bi bi-truck"></i> Free Shipping</span>
                                        @break
                                    @case('free_product')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-box"></i> Free Product</span>
                                        @break
                                    @case('coupon')
                                        <span class="badge bg-secondary"><i class="bi bi-ticket"></i> Coupon</span>
                                        @break
                                @endswitch
                            </td>
                            <td>
                                <span class="fw-semibold text-primary">{{ number_format($reward->points_required) }}</span> pts
                            </td>
                            <td>
                                @if($reward->reward_type === 'discount_percentage')
                                    {{ $reward->reward_value }}%
                                @elseif($reward->reward_type === 'discount_fixed')
                                    ৳{{ number_format($reward->reward_value, 2) }}
                                @elseif($reward->reward_type === 'free_shipping')
                                    -
                                @elseif($reward->reward_type === 'free_product' && $reward->product)
                                    {{ $reward->product->name }}
                                @else
                                    {{ $reward->reward_value }}
                                @endif
                            </td>
                            <td>
                                @if($reward->quantity_available)
                                    <span class="{{ $reward->quantity_remaining <= 0 ? 'text-danger' : '' }}">
                                        {{ $reward->quantity_remaining }} / {{ $reward->quantity_available }}
                                    </span>
                                @else
                                    <span class="text-muted">Unlimited</span>
                                @endif
                            </td>
                            <td>{{ number_format($reward->redeemed_count) }}</td>
                            <td>
                                @if($reward->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.loyalty.rewards.edit', $reward) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.loyalty.rewards.destroy', $reward) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this reward?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-gift fs-1 d-block mb-2"></i>
                                No rewards found. Create your first reward!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $rewards->links() }}
    </div>
</div>
@endsection

@extends('admin.layouts.app')

@section('title', 'Loyalty Tiers')
@section('page-title', 'Loyalty Tiers')

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2"></i>All Tiers</h6>
        <a href="{{ route('admin.loyalty.tiers.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus"></i> Add Tier
        </a>
    </div>
    <div class="card-body">
        <div class="row g-4">
            @forelse($tiers as $tier)
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-2" style="border-color: {{ $tier->slug === 'platinum' ? '#E5E4E2' : ($tier->slug === 'gold' ? '#FFD700' : ($tier->slug === 'silver' ? '#C0C0C0' : '#CD7F32')) }};">
                        <div class="card-header text-center py-3" style="background-color: {{ $tier->slug === 'platinum' ? '#E5E4E2' : ($tier->slug === 'gold' ? '#FFD700' : ($tier->slug === 'silver' ? '#C0C0C0' : '#CD7F32')) }};">
                            <h5 class="mb-0 text-dark">{{ $tier->name }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="fs-3 fw-bold text-primary">{{ number_format($tier->min_points) }}</div>
                                <small class="text-muted">Points Required</small>
                            </div>
                            
                            <ul class="list-unstyled small">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    {{ $tier->points_multiplier }}x Points Multiplier
                                </li>
                                @if($tier->birthday_bonus)
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        {{ number_format($tier->birthday_bonus) }} Birthday Bonus
                                    </li>
                                @endif
                                @if($tier->free_shipping)
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Free Shipping
                                    </li>
                                @endif
                                @if($tier->exclusive_discount)
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        {{ $tier->exclusive_discount }}% Member Discount
                                    </li>
                                @endif
                            </ul>
                            
                            <div class="small text-muted mb-3">
                                <strong>Members:</strong> {{ number_format($tier->members_count ?? 0) }}
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('admin.loyalty.tiers.edit', $tier) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                @if($tier->slug !== 'bronze')
                                    <form action="{{ route('admin.loyalty.tiers.destroy', $tier) }}" method="POST" onsubmit="return confirm('Delete this tier?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-4">
                    <i class="bi bi-trophy fs-1 d-block mb-2"></i>
                    No tiers found. Create your first tier!
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>How Tiers Work</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Points Progression</h6>
                <p class="text-muted small">
                    Customers automatically move to higher tiers based on their lifetime points.
                    The tier is determined by comparing the user's total earned points against each tier's minimum threshold.
                </p>
            </div>
            <div class="col-md-6">
                <h6>Benefits</h6>
                <ul class="text-muted small">
                    <li><strong>Points Multiplier:</strong> Higher tiers earn more points per purchase</li>
                    <li><strong>Birthday Bonus:</strong> Bonus points on user's birthday</li>
                    <li><strong>Free Shipping:</strong> Automatic free shipping on orders</li>
                    <li><strong>Exclusive Discount:</strong> Percentage off all orders</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

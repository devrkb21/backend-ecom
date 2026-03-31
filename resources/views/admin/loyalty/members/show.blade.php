@extends('admin.layouts.app')

@section('title', 'Member Details')
@section('page-title', 'Member: ' . $member->name)

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50 mb-1">Current Points</h6>
                <h3 class="mb-0">{{ number_format($member->loyalty_points) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50 mb-1">Lifetime Points</h6>
                <h3 class="mb-0">{{ number_format($member->lifetime_points) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        @php
            $tierColors = [
                'platinum' => '#E5E4E2',
                'gold' => '#FFD700',
                'silver' => '#C0C0C0',
                'bronze' => '#CD7F32',
            ];
            $tierColor = $tierColors[$member->loyalty_tier ?? 'bronze'] ?? '#CD7F32';
        @endphp
        <div class="card text-dark" style="background-color: {{ $tierColor }};">
            <div class="card-body text-center">
                <h6 class="mb-1 opacity-75">Current Tier</h6>
                <h3 class="mb-0 text-uppercase">{{ $member->loyalty_tier ?? 'Bronze' }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50 mb-1">Rewards Redeemed</h6>
                <h3 class="mb-0">{{ $member->loyaltyRedemptions->count() }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Points History</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Balance</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                <tr>
                                    <td class="small">{{ $transaction->created_at->format('M d, Y H:i') }}</td>
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
                                                <span class="badge bg-purple">Referral</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">{{ $transaction->type }}</span>
                                        @endswitch
                                    </td>
                                    <td class="{{ $transaction->points > 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                        {{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points) }}
                                    </td>
                                    <td>{{ number_format($transaction->balance_after) }}</td>
                                    <td class="small text-muted">{{ $transaction->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No transactions found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{ $transactions->links() }}
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-gift me-2"></i>Redemptions</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reward</th>
                                <th>Points Used</th>
                                <th>Coupon</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($member->loyaltyRedemptions as $redemption)
                                <tr>
                                    <td class="small">{{ $redemption->created_at->format('M d, Y') }}</td>
                                    <td>{{ $redemption->reward->name ?? 'Unknown' }}</td>
                                    <td class="text-danger">-{{ number_format($redemption->points_used) }}</td>
                                    <td>
                                        @if($redemption->coupon_code)
                                            <code>{{ $redemption->coupon_code }}</code>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @switch($redemption->status)
                                            @case('pending')
                                                <span class="badge bg-warning text-dark">Pending</span>
                                                @break
                                            @case('applied')
                                                <span class="badge bg-success">Applied</span>
                                                @break
                                            @case('expired')
                                                <span class="badge bg-secondary">Expired</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-danger">Cancelled</span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No redemptions found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2"></i>Member Information</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-2" style="width: 80px; height: 80px; font-size: 2rem;">
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                    <h5 class="mb-1">{{ $member->name }}</h5>
                    <p class="text-muted small mb-0">{{ $member->email }}</p>
                </div>
                
                <hr>
                
                <table class="table table-sm">
                    <tr>
                        <td class="text-muted">Phone</td>
                        <td class="text-end">{{ $member->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Member Since</td>
                        <td class="text-end">{{ $member->created_at->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Orders</td>
                        <td class="text-end">{{ $member->orders_count ?? $member->orders()->count() }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2"></i>Tier Progress</h6>
            </div>
            <div class="card-body">
                @php
                    $currentTier = \App\Models\LoyaltyTier::where('slug', $member->loyalty_tier ?? 'bronze')->first();
                    $nextTier = $currentTier ? \App\Models\LoyaltyTier::where('min_points', '>', $currentTier->min_points)
                        ->orderBy('min_points')->first() : null;
                @endphp
                
                @if($nextTier)
                    @php
                        $pointsNeeded = $nextTier->min_points - $member->lifetime_points;
                        $progress = (($member->lifetime_points - $currentTier->min_points) / ($nextTier->min_points - $currentTier->min_points)) * 100;
                    @endphp
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $currentTier->name }}</span>
                            <span>{{ $nextTier->name }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, max(0, $progress)) }}%;"></div>
                        </div>
                    </div>
                    <p class="text-muted small text-center mb-0">
                        <strong>{{ number_format(max(0, $pointsNeeded)) }}</strong> points to {{ $nextTier->name }}
                    </p>
                @else
                    <div class="text-center text-success">
                        <i class="bi bi-trophy-fill fs-1 mb-2 d-block"></i>
                        <p class="mb-0">Highest tier reached!</p>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2"></i>Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustPointsModal">
                        <i class="bi bi-plus-slash-minus me-2"></i>Adjust Points
                    </button>
                    <a href="{{ route('admin.users.show', $member) }}" class="btn btn-outline-info">
                        <i class="bi bi-person me-2"></i>View Full Profile
                    </a>
                    <a href="{{ route('admin.loyalty.members.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Members
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Points Modal -->
<div class="modal fade" id="adjustPointsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loyalty.members.adjust', $member) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Points: {{ $member->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        Current Points: <strong>{{ number_format($member->loyalty_points) }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="points" required>
                        <small class="text-muted">Use positive number to add, negative to deduct</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="2" required placeholder="Reason for adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Adjust Points</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

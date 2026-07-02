@extends('admin.layouts.app')

@section('title', 'Loyalty Program')
@section('page-title', 'Loyalty Program Dashboard')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Total Members</h6>
                        <h3 class="mb-0">{{ number_format($stats['total_members']) }}</h3>
                    </div>
                    <i class="bi bi-people fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Points Issued</h6>
                        <h3 class="mb-0">{{ number_format($stats['total_points_issued']) }}</h3>
                    </div>
                    <i class="bi bi-coin fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-dark mb-1">Points Redeemed</h6>
                        <h3 class="mb-0 text-dark">{{ number_format($stats['total_points_redeemed']) }}</h3>
                    </div>
                    <i class="bi bi-gift fs-1 text-dark opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Active Rewards</h6>
                        <h3 class="mb-0">{{ $stats['active_rewards'] }}</h3>
                    </div>
                    <i class="bi bi-stars fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2"></i>Points Activity (Last 30 Days)</h6>
            </div>
            <div class="card-body">
                <canvas id="pointsChart" height="100"></canvas>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Transactions</h6>
                <a href="{{ route('admin.loyalty.transactions') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.loyalty.members.show', $transaction->user) }}">
                                            {{ $transaction->user->name }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($transaction->points > 0)
                                            <span class="badge bg-success">{{ ucfirst($transaction->type) }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ ucfirst($transaction->type) }}</span>
                                        @endif
                                    </td>
                                    <td class="{{ $transaction->points > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points) }}
                                    </td>
                                    <td class="small text-muted">{{ Str::limit($transaction->description, 30) }}</td>
                                    <td class="small">{{ $transaction->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No transactions yet</td>
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
                <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2"></i>Members by Tier</h6>
            </div>
            <div class="card-body">
                @foreach($tierStats as $tier)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <span class="badge me-2" style="background-color: {{ $tier->tier === 'platinum' ? '#E5E4E2' : ($tier->tier === 'gold' ? '#FFD700' : ($tier->tier === 'silver' ? '#C0C0C0' : '#CD7F32')) }}; color: #333;">
                                {{ ucfirst($tier->tier ?? 'Bronze') }}
                            </span>
                        </div>
                        <span class="fw-semibold">{{ number_format($tier->count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-stars me-2"></i>Top Earners</h6>
            </div>
            <div class="card-body">
                @foreach($topEarners as $index => $user)
                    <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-3' : '' }}">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : ($index === 2 ? 'danger' : 'light text-dark')) }} me-2">
                                {{ $index + 1 }}
                            </span>
                            <a href="{{ route('admin.loyalty.members.show', $user) }}">{{ $user->name }}</a>
                        </div>
                        <span class="fw-semibold text-primary">{{ number_format($user->lifetime_points) }} pts</span>
                    </div>
                @endforeach
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.loyalty.rewards.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-gift me-2"></i>Manage Rewards
                    </a>
                    <a href="{{ route('admin.loyalty.tiers.index') }}" class="btn btn-outline-info">
                        <i class="bi bi-trophy me-2"></i>Manage Tiers
                    </a>
                    <a href="{{ route('admin.loyalty.members.index') }}" class="btn btn-outline-success">
                        <i class="bi bi-people me-2"></i>View Members
                    </a>
                    <a href="{{ route('admin.loyalty.redemptions') }}" class="btn btn-outline-warning">
                        <i class="bi bi-receipt me-2"></i>View Redemptions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function initLoyaltyCharts() {
    function renderCharts() {
        var canvas = document.getElementById('pointsChart');
        if (!canvas) return;

        // Destroy existing instance if any
        if (window.__loyaltyPointsChart) {
            window.__loyaltyPointsChart.destroy();
            window.__loyaltyPointsChart = null;
        }

        window.__loyaltyPointsChart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [
                    {
                        label: 'Points Earned',
                        data: {!! json_encode($chartData['earned']) !!},
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Points Redeemed',
                        data: {!! json_encode($chartData['redeemed']) !!},
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Defer to next animation frame to ensure canvas layout is fully resolved
    // (important for document.write()-based SPA navigation)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            requestAnimationFrame(renderCharts);
        });
    } else {
        requestAnimationFrame(renderCharts);
    }
})();
</script>
@endpush

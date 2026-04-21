@extends('admin.layouts.app')

@section('title', 'Sales Report')
@section('page-title', 'Sales Report')

@section('content')
<!-- Period Filter -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0 fw-semibold">Time Period:</label>
            </div>
            <div class="col-auto">
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 150px;">
                    <option value="7" {{ $period == '7' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30" {{ $period == '30' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90" {{ $period == '90' ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="365" {{ $period == '365' ? 'selected' : '' }}>Last Year</option>
                    <option value="all" {{ $period == 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.analytics.export', ['type' => 'sales', 'period' => $period]) }}" 
                   class="btn btn-sm btn-outline-success"
                   data-no-admin-ajax="1">
                    <i class="bi bi-download me-1"></i> Export CSV
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-currency-dollar text-success fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">৳{{ number_format($stats['total_revenue'], 2) }}</h3>
                        <span class="text-muted small">Total Revenue</span>
                        @if($stats['revenue_change'] != 0)
                            <br>
                            <small class="{{ $stats['revenue_change'] > 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bi bi-{{ $stats['revenue_change'] > 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                {{ abs(round($stats['revenue_change'], 1)) }}% vs previous
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-receipt text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ number_format($stats['completed_orders']) }}</h3>
                        <span class="text-muted small">Completed Orders</span>
                        @if($stats['orders_change'] != 0)
                            <br>
                            <small class="{{ $stats['orders_change'] > 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bi bi-{{ $stats['orders_change'] > 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                {{ abs(round($stats['orders_change'], 1)) }}% vs previous
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-graph-up text-info fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">৳{{ number_format($stats['average_order_value'], 2) }}</h3>
                        <span class="text-muted small">Avg Order Value</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-x-circle text-danger fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ number_format($stats['cancelled_orders']) }}</h3>
                        <span class="text-muted small">Cancelled Orders</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-graph-up me-2"></i>Revenue Overview</h6>
    </div>
    <div class="card-body">
        <canvas id="revenueChart" height="80"></canvas>
    </div>
</div>

<!-- Orders Chart -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-bar-chart me-2"></i>Orders Overview</h6>
    </div>
    <div class="card-body">
        <canvas id="ordersChart" height="80"></canvas>
    </div>
</div>

<!-- Payment Status -->
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Orders by Status</h6>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-5">
                <canvas id="statusChart" height="220"></canvas>
            </div>
            <div class="col-md-7">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th class="text-center">Orders</th>
                                <th class="text-end">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                                @if(isset($paymentStats[$status]))
                                    <tr>
                                        <td>
                                            <span class="badge badge-status-{{ $status }}">{{ ucfirst($status) }}</span>
                                        </td>
                                        <td class="text-center">{{ number_format($paymentStats[$status]->count) }}</td>
                                        <td class="text-end fw-semibold">৳{{ number_format($paymentStats[$status]->total, 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const salesData = @json($salesData);
    
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: salesData.map(d => d.label),
            datasets: [{
                label: 'Revenue ($)',
                data: salesData.map(d => d.revenue),
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: function(value) {
                            return '৳' + value.toLocaleString();
                        }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // Orders Chart
    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: salesData.map(d => d.label),
            datasets: [{
                label: 'Orders',
                data: salesData.map(d => d.orders),
                backgroundColor: 'rgba(13, 110, 253, 0.8)',
                hoverBackgroundColor: '#0d6efd',
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { stepSize: 1 }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // Status Chart
    const statusData = @json($paymentStats);
    const statusColors = {
        'pending': '#ffc107',
        'processing': '#17a2b8',
        'shipped': '#6f42c1',
        'delivered': '#198754',
        'cancelled': '#dc3545'
    };

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
            datasets: [{
                data: Object.values(statusData).map(s => s.count),
                backgroundColor: Object.keys(statusData).map(s => statusColors[s] || '#6c757d'),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 15 }
                }
            }
        }
    });
</script>
@endpush

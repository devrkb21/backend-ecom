@extends('admin.layouts.app')

@section('title', 'Orders Report')
@section('page-title', 'Orders Report')

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
                <a href="{{ route('admin.analytics.export', ['type' => 'orders', 'period' => $period]) }}" 
                   class="btn btn-sm btn-outline-success">
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
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-receipt text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ number_format($stats['total_orders']) }}</h3>
                        <span class="text-muted small">Total Orders</span>
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
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ $stats['fulfillment_rate'] }}%</h3>
                        <span class="text-muted small">Fulfillment Rate</span>
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
                            <i class="bi bi-clock-history text-info fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ $stats['avg_processing_hours'] }}h</h3>
                        <span class="text-muted small">Avg Processing</span>
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
                        <h3 class="mb-0">{{ number_format($stats['cancelled']) }}</h3>
                        <span class="text-muted small">Cancelled</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <!-- Orders Chart -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-graph-up me-2"></i>Orders Over Time</h6>
            </div>
            <div class="card-body">
                <canvas id="ordersChart" height="80"></canvas>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Orders</h6>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentOrders->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No orders in this period.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td>{{ $order->user->name ?? 'Guest' }}</td>
                                        <td>
                                            <span class="badge badge-status-{{ $order->status }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">৳{{ number_format($order->total, 2) }}</td>
                                        <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Order Status Sidebar -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Orders by Status</h6>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="220"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>Status Breakdown</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item p-0">
                        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="d-flex justify-content-between align-items-center py-3 px-3 text-decoration-none text-body">
                            <span><span class="badge badge-status-pending me-2">•</span> Pending</span>
                            <strong>{{ $stats['pending'] }}</strong>
                        </a>
                    </li>
                    <li class="list-group-item p-0">
                        <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="d-flex justify-content-between align-items-center py-3 px-3 text-decoration-none text-body">
                            <span><span class="badge badge-status-processing me-2">•</span> Processing</span>
                            <strong>{{ $stats['processing'] }}</strong>
                        </a>
                    </li>
                    <li class="list-group-item p-0">
                        <a href="{{ route('admin.orders.index', ['status' => 'shipped']) }}" class="d-flex justify-content-between align-items-center py-3 px-3 text-decoration-none text-body">
                            <span><span class="badge badge-status-shipped me-2">•</span> Shipped</span>
                            <strong>{{ $stats['shipped'] }}</strong>
                        </a>
                    </li>
                    <li class="list-group-item p-0">
                        <a href="{{ route('admin.orders.index', ['status' => 'delivered']) }}" class="d-flex justify-content-between align-items-center py-3 px-3 text-decoration-none text-body">
                            <span><span class="badge badge-status-delivered me-2">•</span> Delivered</span>
                            <strong>{{ $stats['delivered'] }}</strong>
                        </a>
                    </li>
                    <li class="list-group-item p-0">
                        <a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}" class="d-flex justify-content-between align-items-center py-3 px-3 text-decoration-none text-body">
                            <span><span class="badge badge-status-cancelled me-2">•</span> Cancelled</span>
                            <strong>{{ $stats['cancelled'] }}</strong>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ordersData = @json($ordersData);
    const ordersByStatus = @json($ordersByStatus);

    // Orders Over Time Chart
    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: ordersData.map(d => d.label),
            datasets: [
                {
                    label: 'Delivered',
                    data: ordersData.map(d => d.delivered),
                    backgroundColor: '#198754',
                    stack: 'Stack 0',
                    borderRadius: 4,
                    borderSkipped: false
                },
                {
                    label: 'Cancelled',
                    data: ordersData.map(d => d.cancelled),
                    backgroundColor: '#dc3545',
                    stack: 'Stack 0',
                    borderRadius: 4,
                    borderSkipped: false
                },
                {
                    label: 'Other',
                    data: ordersData.map(d => d.total - d.delivered - d.cancelled),
                    backgroundColor: '#6c757d',
                    stack: 'Stack 0',
                    borderRadius: 4,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            },
            scales: {
                x: { 
                    stacked: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y: { 
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Status Doughnut Chart
    const statusColors = {
        'pending': '#ffc107',
        'processing': '#0dcaf0',
        'shipped': '#6f42c1',
        'delivered': '#198754',
        'cancelled': '#dc3545'
    };

    const statusLabels = Object.keys(ordersByStatus);
    const statusValues = Object.values(ordersByStatus);

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
            datasets: [{
                data: statusValues,
                backgroundColor: statusLabels.map(s => statusColors[s] || '#6c757d'),
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
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            }
        }
    });
</script>
@endpush

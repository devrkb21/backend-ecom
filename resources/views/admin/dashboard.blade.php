@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<!-- Daily Overview -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-calendar-day me-2"></i>Daily Overview</h5>
    
    <form action="{{ route('admin.dashboard') }}" method="GET" class="d-flex align-items-center gap-2" id="filterForm">
        <select name="range" class="form-select form-select-sm w-auto" id="rangeSelect" onchange="handleRangeChange(this)">
            <option value="today" {{ $range == 'today' ? 'selected' : '' }}>Today</option>
            <option value="yesterday" {{ $range == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
            <option value="this_week" {{ $range == 'this_week' ? 'selected' : '' }}>This Week</option>
            <option value="this_month" {{ $range == 'this_month' ? 'selected' : '' }}>This Month</option>
            <option value="last_month" {{ $range == 'last_month' ? 'selected' : '' }}>Last Month</option>
            <option value="this_year" {{ $range == 'this_year' ? 'selected' : '' }}>This Year</option>
            <option value="last_year" {{ $range == 'last_year' ? 'selected' : '' }}>Last Year</option>
            <option value="custom" {{ $range == 'custom' ? 'selected' : '' }}>Custom Date</option>
        </select>
        
        <div id="customDateFields" style="{{ $range == 'custom' ? '' : 'display:none;' }}" class="d-flex align-items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="form-control form-control-sm w-auto">
            <span class="text-muted small">to</span>
            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="form-control form-control-sm w-auto">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    {{-- Today Sale (filtered by date range) --}}
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.bi.sales-reports') }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-primary border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-currency-dollar text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">৳{{ number_format($overviewStats['total_sale'], 2) }}</h4>
                            <span class="text-muted small">
                                Sale ({{ ucfirst(str_replace('_', ' ', $range)) }})
                                <span class="badge bg-primary bg-opacity-10 text-primary ms-1">{{ number_format($overviewStats['orders']) }} Order</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Courier Shipped (filtered) --}}
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.orders.index', ['status' => 'shipped']) }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-info border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-truck text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">৳{{ number_format($courierStats['shipped_total'], 2) }}</h4>
                            <span class="text-muted small">
                                Courier Shipped
                                <span class="badge bg-info bg-opacity-10 text-info ms-1">{{ number_format($courierStats['shipped_count']) }} Parcel</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Courier Delivered (filtered) --}}
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.orders.index', ['status' => 'delivered']) }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-success border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-check-circle text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">৳{{ number_format($courierStats['delivered_total'], 2) }}</h4>
                            <span class="text-muted small">
                                Courier Delivered
                                <span class="badge bg-success bg-opacity-10 text-success ms-1">{{ number_format($courierStats['delivered_count']) }} Parcel</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Courier Cancelled (filtered) --}}
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-danger border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-x-circle text-danger fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">৳{{ number_format($courierStats['cancelled_total'], 2) }}</h4>
                            <span class="text-muted small">
                                Courier Cancelled
                                <span class="badge bg-danger bg-opacity-10 text-danger ms-1">{{ number_format($courierStats['cancelled_count']) }} Parcel</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Abandoned Cart Analytics -->
<h5 class="mb-3 fw-bold text-dark"><i class="bi bi-cart-x me-2"></i>Abandoned Cart Analytics</h5>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.abandoned-carts.index', ['status' => 'open']) }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-warning border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-cart-x text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ number_format($abandonedSummary['open']) }}</h4>
                            <span class="text-muted small">Open Carts</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.abandoned-carts.index') }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-danger border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-bell text-danger fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ number_format($abandonedSummary['reminder_due']) }}</h4>
                            <span class="text-muted small">Reminder Queue</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.abandoned-carts.index') }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-primary border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-cash-coin text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">৳{{ number_format($abandonedSummary['potential_revenue'], 0) }}</h4>
                            <span class="text-muted small">Potential Revenue</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.abandoned-carts.index', ['status' => 'recovered']) }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-success border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-arrow-repeat text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ $abandonedSummary['recovery_rate'] }}%</h4>
                            <span class="text-muted small">Recovery Rate</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Product Inventory Alert -->
<h5 class="mb-3 fw-bold text-dark"><i class="bi bi-exclamation-triangle me-2"></i>Product Inventory Alert</h5>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.products.index', ['stock' => 'out_of_stock']) }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-danger border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-exclamation-octagon text-danger fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0 {{ $inventoryAlert['out_of_stock'] > 0 ? 'text-danger' : '' }}">{{ number_format($inventoryAlert['out_of_stock']) }}</h3>
                            <span class="text-muted small">Out of Stock</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.products.index', ['stock' => 'low_stock']) }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-warning border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0 {{ $inventoryAlert['low_stock'] > 0 ? 'text-warning' : '' }}">{{ number_format($inventoryAlert['low_stock']) }}</h3>
                            <span class="text-muted small">
                                Low Stock (≤10)
                                @if($inventoryAlert['low_stock_variants'] > 0)
                                    <span class="badge bg-warning bg-opacity-10 text-warning ms-1">{{ $inventoryAlert['low_stock_variants'] }} variants</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.products.index', ['status' => 'active']) }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-success border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-check2-circle text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ number_format($inventoryAlert['total_active']) }}</h3>
                            <span class="text-muted small">Active Products</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" class="text-decoration-none d-block h-100 text-dark">
            <div class="card stat-card h-100 border-start border-secondary border-4 hover-elevate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-secondary bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-pause-circle text-secondary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ number_format($inventoryAlert['total_inactive']) }}</h3>
                            <span class="text-muted small">Inactive Products</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold">Revenue - Last 30 Days</h6>
                <a href="{{ route('admin.bi.sales-reports') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-graph-up me-1"></i> View Full Report
                </a>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold">Recent Orders</h6>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentOrders->isEmpty())
                    <div class="text-center py-4 text-muted">
                        No orders yet.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
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
                                        <td>{{ $order->customer_name }}</td>
                                        <td>
                                            <span class="badge badge-status-{{ $order->status }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">৳{{ number_format($order->total, 2) }}</td>
                                        <td>{{ $order->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Order Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-clock-history me-2"></i>Orders Pending Action
                </h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-clock text-warning me-2"></i> Pending</span>
                        <span class="badge bg-warning text-dark rounded-pill px-3">{{ $stats['pending_orders'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-hourglass-split text-info me-2"></i> Processing</span>
                        <span class="badge bg-info rounded-pill px-3">{{ $stats['processing_orders'] }}</span>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-arrow-right me-1"></i> Manage Orders
                </a>
            </div>
        </div>

        <!-- Abandoned Carts -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-cart-x me-2"></i>Abandoned Carts
                </h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-hourglass text-warning me-2"></i> Open</span>
                        <span class="badge bg-warning text-dark rounded-pill px-3">{{ number_format($abandonedSummary['open']) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-bell text-danger me-2"></i> Reminder Due</span>
                        <span class="badge bg-danger rounded-pill px-3">{{ number_format($abandonedSummary['reminder_due']) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-cash text-primary me-2"></i> Potential Revenue</span>
                        <span class="badge bg-primary rounded-pill px-3">৳{{ number_format($abandonedSummary['potential_revenue'], 0) }}</span>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.abandoned-carts.index') }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-arrow-right me-1"></i> Manage Abandoned Carts
                </a>
            </div>
        </div>

        <!-- Top Products -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-trophy me-2"></i>Top Products This Month
                </h6>
            </div>
            <div class="card-body p-0">
                @if($topProducts->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No sales this month yet.
                    </div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($topProducts as $index => $product)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center">
                                    @if($index < 3)
                                        <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'dark') }} rounded-circle me-2" style="width: 24px; height: 24px; line-height: 16px;">
                                            {{ $index + 1 }}
                                        </span>
                                    @else
                                        <span class="text-muted me-3 small">{{ $index + 1 }}</span>
                                    @endif
                                    <span>{{ Str::limit($product->product_name, 22) }}</span>
                                </div>
                                <span class="text-success fw-semibold">৳{{ number_format($product->total_revenue, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.bi.product-performance') }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-bar-chart me-1"></i> View Products Report
                </a>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.products.create') }}" class="btn btn-success">
                        <i class="bi bi-plus-circle me-1"></i> Add Product
                    </a>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-outline-primary">
                        <i class="bi bi-folder-plus me-1"></i> Add Category
                    </a>
                    <a href="{{ route('admin.bi.sales-reports') }}" class="btn btn-outline-info">
                        <i class="bi bi-graph-up me-1"></i> Sales Report
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
    // Handle range select - show/hide custom date fields
    function handleRangeChange(select) {
        const customFields = document.getElementById('customDateFields');
        if (select.value === 'custom') {
            customFields.style.display = 'flex';
        } else {
            customFields.style.display = 'none';
            select.form.submit();
        }
    }

    const chartData = @json($chartData);

    // Calculate top 7 and bottom 7 days for coloring
    const revenuesWithIndices = chartData.map((d, index) => ({ index: index, revenue: d.revenue }));
    // Sort descending
    revenuesWithIndices.sort((a, b) => b.revenue - a.revenue);
    
    // Top 7 (greater than 0)
    const top7Indices = revenuesWithIndices.filter(r => r.revenue > 0).slice(0, 7).map(r => r.index);
    // Bottom 7 (greater than 0, not in top 7)
    const bottom7Indices = revenuesWithIndices.filter(r => r.revenue > 0 && !top7Indices.includes(r.index)).slice(-7).map(r => r.index);

    const backgroundColors = chartData.map((d, index) => {
        if (top7Indices.includes(index)) return 'rgba(25, 135, 84, 0.8)'; // Green for Top 7
        if (bottom7Indices.includes(index)) return 'rgba(220, 53, 69, 0.8)'; // Red for Bottom 7
        return 'rgba(13, 110, 253, 0.8)'; // Blue for others
    });

    const hoverBackgroundColors = chartData.map((d, index) => {
        if (top7Indices.includes(index)) return '#198754';
        if (bottom7Indices.includes(index)) return '#dc3545';
        return '#0d6efd';
    });

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: chartData.map(d => d.label),
            datasets: [{
                label: 'Revenue (৳)',
                data: chartData.map(d => d.revenue),
                backgroundColor: backgroundColors,
                hoverBackgroundColor: hoverBackgroundColors,
                borderRadius: 0,
                borderSkipped: false,
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
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '৳' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>
@endpush

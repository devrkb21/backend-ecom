@extends('admin.layouts.app')

@section('title', 'Sales Reports')

@section('styles')
<style>
    .chart-container { position: relative; height: 300px; }
</style>
@endsection

@section('content')

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark">
                <i class="bi bi-graph-up"></i> Sales Reports
            </h1>
            <p class="text-muted mb-0">Detailed revenue and sales analysis</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <a href="?period=today" class="btn btn-{{ $period == 'today' ? 'primary' : 'outline-primary' }} btn-sm">Today</a>
                <a href="?period=yesterday" class="btn btn-{{ $period == 'yesterday' ? 'primary' : 'outline-primary' }} btn-sm">Yesterday</a>
                <a href="?period=this_week" class="btn btn-{{ in_array($period, ['week', 'this_week']) ? 'primary' : 'outline-primary' }} btn-sm">This Week</a>
                <a href="?period=this_month" class="btn btn-{{ in_array($period, ['month', 'this_month']) ? 'primary' : 'outline-primary' }} btn-sm">This Month</a>
                <a href="?period=last_month" class="btn btn-{{ $period == 'last_month' ? 'primary' : 'outline-primary' }} btn-sm">Last Month</a>
                <a href="?period=this_year" class="btn btn-{{ in_array($period, ['year', 'this_year']) ? 'primary' : 'outline-primary' }} btn-sm">This Year</a>
                <a href="?period=last_year" class="btn btn-{{ $period == 'last_year' ? 'primary' : 'outline-primary' }} btn-sm">Last Year</a>
            </div>
            <a href="{{ route('admin.bi.export-sales', ['period' => $period]) }}" class="btn btn-success btn-sm" data-no-admin-ajax="1">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('admin.orders.index') }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                    <i class="bi bi-currency-dollar text-primary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0 text-primary">৳{{ number_format($overview['revenue'], 0) }}</h3>
                                <span class="text-muted small">Gross Revenue</span>
                                <br>
                                <small class="{{ $overview['revenue_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    <i class="bi bi-arrow-{{ $overview['revenue_growth'] >= 0 ? 'up' : 'down' }}"></i>
                                    {{ abs($overview['revenue_growth']) }}%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('admin.orders.index') }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                    <i class="bi bi-wallet2 text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0 text-success">৳{{ number_format($overview['net_revenue'], 0) }}</h3>
                                <span class="text-muted small">Net Revenue</span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('admin.orders.index') }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                                    <i class="bi bi-receipt text-info fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0 text-info">{{ number_format($overview['orders']) }}</h3>
                                <span class="text-muted small">Total Orders</span>
                                <br>
                                <small class="{{ $overview['orders_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    <i class="bi bi-arrow-{{ $overview['orders_growth'] >= 0 ? 'up' : 'down' }}"></i>
                                    {{ abs($overview['orders_growth']) }}%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('admin.orders.index') }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                    <i class="bi bi-graph-up text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0 text-warning">৳{{ number_format($overview['average_order_value'], 0) }}</h3>
                                <span class="text-muted small">Avg Order Value</span>
                                <br>
                                <small class="{{ $overview['aov_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    <i class="bi bi-arrow-{{ $overview['aov_growth'] >= 0 ? 'up' : 'down' }}"></i>
                                    {{ abs($overview['aov_growth']) }}%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('admin.orders.index') }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                                    <i class="bi bi-arrow-return-left text-danger fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0 text-danger">৳{{ number_format($overview['refunds'], 0) }}</h3>
                                <span class="text-muted small">Refunds</span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-secondary bg-opacity-10 p-3 rounded-circle">
                                    <i class="bi bi-x-circle text-secondary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0 text-secondary">৳{{ number_format($overview['cancelled_revenue'], 0) }}</h3>
                                <span class="text-muted small">Cancelled ({{ $overview['cancelled_count'] }} {{ Str::plural('order', $overview['cancelled_count']) }})</span>
                                <br>
                                <small class="{{ $overview['cancelled_growth'] <= 0 ? 'text-success' : 'text-danger' }}">
                                    <i class="bi bi-arrow-{{ $overview['cancelled_growth'] >= 0 ? 'up' : 'down' }}"></i>
                                    {{ abs($overview['cancelled_growth']) }}%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Daily Sales Chart -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Daily Sales Trend</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancellation by Reason -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Cancellation By Reason</h6>
                </div>
                <div class="card-body">
                    @if(empty($byCancellationReason))
                        <div class="d-flex justify-content-center align-items-center" style="height: 250px;">
                            <span class="text-muted">No cancellations found</span>
                        </div>
                    @else
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="chart-container" style="height: 250px;">
                                    <canvas id="cancellationReasonChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Reason</th>
                                                <th class="text-center">Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($byCancellationReason as $reason)
                                            <tr>
                                                <td>{{ $reason['reason'] }}</td>
                                                <td class="text-center">{{ $reason['count'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sales by Payment Method -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Sales by Payment Method</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container" style="height: 200px;">
                                <canvas id="paymentMethodChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th class="text-center">Orders</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byPaymentMethod as $method)
                                    <tr>
                                        <td>{{ $method['method'] }}</td>
                                        <td class="text-center">{{ $method['count'] }}</td>
                                        <td class="text-end">৳{{ number_format($method['total'], 0) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales by Order Source -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Sales by Order Source</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container" style="height: 200px;">
                                <canvas id="orderSourceChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th class="text-center">Orders</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byOrderSource as $source)
                                    <tr>
                                        <td>{{ ucfirst($source['source']) }}</td>
                                        <td class="text-center">{{ $source['count'] }}</td>
                                        <td class="text-end">৳{{ number_format($source['total'], 0) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Hourly Distribution -->
        <div class="col-lg-8">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">
                        Hourly Sales Distribution
                        <small class="text-muted">(Peak: {{ sprintf('%02d:00', $hourlyDistribution['peak_hour']) }})</small>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales by Location -->
        <div class="col-lg-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Top Sales Locations</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Area/City</th>
                                    <th class="text-center">Orders</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byLocation as $location)
                                <tr>
                                    <td>{{ $location['city'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill">{{ $location['count'] }}</span>
                                    </td>
                                    <td class="text-end fw-bold">৳{{ number_format($location['total'], 0) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">No location data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales by Category -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">Sales by Category</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th class="text-center">Orders</th>
                            <th class="text-center">Units Sold</th>
                            <th class="text-end">Revenue</th>
                            <th style="width: 200px;">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalCategoryRevenue = array_sum(array_column($byCategory, 'revenue')); @endphp
                        @foreach($byCategory as $index => $category)
                        @php $share = $totalCategoryRevenue > 0 ? ($category['revenue'] / $totalCategoryRevenue) * 100 : 0; @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <a href="{{ route('admin.categories.edit', $category['id']) }}">
                                    {{ $category['name'] }}
                                </a>
                            </td>
                            <td class="text-center">{{ number_format($category['orders']) }}</td>
                            <td class="text-center">{{ number_format($category['units_sold']) }}</td>
                            <td class="text-end fw-bold">৳{{ number_format($category['revenue'], 2) }}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $share }}%">
                                        {{ number_format($share, 1) }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Sales Chart
const dailyCtx = document.getElementById('dailySalesChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: @json($dailyChart['labels']),
        datasets: [
            {
                label: 'Revenue (৳)',
                data: @json($dailyChart['datasets'][0]['data']),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y',
            },
            {
                label: 'Orders',
                data: @json($dailyChart['datasets'][1]['data']),
                borderColor: '#1cc88a',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
                tension: 0.3,
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Revenue (৳)' } },
            y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Orders' }, grid: { drawOnChartArea: false } }
        }
    }
});

// Cancellation Reason Pie Chart
const cancelReasonCanvas = document.getElementById('cancellationReasonChart');
if (cancelReasonCanvas) {
    const cancelReasonCtx = cancelReasonCanvas.getContext('2d');
    new Chart(cancelReasonCtx, {
        type: 'pie',
        data: {
            labels: @json(array_column($byCancellationReason, 'reason')),
            datasets: [{
                data: @json(array_column($byCancellationReason, 'count')),
                backgroundColor: ['#e74a3b', '#c0392b', '#e06666', '#990000', '#cc0000', '#f4cccc', '#ea9999', '#85200c'],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

// Payment Method Pie Chart
const paymentCtx = document.getElementById('paymentMethodChart').getContext('2d');
new Chart(paymentCtx, {
    type: 'doughnut',
    data: {
        labels: @json(array_column($byPaymentMethod, 'method')),
        datasets: [{
            data: @json(array_column($byPaymentMethod, 'total')),
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Order Source Pie Chart
const orderSourceCtx = document.getElementById('orderSourceChart').getContext('2d');
new Chart(orderSourceCtx, {
    type: 'doughnut',
    data: {
        labels: @json(array_column($byOrderSource, 'source')),
        datasets: [{
            data: @json(array_column($byOrderSource, 'total')),
            backgroundColor: ['#1cc88a', '#4e73df', '#e74a3b', '#f6c23e', '#36b9cc', '#858796', '#5a5c69'],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed !== null) {
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            label += '৳' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                        }
                        return label;
                    }
                }
            }
        }
    }
});

// Hourly Distribution Chart
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: @json($hourlyDistribution['labels']),
        datasets: [{
            label: 'Orders',
            data: @json($hourlyDistribution['orders']),
            backgroundColor: 'rgba(78, 115, 223, 0.8)',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: false } }
    }
});
</script>
@endpush

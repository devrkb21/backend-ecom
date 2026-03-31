@extends('admin.layouts.app')

@section('title', 'Sales Reports')

@section('styles')
<style>
    .chart-container { position: relative; height: 300px; }
    .stat-mini { text-align: center; padding: 1rem; }
    .stat-mini .value { font-size: 1.5rem; font-weight: bold; }
    .stat-mini .label { font-size: 0.8rem; color: #6c757d; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-graph-up"></i> Sales Reports
            </h1>
            <p class="text-muted mb-0">Detailed revenue and sales analysis</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <a href="?period=today&days={{ $days }}" class="btn btn-{{ $period == 'today' ? 'primary' : 'outline-primary' }} btn-sm">Today</a>
                <a href="?period=week&days={{ $days }}" class="btn btn-{{ $period == 'week' ? 'primary' : 'outline-primary' }} btn-sm">Week</a>
                <a href="?period=month&days={{ $days }}" class="btn btn-{{ $period == 'month' ? 'primary' : 'outline-primary' }} btn-sm">Month</a>
                <a href="?period=quarter&days={{ $days }}" class="btn btn-{{ $period == 'quarter' ? 'primary' : 'outline-primary' }} btn-sm">Quarter</a>
                <a href="?period=year&days={{ $days }}" class="btn btn-{{ $period == 'year' ? 'primary' : 'outline-primary' }} btn-sm">Year</a>
            </div>
            <a href="{{ route('admin.bi.export-sales', ['period' => $period, 'days' => $days]) }}" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="stat-mini">
                    <div class="value text-primary">৳{{ number_format($overview['revenue'], 0) }}</div>
                    <div class="label">Gross Revenue</div>
                    <small class="{{ $overview['revenue_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $overview['revenue_growth'] >= 0 ? '+' : '' }}{{ $overview['revenue_growth'] }}%
                    </small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="stat-mini">
                    <div class="value text-success">৳{{ number_format($overview['net_revenue'], 0) }}</div>
                    <div class="label">Net Revenue</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="stat-mini">
                    <div class="value text-info">{{ number_format($overview['orders']) }}</div>
                    <div class="label">Total Orders</div>
                    <small class="{{ $overview['orders_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $overview['orders_growth'] >= 0 ? '+' : '' }}{{ $overview['orders_growth'] }}%
                    </small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="stat-mini">
                    <div class="value text-warning">৳{{ number_format($overview['average_order_value'], 0) }}</div>
                    <div class="label">Avg Order Value</div>
                    <small class="{{ $overview['aov_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $overview['aov_growth'] >= 0 ? '+' : '' }}{{ $overview['aov_growth'] }}%
                    </small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="stat-mini">
                    <div class="value text-danger">৳{{ number_format($overview['refunds'], 0) }}</div>
                    <div class="label">Refunds</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="stat-mini">
                    <div class="value text-secondary">{{ $overview['start_date'] }}</div>
                    <div class="label">to {{ $overview['end_date'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Sales Chart -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daily Sales Trend</h6>
            <div class="btn-group btn-group-sm">
                <a href="?period={{ $period }}&days=7" class="btn btn-{{ $days == 7 ? 'primary' : 'outline-secondary' }}">7 days</a>
                <a href="?period={{ $period }}&days=30" class="btn btn-{{ $days == 30 ? 'primary' : 'outline-secondary' }}">30 days</a>
                <a href="?period={{ $period }}&days=60" class="btn btn-{{ $days == 60 ? 'primary' : 'outline-secondary' }}">60 days</a>
                <a href="?period={{ $period }}&days=90" class="btn btn-{{ $days == 90 ? 'primary' : 'outline-secondary' }}">90 days</a>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="dailySalesChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sales by Payment Method -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sales by Payment Method</h6>
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
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byPaymentMethod as $method)
                                    <tr>
                                        <td>{{ $method['method'] }}</td>
                                        <td class="text-center">{{ $method['count'] }}</td>
                                        <td class="text-right">৳{{ number_format($method['total'], 0) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hourly Distribution -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Hourly Sales Distribution
                        <small class="text-muted">(Peak: {{ sprintf('%02d:00', $hourlyDistribution['peak_hour']) }})</small>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales by Category -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Sales by Category</h6>
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
                            <th class="text-right">Revenue</th>
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
                            <td class="text-right font-weight-bold">৳{{ number_format($category['revenue'], 2) }}</td>
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
</div>
@endsection

@section('scripts')
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
@endsection

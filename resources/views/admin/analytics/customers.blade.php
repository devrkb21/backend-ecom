@extends('admin.layouts.app')

@section('title', 'Customers Report')
@section('page-title', 'Customers Report')

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
                <a href="{{ route('admin.analytics.export', ['type' => 'customers', 'period' => $period]) }}" 
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
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-people text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ number_format($stats['total_customers']) }}</h3>
                        <span class="text-muted small">Total Customers</span>
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
                            <i class="bi bi-person-plus text-success fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ number_format($stats['new_customers']) }}</h3>
                        <span class="text-muted small">New This Period</span>
                        @if($stats['customer_growth'] != 0)
                            <br>
                            <small class="{{ $stats['customer_growth'] > 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bi bi-{{ $stats['customer_growth'] > 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                {{ abs(round($stats['customer_growth'], 1)) }}% vs previous
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
                            <i class="bi bi-cart-check text-info fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ number_format($stats['customers_with_orders']) }}</h3>
                        <span class="text-muted small">With Orders</span>
                        <br>
                        <small class="text-muted">
                            {{ $stats['total_customers'] > 0 ? round(($stats['customers_with_orders'] / $stats['total_customers']) * 100, 1) : 0 }}% conversion
                        </small>
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
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-arrow-repeat text-warning fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ number_format($stats['repeat_customers']) }}</h3>
                        <span class="text-muted small">Repeat Customers</span>
                        <br>
                        <small class="text-muted">
                            {{ $stats['customers_with_orders'] > 0 ? round(($stats['repeat_customers'] / $stats['customers_with_orders']) * 100, 1) : 0 }}% retention
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <!-- Customer Registrations Chart -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-graph-up me-2"></i>New Customer Registrations</h6>
            </div>
            <div class="card-body">
                <canvas id="registrationsChart" height="80"></canvas>
            </div>
        </div>

        <!-- Top Customers Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-trophy me-2"></i>Top Customers by Revenue</h6>
            </div>
            <div class="card-body p-0">
                @if($topCustomers->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No customer data available.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th class="text-center">Orders</th>
                                    <th class="text-end">Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topCustomers as $index => $customer)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'dark') }}">
                                                    {{ $index + 1 }}
                                                </span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.users.show', $customer) }}" class="text-decoration-none">
                                                <strong>{{ $customer->name }}</strong>
                                            </a>
                                        </td>
                                        <td>{{ $customer->email }}</td>
                                        <td class="text-center">{{ $customer->completed_orders ?? 0 }}</td>
                                        <td class="text-end">
                                            <strong>৳{{ number_format($customer->total_spent ?? 0, 2) }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Customer Stats Sidebar -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Customer Overview</h6>
            </div>
            <div class="card-body">
                <canvas id="customerPieChart" height="220"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-lightning me-2"></i>Quick Stats</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-person-x text-muted me-2"></i>Never Purchased</span>
                        <span class="badge bg-secondary rounded-pill">
                            {{ $stats['total_customers'] - $stats['customers_with_orders'] }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-cart text-muted me-2"></i>Single Purchase</span>
                        <span class="badge bg-info rounded-pill">
                            {{ $stats['customers_with_orders'] - $stats['repeat_customers'] }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-arrow-repeat text-muted me-2"></i>Repeat Buyers</span>
                        <span class="badge bg-success rounded-pill">
                            {{ $stats['repeat_customers'] }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const registrationData = @json($registrationData);
    const stats = @json($stats);

    // Registrations Line Chart
    new Chart(document.getElementById('registrationsChart'), {
        type: 'line',
        data: {
            labels: registrationData.map(d => d.label),
            datasets: [{
                label: 'New Customers',
                data: registrationData.map(d => d.count),
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Customer Pie Chart
    new Chart(document.getElementById('customerPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Never Purchased', 'Single Purchase', 'Repeat Buyers'],
            datasets: [{
                data: [
                    stats.total_customers - stats.customers_with_orders,
                    stats.customers_with_orders - stats.repeat_customers,
                    stats.repeat_customers
                ],
                backgroundColor: ['#6c757d', '#0dcaf0', '#198754'],
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

@extends('admin.layouts.app')

@section('title', 'Business Intelligence')

@section('styles')
<style>
    .stat-card {
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .growth-positive { color: #1cc88a; }
    .growth-negative { color: #e74a3b; }
    .alert-card {
        border-left: 4px solid;
    }
    .alert-card.warning { border-left-color: #f6c23e; }
    .alert-card.danger { border-left-color: #e74a3b; }
</style>
@endsection

@section('content')

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark">
                <i class="bi bi-graph-up-arrow"></i> Business Intelligence
            </h1>
            <p class="text-muted mb-0">Overview of your business performance</p>
        </div>
        <div class="btn-group">
            <a href="?period=today" class="btn btn-{{ $period == 'today' ? 'primary' : 'outline-primary' }}">Today</a>
            <a href="?period=yesterday" class="btn btn-{{ $period == 'yesterday' ? 'primary' : 'outline-primary' }}">Yesterday</a>
            <a href="?period=this_week" class="btn btn-{{ in_array($period, ['week', 'this_week']) ? 'primary' : 'outline-primary' }}">This Week</a>
            <a href="?period=this_month" class="btn btn-{{ in_array($period, ['month', 'this_month']) ? 'primary' : 'outline-primary' }}">This Month</a>
            <a href="?period=last_month" class="btn btn-{{ $period == 'last_month' ? 'primary' : 'outline-primary' }}">Last Month</a>
            <a href="?period=this_year" class="btn btn-{{ in_array($period, ['year', 'this_year']) ? 'primary' : 'outline-primary' }}">This Year</a>
            <a href="?period=last_year" class="btn btn-{{ $period == 'last_year' ? 'primary' : 'outline-primary' }}">Last Year</a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('admin.bi.sales-reports', ['period' => $period]) }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="small fw-bold text-primary text-uppercase mb-1">Revenue</div>
                                <div class="h5 mb-0 fw-bold text-dark">৳{{ number_format($salesOverview['revenue'], 2) }}</div>
                                <small class="{{ $salesOverview['revenue_growth'] >= 0 ? 'growth-positive' : 'growth-negative' }}">
                                    <i class="fas fa-{{ $salesOverview['revenue_growth'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                    {{ abs($salesOverview['revenue_growth']) }}% vs last period
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-currency-dollar fa-2x text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('admin.bi.sales-reports', ['period' => $period]) }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="small fw-bold text-success text-uppercase mb-1">Orders</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ number_format($salesOverview['orders']) }}</div>
                                <small class="{{ $salesOverview['orders_growth'] >= 0 ? 'growth-positive' : 'growth-negative' }}">
                                    <i class="fas fa-{{ $salesOverview['orders_growth'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                    {{ abs($salesOverview['orders_growth']) }}% vs last period
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-cart fa-2x text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('admin.bi.sales-reports', ['period' => $period]) }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="small fw-bold text-info text-uppercase mb-1">Avg Order Value</div>
                                <div class="h5 mb-0 fw-bold text-dark">৳{{ number_format($salesOverview['average_order_value'], 2) }}</div>
                                <small class="{{ $salesOverview['aov_growth'] >= 0 ? 'growth-positive' : 'growth-negative' }}">
                                    <i class="fas fa-{{ $salesOverview['aov_growth'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                    {{ abs($salesOverview['aov_growth']) }}% vs last period
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-graph-up fa-2x text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('admin.bi.customer-analytics', ['period' => $period]) }}" class="text-decoration-none d-block h-100 text-dark">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="small fw-bold text-warning text-uppercase mb-1">New Customers</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ number_format($customerOverview['new_customers']) }}</div>
                                <small class="{{ $customerOverview['new_customer_growth'] >= 0 ? 'growth-positive' : 'growth-negative' }}">
                                    <i class="fas fa-{{ $customerOverview['new_customer_growth'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                    {{ abs($customerOverview['new_customer_growth']) }}% vs last period
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people fa-2x text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Quick Links to Reports -->
    <div class="row mb-4">
        <div class="col-md-3">
            <a href="{{ route('admin.bi.sales-reports') }}" class="card stat-card h-100 text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-bar-chart fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Sales Reports</h5>
                    <p class="text-muted small">Daily, weekly, monthly revenue analysis</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.bi.inventory-alerts') }}" class="card stat-card h-100 text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Inventory Alerts</h5>
                    <p class="text-muted small">Low stock & inventory insights</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.bi.customer-analytics') }}" class="card stat-card h-100 text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-person-badge fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Customer Analytics</h5>
                    <p class="text-muted small">Lifetime value & segmentation</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.bi.product-performance') }}" class="card stat-card h-100 text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-trophy fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Product Performance</h5>
                    <p class="text-muted small">Best sellers & slow movers</p>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Alerts Column -->
        <div class="col-lg-4">
            <!-- Low Stock Alert -->
            @if(count($lowStock) > 0)
            <div class="card alert-card warning shadow mb-4">
                <div class="card-header py-3 bg-warning text-dark">
                    <h6 class="m-0 fw-bold">
                        <i class="bi bi-exclamation-triangle"></i> Low Stock Alert ({{ count($lowStock) }})
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($lowStock, 0, 5) as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $item['id']) }}">
                                            {{ Str::limit($item['name'], 25) }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-warning">{{ $item['stock'] }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(count($lowStock) > 5)
                    <div class="p-2 text-center">
                        <a href="{{ route('admin.bi.inventory-alerts') }}">View all {{ count($lowStock) }} items</a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Out of Stock Alert -->
            @if(count($outOfStock) > 0)
            <div class="card alert-card danger shadow mb-4">
                <div class="card-header py-3 bg-danger text-white">
                    <h6 class="m-0 fw-bold">
                        <i class="bi bi-x-circle"></i> Out of Stock ({{ count($outOfStock) }})
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Last Sale</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($outOfStock, 0, 5) as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $item['id']) }}">
                                            {{ Str::limit($item['name'], 20) }}
                                        </a>
                                    </td>
                                    <td><small class="text-muted">{{ $item['last_sale'] }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(count($outOfStock) > 5)
                    <div class="p-2 text-center">
                        <a href="{{ route('admin.bi.inventory-alerts') }}">View all {{ count($outOfStock) }} items</a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Customer Stats -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-people"></i> Customer Stats
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="h4 mb-0 text-primary">{{ number_format($customerOverview['total_customers']) }}</div>
                            <small class="text-muted">Total Customers</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h4 mb-0 text-success">{{ number_format($customerOverview['active_customers']) }}</div>
                            <small class="text-muted">Active This Period</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-0 text-info">{{ number_format($customerOverview['returning_customers']) }}</div>
                            <small class="text-muted">Returning Customers</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-0 text-warning">{{ $customerOverview['retention_rate'] }}%</div>
                            <small class="text-muted">Retention Rate</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Best Sellers Column -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-trophy"></i> Top Selling Products
                    </h6>
                    <a href="{{ route('admin.bi.product-performance') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center">Units Sold</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-center">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bestSellers as $product)
                                <tr>
                                    <td>
                                        <span class="badge badge-{{ $product['rank'] <= 3 ? 'success' : 'secondary' }}">
                                            {{ $product['rank'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $product['id']) }}">
                                            {{ Str::limit($product['name'], 40) }}
                                        </a>
                                        <br><small class="text-muted">{{ $product['sku'] }}</small>
                                    </td>
                                    <td class="text-center">{{ number_format($product['units_sold']) }}</td>
                                    <td class="text-end fw-bold">৳{{ number_format($product['revenue'], 2) }}</td>
                                    <td class="text-center">
                                        @if($product['stock_status'] == 'good')
                                            <span class="badge badge-success">{{ $product['stock'] }}</span>
                                        @elseif($product['stock_status'] == 'low')
                                            <span class="badge badge-warning">{{ $product['stock'] }}</span>
                                        @else
                                            <span class="badge badge-danger">Out</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No sales data for this period
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Revenue Summary -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted text-uppercase mb-1">Net Revenue</h6>
                                    <h3 class="mb-0 text-success">৳{{ number_format($salesOverview['net_revenue'], 2) }}</h3>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">Gross: ৳{{ number_format($salesOverview['revenue'], 2) }}</small>
                                    <small class="text-danger d-block">Refunds: -৳{{ number_format($salesOverview['refunds'], 2) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted text-uppercase mb-1">Conversion Metrics</h6>
                                    <h3 class="mb-0">{{ $salesOverview['orders'] }} <small class="text-muted">orders</small></h3>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">Period: {{ ucfirst($period) }}</small>
                                    <small class="text-muted d-block">{{ $salesOverview['start_date'] }} - {{ $salesOverview['end_date'] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('admin.layouts.app')

@section('title', 'Customer Analytics')

@section('styles')
<style>
    .segment-card { border-left: 4px solid; }
    .segment-champions { border-left-color: #1cc88a; }
    .segment-loyal { border-left-color: #4e73df; }
    .segment-potential { border-left-color: #36b9cc; }
    .segment-at_risk { border-left-color: #f6c23e; }
    .segment-dormant { border-left-color: #858796; }
    .segment-new { border-left-color: #5a5c69; }
    .cohort-cell { text-align: center; padding: 8px; font-size: 0.85rem; }
    .cohort-header { background: #f8f9fc; font-weight: bold; }
    .cohort-100 { background: #1cc88a; color: white; }
    .cohort-75 { background: #36b9cc; color: white; }
    .cohort-50 { background: #4e73df; color: white; }
    .cohort-25 { background: #f6c23e; }
    .cohort-0 { background: #e74a3b; color: white; }
    .status-badge { font-size: 0.75rem; }
</style>
@endsection

@section('content')

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark">
                <i class="bi bi-people"></i> Customer Analytics
            </h1>
            <p class="text-muted mb-0">Customer lifetime value, segmentation & insights</p>
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
            <a href="{{ route('admin.bi.export-customers') }}" class="btn btn-success btn-sm" data-no-admin-ajax="1">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>

    <!-- Customer Overview -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body text-center">
                    <div class="small fw-bold text-primary text-uppercase mb-1">Total Customers</div>
                    <div class="h4 mb-0 fw-bold">{{ number_format($overview['total_customers']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body text-center">
                    <div class="small fw-bold text-success text-uppercase mb-1">New Customers</div>
                    <div class="h4 mb-0 fw-bold">{{ number_format($overview['new_customers']) }}</div>
                    <small class="{{ $overview['new_customer_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $overview['new_customer_growth'] >= 0 ? '+' : '' }}{{ $overview['new_customer_growth'] }}%
                    </small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body text-center">
                    <div class="small fw-bold text-info text-uppercase mb-1">Active Customers</div>
                    <div class="h4 mb-0 fw-bold">{{ number_format($overview['active_customers']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body text-center">
                    <div class="small fw-bold text-warning text-uppercase mb-1">Returning</div>
                    <div class="h4 mb-0 fw-bold">{{ number_format($overview['returning_customers']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-8 col-12 mb-3">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body text-center">
                    <div class="small fw-bold text-secondary text-uppercase mb-1">Retention Rate</div>
                    <div class="h4 mb-0 fw-bold">{{ $overview['retention_rate'] }}%</div>
                    <div class="progress mt-2" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: {{ $overview['retention_rate'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Segments -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">Customer Segments</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($segments as $segment)
                @php
                    $segmentKey = strtolower(str_replace(' ', '_', $segment['segment']));
                    $colors = [
                        'champions' => 'success',
                        'loyal' => 'primary',
                        'potential' => 'info',
                        'at_risk' => 'warning',
                        'dormant' => 'secondary',
                        'new' => 'dark',
                    ];
                    $color = $colors[$segmentKey] ?? 'secondary';
                @endphp
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card segment-card segment-{{ $segmentKey }} h-100">
                        <div class="card-body">
                            <h6 class="text-{{ $color }} fw-bold mb-2">{{ $segment['segment'] }}</h6>
                            <div class="h4 mb-1">{{ number_format($segment['count']) }}</div>
                            <small class="text-muted">৳{{ number_format($segment['revenue'], 0) }} revenue</small>
                            <hr class="my-2">
                            <small class="text-muted">{{ $segment['criteria'] }}</small>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Customer Cohort Analysis -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">
                Customer Cohort Analysis
                <small class="text-muted">Monthly retention rates</small>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr class="cohort-header">
                            <th class="cohort-cell">Cohort</th>
                            <th class="cohort-cell">Size</th>
                            @for($i = 0; $i < 6; $i++)
                            <th class="cohort-cell">Month {{ $i }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cohorts as $cohort)
                        <tr>
                            <td class="cohort-cell cohort-header">{{ $cohort['month'] }}</td>
                            <td class="cohort-cell">{{ $cohort['cohort_size'] }}</td>
                            @foreach($cohort['retention'] as $rate)
                            @php
                                $bgClass = $rate >= 80 ? 'cohort-100' : ($rate >= 50 ? 'cohort-75' : ($rate >= 30 ? 'cohort-50' : ($rate >= 10 ? 'cohort-25' : 'cohort-0')));
                            @endphp
                            <td class="cohort-cell {{ $bgClass }}">{{ $rate }}%</td>
                            @endforeach
                            @for($j = count($cohort['retention']); $j < 6; $j++)
                            <td class="cohort-cell bg-light">-</td>
                            @endfor
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <small class="text-muted">
                    <strong>Reading:</strong> Each row shows a monthly cohort of customers. The percentages show what portion of that cohort made a purchase in subsequent months.
                </small>
            </div>
        </div>
    </div>

    <!-- Top Customers by Lifetime Value -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">Top Customers by Lifetime Value</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th class="text-center">Orders</th>
                            <th class="text-end">Lifetime Value</th>
                            <th class="text-end">Avg Order</th>
                            <th>Last Order</th>
                            <th>Customer Since</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topCustomers as $index => $customer)
                        <tr>
                            <td>
                                @if($index < 3)
                                    <span class="badge badge-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'dark') }}">
                                        {{ $index + 1 }}
                                    </span>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.users.show', $customer['id']) }}">
                                    <strong>{{ $customer['name'] }}</strong>
                                </a>
                                <br><small class="text-muted">{{ $customer['email'] }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info">{{ $customer['total_orders'] }}</span>
                            </td>
                            <td class="text-end fw-bold text-success">
                                ৳{{ number_format($customer['lifetime_value'], 0) }}
                            </td>
                            <td class="text-end">
                                ৳{{ number_format($customer['avg_order_value'], 0) }}
                            </td>
                            <td>
                                @if($customer['last_order_at'])
                                    {{ \Carbon\Carbon::parse($customer['last_order_at'])->diffForHumans() }}
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>{{ $customer['customer_since'] }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'active' => 'success',
                                        'new' => 'info',
                                        'at_risk' => 'warning',
                                        'dormant' => 'danger',
                                    ];
                                @endphp
                                <span class="badge badge-{{ $statusColors[$customer['status']] ?? 'secondary' }} status-badge">
                                    {{ ucfirst(str_replace('_', ' ', $customer['status'])) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

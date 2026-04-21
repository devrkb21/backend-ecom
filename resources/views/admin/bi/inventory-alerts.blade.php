@extends('admin.layouts.app')

@section('title', 'Inventory Alerts')

@section('styles')
<style>
    .stock-badge { font-size: 0.9rem; min-width: 40px; }
    .turnover-good { color: #1cc88a; }
    .turnover-slow { color: #f6c23e; }
    .turnover-dead { color: #e74a3b; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-box-seam"></i> Inventory Alerts
            </h1>
            <p class="text-muted mb-0">Stock levels and inventory insights</p>
        </div>
        <div>
            <a href="{{ route('admin.bi.export-inventory', ['threshold' => $threshold]) }}" class="btn btn-success" data-no-admin-ajax="1">
                <i class="fas fa-download"></i> Export Report
            </a>
        </div>
    </div>

    <!-- Inventory Valuation Overview -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Units</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($valuation['total_units']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Retail Value</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">৳{{ number_format($valuation['total_retail_value'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Cost Value</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">৳{{ number_format($valuation['total_cost_value'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Potential Profit</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                        ৳{{ number_format($valuation['potential_profit'], 0) }}
                        <small class="text-muted ms-1">({{ number_format($valuation['potential_profit_percentage'], 1) }}%)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Low Stock Alert -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-warning">
                    <h6 class="m-0 font-weight-bold text-dark">
                        <i class="fas fa-exclamation-triangle"></i> Low Stock Items (≤ {{ $threshold }} units)
                        <span class="badge badge-dark ml-2">{{ count($lowStock) }}</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if(count($lowStock) > 0)
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="thead-light sticky-top">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th class="text-center">Stock</th>
                                    <th>Category</th>
                                    <th class="text-right">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStock as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $item['id']) }}">
                                            {{ Str::limit($item['name'], 30) }}
                                        </a>
                                    </td>
                                    <td><small class="text-muted">{{ $item['sku'] }}</small></td>
                                    <td class="text-center">
                                        <span class="badge badge-warning stock-badge">{{ $item['stock'] }}</span>
                                    </td>
                                    <td><small>{{ $item['category'] }}</small></td>
                                    <td class="text-right">৳{{ number_format($item['potential_loss'], 0) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">No low stock items!</p>
                    </div>
                    @endif
                </div>
                <div class="card-footer">
                    <form class="form-inline">
                        <label class="mr-2">Alert threshold:</label>
                        <select name="threshold" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="5" {{ $threshold == 5 ? 'selected' : '' }}>5 units</option>
                            <option value="10" {{ $threshold == 10 ? 'selected' : '' }}>10 units</option>
                            <option value="20" {{ $threshold == 20 ? 'selected' : '' }}>20 units</option>
                            <option value="50" {{ $threshold == 50 ? 'selected' : '' }}>50 units</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Out of Stock -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-danger text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-times-circle"></i> Out of Stock
                        <span class="badge badge-light ml-2">{{ count($outOfStock) }}</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if(count($outOfStock) > 0)
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="thead-light sticky-top">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Last Sale</th>
                                    <th class="text-right">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($outOfStock as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $item['id']) }}">
                                            {{ Str::limit($item['name'], 25) }}
                                        </a>
                                    </td>
                                    <td><small class="text-muted">{{ $item['sku'] }}</small></td>
                                    <td><small>{{ $item['category'] }}</small></td>
                                    <td><small class="text-muted">{{ $item['last_sale'] }}</small></td>
                                    <td class="text-right">৳{{ number_format($item['price'], 0) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">All products are in stock!</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory by Category -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Inventory Value by Category</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Category</th>
                            <th class="text-center">Units</th>
                            <th class="text-right">Retail Value</th>
                            <th class="text-right">Cost Value</th>
                            <th class="text-right">Margin</th>
                            <th style="width: 200px;">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($valuation['by_category'] as $category)
                        @php
                            $margin = $category['value'] > 0 ? (($category['value'] - $category['cost']) / $category['value']) * 100 : 0;
                            $share = $valuation['total_retail_value'] > 0 ? ($category['value'] / $valuation['total_retail_value']) * 100 : 0;
                        @endphp
                        <tr>
                            <td><strong>{{ $category['name'] }}</strong></td>
                            <td class="text-center">{{ number_format($category['units']) }}</td>
                            <td class="text-right">৳{{ number_format($category['value'], 0) }}</td>
                            <td class="text-right text-muted">৳{{ number_format($category['cost'], 0) }}</td>
                            <td class="text-right">
                                <span class="{{ $margin >= 30 ? 'text-success' : ($margin >= 15 ? 'text-warning' : 'text-danger') }}">
                                    {{ number_format($margin, 1) }}%
                                </span>
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-info" style="width: {{ $share }}%">
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

    <!-- Inventory Turnover -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Inventory Turnover Rate (Last 30 Days)
                <small class="text-muted">Higher is better - indicates faster selling products</small>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-center">Sold (30d)</th>
                            <th class="text-center">Turnover Rate</th>
                            <th class="text-center">Days to Sellout</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($turnover, 0, 30) as $item)
                        @php
                            $turnoverClass = $item['turnover_rate'] >= 4 ? 'turnover-good' : ($item['turnover_rate'] >= 1 ? 'turnover-slow' : 'turnover-dead');
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.products.edit', $item['id']) }}">
                                    {{ Str::limit($item['name'], 40) }}
                                </a>
                            </td>
                            <td class="text-center">{{ number_format($item['stock']) }}</td>
                            <td class="text-center">{{ number_format($item['sold_period']) }}</td>
                            <td class="text-center {{ $turnoverClass }}">
                                <strong>{{ $item['turnover_rate'] }}x</strong>
                            </td>
                            <td class="text-center">
                                @if($item['days_to_sellout'])
                                    {{ $item['days_to_sellout'] }} days
                                @else
                                    <span class="text-muted">∞</span>
                                @endif
                            </td>
                            <td>
                                @if($item['turnover_rate'] >= 4)
                                    <span class="badge badge-success">Fast Moving</span>
                                @elseif($item['turnover_rate'] >= 1)
                                    <span class="badge badge-warning">Moderate</span>
                                @else
                                    <span class="badge badge-danger">Slow Moving</span>
                                @endif
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

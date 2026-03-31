@extends('admin.layouts.app')

@section('title', 'Product Performance')

@section('styles')
<style>
    .chart-container { position: relative; height: 300px; }
    .rank-badge { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; }
    .rank-1 { background: #ffd700; color: #000; }
    .rank-2 { background: #c0c0c0; color: #000; }
    .rank-3 { background: #cd7f32; color: #fff; }
    .stock-good { color: #1cc88a; }
    .stock-low { color: #f6c23e; }
    .stock-out { color: #e74a3b; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-trophy"></i> Product Performance
            </h1>
            <p class="text-muted mb-0">Best sellers, slow movers & product insights</p>
        </div>
        <div class="btn-group">
            <a href="?period=week&days={{ $days }}" class="btn btn-{{ $period == 'week' ? 'primary' : 'outline-primary' }} btn-sm">Week</a>
            <a href="?period=month&days={{ $days }}" class="btn btn-{{ $period == 'month' ? 'primary' : 'outline-primary' }} btn-sm">Month</a>
            <a href="?period=quarter&days={{ $days }}" class="btn btn-{{ $period == 'quarter' ? 'primary' : 'outline-primary' }} btn-sm">Quarter</a>
            <a href="?period=year&days={{ $days }}" class="btn btn-{{ $period == 'year' ? 'primary' : 'outline-primary' }} btn-sm">Year</a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="h2 mb-0 text-primary">{{ count($bestSellers) }}</div>
                    <small class="text-muted">Products Sold</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="h2 mb-0 text-success">৳{{ number_format(array_sum(array_column($bestSellers, 'revenue')), 0) }}</div>
                    <small class="text-muted">Total Revenue</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="h2 mb-0 text-info">{{ number_format(array_sum(array_column($bestSellers, 'units_sold'))) }}</div>
                    <small class="text-muted">Units Sold</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="h2 mb-0 text-warning">{{ count($slowMovers) }}</div>
                    <small class="text-muted">Slow Movers</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Best Sellers -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-trophy text-warning"></i> Best Selling Products
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 60px;">Rank</th>
                            <th>Product</th>
                            <th class="text-center">Orders</th>
                            <th class="text-center">Units Sold</th>
                            <th class="text-right">Revenue</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bestSellers as $product)
                        <tr>
                            <td class="text-center">
                                @if($product['rank'] <= 3)
                                    <span class="rank-badge rank-{{ $product['rank'] }}">{{ $product['rank'] }}</span>
                                @else
                                    <span class="text-muted">{{ $product['rank'] }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.products.edit', $product['id']) }}">
                                    <strong>{{ Str::limit($product['name'], 45) }}</strong>
                                </a>
                                <br><small class="text-muted">SKU: {{ $product['sku'] }} | Price: ৳{{ number_format($product['price'], 0) }}</small>
                            </td>
                            <td class="text-center">{{ number_format($product['order_count']) }}</td>
                            <td class="text-center">
                                <span class="badge badge-info">{{ number_format($product['units_sold']) }}</span>
                            </td>
                            <td class="text-right font-weight-bold text-success">
                                ৳{{ number_format($product['revenue'], 0) }}
                            </td>
                            <td class="text-center">
                                @if($product['stock_status'] == 'good')
                                    <span class="stock-good"><i class="fas fa-check-circle"></i> {{ $product['stock'] }}</span>
                                @elseif($product['stock_status'] == 'low')
                                    <span class="stock-low"><i class="fas fa-exclamation-triangle"></i> {{ $product['stock'] }}</span>
                                @else
                                    <span class="stock-out"><i class="fas fa-times-circle"></i> Out</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-info" onclick="showTrends({{ $product['id'] }}, '{{ addslashes($product['name']) }}')">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No sales data for this period</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Slow Movers -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-warning">
            <h6 class="m-0 font-weight-bold text-dark">
                <i class="fas fa-turtle"></i> Slow Moving Products (Last {{ $days }} Days)
                <small>- Products with less than 5 sales</small>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-center">Stock</th>
                            <th class="text-right">Stock Value</th>
                            <th class="text-center">Sold ({{ $days }}d)</th>
                            <th class="text-center">Daily Rate</th>
                            <th class="text-center">Days to Sellout</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slowMovers as $product)
                        <tr>
                            <td>
                                <a href="{{ route('admin.products.edit', $product['id']) }}">
                                    {{ Str::limit($product['name'], 40) }}
                                </a>
                                <br><small class="text-muted">{{ $product['sku'] }}</small>
                            </td>
                            <td><small>{{ $product['category'] }}</small></td>
                            <td class="text-center">{{ number_format($product['stock']) }}</td>
                            <td class="text-right text-danger">৳{{ number_format($product['stock_value'], 0) }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $product['sold_last_period'] > 0 ? 'warning' : 'danger' }}">
                                    {{ $product['sold_last_period'] }}
                                </span>
                            </td>
                            <td class="text-center">{{ $product['daily_sales_rate'] }}</td>
                            <td class="text-center">
                                @if($product['days_of_stock'] === '365+')
                                    <span class="text-danger">{{ $product['days_of_stock'] }}</span>
                                @else
                                    {{ $product['days_of_stock'] }} days
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-success">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0">Great! No slow-moving products.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Product Conversion -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Product Purchase Metrics</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Purchases</th>
                            <th class="text-center">Units Sold</th>
                            <th class="text-center">Avg Units/Order</th>
                            <th class="text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conversionRates as $product)
                        <tr>
                            <td>
                                <a href="{{ route('admin.products.edit', $product['id']) }}">
                                    {{ Str::limit($product['name'], 45) }}
                                </a>
                                <br><small class="text-muted">{{ $product['sku'] }}</small>
                            </td>
                            <td class="text-center">{{ number_format($product['purchases']) }}</td>
                            <td class="text-center">{{ number_format($product['units_sold']) }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $product['avg_units_per_order'] > 1.5 ? 'success' : 'secondary' }}">
                                    {{ $product['avg_units_per_order'] }}
                                </span>
                            </td>
                            <td class="text-right">৳{{ number_format($product['revenue'], 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Product Trends Modal -->
<div class="modal fade" id="trendsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trendsModalTitle">Product Trends</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="chart-container">
                    <canvas id="productTrendsChart"></canvas>
                </div>
                <div class="row mt-3" id="trendsSummary">
                    <!-- Filled by JS -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let trendsChart = null;

function showTrends(productId, productName) {
    $('#trendsModalTitle').text('Sales Trends: ' + productName);
    $('#trendsModal').modal('show');
    
    // Fetch trends data
    fetch('{{ route("admin.bi.product-trends") }}?product_id=' + productId + '&days=30')
        .then(response => response.json())
        .then(data => {
            // Update summary
            $('#trendsSummary').html(`
                <div class="col-md-3 text-center">
                    <div class="h4 text-primary">${data.summary.total_units}</div>
                    <small class="text-muted">Total Units</small>
                </div>
                <div class="col-md-3 text-center">
                    <div class="h4 text-success">৳${data.summary.total_revenue.toLocaleString()}</div>
                    <small class="text-muted">Total Revenue</small>
                </div>
                <div class="col-md-3 text-center">
                    <div class="h4 text-info">${data.summary.avg_daily_units}</div>
                    <small class="text-muted">Avg Daily Units</small>
                </div>
                <div class="col-md-3 text-center">
                    <div class="h4 text-warning">${data.summary.peak_day || 'N/A'}</div>
                    <small class="text-muted">Peak Day</small>
                </div>
            `);
            
            // Update chart
            const ctx = document.getElementById('productTrendsChart').getContext('2d');
            
            if (trendsChart) {
                trendsChart.destroy();
            }
            
            trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.chart.labels,
                    datasets: [
                        {
                            label: 'Units Sold',
                            data: data.chart.units,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            fill: true,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Revenue (৳)',
                            data: data.chart.revenue,
                            borderColor: '#1cc88a',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5],
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Units' } },
                        y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Revenue' }, grid: { drawOnChartArea: false } }
                    }
                }
            });
        })
        .catch(err => {
            console.error('Error fetching trends:', err);
            $('#trendsSummary').html('<div class="col-12 text-center text-danger">Error loading data</div>');
        });
}
</script>
@endsection

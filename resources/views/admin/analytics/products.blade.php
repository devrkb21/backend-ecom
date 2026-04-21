@extends('admin.layouts.app')

@section('title', 'Products Report')
@section('page-title', 'Products Report')

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
                <a href="{{ route('admin.analytics.export', ['type' => 'products', 'period' => $period]) }}" 
                   class="btn btn-sm btn-outline-success"
                   data-no-admin-ajax="1">
                    <i class="bi bi-download me-1"></i> Export CSV
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- Best Selling Products -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-trophy me-2"></i>Best Selling Products</h6>
            </div>
            <div class="card-body p-0">
                @if($bestSellers->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No sales data available for this period.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-end">Qty Sold</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bestSellers as $index => $product)
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
                                            <strong>{{ $product->product_name }}</strong>
                                        </td>
                                        <td class="text-end">{{ number_format($product->total_quantity) }}</td>
                                        <td class="text-end">৳{{ number_format($product->total_revenue, 2) }}</td>
                                        <td class="text-end">{{ number_format($product->order_count) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Category Performance -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Category Performance</h6>
            </div>
            <div class="card-body">
                @if($categoryPerformance->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No category data available for this period.
                    </div>
                @else
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <canvas id="categoryChart" height="220"></canvas>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-end">Revenue</th>
                                            <th class="text-end">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php 
                                            $totalCatRevenue = $categoryPerformance->sum('total_revenue'); 
                                        @endphp
                                        @foreach($categoryPerformance as $cat)
                                            <tr>
                                                <td>{{ $cat->category_name }}</td>
                                                <td class="text-end fw-semibold">৳{{ number_format($cat->total_revenue, 2) }}</td>
                                                <td class="text-end text-muted">
                                                    {{ $totalCatRevenue > 0 ? round(($cat->total_revenue / $totalCatRevenue) * 100, 1) : 0 }}%
                                                </td>
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

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Low Stock Alert -->
        <div class="card mb-4">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Low Stock Alert
                </h6>
            </div>
            <div class="card-body p-0">
                @if($lowStock->isEmpty())
                    <div class="text-center py-4 text-muted">
                        All products have sufficient stock.
                    </div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($lowStock->take(10) as $product)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="text-decoration-none fw-medium">
                                        {{ Str::limit($product->name, 22) }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $product->category->name ?? 'Uncategorized' }}</small>
                                </div>
                                <span class="badge bg-{{ $product->stock_quantity <= 5 ? 'danger' : 'warning' }} {{ $product->stock_quantity <= 5 ? '' : 'text-dark' }} rounded-pill">
                                    {{ $product->stock_quantity }} left
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    @if($lowStock->count() > 10)
                        <div class="text-center py-2 bg-light">
                            <small class="text-muted">+ {{ $lowStock->count() - 10 }} more items</small>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Products Without Sales -->
        <div class="card">
            <div class="card-header bg-secondary bg-opacity-10">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-graph-down text-secondary me-2"></i>No Sales This Period
                </h6>
            </div>
            <div class="card-body p-0">
                @if($noSales->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-emoji-smile fs-3 d-block mb-2"></i>
                        All products have sales!
                    </div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($noSales as $product)
                            <li class="list-group-item py-2">
                                <a href="{{ route('admin.products.edit', $product) }}" class="text-decoration-none fw-medium">
                                    {{ Str::limit($product->name, 28) }}
                                </a>
                                <br>
                                <small class="text-muted">
                                    {{ $product->category->name ?? 'Uncategorized' }} • 
                                    <span class="text-success">৳{{ number_format($product->regular_price, 2) }}</span>
                                </small>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const categoryData = @json($categoryPerformance);
    
    if (categoryData.length > 0) {
        const colors = [
            '#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545',
            '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'
        ];

        new Chart(document.getElementById('categoryChart'), {
            type: 'pie',
            data: {
                labels: categoryData.map(c => c.category_name),
                datasets: [{
                    data: categoryData.map(c => c.total_revenue),
                    backgroundColor: colors.slice(0, categoryData.length),
                    borderWidth: 0
                }]
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
                }
            }
        });
    }
</script>
@endpush

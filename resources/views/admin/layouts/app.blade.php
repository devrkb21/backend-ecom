<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
        }
        body {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #212529;
            padding-top: 1rem;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #212529;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #495057;
            border-radius: 3px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #6c757d;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.65rem 1.25rem;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.05);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-left-color: #0d6efd;
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 8px;
        }
        .sidebar-brand {
            color: #fff;
            font-size: 1.15rem;
            font-weight: 600;
            padding: 0 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 0.75rem;
        }
        .sidebar-section {
            color: #6c757d;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 1rem 1.25rem 0.5rem;
            text-transform: uppercase;
            border-top: 1px solid rgba(255,255,255,0.05);
            margin-top: 0.5rem;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .top-navbar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .content-wrapper {
            padding: 1.5rem;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            border-radius: 0.5rem;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            padding: 1rem 1.25rem;
        }
        .card-footer {
            background-color: #fff;
            border-top: 1px solid rgba(0,0,0,0.08);
        }
        .table th {
            font-weight: 600;
            border-top: none;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #6c757d;
        }
        .table td {
            vertical-align: middle;
        }
        .badge-status-pending { background-color: #ffc107; color: #000; }
        .badge-status-processing { background-color: #17a2b8; }
        .badge-status-shipped { background-color: #6f42c1; }
        .badge-status-delivered { background-color: #28a745; }
        .badge-status-cancelled { background-color: #dc3545; }
        .badge-status-completed { background-color: #28a745; }
        .badge-status-failed { background-color: #dc3545; }
        .badge-status-refunded { background-color: #6c757d; }
        .stat-card .card-body {
            padding: 1.25rem;
        }
        .stat-card h3, .stat-card h4 {
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-shop"></i> Admin Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                    <i class="bi bi-folder"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    <i class="bi bi-box"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.attributes.*') ? 'active' : '' }}" href="{{ route('admin.attributes.index') }}">
                    <i class="bi bi-diagram-3"></i> Attributes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}" href="{{ route('admin.media.index') }}">
                    <i class="bi bi-images"></i> Media
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                    <i class="bi bi-receipt"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.abandoned-carts.*') ? 'active' : '' }}" href="{{ route('admin.abandoned-carts.index') }}">
                    <i class="bi bi-cart-x"></i> Abandoned Carts
                    @php $pendingAbandoned = \App\Models\AbandonedCart::whereIn('status', ['pending', 'follow_up'])->count(); @endphp
                    @if($pendingAbandoned > 0)
                        <span class="badge bg-danger ms-1">{{ $pendingAbandoned }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}" href="{{ route('admin.returns.index') }}">
                    <i class="bi bi-arrow-return-left"></i> Returns & Refunds
                    @php $pendingReturns = \App\Models\ReturnRequest::where('status', 'pending')->count(); @endphp
                    @if($pendingReturns > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $pendingReturns }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                    <i class="bi bi-ticket-perforated"></i> Coupons
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                    <i class="bi bi-star"></i> Reviews
                    @php $pendingReviews = \App\Models\Review::where('is_approved', false)->count(); @endphp
                    @if($pendingReviews > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $pendingReviews }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                    <i class="bi bi-credit-card"></i> Payments
                </a>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            
            <!-- Business Intelligence Section -->
            <li class="sidebar-section">
                <i class="bi bi-graph-up-arrow me-1"></i> Business Intelligence
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.bi.index') ? 'active' : '' }}" href="{{ route('admin.bi.index') }}">
                    <i class="bi bi-speedometer"></i> BI Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.bi.sales-reports') ? 'active' : '' }}" href="{{ route('admin.bi.sales-reports') }}">
                    <i class="bi bi-currency-dollar"></i> Sales Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.bi.inventory-alerts') ? 'active' : '' }}" href="{{ route('admin.bi.inventory-alerts') }}">
                    <i class="bi bi-exclamation-triangle"></i> Inventory Alerts
                    @php 
                        $lowStockCount = \App\Models\Product::where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10)->count();
                        $outOfStockCount = \App\Models\Product::where('stock_quantity', '<=', 0)->count();
                    @endphp
                    @if($lowStockCount > 0 || $outOfStockCount > 0)
                        <span class="badge bg-{{ $outOfStockCount > 0 ? 'danger' : 'warning' }} ms-1">{{ $lowStockCount + $outOfStockCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.bi.customer-analytics') ? 'active' : '' }}" href="{{ route('admin.bi.customer-analytics') }}">
                    <i class="bi bi-person-badge"></i> Customer Analytics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.bi.product-performance') ? 'active' : '' }}" href="{{ route('admin.bi.product-performance') }}">
                    <i class="bi bi-trophy"></i> Product Performance
                </a>
            </li>
            
            <!-- Marketing Section -->
            <li class="sidebar-section">
                <i class="bi bi-megaphone me-1"></i> Marketing
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.flash-sales.*') ? 'active' : '' }}" href="{{ route('admin.flash-sales.index') }}">
                    <i class="bi bi-lightning-charge"></i> Flash Sales
                    @php $activeFlashSales = \App\Models\FlashSale::where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>', now())->count(); @endphp
                    @if($activeFlashSales > 0)
                        <span class="badge bg-danger ms-1">{{ $activeFlashSales }} Live</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.loyalty.*') ? 'active' : '' }}" href="{{ route('admin.loyalty.index') }}">
                    <i class="bi bi-award"></i> Loyalty Program
                </a>
            </li>
            
            <!-- Analytics Section -->
            <li class="sidebar-section">
                <i class="bi bi-bar-chart-line me-1"></i> Analytics
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.analytics.sales') ? 'active' : '' }}" href="{{ route('admin.analytics.sales') }}">
                    <i class="bi bi-graph-up"></i> Sales Report
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.analytics.products') ? 'active' : '' }}" href="{{ route('admin.analytics.products') }}">
                    <i class="bi bi-box-seam"></i> Products Report
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.analytics.orders') ? 'active' : '' }}" href="{{ route('admin.analytics.orders') }}">
                    <i class="bi bi-clipboard-data"></i> Orders Report
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.analytics.customers') ? 'active' : '' }}" href="{{ route('admin.analytics.customers') }}">
                    <i class="bi bi-person-lines-fill"></i> Customers Report
                </a>
            </li>

            <!-- Settings Section -->
            <li class="sidebar-section">
                <i class="bi bi-gear me-1"></i> Settings
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.settings.site*') ? 'active' : '' }}" href="{{ route('admin.settings.site.index') }}">
                    <i class="bi bi-palette"></i> Site Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.settings.payment-gateways*') ? 'active' : '' }}" href="{{ route('admin.settings.payment-gateways') }}">
                    <i class="bi bi-credit-card-2-front"></i> Payment Gateways
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.settings.shipping-methods*') ? 'active' : '' }}" href="{{ route('admin.settings.shipping-methods') }}">
                    <i class="bi bi-truck"></i> Shipping Methods
                </a>
            </li>
            <li class="nav-item mb-4"></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <h5 class="mb-0">@yield('page-title', 'Dashboard')</h5>
            <div class="d-flex align-items-center">
                <span class="me-3">{{ auth()->user()->name }}</span>
                <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </nav>

        <!-- Content -->
        <div class="content-wrapper">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>

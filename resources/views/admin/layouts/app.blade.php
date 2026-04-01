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
        .sidebar .nav-group-toggle {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: transparent;
            border: 0;
            text-align: left;
            cursor: pointer;
        }
        .sidebar .menu-label {
            display: inline-flex;
            align-items: center;
        }
        .sidebar .menu-chevron {
            width: auto;
            margin-right: 0;
            font-size: 0.75rem;
            transition: transform 0.2s ease;
        }
        .sidebar .menu-group.is-open > .nav-group-toggle .menu-chevron {
            transform: rotate(180deg);
        }
        .sidebar .submenu {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.25s ease;
        }
        .sidebar .menu-group.is-open > .submenu {
            max-height: 1200px;
        }
        .sidebar .submenu .nav-link {
            padding: 0.5rem 1.25rem 0.5rem 2.9rem;
            font-size: 0.85rem;
            border-left-width: 2px;
        }
        .sidebar .submenu .nav-link i {
            width: 16px;
            margin-right: 6px;
            font-size: 0.8rem;
        }
        .sidebar-brand {
            color: #fff;
            font-size: 1.15rem;
            font-weight: 600;
            padding: 0 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 0.75rem;
        }
        .sidebar-controls {
            padding: 0 1rem 0.5rem;
        }
        .sidebar-control-btn {
            border-color: rgba(255, 255, 255, 0.25);
            color: #dee2e6;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .sidebar-control-btn:hover {
            border-color: rgba(255, 255, 255, 0.45);
            color: #fff;
            background: rgba(255,255,255,0.08);
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
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
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
            .sidebar-backdrop.show {
                display: block;
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
        <div class="sidebar-controls">
            <button type="button" class="btn btn-outline-light btn-sm w-100 sidebar-control-btn" id="sidebarBulkToggle" aria-label="Toggle all sidebar groups">
                <i class="bi bi-arrows-expand me-1" data-bulk-icon></i>
                <span data-bulk-label>Expand All</span>
            </button>
        </div>
        <ul class="nav flex-column">
            @php
                $pendingAbandoned = \App\Models\AbandonedCart::whereIn('status', ['pending', 'follow_up'])->count();
                $pendingReturns = \App\Models\ReturnRequest::where('status', 'pending')->count();
                $pendingReviews = \App\Models\Review::where('is_approved', false)->count();
                $newOrdersCount = \App\Models\Order::where('status', 'pending')->count();
                $activeFlashSales = \App\Models\FlashSale::where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>', now())
                    ->count();
                $lowStockCount = \App\Models\Product::where('stock_quantity', '>', 0)
                    ->where('stock_quantity', '<=', 10)
                    ->count();
                $outOfStockCount = \App\Models\Product::where('stock_quantity', '<=', 0)->count();

                $isProductCreatePage = request()->routeIs('admin.products.create');
                $isProductManagePage = request()->routeIs('admin.products.*') && !$isProductCreatePage;

                $isCatalogActive = request()->routeIs('admin.products.*')
                    || request()->routeIs('admin.categories.*')
                    || request()->routeIs('admin.attributes.*')
                    || request()->routeIs('admin.media.*');

                $isOrdersActive = request()->routeIs('admin.orders.*')
                    || request()->routeIs('admin.payments.*')
                    || request()->routeIs('admin.returns.*')
                    || request()->routeIs('admin.abandoned-carts.*');

                $isMarketingActive = request()->routeIs('admin.coupons.*')
                    || request()->routeIs('admin.flash-sales.*')
                    || request()->routeIs('admin.loyalty.*')
                    || request()->routeIs('admin.reviews.*');

                $isSalesManagerActive = request()->routeIs('admin.returns.*')
                    || (request()->routeIs('admin.orders.*') && request('status') === 'pending');

                $isAnalyticsActive = request()->routeIs('admin.bi.*')
                    || request()->routeIs('admin.analytics.*');

                $canManageUsers = auth()->user()->hasAdminPermission('users.manage');
                $canManageRoles = auth()->user()->hasAdminPermission('roles.manage');
                $hasUsersMenuAccess = $canManageUsers || $canManageRoles;

                $isUsersActive = request()->routeIs('admin.users.*')
                    || request()->routeIs('admin.roles.*');

                $isSettingsActive = request()->routeIs('admin.settings.*');
            @endphp

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>

            <li class="nav-item menu-group {{ $isCatalogActive ? 'is-open' : '' }}" data-menu-group="catalog" data-active="{{ $isCatalogActive ? '1' : '0' }}">
                <button type="button" class="nav-link nav-group-toggle {{ $isCatalogActive ? 'active' : '' }}" data-group-toggle aria-expanded="{{ $isCatalogActive ? 'true' : 'false' }}">
                    <span class="menu-label"><i class="bi bi-box-seam"></i> Catalog</span>
                    <i class="bi bi-chevron-down menu-chevron"></i>
                </button>
                <ul class="nav flex-column submenu">
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ $isProductManagePage ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                            <i class="bi bi-box"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ $isProductCreatePage ? 'active' : '' }}" href="{{ route('admin.products.create') }}">
                            <i class="bi bi-plus-square"></i> Add Product
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                            <i class="bi bi-folder"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.attributes.*') ? 'active' : '' }}" href="{{ route('admin.attributes.index') }}">
                            <i class="bi bi-diagram-3"></i> Attributes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}" href="{{ route('admin.media.index') }}">
                            <i class="bi bi-images"></i> Media
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item menu-group {{ $isOrdersActive ? 'is-open' : '' }}" data-menu-group="orders" data-active="{{ $isOrdersActive ? '1' : '0' }}">
                <button type="button" class="nav-link nav-group-toggle {{ $isOrdersActive ? 'active' : '' }}" data-group-toggle aria-expanded="{{ $isOrdersActive ? 'true' : 'false' }}">
                    <span class="menu-label"><i class="bi bi-receipt"></i> Orders</span>
                    <i class="bi bi-chevron-down menu-chevron"></i>
                </button>
                <ul class="nav flex-column submenu">
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                            <i class="bi bi-receipt-cutoff"></i> Order Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                            <i class="bi bi-credit-card"></i> Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}" href="{{ route('admin.returns.index') }}">
                            <i class="bi bi-arrow-return-left"></i> Returns & Refunds
                            @if($pendingReturns > 0)
                                <span class="badge bg-warning text-dark ms-1">{{ $pendingReturns }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.abandoned-carts.*') ? 'active' : '' }}" href="{{ route('admin.abandoned-carts.index') }}">
                            <i class="bi bi-cart-x"></i> Abandoned Carts
                            @if($pendingAbandoned > 0)
                                <span class="badge bg-danger ms-1">{{ $pendingAbandoned }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item menu-group {{ $isMarketingActive ? 'is-open' : '' }}" data-menu-group="marketing" data-active="{{ $isMarketingActive ? '1' : '0' }}">
                <button type="button" class="nav-link nav-group-toggle {{ $isMarketingActive ? 'active' : '' }}" data-group-toggle aria-expanded="{{ $isMarketingActive ? 'true' : 'false' }}">
                    <span class="menu-label"><i class="bi bi-megaphone"></i> Marketing</span>
                    <i class="bi bi-chevron-down menu-chevron"></i>
                </button>
                <ul class="nav flex-column submenu">
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                            <i class="bi bi-ticket-perforated"></i> Coupons
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.flash-sales.*') ? 'active' : '' }}" href="{{ route('admin.flash-sales.index') }}">
                            <i class="bi bi-lightning-charge"></i> Flash Sales
                            @if($activeFlashSales > 0)
                                <span class="badge bg-danger ms-1">{{ $activeFlashSales }} Live</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.loyalty.*') ? 'active' : '' }}" href="{{ route('admin.loyalty.index') }}">
                            <i class="bi bi-award"></i> Loyalty Program
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                            <i class="bi bi-star"></i> Reviews
                            @if($pendingReviews > 0)
                                <span class="badge bg-warning text-dark ms-1">{{ $pendingReviews }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item menu-group {{ $isSalesManagerActive ? 'is-open' : '' }}" data-menu-group="sales-manager" data-active="{{ $isSalesManagerActive ? '1' : '0' }}">
                <button type="button" class="nav-link nav-group-toggle {{ $isSalesManagerActive ? 'active' : '' }}" data-group-toggle aria-expanded="{{ $isSalesManagerActive ? 'true' : 'false' }}">
                    <span class="menu-label"><i class="bi bi-briefcase"></i> Sales Manager</span>
                    <i class="bi bi-chevron-down menu-chevron"></i>
                </button>
                <ul class="nav flex-column submenu">
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.orders.*') && request('status') === 'pending' ? 'active' : '' }}" href="{{ route('admin.orders.index', ['status' => 'pending']) }}">
                            <i class="bi bi-cart-check"></i> New Ordered
                            @if($newOrdersCount > 0)
                                <span class="badge bg-danger ms-1">{{ $newOrdersCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}" href="{{ route('admin.returns.index') }}">
                            <i class="bi bi-arrow-return-left"></i> Return & Refunds
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item menu-group {{ $isAnalyticsActive ? 'is-open' : '' }}" data-menu-group="analytics" data-active="{{ $isAnalyticsActive ? '1' : '0' }}">
                <button type="button" class="nav-link nav-group-toggle {{ $isAnalyticsActive ? 'active' : '' }}" data-group-toggle aria-expanded="{{ $isAnalyticsActive ? 'true' : 'false' }}">
                    <span class="menu-label"><i class="bi bi-bar-chart-line"></i> Analytics</span>
                    <i class="bi bi-chevron-down menu-chevron"></i>
                </button>
                <ul class="nav flex-column submenu">
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.bi.index') ? 'active' : '' }}" href="{{ route('admin.bi.index') }}">
                            <i class="bi bi-speedometer"></i> BI Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.bi.sales-reports') ? 'active' : '' }}" href="{{ route('admin.bi.sales-reports') }}">
                            <i class="bi bi-currency-dollar"></i> Sales Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.bi.inventory-alerts') ? 'active' : '' }}" href="{{ route('admin.bi.inventory-alerts') }}">
                            <i class="bi bi-exclamation-triangle"></i> Inventory Alerts
                            @if($lowStockCount > 0 || $outOfStockCount > 0)
                                <span class="badge bg-{{ $outOfStockCount > 0 ? 'danger' : 'warning' }} ms-1">{{ $lowStockCount + $outOfStockCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.bi.customer-analytics') ? 'active' : '' }}" href="{{ route('admin.bi.customer-analytics') }}">
                            <i class="bi bi-person-badge"></i> Customer Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.bi.product-performance') ? 'active' : '' }}" href="{{ route('admin.bi.product-performance') }}">
                            <i class="bi bi-trophy"></i> Product Performance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.analytics.sales') ? 'active' : '' }}" href="{{ route('admin.analytics.sales') }}">
                            <i class="bi bi-graph-up"></i> Sales Report
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.analytics.products') ? 'active' : '' }}" href="{{ route('admin.analytics.products') }}">
                            <i class="bi bi-box-seam"></i> Products Report
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.analytics.orders') ? 'active' : '' }}" href="{{ route('admin.analytics.orders') }}">
                            <i class="bi bi-clipboard-data"></i> Orders Report
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.analytics.customers') ? 'active' : '' }}" href="{{ route('admin.analytics.customers') }}">
                            <i class="bi bi-person-lines-fill"></i> Customers Report
                        </a>
                    </li>
                </ul>
            </li>

            @if($hasUsersMenuAccess)
                <li class="nav-item menu-group {{ $isUsersActive ? 'is-open' : '' }}" data-menu-group="users" data-active="{{ $isUsersActive ? '1' : '0' }}">
                    <button type="button" class="nav-link nav-group-toggle {{ $isUsersActive ? 'active' : '' }}" data-group-toggle aria-expanded="{{ $isUsersActive ? 'true' : 'false' }}">
                        <span class="menu-label"><i class="bi bi-people"></i> Users</span>
                        <i class="bi bi-chevron-down menu-chevron"></i>
                    </button>
                    <ul class="nav flex-column submenu">
                        @if($canManageUsers)
                            <li class="nav-item">
                                <a class="nav-link submenu-link {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.show') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                    <i class="bi bi-people"></i> User List
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link submenu-link {{ request()->routeIs('admin.users.create') ? 'active' : '' }}" href="{{ route('admin.users.create') }}">
                                    <i class="bi bi-person-plus"></i> Add User
                                </a>
                            </li>
                        @endif
                        @if($canManageRoles)
                            <li class="nav-item">
                                <a class="nav-link submenu-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                                    <i class="bi bi-person-gear"></i> Role
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            <li class="nav-item menu-group {{ $isSettingsActive ? 'is-open' : '' }}" data-menu-group="settings" data-active="{{ $isSettingsActive ? '1' : '0' }}">
                <button type="button" class="nav-link nav-group-toggle {{ $isSettingsActive ? 'active' : '' }}" data-group-toggle aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}">
                    <span class="menu-label"><i class="bi bi-gear"></i> Settings</span>
                    <i class="bi bi-chevron-down menu-chevron"></i>
                </button>
                <ul class="nav flex-column submenu">
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.settings.site*') ? 'active' : '' }}" href="{{ route('admin.settings.site.index') }}">
                            <i class="bi bi-palette"></i> Site Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.settings.integrations*') ? 'active' : '' }}" href="{{ route('admin.settings.integrations') }}">
                            <i class="bi bi-plug"></i> Integrations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.settings.payment-gateways*') ? 'active' : '' }}" href="{{ route('admin.settings.payment-gateways') }}">
                            <i class="bi bi-credit-card-2-front"></i> Payment Gateways
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.settings.shipping-methods*') ? 'active' : '' }}" href="{{ route('admin.settings.shipping-methods') }}">
                            <i class="bi bi-truck"></i> Shipping Methods
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item mb-4"></li>
        </ul>
    </nav>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm d-md-none me-2" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0">@yield('page-title', 'Dashboard')</h5>
            </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarBulkToggle = document.getElementById('sidebarBulkToggle');
            const menuGroups = Array.from(document.querySelectorAll('[data-menu-group]'));
            const storageKey = 'admin.sidebar.menuState.v1';

            const readMenuState = () => {
                try {
                    const rawState = localStorage.getItem(storageKey);
                    return rawState ? JSON.parse(rawState) : {};
                } catch (e) {
                    return {};
                }
            };

            const persistMenuState = (state) => {
                try {
                    localStorage.setItem(storageKey, JSON.stringify(state));
                } catch (e) {
                    // Ignore localStorage failures (private mode or blocked storage)
                }
            };

            const menuState = readMenuState();

            const setGroupOpen = (groupItem, isOpen, shouldPersist = true) => {
                groupItem.classList.toggle('is-open', isOpen);

                const toggleButton = groupItem.querySelector('[data-group-toggle]');
                if (toggleButton) {
                    toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                }

                if (shouldPersist) {
                    menuState[groupItem.dataset.menuGroup] = isOpen ? 1 : 0;
                    persistMenuState(menuState);
                }
            };

            const areAllGroupsOpen = () => {
                return menuGroups.length > 0 && menuGroups.every((groupItem) => groupItem.classList.contains('is-open'));
            };

            const updateBulkToggleButton = () => {
                if (!sidebarBulkToggle) {
                    return;
                }

                const icon = sidebarBulkToggle.querySelector('[data-bulk-icon]');
                const label = sidebarBulkToggle.querySelector('[data-bulk-label]');
                const allOpen = areAllGroupsOpen();

                if (icon) {
                    icon.classList.remove('bi-arrows-expand', 'bi-arrows-collapse');
                    icon.classList.add(allOpen ? 'bi-arrows-collapse' : 'bi-arrows-expand');
                }

                if (label) {
                    label.textContent = allOpen ? 'Collapse All' : 'Expand All';
                }
            };

            menuGroups.forEach((groupItem) => {
                const groupName = groupItem.dataset.menuGroup;
                const isActive = groupItem.dataset.active === '1';
                const hasSavedState = Object.prototype.hasOwnProperty.call(menuState, groupName);
                const savedState = hasSavedState ? menuState[groupName] : null;
                const shouldOpen = isActive || savedState === 1 || savedState === '1';

                setGroupOpen(groupItem, shouldOpen, false);

                const toggleButton = groupItem.querySelector('[data-group-toggle]');
                if (toggleButton) {
                    toggleButton.addEventListener('click', function () {
                        const currentlyOpen = groupItem.classList.contains('is-open');
                        setGroupOpen(groupItem, !currentlyOpen, true);
                        updateBulkToggleButton();
                    });
                }
            });

            updateBulkToggleButton();

            sidebarBulkToggle?.addEventListener('click', function () {
                const shouldExpandAll = !areAllGroupsOpen();

                menuGroups.forEach((groupItem) => {
                    setGroupOpen(groupItem, shouldExpandAll, true);
                });

                updateBulkToggleButton();
            });

            const closeMobileSidebar = () => {
                if (!sidebar || window.innerWidth > 768) {
                    return;
                }
                sidebar.classList.remove('show');
                sidebarBackdrop?.classList.remove('show');
            };

            document.querySelectorAll('.submenu-link').forEach((submenuLink) => {
                submenuLink.addEventListener('click', closeMobileSidebar);
            });

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function () {
                    const shouldShow = !sidebar.classList.contains('show');
                    sidebar.classList.toggle('show', shouldShow);
                    sidebarBackdrop?.classList.toggle('show', shouldShow);
                });
            }

            sidebarBackdrop?.addEventListener('click', function () {
                sidebar?.classList.remove('show');
                sidebarBackdrop.classList.remove('show');
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth > 768) {
                    sidebar?.classList.remove('show');
                    sidebarBackdrop?.classList.remove('show');
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>

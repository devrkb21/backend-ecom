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
            transition: transform 0.25s ease;
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
            transition: margin-left 0.25s ease;
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
        @media (min-width: 769px) {
            body.sidebar-collapsed .sidebar {
                transform: translateX(-100%);
            }
            body.sidebar-collapsed .main-content {
                margin-left: 0;
            }
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
        .content-wrapper .table-responsive,
        .content-wrapper .admin-table-scroll {
            width: 100%;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .content-wrapper .table-responsive > .table,
        .content-wrapper .admin-table-scroll > .table {
            width: max-content;
            min-width: 0;
            table-layout: auto;
        }
        .content-wrapper .admin-table-scroll > .table.admin-table-compact > :not(caption) > * > * {
            padding: 0.45rem 0.35rem;
            white-space: nowrap;
        }
        .content-wrapper .admin-table-scroll > .table.admin-table-compact td > .form-control,
        .content-wrapper .admin-table-scroll > .table.admin-table-compact td > .form-select {
            width: auto;
            min-width: 78px;
            max-width: 160px;
            display: inline-block;
        }
        .content-wrapper .admin-table-scroll > .table.admin-table-compact td > textarea.form-control {
            min-width: 180px;
            max-width: 320px;
            width: 100%;
        }
        .content-wrapper .admin-table-scroll > .table.admin-table-compact td .input-group {
            width: auto;
            min-width: 110px;
            max-width: 170px;
        }
        .content-wrapper .admin-table-scroll > .table.admin-table-compact td .input-group .form-control {
            min-width: 72px;
            max-width: 120px;
        }
        .badge-status-pending { background-color: #ffc107; color: #000; }
        .badge-status-processing { background-color: #17a2b8; }
        .badge-status-shipped { background-color: #6f42c1; }
        .badge-status-delivered { background-color: #28a745; }
        .badge-status-cancelled { background-color: #dc3545; }
        .badge-status-completed { background-color: #28a745; }
        .badge-status-failed { background-color: #dc3545; }
        .badge-status-refunded { background-color: #6c757d; }
        #adminAjaxProgress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            z-index: 1200;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.18s ease;
        }
        #adminAjaxProgress.is-active {
            opacity: 1;
        }
        #adminAjaxProgress .admin-ajax-progress-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #0d6efd, #20c997);
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.35);
            transition: width 0.22s ease-out;
        }
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
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.payments.index') || request()->routeIs('admin.payments.show') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                            <i class="bi bi-credit-card"></i> Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.payments.saved-methods') ? 'active' : '' }}" href="{{ route('admin.payments.saved-methods') }}">
                            <i class="bi bi-wallet2"></i> Saved Payment methods
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
                    <li class="nav-item">
                        <a class="nav-link submenu-link {{ request()->routeIs('admin.settings.order-statuses*') ? 'active' : '' }}" href="{{ route('admin.settings.order-statuses') }}">
                            <i class="bi bi-tags"></i> Order Statuses
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
                <button type="button" class="btn btn-outline-secondary btn-sm d-none d-md-inline-flex me-2" id="sidebarCollapseToggle" aria-label="Collapse sidebar" aria-expanded="true">
                    <i class="bi bi-layout-sidebar me-1" data-sidebar-collapse-icon></i>
                    <span data-sidebar-collapse-label>Collapse</span>
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
            @yield('content')
        </div>
    </div>

    <div id="adminAjaxProgress" aria-hidden="true">
        <div class="admin-ajax-progress-bar"></div>
    </div>

    <div id="adminToastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;"></div>

    <script>
        window.__adminFlashToasts = window.__adminFlashToasts || [];

        @if(session('success'))
            window.__adminFlashToasts.push({
                type: 'success',
                message: @json(session('success')),
            });
        @endif

        @if(session('error'))
            window.__adminFlashToasts.push({
                type: 'danger',
                message: @json(session('error')),
            });
        @endif

        @if(session('info'))
            window.__adminFlashToasts.push({
                type: 'info',
                message: @json(session('info')),
            });
        @endif

        @if($errors->any())
            window.__adminFlashToasts.push({
                type: 'danger',
                message: @json($errors->first()),
            });
        @endif
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            function markCompactAdminTable(table) {
                if (!table) {
                    return;
                }

                const headerColumnCount = table.querySelectorAll('thead th').length;
                const firstRowColumnCount = table.querySelectorAll('tr:first-child > th, tr:first-child > td').length;
                const effectiveColumnCount = headerColumnCount || firstRowColumnCount;
                const hasInlineControls = Boolean(table.querySelector('td .form-control, td .form-select, td .input-group'));

                if (effectiveColumnCount >= 7 || hasInlineControls) {
                    table.classList.add('admin-table-compact');
                    return;
                }

                table.classList.remove('admin-table-compact');
            }

            function ensureAdminTableScroll(root) {
                const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

                scope.querySelectorAll('.content-wrapper .table-responsive').forEach(function (wrapper) {
                    wrapper.classList.add('admin-table-scroll');

                    const table = wrapper.querySelector(':scope > table.table');
                    if (table) {
                        markCompactAdminTable(table);
                    }
                });

                scope.querySelectorAll('.content-wrapper table.table').forEach(function (table) {
                    const parent = table.parentElement;
                    if (!parent) {
                        return;
                    }

                    if (parent.classList.contains('table-responsive')) {
                        parent.classList.add('admin-table-scroll');
                        markCompactAdminTable(table);
                        return;
                    }

                    if (table.closest('[data-no-table-scroll="1"]')) {
                        return;
                    }

                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-responsive admin-table-scroll';
                    parent.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                    markCompactAdminTable(table);
                });
            }

            window.ensureAdminTableScroll = ensureAdminTableScroll;

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    ensureAdminTableScroll(document);
                });
            } else {
                ensureAdminTableScroll(document);
            }

            const iconMap = {
                success: 'bi-check-circle',
                danger: 'bi-exclamation-circle',
                warning: 'bi-exclamation-triangle',
                info: 'bi-info-circle',
                primary: 'bi-bell',
            };

            const bgMap = {
                success: 'text-bg-success',
                danger: 'text-bg-danger',
                warning: 'text-bg-warning',
                info: 'text-bg-info',
                primary: 'text-bg-primary',
            };

            window.showAdminToast = function (message, type = 'info', options = {}) {
                if (!message) {
                    return;
                }

                const container = document.getElementById('adminToastContainer');
                if (!container || typeof bootstrap === 'undefined') {
                    return;
                }

                const resolvedType = bgMap[type] ? type : 'info';
                const iconClass = iconMap[resolvedType] || iconMap.info;
                const colorClass = bgMap[resolvedType] || bgMap.info;
                const delay = Number(options.delay || 4500);

                const toastEl = document.createElement('div');
                toastEl.className = 'toast align-items-center border-0 ' + colorClass;
                toastEl.setAttribute('role', 'status');
                toastEl.setAttribute('aria-live', 'polite');
                toastEl.setAttribute('aria-atomic', 'true');

                toastEl.innerHTML = [
                    '<div class="d-flex">',
                    '  <div class="toast-body d-flex align-items-center gap-2">',
                    '    <i class="bi ' + iconClass + '"></i>',
                    '    <span></span>',
                    '  </div>',
                    '  <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>',
                    '</div>'
                ].join('');

                const textNode = toastEl.querySelector('.toast-body span');
                if (textNode) {
                    textNode.textContent = String(message);
                }

                container.appendChild(toastEl);

                const toast = new bootstrap.Toast(toastEl, {
                    autohide: options.autohide !== false,
                    delay: Number.isNaN(delay) ? 4500 : delay,
                });

                toastEl.addEventListener('hidden.bs.toast', function () {
                    toastEl.remove();
                });

                toast.show();
            };

            document.addEventListener('DOMContentLoaded', function () {
                if (!Array.isArray(window.__adminFlashToasts)) {
                    return;
                }

                window.__adminFlashToasts.forEach(function (item) {
                    if (!item || !item.message) {
                        return;
                    }

                    window.showAdminToast(item.message, item.type || 'info');
                });

                window.__adminFlashToasts = [];
            });
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarCollapseToggle = document.getElementById('sidebarCollapseToggle');
            const sidebarBulkToggle = document.getElementById('sidebarBulkToggle');
            const menuGroups = Array.from(document.querySelectorAll('[data-menu-group]'));
            const storageKey = 'admin.sidebar.menuState.v1';
            const sidebarCollapsedKey = 'admin.sidebar.collapsed.v1';

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

            const readSidebarCollapsedState = () => {
                try {
                    return localStorage.getItem(sidebarCollapsedKey) === '1';
                } catch (e) {
                    return false;
                }
            };

            const persistSidebarCollapsedState = (isCollapsed) => {
                try {
                    localStorage.setItem(sidebarCollapsedKey, isCollapsed ? '1' : '0');
                } catch (e) {
                    // Ignore localStorage failures (private mode or blocked storage)
                }
            };

            const menuState = readMenuState();

            const updateSidebarCollapseToggle = (isCollapsed) => {
                if (!sidebarCollapseToggle) {
                    return;
                }

                const icon = sidebarCollapseToggle.querySelector('[data-sidebar-collapse-icon]');
                const label = sidebarCollapseToggle.querySelector('[data-sidebar-collapse-label]');

                sidebarCollapseToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                sidebarCollapseToggle.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');

                if (icon) {
                    icon.classList.remove('bi-layout-sidebar', 'bi-layout-sidebar-inset');
                    icon.classList.add(isCollapsed ? 'bi-layout-sidebar-inset' : 'bi-layout-sidebar');
                }

                if (label) {
                    label.textContent = isCollapsed ? 'Expand' : 'Collapse';
                }
            };

            const setSidebarCollapsed = (isCollapsed, shouldPersist = true) => {
                if (window.innerWidth <= 768) {
                    updateSidebarCollapseToggle(isCollapsed);
                    return;
                }

                document.body.classList.toggle('sidebar-collapsed', isCollapsed);
                updateSidebarCollapseToggle(isCollapsed);

                if (shouldPersist) {
                    persistSidebarCollapsedState(isCollapsed);
                }

                if (isCollapsed) {
                    sidebar?.classList.remove('show');
                    sidebarBackdrop?.classList.remove('show');
                }
            };

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

            setSidebarCollapsed(readSidebarCollapsedState(), false);

            sidebarCollapseToggle?.addEventListener('click', function () {
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                setSidebarCollapsed(!isCollapsed, true);
            });

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
                    setSidebarCollapsed(readSidebarCollapsedState(), false);
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleNodes = document.querySelectorAll('[data-ui-toggle], [data-toggle-control], [data-integration-toggle]');

            const resolveToggleKey = (toggle) => {
                return toggle.dataset.uiToggle
                    || toggle.dataset.toggleControl
                    || toggle.dataset.integrationToggle
                    || null;
            };

            const findSection = (key) => {
                return document.querySelector(
                    `[data-ui-toggle-form="${key}"], [data-toggle-section="${key}"], [data-integration-form="${key}"]`
                );
            };

            const findDisabledNote = (key) => {
                return document.querySelector(
                    `[data-ui-toggle-note="${key}"], [data-toggle-disabled-note="${key}"], [data-integration-disabled-note="${key}"]`
                );
            };

            const syncToggleSection = (toggle) => {
                const key = resolveToggleKey(toggle);
                if (!key) {
                    return;
                }

                const section = findSection(key);
                const note = findDisabledNote(key);

                if (!section && !note) {
                    return;
                }

                const isEnabled = toggle.checked;

                section?.classList.toggle('d-none', !isEnabled);
                note?.classList.toggle('d-none', isEnabled);

                const controls = section?.querySelectorAll('input, select, textarea, button') ?? [];
                controls.forEach((control) => {
                    if ('disabled' in control) {
                        control.disabled = !isEnabled;
                    }
                });
            };

            toggleNodes.forEach((toggle) => {
                syncToggleSection(toggle);
                toggle.addEventListener('change', () => syncToggleSection(toggle));
            });
        });
    </script>
    <script>
        (function () {
            const excludedFormIds = new Set([
                'productEditForm',
                'autoGenerateVariantForm',
                'addVariantForm',
                'editVariantForm',
                'generateVariantsForm',
                'bulkEditModalForm',
                'variantMatrixForm',
                'bulkDeleteVariantsForm',
                'deleteVariantForm',
            ]);

            const ajaxProgressRoot = document.getElementById('adminAjaxProgress');
            const ajaxProgressBar = ajaxProgressRoot
                ? ajaxProgressRoot.querySelector('.admin-ajax-progress-bar')
                : null;
            let ajaxInFlightCount = 0;
            let ajaxProgressTick = null;
            let ajaxProgressHideTimer = null;
            let ajaxProgressValue = 0;

            function startAjaxProgress() {
                ajaxInFlightCount += 1;

                if (!ajaxProgressRoot || !ajaxProgressBar || ajaxInFlightCount > 1) {
                    return;
                }

                if (ajaxProgressHideTimer) {
                    clearTimeout(ajaxProgressHideTimer);
                    ajaxProgressHideTimer = null;
                }

                if (ajaxProgressTick) {
                    clearInterval(ajaxProgressTick);
                    ajaxProgressTick = null;
                }

                ajaxProgressValue = 12;
                ajaxProgressRoot.classList.add('is-active');
                ajaxProgressBar.style.width = ajaxProgressValue + '%';

                ajaxProgressTick = window.setInterval(function () {
                    if (ajaxProgressValue >= 90) {
                        return;
                    }

                    const remaining = 90 - ajaxProgressValue;
                    ajaxProgressValue = Math.min(90, ajaxProgressValue + Math.max(1.5, remaining * 0.14));
                    ajaxProgressBar.style.width = ajaxProgressValue.toFixed(2) + '%';
                }, 180);
            }

            function finishAjaxProgress() {
                if (ajaxInFlightCount > 0) {
                    ajaxInFlightCount -= 1;
                } else {
                    ajaxInFlightCount = 0;
                }

                if (!ajaxProgressRoot || !ajaxProgressBar || ajaxInFlightCount > 0) {
                    return;
                }

                if (ajaxProgressTick) {
                    clearInterval(ajaxProgressTick);
                    ajaxProgressTick = null;
                }

                ajaxProgressValue = 100;
                ajaxProgressBar.style.width = '100%';

                ajaxProgressHideTimer = window.setTimeout(function () {
                    ajaxProgressRoot.classList.remove('is-active');
                    ajaxProgressBar.style.width = '0%';
                    ajaxProgressValue = 0;
                }, 180);
            }

            function showToast(message, type) {
                if (!message) {
                    return;
                }

                if (typeof window.showAdminToast === 'function') {
                    window.showAdminToast(message, type || 'info');
                    return;
                }

                window.alert(message);
            }

            function firstErrorMessage(errors, fallback) {
                if (!errors || typeof errors !== 'object') {
                    return fallback || 'Validation failed.';
                }

                const firstKey = Object.keys(errors)[0];
                if (!firstKey) {
                    return fallback || 'Validation failed.';
                }

                const value = errors[firstKey];
                if (Array.isArray(value) && value.length > 0) {
                    return String(value[0]);
                }

                if (typeof value === 'string' && value.trim() !== '') {
                    return value;
                }

                return fallback || 'Validation failed.';
            }

            function clearAjaxValidationErrors(form) {
                if (!form) {
                    return;
                }

                form.querySelectorAll('.ajax-invalid-feedback').forEach(function (node) {
                    node.remove();
                });

                Array.from(form.elements || []).forEach(function (element) {
                    if (!element || !('classList' in element)) {
                        return;
                    }

                    element.classList.remove('is-invalid');
                    element.removeAttribute('aria-invalid');
                });
            }

            function dotToBracket(path) {
                if (!path || path.indexOf('.') === -1) {
                    return path;
                }

                const segments = path.split('.');
                let output = segments[0];

                for (let i = 1; i < segments.length; i += 1) {
                    output += '[' + segments[i] + ']';
                }

                return output;
            }

            function findFormControlsByKey(form, key) {
                if (!form || !key) {
                    return [];
                }

                const normalized = dotToBracket(key);
                const candidates = [key, normalized];

                return Array.from(form.elements || []).filter(function (element) {
                    return element && typeof element.name === 'string' && candidates.includes(element.name);
                });
            }

            function insertFieldErrorMessage(targetControl, message) {
                if (!targetControl || !message) {
                    return;
                }

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block ajax-invalid-feedback';
                feedback.textContent = String(message);

                const inputGroup = targetControl.closest('.input-group');
                if (inputGroup && inputGroup.parentElement) {
                    inputGroup.insertAdjacentElement('afterend', feedback);
                    return;
                }

                targetControl.insertAdjacentElement('afterend', feedback);
            }

            function renderAjaxValidationErrors(form, errors) {
                if (!form || !errors || typeof errors !== 'object') {
                    return;
                }

                clearAjaxValidationErrors(form);

                Object.keys(errors).forEach(function (key) {
                    const controls = findFormControlsByKey(form, key);
                    if (controls.length === 0) {
                        return;
                    }

                    const rawMessages = errors[key];
                    const message = Array.isArray(rawMessages)
                        ? (rawMessages[0] || '')
                        : (rawMessages || '');

                    controls.forEach(function (control) {
                        if (!control || !('classList' in control)) {
                            return;
                        }

                        control.classList.add('is-invalid');
                        control.setAttribute('aria-invalid', 'true');
                    });

                    insertFieldErrorMessage(controls[controls.length - 1], message);
                });
            }

            window.clearAjaxValidationErrors = clearAjaxValidationErrors;
            window.renderAjaxValidationErrors = renderAjaxValidationErrors;

            function resolveLoadingLabel(label) {
                const normalized = String(label || '').replace(/\s+/g, ' ').trim();
                if (!normalized) {
                    return 'Processing...';
                }

                if (normalized.endsWith('...')) {
                    return normalized;
                }

                return normalized + '...';
            }

            function setSubmittingState(form, isSubmitting, submitter) {
                const submitControls = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
                const explicitSubmitter = submitter && submitControls.includes(submitter)
                    ? submitter
                    : null;
                const activeSubmitter = explicitSubmitter || submitControls.find(function (control) {
                    return !control.disabled;
                }) || submitControls[0] || null;

                submitControls.forEach(function (control) {
                    if (isSubmitting) {
                        control.dataset.ajaxWasDisabled = control.disabled ? '1' : '0';

                        if (activeSubmitter && control === activeSubmitter) {
                            if (control.tagName === 'BUTTON') {
                                if (typeof control.dataset.ajaxOriginalHtml === 'undefined') {
                                    control.dataset.ajaxOriginalHtml = control.innerHTML;
                                }

                                const loadingLabel = control.getAttribute('data-loading-text')
                                    || resolveLoadingLabel(control.textContent);
                                control.textContent = loadingLabel;
                            } else if (control.tagName === 'INPUT') {
                                if (typeof control.dataset.ajaxOriginalValue === 'undefined') {
                                    control.dataset.ajaxOriginalValue = control.value;
                                }

                                const loadingLabel = control.getAttribute('data-loading-text')
                                    || resolveLoadingLabel(control.value);
                                control.value = loadingLabel;
                            }
                        }

                        control.disabled = true;
                        return;
                    }

                    if (typeof control.dataset.ajaxOriginalHtml !== 'undefined') {
                        control.innerHTML = control.dataset.ajaxOriginalHtml;
                        delete control.dataset.ajaxOriginalHtml;
                    }

                    if (typeof control.dataset.ajaxOriginalValue !== 'undefined') {
                        control.value = control.dataset.ajaxOriginalValue;
                        delete control.dataset.ajaxOriginalValue;
                    }

                    if (control.dataset.ajaxWasDisabled !== '1') {
                        control.disabled = false;
                    }

                    delete control.dataset.ajaxWasDisabled;
                });
            }

            async function parseResponse(response) {
                const contentDisposition = (response.headers.get('content-disposition') || '').toLowerCase();
                const contentType = (response.headers.get('content-type') || '').toLowerCase();

                if (contentDisposition.includes('attachment')) {
                    return {
                        kind: 'attachment',
                    };
                }

                if (contentType.includes('application/json')) {
                    try {
                        const payload = await response.json();
                        return {
                            kind: 'json',
                            payload: payload || {},
                        };
                    } catch (error) {
                        return {
                            kind: 'json',
                            payload: {},
                        };
                    }
                }

                const html = await response.text();
                return {
                    kind: 'html',
                    html: html || '',
                };
            }

            function replaceDocument(html, nextUrl, historyMode) {
                if (!html) {
                    return;
                }

                if (nextUrl) {
                    const currentUrl = window.location.href;
                    if (historyMode === 'replace') {
                        window.history.replaceState({}, '', nextUrl);
                    } else if (currentUrl !== nextUrl) {
                        window.history.pushState({}, '', nextUrl);
                    }
                }

                document.open();
                document.write(html);
                document.close();
            }

            function shouldSkipLink(anchor, event) {
                if (!anchor) {
                    return true;
                }

                if (event.defaultPrevented || event.button !== 0) {
                    return true;
                }

                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return true;
                }

                if (anchor.dataset.noAdminAjax === '1' || anchor.dataset.noAjax === '1') {
                    return true;
                }

                if (anchor.hasAttribute('download') || anchor.getAttribute('target') === '_blank') {
                    return true;
                }

                if (anchor.hasAttribute('data-bs-toggle') || anchor.hasAttribute('data-bs-target')) {
                    return true;
                }

                const rawHref = anchor.getAttribute('href') || '';
                if (rawHref === '' || rawHref === '#' || rawHref.startsWith('javascript:') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) {
                    return true;
                }

                let url;
                try {
                    url = new URL(anchor.href, window.location.origin);
                } catch (error) {
                    return true;
                }

                if (url.origin !== window.location.origin) {
                    return true;
                }

                return false;
            }

            async function visitUrl(url, historyMode) {
                startAjaxProgress();

                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'text/html,application/xhtml+xml',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    const parsed = await parseResponse(response);

                    if (parsed.kind === 'attachment') {
                        window.location.href = response.url || url;
                        return;
                    }

                    if (parsed.kind === 'html') {
                        replaceDocument(parsed.html, response.url || url, historyMode || 'push');
                        return;
                    }

                    const payload = parsed.payload || {};
                    if (!response.ok || payload.success === false) {
                        const message = payload.message || firstErrorMessage(payload.errors, 'Unable to load page.');
                        showToast(message, 'danger');
                        return;
                    }

                    if (payload.redirect_url || payload.redirect_to || payload.url) {
                        await visitUrl(payload.redirect_url || payload.redirect_to || payload.url, historyMode || 'push');
                        return;
                    }

                    if (payload.message) {
                        showToast(payload.message, 'info');
                    }
                } finally {
                    finishAjaxProgress();
                }
            }

            async function handleGlobalFormSubmit(event) {
                if (event.defaultPrevented) {
                    return;
                }

                const form = event.target;
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                if (form.dataset.noAdminAjax === '1' || form.dataset.noAjax === '1' || form.dataset.ajax === 'false') {
                    return;
                }

                if (form.closest('[data-no-admin-ajax-scope="1"]')) {
                    return;
                }

                if (excludedFormIds.has(form.id)) {
                    return;
                }

                if ((form.getAttribute('target') || '').toLowerCase() === '_blank') {
                    return;
                }

                const method = (form.getAttribute('method') || 'GET').toUpperCase();
                const action = form.getAttribute('action') || window.location.href;
                const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;

                event.preventDefault();
                clearAjaxValidationErrors(form);
                setSubmittingState(form, true, submitter);

                let startedProgress = false;

                try {
                    if (method === 'GET') {
                        const query = new URLSearchParams(new FormData(form)).toString();
                        const targetUrl = query ? (action + (action.includes('?') ? '&' : '?') + query) : action;
                        await visitUrl(targetUrl, 'push');
                        return;
                    }

                    const formData = new FormData(form);
                    startAjaxProgress();
                    startedProgress = true;

                    const response = await fetch(action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json, text/html;q=0.9',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: formData,
                    });

                    const parsed = await parseResponse(response);

                    if (parsed.kind === 'html') {
                        const mode = response.url && response.url !== window.location.href ? 'push' : 'replace';
                        replaceDocument(parsed.html, response.url || action, mode);
                        return;
                    }

                    const payload = parsed.payload || {};

                    if (response.status === 422 || payload.errors) {
                        renderAjaxValidationErrors(form, payload.errors || {});
                        showToast(payload.message || firstErrorMessage(payload.errors, 'Please fix the highlighted fields.'), 'danger');
                        return;
                    }

                    if (!response.ok || payload.success === false) {
                        showToast(payload.message || firstErrorMessage(payload.errors, 'Request failed.'), 'danger');
                        return;
                    }

                    const redirectUrl = payload.redirect_url || payload.redirect_to || payload.url || null;
                    if (redirectUrl) {
                        await visitUrl(redirectUrl, 'push');
                        return;
                    }

                    if (payload.message) {
                        showToast(payload.message, 'success');
                    }

                    document.dispatchEvent(new CustomEvent('admin:ajax-success', {
                        detail: {
                            formId: form.id || null,
                            action: action,
                            payload: payload,
                        },
                    }));
                } catch (error) {
                    showToast('Network error. Please try again.', 'danger');
                } finally {
                    if (startedProgress) {
                        finishAjaxProgress();
                    }

                    setSubmittingState(form, false, submitter);
                }
            }

            function bindGlobalAjax() {
                if (window.__adminGlobalAjaxBound) {
                    return;
                }

                window.__adminGlobalAjaxBound = true;

                document.addEventListener('submit', handleGlobalFormSubmit);

                document.addEventListener('click', function (event) {
                    const anchor = event.target.closest('a[href]');
                    if (shouldSkipLink(anchor, event)) {
                        return;
                    }

                    event.preventDefault();

                    visitUrl(anchor.href, 'push').catch(function () {
                        showToast('Unable to open that page right now.', 'danger');
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindGlobalAjax);
            } else {
                bindGlobalAjax();
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $range = $request->get('range', 'today');
        $startDate = now()->startOfDay();
        $endDate = now()->endOfDay();

        if ($range === 'yesterday') {
            $startDate = now()->subDay()->startOfDay();
            $endDate = now()->subDay()->endOfDay();
        } elseif ($range === 'last_30_days') {
            $startDate = now()->subDays(30)->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($range === 'custom' && $request->has('start_date') && $request->has('end_date')) {
            try {
                $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->get('end_date'))->endOfDay();
            } catch (\Exception $e) {
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
            }
        }

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Overview stats based on selected range
        $rangeProfit = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('orders.status', 'delivered')
            ->whereNull('orders.deleted_at')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(DB::raw('SUM(order_items.total - (order_items.quantity * COALESCE(product_variants.purchase_price, products.buy_price, 0))) as profit'))
            ->value('profit') ?? 0;

        $overviewStats = [
            'orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_sale' => Order::where('status', 'delivered')->whereBetween('created_at', [$startDate, $endDate])->sum('total'),
            'total_profit' => $rangeProfit,
            'new_customers' => User::where('role', 'customer')->whereBetween('created_at', [$startDate, $endDate])->count(),
        ];

        // This month stats
        $thisMonthStats = [
            'orders' => Order::where('created_at', '>=', $thisMonth)->count(),
            'revenue' => Order::where('status', 'delivered')->where('created_at', '>=', $thisMonth)->sum('total'),
        ];

        // Last month stats for comparison
        $lastMonthStats = [
            'revenue' => Order::where('status', 'delivered')
                ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
                ->sum('total'),
        ];

        // Revenue change percentage
        $revenueChange = $lastMonthStats['revenue'] > 0 
            ? (($thisMonthStats['revenue'] - $lastMonthStats['revenue']) / $lastMonthStats['revenue']) * 100 
            : 0;

        // Lifetime stats
        $totalProfit = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('orders.status', 'delivered')
            ->whereNull('orders.deleted_at')
            ->select(DB::raw('SUM(order_items.total - (order_items.quantity * COALESCE(product_variants.purchase_price, products.buy_price, 0))) as profit'))
            ->value('profit') ?? 0;

        $stats = [
            'total_users' => User::where('role', 'customer')->count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_sale' => Order::where('status', 'delivered')->sum('total'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'total_profit' => $totalProfit,
        ];

        // Sales data for last 7 days chart
        $salesChart = Order::where('status', 'delivered')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing days
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayData = $salesChart->firstWhere('date', $date);
            $chartData[] = [
                'label' => now()->subDays($i)->format('D'),
                'date' => $date,
                'revenue' => $dayData->revenue ?? 0,
                'orders' => $dayData->orders ?? 0,
            ];
        }

        // Recent orders
        $recentOrders = Order::with('user')
            ->latest()
            ->limit(5)
            ->get();

        // Top products this month
        $topProducts = OrderItem::whereHas('order', function ($q) use ($thisMonth) {
                $q->where('status', 'delivered')
                  ->where('created_at', '>=', $thisMonth);
            })
            ->select(
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue')
            )
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // Low stock products count
        $lowStockCount = Product::where('is_active', true)
            ->where('stock_quantity', '<=', 10)
            ->count();

        // Abandoned cart summary
        $abandonedSummary = AbandonedCart::getSummary();

        return view('admin.dashboard', compact(
            'stats',
            'overviewStats',
            'thisMonthStats',
            'revenueChange',
            'chartData',
            'recentOrders',
            'topProducts',
            'lowStockCount',
            'abandonedSummary',
            'range',
            'startDate',
            'endDate'
        ));
    }
}

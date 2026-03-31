<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Basic stats
        $stats = [
            'total_users' => User::where('role', 'customer')->count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total'),
        ];

        // Today's stats
        $todayStats = [
            'orders' => Order::whereDate('created_at', $today)->count(),
            'revenue' => Order::where('status', 'delivered')->whereDate('created_at', $today)->sum('total'),
            'new_customers' => User::where('role', 'customer')->whereDate('created_at', $today)->count(),
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

        return view('admin.dashboard', compact(
            'stats',
            'todayStats',
            'thisMonthStats',
            'revenueChange',
            'chartData',
            'recentOrders',
            'topProducts',
            'lowStockCount'
        ));
    }
}

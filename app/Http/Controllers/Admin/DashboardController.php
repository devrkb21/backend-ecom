<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
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

        switch ($range) {
            case 'yesterday':
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
                break;
            case 'this_week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                break;
            case 'this_month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
            case 'last_year':
                $startDate = now()->subYear()->startOfYear();
                $endDate = now()->subYear()->endOfYear();
                break;
            case 'custom':
                if ($request->has('start_date') && $request->has('end_date')) {
                    try {
                        $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
                        $endDate = Carbon::parse($request->get('end_date'))->endOfDay();
                    } catch (\Exception $e) {
                        $startDate = now()->startOfDay();
                        $endDate = now()->endOfDay();
                    }
                }
                break;
            default: // 'today'
                break;
        }

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $invalidStatuses = ['cancelled', 'failed', 'returned'];

        // Overview stats based on selected range (Today Sale + order count)
        $overviewStats = [
            'orders' => Order::whereBetween('created_at', [$startDate, $endDate])->whereNotIn('status', $invalidStatuses)->count(),
            'total_sale' => Order::whereNotIn('status', $invalidStatuses)->whereBetween('created_at', [$startDate, $endDate])->sum('total'),
        ];

        // Courier stats — filtered by selected date range
        $courierStats = [
            'shipped_total' => Order::where('status', 'shipped')->whereBetween('created_at', [$startDate, $endDate])->sum('total'),
            'shipped_count' => Order::where('status', 'shipped')->whereBetween('created_at', [$startDate, $endDate])->count(),
            'delivered_total' => Order::where('status', 'delivered')->whereBetween('created_at', [$startDate, $endDate])->sum('total'),
            'delivered_count' => Order::where('status', 'delivered')->whereBetween('created_at', [$startDate, $endDate])->count(),
            'cancelled_total' => Order::where('status', 'cancelled')->whereBetween('created_at', [$startDate, $endDate])->sum('total'),
            'cancelled_count' => Order::where('status', 'cancelled')->whereBetween('created_at', [$startDate, $endDate])->count(),
        ];

        // Pending action stats (still needed for sidebar)
        $stats = [
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
        ];

        // Product Inventory Alert (with variant-level stock)
        // Products with variants: check if ALL variants have 0 stock
        $outOfStockWithVariants = Product::where('is_active', true)
            ->whereHas('variants')
            ->whereDoesntHave('variants', function ($q) {
                $q->where('is_active', true)->where('stock_quantity', '>', 0);
            })
            ->count();

        // Products without variants: stock_quantity <= 0
        $outOfStockSimple = Product::where('is_active', true)
            ->whereDoesntHave('variants')
            ->where('stock_quantity', '<=', 0)
            ->count();

        // Low stock: products without variants with stock 1-10
        $lowStockSimple = Product::where('is_active', true)
            ->whereDoesntHave('variants')
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', 10)
            ->count();

        // Low stock: variant products where total active variant stock is 1-10
        $lowStockWithVariants = Product::where('is_active', true)
            ->whereHas('variants', function ($q) {
                $q->where('is_active', true)->where('stock_quantity', '>', 0);
            })
            ->get()
            ->filter(function ($product) {
                $totalStock = $product->variants->where('is_active', true)->sum('stock_quantity');
                return $totalStock > 0 && $totalStock <= 10;
            })
            ->count();

        // Low stock variants (individual variants with stock 1-10)
        $lowStockVariants = ProductVariant::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', 10)
            ->count();

        $inventoryAlert = [
            'out_of_stock' => $outOfStockWithVariants + $outOfStockSimple,
            'low_stock' => $lowStockSimple + $lowStockWithVariants,
            'low_stock_variants' => $lowStockVariants,
            'total_active' => Product::where('is_active', true)->count(),
            'total_inactive' => Product::where('is_active', false)->count(),
        ];

        // This month stats
        $thisMonthStats = [
            'orders' => Order::where('created_at', '>=', $thisMonth)->whereNotIn('status', $invalidStatuses)->count(),
            'revenue' => Order::whereNotIn('status', $invalidStatuses)->where('created_at', '>=', $thisMonth)->sum('total'),
        ];

        // Last month stats for comparison
        $lastMonthStats = [
            'revenue' => Order::whereNotIn('status', $invalidStatuses)
                ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
                ->sum('total'),
        ];

        // Revenue change percentage
        $revenueChange = $lastMonthStats['revenue'] > 0 
            ? (($thisMonthStats['revenue'] - $lastMonthStats['revenue']) / $lastMonthStats['revenue']) * 100 
            : 0;

        // Sales data for last 30 days chart
        $salesChart = Order::where('status', 'delivered')
            ->where('created_at', '>=', now()->subDays(30))
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
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayData = $salesChart->firstWhere('date', $date);
            $chartData[] = [
                'label' => now()->subDays($i)->format('d M'),
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

        // Abandoned cart summary
        $abandonedSummary = AbandonedCart::getSummary();

        return view('admin.dashboard', compact(
            'stats',
            'overviewStats',
            'courierStats',
            'inventoryAlert',
            'thisMonthStats',
            'revenueChange',
            'chartData',
            'recentOrders',
            'topProducts',
            'abandonedSummary',
            'range',
            'startDate',
            'endDate'
        ));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Sales Overview Report
     */
    public function sales(Request $request)
    {
        $period = $request->get('period', '30'); // Default: last 30 days
        $startDate = $this->getStartDate($period);
        $endDate = now();

        // Sales by date for chart
        $salesByDate = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing dates with zero values
        $salesData = $this->fillMissingDates($salesByDate, $startDate, $endDate);

        // Summary stats
        $stats = [
            'total_revenue' => Order::where('status', 'delivered')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total'),
            'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed_orders' => Order::where('status', 'delivered')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'average_order_value' => Order::where('status', 'delivered')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->avg('total') ?? 0,
        ];

        // Compare with previous period
        $previousStart = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $previousEnd = $startDate->copy()->subDay();

        $previousRevenue = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total');

        $previousOrders = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $stats['revenue_change'] = $previousRevenue > 0 
            ? (($stats['total_revenue'] - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        $stats['orders_change'] = $previousOrders > 0 
            ? (($stats['completed_orders'] - $previousOrders) / $previousOrders) * 100 
            : 0;

        // Sales by payment status
        $paymentStats = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('admin.analytics.sales', compact('salesData', 'stats', 'paymentStats', 'period'));
    }

    /**
     * Products Report
     */
    public function products(Request $request)
    {
        $period = $request->get('period', '30');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        // Best selling products
        $bestSellers = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'delivered')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->select(
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_id) as order_count')
            )
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Low stock products
        $lowStock = Product::where('is_active', true)
            ->where(function ($query) {
                $query->where('stock_quantity', '<=', 10)
                      ->orWhereHas('variants', function ($q) {
                          $q->where('stock_quantity', '<=', 10);
                      });
            })
            ->with(['category', 'variants'])
            ->limit(20)
            ->get();

        // Category performance
        $categoryPerformance = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'delivered')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as order_count')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        // Products without sales
        $noSales = Product::where('is_active', true)
            ->whereDoesntHave('orderItems', function ($q) use ($startDate, $endDate) {
                $q->whereHas('order', function ($oq) use ($startDate, $endDate) {
                    $oq->whereBetween('created_at', [$startDate, $endDate]);
                });
            })
            ->with('category')
            ->limit(10)
            ->get();

        return view('admin.analytics.products', compact(
            'bestSellers', 
            'lowStock', 
            'categoryPerformance', 
            'noSales',
            'period'
        ));
    }

    /**
     * Customers Report
     */
    public function customers(Request $request)
    {
        $period = $request->get('period', '30');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        // New customers
        $newCustomers = User::whereBetween('created_at', [$startDate, $endDate])
            ->where('role', 'customer')
            ->count();

        $previousStart = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $previousNewCustomers = User::whereBetween('created_at', [$previousStart, $startDate])
            ->where('role', 'customer')
            ->count();

        $customerGrowth = $previousNewCustomers > 0 
            ? (($newCustomers - $previousNewCustomers) / $previousNewCustomers) * 100 
            : 0;

        // Top customers by revenue
        $topCustomers = User::where('role', 'customer')
            ->withCount(['orders as completed_orders' => function ($q) {
                $q->where('status', 'delivered');
            }])
            ->withSum(['orders as total_spent' => function ($q) {
                $q->where('status', 'delivered');
            }], 'total')
            ->having('total_spent', '>', 0)
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        // Customer registration over time
        $registrations = User::where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $registrationData = $this->fillMissingDates($registrations, $startDate, $endDate, 'count');

        // Customer stats
        $stats = [
            'total_customers' => User::where('role', 'customer')->count(),
            'new_customers' => $newCustomers,
            'customer_growth' => $customerGrowth,
            'customers_with_orders' => User::where('role', 'customer')
                ->has('orders')
                ->count(),
            'repeat_customers' => User::where('role', 'customer')
                ->has('orders', '>=', 2)
                ->count(),
        ];

        return view('admin.analytics.customers', compact(
            'topCustomers',
            'registrationData',
            'stats',
            'period'
        ));
    }

    /**
     * Orders Report
     */
    public function orders(Request $request)
    {
        $period = $request->get('period', '30');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        // Orders by status
        $ordersByStatus = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Orders over time
        $ordersOverTime = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $ordersData = $this->fillMissingDates($ordersOverTime, $startDate, $endDate, 'total');

        // Average processing time (from pending to delivered)
        $avgProcessingTime = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('updated_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->value('avg_hours') ?? 0;

        // Order stats
        $stats = [
            'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
            'pending' => $ordersByStatus['pending'] ?? 0,
            'processing' => $ordersByStatus['processing'] ?? 0,
            'shipped' => $ordersByStatus['shipped'] ?? 0,
            'delivered' => $ordersByStatus['delivered'] ?? 0,
            'cancelled' => $ordersByStatus['cancelled'] ?? 0,
            'avg_processing_hours' => round($avgProcessingTime, 1),
            'fulfillment_rate' => Order::whereBetween('created_at', [$startDate, $endDate])->count() > 0
                ? round((($ordersByStatus['delivered'] ?? 0) / Order::whereBetween('created_at', [$startDate, $endDate])->count()) * 100, 1)
                : 0,
        ];

        // Recent orders
        $recentOrders = Order::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.analytics.orders', compact(
            'ordersByStatus',
            'ordersData',
            'stats',
            'recentOrders',
            'period'
        ));
    }

    /**
     * Export report data as CSV
     */
    public function export(Request $request, string $type)
    {
        $period = $request->get('period', '30');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $filename = "{$type}_report_" . now()->format('Y-m-d') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        switch ($type) {
            case 'sales':
                $data = $this->exportSalesData($startDate, $endDate);
                break;
            case 'products':
                $data = $this->exportProductsData($startDate, $endDate);
                break;
            case 'customers':
                $data = $this->exportCustomersData($startDate, $endDate);
                break;
            case 'orders':
                $data = $this->exportOrdersData($startDate, $endDate);
                break;
            default:
                abort(404);
        }

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Helper: Get start date based on period
     */
    private function getStartDate(string $period): Carbon
    {
        return match ($period) {
            '7' => now()->subDays(7)->startOfDay(),
            '30' => now()->subDays(30)->startOfDay(),
            '90' => now()->subDays(90)->startOfDay(),
            '365' => now()->subYear()->startOfDay(),
            'all' => Carbon::parse('2000-01-01'),
            default => now()->subDays(30)->startOfDay(),
        };
    }

    /**
     * Helper: Fill missing dates in a dataset
     */
    private function fillMissingDates($data, Carbon $startDate, Carbon $endDate, string $valueKey = 'revenue'): array
    {
        $result = [];
        $dataByDate = $data->keyBy('date');

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $item = $dataByDate->get($dateStr);

            $result[] = [
                'date' => $dateStr,
                'label' => $current->format('M d'),
                'revenue' => $item->revenue ?? 0,
                'orders' => $item->orders ?? 0,
                'count' => $item->count ?? 0,
                'total' => $item->total ?? 0,
                'delivered' => $item->delivered ?? 0,
                'cancelled' => $item->cancelled ?? 0,
            ];

            $current->addDay();
        }

        return $result;
    }

    /**
     * Export helpers
     */
    private function exportSalesData(Carbon $startDate, Carbon $endDate): array
    {
        $data = [['Date', 'Orders', 'Revenue']];

        $sales = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($sales as $row) {
            $data[] = [$row->date, $row->orders, number_format((float) $row->revenue, 2)];
        }

        return $data;
    }

    private function exportProductsData(Carbon $startDate, Carbon $endDate): array
    {
        $data = [['Product', 'SKU', 'Quantity Sold', 'Revenue', 'Orders']];

        $products = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'delivered')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->select(
                'product_name',
                'product_sku',
                DB::raw('SUM(quantity) as quantity'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(DISTINCT order_id) as orders')
            )
            ->groupBy('product_name', 'product_sku')
            ->orderByDesc('revenue')
            ->get();

        foreach ($products as $row) {
            $data[] = [
                $row->product_name,
                $row->product_sku,
                $row->quantity,
                number_format((float) $row->revenue, 2),
                $row->orders
            ];
        }

        return $data;
    }

    private function exportCustomersData(Carbon $startDate, Carbon $endDate): array
    {
        $data = [['Customer', 'Email', 'Joined', 'Orders', 'Total Spent']];

        $customers = User::where('role', 'customer')
            ->withCount(['orders as order_count' => function ($q) {
                $q->where('status', 'delivered');
            }])
            ->withSum(['orders as total_spent' => function ($q) {
                $q->where('status', 'delivered');
            }], 'total')
            ->orderByDesc('total_spent')
            ->get();

        foreach ($customers as $customer) {
            $data[] = [
                $customer->name,
                $customer->email,
                $customer->created_at->format('Y-m-d'),
                $customer->order_count ?? 0,
                number_format((float) ($customer->total_spent ?? 0), 2)
            ];
        }

        return $data;
    }

    private function exportOrdersData(Carbon $startDate, Carbon $endDate): array
    {
        $data = [['Order #', 'Customer', 'Status', 'Total', 'Date']];

        $orders = Order::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get();

        foreach ($orders as $order) {
            $data[] = [
                $order->order_number,
                $order->user->name ?? 'Guest',
                ucfirst($order->status),
                number_format((float) $order->total, 2),
                $order->created_at->format('Y-m-d H:i')
            ];
        }

        return $data;
    }
}

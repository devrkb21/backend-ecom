<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class BusinessIntelligenceService
{
    // ==================== SALES REPORTS ====================

    /**
     * Get sales overview for a period
     */
    public function getSalesOverview(string $period = 'month', ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $dates = $this->getDateRange($period, $startDate, $endDate);
        $start = $dates['start'];
        $end = $dates['end'];
        $previousStart = $dates['previous_start'];
        $previousEnd = $dates['previous_end'];

        // Current period metrics
        $currentOrders = Order::whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'failed']);

        $currentRevenue = (clone $currentOrders)->sum('total');
        $currentOrderCount = (clone $currentOrders)->count();
        $currentAvgOrderValue = $currentOrderCount > 0 ? $currentRevenue / $currentOrderCount : 0;

        // Previous period metrics for comparison
        $previousOrders = Order::whereBetween('created_at', [$previousStart, $previousEnd])
            ->whereNotIn('status', ['cancelled', 'failed']);

        $previousRevenue = (clone $previousOrders)->sum('total');
        $previousOrderCount = (clone $previousOrders)->count();
        $previousAvgOrderValue = $previousOrderCount > 0 ? $previousRevenue / $previousOrderCount : 0;

        // Calculate growth percentages
        $revenueGrowth = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        $orderGrowth = $previousOrderCount > 0 ? (($currentOrderCount - $previousOrderCount) / $previousOrderCount) * 100 : 0;
        $aovGrowth = $previousAvgOrderValue > 0 ? (($currentAvgOrderValue - $previousAvgOrderValue) / $previousAvgOrderValue) * 100 : 0;

        // Refunds
        $refunds = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'refunded')
            ->sum('total');

        // Cancelled metrics
        $currentCancelled = Order::whereBetween('created_at', [$start, $end])
            ->where('status', 'cancelled');
        $currentCancelledRevenue = (clone $currentCancelled)->sum('total');
        $currentCancelledCount = (clone $currentCancelled)->count();

        $previousCancelledRevenue = Order::whereBetween('created_at', [$previousStart, $previousEnd])
            ->where('status', 'cancelled')
            ->sum('total');
        
        $cancelledGrowth = $previousCancelledRevenue > 0 ? (($currentCancelledRevenue - $previousCancelledRevenue) / $previousCancelledRevenue) * 100 : 0;

        return [
            'period' => $period,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'revenue' => round($currentRevenue, 2),
            'revenue_growth' => round($revenueGrowth, 1),
            'orders' => $currentOrderCount,
            'orders_growth' => round($orderGrowth, 1),
            'average_order_value' => round($currentAvgOrderValue, 2),
            'aov_growth' => round($aovGrowth, 1),
            'refunds' => round($refunds, 2),
            'net_revenue' => round($currentRevenue - $refunds, 2),
            'cancelled_revenue' => round($currentCancelledRevenue, 2),
            'cancelled_count' => $currentCancelledCount,
            'cancelled_growth' => round($cancelledGrowth, 1),
        ];
    }

    /**
     * Get daily sales data for charts
     */
    public function getDailySalesChart(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);
        $start = $dates['start']->startOfDay();
        $end = $dates['end']->endOfDay();

        $sales = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total) as revenue'),
            DB::raw('COUNT(*) as orders')
        )
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $revenueData = [];
        $ordersData = [];

        $current = $start->copy();
        while ($current <= $end) {
            $date = $current->toDateString();
            $labels[] = $current->format('M d');
            $revenueData[] = isset($sales[$date]) ? round($sales[$date]->revenue, 2) : 0;
            $ordersData[] = isset($sales[$date]) ? $sales[$date]->orders : 0;
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenue (৳)',
                    'data' => $revenueData,
                    'borderColor' => '#4e73df',
                    'backgroundColor' => 'rgba(78, 115, 223, 0.1)',
                ],
                [
                    'label' => 'Orders',
                    'data' => $ordersData,
                    'borderColor' => '#1cc88a',
                    'backgroundColor' => 'rgba(28, 200, 138, 0.1)',
                ],
            ],
        ];
    }

    /**
     * Get sales by payment method
     */
    public function getSalesByPaymentMethod(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return Order::select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => ucfirst($item->payment_method ?? 'Unknown'),
                    'count' => $item->count,
                    'total' => round($item->total, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get cancellations by reason
     */
    public function getCancellationByReason(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        $logs = \App\Models\OrderActivityLog::where('type', 'status_change')
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('metadata', 'LIKE', '%"new_status":"cancelled"%')
            ->get();
        
        $reasons = [];

        foreach ($logs as $log) {
            $metadata = $log->metadata ?? [];
            if (($metadata['new_status'] ?? '') === 'cancelled') {
                $reason = $metadata['reason'] ?? 'Unspecified';
                if (!isset($reasons[$reason])) {
                    $reasons[$reason] = 0;
                }
                $reasons[$reason]++;
            }
        }

        $formatted = [];
        foreach ($reasons as $reason => $count) {
            $formatted[] = [
                'reason' => $reason,
                'count' => $count,
            ];
        }

        usort($formatted, fn($a, $b) => $b['count'] <=> $a['count']);

        return $formatted;
    }

    /**
     * Get sales by order source
     */
    public function getSalesByOrderSource(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return Order::select('order_source', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->groupBy('order_source')
            ->get()
            ->map(function ($item) {
                return [
                    'source' => $item->order_source ?? 'Web',
                    'count' => $item->count,
                    'total' => round($item->total, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get sales by location (shipping city)
     */
    public function getSalesByLocation(string $period = 'month', int $limit = 10): array
    {
        $dates = $this->getDateRange($period);

        return Order::select('shipping_city', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->whereNotNull('shipping_city')
            ->where('shipping_city', '!=', '')
            ->groupBy('shipping_city')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'city' => ucfirst($item->shipping_city),
                    'count' => $item->count,
                    'total' => round($item->total, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get sales by category
     */
    public function getSalesByCategory(string $period = 'month', int $limit = 10): array
    {
        $dates = $this->getDateRange($period);

        return Category::select('categories.id', 'categories.name')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as revenue')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$dates['start'], $dates['end']])
            ->whereNotIn('orders.status', ['cancelled', 'failed'])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'orders' => $item->orders,
                    'units_sold' => $item->units_sold,
                    'revenue' => round($item->revenue, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get hourly sales distribution
     */
    public function getHourlySalesDistribution(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        $hourlyData = Order::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as orders'),
            DB::raw('SUM(total) as revenue')
        )
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $labels = [];
        $ordersData = [];
        $revenueData = [];

        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
            $ordersData[] = $hourlyData->has($h) ? $hourlyData[$h]->orders : 0;
            $revenueData[] = $hourlyData->has($h) ? round($hourlyData[$h]->revenue, 2) : 0;
        }

        return [
            'labels' => $labels,
            'orders' => $ordersData,
            'revenue' => $revenueData,
            'peak_hour' => array_keys($ordersData, max($ordersData))[0] ?? 0,
        ];
    }

    // ==================== INVENTORY ALERTS ====================

    /**
     * Get low stock products
     */
    public function getLowStockProducts(int $threshold = 10): array
    {
        return Product::where('stock_quantity', '<=', $threshold)
            ->where('stock_quantity', '>', 0)
            ->where('is_active', true)
            ->orderBy('stock_quantity', 'asc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock' => $product->stock_quantity,
                    'category' => $product->category->name ?? 'Uncategorized',
                    'price' => $product->regular_price,
                    'potential_loss' => round($product->regular_price * $product->stock_quantity, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts(): array
    {
        return Product::where('stock_quantity', '<=', 0)
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($product) {
                // Calculate days out of stock (rough estimate)
                $lastSale = OrderItem::where('product_id', $product->id)
                    ->latest('created_at')
                    ->first();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category->name ?? 'Uncategorized',
                    'last_sale' => $lastSale ? $lastSale->created_at->diffForHumans() : 'Never',
                    'price' => $product->regular_price,
                ];
            })
            ->toArray();
    }

    /**
     * Get inventory valuation
     */
    public function getInventoryValuation(): array
    {
        $products = Product::where('is_active', true)->get();

        $totalValue = 0;
        $totalCost = 0;
        $totalUnits = 0;
        $categoryBreakdown = [];

        foreach ($products as $product) {
            $value = $product->regular_price * $product->stock_quantity;
            $cost = ($product->buy_price ?? 0) * $product->stock_quantity;
            $totalValue += $value;
            $totalCost += $cost;
            $totalUnits += $product->stock_quantity;

            $categoryName = $product->category->name ?? 'Uncategorized';
            if (!isset($categoryBreakdown[$categoryName])) {
                $categoryBreakdown[$categoryName] = [
                    'units' => 0,
                    'value' => 0,
                    'cost' => 0,
                ];
            }
            $categoryBreakdown[$categoryName]['units'] += $product->stock_quantity;
            $categoryBreakdown[$categoryName]['value'] += $value;
            $categoryBreakdown[$categoryName]['cost'] += $cost;
        }

        $potentialProfit = $totalValue - $totalCost;
        $potentialProfitPercentage = $totalCost > 0
            ? round(($potentialProfit / $totalCost) * 100, 1)
            : 0;

        return [
            'total_units' => $totalUnits,
            'total_retail_value' => round($totalValue, 2),
            'total_cost_value' => round($totalCost, 2),
            'potential_profit' => round($potentialProfit, 2),
            'potential_profit_percentage' => $potentialProfitPercentage,
            'by_category' => collect($categoryBreakdown)
                ->map(fn($data, $name) => [
                    'name' => $name,
                    'units' => $data['units'],
                    'value' => round($data['value'], 2),
                    'cost' => round($data['cost'], 2),
                ])
                ->sortByDesc('value')
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Get inventory turnover rate
     */
    public function getInventoryTurnover(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $soldItems = OrderItem::select('product_id')
            ->selectRaw('SUM(quantity) as sold')
            ->whereHas('order', function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                    ->whereNotIn('status', ['cancelled', 'failed']);
            })
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        return Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->get()
            ->map(function ($product) use ($soldItems, $days) {
                $sold = $soldItems->has($product->id) ? $soldItems[$product->id]->sold : 0;
                $avgInventory = $product->stock_quantity + ($sold / 2); // Rough average
                $turnoverRate = $avgInventory > 0 ? ($sold / $avgInventory) * (365 / $days) : 0;
                $daysToSellout = $sold > 0 ? round(($product->stock_quantity / $sold) * $days, 0) : null;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock' => $product->stock_quantity,
                    'sold_period' => $sold,
                    'turnover_rate' => round($turnoverRate, 2),
                    'days_to_sellout' => $daysToSellout,
                ];
            })
            ->sortByDesc('turnover_rate')
            ->values()
            ->toArray();
    }

    // ==================== CUSTOMER ANALYTICS ====================

    /**
     * Get customer overview
     */
    public function getCustomerOverview(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);
        $previousDates = $this->getDateRange($period, $dates['previous_start'], $dates['previous_end']);

        // New customers
        $newCustomers = User::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('role', 'customer')
            ->count();

        $previousNewCustomers = User::whereBetween('created_at', [$dates['previous_start'], $dates['previous_end']])
            ->where('role', 'customer')
            ->count();

        // Active customers (placed order)
        $activeCustomers = Order::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->distinct('user_id')
            ->count('user_id');

        // Returning customers
        $returningCustomers = Order::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->whereIn('user_id', function ($query) use ($dates) {
                $query->select('user_id')
                    ->from('orders')
                    ->where('created_at', '<', $dates['start'])
                    ->whereNotIn('status', ['cancelled', 'failed']);
            })
            ->distinct('user_id')
            ->count('user_id');

        $newCustomerGrowth = $previousNewCustomers > 0
            ? (($newCustomers - $previousNewCustomers) / $previousNewCustomers) * 100
            : 0;

        return [
            'total_customers' => User::where('role', 'customer')->count(),
            'new_customers' => $newCustomers,
            'new_customer_growth' => round($newCustomerGrowth, 1),
            'active_customers' => $activeCustomers,
            'returning_customers' => $returningCustomers,
            'retention_rate' => $activeCustomers > 0 
                ? round(($returningCustomers / $activeCustomers) * 100, 1) 
                : 0,
        ];
    }

    /**
     * Get top customers by lifetime value
     */
    public function getTopCustomers(int $limit = 20): array
    {
        return User::select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->selectRaw('COUNT(orders.id) as total_orders')
            ->selectRaw('SUM(orders.total) as lifetime_value')
            ->selectRaw('AVG(orders.total) as avg_order_value')
            ->selectRaw('MAX(orders.created_at) as last_order_at')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->where('users.role', 'customer')
            ->whereNotIn('orders.status', ['cancelled', 'failed'])
            ->groupBy('users.id', 'users.name', 'users.email', 'users.created_at')
            ->orderByDesc('lifetime_value')
            ->limit($limit)
            ->get()
            ->map(function ($customer) {
                $daysSinceLastOrder = $customer->last_order_at 
                    ? Carbon::parse($customer->last_order_at)->diffInDays(now())
                    : null;

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'total_orders' => $customer->total_orders,
                    'lifetime_value' => round($customer->lifetime_value, 2),
                    'avg_order_value' => round($customer->avg_order_value, 2),
                    'last_order_at' => $customer->last_order_at,
                    'days_since_last_order' => $daysSinceLastOrder,
                    'customer_since' => Carbon::parse($customer->created_at)->format('M Y'),
                    'status' => $this->getCustomerStatus($daysSinceLastOrder),
                ];
            })
            ->toArray();
    }

    /**
     * Get customer segments by purchase frequency
     */
    public function getCustomerSegments(): array
    {
        $customers = User::select('users.id')
            ->selectRaw('COUNT(orders.id) as order_count')
            ->selectRaw('SUM(orders.total) as total_spent')
            ->selectRaw('MAX(orders.created_at) as last_order')
            ->leftJoin('orders', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                    ->whereNotIn('orders.status', ['cancelled', 'failed']);
            })
            ->where('users.role', 'customer')
            ->groupBy('users.id')
            ->get();

        $segments = [
            'champions' => ['count' => 0, 'revenue' => 0, 'criteria' => 'High frequency, high value, recent'],
            'loyal' => ['count' => 0, 'revenue' => 0, 'criteria' => 'Regular buyers, good value'],
            'potential' => ['count' => 0, 'revenue' => 0, 'criteria' => 'Recent customers with potential'],
            'at_risk' => ['count' => 0, 'revenue' => 0, 'criteria' => 'Previously active, now inactive'],
            'dormant' => ['count' => 0, 'revenue' => 0, 'criteria' => 'No recent activity'],
            'new' => ['count' => 0, 'revenue' => 0, 'criteria' => 'First-time buyers'],
        ];

        foreach ($customers as $customer) {
            $daysSinceOrder = $customer->last_order 
                ? Carbon::parse($customer->last_order)->diffInDays(now())
                : 999;
            $orderCount = $customer->order_count ?? 0;
            $totalSpent = $customer->total_spent ?? 0;

            $segment = $this->determineSegment($orderCount, $totalSpent, $daysSinceOrder);
            $segments[$segment]['count']++;
            $segments[$segment]['revenue'] += $totalSpent;
        }

        return collect($segments)->map(function ($data, $name) {
            return [
                'segment' => ucfirst(str_replace('_', ' ', $name)),
                'count' => $data['count'],
                'revenue' => round($data['revenue'], 2),
                'criteria' => $data['criteria'],
            ];
        })->values()->toArray();
    }

    /**
     * Get customer cohort analysis
     */
    public function getCohortAnalysis(int $months = 6): array
    {
        $cohorts = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $cohortMonth = now()->subMonths($i)->startOfMonth();
            $cohortEnd = (clone $cohortMonth)->endOfMonth();

            // Get customers who made first purchase in this month
            $cohortCustomers = Order::select('user_id')
                ->selectRaw('MIN(created_at) as first_order')
                ->whereNotIn('status', ['cancelled', 'failed'])
                ->groupBy('user_id')
                ->havingRaw('MIN(created_at) BETWEEN ? AND ?', [$cohortMonth, $cohortEnd])
                ->pluck('user_id');

            $cohortSize = $cohortCustomers->count();
            $retention = [];

            // Calculate retention for each subsequent month
            for ($j = 0; $j <= $i; $j++) {
                $checkMonth = (clone $cohortMonth)->addMonths($j);
                $checkEnd = (clone $checkMonth)->endOfMonth();

                $activeInMonth = Order::whereBetween('created_at', [$checkMonth, $checkEnd])
                    ->whereIn('user_id', $cohortCustomers)
                    ->whereNotIn('status', ['cancelled', 'failed'])
                    ->distinct('user_id')
                    ->count('user_id');

                $retention[] = $cohortSize > 0 ? round(($activeInMonth / $cohortSize) * 100, 1) : 0;
            }

            $cohorts[] = [
                'month' => $cohortMonth->format('M Y'),
                'cohort_size' => $cohortSize,
                'retention' => $retention,
            ];
        }

        return $cohorts;
    }

    // ==================== PRODUCT PERFORMANCE ====================

    /**
     * Get best selling products
     */
    public function getBestSellers(string $period = 'month', int $limit = 20): array
    {
        $dates = $this->getDateRange($period);

        return Product::select('products.id', 'products.name', 'products.sku', 'products.regular_price', 'products.stock_quantity')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as revenue')
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$dates['start'], $dates['end']])
            ->whereNotIn('orders.status', ['cancelled', 'failed'])
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.regular_price', 'products.stock_quantity')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(function ($product, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->regular_price,
                    'units_sold' => $product->units_sold,
                    'revenue' => round($product->revenue, 2),
                    'order_count' => $product->order_count,
                    'stock' => $product->stock_quantity,
                    'stock_status' => $product->stock_quantity > 10 ? 'good' : ($product->stock_quantity > 0 ? 'low' : 'out'),
                ];
            })
            ->toArray();
    }

    /**
     * Get slow moving products
     */
    public function getSlowMovers(int $days = 30, int $limit = 20): array
    {
        $startDate = now()->subDays($days);

        // Get products with sales
        $soldProducts = OrderItem::select('product_id')
            ->selectRaw('SUM(quantity) as sold')
            ->whereHas('order', function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                    ->whereNotIn('status', ['cancelled', 'failed']);
            })
            ->groupBy('product_id')
            ->pluck('sold', 'product_id');

        return Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->get()
            ->map(function ($product) use ($soldProducts, $days) {
                $sold = $soldProducts->get($product->id, 0);
                $dailyRate = $sold / $days;
                $daysOfStock = $dailyRate > 0 ? round($product->stock_quantity / $dailyRate, 0) : 999;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock' => $product->stock_quantity,
                    'stock_value' => round($product->regular_price * $product->stock_quantity, 2),
                    'sold_last_period' => $sold,
                    'daily_sales_rate' => round($dailyRate, 2),
                    'days_of_stock' => $daysOfStock > 365 ? '365+' : $daysOfStock,
                    'category' => $product->category->name ?? 'Uncategorized',
                ];
            })
            ->filter(fn($p) => $p['sold_last_period'] < 5) // Less than 5 sales
            ->sortBy('sold_last_period')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Get product performance trends
     */
    public function getProductTrends(int $productId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        $product = Product::findOrFail($productId);

        $dailySales = OrderItem::select(DB::raw('DATE(orders.created_at) as date'))
            ->selectRaw('SUM(order_items.quantity) as units')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as revenue')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', $startDate)
            ->whereNotIn('orders.status', ['cancelled', 'failed'])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $unitsData = [];
        $revenueData = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($date)->format('M d');
            $unitsData[] = $dailySales->has($date) ? $dailySales[$date]->units : 0;
            $revenueData[] = $dailySales->has($date) ? round($dailySales[$date]->revenue, 2) : 0;
        }

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
            ],
            'chart' => [
                'labels' => $labels,
                'units' => $unitsData,
                'revenue' => $revenueData,
            ],
            'summary' => [
                'total_units' => array_sum($unitsData),
                'total_revenue' => array_sum($revenueData),
                'avg_daily_units' => round(array_sum($unitsData) / ($days + 1), 2),
                'peak_day' => $labels[array_search(max($unitsData), $unitsData)] ?? null,
            ],
        ];
    }

    /**
     * Get product conversion rates
     */
    public function getProductConversionRates(int $limit = 20): array
    {
        // This would ideally use view tracking data
        // For now, we'll estimate based on order frequency
        $dates = $this->getDateRange('month');

        return Product::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('COUNT(DISTINCT orders.id) as purchases')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as revenue')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('products.is_active', true)
            ->whereBetween('orders.created_at', [$dates['start'], $dates['end']])
            ->whereNotIn('orders.status', ['cancelled', 'failed'])
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('purchases')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'purchases' => $product->purchases,
                    'units_sold' => $product->units_sold,
                    'revenue' => round($product->revenue, 2),
                    'avg_units_per_order' => round($product->units_sold / $product->purchases, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get products frequently bought together
     */
    public function getFrequentlyBoughtTogether(int $productId, int $limit = 5): array
    {
        // Get orders containing this product
        $orderIds = OrderItem::where('product_id', $productId)
            ->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return [];
        }

        return OrderItem::select('products.id', 'products.name', 'products.regular_price')
            ->selectRaw('COUNT(*) as frequency')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('order_items.order_id', $orderIds)
            ->where('order_items.product_id', '!=', $productId)
            ->groupBy('products.id', 'products.name', 'products.regular_price')
            ->orderByDesc('frequency')
            ->limit($limit)
            ->get()
            ->map(function ($item) use ($orderIds) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->regular_price,
                    'frequency' => $item->frequency,
                    'co_purchase_rate' => round(($item->frequency / $orderIds->count()) * 100, 1),
                ];
            })
            ->toArray();
    }

    // ==================== HELPER METHODS ====================

    protected function getDateRange(string $period, ?Carbon $customStart = null, ?Carbon $customEnd = null): array
    {
        if ($customStart && $customEnd) {
            $daysDiff = $customStart->diffInDays($customEnd);
            return [
                'start' => $customStart,
                'end' => $customEnd,
                'previous_start' => (clone $customStart)->subDays($daysDiff + 1),
                'previous_end' => (clone $customStart)->subDay(),
            ];
        }

        return match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
                'previous_start' => now()->subDay()->startOfDay(),
                'previous_end' => now()->subDay()->endOfDay(),
            ],
            'yesterday' => [
                'start' => now()->subDay()->startOfDay(),
                'end' => now()->subDay()->endOfDay(),
                'previous_start' => now()->subDays(2)->startOfDay(),
                'previous_end' => now()->subDays(2)->endOfDay(),
            ],
            'week', 'this_week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
                'previous_start' => now()->subWeek()->startOfWeek(),
                'previous_end' => now()->subWeek()->endOfWeek(),
            ],
            'month', 'this_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
                'previous_start' => now()->subMonth()->startOfMonth(),
                'previous_end' => now()->subMonth()->endOfMonth(),
            ],
            'last_month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
                'previous_start' => now()->subMonths(2)->startOfMonth(),
                'previous_end' => now()->subMonths(2)->endOfMonth(),
            ],
            'quarter' => [
                'start' => now()->startOfQuarter(),
                'end' => now()->endOfQuarter(),
                'previous_start' => now()->subQuarter()->startOfQuarter(),
                'previous_end' => now()->subQuarter()->endOfQuarter(),
            ],
            'year', 'this_year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
                'previous_start' => now()->subYear()->startOfYear(),
                'previous_end' => now()->subYear()->endOfYear(),
            ],
            'last_year' => [
                'start' => now()->subYear()->startOfYear(),
                'end' => now()->subYear()->endOfYear(),
                'previous_start' => now()->subYears(2)->startOfYear(),
                'previous_end' => now()->subYears(2)->endOfYear(),
            ],
            default => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
                'previous_start' => now()->subMonth()->startOfMonth(),
                'previous_end' => now()->subMonth()->endOfMonth(),
            ],
        };
    }

    protected function getCustomerStatus(?int $daysSinceLastOrder): string
    {
        if ($daysSinceLastOrder === null) return 'new';
        if ($daysSinceLastOrder <= 30) return 'active';
        if ($daysSinceLastOrder <= 90) return 'at_risk';
        return 'dormant';
    }

    protected function determineSegment(int $orderCount, float $totalSpent, int $daysSinceOrder): string
    {
        if ($orderCount === 0) return 'new';
        if ($orderCount === 1 && $daysSinceOrder <= 30) return 'new';
        if ($daysSinceOrder > 180) return 'dormant';
        if ($daysSinceOrder > 90) return 'at_risk';
        if ($orderCount >= 5 && $totalSpent >= 10000 && $daysSinceOrder <= 30) return 'champions';
        if ($orderCount >= 3 && $totalSpent >= 5000) return 'loyal';
        return 'potential';
    }
}

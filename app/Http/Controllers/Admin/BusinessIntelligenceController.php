<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BusinessIntelligenceService;
use Illuminate\Http\Request;

class BusinessIntelligenceController extends Controller
{
    public function __construct(
        protected BusinessIntelligenceService $biService
    ) {}

    /**
     * Main BI Dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');

        $salesOverview = $this->biService->getSalesOverview($period);
        $customerOverview = $this->biService->getCustomerOverview($period);
        $lowStock = $this->biService->getLowStockProducts(10);
        $outOfStock = $this->biService->getOutOfStockProducts();
        $bestSellers = $this->biService->getBestSellers($period, 5);

        return view('admin.bi.index', compact(
            'period',
            'salesOverview',
            'customerOverview',
            'lowStock',
            'outOfStock',
            'bestSellers'
        ));
    }

    /**
     * Sales Reports Page
     */
    public function salesReports(Request $request)
    {
        $period = $request->get('period', 'month');
        $days = $request->get('days', 30);

        $overview = $this->biService->getSalesOverview($period);
        $dailyChart = $this->biService->getDailySalesChart($days);
        $byPaymentMethod = $this->biService->getSalesByPaymentMethod($period);
        $byCategory = $this->biService->getSalesByCategory($period, 10);
        $hourlyDistribution = $this->biService->getHourlySalesDistribution($days);

        return view('admin.bi.sales-reports', compact(
            'period',
            'days',
            'overview',
            'dailyChart',
            'byPaymentMethod',
            'byCategory',
            'hourlyDistribution'
        ));
    }

    /**
     * Inventory Alerts Page
     */
    public function inventoryAlerts(Request $request)
    {
        $threshold = $request->get('threshold', 10);

        $lowStock = $this->biService->getLowStockProducts($threshold);
        $outOfStock = $this->biService->getOutOfStockProducts();
        $valuation = $this->biService->getInventoryValuation();
        $turnover = $this->biService->getInventoryTurnover(30);

        return view('admin.bi.inventory-alerts', compact(
            'threshold',
            'lowStock',
            'outOfStock',
            'valuation',
            'turnover'
        ));
    }

    /**
     * Customer Analytics Page
     */
    public function customerAnalytics(Request $request)
    {
        $period = $request->get('period', 'month');

        $overview = $this->biService->getCustomerOverview($period);
        $topCustomers = $this->biService->getTopCustomers(20);
        $segments = $this->biService->getCustomerSegments();
        $cohorts = $this->biService->getCohortAnalysis(6);

        return view('admin.bi.customer-analytics', compact(
            'period',
            'overview',
            'topCustomers',
            'segments',
            'cohorts'
        ));
    }

    /**
     * Product Performance Page
     */
    public function productPerformance(Request $request)
    {
        $period = $request->get('period', 'month');
        $days = $request->get('days', 30);

        $bestSellers = $this->biService->getBestSellers($period, 20);
        $slowMovers = $this->biService->getSlowMovers($days, 20);
        $conversionRates = $this->biService->getProductConversionRates(20);

        return view('admin.bi.product-performance', compact(
            'period',
            'days',
            'bestSellers',
            'slowMovers',
            'conversionRates'
        ));
    }

    /**
     * Get product trend data (AJAX)
     */
    public function productTrends(Request $request)
    {
        $productId = $request->get('product_id');
        $days = $request->get('days', 30);

        if (!$productId) {
            return response()->json(['error' => 'Product ID required'], 400);
        }

        $trends = $this->biService->getProductTrends($productId, $days);

        return response()->json($trends);
    }

    /**
     * Get frequently bought together (AJAX)
     */
    public function frequentlyBoughtTogether(Request $request)
    {
        $productId = $request->get('product_id');

        if (!$productId) {
            return response()->json(['error' => 'Product ID required'], 400);
        }

        $products = $this->biService->getFrequentlyBoughtTogether($productId);

        return response()->json($products);
    }

    /**
     * Export sales report
     */
    public function exportSalesReport(Request $request)
    {
        $period = $request->get('period', 'month');
        $days = $request->get('days', 30);

        $overview = $this->biService->getSalesOverview($period);
        $byCategory = $this->biService->getSalesByCategory($period, 50);
        $byPaymentMethod = $this->biService->getSalesByPaymentMethod($period);

        $filename = 'sales_report_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($overview, $byCategory, $byPaymentMethod) {
            $file = fopen('php://output', 'w');

            // Overview section
            fputcsv($file, ['=== SALES OVERVIEW ===']);
            fputcsv($file, ['Metric', 'Value', 'Growth %']);
            fputcsv($file, ['Revenue', $overview['revenue'], $overview['revenue_growth'] . '%']);
            fputcsv($file, ['Orders', $overview['orders'], $overview['orders_growth'] . '%']);
            fputcsv($file, ['Avg Order Value', $overview['average_order_value'], $overview['aov_growth'] . '%']);
            fputcsv($file, ['Refunds', $overview['refunds'], '']);
            fputcsv($file, ['Net Revenue', $overview['net_revenue'], '']);
            fputcsv($file, []);

            // By Category
            fputcsv($file, ['=== SALES BY CATEGORY ===']);
            fputcsv($file, ['Category', 'Orders', 'Units Sold', 'Revenue']);
            foreach ($byCategory as $cat) {
                fputcsv($file, [$cat['name'], $cat['orders'], $cat['units_sold'], $cat['revenue']]);
            }
            fputcsv($file, []);

            // By Payment Method
            fputcsv($file, ['=== SALES BY PAYMENT METHOD ===']);
            fputcsv($file, ['Method', 'Orders', 'Total']);
            foreach ($byPaymentMethod as $method) {
                fputcsv($file, [$method['method'], $method['count'], $method['total']]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export inventory report
     */
    public function exportInventoryReport(Request $request)
    {
        $threshold = $request->get('threshold', 10);

        $lowStock = $this->biService->getLowStockProducts($threshold);
        $outOfStock = $this->biService->getOutOfStockProducts();
        $valuation = $this->biService->getInventoryValuation();

        $filename = 'inventory_report_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($lowStock, $outOfStock, $valuation) {
            $file = fopen('php://output', 'w');

            // Valuation Summary
            fputcsv($file, ['=== INVENTORY VALUATION ===']);
            fputcsv($file, ['Total Units', $valuation['total_units']]);
            fputcsv($file, ['Retail Value', $valuation['total_retail_value']]);
            fputcsv($file, ['Cost Value', $valuation['total_cost_value']]);
            fputcsv($file, ['Potential Profit', $valuation['potential_profit']]);
            fputcsv($file, ['Potential Profit %', $valuation['potential_profit_percentage'] . '%']);
            fputcsv($file, []);

            // Low Stock
            fputcsv($file, ['=== LOW STOCK ITEMS ===']);
            fputcsv($file, ['Product', 'SKU', 'Stock', 'Category', 'Price']);
            foreach ($lowStock as $item) {
                fputcsv($file, [$item['name'], $item['sku'], $item['stock'], $item['category'], $item['price']]);
            }
            fputcsv($file, []);

            // Out of Stock
            fputcsv($file, ['=== OUT OF STOCK ===']);
            fputcsv($file, ['Product', 'SKU', 'Category', 'Last Sale', 'Price']);
            foreach ($outOfStock as $item) {
                fputcsv($file, [$item['name'], $item['sku'], $item['category'], $item['last_sale'], $item['price']]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export customer report
     */
    public function exportCustomerReport(Request $request)
    {
        $topCustomers = $this->biService->getTopCustomers(100);
        $segments = $this->biService->getCustomerSegments();

        $filename = 'customer_report_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($topCustomers, $segments) {
            $file = fopen('php://output', 'w');

            // Segments
            fputcsv($file, ['=== CUSTOMER SEGMENTS ===']);
            fputcsv($file, ['Segment', 'Count', 'Revenue', 'Description']);
            foreach ($segments as $seg) {
                fputcsv($file, [$seg['segment'], $seg['count'], $seg['revenue'], $seg['criteria']]);
            }
            fputcsv($file, []);

            // Top Customers
            fputcsv($file, ['=== TOP CUSTOMERS ===']);
            fputcsv($file, ['Name', 'Email', 'Total Orders', 'Lifetime Value', 'Avg Order', 'Last Order', 'Status']);
            foreach ($topCustomers as $customer) {
                fputcsv($file, [
                    $customer['name'],
                    $customer['email'],
                    $customer['total_orders'],
                    $customer['lifetime_value'],
                    $customer['avg_order_value'],
                    $customer['last_order_at'],
                    $customer['status'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

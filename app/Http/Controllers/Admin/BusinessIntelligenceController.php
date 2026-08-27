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
     * Write a CSV row with formula-injection neutralized: a cell starting
     * with =, +, -, or @ is interpreted as a formula by Excel/Sheets when
     * the exported file is opened, so customer/product-name fields (which
     * are attacker-controlled — a shipping name, a category name) could
     * otherwise execute arbitrary formulas on whoever opens the export.
     */
    private static function csvRow($file, array $row): void
    {
        fputcsv($file, array_map(static function ($value) {
            if (is_string($value) && preg_match('/^[=+\-@]/', $value) === 1) {
                return "\t".$value;
            }

            return $value;
        }, $row));
    }

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

        return view('admin.analytics.bi-dashboard', compact(
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

        $overview = $this->biService->getSalesOverview($period);
        $dailyChart = $this->biService->getDailySalesChart($period);
        $byPaymentMethod = $this->biService->getSalesByPaymentMethod($period);
        $byOrderSource = $this->biService->getSalesByOrderSource($period);
        $byLocation = $this->biService->getSalesByLocation($period, 10);
        $byCategory = $this->biService->getSalesByCategory($period, 10);
        $hourlyDistribution = $this->biService->getHourlySalesDistribution($period);
        $byCancellationReason = $this->biService->getCancellationByReason($period);

        return view('admin.analytics.sales-reports', compact(
            'period',
            'overview',
            'dailyChart',
            'byPaymentMethod',
            'byOrderSource',
            'byLocation',
            'byCategory',
            'hourlyDistribution',
            'byCancellationReason'
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

        return view('admin.analytics.inventory-alerts', compact(
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

        return view('admin.analytics.customer-analytics', compact(
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

        return view('admin.analytics.product-performance', compact(
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

        if (! $productId) {
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

        if (! $productId) {
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

        $filename = 'sales_report_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($overview, $byCategory, $byPaymentMethod) {
            $file = fopen('php://output', 'w');

            // Overview section
            self::csvRow($file, ['=== SALES OVERVIEW ===']);
            self::csvRow($file, ['Metric', 'Value', 'Growth %']);
            self::csvRow($file, ['Revenue', $overview['revenue'], $overview['revenue_growth'].'%']);
            self::csvRow($file, ['Orders', $overview['orders'], $overview['orders_growth'].'%']);
            self::csvRow($file, ['Avg Order Value', $overview['average_order_value'], $overview['aov_growth'].'%']);
            self::csvRow($file, ['Refunds', $overview['refunds'], '']);
            self::csvRow($file, ['Net Revenue', $overview['net_revenue'], '']);
            self::csvRow($file, []);

            // By Category
            self::csvRow($file, ['=== SALES BY CATEGORY ===']);
            self::csvRow($file, ['Category', 'Orders', 'Units Sold', 'Revenue']);
            foreach ($byCategory as $cat) {
                self::csvRow($file, [$cat['name'], $cat['orders'], $cat['units_sold'], $cat['revenue']]);
            }
            self::csvRow($file, []);

            // By Payment Method
            self::csvRow($file, ['=== SALES BY PAYMENT METHOD ===']);
            self::csvRow($file, ['Method', 'Orders', 'Total']);
            foreach ($byPaymentMethod as $method) {
                self::csvRow($file, [$method['method'], $method['count'], $method['total']]);
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

        $filename = 'inventory_report_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($lowStock, $outOfStock, $valuation) {
            $file = fopen('php://output', 'w');

            // Valuation Summary
            self::csvRow($file, ['=== INVENTORY VALUATION ===']);
            self::csvRow($file, ['Total Units', $valuation['total_units']]);
            self::csvRow($file, ['Retail Value', $valuation['total_retail_value']]);
            self::csvRow($file, ['Cost Value', $valuation['total_cost_value']]);
            self::csvRow($file, ['Potential Profit', $valuation['potential_profit']]);
            self::csvRow($file, ['Potential Profit %', $valuation['potential_profit_percentage'].'%']);
            self::csvRow($file, []);

            // Low Stock
            self::csvRow($file, ['=== LOW STOCK ITEMS ===']);
            self::csvRow($file, ['Product', 'SKU', 'Stock', 'Category', 'Price']);
            foreach ($lowStock as $item) {
                self::csvRow($file, [$item['name'], $item['sku'], $item['stock'], $item['category'], $item['price']]);
            }
            self::csvRow($file, []);

            // Out of Stock
            self::csvRow($file, ['=== OUT OF STOCK ===']);
            self::csvRow($file, ['Product', 'SKU', 'Category', 'Last Sale', 'Price']);
            foreach ($outOfStock as $item) {
                self::csvRow($file, [$item['name'], $item['sku'], $item['category'], $item['last_sale'], $item['price']]);
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

        $filename = 'customer_report_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($topCustomers, $segments) {
            $file = fopen('php://output', 'w');

            // Segments
            self::csvRow($file, ['=== CUSTOMER SEGMENTS ===']);
            self::csvRow($file, ['Segment', 'Count', 'Revenue', 'Description']);
            foreach ($segments as $seg) {
                self::csvRow($file, [$seg['segment'], $seg['count'], $seg['revenue'], $seg['criteria']]);
            }
            self::csvRow($file, []);

            // Top Customers
            self::csvRow($file, ['=== TOP CUSTOMERS ===']);
            self::csvRow($file, ['Name', 'Email', 'Total Orders', 'Lifetime Value', 'Avg Order', 'Last Order', 'Status']);
            foreach ($topCustomers as $customer) {
                self::csvRow($file, [
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\CourierCheckResult;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingMethodDistrictRate;
use App\Models\BdDistrict;
use App\Services\CourierHistoryCheckService;
use App\Services\FraudDetectionService;
use App\Services\LicenseService;
use App\Services\SmsService;
use App\Support\FraudNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(protected LicenseService $licenseService) {}

    public function index(Request $request)
    {
        $statuses = OrderStatus::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $statusKeys = $statuses->pluck('key')->all();

        if (empty($statusKeys)) {
            $statusKeys = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        }

        $allowedViews = array_merge(['all', 'trash'], $statusKeys);
        $view = (string) $request->input('view', 'all');

        if (!in_array($view, $allowedViews, true)) {
            $view = 'all';
        }

        if ($request->filled('status')) {
            $requestedStatus = (string) $request->input('status');
            if (in_array($requestedStatus, $statusKeys, true)) {
                $view = $requestedStatus;
            }
        }

        $query = Order::with(['user', 'payment', 'statusConfig'])
            ->withCount('items')
            ->orderByDesc('created_at');

        // Orders placed after the license expired stay invisible to admin
        // (storefront checkout is unaffected) until renewal.
        $licenseCutoff = $this->licenseService->expiredSince();
        if ($licenseCutoff !== null) {
            $query->where('created_at', '<=', $licenseCutoff);
        }

        if ($view === 'trash') {
            $query->onlyTrashed();
        } elseif ($view !== 'all') {
            $query->where('status', $view);
        }

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('order_number', 'like', "%{$search}%")
                    ->orWhere('shipping_name', 'like', "%{$search}%")
                    ->orWhere('shipping_email', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });

                if (is_numeric($search)) {
                    $searchQuery->orWhere('id', (int) $search);
                }
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('order_source')) {
            $query->where('order_source', $request->input('order_source'));
        }

        $dateType = $request->input('date_type') === 'delivered_at' ? 'delivered_at' : 'created_at';

        if ($request->filled('date_from')) {
            $query->whereDate($dateType, '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate($dateType, '<=', $request->input('date_to'));
        }

        if ($request->input('date') === 'today') {
            $query->whereDate($dateType, \Carbon\Carbon::today());
        }

        if ($request->filled('product_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->input('product_id'));
            });
        }

        if ($request->filled('shipping_method')) {
            $query->where('shipping_method', $request->input('shipping_method'));
        }

        if ($request->filled('shipping_district_id')) {
            $query->where('shipping_district_id', $request->input('shipping_district_id'));
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $orders = $query->paginate($perPage)->withQueryString();

        $countQuery = fn () => $licenseCutoff !== null
            ? Order::query()->where('created_at', '<=', $licenseCutoff)
            : Order::query();

        $filterCounts = ['all' => $countQuery()->count()];
        foreach ($statusKeys as $statusKey) {
            $filterCounts[$statusKey] = $countQuery()->where('status', $statusKey)->count();
        }
        $filterCounts['trash'] = $licenseCutoff !== null
            ? Order::onlyTrashed()->where('created_at', '<=', $licenseCutoff)->count()
            : Order::onlyTrashed()->count();

        if ($statuses->isEmpty()) {
            $statuses = collect($statusKeys)->map(static function (string $key, int $index) {
                return (object) [
                    'key' => $key,
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ];
            });
        }

        $bulkStatuses = OrderStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($bulkStatuses->isEmpty()) {
            $bulkStatuses = collect($statusKeys)->map(static function (string $key) {
                return (object) [
                    'key' => $key,
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                ];
            });
        }

        $orderSources = Order::select('order_source')->distinct()->whereNotNull('order_source')->pluck('order_source');
        $paymentMethods = Order::select('payment_method')->distinct()->whereNotNull('payment_method')->pluck('payment_method');
        
        // Allowed Payment Statuses
        $paymentStatuses = ['pending', 'awaiting', 'paid', 'failed', 'refunded'];

        $products = \App\Models\Product::select('id', 'name')->where('is_active', true)->orderBy('name')->get();
        $districts = \App\Models\BdDistrict::select('id', 'name')->orderBy('name')->get();
        $availableShippingMethods = \App\Models\ShippingMethod::select('id', 'name')->where('is_active', true)->orderBy('name')->get();

        return view('admin.orders.index', compact(
            'orders', 'view', 'statuses', 'filterCounts', 'bulkStatuses',
            'orderSources', 'paymentMethods', 'paymentStatuses',
            'products', 'districts', 'availableShippingMethods'
        ));
    }

    public function show(Order $order)
    {
        $order->load([
            'user',
            'items.product',
            'items.variant.attributeValues.attribute',
            'payment',
            'statusConfig',
            'shippingDivision',
            'shippingDistrict',
            'shippingUpazila',
            'shippingUnion',
            'activityLogs',
        ]);

        $availableStatuses = OrderStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $activeCoupons = \App\Models\Coupon::active()->get();

        $smsTemplates = SmsService::getOrderSmsTemplates();

        $districts = BdDistrict::orderBy('name')->get(['id', 'name', 'division_id']);

        $courierCheckNormalizedPhone = $order->normalized_phone ?: FraudNormalizer::phone($order->shipping_phone);
        $courierCheckResult = $courierCheckNormalizedPhone
            ? CourierCheckResult::where('normalized_phone', $courierCheckNormalizedPhone)->first()
            : null;

        return view('admin.orders.show', compact('order', 'availableStatuses', 'activeCoupons', 'smsTemplates', 'districts', 'courierCheckResult'));
    }

    /**
     * The "Check" / "Refresh" buttons on the order page — an explicit admin
     * action, so unlike the automatic post-checkout job this runs
     * synchronously and returns the real result (or a real error) directly,
     * for the AJAX call to render in place. "Check" serves a result already
     * cached within CourierHistoryCheckService::SEARCH_CACHE_HOURS without
     * touching any courier; "Refresh" (refresh=true) bypasses that cache
     * for a guaranteed live answer, ignoring the `courier_check_enabled`
     * automation toggle either way since this is an explicit admin request.
     */
    public function checkCourierHistory(Request $request, Order $order, CourierHistoryCheckService $courierHistoryCheckService, FraudDetectionService $fraudDetectionService): JsonResponse
    {
        // Logging into up to 5 courier portals (with multi-account failover
        // on each) can comfortably exceed PHP's default 30s execution limit
        // — this is an explicit, admin-initiated action the user is actively
        // waiting on, so give it real headroom instead of fataling mid-check.
        set_time_limit(180);

        $forceRefresh = $request->boolean('refresh');

        if (!$courierHistoryCheckService->hasAnyCredentialsConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'No courier credentials are configured yet.',
                'settings_url' => route('admin.orders.courier-checker', ['tab' => 'settings']),
            ], 422);
        }

        $normalizedPhone = $order->normalized_phone ?: FraudNormalizer::phone($order->shipping_phone);
        if ($normalizedPhone === null) {
            return response()->json(['success' => false, 'message' => 'This order has no usable phone number to check.'], 422);
        }

        ['result' => $courierCheckResult, 'from_cache' => $fromCache] = $courierHistoryCheckService->checkWithCache($normalizedPhone, $forceRefresh, $order->id);

        if (!$fromCache) {
            $fraudDetectionService->evaluateCourierHistory($order, [
                'total_deliveries' => $courierCheckResult->total_deliveries,
                'success_ratio' => (float) $courierCheckResult->success_ratio,
            ]);
        }

        $html = view('admin.orders.partials.courier-history-card-body', [
            'courierCheckResult' => $courierCheckResult,
            'fromCache' => $fromCache,
        ])->render();

        return response()->json(['success' => true, 'html' => $html, 'from_cache' => $fromCache]);
    }

    public function print(Order $order)
    {
        $order->load([
            'user',
            'items.product',
            'items.variant.attributeValues.attribute',
            'payment',
            'statusConfig',
            'shippingDivision',
            'shippingDistrict',
            'shippingUpazila',
            'shippingUnion',
        ]);

        $invoiceSettings = \App\Models\Setting::getGroup('invoice', false);
        $steadfastEnabled = filter_var(\App\Models\Setting::getValue('courier', 'steadfast_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        $pathaoEnabled = filter_var(\App\Models\Setting::getValue('courier', 'pathao_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        $primaryColor = \App\Models\Setting::getValue('appearance', 'primary_color', '#db2777');

        return view('admin.orders.print', compact('order', 'invoiceSettings', 'steadfastEnabled', 'pathaoEnabled', 'primaryColor'));
    }

    public function export(Request $request)
    {
        $statuses = OrderStatus::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $statusKeys = $statuses->pluck('key')->all();

        if (empty($statusKeys)) {
            $statusKeys = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        }

        $allowedViews = array_merge(['all', 'trash'], $statusKeys);
        $view = (string) $request->input('view', 'all');

        if (!in_array($view, $allowedViews, true)) {
            $view = 'all';
        }

        if ($request->filled('status')) {
            $requestedStatus = (string) $request->input('status');
            if (in_array($requestedStatus, $statusKeys, true)) {
                $view = $requestedStatus;
            }
        }

        $query = Order::with(['user', 'payment', 'statusConfig', 'shippingDivision', 'shippingDistrict', 'items.product', 'items.variant'])
            ->orderByDesc('created_at');

        if ($cutoff = $this->licenseService->expiredSince()) {
            $query->where('created_at', '<=', $cutoff);
        }

        if ($view === 'trash') {
            $query->onlyTrashed();
        } elseif ($view !== 'all') {
            $query->where('status', $view);
        }

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('order_number', 'like', "%{$search}%")
                    ->orWhere('shipping_name', 'like', "%{$search}%")
                    ->orWhere('shipping_email', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });

                if (is_numeric($search)) {
                    $searchQuery->orWhere('id', (int) $search);
                }
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('order_source')) {
            $query->where('order_source', $request->input('order_source'));
        }

        $dateType = $request->input('date_type') === 'delivered_at' ? 'delivered_at' : 'created_at';

        if ($request->filled('date_from')) {
            $query->whereDate($dateType, '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate($dateType, '<=', $request->input('date_to'));
        }

        if ($request->input('date') === 'today') {
            $query->whereDate($dateType, \Carbon\Carbon::today());
        }

        if ($request->filled('product_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->input('product_id'));
            });
        }

        if ($request->filled('shipping_method')) {
            $query->where('shipping_method', $request->input('shipping_method'));
        }

        if ($request->filled('shipping_district_id')) {
            $query->where('shipping_district_id', $request->input('shipping_district_id'));
        }

        $filename = "orders-export-" . date('Y-m-d-His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Order ID', 'Order Number', 'Date Placed', 'Customer Name', 'Customer Phone', 'Customer Email',
            'Shipping Address', 'District', 'Division', 'Payment Method', 'Payment Status',
            'Carrier/Courier', 'Tracking Number', 'Subtotal', 'Discount', 'Shipping Cost', 'Tax', 'Total',
            'Order Status', 'Order Notes', 'Items Details'
        ];

        $callback = function() use($query, $columns) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM to support non-ASCII characters like Bengali perfectly in Microsoft Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, $columns);

            $query->chunk(100, function($orders) use($file) {
                foreach ($orders as $order) {
                    $itemsDetails = [];
                    foreach ($order->items as $item) {
                        $variantStr = ($item->variant && $item->variant->attributeValues->count() > 0) 
                            ? " (" . $item->variant->attributeValues->pluck('value')->implode('/') . ")" 
                            : "";
                        $itemsDetails[] = ($item->product->name ?? $item->product_name) . $variantStr . " x " . $item->quantity;
                    }
                    $itemsString = implode(" | ", $itemsDetails);

                    fputcsv($file, [
                        $order->id,
                        $order->order_number,
                        $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
                        $order->shipping_name ?? ($order->user->name ?? ''),
                        $order->shipping_phone ?? ($order->user->phone ?? ''),
                        $order->shipping_email ?? ($order->user->email ?? ''),
                        $order->shipping_address,
                        $order->shippingDistrict?->name ?? '',
                        $order->shippingDivision?->name ?? '',
                        $order->payment_method,
                        $order->payment_status,
                        $order->carrier,
                        $order->tracking_number,
                        $order->subtotal,
                        $order->discount_amount,
                        $order->shipping,
                        $order->tax,
                        $order->total,
                        $order->status,
                        $order->notes,
                        $itemsString
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $shippingMethods = ShippingMethod::where('is_active', true)->orderBy('sort_order')->get();
        $statuses = OrderStatus::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.orders.create', compact('shippingMethods', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:20'],
            'shipping_email' => ['nullable', 'email', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_zip' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string'],
            'payment_method' => ['required', 'string', 'in:cod,bkash,stripe,bank_transfer,other'],
            'payment_status' => ['required', 'string', 'in:pending,paid,awaiting'],
            'shipping_method_id' => ['nullable', 'exists:shipping_methods,id'],
            'shipping_charge' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'order_source' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += (float) $item['price'] * (int) $item['quantity'];
            }

            $shippingCost = (float) ($validated['shipping_charge'] ?? 0);
            $discount = (float) ($validated['discount_amount'] ?? 0);
            $total = max(0, $subtotal + $shippingCost - $discount);

            // Get shipping method name
            $shippingMethodName = null;
            if (!empty($validated['shipping_method_id'])) {
                $method = ShippingMethod::find($validated['shipping_method_id']);
                $shippingMethodName = $method?->name;
                if ($shippingCost <= 0 && $method) {
                    $shippingCost = (float) $method->base_cost;
                    $total = max(0, $subtotal + $shippingCost - $discount);
                }
            }

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'status' => $validated['status'],
                'order_source' => $validated['order_source'] ?? 'admin',
                'subtotal' => $subtotal,
                'tax' => 0,
                'shipping' => $shippingCost,
                'shipping_method' => $shippingMethodName,
                'total' => $total,
                'discount_amount' => $discount,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_status'],
                'shipping_name' => $validated['shipping_name'],
                'shipping_email' => $validated['shipping_email'] ?? null,
                'shipping_phone' => $validated['shipping_phone'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_city' => $validated['shipping_city'] ?? null,
                'shipping_zip' => $validated['shipping_zip'] ?? null,
                'shipping_country' => 'BD',
                'notes' => $validated['notes'] ?? null,
            ]);

            OrderActivityLog::log($order, 'order_created', 'Manual Order Created', 'Order was manually created by admin.');

            // Create order items and deduct stock
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                $qty = (int) $item['quantity'];
                $variant = !empty($item['variant_id'])
                    ? ProductVariant::where('id', $item['variant_id'])->where('product_id', $product->id)->first()
                    : null;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'product_sku' => $variant?->sku ?: ($product->sku ?? ''),
                    'quantity' => $qty,
                    'price' => (float) $item['price'],
                    'total' => (float) $item['price'] * $qty,
                ]);

                // Deduct stock from the variant when one was selected —
                // Product::decrementStock() no-ops for products that have
                // active variants, so decrementing the parent here would
                // silently fail to reduce real available stock.
                if ($variant) {
                    $variant->decrementStock($qty);
                } else {
                    $product->decrementStock($qty);
                }
            }

            return $order;
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order #' . $order->order_number . ' created successfully.');
    }

    /**
     * AJAX product search for order creation.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));
        if (strlen($query) < 1) {
            return response()->json(['products' => []]);
        }

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->with(['images' => fn($q) => $q->orderByDesc('is_primary')->limit(1)])
            ->limit(15)
            ->get()
            ->map(function (Product $product) {
                $primaryImage = $product->images->first();
                $pricing = $product->resolveGlobalPricingSnapshot();
                $currentPrice = (float) ($pricing['current_price'] ?? $product->price ?? 0);
                $variants = [];

                if ($product->hasActiveVariants()) {
                    $variants = $product->variants()
                        ->where('is_active', true)
                        ->with('attributeValues.attribute')
                        ->get()
                        ->map(function ($v) use ($currentPrice) {
                            $variantPrice = (float) ($v->sale_price ?: $v->price ?: 0);
                            if ($variantPrice <= 0) {
                                $variantPrice = $currentPrice;
                            }
                            return [
                                'id' => $v->id,
                                'sku' => $v->sku,
                                'price' => $variantPrice,
                                'stock' => (int) $v->stock_quantity,
                                'label' => $v->attributeValues->map(fn($av) => $av->value)->join(' / '),
                            ];
                        });
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $currentPrice,
                    'stock' => (int) $product->stock_quantity,
                    'image' => $primaryImage?->url,
                    'variants' => $variants,
                ];
            });

        return response()->json(['products' => $products]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $oldStatus = $order->status;
        $newStatus = $request->validated()['status'];

        if ((string) $oldStatus === (string) $newStatus) {
            if ($request->ajax()) {
                return response()->json(['error' => "Cannot change status from {$oldStatus} to {$newStatus}."]);
            }
            return back()->with('error', "Cannot change status from {$oldStatus} to {$newStatus}.");
        }

        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            $this->restoreOrderStock($order);
        }

        $updateData = ['status' => $newStatus];
        if ($newStatus === 'delivered') {
            $updateData['delivered_at'] = now();
        } elseif ($oldStatus === 'delivered' && $newStatus !== 'delivered') {
            $updateData['delivered_at'] = null;
        }
        $order->update($updateData);

        // Award loyalty points once delivered — mirrors OrderService::updateOrderStatus.
        // awardOrderPoints() is idempotent per order (checks for an existing
        // LoyaltyTransaction), so it's safe even if both paths ever fire for
        // the same order.
        if ($newStatus === 'delivered' && $order->user_id) {
            app(\App\Services\LoyaltyService::class)->awardOrderPoints($order);
        }

        // Log status change
        $oldLabel = OrderStatus::where('key', $oldStatus)->value('label') ?? ucfirst($oldStatus);
        $newLabel = OrderStatus::where('key', $newStatus)->value('label') ?? ucfirst($newStatus);
        
        $reason = $request->input('cancel_reason');
        $logMessage = "Status changed: {$oldLabel} → {$newLabel}";
        if ($newStatus === 'cancelled' && $reason) {
            $logMessage .= " (Reason: {$reason})";
        }

        $logMetadata = [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ];
        if ($newStatus === 'cancelled' && $reason) {
            $logMetadata['reason'] = $reason;
            $this->saveNewCancelReason($reason);
        }

        OrderActivityLog::log($order, 'status_change', $logMessage, null, $logMetadata);

        // Send SMS notification
        try {
            $smsResult = app(SmsService::class)->sendOrderStatusSms($order, $newStatus);
            if ($smsResult['success']) {
                OrderActivityLog::log($order, 'sms_sent', "SMS sent: Status → {$newLabel}", $smsResult['message'] ?? null, [
                    'status' => $newStatus,
                    'phone' => $order->shipping_phone,
                ]);
            } elseif (str_contains($smsResult['message'] ?? '', 'not enabled')) {
                // SMS not enabled for this status — don't log
            } else {
                OrderActivityLog::log($order, 'sms_failed', 'SMS failed', $smsResult['message'] ?? null, [
                    'status' => $newStatus,
                    'error' => $smsResult['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Order SMS failed', ['order_id' => $order->id, 'status' => $newStatus, 'error' => $e->getMessage()]);
            OrderActivityLog::log($order, 'sms_failed', 'SMS failed (exception)', $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'new_status' => $newStatus,
                'new_label' => $newLabel,
                'new_color' => OrderStatus::where('key', $newStatus)->value('color') ?? '#6C757D'
            ]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }

    /**
     * Send a custom or template SMS from the order details page.
     */
    public function sendSms(Request $request, Order $order)
    {
        $validated = $request->validate([
            'sms_message' => ['required', 'string', 'max:500'],
        ]);

        $phone = $order->shipping_phone ?? '';
        if ($phone === '') {
            return back()->with('error', 'No customer phone number found for this order.');
        }

        try {
            $smsService = app(SmsService::class);
            $result = $smsService->send($phone, $validated['sms_message']);

            if ($result['success']) {
                OrderActivityLog::log($order, 'manual_sms', 'Manual SMS sent', $validated['sms_message'], [
                    'phone' => $phone,
                ]);
                return back()->with('success', 'SMS sent successfully to ' . $phone);
            } else {
                OrderActivityLog::log($order, 'sms_failed', 'Manual SMS failed', $result['message'] ?? 'Unknown error', [
                    'phone' => $phone,
                    'attempted_message' => $validated['sms_message'],
                ]);
                return back()->with('error', 'SMS failed: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            OrderActivityLog::log($order, 'sms_failed', 'Manual SMS exception', $e->getMessage());
            return back()->with('error', 'SMS error: ' . $e->getMessage());
        }
    }

    public function updateSource(Request $request, Order $order)
    {
        $request->validate([
            'order_source' => 'nullable|string|max:255',
        ]);

        $order->update([
            'order_source' => $request->input('order_source'),
        ]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order source updated successfully.');
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|string|in:pending,awaiting,paid,failed,refunded',
        ]);

        $oldStatus = $order->payment_status;
        $newStatus = $request->input('payment_status');

        if ($oldStatus === $newStatus) {
            return back();
        }

        $order->update([
            'payment_status' => $newStatus,
        ]);

        // Log the change
        OrderActivityLog::log($order, 'status_change', 'Payment Status Updated', "Payment status changed from {$oldStatus} to {$newStatus}.", [
            'old_payment_status' => $oldStatus,
            'new_payment_status' => $newStatus,
        ]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Payment status updated successfully.');
    }

    public function applyDiscount(Request $request, Order $order)
    {
        $request->validate([
            'discount_type' => 'required|in:fixed,percentage,coupon',
            'discount_value' => 'required|string',
        ]);

        $type = $request->input('discount_type');
        $value = $request->input('discount_value');
        $discountAmount = 0;
        $couponCode = null;

        if ($type === 'fixed') {
            $discountAmount = (float) $value;
        } elseif ($type === 'percentage') {
            $discountAmount = ($order->subtotal * (float) $value) / 100;
        } elseif ($type === 'coupon') {
            $coupon = \App\Models\Coupon::where('code', $value)->first();
            if (!$coupon) {
                return back()->with('error', 'Invalid coupon code.');
            }
            // Assume coupon has a discount_type of fixed or percentage, and discount_value
            // Let's use generic logic if fields exist, or fallback
            $couponType = $coupon->discount_type ?? ($coupon->type ?? 'fixed');
            $couponValue = (float) ($coupon->discount_value ?? ($coupon->value ?? 0));
            
            if ($couponType === 'fixed') {
                $discountAmount = $couponValue;
            } else {
                $discountAmount = ($order->subtotal * $couponValue) / 100;
            }
            $couponCode = $coupon->code;
        }

        // Ensure discount doesn't exceed subtotal
        if ($discountAmount > $order->subtotal) {
            $discountAmount = $order->subtotal;
        }

        $total = ($order->subtotal + $order->tax + $order->shipping) - $discountAmount;
        if ($total < 0) {
            $total = 0;
        }

        $order->update([
            'discount_amount' => $discountAmount,
            'coupon_code' => $couponCode,
            'total' => $total,
        ]);

        return back()->with('success', 'Discount applied successfully.');
    }

    public function removeDiscount(Order $order)
    {
        $total = ($order->subtotal + $order->tax + $order->shipping);
        
        $order->update([
            'discount_amount' => 0,
            'coupon_code' => null,
            'total' => $total,
        ]);

        return back()->with('success', 'Discount removed successfully.');
    }

    public function bulkAction(Request $request)
    {
        $activeStatusKeys = OrderStatus::query()
            ->where('is_active', true)
            ->pluck('key')
            ->all();

        $statusLabelMap = OrderStatus::query()
            ->pluck('label', 'key')
            ->toArray();

        if (empty($activeStatusKeys)) {
            $activeStatusKeys = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        }

        $allowedActions = array_merge($activeStatusKeys, ['trash', 'restore', 'force_delete', 'steadfast_send', 'pathao_send']);

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'distinct', Rule::exists('orders', 'id')],
            'bulk_action' => ['required', 'string', Rule::in($allowedActions)],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['bulk_action'] === 'steadfast_send') {
            return app(\App\Http\Controllers\Admin\SteadfastController::class)->sendBulk($request);
        }

        if ($validated['bulk_action'] === 'pathao_send') {
            return app(\App\Http\Controllers\Admin\PathaoController::class)->sendBulk($request);
        }

        $action = (string) $validated['bulk_action'];
        $orderIds = collect($validated['order_ids'])
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        $orders = Order::withTrashed()
            ->with('items.product')
            ->whereIn('id', $orderIds)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No valid orders were selected.');
        }

        // Orders placed after the license expired can't be bulk-acted-on
        // from the admin panel until renewal.
        $orders = $orders->reject(fn (Order $order) => $this->licenseService->isOrderLocked($order))->values();

        if ($orders->isEmpty()) {
            return back()->with('error', 'The selected order(s) were placed after your license expired. Renew your license to manage them.');
        }

        $updated = 0;
        $skipped = 0;

        $reason = $request->input('cancel_reason');
        if ($action === 'cancelled' && $reason) {
            $this->saveNewCancelReason($reason);
        }

        foreach ($orders as $order) {
            if (!$order instanceof Order) {
                $skipped++;
                continue;
            }

            if ($action === 'trash') {
                if ($order->trashed()) {
                    $skipped++;
                    continue;
                }

                $order->delete();
                $updated++;
                continue;
            }

            if ($action === 'restore') {
                if (!$order->trashed()) {
                    $skipped++;
                    continue;
                }

                $order->restore();
                $updated++;
                continue;
            }

            if ($action === 'force_delete') {
                if (!$order->trashed()) {
                    $skipped++;
                    continue;
                }

                $order->forceDelete();
                $updated++;
                continue;
            }

            if ($order->trashed()) {
                $skipped++;
                continue;
            }

            $oldStatus = $order->status;
            if ((string) $oldStatus === $action) {
                $skipped++;
                continue;
            }

            if ($action === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->restoreOrderStock($order);
            }

            $bulkUpdateData = ['status' => $action];
            if ($action === 'delivered') {
                $bulkUpdateData['delivered_at'] = now();
            } elseif ($oldStatus === 'delivered' && $action !== 'delivered') {
                $bulkUpdateData['delivered_at'] = null;
            }
            $order->update($bulkUpdateData);
            $updated++;

            if ($action === 'delivered' && $order->user_id) {
                app(\App\Services\LoyaltyService::class)->awardOrderPoints($order);
            }

            // Log status change
            $oldLabel = $statusLabelMap[$oldStatus] ?? ucfirst($oldStatus);
            $newLabel = $statusLabelMap[$action] ?? ucfirst($action);
            
            $logMessage = "Status changed: {$oldLabel} → {$newLabel} (Bulk Action)";
            if ($action === 'cancelled' && $reason) {
                $logMessage .= " (Reason: {$reason})";
            }

            $logMetadata = [
                'old_status' => $oldStatus,
                'new_status' => $action,
                'is_bulk' => true,
            ];
            if ($action === 'cancelled' && $reason) {
                $logMetadata['reason'] = $reason;
            }

            OrderActivityLog::log($order, 'status_change', $logMessage, null, $logMetadata);

            // Send SMS notification
            try {
                app(SmsService::class)->sendOrderStatusSms($order, $action);
            } catch (\Throwable $e) {
                \Log::warning('Bulk order SMS failed', ['order_id' => $order->id, 'status' => $action, 'error' => $e->getMessage()]);
            }
        }

        if ($updated === 0) {
            return back()->with('error', 'No selected orders could be updated.');
        }

        $actionLabel = match ($action) {
            'processing' => 'marked as Processing',
            'shipped' => 'marked as Shipped',
            'delivered' => 'marked as Delivered',
            'cancelled' => 'marked as Cancelled',
            'trash' => 'moved to Trash',
            'restore' => 'restored',
            default => 'marked as ' . ($statusLabelMap[$action] ?? ucfirst(str_replace('_', ' ', $action))),
        };

        $message = "{$updated} order(s) {$actionLabel}.";

        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }

        return back()->with('success', $message);
    }

    public function updateCustomerInfo(Request $request, Order $order)
    {
        $validated = $request->validate([
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:20'],
            'shipping_email' => ['nullable', 'email', 'max:255'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_district_id' => ['nullable', 'integer', 'exists:bd_districts,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $changes = [];

        if ($order->shipping_name !== $validated['shipping_name']) {
            $changes[] = "Name: {$order->shipping_name} → {$validated['shipping_name']}";
        }
        if ($order->shipping_phone !== $validated['shipping_phone']) {
            $changes[] = "Phone: {$order->shipping_phone} → {$validated['shipping_phone']}";
        }
        if (($order->shipping_email ?? '') !== ($validated['shipping_email'] ?? '')) {
            $changes[] = "Email updated";
        }
        if ($order->shipping_address !== ($validated['shipping_address'] ?? '')) {
            $changes[] = "Address updated";
        }
        if (($order->shipping_city ?? '') !== ($validated['shipping_city'] ?? '')) {
            $changes[] = "City updated";
        }
        if (($order->notes ?? '') !== ($validated['notes'] ?? '')) {
            $changes[] = "Notes updated";
        }

        $updateData = [
            'shipping_name' => $validated['shipping_name'],
            'shipping_phone' => $validated['shipping_phone'],
            'shipping_email' => $validated['shipping_email'] ?? $order->shipping_email,
            'shipping_address' => $validated['shipping_address'] ?? $order->shipping_address,
            'shipping_city' => $validated['shipping_city'] ?? $order->shipping_city,
            'notes' => $validated['notes'] ?? $order->notes,
        ];

        // Handle district change with shipping cost recalculation
        $newDistrictId = isset($validated['shipping_district_id']) ? (int) $validated['shipping_district_id'] : null;
        $oldDistrictId = $order->shipping_district_id ? (int) $order->shipping_district_id : null;

        if ($newDistrictId && $newDistrictId !== $oldDistrictId) {
            $newDistrict = BdDistrict::with('division')->find($newDistrictId);
            $oldDistrictName = $order->shippingDistrict?->name ?? 'N/A';

            if ($newDistrict) {
                $updateData['shipping_district_id'] = $newDistrictId;
                $updateData['shipping_division_id'] = $newDistrict->division_id;

                // Find the shipping rate for this district
                $newShippingCost = $this->resolveDistrictShippingRate($newDistrictId, $order->shipping_method);

                if ($newShippingCost !== null) {
                    $oldShipping = (float) $order->shipping;
                    $updateData['shipping'] = $newShippingCost;

                    // Recalculate total
                    $total = (float) $order->subtotal + $newShippingCost + (float) $order->tax
                           - (float) $order->discount_amount - (float) $order->loyalty_discount_amount;
                    $updateData['total'] = max(0, $total);

                    $changes[] = "District: {$oldDistrictName} → {$newDistrict->name}";
                    $changes[] = "Shipping: ৳" . number_format($oldShipping, 2) . " → ৳" . number_format($newShippingCost, 2);
                } else {
                    $changes[] = "District: {$oldDistrictName} → {$newDistrict->name} (shipping rate unchanged)";
                }
            }
        }

        // Also update checkout_fields_payload if it exists
        $payload = is_array($order->checkout_fields_payload) ? $order->checkout_fields_payload : [];
        $payload['shipping_name'] = $validated['shipping_name'];
        $payload['shipping_phone'] = $validated['shipping_phone'];
        if (isset($validated['shipping_email'])) {
            $payload['shipping_email'] = $validated['shipping_email'];
        }
        if (isset($validated['shipping_address'])) {
            $payload['shipping_address'] = $validated['shipping_address'];
        }
        if ($newDistrictId) {
            $payload['shipping_district_id'] = (string) $newDistrictId;
        }
        
        // Additional payload fields
        if ($request->has('shipping_location_text')) {
            $payload['shipping_location_text'] = $request->input('shipping_location_text');
        }
        if ($request->has('shipping_area')) {
            $payload['shipping_area'] = $request->input('shipping_area');
        }
        
        $updateData['checkout_fields_payload'] = $payload;

        $order->update($updateData);

        if (!empty($changes)) {
            OrderActivityLog::log(
                $order,
                'status_change',
                'Customer Info Updated',
                implode("\n", $changes)
            );
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Customer information updated successfully.');
    }

    public function updateItems(Request $request, Order $order)
    {
        if (in_array($order->status, ['shipped', 'delivered', 'cancelled', 'returned', 'refunded', 'completed', 'failed'])) {
            return back()->with('error', 'Cannot edit items for an order in this status.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $order) {
            // Restore stock for old items — variant-tracked stock lives on
            // the variant row, not the parent product.
            $order->loadMissing('items.product', 'items.variant');
            foreach ($order->items as $oldItem) {
                if ($oldItem->variant) {
                    $oldItem->variant->incrementStock((int) $oldItem->quantity);
                } elseif ($oldItem->product) {
                    $oldItem->product->incrementStock((int) $oldItem->quantity);
                }
            }

            // Delete old items
            $order->items()->delete();

            // Create new items and deduct stock
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                $qty = (int) $item['quantity'];
                $price = (float) $item['price'];
                $subtotal += $price * $qty;
                $variant = !empty($item['variant_id'])
                    ? ProductVariant::where('id', $item['variant_id'])->where('product_id', $product->id)->first()
                    : null;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'product_sku' => $variant?->sku ?: ($product->sku ?? ''),
                    'quantity' => $qty,
                    'price' => $price,
                    'total' => $price * $qty,
                ]);

                // Deduct stock from the variant when one was selected — see
                // note in store() above.
                if ($variant) {
                    $variant->decrementStock($qty);
                } else {
                    $product->decrementStock($qty);
                }
            }

            // Recalculate order total
            $total = max(0, $subtotal + (float) $order->shipping + (float) $order->tax - (float) $order->discount_amount - (float) $order->loyalty_discount_amount);

            $order->update([
                'subtotal' => $subtotal,
                'total' => $total,
            ]);

            OrderActivityLog::log(
                $order,
                'status_change',
                'Order Items Updated',
                'Order items were modified by admin.'
            );
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order items updated successfully.');
    }

    public function getDistrictShippingRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => ['required', 'integer', 'exists:bd_districts,id'],
            'shipping_method' => ['nullable', 'string'],
        ]);

        $districtId = (int) $validated['district_id'];
        $shippingMethodName = $validated['shipping_method'] ?? null;
        $rate = $this->resolveDistrictShippingRate($districtId, $shippingMethodName);
        $district = BdDistrict::find($districtId);

        return response()->json([
            'success' => true,
            'district_name' => $district?->name,
            'rate' => $rate,
            'formatted_rate' => $rate !== null ? '৳' . number_format($rate, 2) : null,
        ]);
    }

    private function resolveDistrictShippingRate(int $districtId, ?string $shippingMethodName): ?float
    {
        // First, get all ACTIVE methods that are valid for this district
        $availableMethods = ShippingMethod::with(['locationRules', 'districtRates'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(function ($method) use ($districtId) {
                return $method->isAvailableForLocation(null, $districtId, null);
            });

        if ($availableMethods->isEmpty()) {
            return null; // No shipping method available for this district
        }

        // Check if the current shipping method is still valid for this new district
        if ($shippingMethodName) {
            $currentMethod = $availableMethods->first(function($m) use ($shippingMethodName) {
                return $m->code === $shippingMethodName || $m->name === $shippingMethodName;
            });

            if ($currentMethod) {
                $rate = $currentMethod->getDistrictRate($districtId);
                return $rate !== null ? $rate : (float) $currentMethod->base_cost;
            }
        }

        // We want to prioritize methods that have specific location rules over global ones
        $specificMethod = $availableMethods->first(function ($m) {
            return $m->locationRules->isNotEmpty();
        });

        $methodToUse = $specificMethod ?: $availableMethods->first();

        if ($methodToUse) {
            $rate = $methodToUse->getDistrictRate($districtId);
            return $rate !== null ? (float) $rate : (float) $methodToUse->base_cost;
        }

        return null;
    }

    private function restoreOrderStock(Order $order): void
    {
        $order->loadMissing('items.product', 'items.variant');

        foreach ($order->items as $item) {
            // Variant-tracked stock lives on the variant row, not the parent
            // product — Product::incrementStock() no-ops for products with
            // active variants, so restoring only the product silently loses
            // the stock for variant items.
            if ($item->variant) {
                $item->variant->incrementStock($item->quantity);
                continue;
            }

            if ($item->product) {
                $item->product->incrementStock($item->quantity);
            }
        }
    }

    private function saveNewCancelReason(?string $reason): void
    {
        $reason = trim((string) $reason);
        if (!$reason) return;

        $existingReasonsStr = \App\Models\Setting::getValue('general', 'cancellation_reasons', 'Out of Stock,Customer Request,Fraudulent,Payment Failed,Other');
        $existingReasons = array_filter(array_map('trim', explode(',', $existingReasonsStr)));
        
        $found = false;
        foreach ($existingReasons as $er) {
            if (strtolower($er) === strtolower($reason)) {
                $found = true; break;
            }
        }
        
        if (!$found) {
            $existingReasons = array_filter($existingReasons, fn($r) => strtolower($r) !== 'other');
            $existingReasons[] = $reason;
            $existingReasons[] = 'Other';
            \App\Models\Setting::setValue('general', 'cancellation_reasons', implode(',', $existingReasons));
        }
    }

    public function restore(Order $order)
    {
        if ($order->trashed()) {
            $order->restore();
            return back()->with('success', 'Order restored successfully.');
        }

        return back()->with('info', 'Order is not in trash.');
    }

    public function refund(Request $request, Order $order)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0.01|max:' . $order->total,
            'reason' => 'nullable|string|max:255',
        ]);

        if ($order->payment_method !== 'bkash') {
            return back()->with('error', 'Only bKash payments can be refunded through this option.');
        }

        if ($order->payment_status !== 'paid') {
            return back()->with('error', 'Only paid orders can be refunded.');
        }

        $paymentId = $order->bkash_payment_id;
        $transactionId = $order->transaction_id;

        if (!$paymentId || !$transactionId) {
            return back()->with('error', 'Missing bKash payment or transaction ID for this order.');
        }

        $amount = $request->amount ?? (float) $order->total;
        $reason = $request->reason ?? 'Admin initiated refund';

        try {
            $bkashService = app(\App\Services\Payment\BkashPaymentService::class);
            $result = $bkashService->refund($paymentId, $transactionId, $amount, $reason);

            if ($result['success']) {
                $order->update([
                    'payment_status' => $amount >= (float) $order->total ? 'refunded' : 'partially_refunded',
                ]);

                return back()->with('success', 'Refund processed successfully. Refund Trx ID: ' . ($result['refund_transaction_id'] ?? 'N/A'));
            }

            return back()->with('error', 'Refund failed: ' . ($result['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            return back()->with('error', 'Refund error: ' . $e->getMessage());
        }
    }

}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
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

        if ($view === 'all' && $request->filled('status')) {
            $requestedStatus = (string) $request->input('status');
            if (in_array($requestedStatus, $statusKeys, true)) {
                $view = $requestedStatus;
            }
        }

        $query = Order::with(['user', 'payment', 'statusConfig'])
            ->withCount('items')
            ->orderByDesc('created_at');

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

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->input('date') === 'today') {
            $query->whereDate('created_at', \Carbon\Carbon::today());
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $orders = $query->paginate($perPage)->withQueryString();

        $filterCounts = ['all' => Order::query()->count()];
        foreach ($statusKeys as $statusKey) {
            $filterCounts[$statusKey] = Order::query()->where('status', $statusKey)->count();
        }
        $filterCounts['trash'] = Order::onlyTrashed()->count();

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

        return view('admin.orders.index', compact(
            'orders', 'view', 'statuses', 'filterCounts', 'bulkStatuses',
            'orderSources', 'paymentMethods', 'paymentStatuses'
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

        return view('admin.orders.show', compact('order', 'availableStatuses', 'activeCoupons', 'smsTemplates'));
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

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '',
                    'quantity' => $qty,
                    'price' => (float) $item['price'],
                    'total' => (float) $item['price'] * $qty,
                ]);

                // Deduct stock
                if ($product->stock_quantity !== null) {
                    $product->decrement('stock_quantity', $qty);
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
            return back()->with('error', "Cannot change status from {$oldStatus} to {$newStatus}.");
        }

        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            $this->restoreOrderStock($order);
        }

        $order->update(['status' => $newStatus]);

        // Log status change
        $oldLabel = OrderStatus::where('key', $oldStatus)->value('label') ?? ucfirst($oldStatus);
        $newLabel = OrderStatus::where('key', $newStatus)->value('label') ?? ucfirst($newStatus);
        OrderActivityLog::log($order, 'status_change', "Status changed: {$oldLabel} → {$newLabel}", null, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

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

        $allowedActions = array_merge($activeStatusKeys, ['trash', 'restore']);

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'distinct', Rule::exists('orders', 'id')],
            'bulk_action' => ['required', 'string', Rule::in($allowedActions)],
        ]);

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

        $updated = 0;
        $skipped = 0;

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

            if ($order->trashed()) {
                $skipped++;
                continue;
            }

            if ((string) $order->status === $action) {
                $skipped++;
                continue;
            }

            if ($action === 'cancelled' && $order->status !== 'cancelled') {
                $this->restoreOrderStock($order);
            }

            $order->update(['status' => $action]);
            $updated++;

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

    private function restoreOrderStock(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->incrementStock($item->quantity);
            }
        }
    }

}

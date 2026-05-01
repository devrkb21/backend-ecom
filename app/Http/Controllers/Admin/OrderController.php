<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
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
        ]);

        $availableStatuses = OrderStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $activeCoupons = \App\Models\Coupon::active()->get();

        return view('admin.orders.show', compact('order', 'availableStatuses', 'activeCoupons'));
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

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
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

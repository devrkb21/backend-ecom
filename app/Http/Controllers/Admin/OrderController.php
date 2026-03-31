<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['user', 'payment'])
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'payment']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $oldStatus = $order->status;
        $newStatus = $request->validated()['status'];

        // Validate status transitions
        $validTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            return back()->with('error', "Cannot change status from {$oldStatus} to {$newStatus}.");
        }

        // Restore stock if cancelling
        if ($newStatus === 'cancelled') {
            foreach ($order->items as $item) {
                $item->product->incrementStock($item->quantity);
            }
        }

        $order->update(['status' => $newStatus]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }
}

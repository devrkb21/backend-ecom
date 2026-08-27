<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderShipped;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    /**
     * Show tracking form for an order
     */
    public function edit(Order $order): View
    {
        $order->load('trackingHistory', 'user', 'items.product');

        $carriers = [
            'pathao' => 'Pathao',
            'steadfast' => 'Steadfast',
            'redx' => 'REDX',
            'paperfly' => 'Paperfly',
            'sundarban' => 'Sundarban Courier',
            'dhl' => 'DHL',
            'fedex' => 'FedEx',
            'ups' => 'UPS',
            'other' => 'Other',
        ];

        return view('admin.orders.tracking', compact('order', 'carriers'));
    }

    /**
     * Update tracking information
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|max:100',
            'carrier' => 'required|string|max:50',
            'carrier_tracking_url' => 'nullable|url|max:500',
            'estimated_delivery_at' => 'nullable|date',
        ]);

        $oldTrackingNumber = $order->tracking_number;

        $order->update([
            'tracking_number' => $validated['tracking_number'],
            'carrier' => $validated['carrier'],
            'carrier_tracking_url' => $validated['carrier_tracking_url']
                ?? $order->generateTrackingUrl($validated['tracking_number'], $validated['carrier']),
            'estimated_delivery_at' => $validated['estimated_delivery_at'],
            'shipped_at' => $order->shipped_at ?? now(),
        ]);

        // Add tracking event
        if (! $oldTrackingNumber) {
            $order->addTrackingEvent(
                'shipped',
                "Package shipped via {$validated['carrier']}. Tracking number: {$validated['tracking_number']}"
            );

            // Send shipment notification if status is being set to shipped
            if ($order->status !== 'shipped') {
                $order->update(['status' => 'shipped']);
            }

            $order->user->notify(new OrderShipped(
                $order,
                $validated['tracking_number'],
                $validated['carrier']
            ));
        } else {
            $order->addTrackingEvent(
                'tracking_updated',
                "Tracking information updated. New tracking number: {$validated['tracking_number']}"
            );
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Tracking information updated successfully.');
    }

    /**
     * Add a tracking event
     */
    public function addEvent(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:200',
            'occurred_at' => 'nullable|date',
        ]);

        $order->addTrackingEvent(
            $validated['status'],
            $validated['description'],
            $validated['location'],
            null,
            $validated['occurred_at'] ? new \DateTime($validated['occurred_at']) : now()
        );

        // Update main order status if certain events are added
        $statusMap = [
            'processing' => 'processing',
            'shipped' => 'shipped',
            'out_for_delivery' => 'shipped',
            'delivered' => 'delivered',
        ];

        if (isset($statusMap[$validated['status']]) && $order->status !== $statusMap[$validated['status']]) {
            $order->update(['status' => $statusMap[$validated['status']]]);
        }

        return redirect()
            ->back()
            ->with('success', 'Tracking event added successfully.');
    }

    /**
     * Delete a tracking event
     */
    public function deleteEvent(Order $order, int $eventId): RedirectResponse
    {
        $event = $order->trackingHistory()->findOrFail($eventId);
        $event->delete();

        return redirect()
            ->back()
            ->with('success', 'Tracking event deleted.');
    }

    /**
     * Mark order as delivered
     */
    public function markDelivered(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_notes' => 'nullable|string|max:500',
        ]);

        $order->markAsDelivered($validated['delivery_notes'] ?? null);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order marked as delivered.');
    }
}

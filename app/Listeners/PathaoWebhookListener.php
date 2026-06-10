<?php

namespace App\Listeners;

use App\Models\Order;
use App\Models\OrderActivityLog;
use devrkb21\PathaoLaravel\Events\PathaoWebhookReceived;
use Illuminate\Support\Facades\Log;

class PathaoWebhookListener
{
    /**
     * Handle the event.
     *
     * @param  PathaoWebhookReceived  $event
     * @return void
     */
    public function handle(PathaoWebhookReceived $event)
    {
        $payload = $event->payload;
        Log::info('Pathao Webhook Listener Triggered', ['payload' => $payload]);

        $consignmentId = $payload['consignment_id'] ?? null;
        $orderNumber = $payload['merchant_order_id'] ?? null;
        $eventName = $payload['event'] ?? null;

        if (!$consignmentId && !$orderNumber) {
            Log::warning('Pathao Webhook missing consignment_id and merchant_order_id.');
            return;
        }

        // Find the order
        $order = Order::query()
            ->when($consignmentId, function ($q) use ($consignmentId) {
                $q->where('tracking_number', $consignmentId);
            })
            ->when(!$consignmentId && $orderNumber, function ($q) use ($orderNumber) {
                $q->where('order_number', $orderNumber);
            })
            ->first();

        if (!$order) {
            Log::warning('Pathao Webhook: Order not found.', [
                'consignment_id' => $consignmentId,
                'merchant_order_id' => $orderNumber
            ]);
            return;
        }

        $oldStatus = $order->status;
        $newStatus = $oldStatus;
        $paymentStatus = $order->payment_status;
        $orderUpdated = false;
        $statusName = '';

        // Extract message/details from payload
        $reason = $payload['reason'] ?? '';
        $occurredAt = isset($payload['updated_at']) ? \Carbon\Carbon::parse($payload['updated_at']) : now();

        // Map events to statuses
        switch ($eventName) {
            case 'order.created':
            case 'order.updated':
            case 'order.pickup-requested':
            case 'order.assigned-for-pickup':
                if (in_array($oldStatus, ['pending'])) {
                    $newStatus = 'processing';
                    $orderUpdated = true;
                    $statusName = 'Processing';
                }
                break;

            case 'order.picked':
            case 'order.at-the-sorting-hub':
                if (in_array($oldStatus, ['pending', 'processing'])) {
                    $newStatus = 'shipped';
                    $orderUpdated = true;
                    $statusName = 'Shipped';
                }
                break;

            case 'order.in-transit':
            case 'order.received-at-last-mile-hub':
                // Keeping status as shipped, but tracking will be updated
                break;

            case 'order.assigned-for-delivery':
                // Keeping status as shipped, tracking updated
                break;

            case 'order.delivered':
                if ($oldStatus !== 'delivered') {
                    $newStatus = 'delivered';
                    $orderUpdated = true;
                    $statusName = 'Delivered';
                }
                break;

            case 'order.partial-delivery':
                if ($oldStatus !== 'delivered') {
                    $newStatus = 'delivered';
                    $orderUpdated = true;
                    $statusName = 'Partial Delivered';
                    // Update notes/COD info if relevant
                    $collectedAmount = $payload['collected_amount'] ?? null;
                    if ($collectedAmount !== null) {
                        $order->notes = trim(($order->notes ?: '') . " (Partial Delivery: Collected {$collectedAmount})");
                    }
                }
                break;

            case 'order.returned':
            case 'order.returned-to-merchant':
            case 'order.paid-return':
                if ($oldStatus !== 'returned') {
                    $newStatus = 'returned';
                    $orderUpdated = true;
                    $statusName = 'Returned';
                }
                break;

            case 'order.delivery-failed':
            case 'order.pickup-failed':
                // Custom or log failure
                break;

            case 'order.pickup-cancelled':
                if ($oldStatus !== 'cancelled') {
                    $newStatus = 'cancelled';
                    $orderUpdated = true;
                    $statusName = 'Cancelled';
                }
                break;

            case 'order.on-hold':
                // Kept on hold, tracking updated
                break;

            case 'order.paid':
                // strictly update order payment status to paid
                if ($paymentStatus !== 'paid') {
                    $paymentStatus = 'paid';
                    $order->update(['payment_status' => 'paid']);
                    OrderActivityLog::log($order, 'status_change', "Order payment status updated to Paid via Pathao webhook event 'order.paid'.");
                }
                break;
        }

        if ($orderUpdated) {
            $order->update([
                'status' => $newStatus,
                'delivered_at' => ($newStatus === 'delivered') ? now() : $order->delivered_at,
            ]);

            OrderActivityLog::log(
                $order,
                'status_change',
                "Order status updated to {$statusName} via Pathao webhook (Event: {$eventName})"
            );
        }

        // Determine tracking event status category
        $trackingEventStatus = 'in_transit';
        if ($eventName === 'order.delivered' || $eventName === 'order.partial-delivery') {
            $trackingEventStatus = 'delivered';
        } elseif ($eventName === 'order.pickup-cancelled' || $eventName === 'order.delivery-failed' || $eventName === 'order.pickup-failed') {
            $trackingEventStatus = 'exception';
        } elseif ($eventName === 'order.returned' || $eventName === 'order.returned-to-merchant' || $eventName === 'order.paid-return') {
            $trackingEventStatus = 'exception';
        }

        $description = "Pathao Status: {$eventName}";
        if ($reason) {
            $description .= " (Reason: {$reason})";
        }

        $order->addTrackingEvent(
            $trackingEventStatus,
            $description,
            null,
            $eventName,
            $occurredAt
        );
    }
}

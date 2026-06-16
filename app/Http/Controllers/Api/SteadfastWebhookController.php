<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class SteadfastWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from SteadFast Courier / Packzy.
     */
    public function handle(Request $request)
    {
        // 1. Authenticate Request via Bearer Token
        $expectedToken = Setting::getValue('courier', 'steadfast_webhook_token');
        
        if (!empty($expectedToken)) {
            $authHeader = $request->header('Authorization');
            if ($authHeader !== 'Bearer ' . $expectedToken) {
                Log::warning('SteadFast Webhook Unauthorized Request', [
                    'ip' => $request->ip(),
                    'auth_header' => $authHeader
                ]);
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
        }

        $payload = $request->all();
        Log::info('SteadFast Webhook Received', ['payload' => $payload]);

        $notificationType = $request->input('notification_type');
        $consignmentId = $request->input('consignment_id');
        $invoice = $request->input('invoice');

        if (!$consignmentId || !$invoice) {
            return response()->json(['status' => 'error', 'message' => 'Invalid consignment ID or invoice.'], 400);
        }

        // Find the order
        $order = Order::where('tracking_number', $consignmentId)
            ->orWhere('order_number', $invoice)
            ->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        // Handle Delivery Status Update
        if ($notificationType === 'delivery_status') {
            $status = $request->input('status');
            
            if (!$status) {
                return response()->json(['status' => 'error', 'message' => 'Missing status field in delivery_status webhook.'], 400);
            }

            $newStatus = strtolower(trim($status));
            $oldStatus = $order->status;
            $orderUpdated = false;
            $statusName = ucfirst($newStatus);

            switch ($newStatus) {
                case 'pending':
                case 'in_review':
                case 'in_transit':
                    if (in_array($oldStatus, ['processing', 'pending'])) {
                        $order->update(['status' => 'shipped']);
                        $orderUpdated = true;
                        $statusName = 'Shipped';
                    }
                    break;

                case 'delivered':
                case 'success':
                case 'partial_delivered':
                    if ($oldStatus !== 'delivered') {
                        $order->update([
                            'status' => 'delivered',
                            'delivered_at' => now(),
                            'payment_status' => 'paid', // Assuming COD is collected on delivery
                        ]);
                        $orderUpdated = true;
                        $statusName = 'Delivered';
                    }
                    break;

                case 'returned':
                case 'return':
                    if ($oldStatus !== 'returned') {
                        $order->update(['status' => 'returned']);
                        $orderUpdated = true;
                        $statusName = 'Returned';
                    }
                    break;

                case 'cancelled':
                case 'cancel':
                    if ($oldStatus !== 'cancelled') {
                        $order->update(['status' => 'cancelled']);
                        $orderUpdated = true;
                        $statusName = 'Cancelled';
                    }
                    break;
            }

            $trackingMessage = $request->input('tracking_message', "Package marked as {$statusName}");
            $updatedAtString = $request->input('updated_at');
            $occurredAt = $updatedAtString ? \Carbon\Carbon::parse($updatedAtString) : now();

            if ($orderUpdated) {
                \App\Models\OrderActivityLog::log(
                    $order,
                    'status_change',
                    "Order status updated to {$statusName} via SteadFast webhook (Status: {$status})"
                );

                // Send automatic SMS notification for status change
                app(\App\Services\SmsService::class)->sendOrderStatusSms($order, $order->status);
            }

            // Always add tracking event for delivery_status
            $trackingEventStatus = 'in_transit';
            if (in_array($newStatus, ['delivered', 'partial_delivered'])) {
                $trackingEventStatus = 'delivered';
            } elseif ($newStatus === 'cancelled') {
                $trackingEventStatus = 'exception';
            }

            $order->addTrackingEvent(
                $trackingEventStatus,
                $trackingMessage,
                null,
                $status,
                $occurredAt
            );

        } elseif ($notificationType === 'tracking_update') {
            $trackingMessage = $request->input('tracking_message', 'No message');
            $updatedAtString = $request->input('updated_at');
            $occurredAt = $updatedAtString ? \Carbon\Carbon::parse($updatedAtString) : now();

            \App\Models\OrderActivityLog::log(
                $order,
                'tracking_update',
                "SteadFast Tracking Update: {$trackingMessage}"
            );

            $order->addTrackingEvent(
                'tracking_updated',
                $trackingMessage,
                null,
                null,
                $occurredAt
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received successfully.'
        ], 200);
    }
}

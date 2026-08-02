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
        // 1. Authenticate Request via Bearer Token.
        // Fail CLOSED: an unconfigured token must reject every request, not
        // let them all through. Order numbers are visible to customers in
        // confirmation emails/URLs, so a skipped check here lets anyone mark
        // any order "delivered"/paid by guessing/knowing an order number.
        $expectedToken = Setting::getValue('courier', 'steadfast_webhook_token');
        $authHeader = (string) $request->header('Authorization', '');

        if (empty($expectedToken) || !hash_equals('Bearer ' . $expectedToken, $authHeader)) {
            Log::warning('SteadFast Webhook Unauthorized Request', [
                'ip' => $request->ip(),
                'token_configured' => !empty($expectedToken),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        Log::info('SteadFast Webhook Received', [
            'notification_type' => $request->input('notification_type'),
            'consignment_id' => $request->input('consignment_id'),
            'invoice' => $request->input('invoice'),
            'status' => $request->input('status'),
        ]);

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

                default:
                    // Unrecognized status from the courier — don't silently
                    // drop it, flag it so ops can add a mapping if it's a
                    // real new status code.
                    Log::warning('SteadFast Webhook: unrecognized delivery status', [
                        'order_id' => $order->id,
                        'status' => $newStatus,
                    ]);
                    break;
            }

            $trackingMessage = $request->input('tracking_message', "Package marked as {$statusName}");
            $updatedAtString = $request->input('updated_at');
            $occurredAt = $updatedAtString ? \Carbon\Carbon::parse($updatedAtString) : now();

            // Determine the internal status key for SMS template lookup
            $internalStatusKey = match (true) {
                in_array($newStatus, ['pending', 'in_review', 'in_transit']) => 'shipped',
                in_array($newStatus, ['delivered', 'success', 'partial_delivered']) => 'delivered',
                in_array($newStatus, ['returned', 'return']) => 'returned',
                in_array($newStatus, ['cancelled', 'cancel']) => 'cancelled',
                default => $newStatus,
            };

            if ($orderUpdated) {
                \App\Models\OrderActivityLog::log(
                    $order,
                    'status_change',
                    "Order status updated to {$statusName} via SteadFast webhook (Status: {$status})"
                );

                // Send automatic SMS notification for status change
                try {
                    $smsResult = app(\App\Services\SmsService::class)->sendOrderStatusSms($order, $internalStatusKey);
                    if ($smsResult['success']) {
                        \App\Models\OrderActivityLog::log($order, 'sms_sent', "SMS sent: Status → {$statusName} (via SteadFast webhook)", $smsResult['message'] ?? null, [
                            'status' => $internalStatusKey,
                            'phone' => $order->shipping_phone,
                        ]);
                    } elseif (!str_contains($smsResult['message'] ?? '', 'not enabled')) {
                        \App\Models\OrderActivityLog::log($order, 'sms_failed', 'SMS failed (via SteadFast webhook)', $smsResult['message'] ?? null, [
                            'status' => $internalStatusKey,
                            'error' => $smsResult['message'] ?? 'Unknown error',
                        ]);
                    }
                    Log::info('SteadFast Webhook SMS Result', [
                        'order_id' => $order->id,
                        'status' => $internalStatusKey,
                        'sms_success' => $smsResult['success'],
                        'sms_message' => $smsResult['message'] ?? null,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('SteadFast Webhook SMS Exception', [
                        'order_id' => $order->id,
                        'status' => $internalStatusKey,
                        'error' => $e->getMessage(),
                    ]);
                    \App\Models\OrderActivityLog::log($order, 'sms_failed', 'SMS failed (exception via SteadFast webhook)', $e->getMessage());
                }
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

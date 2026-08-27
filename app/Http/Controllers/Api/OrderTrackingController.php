<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderTrackingResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    /**
     * Track order by order number (public access)
     */
    public function trackByOrderNumber(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return $this->getTrackingResponse($order);
    }

    /**
     * Track order by tracking number (public access)
     */
    public function trackByTrackingNumber(string $trackingNumber): JsonResponse
    {
        $order = Order::where('tracking_number', $trackingNumber)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found with this tracking number',
            ], 404);
        }

        return $this->getTrackingResponse($order);
    }

    /**
     * Get tracking details for user's order
     */
    public function show(Request $request, int $orderId): JsonResponse
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return $this->getTrackingResponse($order, true);
    }

    /**
     * Generate tracking response
     */
    protected function getTrackingResponse(Order $order, bool $includePrivate = false): JsonResponse
    {
        $trackingHistory = $order->trackingHistory()
            ->orderBy('occurred_at', 'asc')
            ->get();

        $response = [
            'success' => true,
            'data' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'progress' => $order->tracking_progress,
                'tracking_number' => $order->tracking_number,
                'carrier' => $order->carrier,
                'carrier_tracking_url' => $order->carrier_tracking_url,
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'shipping_city' => $order->shipping_city,
                'shipping_country' => $order->shipping_country,
                'timeline' => $this->buildTimeline($order, $trackingHistory),
                'history' => OrderTrackingResource::collection($trackingHistory),
            ],
        ];

        if ($includePrivate) {
            $response['data']['shipping_name'] = $order->shipping_name;
            $response['data']['shipping_address'] = $order->shipping_address;
            $response['data']['shipping_state'] = $order->shipping_state;
            $response['data']['shipping_zip'] = $order->shipping_zip;
        }

        return response()->json($response);
    }

    /**
     * Build visual timeline for tracking
     */
    protected function buildTimeline(Order $order, $trackingHistory): array
    {
        $timeline = [
            [
                'status' => 'order_placed',
                'label' => 'Order Placed',
                'icon' => '📝',
                'completed' => true,
                'date' => $order->created_at->toIso8601String(),
            ],
            [
                'status' => 'processing',
                'label' => 'Processing',
                'icon' => '⚙️',
                'completed' => in_array($order->status, ['processing', 'shipped', 'delivered']),
                'date' => null,
            ],
            [
                'status' => 'shipped',
                'label' => 'Shipped',
                'icon' => '🚚',
                'completed' => in_array($order->status, ['shipped', 'delivered']),
                'date' => $order->shipped_at?->toIso8601String(),
            ],
            [
                'status' => 'delivered',
                'label' => 'Delivered',
                'icon' => '✅',
                'completed' => $order->status === 'delivered',
                'date' => $order->delivered_at?->toIso8601String(),
            ],
        ];

        // Add current status indication
        $currentStatusMap = [
            'pending' => 'order_placed',
            'processing' => 'processing',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
        ];

        $currentStatus = $currentStatusMap[$order->status] ?? $order->status;

        foreach ($timeline as &$step) {
            $step['is_current'] = $step['status'] === $currentStatus;
        }

        return $timeline;
    }
}

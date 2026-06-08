<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use SteadFast\SteadFastCourierLaravelPackage\Facades\SteadfastCourier;
use App\Services\OrderActivityLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SteadfastController extends Controller
{
    private function initializeSteadfast()
    {
        $apiKey = Setting::getValue('courier', 'steadfast_api_key');
        $secretKey = Setting::getValue('courier', 'steadfast_secret_key');
        $baseUrl = 'https://portal.packzy.com/api/v1'; // Hardcoded base URL per requirement

        config([
            'steadfast-courier.api_key' => $apiKey,
            'steadfast-courier.secret_key' => $secretKey,
            'steadfast-courier.base_url' => $baseUrl,
        ]);
    }

    public function sendSingle(Request $request, Order $order)
    {
        $request->validate([
            'cod_amount' => 'required|numeric|min:0',
            'note' => 'nullable|string'
        ]);

        if (!in_array($order->status, ['pending', 'processing'])) {
            return back()->with('error', 'Only pending or processing orders can be sent to SteadFast.');
        }

        try {
            $this->initializeSteadfast();

            $customerName = trim($order->shipping_name) ?: ($order->user?->name ?: 'Guest Checkout');
            $customerPhone = $order->shipping_phone ?: $order->user?->phone;
            $customerAddress = trim($order->shipping_address . ' ' . ($order->checkout_fields_payload['shipping_location_text'] ?? '') . ' ' . ($order->checkout_fields_payload['shipping_area'] ?? ''));

            $orderData = [
                'invoice' => $order->order_number,
                'recipient_name' => mb_substr($customerName, 0, 100),
                'recipient_phone' => $customerPhone,
                'recipient_address' => $customerAddress ?: 'Not Provided',
                'cod_amount' => (float)$request->cod_amount,
                'note' => $request->note ?? $order->notes,
            ];

            $apiKey = config('steadfast-courier.api_key');
            $secretKey = config('steadfast-courier.secret_key');
            $baseUrl = config('steadfast-courier.base_url');

            $httpResponse = \Illuminate\Support\Facades\Http::withHeaders([
                'Api-Key' => $apiKey,
                'Secret-Key' => $secretKey,
                'Content-Type' => 'application/json',
            ])->post($baseUrl.'/create_order', $orderData);

            $response = $httpResponse->json();

            if (isset($response['status']) && $response['status'] == 200) {
                // Success
                $consignment = $response['consignment'];

                $updateData = [
                    'carrier' => 'steadfast',
                    'tracking_number' => $consignment['consignment_id'],
                    'carrier_tracking_url' => $consignment['tracking_link'] ?? ('https://packzy.com/track/' . $consignment['tracking_code']),
                ];

                if ($order->status === 'pending') {
                    $updateData['status'] = 'processing';
                }

                $order->update($updateData);

                \App\Models\OrderActivityLog::log($order, 'status_change', "Order sent to SteadFast. Consignment ID: {$consignment['consignment_id']}, Tracking Code: {$consignment['tracking_code']}");

                return back()->with('success', 'Order sent to SteadFast Courier successfully.');
            } else {
                // Error from SteadFast API
                $errorMsg = 'Unknown API error';
                if ($response && isset($response['errors'])) {
                    $errorMsg = implode(', ', (array)$response['errors']);
                } elseif (!$response && $httpResponse->body()) {
                    $errorMsg = strip_tags($httpResponse->body());
                }
                
                Log::error('SteadFast API Error', [
                    'status' => $httpResponse->status(), 
                    'body' => $httpResponse->body()
                ]);
                return back()->with('error', 'SteadFast API Error: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('SteadFast Integration Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'SteadFast Integration Error: ' . $e->getMessage());
        }
    }

    public function sendBulk(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id'
        ]);

        try {
            $this->initializeSteadfast();

            $orders = Order::whereIn('id', $request->order_ids)
                ->whereIn('status', ['pending', 'processing'])
                ->where(function($q) {
                    $q->whereNull('carrier')->orWhere('carrier', '!=', 'steadfast');
                })
                ->get();

            if ($orders->isEmpty()) {
                return back()->with('error', 'No eligible orders found to send to SteadFast.');
            }

            $bulkData = [];
            foreach ($orders as $order) {
                $customerName = trim($order->shipping_name) ?: ($order->user?->name ?: 'Guest Checkout');
                $customerPhone = $order->shipping_phone ?: $order->user?->phone;
                $customerAddress = trim($order->shipping_address . ' ' . ($order->checkout_fields_payload['shipping_location_text'] ?? '') . ' ' . ($order->checkout_fields_payload['shipping_area'] ?? ''));

                $bulkData[] = [
                    'invoice' => $order->order_number,
                    'recipient_name' => mb_substr($customerName, 0, 100),
                    'recipient_phone' => $customerPhone,
                    'recipient_address' => $customerAddress ?: 'Not Provided',
                    'cod_amount' => (float)$order->total,
                    'note' => $order->notes,
                ];
            }

            $apiKey = config('steadfast-courier.api_key');
            $secretKey = config('steadfast-courier.secret_key');
            $baseUrl = config('steadfast-courier.base_url');

            $httpResponse = \Illuminate\Support\Facades\Http::withHeaders([
                'Api-Key' => $apiKey,
                'Secret-Key' => $secretKey,
                'Content-Type' => 'application/json',
            ])->post($baseUrl.'/create_order/bulk-order', ['data' => json_encode($bulkData)]);

            $response = $httpResponse->json();

            if (isset($response['status']) && $response['status'] == 200) {
                // Bulk Success
                $successfulData = $response['data'] ?? [];
                
                DB::beginTransaction();
                try {
                    $successCount = 0;
                    foreach ($successfulData as $item) {
                        $order = $orders->firstWhere('order_number', $item['invoice']);
                        if ($order) {
                            $updateData = [
                                'carrier' => 'steadfast',
                                'tracking_number' => $item['consignment_id'],
                                'carrier_tracking_url' => $item['tracking_link'] ?? ('https://packzy.com/track/' . $item['tracking_code']),
                            ];

                            if ($order->status === 'pending') {
                                $updateData['status'] = 'processing';
                            }

                            $order->update($updateData);

                            \App\Models\OrderActivityLog::log($order, 'status_change', "Order bulk sent to SteadFast. Consignment ID: {$item['consignment_id']}, Tracking Code: {$item['tracking_code']}");
                            $successCount++;
                        }
                    }
                    DB::commit();
                    return back()->with('success', "Successfully sent {$successCount} orders to SteadFast Courier.");
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                $errorMsg = 'Unknown bulk API error';
                if ($response && isset($response['errors'])) {
                    $errorMsg = json_encode($response['errors']);
                } elseif (!$response && $httpResponse->body()) {
                    $errorMsg = strip_tags($httpResponse->body());
                }

                Log::error('SteadFast Bulk API Error', [
                    'status' => $httpResponse->status(), 
                    'body' => $httpResponse->body()
                ]);
                return back()->with('error', 'SteadFast Bulk API Error: ' . $errorMsg);
            }

        } catch (\Exception $e) {
            Log::error('SteadFast Bulk Integration Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'SteadFast Bulk Integration Error: ' . $e->getMessage());
        }
    }
}

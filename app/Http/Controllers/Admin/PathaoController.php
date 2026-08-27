<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use devrkb21\PathaoLaravel\Facades\PathaoLaravel;
use devrkb21\PathaoLaravel\Requests\PathaoOrderRequest;

class PathaoController extends Controller
{
    private const GROUP = 'courier';

    private function initializePathao()
    {
        $clientId = Setting::getValue(self::GROUP, 'pathao_client_id');
        $clientSecret = Setting::getValue(self::GROUP, 'pathao_client_secret');
        $secretToken = Setting::getValue(self::GROUP, 'pathao_secret_token');
        $webhookSecret = Setting::getValue(self::GROUP, 'pathao_webhook_integration_secret');
        $sandbox = filter_var(Setting::getValue(self::GROUP, 'pathao_sandbox', '0'), FILTER_VALIDATE_BOOLEAN);

        config([
            'pathao.pathao_client_id' => $clientId,
            'pathao.pathao_client_secret' => $clientSecret,
            'pathao.pathao_secret_token' => $secretToken,
            'pathao.webhook_integration_secret' => $webhookSecret,
            'pathao.sandbox' => $sandbox,
            'pathao.pathao_db_table_name' => 'pathao-courier',
        ]);
    }

    public function sendSingle(Request $request, Order $order)
    {
        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string',
            'recipient_address' => 'required|string|min:10',
            'amount_to_collect' => 'required|numeric|min:0',
            'item_quantity' => 'required|integer|min:1',
            'item_weight' => 'required|numeric|min:0.1',
            'item_type' => 'required|in:1,2',
            'delivery_type' => 'required|in:12,48',
            'special_instruction' => 'nullable|string',
            'manual_location' => 'nullable|boolean',
            'recipient_city' => 'required_if:manual_location,1|nullable|numeric',
            'recipient_zone' => 'required_if:manual_location,1|nullable|numeric',
            'recipient_area' => 'required_if:manual_location,1|nullable|numeric',
        ]);

        if (!in_array($order->status, ['pending', 'processing'])) {
            return back()->with('error', 'Only pending or processing orders can be sent to Pathao.');
        }

        $storeId = Setting::getValue(self::GROUP, 'pathao_store_id');
        if (empty($storeId)) {
            return back()->with('error', 'Default Store ID is not configured. Please set it in Pathao Courier Settings.');
        }

        try {
            $this->initializePathao();

            // Resolve Location
            if ($request->boolean('manual_location')) {
                $cityId = $request->recipient_city;
                $zoneId = $request->recipient_zone;
                $areaId = $request->recipient_area;
            } else {
                $resolved = $this->resolveLocations($order);
                $cityId = $resolved['city_id'];
                $zoneId = $resolved['zone_id'];
                $areaId = $resolved['area_id'];

                if (!$cityId || !$zoneId || !$areaId) {
                    return back()->withInput()->with('error', 'Could not auto-resolve customer address to Pathao locations. Please toggle "Select Location Manually" and choose them manually.');
                }
            }

            // Create Pathao Request object programmatically
            $pathaoRequest = new PathaoOrderRequest();
            $pathaoRequest->replace([
                'store_id' => (int)$storeId,
                'merchant_order_id' => $order->order_number,
                'recipient_name' => $request->recipient_name,
                'recipient_phone' => $request->recipient_phone,
                'recipient_address' => $request->recipient_address,
                'recipient_city' => (int)$cityId,
                'recipient_zone' => (int)$zoneId,
                'recipient_area' => (int)$areaId,
                'delivery_type' => (int)$request->delivery_type,
                'item_type' => (int)$request->item_type,
                'item_quantity' => (int)$request->item_quantity,
                'item_weight' => (float)$request->item_weight,
                'amount_to_collect' => (float)$request->amount_to_collect,
                'special_instruction' => $request->special_instruction ?? $order->notes,
            ]);

            $response = PathaoLaravel::CREATE_ORDER($pathaoRequest);

            if (isset($response['status']) && $response['status'] == 200) {
                // Success
                $consignmentId = $response['data']['data']['consignment_id'] ?? null;
                
                if (empty($consignmentId)) {
                    return back()->with('error', 'Pathao order created but consignment ID was missing in the response.');
                }

                $recipientPhone = $request->recipient_phone;
                $trackingUrl = 'https://merchant.pathao.com/tracking?consignment_id=' . $consignmentId . '&phone=' . urlencode($recipientPhone);

                $updateData = [
                    'carrier' => 'pathao',
                    'tracking_number' => $consignmentId,
                    'carrier_tracking_url' => $trackingUrl,
                ];

                $statusChangedToProcessing = ($order->status === 'pending');
                if ($statusChangedToProcessing) {
                    $updateData['status'] = 'processing';
                }

                $order->update($updateData);

                \App\Models\OrderActivityLog::log($order, 'status_change', "Order sent to Pathao Courier. Consignment ID: {$consignmentId}");

                if ($statusChangedToProcessing) {
                    try {
                        $smsResult = app(\App\Services\SmsService::class)->sendOrderStatusSms($order, 'processing');
                        if ($smsResult['success']) {
                            \App\Models\OrderActivityLog::log($order, 'sms_sent', "SMS sent: Status → Processing (via Pathao)", $smsResult['message'] ?? null, ['status' => 'processing']);
                        } elseif (!str_contains($smsResult['message'] ?? '', 'not enabled')) {
                            \App\Models\OrderActivityLog::log($order, 'sms_failed', 'SMS failed', $smsResult['message'] ?? null, ['status' => 'processing', 'error' => $smsResult['message'] ?? '']);
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Order SMS failed', ['order_id' => $order->id, 'status' => 'processing', 'error' => $e->getMessage()]);
                    }
                }

                return back()->with('success', 'Order sent to Pathao Courier successfully.');
            } else {
                // Error from Pathao
                $errorMsg = $response['message'] ?? 'Unknown API error';
                if (isset($response['data']) && is_array($response['data'])) {
                    $errorMsg .= ' - ' . json_encode($response['data']);
                }
                return back()->withInput()->with('error', 'Pathao API Error: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('Pathao Integration Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Pathao Integration Error: ' . $e->getMessage());
        }
    }

    public function sendBulk(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
            'item_type' => 'required|in:1,2',
            'delivery_type' => 'required|in:12,48',
            'item_weight' => 'required|numeric|min:0.1',
            'special_instruction' => 'nullable|string',
        ]);

        $storeId = Setting::getValue(self::GROUP, 'pathao_store_id');
        if (empty($storeId)) {
            return back()->with('error', 'Default Store ID is not configured. Please set it in Pathao Courier Settings.');
        }

        try {
            $this->initializePathao();

            $orders = Order::whereIn('id', $request->order_ids)
                ->whereIn('status', ['pending', 'processing'])
                ->where(function($q) {
                    $q->whereNull('carrier')->orWhere('carrier', '!=', 'pathao');
                })
                ->get();

            // Orders placed after the license expired can't be dispatched
            // from the admin panel until renewal.
            $licenseService = app(\App\Services\LicenseService::class);
            $orders = $orders->reject(fn (Order $order) => $licenseService->isOrderLocked($order))->values();

            if ($orders->isEmpty()) {
                return back()->with('error', 'No eligible orders found to send to Pathao.');
            }

            $successCount = 0;
            $failCount = 0;
            $errors = [];

            foreach ($orders as $order) {
                // Auto-resolve locations
                $resolved = $this->resolveLocations($order);
                $cityId = $resolved['city_id'];
                $zoneId = $resolved['zone_id'];
                $areaId = $resolved['area_id'];

                if (!$cityId || !$zoneId || !$areaId) {
                    $failCount++;
                    $errors[] = "Order #{$order->order_number}: Could not auto-resolve address.";
                    continue;
                }

                $customerName = trim($order->shipping_name) ?: ($order->user?->name ?: 'Guest Checkout');
                $customerPhone = $order->shipping_phone ?: $order->user?->phone;
                $customerAddress = trim($order->shipping_address . ' ' . ($order->checkout_fields_payload['shipping_location_text'] ?? '') . ' ' . ($order->checkout_fields_payload['shipping_area'] ?? ''));

                try {
                    $pathaoRequest = new PathaoOrderRequest();
                    $pathaoRequest->replace([
                        'store_id' => (int)$storeId,
                        'merchant_order_id' => $order->order_number,
                        'recipient_name' => mb_substr($customerName, 0, 100),
                        'recipient_phone' => $customerPhone,
                        'recipient_address' => $customerAddress ?: 'Not Provided',
                        'recipient_city' => (int)$cityId,
                        'recipient_zone' => (int)$zoneId,
                        'recipient_area' => (int)$areaId,
                        'delivery_type' => (int)$request->delivery_type,
                        'item_type' => (int)$request->item_type,
                        'item_quantity' => (int)($order->items_count ?: $order->items()->count() ?: 1),
                        'item_weight' => (float)$request->item_weight,
                        'amount_to_collect' => (float)$order->total,
                        'special_instruction' => $request->special_instruction ?? $order->notes,
                    ]);

                    $response = PathaoLaravel::CREATE_ORDER($pathaoRequest);

                    if (isset($response['status']) && $response['status'] == 200) {
                        $consignmentId = $response['data']['data']['consignment_id'] ?? null;
                        
                        if ($consignmentId) {
                            $trackingUrl = 'https://merchant.pathao.com/tracking?consignment_id=' . $consignmentId . '&phone=' . urlencode($customerPhone);

                            $updateData = [
                                'carrier' => 'pathao',
                                'tracking_number' => $consignmentId,
                                'carrier_tracking_url' => $trackingUrl,
                            ];

                            $statusChangedToProcessing = ($order->status === 'pending');
                            if ($statusChangedToProcessing) {
                                $updateData['status'] = 'processing';
                            }

                            $order->update($updateData);

                            \App\Models\OrderActivityLog::log($order, 'status_change', "Order bulk sent to Pathao Courier. Consignment ID: {$consignmentId}");

                            if ($statusChangedToProcessing) {
                                try {
                                    $smsResult = app(\App\Services\SmsService::class)->sendOrderStatusSms($order, 'processing');
                                    if ($smsResult['success']) {
                                        \App\Models\OrderActivityLog::log($order, 'sms_sent', "SMS sent: Status → Processing (via Pathao Bulk)", $smsResult['message'] ?? null, ['status' => 'processing']);
                                    } elseif (!str_contains($smsResult['message'] ?? '', 'not enabled')) {
                                        \App\Models\OrderActivityLog::log($order, 'sms_failed', 'SMS failed', $smsResult['message'] ?? null, ['status' => 'processing', 'error' => $smsResult['message'] ?? '']);
                                    }
                                } catch (\Throwable $e) {
                                    \Log::warning('Order SMS failed', ['order_id' => $order->id, 'status' => 'processing', 'error' => $e->getMessage()]);
                                }
                            }
                            $successCount++;
                        } else {
                            $failCount++;
                            $errors[] = "Order #{$order->order_number}: Missing Consignment ID in response.";
                        }
                    } else {
                        $failCount++;
                        $errors[] = "Order #{$order->order_number}: " . ($response['message'] ?? 'API error.');
                    }
                } catch (\Exception $ex) {
                    $failCount++;
                    $errors[] = "Order #{$order->order_number}: " . $ex->getMessage();
                }
            }

            $message = "Successfully sent {$successCount} orders to Pathao.";
            if ($failCount > 0) {
                $message .= " Failed to send {$failCount} orders: " . implode(', ', $errors);
                return back()->with('error', $message);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Pathao Bulk Dispatch Exception: ' . $e->getMessage());
            return back()->with('error', 'Pathao Bulk Dispatch Exception: ' . $e->getMessage());
        }
    }

    public function getZones(Request $request)
    {
        $cityId = $request->get('city_id');
        if (empty($cityId)) {
            return response()->json([]);
        }

        $zones = DB::table('pathao_zones')
            ->where('city_id', $cityId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($zones);
    }

    public function getAreas(Request $request)
    {
        $zoneId = $request->get('zone_id');
        if (empty($zoneId)) {
            return response()->json([]);
        }

        $areas = DB::table('pathao_areas')
            ->where('zone_id', $zoneId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($areas);
    }

    private function resolveLocations(Order $order): array
    {
        $cityId = null;
        $zoneId = null;
        $areaId = null;

        // 1. Resolve City via BdDistrict name mapping
        $districtName = $order->shippingDistrict?->name;
        if ($districtName) {
            $pathaoCity = DB::table('pathao_cities')
                ->where('name', 'like', '%' . $districtName . '%')
                ->first();

            if ($pathaoCity) {
                $cityId = $pathaoCity->id;

                // 2. Resolve Zone via BdUpazila name mapping
                $upazilaName = $order->shippingUpazila?->name;
                if ($upazilaName) {
                    $pathaoZone = DB::table('pathao_zones')
                        ->where('city_id', $cityId)
                        ->where('name', 'like', '%' . $upazilaName . '%')
                        ->first();

                    if ($pathaoZone) {
                        $zoneId = $pathaoZone->id;

                        // 3. Resolve Area via BdUnion name mapping
                        $unionName = $order->shippingUnion?->name;
                        if ($unionName) {
                            $pathaoArea = DB::table('pathao_areas')
                                ->where('zone_id', $zoneId)
                                ->where('name', 'like', '%' . $unionName . '%')
                                ->first();
                            if ($pathaoArea) {
                                $areaId = $pathaoArea->id;
                            }
                        }

                        // Fallback: search shipping address text for matching area name in this zone
                        if (!$areaId) {
                            $addressText = $order->shipping_address;
                            $areas = DB::table('pathao_areas')
                                ->where('zone_id', $zoneId)
                                ->get();

                            foreach ($areas as $area) {
                                if (stripos($addressText, $area->name) !== false) {
                                    $areaId = $area->id;
                                    break;
                                }
                            }
                        }

                        // Default to first area in zone if still unmatched
                        if (!$areaId) {
                            $firstArea = DB::table('pathao_areas')
                                ->where('zone_id', $zoneId)
                                ->first();
                            if ($firstArea) {
                                $areaId = $firstArea->id;
                            }
                        }
                    }
                }

                // If zone not matched, pick first zone in city
                if (!$zoneId) {
                    $firstZone = DB::table('pathao_zones')
                        ->where('city_id', $cityId)
                        ->first();
                    if ($firstZone) {
                        $zoneId = $firstZone->id;
                        $firstArea = DB::table('pathao_areas')
                            ->where('zone_id', $zoneId)
                            ->first();
                        if ($firstArea) {
                            $areaId = $firstArea->id;
                        }
                    }
                }
            }
        }

        // Ultimate fallback: Dhaka City (ID 1)
        if (!$cityId) {
            $dhakaCity = DB::table('pathao_cities')->where('name', 'like', '%Dhaka%')->first()
                ?? DB::table('pathao_cities')->first();

            if ($dhakaCity) {
                $cityId = $dhakaCity->id;
                $firstZone = DB::table('pathao_zones')->where('city_id', $cityId)->first();
                if ($firstZone) {
                    $zoneId = $firstZone->id;
                    $firstArea = DB::table('pathao_areas')->where('zone_id', $zoneId)->first();
                    if ($firstArea) {
                        $areaId = $firstArea->id;
                    }
                }
            }
        }

        return [
            'city_id' => $cityId,
            'zone_id' => $zoneId,
            'area_id' => $areaId,
        ];
    }
}

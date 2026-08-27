<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function show(Request $request, int $orderId): JsonResponse
    {
        $order = Order::with(['user', 'items', 'payment', 'coupon'])->findOrFail($orderId);

        // Users can only view their own invoices unless admin
        if (! $request->user()->isAdmin() && $order->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $invoice = $this->generateInvoiceData($order);

        return $this->successResponse($invoice, 'Invoice generated successfully');
    }

    private function generateInvoiceData(Order $order): array
    {
        return [
            'invoice_number' => 'INV-'.$order->order_number,
            'invoice_date' => $order->created_at->format('Y-m-d'),
            'due_date' => $order->created_at->format('Y-m-d'),

            'company' => [
                'name' => config('shop.company_name', config('app.name')),
                'address' => config('shop.company_address', 'Dhaka, Bangladesh'),
                'email' => config('shop.company_email', config('mail.from.address')),
                'phone' => config('shop.company_phone'),
            ],

            'customer' => [
                'name' => $order->shipping_name ?? $order->user?->name,
                'email' => $order->shipping_email ?? $order->user?->email,
                'phone' => $order->shipping_phone ?? $order->user?->phone,
                'address' => $order->shipping_address,
                'city' => $order->shipping_city,
                'state' => $order->shipping_state,
                'zip' => $order->shipping_zip,
                'country' => $order->shipping_country,
            ],

            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'transaction_id' => $order->transaction_id,
            ],

            'items' => $order->items->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'sku' => $item->product_sku ?? null,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->price,
                    'subtotal' => (float) $item->subtotal,
                    'variant' => $item->variant_name ?? null,
                ];
            })->toArray(),

            'totals' => [
                'subtotal' => (float) $order->subtotal,
                'discount' => (float) ($order->discount_amount ?? 0),
                'coupon_code' => $order->coupon_code,
                'shipping' => (float) $order->shipping,
                'tax' => (float) ($order->tax ?? 0),
                'total' => (float) $order->total,
            ],

            'notes' => $order->notes,
            'currency' => config('shop.currency', 'BDT'),
            'currency_symbol' => config('shop.currency_symbol', '৳'),
        ];
    }
}

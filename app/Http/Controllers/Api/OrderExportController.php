<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderExportController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $request->validate([
            'status' => 'nullable|string|in:pending,processing,shipped,delivered,cancelled',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $query = Order::with(['user', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        if ($orders->isEmpty()) {
            return $this->errorResponse('No orders found matching the criteria', 404);
        }

        $csvContent = $this->buildCsv($orders);
        $filename = 'orders_' . now()->format('Y-m-d_His') . '.csv';

        Storage::put('exports/' . $filename, $csvContent);

        return $this->successResponse([
            'filename' => $filename,
            'total_orders' => $orders->count(),
            'download_url' => url('/api/v1/admin/orders/export/download/' . $filename),
        ], 'Orders exported successfully');
    }

    public function download(Request $request, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        // Defense-in-depth allowlist on top of Laravel's own route-segment
        // handling (which already blocks literal '/'): only allow the exact
        // character set export filenames are actually generated with.
        if (preg_match('/^[A-Za-z0-9_\-\.]+\.csv$/', $filename) !== 1) {
            return $this->errorResponse('Invalid filename', 400);
        }

        $path = 'exports/' . $filename;

        if (!Storage::exists($path)) {
            return $this->errorResponse('File not found', 404);
        }

        return Storage::download($path, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function buildCsv($orders): string
    {
        $headers = [
            'Order Number',
            'Customer Name',
            'Customer Email',
            'Status',
            'Payment Status',
            'Payment Method',
            'Subtotal',
            'Discount',
            'Shipping',
            'Tax',
            'Total',
            'Items Count',
            'Shipping Name',
            'Shipping City',
            'Shipping Country',
            'Coupon Code',
            'Tracking Number',
            'Carrier',
            'Created At',
        ];

        $rows = [];
        $rows[] = implode(',', $headers);

        foreach ($orders as $order) {
            $rows[] = implode(',', [
                $this->escapeCsv($order->order_number),
                $this->escapeCsv($order->user?->name ?? 'Guest'),
                $this->escapeCsv($order->user?->email ?? $order->shipping_email),
                $this->escapeCsv($order->status),
                $this->escapeCsv($order->payment_status),
                $this->escapeCsv($order->payment_method),
                $order->subtotal,
                $order->discount_amount,
                $order->shipping,
                $order->tax,
                $order->total,
                $order->items->count(),
                $this->escapeCsv($order->shipping_name),
                $this->escapeCsv($order->shipping_city),
                $this->escapeCsv($order->shipping_country),
                $this->escapeCsv($order->coupon_code ?? ''),
                $this->escapeCsv($order->tracking_number ?? ''),
                $this->escapeCsv($order->carrier ?? ''),
                $order->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        return implode("\n", $rows);
    }

    private function escapeCsv(?string $value): string
    {
        if ($value === null) {
            return '""';
        }
        return '"' . str_replace('"', '""', $value) . '"';
    }
}

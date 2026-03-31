<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportOrders extends Command
{
    protected $signature = 'orders:export
        {--status= : Filter by order status}
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}
        {--path=exports : Storage path for the CSV file}';

    protected $description = 'Export orders to CSV file';

    public function handle(): int
    {
        $query = Order::with(['user', 'items']);

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        if ($from = $this->option('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $this->option('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        if ($orders->isEmpty()) {
            $this->warn('No orders found matching the criteria.');
            return Command::SUCCESS;
        }

        $filename = 'orders_' . now()->format('Y-m-d_His') . '.csv';
        $path = $this->option('path') . '/' . $filename;

        $csvContent = $this->buildCsv($orders);

        Storage::put($path, $csvContent);

        $this->info("Exported {$orders->count()} orders to: storage/app/{$path}");

        return Command::SUCCESS;
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

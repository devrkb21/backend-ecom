<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low Stock Alert - ' . $this->product->name)
            ->greeting('Stock Alert!')
            ->line('The following product is running low on stock:')
            ->line('**Product:** ' . $this->product->name)
            ->line('**SKU:** ' . $this->product->sku)
            ->line('**Current Stock:** ' . $this->product->stock_quantity . ' units')
            ->line('')
            ->line('Please restock this product soon to avoid stockouts.')
            ->action('View Product', url('/admin/products/' . $this->product->id . '/edit'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock_alert',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'stock_quantity' => $this->product->stock_quantity,
            'message' => "Low stock alert: {$this->product->name} has only {$this->product->stock_quantity} units left.",
        ];
    }
}

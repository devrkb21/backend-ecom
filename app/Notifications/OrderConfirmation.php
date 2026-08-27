<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $itemsText = $this->order->items->map(function ($item) {
            return "• {$item->product_name} x {$item->quantity} = ৳".number_format($item->subtotal, 2);
        })->join("\n");

        return (new MailMessage)
            ->subject('Order Confirmation - #'.$this->order->order_number)
            ->greeting('Thank you for your order!')
            ->line('We have received your order and it is being processed.')
            ->line('**Order Number:** '.$this->order->order_number)
            ->line('**Order Date:** '.$this->order->created_at->format('F j, Y'))
            ->line('')
            ->line('**Items:**')
            ->line($itemsText)
            ->line('')
            ->line('**Subtotal:** ৳'.number_format($this->order->subtotal, 2))
            ->when($this->order->discount_amount > 0, function ($message) {
                return $message->line('**Discount:** -৳'.number_format($this->order->discount_amount, 2));
            })
            ->line('**Shipping:** ৳'.number_format($this->order->shipping, 2))
            ->line('**Total:** ৳'.number_format($this->order->total, 2))
            ->line('')
            ->line('**Shipping Address:**')
            ->line($this->order->shipping_name)
            ->line($this->order->shipping_address)
            ->line($this->order->shipping_city.', '.$this->order->shipping_country)
            ->action('View Order', url('/orders/'.$this->order->id))
            ->line('Thank you for shopping with us!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_confirmation',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'message' => 'Your order #'.$this->order->order_number.' has been confirmed.',
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $statusMessages = [
        'pending' => 'Your order is pending and awaiting processing.',
        'processing' => 'Your order is now being processed.',
        'confirmed' => 'Your order has been confirmed and will be shipped soon.',
        'shipped' => 'Great news! Your order has been shipped.',
        'delivered' => 'Your order has been delivered. Enjoy!',
        'cancelled' => 'Your order has been cancelled.',
        'refunded' => 'Your order has been refunded.',
    ];

    public function __construct(
        public Order $order,
        public string $oldStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusMessage = $this->statusMessages[$this->order->status] ?? 'Your order status has been updated.';

        $mail = (new MailMessage)
            ->subject('Order Update - #'.$this->order->order_number)
            ->greeting('Order Status Update')
            ->line($statusMessage)
            ->line('')
            ->line('**Order Number:** '.$this->order->order_number)
            ->line('**New Status:** '.ucfirst($this->order->status))
            ->line('**Total:** ৳'.number_format($this->order->total, 2));

        if ($this->order->status === 'shipped' && $this->order->tracking_number) {
            $mail->line('**Tracking Number:** '.$this->order->tracking_number);
        }

        return $mail
            ->action('View Order', url('/orders/'.$this->order->id))
            ->line('Thank you for shopping with us!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status_updated',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->order->status,
            'message' => 'Order #'.$this->order->order_number.' status changed to '.ucfirst($this->order->status),
        ];
    }
}

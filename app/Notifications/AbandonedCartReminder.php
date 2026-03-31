<?php

namespace App\Notifications;

use App\Models\AbandonedCart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbandonedCartReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AbandonedCart $abandonedCart
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $itemCount = $this->abandonedCart->item_count;
        $total = number_format($this->abandonedCart->total, 2);

        $message = (new MailMessage)
            ->subject('You left items in your cart!')
            ->greeting('Hi ' . ($this->abandonedCart->name ?? $notifiable->name) . '!')
            ->line("We noticed you left {$itemCount} item(s) in your shopping cart worth ৳{$total}.")
            ->line('Your cart is waiting for you - complete your purchase before your items sell out!');

        if ($this->abandonedCart->cart_items) {
            $itemsList = collect($this->abandonedCart->cart_items)
                ->take(3)
                ->map(fn($item) => "- {$item['name']} x {$item['quantity']}")
                ->join("\n");
            $message->line('**Your items:**')->line($itemsList);
        }

        return $message
            ->action('Complete Your Purchase', env('FRONTEND_URL', 'https://innercollection.com.bd') . '/cart')
            ->line('Need help? Reply to this email and we\'ll assist you.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'abandoned_cart_reminder',
            'abandoned_cart_id' => $this->abandonedCart->id,
            'total' => $this->abandonedCart->total,
            'item_count' => $this->abandonedCart->item_count,
            'message' => 'You have items worth ৳' . number_format($this->abandonedCart->total, 2) . ' in your cart.',
        ];
    }
}

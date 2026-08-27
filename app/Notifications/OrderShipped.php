<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?string $trackingNumber = null,
        public ?string $carrier = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your Order Has Been Shipped! - #'.$this->order->order_number)
            ->greeting('Great news!')
            ->line('Your order has been shipped and is on its way to you.')
            ->line('')
            ->line('**Order Number:** '.$this->order->order_number);

        if ($this->trackingNumber) {
            $mail->line('**Tracking Number:** '.$this->trackingNumber);
        }

        if ($this->carrier) {
            $mail->line('**Carrier:** '.$this->carrier);
        }

        $mail->line('')
            ->line('**Shipping Address:**')
            ->line($this->order->shipping_name)
            ->line($this->order->shipping_address)
            ->line($this->order->shipping_city.', '.$this->order->shipping_country)
            ->line('')
            ->line('**Estimated Delivery:** 3-5 business days');

        // Add tracking link if available
        if ($this->trackingNumber && $this->carrier) {
            $trackingUrl = $this->getTrackingUrl();
            if ($trackingUrl) {
                $mail->action('Track Your Order', $trackingUrl);
            }
        }

        return $mail->line('Thank you for shopping with us!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_shipped',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'tracking_number' => $this->trackingNumber,
            'carrier' => $this->carrier,
            'message' => 'Your order #'.$this->order->order_number.' has been shipped!',
        ];
    }

    protected function getTrackingUrl(): ?string
    {
        if (! $this->trackingNumber || ! $this->carrier) {
            return null;
        }

        $carriers = [
            'pathao' => 'https://merchant.pathao.com/tracking?consignment_id='.$this->trackingNumber,
            'steadfast' => 'https://steadfast.com.bd/tl/'.$this->trackingNumber,
            'redx' => 'https://redx.com.bd/track/'.$this->trackingNumber,
            'paperfly' => 'https://paperfly.com.bd/tracking/'.$this->trackingNumber,
            'sundarban' => 'https://sundarbancourier.com/track/'.$this->trackingNumber,
        ];

        return $carriers[strtolower($this->carrier)] ?? null;
    }
}

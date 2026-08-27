<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $verificationUrl = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Our Store');

        $mail = (new MailMessage)
            ->subject('Welcome to '.$appName.'! 🎉')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Welcome to '.$appName.'! We\'re thrilled to have you with us.')
            ->line('')
            ->line('Here\'s what you can do with your new account:')
            ->line('✅ Browse our extensive product catalog')
            ->line('✅ Add items to your wishlist')
            ->line('✅ Track your orders in real-time')
            ->line('✅ Get personalized recommendations')
            ->line('✅ Receive exclusive offers and discounts')
            ->line('');

        if ($this->verificationUrl) {
            $mail->action('Verify Your Email', $this->verificationUrl)
                ->line('Please verify your email to unlock all features.');
        } else {
            $mail->action('Start Shopping', config('app.frontend_url', url('/')))
                ->line('Start exploring our products now!');
        }

        return $mail->line('')
            ->line('If you have any questions, feel free to reach out to our support team.')
            ->line('Happy shopping! 🛍️');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'message' => 'Welcome to '.config('app.name', 'Our Store').'! Thank you for joining us.',
        ];
    }
}

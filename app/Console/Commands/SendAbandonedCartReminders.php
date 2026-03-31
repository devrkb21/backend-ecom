<?php

namespace App\Console\Commands;

use App\Models\AbandonedCart;
use App\Models\User;
use App\Notifications\AbandonedCartReminder;
use Illuminate\Console\Command;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'cart:send-reminders {--hours=2 : Hours since last activity}';
    protected $description = 'Send reminder emails for abandoned carts';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $abandonedCarts = AbandonedCart::query()
            ->where('status', 'pending')
            ->where('last_activity_at', '<=', now()->subHours($hours))
            ->where('last_activity_at', '>=', now()->subDays(7))
            ->whereNotNull('email')
            ->whereNull('followed_up_at')
            ->get();

        $count = 0;

        foreach ($abandonedCarts as $cart) {
            $user = $cart->user;

            if ($user) {
                $user->notify(new AbandonedCartReminder($cart));
            }

            $cart->update([
                'status' => 'follow_up',
                'followed_up_at' => now(),
            ]);

            $count++;
        }

        $this->info("Sent {$count} abandoned cart reminders.");

        return Command::SUCCESS;
    }
}

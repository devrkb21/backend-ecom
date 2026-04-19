<?php

namespace App\Console\Commands;

use App\Models\AbandonedCart;
use App\Notifications\AbandonedCartReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'cart:send-reminders
                            {--hours=2 : Minimum hours since last activity}
                            {--cooldown=24 : Cooldown hours between reminders}
                            {--max-reminders=3 : Maximum reminders per cart}';

    protected $description = 'Send reminder emails for abandoned carts';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cooldown = (int) $this->option('cooldown');
        $maxReminders = (int) $this->option('max-reminders');

        $hours = max(1, $hours);
        $cooldown = max(1, $cooldown);
        $maxReminders = max(1, $maxReminders);

        $abandonedCarts = AbandonedCart::query()
            ->open()
            ->olderThan($hours)
            ->where('last_activity_at', '>=', now()->subDays(7))
            ->whereNotNull('email')
            ->reminderDue($cooldown, $maxReminders)
            ->get();

        $sent = 0;
        $failed = 0;
        $sentToUsers = 0;
        $sentToGuests = 0;

        $abandonedCarts->each(function (AbandonedCart $cart) use (&$sent, &$failed, &$sentToUsers, &$sentToGuests): void {
            try {
                if ($cart->user) {
                    $cart->user->notify(new AbandonedCartReminder($cart));
                    $sentToUsers++;
                } elseif ($cart->email) {
                    Notification::route('mail', $cart->email)
                        ->notify(new AbandonedCartReminder($cart));
                    $sentToGuests++;
                } else {
                    $failed++;
                    return;
                }

                $cart->markReminderSent('mail');
                $sent++;
            } catch (Throwable $exception) {
                $failed++;

                Log::warning('Failed to send abandoned cart reminder.', [
                    'abandoned_cart_id' => $cart->id,
                    'email' => $cart->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        });

        $this->info("Sent {$sent} abandoned cart reminder(s).");
        $this->line("- User notifications: {$sentToUsers}");
        $this->line("- Guest notifications: {$sentToGuests}");
        $this->line("- Failed: {$failed}");

        return Command::SUCCESS;
    }
}

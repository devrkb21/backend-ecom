<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Send abandoned cart reminders every 2 hours
        $schedule->command('cart:send-reminders --hours=2')
            ->everyTwoHours()
            ->between('9:00', '21:00');

        // Expire ended flash sales every 15 minutes
        $schedule->command('flash-sales:expire')
            ->everyFifteenMinutes();

        // Check low stock products daily at 9 AM
        $schedule->command('inventory:check-low-stock --threshold=5')
            ->dailyAt('09:00');

        // Clean up expired password reset tokens daily
        $schedule->command('auth:clear-resets')
            ->daily();

        // Prune Sanctum expired tokens weekly
        $schedule->command('sanctum:prune-expired --hours=24')
            ->weekly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

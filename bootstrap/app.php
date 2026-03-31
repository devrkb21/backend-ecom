<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->throttleApi();

        // Register middleware aliases
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
        ]);

        // Append API request logging middleware
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\LogApiRequests::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Send abandoned cart reminders every 2 hours (9 AM to 9 PM)
        $schedule->command('cart:send-reminders --hours=2')
            ->everyTwoHours()
            ->between('9:00', '21:00');

        // Expire ended flash sales every 15 minutes
        $schedule->command('flash-sales:expire')
            ->everyFifteenMinutes();

        // Check low stock products daily at 9 AM
        $schedule->command('inventory:check-low-stock --threshold=5')
            ->dailyAt('09:00');

        // Prune Sanctum expired tokens weekly
        $schedule->command('sanctum:prune-expired --hours=24')
            ->weekly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();

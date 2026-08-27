<?php

use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\EnsureLicenseAllowsCreation;
use App\Http\Middleware\EnsureOrderNotLicenseLocked;
use App\Http\Middleware\InternalApiOnly;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\LogApiRequests;
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

        // Register middleware aliases
        $middleware->alias([
            'is_admin' => IsAdmin::class,
            'admin_permission' => EnsureAdminPermission::class,
            'internal.api' => InternalApiOnly::class,
            'license.create' => EnsureLicenseAllowsCreation::class,
            'license.order-lock' => EnsureOrderNotLicenseLocked::class,
        ]);

        // Trust only known reverse proxies, not every client — trusting '*'
        // lets any direct caller spoof X-Forwarded-For and forge the IP that
        // $request->ip() returns, which fraud-block IP matching and any
        // IP-based rate limiting both rely on. Set TRUSTED_PROXIES in .env to
        // a comma-separated list of your actual proxy IPs/CIDRs (e.g. your
        // Next.js host, or Cloudflare's published ranges) to override the
        // private-network default below.
        $trustedProxies = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRUSTED_PROXIES', '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,127.0.0.1'))
        )));
        $middleware->trustProxies(at: $trustedProxies ?: null);

        // Append API request logging middleware
        $middleware->appendToGroup('api', [
            LogApiRequests::class,
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

        // Re-check license status with the licensing server
        $schedule->command('license:verify')
            ->everyThirtyMinutes()
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();

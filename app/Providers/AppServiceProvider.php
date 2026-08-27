<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Observers\OrderObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        Order::observe(OrderObserver::class);

        try {
            if (\Schema::hasTable('settings')) {
                $mailSettings = Setting::where('group', 'integration')->pluck('value', 'key')->toArray();

                if (! empty($mailSettings['mail_enabled']) && ! empty($mailSettings['mail_mailer'])) {
                    Config::set('mail.default', $mailSettings['mail_mailer']);
                    Config::set('mail.mailers.smtp.host', $mailSettings['mail_host'] ?? env('MAIL_HOST', '127.0.0.1'));
                    Config::set('mail.mailers.smtp.port', $mailSettings['mail_port'] ?? env('MAIL_PORT', '2525'));
                    Config::set('mail.mailers.smtp.encryption', $mailSettings['mail_encryption'] ?? env('MAIL_ENCRYPTION', 'tls'));
                    Config::set('mail.mailers.smtp.username', $mailSettings['mail_username'] ?? env('MAIL_USERNAME'));
                    Config::set('mail.mailers.smtp.password', $mailSettings['mail_password'] ?? env('MAIL_PASSWORD'));

                    if (! empty($mailSettings['mail_from_address'])) {
                        Config::set('mail.from.address', $mailSettings['mail_from_address']);
                    }
                    if (! empty($mailSettings['mail_from_name'])) {
                        Config::set('mail.from.name', $mailSettings['mail_from_name']);
                    }
                }

                // Dynamic Pathao courier settings integration
                $pathaoSettings = Setting::where('group', 'courier')->pluck('value', 'key')->toArray();
                if (! empty($pathaoSettings)) {
                    Config::set('pathao.pathao_client_id', $pathaoSettings['pathao_client_id'] ?? '');
                    Config::set('pathao.pathao_client_secret', $pathaoSettings['pathao_client_secret'] ?? '');
                    Config::set('pathao.pathao_secret_token', $pathaoSettings['pathao_secret_token'] ?? '');
                    Config::set('pathao.webhook_integration_secret', $pathaoSettings['pathao_webhook_integration_secret'] ?? '');
                    Config::set('pathao.sandbox', filter_var($pathaoSettings['pathao_sandbox'] ?? '0', FILTER_VALIDATE_BOOLEAN));
                    Config::set('pathao.pathao_db_table_name', 'pathao-courier');
                }
            }
        } catch (\Exception $e) {
            // Silently ignore if DB is not ready during migrations
        }
    }
}

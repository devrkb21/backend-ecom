<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);

        try {
            if (\Schema::hasTable('settings')) {
                $mailSettings = Setting::getGroup('integration', false);
                
                if (!empty($mailSettings['mail_enabled']) && !empty($mailSettings['mail_mailer'])) {
                    Config::set('mail.default', $mailSettings['mail_mailer']);
                    Config::set('mail.mailers.smtp.host', $mailSettings['mail_host'] ?? env('MAIL_HOST', '127.0.0.1'));
                    Config::set('mail.mailers.smtp.port', $mailSettings['mail_port'] ?? env('MAIL_PORT', '2525'));
                    Config::set('mail.mailers.smtp.encryption', $mailSettings['mail_encryption'] ?? env('MAIL_ENCRYPTION', 'tls'));
                    Config::set('mail.mailers.smtp.username', $mailSettings['mail_username'] ?? env('MAIL_USERNAME'));
                    Config::set('mail.mailers.smtp.password', $mailSettings['mail_password'] ?? env('MAIL_PASSWORD'));
                    
                    if (!empty($mailSettings['mail_from_address'])) {
                        Config::set('mail.from.address', $mailSettings['mail_from_address']);
                    }
                    if (!empty($mailSettings['mail_from_name'])) {
                        Config::set('mail.from.name', $mailSettings['mail_from_name']);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently ignore if DB is not ready during migrations
        }
    }
}

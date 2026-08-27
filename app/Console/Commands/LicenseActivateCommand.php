<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Exception;
use Illuminate\Console\Command;

class LicenseActivateCommand extends Command
{
    protected $signature = 'license:activate';

    protected $description = 'Activate this installation\'s license key against the licensing server (run once during setup)';

    public function handle(LicenseService $licenseService): int
    {
        if (config('license.license_key') === '') {
            $this->error('LICENSE_KEY is not set. Add it to .env before activating.');

            return self::FAILURE;
        }

        try {
            $licenseService->activate();
            $this->info('License activated for this domain.');

            // Immediately verify so status/core_config are populated without
            // waiting for the next scheduled tick.
            $licenseService->verify();
            $this->info('Status: '.$licenseService->status());

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Activation failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class LicenseVerifyCommand extends Command
{
    protected $signature = 'license:verify';

    protected $description = 'Check in with the licensing server and refresh the cached license status (scheduled)';

    public function handle(LicenseService $licenseService): int
    {
        if (config('license.license_key') === '') {
            // Nothing to do on an unactivated install; not a failure.
            return self::SUCCESS;
        }

        $licenseService->verify();

        $this->info('License status: '.$licenseService->status());

        return self::SUCCESS;
    }
}

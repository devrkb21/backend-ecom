<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(protected LicenseService $licenseService) {}

    public function show(): JsonResponse
    {
        return $this->successResponse([
            'status' => $this->licenseService->status(),
            'is_valid' => $this->licenseService->isValid(),
            'has_license_key' => $this->licenseService->licenseKey() !== '',
            'masked_license_key' => $this->licenseService->maskedLicenseKey(),
            'product_slug' => config('license.product_slug'),
            'server_configured' => config('license.server_url') !== '' && config('license.public_key') !== '',
            'core_config' => $this->licenseService->coreConfig(),
            'last_verified_at' => $this->licenseService->lastVerifiedAt()?->toIso8601String(),
            'last_error' => $this->licenseService->lastError(),
            'expired_since' => $this->licenseService->expiredSince()?->toIso8601String(),
            'grace_period_hours' => (int) config('license.grace_period_hours'),
            'locked_orders_count' => $this->licenseService->lockedOrdersCount(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => ['required', 'string', 'max:255'],
        ]);

        $this->licenseService->setLicenseKey((string) $request->input('license_key'));

        try {
            $this->licenseService->activate();
        } catch (\Exception $e) {
            // Key is saved either way — activation may legitimately fail on
            // a domain that's already at its activation limit, etc. Surface
            // the error but let the merchant retry verification later.
        }

        $this->licenseService->verify();

        return $this->successResponse([
            'status' => $this->licenseService->status(),
            'last_error' => $this->licenseService->lastError(),
        ], 'License key saved');
    }
}

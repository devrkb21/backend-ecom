<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(protected LicenseService $licenseService) {}

    public function index(): View
    {
        return view('admin.license.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'license_key' => ['required', 'string', 'max:255'],
        ]);

        $this->licenseService->setLicenseKey((string) $request->input('license_key'));

        try {
            $this->licenseService->activate();
        } catch (Exception $e) {
            // Key is saved regardless — activation can legitimately fail
            // (e.g. domain already at its activation limit). The error is
            // still visible via last_error on the license page.
        }

        $this->licenseService->verify();

        if ($this->licenseService->isValid()) {
            return redirect()
                ->route('admin.license')
                ->with('success', 'License key saved and activated successfully.');
        }

        return redirect()
            ->route('admin.license')
            ->with('error', 'License key saved, but activation failed: '.$this->licenseService->lastError());
    }

    public function verifyNow(): RedirectResponse
    {
        $this->licenseService->verify();

        if ($this->licenseService->isValid()) {
            return redirect()
                ->route('admin.license')
                ->with('success', 'License re-verified — status is active.');
        }

        return redirect()
            ->route('admin.license')
            ->with('error', 'License check failed: '.($this->licenseService->lastError() ?: 'unknown error'));
    }
}

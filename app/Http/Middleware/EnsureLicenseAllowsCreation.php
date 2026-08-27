<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks admin-initiated creation of brand new resources (users, categories,
 * products, ...) once the license is expired/invalid past its grace period.
 * Reading and editing existing resources is never affected by this
 * middleware — only routes that create something new should carry it.
 */
class EnsureLicenseAllowsCreation
{
    public function __construct(protected LicenseService $licenseService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->licenseService->shouldBlockCreation()) {
            $message = 'Your license has expired. Please renew or contact support to continue adding new items.';

            if ($request->expectsJson()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $message,
                    'code' => 'license_expired',
                ], 403);
            }

            return redirect()->back()->with('error', $message);
        }

        return $next($request);
    }
}

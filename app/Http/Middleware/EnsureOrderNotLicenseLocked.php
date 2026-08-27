<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Services\LicenseService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks admin access (view or edit) to a single order that was placed after
 * the license expired — the route-model-bound counterpart to the query-level
 * filtering applied in OrderController::index()/export(). Attach to any
 * admin route that resolves an {order} parameter (status updates, tracking,
 * refunds, courier dispatch, notes, etc.) so every order-mutating surface is
 * covered without repeating the check in each controller method.
 */
class EnsureOrderNotLicenseLocked
{
    public function __construct(protected LicenseService $licenseService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $order = $request->route('order');

        if ($order instanceof Order && $this->licenseService->isOrderLocked($order)) {
            $message = 'This order was placed after your license expired. Renew your license to manage new orders.';

            if ($request->expectsJson()) {
                return new JsonResponse(['success' => false, 'message' => $message, 'code' => 'license_expired'], 403);
            }

            return redirect()->route('admin.orders.index')->with('error', $message);
        }

        return $next($request);
    }
}

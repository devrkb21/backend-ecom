<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (!$user || !$user->canAccessAdminPanel()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return redirect()->route('admin.login')->with('error', 'Access denied.');
        }

        $resolvedPermission = $permission ?: $this->resolvePermissionFromRoute($request);

        if ($resolvedPermission === self::DENY) {
            // Route name didn't match any known module and isn't on the explicit
            // no-permission-required allowlist. Fail closed rather than silently
            // granting access — a newly added admin route must be mapped below
            // (or added to $exempt) before it becomes reachable by non-superadmin roles.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Permission denied.',
                    'permission' => null,
                ], 403);
            }

            return redirect()->route('admin.dashboard')->with('error', 'You do not have permission to access this section.');
        }

        if ($resolvedPermission && !$user->hasAdminPermission($resolvedPermission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Permission denied.',
                    'permission' => $resolvedPermission,
                ], 403);
            }

            if ($request->routeIs('admin.dashboard')) {
                abort(403, 'You do not have permission to access dashboard.');
            }

            return redirect()->route('admin.dashboard')->with('error', 'You do not have permission to access this section.');
        }

        return $next($request);
    }

    /** Sentinel meaning "route was resolved, but matches nothing — deny", as opposed to null meaning "no permission required at all". */
    private const DENY = '__deny__';

    /**
     * Route names that are reachable by any authenticated admin-panel user
     * (already gated by is_admin above) and don't belong to a specific module.
     */
    private const EXEMPT_ROUTES = [
        'admin.logout',
    ];

    private function resolvePermissionFromRoute(Request $request): ?string
    {
        $routeName = (string) optional($request->route())->getName();

        if ($routeName === '') {
            // No named route matched under this middleware — nothing to check against,
            // so there's nothing to fail closed on either. Let it through.
            return null;
        }

        if (in_array($routeName, self::EXEMPT_ROUTES, true)) {
            return null;
        }

        $exact = [
            'admin.dashboard' => 'dashboard.view',
            'admin.global-search' => 'dashboard.view',
        ];

        if (isset($exact[$routeName])) {
            return $exact[$routeName];
        }

        $map = [
            'admin.roles.' => 'roles.manage',
            'admin.categories.' => 'catalog.manage',
            'admin.products.' => 'catalog.manage',
            'admin.attributes.' => 'catalog.manage',
            'admin.media.' => 'media.manage',
            'admin.pathao.' => 'orders.manage',
            'admin.orders.' => 'orders.manage',
            'admin.payments.' => 'payments.view',
            'admin.returns.' => 'returns.manage',
            'admin.abandoned-carts.' => 'abandoned_carts.manage',
            'admin.fraud-blocks.' => 'fraud.manage',
            'admin.contact-messages.' => 'contact.manage',
            'admin.pages.' => 'content.manage',
            'admin.landing-pages.' => 'content.manage',
            'admin.customer-groups.' => 'customers.manage',
            'admin.customers.' => 'customers.manage',
            'admin.coupons.' => 'marketing.manage',
            'admin.reviews.' => 'marketing.manage',
            'admin.flash-sales.' => 'marketing.manage',
            'admin.loyalty-rewards.' => 'marketing.manage',
            'admin.loyalty.' => 'marketing.manage',
            'admin.analytics.' => 'analytics.view',
            'admin.bi.' => 'analytics.view',
            'admin.users.' => 'users.manage',
            'admin.settings.' => 'settings.manage',
        ];

        foreach ($map as $prefix => $permission) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                return $permission;
            }
        }

        // Named route under the protected admin group matched nothing above.
        return self::DENY;
    }
}

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

    private function resolvePermissionFromRoute(Request $request): ?string
    {
        $routeName = (string) optional($request->route())->getName();

        if ($routeName === '') {
            return null;
        }

        $map = [
            'admin.dashboard' => 'dashboard.view',
            'admin.roles.' => 'roles.manage',
            'admin.categories.' => 'catalog.manage',
            'admin.products.' => 'catalog.manage',
            'admin.attributes.' => 'catalog.manage',
            'admin.media.' => 'media.manage',
            'admin.orders.' => 'orders.manage',
            'admin.payments.' => 'payments.view',
            'admin.returns.' => 'returns.manage',
            'admin.abandoned-carts.' => 'abandoned_carts.manage',
            'admin.coupons.' => 'marketing.manage',
            'admin.reviews.' => 'marketing.manage',
            'admin.flash-sales.' => 'marketing.manage',
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

        return null;
    }
}

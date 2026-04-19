<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    public function test_products_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/products');

        // Products index is protected by internal.api (shared secret), so unauthenticated
        // requests without that header are forbidden.
        $response->assertForbidden();
    }

    public function test_login_endpoint_remains_publicly_reachable(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
    }

    public function test_only_explicitly_whitelisted_v1_routes_are_public(): void
    {
        $publicWhitelist = [
            'POST api/v1/auth/register',
            'POST api/v1/auth/login',
            'POST api/v1/auth/forgot-password',
            'POST api/v1/auth/reset-password',
            'GET api/v1/auth/email/verify/{id}/{hash}',
            'GET api/v1/stripe/config',
            'POST api/v1/stripe/webhook',
            'POST api/v1/stripe/create-payment-intent',
            'POST api/v1/stripe/confirm-payment',
            'GET api/v1/bkash/config',
            'GET api/v1/bkash/callback',
            'POST api/v1/bkash/create-payment',
            'GET api/v1/track/order/{orderNumber}',
            'GET api/v1/track/tracking/{trackingNumber}',
            'POST api/v1/orders',
            'POST api/v1/cart/coupon',
            'POST api/v1/checkout/track',
            'GET api/v1/orders/{id}/payment-summary',
            'GET api/v1/orders/number/{orderNumber}',
        ];

        $unexpectedPublicRoutes = [];

        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();

            if (!Str::startsWith($uri, 'api/v1/')) {
                continue;
            }

            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            $middleware = $route->gatherMiddleware();
            $hasSanctumAuth = collect($middleware)->contains(function (string $entry) {
                return $entry === 'auth:sanctum' || str_contains($entry, 'Authenticate:sanctum');
            });
            $hasInternalGuard = collect($middleware)->contains(function (string $entry) {
                return $entry === 'internal.api' || str_contains($entry, 'InternalApiOnly');
            });

            foreach ($methods as $method) {
                $signature = sprintf('%s %s', $method, $uri);

                if (in_array($signature, $publicWhitelist, true)) {
                    continue;
                }

                // Non-whitelisted v1 routes must be protected by either sanctum auth
                // or internal.api shared-secret middleware.
                if (!$hasSanctumAuth && !$hasInternalGuard) {
                    $unexpectedPublicRoutes[] = sprintf('%s [%s]', $signature, implode(', ', $middleware));
                }
            }
        }

        $this->assertEmpty(
            $unexpectedPublicRoutes,
            "Unexpected public /api/v1 routes detected:\n" . implode("\n", $unexpectedPublicRoutes)
        );
    }

    public function test_admin_utility_routes_require_admin_middleware(): void
    {
        $routesMissingAdminGuard = [];

        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();

            if (!Str::startsWith($uri, 'api/v1/admin/')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $hasAdminGuard = collect($middleware)->contains(function (string $entry) {
                return $entry === 'is_admin' || str_contains($entry, 'IsAdmin');
            });

            if (!$hasAdminGuard) {
                $methods = implode('|', array_values(array_diff($route->methods(), ['HEAD'])));
                $routesMissingAdminGuard[] = sprintf('%s %s [%s]', $methods, $uri, implode(', ', $middleware));
            }
        }

        $this->assertEmpty(
            $routesMissingAdminGuard,
            "Admin utility routes missing is_admin middleware:\n" . implode("\n", $routesMissingAdminGuard)
        );
    }
}

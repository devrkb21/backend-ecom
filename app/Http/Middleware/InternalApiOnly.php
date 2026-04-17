<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalApiOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredSecret = (string) config('shop.internal_api_secret', '');
        $providedSecret = (string) $request->header('X-Internal-Secret', '');

        if ($configuredSecret === '' || $providedSecret === '' || !hash_equals($configuredSecret, $providedSecret)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}

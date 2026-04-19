<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SavedPaymentMethodResource;
use App\Models\SavedPaymentMethod;
use App\Services\Payment\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedPaymentMethodController extends Controller
{
    public function __construct(
        protected StripePaymentService $stripeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $methods = $request->user()->savedPaymentMethods()
            ->active()
            ->where('gateway', 'stripe')
            ->orderByDesc('is_default')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse(SavedPaymentMethodResource::collection($methods));
    }

    public function setDefault(Request $request, SavedPaymentMethod $savedPaymentMethod): JsonResponse
    {
        if ((int) $savedPaymentMethod->user_id !== (int) $request->user()->id) {
            return $this->errorResponse('Unauthorized.', 403);
        }

        if ($savedPaymentMethod->gateway !== 'stripe') {
            return $this->errorResponse('Unsupported saved payment method gateway.', 400);
        }

        try {
            $this->stripeService->setDefaultSavedPaymentMethod($request->user(), $savedPaymentMethod);

            return $this->successResponse(
                new SavedPaymentMethodResource($savedPaymentMethod->fresh()),
                'Default saved payment method updated.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function remove(Request $request, SavedPaymentMethod $savedPaymentMethod): JsonResponse
    {
        if ((int) $savedPaymentMethod->user_id !== (int) $request->user()->id) {
            return $this->errorResponse('Unauthorized.', 403);
        }

        if ($savedPaymentMethod->gateway !== 'stripe') {
            return $this->errorResponse('Unsupported saved payment method gateway.', 400);
        }

        try {
            $this->stripeService->removeSavedPaymentMethod($request->user(), $savedPaymentMethod);
            return $this->successResponse(null, 'Saved payment method removed.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}

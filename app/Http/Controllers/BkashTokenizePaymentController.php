<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Stub controller for bKash package routes.
 * The actual bKash integration is handled by App\Http\Controllers\Api\BkashController
 * 
 * This controller exists only to satisfy the package's route requirements.
 * These routes are not used in production - use the API endpoints instead.
 */
class BkashTokenizePaymentController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Please use the API endpoints at /api/v1/bkash/*',
            'endpoints' => [
                'config' => '/api/v1/bkash/config',
                'create_payment' => '/api/v1/bkash/create-payment',
                'callback' => '/api/v1/bkash/callback',
            ]
        ], 400);
    }

    public function createPayment(Request $request)
    {
        return redirect('/api/v1/bkash/create-payment');
    }

    public function callBack(Request $request)
    {
        // Redirect to API callback handler
        return app(\App\Http\Controllers\Api\BkashController::class)->callback($request);
    }

    public function searchTnx($trxID)
    {
        return response()->json(['message' => 'Use API endpoint'], 400);
    }

    public function refund(Request $request)
    {
        return response()->json(['message' => 'Use API endpoint /api/v1/bkash/refund'], 400);
    }

    public function refundStatus(Request $request)
    {
        return response()->json(['message' => 'Use API endpoint'], 400);
    }
}

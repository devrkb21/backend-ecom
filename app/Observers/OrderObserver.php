<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\FraudDetectionService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function __construct(
        protected FraudDetectionService $fraudDetectionService
    ) {}

    /**
     * Repeat-offender detection needs to fire no matter which code path
     * moved the order to cancelled/returned — admin status update, the
     * SteadFast webhook, or the Pathao webhook listener all just call
     * Order::update(), so hooking the model event here catches every one
     * of them instead of needing to patch three separate call sites.
     */
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        if (! in_array($order->status, ['cancelled', 'returned'], true)) {
            return;
        }

        try {
            $this->fraudDetectionService->evaluateRepeatOffender($order);
        } catch (\Throwable $e) {
            Log::warning('Fraud repeat-offender evaluation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\CourierCheckResult;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CourierHistoryCheckService;
use App\Services\FraudDetectionService;
use App\Support\FraudNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Looks up an order's phone number across Steadfast/Pathao/RedX/Paperfly/
 * Carrybee via CourierHistoryCheckService and feeds the result into
 * FraudDetectionService::evaluateCourierHistory(). Dispatched right after
 * checkout (App\Http\Controllers\Api\OrderController::store()) so it never
 * blocks or slows down the order — the 5 courier logins this triggers can
 * take several seconds and are individually unreliable.
 */
class CheckCourierHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    // Logging into up to 5 courier portals (with multi-account failover on
    // each) can comfortably exceed the queue worker's default 60s timeout —
    // matches the execution-time headroom given to the synchronous
    // admin-triggered check in Admin\OrderController::checkCourierHistory().
    public int $timeout = 180;

    public function __construct(
        public int $orderId,
        public bool $force = false
    ) {}

    public function handle(CourierHistoryCheckService $courierHistoryCheckService, FraudDetectionService $fraudDetectionService): void
    {
        if (!filter_var(Setting::getValue('fraud_blocks', 'courier_check_enabled', '0'), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $order = Order::find($this->orderId);
        if (!$order) {
            return;
        }

        $normalizedPhone = $order->normalized_phone ?: FraudNormalizer::phone($order->shipping_phone);
        if ($normalizedPhone === null) {
            return;
        }

        $freshnessDays = max(1, (int) Setting::getValue('fraud_blocks', 'courier_check_freshness_days', 7));
        $existing = CourierCheckResult::where('normalized_phone', $normalizedPhone)->first();

        if (!$this->force && $existing && $existing->isFresh($freshnessDays)) {
            return;
        }

        $checkResult = $courierHistoryCheckService->check($normalizedPhone, $order->id);

        $fraudDetectionService->evaluateCourierHistory($order, [
            'total_deliveries' => $checkResult->total_deliveries,
            'success_ratio' => (float) $checkResult->success_ratio,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CheckCourierHistoryJob permanently failed', [
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);
    }
}

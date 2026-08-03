<?php

namespace App\Services;

use App\Models\FraudBlock;
use App\Models\Order;
use App\Models\Setting;
use App\Support\FraudNormalizer;
use Illuminate\Support\Facades\Log;

/**
 * Automated layer on top of the manual FraudBlock blocklist:
 *
 *  - checkBlocklist()      exact block-list hit (existing behaviour, now
 *                           format-normalized)
 *  - checkVelocity()       hard rate-limit on how many orders a phone/IP/
 *                           device can place in a rolling window
 *  - evaluateRepeatOffender()  runs when an order lands on cancelled/returned;
 *                           auto-blocks or flags-for-review once a phone
 *                           crosses the configured bad-order threshold
 *
 * All thresholds live in the `fraud_blocks` settings group and are admin
 * configurable from the Fraud Blocker screen.
 */
class FraudDetectionService
{
    private const SETTINGS_GROUP = 'fraud_blocks';

    public function checkBlocklist(?string $phone, ?string $email, ?string $ip, ?string $userAgent): array
    {
        $result = FraudBlock::checkOrder($phone, $email, $ip, $userAgent);

        return [
            'blocked' => !empty($result['types']),
            'types' => $result['types'],
            'message' => $result['message'],
        ];
    }

    /**
     * Hard rate-limit: reject the checkout outright once a phone/IP/device
     * has placed >= the configured number of orders within the configured
     * window. Independent of the manual blocklist — this is what stops an
     * order-bombing burst before anyone has had a chance to block anything.
     */
    public function checkVelocity(?string $phone, ?string $ip, ?string $userAgent): array
    {
        if (!$this->settingBool('velocity_enabled', true)) {
            return ['exceeded' => false, 'violations' => []];
        }

        $limit = max(1, (int) Setting::getValue(self::SETTINGS_GROUP, 'velocity_limit_count', 5));
        $windowMinutes = max(1, (int) Setting::getValue(self::SETTINGS_GROUP, 'velocity_limit_window_minutes', 60));
        $since = now()->subMinutes($windowMinutes);

        $violations = [];

        $normalizedPhone = FraudNormalizer::phone($phone);
        if ($normalizedPhone !== null) {
            $count = Order::where('normalized_phone', $normalizedPhone)
                ->where('created_at', '>=', $since)
                ->count();
            if ($count >= $limit) {
                $violations['phone'] = $count;
            }
        }

        $normalizedIp = FraudNormalizer::ip($ip);
        if ($normalizedIp !== null) {
            $count = Order::where('device_ip', $normalizedIp)
                ->where('created_at', '>=', $since)
                ->count();
            if ($count >= $limit) {
                $violations['ip'] = $count;
            }
        }

        $deviceHash = FraudNormalizer::device($userAgent);
        if ($deviceHash !== null) {
            $count = Order::where('device_hash', $deviceHash)
                ->where('created_at', '>=', $since)
                ->count();
            if ($count >= $limit) {
                $violations['device'] = $count;
            }
        }

        return [
            'exceeded' => !empty($violations),
            'violations' => $violations,
            'limit' => $limit,
            'window_minutes' => $windowMinutes,
        ];
    }

    /**
     * Stamp device/phone identifiers onto a freshly-created order so future
     * velocity and repeat-offender queries can find it. Called after order
     * creation succeeds (not before), so a failed checkout never pollutes
     * the velocity counters.
     */
    public function tagOrder(Order $order, ?string $ip, ?string $userAgent, ?string $phone): void
    {
        $order->forceFill([
            'device_ip' => FraudNormalizer::ip($ip),
            'device_hash' => FraudNormalizer::device($userAgent),
            'normalized_phone' => FraudNormalizer::phone($phone ?: $order->shipping_phone),
        ])->save();
    }

    /**
     * Run after an order transitions to cancelled/returned. Counts how many
     * of this phone's orders have ended badly; once the configured threshold
     * is crossed, either auto-blocks the phone outright or files it as a
     * "needs review" entry for an admin to confirm, per the configured
     * `repeat_offender_action` setting.
     */
    public function evaluateRepeatOffender(Order $order): void
    {
        if (!$this->settingBool('repeat_offender_enabled', true)) {
            return;
        }

        $normalizedPhone = $order->normalized_phone ?: FraudNormalizer::phone($order->shipping_phone);

        if ($normalizedPhone === null) {
            return;
        }

        $threshold = max(1, (int) Setting::getValue(self::SETTINGS_GROUP, 'repeat_offender_threshold', 3));

        $stats = Order::where('normalized_phone', $normalizedPhone)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $badCount = (int) ($stats['cancelled'] ?? 0) + (int) ($stats['returned'] ?? 0);
        $totalCount = (int) $stats->sum();

        if ($badCount < $threshold) {
            return;
        }

        $action = Setting::getValue(self::SETTINGS_GROUP, 'repeat_offender_action', 'flag');
        $reason = "Auto-detected: {$badCount} cancelled/returned out of {$totalCount} orders for this phone number.";

        $flagged = $this->autoFlagPhone($normalizedPhone, $reason, $action, $order->id);

        if ($flagged) {
            Log::info('Fraud: repeat-offender threshold crossed', [
                'phone' => $normalizedPhone,
                'bad_count' => $badCount,
                'total_count' => $totalCount,
                'action' => $action,
                'order_id' => $order->id,
            ]);
        }
    }

    /**
     * Run after a CheckCourierHistoryJob result comes back for an order's
     * phone number. Independent signal from evaluateRepeatOffender() — this
     * one can catch a customer with a bad record who has never ordered from
     * this store before, since it looks at cross-courier history rather
     * than in-house order history.
     */
    public function evaluateCourierHistory(Order $order, array $result): void
    {
        $normalizedPhone = $order->normalized_phone ?: FraudNormalizer::phone($order->shipping_phone);

        if ($normalizedPhone === null) {
            return;
        }

        $this->evaluateCourierHistoryForPhone($normalizedPhone, $result, $order->id);
    }

    /**
     * Phone-based variant of evaluateCourierHistory() for the ad-hoc
     * "search any number" tool on the Courier Checker page, which has no
     * Order to hang the evaluation off of.
     */
    public function evaluateCourierHistoryForPhone(string $normalizedPhone, array $result, ?int $orderId = null): void
    {
        if (!$this->settingBool('courier_check_enabled', false)) {
            return;
        }

        $minOrders = max(1, (int) Setting::getValue(self::SETTINGS_GROUP, 'courier_check_min_orders', 3));
        $maxCancelRatio = (float) Setting::getValue(self::SETTINGS_GROUP, 'courier_check_max_cancel_ratio', 40);

        $totalDeliveries = (int) ($result['total_deliveries'] ?? 0);
        $successRatio = (float) ($result['success_ratio'] ?? 100);
        $cancelRatio = 100 - $successRatio;

        if ($totalDeliveries < $minOrders || $cancelRatio < $maxCancelRatio) {
            return;
        }

        $action = Setting::getValue(self::SETTINGS_GROUP, 'courier_check_action', 'flag');
        $reason = sprintf(
            'Auto-detected: %.0f%% cancel rate across %d deliveries on other couriers for this phone number.',
            $cancelRatio,
            $totalDeliveries
        );

        $flagged = $this->autoFlagPhone($normalizedPhone, $reason, $action, $orderId);

        if ($flagged) {
            Log::info('Fraud: cross-courier history threshold crossed', [
                'phone' => $normalizedPhone,
                'total_deliveries' => $totalDeliveries,
                'cancel_ratio' => $cancelRatio,
                'action' => $action,
                'order_id' => $orderId,
            ]);
        }
    }

    /**
     * Shared auto-flag pathway for every automated risk signal (repeat
     * in-house cancellations, cross-courier history, future signals).
     * Never overwrites a block a human admin created by hand. Returns
     * whether it actually wrote/updated a block.
     */
    private function autoFlagPhone(string $normalizedPhone, string $reason, string $action, ?int $orderId): bool
    {
        $isAutoBlock = $action === 'auto_block';

        $block = FraudBlock::where('type', 'phone')
            ->where('normalized_value', $normalizedPhone)
            ->first();

        if ($block && $block->source === 'manual') {
            return false;
        }

        FraudBlock::updateOrCreate(
            ['type' => 'phone', 'value' => $normalizedPhone],
            [
                'reason' => $reason,
                'source' => 'auto',
                'is_active' => $isAutoBlock,
                'needs_review' => !$isAutoBlock,
                'order_id' => $orderId,
                'blocked_by' => null,
            ]
        );

        return true;
    }

    private function settingBool(string $key, bool $default): bool
    {
        $value = Setting::getValue(self::SETTINGS_GROUP, $key, $default ? '1' : '0');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\LoyaltyTransaction;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyTier;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    // Points earning rate (points per currency unit spent)
    protected int $pointsPerUnit = 1; // 1 point per ৳1 spent
    protected int $pointsExpireDays = 365; // Points expire after 1 year

    /**
     * Award points for an order
     */
    public function awardOrderPoints(Order $order): ?LoyaltyTransaction
    {
        if (!$order->user_id || $order->status === 'cancelled' || $order->status === 'failed') {
            return null;
        }

        $user = $order->user;

        // Check if points already awarded for this order
        $existing = LoyaltyTransaction::where('order_id', $order->id)
            ->where('type', LoyaltyTransaction::TYPE_EARNED)
            ->exists();

        if ($existing) {
            return null;
        }

        // Calculate points based on order total (excluding shipping)
        $eligibleAmount = $order->subtotal - ($order->discount_amount ?? 0);
        $basePoints = (int) floor($eligibleAmount * $this->pointsPerUnit);

        // Apply tier multiplier
        $tier = $this->getUserTier($user);
        $multiplier = $tier ? $tier->points_multiplier : 1;
        $totalPoints = (int) floor($basePoints * $multiplier);

        if ($totalPoints <= 0) {
            return null;
        }

        return $this->addPoints(
            $user,
            $totalPoints,
            LoyaltyTransaction::TYPE_EARNED,
            "Points earned from order #{$order->order_number}",
            $order->id,
            ['base_points' => $basePoints, 'multiplier' => $multiplier, 'order_total' => $eligibleAmount]
        );
    }

    /**
     * Add points to user
     */
    public function addPoints(
        User $user,
        int $points,
        string $type,
        string $description,
        ?int $orderId = null,
        ?array $metadata = null
    ): LoyaltyTransaction {
        $expiresAt = in_array($type, [LoyaltyTransaction::TYPE_EARNED, LoyaltyTransaction::TYPE_BONUS, LoyaltyTransaction::TYPE_REFERRAL])
            ? now()->addDays($this->pointsExpireDays)
            : null;

        $newBalance = $user->loyalty_points + $points;

        $transaction = LoyaltyTransaction::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'type' => $type,
            'points' => $points,
            'balance_after' => $newBalance,
            'description' => $description,
            'metadata' => $metadata,
            'expires_at' => $expiresAt,
        ]);

        // Update user points
        $user->update([
            'loyalty_points' => $newBalance,
            'lifetime_points' => $user->lifetime_points + ($points > 0 ? $points : 0),
        ]);

        // Check for tier upgrade
        $this->checkAndUpdateTier($user);

        return $transaction;
    }

    /**
     * Deduct points from user
     */
    public function deductPoints(
        User $user,
        int $points,
        string $type,
        string $description,
        ?int $orderId = null,
        ?array $metadata = null
    ): LoyaltyTransaction {
        $newBalance = max(0, $user->loyalty_points - $points);

        $transaction = LoyaltyTransaction::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'type' => $type,
            'points' => -$points,
            'balance_after' => $newBalance,
            'description' => $description,
            'metadata' => $metadata,
        ]);

        $user->update(['loyalty_points' => $newBalance]);

        return $transaction;
    }

    /**
     * Redeem a reward
     */
    public function redeemReward(User $user, LoyaltyReward $reward): array
    {
        // Check if can redeem
        $canRedeem = $reward->canRedeem($user);
        if (!$canRedeem['allowed']) {
            return ['success' => false, 'error' => $canRedeem['reason']];
        }

        DB::beginTransaction();

        try {
            // Deduct points
            $this->deductPoints(
                $user,
                $reward->points_required,
                LoyaltyTransaction::TYPE_REDEEMED,
                "Redeemed: {$reward->name}",
                null,
                ['reward_id' => $reward->id]
            );

            // Generate coupon code if applicable
            $couponCode = null;
            if (in_array($reward->reward_type, [LoyaltyReward::TYPE_DISCOUNT_PERCENTAGE, LoyaltyReward::TYPE_DISCOUNT_FIXED, LoyaltyReward::TYPE_COUPON])) {
                $couponCode = 'LYL-' . strtoupper(Str::random(8));
            }

            // Create redemption record
            $redemption = LoyaltyRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_spent' => $reward->points_required,
                'coupon_code' => $couponCode,
                'status' => LoyaltyRedemption::STATUS_PENDING,
                'expires_at' => now()->addDays(30), // Redemption valid for 30 days
            ]);

            // Update reward redeemed count
            $reward->increment('redeemed_count');

            DB::commit();

            return [
                'success' => true,
                'redemption' => $redemption,
                'coupon_code' => $couponCode,
                'message' => $this->getRedemptionMessage($reward, $couponCode),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'error' => 'Failed to redeem reward. Please try again.'];
        }
    }

    /**
     * Apply redemption to order
     */
    public function applyRedemptionToOrder(LoyaltyRedemption $redemption, Order $order): array
    {
        if (!$redemption->isValid()) {
            return ['success' => false, 'error' => 'Redemption is no longer valid'];
        }

        $reward = $redemption->reward;
        $discount = 0;

        switch ($reward->reward_type) {
            case LoyaltyReward::TYPE_DISCOUNT_PERCENTAGE:
                $discount = $order->subtotal * ($reward->reward_value / 100);
                break;

            case LoyaltyReward::TYPE_DISCOUNT_FIXED:
            case LoyaltyReward::TYPE_COUPON:
                $discount = min($reward->reward_value, $order->subtotal);
                break;

            case LoyaltyReward::TYPE_FREE_SHIPPING:
                $discount = $order->shipping;
                break;
        }

        $redemption->markApplied($order->id);

        return [
            'success' => true,
            'discount' => round($discount, 2),
            'type' => $reward->reward_type,
        ];
    }

    /**
     * Get user's point balance and summary
     */
    public function getUserPointsSummary(User $user): array
    {
        $tier = $this->getUserTier($user);
        $nextTier = $tier?->getNextTier();

        // Get expiring points
        $expiringPoints = LoyaltyTransaction::where('user_id', $user->id)
            ->expiring(30)
            ->sum('points');

        // Recent transactions
        $recentTransactions = LoyaltyTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'current_points' => $user->loyalty_points,
            'lifetime_points' => $user->lifetime_points,
            'tier' => $tier ? [
                'name' => $tier->name,
                'slug' => $tier->slug,
                'multiplier' => $tier->points_multiplier,
                'benefits' => $tier->getBenefitsArray(),
                'badge_image' => $tier->badge_image,
            ] : null,
            'next_tier' => $nextTier ? [
                'name' => $nextTier->name,
                'points_needed' => $tier->getPointsToNextTier($user->lifetime_points),
            ] : null,
            'expiring_points' => $expiringPoints,
            'recent_transactions' => $recentTransactions,
        ];
    }

    /**
     * Get available rewards for user
     */
    public function getAvailableRewards(User $user): Collection
    {
        return LoyaltyReward::active()
            ->orderBy('points_required')
            ->get()
            ->map(function ($reward) use ($user) {
                $reward->can_redeem = $reward->canRedeem($user)['allowed'];
                $reward->points_needed = max(0, $reward->points_required - $user->loyalty_points);
                return $reward;
            });
    }

    /**
     * Get user's redemption history
     */
    public function getUserRedemptions(User $user): Collection
    {
        return LoyaltyRedemption::where('user_id', $user->id)
            ->with('reward')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user tier
     */
    public function getUserTier(User $user): ?LoyaltyTier
    {
        return LoyaltyTier::getTierForPoints($user->lifetime_points);
    }

    /**
     * Check and update user tier
     */
    protected function checkAndUpdateTier(User $user): void
    {
        $newTier = LoyaltyTier::getTierForPoints($user->lifetime_points);

        if ($newTier && $newTier->slug !== $user->loyalty_tier) {
            $oldTier = $user->loyalty_tier;
            $user->update(['loyalty_tier' => $newTier->slug]);

            // Award bonus points for tier upgrade
            if ($newTier->min_points > 0) {
                $this->addPoints(
                    $user,
                    50, // Bonus points for tier upgrade
                    LoyaltyTransaction::TYPE_BONUS,
                    "Congratulations! You've been upgraded to {$newTier->name} tier",
                    null,
                    ['old_tier' => $oldTier, 'new_tier' => $newTier->slug]
                );
            }
        }
    }

    /**
     * Award referral bonus
     */
    public function awardReferralBonus(User $referrer, User $referee, ?Order $order = null): LoyaltyTransaction
    {
        $bonusPoints = 100; // Fixed referral bonus

        return $this->addPoints(
            $referrer,
            $bonusPoints,
            LoyaltyTransaction::TYPE_REFERRAL,
            "Referral bonus: {$referee->name} made their first purchase",
            $order?->id,
            ['referee_id' => $referee->id]
        );
    }

    /**
     * Award birthday bonus
     */
    public function awardBirthdayBonus(User $user): ?LoyaltyTransaction
    {
        // Check if already awarded this year
        $alreadyAwarded = LoyaltyTransaction::where('user_id', $user->id)
            ->where('type', LoyaltyTransaction::TYPE_BONUS)
            ->where('description', 'like', '%birthday%')
            ->whereYear('created_at', now()->year)
            ->exists();

        if ($alreadyAwarded) {
            return null;
        }

        $tier = $this->getUserTier($user);
        $bonusPoints = $tier?->birthday_bonus ?? 50;

        if ($bonusPoints <= 0) {
            return null;
        }

        return $this->addPoints(
            $user,
            $bonusPoints,
            LoyaltyTransaction::TYPE_BONUS,
            "Happy Birthday! Here's your birthday bonus",
            null,
            ['birthday_year' => now()->year]
        );
    }

    /**
     * Expire old points
     */
    public function expireOldPoints(): int
    {
        $expiredCount = 0;

        $expiredTransactions = LoyaltyTransaction::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('type', LoyaltyTransaction::TYPE_EARNED)
            ->where('points', '>', 0)
            ->get()
            ->groupBy('user_id');

        foreach ($expiredTransactions as $userId => $transactions) {
            $user = User::find($userId);
            if (!$user) continue;

            $totalExpired = $transactions->sum('points');

            // Create expiration transaction
            $this->deductPoints(
                $user,
                $totalExpired,
                LoyaltyTransaction::TYPE_EXPIRED,
                "Points expired",
                null,
                ['expired_transaction_ids' => $transactions->pluck('id')->toArray()]
            );

            // Mark original transactions as processed
            LoyaltyTransaction::whereIn('id', $transactions->pluck('id'))
                ->update(['expires_at' => null]); // Clear to prevent re-processing

            $expiredCount += $totalExpired;
        }

        return $expiredCount;
    }

    /**
     * Reverse points for cancelled order
     */
    public function reverseOrderPoints(Order $order): ?LoyaltyTransaction
    {
        $earnedTransaction = LoyaltyTransaction::where('order_id', $order->id)
            ->where('type', LoyaltyTransaction::TYPE_EARNED)
            ->first();

        if (!$earnedTransaction) {
            return null;
        }

        return $this->deductPoints(
            $order->user,
            $earnedTransaction->points,
            LoyaltyTransaction::TYPE_ADJUSTED,
            "Points reversed for cancelled order #{$order->order_number}",
            $order->id,
            ['original_transaction_id' => $earnedTransaction->id]
        );
    }

    /**
     * Get redemption message
     */
    protected function getRedemptionMessage(LoyaltyReward $reward, ?string $couponCode): string
    {
        return match ($reward->reward_type) {
            LoyaltyReward::TYPE_DISCOUNT_PERCENTAGE => "Use code {$couponCode} at checkout for {$reward->reward_value}% off!",
            LoyaltyReward::TYPE_DISCOUNT_FIXED => "Use code {$couponCode} at checkout for ৳{$reward->reward_value} off!",
            LoyaltyReward::TYPE_FREE_SHIPPING => "Your next order qualifies for free shipping!",
            LoyaltyReward::TYPE_FREE_PRODUCT => "A free product has been added to your account!",
            LoyaltyReward::TYPE_COUPON => "Use code {$couponCode} at checkout!",
            default => "Reward redeemed successfully!",
        };
    }

    /**
     * Calculate points value in currency
     */
    public function getPointsValue(int $points): float
    {
        // 100 points = ৳1
        return round($points / 100, 2);
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(int $limit = 10): Collection
    {
        return User::where('lifetime_points', '>', 0)
            ->orderBy('lifetime_points', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'lifetime_points', 'loyalty_tier']);
    }
}

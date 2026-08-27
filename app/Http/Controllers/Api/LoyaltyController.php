<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTier;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * Get user's loyalty summary
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $summary = $this->loyaltyService->getUserPointsSummary($user);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get user's transaction history
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $this->perPage(20);

        $transactions = $user->loyaltyTransactions()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get available rewards
     */
    public function rewards(Request $request): JsonResponse
    {
        $user = $request->user();
        $rewards = $this->loyaltyService->getAvailableRewards($user);

        return response()->json([
            'success' => true,
            'data' => $rewards->map(fn ($reward) => [
                'id' => $reward->id,
                'name' => $reward->name,
                'description' => $reward->description,
                'image' => $reward->image,
                'points_required' => $reward->points_required,
                'reward_type' => $reward->reward_type,
                'reward_value' => $reward->reward_value,
                'reward_description' => $reward->getRewardDescription(),
                'quantity_remaining' => $reward->quantity_remaining,
                'can_redeem' => $reward->can_redeem,
                'points_needed' => $reward->points_needed,
                'ends_at' => $reward->ends_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Redeem a reward
     */
    public function redeem(Request $request): JsonResponse
    {
        $request->validate([
            'reward_id' => 'required|exists:loyalty_rewards,id',
        ]);

        $user = $request->user();
        $reward = LoyaltyReward::findOrFail($request->reward_id);

        $result = $this->loyaltyService->redeemReward($user, $reward);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'redemption_id' => $result['redemption']->id,
                'coupon_code' => $result['coupon_code'],
                'expires_at' => $result['redemption']->expires_at?->toIso8601String(),
                'points_remaining' => $user->fresh()->loyalty_points,
            ],
        ]);
    }

    /**
     * Get user's redemptions
     */
    public function redemptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $redemptions = $this->loyaltyService->getUserRedemptions($user);

        return response()->json([
            'success' => true,
            'data' => $redemptions->map(fn ($r) => [
                'id' => $r->id,
                'reward' => [
                    'name' => $r->reward->name,
                    'type' => $r->reward->reward_type,
                    'value' => $r->reward->reward_value,
                ],
                'points_spent' => $r->points_spent,
                'coupon_code' => $r->coupon_code,
                'status' => $r->status,
                'expires_at' => $r->expires_at?->toIso8601String(),
                'created_at' => $r->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Get active (unused) redemptions
     */
    public function activeRedemptions(Request $request): JsonResponse
    {
        $user = $request->user();

        $redemptions = LoyaltyRedemption::where('user_id', $user->id)
            ->valid()
            ->with('reward')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $redemptions->map(fn ($r) => [
                'id' => $r->id,
                'reward' => [
                    'name' => $r->reward->name,
                    'type' => $r->reward->reward_type,
                    'value' => $r->reward->reward_value,
                    'description' => $r->reward->getRewardDescription(),
                ],
                'coupon_code' => $r->coupon_code,
                'expires_at' => $r->expires_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Cancel a pending redemption
     */
    public function cancelRedemption(LoyaltyRedemption $redemption, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($redemption->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($redemption->status !== LoyaltyRedemption::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending redemptions can be cancelled',
            ], 400);
        }

        $redemption->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Redemption cancelled. Points have been refunded.',
            'data' => [
                'points_refunded' => $redemption->points_spent,
                'current_points' => $user->fresh()->loyalty_points,
            ],
        ]);
    }

    /**
     * Validate redemption coupon code
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $redemption = LoyaltyRedemption::where('coupon_code', $request->coupon_code)
            ->valid()
            ->with('reward')
            ->first();

        if (! $redemption) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Invalid or expired coupon code',
            ]);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'data' => [
                'redemption_id' => $redemption->id,
                'reward_type' => $redemption->reward->reward_type,
                'reward_value' => $redemption->reward->reward_value,
                'description' => $redemption->reward->getRewardDescription(),
            ],
        ]);
    }

    /**
     * Get loyalty tiers info
     */
    public function tiers(): JsonResponse
    {
        $tiers = LoyaltyTier::orderBy('min_points')->get();

        return response()->json([
            'success' => true,
            'data' => $tiers->map(fn ($tier) => [
                'name' => $tier->name,
                'slug' => $tier->slug,
                'min_points' => $tier->min_points,
                'points_multiplier' => $tier->points_multiplier,
                'benefits' => $tier->getBenefitsArray(),
                'badge_image' => $tier->badge_image,
            ]),
        ]);
    }

    /**
     * Get points leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $leaderboard = $this->loyaltyService->getLeaderboard($limit);

        $user = $request->user();
        $userRank = null;

        if ($user) {
            $userRank = User::where('lifetime_points', '>', $user->lifetime_points)->count() + 1;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $leaderboard->map(fn ($u, $index) => [
                    'rank' => $index + 1,
                    'name' => $u->name,
                    'lifetime_points' => $u->lifetime_points,
                    'tier' => $u->loyalty_tier,
                ]),
                'your_rank' => $userRank,
            ],
        ]);
    }
}

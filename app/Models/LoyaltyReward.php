<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyReward extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'points_required',
        'reward_type',
        'reward_value',
        'product_id',
        'quantity_available',
        'redeemed_count',
        'per_user_limit',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'reward_value' => 'decimal:2',
        'quantity_available' => 'integer',
        'redeemed_count' => 'integer',
        'per_user_limit' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Reward types
    const TYPE_DISCOUNT_PERCENTAGE = 'discount_percentage';

    const TYPE_DISCOUNT_FIXED = 'discount_fixed';

    const TYPE_FREE_SHIPPING = 'free_shipping';

    const TYPE_FREE_PRODUCT = 'free_product';

    const TYPE_COUPON = 'coupon';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRedemption::class, 'reward_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }

    public function scopeAffordable($query, int $points)
    {
        return $query->where('points_required', '<=', $points);
    }

    // Accessors
    public function getIsAvailableAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at > now()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at <= now()) {
            return false;
        }

        if ($this->quantity_available !== null && $this->redeemed_count >= $this->quantity_available) {
            return false;
        }

        return true;
    }

    public function getQuantityRemainingAttribute(): ?int
    {
        if ($this->quantity_available === null) {
            return null;
        }

        return max(0, $this->quantity_available - $this->redeemed_count);
    }

    // Methods
    public function canRedeem(User $user): array
    {
        if (! $this->is_available) {
            return ['allowed' => false, 'reason' => 'This reward is not available'];
        }

        if ($user->loyalty_points < $this->points_required) {
            return ['allowed' => false, 'reason' => 'Insufficient points', 'required' => $this->points_required, 'current' => $user->loyalty_points];
        }

        // Check per-user limit
        if ($this->per_user_limit > 0) {
            $userRedemptions = $this->redemptions()
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'applied'])
                ->count();

            if ($userRedemptions >= $this->per_user_limit) {
                return ['allowed' => false, 'reason' => 'You have reached the redemption limit for this reward'];
            }
        }

        return ['allowed' => true];
    }

    public function getRewardDescription(): string
    {
        return match ($this->reward_type) {
            self::TYPE_DISCOUNT_PERCENTAGE => "{$this->reward_value}% off your order",
            self::TYPE_DISCOUNT_FIXED => "৳{$this->reward_value} off your order",
            self::TYPE_FREE_SHIPPING => 'Free shipping on your order',
            self::TYPE_FREE_PRODUCT => 'Free product: '.($this->product?->name ?? 'Selected item'),
            self::TYPE_COUPON => "Special coupon worth ৳{$this->reward_value}",
            default => $this->description,
        };
    }
}

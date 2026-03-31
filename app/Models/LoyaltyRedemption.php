<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyRedemption extends Model
{
    protected $fillable = [
        'user_id',
        'reward_id',
        'order_id',
        'points_spent',
        'coupon_code',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'expires_at' => 'datetime',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPLIED = 'applied';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class, 'reward_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPLIED]);
    }

    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    // Methods
    public function markApplied(int $orderId): void
    {
        $this->update([
            'status' => self::STATUS_APPLIED,
            'order_id' => $orderId,
        ]);
    }

    public function markExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function cancel(): void
    {
        if ($this->status === self::STATUS_PENDING) {
            // Refund points
            $this->user->adjustLoyaltyPoints(
                $this->points_spent,
                'adjusted',
                "Cancelled reward redemption: {$this->reward->name}"
            );

            $this->update(['status' => self::STATUS_CANCELLED]);
        }
    }

    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if ($this->expires_at && $this->expires_at <= now()) {
            return false;
        }

        return true;
    }
}

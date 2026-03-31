<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'points',
        'balance_after',
        'description',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    // Transaction types
    const TYPE_EARNED = 'earned';
    const TYPE_REDEEMED = 'redeemed';
    const TYPE_EXPIRED = 'expired';
    const TYPE_ADJUSTED = 'adjusted';
    const TYPE_BONUS = 'bonus';
    const TYPE_REFERRAL = 'referral';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeEarned($query)
    {
        return $query->where('type', self::TYPE_EARNED);
    }

    public function scopeRedeemed($query)
    {
        return $query->where('type', self::TYPE_REDEEMED);
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('points', '>', 0);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('type', '!=', self::TYPE_EXPIRED);
    }
}

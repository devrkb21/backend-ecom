<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleProduct extends Model
{
    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'flash_price',
        'original_price',
        'discount_percentage',
        'quantity_limit',
        'sold_count',
        'per_user_limit',
        'is_active',
    ];

    protected $casts = [
        'flash_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percentage' => 'integer',
        'quantity_limit' => 'integer',
        'sold_count' => 'integer',
        'per_user_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['stock_remaining', 'is_sold_out'];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('quantity_limit')
                    ->orWhereRaw('sold_count < quantity_limit');
            });
    }

    // Accessors
    public function getStockRemainingAttribute(): ?int
    {
        if ($this->quantity_limit === null) {
            return null; // Unlimited
        }

        return max(0, $this->quantity_limit - $this->sold_count);
    }

    public function getIsSoldOutAttribute(): bool
    {
        if ($this->quantity_limit === null) {
            return false;
        }

        return $this->sold_count >= $this->quantity_limit;
    }

    // Methods
    public function canPurchase(int $quantity = 1, ?int $userId = null): array
    {
        // Check if flash sale is active
        if (!$this->flashSale->isLive()) {
            return ['allowed' => false, 'reason' => 'Flash sale is not active'];
        }

        // Check if product is active in flash sale
        if (!$this->is_active) {
            return ['allowed' => false, 'reason' => 'Product is not available in this flash sale'];
        }

        // Check quantity limit
        if ($this->quantity_limit !== null) {
            $remaining = $this->quantity_limit - $this->sold_count;
            if ($quantity > $remaining) {
                return ['allowed' => false, 'reason' => "Only {$remaining} units available", 'available' => $remaining];
            }
        }

        // Check per-user limit
        if ($userId && $this->per_user_limit > 0) {
            $userPurchased = $this->getUserPurchaseCount($userId);
            $canBuy = $this->per_user_limit - $userPurchased;
            if ($quantity > $canBuy) {
                return ['allowed' => false, 'reason' => "You can only buy {$canBuy} more of this item", 'available' => $canBuy];
            }
        }

        return ['allowed' => true];
    }

    public function getUserPurchaseCount(int $userId): int
    {
        return \App\Models\OrderItem::whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->whereNotIn('status', ['cancelled', 'failed'])
                ->whereBetween('created_at', [$this->flashSale->starts_at, $this->flashSale->ends_at]);
        })
            ->where('product_id', $this->product_id)
            ->sum('quantity');
    }

    public function incrementSoldCount(int $quantity = 1): void
    {
        $this->increment('sold_count', $quantity);
    }
}

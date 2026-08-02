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

    /**
     * Atomically check limits and reserve stock for a purchase of the given
     * product, if it's currently part of a live flash sale. Must be called
     * from inside an existing DB transaction (checkout already wraps order
     * creation in one) so the row lock actually holds until commit.
     *
     * Returns null if the product isn't in an active flash sale (caller
     * should fall back to regular pricing). Throws if it is, but the
     * requested quantity can't be fulfilled (sold out / limit exceeded) —
     * the caller decides whether that aborts checkout.
     *
     * @return array{flash_sale_product_id:int,unit_price:float}|null
     */
    public static function reserveActiveForProduct(int $productId, int $quantity, ?int $userId): ?array
    {
        $flashSaleProduct = static::query()
            ->whereHas('flashSale', fn ($query) => $query->active())
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (!$flashSaleProduct) {
            return null;
        }

        // Re-check liveness after the lock — an in-flight write could have
        // just deactivated the sale/product between the query above and now.
        if (!$flashSaleProduct->flashSale || !$flashSaleProduct->flashSale->isLive() || !$flashSaleProduct->is_active) {
            return null;
        }

        $check = $flashSaleProduct->canPurchase($quantity, $userId);
        if (!$check['allowed']) {
            throw new \Exception($check['reason'] ?? 'This flash sale item is no longer available in the requested quantity.');
        }

        $flashSaleProduct->incrementSoldCount($quantity);

        return [
            'flash_sale_product_id' => $flashSaleProduct->id,
            'unit_price' => (float) $flashSaleProduct->flash_price,
        ];
    }
}

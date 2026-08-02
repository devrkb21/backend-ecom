<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price_adjustment',
        'purchase_price',
        'regular_price',
        'discounted_price',
        'stock_quantity',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_variant_attributes',
            'variant_id',
            'attribute_value_id'
        );
    }

    /**
     * Get the image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            $normalized = ltrim((string) $this->image, '/');

            if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
                return $this->image;
            }

            if (str_starts_with($normalized, 'media/') || str_starts_with($normalized, 'storage/')) {
                return asset($normalized);
            }

            return asset('storage/' . $normalized);
        }
        return null;
    }

    /**
     * Get the variant purchase/cost price.
     */
    public function getPurchasePriceAttribute(): float
    {
        $storedPurchasePrice = $this->attributes['purchase_price'] ?? null;

        if ($storedPurchasePrice !== null) {
            return round(max(0, (float) $storedPurchasePrice), 2);
        }

        return round(max(0, (float) ($this->product?->buy_price ?? 0)), 2);
    }

    /**
     * Get the variant regular price (base regular price + adjustment).
     */
    public function getRegularPriceAttribute(): float
    {
        $storedRegularPrice = $this->attributes['regular_price'] ?? null;
        if ($storedRegularPrice !== null) {
            return round(max(0, (float) $storedRegularPrice), 2);
        }

        $baseRegularPrice = (float) ($this->product?->regular_price ?? 0);

        return round(max(0, $baseRegularPrice + (float) $this->price_adjustment), 2);
    }

    /**
     * Get the variant discounted/current price (base sale or regular + adjustment).
     */
    public function getDiscountedPriceAttribute(): float
    {
        $storedDiscountedPrice = $this->attributes['discounted_price'] ?? null;
        if ($storedDiscountedPrice !== null) {
            return round(max(0, (float) $storedDiscountedPrice), 2);
        }

        $storedRegularPrice = $this->attributes['regular_price'] ?? null;
        if ($storedRegularPrice !== null) {
            return round(max(0, (float) $storedRegularPrice), 2);
        }

        $baseDiscountedPrice = (float) ($this->product?->sale_price ?? $this->product?->regular_price ?? 0);

        return round(max(0, $baseDiscountedPrice + (float) $this->price_adjustment), 2);
    }

    /**
     * Backward compatibility alias used by frontend/cart snapshots.
     */
    public function getFinalPriceAttribute(): float
    {
        return (float) $this->discounted_price;
    }

    /**
     * Get variant name from attributes (e.g., "Red / Large")
     */
    public function getNameAttribute(): string
    {
        return $this->attributeValues->pluck('value')->implode(' / ');
    }

    public function hasStock(int $quantity = 1): bool
    {
        if (!Product::isStockEnabled()) {
            return true;
        }

        return (int) $this->stock_quantity >= max(1, $quantity);
    }

    public function decrementStock(int $quantity): void
    {
        if (!Product::isStockEnabled()) {
            return;
        }

        $this->decrement('stock_quantity', $quantity);
    }

    /**
     * Atomically decrement stock only if enough is still available, in a
     * single conditional UPDATE (WHERE stock_quantity >= quantity) — this
     * closes the race that a separate hasStock() check + decrementStock()
     * call leaves open between two concurrent checkouts for the last unit.
     * Returns false (and leaves stock untouched) if not enough remains.
     */
    public function decrementStockIfAvailable(int $quantity): bool
    {
        if (!Product::isStockEnabled()) {
            return true;
        }

        $updated = static::query()
            ->whereKey($this->id)
            ->where('stock_quantity', '>=', $quantity)
            ->decrement('stock_quantity', $quantity);

        if ($updated > 0) {
            $this->stock_quantity = (int) $this->stock_quantity - $quantity;
        }

        return $updated > 0;
    }

    public function incrementStock(int $quantity): void
    {
        if (!Product::isStockEnabled()) {
            return;
        }

        $this->increment('stock_quantity', $quantity);
    }
}

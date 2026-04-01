<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\Auditable;

class Product extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'regular_price',
        'sale_price',
        'buy_price',
        'sku',
        'stock_quantity',
        'is_active',
        'is_featured',
        'is_new',
        'is_bestseller',
        'sales_count',
        'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'regular_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'buy_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_bestseller' => 'boolean',
            'sales_count' => 'integer',
            'meta_data' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                // Generate SKU from name and timestamp
                $product->sku = strtoupper(Str::slug($product->name, '-') . '-' . now()->format('ymd') . rand(100, 999));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    /**
     * Get average rating
     */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->approvedReviews()->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    /**
     * Get review count
     */
    public function getReviewCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get the primary image path
     */
    public function getPrimaryImageAttribute(): ?string
    {
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $primary = $this->images->firstWhere('is_primary', true);
            return $primary ? $primary->image : $this->images->first()->image;
        }
        return null;
    }

    /**
     * Get the primary image URL
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $primary = $this->images->firstWhere('is_primary', true);
            return $primary ? $primary->url : $this->images->first()->url;
        }
        return null;
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Backward compatibility: alias for regular_price
     */
    public function getPriceAttribute(): float
    {
        return (float) $this->regular_price;
    }

    public function getCurrentPriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->regular_price);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->regular_price;
    }

    /**
     * Get normalized quantity pricing tiers from meta_data.
     *
     * Format:
     * [
     *   ['min_quantity' => 1, 'unit_price' => 100.00],
     *   ['min_quantity' => 3, 'unit_price' => 90.00],
     * ]
     */
    public function getQuantityPricingTiersAttribute(): array
    {
        $tiers = data_get($this->meta_data, 'quantity_pricing', []);

        if (!is_array($tiers)) {
            return [];
        }

        $normalized = [];

        foreach ($tiers as $tier) {
            if (!is_array($tier)) {
                continue;
            }

            $minQuantity = (int) ($tier['min_quantity'] ?? 0);
            $unitPrice = isset($tier['unit_price']) ? (float) $tier['unit_price'] : null;

            if ($minQuantity < 1 || $unitPrice === null || $unitPrice <= 0) {
                continue;
            }

            // Keep the latest value for duplicate quantities.
            $normalized[$minQuantity] = [
                'min_quantity' => $minQuantity,
                'unit_price' => round($unitPrice, 2),
            ];
        }

        ksort($normalized);

        return array_values($normalized);
    }

    public function getPriceForQuantity(int $quantity): float
    {
        $quantity = max(1, $quantity);
        $price = (float) ($this->sale_price ?? $this->regular_price);

        foreach ($this->quantity_pricing_tiers as $tier) {
            if ($quantity >= $tier['min_quantity']) {
                $price = (float) $tier['unit_price'];
            } else {
                break;
            }
        }

        return round($price, 2);
    }

    public function hasFreeDeliveryOffer(): bool
    {
        return (bool) data_get($this->meta_data, 'free_delivery', false);
    }

    /**
     * Calculate profit per unit
     */
    public function getProfitAttribute(): ?float
    {
        if (!$this->buy_price) {
            return null;
        }
        $sellPrice = $this->sale_price ?? $this->regular_price;
        return $sellPrice - $this->buy_price;
    }

    /**
     * Calculate profit margin percentage
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->buy_price) {
            return null;
        }
        $sellPrice = $this->sale_price ?? $this->regular_price;
        if ($sellPrice <= 0) {
            return 0;
        }
        return (($sellPrice - $this->buy_price) / $sellPrice) * 100;
    }

    public function getTotalStockAttribute(): int
    {
        // Only sum variant stock if variants are loaded and exist
        if ($this->relationLoaded('variants') && $this->variants->count() > 0) {
            return $this->variants->sum('stock_quantity');
        }
        // Fall back to base product stock
        return $this->stock_quantity ?? 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock_quantity >= $quantity;
    }

    public function decrementStock(int $quantity): void
    {
        $this->decrement('stock_quantity', $quantity);
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
    }
}

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
                // Generate SKU from 8 digit numeric value
                $product->sku = (string) random_int(10000000, 99999999);
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
        // Variant products should always use aggregate variant stock.
        if ($this->relationLoaded('variants')) {
            if ($this->variants->isNotEmpty()) {
                return (int) $this->variants->sum('stock_quantity');
            }

            return (int) ($this->stock_quantity ?? 0);
        }

        if ($this->variants()->exists()) {
            return (int) $this->variants()->sum('stock_quantity');
        }

        return (int) ($this->stock_quantity ?? 0);
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
        if (!self::isStockEnabled()) {
            return $query;
        }

        return $query->where(function ($q) {
            $q->whereHas('variants', function ($variantQuery) {
                $variantQuery
                    ->where('is_active', true)
                    ->where('stock_quantity', '>', 0);
            })->orWhere(function ($simpleQuery) {
                $simpleQuery
                    ->whereDoesntHave('variants')
                    ->where('stock_quantity', '>', 0);
            });
        });
    }

    public static function isStockEnabled(): bool
    {
        return (bool) Setting::getValue('general', 'stock_enabled', true);
    }

    public function hasActiveVariants(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->where('is_active', true)->isNotEmpty();
        }

        return $this->variants()->where('is_active', true)->exists();
    }

    public function isVariableProduct(): bool
    {
        $explicitFlag = data_get($this->meta_data, 'is_variable');
        if ($explicitFlag !== null) {
            return (bool) $explicitFlag;
        }

        if ($this->relationLoaded('variants')) {
            return $this->variants->isNotEmpty();
        }

        return $this->variants()->exists();
    }

    public function getDefaultVariantId(): ?int
    {
        $raw = data_get($this->meta_data, 'default_variant_id');
        $defaultVariantId = is_numeric($raw) ? (int) $raw : 0;

        return $defaultVariantId > 0 ? $defaultVariantId : null;
    }

    public function resolveGlobalPricingSnapshot(): array
    {
        $baseRegularPrice = round((float) $this->regular_price, 2);
        $baseCurrentPrice = round((float) ($this->sale_price ?? $this->regular_price), 2);
        $baseSalePrice = $this->sale_price !== null ? round((float) $this->sale_price, 2) : null;

        $activeVariants = $this->getActiveVariantsForPricing();

        if ($activeVariants->isEmpty()) {
            return [
                'regular_price' => $baseRegularPrice,
                'sale_price' => $baseSalePrice,
                'current_price' => $baseCurrentPrice,
                'is_on_sale' => $baseSalePrice !== null && $baseSalePrice < $baseRegularPrice,
                'default_variant_id' => null,
                'has_price_range' => false,
                'price_range_min' => $baseCurrentPrice,
                'price_range_max' => $baseCurrentPrice,
            ];
        }

        $resolveVariantCurrentPrice = static function ($variant): float {
            return round(max(0, (float) ($variant->discounted_price ?? $variant->regular_price ?? 0)), 2);
        };

        $defaultVariantId = $this->getDefaultVariantId();
        $defaultVariant = $defaultVariantId
            ? $activeVariants->firstWhere('id', $defaultVariantId)
            : null;

        $selectedVariant = $defaultVariant ?: $activeVariants
            ->sortBy(fn ($variant) => $resolveVariantCurrentPrice($variant))
            ->first();

        $selectedRegularPrice = round(max(0, (float) ($selectedVariant->regular_price ?? 0)), 2);
        $selectedCurrentPrice = $resolveVariantCurrentPrice($selectedVariant);

        if ($selectedRegularPrice <= 0) {
            $selectedRegularPrice = $selectedCurrentPrice;
        }

        $minCurrentPrice = round((float) ($activeVariants->min(fn ($variant) => $resolveVariantCurrentPrice($variant)) ?? $selectedCurrentPrice), 2);
        $maxCurrentPrice = round((float) ($activeVariants->max(fn ($variant) => $resolveVariantCurrentPrice($variant)) ?? $selectedCurrentPrice), 2);

        $salePrice = $selectedCurrentPrice < $selectedRegularPrice
            ? $selectedCurrentPrice
            : null;

        return [
            'regular_price' => $selectedRegularPrice,
            'sale_price' => $salePrice,
            'current_price' => $selectedCurrentPrice,
            'is_on_sale' => $salePrice !== null,
            'default_variant_id' => (int) ($selectedVariant->id ?? 0) ?: null,
            'has_price_range' => abs($maxCurrentPrice - $minCurrentPrice) > 0.0001,
            'price_range_min' => $minCurrentPrice,
            'price_range_max' => $maxCurrentPrice,
        ];
    }

    private function getActiveVariantsForPricing()
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->where('is_active', true)->values();
        }

        return $this->variants()
            ->where('is_active', true)
            ->get(['id', 'product_id', 'regular_price', 'discounted_price', 'price_adjustment', 'stock_quantity', 'is_active']);
    }

    public function getActiveVariantStockQuantity(): int
    {
        if ($this->relationLoaded('variants')) {
            return (int) $this->variants
                ->where('is_active', true)
                ->sum('stock_quantity');
        }

        return (int) $this->variants()
            ->where('is_active', true)
            ->sum('stock_quantity');
    }

    public function hasStock(int $quantity = 1): bool
    {
        if (!self::isStockEnabled()) {
            return true;
        }

        $quantity = max(1, $quantity);

        if ($this->hasActiveVariants()) {
            return $this->getActiveVariantStockQuantity() >= $quantity;
        }

        return (int) $this->stock_quantity >= $quantity;
    }

    public function decrementStock(int $quantity): void
    {
        if (!self::isStockEnabled()) {
            return;
        }

        if ($this->hasActiveVariants()) {
            return;
        }

        $this->decrement('stock_quantity', $quantity);
    }

    public function incrementStock(int $quantity): void
    {
        if (!self::isStockEnabled()) {
            return;
        }

        if ($this->hasActiveVariants()) {
            return;
        }

        $this->increment('stock_quantity', $quantity);
    }
}

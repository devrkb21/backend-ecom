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
        'stock_quantity',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
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
            return asset('storage/' . $this->image);
        }
        return null;
    }

    /**
     * Get the final price (base product price + adjustment)
     */
    public function getFinalPriceAttribute(): float
    {
        return $this->product->price + $this->price_adjustment;
    }

    /**
     * Get variant name from attributes (e.g., "Red / Large")
     */
    public function getNameAttribute(): string
    {
        return $this->attributeValues->pluck('value')->implode(' / ');
    }
}

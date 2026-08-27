<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_ids',
        'title',
        'slug',
        'template_type',
        'theme_color',
        'banner_image',
        'video_embed_code',
        'features',
        'testimonials',
        'custom_css',
        'is_active',
        'show_location',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'product_ids' => 'array',
            'features' => 'array',
            'testimonials' => 'array',
            'is_active' => 'boolean',
            'show_location' => 'boolean',
            'views_count' => 'integer',
        ];
    }

    /** Legacy single-product relation (kept for backward compat) */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * All linked products (via product_ids JSON array).
     * Returns a Collection of Product models.
     */
    public function getLinkedProductsAttribute()
    {
        $ids = $this->product_ids ?? ($this->product_id ? [$this->product_id] : []);
        if (empty($ids)) {
            return collect();
        }

        return Product::whereIn('id', $ids)->with(['variants.attributeValues.attribute', 'images'])->get();
    }
}

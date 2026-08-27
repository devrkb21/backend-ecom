<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    protected $fillable = [
        'attribute_id',
        'value',
        'color_code',
        'image',
        'sort_order',
    ];

    protected $appends = [
        'image_url',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        $normalized = ltrim((string) $this->image, '/');

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            return $this->image;
        }

        if (str_starts_with($normalized, 'media/') || str_starts_with($normalized, 'storage/')) {
            return asset($normalized);
        }

        return asset('storage/'.$normalized);
    }
}

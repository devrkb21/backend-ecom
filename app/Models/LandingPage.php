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
            'features' => 'array',
            'testimonials' => 'array',
            'is_active' => 'boolean',
            'show_location' => 'boolean',
            'views_count' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

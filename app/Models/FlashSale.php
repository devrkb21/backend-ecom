<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FlashSale extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'banner_image',
        'starts_at',
        'ends_at',
        'is_active',
        'is_featured',
        'priority',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'priority' => 'integer',
    ];

    protected $appends = ['status', 'time_remaining'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($flashSale) {
            if (empty($flashSale->slug)) {
                $flashSale->slug = Str::slug($flashSale->name);
            }
        });
    }

    public function flashSaleProducts(): HasMany
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flash_sale_products')
            ->withPivot(['flash_price', 'original_price', 'discount_percentage', 'quantity_limit', 'sold_count', 'per_user_limit', 'is_active'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Accessors
    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->starts_at > $now) {
            return 'upcoming';
        }

        if ($this->ends_at <= $now) {
            return 'ended';
        }

        return 'active';
    }

    public function getTimeRemainingAttribute(): ?array
    {
        if ($this->status !== 'active') {
            return null;
        }

        $now = now();
        $diff = $this->ends_at->diff($now);

        return [
            'days' => $diff->d,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'seconds' => $diff->s,
            'total_seconds' => $now->diffInSeconds($this->ends_at),
        ];
    }

    // Methods
    public function isLive(): bool
    {
        return $this->status === 'active';
    }

    public function hasStarted(): bool
    {
        return $this->starts_at <= now();
    }

    public function hasEnded(): bool
    {
        return $this->ends_at <= now();
    }
}

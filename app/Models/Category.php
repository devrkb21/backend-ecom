<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'image',
        'icon',
        'is_active',
        'show_in_menu',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get full path (e.g., "Electronics > Phones > Smartphones")
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        // Guards against an indirect parent cycle (A -> B -> A) hanging the
        // request — validation only rejects a category being its own direct
        // parent, not a longer loop, so this traversal must bound itself.
        $visited = [$this->id => true];

        while ($parent && !isset($visited[$parent->id])) {
            $visited[$parent->id] = true;
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Get depth level in category tree
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;
        $visited = [$this->id => true];

        while ($parent && !isset($visited[$parent->id])) {
            $visited[$parent->id] = true;
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMenu($query)
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}

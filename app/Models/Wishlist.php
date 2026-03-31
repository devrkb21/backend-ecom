<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'product_variant_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Scope to get wishlist for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if product is in user's wishlist
     */
    public static function isInWishlist(int $userId, int $productId, ?int $variantId = null): bool
    {
        return self::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->exists();
    }

    /**
     * Toggle product in wishlist (add if not exists, remove if exists)
     */
    public static function toggle(int $userId, int $productId, ?int $variantId = null): array
    {
        $existing = self::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existing) {
            $existing->delete();
            return ['added' => false, 'message' => 'Removed from wishlist'];
        }

        self::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
        ]);

        return ['added' => true, 'message' => 'Added to wishlist'];
    }
}

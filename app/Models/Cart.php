<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'discount_amount',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    public function getTotalAttribute(): float
    {
        $subtotal = $this->subtotal;
        $discount = $this->discount_amount ?? 0;
        return max(0, $subtotal - $discount);
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Recalculate coupon discount when cart items change
     */
    public function recalculateCoupon(): void
    {
        if ($this->coupon_id && $this->coupon) {
            $errors = $this->coupon->validateForCart($this);
            
            if (!empty($errors)) {
                // Coupon no longer valid, remove it
                $this->update([
                    'coupon_id' => null,
                    'discount_amount' => 0,
                ]);
            } else {
                // Recalculate discount
                $discount = $this->coupon->calculateDiscount($this);
                $this->update(['discount_amount' => $discount]);
            }
        }
    }

    public function clear(): void
    {
        $this->items()->delete();
        $this->update([
            'coupon_id' => null,
            'discount_amount' => 0,
        ]);
    }
}

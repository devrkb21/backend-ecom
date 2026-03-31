<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'free_shipping',
        'applicable_categories',
        'applicable_products',
        'excluded_products',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'is_active' => 'boolean',
        'free_shipping' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_categories' => 'array',
        'applicable_products' => 'array',
        'excluded_products' => 'array',
    ];

    protected $appends = ['status', 'formatted_value'];

    /**
     * Get coupon usages
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Get orders that used this coupon
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if coupon is valid
     */
    public function isValid(): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check start date
        if ($this->starts_at && Carbon::now()->lt($this->starts_at)) {
            return false;
        }

        // Check expiry date
        if ($this->expires_at && Carbon::now()->gt($this->expires_at)) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if coupon is valid for a user
     */
    public function isValidForUser(?User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if (!$user) {
            return true; // Guest checkout might be allowed
        }

        // Check per-user usage limit
        if ($this->usage_limit_per_user) {
            $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $this->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate coupon for cart
     */
    public function validateForCart(Cart $cart): array
    {
        $errors = [];

        if (!$this->is_active) {
            $errors[] = 'This coupon is no longer active.';
            return $errors;
        }

        if ($this->starts_at && Carbon::now()->lt($this->starts_at)) {
            $errors[] = 'This coupon is not yet valid.';
        }

        if ($this->expires_at && Carbon::now()->gt($this->expires_at)) {
            $errors[] = 'This coupon has expired.';
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            $errors[] = 'This coupon has reached its usage limit.';
        }

        // Check minimum order amount
        $cartTotal = $cart->items->sum(fn($item) => $item->price * $item->quantity);
        if ($this->minimum_order_amount && $cartTotal < $this->minimum_order_amount) {
            $errors[] = "Minimum order amount of ৳{$this->minimum_order_amount} required.";
        }

        // Check applicable products/categories
        if ($this->applicable_products || $this->applicable_categories) {
            $hasApplicableItem = false;
            foreach ($cart->items as $item) {
                if ($this->isProductApplicable($item->product)) {
                    $hasApplicableItem = true;
                    break;
                }
            }
            if (!$hasApplicableItem) {
                $errors[] = 'This coupon is not valid for the items in your cart.';
            }
        }

        // Check per-user limit
        if ($cart->user_id && $this->usage_limit_per_user) {
            $userUsageCount = $this->usages()->where('user_id', $cart->user_id)->count();
            if ($userUsageCount >= $this->usage_limit_per_user) {
                $errors[] = 'You have already used this coupon the maximum number of times.';
            }
        }

        return $errors;
    }

    /**
     * Check if product is applicable for this coupon
     */
    public function isProductApplicable(Product $product): bool
    {
        // Check excluded products first
        if ($this->excluded_products && in_array($product->id, $this->excluded_products)) {
            return false;
        }

        // If no restrictions, all products are applicable
        if (empty($this->applicable_products) && empty($this->applicable_categories)) {
            return true;
        }

        // Check specific products
        if ($this->applicable_products && in_array($product->id, $this->applicable_products)) {
            return true;
        }

        // Check categories
        if ($this->applicable_categories && in_array($product->category_id, $this->applicable_categories)) {
            return true;
        }

        return false;
    }

    /**
     * Calculate discount for cart
     */
    public function calculateDiscount(Cart $cart): float
    {
        $applicableTotal = 0;

        foreach ($cart->items as $item) {
            if ($this->isProductApplicable($item->product)) {
                $applicableTotal += $item->price * $item->quantity;
            }
        }

        if ($applicableTotal <= 0) {
            return 0;
        }

        $discount = 0;

        if ($this->type === 'fixed') {
            $discount = min($this->value, $applicableTotal);
        } else { // percentage
            $discount = $applicableTotal * ($this->value / 100);
            
            // Apply maximum discount cap if set
            if ($this->maximum_discount && $discount > $this->maximum_discount) {
                $discount = $this->maximum_discount;
            }
        }

        return round($discount, 2);
    }

    /**
     * Calculate discount for order total
     */
    public function calculateDiscountForAmount(float $amount): float
    {
        if ($amount <= 0) {
            return 0;
        }

        $discount = 0;

        if ($this->type === 'fixed') {
            $discount = min($this->value, $amount);
        } else { // percentage
            $discount = $amount * ($this->value / 100);
            
            if ($this->maximum_discount && $discount > $this->maximum_discount) {
                $discount = $this->maximum_discount;
            }
        }

        return round($discount, 2);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Get status attribute
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->starts_at && Carbon::now()->lt($this->starts_at)) {
            return 'scheduled';
        }

        if ($this->expires_at && Carbon::now()->gt($this->expires_at)) {
            return 'expired';
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return 'exhausted';
        }

        return 'active';
    }

    /**
     * Get formatted value
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'percentage') {
            return $this->value . '%';
        }
        return '৳' . number_format($this->value, 2);
    }

    /**
     * Scope active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    /**
     * Find coupon by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))->first();
    }
}

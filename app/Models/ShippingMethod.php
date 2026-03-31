<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
        'base_cost',
        'cost_per_item',
        'cost_per_kg',
        'free_shipping_threshold',
        'min_order_amount',
        'max_order_amount',
        'max_weight',
        'min_delivery_days',
        'max_delivery_days',
        'allowed_countries',
        'excluded_countries',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'base_cost' => 'decimal:2',
            'cost_per_item' => 'decimal:2',
            'cost_per_kg' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_order_amount' => 'decimal:2',
            'max_weight' => 'decimal:2',
            'allowed_countries' => 'array',
            'excluded_countries' => 'array',
            'settings' => 'array',
        ];
    }

    /**
     * Get all active shipping methods
     */
    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Find by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this method is available for a given order
     */
    public function isAvailableFor(float $orderAmount, ?float $weight = null, ?string $countryCode = null): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check min order amount
        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return false;
        }

        // Check max order amount
        if ($this->max_order_amount && $orderAmount > $this->max_order_amount) {
            return false;
        }

        // Check weight limit
        if ($weight && $this->max_weight && $weight > $this->max_weight) {
            return false;
        }

        // Check country restrictions
        if ($countryCode) {
            // Check if excluded
            if ($this->excluded_countries && in_array($countryCode, $this->excluded_countries)) {
                return false;
            }

            // Check if allowed (if specific countries are set)
            if ($this->allowed_countries && !empty($this->allowed_countries) && !in_array($countryCode, $this->allowed_countries)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate shipping cost for an order
     */
    public function calculateCost(float $orderAmount, int $itemCount = 1, float $weight = 0): float
    {
        // Check for free shipping
        if ($this->free_shipping_threshold && $orderAmount >= $this->free_shipping_threshold) {
            return 0;
        }

        $cost = (float) $this->base_cost;
        
        // Add per-item cost
        if ($this->cost_per_item > 0) {
            $cost += (float) $this->cost_per_item * $itemCount;
        }

        // Add per-kg cost
        if ($this->cost_per_kg > 0 && $weight > 0) {
            $cost += (float) $this->cost_per_kg * $weight;
        }

        return round($cost, 2);
    }

    /**
     * Get delivery estimate text
     */
    public function getDeliveryEstimate(): ?string
    {
        if (!$this->min_delivery_days && !$this->max_delivery_days) {
            return null;
        }

        if ($this->min_delivery_days && $this->max_delivery_days) {
            if ($this->min_delivery_days === $this->max_delivery_days) {
                return $this->min_delivery_days . ' day' . ($this->min_delivery_days > 1 ? 's' : '');
            }
            return $this->min_delivery_days . '-' . $this->max_delivery_days . ' days';
        }

        if ($this->min_delivery_days) {
            return $this->min_delivery_days . '+ days';
        }

        return 'Up to ' . $this->max_delivery_days . ' days';
    }

    /**
     * Get a setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
}

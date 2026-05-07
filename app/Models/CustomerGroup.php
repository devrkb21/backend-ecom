<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'min_order_count',
        'min_total_spent',
        'discount_percentage',
        'custom_message',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'min_order_count' => 'integer',
        'min_total_spent' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    /**
     * Get the highest qualifying group for a given order count and spent amount.
     */
    public static function getQualifyingGroup(int $orderCount, float $totalSpent): ?self
    {
        return self::where('is_active', true)
            ->where(function ($query) use ($orderCount, $totalSpent) {
                // Number must meet either order count threshold OR total spent threshold (if they are non-zero)
                // If a threshold is 0, it means that condition is disabled. Wait, if both are 0, it's a default group?
                // Let's say: rule is satisfied if (min_order_count > 0 AND orderCount >= min_order_count) 
                // OR (min_total_spent > 0 AND totalSpent >= min_total_spent)
                $query->where(function ($q) use ($orderCount) {
                    $q->where('min_order_count', '>', 0)
                      ->where('min_order_count', '<=', $orderCount);
                })->orWhere(function ($q) use ($totalSpent) {
                    $q->where('min_total_spent', '>', 0)
                      ->where('min_total_spent', '<=', $totalSpent);
                });
            })
            // Sort by sort_order ascending, then highest discount first, then highest threshold first
            ->orderBy('sort_order', 'asc')
            ->orderBy('discount_percentage', 'desc')
            ->first();
    }
}

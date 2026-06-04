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
        'manual_numbers',
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
    public static function getQualifyingGroup(int $orderCount, float $totalSpent, ?string $phone = null): ?self
    {
        $groups = self::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('discount_percentage', 'desc')
            ->get();
            
        $cleanedPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : null;

        foreach ($groups as $group) {
            // Check manual assignment first
            if ($cleanedPhone && $group->manual_numbers) {
                $manualNumbers = array_map('trim', explode(',', $group->manual_numbers));
                $manualNumbers = array_map(fn($num) => preg_replace('/[^0-9]/', '', $num), $manualNumbers);
                
                if (in_array($cleanedPhone, $manualNumbers)) {
                    return $group;
                }
            }

            // Check auto-assign rules
            $meetsOrderCount = $group->min_order_count > 0 && $orderCount >= $group->min_order_count;
            $meetsSpent = $group->min_total_spent > 0 && $totalSpent >= $group->min_total_spent;

            if ($meetsOrderCount || $meetsSpent) {
                return $group;
            }
        }

        return null;
    }
}

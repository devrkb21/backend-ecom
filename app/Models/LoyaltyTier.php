<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'min_points',
        'points_multiplier',
        'birthday_bonus',
        'free_shipping',
        'exclusive_discount',
        'benefits',
        'badge_image',
    ];

    protected $casts = [
        'min_points' => 'integer',
        'points_multiplier' => 'decimal:2',
        'birthday_bonus' => 'integer',
        'free_shipping' => 'boolean',
        'exclusive_discount' => 'integer',
    ];

    // Get tier by points
    public static function getTierForPoints(int $lifetimePoints): ?self
    {
        return self::where('min_points', '<=', $lifetimePoints)
            ->orderBy('min_points', 'desc')
            ->first();
    }

    // Get next tier
    public function getNextTier(): ?self
    {
        return self::where('min_points', '>', $this->min_points)
            ->orderBy('min_points', 'asc')
            ->first();
    }

    // Get points needed for next tier
    public function getPointsToNextTier(int $currentLifetimePoints): ?int
    {
        $nextTier = $this->getNextTier();

        if (! $nextTier) {
            return null; // Already at highest tier
        }

        return max(0, $nextTier->min_points - $currentLifetimePoints);
    }

    // Get benefits as array
    public function getBenefitsArray(): array
    {
        $benefits = [];

        if ($this->points_multiplier > 1) {
            $benefits[] = "Earn {$this->points_multiplier}x points on every purchase";
        }

        if ($this->birthday_bonus > 0) {
            $benefits[] = "{$this->birthday_bonus} bonus points on your birthday";
        }

        if ($this->free_shipping) {
            $benefits[] = 'Free shipping on all orders';
        }

        if ($this->exclusive_discount > 0) {
            $benefits[] = "{$this->exclusive_discount}% exclusive member discount";
        }

        return $benefits;
    }
}

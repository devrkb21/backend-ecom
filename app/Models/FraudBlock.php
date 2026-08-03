<?php

namespace App\Models;

use App\Support\FraudNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudBlock extends Model
{
    protected $fillable = [
        'type',
        'value',
        'normalized_value',
        'reason',
        'custom_message',
        'blocked_by',
        'order_id',
        'is_active',
        'source',
        'needs_review',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'needs_review' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Every write recomputes normalized_value from type+value, so a
        // manually-entered block ("+8801712345678") and an auto-generated
        // one ("01712345678") for the same real phone number still collide
        // on the same normalized form instead of creating two entries that
        // silently don't match each other at checkout.
        static::saving(function (self $block) {
            $block->normalized_value = FraudNormalizer::forType($block->type, $block->value);
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==================== ACCESSORS ====================

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'phone' => 'Phone Number',
            'email' => 'Email Address',
            'ip' => 'IP Address',
            'device' => 'Device / User Agent',
            default => ucfirst($this->type),
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'phone' => 'bi-telephone-x',
            'email' => 'bi-envelope-x',
            'ip' => 'bi-globe2',
            'device' => 'bi-laptop',
            default => 'bi-shield-x',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'phone' => 'danger',
            'email' => 'warning',
            'ip' => 'info',
            'device' => 'secondary',
            default => 'dark',
        };
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Check if a given value is blocked for a specific type.
     */
    public static function isBlocked(string $type, ?string $value): bool
    {
        return self::getBlock($type, $value) !== null;
    }

    /**
     * Get the block record for a given type and value (if blocked). Matches
     * on the normalized form so formatting variance (phone spacing/country
     * code, email case, etc.) can't be used to slip past an existing block.
     */
    public static function getBlock(string $type, ?string $value): ?self
    {
        $normalized = FraudNormalizer::forType($type, $value);

        if ($normalized === null) {
            return null;
        }

        return self::active()
            ->where('type', $type)
            ->where('normalized_value', $normalized)
            ->first();
    }

    /**
     * Check if any of the given order attributes are blocked.
     * Returns array with 'types' and 'message' keys.
     */
    public static function checkOrder(
        ?string $phone = null,
        ?string $email = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        $blocked = [];
        $customMessage = null;

        $checks = [
            'phone' => $phone,
            'email' => $email,
            'ip' => $ip,
            'device' => $userAgent,
        ];

        foreach ($checks as $type => $value) {
            $block = self::getBlock($type, $value);
            if ($block) {
                $blocked[] = $type;
                // Use the first custom message found
                if ($customMessage === null && !empty($block->custom_message)) {
                    $customMessage = $block->custom_message;
                }
            }
        }

        return [
            'types' => $blocked,
            'message' => $customMessage,
        ];
    }

    /**
     * Get summary counts for dashboard/sidebar.
     */
    public static function getSummary(): array
    {
        $counts = self::active()
            ->selectRaw("type, COUNT(*) as count")
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'phone' => $counts['phone'] ?? 0,
            'email' => $counts['email'] ?? 0,
            'ip' => $counts['ip'] ?? 0,
            'device' => $counts['device'] ?? 0,
            'total' => array_sum($counts),
        ];
    }
}

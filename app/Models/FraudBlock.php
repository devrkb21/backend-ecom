<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudBlock extends Model
{
    protected $fillable = [
        'type',
        'value',
        'reason',
        'custom_message',
        'blocked_by',
        'order_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
        if ($value === null || trim($value) === '') {
            return false;
        }

        return self::active()
            ->where('type', $type)
            ->where('value', trim($value))
            ->exists();
    }

    /**
     * Get the block record for a given type and value (if blocked).
     */
    public static function getBlock(string $type, ?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::active()
            ->where('type', $type)
            ->where('value', trim($value))
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

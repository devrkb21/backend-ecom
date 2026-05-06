<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'order_number',
        'guest_access_token_hash',
        'status',
        'order_source',
        'subtotal',
        'tax',
        'shipping',
        'shipping_method',
        'total',
        'payment_method',
        'payment_status',
        'transaction_id',
        'bkash_payment_id',
        'shipping_name',
        'shipping_email',
        'shipping_phone',
        'shipping_address',
        'shipping_division_id',
        'shipping_district_id',
        'shipping_upazila_id',
        'shipping_union_id',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
        'notes',
        'checkout_fields_payload',
        // Tracking fields
        'tracking_number',
        'carrier',
        'carrier_tracking_url',
        'shipped_at',
        'estimated_delivery_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'shipping_division_id' => 'integer',
            'shipping_district_id' => 'integer',
            'shipping_upazila_id' => 'integer',
            'shipping_union_id' => 'integer',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'checkout_fields_payload' => 'array',
            'shipped_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'guest_access_token_hash',
    ];

    public static function generateOrderNumber(): string
    {
        $prefix = static::resolveOrderNumberPrefix();
        $mode = static::resolveOrderNumberGenerationMode();

        return match ($mode) {
            'date_sequence' => static::generateSequentialOrderNumber($prefix, true),
            'global_sequence' => static::generateSequentialOrderNumber($prefix, false),
            'custom_format' => static::generateCustomFormatOrderNumber($prefix),
            default => static::generateTimestampRandomOrderNumber($prefix),
        };
    }

    protected static function resolveOrderNumberPrefix(): string
    {
        $rawPrefix = (string) Setting::getValue('general', 'order_number_prefix', 'ORD');
        $normalizedPrefix = strtoupper(trim($rawPrefix));
        $sanitizedPrefix = preg_replace('/[^A-Z0-9_-]/', '', $normalizedPrefix) ?? '';

        if ($sanitizedPrefix === '') {
            return 'ORD';
        }

        return substr($sanitizedPrefix, 0, 20);
    }

    protected static function resolveOrderNumberGenerationMode(): string
    {
        $rawMode = (string) Setting::getValue('general', 'order_number_generation_mode', 'timestamp_random');
        $allowedModes = ['timestamp_random', 'date_sequence', 'global_sequence', 'custom_format'];

        return in_array($rawMode, $allowedModes, true) ? $rawMode : 'timestamp_random';
    }

    protected static function generateTimestampRandomOrderNumber(string $prefix): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = sprintf(
                '%s-%s-%s',
                $prefix,
                now()->format('YmdHis'),
                strtoupper(Str::random(4))
            );

            if (!static::withTrashed()->where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return sprintf('%s-%s-%s', $prefix, now()->format('YmdHis'), strtoupper(Str::random(6)));
    }

    protected static function generateSequentialOrderNumber(string $prefix, bool $isDaily): string
    {
        $basePrefix = $isDaily ? sprintf('%s-%s', $prefix, now()->format('Ymd')) : $prefix;
        $startingSequence = static::resolveStartingSequenceForPrefix($basePrefix);

        for ($attempt = 0; $attempt < 200; $attempt++) {
            $candidate = sprintf(
                '%s-%s',
                $basePrefix,
                str_pad((string) ($startingSequence + $attempt), 8, '0', STR_PAD_LEFT)
            );

            if (!static::withTrashed()->where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback to random format if sequence retries are exhausted.
        return static::generateTimestampRandomOrderNumber($prefix);
    }

    protected static function resolveStartingSequenceForPrefix(string $basePrefix): int
    {
        $orderNumbers = static::withTrashed()
            ->where('order_number', 'like', $basePrefix . '-%')
            ->orderByDesc('id')
            ->limit(300)
            ->pluck('order_number');

        $maxSequence = 0;

        foreach ($orderNumbers as $orderNumber) {
            if (!is_string($orderNumber)) {
                continue;
            }

            if (!str_starts_with($orderNumber, $basePrefix . '-')) {
                continue;
            }

            $suffix = substr($orderNumber, strlen($basePrefix) + 1);

            if ($suffix !== '' && ctype_digit($suffix)) {
                $maxSequence = max($maxSequence, (int) $suffix);
            }
        }

        return $maxSequence + 1;
    }

    protected static function generateCustomFormatOrderNumber(string $prefix): string
    {
        $format = (string) Setting::getValue('general', 'order_number_custom_format', '{PREFIX}-{YYYY}{MM}{DD}-{SEQ:4}');
        if (trim($format) === '') {
            $format = '{PREFIX}-{YYYY}{MM}{DD}-{SEQ:4}';
        }

        $now = now();
        $baseFormat = str_replace(
            ['{PREFIX}', '{YYYY}', '{YY}', '{MM}', '{DD}'],
            [$prefix, $now->format('Y'), $now->format('y'), $now->format('m'), $now->format('d')],
            $format
        );

        for ($attempt = 0; $attempt < 200; $attempt++) {
            $candidate = $baseFormat;

            // Handle {RAND:N}
            if (preg_match_all('/\{RAND:(\d+)\}/', $candidate, $matches)) {
                foreach ($matches[0] as $index => $match) {
                    $length = (int) $matches[1][$index];
                    $length = max(1, min($length, 32)); // bound length
                    $candidate = str_replace($match, strtoupper(Str::random($length)), $candidate);
                }
            }

            // Handle {SEQ:N}
            if (preg_match('/\{SEQ:(\d+)\}/', $candidate, $matches)) {
                $length = (int) $matches[1];
                $length = max(1, min($length, 20)); // bound length

                // Find max sequence matching the pattern up to {SEQ}
                $patternBeforeSeq = substr($baseFormat, 0, strpos($baseFormat, $matches[0]));
                $startingSequence = static::resolveStartingSequenceForCustomFormat($patternBeforeSeq);

                $candidate = str_replace($matches[0], str_pad((string) ($startingSequence + $attempt), $length, '0', STR_PAD_LEFT), $candidate);
            }

            if (!static::withTrashed()->where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback
        return static::generateTimestampRandomOrderNumber($prefix);
    }

    protected static function resolveStartingSequenceForCustomFormat(string $patternBeforeSeq): int
    {
        if ($patternBeforeSeq === '') {
            return 1;
        }

        $orderNumbers = static::withTrashed()
            ->where('order_number', 'like', str_replace(['_', '%'], ['\_', '\%'], $patternBeforeSeq) . '%')
            ->orderByDesc('id')
            ->limit(300)
            ->pluck('order_number');

        $maxSequence = 0;

        foreach ($orderNumbers as $orderNumber) {
            if (!is_string($orderNumber)) {
                continue;
            }

            if (!str_starts_with($orderNumber, $patternBeforeSeq)) {
                continue;
            }

            $suffix = substr($orderNumber, strlen($patternBeforeSeq));

            // Extract the first sequence of digits in suffix
            if (preg_match('/^\d+/', $suffix, $matches)) {
                $maxSequence = max($maxSequence, (int) $matches[0]);
            }
        }

        return $maxSequence + 1;
    }

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function itemsWithVariant(): HasMany
    {
        return $this->hasMany(OrderItem::class)->with(['variant.attributeValues.attribute']);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function trackingHistory(): HasMany
    {
        return $this->hasMany(OrderTrackingHistory::class)->orderBy('occurred_at', 'desc');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(OrderActivityLog::class)->orderByDesc('created_at');
    }

    public function statusConfig(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status', 'key');
    }

    public function shippingDivision(): BelongsTo
    {
        return $this->belongsTo(BdDivision::class, 'shipping_division_id');
    }

    public function shippingDistrict(): BelongsTo
    {
        return $this->belongsTo(BdDistrict::class, 'shipping_district_id');
    }

    public function shippingUpazila(): BelongsTo
    {
        return $this->belongsTo(BdUpazila::class, 'shipping_upazila_id');
    }

    public function shippingUnion(): BelongsTo
    {
        return $this->belongsTo(BdUnion::class, 'shipping_union_id');
    }

    // ==================== STATUS HELPERS ====================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    // ==================== TRACKING METHODS ====================

    /**
     * Add a tracking history entry
     */
    public function addTrackingEvent(
        string $status,
        ?string $description = null,
        ?string $location = null,
        ?string $carrierStatus = null,
        ?\DateTime $occurredAt = null
    ): OrderTrackingHistory {
        return $this->trackingHistory()->create([
            'status' => $status,
            'description' => $description,
            'location' => $location,
            'carrier_status' => $carrierStatus,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /**
     * Set tracking information and add history event
     */
    public function setTrackingInfo(
        string $trackingNumber,
        string $carrier,
        ?string $trackingUrl = null,
        ?string $estimatedDelivery = null
    ): self {
        $this->update([
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'carrier_tracking_url' => $trackingUrl ?? $this->generateTrackingUrl($trackingNumber, $carrier),
            'shipped_at' => now(),
            'estimated_delivery_at' => $estimatedDelivery ? \Carbon\Carbon::parse($estimatedDelivery) : null,
        ]);

        $this->addTrackingEvent(
            'shipped',
            "Package shipped via {$carrier}. Tracking number: {$trackingNumber}",
            null,
            null,
            now()
        );

        return $this;
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(?string $description = null): self
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->addTrackingEvent(
            'delivered',
            $description ?? 'Package has been delivered successfully.',
            $this->shipping_city,
            null,
            now()
        );

        return $this;
    }

    /**
     * Generate tracking URL based on carrier
     */
    public function generateTrackingUrl(string $trackingNumber, string $carrier): ?string
    {
        $carriers = [
            'pathao' => 'https://merchant.pathao.com/tracking?consignment_id=' . $trackingNumber,
            'steadfast' => 'https://steadfast.com.bd/tl/' . $trackingNumber,
            'redx' => 'https://redx.com.bd/track/' . $trackingNumber,
            'paperfly' => 'https://paperfly.com.bd/tracking/' . $trackingNumber,
            'sundarban' => 'https://sundarbancourier.com/track/' . $trackingNumber,
            'dhl' => 'https://www.dhl.com/en/express/tracking.html?AWB=' . $trackingNumber,
            'fedex' => 'https://www.fedex.com/fedextrack/?trknbr=' . $trackingNumber,
            'ups' => 'https://www.ups.com/track?tracknum=' . $trackingNumber,
        ];

        return $carriers[strtolower($carrier)] ?? null;
    }

    /**
     * Get current tracking status
     */
    public function getCurrentTrackingStatus(): ?OrderTrackingHistory
    {
        return $this->trackingHistory()->first();
    }

    /**
     * Check if order has tracking info
     */
    public function hasTrackingInfo(): bool
    {
        return !empty($this->tracking_number);
    }

    /**
     * Get tracking progress percentage
     */
    public function getTrackingProgressAttribute(): int
    {
        $statuses = [
            'pending' => 10,
            'processing' => 25,
            'packed' => 40,
            'shipped' => 60,
            'in_transit' => 75,
            'out_for_delivery' => 90,
            'delivered' => 100,
            'cancelled' => 0,
        ];

        return $statuses[$this->status] ?? 0;
    }

    // ==================== SCOPES ====================

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWithTracking($query)
    {
        return $query->whereNotNull('tracking_number');
    }

    public function scopePendingDelivery($query)
    {
        return $query->whereIn('status', ['shipped', 'in_transit', 'out_for_delivery']);
    }

    public function hasValidGuestAccessToken(?string $plainToken): bool
    {
        $plainToken = trim((string) $plainToken);
        if ($plainToken === '' || empty($this->guest_access_token_hash)) {
            return false;
        }

        return hash_equals($this->guest_access_token_hash, hash('sha256', $plainToken));
    }
}

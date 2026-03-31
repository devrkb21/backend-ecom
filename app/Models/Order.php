<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\Auditable;

class Order extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'order_number',
        'status',
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
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
        'notes',
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
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'shipped_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}-{$timestamp}-{$random}";
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
            'steadfast' => 'https://steadfast.com.bd/track/' . $trackingNumber,
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
}

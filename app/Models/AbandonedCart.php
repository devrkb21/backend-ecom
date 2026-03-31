<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cart_id',
        'session_id',
        'status',
        'checkout_step',
        'email',
        'phone',
        'name',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
        'cart_items',
        'subtotal',
        'total',
        'coupon_code',
        'discount_amount',
        'payment_method',
        'shipping_method',
        'recovered_order_id',
        'recovered_at',
        'admin_notes',
        'followed_up_by',
        'followed_up_at',
        'last_activity_at',
        'user_agent',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'cart_items' => 'array',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'recovered_at' => 'datetime',
            'followed_up_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function recoveredOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'recovered_order_id');
    }

    public function followedUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followed_up_by');
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFollowUp($query)
    {
        return $query->where('status', 'follow_up');
    }

    public function scopeRecovered($query)
    {
        return $query->where('status', 'recovered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeWithContactInfo($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('email')
              ->orWhereNotNull('phone');
        });
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeOlderThan($query, int $hours = 1)
    {
        return $query->where('last_activity_at', '<=', now()->subHours($hours));
    }

    // ==================== ACCESSORS ====================

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'follow_up' => 'Follow Up',
            'recovered' => 'Recovered',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'follow_up' => 'info',
            'recovered' => 'success',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getCheckoutStepLabelAttribute(): string
    {
        return match ($this->checkout_step) {
            'cart' => 'Cart Page',
            'shipping' => 'Shipping Info',
            'payment' => 'Payment',
            default => 'Unknown',
        };
    }

    public function getItemCountAttribute(): int
    {
        if (!$this->cart_items) {
            return 0;
        }
        return collect($this->cart_items)->sum('quantity');
    }

    public function getFormattedAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->shipping_address,
            $this->shipping_city,
            $this->shipping_state,
            $this->shipping_zip,
            $this->shipping_country,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    public function getContactInfoAttribute(): ?string
    {
        $parts = array_filter([
            $this->name,
            $this->phone ? "📞 {$this->phone}" : null,
            $this->email ? "✉️ {$this->email}" : null,
        ]);

        return $parts ? implode(' | ', $parts) : null;
    }

    public function getTimeSinceAbandonedAttribute(): string
    {
        return $this->last_activity_at 
            ? $this->last_activity_at->diffForHumans() 
            : $this->created_at->diffForHumans();
    }

    // ==================== METHODS ====================

    /**
     * Mark as follow up
     */
    public function markAsFollowUp(?int $adminId = null, ?string $notes = null): self
    {
        $this->update([
            'status' => 'follow_up',
            'followed_up_by' => $adminId,
            'followed_up_at' => now(),
            'admin_notes' => $notes ?? $this->admin_notes,
        ]);

        return $this;
    }

    /**
     * Mark as recovered
     */
    public function markAsRecovered(?int $orderId = null): self
    {
        $this->update([
            'status' => 'recovered',
            'recovered_order_id' => $orderId,
            'recovered_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(?string $notes = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'admin_notes' => $notes ?? $this->admin_notes,
        ]);

        return $this;
    }

    /**
     * Create or update from checkout data
     */
    public static function trackCheckout(array $data, ?int $userId = null, ?string $sessionId = null): self
    {
        // Find existing abandoned cart or create new
        $abandonedCart = self::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } elseif ($sessionId) {
                $query->where('session_id', $sessionId);
            }
        })
        ->whereIn('status', ['pending', 'follow_up'])
        ->where('created_at', '>=', now()->subDays(7)) // Only match recent ones
        ->first();

        if (!$abandonedCart) {
            $abandonedCart = new self();
        }

        $abandonedCart->fill([
            'user_id' => $userId ?? $abandonedCart->user_id,
            'session_id' => $sessionId ?? $abandonedCart->session_id,
            'checkout_step' => $data['checkout_step'] ?? $abandonedCart->checkout_step,
            'email' => $data['email'] ?? $abandonedCart->email,
            'phone' => $data['phone'] ?? $abandonedCart->phone,
            'name' => $data['name'] ?? $abandonedCart->name,
            'shipping_address' => $data['shipping_address'] ?? $abandonedCart->shipping_address,
            'shipping_city' => $data['shipping_city'] ?? $abandonedCart->shipping_city,
            'shipping_state' => $data['shipping_state'] ?? $abandonedCart->shipping_state,
            'shipping_zip' => $data['shipping_zip'] ?? $abandonedCart->shipping_zip,
            'shipping_country' => $data['shipping_country'] ?? $abandonedCart->shipping_country,
            'cart_items' => $data['cart_items'] ?? $abandonedCart->cart_items,
            'subtotal' => $data['subtotal'] ?? $abandonedCart->subtotal,
            'total' => $data['total'] ?? $abandonedCart->total,
            'coupon_code' => $data['coupon_code'] ?? $abandonedCart->coupon_code,
            'discount_amount' => $data['discount_amount'] ?? $abandonedCart->discount_amount,
            'payment_method' => $data['payment_method'] ?? $abandonedCart->payment_method,
            'shipping_method' => $data['shipping_method'] ?? $abandonedCart->shipping_method,
            'cart_id' => $data['cart_id'] ?? $abandonedCart->cart_id,
            'user_agent' => $data['user_agent'] ?? $abandonedCart->user_agent,
            'ip_address' => $data['ip_address'] ?? $abandonedCart->ip_address,
            'last_activity_at' => now(),
        ]);

        $abandonedCart->save();

        return $abandonedCart;
    }

    /**
     * Get potential revenue from abandoned carts
     */
    public static function getPotentialRevenue(?string $status = null): float
    {
        $query = self::query();
        
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['pending', 'follow_up']);
        }

        return $query->sum('total');
    }

    /**
     * Get recovery rate
     */
    public static function getRecoveryRate(int $days = 30): float
    {
        $total = self::where('created_at', '>=', now()->subDays($days))->count();
        
        if ($total === 0) {
            return 0;
        }

        $recovered = self::where('created_at', '>=', now()->subDays($days))
            ->where('status', 'recovered')
            ->count();

        return round(($recovered / $total) * 100, 1);
    }
}

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
        'landing_page_slug',
        'session_id',
        'status',
        'checkout_step',
        'email',
        'phone',
        'name',
        'shipping_address',
        'shipping_location_text',
        'shipping_area',
        'shipping_division',
        'shipping_district',
        'shipping_upazila',
        'shipping_union',
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
        'checkout_fields_payload',
        'recovered_order_id',
        'recovered_at',
        'admin_notes',
        'followed_up_by',
        'followed_up_at',
        'follow_up_date',
        'reminder_count',
        'first_reminder_sent_at',
        'last_reminder_sent_at',
        'last_reminder_channel',
        'last_activity_at',
        'user_agent',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'cart_items' => 'array',
            'checkout_fields_payload' => 'array',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'recovered_at' => 'datetime',
            'followed_up_at' => 'datetime',
            'follow_up_date' => 'date',
            'reminder_count' => 'integer',
            'first_reminder_sent_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
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

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'follow_up']);
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

    public function scopeHighValue($query, float $threshold = 5000)
    {
        return $query->where('total', '>=', $threshold);
    }

    public function scopeReminderDue($query, int $cooldownHours = 24, int $maxReminders = 3)
    {
        $cooldownTime = now()->subHours($cooldownHours);

        return $query
            ->open()
            ->whereNotNull('email')
            ->where(function ($q) use ($cooldownTime) {
                $q->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<=', $cooldownTime);
            })
            ->where(function ($q) use ($maxReminders) {
                $q->whereNull('reminder_count')
                    ->orWhere('reminder_count', '<', $maxReminders);
            });
    }

    public function scopeOverdueFollowUp($query, int $hours = 24)
    {
        $cutoff = now()->subHours($hours);

        return $query->where(function ($q) use ($cutoff) {
            $q->where(function ($pendingQuery) use ($cutoff) {
                $pendingQuery->where('status', 'pending')
                    ->where('last_activity_at', '<=', $cutoff);
            })->orWhere(function ($followUpQuery) use ($cutoff) {
                $followUpQuery->where('status', 'follow_up')
                    ->where(function ($next) use ($cutoff) {
                        $next->whereNull('followed_up_at')
                            ->orWhere('followed_up_at', '<=', $cutoff);
                    });
            });
        });
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
            $this->shipping_area,
            $this->shipping_location_text,
            $this->shipping_union,
            $this->shipping_upazila,
            $this->shipping_district,
            $this->shipping_division,
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

    public function getPriorityLevelAttribute(): string
    {
        if (in_array($this->status, ['recovered', 'cancelled'], true)) {
            return 'low';
        }

        $hasContact = !empty($this->email) || !empty($this->phone);
        $total = (float) $this->total;

        if ($total >= 10000 || ($this->checkout_step === 'payment' && $hasContact)) {
            return 'high';
        }

        if ($total >= 3000 || $this->checkout_step === 'shipping') {
            return 'medium';
        }

        return 'low';
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority_level) {
            'high' => 'danger',
            'medium' => 'warning',
            default => 'secondary',
        };
    }

    // ==================== METHODS ====================

    /**
     * Mark as follow up
     */
    public function markAsFollowUp(?int $adminId = null, ?string $notes = null, ?string $date = null): self
    {
        $this->update([
            'status' => 'follow_up',
            'followed_up_by' => $adminId,
            'followed_up_at' => now(),
            'follow_up_date' => $date,
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
     * Mark as pending (reset)
     */
    public function markAsPending(?string $notes = null): self
    {
        $this->update([
            'status' => 'pending',
            'admin_notes' => $notes ?? $this->admin_notes,
            'followed_up_at' => null,
            'followed_up_by' => null,
            'follow_up_date' => null,
        ]);

        return $this;
    }

    /**
     * Mark reminder delivery metadata and transition pending carts to follow-up.
     */
    public function markReminderSent(string $channel = 'mail'): self
    {
        $updates = [
            'reminder_count' => ((int) $this->reminder_count) + 1,
            'first_reminder_sent_at' => $this->first_reminder_sent_at ?? now(),
            'last_reminder_sent_at' => now(),
            'last_reminder_channel' => $channel,
        ];

        if ($this->status === 'pending') {
            $updates['status'] = 'follow_up';
            $updates['followed_up_at'] = $this->followed_up_at ?? now();
        }

        $this->update($updates);

        return $this;
    }

    /**
     * Create or update from checkout data
     */
    public static function trackCheckout(array $data, ?int $userId = null, ?string $sessionId = null): self
    {
        // Find existing abandoned cart or create new.
        $lookup = self::query();

        if ($userId) {
            $lookup->where('user_id', $userId);
        } elseif ($sessionId) {
            $lookup->where('session_id', $sessionId);
        } else {
            // Prevent accidental cross-user updates when no identifier is available.
            $lookup->whereRaw('1 = 0');
        }

        $abandonedCart = $lookup
            ->whereIn('status', ['pending', 'follow_up'])
            ->where('created_at', '>=', now()->subDays(7))
            ->first();

        if (!$abandonedCart) {
            $abandonedCart = new self();
        }

        $wasFollowUp = $abandonedCart->exists && $abandonedCart->status === 'follow_up';

        $abandonedCart->fill([
            'status' => 'pending',
            'user_id' => $userId ?? $abandonedCart->user_id,
            'session_id' => $sessionId ?? $abandonedCart->session_id,
            'landing_page_slug' => $data['landing_page_slug'] ?? $abandonedCart->landing_page_slug,
            'checkout_step' => $data['checkout_step'] ?? $abandonedCart->checkout_step,
            'email' => $data['email'] ?? $abandonedCart->email,
            'phone' => $data['phone'] ?? $abandonedCart->phone,
            'name' => $data['name'] ?? $abandonedCart->name,
            'shipping_address' => $data['shipping_address'] ?? $abandonedCart->shipping_address,
            'shipping_location_text' => $data['shipping_location_text'] ?? $abandonedCart->shipping_location_text,
            'shipping_area' => $data['shipping_area'] ?? $abandonedCart->shipping_area,
            'shipping_division' => $data['shipping_division'] ?? $abandonedCart->shipping_division,
            'shipping_district' => $data['shipping_district'] ?? $abandonedCart->shipping_district,
            'shipping_upazila' => $data['shipping_upazila'] ?? $abandonedCart->shipping_upazila,
            'shipping_union' => $data['shipping_union'] ?? $abandonedCart->shipping_union,
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
            'checkout_fields_payload' => $data['checkout_fields_payload'] ?? $abandonedCart->checkout_fields_payload,
            'cart_id' => $data['cart_id'] ?? $abandonedCart->cart_id,
            'user_agent' => $data['user_agent'] ?? $abandonedCart->user_agent,
            'ip_address' => $data['ip_address'] ?? $abandonedCart->ip_address,
            'last_activity_at' => now(),
        ]);

        // If user resumed checkout after follow-up, reset follow-up owner/time.
        if ($wasFollowUp) {
            $abandonedCart->followed_up_by = null;
            $abandonedCart->followed_up_at = null;
        }

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

        $recovered = self::whereNotNull('recovered_at')
            ->where('recovered_at', '>=', now()->subDays($days))
            ->count();

        return round(($recovered / $total) * 100, 1);
    }

    /**
     * Aggregated summary for admin dashboard/report cards.
     */
    public static function getSummary(int $days = 30, float $highValueThreshold = 5000, int $overdueHours = 24): array
    {
        $openQuery = self::query()->open();
        $openCount = (clone $openQuery)->count();
        $potentialRevenue = (float) (clone $openQuery)->sum('total');

        $withContactCount = (clone $openQuery)->withContactInfo()->count();
        $highValueOpenCount = (clone $openQuery)->highValue($highValueThreshold)->count();
        $overdueFollowUpCount = self::query()->open()->overdueFollowUp($overdueHours)->count();
        $reminderDueCount = self::query()->reminderDue()->count();

        $recoveredWithinWindow = self::query()
            ->where('status', 'recovered')
            ->whereNotNull('recovered_at')
            ->where('recovered_at', '>=', now()->subDays($days));

        $recoveredCountWithinWindow = (clone $recoveredWithinWindow)->count();
        $recoveredRevenueWithinWindow = (float) (clone $recoveredWithinWindow)->sum('total');

        $totalWithinWindow = self::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->count();

        $recoveryRate = $totalWithinWindow > 0
            ? round(($recoveredCountWithinWindow / $totalWithinWindow) * 100, 1)
            : 0.0;

        return [
            'total' => self::count(),
            'open' => $openCount,
            'pending' => self::pending()->count(),
            'follow_up' => self::followUp()->count(),
            'recovered' => self::recovered()->count(),
            'cancelled' => self::cancelled()->count(),
            'today_abandoned' => self::whereDate('created_at', today())->count(),
            'potential_revenue' => $potentialRevenue,
            'avg_open_value' => $openCount > 0 ? round($potentialRevenue / $openCount, 2) : 0.0,
            'recovered_revenue_30d' => $recoveredRevenueWithinWindow,
            'recovery_rate' => $recoveryRate,
            'with_contact' => $withContactCount,
            'contactable_rate' => $openCount > 0 ? round(($withContactCount / $openCount) * 100, 1) : 0.0,
            'high_value_open' => $highValueOpenCount,
            'overdue_follow_up' => $overdueFollowUpCount,
            'reminder_due' => $reminderDueCount,
            'reminders_sent' => (int) self::sum('reminder_count'),
        ];
    }
}

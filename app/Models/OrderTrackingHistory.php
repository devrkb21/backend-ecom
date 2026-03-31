<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTrackingHistory extends Model
{
    use HasFactory;

    protected $table = 'order_tracking_history';

    protected $fillable = [
        'order_id',
        'status',
        'location',
        'description',
        'carrier_status',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ==================== ACCESSORS ====================

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'order_placed' => 'Order Placed',
            'payment_confirmed' => 'Payment Confirmed',
            'processing' => 'Processing',
            'packed' => 'Packed',
            'shipped' => 'Shipped',
            'in_transit' => 'In Transit',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'returned' => 'Returned',
            'refunded' => 'Refunded',
        ];

        return $labels[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusIconAttribute(): string
    {
        $icons = [
            'order_placed' => '📝',
            'payment_confirmed' => '💳',
            'processing' => '⚙️',
            'packed' => '📦',
            'shipped' => '🚚',
            'in_transit' => '🚛',
            'out_for_delivery' => '🏃',
            'delivered' => '✅',
            'cancelled' => '❌',
            'returned' => '↩️',
            'refunded' => '💰',
        ];

        return $icons[$this->status] ?? '📋';
    }

    // ==================== SCOPES ====================

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('occurred_at', 'desc');
    }

    public function scopeOldestFirst($query)
    {
        return $query->orderBy('occurred_at', 'asc');
    }
}

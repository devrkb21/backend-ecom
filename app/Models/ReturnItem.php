<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_id',
        'order_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'condition',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function return(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ==================== ACCESSORS ====================

    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition) {
            'unopened' => 'Unopened',
            'opened' => 'Opened',
            'damaged' => 'Damaged',
            'used' => 'Used',
            default => ucfirst($this->condition),
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'note',
        'type',
        'is_customer_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_customer_visible' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCustomerVisible($query)
    {
        return $query->where('is_customer_visible', true);
    }

    public function scopeInternal($query)
    {
        return $query->where('type', 'internal');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierCheckResult extends Model
{
    protected $fillable = [
        'normalized_phone',
        'raw_result',
        'total_success',
        'total_cancel',
        'total_deliveries',
        'success_ratio',
        'couriers_ok',
        'couriers_failed',
        'checked_at',
        'last_order_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_result' => 'array',
            'success_ratio' => 'decimal:2',
            'checked_at' => 'datetime',
        ];
    }

    public function lastOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'last_order_id');
    }

    public function cancelRatio(): float
    {
        return round(100 - (float) $this->success_ratio, 2);
    }

    public function isFresh(int $days): bool
    {
        return $this->checked_at !== null && $this->checked_at->gt(now()->subDays($days));
    }

    public function isFreshWithinHours(int $hours): bool
    {
        return $this->checked_at !== null && $this->checked_at->gt(now()->subHours($hours));
    }
}

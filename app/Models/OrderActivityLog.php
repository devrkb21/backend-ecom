<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderActivityLog extends Model
{
    protected $fillable = [
        'order_id',
        'admin_id',
        'admin_name',
        'type',
        'title',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Log an activity for an order.
     */
    public static function log(Order $order, string $type, string $title, ?string $description = null, array $metadata = []): static
    {
        $admin = auth()->user();

        return static::create([
            'order_id' => $order->id,
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name ?? 'System',
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => ! empty($metadata) ? $metadata : null,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethodLocationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_method_id',
        'location_type',
        'location_id',
    ];

    protected function casts(): array
    {
        return [
            'shipping_method_id' => 'integer',
            'location_id' => 'integer',
        ];
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }
}

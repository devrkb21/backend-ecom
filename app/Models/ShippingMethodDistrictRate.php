<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethodDistrictRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_method_id',
        'district_id',
        'rate',
    ];

    protected function casts(): array
    {
        return [
            'shipping_method_id' => 'integer',
            'district_id' => 'integer',
            'rate' => 'decimal:2',
        ];
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(BdDistrict::class, 'district_id');
    }
}

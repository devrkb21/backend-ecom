<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdDistrict extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'division_id',
        'name',
        'bn_name',
        'lat',
        'lon',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'division_id' => 'integer',
            'lat' => 'decimal:7',
            'lon' => 'decimal:7',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(BdDivision::class, 'division_id');
    }

    public function upazilas(): HasMany
    {
        return $this->hasMany(BdUpazila::class, 'district_id');
    }

    public function shippingMethodRates(): HasMany
    {
        return $this->hasMany(ShippingMethodDistrictRate::class, 'district_id');
    }
}

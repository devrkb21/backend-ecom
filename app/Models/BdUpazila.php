<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdUpazila extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'district_id',
        'name',
        'bn_name',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'district_id' => 'integer',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(BdDistrict::class, 'district_id');
    }

    public function unions(): HasMany
    {
        return $this->hasMany(BdUnion::class, 'upazila_id');
    }
}

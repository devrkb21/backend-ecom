<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdDivision extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'bn_name',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
        ];
    }

    public function districts(): HasMany
    {
        return $this->hasMany(BdDistrict::class, 'division_id');
    }
}

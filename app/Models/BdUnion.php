<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BdUnion extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'upazila_id',
        'name',
        'bn_name',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'upazila_id' => 'integer',
        ];
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(BdUpazila::class, 'upazila_id');
    }
}

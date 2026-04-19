<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway',
        'provider_customer_id',
        'provider_payment_method_id',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'card_fingerprint',
        'cardholder_name',
        'is_default',
        'is_active',
        'last_used_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

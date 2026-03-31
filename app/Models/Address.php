<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'type',
        'is_default',
        'name',
        'phone',
        'email',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'instructions',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for shipping addresses
     */
    public function scopeShipping($query)
    {
        return $query->whereIn('type', ['shipping', 'both']);
    }

    /**
     * Scope for billing addresses
     */
    public function scopeBilling($query)
    {
        return $query->whereIn('type', ['billing', 'both']);
    }

    /**
     * Scope for default addresses
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Set this address as default
     */
    public function setAsDefault(): void
    {
        // Remove default from other addresses of same type
        Address::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('type', $this->type)
                    ->orWhere('type', 'both')
                    ->orWhere(function ($q) {
                        if ($this->type === 'both') {
                            $q->whereIn('type', ['shipping', 'billing']);
                        }
                    });
            })
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get formatted full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get formatted address for display
     */
    public function getFormattedAddressAttribute(): string
    {
        $lines = [];
        
        $lines[] = $this->name;
        $lines[] = $this->address_line_1;
        
        if ($this->address_line_2) {
            $lines[] = $this->address_line_2;
        }
        
        $cityLine = $this->city;
        if ($this->state) {
            $cityLine .= ', ' . $this->state;
        }
        if ($this->postal_code) {
            $cityLine .= ' ' . $this->postal_code;
        }
        $lines[] = $cityLine;
        
        $lines[] = $this->country;
        $lines[] = 'Phone: ' . $this->phone;

        return implode("\n", $lines);
    }

    /**
     * Convert to array for order
     */
    public function toOrderData(): array
    {
        return [
            'shipping_name' => $this->name,
            'shipping_phone' => $this->phone,
            'shipping_email' => $this->email,
            'shipping_address' => $this->address_line_1 . ($this->address_line_2 ? ', ' . $this->address_line_2 : ''),
            'shipping_city' => $this->city,
            'shipping_state' => $this->state,
            'shipping_zip' => $this->postal_code,
            'shipping_country' => $this->country,
        ];
    }
}

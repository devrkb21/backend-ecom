<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'role',
        'loyalty_points',
        'lifetime_points',
        'loyalty_tier',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'loyalty_points' => 'integer',
            'lifetime_points' => 'integer',
        ];
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get default shipping address
     */
    public function defaultShippingAddress()
    {
        return $this->addresses()->shipping()->default()->first()
            ?? $this->addresses()->default()->first();
    }

    /**
     * Get default billing address
     */
    public function defaultBillingAddress()
    {
        return $this->addresses()->billing()->default()->first()
            ?? $this->addresses()->default()->first();
    }

    /**
     * Get products in user's wishlist
     */
    public function wishlistProducts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')
            ->withTimestamps();
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function loyaltyRedemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRedemption::class);
    }

    /**
     * Adjust user's loyalty points
     */
    public function adjustLoyaltyPoints(int $points, string $type, string $description, ?int $orderId = null): LoyaltyTransaction
    {
        $newBalance = $this->loyalty_points + $points;

        $transaction = LoyaltyTransaction::create([
            'user_id' => $this->id,
            'order_id' => $orderId,
            'type' => $type,
            'points' => $points,
            'balance_after' => $newBalance,
            'description' => $description,
        ]);

        $this->update([
            'loyalty_points' => $newBalance,
            'lifetime_points' => $this->lifetime_points + ($points > 0 ? $points : 0),
        ]);

        return $transaction;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}

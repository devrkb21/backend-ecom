<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
        'settings',
        'supported_currencies',
        'min_amount',
        'max_amount',
        'icon',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'supported_currencies' => 'array',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
        ];
    }

    /**
     * Get all active payment gateways
     */
    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get gateway by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if gateway is available for given amount
     */
    public function isAvailableFor(float $amount, string $currency = 'BDT'): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Check currency support
        if ($this->supported_currencies && ! in_array($currency, $this->supported_currencies)) {
            return false;
        }

        // Check min amount
        if ($this->min_amount && $amount < $this->min_amount) {
            return false;
        }

        // Check max amount
        if ($this->max_amount && $amount > $this->max_amount) {
            return false;
        }

        return true;
    }

    /**
     * Get a setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value
     */
    public function setSetting(string $key, $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;

        return $this;
    }

    /**
     * Check if this gateway requires redirect (like Stripe, bKash)
     */
    public function requiresRedirect(): bool
    {
        return in_array($this->code, ['stripe', 'bkash']);
    }

    /**
     * Check if payment is collected on delivery
     */
    public function isPayOnDelivery(): bool
    {
        return $this->code === 'cod';
    }

    /**
     * Get display icon HTML
     */
    public function getIconHtml(): string
    {
        $configuredIcon = trim((string) ($this->icon ?? ''));

        if ($configuredIcon !== '') {
            // Render Bootstrap icon classes from admin settings, e.g. "bi-credit-card" or "bi bi-credit-card".
            if (str_contains($configuredIcon, 'bi-') || str_starts_with($configuredIcon, 'bi ')) {
                $className = trim(preg_replace('/\s+/', ' ', $configuredIcon));

                if (str_starts_with($className, 'bi-')) {
                    $className = 'bi '.$className;
                }

                return '<i class="'.e($className).'"></i>';
            }

            // Render image-based icons when admin sets URL or relative storage path.
            if (
                str_starts_with($configuredIcon, 'http://')
                || str_starts_with($configuredIcon, 'https://')
                || str_starts_with($configuredIcon, 'data:image/')
                || str_starts_with($configuredIcon, 'media/')
                || str_starts_with($configuredIcon, '/storage/')
                || str_starts_with($configuredIcon, 'storage/')
                || preg_match('/\.(svg|png|jpe?g|webp|gif)(\?.*)?$/i', $configuredIcon)
            ) {
                $iconPath = $configuredIcon;

                if (
                    ! str_starts_with($iconPath, 'http://')
                    && ! str_starts_with($iconPath, 'https://')
                    && ! str_starts_with($iconPath, '/storage/')
                    && ! str_starts_with($iconPath, 'data:image/')
                ) {
                    $normalizedPath = ltrim($iconPath, '/');

                    if (str_starts_with($normalizedPath, 'media/') || str_starts_with($normalizedPath, 'storage/')) {
                        $iconPath = asset($normalizedPath);
                    } else {
                        $iconPath = asset('storage/'.$normalizedPath);
                    }
                }

                $src = e($iconPath);

                return '<img src="'.$src.'" alt="'.e($this->name).'" style="width:1.5rem;height:1.5rem;object-fit:contain;" loading="lazy">';
            }
        }

        $icons = [
            'cod' => '<i class="bi bi-cash-coin text-success"></i>',
            'stripe' => '<i class="bi bi-credit-card text-primary"></i>',
            'bkash' => '<i class="bi bi-phone text-danger"></i>',
        ];

        return $icons[$this->code] ?? '<i class="bi bi-wallet2"></i>';
    }
}

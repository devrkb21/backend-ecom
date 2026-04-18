<?php

namespace App\Services;

use App\Models\Setting;

class CheckoutTaxService
{
    public function isEnabled(): bool
    {
        return $this->toBoolean(Setting::getValue('checkout', 'tax_enabled', false));
    }

    public function getPercentage(): float
    {
        $raw = Setting::getValue('checkout', 'tax_percentage', 0);
        $numeric = is_numeric($raw) ? (float) $raw : 0.0;

        return max(0, min(100, $numeric));
    }

    public function calculateTaxAmount(float $subtotal): float
    {
        $normalizedSubtotal = max(0, $subtotal);

        if (!$this->isEnabled()) {
            return 0.0;
        }

        $percentage = $this->getPercentage();
        if ($percentage <= 0) {
            return 0.0;
        }

        return round($normalizedSubtotal * ($percentage / 100), 2);
    }

    protected function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}

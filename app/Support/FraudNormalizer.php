<?php

namespace App\Support;

/**
 * Single source of truth for canonicalizing fraud-signal values (phone,
 * email, device) so that format variance (e.g. "01712345678" vs
 * "+8801712345678" vs "880 1712 345678") can't be used to dodge a block —
 * both manual blocklist entries and live checkout checks run every value
 * through the same normalizer before comparing.
 */
class FraudNormalizer
{
    /**
     * Canonicalize a Bangladeshi phone number to its local 11-digit form
     * (01XXXXXXXXX), stripping the +880/880 country code prefix if present.
     * Numbers that don't match a recognizable BD shape are still returned
     * digits-only, so formatting noise (spaces, dashes) never causes a
     * false negative on an otherwise-identical block entry.
     */
    public static function phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = '0'.substr($digits, 3);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            // Missing leading 0, e.g. "1712345678"
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function email(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email === '' ? null : $email;
    }

    /**
     * IPs are compared as-is (no CIDR/subnet folding yet) — just trimmed.
     */
    public static function ip(?string $ip): ?string
    {
        $ip = trim((string) $ip);

        return $ip === '' ? null : $ip;
    }

    /**
     * Raw user-agent strings are long and vary in case; a fixed-length hash
     * keeps the indexed column small and comparisons cheap.
     */
    public static function device(?string $userAgent): ?string
    {
        $ua = trim((string) $userAgent);

        return $ua === '' ? null : hash('sha256', strtolower($ua));
    }

    /**
     * Normalize a fraud_blocks-style value for the given type.
     */
    public static function forType(string $type, ?string $value): ?string
    {
        return match ($type) {
            'phone' => self::phone($value),
            'email' => self::email($value),
            'ip' => self::ip($value),
            'device' => self::device($value),
            default => null,
        };
    }
}

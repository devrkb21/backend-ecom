<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Coderzonebd\LicensingSdk\LicenseManager;
use Exception;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    protected const GROUP = 'license';

    protected ?LicenseManager $manager = null;

    /**
     * Build the SDK client. Throws if the extension/config is missing —
     * callers (activate/verify) are expected to catch and record the error
     * rather than let it bubble into a request/command failure.
     */
    protected function manager(): LicenseManager
    {
        if ($this->manager === null) {
            $this->manager = new LicenseManager(
                (string) config('license.server_url'),
                (string) config('license.public_key'),
                $this->licenseKey(),
                (string) config('license.product_slug'),
                (string) config('license.cache_path'),
                (int) config('license.grace_period_hours'),
            );
        }

        return $this->manager;
    }

    /**
     * The license key can be entered/rotated from the admin panel (stored in
     * the settings table) without needing shell access to edit .env; falls
     * back to LICENSE_KEY from config for the initial deploy.
     */
    public function licenseKey(): string
    {
        $override = (string) Setting::getValue(self::GROUP, 'license_key', '');

        return $override !== '' ? $override : (string) config('license.license_key');
    }

    public function setLicenseKey(string $licenseKey): void
    {
        Setting::setValue(self::GROUP, 'license_key', $licenseKey, ['is_public' => false, 'type' => 'text']);
        // Force a fresh SDK client on the next call so the new key is used
        // instead of one already built from the old value.
        $this->manager = null;
    }

    /**
     * Safe to show in an admin UI: only the last 4 characters, the rest
     * masked. Never expose the raw key back through an API response.
     */
    public function maskedLicenseKey(): ?string
    {
        $key = $this->licenseKey();

        if ($key === '') {
            return null;
        }

        $tail = substr($key, -4);

        return str_repeat('*', max(0, strlen($key) - 4)).$tail;
    }

    /**
     * One-time activation for a fresh domain (run via `license:activate`).
     */
    public function activate(): array
    {
        try {
            $result = $this->manager()->activate();
            Setting::setValue(self::GROUP, 'last_activation_at', now()->toIso8601String(), ['is_public' => false]);
            Setting::setValue(self::GROUP, 'last_activation_error', '', ['is_public' => false]);

            return $result;
        } catch (Exception $e) {
            Setting::setValue(self::GROUP, 'last_activation_error', $e->getMessage(), ['is_public' => false]);
            Log::error('License activation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Periodic check-in (run via `license:verify` on the scheduler). Updates
     * the persisted status so requests can consult it without a network call.
     */
    public function verify(): void
    {
        try {
            $coreConfig = $this->manager()->verify();
            $this->markValid($coreConfig);
        } catch (Exception $e) {
            $this->markInvalid($e->getMessage());
        }
    }

    protected function markValid(array $coreConfig): void
    {
        Setting::setValue(self::GROUP, 'status', 'active', ['is_public' => false, 'type' => 'text']);
        Setting::setValue(self::GROUP, 'core_config', $coreConfig, ['is_public' => false, 'type' => 'json']);
        Setting::setValue(self::GROUP, 'last_verified_at', now()->toIso8601String(), ['is_public' => false]);
        Setting::setValue(self::GROUP, 'last_error', '', ['is_public' => false]);
        // A renewal clears the expiry cutoff so previously-locked orders
        // become visible/manageable again.
        Setting::setValue(self::GROUP, 'expired_since', '', ['is_public' => false]);
    }

    protected function markInvalid(string $error): void
    {
        $wasValid = Setting::getValue(self::GROUP, 'status', 'unactivated') === 'active';

        Setting::setValue(self::GROUP, 'status', 'expired', ['is_public' => false, 'type' => 'text']);
        Setting::setValue(self::GROUP, 'last_error', $error, ['is_public' => false]);

        // Only stamp the cutoff the moment we first transition out of a
        // valid state — repeated failed verifies must not keep pushing it
        // forward, or the order-lock cutoff would never accumulate a backlog.
        if ($wasValid || ! Setting::getValue(self::GROUP, 'expired_since')) {
            Setting::setValue(self::GROUP, 'expired_since', now()->toIso8601String(), ['is_public' => false]);
        }

        Log::warning('License verification failed', ['error' => $error]);
    }

    public function isValid(): bool
    {
        return Setting::getValue(self::GROUP, 'status', 'unactivated') === 'active';
    }

    public function status(): string
    {
        return (string) Setting::getValue(self::GROUP, 'status', 'unactivated');
    }

    public function coreConfig(?string $key = null, mixed $default = null): mixed
    {
        $config = Setting::getValue(self::GROUP, 'core_config', []);

        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default);
    }

    public function lastError(): string
    {
        return (string) Setting::getValue(self::GROUP, 'last_error', '');
    }

    public function lastVerifiedAt(): ?Carbon
    {
        $value = Setting::getValue(self::GROUP, 'last_verified_at', '');

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * The moment this install first transitioned from valid to invalid, or
     * null if currently valid / never has been invalid. This is the cutoff
     * used to lock new orders and block new admin-created resources.
     */
    public function expiredSince(): ?Carbon
    {
        if ($this->isValid()) {
            return null;
        }

        $value = Setting::getValue(self::GROUP, 'expired_since', '');

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Whether admin-initiated creation of a brand new resource should be
     * blocked right now. Storefront/customer actions never consult this.
     */
    public function shouldBlockCreation(): bool
    {
        return ! $this->isValid();
    }

    /**
     * Whether the given order was placed after the license expired, and so
     * should be hidden/locked from the admin surface. Always false while
     * the license is currently valid.
     */
    public function isOrderLocked(Order $order): bool
    {
        $cutoff = $this->expiredSince();

        if ($cutoff === null) {
            return false;
        }

        return $order->created_at !== null && $order->created_at->greaterThan($cutoff);
    }

    /**
     * How many orders are currently hidden from the admin surface because
     * they were placed after the license expired. Zero while the license is
     * valid or has never expired.
     */
    public function lockedOrdersCount(): int
    {
        $cutoff = $this->expiredSince();

        if ($cutoff === null) {
            return 0;
        }

        return Order::where('created_at', '>', $cutoff)->count();
    }
}

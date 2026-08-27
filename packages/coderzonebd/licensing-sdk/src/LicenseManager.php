<?php

namespace Coderzonebd\LicensingSdk;

use Exception;

class LicenseManager
{
    private $serverUrl;
    private $publicKeyBase64;
    private $licenseKey;
    private $productSlug;
    private $cacheFile;
    private $gracePeriodHours;

    public function __construct(string $serverUrl, string $publicKeyBase64, string $licenseKey, string $productSlug, string $cacheFile, int $gracePeriodHours = 0)
    {
        $this->serverUrl = rtrim($serverUrl, '/');
        $this->publicKeyBase64 = $publicKeyBase64;
        $this->licenseKey = $licenseKey;
        $this->productSlug = $productSlug;
        $this->cacheFile = $cacheFile;
        $this->gracePeriodHours = $gracePeriodHours;

        if (!extension_loaded('sodium')) {
            throw new Exception("Sodium extension is required for secure signature verification.");
        }
    }

    /**
     * Activate the license on this machine. Usually run once during installation.
     */
    public function activate(): array
    {
        $payload = [
            'license_key'   => $this->licenseKey,
            'product_slug'  => $this->productSlug,
            'domain'        => $_SERVER['HTTP_HOST'] ?? 'cli',
            'server_ip'     => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
            'hardware_hash' => hash('sha256', php_uname()),
        ];

        return $this->makeRequest('/api/v1/sync/manifest', $payload);
    }

    /**
     * The fast ping that runs continuously to check license validity.
     * Returns the 'core_config' array if successful.
     */
    public function verify(): array
    {
        $nonce = bin2hex(random_bytes(8));
        
        $payload = [
            'license_key' => $this->licenseKey,
            'domain'      => $_SERVER['HTTP_HOST'] ?? 'cli',
            'nonce'       => $nonce,
        ];

        try {
            $response = $this->makeRequest('/api/v1/sync/telemetry', $payload);
            
            if ($response['status'] !== 'active') {
                throw new Exception("License is not active.");
            }

            // Verify Signature using the exact raw JSON string the Go server generated
            $this->verifySignature($response['payload_string'], $response['signature']);

            // Verify Anti-Replay
            if ($response['payload']['nonce'] !== $nonce) {
                throw new Exception("Security Error: Nonce mismatch.");
            }
            if (time() - $response['payload']['iat'] > 15) {
                throw new Exception("Security Error: Payload expired (Replay Attack).");
            }

            // Cache the successful payload
            $this->cachePayload($response);

            return $response['payload']['core_config'];

        } catch (Exception $e) {
            // Check Grace Period Cache
            return $this->handleOfflineGracePeriod($e);
        }
    }

    private function makeRequest(string $endpoint, array $data): array
    {
        $ch = curl_init($this->serverUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Fast timeout to trigger offline fallback quickly
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false || $httpCode >= 400) {
            throw new Exception("Failed to contact licensing server. HTTP Code: " . $httpCode);
        }

        $decoded = json_decode($result, true);
        if (isset($decoded['error'])) {
            throw new Exception("Server Error: " . $decoded['error']);
        }

        return $decoded;
    }

    private function verifySignature(string $message, string $signatureBase64): void
    {
        $publicKey = base64_decode($this->publicKeyBase64);
        $signature = base64_decode($signatureBase64);

        if (!sodium_crypto_sign_verify_detached($signature, $message, $publicKey)) {
            throw new Exception("FATAL: Signature verification failed. License server impersonation detected.");
        }
    }

    private function cachePayload(array $response): void
    {
        if ($this->gracePeriodHours > 0) {
            $dir = dirname($this->cacheFile);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            file_put_contents($this->cacheFile, json_encode($response));
        }
    }

    private function handleOfflineGracePeriod(Exception $originalException): array
    {
        if ($this->gracePeriodHours <= 0 || !file_exists($this->cacheFile)) {
            throw $originalException;
        }

        $cached = json_decode(file_get_contents($this->cacheFile), true);
        if (!$cached || !isset($cached['payload']['iat'])) {
            throw $originalException;
        }

        $hoursOffline = (time() - $cached['payload']['iat']) / 3600;
        if ($hoursOffline > $this->gracePeriodHours) {
            throw new Exception("Grace period of {$this->gracePeriodHours} hours expired. Original error: " . $originalException->getMessage());
        }

        return $cached['payload']['core_config'];
    }
}

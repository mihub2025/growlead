<?php

namespace App\Services\Integrations;

class MetaCapiUserDataNormalizer
{
    /**
     * Normalize and SHA-256 hash email per Meta matching requirements.
     * Returns null when the value is missing or already empty after normalize.
     */
    public function hashEmail(?string $email): ?string
    {
        $normalized = $this->normalizeEmail($email);
        if ($normalized === null) {
            return null;
        }

        return $this->sha256($normalized);
    }

    /**
     * Normalize and SHA-256 hash phone (digits with country code when possible).
     */
    public function hashPhone(?string $phone, ?string $defaultCountryCode = null): ?string
    {
        $normalized = $this->normalizePhone($phone, $defaultCountryCode);
        if ($normalized === null) {
            return null;
        }

        return $this->sha256($normalized);
    }

    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // Meta: trim + lowercase. Do not alter local-part beyond that.
        return $email;
    }

    /**
     * Meta expects phone as digits only, including country code, no symbols.
     */
    public function normalizePhone(?string $phone, ?string $defaultCountryCode = null): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        $cc = preg_replace('/\D+/', '', (string) $defaultCountryCode) ?: null;

        // Local numbers often start with 0 (e.g. 0300...); strip trunk prefix and apply country code.
        if ($cc && str_starts_with($digits, '0')) {
            return $cc.substr($digits, 1);
        }

        if ($cc && ! str_starts_with($digits, $cc) && strlen($digits) <= 10) {
            return $cc.$digits;
        }

        return $digits;
    }

    public function sha256(string $value): string
    {
        // Never double-hash: if caller already passed a 64-char hex digest, return as-is.
        if (preg_match('/^[a-f0-9]{64}$/', $value)) {
            return $value;
        }

        return hash('sha256', $value);
    }
}

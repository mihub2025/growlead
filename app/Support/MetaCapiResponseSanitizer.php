<?php

namespace App\Support;

class MetaCapiResponseSanitizer
{
    /**
     * Remove secrets and unnecessary PII from Meta API payloads before storage/display.
     */
    public static function sanitize(mixed $payload, ?string $accessToken = null): mixed
    {
        if (is_string($payload)) {
            return self::redactString($payload, $accessToken);
        }

        if (! is_array($payload)) {
            return $payload;
        }

        $out = [];
        foreach ($payload as $key => $value) {
            $lower = strtolower((string) $key);
            if (in_array($lower, ['access_token', 'token', 'authorization', 'appsecret_proof', 'capi_access_token'], true)) {
                $out[$key] = '[redacted]';

                continue;
            }
            if (in_array($lower, ['em', 'ph', 'fn', 'ln', 'email', 'phone', 'lead_id'], true) && is_string($value)) {
                $out[$key] = self::maskValue($value);

                continue;
            }
            $out[$key] = self::sanitize($value, $accessToken);
        }

        return $out;
    }

    public static function message(mixed $payload, ?string $accessToken = null): string
    {
        if (is_string($payload)) {
            return self::redactString($payload, $accessToken);
        }

        $message = data_get($payload, 'error.message')
            ?? data_get($payload, 'message')
            ?? (is_array($payload) ? json_encode(self::sanitize($payload, $accessToken)) : (string) $payload);

        return self::redactString((string) $message, $accessToken);
    }

    protected static function redactString(string $value, ?string $accessToken = null): string
    {
        if ($accessToken && $accessToken !== '') {
            $value = str_replace($accessToken, '[redacted-token]', $value);
        }

        $value = preg_replace('/access_token=[^&\s]+/i', 'access_token=[redacted]', $value) ?? $value;
        $value = preg_replace('/EAA[A-Za-z0-9]+/', '[redacted-token]', $value) ?? $value;

        return $value;
    }

    protected static function maskValue(string $value): string
    {
        $len = strlen($value);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, 4).str_repeat('*', max(0, $len - 8)).substr($value, -4);
    }
}

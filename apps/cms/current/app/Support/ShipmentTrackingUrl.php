<?php

declare(strict_types=1);

namespace App\Support;

/** Validate an existing carrier destination; never construct one from untrusted input. */
final class ShipmentTrackingUrl
{
    public static function safe(?string $value): ?string
    {
        if ($value === null || strlen($value) > 2048) return null;
        // Reject controls before trimming, including encoded CR/LF and URL parser ambiguities.
        if (preg_match('/[\x00-\x1f\x7f\\\\]|%(?:0[0-9a-f]|1[0-9a-f]|7f)/i', $value)) return null;
        $url = trim($value);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) return null;
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        if (!str_contains($host, '.') || preg_match('/(?:\.localhost|\.local|\.internal)\z/i', $host)
            || preg_match('/\A(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*\z/i', $host)
            || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)
            || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) return null;
        return $url;
    }
}

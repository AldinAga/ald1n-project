<?php

declare(strict_types=1);

namespace App\Support;

/** Validate a configured courier URL; never infer a carrier or fetch arbitrary URLs. */
final class TrackingUrl
{
    public static function safe(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/[\\\\\x00-\x20\x7f]/', $value)
            || preg_match('/%0[ad]/i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false) return null;
        $parts = parse_url($value);
        if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && !in_array($parts['port'], [80, 443], true))) return null;
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ? $value : null;
        }
        if (!str_contains($host, '.') || preg_match('/(?:^|\.)(localhost|local|internal)$/', $host)
            || !preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])$/D', $host)) return null;
        return $value;
    }
}

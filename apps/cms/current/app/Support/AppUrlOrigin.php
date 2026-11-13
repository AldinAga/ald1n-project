<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The one trusted public origin of this application, taken from
 * config('app.url') and never from the request.
 *
 * Returns null unless the setting is a plain absolute http(s) origin, so a
 * missing or malformed APP_URL leaves URL generation exactly as it was.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class AppUrlOrigin
{
    /** @return string|null "scheme://host[:port]" without a trailing slash */
    public static function configured(): ?string
    {
        $raw = config('app.url');
        if (!is_string($raw)) {
            return null;
        }
        $raw = trim($raw);
        if ($raw === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $raw) === 1) {
            return null;
        }

        $parts = parse_url($raw);
        if (!is_array($parts)) {
            return null;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }
        // An origin only: no credentials, query or fragment, and no sub-path.
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return null;
        }
        $path = (string) ($parts['path'] ?? '');
        if ($path !== '' && $path !== '/') {
            return null;
        }
        $isIpv6 = str_starts_with($host, '[') && str_ends_with($host, ']');
        if (!$isIpv6 && preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', $host) !== 1) {
            return null;
        }

        $origin = $scheme.'://'.$host;
        if (isset($parts['port'])) {
            $port = (int) $parts['port'];
            if ($port < 1 || $port > 65535) {
                return null;
            }
            $origin .= ':'.$port;
        }

        return $origin;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

final class SensitiveDataSanitizer
{
    /** @var list<string> */
    private const SENSITIVE_FRAGMENTS = [
        'password', 'passwd', 'secret', 'token', 'authorization', 'cookie', 'api_key', 'apikey',
        'private_key', 'app_key', 'db_password', 'turnstile', 'remember_token',
    ];

    public function sanitize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return is_string($value) && strlen($value) > 4000 ? mb_substr($value, 0, 4000).'…' : $value;
        }

        $clean = [];
        foreach ($value as $key => $item) {
            $normalized = mb_strtolower((string) $key);
            $sensitive = false;
            foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
                if (str_contains($normalized, $fragment)) {
                    $sensitive = true;
                    break;
                }
            }
            $clean[$key] = $sensitive ? '[REDACTED]' : $this->sanitize($item);
        }

        return $clean;
    }
}

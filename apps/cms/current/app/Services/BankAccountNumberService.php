<?php

declare(strict_types=1);

namespace App\Services;

final class BankAccountNumberService
{
    public function normalize(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;

        if (preg_match('/^(\d{3})\s*-\s*(\d{1,13})\s*-\s*(\d{2})$/', $value, $matches)) {
            return $matches[1].str_pad($matches[2], 13, '0', STR_PAD_LEFT).$matches[3];
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) === 18) return $digits;
        if (strlen($digits) >= 6 && strlen($digits) <= 17) {
            $bank = substr($digits, 0, 3);
            $control = substr($digits, -2);
            $body = substr($digits, 3, -2);
            if ($body !== '' && strlen($body) <= 13) return $bank.str_pad($body, 13, '0', STR_PAD_LEFT).$control;
        }

        return null;
    }

    public function format(string $normalized): string
    {
        if (!preg_match('/^\d{18}$/', $normalized)) return $normalized;
        $body = ltrim(substr($normalized, 3, 13), '0');
        return substr($normalized, 0, 3).'-'.($body !== '' ? $body : '0').'-'.substr($normalized, 16, 2);
    }

    public function passesMod97(string $normalized): bool
    {
        if (!preg_match('/^\d{18}$/', $normalized)) return false;
        $remainder = 0;
        foreach (str_split($normalized) as $digit) $remainder = (($remainder * 10) + (int) $digit) % 97;
        return $remainder === 1;
    }
}

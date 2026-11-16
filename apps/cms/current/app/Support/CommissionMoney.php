<?php

declare(strict_types=1);

namespace App\Support;

/** Iznosi provizije u EUR kao celi centi (int); nikad float. */
final class CommissionMoney
{
    public static function cents(mixed $value): int
    {
        $value = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^(\d{1,9})(?:\.(\d{1,2})\d*)?$/', $value, $m)) {
            return 0;
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    public static function format(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}

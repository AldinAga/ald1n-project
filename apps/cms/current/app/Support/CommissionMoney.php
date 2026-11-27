<?php

declare(strict_types=1);

namespace App\Support;

/** Iznosi provizije u EUR kao celi centi (int); nikad float. */
final class CommissionMoney
{
    /** Samo za testove: simulira da migracija još nije izvršena. */
    public static ?bool $adjustmentsOverride = null;

    public static function cents(mixed $value): int
    {
        return self::minorUnits($value, 9);
    }

    public static function rsdCents(mixed $value): int
    {
        return self::minorUnits($value, 12);
    }

    private static function minorUnits(mixed $value, int $integerDigits): int
    {
        $value = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^(\d{1,'.$integerDigits.'})(?:\.(\d{1,2})\d*)?$/', $value, $m)) {
            return 0;
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    /** Postoji li tabela storno/zaduženja (migracija izvršena); keširano po procesu da ne pravi upit po redu. */
    public static function hasAdjustments(): bool
    {
        if (self::$adjustmentsOverride !== null) {
            return self::$adjustmentsOverride;
        }
        static $has = null;

        return $has ??= \Illuminate\Support\Facades\Schema::hasTable('commission_adjustments');
    }

    public static function format(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}

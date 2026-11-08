<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Serbian count agreement for public storefront copy
 * (1 artikal, 2-4 artikla, 5+ artikala; 11-14 always use the many form;
 * genitive after "od": 1 and 2-4 artikla, 5+ and 11-14 artikala).
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SerbianPlural
{
    public static function choose(int $count, string $one, string $few, string $many): string
    {
        $n = abs($count);
        $lastTwo = $n % 100;
        $last = $n % 10;
        if ($last === 1 && $lastTwo !== 11) {
            return $one;
        }
        if ($last >= 2 && $last <= 4 && ($lastTwo < 12 || $lastTwo > 14)) {
            return $few;
        }

        return $many;
    }

    public static function articles(int $count): string
    {
        return $count.' '.self::choose($count, 'artikal', 'artikla', 'artikala');
    }

    /** Genitive after a preposition such as "od": od 1 artikla, od 5 artikala, od 21 artikla. */
    public static function articlesGenitive(int $count): string
    {
        return $count.' '.self::choose($count, 'artikla', 'artikla', 'artikala');
    }
}

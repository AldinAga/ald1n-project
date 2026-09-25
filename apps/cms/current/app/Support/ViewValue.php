<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Throwable;

final class ViewValue
{
    public static function date(
        mixed $source,
        ?string $attribute = null,
        string $format = 'd.m.Y H:i',
        string $fallback = '—',
    ): string {
        $value = self::value($source, $attribute);

        if ($value === null || $value === '') {
            return $fallback;
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return Carbon::instance($value)->format($format);
            }

            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                return $fallback;
            }

            $text = trim((string) $value);
            if ($text === '' || preg_match('/^0{4}-0{2}-0{2}/', $text) === 1) {
                return $fallback;
            }

            return Carbon::parse($text)->format($format);
        } catch (Throwable) {
            return $fallback;
        }
    }

    public static function dateTimeLocal(mixed $source, ?string $attribute = null): string
    {
        return self::date($source, $attribute, 'Y-m-d\\TH:i', '');
    }

    public static function value(mixed $source, ?string $attribute = null): mixed
    {
        try {
            if ($source instanceof Model && $attribute !== null) {
                return $source->getRawOriginal($attribute);
            }

            if (is_array($source) && $attribute !== null) {
                return $source[$attribute] ?? null;
            }

            if (is_object($source) && $attribute !== null) {
                return $source->{$attribute} ?? null;
            }

            return $source;
        } catch (Throwable) {
            return null;
        }
    }

    public static function decimal(mixed $value, int $maxDecimals = 2, string $fallback = '--'): string
    {
        if (!is_numeric($value)) return $fallback;
        $maxDecimals = max(0, min(2, $maxDecimals));
        $formatted = number_format(round((float) $value, $maxDecimals), $maxDecimals, ',', '.');
        if ($maxDecimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }
        return $formatted;
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = true): ?string
    {
        try {
            if (!Route::has($name)) {
                return null;
            }

            return route($name, $parameters, $absolute);
        } catch (Throwable) {
            return null;
        }
    }
}

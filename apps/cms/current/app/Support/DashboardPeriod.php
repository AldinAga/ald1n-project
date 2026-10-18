<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Pure, strict dashboard GET period resolver; never normalizes invalid input
 * into apparently valid finance data. Defaults to current month-to-date.
 */
final class DashboardPeriod
{
    /** @param array<string,mixed> $query @return array{period:string,date_from:string,date_to:string,error:?string} */
    public static function resolve(array $query, CarbonImmutable $today): array
    {
        $today = $today->setTimezone('Europe/Belgrade')->startOfDay();
        $fallback = [
            'period' => 'default',
            'date_from' => $today->startOfMonth()->toDateString(),
            'date_to' => $today->toDateString(),
            'error' => null,
        ];
        $mode = $query['period'] ?? 'default';
        if (!is_string($mode) || !in_array($mode, ['default', '7d', '30d', 'custom'], true)) {
            return array_replace($fallback, ['error' => 'Izaberite važeći vremenski period.']);
        }

        if ($mode === 'default') {
            return $fallback;
        }

        if ($mode === '7d' || $mode === '30d') {
            return [
                'period' => $mode,
                'date_from' => $today->subDays($mode === '7d' ? 6 : 29)->toDateString(),
                'date_to' => $today->toDateString(),
                'error' => null,
            ];
        }

        $from = $query['date_from'] ?? null;
        $to = $query['date_to'] ?? null;
        if (!self::isValidDate($from) || !self::isValidDate($to)) {
            return array_replace($fallback, [
                'period' => 'custom',
                'error' => 'Unesite oba ispravna datuma u formatu GGGG-MM-DD.',
            ]);
        }
        if ($from > $to) {
            return array_replace($fallback, [
                'period' => 'custom',
                'error' => 'Početni datum ne može biti posle završnog datuma.',
            ]);
        }

        return ['period' => 'custom', 'date_from' => $from, 'date_to' => $to, 'error' => null];
    }

    private static function isValidDate(mixed $date): bool
    {
        if (!is_string($date) || !preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $date)) {
            return false;
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('Europe/Belgrade'));

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}

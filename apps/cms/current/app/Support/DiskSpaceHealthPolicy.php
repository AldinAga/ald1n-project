<?php

declare(strict_types=1);

namespace App\Support;

final class DiskSpaceHealthPolicy
{
    private const GIB = 1073741824;

    /**
     * @param array<string,mixed> $thresholds
     * @return array{status:string,message:string,free_percent:?float}
     */
    public static function evaluate(int|float|false $free, int|float|false $total, array $thresholds = []): array
    {
        if ((!is_int($free) && !is_float($free)) || (!is_int($total) && !is_float($total)) || $total <= 0) {
            return [
                'status' => 'warning',
                'message' => 'Nije moguće pouzdano očitati slobodan prostor na disku.',
                'free_percent' => null,
            ];
        }

        $freeBytes = max(0.0, (float) $free);
        $totalBytes = max(1.0, (float) $total);
        $freePercent = ($freeBytes / $totalBytes) * 100;

        $criticalFreeBytes = self::nonNegative($thresholds['critical_free_bytes'] ?? self::GIB);
        $warningFreeBytes = max(
            $criticalFreeBytes,
            self::nonNegative($thresholds['warning_free_bytes'] ?? (5 * self::GIB)),
        );
        $criticalFreePercent = self::percentage($thresholds['critical_free_percent'] ?? 2.0);
        $warningFreePercent = max(
            $criticalFreePercent,
            self::percentage($thresholds['warning_free_percent'] ?? 5.0),
        );
        $criticalRelativeMaxFreeBytes = max(
            $criticalFreeBytes,
            self::nonNegative($thresholds['critical_relative_max_free_bytes'] ?? (10 * self::GIB)),
        );
        $warningRelativeMaxFreeBytes = max(
            $criticalRelativeMaxFreeBytes,
            self::nonNegative($thresholds['warning_relative_max_free_bytes'] ?? (50 * self::GIB)),
        );

        $critical = $freeBytes < $criticalFreeBytes
            || ($freePercent < $criticalFreePercent && $freeBytes < $criticalRelativeMaxFreeBytes);
        $warning = !$critical && (
            $freeBytes < $warningFreeBytes
            || ($freePercent < $warningFreePercent && $freeBytes < $warningRelativeMaxFreeBytes)
        );

        $status = $critical ? 'critical' : ($warning ? 'warning' : 'healthy');
        $baseMessage = number_format($freeBytes / self::GIB, 2, ',', '.').' GB slobodno ('
            .number_format($freePercent, 1, ',', '.').'%).';

        $message = match ($status) {
            'critical' => $baseMessage.' Kritično malo efektivno dostupnog prostora; odmah oslobodi prostor ili proširi storage.',
            'warning' => $baseMessage.' Slobodan prostor je ispod upozoravajućeg praga; planiraj čišćenje pre narednog backupa/deploy-a.',
            default => $baseMessage.' Apsolutno slobodan prostor je dovoljan.',
        };

        return [
            'status' => $status,
            'message' => $message,
            'free_percent' => $freePercent,
        ];
    }

    private static function nonNegative(mixed $value): float
    {
        return max(0.0, is_numeric($value) ? (float) $value : 0.0);
    }

    private static function percentage(mixed $value): float
    {
        $number = is_numeric($value) ? (float) $value : 0.0;

        return min(100.0, max(0.0, $number));
    }
}

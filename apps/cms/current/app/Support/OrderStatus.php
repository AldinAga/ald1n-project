<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Jedini izvor naziva statusa porudžbine (odluka vlasnika 16.11.2026, tačka 20):
 * Nova, Potvrđena, Poslata, Isporučena, Otkazana. Stari status "processing" ostaje samo u starim redovima
 * i prikazuje se kao "Nova". Porudžbina sa upisanim completed_at je uvek "Isporučena".
 * Author: Ald1n <https://ald1n.com>
 */
final class OrderStatus
{
    /** @var array<string,string> */
    public const LABELS = [
        'new' => 'Nova',
        'processing' => 'Nova',
        'confirmed' => 'Potvrđena',
        'shipped' => 'Poslata',
        'completed' => 'Isporučena',
        'cancelled' => 'Otkazana',
    ];

    /** Status za prikaz i filtere: "processing" je "new", porudžbina sa completed_at je "completed". */
    public static function effective(?string $status, bool $delivered = false): string
    {
        if ($delivered) {
            return 'completed';
        }
        $status = (string) $status;

        return $status === 'processing' ? 'new' : $status;
    }

    public static function label(?string $status, bool $delivered = false): string
    {
        $effective = self::effective($status, $delivered);

        return self::LABELS[$effective] ?? ($effective !== '' ? $effective : '—');
    }

    /** Ton čipa (a1-pill--*): isporučena zelena, otkazana crvena, poslata plava, ostalo upozorenje. */
    public static function tone(?string $status, bool $delivered = false): string
    {
        return match (self::effective($status, $delivered)) {
            'completed' => 'ok',
            'cancelled' => 'danger',
            'shipped' => 'info',
            'new', 'confirmed' => 'warning',
            default => '',
        };
    }
}

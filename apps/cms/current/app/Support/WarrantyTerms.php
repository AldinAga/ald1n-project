<?php

declare(strict_types=1);

namespace App\Support;

/** Prospective default only: never replace an already-issued certificate snapshot. */
final class WarrantyTerms
{
    public const DEFAULT_TEXT = 'Garancija ne obuhvata kvarove i oštećenja za koja se utvrdi da su nastali nakon predaje uređaja usled pada, udara, pritiska, prodora tečnosti, nepravilnog priključenja, korišćenja suprotno nameni ili uputstvu, kao ni neovlašćenog zahvata koji je prouzrokovao kvar. Uzrok i povezanost oštećenja sa prijavljenim kvarom utvrđuju se pregledom i obrazloženim nalazom. Ova isključenja ne ograničavaju pravo potrošača da podnese reklamaciju niti njegova zakonska prava zbog nesaobraznosti, koja ostvaruje prema prodavcu bez naknade.';

    public static function forNewCertificate(?string $configured): string
    {
        return trim((string) $configured) !== '' ? $configured : self::DEFAULT_TEXT;
    }
}

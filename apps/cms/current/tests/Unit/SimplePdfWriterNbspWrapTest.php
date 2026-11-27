<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Pdf\SimplePdfWriter;
use PHPUnit\Framework\TestCase;

/** NBSP drži „12,00 EUR“ zajedno, ali tekst sa NBSP iz baze (npr. adresa iz Worda) se i dalje lomi po razmaku, ne po slovu. */
final class SimplePdfWriterNbspWrapTest extends TestCase
{
    public function test_address_with_nbsp_in_narrow_column_breaks_at_spaces_not_letters(): void
    {
        $pdf = new SimplePdfWriter();
        $address = str_replace(' ', "\u{00A0}", 'Ulica Kralja Aleksandra Karađorđevića 123');

        $lines = $pdf->wrap($address, 80, 8);

        self::assertGreaterThan(1, count($lines));
        $words = explode(' ', 'Ulica Kralja Aleksandra Karađorđevića 123');
        foreach ($lines as $line) {
            self::assertLessThanOrEqual(80.01, $pdf->stringWidth($line, 8));
            foreach (explode(' ', $line) as $part) {
                self::assertContains($part, $words, 'red počinje ili se završava delom reči: "'.$line.'"');
            }
        }
        self::assertSame(implode(' ', $words), implode(' ', $lines));
    }

    public function test_amount_and_currency_joined_by_nbsp_stay_on_one_line(): void
    {
        $pdf = new SimplePdfWriter();
        $text = "Otvorena zaduženja na dan generisanja: 12.345,67\u{00A0}EUR";
        $width = $pdf->stringWidth('Otvorena zaduženja na dan generisanja: 12.345,67', 9) + 2;

        $lines = $pdf->wrap($text, $width, 9);

        self::assertSame("12.345,67\u{00A0}EUR", end($lines));
    }
}

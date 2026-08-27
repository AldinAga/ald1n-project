<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Pdf\SimplePdfWriter;
use PHPUnit\Framework\TestCase;

final class SimplePdfWriterSerbianLatinTest extends TestCase
{
    public function test_serbian_latin_glyphs_use_embedded_truetype_and_tounicode(): void
    {
        $sample = 'ŠĐČĆŽ šđčćž | Đorđe Čačak Šabac Ćuprija Željko';
        $pdf = new SimplePdfWriter();
        $pdf->addPage();
        $pdf->text(40, 40, $sample, 12, false);
        $pdf->text(40, 70, $sample, 12, true);
        $output = $pdf->output();

        self::assertStringStartsWith('%PDF-1.4', $output);
        self::assertStringContainsString('/Subtype /TrueType', $output);
        self::assertStringContainsString('/FontFile2', $output);
        self::assertStringContainsString('/ToUnicode', $output);
        self::assertStringNotContainsString('/Subtype /Type1 /BaseFont /Helvetica', $output);

        foreach ([
            '<8A> <0160>', '<D0> <0110>', '<C8> <010C>', '<C6> <0106>', '<8E> <017D>',
            '<9A> <0161>', '<F0> <0111>', '<E8> <010D>', '<E6> <0107>', '<9E> <017E>',
        ] as $mapping) {
            self::assertStringContainsString($mapping, $output);
        }

        $encoded = iconv('UTF-8', 'Windows-1250//TRANSLIT//IGNORE', $sample);
        self::assertIsString($encoded);
        self::assertStringContainsString($encoded, $output);
    }

    public function test_embedded_font_metrics_drive_wrapping_widths(): void
    {
        $pdf = new SimplePdfWriter();
        $wide = $pdf->stringWidth('WWWW ŠĐŽ', 10, false);
        $narrow = $pdf->stringWidth('iiii ščć', 10, false);
        self::assertGreaterThan($narrow, $wide);
        self::assertGreaterThan(0.0, $pdf->stringWidth('Đorđe', 10, true));
    }
}

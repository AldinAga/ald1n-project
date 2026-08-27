<?php

declare(strict_types=1);

use App\Services\Pdf\SimplePdfWriter;

require dirname(__DIR__).'/vendor/autoload.php';

$sample = 'ŠĐČĆŽ šđčćž | Đorđe Čačak Šabac Ćuprija Željko';
$pdf = new SimplePdfWriter();
$pdf->addPage();
$pdf->text(40, 42, 'Ald1n PDF Serbian Latin smoke', 14, true);
$pdf->text(40, 78, $sample, 12, false);
$pdf->text(40, 108, $sample, 12, true);
$output = $pdf->output();

$checks = [
    'pdf_header' => str_starts_with($output, '%PDF-1.4'),
    'truetype' => str_contains($output, '/Subtype /TrueType'),
    'fontfile2' => str_contains($output, '/FontFile2'),
    'tounicode' => str_contains($output, '/ToUnicode'),
    'no_legacy_type1_helvetica' => !str_contains($output, '/Subtype /Type1 /BaseFont /Helvetica'),
];
foreach ([
    '<8A> <0160>', '<D0> <0110>', '<C8> <010C>', '<C6> <0106>', '<8E> <017D>',
    '<9A> <0161>', '<F0> <0111>', '<E8> <010D>', '<E6> <0107>', '<9E> <017E>',
] as $mapping) {
    $checks['map_'.$mapping] = str_contains($output, $mapping);
}
$encoded = iconv('UTF-8', 'Windows-1250//TRANSLIT//IGNORE', $sample);
$checks['encoded_serbian_text'] = is_string($encoded) && str_contains($output, $encoded);

$failed = array_keys(array_filter($checks, static fn (bool $ok): bool => !$ok));
if ($failed !== []) {
    fwrite(STDERR, 'FAIL PDF Serbian Latin smoke: '.implode(', ', $failed).PHP_EOL);
    exit(1);
}

$target = trim((string) ($argv[1] ?? ''));
if ($target !== '') {
    $directory = dirname($target);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        fwrite(STDERR, 'FAIL Cannot create smoke PDF directory.'.PHP_EOL);
        exit(1);
    }
    if (file_put_contents($target, $output) === false) {
        fwrite(STDERR, 'FAIL Cannot write smoke PDF.'.PHP_EOL);
        exit(1);
    }
    echo 'SMOKE_PDF='.$target.PHP_EOL;
}

echo 'PASS PDF Serbian Latin embedded TrueType + ToUnicode'.PHP_EOL;
echo 'SAMPLE='.$sample.PHP_EOL;
echo 'PDF_BYTES='.strlen($output).PHP_EOL;

<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use RuntimeException;

/**
 * Mali dependency-free PDF 1.4 renderer za poslovne dokumente.
 * Ugradjuje DejaVu TrueType fontove i CP1250 + ToUnicode mapu tako da
 * srpska latinica (S/D/C/C/Z sa dijakriticima) ostaje vidljiva i kopirljiva.
 */
final class SimplePdfWriter
{
    public const PAGE_WIDTH = 595.28;
    public const PAGE_HEIGHT = 841.89;

    /** @var list<string> */
    private array $pages = [];
    private int $currentPage = -1;
    /** @var array<string,array{name:string,path:string,width:int,height:int,data:string,filter:string}> */
    private array $images = [];
    /** @var array<string,array<string,mixed>> */
    private array $fontSpecs = [];

    public function addPage(): int
    {
        $this->pages[] = '';
        $this->currentPage = array_key_last($this->pages);
        return $this->currentPage;
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    public function text(float $x, float $top, string $text, float $size = 10, bool $bold = false, string $color = '#1f2937', string $align = 'left'): void
    {
        $this->assertPage();
        $font = $bold ? 'F2' : 'F1';
        [$r, $g, $b] = $this->rgb($color);
        $width = $this->stringWidth($text, $size, $bold);
        if ($align === 'right') $x -= $width;
        if ($align === 'center') $x -= $width / 2;
        $y = self::PAGE_HEIGHT - $top - $size;
        $encoded = $this->escape($this->encode($text));
        $this->command(sprintf("BT /%s %.2F Tf %.4F %.4F %.4F rg %.2F %.2F Td (%s) Tj ET\n", $font, $size, $r, $g, $b, $x, $y, $encoded));
    }

    /** @return float Top position after rendered lines. */
    public function wrappedText(float $x, float $top, string $text, float $maxWidth, float $size = 10, float $lineHeight = 14, bool $bold = false, string $color = '#1f2937', int $maxLines = 0): float
    {
        $lines = $this->wrap($text, $maxWidth, $size, $bold);
        if ($maxLines > 0 && count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $last = rtrim((string) array_pop($lines), " .\t\n\r\0\x0B").'...';
            $lines[] = $last;
        }
        foreach ($lines as $line) {
            $this->text($x, $top, $line, $size, $bold, $color);
            $top += $lineHeight;
        }
        return $top;
    }

    public function line(float $x1, float $top1, float $x2, float $top2, string $color = '#d7dde5', float $width = 1): void
    {
        [$r, $g, $b] = $this->rgb($color);
        $y1 = self::PAGE_HEIGHT - $top1;
        $y2 = self::PAGE_HEIGHT - $top2;
        $this->command(sprintf("q %.4F %.4F %.4F RG %.2F w %.2F %.2F m %.2F %.2F l S Q\n", $r, $g, $b, $width, $x1, $y1, $x2, $y2));
    }

    public function rect(float $x, float $top, float $width, float $height, string $fill = '#ffffff', ?string $stroke = null, float $strokeWidth = 1): void
    {
        [$fr, $fg, $fb] = $this->rgb($fill);
        $y = self::PAGE_HEIGHT - $top - $height;
        $command = sprintf('q %.4F %.4F %.4F rg ', $fr, $fg, $fb);
        if ($stroke !== null) {
            [$sr, $sg, $sb] = $this->rgb($stroke);
            $command .= sprintf('%.4F %.4F %.4F RG %.2F w ', $sr, $sg, $sb, $strokeWidth);
        }
        $command .= sprintf('%.2F %.2F %.2F %.2F re %s Q', $x, $y, $width, $height, $stroke !== null ? 'B' : 'f');
        $this->command($command."\n");
    }

    public function roundedRect(float $x, float $top, float $width, float $height, float $radius, string $fill = '#ffffff', ?string $stroke = null, float $strokeWidth = 1): void
    {
        $radius = max(0, min($radius, min($width, $height) / 2));
        [$fr, $fg, $fb] = $this->rgb($fill);
        $y = self::PAGE_HEIGHT - $top - $height;
        $k = 0.5522847498;
        $c = $radius * $k;
        $x2 = $x + $width;
        $y2 = $y + $height;
        $path = sprintf(
            '%.2F %.2F m %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c h',
            $x + $radius, $y,
            $x2 - $radius, $y,
            $x2 - $radius + $c, $y, $x2, $y + $radius - $c, $x2, $y + $radius,
            $x2, $y2 - $radius,
            $x2, $y2 - $radius + $c, $x2 - $radius + $c, $y2, $x2 - $radius, $y2,
            $x + $radius, $y2,
            $x + $radius - $c, $y2, $x, $y2 - $radius + $c, $x, $y2 - $radius,
            $x, $y + $radius,
            $x, $y + $radius - $c, $x + $radius - $c, $y, $x + $radius, $y,
        );
        $command = sprintf('q %.4F %.4F %.4F rg ', $fr, $fg, $fb);
        if ($stroke !== null) {
            [$sr, $sg, $sb] = $this->rgb($stroke);
            $command .= sprintf('%.4F %.4F %.4F RG %.2F w ', $sr, $sg, $sb, $strokeWidth);
        }
        $command .= $path.' '.($stroke !== null ? 'B' : 'f').' Q';
        $this->command($command."\n");
    }

    public function imageJpeg(float $x, float $top, float $width, float $height, string $path): bool
    {
        if (!is_file($path)) return false;
        $info = @getimagesize($path);
        if (!is_array($info) || ($info['mime'] ?? '') !== 'image/jpeg') return false;
        $data = @file_get_contents($path);
        if (!is_string($data) || $data === '') return false;

        $key = hash('sha256', $path.'|'.filesize($path).'|'.filemtime($path));
        if (!isset($this->images[$key])) {
            $this->images[$key] = [
                'name' => 'Im'.(count($this->images) + 1),
                'path' => $path,
                'width' => (int) $info[0],
                'height' => (int) $info[1],
                'data' => $data,
                'filter' => 'DCTDecode',
            ];
        }
        $name = $this->images[$key]['name'];
        $y = self::PAGE_HEIGHT - $top - $height;
        $this->command(sprintf("q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n", $width, $height, $x, $y, $name));
        return true;
    }

    public function imagePng(float $x, float $top, float $width, float $height, string $path): bool
    {
        if (!is_file($path)) return false;
        $data = @file_get_contents($path);
        if (!is_string($data) || !str_starts_with($data, "\x89PNG\r\n\x1a\n")) return false;

        try {
            [$pixelWidth, $pixelHeight, $rgb] = $this->decodePng($data);
            $compressed = gzcompress($rgb, 9);
            if (!is_string($compressed)) return false;
        } catch (RuntimeException) {
            return false;
        }

        $key = hash('sha256', $path.'|'.filesize($path).'|'.filemtime($path));
        if (!isset($this->images[$key])) {
            $this->images[$key] = [
                'name' => 'Im'.(count($this->images) + 1),
                'path' => $path,
                'width' => $pixelWidth,
                'height' => $pixelHeight,
                'data' => $compressed,
                'filter' => 'FlateDecode',
            ];
        }
        $name = $this->images[$key]['name'];
        $y = self::PAGE_HEIGHT - $top - $height;
        $this->command(sprintf("q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n", $width, $height, $x, $y, $name));
        return true;
    }

    /** @return array{int,int,string} */
    private function decodePng(string $png): array
    {
        $offset = 8;
        $length = strlen($png);
        $width = $height = $bitDepth = $colorType = $interlace = null;
        $palette = '';
        $idat = '';

        while ($offset + 12 <= $length) {
            $chunkLength = unpack('N', substr($png, $offset, 4))[1] ?? -1;
            $type = substr($png, $offset + 4, 4);
            if ($chunkLength < 0 || $offset + 12 + $chunkLength > $length) throw new RuntimeException('Neispravan PNG chunk.');
            $chunk = substr($png, $offset + 8, $chunkLength);
            if ($type === 'IHDR') {
                $header = unpack('Nwidth/Nheight/Cbit/Ccolor/Ccompression/Cfilter/Cinterlace', $chunk);
                $width = (int) ($header['width'] ?? 0);
                $height = (int) ($header['height'] ?? 0);
                $bitDepth = (int) ($header['bit'] ?? 0);
                $colorType = (int) ($header['color'] ?? -1);
                $interlace = (int) ($header['interlace'] ?? 1);
                if (($header['compression'] ?? 1) !== 0 || ($header['filter'] ?? 1) !== 0) throw new RuntimeException('Nepodržan PNG format.');
            } elseif ($type === 'PLTE') {
                $palette .= $chunk;
            } elseif ($type === 'IDAT') {
                $idat .= $chunk;
            } elseif ($type === 'IEND') {
                break;
            }
            $offset += 12 + $chunkLength;
        }

        if (!$width || !$height || $interlace !== 0 || $idat === '') throw new RuntimeException('Nepodržan PNG dokument.');
        $channels = match ($colorType) { 0 => 1, 2 => 3, 3 => 1, 4 => 2, 6 => 4, default => 0 };
        if ($channels === 0 || !in_array($bitDepth, [1, 2, 4, 8, 16], true)) throw new RuntimeException('Nepodržan PNG color type.');
        if (!in_array($colorType, [0, 3], true) && !in_array($bitDepth, [8, 16], true)) throw new RuntimeException('Nepodržana PNG dubina boje.');

        $raw = @gzuncompress($idat);
        if (!is_string($raw) && strlen($idat) > 6) {
            $raw = @gzinflate(substr($idat, 2, -4));
        }
        if (!is_string($raw)) throw new RuntimeException('PNG dekompresija nije uspela.');
        $rowBytes = (int) ceil($width * $channels * $bitDepth / 8);
        $bytesPerPixel = max(1, (int) ceil($channels * $bitDepth / 8));
        $expected = ($rowBytes + 1) * $height;
        if (strlen($raw) < $expected) throw new RuntimeException('PNG scanline je nepotpun.');

        $position = 0;
        $previous = array_fill(0, $rowBytes, 0);
        $rgb = '';
        for ($y = 0; $y < $height; $y++) {
            $filter = ord($raw[$position++]);
            $scan = array_values(unpack('C*', substr($raw, $position, $rowBytes)) ?: []);
            $position += $rowBytes;
            $reconstructed = [];
            for ($i = 0; $i < $rowBytes; $i++) {
                $left = $i >= $bytesPerPixel ? $reconstructed[$i - $bytesPerPixel] : 0;
                $up = $previous[$i] ?? 0;
                $upLeft = $i >= $bytesPerPixel ? ($previous[$i - $bytesPerPixel] ?? 0) : 0;
                $value = $scan[$i] ?? 0;
                $value = match ($filter) {
                    0 => $value,
                    1 => ($value + $left) & 255,
                    2 => ($value + $up) & 255,
                    3 => ($value + intdiv($left + $up, 2)) & 255,
                    4 => ($value + $this->paeth($left, $up, $upLeft)) & 255,
                    default => throw new RuntimeException('Nepoznat PNG filter.'),
                };
                $reconstructed[$i] = $value;
            }
            $previous = $reconstructed;
            $rgb .= $this->pngRowToRgb($reconstructed, $width, $bitDepth, $colorType, $palette);
        }

        return [$width, $height, $rgb];
    }

    /** @param list<int> $row */
    private function pngRowToRgb(array $row, int $width, int $bitDepth, int $colorType, string $palette): string
    {
        $rgb = '';
        if ($bitDepth < 8) {
            for ($x = 0; $x < $width; $x++) {
                $sample = $this->packedSample($row, $x, $bitDepth);
                if ($colorType === 3) {
                    $base = $sample * 3;
                    if ($base + 2 >= strlen($palette)) throw new RuntimeException('PNG paleta nije potpuna.');
                    $rgb .= $palette[$base].$palette[$base + 1].$palette[$base + 2];
                } else {
                    $max = (1 << $bitDepth) - 1;
                    $gray = (int) round($sample * 255 / $max);
                    $rgb .= chr($gray).chr($gray).chr($gray);
                }
            }
            return $rgb;
        }

        $step = $bitDepth === 16 ? 2 : 1;
        $index = 0;
        for ($x = 0; $x < $width; $x++) {
            if ($colorType === 0) {
                $gray = $row[$index] ?? 0;
                $rgb .= chr($gray).chr($gray).chr($gray);
                $index += $step;
            } elseif ($colorType === 2) {
                $rgb .= chr($row[$index] ?? 0).chr($row[$index + $step] ?? 0).chr($row[$index + 2 * $step] ?? 0);
                $index += 3 * $step;
            } elseif ($colorType === 3) {
                $sample = $row[$index++] ?? 0;
                $base = $sample * 3;
                if ($base + 2 >= strlen($palette)) throw new RuntimeException('PNG paleta nije potpuna.');
                $rgb .= $palette[$base].$palette[$base + 1].$palette[$base + 2];
            } elseif ($colorType === 4) {
                $gray = $row[$index] ?? 0;
                $alpha = $row[$index + $step] ?? 255;
                $gray = $this->onWhite($gray, $alpha);
                $rgb .= chr($gray).chr($gray).chr($gray);
                $index += 2 * $step;
            } else {
                $red = $row[$index] ?? 0;
                $green = $row[$index + $step] ?? 0;
                $blue = $row[$index + 2 * $step] ?? 0;
                $alpha = $row[$index + 3 * $step] ?? 255;
                $rgb .= chr($this->onWhite($red, $alpha)).chr($this->onWhite($green, $alpha)).chr($this->onWhite($blue, $alpha));
                $index += 4 * $step;
            }
        }
        return $rgb;
    }

    /** @param list<int> $row */
    private function packedSample(array $row, int $pixel, int $bitDepth): int
    {
        $perByte = intdiv(8, $bitDepth);
        $byte = $row[intdiv($pixel, $perByte)] ?? 0;
        $shift = 8 - $bitDepth * (($pixel % $perByte) + 1);
        return ($byte >> $shift) & ((1 << $bitDepth) - 1);
    }

    private function onWhite(int $channel, int $alpha): int
    {
        return (int) round(($channel * $alpha + 255 * (255 - $alpha)) / 255);
    }

    private function paeth(int $left, int $up, int $upLeft): int
    {
        $estimate = $left + $up - $upLeft;
        $leftDistance = abs($estimate - $left);
        $upDistance = abs($estimate - $up);
        $diagonalDistance = abs($estimate - $upLeft);
        if ($leftDistance <= $upDistance && $leftDistance <= $diagonalDistance) return $left;
        if ($upDistance <= $diagonalDistance) return $up;
        return $upLeft;
    }

    public function stringWidth(string $text, float $size, bool $bold = false): float
    {
        $encoded = $this->encode($text);
        if ($encoded === '') return 0.0;
        $font = $this->fontSpec($bold);
        $widths = (array) $font['widths'];
        $defaultWidth = (int) ($font['default_width'] ?? 500);
        $units = 0;
        $length = strlen($encoded);
        for ($index = 0; $index < $length; $index++) {
            $units += (int) ($widths[ord($encoded[$index])] ?? $defaultWidth);
        }
        return max(0.0, $units * $size / 1000);
    }

    /** @return list<string> */
    public function wrap(string $text, float $maxWidth, float $size, bool $bold = false): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') return [''];
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            if ($this->stringWidth($candidate, $size, $bold) <= $maxWidth || $line === '') {
                $line = $candidate;
                continue;
            }
            $lines[] = $line;
            $line = $word;
        }
        if ($line !== '') $lines[] = $line;
        return $lines;
    }

    public function output(): string
    {
        if ($this->pages === []) throw new RuntimeException('PDF nema nijednu stranicu.');

        $objects = [];
        $add = static function (string $content) use (&$objects): int {
            $objects[] = $content;
            return count($objects);
        };

        $catalogId = $add('');
        $pagesId = $add('');
        $encodingId = $add('<< /Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [138 /Scaron 142 /Zcaron 154 /scaron 158 /zcaron 198 /Cacute 200 /Ccaron 208 /Dcroat 230 /cacute 232 /ccaron 240 /dcroat] >>');
        $toUnicode = $this->toUnicodeCMap();
        $toUnicodeId = $add(sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($toUnicode), $toUnicode));
        $regularFont = $this->fontSpec(false);
        $boldFont = $this->fontSpec(true);
        $regularCompressed = gzcompress((string) $regularFont['data'], 9);
        $boldCompressed = gzcompress((string) $boldFont['data'], 9);
        if (!is_string($regularCompressed) || !is_string($boldCompressed)) {
            throw new RuntimeException('PDF TrueType font kompresija nije uspela.');
        }
        $regularFontFileId = $add(sprintf(
            "<< /Length %d /Length1 %d /Filter /FlateDecode >>\nstream\n%s\nendstream",
            strlen($regularCompressed), strlen((string) $regularFont['data']), $regularCompressed,
        ));
        $boldFontFileId = $add(sprintf(
            "<< /Length %d /Length1 %d /Filter /FlateDecode >>\nstream\n%s\nendstream",
            strlen($boldCompressed), strlen((string) $boldFont['data']), $boldCompressed,
        ));
        $regularDescriptorId = $add($this->fontDescriptorObject($regularFont, $regularFontFileId));
        $boldDescriptorId = $add($this->fontDescriptorObject($boldFont, $boldFontFileId));
        $fontRegularId = $add($this->fontObject($regularFont, $encodingId, $toUnicodeId, $regularDescriptorId));
        $fontBoldId = $add($this->fontObject($boldFont, $encodingId, $toUnicodeId, $boldDescriptorId));

        $imageObjectIds = [];
        foreach ($this->images as $image) {
            $imageObjectIds[$image['name']] = $add(sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /%s /Length %d >>\nstream\n%s\nendstream",
                $image['width'], $image['height'], $image['filter'], strlen($image['data']), $image['data'],
            ));
        }

        $pageIds = [];
        foreach ($this->pages as $content) {
            $contentId = $add(sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($content), $content));
            $xObjects = '';
            if ($imageObjectIds !== []) {
                $pairs = [];
                foreach ($imageObjectIds as $name => $id) $pairs[] = '/'.$name.' '.$id.' 0 R';
                $xObjects = ' /XObject << '.implode(' ', $pairs).' >>';
            }
            $pageIds[] = $add(sprintf(
                '<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >>%s >> /Contents %d 0 R >>',
                $pagesId, self::PAGE_WIDTH, self::PAGE_HEIGHT, $fontRegularId, $fontBoldId, $xObjects, $contentId,
            ));
        }

        $objects[$catalogId - 1] = sprintf('<< /Type /Catalog /Pages %d 0 R >>', $pagesId);
        $objects[$pagesId - 1] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', array_map(static fn (int $id): string => $id.' 0 R', $pageIds)), count($pageIds));

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $id = $index + 1;
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $pdf .= sprintf("trailer\n<< /Size %d /Root %d 0 R >>\nstartxref\n%d\n%%%%EOF", count($objects) + 1, $catalogId, $xref);
        return $pdf;
    }

    private function command(string $command): void
    {
        $this->assertPage();
        $this->pages[$this->currentPage] .= $command;
    }

    private function assertPage(): void
    {
        if ($this->currentPage < 0) throw new RuntimeException('Pozovi addPage() pre crtanja.');
    }

    /** @return array{float,float,float} */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) $hex = '000000';
        return [hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255];
    }

    /** @return array<string,mixed> */
    private function fontSpec(bool $bold): array
    {
        $key = $bold ? 'bold' : 'regular';
        if (isset($this->fontSpecs[$key])) return $this->fontSpecs[$key];

        $root = dirname(__DIR__, 3);
        $name = $bold ? 'DejaVuSans-Bold' : 'DejaVuSans';
        $path = $root.'/resources/fonts/'.($bold ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf');
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('PDF Unicode font nije dostupan: '.$path);
        }
        $data = @file_get_contents($path);
        if (!is_string($data) || strlen($data) < 100000) {
            throw new RuntimeException('PDF Unicode font je neispravan ili nepotpun: '.$path);
        }

        $tables = $this->ttfTableDirectory($data);
        foreach (['head', 'hhea', 'hmtx', 'maxp', 'cmap'] as $required) {
            if (!isset($tables[$required])) {
                throw new RuntimeException('PDF TrueType font nema obaveznu '.$required.' tabelu.');
            }
        }

        $headOffset = (int) $tables['head']['offset'];
        $hheaOffset = (int) $tables['hhea']['offset'];
        $hmtxOffset = (int) $tables['hmtx']['offset'];
        $maxpOffset = (int) $tables['maxp']['offset'];
        $unitsPerEm = $this->ttfU16($data, $headOffset + 18);
        $numberOfHMetrics = $this->ttfU16($data, $hheaOffset + 34);
        $numberOfGlyphs = $this->ttfU16($data, $maxpOffset + 4);
        if ($unitsPerEm <= 0 || $numberOfHMetrics <= 0 || $numberOfGlyphs <= 0) {
            throw new RuntimeException('PDF TrueType font metrics nisu validni.');
        }

        $cmap = $this->ttfSelectCmap($data, $tables['cmap']);
        $fallbackGlyph = $this->ttfGlyphId($data, $cmap, 0x003F);
        $widths = array_fill(0, 256, 0);
        for ($code = 0; $code <= 255; $code++) {
            $codepoint = $this->cp1250Codepoint($code);
            $glyph = $codepoint === null ? 0 : $this->ttfGlyphId($data, $cmap, $codepoint);
            if ($glyph <= 0 || $glyph >= $numberOfGlyphs) $glyph = $fallbackGlyph;
            $metricIndex = min(max(0, $glyph), $numberOfHMetrics - 1);
            $advance = $this->ttfU16($data, $hmtxOffset + ($metricIndex * 4));
            $widths[$code] = max(0, (int) round($advance * 1000 / $unitsPerEm));
        }

        $scale = static fn (int $value): int => (int) round($value * 1000 / $unitsPerEm);
        $ascent = $scale($this->ttfI16($data, $hheaOffset + 4));
        $descent = $scale($this->ttfI16($data, $hheaOffset + 6));
        $bbox = [
            $scale($this->ttfI16($data, $headOffset + 36)),
            $scale($this->ttfI16($data, $headOffset + 38)),
            $scale($this->ttfI16($data, $headOffset + 40)),
            $scale($this->ttfI16($data, $headOffset + 42)),
        ];

        return $this->fontSpecs[$key] = [
            'name' => $name,
            'path' => $path,
            'data' => $data,
            'widths' => $widths,
            'default_width' => $widths[63] ?: 500,
            'bbox' => $bbox,
            'ascent' => $ascent,
            'descent' => $descent,
            'cap_height' => $ascent,
            'stem_v' => $bold ? 120 : 80,
        ];
    }

    /** @return array<string,array{offset:int,length:int}> */
    private function ttfTableDirectory(string $data): array
    {
        if (strlen($data) < 12) throw new RuntimeException('PDF TrueType font header je nepotpun.');
        $count = $this->ttfU16($data, 4);
        if ($count <= 0 || strlen($data) < 12 + ($count * 16)) {
            throw new RuntimeException('PDF TrueType font table directory je neispravan.');
        }
        $tables = [];
        for ($index = 0; $index < $count; $index++) {
            $position = 12 + ($index * 16);
            $tag = substr($data, $position, 4);
            $offset = $this->ttfU32($data, $position + 8);
            $length = $this->ttfU32($data, $position + 12);
            if ($tag === '' || $offset < 0 || $length < 0 || $offset + $length > strlen($data)) {
                throw new RuntimeException('PDF TrueType font sadrzi neispravnu tabelu.');
            }
            $tables[$tag] = ['offset' => $offset, 'length' => $length];
        }
        return $tables;
    }

    /** @param array{offset:int,length:int} $table @return array{format:int,offset:int} */
    private function ttfSelectCmap(string $data, array $table): array
    {
        $base = $table['offset'];
        $count = $this->ttfU16($data, $base + 2);
        $best = null;
        $bestScore = -1;
        for ($index = 0; $index < $count; $index++) {
            $record = $base + 4 + ($index * 8);
            $platform = $this->ttfU16($data, $record);
            $encoding = $this->ttfU16($data, $record + 2);
            $subtable = $base + $this->ttfU32($data, $record + 4);
            if ($subtable + 2 > strlen($data)) continue;
            $format = $this->ttfU16($data, $subtable);
            $score = match (true) {
                $format === 12 && $platform === 3 && $encoding === 10 => 100,
                $format === 4 && $platform === 3 && $encoding === 1 => 90,
                $format === 12 && $platform === 0 => 80,
                $format === 4 && $platform === 0 => 70,
                default => -1,
            };
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = ['format' => $format, 'offset' => $subtable];
            }
        }
        if (!is_array($best)) throw new RuntimeException('PDF TrueType font nema podrzanu Unicode cmap tabelu.');
        return $best;
    }

    /** @param array{format:int,offset:int} $cmap */
    private function ttfGlyphId(string $data, array $cmap, int $codepoint): int
    {
        $offset = $cmap['offset'];
        if ($cmap['format'] === 12) {
            $groups = $this->ttfU32($data, $offset + 12);
            for ($index = 0; $index < $groups; $index++) {
                $position = $offset + 16 + ($index * 12);
                $start = $this->ttfU32($data, $position);
                $end = $this->ttfU32($data, $position + 4);
                if ($codepoint < $start) break;
                if ($codepoint <= $end) {
                    return $this->ttfU32($data, $position + 8) + ($codepoint - $start);
                }
            }
            return 0;
        }

        $length = $this->ttfU16($data, $offset + 2);
        $segments = intdiv($this->ttfU16($data, $offset + 6), 2);
        $endCodes = $offset + 14;
        $startCodes = $endCodes + ($segments * 2) + 2;
        $deltas = $startCodes + ($segments * 2);
        $ranges = $deltas + ($segments * 2);
        for ($index = 0; $index < $segments; $index++) {
            $end = $this->ttfU16($data, $endCodes + ($index * 2));
            $start = $this->ttfU16($data, $startCodes + ($index * 2));
            if ($codepoint < $start || $codepoint > $end) continue;
            $delta = $this->ttfI16($data, $deltas + ($index * 2));
            $rangeAddress = $ranges + ($index * 2);
            $range = $this->ttfU16($data, $rangeAddress);
            if ($range === 0) return ($codepoint + $delta) & 0xFFFF;
            $glyphAddress = $rangeAddress + $range + (2 * ($codepoint - $start));
            if ($glyphAddress + 2 > $offset + $length) return 0;
            $glyph = $this->ttfU16($data, $glyphAddress);
            return $glyph === 0 ? 0 : (($glyph + $delta) & 0xFFFF);
        }
        return 0;
    }

    private function cp1250Codepoint(int $code): ?int
    {
        $utf8 = @iconv('Windows-1250', 'UTF-8//IGNORE', chr($code));
        if (!is_string($utf8) || $utf8 === '') return null;
        $ucs4 = @iconv('UTF-8', 'UCS-4BE//IGNORE', $utf8);
        if (!is_string($ucs4) || strlen($ucs4) < 4) return null;
        $value = unpack('N', substr($ucs4, 0, 4));
        return isset($value[1]) ? (int) $value[1] : null;
    }

    private function ttfU16(string $data, int $offset): int
    {
        if ($offset < 0 || $offset + 2 > strlen($data)) throw new RuntimeException('PDF TrueType uint16 izlazi van fonta.');
        $value = unpack('n', substr($data, $offset, 2));
        return (int) ($value[1] ?? 0);
    }

    private function ttfI16(string $data, int $offset): int
    {
        $value = $this->ttfU16($data, $offset);
        return $value >= 0x8000 ? $value - 0x10000 : $value;
    }

    private function ttfU32(string $data, int $offset): int
    {
        if ($offset < 0 || $offset + 4 > strlen($data)) throw new RuntimeException('PDF TrueType uint32 izlazi van fonta.');
        $value = unpack('N', substr($data, $offset, 4));
        return (int) ($value[1] ?? 0);
    }

    /** @param array<string,mixed> $font */
    private function fontDescriptorObject(array $font, int $fontFileId): string
    {
        $bbox = array_values((array) $font['bbox']);
        return sprintf(
            '<< /Type /FontDescriptor /FontName /%s /Flags 32 /FontBBox [%d %d %d %d] /ItalicAngle 0 /Ascent %d /Descent %d /CapHeight %d /StemV %d /MissingWidth %d /FontFile2 %d 0 R >>',
            (string) $font['name'],
            (int) ($bbox[0] ?? 0), (int) ($bbox[1] ?? 0), (int) ($bbox[2] ?? 0), (int) ($bbox[3] ?? 0),
            (int) $font['ascent'], (int) $font['descent'], (int) $font['cap_height'], (int) $font['stem_v'],
            (int) $font['default_width'], $fontFileId,
        );
    }

    /** @param array<string,mixed> $font */
    private function fontObject(array $font, int $encodingId, int $toUnicodeId, int $descriptorId): string
    {
        $widths = [];
        $fontWidths = (array) $font['widths'];
        $defaultWidth = (int) ($font['default_width'] ?? 500);
        for ($code = 32; $code <= 255; $code++) {
            $widths[] = (string) ((int) ($fontWidths[$code] ?? $defaultWidth));
        }
        return sprintf(
            '<< /Type /Font /Subtype /TrueType /BaseFont /%s /FirstChar 32 /LastChar 255 /Widths [%s] /FontDescriptor %d 0 R /Encoding %d 0 R /ToUnicode %d 0 R >>',
            (string) $font['name'], implode(' ', $widths), $descriptorId, $encodingId, $toUnicodeId,
        );
    }

    private function toUnicodeCMap(): string
    {
        $entries = [];
        for ($code = 32; $code <= 255; $code++) {
            $unicode = @iconv('Windows-1250', 'UTF-16BE//IGNORE', chr($code));
            if (!is_string($unicode) || $unicode === '') continue;
            $entries[] = sprintf('<%02X> <%s>', $code, strtoupper(bin2hex($unicode)));
        }
        $chunks = [];
        foreach (array_chunk($entries, 100) as $chunk) {
            $chunks[] = count($chunk)." beginbfchar\n".implode("\n", $chunk)."\nendbfchar";
        }
        return "/CIDInit /ProcSet findresource begin\n"
            ."12 dict begin\n"
            ."begincmap\n"
            ."/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n"
            ."/CMapName /Ald1nCP1250 def\n"
            ."/CMapType 2 def\n"
            ."1 begincodespacerange\n<00> <FF>\nendcodespacerange\n"
            .implode("\n", $chunks)."\n"
            ."endcmap\n"
            ."CMapName currentdict /CMap defineresource pop\n"
            ."end\nend";
    }
    private function encode(string $text): string
    {
        $encoded = @iconv('UTF-8', 'Windows-1250//TRANSLIT//IGNORE', $text);
        return is_string($encoded) ? $encoded : preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    private function escape(string $text): string
    {
        return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', '', ' '], $text);
    }
}

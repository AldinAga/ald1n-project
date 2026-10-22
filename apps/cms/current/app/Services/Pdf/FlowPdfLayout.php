<?php

declare(strict_types=1);

namespace App\Services\Pdf;

require_once __DIR__.'/SimplePdfWriter.php';

/** Measured, paginated A4 layout shared by all document/report renderers. */
final class FlowPdfLayout
{
    public const LEFT = 36.0;
    public const WIDTH = 523.0;
    public const BOTTOM = 777.0;
    private SimplePdfWriter $pdf;
    private float $top = 0;
    private bool $finished = false;
    private ?string $pendingSection = null;

    /** @param list<string> $references */
    public function __construct(
        private readonly string $title,
        private readonly string $company,
        private readonly array $references = [],
        private readonly string $logo = '',
        private readonly string $footer = 'Dokument je generisan elektronski.',
    ) {
        $this->pdf = new SimplePdfWriter();
        $this->newPage();
    }

    private function newPage(): void
    {
        if ($this->pdf->pageCount() > 0) $this->pageFooter();
        $this->pdf->addPage();
        $brand = $this->pdf->wrap($this->company ?: 'Ald1n', 208, 13, true);
        $references = [];
        foreach ($this->references as $reference) {
            foreach ($this->pdf->wrap((string) $reference, 220, 8.2, false) as $line) $references[] = $line;
        }
        $height = max(94.0, 50.0 + max(count($brand) * 17.0, count($references) * 12.0));
        if ($height > 300) throw new \LengthException('PDF header exceeds supported data length.');
        $this->pdf->rect(0, 0, SimplePdfWriter::PAGE_WIDTH, $height, '#172033');
        $this->logo();
        foreach ($brand as $i => $line) $this->pdf->text(110, 22 + $i * 17, $line, 13, true, '#ffffff');
        foreach ($references as $i => $line) $this->pdf->text(559, 23 + $i * 12, $line, 8.2, false, '#dbe5f4', 'right');
        $this->pdf->text(self::LEFT, $height - 24, $this->title, 11, true, '#ffffff');
        $this->top = $height + 22;
    }

    private function logo(): void
    {
        $info = $this->logo !== '' && is_file($this->logo) ? @getimagesize($this->logo) : false;
        if (is_array($info) && ($info[0] ?? 0) > 0 && ($info[1] ?? 0) > 0) {
            // Deliberate neutral tile. Preserve artwork/color/aspect; no recoloring.
            $this->pdf->roundedRect(36, 22, 60, 44, 7, '#ffffff');
            $scale = min(48 / $info[0], 32 / $info[1]);
            $width = $info[0] * $scale; $height = $info[1] * $scale;
            $x = 42 + (48 - $width) / 2; $y = 28 + (32 - $height) / 2;
            if ($this->pdf->imageJpeg($x, $y, $width, $height, $this->logo)
                || $this->pdf->imagePng($x, $y, $width, $height, $this->logo)) return;
        }
        $this->pdf->roundedRect(36, 22, 60, 44, 7, '#f59e0b');
        $this->pdf->text(66, 26, 'A', 26, true, '#172033', 'center');
    }

    private function pageFooter(): void
    {
        $this->pdf->line(self::LEFT, 799, 559, 799, '#dce2ea');
        // Full custom notes belong in body; fixed footer only carries stable labels.
        $this->pdf->text(self::LEFT, 811, 'Dokument generisan elektronski', 7.5, false, '#667085');
        $this->pdf->text(559, 811, 'Strana '.$this->pdf->pageCount(), 7.5, false, '#667085', 'right');
    }

    private function ensure(float $height): void
    {
        if ($this->top + $height > self::BOTTOM) $this->newPage();
    }

    public function section(string $title): void
    {
        // Defer a heading until its first content block is measured. This avoids
        // orphan headings without wasting a page for a short footer note.
        if ($this->pendingSection !== null) $this->consumeSection(0);
        $this->pendingSection = $title;
    }

    private function consumeSection(float $nextHeight): void
    {
        if ($this->pendingSection === null) return;
        $lines = $this->pdf->wrap($this->pendingSection, self::WIDTH, 11, true);
        $this->ensure(count($lines) * 15 + 8 + $nextHeight);
        foreach ($lines as $line) { $this->pdf->text(self::LEFT, $this->top, $line, 11, true, '#172033'); $this->top += 15; }
        $this->top += 8;
        $this->pendingSection = null;
    }

    public function paragraph(string $text, float $size = 9, string $color = '#475467'): void
    {
        $lineHeight = $size + 4;
        $lines = $this->pdf->wrap($text, self::WIDTH, $size);
        $this->consumeSection(min(2, count($lines)) * $lineHeight);
        foreach ($lines as $line) {
            $this->ensure($lineHeight);
            $this->pdf->text(self::LEFT, $this->top, $line, $size, false, $color);
            $this->top += $lineHeight;
        }
        $this->top += 12;
    }

    /**
     * @param list<array{title:string,fields:list<array{text:string,bold?:bool,size?:float}>}> $cards
     */
    public function cards(array $cards): void
    {
        $count = count($cards);
        if ($count < 1 || $count > 4) throw new \InvalidArgumentException('PDF card row must have 1-4 columns.');
        $width = (self::WIDTH - 12 * ($count - 1)) / $count;
        $all = [];
        foreach ($cards as $card) {
            $lines = [];
            foreach ($card['fields'] as $field) {
                $bold = $field['bold'] ?? false; $size = (float) ($field['size'] ?? 9);
                foreach ($this->pdf->wrap($field['text'], $width - 28, $size, $bold) as $line) {
                    $lines[] = ['text' => $line, 'size' => $size, 'bold' => $bold];
                }
                $lines[] = ['text' => '', 'size' => 3.0, 'bold' => false];
            }
            $all[] = $lines;
        }
        $positions = array_fill(0, $count, 0); $continuation = false;
        do {
            $remainingHeight = 0.0;
            foreach ($all as $i => $lines) {
                $height = 40.0;
                foreach (array_slice($lines, $positions[$i]) as $line) $height += $line['size'] + 4;
                $remainingHeight = max($remainingHeight, $height);
            }
            $this->consumeSection($remainingHeight < 500 ? $remainingHeight : 100);
            $this->ensure(min(100, $remainingHeight));
            // Keep ordinary card rows intact; split only exceptionally tall data.
            if ($remainingHeight < 500 && $this->top + $remainingHeight > self::BOTTOM) $this->newPage();
            $capacity = self::BOTTOM - $this->top - 40;
            $chunks = []; $height = 55.0;
            foreach ($all as $i => $lines) {
                $chunk = []; $used = 0.0;
                while ($positions[$i] < count($lines)) {
                    $line = $lines[$positions[$i]];
                    if ($used + $line['size'] + 4 > $capacity) break;
                    $chunk[] = $line; $used += $line['size'] + 4; $positions[$i]++;
                }
                $chunks[] = $chunk; $height = max($height, 40 + $used);
            }
            foreach ($cards as $i => $card) {
                $x = self::LEFT + $i * ($width + 12);
                $this->pdf->roundedRect($x, $this->top, $width, $height, 8, '#f7f9fc', '#dce2ea');
                $label = $card['title'].($continuation ? ' (nastavak)' : '');
                $labelLines = $this->pdf->wrap($label, $width - 28, 7.4, true);
                // Titles are controlled labels; reserve 24 pt for at most two lines.
                if (count($labelLines) > 2) throw new \LengthException('PDF card label too long.');
                foreach ($labelLines as $j => $line) $this->pdf->text($x + 14, $this->top + 10 + $j * 9, $line, 7.4, true, '#1f6feb');
                $y = $this->top + 32;
                foreach ($chunks[$i] as $line) {
                    $this->pdf->text($x + 14, $y, $line['text'], $line['size'], $line['bold'], $line['bold'] ? '#202735' : '#475467');
                    $y += $line['size'] + 4;
                }
            }
            $this->top += $height + 16; $more = false;
            foreach ($all as $i => $lines) if ($positions[$i] < count($lines)) $more = true;
            if ($more) { $this->newPage(); $continuation = true; }
        } while ($more);
    }

    /**
     * @param list<array{label:string,width:float,align?:string,bold?:bool}> $columns Widths sum to WIDTH.
     * @param list<list<string>> $rows
     */
    public function table(array $columns, array $rows, float $size = 8): void
    {
        if (abs(array_sum(array_column($columns, 'width')) - self::WIDTH) > 0.1) throw new \InvalidArgumentException('PDF table width mismatch.');
        $headerHeight = 28.0; $headerLines = [];
        foreach ($columns as $column) {
            $lines = $this->pdf->wrap($column['label'], $column['width'] - 16, $size, true);
            $headerLines[] = $lines; $headerHeight = max($headerHeight, 16 + count($lines) * ($size + 3));
        }
        $firstHeight = 35.0;
        if ($rows !== []) {
            foreach ($columns as $i => $column) {
                $firstHeight = max($firstHeight, 16 + count($this->pdf->wrap((string) ($rows[0][$i] ?? ''), $column['width'] - 16, $size, $column['bold'] ?? false)) * ($size + 3.5));
            }
        }
        $this->consumeSection($headerHeight + ($firstHeight < 500 ? $firstHeight : 35));
        $header = function () use ($columns, $size, $headerHeight, $headerLines): void {
            $height = $headerHeight; $wrapped = $headerLines;
            $this->ensure($height + 35);
            $this->pdf->rect(self::LEFT, $this->top, self::WIDTH, $height, '#172033');
            $x = self::LEFT;
            foreach ($columns as $i => $column) {
                foreach ($wrapped[$i] as $j => $line) $this->pdf->text($x + 8, $this->top + 8 + $j * ($size + 3), $line, $size, true, '#ffffff');
                $x += $column['width'];
            }
            $this->top += $height;
        };
        $header();
        foreach ($rows as $rowIndex => $row) {
            if (count($row) !== count($columns)) throw new \InvalidArgumentException('PDF row column mismatch.');
            $wrapped = []; $lineCount = 1;
            foreach ($columns as $i => $column) {
                $lines = $this->pdf->wrap((string) $row[$i], $column['width'] - 16, $size, $column['bold'] ?? false);
                $wrapped[] = $lines; $lineCount = max($lineCount, count($lines));
            }
            $offset = 0; $lineHeight = $size + 3.5;
            if ($lineCount * $lineHeight + 16 < 500 && $this->top + $lineCount * $lineHeight + 16 > self::BOTTOM) { $this->newPage(); $header(); }
            while ($offset < $lineCount) {
                $capacity = (int) floor((self::BOTTOM - $this->top - 16) / $lineHeight);
                if ($capacity < 1) { $this->newPage(); $header(); continue; }
                $take = min($capacity, $lineCount - $offset); $height = 16 + $take * $lineHeight;
                $this->pdf->rect(self::LEFT, $this->top, self::WIDTH, $height, $rowIndex % 2 === 0 ? '#ffffff' : '#f7f9fc', '#dce2ea', 0.5);
                $x = self::LEFT;
                foreach ($columns as $i => $column) {
                    $align = $column['align'] ?? 'left';
                    $anchor = match ($align) { 'right' => $x + $column['width'] - 8, 'center' => $x + $column['width'] / 2, default => $x + 8 };
                    foreach (array_slice($wrapped[$i], $offset, $take) as $j => $line) $this->pdf->text($anchor, $this->top + 8 + $j * $lineHeight, $line, $size, $column['bold'] ?? false, '#202735', $align);
                    $x += $column['width'];
                }
                $this->top += $height; $offset += $take;
                if ($offset < $lineCount) { $this->newPage(); $header(); }
            }
        }
        if ($rows === []) $this->paragraph('Nema stavki za izabrane filtere.');
        $this->top += 16;
    }

    public function paymentQr(string $path): bool
    {
        if ($path === '' || !is_file($path)) return false;
        $this->consumeSection(104);
        $this->ensure(104);
        if (!$this->pdf->imagePng(self::LEFT, $this->top, 84, 84, $path)) return false;
        $this->pdf->text(136, $this->top + 4, 'NBS IPS QR', 10, true, '#1f6feb');
        $this->pdf->wrappedText(136, $this->top + 25, 'Skenirajte kod u aplikaciji banke. Proverite prikazani iznos i podatke primaoca pre potvrde uplate.', 423, 8.5, 12);
        $this->top += 104;
        return true;
    }

    public function output(): string
    {
        if (!$this->finished) {
            if (trim($this->footer) !== '') { $this->section('Napomena'); $this->paragraph($this->footer, 8); }
            $this->consumeSection(0);
            $this->pageFooter(); $this->finished = true;
        }
        return $this->pdf->output();
    }
}

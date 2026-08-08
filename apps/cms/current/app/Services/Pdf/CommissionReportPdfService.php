<?php

declare(strict_types=1);

namespace App\Services\Pdf;

final class CommissionReportPdfService
{
    /** @param array<string,mixed> $report */
    public function render(array $report): string
    {
        $pdf = new SimplePdfWriter();
        $pdf->addPage();
        $top = $this->header($pdf, $report, 1);
        $top = $this->summary($pdf, $report, $top);
        $top = $this->tableHeader($pdf, $top + 18);
        $rows = (array) ($report['rows'] ?? []);

        foreach ($rows as $index => $row) {
            if ($top + 31 > 748) {
                $this->footer($pdf, $report, $pdf->pageCount());
                $pdf->addPage();
                $top = $this->header($pdf, $report, $pdf->pageCount(), true);
                $top = $this->tableHeader($pdf, $top);
            }
            $fill = $index % 2 === 0 ? '#ffffff' : '#f7f9fc';
            $pdf->rect(32, $top, 531, 31, $fill, '#dce2ea', 0.5);
            $pdf->text(42, $top + 10, (string) ($row['order_number'] ?? ''), 7.2, true, '#202735');
            $pdf->wrappedText(185, $top + 8, (string) ($row['user'] ?? ''), 125, 7.8, 10, false, '#202735', 2);
            $status = (string) ($row['status'] ?? '');
            $pdf->text(335, $top + 10, $this->statusLabel($status), 7.8, true, $this->statusColor($status));
            $pdf->text(473, $top + 10, number_format((float) ($row['amount_eur'] ?? 0), 2, ',', '.').' EUR', 8.1, true, '#202735', 'right');
            $pdf->text(551, $top + 10, (string) ($row['date'] ?? ''), 7.8, false, '#667085', 'right');
            $top += 31;
        }

        if ($rows === []) {
            $pdf->roundedRect(32, $top, 531, 50, 10, '#f5f7fa', '#dce2ea');
            $pdf->text(297.5, $top + 18, 'Nema provizija za izabrane filtere.', 10, true, '#667085', 'center');
        }

        $this->footer($pdf, $report, $pdf->pageCount());
        return $pdf->output();
    }

    /** @param array<string,mixed> $report */
    private function header(SimplePdfWriter $pdf, array $report, int $page, bool $continuation = false): float
    {
        $pdf->rect(0, 0, SimplePdfWriter::PAGE_WIDTH, 105, '#172033');
        $pdf->roundedRect(32, 24, 42, 42, 9, '#f59e0b');
        $pdf->text(53, 29, 'A', 25, true, '#ffffff', 'center');
        $pdf->text(86, 26, (string) ($report['company_name'] ?? 'Ald1n CMS'), 15, true, '#ffffff');
        $pdf->text(86, 49, $continuation ? 'Izveštaj provizija - nastavak' : 'Izveštaj provizija', 9, false, '#cbd5e1');
        $pdf->text(563, 27, 'Generisano: '.(string) ($report['generated_at'] ?? ''), 8, false, '#cbd5e1', 'right');
        $pdf->text(563, 47, 'Strana '.$page, 8, false, '#94a3b8', 'right');
        $pdf->text(32, 83, (string) ($report['filters_label'] ?? 'Bez filtera'), 8, false, '#cbd5e1');
        return 124;
    }

    /** @param array<string,mixed> $report */
    private function summary(SimplePdfWriter $pdf, array $report, float $top): float
    {
        $summary = (array) ($report['summary'] ?? []);
        $cards = [
            ['Ukupno', (string) ($summary['count'] ?? 0), '#1f6feb'],
            ['Na čekanju', number_format((float) ($summary['pending_eur'] ?? 0), 2, ',', '.').' EUR', '#f59e0b'],
            ['Odobreno', number_format((float) ($summary['approved_eur'] ?? 0), 2, ',', '.').' EUR', '#7c3aed'],
            ['Isplaćeno', number_format((float) ($summary['paid_eur'] ?? 0), 2, ',', '.').' EUR', '#149a69'],
        ];
        $x = 32;
        foreach ($cards as [$label, $value, $color]) {
            $pdf->roundedRect($x, $top, 125, 63, 9, '#f5f7fa', '#dce2ea');
            $pdf->text($x + 12, $top + 12, $label, 7.8, true, $color);
            $pdf->text($x + 12, $top + 33, $value, 11, true, '#202735');
            $x += 135;
        }
        return $top + 63;
    }

    private function tableHeader(SimplePdfWriter $pdf, float $top): float
    {
        $pdf->rect(32, $top, 531, 27, '#172033');
        $pdf->text(42, $top + 8, 'Porudžbina', 8, true, '#ffffff');
        $pdf->text(185, $top + 8, 'Korisnik', 8, true, '#ffffff');
        $pdf->text(335, $top + 8, 'Status', 8, true, '#ffffff');
        $pdf->text(473, $top + 8, 'Iznos', 8, true, '#ffffff', 'right');
        $pdf->text(551, $top + 8, 'Datum', 8, true, '#ffffff', 'right');
        return $top + 27;
    }

    /** @param array<string,mixed> $report */
    private function footer(SimplePdfWriter $pdf, array $report, int $page): void
    {
        $pdf->line(32, 800, 563, 800, '#dce2ea');
        $pdf->text(32, 812, 'Ald1n CMS · Operational Orders & Commissions', 7.5, false, '#667085');
        $pdf->text(563, 812, 'Strana '.$page, 7.5, false, '#667085', 'right');
    }


    private function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Isplaćena',
            'approved' => 'Odobrena',
            'cancelled' => 'Stornirana',
            default => 'Na čekanju',
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'paid' => '#149a69',
            'approved' => '#7c3aed',
            'cancelled' => '#c24141',
            default => '#b77900',
        };
    }
}

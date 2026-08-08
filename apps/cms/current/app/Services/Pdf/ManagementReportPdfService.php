<?php

declare(strict_types=1);

namespace App\Services\Pdf;

final class ManagementReportPdfService
{
    /** @param array<string,mixed> $report */
    public function render(array $report): string
    {
        $pdf = new SimplePdfWriter();
        $pdf->addPage();
        $top = $this->header($pdf, $report, 1);
        $top = $this->summary($pdf, (array) ($report['summary'] ?? []), $top);
        $top = $this->sectionTitle($pdf, $top + 14, 'Profitabilnost po segmentu');
        $top = $this->segmentHeader($pdf, $top);
        foreach (array_slice((array) ($report['segments'] ?? []), 0, 18) as $index => $row) {
            if ($top + 28 > 770) { $this->footer($pdf, $pdf->pageCount()); $pdf->addPage(); $top = $this->header($pdf, $report, $pdf->pageCount(), true); $top = $this->segmentHeader($pdf, $top); }
            $pdf->rect(32, $top, 531, 28, $index % 2 === 0 ? '#ffffff' : '#f7f9fc', '#dce2ea', 0.4);
            $pdf->wrappedText(40, $top + 7, (string) ($row['label'] ?? ''), 178, 7.5, 9, true, '#202735', 2);
            $pdf->text(270, $top + 9, $this->money((float) ($row['revenue_rsd'] ?? 0)), 7.5, false, '#202735', 'right');
            $pdf->text(370, $top + 9, $this->money((float) ($row['gross_profit_rsd'] ?? 0)), 7.5, true, ((float) ($row['gross_profit_rsd'] ?? 0)) >= 0 ? '#149a69' : '#c24141', 'right');
            $pdf->text(455, $top + 9, number_format((float) ($row['gross_margin_percent'] ?? 0), 1, ',', '.').'%', 7.5, true, '#202735', 'right');
            $pdf->text(550, $top + 9, number_format((float) ($row['cost_coverage_percent'] ?? 0), 1, ',', '.').'%', 7.5, false, '#667085', 'right');
            $top += 28;
        }
        $top = $this->sectionTitle($pdf, $top + 16, 'Operativni pokazatelji');
        $this->operations($pdf, $report, $top);
        $this->footer($pdf, $pdf->pageCount());
        return $pdf->output();
    }

    /** @param array<string,mixed> $report */
    private function header(SimplePdfWriter $pdf, array $report, int $page, bool $continuation = false): float
    {
        $pdf->rect(0, 0, SimplePdfWriter::PAGE_WIDTH, 105, '#172033');
        $logo = (string) ($report['company_logo_path'] ?? '');
        $drawn = $logo !== '' && ($pdf->imageJpeg(32, 22, 54, 42, $logo) || $pdf->imagePng(32, 22, 54, 42, $logo));
        if (!$drawn) { $pdf->roundedRect(32, 22, 42, 42, 9, '#f59e0b'); $pdf->text(53, 27, 'A', 25, true, '#ffffff', 'center'); }
        $x = $drawn ? 98 : 86;
        $pdf->text($x, 24, (string) ($report['company_name'] ?? 'Ald1n CMS'), 15, true, '#ffffff');
        $pdf->text($x, 48, $continuation ? 'Upravljački izveštaj – nastavak' : 'Upravljački izveštaj profitabilnosti', 9, false, '#cbd5e1');
        $pdf->text(563, 26, 'Generisano: '.(string) ($report['generated_at'] ?? ''), 8, false, '#cbd5e1', 'right');
        $pdf->text(563, 47, 'Strana '.$page, 8, false, '#94a3b8', 'right');
        $pdf->text(32, 83, (string) ($report['period_label'] ?? ''), 8.5, true, '#ffffff');
        return 122;
    }

    /** @param array<string,mixed> $s */
    private function summary(SimplePdfWriter $pdf, array $s, float $top): float
    {
        $cards = [
            ['Prihod', $this->money((float) ($s['revenue_rsd'] ?? 0)), '#1f6feb'],
            ['Bruto dobit', $this->money((float) ($s['gross_profit_rsd'] ?? 0)), '#149a69'],
            ['Neto doprinos', $this->money((float) ($s['net_contribution_rsd'] ?? 0)), '#7c3aed'],
            ['Pokrivenost troška', number_format((float) ($s['cost_coverage_percent'] ?? 0), 1, ',', '.').'%', '#f59e0b'],
        ];
        $x = 32;
        foreach ($cards as [$label, $value, $color]) {
            $pdf->roundedRect($x, $top, 125, 61, 9, '#f5f7fa', '#dce2ea');
            $pdf->text($x + 11, $top + 11, $label, 7.5, true, $color);
            $pdf->wrappedText($x + 11, $top + 30, $value, 105, 10, 12, true, '#202735', 2);
            $x += 135;
        }
        $top += 73;
        $details = [
            'Nabavna vrednost' => (float) ($s['cogs_rsd'] ?? 0), 'Provizije' => (float) ($s['commissions_rsd'] ?? 0),
            'Refundacije' => (float) ($s['refunds_rsd'] ?? 0), 'Servisni troškovi' => (float) ($s['service_cost_rsd'] ?? 0),
            'Otvorena potraživanja' => (float) ($s['outstanding_rsd'] ?? 0), 'Promet bez troška' => (float) ($s['revenue_missing_cost_rsd'] ?? 0),
        ];
        $x = 32; $i = 0;
        foreach ($details as $label => $value) {
            $pdf->roundedRect($x, $top, 170, 38, 7, '#ffffff', '#dce2ea');
            $pdf->text($x + 10, $top + 8, $label, 6.8, false, '#667085');
            $pdf->text($x + 160, $top + 22, $this->money($value), 8.1, true, '#202735', 'right');
            $x += 180; $i++;
            if ($i % 3 === 0) { $x = 32; $top += 45; }
        }
        return $top + 42;
    }

    private function sectionTitle(SimplePdfWriter $pdf, float $top, string $title): float
    {
        $pdf->text(32, $top, $title, 11, true, '#172033');
        $pdf->line(32, $top + 18, 563, $top + 18, '#dce2ea');
        return $top + 27;
    }

    private function segmentHeader(SimplePdfWriter $pdf, float $top): float
    {
        $pdf->rect(32, $top, 531, 25, '#172033');
        $pdf->text(40, $top + 7, 'Segment', 7.5, true, '#ffffff');
        $pdf->text(270, $top + 7, 'Prihod', 7.5, true, '#ffffff', 'right');
        $pdf->text(370, $top + 7, 'Bruto dobit', 7.5, true, '#ffffff', 'right');
        $pdf->text(455, $top + 7, 'Marža', 7.5, true, '#ffffff', 'right');
        $pdf->text(550, $top + 7, 'Trošak poznat', 7.5, true, '#ffffff', 'right');
        return $top + 25;
    }

    /** @param array<string,mixed> $report */
    private function operations(SimplePdfWriter $pdf, array $report, float $top): void
    {
        $inventory = (array) ($report['inventory'] ?? []);
        $receivables = (array) ($report['receivables'] ?? []);
        $after = (array) ($report['after_sales'] ?? []);
        $cards = [
            ['Vrednost lagera', $this->money((float) ($inventory['value_rsd'] ?? 0)), 'Bez nabavne cene: '.(int) ($inventory['missing_cost_items'] ?? 0)],
            ['Spori lager', (string) ((int) ($inventory['slow_items'] ?? 0)).' stavki', 'Bez prodaje 90+ dana'],
            ['Potraživanja', $this->money((float) ($receivables['outstanding_rsd'] ?? 0)), 'Otvorenih porudžbina: '.(int) ($receivables['open_orders'] ?? 0)],
            ['Reklamacije', (string) ((int) ($after['cases'] ?? 0)), 'Stopa: '.number_format((float) ($after['complaint_rate_percent'] ?? 0), 2, ',', '.').'%'],
        ];
        $x = 32;
        foreach ($cards as [$label, $value, $note]) {
            $pdf->roundedRect($x, $top, 125, 65, 9, '#f5f7fa', '#dce2ea');
            $pdf->text($x + 10, $top + 10, $label, 7.2, true, '#1f6feb');
            $pdf->wrappedText($x + 10, $top + 29, $value, 105, 9.4, 11, true, '#202735', 2);
            $pdf->text($x + 10, $top + 51, $note, 6.4, false, '#667085');
            $x += 135;
        }
    }

    private function footer(SimplePdfWriter $pdf, int $page): void
    {
        $pdf->line(32, 800, 563, 800, '#dce2ea');
        $pdf->text(32, 812, 'Ald1n CMS · Management Analytics', 7.5, false, '#667085');
        $pdf->text(563, 812, 'Strana '.$page, 7.5, false, '#667085', 'right');
    }

    private function money(float $value): string { return number_format($value, 2, ',', '.').' RSD'; }
}

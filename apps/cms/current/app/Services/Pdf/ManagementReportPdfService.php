<?php

declare(strict_types=1);

namespace App\Services\Pdf;

require_once __DIR__.'/FlowPdfLayout.php';

final class ManagementReportPdfService
{
    /** @param array<string,mixed> $report */
    public function render(array $report): string
    {
        $layout = new FlowPdfLayout('Upravljački izveštaj profitabilnosti', (string) ($report['company_name'] ?? 'Ald1n CMS'), ['Generisano: '.(string) ($report['generated_at'] ?? '')], (string) ($report['company_logo_path'] ?? ''), 'Ald1n CMS - Management Analytics');
        $layout->paragraph((string) ($report['period_label'] ?? ''), 9);
        $s = (array) ($report['summary'] ?? []);
        $this->cards($layout, [
            'PRIHOD' => $this->money((float) ($s['revenue_rsd'] ?? 0)),
            'BRUTO DOBIT' => $this->money((float) ($s['gross_profit_rsd'] ?? 0)),
            'NETO DOPRINOS' => $this->money((float) ($s['net_contribution_rsd'] ?? 0)),
            'POKRIVENOST TROŠKA' => $this->percent((float) ($s['cost_coverage_percent'] ?? 0)),
        ]);
        $this->cards($layout, [
            'NABAVNA VREDNOST' => $this->money((float) ($s['cogs_rsd'] ?? 0)),
            'PROVIZIJE' => $this->money((float) ($s['commissions_rsd'] ?? 0)),
            'REFUNDACIJE' => $this->money((float) ($s['refunds_rsd'] ?? 0)),
        ]);
        $this->cards($layout, [
            'SERVISNI TROŠKOVI' => $this->money((float) ($s['service_cost_rsd'] ?? 0)),
            'OTVORENA POTRAŽIVANJA' => $this->money((float) ($s['outstanding_rsd'] ?? 0)),
            'PROMET BEZ TROŠKA' => $this->money((float) ($s['revenue_missing_cost_rsd'] ?? 0)),
        ]);
        $layout->section('Profitabilnost po segmentu');
        $rows = [];
        // Every supplied segment is retained; pagination replaces the old 18-row cutoff.
        foreach ((array) ($report['segments'] ?? []) as $row) $rows[] = [
            (string) ($row['label'] ?? ''), $this->money((float) ($row['revenue_rsd'] ?? 0)),
            $this->money((float) ($row['gross_profit_rsd'] ?? 0)), $this->percent((float) ($row['gross_margin_percent'] ?? 0)),
            $this->percent((float) ($row['cost_coverage_percent'] ?? 0)),
        ];
        $layout->table([
            ['label' => 'Segment', 'width' => 177, 'bold' => true],
            ['label' => 'Prihod', 'width' => 101, 'align' => 'right'],
            ['label' => 'Bruto dobit', 'width' => 101, 'align' => 'right', 'bold' => true],
            ['label' => 'Marža', 'width' => 67, 'align' => 'right'],
            ['label' => 'Trošak poznat', 'width' => 77, 'align' => 'right'],
        ], $rows, 7.7);
        $layout->section('Operativni pokazatelji');
        $inventory = (array) ($report['inventory'] ?? []); $receivables = (array) ($report['receivables'] ?? []); $after = (array) ($report['after_sales'] ?? []);
        $cards = [
            ['title' => 'VREDNOST LAGERA', 'fields' => [['text' => $this->money((float) ($inventory['value_rsd'] ?? 0)), 'bold' => true], ['text' => 'Bez nabavne cene: '.(int) ($inventory['missing_cost_items'] ?? 0), 'size' => 8]]],
            ['title' => 'SPORI LAGER', 'fields' => [['text' => (string) ((int) ($inventory['slow_items'] ?? 0)).' stavki', 'bold' => true], ['text' => 'Bez prodaje 90+ dana', 'size' => 8]]],
            ['title' => 'POTRAŽIVANJA', 'fields' => [['text' => $this->money((float) ($receivables['outstanding_rsd'] ?? 0)), 'bold' => true], ['text' => 'Otvorenih porudžbina: '.(int) ($receivables['open_orders'] ?? 0), 'size' => 8]]],
            ['title' => 'REKLAMACIJE', 'fields' => [['text' => (string) ((int) ($after['cases'] ?? 0)), 'bold' => true], ['text' => 'Stopa: '.number_format((float) ($after['complaint_rate_percent'] ?? 0), 2, ',', '.').'%', 'size' => 8]]],
        ];
        $layout->cards($cards);
        return $layout->output();
    }

    /** @param array<string,string> $values */
    private function cards(FlowPdfLayout $layout, array $values): void
    {
        $cards = [];
        foreach ($values as $label => $value) $cards[] = ['title' => $label, 'fields' => [['text' => $value, 'size' => 10, 'bold' => true]]];
        $layout->cards($cards);
    }

    private function money(float $value): string { return number_format($value, 2, ',', '.').' RSD'; }
    private function percent(float $value): string { return number_format($value, 1, ',', '.').'%'; }
}

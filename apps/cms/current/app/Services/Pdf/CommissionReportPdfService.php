<?php

declare(strict_types=1);

namespace App\Services\Pdf;

require_once __DIR__.'/FlowPdfLayout.php';

final class CommissionReportPdfService
{
    /** @param array<string,mixed> $report */
    public function render(array $report): string
    {
        $layout = new FlowPdfLayout('Izveštaj provizija', (string) ($report['company_name'] ?? 'Ald1n CMS'), ['Generisano: '.(string) ($report['generated_at'] ?? '')], (string) ($report['company_logo_path'] ?? ''), 'Ald1n CMS - Operational Orders & Commissions');
        $summary = (array) ($report['summary'] ?? []);
        $cards = [];
        foreach ([
            'UKUPNO' => (string) ($summary['count'] ?? 0),
            'NA ČEKANJU' => $this->money((float) ($summary['pending_eur'] ?? 0)),
            'ODOBRENO' => $this->money((float) ($summary['approved_eur'] ?? 0)),
            'ISPLAĆENO' => $this->money((float) ($summary['paid_eur'] ?? 0)),
        ] as $label => $value) $cards[] = ['title' => $label, 'fields' => [['text' => $value, 'bold' => true, 'size' => 10]]];
        $layout->cards($cards);
        $layout->paragraph('Filteri: '.(string) ($report['filters_label'] ?? 'Bez filtera'), 8.5);
        $rows = [];
        foreach ((array) ($report['rows'] ?? []) as $row) $rows[] = [
            (string) ($row['order_number'] ?? ''), (string) ($row['user'] ?? ''),
            $this->statusLabel((string) ($row['status'] ?? '')),
            $this->money((float) ($row['amount_eur'] ?? 0)), (string) ($row['date'] ?? ''),
        ];
        $layout->table([
            ['label' => 'Porudžbina', 'width' => 136, 'bold' => true],
            ['label' => 'Korisnik', 'width' => 137], ['label' => 'Status', 'width' => 83],
            ['label' => 'Iznos', 'width' => 90, 'align' => 'right', 'bold' => true],
            ['label' => 'Datum', 'width' => 77, 'align' => 'right'],
        ], $rows, 8);
        return $layout->output();
    }

    private function statusLabel(string $status): string
    {
        return match ($status) { 'paid' => 'Isplaćena', 'approved' => 'Odobrena', 'cancelled' => 'Stornirana', default => 'Na čekanju' };
    }

    private function money(float $amount): string { return number_format($amount, 2, ',', '.').' EUR'; }
}

<?php

declare(strict_types=1);

namespace App\Services\Pdf;

require_once __DIR__.'/FlowPdfLayout.php';

/** Mesečni obračun provizija po SubAgentu i potvrda isplate po seriji; isti FlowPdfLayout kao ostali dokumenti. */
final class CommissionStatementPdfService
{
    /** @param array<string,mixed> $d */
    public function renderMonthly(array $d): string
    {
        $layout = new FlowPdfLayout(
            'Mesečni obračun provizija',
            (string) $d['company'],
            ['SubAgent: '.$d['agent'], 'Period: '.$d['period'], 'Generisano: '.$d['generated_at']],
            '',
            (string) $d['note'],
        );
        $layout->cards([
            $this->card('UKUPNO PROVIZIJA', $d['total_eur']),
            $this->card('ISPLAĆENO', $d['paid_eur']),
            $this->card('NETO ZA ISPLATU', $d['net_due_eur']),
        ]);
        $layout->paragraph('Prebijena zaduženja u ovom mesecu: '.$d['settled_eur'].'. Otvorena zaduženja na dan generisanja: '.$d['open_debt_eur'].'; od isplate se oduzima najviše iznos za isplatu, ostatak se prenosi na narednu isplatu. Neto za isplatu u RSD: '.$d['net_due_rsd'].'.', 9);
        $layout->section('Porudžbine');
        // Širine su izabrane da iznosi do 99.999,99 EUR i statusi stanu u jedan red (bez prelamanja).
        $layout->table([
            ['label' => 'Porudžbina', 'width' => 115, 'bold' => true],
            ['label' => 'Osnovica EUR', 'width' => 80, 'align' => 'right'],
            ['label' => '%', 'width' => 46, 'align' => 'right'],
            ['label' => 'Provizija EUR', 'width' => 86, 'align' => 'right', 'bold' => true],
            ['label' => 'Provizija RSD', 'width' => 112, 'align' => 'right'],
            ['label' => 'Status', 'width' => 84],
        ], $d['rows'], 8);
        $layout->totals([
            ['Bruto za isplatu (pre storna)', $d['gross_due_eur']],
            ['Korekcije za isplatu (storno i zaduženja)', $d['corrections_eur']],
            ['Neto za isplatu', $d['net_due_eur']],
        ]);
        if ($d['debits'] !== []) {
            $layout->section('Prebijena zaduženja');
            $layout->table([
                ['label' => 'Datum', 'width' => 80],
                ['label' => 'Razlog', 'width' => 343],
                ['label' => 'Iznos EUR', 'width' => 100, 'align' => 'right', 'bold' => true],
            ], $d['debits'], 8);
        }

        return $layout->output();
    }

    /** @param array<string,mixed> $d */
    public function renderPayout(array $d): string
    {
        $layout = new FlowPdfLayout(
            'Potvrda isplate provizije',
            (string) $d['company'],
            ['Isplata: '.$d['batch_number'], 'Datum isplate: '.$d['paid_at'], 'Isplatu izvršio: '.$d['paid_by']],
            '',
            (string) $d['note'],
        );
        $layout->cards([
            $this->card('BRUTO', $d['gross_eur']),
            $this->card('PREBIJENA ZADUŽENJA', $d['debit_eur']),
            $this->card('NETO EUR', $d['net_eur']),
            $this->card('NETO RSD', $d['net_rsd']),
        ]);
        $layout->paragraph('Kurs na dan isplate: '.$d['rate'].'. Način isplate: '.$d['method'].'. Referenca: '.$d['reference'].'.', 9);
        $layout->section('Primaoci');
        $layout->table([
            ['label' => 'SubAgent', 'width' => 163, 'bold' => true],
            ['label' => 'Bruto EUR', 'width' => 80, 'align' => 'right'],
            ['label' => 'Zaduženja EUR', 'width' => 80, 'align' => 'right'],
            ['label' => 'Neto EUR', 'width' => 90, 'align' => 'right', 'bold' => true],
            ['label' => 'Neto RSD', 'width' => 110, 'align' => 'right', 'bold' => true],
        ], $d['recipients'], 8.5);
        $layout->section('Provizije u isplati');
        $layout->table([
            ['label' => 'Porudžbina', 'width' => 160, 'bold' => true],
            ['label' => 'SubAgent', 'width' => 183],
            ['label' => 'Iznos EUR', 'width' => 90, 'align' => 'right', 'bold' => true],
            ['label' => 'Iznos RSD', 'width' => 90, 'align' => 'right'],
        ], $d['rows'], 8);
        if ($d['debits'] !== []) {
            $layout->section('Prebijena zaduženja');
            $layout->table([
                ['label' => 'SubAgent', 'width' => 143],
                ['label' => 'Razlog', 'width' => 290],
                ['label' => 'Iznos EUR', 'width' => 90, 'align' => 'right', 'bold' => true],
            ], $d['debits'], 8);
        }

        return $layout->output();
    }

    private function card(string $title, mixed $value): array
    {
        return ['title' => $title, 'fields' => [['text' => (string) $value, 'bold' => true, 'size' => 10]]];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Pdf;

require_once __DIR__.'/FlowPdfLayout.php';

final class BusinessDocumentPdfService
{
    /**
     * @param array<string,mixed> $document
     * @param array<string,mixed> $order
     * @param list<array<string,mixed>> $items
     */
    public function renderOrderDocument(array $document, array $order, array $items): string
    {
        $number = (string) ($document['document_number'] ?? $order['order_number'] ?? '');
        $revision = max(1, (int) ($document['revision_number'] ?? 1));
        if ($revision > 1) $number .= ' · revizija '.$revision;
        $cancelled = ($document['status'] ?? '') === 'cancelled';
        $delivery = ($document['document_type'] ?? '') === 'delivery_note';
        $layout = new FlowPdfLayout(
            $this->documentTypeLabel((string) ($document['document_type'] ?? 'order_confirmation')).($cancelled ? ' - STORNIRANO' : ''),
            (string) ($document['company_name'] ?? 'Ald1n'),
            [$number, 'Porudžbina: '.(string) ($order['order_number'] ?? '')],
            (string) ($document['company_logo_path'] ?? ''),
            (string) ($document['footer_note'] ?? 'Dokument je generisan elektronski.'),
        );
        $layout->cards([
            $this->card('DATUM IZDAVANJA', [(string) ($document['issued_at'] ?? '')]),
            $this->card($delivery ? 'DATUM ISPORUKE' : 'ROK PLAĆANJA', [$delivery
                ? (string) (($document['delivered_at_snapshot'] ?? '') ?: ($document['issued_at'] ?? ''))
                : (string) (($document['due_at'] ?? '') ?: 'Po dogovoru')]),
        ]);
        $issuer = [(string) ($document['company_name'] ?? ''), trim((string) ($document['company_address'] ?? '').' '.(string) ($document['company_city'] ?? ''))];
        foreach (['company_tax_id' => 'PIB: ', 'company_registration_number' => 'MB: ', 'company_phone' => 'Telefon: ', 'company_email' => 'E-mail: ', 'company_website' => 'Web: '] as $key => $label) {
            if (trim((string) ($document[$key] ?? '')) !== '') $issuer[] = $label.(string) $document[$key];
        }
        // Customer/supplier e-mail addresses are intentionally not part of this public document projection.
        $layout->cards([
            $this->card('IZDAVALAC / DOBAVLJAČ', $issuer),
            $this->card('NARUČILAC / KUPAC', [(string) ($document['customer_name'] ?? ''), trim((string) ($document['customer_address'] ?? '').' '.(string) ($document['customer_city'] ?? '')), (string) ($document['customer_phone'] ?? '')]),
        ]);
        $layout->section('Stavke dokumenta');
        $rows = [];
        foreach (array_values($items) as $index => $item) {
            $rows[] = [(string) ($index + 1), (string) ($item['sku'] ?? ''), (string) ($item['name'] ?? ''), (string) ($item['quantity'] ?? 0), $this->money((float) ($item['unit_price_rsd'] ?? 0)), $this->money((float) ($item['line_total_rsd'] ?? 0))];
        }
        $layout->table([
            ['label' => '#', 'width' => 28], ['label' => 'SKU', 'width' => 99],
            ['label' => 'Artikal', 'width' => 190, 'bold' => true],
            ['label' => 'Kol.', 'width' => 38, 'align' => 'center'],
            ['label' => 'Cena RSD', 'width' => 84, 'align' => 'right'],
            ['label' => 'Ukupno RSD', 'width' => 84, 'align' => 'right', 'bold' => true],
        ], $rows, 8);
        if ($delivery) {
            $this->deliverySummary($layout, $document);
        } else {
            $this->totalsAndPayment($layout, $document, $order);
        }
        return $layout->output();
    }

    /** @param array<string,mixed> $report */
    public function renderOrdersReport(array $report): string
    {
        $layout = new FlowPdfLayout('IZVEŠTAJ PORUDŽBINA', (string) ($report['company_name'] ?? 'Ald1n CMS'), ['Generisano: '.(string) ($report['generated_at'] ?? '')], (string) ($report['company_logo_path'] ?? ''), 'Izveštaj je generisan iz Laravel sistema porudžbina.');
        $summary = (array) ($report['summary'] ?? []);
        $layout->cards([
            $this->card('PORUDŽBINE', [number_format((int) ($summary['orders_count'] ?? 0), 0, ',', '.')]),
            $this->card('VREDNOST', [$this->money((float) ($summary['total_rsd'] ?? 0)).' RSD']),
            $this->card('KOMADA', [number_format((int) ($summary['units_count'] ?? 0), 0, ',', '.')]),
            $this->card('PROVIZIJE', [number_format((float) ($summary['commission_eur'] ?? 0), 2, ',', '.').' EUR']),
        ]);
        $layout->paragraph('Filteri: '.(string) ($report['filters_label'] ?? 'Svi aktivni filteri'), 8.2);
        $rows = [];
        foreach (array_values((array) ($report['orders'] ?? [])) as $index => $order) {
            $rows[] = [(string) ($index + 1), (string) ($order['order_number'] ?? ''), (string) ($order['customer'] ?? ''), (string) ($order['supplier'] ?? ''), (string) ($order['status'] ?? ''), (string) ($order['date'] ?? ''), $this->money((float) ($order['total_rsd'] ?? 0))];
        }
        $layout->table([
            ['label' => '#', 'width' => 26], ['label' => 'Broj', 'width' => 95, 'bold' => true],
            ['label' => 'Korisnik', 'width' => 108], ['label' => 'Dobavljač', 'width' => 90],
            ['label' => 'Status', 'width' => 60], ['label' => 'Datum', 'width' => 65],
            ['label' => 'Iznos RSD', 'width' => 79, 'align' => 'right', 'bold' => true],
        ], $rows, 7.5);
        return $layout->output();
    }

    /** @param list<string> $values @return array{title:string,fields:list<array{text:string,bold:bool,size:float}>} */
    private function card(string $title, array $values): array
    {
        $fields = [];
        foreach ($values as $index => $value) {
            if (trim($value) !== '') $fields[] = ['text' => $value, 'bold' => $index === 0, 'size' => $index === 0 ? 10.0 : 8.5];
        }
        return ['title' => $title, 'fields' => $fields];
    }

    /** @param array<string,mixed> $document @param array<string,mixed> $order */
    private function totalsAndPayment(FlowPdfLayout $layout, array $document, array $order): void
    {
        $payment = ['Način plaćanja: '.$this->paymentLabel((string) ($document['payment_method_snapshot'] ?? ''))];
        if (trim((string) ($document['bank_account_snapshot'] ?? '')) !== '') $payment[] = 'Račun: '.(string) $document['bank_account_snapshot'];
        $total = (float) ($document['total_rsd'] ?? 0); $paid = (float) ($document['paid_total_rsd'] ?? 0);
        $layout->cards([
            $this->card('PLAĆANJE', $payment),
            $this->card('OBRAČUN I SALDO', [
                'Ukupno: '.$this->money($total).' RSD',
                'Osnovica: '.$this->money((float) ($document['tax_base_rsd'] ?? 0)).' RSD',
                'PDV '.number_format((float) ($document['tax_rate_percent'] ?? 0), 2, ',', '.').'%: '.$this->money((float) ($document['tax_amount_rsd'] ?? 0)).' RSD',
                'Plaćeno: '.$this->money($paid).' RSD',
                'Preostalo: '.$this->money(max(0, $total - $paid)).' RSD',
                'Status: '.(string) ($document['payment_state'] ?? 'unpaid'),
            ]),
        ]);
        if (trim((string) ($document['ips_payload'] ?? '')) !== '') {
            // NBS IPS QR is rendered from the original server-generated PNG, not recomputed here.
            $layout->paymentQr((string) ($document['ips_qr_image_path'] ?? ''));
        }
        $note = trim((string) ($document['note'] ?? ''));
        $layout->section('Napomena uz dokument');
        $layout->paragraph($note !== '' ? $note : 'Dokument je automatski generisan na osnovu potvrđene porudžbine '.(string) ($order['order_number'] ?? '').'.', 8.5);
    }

    /** @param array<string,mixed> $document */
    private function deliverySummary(FlowPdfLayout $layout, array $document): void
    {
        $method = match ((string) ($document['delivery_method_snapshot'] ?? '')) {
            'own_transport' => 'Sopstveni prevoz', 'courier' => 'Kurirska služba', 'customer_pickup' => 'Lično preuzimanje', default => 'Drugo',
        };
        $details = ['Način: '.$method, 'Primalac: '.(string) ($document['delivery_recipient_snapshot'] ?? $document['customer_name'] ?? 'Kupac')];
        foreach (['delivered_at_snapshot' => 'Isporučeno: ', 'delivery_reference_snapshot' => 'Referenca: ', 'delivery_note_snapshot' => 'Napomena: '] as $key => $label) {
            if (trim((string) ($document[$key] ?? '')) !== '') $details[] = $label.(string) $document[$key];
        }
        $layout->cards([
            $this->card('PODACI O ISPORUCI', $details),
            $this->card('VREDNOST ROBE', ['Ukupno: '.$this->money((float) ($document['total_rsd'] ?? 0)).' RSD', 'Otpremnica potvrđuje predaju robe i nije fiskalni račun.']),
        ]);
        if (trim((string) ($document['note'] ?? '')) !== '') {
            $layout->section('Napomena uz dokument'); $layout->paragraph((string) $document['note'], 8.5);
        }
    }

    private function documentTypeLabel(string $type): string
    {
        return match ($type) { 'invoice' => 'RAČUN', 'proforma' => 'PREDRAČUN', 'delivery_note' => 'OTPREMNICA', default => 'POTVRDA PORUDŽBINE' };
    }

    private function paymentLabel(string $method): string
    {
        return match ($method) { 'bank_transfer' => 'Uplata na račun', 'cash_on_delivery' => 'Pouzećem', default => $method !== '' ? $method : 'Po dogovoru' };
    }

    private function money(float $amount): string { return number_format($amount, 2, ',', '.'); }
}

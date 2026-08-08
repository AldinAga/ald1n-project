<?php

declare(strict_types=1);

namespace App\Services\Pdf;

final class BusinessDocumentPdfService
{
    private const BLUE = '#1f6feb';
    private const NAVY = '#172033';
    private const ORANGE = '#f59e0b';
    private const GREEN = '#149a69';
    private const TEXT = '#202735';
    private const MUTED = '#667085';
    private const LINE = '#dce2ea';
    private const SOFT = '#f5f7fa';

    /**
     * @param array<string,mixed> $document
     * @param array<string,mixed> $order
     * @param list<array<string,mixed>> $items
     */
    public function renderOrderDocument(array $document, array $order, array $items): string
    {
        $pdf = new SimplePdfWriter();
        $page = $pdf->addPage();
        $top = $this->orderPageHeader($pdf, $document, $order, $page + 1);

        $this->partyCards($pdf, $document, $order, $top);
        $top += 130;
        $top = $this->itemsTableHeader($pdf, $top);

        foreach ($items as $index => $item) {
            $nameLines = $pdf->wrap((string) ($item['name'] ?? ''), 210, 8.5, false);
            $rowHeight = max(28, 14 + min(3, count($nameLines)) * 10);
            if ($top + $rowHeight > 730) {
                $this->pageFooter($pdf, $document, $pdf->pageCount());
                $page = $pdf->addPage();
                $top = $this->orderPageHeader($pdf, $document, $order, $page + 1, true);
                $top = $this->itemsTableHeader($pdf, $top);
            }
            $this->itemRow($pdf, $top, $rowHeight, $index + 1, $item);
            $top += $rowHeight;
        }

        if ($top + 205 > 730) {
            $this->pageFooter($pdf, $document, $pdf->pageCount());
            $page = $pdf->addPage();
            $top = $this->orderPageHeader($pdf, $document, $order, $page + 1, true);
        }

        $top += 14;
        $this->totalsAndPayment($pdf, $document, $order, $top);
        $this->pageFooter($pdf, $document, $pdf->pageCount());

        if (($document['status'] ?? '') === 'cancelled') {
            // Diskretan status u zaglavlju/footru; PDF ostaje čitljiv i arhivski upotrebljiv.
        }

        return $pdf->output();
    }

    /** @param array<string,mixed> $report */
    public function renderOrdersReport(array $report): string
    {
        $pdf = new SimplePdfWriter();
        $page = $pdf->addPage();
        $top = $this->reportHeader($pdf, $report, $page + 1);
        $top = $this->reportSummary($pdf, $report, $top);
        $top = $this->reportTableHeader($pdf, $top + 18);

        /** @var list<array<string,mixed>> $orders */
        $orders = (array) ($report['orders'] ?? []);
        foreach ($orders as $index => $order) {
            $rowHeight = 29;
            if ($top + $rowHeight > 745) {
                $this->reportFooter($pdf, $report, $pdf->pageCount());
                $page = $pdf->addPage();
                $top = $this->reportHeader($pdf, $report, $page + 1, true);
                $top = $this->reportTableHeader($pdf, $top);
            }
            $this->reportRow($pdf, $top, $rowHeight, $index + 1, $order);
            $top += $rowHeight;
        }

        if ($orders === []) {
            $pdf->rect(36, $top, 523, 48, self::SOFT, self::LINE);
            $pdf->text(297.5, $top + 17, 'Nema porudžbina za izabrane filtere.', 10, true, self::MUTED, 'center');
        }

        $this->reportFooter($pdf, $report, $pdf->pageCount());
        return $pdf->output();
    }

    /** @param array<string,mixed> $document @param array<string,mixed> $order */
    private function orderPageHeader(SimplePdfWriter $pdf, array $document, array $order, int $page, bool $continuation = false): float
    {
        $pdf->rect(0, 0, SimplePdfWriter::PAGE_WIDTH, 112, self::NAVY);
        $logoPath = (string) ($document['company_logo_path'] ?? '');
        $logoDrawn = $this->drawContainedJpeg($pdf, $logoPath, 34, 22, 78, 48);
        if (!$logoDrawn) {
            $pdf->roundedRect(34, 25, 44, 44, 10, self::ORANGE);
            $pdf->text(56, 31, 'A', 28, true, '#ffffff', 'center');
        }
        $companyX = $logoDrawn ? 124.0 : 91.0;
        $pdf->text($companyX, 28, (string) ($document['company_name'] ?? 'Ald1n'), 16, true, '#ffffff');
        $pdf->text($companyX, 50, trim(implode(' · ', array_filter([
            $document['company_phone'] ?? null,
            $document['company_email'] ?? null,
            $document['company_website'] ?? null,
        ]))), 8.5, false, '#cbd5e1');

        $type = $this->documentTypeLabel((string) ($document['document_type'] ?? 'order_confirmation'));
        $pdf->text(559, 25, $continuation ? $type.' - nastavak' : $type, 15, true, '#ffffff', 'right');
        $documentNumber = (string) ($document['document_number'] ?? $order['order_number'] ?? '');
        $revisionNumber = max(1, (int) ($document['revision_number'] ?? 1));
        if ($revisionNumber > 1) {
            $documentNumber .= ' · revizija '.$revisionNumber;
        }
        $pdf->text(559, 48, $documentNumber, 10, true, '#cbd5e1', 'right');
        $pdf->text(559, 67, 'Strana '.$page, 8, false, '#94a3b8', 'right');

        if (($document['status'] ?? '') === 'cancelled') {
            $pdf->roundedRect(455, 80, 104, 20, 10, '#7f1d1d');
            $pdf->text(507, 84, 'STORNIRANO', 8.5, true, '#ffffff', 'center');
        }

        if ($continuation) return 132;

        $pdf->text(36, 132, 'Porudžbina', 8, true, self::MUTED);
        $pdf->text(36, 149, (string) ($order['order_number'] ?? ''), 12, true, self::TEXT);
        $pdf->text(215, 132, 'Datum izdavanja', 8, true, self::MUTED);
        $pdf->text(215, 149, (string) ($document['issued_at'] ?? ''), 10, true, self::TEXT);
        $isDeliveryNote = (string) ($document['document_type'] ?? '') === 'delivery_note';
        $pdf->text(378, 132, $isDeliveryNote ? 'Datum isporuke' : 'Rok plaćanja', 8, true, self::MUTED);
        $thirdValue = $isDeliveryNote
            ? (string) (($document['delivered_at_snapshot'] ?? '') ?: ($document['issued_at'] ?? ''))
            : (string) (($document['due_at'] ?? '') ?: 'Po dogovoru');
        $pdf->text(378, 149, $thirdValue, 10, true, self::TEXT);
        $pdf->line(36, 174, 559, 174, self::LINE);
        return 192;
    }

    /** @param array<string,mixed> $document @param array<string,mixed> $order */
    private function partyCards(SimplePdfWriter $pdf, array $document, array $order, float $top): void
    {
        $pdf->roundedRect(36, $top, 254, 110, 10, self::SOFT, self::LINE);
        $pdf->roundedRect(305, $top, 254, 110, 10, '#f8fafc', self::LINE);

        $pdf->text(52, $top + 14, 'IZDAVALAC / DOBAVLJAČ', 8, true, self::BLUE);
        $pdf->text(52, $top + 32, (string) ($document['company_name'] ?? ''), 11, true, self::TEXT);
        $left = array_filter([
            trim((string) ($document['company_address'] ?? '').' '.(string) ($document['company_city'] ?? '')),
            ($document['company_tax_id'] ?? '') !== '' ? 'PIB: '.$document['company_tax_id'] : null,
            ($document['company_registration_number'] ?? '') !== '' ? 'MB: '.$document['company_registration_number'] : null,
        ]);
        $y = $top + 51;
        foreach ($left as $line) {
            $pdf->text(52, $y, (string) $line, 8.5, false, self::MUTED);
            $y += 13;
        }

        $pdf->text(321, $top + 14, 'NARUČILAC / KUPAC', 8, true, self::GREEN);
        $pdf->text(321, $top + 32, (string) ($document['customer_name'] ?? ''), 11, true, self::TEXT);
        $right = array_filter([
            trim((string) ($document['customer_address'] ?? '').' '.(string) ($document['customer_city'] ?? '')),
            $document['customer_phone'] ?? null,
        ]);
        $y = $top + 51;
        foreach ($right as $line) {
            $y = $pdf->wrappedText(321, $y, (string) $line, 220, 8.5, 12, false, self::MUTED, 2);
        }
    }

    private function itemsTableHeader(SimplePdfWriter $pdf, float $top): float
    {
        $pdf->rect(36, $top, 523, 26, self::NAVY);
        $pdf->text(48, $top + 8, '#', 8, true, '#ffffff');
        $pdf->text(72, $top + 8, 'SKU', 8, true, '#ffffff');
        $pdf->text(136, $top + 8, 'Artikal', 8, true, '#ffffff');
        $pdf->text(388, $top + 8, 'Kol.', 8, true, '#ffffff', 'center');
        $pdf->text(473, $top + 8, 'Cena', 8, true, '#ffffff', 'right');
        $pdf->text(547, $top + 8, 'Ukupno', 8, true, '#ffffff', 'right');
        return $top + 26;
    }

    /** @param array<string,mixed> $item */
    private function itemRow(SimplePdfWriter $pdf, float $top, float $height, int $index, array $item): void
    {
        $fill = $index % 2 === 0 ? '#f8fafc' : '#ffffff';
        $pdf->rect(36, $top, 523, $height, $fill, self::LINE, 0.5);
        $pdf->text(48, $top + 9, (string) $index, 8.3, false, self::MUTED);
        $pdf->wrappedText(72, $top + 8, (string) ($item['sku'] ?? ''), 58, 8.2, 10, true, self::TEXT, 2);
        $pdf->wrappedText(136, $top + 8, (string) ($item['name'] ?? ''), 225, 8.5, 10, false, self::TEXT, 3);
        $pdf->text(388, $top + 9, (string) ($item['quantity'] ?? 0), 8.5, true, self::TEXT, 'center');
        $pdf->text(473, $top + 9, $this->money((float) ($item['unit_price_rsd'] ?? 0)), 8.2, false, self::TEXT, 'right');
        $pdf->text(547, $top + 9, $this->money((float) ($item['line_total_rsd'] ?? 0)), 8.2, true, self::TEXT, 'right');
    }

    /** @param array<string,mixed> $document @param array<string,mixed> $order */
    private function totalsAndPayment(SimplePdfWriter $pdf, array $document, array $order, float $top): void
    {
        if ((string) ($document['document_type'] ?? '') === 'delivery_note') {
            $this->deliverySummary($pdf, $document, $top);
            return;
        }

        $height = 154;
        $pdf->roundedRect(36, $top, 310, $height, 10, self::SOFT, self::LINE);
        $pdf->text(52, $top + 15, 'Plaćanje i napomena', 10, true, self::TEXT);
        $payment = $this->paymentLabel((string) ($document['payment_method_snapshot'] ?? ''));
        $pdf->text(52, $top + 37, 'Način plaćanja: '.$payment, 8.5, false, self::MUTED);
        if (($document['bank_account_snapshot'] ?? '') !== '') {
            $pdf->text(52, $top + 53, 'Račun: '.(string) $document['bank_account_snapshot'], 8.5, false, self::MUTED);
        }
        $payload = trim((string) ($document['ips_payload'] ?? ''));
        $qrPath = trim((string) ($document['ips_qr_image_path'] ?? ''));
        if ($payload !== '' && $qrPath !== '' && is_file($qrPath)) {
            $pdf->text(52, $top + 68, 'NBS IPS QR', 7.8, true, self::BLUE);
            $drawn = $pdf->imagePng(52, $top + 78, 66, 66, $qrPath);
            if ($drawn) {
                $pdf->text(132, $top + 80, 'Iznos za plaćanje', 7.6, false, self::MUTED);
                $pdf->text(132, $top + 94, $this->money((float) ($document['total_rsd'] ?? 0)).' RSD', 10, true, self::NAVY);
                $pdf->wrappedText(132, $top + 113, 'Skenirajte kod u aplikaciji banke. Iznos i podaci primaoca su već popunjeni.', 190, 7.2, 9, false, self::MUTED, 4);
            }
        } else {
            $note = trim((string) ($document['note'] ?? ''));
            $pdf->wrappedText(52, $top + 73, $note !== '' ? $note : 'Dokument je automatski generisan na osnovu potvrđene porudžbine '.$order['order_number'].'.', 275, 8.2, 11, false, self::MUTED, 5);
        }

        $pdf->roundedRect(362, $top, 197, $height, 10, '#eef4ff', '#bfd3ff');
        $pdf->text(378, $top + 15, 'Obračun i saldo', 10, true, self::BLUE);
        $pdf->text(378, $top + 38, 'Osnovica', 8.3, false, self::MUTED);
        $pdf->text(543, $top + 38, $this->money((float) ($document['tax_base_rsd'] ?? 0)), 8.3, true, self::TEXT, 'right');
        $pdf->text(378, $top + 56, 'PDV '.number_format((float) ($document['tax_rate_percent'] ?? 0), 2, ',', '.').'%', 8.3, false, self::MUTED);
        $pdf->text(543, $top + 56, $this->money((float) ($document['tax_amount_rsd'] ?? 0)), 8.3, true, self::TEXT, 'right');
        $pdf->line(378, $top + 72, 543, $top + 72, '#bfd3ff');
        $total = (float) ($document['total_rsd'] ?? 0);
        $paid = (float) ($document['paid_total_rsd'] ?? 0);
        $remaining = max(0, $total - $paid);
        $pdf->text(378, $top + 84, 'UKUPNO', 9.5, true, self::BLUE);
        $pdf->text(543, $top + 82, $this->money($total).' RSD', 11, true, self::NAVY, 'right');
        $pdf->text(378, $top + 105, 'Plaćeno', 8.3, false, self::MUTED);
        $pdf->text(543, $top + 105, $this->money($paid).' RSD', 8.5, true, self::GREEN, 'right');
        $pdf->text(378, $top + 123, 'Preostalo', 8.3, false, self::MUTED);
        $pdf->text(543, $top + 123, $this->money($remaining).' RSD', 9, true, $remaining > 0 ? '#b45309' : self::GREEN, 'right');
        $pdf->text(378, $top + 140, 'Status: '.(string) ($document['payment_state'] ?? 'unpaid'), 7.5, true, self::MUTED);
    }

    /** @param array<string,mixed> $document */
    /** @param array<string,mixed> $document */
    private function deliverySummary(SimplePdfWriter $pdf, array $document, float $top): void
    {
        $height = 154;
        $pdf->roundedRect(36, $top, 310, $height, 10, self::SOFT, self::LINE);
        $pdf->text(52, $top + 15, 'Podaci o isporuci', 10, true, self::TEXT);
        $method = match ((string) ($document['delivery_method_snapshot'] ?? '')) {
            'own_transport' => 'Sopstveni prevoz',
            'courier' => 'Kurirska služba',
            'customer_pickup' => 'Lično preuzimanje',
            default => 'Drugo',
        };
        $lines = array_filter([
            'Način: '.$method,
            'Primalac: '.(string) ($document['delivery_recipient_snapshot'] ?? $document['customer_name'] ?? 'Kupac'),
            ($document['delivered_at_snapshot'] ?? '') !== '' ? 'Isporučeno: '.$document['delivered_at_snapshot'] : null,
            ($document['delivery_reference_snapshot'] ?? '') !== '' ? 'Referenca: '.$document['delivery_reference_snapshot'] : null,
            ($document['delivery_note_snapshot'] ?? '') !== '' ? 'Napomena: '.$document['delivery_note_snapshot'] : null,
        ]);
        $y = $top + 38;
        foreach ($lines as $line) {
            $y = $pdf->wrappedText(52, $y, (string) $line, 275, 8.3, 11, false, self::MUTED, 3);
        }

        $pdf->roundedRect(362, $top, 197, $height, 10, '#eef4ff', '#bfd3ff');
        $pdf->text(378, $top + 15, 'Vrednost robe', 10, true, self::BLUE);
        $pdf->text(378, $top + 48, 'Ukupno', 9, false, self::MUTED);
        $pdf->text(543, $top + 46, $this->money((float) ($document['total_rsd'] ?? 0)).' RSD', 10, true, self::TEXT, 'right');
        $pdf->line(378, $top + 67, 543, $top + 67, '#bfd3ff');
        $pdf->wrappedText(378, $top + 82, 'Otpremnica potvrđuje predaju robe i nije fiskalni račun.', 165, 8, 11, false, self::MUTED, 4);
    }

    private function pageFooter(SimplePdfWriter $pdf, array $document, int $page): void
    {
        $pdf->line(36, 790, 559, 790, self::LINE);
        $footer = trim((string) ($document['footer_note'] ?? ''));
        if ($footer === '') $footer = 'Ald1n CMS · poslovni dokument generisan elektronski';
        $pdf->text(36, 803, $footer, 7.2, false, self::MUTED);
        $pdf->text(559, 803, 'Strana '.$page, 7.2, false, self::MUTED, 'right');
    }

    /** @param array<string,mixed> $report */
    private function reportHeader(SimplePdfWriter $pdf, array $report, int $page, bool $continuation = false): float
    {
        $pdf->rect(0, 0, SimplePdfWriter::PAGE_WIDTH, 104, self::NAVY);
        $reportLogo = (string) ($report['company_logo_path'] ?? '');
        $logoDrawn = $this->drawContainedJpeg($pdf, $reportLogo, 34, 21, 72, 45);
        if (!$logoDrawn) {
            $pdf->roundedRect(34, 23, 42, 42, 10, self::ORANGE);
            $pdf->text(55, 29, 'A', 26, true, '#ffffff', 'center');
        }
        $companyX = $logoDrawn ? 116.0 : 90.0;
        $pdf->text($companyX, 25, (string) ($report['company_name'] ?? 'Ald1n CMS'), 15, true, '#ffffff');
        $pdf->text($companyX, 47, 'Reports & Export', 8.5, false, '#cbd5e1');
        $pdf->text(559, 24, $continuation ? 'IZVEŠTAJ PORUDŽBINA - nastavak' : 'IZVEŠTAJ PORUDŽBINA', 13, true, '#ffffff', 'right');
        $pdf->text(559, 47, 'Generisano: '.(string) ($report['generated_at'] ?? ''), 8.5, false, '#cbd5e1', 'right');
        $pdf->text(559, 66, 'Strana '.$page, 8, false, '#94a3b8', 'right');
        return 124;
    }

    /** @param array<string,mixed> $report */
    private function reportSummary(SimplePdfWriter $pdf, array $report, float $top): float
    {
        $cards = [
            ['label' => 'Porudžbine', 'value' => number_format((int) ($report['summary']['orders_count'] ?? 0), 0, ',', '.'), 'color' => self::BLUE],
            ['label' => 'Vrednost', 'value' => $this->money((float) ($report['summary']['total_rsd'] ?? 0)).' RSD', 'color' => self::GREEN],
            ['label' => 'Komada', 'value' => number_format((int) ($report['summary']['units_count'] ?? 0), 0, ',', '.'), 'color' => self::ORANGE],
            ['label' => 'Provizije', 'value' => number_format((float) ($report['summary']['commission_eur'] ?? 0), 2, ',', '.').' EUR', 'color' => '#8b5cf6'],
        ];
        $w = 124.25;
        foreach ($cards as $i => $card) {
            $x = 36 + $i * 133;
            $pdf->roundedRect($x, $top, $w, 66, 9, self::SOFT, self::LINE);
            $pdf->rect($x, $top, 5, 66, $card['color']);
            $pdf->text($x + 15, $top + 13, $card['label'], 8, true, self::MUTED);
            $pdf->wrappedText($x + 15, $top + 31, $card['value'], $w - 27, 10.5, 12, true, self::TEXT, 2);
        }

        $filters = trim((string) ($report['filters_label'] ?? 'Svi aktivni filteri'));
        $pdf->text(36, $top + 82, 'Filteri: '.$filters, 8.2, false, self::MUTED);
        return $top + 98;
    }

    private function reportTableHeader(SimplePdfWriter $pdf, float $top): float
    {
        $pdf->rect(36, $top, 523, 26, self::NAVY);
        $headers = [[48, '#'], [70, 'Broj'], [164, 'Korisnik'], [282, 'Dobavljač'], [379, 'Status'], [454, 'Datum'], [548, 'Iznos']];
        foreach ($headers as [$x, $label]) {
            $align = in_array($label, ['Iznos'], true) ? 'right' : 'left';
            $pdf->text((float) $x, $top + 8, (string) $label, 7.7, true, '#ffffff', $align);
        }
        return $top + 26;
    }

    /** @param array<string,mixed> $order */
    private function reportRow(SimplePdfWriter $pdf, float $top, float $height, int $index, array $order): void
    {
        $pdf->rect(36, $top, 523, $height, $index % 2 === 0 ? '#f8fafc' : '#ffffff', self::LINE, 0.5);
        $pdf->text(48, $top + 10, (string) $index, 7.5, false, self::MUTED);
        $pdf->wrappedText(70, $top + 8, (string) ($order['order_number'] ?? ''), 86, 7.7, 9, true, self::TEXT, 2);
        $pdf->wrappedText(164, $top + 8, (string) ($order['customer'] ?? ''), 108, 7.5, 9, false, self::TEXT, 2);
        $pdf->wrappedText(282, $top + 8, (string) ($order['supplier'] ?? ''), 88, 7.5, 9, false, self::TEXT, 2);
        $pdf->text(379, $top + 10, (string) ($order['status'] ?? ''), 7.5, true, self::MUTED);
        $pdf->text(454, $top + 10, (string) ($order['date'] ?? ''), 7.3, false, self::MUTED);
        $pdf->text(548, $top + 10, $this->money((float) ($order['total_rsd'] ?? 0)), 7.7, true, self::TEXT, 'right');
    }

    /** @param array<string,mixed> $report */
    private function reportFooter(SimplePdfWriter $pdf, array $report, int $page): void
    {
        $pdf->line(36, 790, 559, 790, self::LINE);
        $pdf->text(36, 803, 'Izveštaj je generisan iz Laravel produkcionog sistema porudžbina.', 7.2, false, self::MUTED);
        $pdf->text(559, 803, 'Strana '.$page, 7.2, false, self::MUTED, 'right');
    }

    private function drawContainedJpeg(SimplePdfWriter $pdf, string $path, float $x, float $top, float $boxWidth, float $boxHeight): bool
    {
        $path = trim($path);
        if ($path === '' || !is_file($path)) return false;
        $info = @getimagesize($path);
        if (!is_array($info) || ($info['mime'] ?? '') !== 'image/jpeg') return false;
        $sourceWidth = max(1, (int) ($info[0] ?? 1));
        $sourceHeight = max(1, (int) ($info[1] ?? 1));
        $scale = min($boxWidth / $sourceWidth, $boxHeight / $sourceHeight);
        $width = max(1.0, $sourceWidth * $scale);
        $height = max(1.0, $sourceHeight * $scale);
        $drawX = $x + ($boxWidth - $width) / 2;
        $drawTop = $top + ($boxHeight - $height) / 2;

        return $pdf->imageJpeg($drawX, $drawTop, $width, $height, $path);
    }

    private function documentTypeLabel(string $type): string
    {
        return match ($type) {
            'invoice' => 'RAČUN',
            'proforma' => 'PREDRAČUN',
            'delivery_note' => 'OTPREMNICA',
            default => 'POTVRDA PORUDŽBINE',
        };
    }

    private function paymentLabel(string $method): string
    {
        return match ($method) {
            'bank_transfer' => 'Uplata na račun',
            'cash_on_delivery' => 'Pouzećem',
            default => $method !== '' ? $method : 'Po dogovoru',
        };
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }
}

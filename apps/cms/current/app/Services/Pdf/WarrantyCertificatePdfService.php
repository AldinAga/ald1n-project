<?php

declare(strict_types=1);

namespace App\Services\Pdf;

final class WarrantyCertificatePdfService
{
    /** @param array<string,mixed> $data */
    public function render(array $data): string
    {
        $pdf = new SimplePdfWriter();
        $pdf->addPage();
        $pdf->rect(0, 0, SimplePdfWriter::PAGE_WIDTH, 112, '#172033');
        $logo = (string) ($data['logo_path'] ?? '');
        if ($logo === '' || !$pdf->imageJpeg(36, 24, 70, 46, $logo)) {
            $pdf->roundedRect(36, 25, 44, 44, 10, '#f59e0b');
            $pdf->text(58, 31, 'A', 28, true, '#ffffff', 'center');
        }
        $pdf->text(124, 28, (string) ($data['company_name'] ?? 'Ald1n'), 16, true, '#ffffff');
        $pdf->text(124, 51, 'Garantni list', 10, false, '#cbd5e1');
        $pdf->text(559, 26, (string) ($data['warranty_number'] ?? ''), 13, true, '#ffffff', 'right');
        $pdf->text(559, 50, 'Porudžbina: '.(string) ($data['order_number'] ?? ''), 9, false, '#cbd5e1', 'right');

        $pdf->text(36, 137, 'KUPAC', 8, true, '#149a69');
        $pdf->roundedRect(36, 155, 254, 112, 10, '#f8fafc', '#dce2ea');
        $pdf->text(52, 173, (string) ($data['customer_name'] ?? 'Kupac'), 11, true, '#202735');
        $y = 194;
        foreach (array_filter([
            trim((string) ($data['customer_address'] ?? '').' '.(string) ($data['customer_city'] ?? '')),
            $data['customer_phone'] ?? null,
        ]) as $line) {
            $y = $pdf->wrappedText(52, $y, (string) $line, 220, 8.5, 13, false, '#667085', 3);
        }

        $pdf->text(305, 137, 'ARTIKAL', 8, true, '#1f6feb');
        $pdf->roundedRect(305, 155, 254, 112, 10, '#f8fafc', '#dce2ea');
        $pdf->text(321, 173, (string) ($data['product_name'] ?? ''), 11, true, '#202735');
        $pdf->text(321, 196, 'SKU: '.((string) ($data['product_sku'] ?? '') ?: '—'), 8.5, false, '#667085');
        $pdf->text(321, 215, 'Količina: '.(string) ($data['quantity'] ?? 1), 8.5, false, '#667085');
        $serials = (array) ($data['serial_numbers'] ?? []);
        $pdf->wrappedText(321, 234, 'Serijski broj: '.($serials !== [] ? implode(', ', $serials) : 'nije evidentiran'), 220, 8.5, 11, false, '#667085', 2);

        $pdf->roundedRect(36, 292, 523, 82, 10, '#eef6ff', '#b8d8ff');
        $pdf->text(52, 310, 'Početak garancije', 8, true, '#667085');
        $pdf->text(52, 330, (string) ($data['starts_at'] ?? ''), 12, true, '#202735');
        $pdf->text(225, 310, 'Važi do', 8, true, '#667085');
        $pdf->text(225, 330, (string) ($data['expires_at'] ?? ''), 12, true, '#202735');
        $pdf->text(398, 310, 'Trajanje', 8, true, '#667085');
        $months = max(0, (int) ($data['duration_months'] ?? 0));
        $days = max(0, (int) ($data['duration_days'] ?? 0));
        $durationLabel = trim(($months > 0 ? $months.' mes.' : '').($months > 0 && $days > 0 ? ' + ' : '').($days > 0 ? $days.' dana' : ''));
        $pdf->text(398, 330, $durationLabel !== '' ? $durationLabel : '—', 12, true, '#202735');

        $pdf->text(36, 403, 'Uslovi garancije', 11, true, '#202735');
        $pdf->roundedRect(36, 424, 523, 182, 10, '#ffffff', '#dce2ea');
        $pdf->wrappedText(52, 444, (string) (($data['terms'] ?? '') ?: 'Nisu uneti posebni uslovi garancije.'), 491, 9, 14, false, '#475467', 10);

        $maintenance = trim((string) ($data['maintenance_text'] ?? ''));
        if ($maintenance !== '') {
            $pdf->roundedRect(36, 624, 523, 70, 10, '#fff8e8', '#f3d28b');
            $pdf->text(52, 642, 'Preventivno održavanje', 9, true, '#8a5a00');
            $pdf->wrappedText(52, 661, $maintenance, 491, 8.5, 12, false, '#6b4b00', 3);
        }

        $pdf->line(36, 746, 559, 746, '#dce2ea');
        $pdf->text(36, 762, (string) ($data['footer'] ?? 'Dokument je generisan elektronski.'), 7.8, false, '#667085');
        $pdf->text(559, 762, 'Datum izdavanja: '.(string) ($data['issued_at'] ?? ''), 7.8, false, '#667085', 'right');
        return $pdf->output();
    }
}

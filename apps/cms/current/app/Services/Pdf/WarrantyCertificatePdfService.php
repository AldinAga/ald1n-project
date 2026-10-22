<?php

declare(strict_types=1);

namespace App\Services\Pdf;

require_once __DIR__.'/FlowPdfLayout.php';

final class WarrantyCertificatePdfService
{
    /** @param array<string,mixed> $data */
    public function render(array $data): string
    {
        $layout = new FlowPdfLayout(
            'Garantni list',
            (string) ($data['company_name'] ?? 'Ald1n'),
            [
                (string) ($data['warranty_number'] ?? ''),
                'Porudžbina: '.(string) ($data['order_number'] ?? ''),
                'Datum izdavanja: '.(string) ($data['issued_at'] ?? ''),
            ],
            (string) ($data['logo_path'] ?? ''),
            (string) ($data['footer'] ?? ''),
        );
        $serials = array_map(static fn ($serial): string => (string) $serial, (array) ($data['serial_numbers'] ?? []));
        $layout->cards([
            ['title' => 'KUPAC', 'fields' => [
                ['text' => (string) ($data['customer_name'] ?? 'Kupac'), 'bold' => true, 'size' => 11],
                ['text' => trim((string) ($data['customer_address'] ?? '').' '.(string) ($data['customer_city'] ?? ''))],
                ['text' => (string) ($data['customer_phone'] ?? '')],
            ]],
            ['title' => 'ARTIKAL', 'fields' => [
                ['text' => (string) ($data['product_name'] ?? ''), 'bold' => true, 'size' => 10],
                ['text' => 'SKU: '.((string) ($data['product_sku'] ?? '') ?: '—'), 'size' => 8],
                ['text' => 'Količina: '.(string) ($data['quantity'] ?? 1)],
                ['text' => 'Serijski broj: '.($serials !== [] ? implode(', ', $serials) : 'nije evidentiran'), 'size' => 8.5],
            ]],
        ]);
        $months = max(0, (int) ($data['duration_months'] ?? 0));
        $days = max(0, (int) ($data['duration_days'] ?? 0));
        $duration = trim(($months > 0 ? $months.' mes.' : '').($months > 0 && $days > 0 ? ' + ' : '').($days > 0 ? $days.' dana' : ''));
        $layout->cards([
            ['title' => 'POČETAK GARANCIJE', 'fields' => [['text' => (string) (($data['starts_at'] ?? '') ?: 'Nije evidentiran'), 'bold' => true, 'size' => 11]]],
            ['title' => 'VAŽI DO', 'fields' => [['text' => (string) (($data['expires_at'] ?? '') ?: 'Nije evidentirano'), 'bold' => true, 'size' => 11]]],
            ['title' => 'TRAJANJE', 'fields' => [['text' => $duration !== '' ? $duration : '—', 'bold' => true, 'size' => 11]]],
        ]);
        $layout->section('Uslovi garancije');
        // An issued certificate retains its historical snapshot; no retroactive exclusions.
        $terms = trim((string) ($data['terms'] ?? ''));
        $layout->paragraph($terms !== '' ? $terms : 'Za ovaj garantni list nisu evidentirani posebni uslovi garancije.');
        $layout->section('Zakonska prava potrošača');
        $layout->paragraph('Potrošač ima zakonska prava u slučaju nesaobraznosti robe, koja ostvaruje prema prodavcu bez naknade. Ova garancija i njeno navedeno trajanje ne isključuju, ne ograničavaju i ne skraćuju ta prava niti pravo na podnošenje reklamacije.', 8.5);
        $contact = trim(implode("\n", array_filter([
            (string) ($data['company_name'] ?? ''),
            trim((string) ($data['company_address'] ?? '').' '.(string) ($data['company_city'] ?? '')),
            (string) ($data['company_phone'] ?? ''),
            (string) ($data['company_email'] ?? ''),
        ], static fn (string $line): bool => $line !== '')));
        if ($contact !== '') {
            $layout->section('Davalac garancije / kontakt za reklamacije');
            $layout->paragraph($contact, 8.5);
        }
        $maintenance = trim((string) ($data['maintenance_text'] ?? ''));
        if ($maintenance !== '') {
            $layout->section('Preventivno održavanje');
            $layout->paragraph($maintenance, 8.5);
        }
        return $layout->output();
    }
}

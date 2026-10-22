<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Models\ProductWarranty;
use App\Models\WarrantyIssuance;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class WarrantyCertificatePayloadService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {
    }

    /** @return array<string,mixed> */
    public function buildPending(WarrantyIssuance $issuance): array
    {
        $issuance->loadMissing('order');
        $snapshot = (array) $issuance->snapshot_json;
        $config = $this->settings->all();
        $logoPath = trim((string) ($config['documents_logo_path'] ?? ''))
            ?: trim((string) ($config['site_logo_light_path'] ?? ''));
        $logo = null;
        if ($logoPath !== '') {
            try {
                $candidate = Storage::disk('public')->path($logoPath);
                if (is_file($candidate)) $logo = $candidate;
            } catch (Throwable) { $logo = null; }
        }
        return [
            'logo_path' => $logo,
            'company_name' => (string) (($config['documents_company_name'] ?? '') ?: ($config['site_name'] ?? 'Ald1n')),
            'company_address' => (string) ($config['documents_company_address'] ?? ''),
            'company_city' => (string) ($config['documents_company_city'] ?? ''),
            'company_phone' => (string) ($config['documents_company_phone'] ?? ''),
            'company_email' => (string) ($config['documents_company_email'] ?? ''),
            'warranty_number' => $issuance->warranty_number,
            'order_number' => $issuance->order?->order_number,
            'customer_name' => $snapshot['customer_name'] ?? 'Kupac',
            'customer_address' => $snapshot['customer_address'] ?? '',
            'customer_city' => trim((string) ($snapshot['customer_postal_code'] ?? '').' '.(string) ($snapshot['customer_city'] ?? '')),
            'customer_phone' => $snapshot['customer_phone'] ?? '',
            'product_name' => $snapshot['product_name'] ?? '',
            'product_sku' => $snapshot['product_sku'] ?? '',
            'quantity' => (int) ($snapshot['quantity'] ?? 1),
            'serial_numbers' => [],
            'starts_at' => 'Po isporuci',
            'expires_at' => 'Po potvrdi isporuke',
            'duration_months' => (int) ($snapshot['duration_months'] ?? 0),
            'duration_days' => (int) ($snapshot['duration_days'] ?? 0),
            'terms' => $snapshot['terms_snapshot'] ?? '',
            'maintenance_text' => '',
            'footer' => $config['documents_footer_note'] ?? '',
            'issued_at' => $issuance->issued_at?->format('d.m.Y H:i'),
            'validity_note' => 'Garantni list je izdat po prvoj potvrđenoj uplati. Garantni rok još ne teče: datum početka i isteka utvrđuju se na osnovu stvarne isporuke. Do tada ne postoji aktivan garantni rok.',
        ];
    }

    /** @return array<string,mixed> */
    public function build(ProductWarranty $warranty): array
    {
        $warranty->loadMissing('order');

        $configuration = $this->settings->all();
        $logoPath = trim((string) ($configuration['documents_logo_path'] ?? ''))
            ?: trim((string) ($configuration['site_logo_light_path'] ?? ''));
        $localLogo = null;

        if ($logoPath !== '') {
            try {
                $candidate = Storage::disk('public')->path($logoPath);

                if (is_file($candidate)) {
                    $localLogo = $candidate;
                }
            } catch (Throwable) {
                $localLogo = null;
            }
        }

        $issuanceDate = \Illuminate\Support\Facades\Schema::hasTable('warranty_issuances')
            ? WarrantyIssuance::query()->where('order_item_id', $warranty->order_item_id)->value('issued_at')
            : null;

        $maintenanceText = $warranty->maintenance_interval_months
            ? 'Preporučeni interval: svakih '.$warranty->maintenance_interval_months
                .' meseci. Sledeći termin: '
                .($warranty->next_maintenance_at?->format('d.m.Y') ?? 'nije planiran').'.'
            : '';

        return [
            'logo_path' => $localLogo,
            'company_name' => ((string) ($configuration['documents_company_name'] ?? ''))
                ?: (string) ($configuration['site_name'] ?? 'Ald1n'),
            'company_address' => (string) ($configuration['documents_company_address'] ?? ''),
            'company_city' => (string) ($configuration['documents_company_city'] ?? ''),
            'company_phone' => (string) ($configuration['documents_company_phone'] ?? ''),
            'company_email' => (string) ($configuration['documents_company_email'] ?? ''),
            'warranty_number' => $warranty->warranty_number,
            'order_number' => $warranty->order?->order_number,
            'customer_name' => $warranty->customer_name_snapshot,
            'customer_address' => $warranty->customer_address_snapshot,
            'customer_city' => trim(
                (string) $warranty->customer_postal_code_snapshot
                .' '
                .(string) $warranty->customer_city_snapshot,
            ),
            'customer_phone' => $warranty->customer_phone_snapshot,
            'product_name' => $warranty->product_name_snapshot,
            'product_sku' => $warranty->product_sku_snapshot,
            'quantity' => $warranty->quantity,
            'serial_numbers' => $warranty->serial_numbers_json ?? [],
            'starts_at' => $warranty->starts_at?->format('d.m.Y'),
            'expires_at' => $warranty->expires_at?->format('d.m.Y'),
            'duration_months' => $warranty->duration_months,
            'duration_days' => $warranty->duration_days,
            'terms' => $warranty->terms_snapshot,
            'maintenance_text' => $maintenanceText,
            'footer' => $configuration['documents_footer_note'] ?? '',
            'issued_at' => $issuanceDate
                ? \Illuminate\Support\Carbon::parse($issuanceDate)->format('d.m.Y H:i')
                : $warranty->created_at?->format('d.m.Y H:i'),
        ];
    }
}

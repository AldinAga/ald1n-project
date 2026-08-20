<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Models\ProductWarranty;
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

        $maintenanceText = $warranty->maintenance_interval_months
            ? 'Preporučeni interval: svakih '.$warranty->maintenance_interval_months
                .' meseci. Sledeći termin: '
                .($warranty->next_maintenance_at?->format('d.m.Y') ?? 'nije planiran').'.'
            : '';

        return [
            'logo_path' => $localLogo,
            'company_name' => ((string) ($configuration['documents_company_name'] ?? ''))
                ?: (string) ($configuration['site_name'] ?? 'Ald1n'),
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
            'issued_at' => $warranty->created_at?->format('d.m.Y H:i'),
        ];
    }
}

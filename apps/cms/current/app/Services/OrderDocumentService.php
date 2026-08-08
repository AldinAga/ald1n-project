<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use App\Services\Pdf\BusinessDocumentPdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OrderDocumentService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly SettingsService $settings,
        private readonly BusinessDocumentPdfService $pdf,
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly NbsIpsQrService $nbsIpsQr,
        private readonly OrderEmailOutboxService $emails,
        private readonly ReceivablesService $receivables,
    ) {}

    public function issue(Order $order, string $type, User $actor): OrderDocument
    {
        if (!in_array($type, ['order_confirmation', 'proforma', 'invoice', 'delivery_note'], true)) {
            throw ValidationException::withMessages(['document_type' => 'Nepodržan tip dokumenta.']);
        }

        $this->assertIssueSchemaReady($type);

        $existing = OrderDocument::query()
            ->where('order_id', $order->id)
            ->where('document_type', $type)
            ->where('status', 'issued')
            ->orderByDesc('revision_number')
            ->orderByDesc('id')
            ->first();
        if ($existing instanceof OrderDocument) return $existing;

        $order->loadMissing('user');
        $preparedIps = $this->nbsIpsQr->applies($order, $type)
            ? $this->nbsIpsQr->generate($order, round((float) $order->subtotal_rsd, 2))
            : null;
        $storedIpsPath = null;

        try {
            $document = DB::transaction(function () use ($order, $type, $actor, $preparedIps, &$storedIpsPath): OrderDocument {
            /** @var Order $locked */
            $locked = Order::query()->with(['user', 'supplier', 'items', 'delivery'])->lockForUpdate()->findOrFail($order->id);
            $existingIssued = OrderDocument::query()
                ->where('order_id', $locked->id)
                ->where('document_type', $type)
                ->where('status', 'issued')
                ->orderByDesc('revision_number')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            if ($existingIssued !== null) {
                return $existingIssued;
            }

            $latestRevision = OrderDocument::query()
                ->where('order_id', $locked->id)
                ->where('document_type', $type)
                ->orderByDesc('revision_number')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            $revisionNumber = max(1, (int) ($latestRevision?->revision_number ?? 0) + 1);

            $settings = $this->settings->all();
            $this->validateCompanySettings($type, $settings);
            $issuedAt = now();
            $rate = ($settings['documents_vat_enabled'] ?? '0') === '1'
                ? max(0.0, min(100.0, (float) ($settings['documents_vat_rate'] ?? 20)))
                : 0.0;
            $total = round((float) $locked->subtotal_rsd, 2);
            $base = $rate > 0 ? round($total / (1 + $rate / 100), 2) : $total;
            $tax = round($total - $base, 2);
            $dueDays = max(0, min(365, (int) ($settings['documents_payment_due_days'] ?? 7)));
            $dueAt = in_array($type, ['order_confirmation', 'delivery_note'], true) ? null : $issuedAt->copy()->addDays($dueDays);
            if ($type === 'delivery_note' && $locked->delivery === null) {
                throw ValidationException::withMessages(['document_type' => 'Otpremnica se izdaje nakon evidentirane isporuke. Najpre kompletiraj porudžbinu.']);
            }
            // Legacy/imported orders remain read-only for inventory operations, but their
            // locally imported snapshot may still receive financial documents.
            $customerName = $type === 'delivery_note'
                ? (trim((string) $locked->delivery?->recipient_name) ?: trim((string) $locked->shipping_full_name) ?: 'Kupac')
                : (trim((string) $locked->shipping_full_name) ?: ($locked->user?->displayName() ?: 'Kupac'));
            $customerPhone = $type === 'delivery_note'
                ? (trim((string) $locked->delivery?->recipient_phone) ?: $locked->shipping_phone)
                : $locked->shipping_phone;

            $document = OrderDocument::query()->create([
                'order_id' => $locked->id,
                'document_type' => $type,
                'revision_number' => $revisionNumber,
                'supersedes_document_id' => $latestRevision?->id,
                'document_number' => $this->numbers->next($type, (int) $issuedAt->format('Y')),
                'status' => 'issued',
                'issued_by' => $actor->id,
                'issued_at' => $issuedAt,
                'due_at' => $dueAt?->toDateString(),
                'currency' => 'RSD',
                'subtotal_rsd' => $total,
                'tax_rate_percent' => $rate,
                'tax_base_rsd' => $base,
                'tax_amount_rsd' => $tax,
                'total_rsd' => $total,
                'company_name' => trim((string) ($settings['documents_company_name'] ?? '')) ?: trim((string) ($settings['site_name'] ?? 'Ald1n CMS')),
                'company_address' => trim((string) ($settings['documents_company_address'] ?? '')) ?: null,
                'company_city' => trim((string) ($settings['documents_company_city'] ?? '')) ?: null,
                'company_tax_id' => trim((string) ($settings['documents_company_tax_id'] ?? '')) ?: null,
                'company_registration_number' => trim((string) ($settings['documents_company_registration_number'] ?? '')) ?: null,
                'company_phone' => trim((string) ($settings['documents_company_phone'] ?? '')) ?: null,
                'company_email' => trim((string) ($settings['documents_company_email'] ?? '')) ?: null,
                'company_website' => trim((string) ($settings['documents_company_website'] ?? '')) ?: null,
                'company_logo_path' => trim((string) ($settings['documents_logo_path'] ?? '')) ?: trim((string) ($settings['site_logo_light_path'] ?? '')) ?: null,
                'customer_name' => $customerName,
                'customer_address' => $locked->shipping_address,
                'customer_city' => trim($locked->shipping_postal_code.' '.$locked->shipping_city),
                'customer_phone' => $customerPhone,
                'customer_email' => null,
                'supplier_name' => $locked->supplier_name_snapshot ?: $locked->supplier?->displayName(),
                'supplier_email' => $locked->supplier_email_snapshot ?: $locked->supplier?->email,
                'payment_method_snapshot' => $locked->payment_method,
                'payment_status_snapshot' => $locked->payment_status,
                'bank_account_snapshot' => $locked->bank_account_number_display_snapshot,
                'ips_payload_snapshot' => $preparedIps['payload'] ?? null,
                'ips_qr_image_path' => null,
                'ips_qr_generated_at' => null,
                'ips_qr_error' => null,
                'note' => trim((string) ($settings['documents_default_note'] ?? '')) ?: null,
                'delivery_method_snapshot' => $locked->delivery?->delivery_method,
                'delivery_recipient_snapshot' => $locked->delivery?->recipient_name,
                'delivered_at_snapshot' => $locked->delivery?->delivered_at,
                'delivery_reference_snapshot' => $locked->delivery?->reference,
                'delivery_note_snapshot' => $locked->delivery?->note,
            ]);

            if (is_array($preparedIps)) {
                $storedIpsPath = $this->nbsIpsQr->store($document, $preparedIps);
                $document->update([
                    'ips_qr_image_path' => $storedIpsPath,
                    'ips_qr_generated_at' => now(),
                    'ips_qr_error' => null,
                ]);
                $document->refresh();
            }

            if ($dueAt !== null && !in_array((string) $locked->payment_state, ['paid', 'overpaid', 'cancelled'], true)) {
                $locked->update(['payment_due_at' => $dueAt->copy()->endOfDay()]);
            }

            $this->audit->log(
                'order.document_issued',
                'Izdat dokument '.$document->document_number.' za '.$locked->order_number,
                $document,
                after: [
                    'document_type' => $type,
                    'status' => 'issued',
                    'revision_number' => $revisionNumber,
                    'supersedes_document_id' => $latestRevision?->id,
                    'total_rsd' => $total,
                ],
                user: $actor,
            );

            return $document;
            }, 5);
        } catch (Throwable $exception) {
            $this->nbsIpsQr->delete($storedIpsPath);
            throw $exception;
        }

        $document->loadMissing('order.user');
        if ($document->wasRecentlyCreated && $document->order?->user instanceof User) {
            try {
                $labels = ['order_confirmation' => 'Potvrda porudžbine', 'proforma' => 'Predračun', 'invoice' => 'Račun', 'delivery_note' => 'Otpremnica'];
                $this->notifications->order(
                    $document->order->user,
                    'order.document_issued',
                    ($labels[$document->document_type] ?? 'Dokument').' je izdat',
                    sprintf('%s %s je spreman za porudžbinu %s.', $labels[$document->document_type] ?? 'Dokument', $document->document_number, $document->order->order_number),
                    $document->order,
                    [
                        'url' => route('orders.documents.show', ['order' => $document->order_id, 'document' => $document->id]),
                        'action_label' => 'Otvori PDF dokument',
                        'icon' => 'file-text',
                        'severity' => 'success',
                    ],
                );
            } catch (Throwable $exception) {
                // Dokument je već uspešno i transakcijski izdat. Kvar opcionog
                // notification kanala ne sme korisniku vratiti HTTP 500.
                Log::warning('Document notification failed after successful issuance.', [
                    'document_id' => $document->id,
                    'document_number' => $document->document_number,
                    'exception' => $exception,
                ]);
            }
        }
        if ($document->wasRecentlyCreated) {
            try {
                $this->emails->documentIssued($document);
            } catch (Throwable $exception) {
                Log::warning('Document email enqueue failed after successful issuance.', ['document_id' => $document->id, 'exception' => $exception]);
            }
            if (in_array($document->document_type, ['proforma', 'invoice'], true) && $document->order instanceof Order) {
                try {
                    $this->receivables->ensureForOrder($document->order, $actor);
                } catch (Throwable $exception) {
                    Log::warning('Receivable case synchronization failed after document issuance.', ['document_id' => $document->id, 'exception' => $exception]);
                }
            }
        }

        return $document;
    }

    public function cancel(OrderDocument $document, User $actor, string $reason): OrderDocument
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['cancellation_reason' => 'Unesite razlog storniranja dokumenta.']);
        }

        $statusChanged = false;
        $cancelled = DB::transaction(function () use ($document, $actor, $reason, &$statusChanged): OrderDocument {
            /** @var OrderDocument $locked */
            $locked = OrderDocument::query()->lockForUpdate()->findOrFail($document->id);
            if ($locked->status === 'cancelled') return $locked;
            if ($locked->status !== 'issued') {
                throw ValidationException::withMessages(['document' => 'Samo aktivan dokument može biti storniran.']);
            }
            $locked->update([
                'status' => 'cancelled',
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
            $statusChanged = true;
            $this->audit->log(
                'order.document_cancelled',
                'Storniran dokument '.$locked->document_number,
                $locked,
                before: ['status' => 'issued'],
                after: ['status' => 'cancelled', 'cancellation_reason' => $reason],
                user: $actor,
            );
            return $locked;
        }, 5);

        $cancelled->loadMissing('order.user');
        if ($statusChanged && $cancelled->order?->user instanceof User) {
            try {
                $this->notifications->order(
                    $cancelled->order->user,
                    'order.document_cancelled',
                    'Dokument je storniran',
                    'Dokument '.$cancelled->document_number.' za porudžbinu '.$cancelled->order->order_number.' je storniran. Razlog: '.$reason,
                    $cancelled->order,
                    ['severity' => 'danger', 'icon' => 'file-text'],
                );
            } catch (Throwable $exception) {
                Log::warning('Document cancellation notification failed.', [
                    'document_id' => $cancelled->id,
                    'document_number' => $cancelled->document_number,
                    'exception' => $exception,
                ]);
            }
        }
        if ($statusChanged) {
            try {
                $this->emails->documentCancelled($cancelled, $reason);
            } catch (Throwable $exception) {
                Log::warning('Document cancellation email enqueue failed.', ['document_id' => $cancelled->id, 'exception' => $exception]);
            }
        }

        return $cancelled;
    }

    public function render(OrderDocument $document): string
    {
        $document->loadMissing(['supersedes', 'order.user', 'order.supplier', 'order.items', 'order.payments', 'order.delivery']);
        $document = $this->nbsIpsQr->ensureForDocument($document);
        $document->loadMissing(['supersedes', 'order.user', 'order.supplier', 'order.items', 'order.payments', 'order.delivery']);
        $order = $document->order;
        $ipsQrPath = $this->nbsIpsQr->localPath($document);
        $settings = $this->settings->all();
        $logoSetting = trim((string) ($settings['documents_logo_path'] ?? ''))
            ?: trim((string) ($settings['site_logo_light_path'] ?? ''));
        $logoPath = $this->localLogoPath(trim((string) ($document->company_logo_path ?? '')));
        if ($logoPath === null) {
            $logoPath = $this->localLogoPath($logoSetting);
        }

        return $this->pdf->renderOrderDocument([
            'document_type' => $document->document_type,
            'document_number' => $document->document_number,
            'revision_number' => (int) ($document->revision_number ?? 1),
            'supersedes_document_number' => $document->supersedes?->document_number,
            'cancellation_reason' => $document->cancellation_reason,
            'status' => $document->status,
            'issued_at' => $document->issued_at?->format('d.m.Y H:i'),
            'due_at' => $document->due_at?->format('d.m.Y'),
            'subtotal_rsd' => (float) $document->subtotal_rsd,
            'tax_rate_percent' => (float) $document->tax_rate_percent,
            'tax_base_rsd' => (float) $document->tax_base_rsd,
            'tax_amount_rsd' => (float) $document->tax_amount_rsd,
            'total_rsd' => (float) $document->total_rsd,
            'company_name' => $document->company_name,
            'company_address' => $document->company_address,
            'company_city' => $document->company_city,
            'company_tax_id' => $document->company_tax_id,
            'company_registration_number' => $document->company_registration_number,
            'company_phone' => $document->company_phone,
            'company_email' => $document->company_email,
            'company_website' => $document->company_website,
            'company_logo_path' => $logoPath,
            'customer_name' => $document->customer_name,
            'customer_address' => $document->customer_address,
            'customer_city' => $document->customer_city,
            'customer_phone' => $document->customer_phone,
            'customer_email' => null,
            'supplier_name' => $document->supplier_name,
            'supplier_email' => $document->supplier_email,
            'payment_method_snapshot' => $document->payment_method_snapshot,
            'payment_status_snapshot' => $document->payment_status_snapshot,
            'bank_account_snapshot' => $document->bank_account_snapshot,
            'note' => $document->note,
            'footer_note' => $settings['documents_footer_note'] ?? '',
            'ips_payload' => $document->ips_payload_snapshot,
            'ips_qr_image_path' => $ipsQrPath,
            'paid_total_rsd' => (float) ($order?->paid_total_rsd ?? 0),
            'payment_state' => (string) ($order?->payment_state ?? 'unpaid'),
            'delivery_method_snapshot' => $document->delivery_method_snapshot,
            'delivery_recipient_snapshot' => $document->delivery_recipient_snapshot,
            'delivered_at_snapshot' => $document->delivered_at_snapshot?->format('d.m.Y H:i'),
            'delivery_reference_snapshot' => $document->delivery_reference_snapshot,
            'delivery_note_snapshot' => $document->delivery_note_snapshot,
        ], [
            'order_number' => $order?->order_number,
            'supplier_name' => $document->supplier_name,
        ], $order?->items->map(static fn ($item): array => [
            'sku' => $item->variant_sku_snapshot ?: $item->product_sku,
            'name' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
            'quantity' => (int) $item->quantity,
            'unit_price_rsd' => (float) $item->unit_price_rsd,
            'line_total_rsd' => (float) $item->line_total_rsd,
        ])->values()->all() ?? []);
    }


    private function assertIssueSchemaReady(string $type): void
    {
        $requiredTables = ['order_documents', 'document_counters'];
        if ($type === 'delivery_note') {
            $requiredTables[] = 'order_deliveries';
        }

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                throw ValidationException::withMessages([
                    'document_type' => 'Baza nije potpuno nadograđena za izdavanje dokumenata. Pokreni: php artisan migrate --force',
                ]);
            }
        }

        $requiredColumns = ['document_type', 'revision_number', 'supersedes_document_id', 'cancellation_reason', 'document_number', 'order_id'];
        if (in_array($type, ['proforma', 'invoice'], true)) {
            $requiredColumns = array_merge($requiredColumns, ['ips_payload_snapshot', 'ips_qr_image_path', 'ips_qr_generated_at', 'ips_qr_error']);
        }
        if ($type === 'delivery_note') {
            $requiredColumns = array_merge($requiredColumns, [
                'delivery_method_snapshot',
                'delivery_recipient_snapshot',
                'delivered_at_snapshot',
                'delivery_reference_snapshot',
                'delivery_note_snapshot',
            ]);
        }

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('order_documents', $column)) {
                throw ValidationException::withMessages([
                    'document_type' => 'Nedostaje kolona '.$column.' za životni ciklus dokumenata. Pokreni: php artisan migrate --force',
                ]);
            }
        }

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            try {
                $rows = DB::select('SHOW INDEX FROM `order_documents`');
                $uniqueColumns = [];
                foreach ($rows as $row) {
                    if ((int) ($row->Non_unique ?? 1) !== 0 || (string) ($row->Key_name ?? '') === 'PRIMARY') {
                        continue;
                    }
                    $uniqueColumns[(string) $row->Key_name][(int) $row->Seq_in_index] = (string) $row->Column_name;
                }
                foreach ($uniqueColumns as $columns) {
                    ksort($columns);
                    if (array_values($columns) === ['order_id', 'document_type']) {
                        throw ValidationException::withMessages([
                            'document_type' => 'Baza još ne podržava ponovno izdavanje nakon storniranja. Pokreni: php artisan migrate --force',
                        ]);
                    }
                }
            } catch (ValidationException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                Log::warning('Document revision index inspection failed.', ['exception' => $exception]);
            }
        }

        if ($type !== 'delivery_note' || !in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        try {
            $column = DB::selectOne("SHOW COLUMNS FROM `order_documents` WHERE `Field` = 'document_type'");
            $definition = strtolower((string) ($column->Type ?? ''));
            if (str_starts_with($definition, 'enum(') && !str_contains($definition, "'delivery_note'")) {
                throw ValidationException::withMessages([
                    'document_type' => 'Kolona za tip dokumenta još ne podržava otpremnicu. Pokreni novu migraciju: php artisan migrate --force',
                ]);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Document schema type inspection failed.', ['exception' => $exception]);
        }
    }

    /** @param array<string,string> $settings */
    private function validateCompanySettings(string $type, array $settings): void
    {
        // Potvrda porudžbine mora raditi odmah nakon instalacije. Za naziv firme
        // već postoji bezbedan fallback na site_name / naziv aplikacije, pa ga ne
        // treba blokirati obaveznim dokument podešavanjem.
        $effectiveCompanyName = trim((string) ($settings['documents_company_name'] ?? ''))
            ?: trim((string) ($settings['site_name'] ?? ''))
            ?: trim((string) config('app.name', 'Ald1n CMS'));

        $missing = [];
        if ($effectiveCompanyName === '') {
            $missing[] = 'Naziv firme';
        }

        if (in_array($type, ['proforma', 'invoice', 'delivery_note'], true)) {
            foreach ([
                'documents_company_address' => 'Adresa firme',
                'documents_company_city' => 'Grad firme',
            ] as $key => $label) {
                if (trim((string) ($settings[$key] ?? '')) === '') $missing[] = $label;
            }
        }

        if ($type === 'invoice') {
            foreach ([
                'documents_company_tax_id' => 'PIB',
                'documents_company_registration_number' => 'Matični broj',
            ] as $key => $label) {
                if (trim((string) ($settings[$key] ?? '')) === '') $missing[] = $label;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'document_type' => 'Pre izdavanja dokumenta podesi: '.implode(', ', $missing).'.',
            ]);
        }
    }

    private function localLogoPath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') return null;
        try {
            $full = Storage::disk('public')->path($path);
            return is_file($full) ? $full : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

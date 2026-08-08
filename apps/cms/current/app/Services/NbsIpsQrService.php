<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class NbsIpsQrService
{
    public function __construct(private readonly IpsPaymentPayloadService $payloads) {}

    public function applies(Order $order, string $documentType): bool
    {
        return $order->payment_method === 'bank_transfer'
            && in_array($documentType, ['proforma', 'invoice'], true);
    }

    /** @return array{payload:string,png:string} */
    public function generate(Order $order, float $amountRsd): array
    {
        $payload = $this->payloads->payload($order, $amountRsd);
        if ($payload === null) {
            throw ValidationException::withMessages(['ips_qr' => 'NBS IPS QR je dostupan samo za porudžbine sa uplatom na račun.']);
        }

        $endpoint = (string) config('services.ips_qr.generate_url', 'https://nbs.rs/QRcode/api/qr/v1/generate/320?lang=sr_RS_Latn');
        $timeout = max(5, min(60, (int) config('services.ips_qr.timeout', 20)));

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout($timeout)
                ->retry(2, 300)
                ->withBody($payload, 'text/plain; charset=UTF-8')
                ->post($endpoint);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'ips_qr' => 'NBS servis za generisanje IPS QR koda trenutno nije dostupan. Dokument nije izdat da ne bi ostao bez obaveznog QR koda.',
            ]);
        }

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'ips_qr' => 'NBS servis je vratio HTTP '.$response->status().'. Dokument nije izdat.',
            ]);
        }

        $data = $response->json();
        $code = is_array($data) ? (int) data_get($data, 's.code', -1) : -1;
        $description = is_array($data) ? trim((string) data_get($data, 's.desc', '')) : '';
        $errors = is_array($data) ? (array) data_get($data, 'e', []) : [];
        if ($code !== 0) {
            $detail = trim(implode(' ', array_map('strval', $errors)));
            throw ValidationException::withMessages([
                'ips_qr' => 'NBS IPS QR nije validan: '.($detail !== '' ? $detail : ($description !== '' ? $description : 'nepoznata greška')).'.',
            ]);
        }

        $encoded = is_array($data) ? (string) ($data['i'] ?? '') : '';
        $png = base64_decode($encoded, true);
        if (!is_string($png) || !str_starts_with($png, "\x89PNG\r\n\x1a\n")) {
            throw ValidationException::withMessages(['ips_qr' => 'NBS servis nije vratio ispravnu PNG sliku QR koda.']);
        }

        return ['payload' => $payload, 'png' => $png];
    }

    /** @param array{payload:string,png:string} $generated */
    public function store(OrderDocument $document, array $generated): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $document->document_number) ?: 'document-'.$document->id;
        $path = 'documents/ips-qr/order-'.$document->order_id.'/'.$safeNumber.'.png';
        if (!Storage::disk('local')->put($path, $generated['png'])) {
            throw ValidationException::withMessages(['ips_qr' => 'NBS IPS QR je generisan, ali privatni storage nije upisiv. Dokument nije izdat.']);
        }
        return $path;
    }

    public function ensureForDocument(OrderDocument $document): OrderDocument
    {
        $document->loadMissing('order.user');
        $order = $document->order;
        if (!$order instanceof Order || !$this->applies($order, (string) $document->document_type)) return $document;
        // Stornirani istorijski dokument bez QR snapshot-a mora ostati pregledljiv i kada NBS servis nije dostupan.
        if ((string) $document->status !== 'issued') return $document;
        if ($this->storedPath($document) !== null) return $document;

        $this->assertSchemaReady();
        $generated = $this->generate($order, (float) $document->total_rsd);
        $storedPath = null;

        try {
            return DB::transaction(function () use ($document, $generated, &$storedPath): OrderDocument {
                /** @var OrderDocument $locked */
                $locked = OrderDocument::query()->lockForUpdate()->findOrFail($document->id);
                if ($this->storedPath($locked) !== null) return $locked;
                $storedPath = $this->store($locked, $generated);
                $locked->update([
                    'ips_payload_snapshot' => $generated['payload'],
                    'ips_qr_image_path' => $storedPath,
                    'ips_qr_generated_at' => now(),
                    'ips_qr_error' => null,
                ]);
                return $locked->refresh();
            }, 5);
        } catch (Throwable $exception) {
            if ($storedPath !== null) Storage::disk('local')->delete($storedPath);
            try {
                if (Schema::hasColumn('order_documents', 'ips_qr_error')) {
                    OrderDocument::query()->whereKey($document->id)->update([
                        'ips_qr_error' => $this->truncate($exception->getMessage(), 2000),
                        'updated_at' => now(),
                    ]);
                }
            } catch (Throwable $logException) {
                Log::warning('NBS IPS QR greška nije mogla biti sačuvana.', ['document_id' => $document->id, 'exception' => $logException]);
            }
            throw $exception;
        }
    }

    public function localPath(OrderDocument $document): ?string
    {
        $path = $this->storedPath($document);
        return $path !== null ? Storage::disk('local')->path($path) : null;
    }

    public function delete(?string $path): void
    {
        if (is_string($path) && $path !== '') Storage::disk('local')->delete($path);
    }

    public function assertSchemaReady(): void
    {
        foreach (['ips_payload_snapshot', 'ips_qr_image_path', 'ips_qr_generated_at', 'ips_qr_error'] as $column) {
            if (!Schema::hasColumn('order_documents', $column)) {
                throw ValidationException::withMessages(['ips_qr' => 'Nedostaje kolona order_documents.'.$column.'. Pokreni: php artisan migrate --force']);
            }
        }
    }

    private function storedPath(OrderDocument $document): ?string
    {
        $path = trim((string) $document->ips_qr_image_path);
        if ($path === '' || !Storage::disk('local')->exists($path)) return null;
        return $path;
    }

    private function truncate(string $value, int $length): string
    {
        return function_exists('mb_substr') ? (string) mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
    }
}

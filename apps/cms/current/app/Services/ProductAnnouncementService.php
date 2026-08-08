<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderEmailOutbox;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class ProductAnnouncementService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Priprema po jednu deduplikovanu outbox poruku za svakog aktivnog
     * registrovanog korisnika sa ispravnom e-mail adresom.
     *
     * Poziva se samo pri prvom objavljivanju artikla. Greška u pripremi
     * obaveštenja nikada ne sme poništiti već sačuvan artikal.
     */
    public function queueForNewlyPublished(Product $product, ?User $actor = null): int
    {
        if ($this->settings->get('product_email_new_items_enabled', '0') !== '1') {
            return 0;
        }
        if ($product->status !== 'active' || $product->deleted_at !== null) {
            return 0;
        }
        if (!Schema::hasTable('order_email_outbox') || !Schema::hasTable('users')) {
            return 0;
        }

        $product->loadMissing('primaryImage');
        $interval = max(0, min(1440, (int) $this->settings->get('product_email_new_items_interval_minutes', '0')));
        $scheduledFor = now()->addMinutes($interval);
        $subject = $this->truncate('Novi artikal: '.trim((string) $product->name), 255);
        $description = $this->plainDescription($product);
        $priceFormatted = $this->formattedPrice($product);
        $imageUrl = $this->absoluteImageUrl($product);
        $message = $this->message($product, $description, $priceFormatted);
        $actionUrl = route('catalog.show', ['slug' => $product->slug]);
        $metadata = [
            'product_id' => (int) $product->id,
            'product_sku' => (string) $product->sku,
            'product_slug' => (string) $product->slug,
            'product_name' => (string) $product->name,
            'product_description' => $description,
            'product_price' => $product->price_amount !== null ? (string) $product->price_amount : null,
            'product_price_currency' => (string) $product->price_currency,
            'product_price_formatted' => $priceFormatted,
            'product_image_url' => $imageUrl,
            'actor_user_id' => $actor?->id,
            'source' => 'automatic_new_product_announcement',
        ];
        $queued = 0;

        try {
            User::query()
                ->where('status', 'active')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->orderBy('id')
                ->chunkById(250, function ($users) use ($product, $subject, $message, $actionUrl, $scheduledFor, $metadata, &$queued): void {
                    foreach ($users as $recipient) {
                        $email = strtolower(trim((string) $recipient->email));
                        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                            continue;
                        }

                        $row = OrderEmailOutbox::query()->firstOrCreate(
                            [
                                'dedupe_key' => hash('sha256', 'product_published|'.$product->id.'|'.$recipient->id),
                            ],
                            [
                                'order_id' => null,
                                'recipient_user_id' => (int) $recipient->id,
                                'document_id' => null,
                                'recipient_email' => $email,
                                'recipient_name' => $recipient->displayName(),
                                'event_type' => 'product_published',
                                'batch_key' => 'product-published:'.$product->id,
                                'subject' => $subject,
                                'message' => $message,
                                'action_url' => $actionUrl,
                                'attach_document' => false,
                                'attach_active_invoice' => false,
                                'status' => 'pending',
                                'attempt_count' => 0,
                                'scheduled_for' => $scheduledFor,
                                'metadata_json' => $metadata,
                            ],
                        );

                        if ($row->wasRecentlyCreated) {
                            $queued++;
                        }
                    }
                });

            if ($queued > 0) {
                $this->audit->log(
                    'product.announcement.queued',
                    'Pripremljeno obaveštenje o novom artiklu '.$product->sku.' za '.$queued.' korisnika.',
                    $product,
                    metadata: [
                        'recipient_count' => $queued,
                        'interval_minutes' => $interval,
                        'actor_user_id' => $actor?->id,
                        'includes_primary_image' => $imageUrl !== null,
                    ],
                    user: $actor,
                );
            }
        } catch (Throwable $exception) {
            Log::error('Priprema e-mail obaveštenja o novom artiklu nije uspela.', [
                'product_id' => $product->id,
                'actor_user_id' => $actor?->id,
                'queued_before_failure' => $queued,
                'exception' => $exception,
            ]);
        }

        return $queued;
    }

    private function message(Product $product, string $description, ?string $priceFormatted): string
    {
        $parts = ['U katalog je dodat novi artikal „'.trim((string) $product->name).'“.'];
        $sku = trim((string) $product->sku);
        if ($sku !== '') $parts[] = 'Šifra artikla: '.$sku.'.';
        if ($description !== '') $parts[] = 'Opis: '.$this->truncate($description, 500).'.';
        if ($priceFormatted !== null) $parts[] = 'Cena: '.$priceFormatted.'.';
        $parts[] = 'Detalje i fotografije možete pogledati u katalogu.';

        return implode(' ', $parts);
    }

    private function plainDescription(Product $product): string
    {
        $description = html_entity_decode(strip_tags((string) $product->description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = preg_replace('/\s+/u', ' ', trim($description)) ?? trim($description);
        return $this->truncate($description, 1200);
    }

    private function formattedPrice(Product $product): ?string
    {
        if (!is_numeric($product->price_amount) || (float) $product->price_amount <= 0) return null;
        return number_format((float) $product->price_amount, 2, ',', '.').' '.trim((string) $product->price_currency);
    }

    private function absoluteImageUrl(Product $product): ?string
    {
        $imageUrl = trim((string) ($product->primaryImage?->url ?? ''));
        if ($imageUrl === '') return null;
        return Str::startsWith($imageUrl, ['http://', 'https://']) ? $imageUrl : url($imageUrl);
    }

    private function truncate(string $value, int $length): string
    {
        return function_exists('mb_substr')
            ? (string) mb_substr($value, 0, $length, 'UTF-8')
            : substr($value, 0, $length);
    }
}

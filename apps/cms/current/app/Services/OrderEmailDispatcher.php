<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderDocument;
use App\Models\OrderEmailOutbox;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class OrderEmailDispatcher
{
    public function __construct(private readonly OrderDocumentService $documents) {}

    /** @return array{sent:int,failed:int,groups:int} */
    public function dispatch(int $limit = 200): array
    {
        if (!Schema::hasTable('order_email_outbox')) return ['sent' => 0, 'failed' => 0, 'groups' => 0];
        OrderEmailOutbox::query()
            ->where('status', 'sending')
            ->where('last_attempt_at', '<=', now()->subMinutes(15))
            ->update(['status' => 'retry', 'scheduled_for' => now(), 'updated_at' => now()]);

        $rows = OrderEmailOutbox::query()
            ->whereIn('status', ['pending', 'retry'])
            ->where(static fn ($query) => $query->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now()))
            ->orderBy('scheduled_for')->orderBy('id')->limit(max(1, min(1000, $limit)))->get();

        $sent = 0;
        $failed = 0;
        $groups = 0;
        foreach ($rows->groupBy(static fn (OrderEmailOutbox $row): string => strtolower($row->recipient_email).'|'.$row->batch_key) as $batch) {
            $groups++;
            /** @var Collection<int,OrderEmailOutbox> $claimed */
            $claimed = DB::transaction(function () use ($batch): Collection {
                $ids = $batch->pluck('id')->all();
                $locked = OrderEmailOutbox::query()->whereIn('id', $ids)->whereIn('status', ['pending', 'retry'])->lockForUpdate()->get();
                if ($locked->isEmpty()) return collect();
                OrderEmailOutbox::query()->whereIn('id', $locked->pluck('id'))->update(['status' => 'sending', 'last_attempt_at' => now(), 'updated_at' => now()]);
                return $locked;
            }, 5);
            if ($claimed->isEmpty()) continue;

            $claimed = $this->removeStaleDocumentRows($claimed);
            if ($claimed->isEmpty()) continue;

            try {
                $this->sendBatch($claimed);
                OrderEmailOutbox::query()->whereIn('id', $claimed->pluck('id'))->update([
                    'status' => 'sent', 'sent_at' => now(), 'last_error' => null, 'updated_at' => now(),
                ]);
                $sent += $claimed->count();
            } catch (Throwable $exception) {
                $maxAttempts = (int) $claimed->max('attempt_count') + 1;
                $status = $maxAttempts >= 5 ? 'failed' : 'retry';
                $retryAt = now()->addMinutes(min(240, 5 * (2 ** min(5, $maxAttempts - 1))));
                OrderEmailOutbox::query()->whereIn('id', $claimed->pluck('id'))->update([
                    'status' => $status,
                    'attempt_count' => DB::raw('attempt_count + 1'),
                    'scheduled_for' => $status === 'retry' ? $retryAt : null,
                    'last_error' => $this->truncate($exception->getMessage(), 4000),
                    'updated_at' => now(),
                ]);
                $failed += $claimed->count();
                Log::error('Order email outbox dispatch failed.', ['outbox_ids' => $claimed->pluck('id')->all(), 'exception' => $exception]);
            }
        }

        return compact('sent', 'failed', 'groups');
    }

    private function truncate(string $value, int $length): string
    {
        return function_exists('mb_substr') ? (string) mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
    }

    /** @param Collection<int,OrderEmailOutbox> $rows
     *  @return Collection<int,OrderEmailOutbox>
     */
    private function removeStaleDocumentRows(Collection $rows): Collection
    {
        $documentIds = $rows
            ->filter(static fn (OrderEmailOutbox $row): bool => $row->attach_document && $row->document_id !== null)
            ->pluck('document_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();
        if ($documentIds->isEmpty()) return $rows;

        $issuedIds = OrderDocument::query()
            ->whereIn('id', $documentIds->all())
            ->where('status', 'issued')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $issuedLookup = array_fill_keys($issuedIds, true);
        $staleIds = $rows
            ->filter(static fn (OrderEmailOutbox $row): bool => $row->attach_document
                && $row->document_id !== null
                && !isset($issuedLookup[(int) $row->document_id]))
            ->pluck('id')
            ->all();

        if ($staleIds !== []) {
            OrderEmailOutbox::query()->whereIn('id', $staleIds)->update([
                'status' => 'cancelled',
                'last_error' => 'Dokument je storniran ili uklonjen pre slanja.',
                'updated_at' => now(),
            ]);
        }

        return $rows->reject(static fn (OrderEmailOutbox $row): bool => in_array($row->id, $staleIds, true))->values();
    }

    /** @param Collection<int,OrderEmailOutbox> $rows */
    private function sendBatch(Collection $rows): void
    {
        /** @var OrderEmailOutbox $first */
        $first = $rows->first();
        $subject = $rows->count() === 1
            ? $first->subject
            : ($rows->every(static fn (OrderEmailOutbox $row): bool => str_starts_with((string) $row->event_type, 'product_'))
                ? 'Novi artikli u katalogu ('.$rows->count().')'
                : 'Obaveštenja iz sistema ('.$rows->count().')');
        $attachments = [];

        foreach ($rows as $row) {
            $document = null;
            if ($row->attach_document && $row->document_id) {
                $document = OrderDocument::query()->find($row->document_id);
            } elseif ($row->attach_active_invoice && $row->order_id) {
                $document = OrderDocument::query()->where('order_id', $row->order_id)->where('document_type', 'invoice')->where('status', 'issued')->latest('revision_number')->latest('id')->first();
            }
            if (!$document instanceof OrderDocument || $document->status !== 'issued' || isset($attachments[$document->id])) continue;
            $attachments[$document->id] = [
                'data' => $this->documents->render($document),
                'name' => $document->document_number.'.pdf',
            ];
        }

        Mail::send('emails.order-events', ['events' => $rows, 'recipientName' => $first->recipient_name], function ($mail) use ($first, $subject, $attachments): void {
            $mail->to($first->recipient_email, $first->recipient_name)->subject($subject);
            foreach ($attachments as $attachment) {
                $mail->attachData($attachment['data'], $attachment['name'], ['mime' => 'application/pdf']);
            }
        });
    }
}

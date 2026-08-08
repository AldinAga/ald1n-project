<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderEmailOutbox;
use App\Services\AuditLogger;
use App\Services\OrderEmailDispatcher;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class OrderEmailSettingsController extends Controller
{
    /** @var list<int> */
    private const INTERVALS = [0, 5, 15, 30, 60, 120, 240, 720, 1440];

    public function index(SettingsService $settings): View
    {
        $ready = Schema::hasTable('order_email_outbox');
        return view('admin.settings.order-emails', [
            'settings' => $settings->all(),
            'schemaReady' => $ready,
            'stats' => $ready ? [
                'pending' => OrderEmailOutbox::query()->whereIn('status', ['pending', 'retry'])->count(),
                'failed' => OrderEmailOutbox::query()->where('status', 'failed')->count(),
                'sent_today' => OrderEmailOutbox::query()->where('status', 'sent')->whereDate('sent_at', today())->count(),
            ] : ['pending' => 0, 'failed' => 0, 'sent_today' => 0],
            'recent' => $ready ? OrderEmailOutbox::query()->with(['order', 'document'])->latest('id')->limit(60)->get() : collect(),
            'intervals' => self::INTERVALS,
        ]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'order_email_custom_recipients' => ['nullable', 'string', 'max:5000'],
            'order_email_creation_interval_minutes' => ['required', 'integer', Rule::in(self::INTERVALS)],
            'order_email_update_interval_minutes' => ['required', 'integer', Rule::in(self::INTERVALS)],
            'order_email_document_interval_minutes' => ['required', 'integer', Rule::in(self::INTERVALS)],
            'product_email_new_items_interval_minutes' => ['required', 'integer', Rule::in(self::INTERVALS)],
        ]);
        $emails = collect(preg_split('/[\s,;]+/', (string) ($data['order_email_custom_recipients'] ?? '')) ?: [])
            ->map(static fn (string $email): string => strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values();
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return back()->withErrors(['order_email_custom_recipients' => 'Neispravna e-mail adresa: '.$email])->withInput();
            }
        }

        $before = $settings->all();
        $keys = [
            'order_email_enabled', 'order_email_creation_enabled', 'order_email_updates_enabled', 'order_email_documents_enabled',
            'order_email_send_creator', 'order_email_send_supplier', 'order_email_custom_recipients_for_updates',
            'order_email_event_created', 'order_email_event_status', 'order_email_event_tracking', 'order_email_event_payment',
            'order_email_event_accepted', 'order_email_event_reassigned', 'order_email_event_deadlines', 'order_email_event_completed',
            'order_email_event_reopened', 'order_email_event_document_cancelled', 'order_email_event_other',
            'order_email_updates_attach_invoice',
            'order_email_document_order_confirmation', 'order_email_document_proforma', 'order_email_document_invoice', 'order_email_document_delivery_note',
            'product_email_new_items_enabled',
        ];
        $values = [];
        foreach ($keys as $key) $values[$key] = $request->boolean($key) ? '1' : '0';
        $values['order_email_custom_recipients'] = $emails->implode("\n");
        $values['order_email_creation_interval_minutes'] = (string) $data['order_email_creation_interval_minutes'];
        $values['order_email_update_interval_minutes'] = (string) $data['order_email_update_interval_minutes'];
        $values['order_email_document_interval_minutes'] = (string) $data['order_email_document_interval_minutes'];
        $values['product_email_new_items_interval_minutes'] = (string) $data['product_email_new_items_interval_minutes'];

        $settings->putMany($values, $request->user()->id);
        $audit->log('settings.order_emails_updated', 'Ažurirana podešavanja e-mail obaveštenja.', null, $before, $values, user: $request->user());
        return back()->with('status', 'Podešavanja e-mail obaveštenja su sačuvana.');
    }

    public function dispatch(Request $request, OrderEmailDispatcher $dispatcher, AuditLogger $audit): RedirectResponse
    {
        $result = $dispatcher->dispatch(500);
        $audit->log('order_emails.manual_dispatch', 'Ručno pokrenuto slanje e-mail outbox reda.', null, metadata: $result, user: $request->user());
        return back()->with('status', sprintf('Slanje završeno: %d poslato, %d neuspešno.', $result['sent'], $result['failed']));
    }

    public function retry(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('order_email_outbox')) return back()->withErrors(['email' => 'Pokreni php artisan migrate --force pre ponovnog slanja.']);
        $count = OrderEmailOutbox::query()->where('status', 'failed')->update([
            'status' => 'retry', 'scheduled_for' => now(), 'last_error' => null, 'updated_at' => now(),
        ]);
        return back()->with('status', 'Za ponovno slanje pripremljeno je '.$count.' poruka.');
    }
}

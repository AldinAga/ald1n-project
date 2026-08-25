<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Admin\AutomationController as WebAutomationController;
use App\Http\Controllers\Admin\BankAccountController as WebBankAccountController;
use App\Http\Controllers\Admin\DocumentSettingsController as WebDocumentSettingsController;
use App\Http\Controllers\Admin\OrderEmailSettingsController as WebOrderEmailSettingsController;
use App\Http\Controllers\Admin\SiteAppearanceController as WebSiteAppearanceController;
use App\Http\Controllers\Admin\TurnstileSettingsController as WebTurnstileSettingsController;
use App\Http\Controllers\Controller;
use App\Models\AutomationRun;
use App\Models\BankAccount;
use App\Models\OperationalAlert;
use App\Models\OrderEmailOutbox;
use App\Services\AuditLogger;
use App\Services\AutomationReadinessService;
use App\Services\OperationalAutomationService;
use App\Services\OrderEmailDispatcher;
use App\Services\SettingsService;
use App\Services\SiteAssetUrlService;
use App\Services\TurnstileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class SystemSettingsController extends Controller
{
    /** @var list<int> */
    private const EMAIL_INTERVALS = [0, 5, 15, 30, 60, 120, 240, 720, 1440];

    /** @var list<string> */
    private const AUTOMATION_KEYS = [
        'automation_enabled', 'automation_unaccepted_order_hours', 'automation_alert_reminder_hours',
        'automation_low_stock_enabled', 'automation_overdue_payment_enabled', 'automation_deadline_alerts_enabled',
        'automation_daily_digest_enabled', 'automation_warranty_alerts_enabled', 'warranty_expiry_notice_days',
        'warranty_maintenance_notice_days', 'automation_last_success_at', 'automation_last_error',
    ];

    /** @var list<string> */
    private const APPEARANCE_KEYS = [
        'site_name', 'site_logo_alt', 'site_logo_light_path', 'site_logo_dark_path', 'site_favicon_path',
        'site_header_logo_height', 'site_footer_show_logo', 'site_footer_layout', 'site_footer_copyright_text',
        'site_footer_secondary_text', 'site_footer_links_new_tab', 'site_footer_link_1_label', 'site_footer_link_1_url',
        'site_footer_link_2_label', 'site_footer_link_2_url', 'site_footer_link_3_label', 'site_footer_link_3_url',
        'login_background_mode', 'login_background_image_path', 'login_background_fallback_path',
        'login_background_youtube_id', 'login_background_overlay_opacity', 'login_background_blur_px',
        'login_background_slide_interval', 'login_background_mobile_static',
    ];

    /** @var list<string> */
    private const ORDER_EMAIL_KEYS = [
        'order_email_enabled', 'order_email_creation_enabled', 'order_email_updates_enabled', 'order_email_documents_enabled',
        'order_email_send_creator', 'order_email_send_supplier', 'order_email_custom_recipients',
        'order_email_custom_recipients_for_updates', 'order_email_creation_interval_minutes',
        'order_email_update_interval_minutes', 'order_email_document_interval_minutes', 'order_email_event_created',
        'order_email_event_status', 'order_email_event_tracking', 'order_email_event_payment', 'order_email_event_accepted',
        'order_email_event_reassigned', 'order_email_event_deadlines', 'order_email_event_completed', 'order_email_event_reopened',
        'order_email_event_document_cancelled', 'order_email_event_other', 'order_email_updates_attach_invoice',
        'order_email_document_order_confirmation', 'order_email_document_proforma', 'order_email_document_invoice',
        'order_email_document_delivery_note', 'product_email_new_items_enabled', 'product_email_new_items_interval_minutes',
    ];

    /** @var list<string> */
    private const DOCUMENT_KEYS = [
        'documents_company_name', 'documents_company_address', 'documents_company_city', 'documents_company_tax_id',
        'documents_company_registration_number', 'documents_company_phone', 'documents_company_email',
        'documents_company_website', 'documents_logo_path', 'documents_vat_enabled', 'documents_vat_rate',
        'documents_payment_due_days', 'documents_default_note', 'documents_footer_note',
    ];

    public function automation(Request $request, SettingsService $settings, AutomationReadinessService $readiness): JsonResponse
    {
        $ready = $readiness->ready();
        $stats = ['open' => 0, 'danger' => 0, 'warning' => 0, 'failed_runs' => 0];
        $alerts = [];
        $runs = [];

        if ($ready) {
            $stats = [
                'open' => OperationalAlert::query()->where('status', 'open')->count(),
                'danger' => OperationalAlert::query()->where('status', 'open')->where('severity', 'danger')->count(),
                'warning' => OperationalAlert::query()->where('status', 'open')->where('severity', 'warning')->count(),
                'failed_runs' => AutomationRun::query()->where('status', 'failed')->where('started_at', '>=', now()->subDays(7))->count(),
            ];
            $alerts = OperationalAlert::query()
                ->where('status', 'open')
                ->orderByRaw("CASE severity WHEN 'danger' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
                ->latest('last_detected_at')
                ->limit(80)
                ->get()
                ->map(static fn (OperationalAlert $alert): array => [
                    'id' => (int) $alert->id,
                    'type' => (string) $alert->type,
                    'severity' => (string) $alert->severity,
                    'title' => (string) $alert->title,
                    'message' => (string) $alert->message,
                    'status' => (string) $alert->status,
                    'first_detected_at' => $alert->first_detected_at?->toIso8601String(),
                    'last_detected_at' => $alert->last_detected_at?->toIso8601String(),
                ])->all();
            $runs = AutomationRun::query()->latest('started_at')->limit(20)->get()
                ->map(static fn (AutomationRun $run): array => [
                    'id' => (int) $run->id,
                    'task' => (string) $run->task,
                    'status' => (string) $run->status,
                    'created_alerts' => (int) $run->created_alerts,
                    'updated_alerts' => (int) $run->updated_alerts,
                    'resolved_alerts' => (int) $run->resolved_alerts,
                    'notifications_sent' => (int) $run->notifications_sent,
                    'started_at' => $run->started_at?->toIso8601String(),
                    'finished_at' => $run->finished_at?->toIso8601String(),
                    'error_message' => $run->error_message ? (string) $run->error_message : null,
                ])->all();
        }

        return $this->jsonNoStore([
            'data' => [
                'settings' => $this->pick($settings->all(), self::AUTOMATION_KEYS),
                'ready' => $ready,
                'missing' => $ready ? [] : $readiness->missing(),
                'stats' => $stats,
                'alerts' => $alerts,
                'runs' => $runs,
                'capabilities' => [
                    'manage_settings' => $request->user()?->can('system.manage_settings') === true,
                    'run_automation' => $request->user()?->can('automation.manage') === true,
                ],
            ],
        ]);
    }

    public function updateAutomation(Request $request): JsonResponse
    {
        $this->delegate(WebAutomationController::class, 'update', ['request' => $request]);
        return $this->automation($request, app(SettingsService::class), app(AutomationReadinessService::class));
    }

    public function runAutomation(Request $request, OperationalAutomationService $automation, AuditLogger $audit): JsonResponse
    {
        try {
            $run = $automation->run($request->user(), true, $request->boolean('digest'));
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['automation' => 'Automatizacija nije uspela: '.$exception->getMessage()]);
        }
        $audit->log('automation.manual_run', 'Ručno pokrenuta operativna automatizacija.', $run, metadata: ['task' => $run->task, 'status' => $run->status], user: $request->user());
        return $this->automation($request, app(SettingsService::class), app(AutomationReadinessService::class));
    }

    public function resolveAutomationAlert(Request $request, OperationalAlert $alert): JsonResponse
    {
        $this->delegate(WebAutomationController::class, 'resolve', ['request' => $request, 'alert' => $alert]);
        return $this->automation($request, app(SettingsService::class), app(AutomationReadinessService::class));
    }

    public function turnstile(SettingsService $settings, TurnstileService $turnstile): JsonResponse
    {
        $databaseSecretConfigured = $settings->getSecret('turnstile_secret_key') !== null;
        $environmentSecretConfigured = trim((string) config('services.turnstile.secret_key')) !== '';
        return $this->jsonNoStore(['data' => [
            'settings' => [
                'turnstile_enabled' => $turnstile->enabled() ? '1' : '0',
                'turnstile_site_key' => $turnstile->siteKey(),
                'turnstile_expected_hostname' => $turnstile->expectedHostname(),
            ],
            'secret_configured' => $turnstile->secretConfigured(),
            'secret_source' => $databaseSecretConfigured ? 'Podešavanja aplikacije' : ($environmentSecretConfigured ? '.env fajl' : 'Nije podešen'),
            'site_key_source' => $settings->hasStoredValue('turnstile_site_key') ? 'Podešavanja aplikacije' : '.env fajl',
        ]]);
    }

    public function updateTurnstile(Request $request): JsonResponse
    {
        $this->delegate(WebTurnstileSettingsController::class, 'update', ['request' => $request]);
        return $this->turnstile(app(SettingsService::class), app(TurnstileService::class));
    }

    public function appearance(Request $request, SettingsService $settings, SiteAssetUrlService $assets): JsonResponse
    {
        $values = $settings->all();
        $data = $this->pick($values, self::APPEARANCE_KEYS);
        $slides = [];
        for ($slot = 1; $slot <= 8; $slot++) {
            $path = trim((string) ($values['login_background_slide_'.$slot.'_path'] ?? ''));
            $slides[] = [
                'slot' => $slot,
                'path' => $path,
                'url' => $assets->url($path),
                'active' => ($values['login_background_slide_'.$slot.'_active'] ?? '0') === '1',
                'order' => (int) ($values['login_background_slide_'.$slot.'_order'] ?? $slot * 10),
            ];
        }
        return $this->jsonNoStore(['data' => [
            'settings' => $data,
            'assets' => [
                'logo_light_url' => $assets->url($values['site_logo_light_path'] ?? ''),
                'logo_dark_url' => $assets->url($values['site_logo_dark_path'] ?? ''),
                'favicon_url' => $assets->url($values['site_favicon_path'] ?? ''),
                'login_background_url' => $assets->url($values['login_background_image_path'] ?? ''),
                'login_fallback_url' => $assets->url($values['login_background_fallback_path'] ?? ''),
                'slides' => $slides,
            ],
            'can_manage_login_background' => $request->user()?->hasRole('superadmin') === true,
            'upload_limits' => [
                'images' => ['mime_types' => ['image/png', 'image/jpeg', 'image/webp'], 'max_bytes' => 8 * 1024 * 1024],
                'favicon' => ['mime_types' => ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'], 'max_bytes' => 1024 * 1024],
            ],
        ]]);
    }

    public function updateAppearance(Request $request): JsonResponse
    {
        $this->delegate(WebSiteAppearanceController::class, 'update', ['request' => $request]);
        return $this->appearance($request, app(SettingsService::class), app(SiteAssetUrlService::class));
    }

    public function removeAppearanceAsset(Request $request, string $asset): JsonResponse
    {
        $this->delegate(WebSiteAppearanceController::class, 'removeAsset', ['request' => $request, 'asset' => $asset]);
        return $this->appearance($request, app(SettingsService::class), app(SiteAssetUrlService::class));
    }

    public function orderEmails(SettingsService $settings): JsonResponse
    {
        $ready = Schema::hasTable('order_email_outbox');
        $stats = ['pending' => 0, 'failed' => 0, 'sent_today' => 0];
        $recent = [];
        if ($ready) {
            $stats = [
                'pending' => OrderEmailOutbox::query()->whereIn('status', ['pending', 'retry'])->count(),
                'failed' => OrderEmailOutbox::query()->where('status', 'failed')->count(),
                'sent_today' => OrderEmailOutbox::query()->where('status', 'sent')->whereDate('sent_at', today())->count(),
            ];
            $recent = OrderEmailOutbox::query()->latest('id')->limit(60)->get()->map(static fn (OrderEmailOutbox $row): array => [
                'id' => (int) $row->id,
                'recipient_email' => (string) $row->recipient_email,
                'event_type' => (string) $row->event_type,
                'subject' => (string) $row->subject,
                'status' => (string) $row->status,
                'attempt_count' => (int) $row->attempt_count,
                'scheduled_for' => $row->scheduled_for?->toIso8601String(),
                'sent_at' => $row->sent_at?->toIso8601String(),
                'last_error' => $row->last_error ? (string) $row->last_error : null,
            ])->all();
        }
        return $this->jsonNoStore(['data' => [
            'settings' => $this->pick($settings->all(), self::ORDER_EMAIL_KEYS),
            'schema_ready' => $ready,
            'stats' => $stats,
            'recent' => $recent,
            'intervals' => self::EMAIL_INTERVALS,
        ]]);
    }

    public function updateOrderEmails(Request $request): JsonResponse
    {
        $raw = (string) $request->input('order_email_custom_recipients', '');
        foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $email) {
            $email = strtolower(trim($email));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw ValidationException::withMessages(['order_email_custom_recipients' => 'Neispravna e-mail adresa: '.$email]);
            }
        }
        $this->delegate(WebOrderEmailSettingsController::class, 'update', ['request' => $request]);
        return $this->orderEmails(app(SettingsService::class));
    }

    public function dispatchOrderEmails(Request $request, OrderEmailDispatcher $dispatcher, AuditLogger $audit): JsonResponse
    {
        $result = $dispatcher->dispatch(500);
        $audit->log('order_emails.manual_dispatch', 'Ručno pokrenuto slanje e-mail outbox reda.', null, metadata: $result, user: $request->user());
        return $this->jsonNoStore(['message' => sprintf('Slanje završeno: %d poslato, %d neuspešno.', $result['sent'], $result['failed']), 'result' => $result, 'data' => $this->orderEmails(app(SettingsService::class))->getData(true)['data']]);
    }

    public function retryOrderEmails(Request $request): JsonResponse
    {
        $this->delegate(WebOrderEmailSettingsController::class, 'retry', ['request' => $request]);
        return $this->orderEmails(app(SettingsService::class));
    }

    public function documents(SettingsService $settings): JsonResponse
    {
        $values = $settings->all();
        $path = trim((string) ($values['documents_logo_path'] ?? ''));
        $logoUrl = null;
        if ($path !== '') {
            try { $logoUrl = Storage::disk('public')->url($path); } catch (Throwable) { $logoUrl = null; }
        }
        return $this->jsonNoStore(['data' => [
            'settings' => $this->pick($values, self::DOCUMENT_KEYS),
            'document_logo_url' => $logoUrl,
            'upload_limits' => ['mime_types' => ['image/png', 'image/jpeg', 'image/webp'], 'max_bytes' => 4 * 1024 * 1024],
        ]]);
    }

    public function updateDocuments(Request $request): JsonResponse
    {
        $this->delegate(WebDocumentSettingsController::class, 'update', ['request' => $request]);
        return $this->documents(app(SettingsService::class));
    }

    public function removeDocumentLogo(Request $request): JsonResponse
    {
        $this->delegate(WebDocumentSettingsController::class, 'removeLogo', ['request' => $request]);
        return $this->documents(app(SettingsService::class));
    }

    public function bankAccounts(): JsonResponse
    {
        $accounts = Schema::hasTable('bank_accounts') ? BankAccount::query()->latest('updated_at')->get()->map(static fn (BankAccount $account): array => [
            'id' => (int) $account->id,
            'label' => (string) $account->label,
            'recipient_name' => (string) $account->recipient_name,
            'recipient_address' => $account->recipient_address ? (string) $account->recipient_address : null,
            'account_number' => (string) $account->account_number,
            'account_number_display' => (string) $account->account_number_display,
            'payment_code' => (string) $account->payment_code,
            'is_active' => (bool) $account->is_active,
        ])->all() : [];
        return $this->jsonNoStore(['data' => ['accounts' => $accounts]]);
    }

    public function storeBankAccount(Request $request): JsonResponse
    {
        $this->delegate(WebBankAccountController::class, 'store', ['request' => $request]);
        return $this->bankAccounts();
    }

    public function updateBankAccount(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->delegate(WebBankAccountController::class, 'update', ['request' => $request, 'bankAccount' => $bankAccount]);
        return $this->bankAccounts();
    }

    public function destroyBankAccount(BankAccount $bankAccount): JsonResponse
    {
        $this->delegate(WebBankAccountController::class, 'destroy', ['bankAccount' => $bankAccount]);
        return $this->bankAccounts();
    }

    /** @param array<string,mixed> $values @param list<string> $keys @return array<string,mixed> */
    private function pick(array $values, array $keys): array
    {
        $picked = [];
        foreach ($keys as $key) $picked[$key] = $values[$key] ?? '';
        return $picked;
    }

    /** @param class-string $controller @param array<string,mixed> $parameters */
    private function delegate(string $controller, string $method, array $parameters): void
    {
        app()->call([app($controller), $method], $parameters);
    }

    /** @param array<string,mixed> $payload */
    private function jsonNoStore(array $payload): JsonResponse
    {
        return response()->json($payload)->header('Cache-Control', 'private, no-store, max-age=0');
    }
}

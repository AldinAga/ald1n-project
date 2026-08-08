<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SettingsService
{
    private const CACHE_KEY = 'ald1n.settings.all';

    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'turnstile_secret_key',
    ];

    /** @var array<string,string> */
    private const DEFAULTS = [
        'eur_rsd_rate' => '',
        'eur_rsd_mode' => 'manual',
        'eur_rsd_provider' => 'frankfurter',
        'eur_rsd_source' => 'Nije podešeno',
        'eur_rsd_stale_after_hours' => '48',
        'eur_rsd_provider_date' => '',
        'eur_rsd_updated_at' => '',
        'eur_rsd_last_attempt_at' => '',
        'eur_rsd_last_error' => '',
        'site_name' => 'Ald1n CMS',
        'site_logo_path' => '',
        'site_logo_light_path' => '',
        'site_logo_dark_path' => '',
        'site_logo_alt' => 'Ald1n CMS',
        'site_favicon_path' => '',
        'site_header_logo_height' => '38',
        'site_footer_show_logo' => '0',
        'site_footer_copyright_text' => '© {year} {site_name}',
        'site_footer_secondary_text' => '',
        'site_footer_layout' => 'split',
        'site_footer_links_new_tab' => '0',
        'site_footer_link_1_label' => '',
        'site_footer_link_1_url' => '',
        'site_footer_link_2_label' => '',
        'site_footer_link_2_url' => '',
        'site_footer_link_3_label' => '',
        'site_footer_link_3_url' => '',
        'documents_company_name' => 'Ald1n',
        'documents_company_address' => '',
        'documents_company_city' => '',
        'documents_company_tax_id' => '',
        'documents_company_registration_number' => '',
        'documents_company_phone' => '',
        'documents_company_email' => '',
        'documents_company_website' => '',
        'documents_logo_path' => '',
        'documents_vat_enabled' => '0',
        'documents_vat_rate' => '20',
        'documents_payment_due_days' => '7',
        'documents_default_note' => 'Hvala na ukazanom poverenju.',
        'documents_footer_note' => 'Dokument je generisan elektronski iz Ald1n CMS sistema.',
        'automation_enabled' => '1',
        'automation_unaccepted_order_hours' => '4',
        'automation_alert_reminder_hours' => '24',
        'automation_low_stock_enabled' => '1',
        'automation_overdue_payment_enabled' => '1',
        'automation_deadline_alerts_enabled' => '1',
        'automation_daily_digest_enabled' => '1',
        'automation_warranty_alerts_enabled' => '1',
        'warranty_expiry_notice_days' => '30',
        'warranty_maintenance_notice_days' => '7',
        'automation_last_success_at' => '',
        'order_email_enabled' => '1',
        'order_email_creation_enabled' => '1',
        'order_email_updates_enabled' => '1',
        'order_email_documents_enabled' => '1',
        'order_email_send_creator' => '1',
        'order_email_send_supplier' => '1',
        'order_email_custom_recipients' => '',
        'order_email_custom_recipients_for_updates' => '0',
        'order_email_creation_interval_minutes' => '0',
        'order_email_update_interval_minutes' => '0',
        'order_email_document_interval_minutes' => '0',
        'order_email_event_created' => '1',
        'order_email_event_status' => '1',
        'order_email_event_tracking' => '1',
        'order_email_event_payment' => '1',
        'order_email_event_accepted' => '1',
        'order_email_event_reassigned' => '1',
        'order_email_event_deadlines' => '1',
        'order_email_event_completed' => '1',
        'order_email_event_reopened' => '1',
        'order_email_event_document_cancelled' => '1',
        'order_email_event_other' => '1',
        'order_email_updates_attach_invoice' => '0',
        'order_email_document_order_confirmation' => '0',
        'order_email_document_proforma' => '1',
        'order_email_document_invoice' => '1',
        'order_email_document_delivery_note' => '1',
        'product_email_new_items_enabled' => '0',
        'product_email_new_items_interval_minutes' => '0',
        'automation_last_error' => '',
    ];

    /** @return array<string,string> */
    public function all(): array
    {
        $settings = $this->raw();
        foreach (self::SENSITIVE_KEYS as $key) {
            unset($settings[$key]);
        }

        $merged = array_replace(self::DEFAULTS, $settings);
        $legacy = $merged['site_logo_path'];
        if ($merged['site_logo_light_path'] === '') $merged['site_logo_light_path'] = $legacy;
        if ($merged['site_logo_dark_path'] === '') $merged['site_logo_dark_path'] = $legacy;
        $merged['site_header_logo_height'] = (string) max(24, min(80, (int) $merged['site_header_logo_height']));
        $merged['site_footer_show_logo'] = $merged['site_footer_show_logo'] === '1' ? '1' : '0';
        $merged['site_footer_links_new_tab'] = $merged['site_footer_links_new_tab'] === '1' ? '1' : '0';
        $merged['site_footer_layout'] = in_array($merged['site_footer_layout'], ['split', 'centered'], true) ? $merged['site_footer_layout'] : 'split';

        return $merged;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $all = $this->all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function hasStoredValue(string $key): bool
    {
        return array_key_exists($key, $this->raw());
    }

    public function getSecret(string $key): ?string
    {
        $encrypted = trim((string) ($this->raw()[$key] ?? ''));
        if ($encrypted === '') return null;

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }
    }

    public function eurRsdRate(): ?float
    {
        $value = $this->get('eur_rsd_rate');
        return is_numeric($value) && (float) $value > 0 ? (float) $value : null;
    }

    /** @param array<string,string|null> $values */
    public function putMany(array $values, ?int $userId): void
    {
        DB::transaction(function () use ($values, $userId): void {
            foreach ($values as $key => $value) {
                Setting::query()->updateOrCreate(
                    ['setting_key' => $key],
                    ['setting_value' => $value, 'updated_by' => $userId],
                );
            }
        });
        $this->forgetCache();
    }

    public function putSecret(string $key, string $value, ?int $userId): void
    {
        $value = trim($value);
        if ($value === '') return;

        $this->putMany([$key => Crypt::encryptString($value)], $userId);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function renderTemplate(string $text): string
    {
        return strtr($text, [
            '{year}' => date('Y'),
            '{site_name}' => (string) $this->get('site_name', 'Ald1n CMS'),
            '{version}' => (string) config('app.version'),
        ]);
    }

    /** @return array<string,string> */
    private function raw(): array
    {
        /** @var array<string,string> $settings */
        $settings = Cache::remember(self::CACHE_KEY, now()->addMinutes(10), static function (): array {
            $rows = Setting::query()->pluck('setting_value', 'setting_key')->all();
            return array_map(static fn ($value): string => (string) ($value ?? ''), $rows);
        });

        return $settings;
    }
}

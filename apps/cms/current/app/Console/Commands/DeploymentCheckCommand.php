<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LegacyReadOnlyGuard;
use App\Services\TurnstileService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Throwable;

final class DeploymentCheckCommand extends Command
{
    /** @var list<string> */
    private const OPERATIONS_TABLES = [
        'bank_accounts',
        'orders',
        'order_status_history',
        'order_items',
        'order_commissions',
        'commission_status_history',
        'order_ips_qr',
        'stock_movements',
        'idempotency_keys',
        'exchange_rate_history',
        'legacy_audit_logs',
        'document_counters',
        'order_documents',
        'order_email_outbox',
        'order_internal_notes',
        'order_assignments',
        'commission_payment_batches',
        'notifications',
        'order_payments',
        'order_deliveries',
        'after_sales_cases',
        'after_sales_case_items',
        'after_sales_messages',
        'after_sales_attachments',
        'after_sales_status_history',
        'after_sales_actions',
        'after_sales_action_items',
        'field_service_teams',
        'field_work_orders',
        'field_work_order_attachments',
        'service_part_suppliers',
        'service_parts',
        'field_work_order_parts',
        'service_part_movements',
        'service_part_purchase_requests',
        'service_part_purchase_request_items',
        'warranty_rules',
        'product_warranties',
        'warranty_maintenance_records',
        'receivable_cases',
        'receivable_installments',
        'receivable_contacts',
        'report_schedules',
        'report_deliveries',
        'product_types',
        'product_type_fields',
        'products',
        'specification_fields',
        'specification_options',
        'specification_option_dependencies',
        'product_spec_values',
        'product_lines',
        'stock_receipts',
        'stock_receipt_items',
        'inventory_counts',
        'inventory_count_items',
        'automation_runs',
        'operational_alerts',
        'notification_preferences',
        'backup_runs',
        'system_health_snapshots',
        'system_runtime_states',
        'security_events',
    ];

    /** @var array<string,list<string>> */
    private const OPERATIONS_COLUMNS = [
        'orders' => ['id', 'order_number', 'user_id', 'supplier_user_id', 'supplier_name_snapshot', 'supplier_role_snapshot', 'assigned_at', 'assigned_by', 'reassigned_at', 'accepted_by', 'accepted_at', 'expected_processing_at', 'expected_shipping_at', 'last_internal_note_at', 'status', 'subtotal_rsd', 'source_system', 'inventory_state', 'inventory_returned_at', 'payment_state', 'paid_total_rsd', 'payment_due_at', 'payment_verified_at', 'completed_at', 'completed_by', 'completion_note', 'reopened_at', 'reopened_by', 'reopen_reason'],
        'order_items' => ['id', 'order_id', 'product_id', 'quantity', 'commission_total_eur_snapshot', 'purchase_unit_rsd_snapshot', 'purchase_total_rsd_snapshot', 'cost_source_snapshot', 'brand_name_snapshot', 'product_line_name_snapshot', 'product_type_name_snapshot'],
        'order_commissions' => ['id', 'order_id', 'user_id', 'status', 'total_eur', 'payment_batch_id', 'payment_method', 'payment_reference', 'status_updated_at'],
        'commission_status_history' => ['id', 'commission_id', 'order_id', 'changed_by', 'old_status', 'new_status', 'note', 'metadata_json', 'created_at'],
        'order_internal_notes' => ['id', 'order_id', 'user_id', 'note', 'created_at', 'updated_at'],
        'order_assignments' => ['id', 'order_id', 'old_supplier_user_id', 'new_supplier_user_id', 'changed_by', 'reason', 'created_at'],
        'commission_payment_batches' => ['id', 'batch_number', 'payment_method', 'payment_reference', 'commission_count', 'total_eur', 'paid_by', 'paid_at'],
        'notifications' => ['id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'created_at', 'updated_at'],
        'stock_movements' => ['id', 'product_id', 'quantity_change', 'event_key', 'source', 'metadata_json', 'stock_receipt_id', 'inventory_count_id'],
        'idempotency_keys' => ['id', 'scope', 'actor_key', 'key_hash', 'request_hash', 'status'],
        'document_counters' => ['id', 'document_type', 'year', 'next_number'],
        'order_documents' => ['id', 'order_id', 'document_type', 'revision_number', 'supersedes_document_id', 'document_number', 'status', 'issued_by', 'issued_at', 'subtotal_rsd', 'tax_rate_percent', 'tax_amount_rsd', 'total_rsd', 'company_name', 'customer_name', 'delivery_method_snapshot', 'delivery_recipient_snapshot', 'delivered_at_snapshot', 'delivery_reference_snapshot', 'delivery_note_snapshot', 'ips_payload_snapshot', 'ips_qr_image_path', 'ips_qr_generated_at', 'ips_qr_error', 'cancelled_at', 'cancellation_reason'],
        'order_email_outbox' => ['id', 'order_id', 'recipient_user_id', 'document_id', 'recipient_email', 'event_type', 'dedupe_key', 'batch_key', 'subject', 'message', 'attach_document', 'attach_active_invoice', 'status', 'attempt_count', 'scheduled_for', 'sent_at', 'last_error', 'metadata_json'],
        'order_payments' => ['id', 'order_id', 'after_sales_action_id', 'payment_number', 'entry_type', 'status', 'amount_rsd', 'payment_method', 'proof_path', 'submitted_by', 'verified_by'],
        'order_deliveries' => ['id', 'order_id', 'delivery_method', 'delivered_at', 'recipient_name', 'recipient_phone', 'reference', 'note', 'proof_disk', 'proof_path', 'confirmed_by'],
        'after_sales_cases' => ['id', 'case_number', 'order_id', 'opened_by', 'assigned_to', 'case_type', 'priority', 'status', 'subject', 'description', 'due_at', 'first_response_at', 'resolved_at', 'closed_at'],
        'after_sales_case_items' => ['id', 'after_sales_case_id', 'order_item_id', 'product_id', 'product_name_snapshot', 'quantity'],
        'after_sales_messages' => ['id', 'after_sales_case_id', 'user_id', 'visibility', 'body'],
        'after_sales_attachments' => ['id', 'after_sales_case_id', 'message_id', 'disk', 'path', 'mime_type', 'size_bytes'],
        'after_sales_status_history' => ['id', 'after_sales_case_id', 'from_status', 'to_status', 'actor_id', 'metadata_json'],
        'after_sales_actions' => ['id', 'action_number', 'after_sales_case_id', 'action_type', 'status', 'inventory_handling', 'assigned_to', 'scheduled_at', 'due_at', 'amount_rsd', 'payment_id', 'completed_at', 'cancelled_at'],
        'after_sales_action_items' => ['id', 'after_sales_action_id', 'after_sales_case_item_id', 'product_id', 'quantity', 'disposition', 'stock_effect'],
        'field_service_teams' => ['id', 'code', 'name', 'team_type', 'contact_person', 'phone', 'vehicle_registration', 'service_area', 'is_active'],
        'field_work_orders' => ['id', 'work_order_number', 'after_sales_action_id', 'field_service_team_id', 'status', 'planned_start_at', 'planned_end_at', 'en_route_at', 'on_site_at', 'completed_at', 'cancelled_at', 'customer_name_snapshot', 'service_address_snapshot', 'total_cost_rsd'],
        'field_work_order_attachments' => ['id', 'field_work_order_id', 'uploaded_by', 'visibility', 'path', 'mime_type', 'size_bytes'],
        'service_part_suppliers' => ['id', 'code', 'name', 'lead_time_days', 'is_active'],
        'service_parts' => ['id', 'sku', 'name', 'stock_quantity', 'reserved_quantity', 'minimum_quantity', 'average_cost_rsd', 'is_active'],
        'field_work_order_parts' => ['id', 'field_work_order_id', 'service_part_id', 'supply_mode', 'requested_quantity', 'reserved_quantity', 'consumed_quantity'],
        'service_part_movements' => ['id', 'event_key', 'service_part_id', 'movement_type', 'stock_change', 'reserved_change', 'stock_before', 'stock_after'],
        'service_part_purchase_requests' => ['id', 'request_number', 'supplier_id', 'status', 'expected_at', 'received_at', 'total_cost_rsd'],
        'service_part_purchase_request_items' => ['id', 'purchase_request_id', 'service_part_id', 'ordered_quantity', 'received_quantity', 'unit_cost_rsd'],
        'warranty_rules' => ['id', 'scope_type', 'category_id', 'product_id', 'duration_months', 'duration_days', 'maintenance_interval_months', 'is_active'],
        'product_warranties' => ['id', 'warranty_number', 'order_id', 'order_item_id', 'product_id', 'user_id', 'status', 'starts_at', 'expires_at', 'duration_months', 'duration_days', 'serial_numbers_json', 'next_maintenance_at'],
        'warranty_maintenance_records' => ['id', 'product_warranty_id', 'status', 'due_at', 'scheduled_at', 'completed_at'],
        'receivable_cases' => ['id', 'order_id', 'case_number', 'status', 'collection_stage', 'assigned_to', 'next_action_at', 'promised_payment_at', 'last_reminder_stage', 'last_reminder_at', 'closed_at'],
        'receivable_installments' => ['id', 'receivable_case_id', 'sequence_no', 'due_at', 'amount_rsd', 'paid_amount_rsd', 'status', 'paid_at'],
        'receivable_contacts' => ['id', 'receivable_case_id', 'order_email_outbox_id', 'event_key', 'channel', 'direction', 'note', 'visible_to_customer', 'is_automatic', 'contacted_at'],
        'report_schedules' => ['id', 'name', 'report_type', 'frequency', 'send_time', 'timezone', 'recipients_json', 'formats_json', 'is_active', 'next_run_at'],
        'report_deliveries' => ['id', 'report_schedule_id', 'recipient_email', 'period_from', 'period_to', 'status', 'attempt_count', 'scheduled_for', 'dedupe_key'],
        'product_types' => ['id', 'name', 'name_template', 'auto_name_enabled', 'minimum_completeness_percent', 'default_product_status', 'required_core_fields_json'],
        'product_type_fields' => ['product_type_id', 'field_id', 'default_value', 'default_detail', 'completeness_weight', 'include_in_name'],
        'products' => ['id', 'product_type_id', 'brand_id', 'product_line_id', 'model_name', 'purchase_price_rsd', 'completeness_percent', 'name_is_manual', 'source_product_id'],
        'specification_fields' => ['id', 'name', 'data_type', 'parent_field_id', 'detail_input_enabled', 'detail_label', 'detail_placeholder'],
        'specification_options' => ['id', 'field_id', 'label', 'value', 'status', 'sort_order'],
        'specification_option_dependencies' => ['parent_option_id', 'child_option_id', 'created_at'],
        'product_spec_values' => ['product_id', 'field_id', 'value_text', 'value_detail', 'value_number', 'value_boolean'],
        'product_lines' => ['id', 'brand_id', 'name', 'slug', 'status'],
        'stock_receipts' => ['id', 'receipt_number', 'status', 'received_on', 'total_units', 'posted_at'],
        'stock_receipt_items' => ['id', 'stock_receipt_id', 'product_id', 'quantity'],
        'inventory_counts' => ['id', 'count_number', 'status', 'counted_on', 'total_variance', 'finalized_at'],
        'inventory_count_items' => ['id', 'inventory_count_id', 'product_id', 'system_quantity', 'counted_quantity', 'variance'],
        'automation_runs' => ['id', 'task', 'status', 'started_at', 'finished_at', 'summary_json'],
        'operational_alerts' => ['id', 'alert_key', 'type', 'severity', 'status', 'last_detected_at', 'last_notified_at'],
        'notification_preferences' => ['id', 'user_id', 'in_app_enabled', 'email_enabled', 'order_updates', 'payment_alerts', 'document_updates', 'after_sales_updates', 'warranty_updates', 'service_updates', 'receivable_updates', 'daily_digest'],
        'user_activation_tokens' => ['id', 'user_id', 'token_hash', 'expires_at', 'sent_at', 'accepted_at', 'created_by'],
        'user_login_sessions' => ['id', 'user_id', 'session_hash', 'device_label', 'last_seen_at', 'revoked_at', 'logged_out_at'],
        'portal_conversations' => ['id', 'user_id', 'order_id', 'assigned_to', 'subject', 'status', 'priority', 'last_message_at'],
        'portal_messages' => ['id', 'conversation_id', 'sender_id', 'visibility', 'body', 'sent_at', 'read_by_customer_at', 'read_by_staff_at'],
        'portal_order_link_history' => ['id', 'order_id', 'from_user_id', 'to_user_id', 'changed_by', 'reason'],
        'backup_runs' => ['id', 'backup_key', 'backup_type', 'status', 'backup_path', 'size_bytes', 'started_at'],
        'system_health_snapshots' => ['id', 'status', 'checks_json', 'checked_at'],
        'system_runtime_states' => ['id', 'state_key', 'state_value', 'recorded_at'],
        'security_events' => ['id', 'event_type', 'severity', 'request_id', 'route_name', 'ip_address', 'context_json', 'created_at'],
        'audit_logs' => ['id', 'action', 'level', 'request_id', 'subject', 'created_at'],
    ];

    /** @var list<string> */
    private const CORE_TABLES = [
        'roles',
        'user_groups',
        'permissions',
        'users',
        'user_group_permissions',
        'settings',
    ];

    /** @var array<string,list<string>> */
    private const LOGIN_SCHEMA_COLUMNS = [
        'users' => ['id', 'role_id', 'username', 'email', 'password_hash', 'status', 'remember_token', 'last_login_at', 'address', 'city', 'postal_code', 'email_verified_at', 'portal_activated_at'],
        'roles' => ['id', 'name', 'slug'],
        'settings' => ['id', 'setting_key', 'setting_value'],
    ];

    /** @var list<string> */
    private const REQUIRED_PERMISSIONS = [
        'catalog.view',
        'catalog.view_prices',
        'orders.create',
        'orders.view_own',
        'orders.cancel_own',
        'catalog.manage_products',
        'catalog.manage_images',
        'catalog.manage_taxonomy',
        'catalog.audit',
        'catalog.sync_legacy',
        'orders.manage',
        'commissions.manage',
        'stock.view',
        'stock.adjust',
        'system.manage_users',
        'system.manage_settings',
        'reports.view',
        'reports.export',
        'reports.manage',
        'invoices.manage',
        'invoices.view_own',
        'commissions.view_own',
        'orders.reassign',
        'orders.internal_notes',
        'notifications.view',
        'payments.manage',
        'payments.upload_proof',
        'payments.view_own',
        'inventory.receive',
        'inventory.count',
        'inventory.export',
        'automation.manage',
        'system.health',
        'backups.manage',
        'audit.export',
        'security.view',
        'orders.confirm_delivery',
        'orders.reopen',
        'after_sales.create',
        'after_sales.view_own',
        'after_sales.manage',
        'after_sales.execute',
        'field_operations.view',
        'field_operations.manage',
        'service_parts.view',
        'service_parts.manage',
        'service_parts.procurement',
        'warranties.view_own',
        'warranties.manage',
        'receivables.manage',
    ];

    protected $signature = 'app:deployment-check
        {--repair : Pokreni migracije i CoreAccessSeeder pre provere}
        {--strict-legacy-grants : Tretiraj šire legacy DB grantove kao FAIL umesto WARN}';

    protected $description = 'Proveri i po potrebi bezbedno popravi produkcionu konfiguraciju za cms.ald1n.com';

    public function handle(LegacyReadOnlyGuard $legacyGuard, TurnstileService $turnstile): int
    {
        $repairFailed = false;
        if ($this->option('repair')) {
            $runtimeOk = $this->repairRuntimeDirectories();
            $databaseOk = $this->repairProductionDatabase();
            $repairFailed = !$runtimeOk || !$databaseOk;
        }

        $turnstileEnabled = $turnstile->enabled();
        $turnstileHostname = $turnstile->expectedHostname();
        $applicationHostname = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        $checks = [
            'PHP 8.4+' => version_compare(PHP_VERSION, '8.4.0', '>='),
            'APP_URL cms.ald1n.com' => parse_url((string) config('app.url'), PHP_URL_HOST) === 'cms.ald1n.com',
            'deploy path' => realpath(base_path()) === realpath((string) config('app.deploy_path')),
            'APP_KEY' => trim((string) config('app.key')) !== '',
            'APP_DEBUG=false' => config('app.debug') === false,
            'Turnstile site key' => !$turnstileEnabled || $turnstile->siteKey() !== '',
            'Turnstile secret' => !$turnstileEnabled || $turnstile->secretConfigured(),
            'Turnstile hostname' => !$turnstileEnabled || $turnstileHostname === '' || $turnstileHostname === $applicationHostname,
            'Turnstile action' => !$turnstileEnabled || config('services.turnstile.expected_action') === 'login',
            'storage writable' => is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
            'session directory writable' => $this->directoryIsWritable(storage_path('framework/sessions')),
            'cache directory writable' => $this->directoryIsWritable(storage_path('framework/cache/data')),
            'compiled view directory writable' => $this->directoryIsWritable(storage_path('framework/views')),
            'log directory writable' => $this->directoryIsWritable(storage_path('logs')),
            'public storage link' => is_link(public_path('storage')) || is_dir(public_path('storage')),
            'legacy database defined' => (string) config('database.connections.legacy.database') === 'icaffeco_cms',
            'target database defined' => (string) config('database.connections.mysql.database') === 'icaffeco_lrvl',
            'mail transport' => in_array((string) config('mail.default'), ['smtp', 'sendmail'], true),
            'mail from address' => filter_var((string) config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
            'SMTP host' => (string) config('mail.default') !== 'smtp'
                || trim((string) config('mail.mailers.smtp.host')) !== '',
            'cache store supported' => in_array((string) config('cache.default'), ['file', 'database', 'array'], true),
            'Redis disabled' => (string) config('cache.default') !== 'redis'
                && (string) config('session.driver') !== 'redis'
                && (string) config('queue.default') !== 'redis'
        ];

        $missingOperations = [];
        $missingOperationColumns = [];
        $missingCoreTables = [];
        $missingLoginColumns = [];
        $missingPermissions = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = true;
            $checks['password reset table'] = Schema::hasTable('password_reset_tokens');

            $missingCoreTables = array_values(array_filter(
                self::CORE_TABLES,
                static fn (string $table): bool => !Schema::hasTable($table),
            ));
            foreach (self::LOGIN_SCHEMA_COLUMNS as $table => $columns) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $existingColumns = Schema::getColumnListing($table);
                foreach (array_diff($columns, $existingColumns) as $column) {
                    $missingLoginColumns[] = $table.'.'.$column;
                }
            }
            $checks['post-login core schema'] = $missingCoreTables === [] && $missingLoginColumns === [];

            $missingOperations = array_values(array_filter(
                self::OPERATIONS_TABLES,
                static fn (string $table): bool => !Schema::hasTable($table),
            ));

            foreach (self::OPERATIONS_COLUMNS as $table => $columns) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $existingColumns = Schema::getColumnListing($table);
                foreach (array_diff($columns, $existingColumns) as $column) {
                    $missingOperationColumns[] = $table.'.'.$column;
                }
            }
            $checks['operations tables'] = $missingOperations === [] && $missingOperationColumns === [];
            $checks['delivery note document type'] = $this->deliveryNoteTypeSupported();
            $checks['document revisions'] = $this->documentRevisionSchemaSupported();

            if (Schema::hasTable('permissions')) {
                $existingPermissions = DB::table('permissions')
                    ->whereIn('slug', self::REQUIRED_PERMISSIONS)
                    ->pluck('slug')
                    ->all();
                $missingPermissions = array_values(array_diff(self::REQUIRED_PERMISSIONS, $existingPermissions));
            } else {
                $missingPermissions = self::REQUIRED_PERMISSIONS;
            }
            $checks['system permissions'] = $missingPermissions === [];

            $checks['active login user'] = Schema::hasTable('users')
                && DB::table('users')->where('status', 'active')->exists();
            $legacyImagesExist = Schema::hasTable('product_images')
                && DB::table('product_images')->where('storage_disk', 'legacy')->exists();
            $legacyMediaRoot = trim((string) config('services.legacy_media.root'));
            $checks['legacy product media root'] = !$legacyImagesExist
                || ($legacyMediaRoot !== '' && is_dir($legacyMediaRoot.'/uploads/products'));
        } catch (Throwable $exception) {
            $checks['database'] = false;
            $checks['password reset table'] = false;
            $checks['post-login core schema'] = false;
            $checks['operations tables'] = false;
            $checks['delivery note document type'] = false;
            $checks['system permissions'] = false;
            $checks['active login user'] = false;
            $checks['legacy product media root'] = false;
            $this->warn('Laravel DB provera: '.$exception->getMessage());
        }

        $legacyGrantWarnings = [];
        try {
            $legacy = DB::connection('legacy');
            $legacy->getPdo();
            $checks['legacy database'] = true;

            $checks['legacy session read-only'] = $this->legacySessionIsReadOnly($legacy);
            $checks['legacy SQL guard'] = $this->legacyMutationGuardIsActive($legacy);

            $grants = array_map(
                static fn ($row): string => (string) array_values((array) $row)[0],
                $legacy->select('SHOW GRANTS FOR CURRENT_USER()'),
            );
            $legacyGrantWarnings = $legacyGuard->grantViolations($grants);
            if ($this->option('strict-legacy-grants')) {
                $checks['legacy grants least privilege'] = $legacyGrantWarnings === [];
            }
        } catch (Throwable $exception) {
            $checks['legacy database'] = false;
            $checks['legacy session read-only'] = false;
            $checks['legacy SQL guard'] = false;
            if ($this->option('strict-legacy-grants')) {
                $checks['legacy grants least privilege'] = false;
            }
            $this->warn('Legacy DB provera: '.$exception->getMessage());
        }

        $checks['separate databases'] = (string) config('database.connections.mysql.database') !== ''
            && (string) config('database.connections.mysql.database') !== (string) config('database.connections.legacy.database');

        $failed = $repairFailed;
        foreach ($checks as $label => $ok) {
            $this->line(($ok ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' '.$label);
            $failed = $failed || !$ok;
        }

        if ($missingCoreTables !== [] || $missingLoginColumns !== []) {
            $this->newLine();
            if ($missingCoreTables !== []) {
                $this->warn('Nedostaju core tabele za login/dashboard: '.implode(', ', $missingCoreTables));
            }
            if ($missingLoginColumns !== []) {
                $this->warn('Nedostaju core kolone za login/dashboard: '.implode(', ', $missingLoginColumns));
            }
            $this->line('Pokreni: php artisan app:deployment-check --repair');
        }

        if ($missingOperations !== []) {
            $this->newLine();
            $this->warn('Nedostaju operativne tabele: '.implode(', ', $missingOperations));
            $this->line('Pokreni: php artisan app:deployment-check --repair');
        }

        if ($missingOperationColumns !== []) {
            $this->newLine();
            $this->warn('Nedostaju operativne kolone: '.implode(', ', $missingOperationColumns));
            $this->line('Pokreni: php artisan app:deployment-check --repair');
        }

        if ($missingPermissions !== []) {
            $this->newLine();
            $this->warn('Nedostaju sistemske dozvole: '.implode(', ', $missingPermissions));
            $this->line('Pokreni: php artisan app:deployment-check --repair');
        }

        if ($legacyGrantWarnings !== [] && !$this->option('strict-legacy-grants')) {
            $this->newLine();
            $this->warn('Legacy nalog ima šire DB grantove od preporučenih SELECT/SHOW VIEW prava.');
            $this->line('Runtime ostaje blokiran kroz read-only session i SQL guard, zato ovo nije deployment FAIL.');
            foreach ($legacyGrantWarnings as $warning) {
                $this->line('  - '.$warning);
            }
            $this->line('Za strogu least-privilege proveru: php artisan app:deployment-check --strict-legacy-grants');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function repairRuntimeDirectories(): bool
    {
        $this->info('Proveravam runtime direktorijume za session, cache, view i log fajlove...');
        $ok = true;

        foreach ([
            storage_path('framework/sessions'),
            storage_path('framework/cache/data'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ] as $directory) {
            if (!is_dir($directory)) {
                try {
                    if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                        throw new \RuntimeException('mkdir nije uspeo');
                    }
                } catch (Throwable $exception) {
                    $this->error('Ne mogu da kreiram '.$directory.': '.$exception->getMessage());
                    $ok = false;
                    continue;
                }
            }

            @chmod($directory, 0775);
            if (!$this->directoryIsWritable($directory)) {
                $this->error('Direktorijum nije upisiv: '.$directory);
                $ok = false;
            } else {
                $this->line('<fg=green>PASS</> '.$directory);
            }
        }

        if ($ok) {
            try {
                Artisan::call('view:clear');
                $viewCode = Artisan::call('view:cache');
                if ($viewCode !== self::SUCCESS) {
                    $this->error('Blade view cache nije mogao da se generiše.');
                    $ok = false;
                } else {
                    $this->line('<fg=green>PASS</> Blade view compile/cache');
                }
            } catch (Throwable $exception) {
                $this->error('Blade view compile nije uspeo: '.$exception->getMessage());
                $ok = false;
            }
        }

        return $ok;
    }

    private function directoryIsWritable(string $directory): bool
    {
        if (!is_dir($directory) || !is_writable($directory)) {
            return false;
        }

        $probe = null;
        try {
            $probe = $directory.'/.ald1n-write-probe-'.bin2hex(random_bytes(6));
            if (file_put_contents($probe, 'ok', LOCK_EX) === false) {
                return false;
            }
            return true;
        } catch (Throwable) {
            return false;
        } finally {
            if (is_string($probe) && is_file($probe)) {
                @unlink($probe);
            }
        }
    }

    private function repairProductionDatabase(): bool
    {
        $this->info('Pokrećem bezbednu popravku: migracije + CoreAccessSeeder...');

        $migrationOk = false;
        $seedOk = false;

        try {
            $migrationCode = Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = trim(Artisan::output());
            if ($migrationOutput !== '') {
                $this->line($migrationOutput);
            }
            $migrationOk = $migrationCode === self::SUCCESS;
            if (!$migrationOk) {
                $this->error('Migracije nisu završene uspešno. CoreAccessSeeder će ipak biti pokušán kako bi se dozvole popravile nezavisno.');
            }
        } catch (Throwable $exception) {
            $this->error('Migracije nisu uspele: '.$exception->getMessage());
        }

        try {
            $seedCode = Artisan::call('db:seed', [
                '--class' => CoreAccessSeeder::class,
                '--force' => true,
            ]);
            $seedOutput = trim(Artisan::output());
            if ($seedOutput !== '') {
                $this->line($seedOutput);
            }
            $seedOk = $seedCode === self::SUCCESS;
            if (!$seedOk) {
                $this->error('CoreAccessSeeder nije završen uspešno.');
            }
        } catch (Throwable $exception) {
            $this->error('CoreAccessSeeder nije uspeo: '.$exception->getMessage());
        }

        try {
            DB::purge();
            DB::reconnect();
        } catch (Throwable $exception) {
            $this->warn('Ponovno povezivanje sa bazom nije uspelo: '.$exception->getMessage());
        }

        if ($migrationOk && $seedOk) {
            $this->info('Popravka baze je završena. Pokrećem proveru...');
            $this->newLine();
            return true;
        }

        $this->error('Popravka nije kompletna. Pročitaj migracioni/seeder izlaz iznad; aplikacija ostaje u bezbednom fallback režimu umesto da dashboard vrati 500.');
        return false;
    }

    private function legacySessionIsReadOnly($connection): bool
    {
        foreach (['transaction_read_only', 'tx_read_only'] as $variable) {
            try {
                $row = $connection->selectOne('SELECT @@SESSION.'.$variable.' AS read_only_value');
                if ($row !== null) {
                    return (int) ($row->read_only_value ?? 0) === 1;
                }
            } catch (Throwable) {
                // MySQL i MariaDB koriste različite nazive promenljive.
            }
        }

        return false;
    }

    private function legacyMutationGuardIsActive($connection): bool
    {
        try {
            // Promena session promenljive nema uticaj na podatke. Ako beforeExecuting
            // guard radi, naredba mora biti blokirana pre slanja MySQL serveru.
            $connection->statement('SET @ald1n_legacy_guard_probe = 1');
            return false;
        } catch (LogicException) {
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function deliveryNoteTypeSupported(): bool
    {
        if (!Schema::hasTable('order_documents') || !Schema::hasColumn('order_documents', 'document_type')) {
            return false;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return true;
        }

        try {
            $column = DB::selectOne("SHOW COLUMNS FROM `order_documents` WHERE `Field` = 'document_type'");
            $definition = strtolower((string) ($column->Type ?? ''));

            return !str_starts_with($definition, 'enum(') || str_contains($definition, "'delivery_note'");
        } catch (Throwable) {
            return false;
        }
    }

    private function documentRevisionSchemaSupported(): bool
    {
        foreach (['revision_number', 'supersedes_document_id', 'cancellation_reason'] as $column) {
            if (!Schema::hasColumn('order_documents', $column)) {
                return false;
            }
        }

        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return true;
        }

        try {
            $rows = DB::select('SHOW INDEX FROM `order_documents`');
            $indexes = [];
            foreach ($rows as $row) {
                if ((int) ($row->Non_unique ?? 1) !== 0 || (string) ($row->Key_name ?? '') === 'PRIMARY') {
                    continue;
                }
                $indexes[(string) $row->Key_name][(int) $row->Seq_in_index] = (string) $row->Column_name;
            }
            foreach ($indexes as $columns) {
                ksort($columns);
                if (array_values($columns) === ['order_id', 'document_type']) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

}

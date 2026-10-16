<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 121-column, explicit MySQL relationship classification from verified
 * ephemeral MySQL 8.4 schema. Historical links are never rewritten.
 *
 * ANY added/dropped/altered FK or missing unconstrained actor field makes
 * both preview and retirement fail CLOSED before a business data mutation.
 */
final class UserRetirementRelationPolicy
{
    /** @var array<string,array{0:string,1:string}> */
    public const USER_FKS = [
        'after_sales_actions.assigned_to' => ['active_assignment', 'SET NULL'],
        'after_sales_actions.cancelled_by' => ['immutable_history', 'SET NULL'],
        'after_sales_actions.completed_by' => ['immutable_history', 'SET NULL'],
        'after_sales_actions.created_by' => ['immutable_history', 'RESTRICT'],
        'after_sales_actions.started_by' => ['immutable_history', 'SET NULL'],
        'after_sales_actions.updated_by' => ['immutable_history', 'SET NULL'],
        'after_sales_attachments.uploaded_by' => ['immutable_history', 'RESTRICT'],
        'after_sales_cases.assigned_to' => ['active_assignment', 'SET NULL'],
        'after_sales_cases.closed_by' => ['immutable_history', 'SET NULL'],
        'after_sales_cases.opened_by' => ['immutable_history', 'RESTRICT'],
        'after_sales_messages.user_id' => ['immutable_history', 'RESTRICT'],
        'after_sales_status_history.actor_id' => ['immutable_history', 'RESTRICT'],
        'audit_logs.user_id' => ['immutable_history', 'SET NULL'],
        'automation_runs.triggered_by' => ['immutable_history', 'SET NULL'],
        'backup_runs.created_by' => ['immutable_history', 'SET NULL'],
        'bank_accounts.created_by' => ['immutable_history', 'SET NULL'],
        'bank_accounts.updated_by' => ['immutable_history', 'SET NULL'],
        'brands.created_by' => ['immutable_history', 'SET NULL'],
        'brands.updated_by' => ['immutable_history', 'SET NULL'],
        'catalog_sync_runs.started_by' => ['immutable_history', 'SET NULL'],
        'categories.created_by' => ['immutable_history', 'SET NULL'],
        'categories.updated_by' => ['immutable_history', 'SET NULL'],
        'commission_payment_batches.paid_by' => ['immutable_history', 'SET NULL'],
        'commission_status_history.changed_by' => ['immutable_history', 'SET NULL'],
        'courier_services.created_by' => ['immutable_history', 'SET NULL'],
        'courier_services.updated_by' => ['immutable_history', 'SET NULL'],
        'customer_crm_notes.author_user_id' => ['immutable_history', 'RESTRICT'],
        'customer_crm_notes.user_id' => ['immutable_history', 'CASCADE'],
        'data_quality_snapshots.run_by' => ['immutable_history', 'SET NULL'],
        'exchange_rate_history.updated_by' => ['immutable_history', 'SET NULL'],
        'field_service_teams.created_by' => ['immutable_history', 'SET NULL'],
        'field_service_teams.updated_by' => ['immutable_history', 'SET NULL'],
        'field_work_order_attachments.uploaded_by' => ['immutable_history', 'SET NULL'],
        'field_work_order_parts.created_by' => ['immutable_history', 'SET NULL'],
        'field_work_order_parts.updated_by' => ['immutable_history', 'SET NULL'],
        'field_work_orders.created_by' => ['immutable_history', 'SET NULL'],
        'field_work_orders.status_by' => ['immutable_history', 'SET NULL'],
        'field_work_orders.updated_by' => ['immutable_history', 'SET NULL'],
        'inventory_counts.created_by' => ['immutable_history', 'SET NULL'],
        'inventory_counts.finalized_by' => ['immutable_history', 'SET NULL'],
        'legacy_audit_logs.user_id' => ['immutable_history', 'SET NULL'],
        'mobile_devices.user_id' => ['access_record', 'CASCADE'],
        'mobile_push_outbox.user_id' => ['access_record', 'CASCADE'],
        'notification_preferences.user_id' => ['access_record', 'CASCADE'],
        'operational_alerts.user_id' => ['immutable_history', 'SET NULL'],
        'order_assignments.changed_by' => ['immutable_history', 'SET NULL'],
        'order_assignments.new_supplier_user_id' => ['immutable_history', 'CASCADE'],
        'order_assignments.old_supplier_user_id' => ['immutable_history', 'SET NULL'],
        'order_commissions.approved_by' => ['immutable_history', 'SET NULL'],
        'order_commissions.cancelled_by' => ['immutable_history', 'SET NULL'],
        'order_commissions.paid_by' => ['immutable_history', 'SET NULL'],
        'order_commissions.user_id' => ['immutable_history', 'NO ACTION'],
        'order_deliveries.confirmed_by' => ['immutable_history', 'SET NULL'],
        'order_documents.cancelled_by' => ['immutable_history', 'SET NULL'],
        'order_documents.issued_by' => ['immutable_history', 'SET NULL'],
        'order_email_outbox.recipient_user_id' => ['immutable_history', 'SET NULL'],
        'order_internal_notes.user_id' => ['immutable_history', 'SET NULL'],
        'order_payments.rejected_by' => ['immutable_history', 'SET NULL'],
        'order_payments.submitted_by' => ['immutable_history', 'SET NULL'],
        'order_payments.verified_by' => ['immutable_history', 'SET NULL'],
        'order_payments.voided_by' => ['immutable_history', 'SET NULL'],
        'order_shipments.recorded_by' => ['immutable_history', 'SET NULL'],
        'order_status_history.changed_by' => ['immutable_history', 'SET NULL'],
        'orders.accepted_by' => ['immutable_history', 'SET NULL'],
        'orders.assigned_by' => ['immutable_history', 'SET NULL'],
        'orders.cancelled_by' => ['immutable_history', 'SET NULL'],
        'orders.direct_sale_recorded_by' => ['immutable_history', 'SET NULL'],
        'orders.supplier_user_id' => ['active_assignment', 'SET NULL'],
        'orders.tracking_updated_by' => ['immutable_history', 'SET NULL'],
        'orders.updated_by' => ['immutable_history', 'SET NULL'],
        'orders.user_id' => ['order_owner', 'NO ACTION'],
        'password_reset_tokens.user_id' => ['access_record', 'CASCADE'],
        'portal_conversations.assigned_to' => ['active_assignment', 'SET NULL'],
        'portal_conversations.closed_by' => ['immutable_history', 'SET NULL'],
        'portal_conversations.created_by' => ['immutable_history', 'SET NULL'],
        'portal_conversations.user_id' => ['immutable_history', 'CASCADE'],
        'portal_messages.sender_id' => ['immutable_history', 'SET NULL'],
        'portal_order_link_history.changed_by' => ['immutable_history', 'SET NULL'],
        'portal_order_link_history.from_user_id' => ['immutable_history', 'SET NULL'],
        'portal_order_link_history.to_user_id' => ['immutable_history', 'SET NULL'],
        'product_lines.created_by' => ['immutable_history', 'SET NULL'],
        'product_lines.updated_by' => ['immutable_history', 'SET NULL'],
        'product_types.created_by' => ['immutable_history', 'SET NULL'],
        'product_types.updated_by' => ['immutable_history', 'SET NULL'],
        'product_warranties.created_by' => ['immutable_history', 'SET NULL'],
        'product_warranties.user_id' => ['immutable_history', 'CASCADE'],
        'product_warranties.voided_by' => ['immutable_history', 'SET NULL'],
        'products.created_by' => ['immutable_history', 'NO ACTION'],
        'products.updated_by' => ['immutable_history', 'SET NULL'],
        'receivable_cases.assigned_to' => ['active_assignment', 'SET NULL'],
        'receivable_cases.created_by' => ['immutable_history', 'SET NULL'],
        'receivable_cases.updated_by' => ['immutable_history', 'SET NULL'],
        'receivable_contacts.user_id' => ['immutable_history', 'SET NULL'],
        'report_schedules.created_by' => ['immutable_history', 'SET NULL'],
        'report_schedules.updated_by' => ['immutable_history', 'SET NULL'],
        'restore_credentials.user_id' => ['access_record', 'CASCADE'],
        'security_events.user_id' => ['immutable_history', 'SET NULL'],
        'service_part_movements.user_id' => ['immutable_history', 'SET NULL'],
        'service_part_purchase_requests.created_by' => ['immutable_history', 'SET NULL'],
        'service_part_purchase_requests.updated_by' => ['immutable_history', 'SET NULL'],
        'service_part_suppliers.created_by' => ['immutable_history', 'SET NULL'],
        'service_part_suppliers.updated_by' => ['immutable_history', 'SET NULL'],
        'service_parts.created_by' => ['immutable_history', 'SET NULL'],
        'service_parts.updated_by' => ['immutable_history', 'SET NULL'],
        'settings.updated_by' => ['immutable_history', 'SET NULL'],
        'specification_fields.created_by' => ['immutable_history', 'SET NULL'],
        'specification_fields.updated_by' => ['immutable_history', 'SET NULL'],
        'stock_movements.user_id' => ['immutable_history', 'SET NULL'],
        'stock_receipts.cancelled_by' => ['immutable_history', 'SET NULL'],
        'stock_receipts.created_by' => ['immutable_history', 'SET NULL'],
        'stock_receipts.posted_by' => ['immutable_history', 'SET NULL'],
        'system_health_snapshots.checked_by' => ['immutable_history', 'SET NULL'],
        'user_activation_tokens.created_by' => ['immutable_history', 'SET NULL'],
        'user_activation_tokens.user_id' => ['access_record', 'CASCADE'],
        'user_external_identities.user_id' => ['access_record', 'CASCADE'],
        'user_login_sessions.revoked_by' => ['immutable_history', 'SET NULL'],
        'user_login_sessions.user_id' => ['access_record', 'CASCADE'],
        'users.approved_by' => ['immutable_history', 'SET NULL'],
        'warranty_maintenance_records.completed_by' => ['immutable_history', 'SET NULL'],
        'warranty_rules.created_by' => ['immutable_history', 'SET NULL'],
        'warranty_rules.updated_by' => ['immutable_history', 'SET NULL'],
    ];

    /**
     * Legacy columns without a user FK: immutable audit provenance.
     * exchange_rate_history.triggered_by is a string, not a numeric user ID;
     * it is deliberately not auto-reassigned.
     *
     * @var list<string>
     */
    public const UNCONSTRAINED_HISTORY = [
        'exchange_rate_history.triggered_by',
        'orders.archived_by',
        'orders.completed_by',
        'orders.purged_by',
        'orders.reopened_by',
        'user_groups.created_by',
        'user_groups.updated_by',
    ];

    /**
     * InnoDB checks NO ACTION and RESTRICT identically, immediately.
     * MySQL can report either spelling for otherwise identical FK definitions
     * across migrated hosts. Normalize only this equivalence. Never
     * normalize CASCADE, SET NULL, or unknown actions into RESTRICT.
     */
    public static function canonicalMysqlDeleteRule(string $rule, string $engine): string
    {
        $rule = strtoupper(trim($rule));

        return strtoupper(trim($engine)) === 'INNODB' && $rule === 'NO ACTION' ? 'RESTRICT' : $rule;
    }

    /**
     * Fail closed on database drift. The application never hard-deletes users,
     * so CASCADE/SET NULL/RESTRICT constraints remain untouched.
     *
     * @return array{foreign_keys:int,unconstrained_history:int}
     */
    public function verifySchema(): array
    {
        abort_unless(
            in_array(DB::getDriverName(), ['mysql', 'mariadb'], true),
            503,
            'Bezbedno povlačenje naloga zahteva proverenu MySQL šemu.',
        );

        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE as k')
            ->join('information_schema.REFERENTIAL_CONSTRAINTS as r', static function ($join): void {
                $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')
                    ->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME')
                    ->on('r.TABLE_NAME', '=', 'k.TABLE_NAME');
            })
            ->join('information_schema.TABLES as t', static function ($join): void {
                $join->on('t.TABLE_SCHEMA', '=', 'k.TABLE_SCHEMA')
                    ->on('t.TABLE_NAME', '=', 'k.TABLE_NAME');
            })
            ->where('k.TABLE_SCHEMA', DB::getDatabaseName())
            ->where('k.REFERENCED_TABLE_NAME', 'users')
            ->where('k.REFERENCED_COLUMN_NAME', 'id')
            ->get(['k.TABLE_NAME', 'k.COLUMN_NAME', 'r.DELETE_RULE', 't.ENGINE']);

        $actual = [];
        foreach ($constraints as $constraint) {
            $key = (string) $constraint->TABLE_NAME.'.'.(string) $constraint->COLUMN_NAME;
            $actual[$key] = self::canonicalMysqlDeleteRule((string) $constraint->DELETE_RULE, (string) $constraint->ENGINE);
        }

        $expected = [];
        foreach (self::USER_FKS as $key => $policy) {
            $expected[$key] = self::canonicalMysqlDeleteRule($policy[1], 'InnoDB');
        }
        ksort($expected);
        ksort($actual);

        if (count($expected) !== 121 || $actual !== $expected) {
            Log::critical('User retirement schema gate mismatch', [
                'missing' => array_keys(array_diff_key($expected, $actual)),
                'unknown' => array_keys(array_diff_key($actual, $expected)),
                'changed_rules' => array_keys(array_diff_assoc(
                    array_intersect_key($actual, $expected),
                    array_intersect_key($expected, $actual),
                )),
            ]);
            abort(503, 'Bezbedno uklanjanje nije dostupno dok se ne proveri šema korisničkih relacija.');
        }

        foreach (self::UNCONSTRAINED_HISTORY as $key) {
            [$table, $column] = explode('.', $key, 2);
            if (!Schema::hasColumn($table, $column)) {
                Log::critical('User retirement missing historical actor column', ['column' => $key]);
                abort(503, 'Nedostaje istorijska evidencija potrebna za bezbedno uklanjanje.');
            }
        }

        return [
            'foreign_keys' => count($expected),
            'unconstrained_history' => count(self::UNCONSTRAINED_HISTORY),
        ];
    }
}

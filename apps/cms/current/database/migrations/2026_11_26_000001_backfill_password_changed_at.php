<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumns('users', ['id', 'created_at', 'password_changed_at'])
            || !Schema::hasTable('audit_logs') || !Schema::hasColumns('audit_logs', ['action', 'auditable_type', 'auditable_id'])
            || !Schema::hasTable('password_reset_tokens') || !Schema::hasColumns('password_reset_tokens', ['user_id', 'used_at'])
            || !Schema::hasTable('user_activation_tokens') || !Schema::hasColumns('user_activation_tokens', ['user_id', 'accepted_at'])) {
            return;
        }

        // A Google identity linked within five minutes can belong to either a Google signup or an admin-created account.
        // Users have no creation-source column. Role and status can change, and portal invitations and public signup also
        // start with random passwords. Keep NULL without positive evidence that a usable password was set. An issued
        // token alone is insufficient, and completed reset/activation records may already have been deleted.
        DB::table('users')
            ->whereNull('password_changed_at')
            ->whereNotNull('created_at')
            ->where(static function (Builder $query): void {
                $query->whereExists(static function (Builder $audit): void {
                    $audit->select('audit_logs.auditable_id')->from('audit_logs')
                        ->whereColumn('audit_logs.auditable_id', 'users.id')
                        ->where('audit_logs.auditable_type', 'App\\Models\\User')
                        ->whereIn('audit_logs.action', ['user.created', 'auth.password_reset.cli', 'account.password_changed']);
                })->orWhereExists(static function (Builder $reset): void {
                    $reset->select('password_reset_tokens.user_id')->from('password_reset_tokens')
                        ->whereColumn('password_reset_tokens.user_id', 'users.id')
                        ->whereNotNull('password_reset_tokens.used_at');
                })->orWhereExists(static function (Builder $activation): void {
                    $activation->select('user_activation_tokens.user_id')->from('user_activation_tokens')
                        ->whereColumn('user_activation_tokens.user_id', 'users.id')
                        ->whereNotNull('user_activation_tokens.accepted_at');
                });
            })
            ->update(['password_changed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // A rollback cannot distinguish backfilled dates from dates written by the application.
    }
};

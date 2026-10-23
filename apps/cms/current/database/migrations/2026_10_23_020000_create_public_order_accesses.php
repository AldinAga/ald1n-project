<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('public_order_accesses')) {
            foreach (['order_id', 'token_hash', 'checkout_key_hash', 'request_hash', 'guest_email',
                'terms_snapshot_hash', 'consent_at', 'expires_at', 'revoked_at'] as $column) {
                if (!Schema::hasColumn('public_order_accesses', $column)) {
                    throw new RuntimeException('PUBLIC_ORDER_ACCESS_SCHEMA_INCOMPLETE: '.$column);
                }
            }
            return;
        }
        foreach (['orders', 'products', 'order_items', 'stock_movements', 'users'] as $table) {
            if (!Schema::hasTable($table)) throw new RuntimeException('PUBLIC_ORDER_REQUIRED_TABLE_MISSING: '.$table);
        }
        $old = (int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS n')->n;
        DB::statement('SET SESSION lock_wait_timeout = 5');
        try {
            Schema::create('public_order_accesses', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id')->unique();
                $table->char('token_hash', 64)->unique();
                $table->char('checkout_key_hash', 64)->unique();
                $table->char('request_hash', 64);
                $table->text('guest_email'); // encrypted at the Eloquent model boundary
                $table->char('terms_snapshot_hash', 64);
                $table->timestamp('consent_at');
                $table->timestamp('expires_at');
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
                $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
                $table->index(['expires_at', 'revoked_at']);
            });
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.max(1, $old));
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('public_order_accesses')) return;
        if (DB::table('public_order_accesses')->exists()) {
            throw new RuntimeException('PUBLIC_ORDER_CAPABILITIES_EXIST: preserve owner access evidence');
        }
        Schema::drop('public_order_accesses');
    }
};

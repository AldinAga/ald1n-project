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
        if (!Schema::hasTable('orders') || !Schema::hasTable('public_order_accesses')) {
            throw new RuntimeException('PUBLIC_CHECKOUT_DEPENDENCIES_INCOMPLETE');
        }
        if (Schema::hasTable('public_checkout_attempts')) {
            foreach (['key_hash','request_fingerprint','receipt_token_ciphertext','order_id'] as $column) {
                if (!Schema::hasColumn('public_checkout_attempts',$column)) {
                    throw new RuntimeException('PUBLIC_CHECKOUT_PARTIAL_TABLE '.$column);
                }
            }
            return;
        }
        $old=(int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS v')->v;
        DB::statement('SET SESSION lock_wait_timeout = 5');
        try {
            Schema::create('public_checkout_attempts', static function (Blueprint $t): void {
                $t->id();
                $t->char('key_hash',64)->unique();
                $t->char('request_fingerprint',64);
                $t->text('receipt_token_ciphertext')->nullable();
                $t->unsignedBigInteger('order_id')->nullable()->unique();
                $t->timestamps();
                $t->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            });
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.max(1,$old));
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('public_checkout_attempts')) return;
        if (DB::table('public_checkout_attempts')->exists()) {
            throw new RuntimeException('PUBLIC_CHECKOUT_EVIDENCE_EXISTS');
        }
        Schema::drop('public_checkout_attempts');
    }
};

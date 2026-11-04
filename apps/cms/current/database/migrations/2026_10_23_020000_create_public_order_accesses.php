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
        if (!Schema::hasTable('orders')) {
            throw new RuntimeException('PUBLIC_ORDER_ACCESS_REQUIRES_EXISTING_ORDERS');
        }
        if (Schema::hasTable('public_order_accesses')) {
            foreach (['order_id','token_hash','expires_at','revoked_at','email_ciphertext'] as $column) {
                if (!Schema::hasColumn('public_order_accesses',$column)) {
                    throw new RuntimeException('PUBLIC_ORDER_ACCESS_PARTIAL_TABLE '.$column);
                }
            }
            return;
        }
        $timeout=(int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS v')->v;
        DB::statement('SET SESSION lock_wait_timeout = 5');
        try {
            Schema::create('public_order_accesses', static function (Blueprint $t): void {
                $t->id();
                $t->unsignedBigInteger('order_id')->unique();
                $t->char('token_hash',64)->unique();
                $t->text('email_ciphertext');
                $t->dateTime('expires_at');
                $t->dateTime('revoked_at')->nullable();
                $t->timestamps();
                $t->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
                $t->index(['expires_at','revoked_at']);
            });
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.max(1,$timeout));
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('public_order_accesses')) return;
        if (DB::table('public_order_accesses')->exists()) {
            throw new RuntimeException('PUBLIC_ORDER_ACCESS_HISTORY_CANNOT_BE_DROPPED');
        }
        Schema::drop('public_order_accesses');
    }
};

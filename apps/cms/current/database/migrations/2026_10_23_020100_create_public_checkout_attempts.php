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
            foreach (['key_hash','request_fingerprint','receipt_token_ciphertext','order_id',
                'terms_url','privacy_url','shipping_description','shipping_fee_rsd','merchant_policy_digest','terms_accepted_at'] as $column) {
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
                // Immutable checkout evidence is kept separately from mutable
                // site policy settings and from the shopper's receipt URL.
                $t->string('terms_url',2048)->nullable();
                $t->string('privacy_url',2048)->nullable();
                $t->string('shipping_description',500)->nullable();
                $t->decimal('shipping_fee_rsd',12,2)->nullable();
                $t->char('merchant_policy_digest',64)->nullable();
                $t->dateTime('terms_accepted_at')->nullable();
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

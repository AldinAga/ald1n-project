<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Zahtevi za brisanje naloga. Namerno BEZ stranog kljuca na users: politika povlacenja
// korisnika (UserRetirementRelationPolicy) fail-closed proverava tacno 121 FK ka users.
// Tabela ne sadrzi lične podatke: samo user_id, vremena, status i SHA-256 hash tokena.
// status: pending | cancelled | waiting_obligations | completed.
// pending bez grace_until = ceka potvrdu e-mailom (web); sa grace_until = rok mirovanja traje.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('account_deletion_requests')) {
            return;
        }

        Schema::create('account_deletion_requests', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('requested_at');
            $table->string('status', 24)->default('pending');
            $table->dateTime('grace_until')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('source', 10)->default('app');
            $table->char('confirm_token_hash', 64)->nullable();
            $table->dateTime('confirm_expires_at')->nullable();
            $table->char('cancel_token_hash', 64)->nullable();
            $table->dateTime('owner_notified_at')->nullable();
            // 1 = nalog je bio aktivan i blokirao ga je sam zahtjev; 0 = administrator je preuzeo blokadu.
            $table->boolean('blocked_by_request')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'status'], 'account_deletion_user_status_index');
            $table->index(['status', 'grace_until'], 'account_deletion_due_index');
            $table->index('confirm_token_hash', 'account_deletion_confirm_index');
            $table->index('cancel_token_hash', 'account_deletion_cancel_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};

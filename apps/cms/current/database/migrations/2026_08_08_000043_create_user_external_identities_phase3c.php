<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_external_identities')) {
            return;
        }

        Schema::create('user_external_identities', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_subject', 255);
            $table->string('provider_email', 190)->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_subject'], 'external_identity_provider_subject_unique');
            $table->unique(['user_id', 'provider'], 'external_identity_user_provider_unique');
            $table->index(['provider', 'provider_email'], 'external_identity_provider_email_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_external_identities');
    }
};

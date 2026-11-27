<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kurs EUR/RSD na dan isplate (odluka vlasnika 44): beleži se po isplati i štampa na PDF potvrdi.
// Nullable, bez indeksa; kod radi i dok kolona ne postoji.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commission_payment_batches') && !Schema::hasColumn('commission_payment_batches', 'eur_rsd_rate')) {
            Schema::table('commission_payment_batches', static function (Blueprint $table): void {
                $table->decimal('eur_rsd_rate', 12, 6)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('commission_payment_batches', 'eur_rsd_rate')) {
            Schema::table('commission_payment_batches', static function (Blueprint $table): void {
                $table->dropColumn('eur_rsd_rate');
            });
        }
    }
};

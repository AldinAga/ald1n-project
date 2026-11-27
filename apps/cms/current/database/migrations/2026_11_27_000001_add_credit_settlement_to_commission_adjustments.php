<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('commission_adjustments', 'settled_at')) {
            Schema::table('commission_adjustments', static function (Blueprint $table): void {
                $table->dateTime('settled_at')->nullable();
            });
        }

        if (!Schema::hasColumn('commission_adjustments', 'settled_by')) {
            Schema::table('commission_adjustments', static function (Blueprint $table): void {
                $table->unsignedBigInteger('settled_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Zadrži podatke o evidentiranim isplatama kredita.
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Pošiljka vraćena" (odluka vlasnika 16.11.2026, tačka 16): zapis o slanju ostaje sačuvan i obeležava se kao vraćen
 * (razlog obavezan), a porudžbina se vraća u "Potvrđena". Samo dodatne, nullable kolone; aplikacija radi i pre migracije
 * (OrderShipment::hasReturnColumns()). Author: Ald1n <https://ald1n.com>
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('order_shipments')) {
            return;
        }

        Schema::table('order_shipments', static function (Blueprint $table): void {
            if (!Schema::hasColumn('order_shipments', 'returned_at')) {
                $table->dateTime('returned_at')->nullable();
            }
            if (!Schema::hasColumn('order_shipments', 'returned_by')) {
                $table->unsignedBigInteger('returned_by')->nullable();
            }
            if (!Schema::hasColumn('order_shipments', 'return_reason')) {
                $table->text('return_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('order_shipments')) {
            return;
        }

        Schema::table('order_shipments', static function (Blueprint $table): void {
            foreach (['returned_at', 'returned_by', 'return_reason'] as $column) {
                if (Schema::hasColumn('order_shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

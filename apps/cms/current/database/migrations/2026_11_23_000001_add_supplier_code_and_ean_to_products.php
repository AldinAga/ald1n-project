<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Šifra dobavljača i EAN na artiklu (odluka vlasnika). Samo dodatne, nullable kolone bez unique ograničenja;
 * aplikacija radi i pre migracije (Product::hasIdentifierColumns()). Author: Ald1n <https://ald1n.com>
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', static function (Blueprint $table): void {
            if (!Schema::hasColumn('products', 'supplier_code')) {
                $table->string('supplier_code', 100)->nullable();
                $table->index('supplier_code', 'products_supplier_code_idx');
            }
            if (!Schema::hasColumn('products', 'ean')) {
                $table->string('ean', 32)->nullable();
                $table->index('ean', 'products_ean_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', static function (Blueprint $table): void {
            foreach (['supplier_code' => 'products_supplier_code_idx', 'ean' => 'products_ean_idx'] as $column => $index) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropIndex($index);
                    $table->dropColumn($column);
                }
            }
        });
    }
};

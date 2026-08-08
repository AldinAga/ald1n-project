<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('order_documents') || !Schema::hasColumn('order_documents', 'document_type')) {
            return;
        }

        // Starije instalacije su kreirale document_type kao ENUM sa samo tri
        // vrednosti. Otpremnica koristi delivery_note, pa MySQL odbija INSERT.
        // VARCHAR omogućava nove tipove dok aplikacija i dalje strogo validira
        // dozvoljene vrednosti pre upisa.
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("UPDATE `order_documents` SET `document_type` = 'order_confirmation' WHERE `document_type` IS NULL OR `document_type` = ''");
            DB::statement('ALTER TABLE `order_documents` MODIFY `document_type` VARCHAR(40) NOT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("UPDATE order_documents SET document_type = 'order_confirmation' WHERE document_type IS NULL OR document_type = ''");
            DB::statement('ALTER TABLE order_documents ALTER COLUMN document_type TYPE VARCHAR(40)');
            DB::statement('ALTER TABLE order_documents ALTER COLUMN document_type SET NOT NULL');
        }

        // SQLite već mapira Laravel enum na tekstualnu kolonu, pa promena nije potrebna.
    }

    public function down(): void
    {
        // Namerno nema vraćanja na ENUM: postojeći delivery_note zapisi bi bili
        // odsečeni ili izgubljeni. Šira VARCHAR kolona je bezbedna i unazad kompatibilna.
    }
};

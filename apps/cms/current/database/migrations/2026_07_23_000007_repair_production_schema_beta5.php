<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        /** @var Migration $operations */
        $operations = require __DIR__.'/2026_07_22_000005_create_legacy_operations_tables.php';
        $operations->up();

        /** @var Migration $production */
        $production = require __DIR__.'/2026_07_22_000006_enable_production_orders_inventory.php';
        $production->up();
    }

    public function down(): void
    {
        // Repair migracija je namerno nepovratna: ne briše produkcione podatke niti tabele.
    }
};

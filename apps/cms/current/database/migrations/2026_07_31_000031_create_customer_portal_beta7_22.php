<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('notification_preferences')) {
            return;
        }

        $columns = [
            'document_updates' => static fn (Blueprint $table) => $table->boolean('document_updates')->default(true),
            'after_sales_updates' => static fn (Blueprint $table) => $table->boolean('after_sales_updates')->default(true),
            'warranty_updates' => static fn (Blueprint $table) => $table->boolean('warranty_updates')->default(true),
            'service_updates' => static fn (Blueprint $table) => $table->boolean('service_updates')->default(true),
            'receivable_updates' => static fn (Blueprint $table) => $table->boolean('receivable_updates')->default(true),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('notification_preferences', $column)) {
                continue;
            }
            Schema::table('notification_preferences', static function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }
    }

    public function down(): void
    {
        // Korisničke preference se namerno ne uklanjaju pri rollback-u.
    }
};

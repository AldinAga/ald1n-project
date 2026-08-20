<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'archived_at')) {
            Schema::table('orders', static function (Blueprint $table): void {
                $table->timestamp('archived_at')->nullable();
            });
        }

        if (!Schema::hasColumn('orders', 'archived_by')) {
            Schema::table('orders', static function (Blueprint $table): void {
                $table->unsignedBigInteger('archived_by')->nullable();
            });
        }

        if (!Schema::hasColumn('orders', 'archive_reason')) {
            Schema::table('orders', static function (Blueprint $table): void {
                $table->text('archive_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['archive_reason', 'archived_by', 'archived_at'],
            static fn (string $column): bool => Schema::hasColumn('orders', $column),
        ));

        if ($columns !== []) {
            Schema::table('orders', static function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};

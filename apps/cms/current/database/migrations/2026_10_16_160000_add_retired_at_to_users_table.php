<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->timestamp('retired_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        // Never silently erase a live retirement marker during a routine
        // rollback. Retired users remain blocked; owners must assess reversal
        // separately if any records were already retired.
        if (Schema::hasColumn('users', 'retired_at')
            && DB::table('users')->whereNotNull('retired_at')->exists()) {
            throw new \RuntimeException(
                'Rollback HOLD: retired_at contains irreversible retirement records.'
            );
        }

        Schema::table('users', static function (Blueprint $table): void {
            $table->dropColumn('retired_at');
        });
    }
};

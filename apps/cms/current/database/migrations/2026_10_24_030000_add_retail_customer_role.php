<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) throw new RuntimeException('B802_ROLES_TABLE_MISSING');
        if (!DB::table('roles')->exists()) return; // Fresh install is populated by CoreAccessSeeder.
        if (!DB::table('roles')->where('slug','user')->exists()) throw new RuntimeException('B802_LEGACY_ROLE_MISSING');
        DB::transaction(static function (): void {
            // Relabel only: preserve user slug, IDs, users, groups and all history.
            DB::table('roles')->where('slug','user')->update(['name'=>'SubAgent']);
            DB::table('roles')->updateOrInsert(['slug'=>'customer'],['name'=>'Kupac']);
        });
    }
    public function down(): void
    {
        // Intentionally non-destructive: never delete a role referenced by buyers,
        // reassign existing users or relabel historical financial ownership.
    }
};

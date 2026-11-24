<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('user_group_permissions')) return;

        $slug = 'catalog.view_notes';
        $values = array_filter([
            'name' => 'Pregled napomena artikla',
            'description' => 'Napomene artikla u API-ju za prijavljene SubAgente.',
            'sort_order' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ], static fn ($v, $column): bool => Schema::hasColumn('permissions', $column), ARRAY_FILTER_USE_BOTH);

        DB::table('permissions')->insertOrIgnore(['slug' => $slug] + $values);
        $id = DB::table('permissions')->where('slug', $slug)->value('id');
        if ($id === null) return;

        // Odluka vlasnika 24.11.: svi SubAgenti koji vide katalog vide i napomene (admin/superadmin dobijaju sve dozvole automatski).
        $viewId = DB::table('permissions')->where('slug', 'catalog.view')->value('id');
        if ($viewId === null) return;
        $withTimestamp = Schema::hasColumn('user_group_permissions', 'created_at');
        foreach (DB::table('user_group_permissions')->where('permission_id', $viewId)->pluck('group_id') as $groupId) {
            DB::table('user_group_permissions')->insertOrIgnore(
                ['group_id' => $groupId, 'permission_id' => $id] + ($withTimestamp ? ['created_at' => now()] : []),
            );
        }
    }

    public function down(): void
    {
        // Dozvola i dodele se namerno ne brišu (audit istorija).
    }
};

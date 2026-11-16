<?php

declare(strict_types=1);

use App\Services\CommissionAutoApprovalService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Jednokratni, idempotentni prenos: pending provizije čija porudžbina je već plaćena ili isporučena postaju
// "Za isplatu" (approved, approved_by NULL). Odobrene i isplaćene se ne diraju. Bez obaveštenja korisnicima.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_commissions') || !Schema::hasTable('orders') || !Schema::hasColumn('orders', 'payment_state')) {
            return;
        }
        if (!DB::table('order_commissions')->where('status', 'pending')->exists()) {
            return;
        }

        app(CommissionAutoApprovalService::class)->backfill();
    }

    public function down(): void
    {
        // Namerno prazno: istorija (commission_status_history, audit log) čuva trag prenosa.
    }
};

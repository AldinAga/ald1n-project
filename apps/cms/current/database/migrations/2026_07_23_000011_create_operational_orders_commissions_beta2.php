<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->extendOrders();
        $this->extendCommissions();
        $this->extendCommissionHistory();
        $this->createOrderInternalNotes();
        $this->createOrderAssignments();
        $this->createCommissionPaymentBatches();
        $this->createNotifications();
        $this->seedPermissions();
    }

    public function down(): void
    {
        // Production-safe migration: no destructive rollback of operational history.
    }

    private function extendOrders(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $missing = [
            'assigned_by' => !Schema::hasColumn('orders', 'assigned_by'),
            'reassigned_at' => !Schema::hasColumn('orders', 'reassigned_at'),
            'accepted_by' => !Schema::hasColumn('orders', 'accepted_by'),
            'accepted_at' => !Schema::hasColumn('orders', 'accepted_at'),
            'expected_processing_at' => !Schema::hasColumn('orders', 'expected_processing_at'),
            'expected_shipping_at' => !Schema::hasColumn('orders', 'expected_shipping_at'),
            'last_internal_note_at' => !Schema::hasColumn('orders', 'last_internal_note_at'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('orders', static function (Blueprint $table) use ($missing): void {
                if ($missing['assigned_by']) $table->unsignedBigInteger('assigned_by')->nullable();
                if ($missing['reassigned_at']) $table->dateTime('reassigned_at')->nullable();
                if ($missing['accepted_by']) $table->unsignedBigInteger('accepted_by')->nullable();
                if ($missing['accepted_at']) $table->dateTime('accepted_at')->nullable();
                if ($missing['expected_processing_at']) $table->dateTime('expected_processing_at')->nullable();
                if ($missing['expected_shipping_at']) $table->dateTime('expected_shipping_at')->nullable();
                if ($missing['last_internal_note_at']) $table->dateTime('last_internal_note_at')->nullable();
            });
        }

        foreach ([
            ['assigned_by', 'users'],
            ['accepted_by', 'users'],
        ] as [$column, $tableName]) {
            if (!Schema::hasColumn('orders', $column)) continue;
            try {
                Schema::table('orders', static function (Blueprint $table) use ($column, $tableName): void {
                    $table->foreign($column)->references('id')->on($tableName)->nullOnDelete();
                });
            } catch (Throwable) {
                // Foreign key already exists on repaired installations.
            }
        }

        try {
            Schema::table('orders', static function (Blueprint $table): void {
                $table->index(['supplier_user_id', 'accepted_at', 'status', 'created_at'], 'orders_attention_queue_index');
            });
        } catch (Throwable) {
            // Index already exists.
        }
    }

    private function extendCommissions(): void
    {
        if (!Schema::hasTable('order_commissions')) {
            return;
        }

        $missing = [
            'payment_batch_id' => !Schema::hasColumn('order_commissions', 'payment_batch_id'),
            'payment_method' => !Schema::hasColumn('order_commissions', 'payment_method'),
            'payment_reference' => !Schema::hasColumn('order_commissions', 'payment_reference'),
            'status_updated_at' => !Schema::hasColumn('order_commissions', 'status_updated_at'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('order_commissions', static function (Blueprint $table) use ($missing): void {
                if ($missing['payment_batch_id']) $table->unsignedBigInteger('payment_batch_id')->nullable();
                if ($missing['payment_method']) $table->string('payment_method', 50)->nullable();
                if ($missing['payment_reference']) $table->string('payment_reference', 190)->nullable();
                if ($missing['status_updated_at']) $table->dateTime('status_updated_at')->nullable();
            });
        }

        try {
            Schema::table('order_commissions', static function (Blueprint $table): void {
                $table->index(['payment_batch_id', 'status'], 'order_commissions_batch_status_index');
            });
        } catch (Throwable) {
            // Index already exists.
        }

        DB::table('order_commissions')->whereNull('status_updated_at')->update([
            'status_updated_at' => DB::raw('COALESCE(updated_at, created_at)'),
        ]);
    }

    private function extendCommissionHistory(): void
    {
        if (!Schema::hasTable('commission_status_history')) {
            return;
        }

        if (!Schema::hasColumn('commission_status_history', 'metadata_json')) {
            Schema::table('commission_status_history', static function (Blueprint $table): void {
                $table->json('metadata_json')->nullable();
            });
        }
    }

    private function createOrderInternalNotes(): void
    {
        if (Schema::hasTable('order_internal_notes')) {
            return;
        }

        Schema::create('order_internal_notes', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note');
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['order_id', 'created_at']);
        });
    }

    private function createOrderAssignments(): void
    {
        if (Schema::hasTable('order_assignments')) {
            return;
        }

        Schema::create('order_assignments', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('old_supplier_user_id')->nullable();
            $table->unsignedBigInteger('new_supplier_user_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('reason', 1000);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('old_supplier_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('new_supplier_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['order_id', 'created_at']);
            $table->index(['new_supplier_user_id', 'created_at']);
        });
    }

    private function createCommissionPaymentBatches(): void
    {
        if (!Schema::hasTable('commission_payment_batches')) {
            Schema::create('commission_payment_batches', static function (Blueprint $table): void {
                $table->id();
                $table->string('batch_number', 50)->unique();
                $table->string('payment_method', 50);
                $table->string('payment_reference', 190)->nullable();
                $table->text('note')->nullable();
                $table->unsignedInteger('commission_count')->default(0);
                $table->decimal('total_eur', 14, 2)->default(0);
                $table->unsignedBigInteger('paid_by')->nullable();
                $table->dateTime('paid_at');
                $table->timestamps();
                $table->foreign('paid_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['paid_at', 'paid_by']);
            });
        }

        if (Schema::hasTable('order_commissions') && Schema::hasColumn('order_commissions', 'payment_batch_id')) {
            try {
                Schema::table('order_commissions', static function (Blueprint $table): void {
                    $table->foreign('payment_batch_id')->references('id')->on('commission_payment_batches')->nullOnDelete();
                });
            } catch (Throwable) {
                // Foreign key already exists or the hosting database does not support online alteration.
            }
        }
    }

    private function createNotifications(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_id', 'read_at', 'created_at'], 'notifications_inbox_index');
        });
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $rows = [
            ['name' => 'Pregled svojih provizija', 'slug' => 'commissions.view_own', 'description' => 'Korisnik prati obračun, odobrenje i isplatu svojih provizija.', 'sort_order' => 47],
            ['name' => 'Ponovna dodela porudžbine', 'slug' => 'orders.reassign', 'description' => 'SuperAdministrator dodeljuje porudžbinu drugom odgovornom licu.', 'sort_order' => 104],
            ['name' => 'Interne napomene porudžbine', 'slug' => 'orders.internal_notes', 'description' => 'Administratorske interne napomene koje korisnik ne vidi.', 'sort_order' => 105],
            ['name' => 'Pregled obaveštenja', 'slug' => 'notifications.view', 'description' => 'Pregled poslovnih obaveštenja u aplikaciji.', 'sort_order' => 48],
        ];

        foreach ($rows as $row) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $row['slug']],
                $row + ['created_at' => now()],
            );
        }

        if (!Schema::hasTable('user_group_permissions') || !Schema::hasTable('user_groups')) {
            return;
        }

        $groupIds = DB::table('user_groups')->where('status', 'active')->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', ['commissions.view_own', 'notifications.view'])->pluck('id');
        foreach ($groupIds as $groupId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('user_group_permissions')->insertOrIgnore([
                    'group_id' => $groupId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                ]);
            }
        }
    }
};

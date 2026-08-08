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
        $this->createDeliveries();
        $this->extendDocuments();
        $this->seedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('order_documents')) {
            $columns = array_values(array_filter([
                'delivery_method_snapshot',
                'delivery_recipient_snapshot',
                'delivered_at_snapshot',
                'delivery_reference_snapshot',
                'delivery_note_snapshot',
            ], static fn (string $column): bool => Schema::hasColumn('order_documents', $column)));

            if ($columns !== []) {
                Schema::table('order_documents', static function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }

        Schema::dropIfExists('order_deliveries');

        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', static function (Blueprint $table): void {
                    $table->dropIndex('orders_reopen_state_index');
                });
            } catch (\Throwable) {
                // Indeks možda ne postoji na delimično nadograđenoj instalaciji.
            }

            $columns = array_values(array_filter([
                'reopened_at', 'reopened_by', 'reopen_reason',
            ], static fn (string $column): bool => Schema::hasColumn('orders', $column)));

            if ($columns !== []) {
                Schema::table('orders', static function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    private function extendOrders(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $missing = [
            'reopened_at' => !Schema::hasColumn('orders', 'reopened_at'),
            'reopened_by' => !Schema::hasColumn('orders', 'reopened_by'),
            'reopen_reason' => !Schema::hasColumn('orders', 'reopen_reason'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('orders', static function (Blueprint $table) use ($missing): void {
                if ($missing['reopened_at']) $table->dateTime('reopened_at')->nullable();
                if ($missing['reopened_by']) $table->unsignedBigInteger('reopened_by')->nullable();
                if ($missing['reopen_reason']) $table->string('reopen_reason', 1000)->nullable();
            });
        }

        try {
            Schema::table('orders', static function (Blueprint $table): void {
                $table->index(['reopened_at', 'completed_at'], 'orders_reopen_state_index');
            });
        } catch (\Throwable) {
            // Indeks možda već postoji.
        }
    }

    private function createDeliveries(): void
    {
        if (Schema::hasTable('order_deliveries')) {
            return;
        }

        Schema::create('order_deliveries', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('delivery_method', 40)->default('own_transport');
            $table->dateTime('delivered_at');
            $table->string('recipient_name', 190);
            $table->string('recipient_phone', 60)->nullable();
            $table->string('reference', 190)->nullable();
            $table->text('note')->nullable();
            $table->string('proof_disk', 40)->nullable();
            $table->string('proof_path', 500)->nullable();
            $table->string('proof_original_name', 255)->nullable();
            $table->string('proof_mime_type', 120)->nullable();
            $table->unsignedBigInteger('proof_size')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['delivered_at', 'delivery_method']);
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function extendDocuments(): void
    {
        if (!Schema::hasTable('order_documents')) {
            return;
        }

        $missing = [
            'delivery_method_snapshot' => !Schema::hasColumn('order_documents', 'delivery_method_snapshot'),
            'delivery_recipient_snapshot' => !Schema::hasColumn('order_documents', 'delivery_recipient_snapshot'),
            'delivered_at_snapshot' => !Schema::hasColumn('order_documents', 'delivered_at_snapshot'),
            'delivery_reference_snapshot' => !Schema::hasColumn('order_documents', 'delivery_reference_snapshot'),
            'delivery_note_snapshot' => !Schema::hasColumn('order_documents', 'delivery_note_snapshot'),
        ];

        if (!in_array(true, $missing, true)) {
            return;
        }

        Schema::table('order_documents', static function (Blueprint $table) use ($missing): void {
            if ($missing['delivery_method_snapshot']) $table->string('delivery_method_snapshot', 40)->nullable();
            if ($missing['delivery_recipient_snapshot']) $table->string('delivery_recipient_snapshot', 190)->nullable();
            if ($missing['delivered_at_snapshot']) $table->dateTime('delivered_at_snapshot')->nullable();
            if ($missing['delivery_reference_snapshot']) $table->string('delivery_reference_snapshot', 190)->nullable();
            if ($missing['delivery_note_snapshot']) $table->text('delivery_note_snapshot')->nullable();
        });
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach ([
            [
                'slug' => 'orders.confirm_delivery',
                'name' => 'Potvrda isporuke',
                'description' => 'Evidentiranje primaoca, datuma, načina i dokaza isporuke.',
                'sort_order' => 106,
            ],
            [
                'slug' => 'orders.reopen',
                'name' => 'Ponovno otvaranje porudžbine',
                'description' => 'SuperAdministrator ponovo otvara greškom kompletiranu porudžbinu uz obavezan razlog.',
                'sort_order' => 107,
            ],
        ] as $row) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $row['slug']],
                $row + ['created_at' => now()],
            );
        }
    }
};

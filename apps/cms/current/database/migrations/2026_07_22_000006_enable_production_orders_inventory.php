<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->repairOrdersTable();
        $this->repairStockMovementsTable();

        if (!Schema::hasTable('idempotency_keys')) {
            Schema::create('idempotency_keys', function (Blueprint $table): void {
                $table->id();
                $table->string('scope', 100);
                $table->string('actor_key', 100);
                $table->char('key_hash', 64);
                $table->char('request_hash', 64);
                $table->string('status', 20)->default('processing');
                $table->string('response_type', 100)->nullable();
                $table->unsignedBigInteger('response_id')->nullable();
                $table->dateTime('locked_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->timestamps();
                $table->unique(['scope', 'actor_key', 'key_hash'], 'idempotency_scope_actor_key_unique');
                $table->index(['status', 'expires_at']);
            });
        }
    }

    private function repairOrdersTable(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        if (!Schema::hasColumn('orders', 'source_system')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('source_system', 20)->default('legacy')->after('id');
            });
        }
        if (!Schema::hasColumn('orders', 'idempotency_key_hash')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->char('idempotency_key_hash', 64)->nullable()->after('order_number');
            });
        }
        if (!Schema::hasColumn('orders', 'request_fingerprint')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->char('request_fingerprint', 64)->nullable()->after('idempotency_key_hash');
            });
        }
        if (!Schema::hasColumn('orders', 'inventory_state')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('inventory_state', 20)->default('none')->after('status');
            });
        }
        if (!Schema::hasColumn('orders', 'inventory_reserved_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dateTime('inventory_reserved_at')->nullable()->after('inventory_state');
            });
        }
        if (!Schema::hasColumn('orders', 'inventory_returned_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dateTime('inventory_returned_at')->nullable()->after('inventory_reserved_at');
            });
        }
        if (!Schema::hasColumn('orders', 'cancelled_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dateTime('cancelled_at')->nullable()->after('inventory_returned_at');
            });
        }
        if (!Schema::hasColumn('orders', 'cancelled_by')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
                $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('orders', 'updated_by')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->unsignedBigInteger('updated_by')->nullable()->after('tracking_updated_by');
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }
        if (!$this->hasIndex('orders', 'orders_source_system_index')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->index('source_system');
            });
        }
        if (!$this->hasIndex('orders', 'orders_inventory_state_index')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->index('inventory_state');
            });
        }
        if (!$this->hasIndex('orders', 'orders_user_idempotency_unique')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->unique(['user_id', 'idempotency_key_hash'], 'orders_user_idempotency_unique');
            });
        }
    }

    private function repairStockMovementsTable(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        if (!Schema::hasColumn('stock_movements', 'event_key')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->string('event_key', 190)->nullable()->after('id');
            });
        }
        if (!Schema::hasColumn('stock_movements', 'source')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->string('source', 30)->default('system')->after('movement_type');
            });
        }
        if (!Schema::hasColumn('stock_movements', 'metadata_json')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->json('metadata_json')->nullable()->after('note');
            });
        }
        if (!$this->hasIndex('stock_movements', 'stock_movements_event_key_unique')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->unique('event_key');
            });
        }
        if (!$this->hasIndex('stock_movements', 'stock_movements_source_index')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->index('source');
            });
        }
        if (!$this->hasIndex('stock_movements', 'stock_movements_order_id_movement_type_index')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->index(['order_id', 'movement_type']);
            });
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        try {
            foreach (Schema::getIndexes($table) as $index) {
                if (($index['name'] ?? null) === $name) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // Ako drajver ne podržava introspekciju indeksa, migracija će pokušati da ga doda.
        }

        return false;
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->dropIndex(['order_id', 'movement_type']);
                $table->dropUnique(['event_key']);
                $table->dropIndex(['source']);
                $table->dropColumn(['event_key', 'source', 'metadata_json']);
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropForeign(['cancelled_by']);
                $table->dropForeign(['updated_by']);
                $table->dropUnique('orders_user_idempotency_unique');
                $table->dropIndex(['source_system']);
                $table->dropIndex(['inventory_state']);
                $table->dropColumn([
                    'source_system', 'idempotency_key_hash', 'request_fingerprint', 'inventory_state',
                    'inventory_reserved_at', 'inventory_returned_at', 'cancelled_at', 'cancelled_by', 'updated_by',
                ]);
            });
        }
    }
};

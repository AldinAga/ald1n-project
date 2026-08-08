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
        $this->createDocumentCounters();
        $this->createOrderDocuments();
        $this->seedAccessPermissions();
        $this->backfillSupplierAssignments();
    }

    public function down(): void
    {
        Schema::dropIfExists('order_documents');
        Schema::dropIfExists('document_counters');

        if (Schema::hasTable('orders')) {
            $columns = array_values(array_filter([
                'supplier_user_id', 'supplier_name_snapshot', 'supplier_email_snapshot',
                'supplier_phone_snapshot', 'supplier_role_snapshot', 'assigned_at',
            ], static fn (string $column): bool => Schema::hasColumn('orders', $column)));

            if ($columns !== []) {
                Schema::table('orders', static function (Blueprint $table) use ($columns): void {
                    if (in_array('supplier_user_id', $columns, true)) {
                        try { $table->dropForeign(['supplier_user_id']); } catch (\Throwable) {}
                        try { $table->dropIndex('orders_supplier_status_created_index'); } catch (\Throwable) {}
                    }
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
            'supplier_user_id' => !Schema::hasColumn('orders', 'supplier_user_id'),
            'supplier_name_snapshot' => !Schema::hasColumn('orders', 'supplier_name_snapshot'),
            'supplier_email_snapshot' => !Schema::hasColumn('orders', 'supplier_email_snapshot'),
            'supplier_phone_snapshot' => !Schema::hasColumn('orders', 'supplier_phone_snapshot'),
            'supplier_role_snapshot' => !Schema::hasColumn('orders', 'supplier_role_snapshot'),
            'assigned_at' => !Schema::hasColumn('orders', 'assigned_at'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('orders', static function (Blueprint $table) use ($missing): void {
                if ($missing['supplier_user_id']) $table->unsignedBigInteger('supplier_user_id')->nullable()->after('user_id');
                if ($missing['supplier_name_snapshot']) $table->string('supplier_name_snapshot', 190)->nullable()->after('supplier_user_id');
                if ($missing['supplier_email_snapshot']) $table->string('supplier_email_snapshot', 190)->nullable()->after('supplier_name_snapshot');
                if ($missing['supplier_phone_snapshot']) $table->string('supplier_phone_snapshot', 40)->nullable()->after('supplier_email_snapshot');
                if ($missing['supplier_role_snapshot']) $table->string('supplier_role_snapshot', 50)->nullable()->after('supplier_phone_snapshot');
                if ($missing['assigned_at']) $table->dateTime('assigned_at')->nullable()->after('supplier_role_snapshot');
            });
        }

        if (Schema::hasColumn('orders', 'supplier_user_id')) {
            try {
                Schema::table('orders', static function (Blueprint $table): void {
                    $table->foreign('supplier_user_id')->references('id')->on('users')->nullOnDelete();
                });
            } catch (\Throwable) {
                // Delimično primenjen upgrade može već imati ključ.
            }
            try {
                Schema::table('orders', static function (Blueprint $table): void {
                    $table->index(['supplier_user_id', 'status', 'created_at'], 'orders_supplier_status_created_index');
                });
            } catch (\Throwable) {
                // Indeks već postoji.
            }
        }
    }

    private function createDocumentCounters(): void
    {
        if (Schema::hasTable('document_counters')) {
            return;
        }

        Schema::create('document_counters', static function (Blueprint $table): void {
            $table->id();
            $table->string('document_type', 40);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
            $table->unique(['document_type', 'year']);
        });
    }

    private function createOrderDocuments(): void
    {
        if (Schema::hasTable('order_documents')) {
            return;
        }

        Schema::create('order_documents', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->enum('document_type', ['order_confirmation', 'proforma', 'invoice']);
            $table->string('document_number', 50)->unique();
            $table->enum('status', ['issued', 'cancelled'])->default('issued');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->dateTime('issued_at');
            $table->date('due_at')->nullable();
            $table->char('currency', 3)->default('RSD');
            $table->decimal('subtotal_rsd', 14, 2);
            $table->decimal('tax_rate_percent', 5, 2)->default(0);
            $table->decimal('tax_base_rsd', 14, 2)->default(0);
            $table->decimal('tax_amount_rsd', 14, 2)->default(0);
            $table->decimal('total_rsd', 14, 2);
            $table->string('company_name', 190);
            $table->string('company_address', 255)->nullable();
            $table->string('company_city', 120)->nullable();
            $table->string('company_tax_id', 40)->nullable();
            $table->string('company_registration_number', 40)->nullable();
            $table->string('company_phone', 60)->nullable();
            $table->string('company_email', 190)->nullable();
            $table->string('company_website', 190)->nullable();
            $table->string('company_logo_path', 500)->nullable();
            $table->string('customer_name', 190);
            $table->string('customer_address', 255)->nullable();
            $table->string('customer_city', 140)->nullable();
            $table->string('customer_phone', 60)->nullable();
            $table->string('customer_email', 190)->nullable();
            $table->string('supplier_name', 190)->nullable();
            $table->string('supplier_email', 190)->nullable();
            $table->string('payment_method_snapshot', 50)->nullable();
            $table->string('payment_status_snapshot', 50)->nullable();
            $table->string('bank_account_snapshot', 60)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['order_id', 'document_type']);
            $table->index(['document_type', 'status', 'issued_at']);
        });
    }

    private function seedAccessPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $rows = [
            ['name' => 'Pregled izveštaja', 'slug' => 'reports.view', 'description' => 'Pregled operativnih i prodajnih izveštaja.', 'sort_order' => 150],
            ['name' => 'Izvoz izveštaja', 'slug' => 'reports.export', 'description' => 'CSV i PDF izvoz filtriranih porudžbina.', 'sort_order' => 160],
            ['name' => 'Upravljanje dokumentima', 'slug' => 'invoices.manage', 'description' => 'Izdavanje i storniranje predračuna i računa.', 'sort_order' => 170],
            ['name' => 'Pregled svojih dokumenata', 'slug' => 'invoices.view_own', 'description' => 'PDF potvrde, predračuni i računi sopstvenih porudžbina.', 'sort_order' => 46],
        ];

        foreach ($rows as $row) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $row['slug']],
                $row + ['created_at' => now()],
            );
        }

        if (Schema::hasTable('user_group_permissions')
            && Schema::hasTable('user_groups')
            && DB::table('user_groups')->where('id', 1)->exists()) {
            $permissionId = DB::table('permissions')->where('slug', 'invoices.view_own')->value('id');
            if ($permissionId !== null) {
                DB::table('user_group_permissions')->insertOrIgnore([
                    'group_id' => 1,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function backfillSupplierAssignments(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'supplier_user_id') || !Schema::hasTable('users') || !Schema::hasTable('roles')) {
            return;
        }

        $supplier = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.status', 'active')
            ->whereIn('roles.slug', ['superadmin', 'admin'])
            ->orderByRaw("CASE WHEN roles.slug = 'superadmin' THEN 0 ELSE 1 END")
            ->orderBy('users.id')
            ->select(['users.id', 'users.first_name', 'users.last_name', 'users.username', 'users.email', 'users.phone', 'roles.name as role_name'])
            ->first();

        if ($supplier === null) {
            return;
        }

        $name = trim((string) $supplier->first_name.' '.(string) $supplier->last_name);
        if ($name === '') $name = (string) $supplier->username;

        DB::table('orders')
            ->where('source_system', 'laravel')
            ->whereNull('supplier_user_id')
            ->update([
                'supplier_user_id' => $supplier->id,
                'supplier_name_snapshot' => $name,
                'supplier_email_snapshot' => $supplier->email,
                'supplier_phone_snapshot' => $supplier->phone,
                'supplier_role_snapshot' => $supplier->role_name,
                'assigned_at' => now(),
            ]);
    }
};

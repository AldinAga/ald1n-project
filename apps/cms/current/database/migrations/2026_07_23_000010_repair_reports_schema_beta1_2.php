<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->repairOrderSupplierColumns();
        $this->repairDocumentCounters();
        $this->repairOrderDocuments();
        $this->repairReportPermissions();
    }

    public function down(): void
    {
        // Recovery migracija je namerno nedestruktivna.
    }

    private function repairOrderSupplierColumns(): void
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

        if (!in_array(true, $missing, true)) {
            return;
        }

        Schema::table('orders', static function (Blueprint $table) use ($missing): void {
            if ($missing['supplier_user_id']) $table->unsignedBigInteger('supplier_user_id')->nullable();
            if ($missing['supplier_name_snapshot']) $table->string('supplier_name_snapshot', 190)->nullable();
            if ($missing['supplier_email_snapshot']) $table->string('supplier_email_snapshot', 190)->nullable();
            if ($missing['supplier_phone_snapshot']) $table->string('supplier_phone_snapshot', 40)->nullable();
            if ($missing['supplier_role_snapshot']) $table->string('supplier_role_snapshot', 50)->nullable();
            if ($missing['assigned_at']) $table->dateTime('assigned_at')->nullable();
        });
    }

    private function repairDocumentCounters(): void
    {
        if (!Schema::hasTable('document_counters')) {
            Schema::create('document_counters', static function (Blueprint $table): void {
                $table->id();
                $table->string('document_type', 40);
                $table->unsignedSmallInteger('year');
                $table->unsignedBigInteger('next_number')->default(1);
                $table->timestamps();
                $table->unique(['document_type', 'year']);
            });
            return;
        }

        $missing = array_flip(array_diff(
            ['document_type', 'year', 'next_number', 'created_at', 'updated_at'],
            Schema::getColumnListing('document_counters'),
        ));

        if ($missing === []) {
            return;
        }

        Schema::table('document_counters', static function (Blueprint $table) use ($missing): void {
            if (isset($missing['document_type'])) $table->string('document_type', 40)->nullable();
            if (isset($missing['year'])) $table->unsignedSmallInteger('year')->nullable();
            if (isset($missing['next_number'])) $table->unsignedBigInteger('next_number')->default(1);
            if (isset($missing['created_at'])) $table->timestamp('created_at')->nullable();
            if (isset($missing['updated_at'])) $table->timestamp('updated_at')->nullable();
        });
    }

    private function repairOrderDocuments(): void
    {
        if (!Schema::hasTable('order_documents')) {
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
                $table->decimal('subtotal_rsd', 14, 2)->default(0);
                $table->decimal('tax_rate_percent', 5, 2)->default(0);
                $table->decimal('tax_base_rsd', 14, 2)->default(0);
                $table->decimal('tax_amount_rsd', 14, 2)->default(0);
                $table->decimal('total_rsd', 14, 2)->default(0);
                $table->string('company_name', 190)->default('Ald1n');
                $table->string('company_address', 255)->nullable();
                $table->string('company_city', 120)->nullable();
                $table->string('company_tax_id', 40)->nullable();
                $table->string('company_registration_number', 40)->nullable();
                $table->string('company_phone', 60)->nullable();
                $table->string('company_email', 190)->nullable();
                $table->string('company_website', 190)->nullable();
                $table->string('company_logo_path', 500)->nullable();
                $table->string('customer_name', 190)->default('Korisnik');
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
            return;
        }

        $existing = Schema::getColumnListing('order_documents');
        $missing = array_flip(array_diff([
            'order_id', 'document_type', 'document_number', 'status', 'issued_by', 'issued_at', 'due_at',
            'currency', 'subtotal_rsd', 'tax_rate_percent', 'tax_base_rsd', 'tax_amount_rsd', 'total_rsd',
            'company_name', 'company_address', 'company_city', 'company_tax_id',
            'company_registration_number', 'company_phone', 'company_email', 'company_website',
            'company_logo_path', 'customer_name', 'customer_address', 'customer_city', 'customer_phone',
            'customer_email', 'supplier_name', 'supplier_email', 'payment_method_snapshot',
            'payment_status_snapshot', 'bank_account_snapshot', 'note', 'cancelled_by', 'cancelled_at',
            'created_at', 'updated_at',
        ], $existing));

        if ($missing === []) {
            return;
        }

        Schema::table('order_documents', static function (Blueprint $table) use ($missing): void {
            if (isset($missing['order_id'])) $table->unsignedBigInteger('order_id')->nullable();
            if (isset($missing['document_type'])) $table->string('document_type', 40)->default('order_confirmation');
            if (isset($missing['document_number'])) $table->string('document_number', 50)->nullable();
            if (isset($missing['status'])) $table->string('status', 20)->default('issued');
            if (isset($missing['issued_by'])) $table->unsignedBigInteger('issued_by')->nullable();
            if (isset($missing['issued_at'])) $table->dateTime('issued_at')->nullable();
            if (isset($missing['due_at'])) $table->date('due_at')->nullable();
            if (isset($missing['currency'])) $table->char('currency', 3)->default('RSD');
            if (isset($missing['subtotal_rsd'])) $table->decimal('subtotal_rsd', 14, 2)->default(0);
            if (isset($missing['tax_rate_percent'])) $table->decimal('tax_rate_percent', 5, 2)->default(0);
            if (isset($missing['tax_base_rsd'])) $table->decimal('tax_base_rsd', 14, 2)->default(0);
            if (isset($missing['tax_amount_rsd'])) $table->decimal('tax_amount_rsd', 14, 2)->default(0);
            if (isset($missing['total_rsd'])) $table->decimal('total_rsd', 14, 2)->default(0);
            if (isset($missing['company_name'])) $table->string('company_name', 190)->default('Ald1n');
            if (isset($missing['company_address'])) $table->string('company_address', 255)->nullable();
            if (isset($missing['company_city'])) $table->string('company_city', 120)->nullable();
            if (isset($missing['company_tax_id'])) $table->string('company_tax_id', 40)->nullable();
            if (isset($missing['company_registration_number'])) $table->string('company_registration_number', 40)->nullable();
            if (isset($missing['company_phone'])) $table->string('company_phone', 60)->nullable();
            if (isset($missing['company_email'])) $table->string('company_email', 190)->nullable();
            if (isset($missing['company_website'])) $table->string('company_website', 190)->nullable();
            if (isset($missing['company_logo_path'])) $table->string('company_logo_path', 500)->nullable();
            if (isset($missing['customer_name'])) $table->string('customer_name', 190)->default('Korisnik');
            if (isset($missing['customer_address'])) $table->string('customer_address', 255)->nullable();
            if (isset($missing['customer_city'])) $table->string('customer_city', 140)->nullable();
            if (isset($missing['customer_phone'])) $table->string('customer_phone', 60)->nullable();
            if (isset($missing['customer_email'])) $table->string('customer_email', 190)->nullable();
            if (isset($missing['supplier_name'])) $table->string('supplier_name', 190)->nullable();
            if (isset($missing['supplier_email'])) $table->string('supplier_email', 190)->nullable();
            if (isset($missing['payment_method_snapshot'])) $table->string('payment_method_snapshot', 50)->nullable();
            if (isset($missing['payment_status_snapshot'])) $table->string('payment_status_snapshot', 50)->nullable();
            if (isset($missing['bank_account_snapshot'])) $table->string('bank_account_snapshot', 60)->nullable();
            if (isset($missing['note'])) $table->text('note')->nullable();
            if (isset($missing['cancelled_by'])) $table->unsignedBigInteger('cancelled_by')->nullable();
            if (isset($missing['cancelled_at'])) $table->dateTime('cancelled_at')->nullable();
            if (isset($missing['created_at'])) $table->timestamp('created_at')->nullable();
            if (isset($missing['updated_at'])) $table->timestamp('updated_at')->nullable();
        });
    }

    private function repairReportPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $rows = [
            ['name' => 'Pregled izveštaja', 'slug' => 'reports.view', 'description' => 'Pregled operativnih i prodajnih izveštaja.', 'sort_order' => 150],
            ['name' => 'Izvoz izveštaja', 'slug' => 'reports.export', 'description' => 'CSV i PDF izvoz filtriranih porudžbina.', 'sort_order' => 160],
            ['name' => 'Upravljanje dokumentima', 'slug' => 'invoices.manage', 'description' => 'Izdavanje i storniranje predračuna i računa.', 'sort_order' => 170],
            ['name' => 'Pregled svojih dokumenata', 'slug' => 'invoices.view_own', 'description' => 'PDF dokumenti sopstvenih porudžbina.', 'sort_order' => 46],
        ];

        foreach ($rows as $row) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $row['slug']],
                $row + ['created_at' => now()],
            );
        }
    }
};

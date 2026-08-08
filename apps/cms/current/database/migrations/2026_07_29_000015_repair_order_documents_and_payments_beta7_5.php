<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->repairOrderPaymentColumns();
        $this->repairDocumentCounters();
        $this->repairOrderDocuments();
        $this->repairOrderPayments();
        $this->repairPermissions();
        $this->backfillPaymentState();
    }

    public function down(): void
    {
        // Recovery migracija je namerno nedestruktivna i ne briše finansijsku istoriju.
    }

    private function repairOrderPaymentColumns(): void
    {
        if (!Schema::hasTable('orders')) return;

        $missing = [
            'payment_state' => !Schema::hasColumn('orders', 'payment_state'),
            'paid_total_rsd' => !Schema::hasColumn('orders', 'paid_total_rsd'),
            'payment_due_at' => !Schema::hasColumn('orders', 'payment_due_at'),
            'payment_verified_at' => !Schema::hasColumn('orders', 'payment_verified_at'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('orders', static function (Blueprint $table) use ($missing): void {
                if ($missing['payment_state']) $table->string('payment_state', 30)->default('unpaid');
                if ($missing['paid_total_rsd']) $table->decimal('paid_total_rsd', 14, 2)->default(0);
                if ($missing['payment_due_at']) $table->dateTime('payment_due_at')->nullable();
                if ($missing['payment_verified_at']) $table->dateTime('payment_verified_at')->nullable();
            });
        }

        try {
            Schema::table('orders', static function (Blueprint $table): void {
                $table->index(['payment_state', 'payment_due_at', 'created_at'], 'orders_payment_attention_index');
            });
        } catch (\Throwable) {
            // Indeks već postoji ili konkretna baza ne dozvoljava njegovo ponovno kreiranje.
        }
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
                $table->unique(['document_type', 'year'], 'document_counters_type_year_unique');
            });
            return;
        }

        $columns = Schema::getColumnListing('document_counters');
        $missing = array_flip(array_diff(
            ['document_type', 'year', 'next_number', 'created_at', 'updated_at'],
            $columns,
        ));

        if ($missing !== []) {
            Schema::table('document_counters', static function (Blueprint $table) use ($missing): void {
                if (isset($missing['document_type'])) $table->string('document_type', 40)->nullable();
                if (isset($missing['year'])) $table->unsignedSmallInteger('year')->nullable();
                if (isset($missing['next_number'])) $table->unsignedBigInteger('next_number')->default(1);
                if (isset($missing['created_at'])) $table->timestamp('created_at')->nullable();
                if (isset($missing['updated_at'])) $table->timestamp('updated_at')->nullable();
            });
        }

        // Delimičan raniji upgrade je mogao ostaviti duple brojače bez unique indeksa.
        if (Schema::hasColumn('document_counters', 'id')
            && Schema::hasColumn('document_counters', 'document_type')
            && Schema::hasColumn('document_counters', 'year')
            && Schema::hasColumn('document_counters', 'next_number')) {
            DB::table('document_counters')
                ->whereNull('document_type')
                ->orWhere('document_type', '')
                ->orWhereNull('year')
                ->delete();

            $duplicates = DB::table('document_counters')
                ->select(['document_type', 'year'])
                ->selectRaw('MIN(id) AS keeper_id, MAX(next_number) AS max_next, COUNT(*) AS row_count')
                ->groupBy('document_type', 'year')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $duplicate) {
                DB::table('document_counters')->where('id', (int) $duplicate->keeper_id)->update([
                    'next_number' => max(1, (int) $duplicate->max_next),
                    'updated_at' => now(),
                ]);
                DB::table('document_counters')
                    ->where('document_type', (string) $duplicate->document_type)
                    ->where('year', (int) $duplicate->year)
                    ->where('id', '<>', (int) $duplicate->keeper_id)
                    ->delete();
            }
        }

        try {
            Schema::table('document_counters', static function (Blueprint $table): void {
                $table->unique(['document_type', 'year'], 'document_counters_type_year_unique');
            });
        } catch (\Throwable) {
            // Unique indeks već postoji.
        }
    }

    private function repairOrderDocuments(): void
    {
        if (!Schema::hasTable('order_documents')) {
            Schema::create('order_documents', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('document_type', 40);
                $table->string('document_number', 50)->unique();
                $table->string('status', 20)->default('issued');
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->dateTime('issued_at');
                $table->date('due_at')->nullable();
                $table->char('currency', 3)->default('RSD');
                $table->decimal('subtotal_rsd', 14, 2)->default(0);
                $table->decimal('tax_rate_percent', 5, 2)->default(0);
                $table->decimal('tax_base_rsd', 14, 2)->default(0);
                $table->decimal('tax_amount_rsd', 14, 2)->default(0);
                $table->decimal('total_rsd', 14, 2)->default(0);
                $table->string('company_name', 190)->default('Ald1n CMS');
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
                $table->unique(['order_id', 'document_type'], 'order_documents_order_type_unique');
                $table->index(['document_type', 'status', 'issued_at'], 'order_documents_type_status_issued_index');
            });
            return;
        }

        $existing = Schema::getColumnListing('order_documents');
        $missing = array_flip(array_diff([
            'order_id', 'document_type', 'document_number', 'status', 'issued_by', 'issued_at', 'due_at',
            'currency', 'subtotal_rsd', 'tax_rate_percent', 'tax_base_rsd', 'tax_amount_rsd', 'total_rsd',
            'company_name', 'company_address', 'company_city', 'company_tax_id', 'company_registration_number',
            'company_phone', 'company_email', 'company_website', 'company_logo_path', 'customer_name',
            'customer_address', 'customer_city', 'customer_phone', 'customer_email', 'supplier_name',
            'supplier_email', 'payment_method_snapshot', 'payment_status_snapshot', 'bank_account_snapshot',
            'note', 'cancelled_by', 'cancelled_at', 'created_at', 'updated_at',
        ], $existing));

        if ($missing !== []) {
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
                if (isset($missing['company_name'])) $table->string('company_name', 190)->default('Ald1n CMS');
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

        try {
            Schema::table('order_documents', static function (Blueprint $table): void {
                $table->unique(['order_id', 'document_type'], 'order_documents_order_type_unique');
            });
        } catch (\Throwable) {
            // Indeks već postoji ili postoje stari duplikati koje ne smemo automatski obrisati.
        }
    }

    private function repairOrderPayments(): void
    {
        if (!Schema::hasTable('order_payments')) {
            Schema::create('order_payments', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('payment_number', 50)->unique();
                $table->string('entry_type', 20)->default('payment');
                $table->string('status', 20)->default('submitted');
                $table->decimal('amount_rsd', 14, 2);
                $table->string('payment_method', 50);
                $table->dateTime('paid_at')->nullable();
                $table->string('reference', 190)->nullable();
                $table->text('note')->nullable();
                $table->string('proof_path', 500)->nullable();
                $table->string('proof_original_name', 255)->nullable();
                $table->string('proof_mime_type', 100)->nullable();
                $table->unsignedBigInteger('proof_size_bytes')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->dateTime('verified_at')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable();
                $table->dateTime('rejected_at')->nullable();
                $table->string('rejection_reason', 1000)->nullable();
                $table->unsignedBigInteger('voided_by')->nullable();
                $table->dateTime('voided_at')->nullable();
                $table->timestamps();

                $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
                $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['order_id', 'status', 'created_at'], 'order_payments_order_status_created_index');
                $table->index(['status', 'paid_at'], 'order_payments_status_paid_index');
                $table->index(['payment_method', 'status'], 'order_payments_method_status_index');
            });
            return;
        }

        $existing = Schema::getColumnListing('order_payments');
        $missing = array_flip(array_diff([
            'order_id', 'payment_number', 'entry_type', 'status', 'amount_rsd', 'payment_method', 'paid_at',
            'reference', 'note', 'proof_path', 'proof_original_name', 'proof_mime_type', 'proof_size_bytes',
            'submitted_by', 'verified_by', 'verified_at', 'rejected_by', 'rejected_at', 'rejection_reason',
            'voided_by', 'voided_at', 'created_at', 'updated_at',
        ], $existing));

        if ($missing !== []) {
            Schema::table('order_payments', static function (Blueprint $table) use ($missing): void {
                if (isset($missing['order_id'])) $table->unsignedBigInteger('order_id')->nullable();
                if (isset($missing['payment_number'])) $table->string('payment_number', 50)->nullable();
                if (isset($missing['entry_type'])) $table->string('entry_type', 20)->default('payment');
                if (isset($missing['status'])) $table->string('status', 20)->default('submitted');
                if (isset($missing['amount_rsd'])) $table->decimal('amount_rsd', 14, 2)->default(0);
                if (isset($missing['payment_method'])) $table->string('payment_method', 50)->default('other');
                if (isset($missing['paid_at'])) $table->dateTime('paid_at')->nullable();
                if (isset($missing['reference'])) $table->string('reference', 190)->nullable();
                if (isset($missing['note'])) $table->text('note')->nullable();
                if (isset($missing['proof_path'])) $table->string('proof_path', 500)->nullable();
                if (isset($missing['proof_original_name'])) $table->string('proof_original_name', 255)->nullable();
                if (isset($missing['proof_mime_type'])) $table->string('proof_mime_type', 100)->nullable();
                if (isset($missing['proof_size_bytes'])) $table->unsignedBigInteger('proof_size_bytes')->nullable();
                if (isset($missing['submitted_by'])) $table->unsignedBigInteger('submitted_by')->nullable();
                if (isset($missing['verified_by'])) $table->unsignedBigInteger('verified_by')->nullable();
                if (isset($missing['verified_at'])) $table->dateTime('verified_at')->nullable();
                if (isset($missing['rejected_by'])) $table->unsignedBigInteger('rejected_by')->nullable();
                if (isset($missing['rejected_at'])) $table->dateTime('rejected_at')->nullable();
                if (isset($missing['rejection_reason'])) $table->string('rejection_reason', 1000)->nullable();
                if (isset($missing['voided_by'])) $table->unsignedBigInteger('voided_by')->nullable();
                if (isset($missing['voided_at'])) $table->dateTime('voided_at')->nullable();
                if (isset($missing['created_at'])) $table->timestamp('created_at')->nullable();
                if (isset($missing['updated_at'])) $table->timestamp('updated_at')->nullable();
            });
        }

        try {
            Schema::table('order_payments', static function (Blueprint $table): void {
                $table->unique('payment_number', 'order_payments_payment_number_unique');
            });
        } catch (\Throwable) {
            // Indeks već postoji ili stari podaci zahtevaju ručnu proveru.
        }
    }

    private function repairPermissions(): void
    {
        if (!Schema::hasTable('permissions')) return;

        foreach ([
            ['name' => 'Upravljanje dokumentima', 'slug' => 'invoices.manage', 'description' => 'Izdavanje i storniranje predračuna i računa.', 'sort_order' => 170],
            ['name' => 'Pregled svojih dokumenata', 'slug' => 'invoices.view_own', 'description' => 'PDF dokumenti sopstvenih porudžbina.', 'sort_order' => 46],
            ['name' => 'Upravljanje uplatama', 'slug' => 'payments.manage', 'description' => 'Evidentiranje, verifikacija, refundacije i storniranje uplata.', 'sort_order' => 180],
            ['name' => 'Slanje potvrde o uplati', 'slug' => 'payments.upload_proof', 'description' => 'Slanje potvrde za sopstvenu porudžbinu.', 'sort_order' => 49],
            ['name' => 'Pregled svojih uplata', 'slug' => 'payments.view_own', 'description' => 'Pregled uplata sopstvenih porudžbina.', 'sort_order' => 50],
        ] as $row) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $row['slug']],
                $row + ['created_at' => now()],
            );
        }

        if (!Schema::hasTable('user_group_permissions') || !Schema::hasTable('user_groups')) return;

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['invoices.view_own', 'payments.upload_proof', 'payments.view_own'])
            ->pluck('id');
        $groupIds = DB::table('user_groups')->where('status', 'active')->pluck('id');

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

    private function backfillPaymentState(): void
    {
        if (!Schema::hasTable('orders')
            || !Schema::hasColumn('orders', 'payment_status')
            || !Schema::hasColumn('orders', 'payment_state')
            || !Schema::hasColumn('orders', 'paid_total_rsd')) return;

        DB::table('orders')->where('payment_status', 'paid')->update([
            'payment_state' => 'paid',
            'paid_total_rsd' => DB::raw('subtotal_rsd'),
        ]);
        DB::table('orders')->where('payment_status', 'cancelled')->update(['payment_state' => 'cancelled']);
        DB::table('orders')->whereNull('payment_state')->update(['payment_state' => 'unpaid']);
    }
};

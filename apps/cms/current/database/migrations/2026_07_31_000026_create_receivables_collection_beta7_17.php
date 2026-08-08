<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->createOrRepairCases();
        $this->createOrRepairInstallments();
        $this->createOrRepairContacts();
        $this->seedPermission();
        $this->seedSettings();
    }

    public function down(): void
    {
        // Produkcijska recovery migracija: istorija naplate se namerno ne briše.
    }

    private function createOrRepairCases(): void
    {
        if (!Schema::hasTable('receivable_cases')) {
            Schema::create('receivable_cases', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id')->unique();
                $table->string('case_number', 50)->unique();
                $table->string('status', 30)->default('monitoring');
                $table->unsignedTinyInteger('collection_stage')->default(0);
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->dateTime('next_action_at')->nullable();
                $table->dateTime('promised_payment_at')->nullable();
                $table->dateTime('last_contact_at')->nullable();
                $table->smallInteger('last_reminder_stage')->nullable();
                $table->dateTime('last_reminder_at')->nullable();
                $table->text('internal_note')->nullable();
                $table->json('metadata_json')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->timestamps();

                $table->foreign('order_id', 'receivable_cases_order_id_foreign')->references('id')->on('orders')->cascadeOnDelete();
                $table->foreign('assigned_to', 'receivable_cases_assigned_to_foreign')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by', 'receivable_cases_created_by_foreign')->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by', 'receivable_cases_updated_by_foreign')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'next_action_at'], 'receivable_cases_status_action_index');
                $table->index(['assigned_to', 'status'], 'receivable_cases_owner_status_index');
                $table->index(['collection_stage', 'last_reminder_at'], 'receivable_cases_stage_reminder_index');
            });

            return;
        }

        $definitions = [
            'order_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('order_id')->nullable(),
            'case_number' => static fn (Blueprint $table) => $table->string('case_number', 50)->nullable(),
            'status' => static fn (Blueprint $table) => $table->string('status', 30)->default('monitoring'),
            'collection_stage' => static fn (Blueprint $table) => $table->unsignedTinyInteger('collection_stage')->default(0),
            'assigned_to' => static fn (Blueprint $table) => $table->unsignedBigInteger('assigned_to')->nullable(),
            'next_action_at' => static fn (Blueprint $table) => $table->dateTime('next_action_at')->nullable(),
            'promised_payment_at' => static fn (Blueprint $table) => $table->dateTime('promised_payment_at')->nullable(),
            'last_contact_at' => static fn (Blueprint $table) => $table->dateTime('last_contact_at')->nullable(),
            'last_reminder_stage' => static fn (Blueprint $table) => $table->smallInteger('last_reminder_stage')->nullable(),
            'last_reminder_at' => static fn (Blueprint $table) => $table->dateTime('last_reminder_at')->nullable(),
            'internal_note' => static fn (Blueprint $table) => $table->text('internal_note')->nullable(),
            'metadata_json' => static fn (Blueprint $table) => $table->json('metadata_json')->nullable(),
            'created_by' => static fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable(),
            'updated_by' => static fn (Blueprint $table) => $table->unsignedBigInteger('updated_by')->nullable(),
            'closed_at' => static fn (Blueprint $table) => $table->dateTime('closed_at')->nullable(),
            'created_at' => static fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => static fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];
        $this->ensureColumns('receivable_cases', $definitions);
        $this->ensureCaseKeys();
    }

    private function createOrRepairInstallments(): void
    {
        if (!Schema::hasTable('receivable_installments')) {
            Schema::create('receivable_installments', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('receivable_case_id');
                $table->unsignedSmallInteger('sequence_no');
                $table->dateTime('due_at');
                $table->decimal('amount_rsd', 14, 2);
                $table->decimal('paid_amount_rsd', 14, 2)->default(0);
                $table->string('status', 20)->default('pending');
                $table->dateTime('paid_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->foreign('receivable_case_id', 'receivable_installments_case_id_foreign')->references('id')->on('receivable_cases')->cascadeOnDelete();
                $table->unique(['receivable_case_id', 'sequence_no'], 'receivable_installment_sequence_unique');
                $table->index(['status', 'due_at'], 'receivable_installments_status_due_index');
            });

            return;
        }

        $this->ensureColumns('receivable_installments', [
            'receivable_case_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('receivable_case_id')->nullable(),
            'sequence_no' => static fn (Blueprint $table) => $table->unsignedSmallInteger('sequence_no')->default(1),
            'due_at' => static fn (Blueprint $table) => $table->dateTime('due_at')->nullable(),
            'amount_rsd' => static fn (Blueprint $table) => $table->decimal('amount_rsd', 14, 2)->default(0),
            'paid_amount_rsd' => static fn (Blueprint $table) => $table->decimal('paid_amount_rsd', 14, 2)->default(0),
            'status' => static fn (Blueprint $table) => $table->string('status', 20)->default('pending'),
            'paid_at' => static fn (Blueprint $table) => $table->dateTime('paid_at')->nullable(),
            'note' => static fn (Blueprint $table) => $table->text('note')->nullable(),
            'created_at' => static fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => static fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);
        $this->ensureInstallmentKeys();
    }

    private function createOrRepairContacts(): void
    {
        if (!Schema::hasTable('receivable_contacts')) {
            Schema::create('receivable_contacts', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('receivable_case_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('order_email_outbox_id')->nullable();
                $table->string('event_key', 190)->nullable()->unique();
                $table->string('channel', 20)->default('internal');
                $table->string('direction', 20)->default('internal');
                $table->string('subject', 190)->nullable();
                $table->text('note');
                $table->boolean('visible_to_customer')->default(false);
                $table->boolean('is_automatic')->default(false);
                $table->dateTime('contacted_at');
                $table->timestamps();

                $table->foreign('receivable_case_id', 'receivable_contacts_case_id_foreign')->references('id')->on('receivable_cases')->cascadeOnDelete();
                $table->foreign('user_id', 'receivable_contacts_user_id_foreign')->references('id')->on('users')->nullOnDelete();
                $table->foreign('order_email_outbox_id', 'receivable_contacts_outbox_id_foreign')->references('id')->on('order_email_outbox')->nullOnDelete();
                $table->index(['receivable_case_id', 'contacted_at'], 'receivable_contacts_case_date_index');
            });

            return;
        }

        $this->ensureColumns('receivable_contacts', [
            'receivable_case_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('receivable_case_id')->nullable(),
            'user_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable(),
            'order_email_outbox_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('order_email_outbox_id')->nullable(),
            'event_key' => static fn (Blueprint $table) => $table->string('event_key', 190)->nullable(),
            'channel' => static fn (Blueprint $table) => $table->string('channel', 20)->default('internal'),
            'direction' => static fn (Blueprint $table) => $table->string('direction', 20)->default('internal'),
            'subject' => static fn (Blueprint $table) => $table->string('subject', 190)->nullable(),
            'note' => static fn (Blueprint $table) => $table->text('note')->nullable(),
            'visible_to_customer' => static fn (Blueprint $table) => $table->boolean('visible_to_customer')->default(false),
            'is_automatic' => static fn (Blueprint $table) => $table->boolean('is_automatic')->default(false),
            'contacted_at' => static fn (Blueprint $table) => $table->dateTime('contacted_at')->nullable(),
            'created_at' => static fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => static fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);
        $this->ensureContactKeys();
    }

    /** @param array<string,callable(Blueprint):mixed> $definitions */
    private function ensureColumns(string $tableName, array $definitions): void
    {
        foreach ($definitions as $column => $definition) {
            if (Schema::hasColumn($tableName, $column)) {
                continue;
            }
            Schema::table($tableName, static function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }
    }

    private function ensureCaseKeys(): void
    {
        $this->ensureIndex('receivable_cases', 'receivable_cases_order_id_unique', static fn (Blueprint $table) => $table->unique('order_id', 'receivable_cases_order_id_unique'));
        $this->ensureIndex('receivable_cases', 'receivable_cases_case_number_unique', static fn (Blueprint $table) => $table->unique('case_number', 'receivable_cases_case_number_unique'));
        $this->ensureIndex('receivable_cases', 'receivable_cases_status_action_index', static fn (Blueprint $table) => $table->index(['status', 'next_action_at'], 'receivable_cases_status_action_index'));
        $this->ensureIndex('receivable_cases', 'receivable_cases_owner_status_index', static fn (Blueprint $table) => $table->index(['assigned_to', 'status'], 'receivable_cases_owner_status_index'));
        $this->ensureIndex('receivable_cases', 'receivable_cases_stage_reminder_index', static fn (Blueprint $table) => $table->index(['collection_stage', 'last_reminder_at'], 'receivable_cases_stage_reminder_index'));
        $this->ensureForeignKey('receivable_cases', 'receivable_cases_order_id_foreign', 'order_id', 'orders', 'cascade');
        $this->ensureForeignKey('receivable_cases', 'receivable_cases_assigned_to_foreign', 'assigned_to', 'users', 'set null');
        $this->ensureForeignKey('receivable_cases', 'receivable_cases_created_by_foreign', 'created_by', 'users', 'set null');
        $this->ensureForeignKey('receivable_cases', 'receivable_cases_updated_by_foreign', 'updated_by', 'users', 'set null');
    }

    private function ensureInstallmentKeys(): void
    {
        $this->ensureIndex('receivable_installments', 'receivable_installment_sequence_unique', static fn (Blueprint $table) => $table->unique(['receivable_case_id', 'sequence_no'], 'receivable_installment_sequence_unique'));
        $this->ensureIndex('receivable_installments', 'receivable_installments_status_due_index', static fn (Blueprint $table) => $table->index(['status', 'due_at'], 'receivable_installments_status_due_index'));
        $this->ensureForeignKey('receivable_installments', 'receivable_installments_case_id_foreign', 'receivable_case_id', 'receivable_cases', 'cascade');
    }

    private function ensureContactKeys(): void
    {
        $this->ensureIndex('receivable_contacts', 'receivable_contacts_event_key_unique', static fn (Blueprint $table) => $table->unique('event_key', 'receivable_contacts_event_key_unique'));
        $this->ensureIndex('receivable_contacts', 'receivable_contacts_case_date_index', static fn (Blueprint $table) => $table->index(['receivable_case_id', 'contacted_at'], 'receivable_contacts_case_date_index'));
        $this->ensureForeignKey('receivable_contacts', 'receivable_contacts_case_id_foreign', 'receivable_case_id', 'receivable_cases', 'cascade');
        $this->ensureForeignKey('receivable_contacts', 'receivable_contacts_user_id_foreign', 'user_id', 'users', 'set null');
        $this->ensureForeignKey('receivable_contacts', 'receivable_contacts_outbox_id_foreign', 'order_email_outbox_id', 'order_email_outbox', 'set null');
    }

    /** @param callable(Blueprint):mixed $definition */
    private function ensureIndex(string $tableName, string $indexName, callable $definition): void
    {
        if ($this->hasIndex($tableName, $indexName)) return;
        Schema::table($tableName, static function (Blueprint $table) use ($definition): void {
            $definition($table);
        });
    }

    private function ensureForeignKey(string $tableName, string $name, string $column, string $targetTable, string $deleteRule): void
    {
        if (!Schema::hasTable($targetTable) || $this->hasForeignKey($tableName, $name)) return;
        Schema::table($tableName, static function (Blueprint $table) use ($name, $column, $targetTable, $deleteRule): void {
            $foreign = $table->foreign($column, $name)->references('id')->on($targetTable);
            if ($deleteRule === 'cascade') $foreign->cascadeOnDelete(); else $foreign->nullOnDelete();
        });
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        try {
            foreach (Schema::getIndexes($tableName) as $index) {
                if (($index['name'] ?? null) === $indexName) return true;
            }
        } catch (\Throwable) {
        }
        return false;
    }

    private function hasForeignKey(string $tableName, string $foreignName): bool
    {
        try {
            foreach (Schema::getForeignKeys($tableName) as $foreign) {
                if (($foreign['name'] ?? null) === $foreignName) return true;
            }
        } catch (\Throwable) {
        }
        return false;
    }

    private function seedPermission(): void
    {
        if (!Schema::hasTable('permissions')) return;

        $key = ['slug' => 'receivables.manage'];
        $values = [
            'name' => 'Upravljanje potraživanjima',
            'description' => 'Aging pregled, automatske opomene, obećanja plaćanja, planovi rata i evidencija komunikacije.',
            'sort_order' => 185,
        ];

        $this->schemaAwareUpdateOrInsert('permissions', $key, $values);
    }

    private function seedSettings(): void
    {
        if (!Schema::hasTable('settings')) return;
        $defaults = [
            'receivables_enabled' => '1',
            'receivables_auto_create_cases' => '1',
            'receivables_auto_reminders_enabled' => '1',
            'receivables_due_soon_days' => '3',
            'receivables_reminder_stages' => '0,3,7,15,30',
            'receivables_pause_on_promise' => '1',
            'receivables_attach_document' => 'invoice',
            'receivables_send_creator' => '1',
            'receivables_send_supplier' => '1',
            'receivables_custom_recipients' => '',
        ];
        foreach ($defaults as $key => $value) {
            $this->schemaAwareUpdateOrInsert(
                'settings',
                ['setting_key' => $key],
                ['setting_value' => $value],
            );
        }
    }

    /**
     * Update or insert a row without assuming that a legacy table has both
     * Laravel timestamp columns. Some production permissions tables contain
     * created_at only, so including updated_at would abort the whole migration.
     *
     * @param array<string,mixed> $key
     * @param array<string,mixed> $values
     */
    private function schemaAwareUpdateOrInsert(string $table, array $key, array $values): void
    {
        $query = DB::table($table);
        foreach ($key as $column => $value) {
            $query->where($column, $value);
        }

        $updateValues = $this->onlyExistingColumns($table, $values);
        if (Schema::hasColumn($table, 'updated_at')) {
            $updateValues['updated_at'] = now();
        }

        if ($query->exists()) {
            if ($updateValues !== []) {
                $query->update($updateValues);
            }
            return;
        }

        $insertValues = $this->onlyExistingColumns($table, $key + $values);
        if (Schema::hasColumn($table, 'created_at')) {
            $insertValues['created_at'] = now();
        }
        if (Schema::hasColumn($table, 'updated_at')) {
            $insertValues['updated_at'] = now();
        }

        DB::table($table)->insert($insertValues);
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function onlyExistingColumns(string $table, array $values): array
    {
        $filtered = [];
        foreach ($values as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }
        return $filtered;
    }
};

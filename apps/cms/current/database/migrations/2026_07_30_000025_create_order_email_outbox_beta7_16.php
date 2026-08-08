<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addWarrantyDurationDays();
        $this->addIpsQrDocumentSnapshot();
        $this->createOrRepairOrderEmailOutbox();
    }

    public function down(): void
    {
        Schema::dropIfExists('order_email_outbox');

        if (Schema::hasTable('order_documents')) {
            $columns = array_values(array_filter([
                Schema::hasColumn('order_documents', 'ips_payload_snapshot') ? 'ips_payload_snapshot' : null,
                Schema::hasColumn('order_documents', 'ips_qr_image_path') ? 'ips_qr_image_path' : null,
                Schema::hasColumn('order_documents', 'ips_qr_generated_at') ? 'ips_qr_generated_at' : null,
                Schema::hasColumn('order_documents', 'ips_qr_error') ? 'ips_qr_error' : null,
            ]));
            if ($columns !== []) {
                Schema::table('order_documents', static function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('product_warranties') && Schema::hasColumn('product_warranties', 'duration_days')) {
            Schema::table('product_warranties', static function (Blueprint $table): void {
                $table->dropColumn('duration_days');
            });
        }
        if (Schema::hasTable('warranty_rules') && Schema::hasColumn('warranty_rules', 'duration_days')) {
            Schema::table('warranty_rules', static function (Blueprint $table): void {
                $table->dropColumn('duration_days');
            });
        }
    }

    private function addWarrantyDurationDays(): void
    {
        if (Schema::hasTable('warranty_rules') && !Schema::hasColumn('warranty_rules', 'duration_days')) {
            Schema::table('warranty_rules', static function (Blueprint $table): void {
                $table->unsignedInteger('duration_days')->default(0)->after('duration_months');
            });
        }

        if (Schema::hasTable('product_warranties') && !Schema::hasColumn('product_warranties', 'duration_days')) {
            Schema::table('product_warranties', static function (Blueprint $table): void {
                $table->unsignedInteger('duration_days')->default(0)->after('duration_months');
            });
        }
    }

    private function addIpsQrDocumentSnapshot(): void
    {
        if (!Schema::hasTable('order_documents')) {
            return;
        }

        // Kolone se dodaju pojedinačno. To je pouzdanije na MariaDB kada se
        // migracija ponavlja nakon delimično izvršenog DDL koraka.
        if (!Schema::hasColumn('order_documents', 'ips_payload_snapshot')) {
            Schema::table('order_documents', static function (Blueprint $table): void {
                $table->text('ips_payload_snapshot')->nullable()->after('bank_account_snapshot');
            });
        }
        if (!Schema::hasColumn('order_documents', 'ips_qr_image_path')) {
            Schema::table('order_documents', static function (Blueprint $table): void {
                $table->string('ips_qr_image_path', 500)->nullable()->after('ips_payload_snapshot');
            });
        }
        if (!Schema::hasColumn('order_documents', 'ips_qr_generated_at')) {
            Schema::table('order_documents', static function (Blueprint $table): void {
                $table->timestamp('ips_qr_generated_at')->nullable()->after('ips_qr_image_path');
            });
        }
        if (!Schema::hasColumn('order_documents', 'ips_qr_error')) {
            Schema::table('order_documents', static function (Blueprint $table): void {
                $table->text('ips_qr_error')->nullable()->after('ips_qr_generated_at');
            });
        }
    }

    private function createOrRepairOrderEmailOutbox(): void
    {
        if (!Schema::hasTable('order_email_outbox')) {
            Schema::create('order_email_outbox', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('recipient_user_id')->nullable();
                $table->unsignedBigInteger('document_id')->nullable();
                $table->string('recipient_email', 190);
                $table->string('recipient_name', 190)->nullable();
                $table->string('event_type', 80);
                $table->string('dedupe_key', 64);
                $table->string('batch_key', 100);
                $table->string('subject', 255);
                $table->text('message');
                $table->text('action_url')->nullable();
                $table->boolean('attach_document')->default(false);
                $table->boolean('attach_active_invoice')->default(false);
                $table->string('status', 30)->default('pending');
                $table->unsignedSmallInteger('attempt_count')->default(0);
                $table->timestamp('scheduled_for')->nullable();
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->text('last_error')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->unique('dedupe_key', 'order_email_outbox_dedupe_key_unique');
                $table->index(['status', 'scheduled_for', 'id'], 'order_email_outbox_dispatch_index');
                $table->index(['recipient_email', 'batch_key', 'status'], 'order_email_outbox_recipient_batch_index');
                $table->index(['order_id', 'created_at'], 'order_email_outbox_order_index');
                $table->foreign('order_id', 'order_email_outbox_order_id_foreign')->references('id')->on('orders')->cascadeOnDelete();
                $table->foreign('recipient_user_id', 'order_email_outbox_recipient_user_id_foreign')->references('id')->on('users')->nullOnDelete();
                $table->foreign('document_id', 'order_email_outbox_document_id_foreign')->references('id')->on('order_documents')->nullOnDelete();
            });

            return;
        }

        // Recovery putanja za hosting na kojem je DDL stao na pola. Svaka
        // kolona i svaki indeks proveravaju se pre dodavanja.
        $definitions = [
            'order_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('order_id')->nullable(),
            'recipient_user_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('recipient_user_id')->nullable(),
            'document_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('document_id')->nullable(),
            'recipient_email' => static fn (Blueprint $table) => $table->string('recipient_email', 190)->default(''),
            'recipient_name' => static fn (Blueprint $table) => $table->string('recipient_name', 190)->nullable(),
            'event_type' => static fn (Blueprint $table) => $table->string('event_type', 80)->default('other'),
            'dedupe_key' => static fn (Blueprint $table) => $table->string('dedupe_key', 64)->nullable(),
            'batch_key' => static fn (Blueprint $table) => $table->string('batch_key', 100)->default('recovered'),
            'subject' => static fn (Blueprint $table) => $table->string('subject', 255)->default('Obaveštenje o porudžbini'),
            'message' => static fn (Blueprint $table) => $table->text('message')->nullable(),
            'action_url' => static fn (Blueprint $table) => $table->text('action_url')->nullable(),
            'attach_document' => static fn (Blueprint $table) => $table->boolean('attach_document')->default(false),
            'attach_active_invoice' => static fn (Blueprint $table) => $table->boolean('attach_active_invoice')->default(false),
            'status' => static fn (Blueprint $table) => $table->string('status', 30)->default('pending'),
            'attempt_count' => static fn (Blueprint $table) => $table->unsignedSmallInteger('attempt_count')->default(0),
            'scheduled_for' => static fn (Blueprint $table) => $table->timestamp('scheduled_for')->nullable(),
            'last_attempt_at' => static fn (Blueprint $table) => $table->timestamp('last_attempt_at')->nullable(),
            'sent_at' => static fn (Blueprint $table) => $table->timestamp('sent_at')->nullable(),
            'last_error' => static fn (Blueprint $table) => $table->text('last_error')->nullable(),
            'metadata_json' => static fn (Blueprint $table) => $table->json('metadata_json')->nullable(),
            'created_at' => static fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => static fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($definitions as $column => $definition) {
            if (Schema::hasColumn('order_email_outbox', $column)) {
                continue;
            }
            Schema::table('order_email_outbox', static function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }

        if (!$this->hasIndex('order_email_outbox', 'order_email_outbox_dedupe_key_unique')) {
            Schema::table('order_email_outbox', static function (Blueprint $table): void {
                $table->unique('dedupe_key', 'order_email_outbox_dedupe_key_unique');
            });
        }
        if (!$this->hasIndex('order_email_outbox', 'order_email_outbox_dispatch_index')) {
            Schema::table('order_email_outbox', static function (Blueprint $table): void {
                $table->index(['status', 'scheduled_for', 'id'], 'order_email_outbox_dispatch_index');
            });
        }
        if (!$this->hasIndex('order_email_outbox', 'order_email_outbox_recipient_batch_index')) {
            Schema::table('order_email_outbox', static function (Blueprint $table): void {
                $table->index(['recipient_email', 'batch_key', 'status'], 'order_email_outbox_recipient_batch_index');
            });
        }
        if (!$this->hasIndex('order_email_outbox', 'order_email_outbox_order_index')) {
            Schema::table('order_email_outbox', static function (Blueprint $table): void {
                $table->index(['order_id', 'created_at'], 'order_email_outbox_order_index');
            });
        }

        $this->ensureForeignKey('order_email_outbox_order_id_foreign', 'order_id', 'orders', 'cascade');
        $this->ensureForeignKey('order_email_outbox_recipient_user_id_foreign', 'recipient_user_id', 'users', 'set null');
        $this->ensureForeignKey('order_email_outbox_document_id_foreign', 'document_id', 'order_documents', 'set null');
    }

    private function ensureForeignKey(string $name, string $column, string $targetTable, string $deleteRule): void
    {
        if ($this->hasForeignKey('order_email_outbox', $name)) {
            return;
        }

        Schema::table('order_email_outbox', static function (Blueprint $table) use ($name, $column, $targetTable, $deleteRule): void {
            $foreign = $table->foreign($column, $name)->references('id')->on($targetTable);
            if ($deleteRule === 'cascade') {
                $foreign->cascadeOnDelete();
            } else {
                $foreign->nullOnDelete();
            }
        });
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
        }

        return false;
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        try {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if (($foreignKey['name'] ?? null) === $name) {
                    return true;
                }
            }
        } catch (\Throwable) {
        }

        return false;
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_documents')) {
            return;
        }

        // Kolone se dodaju pre izmene indeksa kako bi migracija bila bezbedna i
        // pri ponovnom pokretanju nakon delimično uspešnog MySQL DDL koraka.
        $columns = Schema::getColumnListing('order_documents');
        Schema::table('order_documents', static function (Blueprint $table) use ($columns): void {
            if (!in_array('revision_number', $columns, true)) {
                $table->unsignedInteger('revision_number')->default(1)->after('document_type');
            }
            if (!in_array('supersedes_document_id', $columns, true)) {
                $table->unsignedBigInteger('supersedes_document_id')->nullable()->after('revision_number');
            }
            if (!in_array('cancellation_reason', $columns, true)) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }
        });

        // InnoDB može da koristi stari UNIQUE(order_id, document_type) kao
        // pomoćni indeks za FK order_documents.order_id. Taj indeks se ne sme
        // ukloniti dok prvo ne postoji drugi indeks čiji je prvi stupac order_id.
        $this->ensureOrderIdForeignKeySupportIndex();
        $this->dropOrderTypeUniqueIndexes();

        $this->backfillRevisionChain();

        $this->ensureIndex(
            ['order_id', 'document_type', 'status'],
            'order_documents_order_type_status_index',
        );
        $this->ensureIndex(
            ['supersedes_document_id'],
            'order_documents_supersedes_index',
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('order_documents')) {
            return;
        }

        try {
            Schema::table('order_documents', static function (Blueprint $table): void {
                $table->dropIndex('order_documents_order_type_status_index');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('order_documents', static function (Blueprint $table): void {
                $table->dropIndex('order_documents_supersedes_index');
            });
        } catch (\Throwable) {
        }

        $columns = Schema::getColumnListing('order_documents');
        Schema::table('order_documents', static function (Blueprint $table) use ($columns): void {
            $drop = array_values(array_intersect([
                'revision_number',
                'supersedes_document_id',
                'cancellation_reason',
            ], $columns));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });

        $duplicates = DB::table('order_documents')
            ->select(['order_id', 'document_type'])
            ->groupBy('order_id', 'document_type')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (!$duplicates) {
            try {
                Schema::table('order_documents', static function (Blueprint $table): void {
                    $table->unique(['order_id', 'document_type'], 'order_documents_order_type_unique');
                });
            } catch (\Throwable) {
            }
        }
    }

    private function backfillRevisionChain(): void
    {
        if (!Schema::hasColumn('order_documents', 'revision_number')
            || !Schema::hasColumn('order_documents', 'supersedes_document_id')) {
            return;
        }

        $lastKey = null;
        $revision = 0;
        $previousId = null;

        foreach (DB::table('order_documents')
            ->select(['id', 'order_id', 'document_type'])
            ->orderBy('order_id')
            ->orderBy('document_type')
            ->orderByRaw('COALESCE(issued_at, created_at) ASC')
            ->orderBy('id')
            ->cursor() as $row) {
            $key = (string) $row->order_id.'|'.(string) $row->document_type;
            if ($key !== $lastKey) {
                $lastKey = $key;
                $revision = 1;
                $previousId = null;
            } else {
                $revision++;
            }

            DB::table('order_documents')->where('id', $row->id)->update([
                'revision_number' => $revision,
                'supersedes_document_id' => $previousId,
            ]);

            $previousId = (int) $row->id;
        }
    }

    private function ensureOrderIdForeignKeySupportIndex(): void
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $indexes = $this->mysqlIndexes();
        foreach ($indexes as $name => $definition) {
            $columns = $definition['columns'];
            if (($columns[0] ?? null) !== 'order_id') {
                continue;
            }

            // Stari unique indeks je upravo indeks koji uklanjamo. Potreban je
            // zaseban alternativni indeks za FK pre DROP INDEX naredbe.
            $isOldOrderTypeUnique = $definition['unique']
                && $columns === ['order_id', 'document_type'];
            if (!$isOldOrderTypeUnique) {
                return;
            }
        }

        if (isset($indexes['order_documents_order_id_fk_index'])) {
            return;
        }

        DB::statement(
            'ALTER TABLE `order_documents` '
            .'ADD INDEX `order_documents_order_id_fk_index` (`order_id`)',
        );
    }

    private function dropOrderTypeUniqueIndexes(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            foreach ($this->mysqlIndexes() as $name => $definition) {
                if (!$definition['unique'] || $definition['columns'] !== ['order_id', 'document_type']) {
                    continue;
                }

                DB::statement(
                    'ALTER TABLE `order_documents` DROP INDEX `'
                    .str_replace('`', '``', $name).'`',
                );
            }
            return;
        }

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('order_documents')");
            foreach ($rows as $row) {
                if ((int) ($row->unique ?? 0) !== 1) {
                    continue;
                }
                $name = (string) $row->name;
                $info = DB::select("PRAGMA index_info('".str_replace("'", "''", $name)."')");
                $columns = array_map(static fn (object $item): string => (string) $item->name, $info);
                if ($columns === ['order_id', 'document_type']) {
                    DB::statement('DROP INDEX IF EXISTS "'.str_replace('"', '""', $name).'"');
                }
            }
            return;
        }

        foreach (['order_documents_order_type_unique', 'order_documents_order_id_document_type_unique'] as $name) {
            try {
                DB::statement('DROP INDEX IF EXISTS "'.str_replace('"', '""', $name).'"');
            } catch (\Throwable) {
            }
        }
    }

    /**
     * @return array<string,array{unique:bool,columns:list<string>}>
     */
    private function mysqlIndexes(): array
    {
        $rows = DB::select('SHOW INDEX FROM `order_documents`');
        $indexes = [];

        foreach ($rows as $row) {
            $name = (string) ($row->Key_name ?? '');
            if ($name === '' || $name === 'PRIMARY') {
                continue;
            }

            $indexes[$name]['unique'] = (int) ($row->Non_unique ?? 1) === 0;
            $indexes[$name]['columns'][(int) ($row->Seq_in_index ?? 1)] = (string) ($row->Column_name ?? '');
        }

        foreach ($indexes as $name => $definition) {
            ksort($definition['columns']);
            $indexes[$name]['columns'] = array_values($definition['columns']);
        }

        return $indexes;
    }

    /**
     * @param list<string> $columns
     */
    private function ensureIndex(array $columns, string $name): void
    {
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (isset($this->mysqlIndexes()[$name])) {
                return;
            }
        }

        try {
            Schema::table('order_documents', static function (Blueprint $table) use ($columns, $name): void {
                $table->index($columns, $name);
            });
        } catch (\Throwable) {
            // Indeks već postoji pod istim ili kompatibilnim imenom.
        }
    }
};

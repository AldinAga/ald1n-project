<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class TotalProductPurgeService
{
    public const IRREVERSIBLE_CONFIRMATION = 'TRAJNO OBRIŠI SVE TRAGOVE';

    private const REDACTED = '[TRAJNO OBRISAN ARTIKAL]';

    public function __construct(
        private readonly TotalProductPurgeVerifier $verifier,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param array{
     *   confirmation:mixed,
     *   reason:mixed,
     *   irreversible_confirmation:mixed,
     *   retention_acknowledged:mixed
     * } $input
     * @return array<string,mixed>
     */
    public function purge(Product $product, User $actor, array $input): array
    {
        abort_unless($actor->hasRole('superadmin'), 403);

        $confirmation = trim((string) ($input['confirmation'] ?? ''));
        $reason = trim((string) ($input['reason'] ?? ''));
        $irreversible = trim((string) ($input['irreversible_confirmation'] ?? ''));
        $retentionAcknowledged = filter_var(
            $input['retention_acknowledged'] ?? false,
            FILTER_VALIDATE_BOOL,
        );

        if (!hash_equals((string) $product->sku, $confirmation)) {
            throw ValidationException::withMessages([
                'total_confirmation' => 'Za Total Product Purge upiši tačan SKU artikla: '.$product->sku.'.',
            ]);
        }

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'total_reason' => 'Unesi razlog Total Product Purge operacije od najmanje 10 karaktera.',
            ]);
        }

        if (!hash_equals(self::IRREVERSIBLE_CONFIRMATION, $irreversible)) {
            throw ValidationException::withMessages([
                'total_irreversible_confirmation' => 'Za drugu nepovratnu potvrdu upiši tačno: '.self::IRREVERSIBLE_CONFIRMATION,
            ]);
        }

        if (!$retentionAcknowledged) {
            throw ValidationException::withMessages([
                'total_retention_acknowledged' => 'Potvrdi da disaster-recovery backupi i off-host kopije predstavljaju zasebnu retention granicu i da se ovom akcijom ne brišu.',
            ]);
        }

        $this->assertSchemaFoundation();

        $productId = (int) $product->getKey();
        $quarantine = null;
        $committed = false;

        try {
            /** @var array<string,mixed> $result */
            $result = DB::transaction(function () use (
                $productId,
                $actor,
                $confirmation,
                $reason,
                &$quarantine,
                &$committed,
            ): array {
                /** @var Product|null $locked */
                $locked = Product::query()->whereKey($productId)->lockForUpdate()->first();

                if (!$locked instanceof Product) {
                    throw ValidationException::withMessages([
                        'total_confirmation' => 'Artikal više ne postoji.',
                    ]);
                }

                if (!hash_equals((string) $locked->sku, $confirmation)) {
                    throw ValidationException::withMessages([
                        'total_confirmation' => 'SKU artikla se promenio od otvaranja forme. Ponovi potvrdu sa aktuelnim SKU.',
                    ]);
                }

                $manifest = $this->verifier->capture($locked);
                $this->verifier->assertReady($locked, $manifest);

                $this->lockMatchedRows($manifest);
                $quarantine = $this->quarantinePublicProductDirectory($manifest);

                $counts = [
                    'business_rows_unlinked' => 0,
                    'business_rows_redacted' => 0,
                    'owned_rows_deleted' => 0,
                    'async_rows_deleted' => 0,
                    'derived_rows_deleted' => 0,
                    'audit_rows_deleted' => 0,
                    'audit_rows_redacted' => 0,
                ];

                $this->unlinkProductReferences($manifest, $counts);
                $this->redactBusinessHistory($manifest, $counts);
                $this->cleanAuditAndAsyncTraces($manifest, $counts);
                $this->deleteDerivedDiagnostics($manifest, $counts);
                $this->deleteProductOwnedRows($manifest, $counts);

                $deletedProduct = DB::table('products')
                    ->where('id', (int) $manifest['product_id'])
                    ->delete();

                if ($deletedProduct !== 1) {
                    throw new RuntimeException('Total Product Purge nije obrisao tačno jedan products red.');
                }

                $counts['owned_rows_deleted']++;

                $this->verifier->assertDatabaseZero($manifest);

                $reasonHash = hash('sha256', $reason);

                $this->audit->log(
                    'catalog.total_purge',
                    'Izvršen Total Product Purge',
                    null,
                    metadata: [
                        'reason_sha256' => $reasonHash,
                        'business_rows_unlinked' => $counts['business_rows_unlinked'],
                        'business_rows_redacted' => $counts['business_rows_redacted'],
                        'owned_rows_deleted' => $counts['owned_rows_deleted'],
                        'async_rows_deleted' => $counts['async_rows_deleted'],
                        'derived_rows_deleted' => $counts['derived_rows_deleted'],
                        'audit_rows_deleted' => $counts['audit_rows_deleted'],
                        'audit_rows_redacted' => $counts['audit_rows_redacted'],
                        'product_identity_retained' => false,
                        'normal_restore_available' => false,
                        'backup_retention_boundary_separate' => true,
                    ],
                    user: $actor,
                );

                $committed = true;

                return [
                    'manifest' => $manifest,
                    'counts' => $counts,
                ];
            }, 5);
        } catch (Throwable $exception) {
            if (!$committed && is_array($quarantine)) {
                $this->restoreQuarantine($quarantine);
            }

            throw $exception;
        }

        $manifest = (array) $result['manifest'];
        $counts = (array) $result['counts'];

        $this->deleteQuarantine($quarantine);
        $this->removeStaleDataQualityExport($manifest);
        $this->verifier->assertDatabaseZero($manifest);
        $this->verifier->assertLiveFilesystemZero($manifest);

        return [
            'product_id_deleted' => true,
            'business_rows_unlinked' => (int) ($counts['business_rows_unlinked'] ?? 0),
            'business_rows_redacted' => (int) ($counts['business_rows_redacted'] ?? 0),
            'owned_rows_deleted' => (int) ($counts['owned_rows_deleted'] ?? 0),
            'async_rows_deleted' => (int) ($counts['async_rows_deleted'] ?? 0),
            'derived_rows_deleted' => (int) ($counts['derived_rows_deleted'] ?? 0),
            'audit_rows_deleted' => (int) ($counts['audit_rows_deleted'] ?? 0),
            'audit_rows_redacted' => (int) ($counts['audit_rows_redacted'] ?? 0),
            'filesystem_zero_trace' => true,
            'database_zero_trace' => true,
            'normal_restore_available' => false,
            'backup_retention_boundary_separate' => true,
        ];
    }

    private function assertSchemaFoundation(): void
    {
        $database = DB::getDatabaseName();

        foreach ([
            'order_items' => 'order_items_product_id_foreign',
            'stock_movements' => 'stock_movements_product_id_foreign',
        ] as $table => $constraint) {
            $column = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', 'product_id')
                ->first(['DATA_TYPE', 'COLUMN_TYPE', 'IS_NULLABLE']);

            if ($column === null) {
                throw new RuntimeException($table.'.product_id schema foundation missing.');
            }

            $dataType = strtolower(trim((string) $column->DATA_TYPE));
            $columnType = strtolower(trim((string) $column->COLUMN_TYPE));
            $unsigned = preg_match('/(^|\s)unsigned($|\s)/', $columnType) === 1;

            if ($dataType !== 'bigint' || !$unsigned || strtoupper((string) $column->IS_NULLABLE) !== 'YES') {
                throw new RuntimeException($table.'.product_id must be BIGINT UNSIGNED NULL.');
            }

            $fk = DB::table('information_schema.KEY_COLUMN_USAGE as k')
                ->join('information_schema.REFERENTIAL_CONSTRAINTS as r', function ($join): void {
                    $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')
                        ->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME')
                        ->on('r.TABLE_NAME', '=', 'k.TABLE_NAME');
                })
                ->where('k.TABLE_SCHEMA', $database)
                ->where('k.TABLE_NAME', $table)
                ->where('k.COLUMN_NAME', 'product_id')
                ->where('k.CONSTRAINT_NAME', $constraint)
                ->where('k.REFERENCED_TABLE_NAME', 'products')
                ->where('k.REFERENCED_COLUMN_NAME', 'id')
                ->first(['r.DELETE_RULE']);

            if ($fk === null || strtoupper((string) $fk->DELETE_RULE) !== 'SET NULL') {
                throw new RuntimeException($table.'.product_id must use ON DELETE SET NULL.');
            }
        }
    }

    /** @param array<string,mixed> $manifest */
    private function lockMatchedRows(array $manifest): void
    {
        $productId = (int) $manifest['product_id'];

        foreach ([
            'order_items',
            'stock_movements',
            'stock_receipt_items',
            'inventory_count_items',
            'product_warranties',
            'after_sales_case_items',
            'after_sales_action_items',
            'operational_alerts',
            'warranty_rules',
        ] as $table) {
            if (
                !Schema::hasTable($table)
                || !Schema::hasColumn($table, 'id')
                || !Schema::hasColumn($table, 'product_id')
            ) {
                continue;
            }

            DB::table($table)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->get(['id']);
        }
    }

    /**
     * @param array<string,mixed> $manifest
     * @return array{source:string,destination:string,root:string}|null
     */
    private function quarantinePublicProductDirectory(array $manifest): ?array
    {
        $productId = (int) $manifest['product_id'];
        $source = storage_path('app/public/products/'.$productId);

        if (!is_dir($source)) {
            return null;
        }

        $root = storage_path('app/private/total-product-purge-quarantine/'.bin2hex(random_bytes(16)));
        $destination = $root.'/products-'.$productId;

        if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) {
            throw new RuntimeException('Nije moguće kreirati privatni Total Product Purge quarantine.');
        }

        if (!@rename($source, $destination)) {
            @rmdir($root);
            throw new RuntimeException('Nije moguće atomically premestiti product media direktorijum u privatni quarantine.');
        }

        return [
            'source' => $source,
            'destination' => $destination,
            'root' => $root,
        ];
    }

    /** @param array{source:string,destination:string,root:string}|null $quarantine */
    private function restoreQuarantine(?array $quarantine): void
    {
        if ($quarantine === null || !is_dir($quarantine['destination'])) {
            return;
        }

        $parent = dirname($quarantine['source']);

        if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new RuntimeException('DB rollback je uspeo, ali nije moguće obnoviti product media parent direktorijum.');
        }

        if (!@rename($quarantine['destination'], $quarantine['source'])) {
            throw new RuntimeException('DB rollback je uspeo, ali vraćanje quarantined product media fajlova nije uspelo.');
        }

        @rmdir($quarantine['root']);
    }

    /** @param array{source:string,destination:string,root:string}|null $quarantine */
    private function deleteQuarantine(?array $quarantine): void
    {
        if ($quarantine === null) {
            return;
        }

        if (is_dir($quarantine['destination'])) {
            $this->deleteDirectory($quarantine['destination']);
        }

        if (is_dir($quarantine['root']) && !@rmdir($quarantine['root'])) {
            throw new RuntimeException('Total Product Purge je commitovan, ali privatni quarantine root nije moguće ukloniti: '.$quarantine['root']);
        }
    }

    private function deleteDirectory(string $path): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $ok = $item->isDir()
                ? @rmdir($item->getPathname())
                : @unlink($item->getPathname());

            if (!$ok) {
                throw new RuntimeException('Nije moguće ukloniti quarantined fajl: '.$item->getPathname());
            }
        }

        if (!@rmdir($path)) {
            throw new RuntimeException('Nije moguće ukloniti quarantined direktorijum: '.$path);
        }
    }

    /** @param array<string,mixed> $manifest @param array<string,int> $counts */
    private function unlinkProductReferences(array $manifest, array &$counts): void
    {
        $productId = (int) $manifest['product_id'];

        $counts['business_rows_unlinked'] += DB::table('products')
            ->where('source_product_id', $productId)
            ->update(['source_product_id' => null]);

        foreach ([
            'order_items',
            'stock_movements',
            'stock_receipt_items',
            'inventory_count_items',
            'product_warranties',
            'after_sales_case_items',
            'after_sales_action_items',
        ] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'product_id')) {
                continue;
            }

            $count = (int) DB::table($table)->where('product_id', $productId)->count();
            if ($count <= 0) {
                continue;
            }

            $this->assertNullable($table, 'product_id');
            $counts['business_rows_unlinked'] += DB::table($table)
                ->where('product_id', $productId)
                ->update(['product_id' => null]);
        }

        if (Schema::hasTable('warranty_rules') && Schema::hasColumn('warranty_rules', 'product_id')) {
            $count = (int) DB::table('warranty_rules')->where('product_id', $productId)->count();

            if ($count > 0) {
                $this->assertNullable('warranty_rules', 'product_id');
                $updates = ['product_id' => null];

                if (Schema::hasColumn('warranty_rules', 'status')) {
                    $updates['status'] = 'inactive';
                }
                if (Schema::hasColumn('warranty_rules', 'is_active')) {
                    $updates['is_active'] = false;
                }

                $counts['business_rows_unlinked'] += DB::table('warranty_rules')
                    ->where('product_id', $productId)
                    ->update($updates);
            }
        }
    }

    /** @param array<string,mixed> $manifest @param array<string,int> $counts */
    private function redactBusinessHistory(array $manifest, array &$counts): void
    {
        $productId = (int) $manifest['product_id'];

        $this->redactLinkedRows(
            'order_items',
            $manifest,
            ['product_sku', 'product_name'],
            $counts,
            $productId,
        );

        $this->redactLinkedRows(
            'product_warranties',
            $manifest,
            ['product_sku_snapshot', 'product_name_snapshot', 'serial_number_snapshot'],
            $counts,
            $productId,
        );

        foreach ([
            'stock_movements',
            'stock_receipt_items',
            'inventory_count_items',
            'after_sales_case_items',
            'after_sales_action_items',
            'warranty_rules',
        ] as $table) {
            $this->redactAllMatchedTextColumns($table, $manifest, $counts);
        }

        $this->redactAllMatchedTextColumns('order_email_outbox', $manifest, $counts);
    }

    /** @param array<string,mixed> $manifest @param array<string,int> $counts */
    private function cleanAuditAndAsyncTraces(array $manifest, array &$counts): void
    {
        $productId = (int) $manifest['product_id'];

        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'id')) {
            $productAuditIds = DB::table('audit_logs')
                ->where(function ($query) use ($productId): void {
                    if (Schema::hasColumn('audit_logs', 'auditable_id') && Schema::hasColumn('audit_logs', 'auditable_type')) {
                        $query->where('auditable_id', $productId)
                            ->where(function ($type): void {
                                $type->where('auditable_type', 'like', '%Product')
                                    ->orWhere('auditable_type', 'product');
                            });
                    }
                })
                ->pluck('id')
                ->all();

            if ($productAuditIds !== []) {
                $counts['audit_rows_deleted'] += DB::table('audit_logs')
                    ->whereIn('id', $productAuditIds)
                    ->delete();
            }

            $this->redactAllMatchedTextColumns('audit_logs', $manifest, $counts, auditMode: true);
        }

        foreach (['notifications', 'mobile_push_outbox', 'jobs', 'failed_jobs', 'idempotency_keys'] as $table) {
            $counts['async_rows_deleted'] += $this->deleteRowsMatchingTokens($table, $manifest);
        }

        if (Schema::hasTable('operational_alerts') && Schema::hasColumn('operational_alerts', 'id')) {
            $query = DB::table('operational_alerts');
            $query->where(function ($nested) use ($productId, $manifest): void {
                $has = false;

                if (Schema::hasColumn('operational_alerts', 'product_id')) {
                    $nested->where('product_id', $productId);
                    $has = true;
                }

                foreach ($this->textColumns('operational_alerts') as $column) {
                    foreach ((array) $manifest['all_tokens'] as $token) {
                        if (!is_string($token) || $token === '') continue;
                        $sql = 'CAST(`'.str_replace('`', '``', $column).'` AS CHAR) LIKE ?';
                        if ($has) {
                            $nested->orWhereRaw($sql, ['%'.$token.'%']);
                        } else {
                            $nested->whereRaw($sql, ['%'.$token.'%']);
                            $has = true;
                        }
                    }
                }
            });

            $matchedIds = $query->pluck('id')->all();
            if ($matchedIds !== []) {
                $counts['async_rows_deleted'] += DB::table('operational_alerts')->whereIn('id', $matchedIds)->delete();
            }
        }
    }

    /** @param array<string,mixed> $manifest @param array<string,int> $counts */
    private function deleteDerivedDiagnostics(array $manifest, array &$counts): void
    {
        $counts['derived_rows_deleted'] += $this->deleteRowsMatchingTokens('data_quality_snapshots', $manifest);
    }

    /** @param array<string,mixed> $manifest @param array<string,int> $counts */
    private function deleteProductOwnedRows(array $manifest, array &$counts): void
    {
        $productId = (int) $manifest['product_id'];

        foreach (['product_categories', 'product_spec_values', 'product_images'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'product_id')) {
                $counts['owned_rows_deleted'] += DB::table($table)
                    ->where('product_id', $productId)
                    ->delete();
            }
        }
    }

    /**
     * @param array<string,mixed> $manifest
     * @param list<string> $identityColumns
     * @param array<string,int> $counts
     */
    private function redactLinkedRows(
        string $table,
        array $manifest,
        array $identityColumns,
        array &$counts,
        int $productId,
    ): void {
        if (
            !Schema::hasTable($table)
            || !Schema::hasColumn($table, 'id')
            || !Schema::hasColumn($table, 'product_id')
        ) {
            return;
        }

        $ids = DB::table($table)->where('product_id', $productId)->pluck('id')->all();

        foreach ($ids as $id) {
            $updates = [];
            foreach ($identityColumns as $column) {
                if (!Schema::hasColumn($table, $column)) continue;
                $updates[$column] = $this->columnNullable($table, $column) ? null : self::REDACTED;
            }
            if ($updates !== []) {
                DB::table($table)->where('id', $id)->update($updates);
                $counts['business_rows_redacted']++;
            }
        }

        $this->redactAllMatchedTextColumns($table, $manifest, $counts);
    }

    /**
     * @param array<string,mixed> $manifest
     * @param array<string,int> $counts
     */
    private function redactAllMatchedTextColumns(
        string $table,
        array $manifest,
        array &$counts,
        bool $auditMode = false,
    ): void {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'id')) {
            return;
        }

        $columns = $this->textColumns($table);

        if ($columns === []) {
            return;
        }

        $tokens = array_values(array_unique(array_filter((array) $manifest['all_tokens'], static function ($value): bool {
            return is_string($value) && mb_strlen(trim($value)) >= 3;
        })));

        if ($tokens === []) {
            return;
        }

        $ids = DB::table($table)
            ->where(function ($query) use ($columns, $tokens): void {
                $first = true;

                foreach ($columns as $column) {
                    foreach ($tokens as $token) {
                        $sql = 'CAST(`'.str_replace('`', '``', $column).'` AS CHAR) LIKE ?';

                        if ($first) {
                            $query->whereRaw($sql, ['%'.$token.'%']);
                            $first = false;
                        } else {
                            $query->orWhereRaw($sql, ['%'.$token.'%']);
                        }
                    }
                }
            })
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            $row = DB::table($table)->where('id', $id)->first($columns);

            if ($row === null) {
                continue;
            }

            $updates = [];

            foreach ($columns as $column) {
                $value = $row->{$column} ?? null;

                if (!is_string($value) || !$this->containsAnyToken($value, $tokens)) {
                    continue;
                }

                if ($auditMode && $column === 'subject') {
                    $updates[$column] = 'Istorijski događaj — identitet trajno uklonjen';
                    continue;
                }

                $updates[$column] = $this->sanitizeTextOrJson(
                    $value,
                    $manifest,
                );
            }

            if ($updates !== []) {
                DB::table($table)->where('id', $id)->update($updates);

                if ($auditMode) {
                    $counts['audit_rows_redacted']++;
                } else {
                    $counts['business_rows_redacted']++;
                }
            }
        }
    }

    /** @param array<string,mixed> $manifest */
    private function deleteRowsMatchingTokens(string $table, array $manifest): int
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'id')) {
            return 0;
        }

        $columns = $this->textColumns($table);

        if ($columns === []) {
            return 0;
        }

        $tokens = array_values(array_unique(array_filter((array) $manifest['all_tokens'], static function ($value): bool {
            return is_string($value) && mb_strlen(trim($value)) >= 3;
        })));

        if ($tokens === []) {
            return 0;
        }

        $ids = DB::table($table)
            ->where(function ($query) use ($columns, $tokens): void {
                $first = true;

                foreach ($columns as $column) {
                    foreach ($tokens as $token) {
                        $sql = 'CAST(`'.str_replace('`', '``', $column).'` AS CHAR) LIKE ?';

                        if ($first) {
                            $query->whereRaw($sql, ['%'.$token.'%']);
                            $first = false;
                        } else {
                            $query->orWhereRaw($sql, ['%'.$token.'%']);
                        }
                    }
                }
            })
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return 0;
        }

        return DB::table($table)->whereIn('id', $ids)->delete();
    }

    /** @param array<string,mixed> $manifest */
    private function sanitizeTextOrJson(string $value, array $manifest): string
    {
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $sanitized = $this->sanitizeMixed($decoded, $manifest);

            return json_encode(
                $sanitized,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        }

        return $this->replaceTokens($value, (array) $manifest['all_tokens']);
    }

    /** @param array<string,mixed> $manifest */
    private function sanitizeMixed(mixed $value, array $manifest, ?string $key = null): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $childKey => $childValue) {
                $sanitized[$childKey] = $this->sanitizeMixed(
                    $childValue,
                    $manifest,
                    is_string($childKey) ? $childKey : null,
                );
            }
            return $sanitized;
        }

        if ($key !== null) {
            $keyLower = strtolower($key);
            if (
                in_array($keyLower, ['product_id', 'source_product_id'], true)
                && (int) $value === (int) $manifest['product_id']
            ) {
                return null;
            }
            if (
                in_array($keyLower, ['product_sku', 'product_name', 'product_slug', 'sku'], true)
                && is_string($value)
                && $this->containsAnyToken($value, (array) $manifest['all_tokens'])
            ) {
                return self::REDACTED;
            }
        }

        if (is_string($value)) {
            return $this->replaceTokens($value, (array) $manifest['all_tokens']);
        }
        return $value;
    }

    /** @param list<string> $tokens */
    private function replaceTokens(string $value, array $tokens): string
    {
        foreach ($tokens as $token) {
            if (!is_string($token) || trim($token) === '') {
                continue;
            }

            $value = str_ireplace($token, self::REDACTED, $value);
        }

        return $value;
    }

    /** @param list<string> $tokens */
    private function containsAnyToken(string $value, array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (is_string($token) && $token !== '' && stripos($value, $token) !== false) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function textColumns(string $table): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->whereIn('DATA_TYPE', [
                'char',
                'varchar',
                'tinytext',
                'text',
                'mediumtext',
                'longtext',
                'json',
            ])
            ->orderBy('ORDINAL_POSITION')
            ->pluck('COLUMN_NAME')
            ->map(static fn ($value): string => (string) $value)
            ->all();
    }

    private function assertNullable(string $table, string $column): void
    {
        if (!$this->columnNullable($table, $column)) {
            throw new RuntimeException(
                'Total Product Purge ne može odvezati '.$table.'.'.$column.' jer kolona nije nullable.'
            );
        }
    }

    private function columnNullable(string $table, string $column): bool
    {
        $database = DB::getDatabaseName();

        $row = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first(['IS_NULLABLE']);

        return $row !== null && strtoupper((string) $row->IS_NULLABLE) === 'YES';
    }

    /** @param array<string,mixed> $manifest */
    private function removeStaleDataQualityExport(array $manifest): void
    {
        $path = storage_path('app/data-quality/v2.1.6-data-quality.json');

        if (!is_file($path)) {
            return;
        }

        $content = @file_get_contents($path);

        if (!is_string($content)) {
            throw new RuntimeException('Nije moguće proveriti stale Data Quality JSON export.');
        }

        if (!$this->containsAnyToken($content, (array) $manifest['all_tokens'])) {
            return;
        }

        if (!@unlink($path)) {
            throw new RuntimeException('Total Product Purge je commitovan, ali stale Data Quality JSON nije moguće ukloniti.');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class TotalProductPurgeVerifier
{
    private const KNOWN_DIRECT_TABLES = [
        'after_sales_action_items',
        'after_sales_case_items',
        'inventory_count_items',
        'operational_alerts',
        'order_items',
        'product_categories',
        'product_images',
        'product_spec_values',
        'product_warranties',
        'products',
        'stock_movements',
        'stock_receipt_items',
        'warranty_rules',
    ];

    private const KNOWN_TEXT_TABLES = [
        'after_sales_action_items',
        'after_sales_case_items',
        'audit_logs',
        'data_quality_snapshots',
        'failed_jobs',
        'idempotency_keys',
        'inventory_count_items',
        'jobs',
        'mobile_push_outbox',
        'notifications',
        'operational_alerts',
        'order_email_outbox',
        'order_items',
        'product_categories',
        'product_images',
        'product_spec_values',
        'product_warranties',
        'products',
        'stock_movements',
        'stock_receipt_items',
        'warranty_rules',
    ];

    /** @return array<string,mixed> */
    public function capture(Product $product): array
    {
        $productId = (int) $product->getKey();
        $sku = trim((string) $product->sku);
        $name = trim((string) $product->name);
        $slug = trim((string) $product->slug);

        $images = ProductImage::query()
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->get(['id', 'storage_disk', 'file_path']);

        $identityTokens = [];
        $this->appendToken($identityTokens, $sku, 3);
        $this->appendToken($identityTokens, $slug, 3);

        $displayTokens = [];
        $this->appendToken($displayTokens, $name, 5);

        $pathTokens = [];
        $this->appendToken($pathTokens, 'products/'.$productId.'/', 3);

        foreach ($images as $image) {
            $path = trim((string) $image->file_path);
            $this->appendToken($pathTokens, $path, 3);

            if ($path !== '') {
                $this->appendToken($pathTokens, basename($path), 3);
            }
        }

        $structuredTokens = [
            '"product_id":'.$productId,
            '"product_id": '.$productId,
            "'product_id' => ".$productId,
            'product_id='.$productId,
            '"source_product_id":'.$productId,
            '"source_product_id": '.$productId,
            'source_product_id='.$productId,
        ];

        $allTokens = array_values(array_unique(array_filter(array_merge(
            array_keys($identityTokens),
            array_keys($displayTokens),
            array_keys($pathTokens),
            $structuredTokens,
        ), static fn ($value): bool => is_string($value) && trim($value) !== '')));

        $direct = $this->directReferences($productId);
        $text = $this->textTraces($allTokens);

        $unknownDirect = array_values(array_filter(
            $direct,
            static fn (array $row): bool =>
                (int) $row['count'] > 0
                && !in_array((string) $row['table'], self::KNOWN_DIRECT_TABLES, true),
        ));

        $unknownText = array_values(array_filter(
            $text,
            static fn (array $row): bool =>
                (int) $row['count'] > 0
                && !in_array((string) $row['table'], self::KNOWN_TEXT_TABLES, true),
        ));

        $relatedOrderIds = [];

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'order_id')) {
            $relatedOrderIds = DB::table('order_items')
                ->where('product_id', $productId)
                ->pluck('order_id')
                ->map(static fn ($value): int => (int) $value)
                ->filter(static fn (int $value): bool => $value > 0)
                ->unique()
                ->values()
                ->all();
        }

        $sentOutbox = 0;

        if (
            $relatedOrderIds !== []
            && Schema::hasTable('order_email_outbox')
            && Schema::hasColumn('order_email_outbox', 'order_id')
        ) {
            $query = DB::table('order_email_outbox')->whereIn('order_id', $relatedOrderIds);

            if (Schema::hasColumn('order_email_outbox', 'sent_at')) {
                $query->whereNotNull('sent_at');
                $sentOutbox = (int) $query->count();
            } elseif (Schema::hasColumn('order_email_outbox', 'status')) {
                $sentOutbox = (int) $query->where('status', 'sent')->count();
            }
        }

        return [
            'product_id' => $productId,
            'sku' => $sku,
            'name' => $name,
            'slug' => $slug,
            'image_rows' => $images->map(static fn ($image): array => [
                'id' => (int) $image->id,
                'storage_disk' => (string) $image->storage_disk,
                'file_path' => (string) $image->file_path,
            ])->values()->all(),
            'identity_tokens' => array_keys($identityTokens),
            'display_tokens' => array_keys($displayTokens),
            'path_tokens' => array_keys($pathTokens),
            'structured_tokens' => $structuredTokens,
            'all_tokens' => $allTokens,
            'direct_references' => $direct,
            'text_traces' => $text,
            'unknown_direct' => $unknownDirect,
            'unknown_text' => $unknownText,
            'related_order_ids' => $relatedOrderIds,
            'sent_email_outbox_count' => $sentOutbox,
        ];
    }

    /** @param array<string,mixed> $manifest */
    public function assertReady(Product $product, array $manifest): void
    {
        if ($product->deleted_at === null || (string) $product->status !== 'archived') {
            throw ValidationException::withMessages([
                'total_confirmation' => 'Total Product Purge je dozvoljen samo za artikal koji je već arhiviran.',
            ]);
        }

        if ((array) $manifest['unknown_direct'] !== []) {
            $labels = array_map(
                static fn (array $row): string => $row['table'].'.'.$row['column'].'='.$row['count'],
                (array) $manifest['unknown_direct'],
            );

            throw ValidationException::withMessages([
                'total_confirmation' => 'Total Product Purge je blokiran zbog nepoznatih direktnih zavisnosti: '.implode(', ', $labels).'.',
            ]);
        }

        if ((array) $manifest['unknown_text'] !== []) {
            $labels = array_map(
                static fn (array $row): string => $row['table'].'.'.$row['column'].'='.$row['count'],
                (array) $manifest['unknown_text'],
            );

            throw ValidationException::withMessages([
                'total_confirmation' => 'Total Product Purge je blokiran zbog neklasifikovanih tekstualnih/JSON tragova: '.implode(', ', $labels).'.',
            ]);
        }

        if ((int) $manifest['sent_email_outbox_count'] > 0) {
            throw ValidationException::withMessages([
                'total_confirmation' => 'Artikal je povezan sa već poslatom eksternom e-mail porukom. CMS ne može povući već isporučenu eksternu kopiju, pa Total Product Purge ne može garantovati potpuni trag bez posebne retention odluke.',
            ]);
        }

        foreach ((array) $manifest['image_rows'] as $image) {
            $disk = (string) ($image['storage_disk'] ?? '');
            $path = ltrim(str_replace('\\', '/', trim((string) ($image['file_path'] ?? ''))), '/');

            if ($disk !== 'public') {
                throw ValidationException::withMessages([
                    'total_confirmation' => 'Total Product Purge je blokiran jer artikal ima sliku van lokalnog public storage-a ('.$disk.'). Takav izvor prvo mora biti migriran ili zasebno uklonjen.',
                ]);
            }

            if (!str_starts_with($path, 'products/'.(int) $manifest['product_id'].'/')) {
                throw ValidationException::withMessages([
                    'total_confirmation' => 'Total Product Purge je blokiran zbog neočekivane putanje slike: '.$path.'.',
                ]);
            }

            $absolute = storage_path('app/public/'.$path);
            if (!is_file($absolute)) {
                throw ValidationException::withMessages([
                    'total_confirmation' => 'Total Product Purge je blokiran jer očekivani lokalni fajl slike ne postoji: '.$path.'.',
                ]);
            }
        }

        foreach ((array) $manifest['direct_references'] as $row) {
            if ((int) $row['count'] <= 0) {
                continue;
            }

            $table = (string) $row['table'];
            $column = (string) $row['column'];

            if (in_array($table, [
                'order_items',
                'stock_movements',
                'stock_receipt_items',
                'inventory_count_items',
                'product_warranties',
                'after_sales_case_items',
                'after_sales_action_items',
                'operational_alerts',
                'warranty_rules',
                'products',
            ], true)) {
                if (
                    in_array($column, ['product_id', 'source_product_id'], true)
                    && !$this->columnNullable($table, $column)
                    && !in_array($table, ['operational_alerts'], true)
                ) {
                    throw ValidationException::withMessages([
                        'total_confirmation' => 'Total Product Purge zahteva nullable referencu za '.$table.'.'.$column.'. Potrebna je dodatna schema foundation migracija pre izvršenja.',
                    ]);
                }
            }
        }
    }

    /** @param array<string,mixed> $manifest */
    public function assertDatabaseZero(array $manifest): void
    {
        $productId = (int) $manifest['product_id'];
        if (DB::table('products')->where('id', $productId)->exists()) {
            throw new RuntimeException('ZERO TRACE failed: products.id still exists.');
        }

        if ((string) $manifest['sku'] !== '' && DB::table('products')->where('sku', (string) $manifest['sku'])->exists()) {
            throw new RuntimeException('ZERO TRACE failed: products.sku still exists.');
        }

        $remainingDirect = array_values(array_filter(
            $this->directReferences($productId),
            static fn (array $row): bool => (int) $row['count'] > 0,
        ));

        if ($remainingDirect !== []) {
            throw new RuntimeException(
                'ZERO TRACE failed: direct product references remain: '
                .implode(', ', array_map(
                    static fn (array $row): string => $row['table'].'.'.$row['column'].'='.$row['count'],
                    $remainingDirect,
                ))
            );
        }

        $remainingText = array_values(array_filter(
            $this->textTraces((array) $manifest['all_tokens']),
            static fn (array $row): bool => (int) $row['count'] > 0,
        ));

        if ($remainingText !== []) {
            throw new RuntimeException(
                'ZERO TRACE failed: live DB text/JSON traces remain: '
                .implode(', ', array_map(
                    static fn (array $row): string => $row['table'].'.'.$row['column'].'='.$row['count'],
                    $remainingText,
                ))
            );
        }
    }

    /** @param array<string,mixed> $manifest */
    public function assertLiveFilesystemZero(array $manifest): void
    {
        $productId = (int) $manifest['product_id'];
        $productDirectory = storage_path('app/public/products/'.$productId);

        if (is_dir($productDirectory) || is_file($productDirectory)) {
            throw new RuntimeException('ZERO TRACE failed: public product directory still exists.');
        }

        $tokens = array_values(array_unique(array_filter((array) $manifest['all_tokens'], static function ($value): bool {
            return is_string($value) && mb_strlen(trim($value)) >= 3;
        })));

        $roots = [
            storage_path('app'),
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            public_path('storage'),
            public_path('uploads'),
        ];

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $item) {
                if (!$item->isFile()) {
                    continue;
                }

                $path = $item->getPathname();

                if (str_starts_with($path, storage_path('app/backups').DIRECTORY_SEPARATOR)) {
                    continue;
                }

                if (str_contains($path, 'total-product-purge-quarantine')) {
                    continue;
                }

                foreach ($tokens as $token) {
                    if ($token !== '' && str_contains($path, $token)) {
                        throw new RuntimeException('ZERO TRACE failed: live filesystem path contains selected product identity: '.$path);
                    }
                }

                if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                    $match = $this->pdfContainsTokens($path, $tokens);

                    if ($match === true) {
                        throw new RuntimeException('ZERO TRACE failed: live PDF contains selected product identity: '.$path);
                    }

                    continue;
                }

                if ((int) $item->getSize() > 25 * 1024 * 1024) {
                    continue;
                }

                $content = @file_get_contents($path);
                if (!is_string($content)) {
                    continue;
                }

                foreach ($tokens as $token) {
                    if ($token !== '' && str_contains($content, $token)) {
                        throw new RuntimeException('ZERO TRACE failed: live filesystem content contains selected product identity: '.$path);
                    }
                }
            }
        }
    }

    /** @return list<array{table:string,column:string,parent:string,count:int,delete_rule:string}> */
    private function directReferences(int $productId): array
    {
        $database = DB::getDatabaseName();

        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE as k')
            ->leftJoin('information_schema.REFERENTIAL_CONSTRAINTS as r', function ($join): void {
                $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')
                    ->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME')
                    ->on('r.TABLE_NAME', '=', 'k.TABLE_NAME');
            })
            ->where('k.TABLE_SCHEMA', $database)
            ->where('k.REFERENCED_TABLE_NAME', 'products')
            ->where('k.REFERENCED_COLUMN_NAME', 'id')
            ->orderBy('k.TABLE_NAME')
            ->orderBy('k.COLUMN_NAME')
            ->get([
                'k.TABLE_NAME',
                'k.COLUMN_NAME',
                'k.REFERENCED_TABLE_NAME',
                'r.DELETE_RULE',
            ]);

        $rows = [];

        foreach ($foreignKeys as $fk) {
            $table = (string) $fk->TABLE_NAME;
            $column = (string) $fk->COLUMN_NAME;
            $count = 0;

            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $count = (int) DB::table($table)->where($column, $productId)->count();
            }

            $rows[] = [
                'table' => $table,
                'column' => $column,
                'parent' => 'products',
                'count' => $count,
                'delete_rule' => strtoupper((string) ($fk->DELETE_RULE ?? 'UNKNOWN')),
            ];
        }

        return $rows;
    }

    /** @return list<array{table:string,column:string,count:int}> */
    private function textTraces(array $tokens): array
    {
        $tokens = array_values(array_unique(array_filter($tokens, static function ($value): bool {
            return is_string($value) && mb_strlen(trim($value)) >= 3;
        })));

        if ($tokens === []) {
            return [];
        }

        $database = DB::getDatabaseName();

        $columns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->whereIn('DATA_TYPE', [
                'char',
                'varchar',
                'tinytext',
                'text',
                'mediumtext',
                'longtext',
                'json',
            ])
            ->orderBy('TABLE_NAME')
            ->orderBy('ORDINAL_POSITION')
            ->get(['TABLE_NAME', 'COLUMN_NAME']);

        $rows = [];

        foreach ($columns as $column) {
            $table = (string) $column->TABLE_NAME;
            $name = (string) $column->COLUMN_NAME;

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $name)) {
                continue;
            }

            try {
                $query = DB::table($table)->where(function ($nested) use ($name, $tokens): void {
                    foreach ($tokens as $index => $token) {
                        $sql = 'CAST(`'.str_replace('`', '``', $name).'` AS CHAR) LIKE ?';
                        $binding = '%'.$token.'%';

                        if ($index === 0) {
                            $nested->whereRaw($sql, [$binding]);
                        } else {
                            $nested->orWhereRaw($sql, [$binding]);
                        }
                    }
                });

                $count = (int) $query->count();
            } catch (Throwable) {
                continue;
            }

            if ($count > 0) {
                $rows[] = [
                    'table' => $table,
                    'column' => $name,
                    'count' => $count,
                ];
            }
        }

        return $rows;
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

    /** @param array<string,true> $bucket */
    private function appendToken(array &$bucket, mixed $value, int $minimum): void
    {
        $value = trim((string) $value);

        if ($value === '' || mb_strlen($value) < $minimum) {
            return;
        }

        $bucket[$value] = true;
    }

    /** @param list<string> $tokens */
    private function pdfContainsTokens(string $path, array $tokens): bool
    {
        $bytes = @file_get_contents($path);

        if (!is_string($bytes)) {
            return false;
        }

        $needles = [];

        foreach ($tokens as $token) {
            $needles[] = $token;
            $needles[] = $this->pdfEscape($token);

            $cp = @iconv('UTF-8', 'Windows-1250//TRANSLIT//IGNORE', $token);

            if (is_string($cp) && $cp !== '') {
                $needles[] = $cp;
                $needles[] = $this->pdfEscape($cp);
            }
        }

        $needles = array_values(array_unique(array_filter($needles, static fn ($value): bool => $value !== '')));

        foreach ($needles as $needle) {
            if (str_contains($bytes, $needle)) {
                return true;
            }
        }

        $matches = [];
        $count = preg_match_all(
            '/<{2}\s*(.*?)>>\s*stream(?:\r\n|\n|\r)(.*?)(?:\r\n|\n|\r)endstream/s',
            $bytes,
            $matches,
            PREG_SET_ORDER,
        );

        if ($count === false) {
            return false;
        }

        foreach ($matches as $match) {
            $dictionary = (string) ($match[1] ?? '');
            $stream = (string) ($match[2] ?? '');

            if (str_contains($dictionary, '/DCTDecode') || str_contains($dictionary, '/JPXDecode')) {
                continue;
            }

            if (str_contains($dictionary, '/FlateDecode')) {
                $decoded = @zlib_decode(rtrim($stream, "\r\n"));

                if (!is_string($decoded)) {
                    $decoded = @gzuncompress(rtrim($stream, "\r\n"));
                }

                if (!is_string($decoded)) {
                    continue;
                }

                $stream = $decoded;
            }

            foreach ($needles as $needle) {
                if (str_contains($stream, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\\(', '\\)', '\\r', '\\n'],
            $value,
        );
    }
}

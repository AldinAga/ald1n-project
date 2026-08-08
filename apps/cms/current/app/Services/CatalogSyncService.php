<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CatalogSyncRun;
use App\Models\CatalogSyncState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class CatalogSyncService
{
    /** @var array<string,list<string>> */
    private array $keys = [
        'categories'=>['id'], 'brands'=>['id'], 'product_lines'=>['id'], 'product_types'=>['id'],
        'specification_fields'=>['id'], 'product_type_fields'=>['product_type_id','field_id'],
        'products'=>['id'], 'product_categories'=>['product_id','category_id'],
        'product_spec_values'=>['product_id','field_id'], 'product_images'=>['id'], 'settings'=>['id'],
    ];

    /** @return array<string,array<string,int>> */
    public function diff(?string $onlyTable = null): array
    {
        $summary = [];
        foreach ($this->tables($onlyTable) as $table => $keys) {
            $legacy = $this->rows('legacy', $table, $keys);
            $target = $this->rows('mysql', $table, $keys);
            $counts = ['same'=>0,'changed'=>0,'missing_target'=>0,'target_only'=>0];
            foreach ($legacy as $key => $row) {
                if (!isset($target[$key])) $counts['missing_target']++;
                elseif ($this->checksum($row) === $this->checksum($target[$key])) $counts['same']++;
                else $counts['changed']++;
            }
            foreach ($target as $key => $_) if (!isset($legacy[$key])) $counts['target_only']++;
            $summary[$table] = $counts;
        }
        return $summary;
    }

    /** @return array<string,array<string,int>> */
    public function sync(bool $apply, ?int $userId = null): array
    {
        $this->assertSeparateDatabases();
        $run = CatalogSyncRun::query()->create([
            'mode' => $apply ? 'apply' : 'dry-run', 'status' => 'running', 'started_by' => $userId,
            'legacy_database' => (string) config('database.connections.legacy.database'),
            'target_database' => (string) config('database.connections.mysql.database'), 'started_at' => now(),
        ]);
        try {
            $summary = [];
            $callback = function () use ($apply, &$summary): void {
                foreach ($this->keys as $table => $keys) $summary[$table] = $this->syncTable($table, $keys, $apply);
                if ($apply) $this->restoreCategoryParents();
            };
            $apply ? DB::transaction($callback, 3) : $callback();
            $run->update(['status'=>'completed','summary_json'=>$summary,'finished_at'=>now()]);
            return $summary;
        } catch (Throwable $e) {
            $run->update(['status'=>'failed','error_message'=>mb_substr($e->getMessage(),0,65000),'finished_at'=>now()]);
            throw $e;
        }
    }

    public function legacyGrants(): array
    {
        $row = DB::connection('legacy')->selectOne('SELECT CURRENT_USER() AS current_user, DATABASE() AS database_name');
        $grants = array_map(static fn ($grant): string => (string) array_values((array) $grant)[0], DB::connection('legacy')->select('SHOW GRANTS FOR CURRENT_USER()'));
        $joined = strtoupper(implode("\n", $grants));
        $write = preg_match('/\b(ALL PRIVILEGES|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|TRUNCATE)\b/', $joined) === 1;
        return ['current_user'=>(string)$row->current_user,'database'=>(string)$row->database_name,'grants'=>$grants,'has_write_privileges'=>$write];
    }

    /** @param list<string> $keys @return array<string,int> */
    private function syncTable(string $table, array $keys, bool $apply): array
    {
        $legacyRows = $this->rows('legacy', $table, $keys);
        $targetRows = $this->rows('mysql', $table, $keys);
        $counts = ['same'=>0,'baseline'=>0,'insert'=>0,'update'=>0,'local_only'=>0,'conflict'=>0];

        foreach ($legacyRows as $entityKey => $legacyRow) {
            $legacyHash = $this->checksum($legacyRow);
            $targetRow = $targetRows[$entityKey] ?? null;
            $state = CatalogSyncState::query()->where(['entity_type'=>$table,'entity_key'=>$entityKey])->first();
            if ($targetRow === null) {
                $counts['insert']++;
                if ($apply) $this->upsert($table, $keys, $legacyRow, true);
                $targetHash = $legacyHash;
                if ($apply) $this->state($table, $entityKey, $legacyHash, $targetHash);
                continue;
            }
            $targetHash = $this->checksum($targetRow);
            if ($state === null) {
                if (hash_equals($legacyHash, $targetHash)) {
                    $counts['baseline']++;
                    if ($apply) $this->state($table, $entityKey, $legacyHash, $targetHash);
                } else $counts['conflict']++;
                continue;
            }
            $legacyChanged = !hash_equals($state->legacy_checksum, $legacyHash);
            $targetChanged = !hash_equals($state->target_checksum, $targetHash);
            if (!$legacyChanged && !$targetChanged) { $counts['same']++; continue; }
            if ($legacyChanged && !$targetChanged) {
                $counts['update']++;
                if ($apply) {
                    $this->upsert($table, $keys, $legacyRow, false);
                    $this->state($table, $entityKey, $legacyHash, $legacyHash);
                }
                continue;
            }
            if (!$legacyChanged && $targetChanged) { $counts['local_only']++; continue; }
            $counts['conflict']++;
        }
        foreach ($targetRows as $key => $_) if (!isset($legacyRows[$key])) $counts['local_only']++;
        return $counts;
    }

    /** @param list<string> $keys @return array<string,array<string,mixed>> */
    private function rows(string $connection, string $table, array $keys): array
    {
        if (!Schema::connection($connection)->hasTable($table)) throw new RuntimeException("Tabela {$table} ne postoji na {$connection} konekciji.");
        $legacyColumns = Schema::connection('legacy')->getColumnListing($table);
        $targetColumns = Schema::connection('mysql')->getColumnListing($table);
        $columns = array_values(array_intersect($legacyColumns, $targetColumns));
        $result = [];
        DB::connection($connection)->table($table)->select($columns)->orderBy($keys[0])->chunk(500, function ($rows) use (&$result, $keys): void {
            foreach ($rows as $row) {
                $array = (array) $row;
                $key = implode('|', array_map(static fn ($column): string => (string) ($array[$column] ?? ''), $keys));
                ksort($array);
                $result[$key] = $array;
            }
        });
        return $result;
    }

    /** @param list<string> $keys @param array<string,mixed> $row */
    private function upsert(string $table, array $keys, array $row, bool $insert): void
    {
        if ($table === 'categories') $row['parent_id'] = null;
        if ($table === 'product_images') $row['storage_disk'] = 'legacy';
        if ($table === 'products') { $row['legacy_checksum'] = $this->checksum($row); $row['legacy_synced_at'] = now(); }
        $updates = array_values(array_diff(array_keys($row), $keys));
        DB::table($table)->upsert([$row], $keys, $updates);
    }

    private function restoreCategoryParents(): void
    {
        DB::connection('legacy')->table('categories')->whereNotNull('parent_id')->orderBy('id')->chunk(500, function ($rows): void {
            foreach ($rows as $row) {
                if (DB::table('categories')->where('id', (int) $row->parent_id)->exists()) DB::table('categories')->where('id', (int) $row->id)->update(['parent_id'=>(int)$row->parent_id]);
            }
        });
    }

    private function state(string $table, string $key, string $legacyHash, string $targetHash): void
    {
        CatalogSyncState::query()->updateOrCreate(['entity_type'=>$table,'entity_key'=>$key], ['legacy_checksum'=>$legacyHash,'target_checksum'=>$targetHash,'synced_at'=>now()]);
    }

    /** @param array<string,mixed> $row */
    private function checksum(array $row): string
    {
        foreach (['legacy_checksum','legacy_synced_at','locally_modified_at','storage_disk','file_hash'] as $column) unset($row[$column]);
        ksort($row);
        return hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRESERVE_ZERO_FRACTION|JSON_THROW_ON_ERROR));
    }

    /** @return array<string,list<string>> */
    private function tables(?string $only): array
    {
        if ($only === null) return $this->keys;
        if (!isset($this->keys[$only])) throw new RuntimeException('Nepoznata tabela: '.$only);
        return [$only=>$this->keys[$only]];
    }

    private function assertSeparateDatabases(): void
    {
        $target = (string) config('database.connections.mysql.database');
        $legacy = (string) config('database.connections.legacy.database');
        if ($target === '' || $legacy === '' || hash_equals($target, $legacy)) throw new RuntimeException('Ciljna i legacy baza moraju biti različite.');
    }
}

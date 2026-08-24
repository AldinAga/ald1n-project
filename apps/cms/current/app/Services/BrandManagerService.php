<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3
// MOBILE_V1_0_BRAND_LINE_EXPANSION_BATCH22_V3
final class BrandManagerService
{
    /** @return array<string,mixed> */
    public function managerData(?string $query = null, ?int $productTypeId = null): array
    {
        $types = $this->productTypes();
        $validTypeIds = array_column($types, 'id');
        if ($productTypeId !== null && !in_array($productTypeId, $validTypeIds, true)) {
            $productTypeId = null;
        }

        $brandQuery = DB::table('brands as b')
            ->select(['b.id', 'b.name', 'b.slug', 'b.description', 'b.website_url', 'b.status', 'b.sort_order'])
            ->orderBy('b.sort_order')
            ->orderBy('b.name');

        $needle = trim((string) $query);
        if ($needle !== '') {
            $brandQuery->where(function ($builder) use ($needle): void {
                $builder->whereRaw('LOWER(b.name) LIKE ?', ['%'.mb_strtolower($needle).'%'])
                    ->orWhereRaw('LOWER(COALESCE(b.description, ?)) LIKE ?', ['', '%'.mb_strtolower($needle).'%'])
                    ->orWhereRaw('LOWER(COALESCE(b.website_url, ?)) LIKE ?', ['', '%'.mb_strtolower($needle).'%']);
            });
        }
        if ($productTypeId !== null) {
            $brandQuery->whereExists(function ($builder) use ($productTypeId): void {
                $builder->selectRaw('1')
                    ->from('brand_product_type as bpt')
                    ->whereColumn('bpt.brand_id', 'b.id')
                    ->where('bpt.product_type_id', $productTypeId);
            });
        }

        $brandIds = $brandQuery->pluck('b.id')->map(static fn ($id): int => (int) $id)->all();
        $brands = [];
        foreach ($brandIds as $brandId) {
            $brands[] = $this->brandPayload($brandId, $types);
        }

        return [
            'brands' => $brands,
            'product_types' => $types,
            'filters' => [
                'q' => $needle,
                'product_type_id' => $productTypeId,
            ],
            'capabilities' => [
                'create' => true,
                'update' => true,
                'delete' => false,
                'max_lines_per_type' => 10,
            ],
        ];
    }

    /** @return array<string,mixed> */
    public function optionsData(): array
    {
        return [
            'product_types' => $this->productTypes(),
            'statuses' => [
                ['value' => 'active', 'label' => 'Aktivan'],
                ['value' => 'inactive', 'label' => 'Neaktivan'],
            ],
            'max_lines_per_type' => 10,
        ];
    }

    /** @return array<string,mixed> */
    public function brandPayload(int $brandId, ?array $types = null): array
    {
        $brand = DB::table('brands')->where('id', $brandId)->first();
        if ($brand === null) {
            abort(404);
        }

        $types ??= $this->productTypes();
        $typeIndex = [];
        foreach ($types as $type) {
            $typeIndex[(int) $type['id']] = $type;
        }

        $typeIds = DB::table('brand_product_type')
            ->where('brand_id', $brandId)
            ->orderBy('product_type_id')
            ->pluck('product_type_id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        $lineRows = DB::table('product_lines')
            ->where('brand_id', $brandId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status', 'sort_order']);
        $lineIds = $lineRows->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $lineTypeMap = [];
        if ($lineIds !== []) {
            $pairs = DB::table('product_line_product_type')
                ->whereIn('product_line_id', $lineIds)
                ->orderBy('product_line_id')
                ->orderBy('product_type_id')
                ->get(['product_line_id', 'product_type_id']);
            foreach ($pairs as $pair) {
                $lineTypeMap[(int) $pair->product_line_id][] = (int) $pair->product_type_id;
            }
        }

        $lines = [];
        $lineGroups = [];
        foreach ($lineRows as $line) {
            $ids = array_values(array_unique($lineTypeMap[(int) $line->id] ?? []));
            sort($ids);
            $payload = [
                'id' => (int) $line->id,
                'name' => (string) $line->name,
                'slug' => (string) $line->slug,
                'status' => (string) $line->status,
                'sort_order' => (int) $line->sort_order,
                'product_type_ids' => $ids,
            ];
            $lines[] = $payload;
            foreach ($ids as $typeId) {
                $lineGroups[(string) $typeId][] = $payload;
            }
        }

        $linkedTypes = [];
        foreach ($typeIds as $typeId) {
            if (isset($typeIndex[$typeId])) {
                $linkedTypes[] = $typeIndex[$typeId];
            }
        }

        return [
            'id' => (int) $brand->id,
            'name' => (string) $brand->name,
            'slug' => (string) $brand->slug,
            'description' => $brand->description !== null ? (string) $brand->description : null,
            'website_url' => $brand->website_url !== null ? (string) $brand->website_url : null,
            'status' => (string) $brand->status,
            'sort_order' => (int) $brand->sort_order,
            'product_type_ids' => $typeIds,
            'product_types' => $linkedTypes,
            'lines' => $lines,
            'line_groups' => $lineGroups,
        ];
    }

    /** @param array<string,mixed> $data */
    public function create(array $data, User $actor): Brand
    {
        $brandId = DB::transaction(function () use ($data): int {
            $normalized = $this->normalizeInput($data);
            $this->assertUniqueName($normalized['name'], null);
            $slug = $this->uniqueSlug($normalized['name'], null);
            $now = now();
            $row = [
                'name' => $normalized['name'],
                'slug' => $slug,
                'description' => $normalized['description'],
                'website_url' => $normalized['website_url'],
                'status' => $normalized['status'],
                'sort_order' => $normalized['sort_order'],
            ];
            if (Schema::hasColumn('brands', 'created_at')) $row['created_at'] = $now;
            if (Schema::hasColumn('brands', 'updated_at')) $row['updated_at'] = $now;
            $brandId = (int) DB::table('brands')->insertGetId($row);
            $this->syncRelations($brandId, $normalized, false);
            return $brandId;
        });

        app(CatalogReferenceCache::class)->forget();
        return Brand::query()->findOrFail($brandId);
    }

    /** @param array<string,mixed> $data */
    public function update(Brand $brand, array $data, User $actor): Brand
    {
        DB::transaction(function () use ($brand, $data): void {
            $brandId = (int) $brand->getKey();
            $normalized = $this->normalizeInput($data);
            $this->assertUniqueName($normalized['name'], $brandId);
            $row = [
                'name' => $normalized['name'],
                'slug' => $this->uniqueSlug($normalized['name'], $brandId),
                'description' => $normalized['description'],
                'website_url' => $normalized['website_url'],
                'status' => $normalized['status'],
                'sort_order' => $normalized['sort_order'],
            ];
            if (Schema::hasColumn('brands', 'updated_at')) $row['updated_at'] = now();
            DB::table('brands')->where('id', $brandId)->update($row);
            $this->syncRelations($brandId, $normalized, true);
        });

        app(CatalogReferenceCache::class)->forget();
        return Brand::query()->findOrFail((int) $brand->getKey());
    }

    /** @return list<array<string,mixed>> */
    private function productTypes(): array
    {
        $query = DB::table('product_types as pt')
            ->leftJoin('categories as c', 'c.id', '=', 'pt.category_id')
            ->orderBy('pt.sort_order')
            ->orderBy('pt.name');

        $columns = ['pt.id', 'pt.name', 'pt.slug', 'pt.category_id', 'c.name as category_name'];
        if (Schema::hasColumn('product_types', 'status')) {
            $columns[] = 'pt.status';
        }

        return $query->get($columns)->map(static function ($row): array {
            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'slug' => (string) $row->slug,
                'status' => property_exists($row, 'status') ? (string) $row->status : 'active',
                'category_id' => $row->category_id !== null ? (int) $row->category_id : null,
                'category_name' => $row->category_name !== null ? (string) $row->category_name : null,
            ];
        })->values()->all();
    }

    /** @param array<string,mixed> $data @return array{name:string,description:?string,website_url:?string,status:string,sort_order:int,product_type_ids:list<int>,line_names_by_type:array<int,list<string>>} */
    private function normalizeInput(array $data): array
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) ($data['name'] ?? ''))) ?: '';
        $description = trim((string) ($data['description'] ?? ''));
        $website = trim((string) ($data['website_url'] ?? ''));
        $status = (string) ($data['status'] ?? 'active');
        $sortOrder = max(0, min(1000000, (int) ($data['sort_order'] ?? 100)));
        $typeIds = array_values(array_unique(array_filter(array_map('intval', (array) ($data['product_type_ids'] ?? [])), static fn (int $id): bool => $id > 0)));
        sort($typeIds);

        if ($name === '' || $typeIds === []) {
            $errors = [];
            if ($name === '') $errors['name'] = 'Naziv brenda je obavezan.';
            if ($typeIds === []) $errors['product_type_ids'] = 'Izaberi najmanje jedan tip proizvoda.';
            throw ValidationException::withMessages($errors);
        }

        $existingTypes = DB::table('product_types')->whereIn('id', $typeIds)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        sort($existingTypes);
        if ($existingTypes !== $typeIds) {
            throw ValidationException::withMessages(['product_type_ids' => 'Izabran je nepostojeći tip proizvoda.']);
        }

        $lineMap = [];
        foreach ((array) ($data['line_names_by_type'] ?? []) as $rawTypeId => $rawNames) {
            $typeId = (int) $rawTypeId;
            if ($typeId <= 0 || !in_array($typeId, $typeIds, true)) {
                throw ValidationException::withMessages(['line_names_by_type' => 'Linije mogu biti zadate samo za izabrane tipove proizvoda.']);
            }
            $names = [];
            $normalizedSeen = [];
            foreach ((array) $rawNames as $rawName) {
                $lineName = preg_replace('/\s+/u', ' ', trim((string) $rawName)) ?: '';
                if ($lineName === '') continue;
                $key = mb_strtolower($lineName);
                if (isset($normalizedSeen[$key])) continue;
                $normalizedSeen[$key] = true;
                $names[] = $lineName;
            }
            if (count($names) > 10) {
                throw ValidationException::withMessages(['line_names_by_type.'.$typeId => 'Najviše deset kuriranih linija može biti uneto po tipu proizvoda.']);
            }
            $lineMap[$typeId] = $names;
        }

        return [
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'website_url' => $website !== '' ? $website : null,
            'status' => in_array($status, ['active', 'inactive'], true) ? $status : 'active',
            'sort_order' => $sortOrder,
            'product_type_ids' => $typeIds,
            'line_names_by_type' => $lineMap,
        ];
    }

    private function assertUniqueName(string $name, ?int $ignoreId): void
    {
        $query = DB::table('brands')->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))]);
        if ($ignoreId !== null) $query->where('id', '<>', $ignoreId);
        if ($query->exists()) {
            throw ValidationException::withMessages(['name' => 'Brend sa ovim nazivom već postoji. Koristi postojeći globalni brend.']);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId): string
    {
        $base = Str::slug($name);
        if ($base === '') $base = 'brand';
        $slug = $base;
        $counter = 2;
        while (true) {
            $query = DB::table('brands')->where('slug', $slug);
            if ($ignoreId !== null) $query->where('id', '<>', $ignoreId);
            if (!$query->exists()) return $slug;
            $slug = $base.'-'.$counter;
            $counter++;
        }
    }

    /** @param array{name:string,description:?string,website_url:?string,status:string,sort_order:int,product_type_ids:list<int>,line_names_by_type:array<int,list<string>>} $data */
    private function syncRelations(int $brandId, array $data, bool $updating): void
    {
        $selected = $data['product_type_ids'];
        $existing = DB::table('brand_product_type')->where('brand_id', $brandId)->pluck('product_type_id')->map(static fn ($id): int => (int) $id)->all();
        sort($existing);
        $removed = array_values(array_diff($existing, $selected));

        if ($updating && $removed !== []) {
            foreach ($removed as $typeId) {
                $productUse = DB::table('products')->where('brand_id', $brandId)->where('product_type_id', $typeId)->exists();
                $brandLineIds = DB::table('product_lines')->where('brand_id', $brandId)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                $lineUse = $brandLineIds !== [] && DB::table('products')->where('product_type_id', $typeId)->whereIn('product_line_id', $brandLineIds)->exists();
                if ($productUse || $lineUse) {
                    throw ValidationException::withMessages([
                        'product_type_ids' => 'Veza brenda sa tipom ID '.$typeId.' ne može biti uklonjena jer postoje istorijski ili aktivni artikli koji je koriste.',
                    ]);
                }
            }

            $brandLineIds = DB::table('product_lines')->where('brand_id', $brandId)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            if ($brandLineIds !== []) {
                DB::table('product_line_product_type')->whereIn('product_line_id', $brandLineIds)->whereIn('product_type_id', $removed)->delete();
            }
            DB::table('brand_product_type')->where('brand_id', $brandId)->whereIn('product_type_id', $removed)->delete();
        }

        foreach ($selected as $typeId) {
            DB::table('brand_product_type')->insertOrIgnore(['brand_id' => $brandId, 'product_type_id' => $typeId]);
        }

        foreach ($data['line_names_by_type'] as $typeId => $lineNames) {
            foreach ($lineNames as $lineName) {
                $lineId = $this->findOrCreateLine($brandId, $data['name'], $lineName);
                DB::table('product_line_product_type')->insertOrIgnore([
                    'product_line_id' => $lineId,
                    'product_type_id' => $typeId,
                ]);
            }
        }
    }

    private function findOrCreateLine(int $brandId, string $brandName, string $lineName): int
    {
        $existing = DB::table('product_lines')
            ->where('brand_id', $brandId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($lineName))])
            ->first();
        if ($existing !== null) {
            if (property_exists($existing, 'status') && (string) $existing->status !== 'active') {
                $update = ['status' => 'active'];
                if (Schema::hasColumn('product_lines', 'updated_at')) $update['updated_at'] = now();
                DB::table('product_lines')->where('id', (int) $existing->id)->update($update);
            }
            return (int) $existing->id;
        }

        $base = Str::slug($brandName.' '.$lineName);
        if ($base === '') $base = 'line';
        $slug = $base;
        $counter = 2;
        while (DB::table('product_lines')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        $row = [
            'brand_id' => $brandId,
            'name' => $lineName,
            'slug' => $slug,
            'status' => 'active',
            'sort_order' => ((int) DB::table('product_lines')->where('brand_id', $brandId)->max('sort_order')) + 10,
        ];
        if (Schema::hasColumn('product_lines', 'created_at')) $row['created_at'] = now();
        if (Schema::hasColumn('product_lines', 'updated_at')) $row['updated_at'] = now();
        return (int) DB::table('product_lines')->insertGetId($row);
    }
}

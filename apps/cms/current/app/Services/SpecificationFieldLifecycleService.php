<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\SpecificationField;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SpecificationFieldLifecycleService
{
    public function __construct(private readonly ProductCompletenessService $completeness) {}

    /**
     * @return array{types:int,products:int,children:int,options:int,templates:int,recalculated:int,downgraded:int}
     */
    public function purge(SpecificationField $field): array
    {
        $fieldId = (int) $field->id;
        $fieldSlug = (string) $field->slug;
        $typeIds = Schema::hasTable('product_type_fields')
            ? DB::table('product_type_fields')->where('field_id', $fieldId)->pluck('product_type_id')->map(static fn ($id): int => (int) $id)->all()
            : [];
        $productIds = Schema::hasTable('product_spec_values')
            ? DB::table('product_spec_values')->where('field_id', $fieldId)->pluck('product_id')->map(static fn ($id): int => (int) $id)->all()
            : [];
        $childCount = Schema::hasColumn('specification_fields', 'parent_field_id')
            ? (int) SpecificationField::query()->where('parent_field_id', $fieldId)->count()
            : 0;
        $optionIds = Schema::hasTable('specification_options')
            ? DB::table('specification_options')->where('field_id', $fieldId)->pluck('id')->map(static fn ($id): int => (int) $id)->all()
            : [];

        $templatesChanged = DB::transaction(function () use ($fieldId, $fieldSlug, $optionIds): int {
            if ($optionIds !== [] && Schema::hasTable('specification_option_dependencies')) {
                DB::table('specification_option_dependencies')
                    ->whereIn('parent_option_id', $optionIds)
                    ->orWhereIn('child_option_id', $optionIds)
                    ->delete();
            }
            if (Schema::hasColumn('specification_fields', 'parent_field_id')) {
                SpecificationField::query()->where('parent_field_id', $fieldId)->update(['parent_field_id' => null]);
            }
            if (Schema::hasColumn('specification_fields', 'storage_source_field_id')) {
                SpecificationField::query()->where('storage_source_field_id', $fieldId)->update([
                    'storage_source_field_id' => null,
                    'storage_role' => null,
                ]);
            }
            if (Schema::hasTable('product_spec_values')) {
                DB::table('product_spec_values')->where('field_id', $fieldId)->delete();
            }
            if (Schema::hasTable('product_type_fields')) {
                DB::table('product_type_fields')->where('field_id', $fieldId)->delete();
            }
            if (Schema::hasTable('specification_options')) {
                DB::table('specification_options')->where('field_id', $fieldId)->delete();
            }

            $templatesChanged = $this->removeTemplateTokens($fieldSlug);
            SpecificationField::query()->whereKey($fieldId)->delete();

            return $templatesChanged;
        }, 3);

        $examined = 0;
        $downgraded = 0;
        foreach (array_values(array_unique($typeIds)) as $typeId) {
            $type = ProductType::query()->find($typeId);
            if ($type === null) {
                continue;
            }
            $result = $this->completeness->recalculateType($type);
            $examined += $result['examined'];
            $downgraded += $result['downgraded'];
        }

        // Ako instalacija istorijski nije imala FK, obuhvati i proizvode koji nisu bili u pivot listi.
        foreach (array_values(array_unique($productIds)) as $productId) {
            $product = Product::query()->find($productId);
            if ($product === null || in_array((int) $product->product_type_id, $typeIds, true)) {
                continue;
            }
            $result = $this->completeness->recalculate($product);
            $examined++;
            if ($result['status_changed']) {
                $downgraded++;
            }
        }

        return [
            'types' => count(array_unique($typeIds)),
            'products' => count(array_unique($productIds)),
            'children' => $childCount,
            'options' => count($optionIds),
            'templates' => $templatesChanged,
            'recalculated' => $examined,
            'downgraded' => $downgraded,
        ];
    }

    /** @return array<string,int> */
    public function integrityCounts(): array
    {
        return [
            'orphan_product_values' => $this->countOrphans('product_spec_values', 'field_id', 'specification_fields', 'id')
                + $this->countOrphans('product_spec_values', 'product_id', 'products', 'id'),
            'orphan_type_fields' => $this->countOrphans('product_type_fields', 'field_id', 'specification_fields', 'id')
                + $this->countOrphans('product_type_fields', 'product_type_id', 'product_types', 'id'),
            'orphan_options' => $this->countOrphans('specification_options', 'field_id', 'specification_fields', 'id'),
            'orphan_dependencies' => $this->dependencyOrphanCount(),
            'orphan_parents' => $this->parentOrphanCount(),
            'orphan_storage_sources' => $this->storageSourceOrphanCount(),
            'unassigned_product_values' => $this->unassignedProductValueCount(),
        ];
    }

    /** @return array<string,int> */
    public function repairIntegrity(): array
    {
        DB::transaction(function (): void {
            $this->deleteOrphans('product_spec_values', 'field_id', 'specification_fields', 'id');
            $this->deleteOrphans('product_spec_values', 'product_id', 'products', 'id');
            $this->deleteOrphans('product_type_fields', 'field_id', 'specification_fields', 'id');
            $this->deleteOrphans('product_type_fields', 'product_type_id', 'product_types', 'id');
            $this->deleteOrphans('specification_options', 'field_id', 'specification_fields', 'id');
            $this->deleteDependencyOrphans();
            $this->clearParentOrphans();
            $this->clearStorageSourceOrphans();
            $this->deleteUnassignedProductValues();
        }, 3);

        return $this->integrityCounts();
    }

    private function removeTemplateTokens(string $slug): int
    {
        if ($slug === '' || !Schema::hasTable('product_types') || !Schema::hasColumn('product_types', 'name_template')) {
            return 0;
        }

        $tokens = ['{'.$slug.'}', '{'.$slug.'_detail}'];
        $changed = 0;
        ProductType::query()
            ->where(function ($query) use ($tokens): void {
                foreach ($tokens as $token) {
                    $query->orWhere('name_template', 'like', '%'.$token.'%');
                }
            })
            ->orderBy('id')
            ->chunkById(100, function ($types) use ($tokens, &$changed): void {
                foreach ($types as $type) {
                    $template = (string) $type->name_template;
                    $updated = str_replace($tokens, ' ', $template);
                    $updated = preg_replace('/\s+/', ' ', trim($updated)) ?? trim($updated);
                    if ($updated === $template) {
                        continue;
                    }
                    $type->forceFill(['name_template' => $updated])->save();
                    $changed++;
                }
            });

        return $changed;
    }

    private function countOrphans(string $table, string $column, string $parentTable, string $parentColumn): int
    {
        if (!Schema::hasTable($table) || !Schema::hasTable($parentTable) || !Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table.' as child')
            ->leftJoin($parentTable.' as parent', 'parent.'.$parentColumn, '=', 'child.'.$column)
            ->whereNotNull('child.'.$column)
            ->whereNull('parent.'.$parentColumn)
            ->count();
    }

    private function deleteOrphans(string $table, string $column, string $parentTable, string $parentColumn): void
    {
        if ($this->countOrphans($table, $column, $parentTable, $parentColumn) === 0) {
            return;
        }

        DB::table($table)->whereNotExists(function ($query) use ($column, $parentTable, $parentColumn, $table): void {
            $query->selectRaw('1')
                ->from($parentTable)
                ->whereColumn($parentTable.'.'.$parentColumn, $table.'.'.$column);
        })->delete();
    }

    private function dependencyOrphanCount(): int
    {
        if (!Schema::hasTable('specification_option_dependencies') || !Schema::hasTable('specification_options')) {
            return 0;
        }

        return (int) DB::table('specification_option_dependencies as dependencies')
            ->leftJoin('specification_options as parents', 'parents.id', '=', 'dependencies.parent_option_id')
            ->leftJoin('specification_options as children', 'children.id', '=', 'dependencies.child_option_id')
            ->whereNull('parents.id')
            ->orWhereNull('children.id')
            ->count();
    }

    private function deleteDependencyOrphans(): void
    {
        if (!Schema::hasTable('specification_option_dependencies') || !Schema::hasTable('specification_options')) {
            return;
        }

        DB::table('specification_option_dependencies')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('specification_options')->whereColumn('specification_options.id', 'specification_option_dependencies.parent_option_id'))
            ->orWhereNotExists(fn ($query) => $query->selectRaw('1')->from('specification_options')->whereColumn('specification_options.id', 'specification_option_dependencies.child_option_id'))
            ->delete();
    }

    private function parentOrphanCount(): int
    {
        if (!Schema::hasTable('specification_fields') || !Schema::hasColumn('specification_fields', 'parent_field_id')) {
            return 0;
        }

        return (int) DB::table('specification_fields as child')
            ->leftJoin('specification_fields as parent', 'parent.id', '=', 'child.parent_field_id')
            ->whereNotNull('child.parent_field_id')
            ->whereNull('parent.id')
            ->count();
    }

    private function clearParentOrphans(): void
    {
        if ($this->parentOrphanCount() === 0) {
            return;
        }

        DB::table('specification_fields')
            ->whereNotNull('parent_field_id')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('specification_fields as parent')->whereColumn('parent.id', 'specification_fields.parent_field_id'))
            ->update(['parent_field_id' => null]);
    }


    private function storageSourceOrphanCount(): int
    {
        if (!Schema::hasTable('specification_fields') || !Schema::hasColumn('specification_fields', 'storage_source_field_id')) {
            return 0;
        }

        return (int) DB::table('specification_fields as total')
            ->leftJoin('specification_fields as source', 'source.id', '=', 'total.storage_source_field_id')
            ->whereNotNull('total.storage_source_field_id')
            ->whereNull('source.id')
            ->count();
    }

    private function clearStorageSourceOrphans(): void
    {
        if ($this->storageSourceOrphanCount() === 0) return;

        DB::table('specification_fields')
            ->whereNotNull('storage_source_field_id')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('specification_fields as source')->whereColumn('source.id', 'specification_fields.storage_source_field_id'))
            ->update(['storage_source_field_id' => null, 'storage_role' => null]);
    }

    private function unassignedProductValueCount(): int
    {
        if (!Schema::hasTable('product_spec_values') || !Schema::hasTable('products') || !Schema::hasTable('product_type_fields')) {
            return 0;
        }

        return (int) DB::table('product_spec_values as values')
            ->join('products', 'products.id', '=', 'values.product_id')
            ->leftJoin('product_type_fields as allowed', function ($join): void {
                $join->on('allowed.product_type_id', '=', 'products.product_type_id')
                    ->on('allowed.field_id', '=', 'values.field_id');
            })
            ->whereNotNull('products.product_type_id')
            ->whereNull('allowed.field_id')
            ->count();
    }

    private function deleteUnassignedProductValues(): void
    {
        if ($this->unassignedProductValueCount() === 0) {
            return;
        }

        DB::table('product_spec_values')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('products')->whereColumn('products.id', 'product_spec_values.product_id')->whereNotNull('products.product_type_id'))
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('products')
                    ->join('product_type_fields', function ($join): void {
                        $join->on('product_type_fields.product_type_id', '=', 'products.product_type_id')
                            ->on('product_type_fields.field_id', '=', 'product_spec_values.field_id');
                    })
                    ->whereColumn('products.id', 'product_spec_values.product_id');
            })
            ->delete();
    }

}

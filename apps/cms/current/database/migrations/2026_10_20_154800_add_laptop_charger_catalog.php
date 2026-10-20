<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Additive data-only catalog setup. No schema change and NO automatic deployment.
 * Applies only existing laptop-associated brands to this charger type.
 * The normal type-scoped product-line selector becomes "Tip priključka" in the app.
 */
return new class extends Migration
{
    private const SLUG = 'punjaci-za-laptopove';
    private const NAME = 'Punjači za laptopove';
    private const WATT_SLUG = 'snaga-napajanja';

    /** Brand-local product lines; each has a dimension or identifies a standard plug family. */
    private const CONNECTORS = [
        'dell' => [
            'Debela igla (7,4 × 5,0 mm)',
            'Tanka igla (4,5 × 3,0 mm)',
            'Type-C (USB-C PD)',
        ],
        'hp' => [
            'Debela igla (7,4 × 5,0 mm)',
            'Plava igla (4,5 × 3,0 mm)',
            'Type-C (USB-C PD)',
        ],
        'lenovo' => [
            'Kocka / Slim Tip (11 × 4,5 mm)',
            'Tanka igla (4,0 × 1,7 mm)',
            'Okrugli (7,9 × 5,5 mm)',
            'Type-C (USB-C PD)',
        ],
        'asus' => [
            'Tanka okrugla (4,0 × 1,35 mm)',
            'Okrugla (5,5 × 2,5 mm)',
            'Gaming pin (6,0 × 3,7 mm)',
            'Type-C (USB-C PD)',
        ],
        'acer' => [
            'Okrugla (5,5 × 1,7 mm)',
            'Tanka okrugla (3,0 × 1,1 mm)',
            'Type-C (USB-C PD)',
        ],
        'msi' => ['Okrugla (5,5 × 2,5 mm)', 'Type-C (USB-C PD)'],
        'apple' => ['MagSafe 1', 'MagSafe 2', 'MagSafe 3', 'Type-C (USB-C PD)'],
        'microsoft' => ['Surface Connect', 'Type-C (USB-C PD)'],
        'huawei' => ['Type-C (USB-C PD)'],
        'honor' => ['Type-C (USB-C PD)'],
        'samsung' => ['Okrugla (5,5 × 3,0 mm)', 'Type-C (USB-C PD)'],
        'toshiba' => ['Okrugla (5,5 × 2,5 mm)', 'Tanka okrugla (4,0 × 1,7 mm)', 'Type-C (USB-C PD)'],
        'dynabook' => ['Okrugla (5,5 × 2,5 mm)', 'Tanka okrugla (4,0 × 1,7 mm)', 'Type-C (USB-C PD)'],
    ];

    public function up(): void
    {
        foreach ([
            'categories', 'product_types', 'specification_fields', 'product_type_fields',
            'brands', 'brand_product_type', 'product_lines', 'product_line_product_type',
        ] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException('Laptop charger catalog HOLD: missing required table '.$table);
            }
        }

        DB::transaction(function (): void {
            $categoryId = $this->categoryId();
            $typeId = $this->productTypeId($categoryId);
            $fieldId = $this->powerFieldId();
            DB::table('product_type_fields')->insertOrIgnore([
                'product_type_id' => $typeId,
                'field_id' => $fieldId,
                'is_required' => true,
                'is_filterable' => true,
                'show_in_summary' => true,
                'sort_order' => 10,
                'default_value' => null,
                'default_detail' => null,
                'completeness_weight' => 1,
                'include_in_name' => false,
                'created_at' => now(),
            ]);

            // Read the ACTUAL laptop product type associations; never add global/new brands.
            $laptopTypeIds = DB::table('product_types')->get(['id', 'name', 'slug'])
                ->filter(static function ($type): bool {
                    $normalized = Str::lower(Str::ascii((string) $type->name.' '.(string) $type->slug));
                    return (str_contains($normalized, 'laptop')
                            || str_contains($normalized, 'notebook')
                            || str_contains($normalized, 'prenosni racunar'))
                        && !str_contains($normalized, 'punjac')
                        && !str_contains($normalized, 'charger');
                })
                ->pluck('id')->map(static fn ($id): int => (int) $id)->all();

            if ($laptopTypeIds === []) {
                return; // On new installs, catalog type/field exist; owner assigns laptop brands later.
            }

            $existingBrandIds = DB::table('brand_product_type')
                ->whereIn('product_type_id', $laptopTypeIds)
                ->distinct()->pluck('brand_id')->map(static fn ($id): int => (int) $id)->all();
            $brands = DB::table('brands')->whereIn('id', $existingBrandIds)
                ->where('status', 'active')->orderBy('id')->get(['id', 'name', 'slug']);

            foreach ($brands as $brand) {
                $normalized = preg_replace('/[^a-z0-9]+/', '',
                    Str::lower(Str::ascii((string) $brand->slug))) ?: '';
                $key = match ($normalized) {
                    'hpinc', 'hewlettpackard' => 'hp',
                    'asustek' => 'asus',
                    'microstar' => 'msi',
                    default => $normalized,
                };
                $connectors = self::CONNECTORS[$key] ?? [];
                if ($connectors === []) continue; // No guessing new connector types for uncurated brands.

                DB::table('brand_product_type')->insertOrIgnore([
                    'brand_id' => (int) $brand->id,
                    'product_type_id' => $typeId,
                ]);
                foreach ($connectors as $index => $name) {
                    $line = DB::table('product_lines')
                        ->where('brand_id', (int) $brand->id)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first(['id', 'status']);
                    if ($line !== null && (string) $line->status !== 'active') {
                        continue; // Manual deactivation takes precedence.
                    }
                    $lineId = $line !== null ? (int) $line->id : $this->createLine(
                        (int) $brand->id, (string) $brand->name, $name, $index
                    );
                    DB::table('product_line_product_type')->insertOrIgnore([
                        'product_line_id' => $lineId,
                        'product_type_id' => $typeId,
                    ]);
                }
            }
        }, 3);
    }

    public function down(): void
    {
        // Never purge live taxonomy, product references, model lines or specs on rollback.
    }

    private function categoryId(): int
    {
        $row = DB::table('categories')->where('slug', self::SLUG)->first(['id', 'name', 'status']);
        if ($row !== null) {
            if ((string) $row->name !== self::NAME || (string) $row->status !== 'active') {
                throw new RuntimeException('Laptop charger category HOLD: existing slug is manually owned/inactive');
            }
            return (int) $row->id;
        }
        return (int) DB::table('categories')->insertGetId([
            'name' => self::NAME,
            'slug' => self::SLUG,
            'description' => 'Zamenski i originalni adapteri za laptopove; proveriti priključak, snagu i napon.',
            'status' => 'active',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function productTypeId(int $categoryId): int
    {
        $row = DB::table('product_types')->where('slug', self::SLUG)->first(['id', 'name', 'status', 'category_id']);
        if ($row !== null) {
            if ((string) $row->name !== self::NAME || (string) $row->status !== 'active'
                || (int) $row->category_id !== $categoryId) {
                throw new RuntimeException('Laptop charger type HOLD: existing slug/category was manually modified');
            }
            return (int) $row->id;
        }
        return (int) DB::table('product_types')->insertGetId([
            'category_id' => $categoryId,
            'name' => self::NAME,
            'slug' => self::SLUG,
            'description' => 'Brend, tip priključka i obavezna snaga punjača u W.',
            'status' => 'active',
            'sort_order' => 0,
            'auto_name_enabled' => false,
            'minimum_completeness_percent' => 0,
            'default_product_status' => 'draft',
            'required_core_fields_json' => json_encode(['brand', 'line', 'categories', 'price'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function powerFieldId(): int
    {
        $field = DB::table('specification_fields')->where('slug', self::WATT_SLUG)->first(['id', 'data_type', 'unit']);
        if ($field !== null) {
            if (!in_array((string) $field->data_type, ['integer', 'decimal'], true)
                || !in_array(Str::lower(trim((string) $field->unit)), ['w', 'watt'], true)) {
                throw new RuntimeException('Laptop charger W field HOLD: existing desktop power field is not a numeric watt field');
            }
            return (int) $field->id;
        }
        // Fresh test installs may lack the historic desktop-power field.
        // Create it with the canonical slug; never create an alias or duplicate.
        return (int) DB::table('specification_fields')->insertGetId([
            'name' => 'Snaga napajanja',
            'slug' => self::WATT_SLUG,
            'data_type' => 'integer',
            'filter_type' => 'range',
            'unit' => 'W',
            'placeholder' => 'npr. 65',
            'help_text' => 'Snaga punjača izražena u vatima (W).',
            'min_value' => 1,
            'max_value' => 500,
            'status' => 'active',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createLine(int $brandId, string $brandName, string $name, int $index): int
    {
        $base = Str::slug($brandName.' '.$name);
        $slug = $base !== '' ? $base : 'punjac-prikljucak';
        $candidate = $slug;
        $i = 2;
        while (DB::table('product_lines')->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$i++;
        }
        return (int) DB::table('product_lines')->insertGetId([
            'brand_id' => $brandId,
            'name' => $name,
            'slug' => $candidate,
            'status' => 'active',
            'sort_order' => ($index + 1) * 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};

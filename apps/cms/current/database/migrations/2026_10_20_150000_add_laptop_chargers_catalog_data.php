<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Data-only catalog extension. No new tables, columns, brands, product variants
 * or existing product changes. Production execution requires the normal gates.
 */
return new class extends Migration
{
    private const TYPE_SLUG = 'punjaci-za-laptopove';
    private const CONNECTOR_SLUG = 'tip-konektora-punjaca';
    private const POWER_SLUG = 'snaga-napajanja';

    /**
     * Labels are deliberately brand-prefixed: matching connector dimensions
     * do NOT imply electrical compatibility or smart-pin interoperability.
     * USB-C PD is a protocol/connector class, not a barrel mm measurement.
     *
     * Reference dimensions:
     * HP: hp.com/us-en/tech-takes/laptops/how-to/how-to-choose-right-charging-cord-hp-laptop.html
     * Lenovo connector families: support.lenovo.com/rs/en/accessories/pd014168-ac-dc-and-acdc-power-adapter-charger-reference-guide
     * Acer/ASUS: hotspotpro.rs/kako-da-odaberete-odgovarajuci-punjac-za-laptop/
     * Dell: wanhunglo.com/2024/for-dell-laptop-dc-power-charge-converter-adapter-cable-cord-7-45-0-to-4-53-0-mm-female-whl-102/
     */
    private const CONNECTORS = [
        'dell' => [
            'Dell — debela igla (7.4 × 5.0 mm, centar pin)',
            'Dell — tanka igla (4.5 × 3.0 mm, centar pin)',
            'Dell — USB-C PD',
        ],
        'hp' => [
            'HP — debela igla (7.4 × 5.0 mm, Smart Pin)',
            'HP — plava igla (4.5 × 3.0 mm, Smart Pin)',
            'HP — USB-C PD',
        ],
        'lenovo' => [
            'Lenovo — kocka / Slim Tip (11 × 4.5 mm)',
            'Lenovo — tanka igla (4.0 × 1.7 mm)',
            'Lenovo — okrugli (7.9 × 5.5 mm, centar pin)',
            'Lenovo — USB-C PD',
        ],
        'asus' => [
            'ASUS — okrugli (5.5 × 2.5 mm)',
            'ASUS — tanki (4.0 × 1.35 mm)',
            'ASUS — gaming (6.0 × 3.7 mm)',
            'ASUS — USB-C PD',
        ],
        'acer' => [
            'Acer — okrugli (5.5 × 1.7 mm)',
            'Acer — mali (3.0 × 1.1 mm)',
            'Acer — USB-C PD',
        ],
        'msi' => [
            'MSI — okrugli (5.5 × 2.5 mm)',
            'MSI — USB-C PD',
        ],
        'apple' => ['Apple — MagSafe 2', 'Apple — MagSafe 3', 'Apple — USB-C PD'],
        'microsoft' => ['Microsoft — Surface Connect', 'Microsoft — USB-C PD'],
        'huawei' => ['Huawei — USB-C PD'],
        'honor' => ['Honor — USB-C PD'],
        'samsung' => ['Samsung — USB-C PD'],
        'toshiba' => ['Toshiba — okrugli (5.5 × 2.5 mm)', 'Toshiba — USB-C PD'],
        'dynabook' => ['Dynabook — okrugli (5.5 × 2.5 mm)', 'Dynabook — USB-C PD'],
    ];

    public function up(): void
    {
        foreach (['product_types', 'categories', 'brands', 'brand_product_type',
            'specification_fields', 'specification_options', 'product_type_fields'] as $table) {
            if (!Schema::hasTable($table)) return;
        }

        if (!Schema::hasColumn('product_types', 'category_id')) return;

        $laptops = DB::table('product_types')
            ->whereIn('slug', ['laptop', 'laptopovi', 'laptop-racunari', 'notebook'])
            ->where('status', 'active')
            ->get(['id', 'category_id']);
        $power = DB::table('specification_fields')
            ->where('slug', self::POWER_SLUG)
            ->where('status', 'active')
            ->first(['id', 'data_type']);

        // Source brands and the *existing* power field are mandatory. Never
        // fabricate brands or a duplicate watt field on an incomplete database.
        if ($laptops->isEmpty() || $power === null || !in_array($power->data_type, ['integer', 'decimal'], true)) return;

        $laptopIds = $laptops->pluck('id')->all();
        $brands = DB::table('brands as brands')
            ->join('brand_product_type as bpt', 'bpt.brand_id', '=', 'brands.id')
            ->whereIn('bpt.product_type_id', $laptopIds)
            ->where('brands.status', 'active')
            ->select(['brands.id', 'brands.slug', 'brands.name'])
            ->distinct()
            ->orderBy('brands.id')
            ->get();
        if ($brands->isEmpty()) return;

        DB::transaction(function () use ($brands, $laptops, $power): void {
            $now = now();
            $categoryId = DB::table('categories')->where('slug', self::TYPE_SLUG)->value('id');
            if ($categoryId === null) {
                $categoryId = DB::table('categories')->insertGetId($this->presentColumns('categories', [
                    'name' => 'Punjači za laptopove',
                    'slug' => self::TYPE_SLUG,
                    'description' => 'Punjaci za postojeće brendove laptopova, po tipu konektora i snazi.',
                    'status' => 'active',
                    'sort_order' => 150,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }

            $typeId = DB::table('product_types')->where('slug', self::TYPE_SLUG)->value('id');
            if ($typeId === null) {
                $typeId = DB::table('product_types')->insertGetId($this->presentColumns('product_types', [
                    'category_id' => $categoryId,
                    'name' => 'Punjači za laptopove',
                    'slug' => self::TYPE_SLUG,
                    'description' => 'Novi punjači po brendu, konektoru i snazi (W).',
                    'status' => 'active',
                    'sort_order' => 150,
                    'name_template' => '{brand} punjač {snaga-napajanja} {tip-konektora-punjaca}',
                    'auto_name_enabled' => false,
                    'minimum_completeness_percent' => 0,
                    'default_product_status' => 'draft',
                    'required_core_fields_json' => json_encode(['brand', 'price'], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            } else {
                // Do not overwrite a manually curated category relationship.
                $currentCategoryId = DB::table('product_types')->where('id', $typeId)->value('category_id');
                if ($currentCategoryId !== null && (int) $currentCategoryId !== (int) $categoryId) {
                    throw new RuntimeException('HOLD_LAPTOP_CHARGERS_TYPE_CATEGORY_CONFLICT');
                }
                if ($currentCategoryId === null) {
                    DB::table('product_types')->where('id', $typeId)->update(['category_id' => $categoryId]);
                }
            }

            // The same catalog visibility groups that can access laptops may
            // see charger products. Never modify existing group/category rows.
            if (Schema::hasTable('user_group_categories')) {
                $sourceCategories = $laptops->pluck('category_id')->filter()->unique()->all();
                if ($sourceCategories !== []) {
                    $groups = DB::table('user_group_categories')
                        ->whereIn('category_id', $sourceCategories)
                        ->distinct()->pluck('group_id');
                    foreach ($groups as $groupId) {
                        DB::table('user_group_categories')->insertOrIgnore([
                            'group_id' => $groupId,
                            'category_id' => $categoryId,
                            'created_at' => $now,
                        ]);
                    }
                }
            }

            $options = [];
            foreach ($brands as $brand) {
                // Link ONLY existing, active brands selected for a laptop type.
                DB::table('brand_product_type')->insertOrIgnore([
                    'brand_id' => (int) $brand->id,
                    'product_type_id' => (int) $typeId,
                ]);
                $brandKey = str_replace('-', '', Str::slug((string) ($brand->slug ?: $brand->name)));
                $brandKey = match ($brandKey) {
                    'hewlettpackard', 'hpinc' => 'hp',
                    'asustek' => 'asus',
                    'microstar' => 'msi',
                    default => $brandKey,
                };
                foreach (self::CONNECTORS[$brandKey] ?? [] as $label) {
                    $options[$label] = $label;
                }
            }
            if ($options === []) {
                // Existing brand type links remain valid, but don't fabricate
                // connector values for unrecognized manufacturers.
                return;
            }

            $connectorId = DB::table('specification_fields')->where('slug', self::CONNECTOR_SLUG)->value('id');
            if ($connectorId === null) {
                $connectorId = DB::table('specification_fields')->insertGetId($this->presentColumns('specification_fields', [
                    'name' => 'Tip konektora punjača',
                    'slug' => self::CONNECTOR_SLUG,
                    'data_type' => 'select',
                    'filter_type' => 'select',
                    'help_text' => 'Dimenzije su spoljašnji × unutrašnji prečnik u mm. Proveri model, napon, polaritet i Smart Pin pre prodaje; USB-C mora podržavati PD.',
                    'options_text' => implode("\n", $options),
                    'status' => 'active',
                    'sort_order' => 160,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            } else {
                $existing = DB::table('specification_fields')->where('id', $connectorId)->first(['data_type', 'options_text']);
                if ($existing === null || (string) $existing->data_type !== 'select') {
                    throw new RuntimeException('HOLD_LAPTOP_CHARGERS_CONNECTOR_FIELD_TYPE_CONFLICT');
                }
                // Preserve the owner's custom options and append only missing ones.
                $stored = preg_split('/\R+/', trim((string) $existing->options_text)) ?: [];
                $newText = array_values(array_unique(array_merge($stored, array_values($options))));
                if (implode("\n", $stored) !== implode("\n", $newText)) {
                    DB::table('specification_fields')->where('id', $connectorId)->update([
                        'options_text' => implode("\n", $newText),
                        'updated_at' => $now,
                    ]);
                }
            }

            $order = 10;
            foreach (array_values($options) as $label) {
                $exists = DB::table('specification_options')
                    ->where('field_id', $connectorId)->where('value', $label)->exists();
                if (!$exists) {
                    DB::table('specification_options')->insert($this->presentColumns('specification_options', [
                        'field_id' => $connectorId,
                        'label' => $label,
                        'value' => $label,
                        'status' => 'active',
                        'sort_order' => $order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
                }
                $order += 10;
            }

            foreach ([
                ['id' => $connectorId, 'position' => 10],
                ['id' => (int) $power->id, 'position' => 20],
            ] as $field) {
                DB::table('product_type_fields')->insertOrIgnore($this->presentColumns('product_type_fields', [
                    'product_type_id' => (int) $typeId,
                    'field_id' => $field['id'],
                    'is_required' => true,
                    'is_filterable' => true,
                    'show_in_summary' => true,
                    'sort_order' => $field['position'],
                    'completeness_weight' => 1,
                    'include_in_name' => false,
                    'created_at' => $now,
                ]));
            }
        }, 3);
    }

    public function down(): void
    {
        // Data-only migration is intentionally non-destructive: existing
        // chargers, brand links and manually edited field options remain.
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function presentColumns(string $table, array $payload): array
    {
        $columns = Schema::getColumnListing($table);

        return array_intersect_key($payload, array_flip($columns));
    }
};

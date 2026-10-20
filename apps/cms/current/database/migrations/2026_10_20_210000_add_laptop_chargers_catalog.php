<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

// DML-only additions to existing type-scoped catalogue. No schema alteration,
// no product/stock/order mutations and no product variants.
return new class extends Migration
{
    private const TYPE_SLUG = 'punjaci-za-laptopove';
    private const WATT_SLUG = 'snaga-napajanja';

    /** @var array<string,list<string>> */
    private const CONNECTORS = [
        'dell' => ['Debela igla (7,4 × 5,0 mm, centralni pin)', 'Tanka igla (4,5 × 3,0 mm, centralni pin)', 'USB-C Power Delivery'],
        'hp' => ['Debela igla (7,4 × 5,0 mm, centralni pin)', 'Plava igla (4,5 × 3,0 mm, centralni pin)', 'USB-C Power Delivery'],
        'lenovo' => ['Kocka / Slim Tip (11 × 4,5 mm)', 'Tanka igla (4,0 × 1,7 mm)', 'Okrugli (7,9 × 5,5 mm, centralni pin)', 'USB-C Power Delivery'],
        'asus' => ['Okrugli (5,5 × 2,5 mm)', 'Tanki okrugli (4,0 × 1,35 mm)', 'Gaming okrugli (6,0 × 3,7 mm)', 'USB-C Power Delivery'],
        'acer' => ['Okrugli (5,5 × 1,7 mm)', 'Tanki okrugli (3,0 × 1,1 mm)', 'USB-C Power Delivery'],
        'msi' => ['Okrugli (5,5 × 2,5 mm)', 'USB-C Power Delivery'],
        'apple' => ['MagSafe 1', 'MagSafe 2', 'MagSafe 3', 'USB-C Power Delivery'],
        'microsoft' => ['Surface Connect', 'USB-C Power Delivery'],
        'huawei' => ['USB-C Power Delivery'],
        'honor' => ['USB-C Power Delivery'],
        'samsung' => ['USB-C Power Delivery'],
        'toshiba' => ['Okrugli (5,5 × 2,5 mm)', 'USB-C Power Delivery'],
        'dynabook' => ['Okrugli (5,5 × 2,5 mm)', 'USB-C Power Delivery'],
    ];

    public function up(): void
    {
        foreach (['categories', 'product_types', 'specification_fields', 'product_type_fields',
            'brands', 'brand_product_type', 'product_lines', 'product_line_product_type'] as $table) {
            if (!Schema::hasTable($table)) return;
        }
        foreach (['category_id', 'name_template', 'default_product_status', 'required_core_fields_json'] as $col) {
            if (!Schema::hasColumn('product_types', $col)) return;
        }
        $power = DB::table('specification_fields')->where('slug', self::WATT_SLUG)->where('status', 'active')->first(['id']);
        if ($power === null) return; // Never create duplicate watt field or partially configured type.

        $laptopTypeIds = DB::table('product_types')->where('status', 'active')->get(['id','name','slug'])
            ->filter(static function ($row): bool {
                $text = Str::lower(Str::ascii((string) $row->name.' '.(string) $row->slug));
                return (str_contains($text, 'laptop') || str_contains($text, 'notebook') || str_contains($text, 'prenosni'))
                    && !str_contains($text, 'punjac') && !str_contains($text, 'charger');
            })->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        if ($laptopTypeIds === []) return;
        $brandIds = DB::table('brand_product_type')->whereIn('product_type_id', $laptopTypeIds)
            ->distinct()->pluck('brand_id')->map(static fn ($id): int => (int) $id)->all();
        if ($brandIds === []) return;
        $brands = DB::table('brands')->whereIn('id', $brandIds)->where('status', 'active')
            ->orderBy('id')->get(['id','name','slug'])
            ->filter(fn ($brand): bool => isset(self::CONNECTORS[$this->brandKey((string) $brand->slug, (string) $brand->name)]));
        if ($brands->isEmpty()) return;

        DB::transaction(function () use ($brands, $power): void {
            $category = DB::table('categories')->where('slug', self::TYPE_SLUG)->first();
            $categoryId = $category !== null ? (int) $category->id : (int) DB::table('categories')->insertGetId([
                'name' => 'Punjači za laptopove', 'slug' => self::TYPE_SLUG,
                'description' => 'Punjači za laptopove prema brendu, priključku i snazi.',
                'status' => 'active', 'sort_order' => 250,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $type = DB::table('product_types')->where('slug', self::TYPE_SLUG)->first();
            if ($type !== null && (int) $type->category_id !== $categoryId) {
                throw new RuntimeException('HOLD_CHARGERS_TYPE_CATEGORY_CONFLICT');
            }
            $typeId = $type !== null ? (int) $type->id : (int) DB::table('product_types')->insertGetId([
                'category_id' => $categoryId,
                'name' => 'Punjači za laptopove',
                'slug' => self::TYPE_SLUG,
                'description' => 'Brend, fizički tip priključka i snaga napajanja (W).',
                'status' => 'active', 'sort_order' => 250,
                'name_template' => 'Punjač za laptop {brand} {line} {snaga-napajanja}',
                'auto_name_enabled' => true,
                'minimum_completeness_percent' => 0,
                'default_product_status' => 'draft',
                'required_core_fields_json' => json_encode(['brand','line','price'], JSON_THROW_ON_ERROR),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('product_type_fields')->insertOrIgnore([
                'product_type_id' => $typeId, 'field_id' => (int) $power->id,
                'is_required' => true, 'is_filterable' => true, 'show_in_summary' => true,
                'sort_order' => 10, 'created_at' => now(), 'completeness_weight' => 2,
                'include_in_name' => true,
            ]);
            foreach ($brands as $brand) {
                $brandId = (int) $brand->id;
                $key = $this->brandKey((string) $brand->slug, (string) $brand->name);
                DB::table('brand_product_type')->insertOrIgnore([
                    'brand_id' => $brandId, 'product_type_id' => $typeId,
                ]);
                foreach (self::CONNECTORS[$key] as $index => $label) {
                    $line = DB::table('product_lines')->where('brand_id', $brandId)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($label))])->first();
                    if ($line !== null && $line->status !== 'active') continue;
                    if ($line === null) {
                        $root = Str::slug('punjac '.$brand->name.' '.$label);
                        $slug = $root;
                        $suffix = 2;
                        while (DB::table('product_lines')->where('brand_id', $brandId)->where('slug', $slug)->exists()) {
                            $slug = mb_substr($root, 0, 128).'-'.$suffix++;
                        }
                        $lineId = (int) DB::table('product_lines')->insertGetId([
                            'brand_id' => $brandId, 'name' => $label, 'slug' => $slug,
                            'status' => 'active', 'sort_order' => 500 + $index * 10,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    } else {
                        $lineId = (int) $line->id;
                    }
                    DB::table('product_line_product_type')->insertOrIgnore([
                        'product_line_id' => $lineId, 'product_type_id' => $typeId,
                    ]);
                }
            }
        }, 3);
    }

    public function down(): void
    {
        // Non-destructive: future articles may reference these catalogue records.
    }

    private function brandKey(string $slug, string $name): string
    {
        $key = preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($slug ?: $name))) ?: '';
        return match ($key) {
            'hpinc', 'hewlettpackard' => 'hp',
            'asustek' => 'asus',
            default => $key,
        };
    }
};

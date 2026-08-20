<?php

declare(strict_types=1);

use App\Models\Product;
use App\Services\ProductCompletenessService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string,mixed> */
    private array $snapshot = array (
  'field' => 
  array (
    'id' => 11,
    'name' => 'Interfejs diska',
    'slug' => 'interfejs-diska',
    'data_type' => 'select',
    'filter_type' => 'select',
    'unit' => NULL,
    'placeholder' => 'Izaberi interfejs',
    'help_text' => 'Interfejs ili generacija magistrale diska.',
    'options_text' => 'SATA II
SATA III
PCIe 3.0
PCIe 4.0
PCIe 5.0
USB',
    'min_value' => NULL,
    'max_value' => NULL,
    'status' => 'active',
    'sort_order' => 110,
    'created_by' => NULL,
    'updated_by' => NULL,
    'created_at' => '2026-07-15 10:01:26',
    'updated_at' => '2026-07-15 10:01:26',
    'parent_field_id' => NULL,
    'detail_input_enabled' => 0,
    'detail_label' => NULL,
    'detail_placeholder' => NULL,
    'storage_role' => 'components',
    'storage_source_field_id' => NULL,
  ),
  'canonical' => 
  array (
    'id' => 10,
    'name' => 'Tip diska',
    'slug' => 'tip-diska',
    'data_type' => 'select',
    'filter_type' => 'select',
    'unit' => NULL,
    'placeholder' => 'Izaberi tip',
    'help_text' => 'Vrsta glavnog uredjaja za skladistenje.',
    'options_text' => 'HDD
SSD
SATA SSD
NVMe SSD
eMMC',
    'min_value' => NULL,
    'max_value' => NULL,
    'status' => 'active',
    'sort_order' => 100,
    'created_by' => NULL,
    'updated_by' => NULL,
    'created_at' => '2026-07-15 10:01:26',
    'updated_at' => '2026-07-15 10:01:26',
    'parent_field_id' => NULL,
    'detail_input_enabled' => 0,
    'detail_label' => NULL,
    'detail_placeholder' => NULL,
    'storage_role' => 'components',
    'storage_source_field_id' => NULL,
  ),
  'total' => 
  array (
    'id' => 9,
    'name' => 'Ukupan kapacitet diskova',
    'slug' => 'kapacitet-diska',
    'data_type' => 'integer',
    'filter_type' => 'range',
    'unit' => 'GB',
    'placeholder' => '512',
    'help_text' => 'Automatski zbir kapaciteta svih unetih diskova. Polje se ne unosi ručno.',
    'options_text' => NULL,
    'min_value' => '1.0000',
    'max_value' => '100000.0000',
    'status' => 'active',
    'sort_order' => 100,
    'created_by' => NULL,
    'updated_by' => NULL,
    'created_at' => '2026-07-15 10:01:26',
    'updated_at' => '2026-08-04 08:13:23',
    'parent_field_id' => NULL,
    'detail_input_enabled' => 0,
    'detail_label' => NULL,
    'detail_placeholder' => NULL,
    'storage_role' => 'total_capacity',
    'storage_source_field_id' => 10,
  ),
  'options' => 
  array (
    0 => 
    array (
      'id' => 23,
      'field_id' => 11,
      'label' => 'SATA II',
      'value' => 'SATA II',
      'status' => 'active',
      'sort_order' => 10,
      'created_at' => '2026-07-31 09:53:57',
      'updated_at' => '2026-07-31 09:53:57',
    ),
    1 => 
    array (
      'id' => 24,
      'field_id' => 11,
      'label' => 'SATA III',
      'value' => 'SATA III',
      'status' => 'active',
      'sort_order' => 20,
      'created_at' => '2026-07-31 09:53:57',
      'updated_at' => '2026-07-31 09:53:57',
    ),
    2 => 
    array (
      'id' => 25,
      'field_id' => 11,
      'label' => 'PCIe 3.0',
      'value' => 'PCIe 3.0',
      'status' => 'active',
      'sort_order' => 30,
      'created_at' => '2026-07-31 09:53:57',
      'updated_at' => '2026-07-31 09:53:57',
    ),
    3 => 
    array (
      'id' => 26,
      'field_id' => 11,
      'label' => 'PCIe 4.0',
      'value' => 'PCIe 4.0',
      'status' => 'active',
      'sort_order' => 40,
      'created_at' => '2026-07-31 09:53:57',
      'updated_at' => '2026-07-31 09:53:57',
    ),
    4 => 
    array (
      'id' => 27,
      'field_id' => 11,
      'label' => 'PCIe 5.0',
      'value' => 'PCIe 5.0',
      'status' => 'active',
      'sort_order' => 50,
      'created_at' => '2026-07-31 09:53:57',
      'updated_at' => '2026-07-31 09:53:57',
    ),
    5 => 
    array (
      'id' => 28,
      'field_id' => 11,
      'label' => 'USB',
      'value' => 'USB',
      'status' => 'active',
      'sort_order' => 60,
      'created_at' => '2026-07-31 09:53:57',
      'updated_at' => '2026-07-31 09:53:57',
    ),
  ),
  'option_ids' => 
  array (
    0 => 23,
    1 => 24,
    2 => 25,
    3 => 26,
    4 => 27,
    5 => 28,
  ),
  'dependencies' => 
  array (
  ),
  'type_fields' => 
  array (
    0 => 
    array (
      'product_type_id' => 8,
      'field_id' => 11,
      'is_required' => 0,
      'is_filterable' => 1,
      'show_in_summary' => 1,
      'sort_order' => 40,
      'created_at' => '2026-07-15 10:01:26',
      'default_value' => NULL,
      'default_detail' => NULL,
      'completeness_weight' => 1,
      'include_in_name' => 0,
    ),
  ),
  'product_values' => 
  array (
    0 => 
    array (
      'product_id' => 4,
      'field_id' => 11,
      'value_text' => 'SATA III 256 GB',
      'value_number' => NULL,
      'value_boolean' => NULL,
      'created_at' => '2026-08-18 08:53:37',
      'updated_at' => '2026-08-18 08:53:37',
      'value_detail' => NULL,
      'value_json' => '[{"type":"SATA III","capacity_gb":"256"}]',
    ),
    1 => 
    array (
      'product_id' => 34,
      'field_id' => 11,
      'value_text' => 'PCIe 3.0 256 GB',
      'value_number' => NULL,
      'value_boolean' => NULL,
      'created_at' => '2026-08-19 21:28:42',
      'updated_at' => '2026-08-19 21:28:42',
      'value_detail' => NULL,
      'value_json' => '[{"type":"PCIe 3.0","capacity_gb":"256"}]',
    ),
    2 => 
    array (
      'product_id' => 35,
      'field_id' => 11,
      'value_text' => 'SATA III',
      'value_number' => NULL,
      'value_boolean' => NULL,
      'created_at' => '2026-08-19 21:19:02',
      'updated_at' => '2026-08-19 21:19:02',
      'value_detail' => NULL,
      'value_json' => NULL,
    ),
  ),
  'children' => 
  array (
  ),
  'source_refs' => 
  array (
  ),
  'templates' => 
  array (
  ),
  'affected_products' => 
  array (
    0 => 
    array (
      'id' => 4,
      'status' => 'inactive',
      'completeness_percent' => 100,
      'updated_at' => '2026-08-18 08:53:37',
      'locally_modified_at' => '2026-08-18 08:53:37',
    ),
    1 => 
    array (
      'id' => 34,
      'status' => 'active',
      'completeness_percent' => 100,
      'updated_at' => '2026-08-19 21:28:42',
      'locally_modified_at' => '2026-08-19 21:28:42',
    ),
    2 => 
    array (
      'id' => 35,
      'status' => 'active',
      'completeness_percent' => 100,
      'updated_at' => '2026-08-19 22:03:05',
      'locally_modified_at' => '2026-08-19 21:19:02',
    ),
  ),
  'inactive_positive_stock' => 
  array (
    0 => 
    array (
      'id' => 4,
      'sku' => 'SSD-256GB-M2',
      'name' => 'Micron 256GB M.2 SATA',
      'stock_quantity' => 18,
      'status' => 'inactive',
      'completeness_percent' => 100,
    ),
    1 => 
    array (
      'id' => 18,
      'sku' => 'LENOVO-THINKPAD-16GB-256GB-INTEL-CORE-I5-8265U-LAPTOP',
      'name' => 'Lenovo ThinkPad T480 i5-8265U 16GB 256GB Intel',
      'stock_quantity' => 1,
      'status' => 'inactive',
      'completeness_percent' => 100,
    ),
    2 => 
    array (
      'id' => 21,
      'sku' => 'HP-DESKTOP-RACUNAR',
      'name' => 'HP EliteDesk 800 G2 i5-6500T',
      'stock_quantity' => 2,
      'status' => 'inactive',
      'completeness_percent' => 100,
    ),
  ),
  'business_hash' => '2387453892c5c8dfd3552efbda5aa22835ffed561e0c8c37ba21ea5aa437f04a',
);

    public function up(): void
    {
        $s = $this->snapshot;
        $field = (array) $s['field'];
        $fieldId = (int) $field['id'];
        $optionIds = array_map('intval', (array) ($s['option_ids'] ?? []));
        DB::transaction(function () use ($s, $fieldId, $optionIds): void {
            $live = DB::table('specification_fields')->where('id', $fieldId)->first();
            if ($live === null) return;
            if ((string) ($live->slug ?? '') !== 'interfejs-diska') throw new RuntimeException('Duplicate disk interface field ID was reused; migration stopped.');
            if ($optionIds !== [] && Schema::hasTable('specification_option_dependencies')) {
                DB::table('specification_option_dependencies')->whereIn('parent_option_id', $optionIds)->orWhereIn('child_option_id', $optionIds)->delete();
            }
            if (Schema::hasColumn('specification_fields', 'parent_field_id')) DB::table('specification_fields')->where('parent_field_id', $fieldId)->update(['parent_field_id' => null]);
            if (Schema::hasColumn('specification_fields', 'storage_source_field_id')) DB::table('specification_fields')->where('storage_source_field_id', $fieldId)->update(['storage_source_field_id' => null]);
            DB::table('product_spec_values')->where('field_id', $fieldId)->delete();
            DB::table('product_type_fields')->where('field_id', $fieldId)->delete();
            DB::table('specification_options')->where('field_id', $fieldId)->delete();
            foreach ((array) ($s['templates'] ?? []) as $row) {
                $template = (string) ($row['name_template'] ?? '');
                $updated = str_replace(['{interfejs-diska}', '{interfejs-diska_detail}'], ' ', $template);
                $updated = preg_replace('/\s+/', ' ', trim($updated)) ?? trim($updated);
                DB::table('product_types')->where('id', (int) $row['id'])->update(['name_template' => $updated]);
            }
            DB::table('specification_fields')->where('id', $fieldId)->delete();
            foreach ((array) ($s['affected_products'] ?? []) as $row) {
                $product = Product::query()->find((int) $row['id']);
                if ($product instanceof Product) app(ProductCompletenessService::class)->recalculate($product, false);
            }
        }, 3);
    }

    public function down(): void
    {
        $s = $this->snapshot;
        $field = (array) $s['field'];
        $fieldId = (int) $field['id'];
        $optionIds = array_map('intval', (array) ($s['option_ids'] ?? []));
        DB::transaction(function () use ($s, $field, $fieldId, $optionIds): void {
            if ($optionIds !== [] && Schema::hasTable('specification_option_dependencies')) {
                DB::table('specification_option_dependencies')->whereIn('parent_option_id', $optionIds)->orWhereIn('child_option_id', $optionIds)->delete();
            }
            DB::table('product_spec_values')->where('field_id', $fieldId)->delete();
            DB::table('product_type_fields')->where('field_id', $fieldId)->delete();
            DB::table('specification_options')->where('field_id', $fieldId)->delete();
            if (Schema::hasColumn('specification_fields', 'parent_field_id')) DB::table('specification_fields')->where('parent_field_id', $fieldId)->update(['parent_field_id' => null]);
            if (Schema::hasColumn('specification_fields', 'storage_source_field_id')) DB::table('specification_fields')->where('storage_source_field_id', $fieldId)->update(['storage_source_field_id' => null]);
            DB::table('specification_fields')->where('id', $fieldId)->delete();
            DB::table('specification_fields')->insert($field);
            foreach ((array) ($s['options'] ?? []) as $row) DB::table('specification_options')->insert($row);
            foreach ((array) ($s['type_fields'] ?? []) as $row) DB::table('product_type_fields')->insert($row);
            foreach ((array) ($s['product_values'] ?? []) as $row) DB::table('product_spec_values')->insert($row);
            foreach ((array) ($s['dependencies'] ?? []) as $row) DB::table('specification_option_dependencies')->insert($row);
            foreach ((array) ($s['children'] ?? []) as $row) DB::table('specification_fields')->where('id', (int) $row['id'])->update(['parent_field_id' => $row['parent_field_id']]);
            foreach ((array) ($s['source_refs'] ?? []) as $row) DB::table('specification_fields')->where('id', (int) $row['id'])->update(['storage_source_field_id' => $row['storage_source_field_id']]);
            foreach ((array) ($s['templates'] ?? []) as $row) DB::table('product_types')->where('id', (int) $row['id'])->update(['name_template' => $row['name_template']]);
            foreach ((array) ($s['affected_products'] ?? []) as $row) {
                DB::table('products')->where('id', (int) $row['id'])->update([
                    'status' => $row['status'],
                    'completeness_percent' => $row['completeness_percent'],
                    'updated_at' => $row['updated_at'],
                    'locally_modified_at' => $row['locally_modified_at'],
                ]);
            }
        }, 3);
    }
};
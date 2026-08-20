
============================================================
CMS PRODUCT VARIANTS - DECOMMISSION BATCH 2C1 EXACT RUNTIME + SCHEMA PURGE PREFLIGHT
============================================================
DATE=Wed Aug 19 01:46:28 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2C1-EXACT-RUNTIME-SCHEMA-PURGE-PREFLIGHT-20260819-014628.md
MODE=READ_ONLY_EXACT_RUNTIME_SCHEMA_PURGE_PREFLIGHT
PURPOSE=DISCOVER_EXACT_POST_BATCH2B_RUNTIME_REFERENCES_FOREIGN_KEYS_INDEXES_AND_SNAPSHOT_DROP_SAFETY_BEFORE_DESTRUCTIVE_BATCH2C2
SOURCE_WRITES_EXPECTED=0
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
EAS_BUILD=NO

============================================================
0. PREFLIGHT + PREREQUISITES + CONCURRENCY
============================================================
PREFLIGHT_COMMAND_bash=PASS
PREFLIGHT_COMMAND_php=PASS
PREFLIGHT_COMMAND_grep=PASS
PREFLIGHT_COMMAND_sed=PASS
PREFLIGHT_COMMAND_awk=PASS
PREFLIGHT_COMMAND_cat=PASS
PREFLIGHT_COMMAND_cp=PASS
PREFLIGHT_COMMAND_mkdir=PASS
PREFLIGHT_COMMAND_rm=PASS
PREFLIGHT_COMMAND_rmdir=PASS
PREFLIGHT_COMMAND_sha256sum=PASS
PREFLIGHT_COMMAND_find=PASS
PREFLIGHT_COMMAND_sort=PASS
PREFLIGHT_COMMAND_wc=PASS
PREFLIGHT_COMMAND_git=PASS
PREFLIGHT_COMMAND_cmp=PASS
PREFLIGHT_COMMAND_diff=PASS
PREFLIGHT_COMMAND_ls=PASS
PREFLIGHT_COMMAND_mktemp=PASS
PREFLIGHT_COMMAND_date=PASS
PREFLIGHT_COMMAND_dirname=PASS
PREFLIGHT_COMMAND_basename=PASS
PREFLIGHT_COMMAND_tee=PASS
PREFLIGHT_COMMAND_tr=PASS
PREFLIGHT_COMMAND_head=PASS
PREFLIGHT_COMMAND_tail=PASS
PREFLIGHT_COMMAND_cut=PASS
CONCURRENCY_LOCK=ACQUIRED
BATCH2B2_V5_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2B2-V5-PUBLIC-CONTRACT-MOBILE-CUTOVER-20260819-013911.md
BATCH2B2_V5_PREREQUISITE=PASS_PUBLIC_CONTRACT_MOBILE_CUTOVER
BATCH2B1_V3_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2B1-V3-CORE-RUNTIME-SINGLE-PRODUCT-LOCK-20260819-002527.md
BATCH2B1_V3_PREREQUISITE=PASS_SINGLE_PRODUCT_WRITE_LOCK
FIELD_OPERATIONS_BATCH3_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-FIELD-OPERATIONS-ADMIN-MOBILE-CLIENT-UI-BATCH3-20260818-234543.md
FIELD_OPERATIONS_PROGRESS_BASELINE=75_PERCENT
GIT_BASELINE_CAPTURED=YES

============================================================
1. LIVE DATABASE ZERO-STATE + EXACT VARIANT SCHEMA DISCOVERY
============================================================
No syntax errors detected in /tmp/ald1n-variants-2c1.YSUEsv/db-probe.php
DATABASE_NAME=icaffeco_lrvl
PRODUCT_VARIANT_COUNT=0
PRODUCT_VARIANT_SPEC_VALUE_COUNT=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_IMAGES=0
NON_NULL_PRODUCT_VARIANT_ID_ORDER_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_STOCK_MOVEMENTS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_CASE_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_ACTION_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_WARRANTIES=0
PRODUCT_VARIANT_ID_SURFACE_OPERATIONAL_ALERTS=COLUMN_ABSENT
BUSINESS_VARIANT_REFERENCE_NON_NULL_TOTAL=0
PRODUCTS_VARIANTS_ENABLED=0
PRODUCTS_DEFAULT_VARIANT_ID_NON_NULL=0
LIVE_DB_ZERO_VARIANT_STATE=PASS
VARIANT_RELATED_COLUMN_COUNT=39
VARIANT_RELATED_COLUMNS_BEGIN
after_sales_action_items|product_variant_id|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
after_sales_case_items|product_variant_id|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
order_items|product_variant_id|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
order_items|variant_sku_snapshot|varchar(100)|NULLABLE=YES|DEFAULT=NULL
order_items|variant_name_snapshot|varchar(190)|NULLABLE=YES|DEFAULT=NULL
order_items|variant_attributes_json|longtext|NULLABLE=YES|DEFAULT=NULL
products|variants_enabled|tinyint(1)|NULLABLE=NO|DEFAULT=0
products|default_variant_id|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
product_images|product_variant_id|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
product_variants|id|bigint(20) unsigned|NULLABLE=NO|DEFAULT=NULL
product_variants|product_id|bigint(20) unsigned|NULLABLE=NO|DEFAULT=NULL
product_variants|sku|varchar(100)|NULLABLE=NO|DEFAULT=NULL
product_variants|name|varchar(190)|NULLABLE=NO|DEFAULT=NULL
product_variants|price_amount|decimal(12,2)|NULLABLE=NO|DEFAULT=0.00
product_variants|price_currency|varchar(3)|NULLABLE=NO|DEFAULT='RSD'
product_variants|purchase_price_rsd|decimal(14,2)|NULLABLE=YES|DEFAULT=NULL
product_variants|manual_commission_eur|decimal(12,2)|NULLABLE=YES|DEFAULT=NULL
product_variants|stock_quantity|int(10) unsigned|NULLABLE=NO|DEFAULT=0
product_variants|low_stock_threshold|int(10) unsigned|NULLABLE=NO|DEFAULT=1
product_variants|status|varchar(20)|NULLABLE=NO|DEFAULT='draft'
product_variants|is_default|tinyint(1)|NULLABLE=NO|DEFAULT=0
product_variants|warranty_rule_id|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
product_variants|sort_order|int(10) unsigned|NULLABLE=NO|DEFAULT=0
product_variants|created_by|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
product_variants|updated_by|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
product_variants|created_at|timestamp|NULLABLE=YES|DEFAULT=NULL
product_variants|updated_at|timestamp|NULLABLE=YES|DEFAULT=NULL
product_variants|deleted_at|datetime|NULLABLE=YES|DEFAULT=NULL
product_variant_spec_values|product_variant_id|bigint(20) unsigned|NULLABLE=NO|DEFAULT=NULL
product_variant_spec_values|field_id|bigint(20) unsigned|NULLABLE=NO|DEFAULT=NULL
product_variant_spec_values|value_text|varchar(1000)|NULLABLE=YES|DEFAULT=NULL
product_variant_spec_values|value_detail|varchar(500)|NULLABLE=YES|DEFAULT=NULL
product_variant_spec_values|value_json|longtext|NULLABLE=YES|DEFAULT=NULL
product_variant_spec_values|value_number|decimal(18,4)|NULLABLE=YES|DEFAULT=NULL
product_variant_spec_values|value_boolean|tinyint(1)|NULLABLE=YES|DEFAULT=NULL
product_variant_spec_values|created_at|timestamp|NULLABLE=YES|DEFAULT=NULL
product_variant_spec_values|updated_at|timestamp|NULLABLE=YES|DEFAULT=NULL
product_warranties|product_variant_id|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
stock_movements|product_variant_id|bigint(20) unsigned|NULLABLE=YES|DEFAULT=NULL
VARIANT_RELATED_COLUMNS_END
VARIANT_RELATED_FOREIGN_KEY_COUNT=13
VARIANT_RELATED_FOREIGN_KEYS_BEGIN
after_sales_action_items|product_variant_id|after_sales_action_items_variant_fk|REF=product_variants.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
after_sales_case_items|product_variant_id|after_sales_case_items_variant_fk|REF=product_variants.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
order_items|product_variant_id|order_items_variant_fk|REF=product_variants.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
products|default_variant_id|products_default_variant_fk|REF=product_variants.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
product_images|product_variant_id|product_images_variant_fk|REF=product_variants.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
product_variants|created_by|product_variants_created_by_fk|REF=users.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
product_variants|product_id|product_variants_product_fk|REF=products.id|ON_UPDATE=RESTRICT|ON_DELETE=CASCADE
product_variants|updated_by|product_variants_updated_by_fk|REF=users.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
product_variants|warranty_rule_id|product_variants_warranty_rule_fk|REF=warranty_rules.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
product_variant_spec_values|field_id|variant_spec_field_fk|REF=specification_fields.id|ON_UPDATE=RESTRICT|ON_DELETE=CASCADE
product_variant_spec_values|product_variant_id|variant_spec_variant_fk|REF=product_variants.id|ON_UPDATE=RESTRICT|ON_DELETE=CASCADE
product_warranties|product_variant_id|product_warranties_variant_fk|REF=product_variants.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
stock_movements|product_variant_id|stock_movements_variant_fk|REF=product_variants.id|ON_UPDATE=RESTRICT|ON_DELETE=SET NULL
VARIANT_RELATED_FOREIGN_KEYS_END
VARIANT_RELATED_INDEX_COUNT=20
VARIANT_RELATED_INDEXES_BEGIN
after_sales_action_items|after_sales_action_items_variant_index|NON_UNIQUE=1|COLUMNS=product_variant_id
after_sales_case_items|after_sales_case_items_variant_index|NON_UNIQUE=1|COLUMNS=product_variant_id
order_items|order_items_variant_order_index|NON_UNIQUE=1|COLUMNS=product_variant_id
products|products_default_variant_fk|NON_UNIQUE=1|COLUMNS=default_variant_id
products|products_variants_status_index|NON_UNIQUE=1|COLUMNS=variants_enabled
product_images|product_images_primary_sort_v216_idx|NON_UNIQUE=1|COLUMNS=product_variant_id
product_images|product_images_variant_sort_index|NON_UNIQUE=1|COLUMNS=product_variant_id
product_variants|PRIMARY|NON_UNIQUE=0|COLUMNS=id
product_variants|product_variants_created_by_fk|NON_UNIQUE=1|COLUMNS=created_by
product_variants|product_variants_product_default_index|NON_UNIQUE=1|COLUMNS=product_id,is_default
product_variants|product_variants_product_status_sort_index|NON_UNIQUE=1|COLUMNS=product_id,status,sort_order
product_variants|product_variants_runtime_v216_idx|NON_UNIQUE=1|COLUMNS=product_id,deleted_at,status,is_default
product_variants|product_variants_sku_unique|NON_UNIQUE=0|COLUMNS=sku
product_variants|product_variants_stock_index|NON_UNIQUE=1|COLUMNS=stock_quantity,low_stock_threshold
product_variants|product_variants_updated_by_fk|NON_UNIQUE=1|COLUMNS=updated_by
product_variants|product_variants_warranty_rule_fk|NON_UNIQUE=1|COLUMNS=warranty_rule_id
product_variant_spec_values|PRIMARY|NON_UNIQUE=0|COLUMNS=product_variant_id,field_id
product_variant_spec_values|variant_spec_field_number_index|NON_UNIQUE=1|COLUMNS=field_id,value_number
product_warranties|product_warranties_variant_status_index|NON_UNIQUE=1|COLUMNS=product_variant_id
stock_movements|stock_movements_variant_created_index|NON_UNIQUE=1|COLUMNS=product_variant_id
VARIANT_RELATED_INDEXES_END
VARIANT_NAMED_TABLE_COUNT=2
VARIANT_NAMED_TABLES_BEGIN
product_variants|ENGINE=InnoDB|EST_ROWS=0
product_variant_spec_values|ENGINE=InnoDB|EST_ROWS=0
VARIANT_NAMED_TABLES_END
VARIANT_SNAPSHOT_COLUMN_COUNT=3
VARIANT_SNAPSHOT_CONTENT_BEGIN
order_items|variant_sku_snapshot|NON_EMPTY=0
order_items|variant_name_snapshot|NON_EMPTY=0
order_items|variant_attributes_json|NON_EMPTY=0
VARIANT_SNAPSHOT_CONTENT_END
VARIANT_SNAPSHOT_NON_EMPTY_TOTAL=0
VARIANT_SNAPSHOT_DROP_ELIGIBLE=YES
CORE_VARIANT_SCHEMA_DROP_ELIGIBLE=YES

============================================================
2. EXACT POST-BATCH2B ACTIVE SOURCE SIGNAL MAP
============================================================
POST_BATCH2B_ACTIVE_COMPAT_VARIANT_SIGNAL_LINE_COUNT=444
POST_BATCH2B_ACTIVE_COMPAT_VARIANT_SIGNAL_FILE_COUNT=70
POST_BATCH2B_ACTIVE_COMPAT_VARIANT_SIGNAL_MAP_BEGIN
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:33:            'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_json'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:106:        'after_sales_case_items' => ['id', 'after_sales_case_id', 'order_item_id', 'product_id', 'product_variant_id', 'product_name_snapshot', 'quantity'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:111:        'after_sales_action_items' => ['id', 'after_sales_action_id', 'after_sales_case_item_id', 'product_id', 'product_variant_id', 'quantity', 'disposition', 'stock_effect'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:122:        'product_warranties' => ['id', 'warranty_number', 'order_id', 'order_item_id', 'product_variant_id', 'user_id', 'status', 'starts_at', 'expires_at', 'duration_months', 'duration_days', 'serial_numbers_json', 'next_maintenance_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:131:        'products' => ['id', 'product_type_id', 'brand_id', 'product_line_id', 'model_name', 'purchase_price_rsd', 'completeness_percent', 'name_is_manual', 'source_product_id', 'variants_enabled', 'default_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:136:        'product_variants' => ['id', 'product_id', 'sku', 'name', 'price_amount', 'price_currency', 'stock_quantity', 'low_stock_threshold', 'status', 'is_default', 'warranty_rule_id', 'deleted_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:137:        'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_number', 'value_boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:72:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:73:        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:91:        'order_items' => ['id', 'order_id', 'product_id', 'product_variant_id', 'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json', 'quantity', 'commission_total_eur_snapshot', 'purchase_unit_rsd_snapshot', 'purchase_total_rsd_snapshot', 'cost_source_snapshot', 'brand_name_snapshot', 'product_line_name_snapshot', 'product_type_name_snapshot'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:98:        'stock_movements' => ['id', 'product_id', 'product_variant_id', 'quantity_change', 'event_key', 'source', 'metadata_json', 'stock_receipt_id', 'inventory_count_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:91:                    (string) ($row->variant_sku_snapshot ?: $row->product_sku ?: '—'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:116:                    (string) ($row->variant_sku_snapshot ?: $row->product_sku ?: '—'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCreateDoctorCommand.php:30:        foreach (['users', 'roles', 'products', 'product_variants', 'bank_accounts'] as $table) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:103:            $lowStock = DB::table('products')->whereNull('deleted_at')->where('variants_enabled', false)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:104:            $lowVariantStock = Schema::hasTable('product_variants') ? DB::table('product_variants')->whereNull('deleted_at')->where('status', 'active')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count() : 0;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:35:        'stock_movements' => ['stock_receipt_id', 'inventory_count_id', 'product_variant_id', 'event_key', 'metadata_json'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PerformanceDoctorCommand.php:33:            'product_variants' => ['product_variants_runtime_v216_idx'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:16:final class ProductVariantsDoctorCommand extends Command
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:21:    public function handle(ProductVariantService $variants): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:27:                Product::query()->where('variants_enabled', true)->orderBy('id')->chunkById(100, function ($products) use ($variants): void {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:38:            'products' => ['id', 'variants_enabled', 'default_variant_id', 'stock_quantity', 'price_amount', 'price_currency'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:39:            'product_variants' => ['id', 'product_id', 'sku', 'name', 'price_amount', 'price_currency', 'stock_quantity', 'low_stock_threshold', 'status', 'is_default', 'warranty_rule_id', 'deleted_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:40:            'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_number', 'value_boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:41:            'product_images' => ['product_id', 'product_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:42:            'order_items' => ['product_id', 'product_variant_id', 'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:43:            'stock_movements' => ['product_id', 'product_variant_id', 'event_key'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:44:            'after_sales_case_items' => ['product_id', 'product_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:45:            'after_sales_action_items' => ['product_id', 'product_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:46:            'product_warranties' => ['product_id', 'product_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:68:        $routes = ['admin.products.variants.index', 'admin.products.variants.store', 'admin.products.variants.update', 'admin.products.variants.stock', 'admin.products.variants.default'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:77:            $duplicateSku = DB::table('product_variants')->selectRaw('LOWER(sku) normalized, COUNT(*) total')->whereNull('deleted_at')->groupByRaw('LOWER(sku)')->havingRaw('COUNT(*) > 1')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:78:            $crossSku = DB::table('product_variants as variants')->join('products', DB::raw('LOWER(products.sku)'), '=', DB::raw('LOWER(variants.sku)'))->whereNull('variants.deleted_at')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:79:            $multipleDefaults = DB::table('product_variants')->select('product_id')->whereNull('deleted_at')->where('is_default', true)->groupBy('product_id')->havingRaw('COUNT(*) > 1')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:80:            $wrongDefault = DB::table('products')->join('product_variants', 'product_variants.id', '=', 'products.default_variant_id')->whereColumn('product_variants.product_id', '!=', 'products.id')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:81:            $wrongOrderVariant = DB::table('order_items')->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')->whereColumn('product_variants.product_id', '!=', 'order_items.product_id')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:82:            $wrongAfterSales = DB::table('after_sales_case_items')->join('product_variants', 'product_variants.id', '=', 'after_sales_case_items.product_variant_id')->whereColumn('product_variants.product_id', '!=', 'after_sales_case_items.product_id')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:83:            $aggregateMismatch = DB::table('products')->where('variants_enabled', true)->whereRaw('stock_quantity <> (SELECT COALESCE(SUM(stock_quantity),0) FROM product_variants WHERE product_variants.product_id=products.id AND product_variants.status=? AND product_variants.deleted_at IS NULL)', ['active'])->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:8:use App\Services\ProductVariantService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:495:            'variants' => ['product_variant_spec_values', 'field_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DirectSaleController.php:24:            'product_variant_id' => ['prohibited'], // PRODUCT_VARIANTS_DECOMMISSION_SINGLE_PRODUCT_LOCK
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:127:            $rows = Product::query()->whereNull('deleted_at')->where('variants_enabled', false)->orderBy('sku')->get(['sku', 'name', 'stock_quantity', 'low_stock_threshold', 'status']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:41:                ->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:55:                'lowStock' => Product::query()->whereNull('deleted_at')->where('variants_enabled', false)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->limit(50)->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:101:        abort_unless((int) $variant->product_id === (int) $product->id && (int) $image->product_variant_id === (int) $variant->id, 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:11:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:15:use App\Services\ProductVariantService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:21:final class ProductVariantController extends Controller
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:34:        return view('admin.products.variants', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:45:    public function store(ProductVariantRequest $request, Product $product, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:49:        return redirect()->route('admin.products.variants.index', $product)->with('status', 'Varijanta '.$variant->sku.' je kreirana.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:52:    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:60:    public function adjust(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:73:    public function setDefault(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:81:    public function archive(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:89:    public function images(Request $request, Product $product, ProductVariant $variant, ProductImageService $images): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:8:use App\Http\Requests\ProductVariantRequest;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:98:    public function deleteImage(Product $product, ProductVariant $variant, ProductImage $image, ProductImageService $images): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:61:            'variants_enabled',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:62:            'default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:82:            if ($product && (bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:16:final class ProductVariantRequest extends FormRequest
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:31:        $variantId = $variant instanceof ProductVariant ? $variant->id : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:33:            'sku' => ['required', 'string', 'max:100', 'regex:#^[A-Z0-9._/-]+$#', Rule::unique('product_variants', 'sku')->ignore($variantId), Rule::unique('products', 'sku')],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:34:            'items.*.product_variant_id' => ['prohibited'], // PRODUCT_VARIANTS_DECOMMISSION_SINGLE_PRODUCT_LOCK
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:45:                $variantId = (int) ($item['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:52:                if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:54:                        $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izaberi konfiguraciju proizvoda.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:57:                    $valid = ProductVariant::query()->whereKey($variantId)->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->exists();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:58:                    if (!$valid) $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izabrana konfiguracija nije dostupna za ovaj proizvod.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:60:                    $validator->errors()->add('items.'.$index.'.product_variant_id', 'Ovaj proizvod nema aktivne varijante.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:71:            $variantId = (int) ($item['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:73:            if ($productId > 0 && $quantity > 0) $items[] = ['product_id' => $productId, 'product_variant_id' => $variantId > 0 ? $variantId : null, 'quantity' => $quantity];
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesActionItem.php:13:        'after_sales_action_id', 'after_sales_case_item_id', 'product_id', 'product_variant_id', 'sku_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesActionItem.php:25:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesCaseItem.php:12:    protected $fillable = ['after_sales_case_id', 'order_item_id', 'product_id', 'product_variant_id', 'sku_snapshot', 'product_name_snapshot', 'quantity', 'issue_description'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesCaseItem.php:16:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:16:        'order_id', 'product_id', 'product_variant_id', 'product_sku', 'product_name', 'quantity',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:18:        'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:37:            'variant_attributes_json' => 'array',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:43:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:20:        'legacy_synced_at', 'locally_modified_at', 'completeness_percent', 'name_is_manual', 'source_product_id', 'variants_enabled', 'default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:36:            'variants_enabled' => 'boolean',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:52:    public function images(): HasMany { return $this->hasMany(ProductImage::class)->whereNull('product_variant_id')->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:54:    public function primaryImage(): HasOne { return $this->hasOne(ProductImage::class)->whereNull('product_variant_id')->where('is_primary', true)->orderBy('sort_order'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:55:    public function variants(): HasMany { return $this->hasMany(ProductVariant::class)->whereNull('deleted_at')->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:56:    public function activeVariants(): HasMany { return $this->hasMany(ProductVariant::class)->whereNull('deleted_at')->where('status', 'active')->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:57:    public function defaultVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'default_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:16:        'product_id', 'product_variant_id', 'file_path', 'storage_disk', 'original_filename', 'mime_type', 'file_size',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:21:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariant.php:12:final class ProductVariant extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariant.php:37:    public function specificationValues(): HasMany { return $this->hasMany(ProductVariantSpecValue::class); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:10:final class ProductVariantSpecValue extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:12:    protected $table = 'product_variant_spec_values';
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:15:    protected $fillable = ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_json', 'value_number', 'value_boolean'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:18:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductWarranty.php:14:        'warranty_number', 'order_id', 'order_item_id', 'product_id', 'product_variant_id', 'user_id', 'warranty_rule_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductWarranty.php:41:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/StockMovement.php:15:        'event_key', 'product_id', 'product_variant_id', 'order_id', 'user_id', 'movement_type', 'source', 'quantity_change',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/StockMovement.php:37:        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:116:                if ($products->contains(static fn (Product $product): bool => (bool) $product->variants_enabled)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:46:                if ($products->contains(static fn (Product $product): bool => (bool) $product->variants_enabled)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:106:                    'product_variant_id' => $caseItem->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:13:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:291:        $variantIds = $effectItems->pluck('product_variant_id')->filter()->map(static fn (mixed $id): int => (int) $id)->unique()->sort()->values();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:292:        $variants = ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:299:            if ((bool) $product->variants_enabled && $item->product_variant_id === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:302:            if ($item->product_variant_id !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:303:                $variant = $variants->get((int) $item->product_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:30:        private readonly ProductVariantService $variants,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:310:        $required = $effectItems->where('stock_effect', 'decrease')->groupBy(static fn ($item): string => $item->product_variant_id ? 'v:'.$item->product_variant_id : 'p:'.$item->product_id)->map(static fn ($rows): int => (int) $rows->sum('quantity'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:313:                /** @var ProductVariant $variant */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:328:        foreach ($effectItems->sortBy(static fn ($item): string => str_pad((string) $item->product_id, 20, '0', STR_PAD_LEFT).'-'.str_pad((string) ($item->product_variant_id ?? 0), 20, '0', STR_PAD_LEFT).'-'.str_pad((string) $item->id, 20, '0', STR_PAD_LEFT)) as $item) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:338:            /** @var ProductVariant|null $variant */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:339:            $variant = $item->product_variant_id !== null ? $variants->get((int) $item->product_variant_id) : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:352:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:364:                    'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:97:                        'product_variant_id' => $item->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:98:                        'sku_snapshot' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:99:                        'product_name_snapshot' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:204:                    ->whereNull('product_images.product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:285:            ->select(['product_id', 'product_variant_id'])
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:288:            ->groupBy('product_id', 'product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:298:        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:303:            ->where('variants_enabled', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:306:                $query->whereNull('default_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:308:                        $variant->selectRaw('1')->from('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:309:                            ->whereColumn('product_variants.id', 'products.default_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:310:                            ->whereColumn('product_variants.product_id', 'products.id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:311:                            ->whereNull('product_variants.deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:315:        $samples = (clone $invalidDefault)->select(['id', 'sku', 'name', 'default_variant_id'])->orderBy('id')->limit($limit)->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:318:        $multipleDefaults = DB::table('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:355:        if (Schema::hasTable('product_variant_spec_values') && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:356:            $variantQuery = DB::table('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:357:                ->join('product_variants', 'product_variants.id', '=', 'product_variant_spec_values.product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:358:                ->join('products', 'products.id', '=', 'product_variants.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:359:                ->leftJoin('specification_fields', 'specification_fields.id', '=', 'product_variant_spec_values.field_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:362:                        ->on('product_type_fields.field_id', '=', 'product_variant_spec_values.field_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:371:                'product_variants.id', 'product_variants.sku', 'product_variants.name', 'product_variant_spec_values.field_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:372:            ])->orderBy('product_variants.id')->limit($limit)->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:411:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:412:            $metrics['variants_total'] = (int) DB::table('product_variants')->whereNull('deleted_at')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:431:            ->select(['product_id', 'product_variant_id'])
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:433:            ->groupBy('product_id', 'product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:443:                        $group->product_variant_id === null
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:444:                            ? $query->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:445:                            : $query->where('product_variant_id', (int) $group->product_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:462:        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants') || !Schema::hasColumn('products', 'default_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:468:            ->where('variants_enabled', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:474:                        $variants = DB::table('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:483:                            if ($product->default_variant_id !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:484:                                DB::table('products')->where('id', (int) $product->id)->update(['default_variant_id' => null]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:489:                        $chosen = $variants->firstWhere('id', (int) $product->default_variant_id) ?? $variants->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:491:                        if ((int) $product->default_variant_id === (int) $chosen->id && $defaultCount === 1 && (bool) $chosen->is_default) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:494:                        DB::table('product_variants')->where('product_id', (int) $product->id)->update(['is_default' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:495:                        DB::table('product_variants')->where('id', (int) $chosen->id)->update(['is_default' => true]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:496:                        DB::table('products')->where('id', (int) $product->id)->update(['default_variant_id' => (int) $chosen->id]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:129:        if ((bool) $lockedProduct->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:12:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:130:            if ($payload['product_variant_id'] === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:132:                    'product_variant_id' => 'Izaberi varijantu artikla.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:136:            $variant = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:138:                ->whereKey($payload['product_variant_id'])
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:145:            if (!$variant instanceof ProductVariant) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:147:                    'product_variant_id' => 'Izabrana varijanta nije dostupna.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:150:        } elseif ($payload['product_variant_id'] !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:152:                'product_variant_id' => 'Ovaj artikal nema aktivne varijante.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:159:            $variant instanceof ProductVariant ? $variant : $lockedProduct,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:163:        $quantityBefore = $variant instanceof ProductVariant
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:218:        if ($variant instanceof ProductVariant && (float) ($variant->purchase_price_rsd ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:229:            'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:237:            'variant_sku_snapshot' => $variant?->sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:238:            'variant_name_snapshot' => $variant?->name,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:239:            'variant_attributes_json' => $variant ? $this->variantAttributes($variant) : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:253:        if ($variant instanceof ProductVariant) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:259:            $parentStock = (int) ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:279:            'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:343:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:368:    private function catalogUnitPriceRsd(Product|ProductVariant $sellable, ?float $rate): float
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:383:    private function variantAttributes(ProductVariant $variant): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:50:        if (isset($input['product_variant_id']) && (int) $input['product_variant_id'] > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:52:                'product_variant_id' => 'Varijante proizvoda su trajno deaktivirane. Direktna prodaja koristi samo artikal.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:59:            'product_variant_id' => null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:97:    /** @param array{product_id:int,buyer_name:string,buyer_phone:?string,product_variant_id:?int,quantity:int,sale_price_rsd:float,payment_method:string} $payload */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/InventoryService.php:21:        if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:230:        $products = DB::table('products')->whereNull('deleted_at')->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:233:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:234:            $variants = DB::table('product_variants as v')->join('products as p', 'p.id', '=', 'v.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:24:        'products' => ['id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd', 'variants_enabled'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:367:                    ->orWhere($alias.'.variant_name_snapshot', 'like', $search)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:368:                    ->orWhere($alias.'.variant_sku_snapshot', 'like', $search);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:394:            ? DB::table('stock_movements')->where($kind === 'variant' ? 'product_variant_id' : 'product_id', $id)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:395:                ->when($kind === 'product', static fn ($q) => $q->whereNull('product_variant_id'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:399:            ? DB::table('stock_movements')->where($kind === 'variant' ? 'product_variant_id' : 'product_id', $id)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:400:                ->when($kind === 'product', static fn ($q) => $q->whereNull('product_variant_id'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:14:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:202:                ->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:241:            if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:242:                $lowVariants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:260:                        'action_url' => route('admin.products.variants.index', $variant->product_id).'#variant-'.$variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:261:                        'metadata_json' => ['product_variant_id' => $variant->id, 'stock' => $variant->stock_quantity, 'threshold' => $variant->low_stock_threshold],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:447:                $variantName = $this->text($item, 'variant_name_snapshot');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:448:                $variantSku = $this->text($item, 'variant_sku_snapshot');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:449:                $attributes = $item->getAttribute('variant_attributes_json');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:61:        $this->loadMany($order, 'items', 'order_items', ['id', 'order_id'], $warnings, ['product' => ['products', ['id']], 'variant' => ['product_variants', ['id']]]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:355:            'sku' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:356:            'name' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:237:        $variantId = (int) ($item->product_variant_id ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:238:        if ($variantId > 0 && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:239:            $variantCost = $this->positive(DB::table('product_variants')->where('id', $variantId)->value('purchase_price_rsd'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:52:                'oi.product_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:55:                'oi.variant_sku_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:56:                'oi.variant_name_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:11:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:149:            $variantId = (int) ($itemData['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:150:            /** @var ProductVariant|null $variant */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:153:            if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:182:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:185:                'variant_sku_snapshot' => $variant?->sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:186:                'variant_name_snapshot' => $variant?->name,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:187:                'variant_attributes_json' => $variantAttributes,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:219:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:235:            $aggregate = (int) ProductVariant::query()->where('product_id', $variantProductId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:328:    private function priceRsd(Product|ProductVariant $product, ?float $rate): float
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:341:    /** @param array<int,array<string,mixed>> $items @return array<int,array{product_id:int,product_variant_id:?int,quantity:int}> */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:347:            $variantId = (int) ($item['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:351:            if (!isset($normalized[$key])) $normalized[$key] = ['product_id' => $productId, 'product_variant_id' => $variantId > 0 ? $variantId : null, 'quantity' => 0];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:359:    private function variantAttributes(ProductVariant $variant): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:39:            if (is_array($item) && (int) ($item['product_variant_id'] ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:41:                    'items.'.(string) $index.'.product_variant_id' => 'Varijante proizvoda su trajno deaktivirane. Izaberi samo artikal.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:66:        $variantIds = array_values(array_unique(array_filter(array_map(static fn (array $item): int => (int) ($item['product_variant_id'] ?? 0), $items))));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:82:        $variants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:12:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:586:        foreach ($order->items->sortBy(fn ($item) => sprintf('%010d:%010d', (int) $item->product_id, (int) ($item->product_variant_id ?? 0))) as $item) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:592:            if ((int) ($item->product_variant_id ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:593:                $variant = ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->findOrFail((int) $item->product_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:606:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:615:                'metadata_json' => ['order_item_id' => $item->id, 'variant_sku' => $item->variant_sku_snapshot, 'one_time_return' => true],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:619:            $aggregate = (int) ProductVariant::query()->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:106:            if ((bool) $locked->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:10:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:206:            if (!empty($options['copy_variants']) && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:30:        private readonly ProductVariantService $variants,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:346:                return Schema::hasTable('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:347:                    && ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:443:                    || ProductVariant::query()->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])->exists(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:451:            $variant = ProductVariant::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:469:                DB::table('product_variant_spec_values')->insert([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:470:                    'product_variant_id' => $variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:493:            'completeness_percent','name_is_manual','source_product_id','variants_enabled','default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:123:                if (Schema::hasColumn('products', 'default_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:124:                    $locked->forceFill(['default_variant_id' => null])->saveQuietly();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:106:    public function cloneVariantImages(ProductVariant $source, ProductVariant $target): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:118:                'product_variant_id' => $target->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:138:        abort_if($image->product_variant_id !== null, 422, 'Glavna slika artikla ne može biti slika varijante.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:143:                ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:151:            ProductImage::query()->where('product_id', $product->id)->whereNull('product_variant_id')->update(['is_primary' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:235:            ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:258:            ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:275:            ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:27:    public function uploadVariant(Product $product, ProductVariant $variant, array $files): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:292:        $variantId = $image->product_variant_id !== null ? (int) $image->product_variant_id : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:298:            $variantId !== null ? $nextQuery->where('product_variant_id', $variantId) : $nextQuery->whereNull('product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:34:    private function uploadInternal(Product $product, ?ProductVariant $variant, array $files): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:45:            $variant ? $scope->where('product_variant_id', $variant->id) : $scope->whereNull('product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:49:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:9:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:105:    public function adjustStock(Product $product, ProductVariant $variant, int $change, string $note, string $key, User $actor): StockMovement
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:113:            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:119:                'event_key' => $eventKey, 'product_id' => $product->id, 'product_variant_id' => $locked->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:130:    public function archive(Product $product, ProductVariant $variant, User $actor): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:135:            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:144:    public function setDefault(Product $product, ProductVariant $variant, User $actor): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:149:            $locked = ProductVariant::query()->whereNull('deleted_at')->lockForUpdate()->findOrFail($variant->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:165:    private function setDefaultLocked(Product $product, ProductVariant $variant): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:167:        ProductVariant::query()->where('product_id', $product->id)->where('id', '!=', $variant->id)->update(['is_default' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:169:        $product->forceFill(['default_variant_id' => $variant->id, 'variants_enabled' => true])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:16:final class ProductVariantService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:174:        $base = ProductVariant::query()->where('product_id', $product->id)->whereNull('deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:180:        else $product->forceFill(['default_variant_id' => null, 'variants_enabled' => false])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:185:        $variants = ProductVariant::query()->where('product_id', $product->id)->whereNull('deleted_at')->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:187:            $product->forceFill(['variants_enabled' => false, 'default_variant_id' => null])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:191:        $default = $active->firstWhere('id', (int) $product->default_variant_id) ?? $active->first() ?? $variants->firstWhere('id', (int) $product->default_variant_id) ?? $variants->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:192:        ProductVariant::query()->where('product_id', $product->id)->update(['is_default' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:193:        if ($default) ProductVariant::query()->whereKey($default->id)->update(['is_default' => true]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:195:            'variants_enabled' => true,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:196:            'default_variant_id' => $default?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:206:    private function syncSpecifications(Product $product, ProductVariant $variant, array $specs, array $details, array $structured = []): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:208:        DB::table('product_variant_spec_values')->where('product_variant_id', $variant->id)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:213:            $row = ['product_variant_id' => $variant->id, 'field_id' => $field->id, 'value_text' => null, 'value_detail' => null, 'value_json' => null, 'value_number' => null, 'value_boolean' => null, 'created_at' => now(), 'updated_at' => now()];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:21:    public function create(Product $product, array $data, User $actor): ProductVariant
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:231:                DB::table('product_variant_spec_values')->insert($row);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:23:        return DB::transaction(function () use ($product, $data, $actor): ProductVariant {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:258:    private function assertOwner(Product $product, ProductVariant $variant): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:264:    private function snapshot(ProductVariant $variant): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:32:            $variant = ProductVariant::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:54:                    'product_variant_id' => $variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:65:            $mustDefault = (bool) ($data['is_default'] ?? false) || !ProductVariant::query()->where('product_id', $locked->id)->where('id', '!=', $variant->id)->whereNull('deleted_at')->exists();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:74:    public function update(Product $product, ProductVariant $variant, array $data, User $actor): ProductVariant
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:77:        return DB::transaction(function () use ($product, $variant, $data, $actor): ProductVariant {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:79:            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:118:            'orphan_variant_values' => $this->countOrphans('product_variant_spec_values', 'field_id', 'specification_fields', 'id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:119:                + $this->countOrphans('product_variant_spec_values', 'product_variant_id', 'product_variants', 'id'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:137:            $this->deleteOrphans('product_variant_spec_values', 'field_id', 'specification_fields', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:138:            $this->deleteOrphans('product_variant_spec_values', 'product_variant_id', 'product_variants', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:30:        $variantCount = Schema::hasTable('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:31:            ? (int) DB::table('product_variant_spec_values')->where('field_id', $fieldId)->count()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:323:        if (!Schema::hasTable('product_variant_spec_values') || !Schema::hasTable('product_variants') || !Schema::hasTable('products') || !Schema::hasTable('product_type_fields')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:327:        return (int) DB::table('product_variant_spec_values as values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:328:            ->join('product_variants as variants', 'variants.id', '=', 'values.product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:345:        DB::table('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:348:                    ->from('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:352:                            ->on('product_type_fields.field_id', '=', 'product_variant_spec_values.field_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:354:                    ->whereColumn('variants.id', 'product_variant_spec_values.product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:56:            if (Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:57:                DB::table('product_variant_spec_values')->where('field_id', $fieldId)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:156:            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:202:            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:351:        DB::table('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:358:                    if ($this->normalizeOne('product_variant_spec_values', 'product_variant_id', (int) $entity->id, $sourceId, $totalId)) $changed++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:451:        DB::table('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:459:                        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:460:                        'product_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:273:                if ($variantIds !== [] && Schema::hasColumn($table, 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:275:                        $nested->orWhereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:277:                        $nested->whereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:386:        if ($variantIds !== [] && Schema::hasColumn('products', 'default_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:388:                ->whereIn('default_variant_id', $variantIds)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:389:                ->update(['default_variant_id' => null]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:417:            if ($variantIds !== [] && Schema::hasColumn($table, 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:418:                $count = (int) DB::table($table)->whereIn('product_variant_id', $variantIds)->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:421:                    $this->assertNullable($table, 'product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:424:                        ->whereIn('product_variant_id', $variantIds)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:425:                        ->update(['product_variant_id' => null]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:462:            ['product_sku', 'product_name', 'variant_sku_snapshot', 'variant_name_snapshot'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:474:                'variant_sku_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:475:                'variant_name_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:518:                                    ->where('auditable_type', 'like', '%ProductVariant%');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:558:                    if ($variantIds !== [] && Schema::hasColumn('operational_alerts', 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:560:                            $nested->orWhereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:562:                            $nested->whereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:612:        if ($variantIds !== [] && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:613:            $counts['owned_rows_deleted'] += DB::table('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:614:                ->whereIn('product_variant_id', $variantIds)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:626:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:627:            $counts['owned_rows_deleted'] += DB::table('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:659:            if ($variantIds !== [] && Schema::hasColumn($table, 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:661:                    $nested->orWhereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:663:                    $nested->whereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:872:                in_array($keyLower, ['product_variant_id', 'variant_id'], true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:100:            ->get(['id', 'product_variant_id', 'storage_disk', 'file_path']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:171:                if ($variantIds !== [] && Schema::hasColumn('order_items', 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:172:                    $nested->orWhereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:212:                'product_variant_id' => $image->product_variant_id === null
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:214:                    : (int) $image->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:27:        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:28:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:314:                    in_array($column, ['product_id', 'product_variant_id', 'source_product_id', 'default_variant_id'], true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:469:                    $nested->where('k.REFERENCED_TABLE_NAME', 'product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:53:        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:54:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:70:        $variants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:9:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:59:                    'product_variant_id' => $item->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:74:                    'product_sku_snapshot' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:75:                    'product_name_snapshot' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/bin/catalog-settings-integrity-hotfix-smoke.php:25:$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/catalog-settings-integrity-hotfix-smoke.php:26:$variantService = $source('app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/catalog-settings-integrity-hotfix-smoke.php:35:$check('Trajno brisanje polja eksplicitno uklanja sve povezane reference', str_contains($fieldLifecycle, "product_variant_spec_values')->where('field_id'") && str_contains($fieldLifecycle, "product_spec_values')->where('field_id'") && str_contains($fieldLifecycle, "product_type_fields')->where('field_id'") && str_contains($fieldLifecycle, "specification_options')->where('field_id'"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/cms-v2.1.6-smoke.php:40:$check('Migracija kreira indekse galerija i varijanti', str_contains($migration, 'product_images_primary_sort_v216_idx') && str_contains($migration, 'product_variants_runtime_v216_idx'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-media-ux-smoke.php:33:$check('Reorder čuva sve slike i odbacuje tuđe ID-jeve', str_contains($service, '$existingLookup') && str_contains($service, 'Neposlati ID-jevi se dodaju na kraj') && str_contains($service, "whereNull('product_variant_id')"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-save-regex-hotfix-smoke.php:22:$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-save-regex-hotfix-smoke.php:27:$check('ProductVariantRequest koristi bezbedan SKU regex delimiter', str_contains($variantRequest, $safeRule));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:13:$migration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:14:$service = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:24:$doctor = (string) file_get_contents($root.'/app/Console/Commands/ProductVariantsDoctorCommand.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:26:$check('Migracija uvodi varijante, specifikacije i istorijske snapshot kolone', str_contains($migration, 'product_variants') && str_contains($migration, 'product_variant_spec_values') && str_contains($migration, 'variant_attributes_json'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:30:$check('Porudžbina zaključava varijantu i čuva snapshot', str_contains($order, 'lockForUpdate') && str_contains($order, 'variant_sku_snapshot') && str_contains($order, 'variant_attributes_json'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:31:$check('Otkazivanje vraća lager na istu varijantu', str_contains($workflow, 'product_variant_id') && str_contains($workflow, 'cancel-return'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:32:$check('Postprodajna zamena i povrat koriste konkretnu varijantu', str_contains($afterSales, 'ProductVariant::query()') && str_contains($afterSales, "'product_variant_id' => \$variant?->id"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:37:$check('Forma porudžbine zahteva izbor konfiguracije', str_contains($orderView, 'product_variant_id') && str_contains($orderView, 'variantMap'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/stable-maintenance-smoke.php:32:$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:742:$productVariantsMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:743:$productVariantService = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:744:$productVariantRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:745:$productVariantController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductVariantController.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:748:$productVariantDoctor = (string) file_get_contents($root.'/app/Console/Commands/ProductVariantsDoctorCommand.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:749:$productVariantFeature = (string) file_get_contents($root.'/tests/Feature/ProductVariantsWorkflowTest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:750:$check('beta7.20 migracija uvodi varijante specifikacije slike i snapshot', str_contains($productVariantsMigration, 'product_variants') && str_contains($productVariantsMigration, 'product_variant_spec_values') && str_contains($productVariantsMigration, 'variant_attributes_json'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:754:$check('beta7.20 serverska validacija štiti SKU i korelisane specifikacije', str_contains($productVariantRequest, "Rule::unique('product_variants'") && str_contains($productVariantRequest, 'specification_option_dependencies'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:757:$check('beta7.20 porudžbina čuva variant snapshot i vraća isti lager', str_contains((string) file_get_contents($root.'/app/Services/OrderService.php'), 'variant_sku_snapshot') && str_contains((string) file_get_contents($root.'/app/Services/OrderWorkflowService.php'), 'product_variant_id'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:758:$check('beta7.20 postprodaja garancija i stock movement nose variant id', str_contains((string) file_get_contents($root.'/app/Services/AfterSalesActionService.php'), "'product_variant_id' => \$variant?->id") && str_contains((string) file_get_contents($root.'/app/Services/WarrantyService.php'), "'product_variant_id' => \$item->product_variant_id") && str_contains((string) file_get_contents($root.'/app/Models/StockMovement.php'), 'product_variant_id'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:972:$productVariantRequestRegex = (string) @file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:974:$check('v2.1.3.3 ProductVariantRequest zadržava validan SKU regex delimiter', str_contains($productVariantRequestRegex, "'regex:#^[A-Z0-9._/-]+$#'"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:979:$storageVariantRequest = (string) @file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:980:$storageVariantService = (string) @file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/storage-capacity-total-smoke.php:39:$variantRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/storage-capacity-total-smoke.php:41:$variantService = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:110:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:150:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:307:        'product_variants' => [
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:36:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:70:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:347:                    @if($product->exists && $product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:17:<form method="post" data-variant-form action="{{ route('admin.products.variants.update',[$product,$variant]) }}">@csrf @method('PUT')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:24:<form class="panel nested-panel" method="post" action="{{ route('admin.products.variants.stock',[$product,$variant]) }}">@csrf
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:27:<form class="panel nested-panel" method="post" enctype="multipart/form-data" action="{{ route('admin.products.variants.images',[$product,$variant]) }}">@csrf
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:33:<form id="default-{{ $variant->id }}" method="post" action="{{ route('admin.products.variants.default',[$product,$variant]) }}">@csrf</form>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:34:<form id="archive-{{ $variant->id }}" method="post" action="{{ route('admin.products.variants.archive',[$product,$variant]) }}">@csrf @method('DELETE')</form>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:35:@foreach($variant->images as $image)<form id="delete-variant-image-{{ $image->id }}" method="post" action="{{ route('admin.products.variants.images.delete',[$product,$variant,$image]) }}">@csrf @method('DELETE')</form>@endforeach
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:9:<form method="post" data-variant-form action="{{ route('admin.products.variants.store',$product) }}">@csrf
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/stock/index.blade.php:6:<section class="panel form-section"><h2>Trenutno stanje</h2><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal</th><th>Lager</th><th>Prag</th>@can('stock.adjust')<th>Korekcija</th>@endcan</tr></thead><tbody>@forelse($products as $product)<tr><td><strong>{{ $product->sku }}</strong></td><td>{{ $product->name }}</td><td><strong class="{{ $product->stock_quantity <= $product->low_stock_threshold ? 'text-danger' : 'text-success' }}">{{ $product->stock_quantity }}</strong></td><td>{{ $product->low_stock_threshold }}</td>@can('stock.adjust')<td>@if($product->variants_enabled)<a class="button button-ghost button-small" href="{{ route('admin.products.variants.index',$product) }}">Varijante ({{ $product->variants_count }})</a>@else<form class="inline-form" method="post" action="{{ route('admin.stock.adjust',$product) }}">@csrf<input type="hidden" name="idempotency_key" value="{{ $idempotencyKeys[$product->id] }}"><input type="number" name="quantity_change" required placeholder="+/-" class="ux-stock-max-width-90"><input name="note" required maxlength="1000" placeholder="Obavezan razlog"><button class="button button-ghost button-small" type="submit">Primeni</button></form>@endif</td>@endcan</tr>@empty<tr><td colspan="5">Nema artikala.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:308:                @if($product->variants_enabled)<span class="success">{{ $product->activeVariants->count() }} varijanti</span>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:326:                        @if($product->variants_enabled && $variantPrices->isNotEmpty() && $variantPrices->min() !== $variantPrices->max())
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:151:        @if($product->variants_enabled && $product->activeVariants->isNotEmpty())
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:253:            @if(!$product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:312:            @if($product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:315:                    <select name="product_variant_id" required>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:318:                            <option value="{{ $variant->id }}" @selected((string) old('product_variant_id') === (string) $variant->id)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:176:        const preselected = Number(initialItems?.[index]?.product_variant_id || (index === 0 ? selectedVariantId : 0));
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:64:                                                    @if($product->variants_enabled) · {{ $product->activeVariants->count() }} varijanti @endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:70:                                        <select name="items[{{ $index }}][product_variant_id]" data-order-variant>
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogDetailPageTest.php:143:        $product->update(['variants_enabled' => true]);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogDetailPageTest.php:145:        ProductVariant::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogDetailPageTest.php:9:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:16:final class ProductVariantsWorkflowTest extends TestCase
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:63:        self::assertTrue($product->variants_enabled);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:65:        self::assertNotNull($product->default_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php:34:        self::assertStringContainsString("product_variant_spec_values')->where('field_id'", $service);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:25:        $variantRsd = new ProductVariant();
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:33:        $variantEur = new ProductVariant();
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:82:        self::assertStringContainsString('$variant instanceof ProductVariant ? $variant : $lockedProduct', $direct);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:90:        self::assertStringContainsString('private function priceRsd(Product|ProductVariant $product, ?float $rate): float', $orders);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductSaveRegexHotfixContractTest.php:17:        $variant = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:13:        $source = (string) file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:14:        self::assertStringContainsString('product_variants', $source);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:15:        self::assertStringContainsString('product_variant_spec_values', $source);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:9:final class ProductVariantsMigrationContractTest extends TestCase
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsUiContractTest.php:18:        self::assertStringContainsString('product_variant_id', $order);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsUiContractTest.php:9:final class ProductVariantsUiContractTest extends TestCase
POST_BATCH2B_ACTIVE_COMPAT_VARIANT_SIGNAL_MAP_END

============================================================
3. FEATURE FILE + HISTORICAL MIGRATION POLICY
============================================================
DORMANT_VARIANT_FEATURE_FILE=EXISTS|app/Http/Controllers/Admin/ProductVariantController.php|SHA256=71e00452deeb5926adf95e4c110fb1e44cf14feb012ca4bc2d75914f68be101f
DORMANT_VARIANT_FEATURE_FILE=EXISTS|app/Http/Requests/ProductVariantRequest.php|SHA256=841b65ed115fde3ebd1a150ecd74f6bddc742d004e4236413f79077522cf4a84
DORMANT_VARIANT_FEATURE_FILE=EXISTS|app/Services/ProductVariantService.php|SHA256=6dc9830ea5c069817809ed82be4cf71f1f027938050929e146902ef8071770ac
DORMANT_VARIANT_FEATURE_FILE=EXISTS|app/Models/ProductVariant.php|SHA256=31cf241371f2c62e009037ee5c3191a178c4ed7f27e72ca70f5b92f65afe3883
DORMANT_VARIANT_FEATURE_FILE=EXISTS|app/Models/ProductVariantSpecValue.php|SHA256=b46c9af225f1ac175b15e816014939b7cc4f44852aaf48cd551ccf7dca4f1d5f
DORMANT_VARIANT_FEATURE_FILE=EXISTS|app/Console/Commands/ProductVariantsDoctorCommand.php|SHA256=4a6c2de92d2a4dca5e08f569d05533d9dcfc5204385a5303cb263e19b46425ea
DORMANT_VARIANT_FEATURE_FILE=EXISTS|resources/views/admin/products/variants.blade.php|SHA256=43dfd0d546dd59842541b91f0ff1f4123bed408293a9fd336f03afec085281a1
DORMANT_VARIANT_FEATURE_FILE_COUNT=7
HISTORICAL_VARIANT_MIGRATION=PRESERVE|database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php|SHA256=04f4cde657404725cf6f898e33767564e23610b9de583eaff0490d04e45eee67
HISTORICAL_SCHEMA_POLICY=DO_NOT_DELETE_MIGRATION_HISTORY_FILES

============================================================
4. STATIC CHECK + SMOKE/TEST DECOMMISSION TARGETS
============================================================
STATIC_CHECK_VARIANT_SIGNAL_LINE_COUNT=23
STATIC_CHECK_VARIANT_SIGNALS_BEGIN
742:$productVariantsMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
743:$productVariantService = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
744:$productVariantRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
745:$productVariantController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductVariantController.php');
746:$productVariantsView = (string) file_get_contents($root.'/resources/views/admin/products/variants.blade.php');
747:$productVariantFields = (string) file_get_contents($root.'/resources/views/admin/products/partials/variant-fields.blade.php');
748:$productVariantDoctor = (string) file_get_contents($root.'/app/Console/Commands/ProductVariantsDoctorCommand.php');
749:$productVariantFeature = (string) file_get_contents($root.'/tests/Feature/ProductVariantsWorkflowTest.php');
750:$check('beta7.20 migracija uvodi varijante specifikacije slike i snapshot', str_contains($productVariantsMigration, 'product_variants') && str_contains($productVariantsMigration, 'product_variant_spec_values') && str_contains($productVariantsMigration, 'variant_attributes_json'));
751:$check('beta7.20 migracija je recovery-safe za MariaDB i proširuje istorijske module', str_contains($productVariantsMigration, 'addColumn') && str_contains($productVariantsMigration, 'extendAfterSales') && str_contains($productVariantsMigration, 'extendWarranties'));
752:$check('beta7.20 varijanta ima SKU cenu lager status default i garanciju', str_contains($productVariantService, 'stock_quantity') && str_contains($productVariantService, 'is_default') && str_contains($productVariantService, 'warranty_rule_id'));
753:$check('beta7.20 default preferira aktivnu varijantu i roditelj sabira aktivan lager', str_contains($productVariantService, "where('status', 'active')") && str_contains($productVariantService, 'syncParentLocked'));
754:$check('beta7.20 serverska validacija štiti SKU i korelisane specifikacije', str_contains($productVariantRequest, "Rule::unique('product_variants'") && str_contains($productVariantRequest, 'specification_option_dependencies'));
755:$check('beta7.20 admin ima CRUD lager slike i default varijantu', str_contains($productVariantController, 'adjustStock') && str_contains($productVariantController, 'setDefault') && str_contains($productVariantController, 'uploadVariant'));
756:$check('beta7.20 UI filtrira zavisne specifikacije varijante', str_contains($productVariantsView, 'data-variant-form') && str_contains($productVariantFields, 'data-parent-option-ids'));
757:$check('beta7.20 porudžbina čuva variant snapshot i vraća isti lager', str_contains((string) file_get_contents($root.'/app/Services/OrderService.php'), 'variant_sku_snapshot') && str_contains((string) file_get_contents($root.'/app/Services/OrderWorkflowService.php'), 'product_variant_id'));
758:$check('beta7.20 postprodaja garancija i stock movement nose variant id', str_contains((string) file_get_contents($root.'/app/Services/AfterSalesActionService.php'), "'product_variant_id' => \$variant?->id") && str_contains((string) file_get_contents($root.'/app/Services/WarrantyService.php'), "'product_variant_id' => \$item->product_variant_id") && str_contains((string) file_get_contents($root.'/app/Models/StockMovement.php'), 'product_variant_id'));
762:$check('beta7.20 doctor proverava SKU default snapshot i aggregate', str_contains($productVariantDoctor, 'app:product-variants-doctor') && str_contains($productVariantDoctor, 'aggregateMismatch'));
763:$check('beta7.20 feature i smoke testovi postoje', str_contains($productVariantFeature, 'test_admin_can_create_variant_and_parent_receives_aggregate_stock') && is_file($root.'/bin/product-variant-smoke.php'));
972:$productVariantRequestRegex = (string) @file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
974:$check('v2.1.3.3 ProductVariantRequest zadržava validan SKU regex delimiter', str_contains($productVariantRequestRegex, "'regex:#^[A-Z0-9._/-]+$#'"));
979:$storageVariantRequest = (string) @file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
980:$storageVariantService = (string) @file_get_contents($root.'/app/Services/ProductVariantService.php');
STATIC_CHECK_VARIANT_SIGNALS_END
VARIANT_TEST_SMOKE_TARGET=bin/product-variant-smoke.php|SIGNALS=8
VARIANT_TEST_SMOKE_TARGET=bin/storage-capacity-total-smoke.php|SIGNALS=2
VARIANT_TEST_SMOKE_TARGET=bin/catalog-settings-integrity-hotfix-smoke.php|SIGNALS=3
VARIANT_TEST_SMOKE_TARGET=bin/stable-maintenance-smoke.php|SIGNALS=1
VARIANT_TEST_SMOKE_TARGET=bin/product-save-regex-hotfix-smoke.php|SIGNALS=2
VARIANT_TEST_SMOKE_TARGET=tests/Unit/ProductVariantsUiContractTest.php|SIGNALS=2
VARIANT_TEST_SMOKE_TARGET=tests/Feature/ProductVariantsWorkflowTest.php|SIGNALS=3
VARIANT_TEST_SMOKE_TARGET=tests/Unit/ProductVariantsMigrationContractTest.php|SIGNALS=4
VARIANT_TEST_SMOKE_TARGET=tests/Feature/CatalogDetailPageTest.php|SIGNALS=3
VARIANT_TEST_SMOKE_TARGET=tests/Unit/DirectSaleMaxUnitPriceContractTest.php|SIGNALS=5
VARIANT_TEST_SMOKE_TARGET=tests/Unit/ProductSaveRegexHotfixContractTest.php|SIGNALS=1
PRODUCT_VARIANTS_MIGRATION_CONTRACT_POLICY=PRESERVE_OR_REWRITE_TO_HISTORICAL_ONLY_DO_NOT_DELETE_MIGRATION_HISTORY

============================================================
5. OPENAPI + MOBILE CUTOVER RECERTIFICATION
============================================================
OPENAPI_PUBLIC_VARIANT_SIGNAL_COUNT=0
OPENAPI_PUBLIC_VARIANT_CONTRACT=PASS_ZERO_PARITY_3_COPIES
MOBILE_ACTIVE_VARIANT_SIGNAL_LINE_COUNT=5
MOBILE_ACTIVE_VARIANT_SIGNAL_FILE_COUNT=1
MOBILE_ACTIVE_VARIANT_SIGNALS_BEGIN
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:343:assert(cart.includes('productId') && cart.includes('quantity') && !cart.includes('variantId') && !cart.includes('variantName'), 'Lokalna korpa koristi samo proizvod i količinu; variant identitet je dekomisioniran.');
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:351:assert(!productVariantsDecommissionApiTypes.includes('ProductVariant') && !productVariantsDecommissionApiTypes.includes('variants_enabled') && !productVariantsDecommissionApiTypes.includes('product_variant_id') && !productVariantsDecommissionApiTypes.includes('variant_name') && !productVariantsDecommissionApiTypes.includes('variant_attributes'), 'Mobile API tipovi više ne izlažu Product Variants.');
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:352:assert(!productVariantsDecommissionProductScreen.includes('variants_enabled') && !productVariantsDecommissionProductScreen.includes('selectedVariant') && !productVariantsDecommissionProductScreen.includes('variantId') && !productVariantsDecommissionProductScreen.includes('variantName'), 'Mobile Product detalj više nema variant izbor.');
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:353:assert(!checkout.includes('product_variant_id') && !checkout.includes('variantId'), 'Mobile checkout šalje samo product_id i quantity.');
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:354:assert(!productVariantsDecommissionAdminAfterSales.includes('product_variant_id'), 'Admin After-sales Mobile contract više ne izlaže product_variant_id.');
MOBILE_ACTIVE_VARIANT_SIGNALS_END
FAIL: active Mobile variant signals remain after Batch 2B2

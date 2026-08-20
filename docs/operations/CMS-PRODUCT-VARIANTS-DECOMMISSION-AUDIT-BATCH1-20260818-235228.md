============================================================
CMS PRODUCT VARIANTS - DECOMMISSION READ-ONLY AUDIT - BATCH 1
============================================================
DATE=Tue Aug 18 23:52:28 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-AUDIT-BATCH1-20260818-235228.md
MODE=READ_ONLY_DECOMMISSION_DISCOVERY
TARGET=REMOVE_PRODUCT_VARIANTS_OPERATIONAL_FEATURE_AND_SCHEMA_WHEN_SAFE
SOURCE_WRITES_EXPECTED=0
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
ROUTE_CACHE_CHANGED=NO
VIEW_CACHE_CHANGED=NO
OPENAPI_CHANGED=NO
MOBILE_SOURCE_CHANGED=0
EAS_BUILD=NO

============================================================
0. PREFLIGHT
============================================================
PASS command: php
PASS command: grep
PASS command: sed
PASS command: awk
PASS command: cut
PASS command: head
PASS command: tail
PASS command: cat
PASS command: mktemp
PASS command: date
PASS command: mkdir
PASS command: rmdir
PASS command: sha256sum
PASS command: sort
PASS command: wc
PASS command: find
PASS command: git
PASS command: tee
PASS command: cmp
PASS command: diff
PASS command: realpath
PHP_VERSION=8.4.23
MOBILE_PACKAGE_VERSION=0.7.0
CMS_VERSION=2.2.0
CONCURRENCY_LOCK=ACQUIRED
PREFLIGHT=PASS

============================================================
1. CURRENT VARIANT RUNTIME ROUTE SURFACE
============================================================

  GET|HEAD  admin/catalog ............................................................................................. admin.products.index › Admin\ProductController@index
  POST      admin/catalog ............................................................................................. admin.products.store › Admin\ProductController@store
  GET|HEAD  admin/catalog-settings/product-type/{productType:slug} ........................... admin.dictionary.product-type › Admin\CatalogDictionaryController@productType
  PATCH     admin/catalog-settings/product-type/{productType:slug}/fields/reorder admin.dictionary.product-type.fields.reorder › Admin\CatalogDictionaryController@reorderT…
  GET|HEAD  admin/catalog-settings/{resource} ............................................................. admin.dictionary.index › Admin\CatalogDictionaryController@index
  POST      admin/catalog-settings/{resource} ............................................................. admin.dictionary.store › Admin\CatalogDictionaryController@store
  PATCH     admin/catalog-settings/{resource}/reorder ................................................. admin.dictionary.reorder › Admin\CatalogDictionaryController@reorder
  PUT       admin/catalog-settings/{resource}/{item} .................................................... admin.dictionary.update › Admin\CatalogDictionaryController@update
  DELETE    admin/catalog-settings/{resource}/{item} .................................................. admin.dictionary.destroy › Admin\CatalogDictionaryController@destroy
  DELETE    admin/catalog-settings/{resource}/{item}/purge ................................................ admin.dictionary.purge › Admin\CatalogDictionaryController@purge
  GET|HEAD  admin/catalog/archived .............................................................................. admin.products.archived › Admin\ProductController@archived
  GET|HEAD  admin/catalog/bulk ..................................................................................... admin.products.bulk › Admin\ProductBulkController@index
  POST      admin/catalog/bulk ........................................................................... admin.products.bulk.process › Admin\ProductBulkController@process
  GET|HEAD  admin/catalog/create .................................................................................... admin.products.create › Admin\ProductController@create
  POST      admin/catalog/name-preview ................................................................... admin.products.name-preview › Admin\ProductController@namePreview
  GET|HEAD  admin/catalog/{product} ................................................................................... admin.products.manage › Admin\ProductController@edit
  PUT       admin/catalog/{product} ................................................................................. admin.products.update › Admin\ProductController@update
  DELETE    admin/catalog/{product} ............................................................................... admin.products.archive › Admin\ProductController@archive
  GET|HEAD  admin/catalog/{product}/clone ......................................................................... admin.products.clone › Admin\ProductController@cloneForm
  POST      admin/catalog/{product}/clone .................................................................. admin.products.clone.store › Admin\ProductController@cloneStore
  POST      admin/catalog/{product}/direct-sale .................................................................... admin.products.direct-sale › Admin\DirectSaleController
  GET|HEAD  admin/catalog/{product}/edit ................................................................................ admin.products.edit › Admin\ProductController@edit
  GET|HEAD  admin/catalog/{product}/images ................................................................ admin.products.images.index › Admin\ProductImageController@index
  POST      admin/catalog/{product}/images ................................................................ admin.products.images.store › Admin\ProductImageController@store
  PUT       admin/catalog/{product}/images/reorder .................................................... admin.products.images.reorder › Admin\ProductImageController@reorder
  DELETE    admin/catalog/{product}/images/{image} .................................................... admin.products.images.destroy › Admin\ProductImageController@destroy
  PATCH     admin/catalog/{product}/images/{image}/primary ............................................ admin.products.images.primary › Admin\ProductImageController@primary
  PATCH     admin/catalog/{product}/images/{image}/rotate ............................................... admin.products.images.rotate › Admin\ProductImageController@rotate
  DELETE    admin/catalog/{product}/purge ............................................................................. admin.products.purge › Admin\ProductController@purge
  POST      admin/catalog/{product}/regenerate-name ................................................ admin.products.regenerate-name › Admin\ProductController@regenerateName
  POST      admin/catalog/{product}/restore ....................................................................... admin.products.restore › Admin\ProductController@restore
  DELETE    admin/catalog/{product}/total-purge ............................................................ admin.products.total-purge › Admin\ProductController@totalPurge
  GET|HEAD  admin/catalog/{product}/variants .......................................................... admin.products.variants.index › Admin\ProductVariantController@index
  POST      admin/catalog/{product}/variants .......................................................... admin.products.variants.store › Admin\ProductVariantController@store
  PUT       admin/catalog/{product}/variants/{variant} .............................................. admin.products.variants.update › Admin\ProductVariantController@update
  DELETE    admin/catalog/{product}/variants/{variant} ............................................ admin.products.variants.archive › Admin\ProductVariantController@archive
  POST      admin/catalog/{product}/variants/{variant}/default ................................. admin.products.variants.default › Admin\ProductVariantController@setDefault
  POST      admin/catalog/{product}/variants/{variant}/images ....................................... admin.products.variants.images › Admin\ProductVariantController@images
  DELETE    admin/catalog/{product}/variants/{variant}/images/{image} ................... admin.products.variants.images.delete › Admin\ProductVariantController@deleteImage
  POST      admin/catalog/{product}/variants/{variant}/stock ......................................... admin.products.variants.stock › Admin\ProductVariantController@adjust
  GET|HEAD  api/v1/admin/catalog/options ...................................................... api.v1.admin.catalog.options › Api\V1\Admin\CatalogProductController@options
  POST      api/v1/admin/catalog/products ................................................ api.v1.admin.catalog.products.store › Api\V1\Admin\CatalogProductController@store
  POST      api/v1/admin/catalog/products/{product}/images ....................... api.v1.admin.catalog.products.images.store › Api\V1\Admin\CatalogProductController@images

                                                                                                                                                         Showing [43] routes

ADMIN_CATALOG_ROUTE_LIST_EXIT_CODE=0
VARIANT_WEB_ROUTE_SIGNAL_COUNT=8
VARIANT_ADMIN_API_ROUTE_SIGNAL_COUNT=0
VARIANT_WEB_RUNTIME_FEATURE=YES

============================================================
2. VARIANT FEATURE FILE INVENTORY
============================================================
VARIANT_FEATURE_FILE=EXISTS|app/Http/Controllers/Admin/ProductVariantController.php|SHA256=71e00452deeb5926adf95e4c110fb1e44cf14feb012ca4bc2d75914f68be101f
VARIANT_FEATURE_FILE=EXISTS|app/Http/Requests/ProductVariantRequest.php|SHA256=841b65ed115fde3ebd1a150ecd74f6bddc742d004e4236413f79077522cf4a84
VARIANT_FEATURE_FILE=EXISTS|app/Services/ProductVariantService.php|SHA256=6dc9830ea5c069817809ed82be4cf71f1f027938050929e146902ef8071770ac
VARIANT_FEATURE_FILE=EXISTS|app/Models/ProductVariant.php|SHA256=31cf241371f2c62e009037ee5c3191a178c4ed7f27e72ca70f5b92f65afe3883
VARIANT_FEATURE_FILE=EXISTS|app/Models/ProductVariantSpecValue.php|SHA256=b46c9af225f1ac175b15e816014939b7cc4f44852aaf48cd551ccf7dca4f1d5f
VARIANT_FEATURE_FILE=EXISTS|app/Console/Commands/ProductVariantsDoctorCommand.php|SHA256=4a6c2de92d2a4dca5e08f569d05533d9dcfc5204385a5303cb263e19b46425ea
VARIANT_FEATURE_FILE=EXISTS|resources/views/admin/products/variants.blade.php|SHA256=43dfd0d546dd59842541b91f0ff1f4123bed408293a9fd336f03afec085281a1

============================================================
3. SOURCE REFERENCE MAP
============================================================
SOURCE_SCAN_ROOT=/home/icaffeco/ald1n-project/apps/cms/current/app
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:97:                        'product_variant_id' => $item->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:98:                        'sku_snapshot' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:99:                        'product_name_snapshot' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:46:                if ($products->contains(static fn (Product $product): bool => (bool) $product->variants_enabled)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:116:                if ($products->contains(static fn (Product $product): bool => (bool) $product->variants_enabled)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:355:            'sku' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:356:            'name' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:16:final class ProductVariantService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:21:    public function create(Product $product, array $data, User $actor): ProductVariant
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:23:        return DB::transaction(function () use ($product, $data, $actor): ProductVariant {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:32:            $variant = ProductVariant::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:54:                    'product_variant_id' => $variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:65:            $mustDefault = (bool) ($data['is_default'] ?? false) || !ProductVariant::query()->where('product_id', $locked->id)->where('id', '!=', $variant->id)->whereNull('deleted_at')->exists();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:74:    public function update(Product $product, ProductVariant $variant, array $data, User $actor): ProductVariant
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:77:        return DB::transaction(function () use ($product, $variant, $data, $actor): ProductVariant {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:79:            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
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
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:231:                DB::table('product_variant_spec_values')->insert($row);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:258:    private function assertOwner(Product $product, ProductVariant $variant): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:264:    private function snapshot(ProductVariant $variant): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:12:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:53:            'product_variant_id' => isset($input['product_variant_id']) && $input['product_variant_id'] !== ''
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:54:                ? (int) $input['product_variant_id']
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:93:    /** @param array{product_id:int,buyer_name:string,buyer_phone:?string,product_variant_id:?int,quantity:int,sale_price_rsd:float,payment_method:string} $payload */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:125:        if ((bool) $lockedProduct->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:126:            if ($payload['product_variant_id'] === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:128:                    'product_variant_id' => 'Izaberi varijantu artikla.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:132:            $variant = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:134:                ->whereKey($payload['product_variant_id'])
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:141:            if (!$variant instanceof ProductVariant) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:143:                    'product_variant_id' => 'Izabrana varijanta nije dostupna.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:146:        } elseif ($payload['product_variant_id'] !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:148:                'product_variant_id' => 'Ovaj artikal nema aktivne varijante.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:155:            $variant instanceof ProductVariant ? $variant : $lockedProduct,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:159:        $quantityBefore = $variant instanceof ProductVariant
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:214:        if ($variant instanceof ProductVariant && (float) ($variant->purchase_price_rsd ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:225:            'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:233:            'variant_sku_snapshot' => $variant?->sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:234:            'variant_name_snapshot' => $variant?->name,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:235:            'variant_attributes_json' => $variant ? $this->variantAttributes($variant) : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:249:        if ($variant instanceof ProductVariant) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:255:            $parentStock = (int) ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:275:            'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:339:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:364:    private function catalogUnitPriceRsd(Product|ProductVariant $sellable, ?float $rate): float
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:379:    private function variantAttributes(ProductVariant $variant): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:59:                    'product_variant_id' => $item->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:74:                    'product_sku_snapshot' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:75:                    'product_name_snapshot' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:447:                $variantName = $this->text($item, 'variant_name_snapshot');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:448:                $variantSku = $this->text($item, 'variant_sku_snapshot');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:449:                $attributes = $item->getAttribute('variant_attributes_json');
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
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:156:            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:202:            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:351:        DB::table('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:358:                    if ($this->normalizeOne('product_variant_spec_values', 'product_variant_id', (int) $entity->id, $sourceId, $totalId)) $changed++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:451:        DB::table('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:459:                        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:460:                        'product_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:9:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:27:        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:28:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:53:        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:54:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:70:        $variants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:100:            ->get(['id', 'product_variant_id', 'storage_disk', 'file_path']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:171:                if ($variantIds !== [] && Schema::hasColumn('order_items', 'product_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:172:                    $nested->orWhereIn('product_variant_id', $variantIds);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:212:                'product_variant_id' => $image->product_variant_id === null
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:214:                    : (int) $image->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:314:                    in_array($column, ['product_id', 'product_variant_id', 'source_product_id', 'default_variant_id'], true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:469:                    $nested->where('k.REFERENCED_TABLE_NAME', 'product_variants')
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
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/InventoryService.php:21:        if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:12:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:586:        foreach ($order->items->sortBy(fn ($item) => sprintf('%010d:%010d', (int) $item->product_id, (int) ($item->product_variant_id ?? 0))) as $item) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:592:            if ((int) ($item->product_variant_id ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:593:                $variant = ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->findOrFail((int) $item->product_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:606:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:615:                'metadata_json' => ['order_item_id' => $item->id, 'variant_sku' => $item->variant_sku_snapshot, 'one_time_return' => true],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:619:            $aggregate = (int) ProductVariant::query()->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:24:        'products' => ['id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd', 'variants_enabled'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:230:        $products = DB::table('products')->whereNull('deleted_at')->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:233:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:234:            $variants = DB::table('product_variants as v')->join('products as p', 'p.id', '=', 'v.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:367:                    ->orWhere($alias.'.variant_name_snapshot', 'like', $search)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:368:                    ->orWhere($alias.'.variant_sku_snapshot', 'like', $search);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:394:            ? DB::table('stock_movements')->where($kind === 'variant' ? 'product_variant_id' : 'product_id', $id)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:395:                ->when($kind === 'product', static fn ($q) => $q->whereNull('product_variant_id'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:399:            ? DB::table('stock_movements')->where($kind === 'variant' ? 'product_variant_id' : 'product_id', $id)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:400:                ->when($kind === 'product', static fn ($q) => $q->whereNull('product_variant_id'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:52:                'oi.product_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:55:                'oi.variant_sku_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:56:                'oi.variant_name_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:237:        $variantId = (int) ($item->product_variant_id ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:238:        if ($variantId > 0 && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:239:            $variantCost = $this->positive(DB::table('product_variants')->where('id', $variantId)->value('purchase_price_rsd'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:11:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:58:        $variantIds = array_values(array_unique(array_filter(array_map(static fn (array $item): int => (int) ($item['product_variant_id'] ?? 0), $items))));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:74:        $variants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:141:            $variantId = (int) ($itemData['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:142:            /** @var ProductVariant|null $variant */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:145:            if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:174:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:177:                'variant_sku_snapshot' => $variant?->sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:178:                'variant_name_snapshot' => $variant?->name,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:179:                'variant_attributes_json' => $variantAttributes,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:211:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:227:            $aggregate = (int) ProductVariant::query()->where('product_id', $variantProductId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:320:    private function priceRsd(Product|ProductVariant $product, ?float $rate): float
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:333:    /** @param array<int,array<string,mixed>> $items @return array<int,array{product_id:int,product_variant_id:?int,quantity:int}> */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:339:            $variantId = (int) ($item['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:343:            if (!isset($normalized[$key])) $normalized[$key] = ['product_id' => $productId, 'product_variant_id' => $variantId > 0 ? $variantId : null, 'quantity' => 0];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:351:    private function variantAttributes(ProductVariant $variant): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:10:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:30:        private readonly ProductVariantService $variants,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:106:            if ((bool) $locked->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:204:            if (!empty($options['copy_variants']) && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:344:                return Schema::hasTable('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:345:                    && ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:441:                    || ProductVariant::query()->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])->exists(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:449:            $variant = ProductVariant::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:467:                DB::table('product_variant_spec_values')->insert([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:468:                    'product_variant_id' => $variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:491:            'completeness_percent','name_is_manual','source_product_id','variants_enabled','default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:30:        $variantCount = Schema::hasTable('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:31:            ? (int) DB::table('product_variant_spec_values')->where('field_id', $fieldId)->count()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:56:            if (Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:57:                DB::table('product_variant_spec_values')->where('field_id', $fieldId)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:118:            'orphan_variant_values' => $this->countOrphans('product_variant_spec_values', 'field_id', 'specification_fields', 'id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:119:                + $this->countOrphans('product_variant_spec_values', 'product_variant_id', 'product_variants', 'id'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:137:            $this->deleteOrphans('product_variant_spec_values', 'field_id', 'specification_fields', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:138:            $this->deleteOrphans('product_variant_spec_values', 'product_variant_id', 'product_variants', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:323:        if (!Schema::hasTable('product_variant_spec_values') || !Schema::hasTable('product_variants') || !Schema::hasTable('products') || !Schema::hasTable('product_type_fields')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:327:        return (int) DB::table('product_variant_spec_values as values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:328:            ->join('product_variants as variants', 'variants.id', '=', 'values.product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:345:        DB::table('product_variant_spec_values')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:348:                    ->from('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:352:                            ->on('product_type_fields.field_id', '=', 'product_variant_spec_values.field_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:354:                    ->whereColumn('variants.id', 'product_variant_spec_values.product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:13:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:30:        private readonly ProductVariantService $variants,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:106:                    'product_variant_id' => $caseItem->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:291:        $variantIds = $effectItems->pluck('product_variant_id')->filter()->map(static fn (mixed $id): int => (int) $id)->unique()->sort()->values();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:292:        $variants = ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:299:            if ((bool) $product->variants_enabled && $item->product_variant_id === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:302:            if ($item->product_variant_id !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:303:                $variant = $variants->get((int) $item->product_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:310:        $required = $effectItems->where('stock_effect', 'decrease')->groupBy(static fn ($item): string => $item->product_variant_id ? 'v:'.$item->product_variant_id : 'p:'.$item->product_id)->map(static fn ($rows): int => (int) $rows->sum('quantity'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:313:                /** @var ProductVariant $variant */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:328:        foreach ($effectItems->sortBy(static fn ($item): string => str_pad((string) $item->product_id, 20, '0', STR_PAD_LEFT).'-'.str_pad((string) ($item->product_variant_id ?? 0), 20, '0', STR_PAD_LEFT).'-'.str_pad((string) $item->id, 20, '0', STR_PAD_LEFT)) as $item) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:338:            /** @var ProductVariant|null $variant */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:339:            $variant = $item->product_variant_id !== null ? $variants->get((int) $item->product_variant_id) : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:352:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:364:                    'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:123:                if (Schema::hasColumn('products', 'default_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:124:                    $locked->forceFill(['default_variant_id' => null])->saveQuietly();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:14:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:202:                ->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:241:            if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:242:                $lowVariants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:260:                        'action_url' => route('admin.products.variants.index', $variant->product_id).'#variant-'.$variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:261:                        'metadata_json' => ['product_variant_id' => $variant->id, 'stock' => $variant->stock_quantity, 'threshold' => $variant->low_stock_threshold],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:61:        $this->loadMany($order, 'items', 'order_items', ['id', 'order_id'], $warnings, ['product' => ['products', ['id']], 'variant' => ['product_variants', ['id']]]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:9:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:27:    public function uploadVariant(Product $product, ProductVariant $variant, array $files): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:34:    private function uploadInternal(Product $product, ?ProductVariant $variant, array $files): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:41:            $directory = 'products/'.$product->id.($variant ? '/variants/'.$variant->id : '');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:45:            $variant ? $scope->where('product_variant_id', $variant->id) : $scope->whereNull('product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:49:                'product_variant_id' => $variant?->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:106:    public function cloneVariantImages(ProductVariant $source, ProductVariant $target): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:113:            $newPath = 'products/'.$target->product_id.'/variants/'.$target->id.'/'.Str::uuid().'.'.$extension;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:114:            Storage::disk('public')->makeDirectory('products/'.$target->product_id.'/variants/'.$target->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:118:                'product_variant_id' => $target->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:138:        abort_if($image->product_variant_id !== null, 422, 'Glavna slika artikla ne može biti slika varijante.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:143:                ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:151:            ProductImage::query()->where('product_id', $product->id)->whereNull('product_variant_id')->update(['is_primary' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:235:            ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:258:            ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:275:            ->whereNull('product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:292:        $variantId = $image->product_variant_id !== null ? (int) $image->product_variant_id : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:298:            $variantId !== null ? $nextQuery->where('product_variant_id', $variantId) : $nextQuery->whereNull('product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:16:        'order_id', 'product_id', 'product_variant_id', 'product_sku', 'product_name', 'quantity',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:18:        'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:37:            'variant_attributes_json' => 'array',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderItem.php:43:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:16:        'product_id', 'product_variant_id', 'file_path', 'storage_disk', 'original_filename', 'mime_type', 'file_size',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:21:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/StockMovement.php:15:        'event_key', 'product_id', 'product_variant_id', 'order_id', 'user_id', 'movement_type', 'source', 'quantity_change',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/StockMovement.php:37:        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesActionItem.php:13:        'after_sales_action_id', 'after_sales_case_item_id', 'product_id', 'product_variant_id', 'sku_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesActionItem.php:25:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:10:final class ProductVariantSpecValue extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:12:    protected $table = 'product_variant_spec_values';
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:15:    protected $fillable = ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_json', 'value_number', 'value_boolean'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariantSpecValue.php:18:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariant.php:12:final class ProductVariant extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductVariant.php:37:    public function specificationValues(): HasMany { return $this->hasMany(ProductVariantSpecValue::class); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesCaseItem.php:12:    protected $fillable = ['after_sales_case_id', 'order_item_id', 'product_id', 'product_variant_id', 'sku_snapshot', 'product_name_snapshot', 'quantity', 'issue_description'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesCaseItem.php:16:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:20:        'legacy_synced_at', 'locally_modified_at', 'completeness_percent', 'name_is_manual', 'source_product_id', 'variants_enabled', 'default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:36:            'variants_enabled' => 'boolean',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:52:    public function images(): HasMany { return $this->hasMany(ProductImage::class)->whereNull('product_variant_id')->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:54:    public function primaryImage(): HasOne { return $this->hasOne(ProductImage::class)->whereNull('product_variant_id')->where('is_primary', true)->orderBy('sort_order'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:55:    public function variants(): HasMany { return $this->hasMany(ProductVariant::class)->whereNull('deleted_at')->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:56:    public function activeVariants(): HasMany { return $this->hasMany(ProductVariant::class)->whereNull('deleted_at')->where('status', 'active')->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:57:    public function defaultVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'default_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductWarranty.php:14:        'warranty_number', 'order_id', 'order_item_id', 'product_id', 'product_variant_id', 'user_id', 'warranty_rule_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductWarranty.php:41:    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:82:            if ($product && (bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:34:            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:45:                $variantId = (int) ($item['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:52:                if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:54:                        $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izaberi konfiguraciju proizvoda.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:57:                    $valid = ProductVariant::query()->whereKey($variantId)->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->exists();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:58:                    if (!$valid) $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izabrana konfiguracija nije dostupna za ovaj proizvod.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:60:                    $validator->errors()->add('items.'.$index.'.product_variant_id', 'Ovaj proizvod nema aktivne varijante.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:71:            $variantId = (int) ($item['product_variant_id'] ?? 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreOrderRequest.php:73:            if ($productId > 0 && $quantity > 0) $items[] = ['product_id' => $productId, 'product_variant_id' => $variantId > 0 ? $variantId : null, 'quantity' => $quantity];
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:16:final class ProductVariantRequest extends FormRequest
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:31:        $variantId = $variant instanceof ProductVariant ? $variant->id : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductVariantRequest.php:33:            'sku' => ['required', 'string', 'max:100', 'regex:#^[A-Z0-9._/-]+$#', Rule::unique('product_variants', 'sku')->ignore($variantId), Rule::unique('products', 'sku')],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/ProductResource.php:36:            'variants_enabled' => (bool) $this->variants_enabled,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:45:                'product_variant_id' => $item->product_variant_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:46:                'sku' => $item->variant_sku_snapshot ?: $item->product_sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:47:                'name' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:48:                'variant_name' => $item->variant_name_snapshot,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:49:                'variant_attributes' => $item->variant_attributes_json ?? [],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DirectSaleController.php:24:            'product_variant_id' => ['nullable', 'integer', 'min:1'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:41:                ->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:55:                'lowStock' => Product::query()->whereNull('deleted_at')->where('variants_enabled', false)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->limit(50)->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:127:            $rows = Product::query()->whereNull('deleted_at')->where('variants_enabled', false)->orderBy('sku')->get(['sku', 'name', 'stock_quantity', 'low_stock_threshold', 'status']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:495:            'variants' => ['product_variant_spec_values', 'field_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:8:use App\Http\Requests\ProductVariantRequest;
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
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:98:    public function deleteImage(Product $product, ProductVariant $variant, ProductImage $image, ProductImageService $images): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:101:        abort_unless((int) $variant->product_id === (int) $product->id && (int) $image->product_variant_id === (int) $variant->id, 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php:228:                'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php:322:                'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php:73:                        'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php:74:                        'sku' => (string) ($item->variant_sku_snapshot ?: $item->product_sku ?: ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php:75:                        'name' => (string) $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php:203:                'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:61:            'variants_enabled',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:62:            'default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:35:        'stock_movements' => ['stock_receipt_id', 'inventory_count_id', 'product_variant_id', 'event_key', 'metadata_json'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:103:            $lowStock = DB::table('products')->whereNull('deleted_at')->where('variants_enabled', false)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:104:            $lowVariantStock = Schema::hasTable('product_variants') ? DB::table('product_variants')->whereNull('deleted_at')->where('status', 'active')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count() : 0;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:33:            'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_json'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:72:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:73:        'product_variant_spec_values',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:91:        'order_items' => ['id', 'order_id', 'product_id', 'product_variant_id', 'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json', 'quantity', 'commission_total_eur_snapshot', 'purchase_unit_rsd_snapshot', 'purchase_total_rsd_snapshot', 'cost_source_snapshot', 'brand_name_snapshot', 'product_line_name_snapshot', 'product_type_name_snapshot'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:98:        'stock_movements' => ['id', 'product_id', 'product_variant_id', 'quantity_change', 'event_key', 'source', 'metadata_json', 'stock_receipt_id', 'inventory_count_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:106:        'after_sales_case_items' => ['id', 'after_sales_case_id', 'order_item_id', 'product_id', 'product_variant_id', 'product_name_snapshot', 'quantity'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:111:        'after_sales_action_items' => ['id', 'after_sales_action_id', 'after_sales_case_item_id', 'product_id', 'product_variant_id', 'quantity', 'disposition', 'stock_effect'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:122:        'product_warranties' => ['id', 'warranty_number', 'order_id', 'order_item_id', 'product_variant_id', 'user_id', 'status', 'starts_at', 'expires_at', 'duration_months', 'duration_days', 'serial_numbers_json', 'next_maintenance_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:131:        'products' => ['id', 'product_type_id', 'brand_id', 'product_line_id', 'model_name', 'purchase_price_rsd', 'completeness_percent', 'name_is_manual', 'source_product_id', 'variants_enabled', 'default_variant_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:136:        'product_variants' => ['id', 'product_id', 'sku', 'name', 'price_amount', 'price_currency', 'stock_quantity', 'low_stock_threshold', 'status', 'is_default', 'warranty_rule_id', 'deleted_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:137:        'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_number', 'value_boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:116:                    (string) ($row->variant_sku_snapshot ?: $row->product_sku ?: '—'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:91:                    (string) ($row->variant_sku_snapshot ?: $row->product_sku ?: '—'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCreateDoctorCommand.php:30:        foreach (['users', 'roles', 'products', 'product_variants', 'bank_accounts'] as $table) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PerformanceDoctorCommand.php:33:            'product_variants' => ['product_variants_runtime_v216_idx'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:8:use App\Services\ProductVariantService;
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
SOURCE_SCAN_ROOT=/home/icaffeco/ald1n-project/apps/cms/current/routes
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:41:use App\Http\Controllers\Admin\ProductVariantController;
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:217:            Route::get('/catalog/{product}/variants', [ProductVariantController::class, 'index'])->name('products.variants.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:218:            Route::post('/catalog/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:219:            Route::put('/catalog/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->whereNumber('variant')->name('products.variants.update');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:220:            Route::post('/catalog/{product}/variants/{variant}/stock', [ProductVariantController::class, 'adjust'])->whereNumber('variant')->name('products.variants.stock');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:221:            Route::post('/catalog/{product}/variants/{variant}/default', [ProductVariantController::class, 'setDefault'])->whereNumber('variant')->name('products.variants.default');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:222:            Route::delete('/catalog/{product}/variants/{variant}', [ProductVariantController::class, 'archive'])->whereNumber('variant')->name('products.variants.archive');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:223:            Route::post('/catalog/{product}/variants/{variant}/images', [ProductVariantController::class, 'images'])->whereNumber('variant')->middleware('throttle:uploads')->name('products.variants.images');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:224:            Route::delete('/catalog/{product}/variants/{variant}/images/{image}', [ProductVariantController::class, 'deleteImage'])->whereNumber('variant')->whereNumber('image')->name('products.variants.images.delete');
SOURCE_SCAN_ROOT=/home/icaffeco/ald1n-project/apps/cms/current/resources/views
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:64:                                                    @if($product->variants_enabled) · {{ $product->activeVariants->count() }} varijanti @endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:70:                                        <select name="items[{{ $index }}][product_variant_id]" data-order-variant>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:176:        const preselected = Number(initialItems?.[index]?.product_variant_id || (index === 0 ? selectedVariantId : 0));
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/index.blade.php:17:@forelse($products as $product)<tr><td data-label="Izbor"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" data-product-select></td><td data-label="Artikal"><div class="table-product"><div class="table-thumb">@if($product->primaryImage)<img src="{{ $product->primaryImage->url }}" alt="">@else ◫ @endif</div><div><strong>{{ $product->name }}</strong><small>SKU {{ $product->sku }} · {{ $product->images_count }} slika @if($product->variants_count)· {{ $product->active_variants_count }}/{{ $product->variants_count }} aktivnih varijanti @endif</small></div></div></td><td data-label="Brend / linija">{{ $product->brand?->name ?? '—' }}@if($product->line)<small class="muted"> · {{ $product->line->name }}</small>@endif</td><td data-label="Status"><span class="status-badge status-{{ $product->deleted_at ? 'archived' : $product->status }}">{{ $product->deleted_at ? 'Arhiviran' : $product->status }}</span></td><td data-label="Kompletnost"><div class="mini-completeness"><span><i style="width:{{ (int)$product->completeness_percent }}%"></i></span><b>{{ (int)$product->completeness_percent }}%</b></div></td><td data-label="Cena">{{ number_format((float)$product->price_amount,2,',','.') }} {{ $product->price_currency }}</td><td data-label="Lager">{{ $product->stock_quantity }}</td><td data-label="Izmena">{{ optional($product->updated_at)->format('d.m.Y. H:i') }}</td><td class="row-actions"><a class="button button-small button-ghost" href="{{ route('admin.products.edit',$product) }}">Izmeni</a><a class="button button-small button-ghost" href="{{ route('admin.products.clone',$product) }}">Kloniraj</a><a class="button button-small button-ghost" href="{{ route('admin.products.variants.index',$product) }}">Varijante</a>@can('catalog.manage_images')<a class="button button-small button-ghost" href="{{ route('admin.products.images.index',$product) }}">Slike</a>@endcan</td></tr>@empty<tr><td colspan="9"><div class="empty-state">Nema artikala za izabrane filtere.</div></td></tr>@endforelse
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:14:                <a class="button button-ghost" href="{{ route('admin.products.variants.index',$product) }}">Varijante @if($product->variants_enabled)({{ $product->variants()->count() }})@endif</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:347:                    @if($product->exists && $product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:9:<form method="post" data-variant-form action="{{ route('admin.products.variants.store',$product) }}">@csrf
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:17:<form method="post" data-variant-form action="{{ route('admin.products.variants.update',[$product,$variant]) }}">@csrf @method('PUT')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:24:<form class="panel nested-panel" method="post" action="{{ route('admin.products.variants.stock',[$product,$variant]) }}">@csrf
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:27:<form class="panel nested-panel" method="post" enctype="multipart/form-data" action="{{ route('admin.products.variants.images',[$product,$variant]) }}">@csrf
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:33:<form id="default-{{ $variant->id }}" method="post" action="{{ route('admin.products.variants.default',[$product,$variant]) }}">@csrf</form>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:34:<form id="archive-{{ $variant->id }}" method="post" action="{{ route('admin.products.variants.archive',[$product,$variant]) }}">@csrf @method('DELETE')</form>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:35:@foreach($variant->images as $image)<form id="delete-variant-image-{{ $image->id }}" method="post" action="{{ route('admin.products.variants.images.delete',[$product,$variant,$image]) }}">@csrf @method('DELETE')</form>@endforeach
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/stock/index.blade.php:6:<section class="panel form-section"><h2>Trenutno stanje</h2><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal</th><th>Lager</th><th>Prag</th>@can('stock.adjust')<th>Korekcija</th>@endcan</tr></thead><tbody>@forelse($products as $product)<tr><td><strong>{{ $product->sku }}</strong></td><td>{{ $product->name }}</td><td><strong class="{{ $product->stock_quantity <= $product->low_stock_threshold ? 'text-danger' : 'text-success' }}">{{ $product->stock_quantity }}</strong></td><td>{{ $product->low_stock_threshold }}</td>@can('stock.adjust')<td>@if($product->variants_enabled)<a class="button button-ghost button-small" href="{{ route('admin.products.variants.index',$product) }}">Varijante ({{ $product->variants_count }})</a>@else<form class="inline-form" method="post" action="{{ route('admin.stock.adjust',$product) }}">@csrf<input type="hidden" name="idempotency_key" value="{{ $idempotencyKeys[$product->id] }}"><input type="number" name="quantity_change" required placeholder="+/-" class="ux-stock-max-width-90"><input name="note" required maxlength="1000" placeholder="Obavezan razlog"><button class="button button-ghost button-small" type="submit">Primeni</button></form>@endif</td>@endcan</tr>@empty<tr><td colspan="5">Nema artikala.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:308:                @if($product->variants_enabled)<span class="success">{{ $product->activeVariants->count() }} varijanti</span>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:326:                        @if($product->variants_enabled && $variantPrices->isNotEmpty() && $variantPrices->min() !== $variantPrices->max())
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:340:                    <a class="button button-small button-ghost" href="{{ route('admin.products.variants.index', $product) }}">Varijante</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:134:                <a class="button button-ghost button-small" href="{{ route('admin.products.variants.index', $product) }}">Varijante</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:151:        @if($product->variants_enabled && $product->activeVariants->isNotEmpty())
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:253:            @if(!$product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:312:            @if($product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:315:                    <select name="product_variant_id" required>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:318:                            <option value="{{ $variant->id }}" @selected((string) old('product_variant_id') === (string) $variant->id)>
SOURCE_SCAN_ROOT=/home/icaffeco/ald1n-project/apps/cms/current/config
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:36:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:70:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:110:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:150:            'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/config/release.php:307:        'product_variants' => [
SOURCE_SCAN_ROOT=/home/icaffeco/ald1n-project/apps/cms/current/bin
/home/icaffeco/ald1n-project/apps/cms/current/bin/storage-capacity-total-smoke.php:39:$variantRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/storage-capacity-total-smoke.php:41:$variantService = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-media-ux-smoke.php:33:$check('Reorder čuva sve slike i odbacuje tuđe ID-jeve', str_contains($service, '$existingLookup') && str_contains($service, 'Neposlati ID-jevi se dodaju na kraj') && str_contains($service, "whereNull('product_variant_id')"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/catalog-settings-integrity-hotfix-smoke.php:25:$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/catalog-settings-integrity-hotfix-smoke.php:26:$variantService = $source('app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/catalog-settings-integrity-hotfix-smoke.php:35:$check('Trajno brisanje polja eksplicitno uklanja sve povezane reference', str_contains($fieldLifecycle, "product_variant_spec_values')->where('field_id'") && str_contains($fieldLifecycle, "product_spec_values')->where('field_id'") && str_contains($fieldLifecycle, "product_type_fields')->where('field_id'") && str_contains($fieldLifecycle, "specification_options')->where('field_id'"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:742:$productVariantsMigration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:743:$productVariantService = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:744:$productVariantRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:745:$productVariantController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductVariantController.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:746:$productVariantsView = (string) file_get_contents($root.'/resources/views/admin/products/variants.blade.php');
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
/home/icaffeco/ald1n-project/apps/cms/current/bin/stable-maintenance-smoke.php:32:$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:13:$migration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:14:$service = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:22:$variantView = (string) file_get_contents($root.'/resources/views/admin/products/variants.blade.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:24:$doctor = (string) file_get_contents($root.'/app/Console/Commands/ProductVariantsDoctorCommand.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:26:$check('Migracija uvodi varijante, specifikacije i istorijske snapshot kolone', str_contains($migration, 'product_variants') && str_contains($migration, 'product_variant_spec_values') && str_contains($migration, 'variant_attributes_json'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:30:$check('Porudžbina zaključava varijantu i čuva snapshot', str_contains($order, 'lockForUpdate') && str_contains($order, 'variant_sku_snapshot') && str_contains($order, 'variant_attributes_json'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:31:$check('Otkazivanje vraća lager na istu varijantu', str_contains($workflow, 'product_variant_id') && str_contains($workflow, 'cancel-return'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:32:$check('Postprodajna zamena i povrat koriste konkretnu varijantu', str_contains($afterSales, 'ProductVariant::query()') && str_contains($afterSales, "'product_variant_id' => \$variant?->id"));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-variant-smoke.php:37:$check('Forma porudžbine zahteva izbor konfiguracije', str_contains($orderView, 'product_variant_id') && str_contains($orderView, 'variantMap'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/cms-v2.1.6-smoke.php:40:$check('Migracija kreira indekse galerija i varijanti', str_contains($migration, 'product_images_primary_sort_v216_idx') && str_contains($migration, 'product_variants_runtime_v216_idx'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-save-regex-hotfix-smoke.php:22:$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/product-save-regex-hotfix-smoke.php:27:$check('ProductVariantRequest koristi bezbedan SKU regex delimiter', str_contains($variantRequest, $safeRule));
SOURCE_SCAN_ROOT=/home/icaffeco/ald1n-project/apps/cms/current/tests
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsUiContractTest.php:9:final class ProductVariantsUiContractTest extends TestCase
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsUiContractTest.php:13:        $variants = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/products/variants.blade.php');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsUiContractTest.php:18:        self::assertStringContainsString('product_variant_id', $order);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php:34:        self::assertStringContainsString("product_variant_spec_values')->where('field_id'", $service);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:8:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:25:        $variantRsd = new ProductVariant();
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:33:        $variantEur = new ProductVariant();
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:82:        self::assertStringContainsString('$variant instanceof ProductVariant ? $variant : $lockedProduct', $direct);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/DirectSaleMaxUnitPriceContractTest.php:90:        self::assertStringContainsString('private function priceRsd(Product|ProductVariant $product, ?float $rate): float', $orders);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductSaveRegexHotfixContractTest.php:17:        $variant = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:9:final class ProductVariantsMigrationContractTest extends TestCase
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:13:        $source = (string) file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:14:        self::assertStringContainsString('product_variants', $source);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/ProductVariantsMigrationContractTest.php:15:        self::assertStringContainsString('product_variant_spec_values', $source);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:16:final class ProductVariantsWorkflowTest extends TestCase
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:48:        $this->actingAs($admin)->post('/admin/catalog/'.$product->id.'/variants', [
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:60:        ])->assertRedirect('/admin/catalog/'.$product->id.'/variants');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:63:        self::assertTrue($product->variants_enabled);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ProductVariantsWorkflowTest.php:65:        self::assertNotNull($product->default_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogDetailPageTest.php:9:use App\Models\ProductVariant;
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogDetailPageTest.php:143:        $product->update(['variants_enabled' => true]);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogDetailPageTest.php:145:        ProductVariant::query()->create([
SOURCE_SCAN_ROOT=/home/icaffeco/ald1n-project/packages/api-contract
/home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml:456:                      product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml:3774:                  product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml:3812:                  product_variant_id: { type: [integer, 'null'] }
SOURCE_SCAN_ROOT=/home/icaffeco/ald1n-project/apps/mobile/current/src
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/after-sales-admin-api.ts:65:  product_variant_id: number | null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/after-sales-admin-api.ts:76:  product_variant_id: number | null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:140:export type ProductVariant = {
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:167:  variants_enabled: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:168:  variants?: ProductVariant[];
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:201:  product_variant_id: Nullable<number>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:423:    product_variant_id: Nullable<number>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:437:  product_variant_id: Nullable<number>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:692:  product_variant_id: Nullable<number>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:140:        product_variant_id: item.variantId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:36:    if (!product?.variants_enabled) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:54:  const selectedVariant = product.variants_enabled
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:63:  const requiresVariant = product.variants_enabled;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:142:      {product.variants_enabled && product.variants?.length ? <Card><Text style={styles.sectionTitle}>Izaberi konfiguraciju</Text>{product.variants.map((variant) => {
SOURCE_SCAN_ROOT=/home/icaffeco/ald1n-project/apps/mobile/current/docs
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:456:                      product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3774:                  product_variant_id: { type: [integer, 'null'] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3812:                  product_variant_id: { type: [integer, 'null'] }
SOURCE_FILES_WITH_VARIANT_SIGNALS=82

============================================================
4. DATABASE VARIANT STATE + HISTORICAL REFERENCE PROBE
============================================================
No syntax errors detected in /tmp/ald1n-product-variants-decommission-audit.8qXsXo/db-probe.php
TABLE_product_variants=PRESENT
COUNT_product_variants=0
COLUMNS_product_variants=id,product_id,sku,name,price_amount,price_currency,purchase_price_rsd,manual_commission_eur,stock_quantity,low_stock_threshold,status,is_default,warranty_rule_id,sort_order,created_by,updated_by,created_at,updated_at,deleted_at
TABLE_product_variant_spec_values=PRESENT
COUNT_product_variant_spec_values=0
COLUMNS_product_variant_spec_values=product_variant_id,field_id,value_text,value_detail,value_json,value_number,value_boolean,created_at,updated_at
REFERENCE_products.default_variant_id_NONZERO=0
REFERENCE_products.variants_enabled_NONZERO=0
REFERENCE_product_images.product_variant_id_NONZERO=0
REFERENCE_order_items.product_variant_id_NONZERO=0
REFERENCE_stock_movements.product_variant_id_NONZERO=0
REFERENCE_after_sales_case_items.product_variant_id_NONZERO=0
REFERENCE_after_sales_action_items.product_variant_id_NONZERO=0
REFERENCE_product_warranties.product_variant_id_NONZERO=0
VARIANT_REFERENCE_SURFACES_WITH_NONZERO_ROWS=0
VARIANT_IMAGE_ROWS=0
VARIANT_FOREIGN_KEY_COUNT=17
VARIANT_FK={"TABLE_NAME":"after_sales_action_items","COLUMN_NAME":"product_variant_id","CONSTRAINT_NAME":"after_sales_action_items_variant_fk","REFERENCED_TABLE_NAME":"product_variants","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"after_sales_case_items","COLUMN_NAME":"product_variant_id","CONSTRAINT_NAME":"after_sales_case_items_variant_fk","REFERENCED_TABLE_NAME":"product_variants","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"order_items","COLUMN_NAME":"product_variant_id","CONSTRAINT_NAME":"order_items_variant_fk","REFERENCED_TABLE_NAME":"product_variants","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"products","COLUMN_NAME":"default_variant_id","CONSTRAINT_NAME":"products_default_variant_fk","REFERENCED_TABLE_NAME":"product_variants","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"product_images","COLUMN_NAME":"product_variant_id","CONSTRAINT_NAME":"product_images_variant_fk","REFERENCED_TABLE_NAME":"product_variants","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"product_variants","COLUMN_NAME":"created_by","CONSTRAINT_NAME":"product_variants_created_by_fk","REFERENCED_TABLE_NAME":"users","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"product_variants","COLUMN_NAME":"id","CONSTRAINT_NAME":"PRIMARY","REFERENCED_TABLE_NAME":null,"REFERENCED_COLUMN_NAME":null}
VARIANT_FK={"TABLE_NAME":"product_variants","COLUMN_NAME":"product_id","CONSTRAINT_NAME":"product_variants_product_fk","REFERENCED_TABLE_NAME":"products","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"product_variants","COLUMN_NAME":"sku","CONSTRAINT_NAME":"product_variants_sku_unique","REFERENCED_TABLE_NAME":null,"REFERENCED_COLUMN_NAME":null}
VARIANT_FK={"TABLE_NAME":"product_variants","COLUMN_NAME":"updated_by","CONSTRAINT_NAME":"product_variants_updated_by_fk","REFERENCED_TABLE_NAME":"users","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"product_variants","COLUMN_NAME":"warranty_rule_id","CONSTRAINT_NAME":"product_variants_warranty_rule_fk","REFERENCED_TABLE_NAME":"warranty_rules","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"product_variant_spec_values","COLUMN_NAME":"field_id","CONSTRAINT_NAME":"variant_spec_field_fk","REFERENCED_TABLE_NAME":"specification_fields","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"product_variant_spec_values","COLUMN_NAME":"field_id","CONSTRAINT_NAME":"PRIMARY","REFERENCED_TABLE_NAME":null,"REFERENCED_COLUMN_NAME":null}
VARIANT_FK={"TABLE_NAME":"product_variant_spec_values","COLUMN_NAME":"product_variant_id","CONSTRAINT_NAME":"PRIMARY","REFERENCED_TABLE_NAME":null,"REFERENCED_COLUMN_NAME":null}
VARIANT_FK={"TABLE_NAME":"product_variant_spec_values","COLUMN_NAME":"product_variant_id","CONSTRAINT_NAME":"variant_spec_variant_fk","REFERENCED_TABLE_NAME":"product_variants","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"product_warranties","COLUMN_NAME":"product_variant_id","CONSTRAINT_NAME":"product_warranties_variant_fk","REFERENCED_TABLE_NAME":"product_variants","REFERENCED_COLUMN_NAME":"id"}
VARIANT_FK={"TABLE_NAME":"stock_movements","COLUMN_NAME":"product_variant_id","CONSTRAINT_NAME":"stock_movements_variant_fk","REFERENCED_TABLE_NAME":"product_variants","REFERENCED_COLUMN_NAME":"id"}
DATABASE_WRITES_DURING_VARIANT_DECOMMISSION_AUDIT=0
VARIANT_DATABASE_PROBE=PASS

============================================================
5. PRODUCT / CATALOG / ORDER BUSINESS COUPLING
============================================================
COUPLING_FILE=app/Models/Product.php
20:        'legacy_synced_at', 'locally_modified_at', 'completeness_percent', 'name_is_manual', 'source_product_id', 'variants_enabled', 'default_variant_id',
36:            'variants_enabled' => 'boolean',
52:    public function images(): HasMany { return $this->hasMany(ProductImage::class)->whereNull('product_variant_id')->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'); }
54:    public function primaryImage(): HasOne { return $this->hasOne(ProductImage::class)->whereNull('product_variant_id')->where('is_primary', true)->orderBy('sort_order'); }
55:    public function variants(): HasMany { return $this->hasMany(ProductVariant::class)->whereNull('deleted_at')->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id'); }
56:    public function activeVariants(): HasMany { return $this->hasMany(ProductVariant::class)->whereNull('deleted_at')->where('status', 'active')->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id'); }
57:    public function defaultVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'default_variant_id'); }
COUPLING_FILE=app/Services/ProductAdminService.php
10:use App\Models\ProductVariant;
30:        private readonly ProductVariantService $variants,
106:            if ((bool) $locked->variants_enabled) {
204:            if (!empty($options['copy_variants']) && Schema::hasTable('product_variants')) {
205:                $this->cloneVariants($source, $clone, $user, $warrantyRuleMap, !empty($options['copy_images']));
344:                return Schema::hasTable('product_variants')
345:                    && ProductVariant::query()
435:    private function cloneVariants(Product $source, Product $clone, User $user, array $warrantyRuleMap, bool $copyImages): void
441:                    || ProductVariant::query()->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])->exists(),
449:            $variant = ProductVariant::query()->create([
467:                DB::table('product_variant_spec_values')->insert([
468:                    'product_variant_id' => $variant->id,
491:            'completeness_percent','name_is_manual','source_product_id','variants_enabled','default_variant_id',
COUPLING_FILE=app/Services/ProductImageService.php
9:use App\Models\ProductVariant;
27:    public function uploadVariant(Product $product, ProductVariant $variant, array $files): int
34:    private function uploadInternal(Product $product, ?ProductVariant $variant, array $files): int
45:            $variant ? $scope->where('product_variant_id', $variant->id) : $scope->whereNull('product_variant_id');
49:                'product_variant_id' => $variant?->id,
106:    public function cloneVariantImages(ProductVariant $source, ProductVariant $target): int
118:                'product_variant_id' => $target->id,
138:        abort_if($image->product_variant_id !== null, 422, 'Glavna slika artikla ne može biti slika varijante.');
143:                ->whereNull('product_variant_id')
151:            ProductImage::query()->where('product_id', $product->id)->whereNull('product_variant_id')->update(['is_primary' => false]);
235:            ->whereNull('product_variant_id')
258:            ->whereNull('product_variant_id')
275:            ->whereNull('product_variant_id')
292:        $variantId = $image->product_variant_id !== null ? (int) $image->product_variant_id : null;
298:            $variantId !== null ? $nextQuery->where('product_variant_id', $variantId) : $nextQuery->whereNull('product_variant_id');
COUPLING_FILE=app/Services/CatalogQueryService.php
COUPLING_FILE=app/Services/DataQualityService.php
204:                    ->whereNull('product_images.product_variant_id');
285:            ->select(['product_id', 'product_variant_id'])
288:            ->groupBy('product_id', 'product_variant_id')
298:        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants')) {
303:            ->where('variants_enabled', true)
306:                $query->whereNull('default_variant_id')
308:                        $variant->selectRaw('1')->from('product_variants')
309:                            ->whereColumn('product_variants.id', 'products.default_variant_id')
310:                            ->whereColumn('product_variants.product_id', 'products.id')
311:                            ->whereNull('product_variants.deleted_at');
315:        $samples = (clone $invalidDefault)->select(['id', 'sku', 'name', 'default_variant_id'])->orderBy('id')->limit($limit)->get();
316:        $issues[] = $this->issue('invalid_default_variants', 'Proizvodi sa nevažećom podrazumevanom varijantom', 'warning', $invalidCount, 'Bezbedna popravka bira aktivnu ili prvu raspoloživu varijantu.', true, $this->rows($samples));
318:        $multipleDefaults = DB::table('product_variants')
327:        $issues[] = $this->issue('multiple_default_variants', 'Proizvodi sa više podrazumevanih varijanti', 'warning', $multipleCount, 'Samo jedna varijanta može biti podrazumevana.', true, $this->rows($multipleSamples));
355:        if (Schema::hasTable('product_variant_spec_values') && Schema::hasTable('product_variants')) {
356:            $variantQuery = DB::table('product_variant_spec_values')
357:                ->join('product_variants', 'product_variants.id', '=', 'product_variant_spec_values.product_variant_id')
358:                ->join('products', 'products.id', '=', 'product_variants.product_id')
359:                ->leftJoin('specification_fields', 'specification_fields.id', '=', 'product_variant_spec_values.field_id')
362:                        ->on('product_type_fields.field_id', '=', 'product_variant_spec_values.field_id');
371:                'product_variants.id', 'product_variants.sku', 'product_variants.name', 'product_variant_spec_values.field_id',
372:            ])->orderBy('product_variants.id')->limit($limit)->get();
411:        if (Schema::hasTable('product_variants')) {
412:            $metrics['variants_total'] = (int) DB::table('product_variants')->whereNull('deleted_at')->count();
431:            ->select(['product_id', 'product_variant_id'])
433:            ->groupBy('product_id', 'product_variant_id')
443:                        $group->product_variant_id === null
444:                            ? $query->whereNull('product_variant_id')
445:                            : $query->where('product_variant_id', (int) $group->product_variant_id);
462:        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants') || !Schema::hasColumn('products', 'default_variant_id')) {
468:            ->where('variants_enabled', true)
474:                        $variants = DB::table('product_variants')
483:                            if ($product->default_variant_id !== null) {
484:                                DB::table('products')->where('id', (int) $product->id)->update(['default_variant_id' => null]);
489:                        $chosen = $variants->firstWhere('id', (int) $product->default_variant_id) ?? $variants->first();
491:                        if ((int) $product->default_variant_id === (int) $chosen->id && $defaultCount === 1 && (bool) $chosen->is_default) {
494:                        DB::table('product_variants')->where('product_id', (int) $product->id)->update(['is_default' => false]);
495:                        DB::table('product_variants')->where('id', (int) $chosen->id)->update(['is_default' => true]);
496:                        DB::table('products')->where('id', (int) $product->id)->update(['default_variant_id' => (int) $chosen->id]);
COUPLING_FILE=app/Services/ManagementReportService.php
24:        'products' => ['id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd', 'variants_enabled'],
230:        $products = DB::table('products')->whereNull('deleted_at')->where('variants_enabled', false)
233:        if (Schema::hasTable('product_variants')) {
234:            $variants = DB::table('product_variants as v')->join('products as p', 'p.id', '=', 'v.product_id')
367:                    ->orWhere($alias.'.variant_name_snapshot', 'like', $search)
368:                    ->orWhere($alias.'.variant_sku_snapshot', 'like', $search);
394:            ? DB::table('stock_movements')->where($kind === 'variant' ? 'product_variant_id' : 'product_id', $id)
395:                ->when($kind === 'product', static fn ($q) => $q->whereNull('product_variant_id'))
399:            ? DB::table('stock_movements')->where($kind === 'variant' ? 'product_variant_id' : 'product_id', $id)
400:                ->when($kind === 'product', static fn ($q) => $q->whereNull('product_variant_id'))
COUPLING_FILE=app/Services/OrderService.php
11:use App\Models\ProductVariant;
58:        $variantIds = array_values(array_unique(array_filter(array_map(static fn (array $item): int => (int) ($item['product_variant_id'] ?? 0), $items))));
74:        $variants = ProductVariant::query()
141:            $variantId = (int) ($itemData['product_variant_id'] ?? 0);
142:            /** @var ProductVariant|null $variant */
145:            if ((bool) $product->variants_enabled) {
174:                'product_variant_id' => $variant?->id,
177:                'variant_sku_snapshot' => $variant?->sku,
178:                'variant_name_snapshot' => $variant?->name,
179:                'variant_attributes_json' => $variantAttributes,
211:                'product_variant_id' => $variant?->id,
220:                'metadata_json' => ['order_item_id' => $orderItem->id, 'source_system' => 'laravel', 'variant_sku' => $variant?->sku],
227:            $aggregate = (int) ProductVariant::query()->where('product_id', $variantProductId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
320:    private function priceRsd(Product|ProductVariant $product, ?float $rate): float
333:    /** @param array<int,array<string,mixed>> $items @return array<int,array{product_id:int,product_variant_id:?int,quantity:int}> */
339:            $variantId = (int) ($item['product_variant_id'] ?? 0);
343:            if (!isset($normalized[$key])) $normalized[$key] = ['product_id' => $productId, 'product_variant_id' => $variantId > 0 ? $variantId : null, 'quantity' => 0];
351:    private function variantAttributes(ProductVariant $variant): array
COUPLING_FILE=app/Services/OrderWorkflowService.php
12:use App\Models\ProductVariant;
586:        foreach ($order->items->sortBy(fn ($item) => sprintf('%010d:%010d', (int) $item->product_id, (int) ($item->product_variant_id ?? 0))) as $item) {
592:            if ((int) ($item->product_variant_id ?? 0) > 0) {
593:                $variant = ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->findOrFail((int) $item->product_variant_id);
606:                'product_variant_id' => $variant?->id,
615:                'metadata_json' => ['order_item_id' => $item->id, 'variant_sku' => $item->variant_sku_snapshot, 'one_time_return' => true],
619:            $aggregate = (int) ProductVariant::query()->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
COUPLING_FILE=app/Http/Requests/ProductRequest.php
82:            if ($product && (bool) $product->variants_enabled) {
COUPLING_FILE=app/Http/Requests/StoreOrderRequest.php
8:use App\Models\ProductVariant;
34:            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
45:                $variantId = (int) ($item['product_variant_id'] ?? 0);
52:                if ((bool) $product->variants_enabled) {
54:                        $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izaberi konfiguraciju proizvoda.');
57:                    $valid = ProductVariant::query()->whereKey($variantId)->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->exists();
58:                    if (!$valid) $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izabrana konfiguracija nije dostupna za ovaj proizvod.');
60:                    $validator->errors()->add('items.'.$index.'.product_variant_id', 'Ovaj proizvod nema aktivne varijante.');
71:            $variantId = (int) ($item['product_variant_id'] ?? 0);
73:            if ($productId > 0 && $quantity > 0) $items[] = ['product_id' => $productId, 'product_variant_id' => $variantId > 0 ? $variantId : null, 'quantity' => $quantity];
COUPLING_FILE=app/Http/Controllers/Admin/ProductController.php
129:            'copy_variants' => ['nullable', 'boolean'],
COUPLING_FILE=app/Http/Resources/OrderResource.php
45:                'product_variant_id' => $item->product_variant_id,
46:                'sku' => $item->variant_sku_snapshot ?: $item->product_sku,
47:                'name' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
48:                'variant_name' => $item->variant_name_snapshot,
49:                'variant_attributes' => $item->variant_attributes_json ?? [],
COUPLING_FILE=app/Console/Commands/DeploymentCheckCommand.php
72:        'product_variants',
73:        'product_variant_spec_values',
91:        'order_items' => ['id', 'order_id', 'product_id', 'product_variant_id', 'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json', 'quantity', 'commission_total_eur_snapshot', 'purchase_unit_rsd_snapshot', 'purchase_total_rsd_snapshot', 'cost_source_snapshot', 'brand_name_snapshot', 'product_line_name_snapshot', 'product_type_name_snapshot'],
98:        'stock_movements' => ['id', 'product_id', 'product_variant_id', 'quantity_change', 'event_key', 'source', 'metadata_json', 'stock_receipt_id', 'inventory_count_id'],
106:        'after_sales_case_items' => ['id', 'after_sales_case_id', 'order_item_id', 'product_id', 'product_variant_id', 'product_name_snapshot', 'quantity'],
111:        'after_sales_action_items' => ['id', 'after_sales_action_id', 'after_sales_case_item_id', 'product_id', 'product_variant_id', 'quantity', 'disposition', 'stock_effect'],
122:        'product_warranties' => ['id', 'warranty_number', 'order_id', 'order_item_id', 'product_variant_id', 'user_id', 'status', 'starts_at', 'expires_at', 'duration_months', 'duration_days', 'serial_numbers_json', 'next_maintenance_at'],
131:        'products' => ['id', 'product_type_id', 'brand_id', 'product_line_id', 'model_name', 'purchase_price_rsd', 'completeness_percent', 'name_is_manual', 'source_product_id', 'variants_enabled', 'default_variant_id'],
136:        'product_variants' => ['id', 'product_id', 'sku', 'name', 'price_amount', 'price_currency', 'stock_quantity', 'low_stock_threshold', 'status', 'is_default', 'warranty_rule_id', 'deleted_at'],
137:        'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_number', 'value_boolean'],

============================================================
6. UI / NAVIGATION / DATA QUALITY SIGNALS
============================================================
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:46:                <p class="muted">Izaberi do pet konfiguracija. Kod proizvoda sa varijantama obavezno izaberi tačan SKU konfiguracije.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:64:                                                    @if($product->variants_enabled) · {{ $product->activeVariants->count() }} varijanti @endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:71:                                            <option value="">Bez varijante</option>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:178:            const variants = variantMap[String(product.value)] || [];
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:181:            if (!variants.length) {
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:182:                variant.append(new Option('Bez varijante', ''));
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:184:                help.textContent = product.value ? 'Proizvod nema varijante.' : '';
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:190:            variants.forEach((item) => {
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/index.blade.php:17:@forelse($products as $product)<tr><td data-label="Izbor"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" data-product-select></td><td data-label="Artikal"><div class="table-product"><div class="table-thumb">@if($product->primaryImage)<img src="{{ $product->primaryImage->url }}" alt="">@else ◫ @endif</div><div><strong>{{ $product->name }}</strong><small>SKU {{ $product->sku }} · {{ $product->images_count }} slika @if($product->variants_count)· {{ $product->active_variants_count }}/{{ $product->variants_count }} aktivnih varijanti @endif</small></div></div></td><td data-label="Brend / linija">{{ $product->brand?->name ?? '—' }}@if($product->line)<small class="muted"> · {{ $product->line->name }}</small>@endif</td><td data-label="Status"><span class="status-badge status-{{ $product->deleted_at ? 'archived' : $product->status }}">{{ $product->deleted_at ? 'Arhiviran' : $product->status }}</span></td><td data-label="Kompletnost"><div class="mini-completeness"><span><i style="width:{{ (int)$product->completeness_percent }}%"></i></span><b>{{ (int)$product->completeness_percent }}%</b></div></td><td data-label="Cena">{{ number_format((float)$product->price_amount,2,',','.') }} {{ $product->price_currency }}</td><td data-label="Lager">{{ $product->stock_quantity }}</td><td data-label="Izmena">{{ optional($product->updated_at)->format('d.m.Y. H:i') }}</td><td class="row-actions"><a class="button button-small button-ghost" href="{{ route('admin.products.edit',$product) }}">Izmeni</a><a class="button button-small button-ghost" href="{{ route('admin.products.clone',$product) }}">Kloniraj</a><a class="button button-small button-ghost" href="{{ route('admin.products.variants.index',$product) }}">Varijante</a>@can('catalog.manage_images')<a class="button button-small button-ghost" href="{{ route('admin.products.images.index',$product) }}">Slike</a>@endcan</td></tr>@empty<tr><td colspan="9"><div class="empty-state">Nema artikala za izabrane filtere.</div></td></tr>@endforelse
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:14:                <a class="button button-ghost" href="{{ route('admin.products.variants.index',$product) }}">Varijante @if($product->variants_enabled)({{ $product->variants()->count() }})@endif</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:347:                    @if($product->exists && $product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:349:                        <label><span>Ukupan lager varijanti</span><input type="number" value="{{ $product->stock_quantity }}" disabled><small class="muted">Zbir aktivnih varijanti. Menja se na ekranu Varijante.</small></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:350:                        <label><span>Ukupan prag</span><input type="number" value="{{ $product->low_stock_threshold }}" disabled><small class="muted">Zbir pragova aktivnih varijanti.</small></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:375:                                <p>Trajno brisanje uklanja artikal, specifikacije, varijante i zapise galerije. Ovu radnju nije moguće poništiti.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:396:                                    <p class="muted">Live CMS će ukloniti sam artikal, slike, specifikacije i varijante; istorijske porudžbine, lager i garancije ostaju kao poslovni događaji, ali bez product/variant veze, SKU-a, naziva i snapshot identiteta izabranog artikla.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:2:@section('title', 'Varijante proizvoda')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:5:<div class="page-heading"><div><span class="eyebrow">Konfiguracije proizvoda</span><h1>Varijante</h1><p>{{ $product->name }} · {{ $product->sku }}</p></div><span class="count-pill">{{ $product->variants->count() }} varijanti · ukupno {{ $product->stock_quantity }} kom.</span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:6:@if(!$product->type)<div class="alert error">Pre dodavanja varijanti artikal mora imati izabran tip.</div>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:8:<h2>Dodaj novu varijantu</h2>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:9:<form method="post" data-variant-form action="{{ route('admin.products.variants.store',$product) }}">@csrf
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:11:<button class="button button-primary" type="submit" @disabled(!$product->type)>Dodaj varijantu</button>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:14:@forelse($product->variants as $variant)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:17:<form method="post" data-variant-form action="{{ route('admin.products.variants.update',[$product,$variant]) }}">@csrf @method('PUT')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:19:<button class="button button-primary" type="submit">Sačuvaj varijantu</button>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:21:@if((int)$variant->stock_quantity===0)<button class="button button-danger" type="submit" form="archive-{{ $variant->id }}" onclick="return confirm('Arhivirati varijantu?')">Arhiviraj</button>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:24:<form class="panel nested-panel" method="post" action="{{ route('admin.products.variants.stock',[$product,$variant]) }}">@csrf
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:27:<form class="panel nested-panel" method="post" enctype="multipart/form-data" action="{{ route('admin.products.variants.images',[$product,$variant]) }}">@csrf
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:28:<h3>Posebne slike varijante</h3><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required><button class="button button-ghost" type="submit">Dodaj slike</button>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:33:<form id="default-{{ $variant->id }}" method="post" action="{{ route('admin.products.variants.default',[$product,$variant]) }}">@csrf</form>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:34:<form id="archive-{{ $variant->id }}" method="post" action="{{ route('admin.products.variants.archive',[$product,$variant]) }}">@csrf @method('DELETE')</form>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:35:@foreach($variant->images as $image)<form id="delete-variant-image-{{ $image->id }}" method="post" action="{{ route('admin.products.variants.images.delete',[$product,$variant,$image]) }}">@csrf @method('DELETE')</form>@endforeach
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/variants.blade.php:36:@empty<div class="empty-state">Još nema varijanti. Postojeći artikal nastavlja da koristi sopstvenu cenu i lager dok ne dodaš prvu varijantu.</div>@endforelse
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/clone.blade.php:18:'copy_variants'=>'Varijante, njihove specifikacije i cene (lager se ne kopira)',
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/clone.blade.php:21:<section class="panel form-section"><h2>Bezbednosna pravila kloniranja</h2><ul class="compact-list"><li>Novi artikal uvek dobija novi SKU.</li><li>Lager novog artikla je uvek 0.</li><li>Status novog artikla je uvek Nacrt.</li><li>Rezervacije, serijski brojevi, prodaja i istorija se ne kopiraju.</li><li>Kategorija se automatski dodeljuje prema tipu proizvoda.</li><li>Klonirane varijante dobijaju nove SKU oznake, status Nacrt i lager 0.</li></ul></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/clone.blade.php:22:</div><aside class="form-side"><section class="panel form-section sticky-card"><h2>Pregled izvora</h2><p><strong>{{ $product->name }}</strong></p><p class="muted">{{ $product->brand?->name ?? 'Bez brenda' }} @if($product->line)· {{ $product->line->name }}@endif</p><p>{{ number_format((float)$product->price_amount,2,',','.') }} {{ $product->price_currency }}</p><p>{{ $product->specificationValues->count() }} specifikacija · {{ $product->images->count() }} slika · {{ $product->variants->count() }} varijanti</p><button class="button button-primary button-large">Kloniraj kao novi artikal</button></section></aside></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/partials/variant-fields.blade.php:8:<label><span>Naziv varijante</span><input name="name" maxlength="190" value="{{ old('name',$variant?->name) }}" placeholder="Automatski iz specifikacija"></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/partials/variant-fields.blade.php:18:<label class="check-card"><input type="checkbox" name="is_default" value="1" @checked(old('is_default',$variant?->is_default ?? false))><span>Podrazumevana varijanta</span></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/partials/variant-fields.blade.php:21:<h3>Specifikacije varijante</h3><div class="field-grid">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/partials/variant-fields.blade.php:68:    @if($storageTotalField)<div class="storage-total-card"><label><span>Ukupan kapacitet diskova (GB)</span><input type="number" value="{{ $storageTotalValue }}" readonly data-storage-total-display></label><input type="hidden" name="specs[{{ $storageTotalField->id }}]" value="{{ $storageTotalValue }}" data-storage-total-value><small class="muted">Automatski zbir svih diskova ove varijante.</small></div>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/dictionary/index.blade.php:80:                        @php($usage=$usageCounts[$item->id]??['types'=>0,'products'=>0,'variants'=>0,'children'=>0])
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/dictionary/index.blade.php:81:                        <div class="spec-field-usage"><span>Tipovi: <strong>{{ $usage['types'] }}</strong></span><span>Artikli: <strong>{{ $usage['products'] }}</strong></span><span>Varijante: <strong>{{ $usage['variants'] }}</strong></span><span>Zavisna polja: <strong>{{ $usage['children'] }}</strong></span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/dictionary/index.blade.php:90:                        <div><p>Ovo briše polje i njegove vrednosti iz artikala i varijanti. Za potvrdu upiši tačan naziv: <strong>{{ $item->name }}</strong></p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:34:<section class="panel form-section"><h2>Kvalitet finansijskih podataka</h2><dl class="detail-list"><dt>Pokriven promet</dt><dd>{{ number_format($s['known_revenue_rsd'],2,',','.') }} RSD</dd><dt>Promet bez nabavne cene</dt><dd>{{ number_format($s['revenue_missing_cost_rsd'],2,',','.') }} RSD</dd><dt>Stavke bez troška</dt><dd>{{ $s['missing_cost_lines'] }}</dd><dt>Preporuka</dt><dd>@if($s['cost_coverage_percent']<95)<span class="analytics-warning">Dopuniti nabavne cene proizvoda i varijanti.</span>@else<span class="analytics-good">Podaci su dovoljno pokriveni.</span>@endif</dd></dl></section></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:42:<section class="panel form-section"><div class="section-heading-row"><div><h2>Najveća vrednost lagera</h2><p class="muted">Artikli i varijante koji vezuju najviše kapitala.</p></div><a class="button button-ghost button-small" href="{{ route('admin.reports.inventory.csv') }}">Postojeći lager CSV</a></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal / varijanta</th><th>Količina</th><th>Jedinični trošak</th><th>Vrednost</th><th>Starost</th></tr></thead><tbody>@forelse(array_slice($report['inventory']['top_value'],0,15) as $row)<tr><td>{{ $row['sku'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['quantity'] }}</td><td>{{ $row['has_cost']?number_format($row['unit_cost_rsd'],2,',','.').' RSD':'Nedostaje' }}</td><td>{{ number_format($row['value_rsd'],2,',','.') }} RSD</td><td>{{ $row['age_days'] }} dana</td></tr>@empty<tr><td colspan="6">Nema lagera.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/data-quality/index.blade.php:13:        <p>Centralni pregled integriteta artikala, galerija, varijanti, kategorija, specifikacija i korisničkih uloga.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/data-quality/index.blade.php:25:        <article><small>Varijante</small><strong>{{ (int)($report['metrics']['variants_total'] ?? 0) }}</strong></article>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/data-quality/index.blade.php:67:    <div><h2>Bezbedna automatska popravka</h2><p class="muted">Usklađuje kategorije tipova, čisti zastarele specifikacione veze, preračunava diskove i kompletnost, normalizuje glavne slike i podrazumevane varijante. Ne briše artikle, slike ni poslovnu istoriju.</p></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/stock/index.blade.php:6:<section class="panel form-section"><h2>Trenutno stanje</h2><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal</th><th>Lager</th><th>Prag</th>@can('stock.adjust')<th>Korekcija</th>@endcan</tr></thead><tbody>@forelse($products as $product)<tr><td><strong>{{ $product->sku }}</strong></td><td>{{ $product->name }}</td><td><strong class="{{ $product->stock_quantity <= $product->low_stock_threshold ? 'text-danger' : 'text-success' }}">{{ $product->stock_quantity }}</strong></td><td>{{ $product->low_stock_threshold }}</td>@can('stock.adjust')<td>@if($product->variants_enabled)<a class="button button-ghost button-small" href="{{ route('admin.products.variants.index',$product) }}">Varijante ({{ $product->variants_count }})</a>@else<form class="inline-form" method="post" action="{{ route('admin.stock.adjust',$product) }}">@csrf<input type="hidden" name="idempotency_key" value="{{ $idempotencyKeys[$product->id] }}"><input type="number" name="quantity_change" required placeholder="+/-" class="ux-stock-max-width-90"><input name="note" required maxlength="1000" placeholder="Obavezan razlog"><button class="button button-ghost button-small" type="submit">Primeni</button></form>@endif</td>@endcan</tr>@empty<tr><td colspan="5">Nema artikala.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:308:                @if($product->variants_enabled)<span class="success">{{ $product->activeVariants->count() }} varijanti</span>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:326:                        @if($product->variants_enabled && $variantPrices->isNotEmpty() && $variantPrices->min() !== $variantPrices->max())
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/index.blade.php:340:                    <a class="button button-small button-ghost" href="{{ route('admin.products.variants.index', $product) }}">Varijante</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:134:                <a class="button button-ghost button-small" href="{{ route('admin.products.variants.index', $product) }}">Varijante</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:151:        @if($product->variants_enabled && $product->activeVariants->isNotEmpty())
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:153:                <div class="section-heading-row"><div><h2>Izaberi konfiguraciju</h2><p class="muted">Cena, lager i SKU pripadaju konkretnoj varijanti.</p></div></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:211:                                        <a class="button button-primary" href="{{ route('orders.create', ['product' => $product->id, 'variant' => $variant->id]) }}">Poruči ovu varijantu</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:253:            @if(!$product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:312:            @if($product->variants_enabled)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:314:                    <span>Varijanta</span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/catalog/show.blade.php:316:                        <option value="">Izaberi varijantu</option>
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/StockMovementController.php:32:            ->withCount('variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:41:                ->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:55:                'lowStock' => Product::query()->whereNull('deleted_at')->where('variants_enabled', false)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->limit(50)->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:127:            $rows = Product::query()->whereNull('deleted_at')->where('variants_enabled', false)->orderBy('sku')->get(['sku', 'name', 'stock_quantity', 'low_stock_threshold', 'status']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:113:        $product->load(['brand', 'line', 'type', 'categories', 'specificationValues', 'images', 'warrantyRules', 'variants.specificationValues', 'variants.images']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:129:            'copy_variants' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:489:            $counts[$id] = ['types' => 0, 'products' => 0, 'variants' => 0, 'children' => 0];
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:495:            'variants' => ['product_variant_spec_values', 'field_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:30:            'variants.specificationValues.field',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:31:            'variants.images',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:32:            'variants.warrantyRule',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:34:        return view('admin.products.variants', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:49:        return redirect()->route('admin.products.variants.index', $product)->with('status', 'Varijanta '.$variant->sku.' je kreirana.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:57:        return back()->with('status', 'Varijanta je sačuvana.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:70:        return back()->with('status', 'Lager varijante je korigovan.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:78:        return back()->with('status', 'Podrazumevana varijanta je promenjena.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:86:        return back()->with('status', 'Varijanta je arhivirana.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:95:        return back()->with('status', 'Dodato slika varijante: '.$count.'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductVariantController.php:103:        return back()->with('status', 'Slika varijante je uklonjena.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:61:            'variants_enabled',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:46:                if ($products->contains(static fn (Product $product): bool => (bool) $product->variants_enabled)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:47:                    throw ValidationException::withMessages(['items' => 'Ulaz robe za artikal sa varijantama evidentirajte na konkretnoj varijanti proizvoda.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:116:                if ($products->contains(static fn (Product $product): bool => (bool) $product->variants_enabled)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:117:                    throw ValidationException::withMessages(['items' => 'Popis za artikal sa varijantama izvršite po pojedinačnim varijantama.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:61:                    'note' => 'Početno stanje varijante '.$variant->sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:68:            $this->audit->log('product.variant.created', 'Kreirana varijanta '.$variant->sku, $variant, after: $this->snapshot($variant), user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:100:            $this->audit->log('product.variant.updated', 'Izmenjena varijanta '.$locked->sku, $locked, $before, $this->snapshot($locked), user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:116:            if ($after < 0) throw ValidationException::withMessages(['quantity_change' => 'Korekcija ne može spustiti lager varijante ispod nule.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:125:            $this->audit->log('product.variant.stock_adjusted', 'Korigovan lager varijante '.$locked->sku, $locked, ['stock_quantity' => $before], ['stock_quantity' => $after], ['movement_id' => $movement->id], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:136:            if ((int) $locked->stock_quantity > 0) throw ValidationException::withMessages(['variant' => 'Varijanta sa stanjem većim od nule ne može biti arhivirana.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:140:            $this->audit->log('product.variant.archived', 'Arhivirana varijanta '.$locked->sku, $locked, user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:152:            $this->audit->log('product.variant.default', 'Postavljena podrazumevana varijanta '.$locked->sku, $locked, user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:169:        $product->forceFill(['default_variant_id' => $variant->id, 'variants_enabled' => true])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:180:        else $product->forceFill(['default_variant_id' => null, 'variants_enabled' => false])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:185:        $variants = ProductVariant::query()->where('product_id', $product->id)->whereNull('deleted_at')->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:186:        if ($variants->isEmpty()) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:187:            $product->forceFill(['variants_enabled' => false, 'default_variant_id' => null])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:190:        $active = $variants->where('status', 'active');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:191:        $default = $active->firstWhere('id', (int) $product->default_variant_id) ?? $active->first() ?? $variants->firstWhere('id', (int) $product->default_variant_id) ?? $variants->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:195:            'variants_enabled' => true,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:234:                    'specs.'.$field->id => 'Specifikacija varijante „'.$field->name.'“ nije sačuvana. Osveži stranicu i pokušaj ponovo. Tehnički kod: '.(string) ($exception->errorInfo[1] ?? $exception->getCode()),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductVariantService.php:255:        return mb_substr($parts !== [] ? implode(' · ', $parts) : 'Varijanta', 0, 190);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:125:        if ((bool) $lockedProduct->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:128:                    'product_variant_id' => 'Izaberi varijantu artikla.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:143:                    'product_variant_id' => 'Izabrana varijanta nije dostupna.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:148:                'product_variant_id' => 'Ovaj artikal nema aktivne varijante.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:626:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:627:            $counts['owned_rows_deleted'] += DB::table('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:117:     * @return array{components:int,totals:int,pairs:int,products:int,variants:int}
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:121:        $summary = ['components' => 0, 'totals' => 0, 'pairs' => 0, 'products' => 0, 'variants' => 0];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:156:            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:157:                $summary['variants'] += $this->normalizeVariantTable($pair['source_id'], $pair['total_id'], $pair['type_ids']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:202:            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:351:        DB::table('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:352:            ->join('products', 'products.id', '=', 'variants.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:354:            ->orderBy('variants.id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:355:            ->select('variants.id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:360:            }, 'variants.id', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:451:        DB::table('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:452:            ->join('products', 'products.id', '=', 'variants.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:454:            ->orderBy('variants.id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:455:            ->select('variants.id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:468:            }, 'variants.id', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:28:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:54:        'product_variants',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:70:        $variants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:75:        $variantIds = $variants
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:81:        $variantSkus = $variants
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:89:        $variantNames = $variants
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:469:                    $nested->where('k.REFERENCED_TABLE_NAME', 'product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:610:        $variants = [];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:613:            $variants[] = $token;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:614:            $variants[] = $this->pdfEscape($token);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:619:                $variants[] = $cp;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:620:                $variants[] = $this->pdfEscape($cp);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:624:        $variants = array_values(array_unique(array_filter($variants, static fn ($value): bool => $value !== '')));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:626:        foreach ($variants as $needle) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:666:            foreach ($variants as $needle) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:298:        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:303:            ->where('variants_enabled', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:308:                        $variant->selectRaw('1')->from('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:309:                            ->whereColumn('product_variants.id', 'products.default_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:310:                            ->whereColumn('product_variants.product_id', 'products.id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:311:                            ->whereNull('product_variants.deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:316:        $issues[] = $this->issue('invalid_default_variants', 'Proizvodi sa nevažećom podrazumevanom varijantom', 'warning', $invalidCount, 'Bezbedna popravka bira aktivnu ili prvu raspoloživu varijantu.', true, $this->rows($samples));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:318:        $multipleDefaults = DB::table('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:327:        $issues[] = $this->issue('multiple_default_variants', 'Proizvodi sa više podrazumevanih varijanti', 'warning', $multipleCount, 'Samo jedna varijanta može biti podrazumevana.', true, $this->rows($multipleSamples));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:355:        if (Schema::hasTable('product_variant_spec_values') && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:357:                ->join('product_variants', 'product_variants.id', '=', 'product_variant_spec_values.product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:358:                ->join('products', 'products.id', '=', 'product_variants.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:371:                'product_variants.id', 'product_variants.sku', 'product_variants.name', 'product_variant_spec_values.field_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:372:            ])->orderBy('product_variants.id')->limit($limit)->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:373:            $issues[] = $this->issue('orphan_variant_specifications', 'Zastarele ili nepovezane specifikacije varijanti', 'critical', $variantCount, 'Vrednost varijante mora pripadati aktivnom polju tipa proizvoda.', true, $this->rows($variantSamples));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:401:            'variants_total' => 0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:411:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:412:            $metrics['variants_total'] = (int) DB::table('product_variants')->whereNull('deleted_at')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:462:        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants') || !Schema::hasColumn('products', 'default_variant_id')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:468:            ->where('variants_enabled', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:474:                        $variants = DB::table('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:482:                        if ($variants->isEmpty()) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:489:                        $chosen = $variants->firstWhere('id', (int) $product->default_variant_id) ?? $variants->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:490:                        $defaultCount = $variants->where('is_default', true)->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:494:                        DB::table('product_variants')->where('product_id', (int) $product->id)->update(['is_default' => false]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:495:                        DB::table('product_variants')->where('id', (int) $chosen->id)->update(['is_default' => true]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/InventoryService.php:21:        if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/InventoryService.php:22:            throw ValidationException::withMessages(['product' => 'Artikal koristi varijante. Lager korigujte na ekranu Varijante proizvoda.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:24:        'products' => ['id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd', 'variants_enabled'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:230:        $products = DB::table('products')->whereNull('deleted_at')->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:233:        if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:234:            $variants = DB::table('product_variants as v')->join('products as p', 'p.id', '=', 'v.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:237:            foreach ($variants as $variant) $rows->push($this->inventoryRow('variant', $variant->id, $variant->sku, $variant->name, (int) $variant->stock_quantity, $variant->purchase_price_rsd, $variant->created_at));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:238:        if ($variantId > 0 && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:239:            $variantCost = $this->positive(DB::table('product_variants')->where('id', $variantId)->value('purchase_price_rsd'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:61:                    ->orWhereHas('variants', static fn (Builder $variantQuery) => $variantQuery
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:91:            ->withCount(['images', 'variants', 'activeVariants']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:74:        $variants = ProductVariant::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:83:        if ($variants->count() !== count($variantIds)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:143:            $variant = $variantId > 0 ? $variants->get($variantId) : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:145:            if ((bool) $product->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:154:                if ($variant !== null) throw ValidationException::withMessages(['items' => 'Proizvod '.$product->name.' nema aktivne varijante.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:30:        private readonly ProductVariantService $variants,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:106:            if ((bool) $locked->variants_enabled) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:143:            $source->loadMissing(['categories', 'specificationValues.field', 'images', 'warrantyRules', 'variants.specificationValues', 'variants.images']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:204:            if (!empty($options['copy_variants']) && Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:206:                $this->variants->syncParent($clone);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:344:                return Schema::hasTable('product_variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:437:        foreach ($source->variants as $sourceVariant) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:491:            'completeness_percent','name_is_manual','source_product_id','variants_enabled','default_variant_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:18:     * @return array{types:int,products:int,variants:int,children:int,options:int,templates:int,recalculated:int,downgraded:int}
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:103:            'variants' => $variantCount,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:119:                + $this->countOrphans('product_variant_spec_values', 'product_variant_id', 'product_variants', 'id'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:138:            $this->deleteOrphans('product_variant_spec_values', 'product_variant_id', 'product_variants', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:323:        if (!Schema::hasTable('product_variant_spec_values') || !Schema::hasTable('product_variants') || !Schema::hasTable('products') || !Schema::hasTable('product_type_fields')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:328:            ->join('product_variants as variants', 'variants.id', '=', 'values.product_variant_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:329:            ->join('products', 'products.id', '=', 'variants.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:348:                    ->from('product_variants as variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:349:                    ->join('products', 'products.id', '=', 'variants.product_id')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:354:                    ->whereColumn('variants.id', 'product_variant_spec_values.product_variant_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:30:        private readonly ProductVariantService $variants,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:292:        $variants = ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:293:        if ($variants->count() !== $variantIds->count()) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:294:            throw ValidationException::withMessages(['items' => 'Jedna od varijanti više nije dostupna za promenu lagera.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:299:            if ((bool) $product->variants_enabled && $item->product_variant_id === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:300:                throw ValidationException::withMessages(['items' => 'Za artikal '.$product->sku.' mora biti poznata konkretna varijanta. Izaberite eksternu obradu lagera.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:303:                $variant = $variants->get((int) $item->product_variant_id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:305:                    throw ValidationException::withMessages(['items' => 'Varijanta ne pripada izabranom proizvodu.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:314:                $variant = $variants->get((int) substr((string) $key, 2));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:316:                    throw ValidationException::withMessages(['items' => 'Nema dovoljno lagera za varijantu '.$variant->sku.'. Dostupno: '.$variant->stock_quantity.', potrebno: '.$quantity.'.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:339:            $variant = $item->product_variant_id !== null ? $variants->get((int) $item->product_variant_id) : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:371:        foreach ($affectedParents as $parent) $this->variants->syncParent($parent);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:202:                ->where('variants_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:241:            if (Schema::hasTable('product_variants')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:258:                        'title' => (int) $variant->stock_quantity <= 0 ? 'Varijanta je bez lagera' : 'Nizak lager varijante',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:260:                        'action_url' => route('admin.products.variants.index', $variant->product_id).'#variant-'.$variant->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:271:                                'action_label' => 'Otvori varijantu',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:61:        $this->loadMany($order, 'items', 'order_items', ['id', 'order_id'], $warnings, ['product' => ['products', ['id']], 'variant' => ['product_variants', ['id']]]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:41:            $directory = 'products/'.$product->id.($variant ? '/variants/'.$variant->id : '');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:64:            $this->audit->log($variant ? 'product.variant.images.uploaded' : 'product.images.uploaded', $variant ? 'Dodate slike varijante '.$variant->sku : 'Dodate slike artikla '.$product->sku, $variant ?? $product, metadata: ['count' => $created, 'product_id' => $product->id]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:113:            $newPath = 'products/'.$target->product_id.'/variants/'.$target->id.'/'.Str::uuid().'.'.$extension;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:114:            Storage::disk('public')->makeDirectory('products/'.$target->product_id.'/variants/'.$target->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:138:        abort_if($image->product_variant_id !== null, 422, 'Glavna slika artikla ne može biti slika varijante.');

============================================================
7. OPENAPI + MOBILE VARIANT SIGNALS
============================================================
OPENAPI_VARIANT_SIGNAL_COUNT=packages/api-contract/openapi.yaml|4
OPENAPI_VARIANT_SIGNAL_COUNT=apps/cms/current/docs/openapi.yaml|4
OPENAPI_VARIANT_SIGNAL_COUNT=apps/mobile/current/docs/openapi.yaml|4
MOBILE_SOURCE_FILES_WITH_VARIANT_SIGNALS=38

============================================================
8. LATEST 500 / VARIANT ERROR LOG SIGNALS
============================================================
LATEST_LARAVEL_LOG=/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/scheduler.log

============================================================
9. DECOMMISSION SAFETY CLASSIFICATION
============================================================
DECOMMISSION_OPERATIONAL_REMOVE=ProductVariantController,ProductVariantRequest,ProductVariantService,variants Blade,variant routes,variant UI links,clone variant option,variant doctor,active API/mobile variant workflow
DECOMMISSION_RUNTIME_SIMPLIFY=Product model variant relations,ProductAdminService variant clone/sync,ProductImageService variant upload/clone,DataQuality variant metric,ManagementReport variant branch,order create variant selection
HISTORICAL_SCHEMA_POLICY=DO_NOT_DELETE_MIGRATION_HISTORY_FILES
HISTORICAL_DATA_POLICY=DROP_VARIANT_TABLES_AND_COLUMNS_ONLY_IF_RUNTIME_PROBE_CONFIRMS_ZERO_VARIANT_ROWS_AND_ZERO_NON_NULL_BUSINESS_REFERENCES
HISTORICAL_SNAPSHOT_POLICY=variant snapshot columns may be removable only when all rows are null/empty; otherwise retain as legacy-read-only until separate history-safe migration
FULL_SCHEMA_DECOMMISSION_ELIGIBLE=YES
DECOMMISSION_DECISION=READY_FOR_FULL_RUNTIME_AND_SCHEMA_REMOVAL_WITH_NEW_FORWARD_MIGRATION

============================================================
10. READ-ONLY IMMUTABILITY
============================================================
GIT_VISIBLE_STATE=UNCHANGED_DURING_AUDIT
SOURCE_WRITES_DURING_AUDIT=0
DATABASE_WRITES_DURING_AUDIT=0

============================================================
11. FINAL
============================================================
PRODUCT_VARIANT_COUNT=0
VARIANT_REFERENCE_SURFACES_WITH_NONZERO_ROWS=0
VARIANT_WEB_ROUTE_SIGNAL_COUNT=8
VARIANT_ADMIN_API_ROUTE_SIGNAL_COUNT=0
SOURCE_FILES_WITH_VARIANT_SIGNALS=82
MOBILE_SOURCE_FILES_WITH_VARIANT_SIGNALS=38
CMS_PRODUCT_VARIANTS_DECOMMISSION_AUDIT_BATCH1=PASS
NEXT_ACTION=PREPARE_CMS_PRODUCT_VARIANTS_FULL_DECOMMISSION_IMPLEMENTATION_BATCH2_BASED_ON_THIS_REPORT
REPORT_READY_TO_UPLOAD=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-AUDIT-BATCH1-20260818-235228.md
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-AUDIT-BATCH1-20260818-235228.md

PASS: CMS PRODUCT VARIANTS DECOMMISSION READ-ONLY AUDIT BATCH 1 COMPLETE

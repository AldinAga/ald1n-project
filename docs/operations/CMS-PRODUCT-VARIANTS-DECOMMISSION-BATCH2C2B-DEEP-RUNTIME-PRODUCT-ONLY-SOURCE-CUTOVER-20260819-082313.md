
============================================================
CMS PRODUCT VARIANTS - DECOMMISSION BATCH 2C2B DEEP RUNTIME PRODUCT-ONLY SOURCE CUTOVER
============================================================
DATE=Wed Aug 19 08:23:13 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2C2B-DEEP-RUNTIME-PRODUCT-ONLY-SOURCE-CUTOVER-20260819-082313.md
BACKUP=/home/icaffeco/backups/releases/product-variants-decommission-batch2c2b-deep-runtime-source-20260819-082313
MODE=SOURCE_ONLY_DEEP_RUNTIME_PRODUCT_ONLY_CUTOVER
SOURCE_REPLACEMENTS_EXPECTED=48
SOURCE_DELETIONS_EXPECTED=8
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
EAS_BUILD=NO
CMS_STATIC_CHECK=DEFERRED_TO_BATCH2C2C_STATIC_TEST_CONTRACT_REWRITE_AND_SCHEMA_PURGE

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
PREFLIGHT_COMMAND_mktemp=PASS
PREFLIGHT_COMMAND_date=PASS
PREFLIGHT_COMMAND_dirname=PASS
PREFLIGHT_COMMAND_basename=PASS
PREFLIGHT_COMMAND_tee=PASS
PREFLIGHT_COMMAND_tr=PASS
PREFLIGHT_COMMAND_head=PASS
PREFLIGHT_COMMAND_tail=PASS
PREFLIGHT_COMMAND_cut=PASS
PREFLIGHT_COMMAND_stat=PASS
PREFLIGHT_COMMAND_chmod=PASS
CONCURRENCY_LOCK=ACQUIRED
BATCH2C2A_V3_PREREQUISITE=PASS_EXACT_MUTATION_SOURCE_CAPTURE_RECERTIFIED
FULL_SOURCE_CAPTURE_PREREQUISITE=PASS_70_CANONICAL_PLUS_4_AUXILIARY

============================================================
1. EXACT 74-FILE CAPTURE HASH RECERTIFICATION + SOURCE CONTRACT
============================================================
CAPTURED_SOURCE_HASH_RECERTIFICATION=PASS_74_OF_74_EXACT
IMMUTABLE_CAPTURED_FILES=PASS_18_OF_18_EXACT
DEFERRED_STATIC_TEST_CONTRACT_HASHES=PASS_15_OF_15_UNCHANGED
MANAGED_BASELINE=PASS_56_OF_56_EXACT
GIT_OUTSIDE_ALLOWLIST_BASELINE=CAPTURED
ROUTE_CACHE_BASELINE=CAPTURED_UNCHANGED_POLICY

============================================================
2. LIVE DB ZERO-STATE RECHECK BEFORE SOURCE MUTATION
============================================================
No syntax errors detected in /tmp/ald1n-variants-2c2b.qpTBCU/db-probe.php
PRODUCT_VARIANT_COUNT=0
PRODUCT_VARIANT_SPEC_VALUE_COUNT=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_IMAGES=0
NON_NULL_PRODUCT_VARIANT_ID_ORDER_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_STOCK_MOVEMENTS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_CASE_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_ACTION_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_WARRANTIES=0
NON_EMPTY_ORDER_ITEMS_VARIANT_SKU_SNAPSHOT=0
NON_EMPTY_ORDER_ITEMS_VARIANT_NAME_SNAPSHOT=0
NON_EMPTY_ORDER_ITEMS_VARIANT_ATTRIBUTES_JSON=0
PRODUCTS_VARIANTS_ENABLED=0
PRODUCTS_DEFAULT_VARIANT_ID_NON_NULL=0
BUSINESS_VARIANT_REFERENCE_NON_NULL_TOTAL=0
VARIANT_SNAPSHOT_NON_EMPTY_TOTAL=0
LIVE_DB_ZERO_VARIANT_STATE=PASS
VARIANT_SCHEMA_STILL_PRESENT=PASS_EXPECTED_SOURCE_ONLY_INTERMEDIATE_GATE
VARIANT_SNAPSHOT_DROP_ELIGIBLE=YES_RECONFIRMED
CORE_VARIANT_SCHEMA_DROP_ELIGIBLE=YES_RECONFIRMED

============================================================
3. BACKUP 56 MANAGED SOURCE FILES
============================================================
BACKUP_VERIFIED=PASS_56_OF_56
BACKUP_PATH=/home/icaffeco/backups/releases/product-variants-decommission-batch2c2b-deep-runtime-source-20260819-082313

============================================================
4. BUILD EXACT 48-FILE PRODUCT-ONLY SOURCE REPLACEMENT SET IN TEMP
============================================================
TEMP_REPLACEMENT_HASHES=PASS_48_OF_48_EXACT
TEMP_PHP_SYNTAX=PASS_43_FILES
TEMP_MANAGED_WHITESPACE_GUARD=PASS_48_OF_48
TEMP_RUNTIME_VARIANT_SIGNALS=PASS_ZERO_IN_48_REPLACEMENTS

============================================================
5. INSTALL 48 REPLACEMENTS + RETIRE 8 DORMANT FEATURE FILES
============================================================
SOURCE_REPLACEMENTS=48
DORMANT_VARIANT_FEATURE_FILES_REMOVED=8
SOURCE_MUTATION=PASS_48_REPLACEMENTS_8_DELETIONS

============================================================
6. POST-INSTALL RUNTIME SOURCE ZERO-VARIANT CONTRACT + PHP BOOTSTRAP
============================================================
POST_CUTOVER_RUNTIME_VARIANT_SIGNAL_LINE_COUNT=31
POST_CUTOVER_RUNTIME_VARIANT_SIGNALS_BEGIN
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:32:                'activeVariants.specificationValues.field',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:33:                'activeVariants.images',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:61:                    ->orWhereHas('variants', static fn (Builder $variantQuery) => $variantQuery
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:89:                'activeVariants:id,product_id,sku,name,price_amount,price_currency,stock_quantity,is_default',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:91:            ->withCount(['images', 'variants', 'activeVariants']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:104:                    ->orWhereHas('activeVariants', static fn (Builder $variantQuery) => $variantQuery
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSpecificationFilterService.php:47:                    $this->whereProductOrVariantSpec($query, fn (Builder $specQuery) => $specQuery
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSpecificationFilterService.php:52:                $this->whereProductOrVariantSpec($query, fn (Builder $specQuery) => $specQuery
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSpecificationFilterService.php:57:                $this->whereProductOrVariantSpec($query, fn (Builder $specQuery) => $specQuery
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSpecificationFilterService.php:68:                    $this->whereProductOrVariantSpec($query, function (Builder $specQuery) use ($fieldId, $min, $max): void {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSpecificationFilterService.php:77:                $this->whereProductOrVariantSpec($query, fn (Builder $specQuery) => $specQuery
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSpecificationFilterService.php:85:    private function whereProductOrVariantSpec(Builder $query, callable $constraint): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSpecificationFilterService.php:89:                ->orWhereHas('activeVariants.specificationValues', $constraint);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/StockMovementController.php:18:        $query = StockMovement::query()->with(['product', 'variant', 'order', 'user'])->latest('id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/StockMovementController.php:20:            $query->where(static fn ($scope) => $scope->whereHas('product', static fn ($q) => $q->where('sku', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'))->orWhereHas('variant', static fn ($q) => $q->where('sku', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/StockMovementController.php:32:            ->withCount('variants')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:113:        $product->load(['brand', 'line', 'type', 'categories', 'specificationValues', 'images', 'warrantyRules', 'variants.specificationValues', 'variants.images']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:141:        $product->activeVariants->each(function ($variant) use ($commission, $rate): void {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:142:            $variant->setAttribute('commission_eur', $commission->unitEur(
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:143:                (float) $variant->price_amount,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:144:                (string) $variant->price_currency,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:145:                $variant->manual_commission_eur !== null ? (float) $variant->manual_commission_eur : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DataQualityDoctorCommand.php:17:    protected $description = 'Proveri duplikate, vlasništvo, fotografije, varijante, kategorije, specifikacije i kompletnost podataka.';
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/dictionary/index.blade.php:80:                        @php($usage=$usageCounts[$item->id]??['types'=>0,'products'=>0,'variants'=>0,'children'=>0])
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/dictionary/index.blade.php:81:                        <div class="spec-field-usage"><span>Tipovi: <strong>{{ $usage['types'] }}</strong></span><span>Artikli: <strong>{{ $usage['products'] }}</strong></span><span>Varijante: <strong>{{ $usage['variants'] }}</strong></span><span>Zavisna polja: <strong>{{ $usage['children'] }}</strong></span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/dictionary/index.blade.php:90:                        <div><p>Ovo briše polje i njegove vrednosti iz artikala i varijanti. Za potvrdu upiši tačan naziv: <strong>{{ $item->name }}</strong></p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:34:<section class="panel form-section"><h2>Kvalitet finansijskih podataka</h2><dl class="detail-list"><dt>Pokriven promet</dt><dd>{{ number_format($s['known_revenue_rsd'],2,',','.') }} RSD</dd><dt>Promet bez nabavne cene</dt><dd>{{ number_format($s['revenue_missing_cost_rsd'],2,',','.') }} RSD</dd><dt>Stavke bez troška</dt><dd>{{ $s['missing_cost_lines'] }}</dd><dt>Preporuka</dt><dd>@if($s['cost_coverage_percent']<95)<span class="analytics-warning">Dopuniti nabavne cene proizvoda i varijanti.</span>@else<span class="analytics-good">Podaci su dovoljno pokriveni.</span>@endif</dd></dl></section></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:42:<section class="panel form-section"><div class="section-heading-row"><div><h2>Najveća vrednost lagera</h2><p class="muted">Artikli i varijante koji vezuju najviše kapitala.</p></div><a class="button button-ghost button-small" href="{{ route('admin.reports.inventory.csv') }}">Postojeći lager CSV</a></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal / varijanta</th><th>Količina</th><th>Jedinični trošak</th><th>Vrednost</th><th>Starost</th></tr></thead><tbody>@forelse(array_slice($report['inventory']['top_value'],0,15) as $row)<tr><td>{{ $row['sku'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['quantity'] }}</td><td>{{ $row['has_cost']?number_format($row['unit_cost_rsd'],2,',','.').' RSD':'Nedostaje' }}</td><td>{{ number_format($row['value_rsd'],2,',','.') }} RSD</td><td>{{ $row['age_days'] }} dana</td></tr>@empty<tr><td colspan="6">Nema lagera.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/data-quality/index.blade.php:13:        <p>Centralni pregled integriteta artikala, galerija, varijanti, kategorija, specifikacija i korisničkih uloga.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/data-quality/index.blade.php:25:        <article><small>Varijante</small><strong>{{ (int)($report['metrics']['variants_total'] ?? 0) }}</strong></article>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/data-quality/index.blade.php:67:    <div><h2>Bezbedna automatska popravka</h2><p class="muted">Usklađuje kategorije tipova, čisti zastarele specifikacione veze, preračunava diskove i kompletnost, normalizuje glavne slike i podrazumevane varijante. Ne briše artikle, slike ni poslovnu istoriju.</p></div>
POST_CUTOVER_RUNTIME_VARIANT_SIGNALS_END
FAIL: active CMS runtime variant signals remain after source cutover
FAIL_UNEXPECTED_LINE=16158
FAIL_EXIT_CODE=1

============================================================
ROLLBACK
============================================================
ROLLBACK_SOURCE=RESTORED_56_MANAGED_FILES
DATABASE_ROLLBACK=NOT_REQUIRED_NO_DATABASE_WRITES
ROUTE_CACHE_ROLLBACK=NOT_REQUIRED_ROUTE_CACHE_NEVER_MUTATED
REPORT_READY_TO_UPLOAD=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2C2B-DEEP-RUNTIME-PRODUCT-ONLY-SOURCE-CUTOVER-20260819-082313.md

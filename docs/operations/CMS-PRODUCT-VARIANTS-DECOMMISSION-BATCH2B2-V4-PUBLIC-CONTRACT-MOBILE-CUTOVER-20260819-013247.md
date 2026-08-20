
============================================================
CMS PRODUCT VARIANTS - DECOMMISSION BATCH 2B2 V4 PUBLIC CONTRACT + MOBILE CUTOVER
============================================================
DATE=Wed Aug 19 01:32:48 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2B2-V4-PUBLIC-CONTRACT-MOBILE-CUTOVER-20260819-013247.md
BACKUP=/home/icaffeco/backups/releases/cms-product-variants-decommission-batch2b2-v4-public-contract-mobile-cutover-20260819-013247
MODE=MUTATING_PUBLIC_CONTRACT_AND_MOBILE_CUTOVER_V4_WITH_BACKUP_AND_ROLLBACK
PATCH_OBSERVABILITY=PER_TARGET_BEGIN_PASS_FAIL_WITH_STDERR_CAPTURE
PURPOSE=REMOVE_PRODUCT_VARIANTS_FROM_PUBLIC_API_OPENAPI_MOBILE_WHILE_PRESERVING_DORMANT_DEEP_COMPATIBILITY_UNTIL_BATCH2C
MANAGED_FILE_COUNT=14
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
EAS_BUILD=NO

============================================================
0. PREFLIGHT + PREREQUISITES + CONCURRENCY
============================================================
PREFLIGHT_COMMAND_bash=PASS
PREFLIGHT_COMMAND_grep=PASS
PREFLIGHT_COMMAND_sed=PASS
PREFLIGHT_COMMAND_awk=PASS
PREFLIGHT_COMMAND_cat=PASS
PREFLIGHT_COMMAND_cp=PASS
PREFLIGHT_COMMAND_mkdir=PASS
PREFLIGHT_COMMAND_rm=PASS
PREFLIGHT_COMMAND_sha256sum=PASS
PREFLIGHT_COMMAND_find=PASS
PREFLIGHT_COMMAND_sort=PASS
PREFLIGHT_COMMAND_wc=PASS
PREFLIGHT_COMMAND_git=PASS
PREFLIGHT_COMMAND_cmp=PASS
PREFLIGHT_COMMAND_mktemp=PASS
PREFLIGHT_COMMAND_date=PASS
PREFLIGHT_COMMAND_php=PASS
PREFLIGHT_COMMAND_diff=PASS
PREFLIGHT_COMMAND_dirname=PASS
PREFLIGHT_COMMAND_basename=PASS
PREFLIGHT_COMMAND_chmod=PASS
PREFLIGHT_COMMAND_touch=PASS
PREFLIGHT_COMMAND_tr=PASS
PREFLIGHT_COMMAND_tee=PASS
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
BATCH2A_V4_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2A-V4-OPERATIONAL-RETIREMENT-20260819-001247.md
BATCH2A_V4_PREREQUISITE=PASS
BATCH2B1_V3_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2B1-V3-CORE-RUNTIME-SINGLE-PRODUCT-LOCK-20260819-002527.md
BATCH2B1_V3_PREREQUISITE=PASS
PRIOR_BATCH2B2_V3_FAILED_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2B2-V3-PUBLIC-CONTRACT-MOBILE-CUTOVER-20260819-011213.md
PRIOR_BATCH2B2_V3_FAILURE=MOBILE_PRODUCT_SCREEN_VARIANT_BEHAVIOR_REMAINED_IN_TEMP
PRIOR_BATCH2B2_V3_CART_PATCH=PASS_PRODUCT_ONLY_IDENTITY
PRIOR_BATCH2B2_V3_ROLLBACK=PASS_SOURCE_RESTORED_NO_DB_WRITES
CONCURRENCY_LOCK=ACQUIRED
MOBILE_APP_VERSION=0.7.0

============================================================
1. DUPLICATE-RUN SAFE CHECK
============================================================
ALREADY_APPLIED=NO

============================================================
2. LIVE DATABASE + ROUTE ZERO-STATE SAFETY RECHECK
============================================================
PRODUCT_VARIANT_COUNT=0
PRODUCT_VARIANT_SPEC_VALUE_COUNT=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_IMAGES=0
NON_NULL_PRODUCT_VARIANT_ID_ORDER_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_STOCK_MOVEMENTS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_CASE_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_AFTER_SALES_ACTION_ITEMS=0
NON_NULL_PRODUCT_VARIANT_ID_PRODUCT_WARRANTIES=0
PRODUCTS_VARIANTS_ENABLED=0
PRODUCTS_DEFAULT_VARIANT_ID_NON_NULL=0
BUSINESS_VARIANT_REFERENCE_NON_NULL_TOTAL=0
LIVE_DB_ZERO_VARIANT_STATE=PASS
VARIANT_WEB_ROUTE_SIGNAL_COUNT=0
VARIANT_WEB_ROUTE_RUNTIME=PASS_ZERO

============================================================
3. OPENAPI PRE-PATCH PARITY + EXACT PUBLIC VARIANT COUNT
============================================================
OPENAPI_PRODUCT_VARIANT_ID_COUNT_BEFORE_api-contract_openapi.yaml=3
OPENAPI_PRODUCT_VARIANT_ID_COUNT_BEFORE_docs_openapi.yaml=3
OPENAPI_PRODUCT_VARIANT_ID_COUNT_BEFORE_docs_openapi.yaml=3
OPENAPI_PREPATCH_PARITY=PASS_3_COPIES_3_PUBLIC_VARIANT_PROPERTIES_EACH

============================================================
4. IMMUTABLE BASELINE + GIT SNAPSHOT
============================================================
IMMUTABLE_BASELINE_FILE_COUNT=22
GIT_BASELINE_CAPTURED=YES

============================================================
5. VERIFIED 14-FILE BACKUP
============================================================
BACKUP_FILE_COUNT=14
BACKUP_VERIFIED=PASS

============================================================
6. BUILD PATCHED 14-FILE SOURCE IN TEMP
============================================================
PATCH_STEP_BEGIN=CMS_PRODUCT_RESOURCE
PATCH_STEP_PASS=CMS_PRODUCT_RESOURCE
PATCH_STEP_BEGIN=CMS_ORDER_RESOURCE
PATCH_STEP_PASS=CMS_ORDER_RESOURCE
PATCH_STEP_BEGIN=CMS_AFTER_SALES_CUSTOMER_API
PATCH_STEP_PASS=CMS_AFTER_SALES_CUSTOMER_API
PATCH_STEP_BEGIN=CMS_AFTER_SALES_ADMIN_API
PATCH_STEP_PASS=CMS_AFTER_SALES_ADMIN_API
PATCH_STEP_BEGIN=OPENAPI_3_COPIES
PATCH_STEP_PASS=OPENAPI_3_COPIES
PATCH_STEP_BEGIN=MOBILE_API_TYPES
PATCH_STEP_PASS=MOBILE_API_TYPES
PATCH_STEP_BEGIN=MOBILE_ADMIN_AFTER_SALES_TYPES
PATCH_STEP_PASS=MOBILE_ADMIN_AFTER_SALES_TYPES
PATCH_STEP_BEGIN=MOBILE_CART_PROVIDER
CART_PROVIDER_VARIANT_SIGNALS_BEFORE_COUNT=5
CART_PROVIDER_VARIANT_SIGNALS_BEFORE_BEGIN
0011:  variantId: Nullable<number>;
0012:  variantName: Nullable<string>;
0033:function cartKey(productId: number, variantId: number | null): string {
0034:  return `${productId}:${variantId ?? 0}`;
0059:    const key = cartKey(input.productId, input.variantId);
CART_PROVIDER_VARIANT_SIGNALS_BEFORE_END
CART_PROVIDER_VARIANT_SIGNALS_AFTER_COUNT=0
CART_PROVIDER_VARIANT_SIGNALS_AFTER_BEGIN
CART_PROVIDER_VARIANT_SIGNALS_AFTER_END
CART_PROVIDER_PRODUCT_ONLY_IDENTITY_REWRITE=PASS
PATCH_STEP_PASS=MOBILE_CART_PROVIDER
PATCH_STEP_BEGIN=MOBILE_PRODUCT_SCREEN
PRODUCT_SCREEN_VARIANT_SIGNALS_BEFORE_COUNT=26
PRODUCT_SCREEN_VARIANT_SIGNALS_BEFORE_BEGIN
0031:  const [selectedVariantId, setSelectedVariantId] = useState<number | null>(null);
0036:    if (!product?.variants_enabled) {
0037:      setSelectedVariantId(null);
0040:    const variants = product.variants ?? [];
0041:    if (selectedVariantId !== null && variants.some((variant) => variant.id === selectedVariantId)) return;
0045:    setSelectedVariantId(preferred?.id ?? null);
0046:  }, [product, selectedVariantId]);
0048:  useEffect(() => setQuantity(1), [selectedVariantId]);
0054:  const selectedVariant = product.variants_enabled
0055:    ? product.variants?.find((variant) => variant.id === selectedVariantId) ?? null
0057:  const stock = selectedVariant?.stock_quantity ?? product.stock_quantity;
0058:  const price = selectedVariant?.price ?? product.price ?? null;
0059:  const selectedSku = selectedVariant?.sku ?? product.sku;
0060:  const imageUrl = selectedVariant?.images.find((image) => image.primary)?.url
0061:    ?? selectedVariant?.images[0]?.url
0063:  const requiresVariant = product.variants_enabled;
0064:  const canAdd = canCreateOrder && stock > 0 && (!requiresVariant || selectedVariant !== null);
0091:      variantId: selectedVariant?.id ?? null,
0092:      variantName: selectedVariant?.name ?? null,
0103:        message: `${product.name}${selectedVariant ? ` · ${selectedVariant.name}` : ''} je dodat u korpu.`,
0142:      {product.variants_enabled && product.variants?.length ? <Card><Text style={styles.sectionTitle}>Izaberi konfiguraciju</Text>{product.variants.map((variant) => {
0143:        const active = selectedVariantId === variant.id;
0144:        return <Pressable key={variant.id} onPress={() => setSelectedVariantId(variant.id)} style={[styles.variant, active && styles.variantSelected]}><View style={[styles.radio, active && styles.radioSelected]} /><View style={{ flex: 1 }}><Text style={styles.variantName}>{variant.name}</Text><Text style={styles.variantMeta}>{variant.sku} · {variant.stock_quantity} na stanju</Text></View>{variant.price ? <Text style={styles.variantPrice}>{formatMoney(variant.price.amount, variant.price.currency)}</Text> : null}</Pressable>;
0193:  variantName: { ...typography.label, color: theme.ink },
0194:  variantMeta: { ...typography.small, color: theme.muted, marginTop: 3 },
0195:  variantPrice: { ...typography.label, color: theme.primaryDark },
PRODUCT_SCREEN_VARIANT_SIGNALS_BEFORE_END
PRODUCT_SCREEN_FEEDBACK_VARIANT_REWRITE_COUNT=0
PRODUCT_SCREEN_VARIANT_SIGNALS_AFTER_COUNT=0
PRODUCT_SCREEN_VARIANT_SIGNALS_AFTER_BEGIN
PRODUCT_SCREEN_VARIANT_SIGNALS_AFTER_END
PRODUCT_SCREEN_PRODUCT_ONLY_CUTOVER=PASS
PRODUCT_SCREEN_APP_FEEDBACK_FLOW=PRESERVED_IF_PRESENT
PRODUCT_SCREEN_COPY_DESCRIPTION_FLOW=PRESERVED_IF_PRESENT
PATCH_STEP_PASS=MOBILE_PRODUCT_SCREEN
PATCH_STEP_BEGIN=MOBILE_CART_SCREEN
PATCH_STEP_PASS=MOBILE_CART_SCREEN
PATCH_STEP_BEGIN=MOBILE_CHECKOUT
PATCH_STEP_PASS=MOBILE_CHECKOUT
PATCH_STEP_BEGIN=MOBILE_VALIDATOR
PATCH_STEP_PASS=MOBILE_VALIDATOR
PATCH_OBSERVABILITY=PASS_PER_TARGET_BEGIN_PASS_FAIL
PATCH_PUBLIC_CONTRACT_MOBILE_CUTOVER=PASS_14_FILES
PATCH_OBSERVABILITY=PASS_PER_TARGET_BEGIN_PASS_FAIL_STDERR_CAPTURED
TEMP_MANAGED_WHITESPACE_GUARD=PASS_14_OF_14
Errors parsing /tmp/ald1n-variants-2b2-v4.g8Umh9/new/apps/cms/current/app/Http/Resources/ProductResource.php

============================================================
ROLLBACK
============================================================
PASS restored apps/cms/current/app/Http/Resources/ProductResource.php
PASS restored apps/cms/current/app/Http/Resources/OrderResource.php
PASS restored apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php
PASS restored apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php
PASS restored packages/api-contract/openapi.yaml
PASS restored apps/cms/current/docs/openapi.yaml
PASS restored apps/mobile/current/docs/openapi.yaml
PASS restored apps/mobile/current/src/types/api.ts
PASS restored apps/mobile/current/src/features/admin/after-sales-admin-api.ts
PASS restored apps/mobile/current/src/features/cart/cart-provider.tsx
PASS restored apps/mobile/current/src/app/(app)/product/[slug].tsx
PASS restored apps/mobile/current/src/app/(app)/cart.tsx
PASS restored apps/mobile/current/src/app/(app)/checkout.tsx
PASS restored apps/mobile/current/scripts/validate-project.mjs
ROLLBACK_SOURCE=RESTORED_14_MANAGED_FILES
DATABASE_ROLLBACK=NOT_REQUIRED_NO_DATABASE_WRITES
BACKUP=/home/icaffeco/backups/releases/cms-product-variants-decommission-batch2b2-v4-public-contract-mobile-cutover-20260819-013247

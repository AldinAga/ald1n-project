============================================================
CMS SHORT SKU GENERATOR V2 - AUDIT BATCH 1
============================================================
DATE=Mon Aug 17 12:12:12 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-SHORT-SKU-GENERATOR-V2-AUDIT-BATCH1-20260817-121212.md
MODE=READ_ONLY_SHORT_SKU_ARCHITECTURE_AUDIT
TARGET_FUTURE_FORMAT=BRAND_PLUS_SEQUENCE_OR_CATEGORY_PLUS_SEQUENCE
EXISTING_SKU_REWRITE_EXPECTED=NO
APPLICATION_SOURCE_WRITES_EXPECTED=0
DATABASE_BUSINESS_DATA_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
ROUTE_CHANGES=NO
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
PHP_VERSION=8.4.23
CONCURRENCY_LOCK=ACQUIRED
PREFLIGHT=PASS

============================================================
1. CURRENT SKU SOURCE AUTHORITY
============================================================
SKU_GENERATOR_FOUND=YES
SKU_GENERATOR_FILE=app/Services/ProductSkuGenerator.php
SKU_GENERATOR_SHA256=fccf149ad6ad4899e7813fa19537c3fd6d7c1ae8e30a0686b9e470b30bba3cc0
--- SKU generator relevant lines ---
7:final class ProductSkuGenerator
12:    public function generate(array $parts, callable $exists): string
36:    private function normalizePart(string $value): string
49:    private function limit(string $value, int $maximum): string
51:        return rtrim(substr($value, 0, max(1, $maximum)), '-');
SOURCE_FILE=app/Services/ProductAdminService.php|SHA256=69ea243a7dbabce3f1f10aeea47bc2c5ac0d3589d3b27ba7a93b001ced9dccde
7:use App\Models\Brand;
10:use App\Models\ProductVariant;
25:        private readonly ProductSkuGenerator $skuGenerator,
29:        private readonly ProductVariantService $variants,
38:            $generated = $this->shouldGenerateName($data);
39:            if ($generated) $data['name'] = $this->generateProductName($data, $specs, $specDetails);
45:            $data['name_is_manual'] = !$generated;
46:            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails]);
51:            $categories = array_map('intval', $data['category_ids'] ?? []);
52:            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);
72:            $this->audit->log('product.created', 'Kreiran artikal '.$product->sku, $product, after: $this->snapshot($product), metadata: ['completeness' => $completeness]);
87:            $generated = $this->shouldGenerateName($data);
88:            if ($generated) $data['name'] = $this->generateProductName($data, $specs, $specDetails);
95:            $data['name_is_manual'] = $generated
98:            if (!empty($data['regenerate_sku'])) $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails], $locked->id);
102:            $categories = array_map('intval', $data['category_ids'] ?? []);
103:            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);
105:            if ((bool) $locked->variants_enabled) {
133:            $this->audit->log('product.updated', 'Izmenjen artikal '.$locked->sku, $locked, $before, $this->snapshot($locked), ['completeness' => $completeness]);
139:    public function clone(Product $source, array $options, User $user): Product
142:            $source->loadMissing(['categories', 'specificationValues.field', 'images', 'warrantyRules', 'variants.specificationValues', 'variants.images']);
162:                'brand_id' => $copyBasic ? $source->brand_id : null,
180:            if (!empty($options['regenerate_name']) && $data['product_type_id']) {
181:                $data['name'] = $this->generateProductName($data, $specs, $details);
186:            $data['name'] = $this->uniqueCloneName((string) $data['name']);
188:            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $details]);
189:            $typeCategoryId = !empty($data['product_type_id'])
190:                ? ProductType::query()->whereKey((int) $data['product_type_id'])->value('category_id')
192:            $categoryIds = $typeCategoryId !== null ? [(int) $typeCategoryId] : [];
193:            $completeness = $this->templates->completeness($this->typeFromData($data), $data + ['category_ids' => $categoryIds], $specs, $details);
196:            $clone = Product::query()->create($data);
197:            $clone->categories()->sync($categoryIds);
198:            if ($copySpecs) $this->syncSpecifications($clone, $specs, $details, $structured);
199:            if (!empty($options['copy_images'])) $this->images->cloneImages($source, $clone);
201:                ? $this->cloneWarrantyRules($source, $clone, $user)
203:            if (!empty($options['copy_variants']) && Schema::hasTable('product_variants')) {
204:                $this->cloneVariants($source, $clone, $user, $warrantyRuleMap, !empty($options['copy_images']));
205:                $this->variants->syncParent($clone);
208:            $this->audit->log('product.cloned', 'Kloniran artikal '.$source->sku.' kao '.$clone->sku, $clone, after: $this->snapshot($clone), metadata: ['source_product_id' => $source->id, 'options' => $options]);
209:            return $clone;
213:    public function regenerateName(Product $product, User $user): Product
228:            $name = $this->generateProductName($data, $specs, $details);
232:            $this->audit->log('product.name.regenerated', 'Regenerisan naziv artikla '.$locked->sku, $locked, $before, $this->snapshot($locked));
241:        $this->audit->log('product.archived', 'Arhiviran artikal '.$product->sku, $product, $before, $this->snapshot($product));
248:        $this->audit->log('product.restored', 'Vraćen artikal '.$product->sku, $product, $before, $this->snapshot($product));
273:    private function shouldGenerateName(array $data): bool
275:        if (!empty($data['regenerate_name'])) return true;
281:    private function generateProductName(array $data, array $specs, array $details): string
283:        return $this->templates->generateName($this->typeFromData($data), $data, $specs, $details);
296:    private function generateSku(array $data, ?int $ignoreId = null): string
299:        if (!empty($data['brand_id'])) $parts[] = (string) Brand::query()->whereKey($data['brand_id'])->value('name');
319:        return $this->skuGenerator->generate($parts, static fn (string $candidate): bool => Product::query()->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists());
331:    private function uniqueCloneName(string $name): string
390:    private function cloneWarrantyRules(Product $source, Product $clone, User $user): array
395:            $copy->product_id = $clone->id;
396:            $copy->name = mb_substr($rule->name.' — '.$clone->sku, 0, 190);
406:    private function cloneVariants(Product $source, Product $clone, User $user, array $warrantyRuleMap, bool $copyImages): void
408:        foreach ($source->variants as $sourceVariant) {
409:            $sku = $this->skuGenerator->generate(
410:                [$clone->sku, $sourceVariant->name],
411:                static fn (string $candidate): bool => Product::query()->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])->exists()
412:                    || ProductVariant::query()->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])->exists(),
415:            if ($sourceVariant->warranty_rule_id !== null) {
416:                $sourceRuleId = (int) $sourceVariant->warranty_rule_id;
420:            $variant = ProductVariant::query()->create([
421:                'product_id' => $clone->id,
422:                'sku' => $sku,
423:                'name' => $sourceVariant->name,
424:                'price_amount' => $sourceVariant->price_amount,
425:                'price_currency' => $sourceVariant->price_currency,
426:                'purchase_price_rsd' => $sourceVariant->purchase_price_rsd,
427:                'manual_commission_eur' => $sourceVariant->manual_commission_eur,
429:                'low_stock_threshold' => $sourceVariant->low_stock_threshold,
431:                'is_default' => (bool) $sourceVariant->is_default,
433:                'sort_order' => $sourceVariant->sort_order,
437:            foreach ($sourceVariant->specificationValues as $value) {
438:                DB::table('product_variant_spec_values')->insert([
439:                    'product_variant_id' => $variant->id,
450:            if ($copyImages) $this->images->cloneVariantImages($sourceVariant, $variant);
460:            'id','sku','name','slug','product_type_id','brand_id','product_line_id','model_name','price_amount','price_currency','purchase_price_rsd',
462:            'completeness_percent','name_is_manual','source_product_id','variants_enabled','default_variant_id',
463:        ]) + ['category_ids' => $fresh->categories->pluck('id')->map(fn ($id) => (int) $id)->all()];
SOURCE_FILE=app/Http/Requests/ProductRequest.php|SHA256=86330c4aa65fbd09eb04f4fecd52e0a262bdf7cfc900bcf96095319e7d978fce
7:use App\Models\Category;
41:            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
44:            'sku' => [$productId ? 'required' : 'nullable', 'string', 'max:100', 'regex:#^[A-Z0-9._/-]+$#', Rule::unique('products', 'sku')->ignore($productId)],
45:            'regenerate_sku' => ['nullable', 'boolean'],
46:            'regenerate_name' => ['nullable', 'boolean'],
57:            'category_ids' => ['array', 'max:1'],
58:            'category_ids.*' => ['integer', 'exists:categories,id'],
82:            if ($product && (bool) $product->variants_enabled) {
91:            $brandId = $this->input('brand_id');
94:                $lineBrand = ProductLine::query()->whereKey((int) $lineId)->value('brand_id');
95:                if ($lineBrand !== null && (int) $lineBrand !== (int) $brandId) {
117:            if ($type !== null && $type->category_id === null) {
118:                $validator->errors()->add('product_type_id', 'Izabrani tip nema automatsku sistemsku kategoriju. Pokreni catalog settings repair ili ponovo sačuvaj tip.');
121:            if (trim((string) $this->input('name')) === '' && !$this->boolean('regenerate_name') && !(bool) ($type?->auto_name_enabled ?? false)) {
238:        $categoryId = $this->resolveCategoryId($type);
241:            'sku' => mb_strtoupper(trim((string) $this->input('sku'))),
244:            'brand_id' => $this->filled('brand_id') ? $this->integer('brand_id') : null,
248:            'category_ids' => $categoryId !== null ? [$categoryId] : [],
255:    private function resolveCategoryId(?ProductType $type): ?int
258:        if ($type->category_id !== null) return (int) $type->category_id;
261:        $categoryId = $slug !== '' ? Category::query()->where('status', 'active')->where('slug', $slug)->value('id') : null;
262:        if ($categoryId === null) {
263:            $categoryId = Category::query()->where('status', 'active')->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $type->name))])->value('id');
265:        return $categoryId !== null ? (int) $categoryId : null;
SOURCE_FILE=app/Http/Requests/ProductVariantRequest.php|SHA256=841b65ed115fde3ebd1a150ecd74f6bddc742d004e4236413f79077522cf4a84
8:use App\Models\ProductVariant;
16:final class ProductVariantRequest extends FormRequest
30:        $variant = $this->route('variant');
31:        $variantId = $variant instanceof ProductVariant ? $variant->id : null;
33:            'sku' => ['required', 'string', 'max:100', 'regex:#^[A-Z0-9._/-]+$#', Rule::unique('product_variants', 'sku')->ignore($variantId), Rule::unique('products', 'sku')],
39:            'stock_quantity' => [$variantId ? 'nullable' : 'required', 'integer', 'min:0', 'max:1000000'],
179:            'sku' => mb_strtoupper(trim((string) $this->input('sku'))),
SOURCE_FILE=app/Http/Controllers/Admin/ProductController.php|SHA256=10fbf74afbc2e8ce3f67b6e252b09e6e0a608dc31e96826d61dab1460543c438
9:use App\Models\Brand;
47:                $nested->where('name', 'like', $like)->orWhere('sku', 'like', $like);
110:    public function cloneForm(Product $product): View
113:        $product->load(['brand', 'line', 'type', 'categories', 'specificationValues', 'images', 'warrantyRules', 'variants.specificationValues', 'variants.images']);
114:        return view('admin.products.clone', ['product' => $product]);
117:    public function cloneStore(Product $product, Request $request, ProductAdminService $service): RedirectResponse
129:            'copy_variants' => ['nullable', 'boolean'],
130:            'regenerate_name' => ['nullable', 'boolean'],
132:        $clone = $service->clone($product, $data, $request->user());
133:        return redirect()->route('admin.products.edit', $clone)->with('status', 'Artikal je kloniran. Novi SKU je '.$clone->sku.'.');
136:    public function regenerateName(Product $product, Request $request, ProductAdminService $service): RedirectResponse
139:        $service->regenerateName($product, $request->user());
147:            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
150:            'sku' => ['nullable', 'string', 'max:100'],
155:        return response()->json(['name' => $templates->generateName($type, $data, (array) ($data['specs'] ?? []), (array) ($data['spec_details'] ?? []))]);
180:        if (!hash_equals((string) $product->sku, trim((string) $data['confirmation']))) {
182:                'confirmation' => 'Za trajno brisanje upiši tačnu šifru artikla: '.$product->sku.'.',
239:                'category',
250:            'brands' => Brand::query()->where('status', 'active')->orderBy('name')->get(),
SOURCE_FILE=app/Http/Controllers/Admin/ProductVariantController.php|SHA256=71e00452deeb5926adf95e4c110fb1e44cf14feb012ca4bc2d75914f68be101f
8:use App\Http\Requests\ProductVariantRequest;
11:use App\Models\ProductVariant;
15:use App\Services\ProductVariantService;
21:final class ProductVariantController extends Controller
30:            'variants.specificationValues.field',
31:            'variants.images',
32:            'variants.warrantyRule',
34:        return view('admin.products.variants', [
45:    public function store(ProductVariantRequest $request, Product $product, ProductVariantService $service): RedirectResponse
48:        $variant = $service->create($product, $request->validated(), $request->user());
49:        return redirect()->route('admin.products.variants.index', $product)->with('status', 'Varijanta '.$variant->sku.' je kreirana.');
52:    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
55:        abort_unless((int) $variant->product_id === (int) $product->id, 404);
56:        $service->update($product, $variant, $request->validated(), $request->user());
60:    public function adjust(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
63:        abort_unless((int) $variant->product_id === (int) $product->id, 404);
69:        $service->adjustStock($product, $variant, (int) $data['quantity_change'], (string) $data['note'], (string) $data['idempotency_key'], $request->user());
73:    public function setDefault(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
76:        abort_unless((int) $variant->product_id === (int) $product->id, 404);
77:        $service->setDefault($product, $variant, $request->user());
81:    public function archive(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
84:        abort_unless((int) $variant->product_id === (int) $product->id, 404);
85:        $service->archive($product, $variant, $request->user());
89:    public function images(Request $request, Product $product, ProductVariant $variant, ProductImageService $images): RedirectResponse
92:        abort_unless((int) $variant->product_id === (int) $product->id, 404);
94:        $count = $images->uploadVariant($product, $variant, $data['images']);
98:    public function deleteImage(Product $product, ProductVariant $variant, ProductImage $image, ProductImageService $images): RedirectResponse
101:        abort_unless((int) $variant->product_id === (int) $product->id && (int) $image->product_variant_id === (int) $variant->id, 404);
SOURCE_FILE=app/Services/ProductTemplateService.php|SHA256=95da31ea5d47e75f60a5c4b67b173341031f9ee0f87dabed334902d2b2516261
7:use App\Models\Brand;
16:    public const DEFAULT_NAME_TEMPLATE = '{brand} {line} {model} {cpu_family} {cpu_detail} {ram} {storage}';
48:                'brand' => !empty($data['brand_id']),
51:                'categories' => !empty($data['category_ids']),
80:    public function generateName(?ProductType $type, array $data, array $specs, array $details = []): string
86:            $tokens = ['{brand}', '{line}', '{model}'];
95:            'brand' => !empty($data['brand_id']) ? (string) Brand::query()->whereKey((int) $data['brand_id'])->value('name') : '',
99:            'sku' => trim((string) ($data['sku'] ?? '')),
127:        $base = ['{brand}', '{line}', '{model}', '{type}', '{sku}'];
142:        if (is_array($raw)) return array_values(array_intersect($raw, ['brand', 'line', 'model', 'categories', 'description', 'price']));
144:        return is_array($decoded) ? array_values(array_intersect($decoded, ['brand', 'line', 'model', 'categories', 'description', 'price'])) : [];
150:            'brand' => 'Brend',
SKU_RELATED_SOURCE_FILE_COUNT=87
CURRENT_SKU_SOURCE_AUTHORITY_AUDIT=PASS

============================================================
2. DATABASE SKU SHAPE + DEPENDENCY AUDIT
============================================================
No syntax errors detected in /tmp/ald1n-short-sku-audit.UFmr4V/db-audit.php
DB_DRIVER=mysql
DB_NAME=icaffeco_lrvl
PRODUCTS_TABLE_PRESENT=YES
PRODUCTS_SKU_COLUMN_PRESENT=YES
PRODUCT_SKU_INDEX_JSON=[{"key":"products_sku_unique","non_unique":0,"seq":1}]
PRODUCT_SKU_UNIQUE_INDEX=YES
PRODUCT_COLUMNS_JSON=["id","product_type_id","brand_id","product_line_id","model_name","sku","name","slug","price_amount","price_currency","manual_commission_eur","description","notes","stock_quantity","low_stock_threshold","status","created_by","updated_by","created_at","updated_at","deleted_at","legacy_checksum","legacy_synced_at","locally_modified_at","completeness_percent","name_is_manual","source_product_id","variants_enabled","default_variant_id","purchase_price_rsd"]
PRODUCT_SKU_PREVIEW_JSON={"id":1,"sku":"LATITUDE-551P","sku_length":13,"brand":"Dell","category":"","prefix_source":"brand","prefix_preview":"DELL","illustrative_id_based_preview":"DELL-000001"}
PRODUCT_SKU_PREVIEW_JSON={"id":2,"sku":"HP-630-G10","sku_length":10,"brand":"HP","category":"","prefix_source":"brand","prefix_preview":"HP","illustrative_id_based_preview":"HP-000002"}
PRODUCT_SKU_PREVIEW_JSON={"id":3,"sku":"THINPAD-T15-GEN2","sku_length":16,"brand":"Lenovo","category":"","prefix_source":"brand","prefix_preview":"LENOVO","illustrative_id_based_preview":"LENOVO-000003"}
PRODUCT_SKU_PREVIEW_JSON={"id":4,"sku":"SSD-256GB-M2","sku_length":12,"brand":"Samsung","category":"","prefix_source":"brand","prefix_preview":"SAMSUNG","illustrative_id_based_preview":"SAMSUNG-000004"}
PRODUCT_SKU_PREVIEW_JSON={"id":5,"sku":"T490S-TOUCHSCREEN","sku_length":17,"brand":"Lenovo","category":"","prefix_source":"brand","prefix_preview":"LENOVO","illustrative_id_based_preview":"LENOVO-000005"}
PRODUCT_SKU_PREVIEW_JSON={"id":6,"sku":"ASUS-TUF-GAMING","sku_length":15,"brand":"ASUS","category":"","prefix_source":"brand","prefix_preview":"ASUS","illustrative_id_based_preview":"ASUS-000006"}
PRODUCT_SKU_PREVIEW_JSON={"id":7,"sku":"DELL-LATITUDE-5440","sku_length":18,"brand":"Dell","category":"","prefix_source":"brand","prefix_preview":"DELL","illustrative_id_based_preview":"DELL-000007"}
PRODUCT_SKU_PREVIEW_JSON={"id":8,"sku":"SAMSUNG-RAM-MEMORIJA-DDR4-8GB-SKHYNIX-MICRON-POLOVNO","sku_length":52,"brand":"Samsung","category":"","prefix_source":"brand","prefix_preview":"SAMSUNG","illustrative_id_based_preview":"SAMSUNG-000008"}
PRODUCT_SKU_PREVIEW_JSON={"id":9,"sku":"HP-ELITEBOOK-845-G8-RYZEN-5-PRO-16-0000GB-256-LAPTOP","sku_length":52,"brand":"HP","category":"","prefix_source":"brand","prefix_preview":"HP","illustrative_id_based_preview":"HP-000009"}
PRODUCT_SKU_PREVIEW_JSON={"id":10,"sku":"DELL-LATITUDE-3540-I5-1335U-16GB-256GB-INTEL-CORE-16-0000GB-256-LAPTOP","sku_length":70,"brand":"Dell","category":"","prefix_source":"brand","prefix_preview":"DELL","illustrative_id_based_preview":"DELL-000010"}
PRODUCT_SKU_PREVIEW_JSON={"id":11,"sku":"DELL-LATITUDE-INTEL-CORE-I7-1185G7-16GB-512GB-LAPTOP","sku_length":52,"brand":"Dell","category":"","prefix_source":"brand","prefix_preview":"DELL","illustrative_id_based_preview":"DELL-000011"}
PRODUCT_SKU_PREVIEW_JSON={"id":12,"sku":"DELL-LATITUDE-7410-INTEL-CORE-I7-10610U-16GB-256GB-LAPTOP","sku_length":57,"brand":"Dell","category":"","prefix_source":"brand","prefix_preview":"DELL","illustrative_id_based_preview":"DELL-000012"}
PRODUCT_SKU_PREVIEW_JSON={"id":13,"sku":"DELL-LATITUDE-I7-8665U-16GB-256GB-TOUCHSCREEN-INTEL-CORE-7-LAPTOP","sku_length":65,"brand":"Dell","category":"","prefix_source":"brand","prefix_preview":"DELL","illustrative_id_based_preview":"DELL-000013"}
PRODUCT_SKU_PREVIEW_JSON={"id":14,"sku":"7950X-16GB-GAINWARD-NVIDIA-GEFORCE-RTX-3090-AMD-RYZEN-9-POLOVNO-DESKTOP-RACUNAR","sku_length":79,"brand":"","category":"","prefix_source":"fallback","prefix_preview":"ARTIKAL","illustrative_id_based_preview":"ARTIKAL-000014"}
PRODUCT_SKU_PREVIEW_JSON={"id":15,"sku":"RYZEN-7-5700-16GB-512GB-GIGABYTE-NVIDIA-RTX-3060TI-AMD-POLOVNO-DESKTOP-RACUNAR","sku_length":78,"brand":"","category":"","prefix_source":"fallback","prefix_preview":"ARTIKAL","illustrative_id_based_preview":"ARTIKAL-000015"}
PRODUCT_SKU_PREVIEW_JSON={"id":16,"sku":"LENOVO-THINKPAD-I7-8565U-8GB-256GB-INTEL-CORE-7-LAPTOP","sku_length":54,"brand":"Lenovo","category":"","prefix_source":"brand","prefix_preview":"LENOVO","illustrative_id_based_preview":"LENOVO-000016"}
PRODUCT_SKU_PREVIEW_JSON={"id":17,"sku":"8GB-GAINWARD-NVIDIA-GEFORCE-RTX-3060TI-POLOVNO-GRAFICKA-KARTA","sku_length":61,"brand":"","category":"","prefix_source":"fallback","prefix_preview":"ARTIKAL","illustrative_id_based_preview":"ARTIKAL-000017"}
PRODUCT_SKU_PREVIEW_JSON={"id":18,"sku":"LENOVO-THINKPAD-16GB-256GB-INTEL-CORE-I5-8265U-LAPTOP","sku_length":53,"brand":"Lenovo","category":"","prefix_source":"brand","prefix_preview":"LENOVO","illustrative_id_based_preview":"LENOVO-000018"}
PRODUCT_SKU_PREVIEW_JSON={"id":21,"sku":"HP-DESKTOP-RACUNAR","sku_length":18,"brand":"HP","category":"","prefix_source":"brand","prefix_preview":"HP","illustrative_id_based_preview":"HP-000021"}
PRODUCT_SKU_PREVIEW_JSON={"id":22,"sku":"HP-ELITEBOOK-INTEL-CORE-I5-1145G7-16GB-256GB-LAPTOP","sku_length":51,"brand":"HP","category":"","prefix_source":"brand","prefix_preview":"HP","illustrative_id_based_preview":"HP-000022"}
PRODUCT_SKU_PREVIEW_JSON={"id":23,"sku":"HP-ELITEBOOK-630-G8-INTEL-CORE-I5-1145G7-16GB-256GB-KOPIJA-16-0000GB-POLOVNO-LAPTOP","sku_length":83,"brand":"HP","category":"","prefix_source":"brand","prefix_preview":"HP","illustrative_id_based_preview":"HP-000023"}
PRODUCT_SKU_PREVIEW_JSON={"id":24,"sku":"LENOVO-THINKPAD-INTEL-CORE-I5-7200U-8GB-256GB-POLOVNO-LAPTOP","sku_length":60,"brand":"Lenovo","category":"","prefix_source":"brand","prefix_preview":"LENOVO","illustrative_id_based_preview":"LENOVO-000024"}
PRODUCT_SKU_PREVIEW_JSON={"id":25,"sku":"DELL-LATITUDE-INTEL-CORE-I5-1145G7-16GB-256GB-KAO-NOVO-LAPTOP","sku_length":61,"brand":"Dell","category":"","prefix_source":"brand","prefix_preview":"DELL","illustrative_id_based_preview":"DELL-000025"}
PRODUCT_SKU_PREVIEW_JSON={"id":26,"sku":"GAMER-INTEL-CORE-I5-8400-16GB-NVME-SSD-256-GB-HDD-2048-ASUS-TUF-GAMING-NVIDIA-GTX-1660-SUPER-KAO-NOV","sku_length":100,"brand":"","category":"","prefix_source":"fallback","prefix_preview":"ARTIKAL","illustrative_id_based_preview":"ARTIKAL-000026"}
PRODUCT_SKU_PREVIEW_JSON={"id":30,"sku":"DELL-LATITUDE-E5570-INTEL-CORE-I5-6300U-16GB-256GB-POLOVNO-LAPTOP","sku_length":65,"brand":"Dell","category":"","prefix_source":"brand","prefix_preview":"DELL","illustrative_id_based_preview":"DELL-000030"}
PRODUCT_SKU_PREVIEW_JSON={"id":31,"sku":"DELL-LATITUDE-5410-INTEL-CORE-I5-10210U-256GB-POLOVNO-DDR4-LAPTOP","sku_length":65,"brand":"Dell","category":"","prefix_source":"brand","prefix_preview":"DELL","illustrative_id_based_preview":"DELL-000031"}
PRODUCT_COUNT=26
SKU_LENGTH_MIN=10
SKU_LENGTH_MAX=100
SKU_LENGTH_AVG=49.12
SKU_LENGTH_GT20=18
SKU_LENGTH_GT30=18
SKU_LENGTH_GT40=18
DUPLICATE_PRODUCT_SKU_COUNT=0
SKU_DATABASE_COLUMN_DEPENDENCY_COUNT=11
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"after_sales_action_items","COLUMN_NAME":"sku_snapshot","DATA_TYPE":"varchar","IS_NULLABLE":"YES"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"after_sales_case_items","COLUMN_NAME":"sku_snapshot","DATA_TYPE":"varchar","IS_NULLABLE":"YES"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"field_work_order_parts","COLUMN_NAME":"part_sku_snapshot","DATA_TYPE":"varchar","IS_NULLABLE":"NO"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"inventory_count_items","COLUMN_NAME":"product_sku","DATA_TYPE":"varchar","IS_NULLABLE":"NO"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"order_items","COLUMN_NAME":"product_sku","DATA_TYPE":"varchar","IS_NULLABLE":"NO"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"order_items","COLUMN_NAME":"variant_sku_snapshot","DATA_TYPE":"varchar","IS_NULLABLE":"YES"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"products","COLUMN_NAME":"sku","DATA_TYPE":"varchar","IS_NULLABLE":"NO"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"product_variants","COLUMN_NAME":"sku","DATA_TYPE":"varchar","IS_NULLABLE":"NO"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"product_warranties","COLUMN_NAME":"product_sku_snapshot","DATA_TYPE":"varchar","IS_NULLABLE":"YES"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"service_parts","COLUMN_NAME":"sku","DATA_TYPE":"varchar","IS_NULLABLE":"NO"}
SKU_DATABASE_COLUMN_JSON={"TABLE_NAME":"stock_receipt_items","COLUMN_NAME":"product_sku","DATA_TYPE":"varchar","IS_NULLABLE":"NO"}
PRODUCT_VARIANT_SKU_PRESENT=YES
PRODUCT_VARIANT_COUNT=0
PRODUCT_VARIANT_SKU_MAX_LENGTH=0
DATABASE_SKU_AUDIT_RUNTIME=PASS
DATABASE_SKU_AUDIT_EXIT_CODE=0
DATABASE_SKU_SHAPE_AND_DEPENDENCY_AUDIT=PASS

============================================================
3. CODE REFERENCE + CREATE/CLONE/VARIANT PATH AUDIT
============================================================
--- SEARCH: ProductSkuGenerator ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductSkuGenerator.php:7:final class ProductSkuGenerator
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:25:        private readonly ProductSkuGenerator $skuGenerator,
--- SEARCH: ->sku ---
--- SEARCH: sku = ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:448:                $variantSku = $this->text($item, 'variant_sku_snapshot');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:66:        $sku = trim((string) $product->sku);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:70:                'CASE WHEN name = ? THEN 0 WHEN sku = ? THEN 1 WHEN name LIKE ? THEN 2 WHEN sku LIKE ? THEN 3 ELSE 4 END',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/BusinessDocumentPdfService.php:193:        $sku = trim($sku);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/BusinessDocumentPdfService.php:194:        if ($sku === '') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAnnouncementService.php:137:        $sku = trim((string) $product->sku);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:409:            $sku = $this->skuGenerator->generate(
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:77:            $duplicateSku = DB::table('product_variants')->selectRaw('LOWER(sku) normalized, COUNT(*) total')->whereNull('deleted_at')->groupByRaw('LOWER(sku)')->havingRaw('COUNT(*) > 1')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductVariantsDoctorCommand.php:78:            $crossSku = DB::table('product_variants as variants')->join('products', DB::raw('LOWER(products.sku)'), '=', DB::raw('LOWER(variants.sku)'))->whereNull('variants.deleted_at')->count();
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/order-emails.blade.php:134:                    $productSku = is_array($row->metadata_json) ? ($row->metadata_json['product_sku'] ?? null) : null;
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:25:        $productSku = trim((string) ($metadata['product_sku'] ?? ''));
--- SEARCH: sku\] ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:220:                'metadata_json' => ['order_item_id' => $orderItem->id, 'source_system' => 'laravel', 'variant_sku' => $variant?->sku],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:428:            'metadata_json' => ['service_part_sku' => $part->sku],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:217:                    'action_url' => route('admin.inventory.index', ['q' => $product->sku]),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/StockAdjustmentController.php:26:        return redirect()->route('admin.stock.index', ['q' => $product->sku])
--- SEARCH: generateSku ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:46:            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:98:            if (!empty($data['regenerate_sku'])) $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails], $locked->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:188:            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $details]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:296:    private function generateSku(array $data, ?int $ignoreId = null): string
--- SEARCH: generate.*sku ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:46:            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:52:            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:98:            if (!empty($data['regenerate_sku'])) $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails], $locked->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:103:            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:188:            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $details]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:232:            $this->audit->log('product.name.regenerated', 'Regenerisan naziv artikla '.$locked->sku, $locked, $before, $this->snapshot($locked));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:296:    private function generateSku(array $data, ?int $ignoreId = null): string
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:319:        return $this->skuGenerator->generate($parts, static fn (string $candidate): bool => Product::query()->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists());
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:45:            'regenerate_sku' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:157:                            <label class="check-card"><input type="checkbox" name="regenerate_sku" value="1" @checked(old('regenerate_sku'))><span>Automatski generiši novi SKU</span></label>
CREATE_PATH_SKU_SIGNAL_FILE_COUNT=3
CLONE_SKU_SIGNAL_COUNT=19
VARIANT_SKU_SIGNAL_COUNT=59
CODE_REFERENCE_AUDIT=PASS

============================================================
4. CONCURRENCY + SEQUENCE DESIGN READINESS
============================================================
GENERATOR_HAS_EXPLICIT_LOCKING=NO
GENERATOR_HAS_TRANSACTION=NO
GENERATOR_HAS_MAX_OR_COUNT_BASED_NUMBERING=YES
RECOMMENDED_EXISTING_SKU_POLICY=IMMUTABLE_PRESERVE
RECOMMENDED_FUTURE_SKU_PREFIX_POLICY=BRAND_ELSE_CATEGORY_ELSE_ARTIKAL
RECOMMENDED_FUTURE_SEQUENCE_POLICY=GLOBAL_CONCURRENCY_SAFE_SEQUENCE_NOT_MAX_PLUS_ONE
RECOMMENDED_PREFIX_NORMALIZATION=UPPERCASE_ASCII_ALNUM_HYPHEN
RECOMMENDED_PREFIX_MAX_LENGTH=14
RECOMMENDED_SEQUENCE_WIDTH=6
RECOMMENDED_EXAMPLE=HP-000027
RECOMMENDED_NO_BRAND_EXAMPLE=LAPTOP-000028
VARIANT_SKU_POLICY=DO_NOT_CHANGE_UNTIL_VARIANT_GENERATOR_CONTRACT_IS_AUDITED
CONCURRENCY_AND_SEQUENCE_DESIGN_AUDIT=PASS

============================================================
5. SAFETY + FINAL DECISION
============================================================
OPENAPI_SHA256=caf7a4d0238e4c085dadb5dcee68b24bbbfb67f16382eb31cf1c64c2a93cbc37
CURRENT_TARGETED_GIT_DIFF_CHECK=PASS
EXISTING_SKU_REWRITE_RECOMMENDED=NO
EXISTING_SKU_DEPENDENCY_COLUMN_COUNT=11
PRODUCT_SKU_UNIQUE_INDEX=YES
PRODUCT_VARIANT_SKU_PRESENT=YES
APPLICATION_SOURCE_WRITES=0
DATABASE_BUSINESS_DATA_WRITES=0
MIGRATIONS_RUN=NO
ROUTES_CHANGED=NO
OPENAPI_CHANGED=NO
MOBILE_SOURCE_CHANGED=0
EAS_BUILD=NO
CMS_SHORT_SKU_GENERATOR_V2_AUDIT_BATCH1=PASS
NEXT_ACTION=GENERATE_SHORT_SKU_GENERATOR_V2_IMPLEMENTATION_PLAN_FROM_LIVE_GENERATOR_AND_SEQUENCE_FINDINGS
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-SHORT-SKU-GENERATOR-V2-AUDIT-BATCH1-20260817-121212.md

PASS: CMS SHORT SKU GENERATOR V2 AUDIT BATCH 1 COMPLETE

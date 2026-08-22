============================================================
078 - MOBILE v0.9.0 SUPERADMIN INVENTORY VALUE KPI TOPOLOGY AUDIT
============================================================
DATE=Sat Aug 22 12:18:05 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
PURPOSE=READ_ONLY_AUDIT_FOR_TWO_SUPERADMIN_HOME_KPIS_PURCHASE_VALUE_AND_SALE_VALUE
TARGET_KPI_1=Lager - nabavna vrednost
TARGET_KPI_2=Lager - prodajna vrednost
VISIBILITY=SUPERADMIN_ONLY_UI_AND_API
SOURCE_CODE_CHANGES=NO
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=NO
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO

============================================================
0. PREFLIGHT + 077 PREREQUISITE
============================================================
PREREQUISITE_077=PASS
PREREQUISITE_077_REPORT=/home/icaffeco/ald1n-project/docs/operations/077-MOBILE-V0.9.0-GLOBAL-BRAND-MANAGER-LARAVEL-MOBILE-IMPLEMENTATION-BATCH3-20260822-120340.md
PHP_VERSION=8.4.24
NODE_VERSION=v22.23.2

============================================================
1. SOURCE TOPOLOGY - READ ONLY
============================================================
MOBILE_HOME_FILE=/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx
--- MOBILE HOME KPI / ADMIN SIGNALS ---
10:import { hasAdminAccess } from '@/features/admin/admin-access';
11:import { adminQueryKeys } from '@/features/admin/admin-query-keys';
13:  apiAdminReports,
14:  type AdminReportTrendPoint,
15:} from '@/features/admin/reports-admin-api';
35:  const adminAllowed = hasAdminAccess({
41:    queryKey: adminQueryKeys.managementReport(HOME_REPORT_PARAMS),
42:    queryFn: () => apiAdminReports.management(HOME_REPORT_PARAMS),
50:    can('catalog.manage_products') ? { title: 'Dodaj artikal', copy: 'Kreiraj novi artikal', glyph: 'catalog' as const, route: '/admin/catalog/create' as const } : null,
51:    can('commissions.manage') ? { title: 'Provizije', copy: 'Pregledaj i obradi provizije', glyph: 'orders' as const, route: '/admin/commissions' as const } : can('commissions.view_own') ? { title: 'Moje provizije', copy: 'Pregledaj obračun i status isplate', glyph: 'orders' as const, route: '/commissions' as const } : null,
54:    adminAllowed ? { title: 'Administracija', copy: 'Otvori administratorski radni prostor', glyph: 'check' as const, route: '/admin' as const } : null,
60:    route: '/catalog' | '/admin/catalog/create' | '/admin/commissions' | '/commissions' | '/cart' | '/orders' | '/admin' | '/notifications';
107:              onPress={() => router.push('/admin/reports')}
126:              <View style={styles.kpiGrid}>
153:              <SalesPulse points={report.trend} periodLabel={report.period_label} />
170:                  value={`${report.after_sales.open} otvoreno · ${report.after_sales.overdue} preko roka`}
174:                  label="Lager"
175:                  value={`${report.inventory.items_count} stavki · ${report.inventory.slow_items} sporo obrtnih`}
247:      onPress={() => router.push('/admin/reports')}
248:      style={({ pressed }) => [styles.kpiPressable, pressed && styles.pressed]}
260:function SalesPulse({
264:  points: AdminReportTrendPoint[];
275:          <Text style={styles.sectionEyebrow}>FINANSIJSKI PULS</Text>
404:    kpiGrid: {
409:    kpiPressable: { width: '48%', flexGrow: 1 },
--- MOBILE API / ADMIN FOUNDATION SIGNALS ---
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/catalog/product-card.tsx:38:    product.stock_quantity > 5
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/catalog/product-card.tsx:40:      : product.stock_quantity > 0
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/catalog/product-card.tsx:45:    product.stock_quantity > 0
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/catalog/product-card.tsx:46:      ? `${product.stock_quantity} na stanju`
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/service-parts-admin-api.ts:14:  stock_quantity: number | null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/service-parts-admin-api.ts:105:  stock_quantity?: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-api.ts:47:    const response = await apiRequest<{ data: AdminFoundation }> ('admin/foundation');
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/inventory-admin-api.ts:19:  stock_quantity: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/catalog-admin-api.ts:28:  purchase_price_rsd: number | null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/catalog-admin-api.ts:29:  stock_quantity: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/catalog-admin-api.ts:88:  purchase_price_rsd: number | null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/catalog-admin-api.ts:90:  stock_quantity: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/catalog-admin-api.ts:123:    stock_quantity: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/catalog-admin-api.ts:139:  sale_price_rsd: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/catalog-admin-api.ts:153:    sale_price_rsd: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/catalog-admin-api.ts:154:    stock_quantity_after: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/field-operations-admin-api.ts:47:  stock_quantity: number | null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:153:  stock_quantity: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:765:    stock_quantity: 0;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:788:  purchase_price_rsd?: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:792:  stock_quantity: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:38:  const stock = product.stock_quantity;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1053:        <MetricCard label="Vrednost lagera" value={formatMoney(inventory.value_rsd, 'RSD')} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1059:      {inventory.top_value.slice(0, 8).map((item) => (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:67:    stock: String(part.stock_quantity ?? 0),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:122:      ...(includeOpeningStock ? { stock_quantity: decimal(draft.stock, 'Početno stanje') } : {}),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:234:          <Text style={styles.meta}>Fizičko stanje: {quantity(editing.stock_quantity)} {editing.unit ?? ''}. Ova forma namerno ne menja fizičko stanje.</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:249:          <Text style={styles.meta}>Trenutno stanje {quantity(adjusting.stock_quantity)} · rezervisano {quantity(adjusting.reserved_quantity)} · raspoloživo {quantity(adjusting.available_quantity)} {adjusting.unit ?? ''}.</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:271:                <Text style={styles.meta}>Stanje: {quantity(part.stock_quantity)} · Rezervisano: {quantity(part.reserved_quantity)} · Raspoloživo: {quantity(part.available_quantity)}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:83:        <Text style={{ ...typography.small, color: theme.muted }}>Stanje {numberLabel(product.stock_quantity)} · Prag {numberLabel(product.low_stock_threshold)}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:218:      : [...current, { product, counted: String(product.stock_quantity), note: '' }]);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:384:              <Text style={styles.meta}>Sistemsko stanje: {numberLabel(item.product.stock_quantity)}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:399:          <Text style={styles.meta}>Trenutno stanje: {numberLabel(adjust.product.stock_quantity)}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:422:                <Text style={styles.stock}>{numberLabel(product.stock_quantity)}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/[id].tsx:187:      label: `${part.sku} · ${part.name} · stanje ${formatNumber(part.stock_quantity, 3)}`,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:86:            <Text style={styles.valuationValue}>{moneyRsd(inventoryValuation.purchase_value_rsd)}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:91:            <Text style={styles.valuationValue}>{moneyRsd(inventoryValuation.sale_value_rsd)}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:106:        message: `${response.data.order_number} · preostali lager: ${response.data.stock_quantity_after}`,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:146:    else if (qty > options.product.stock_quantity) nextErrors.quantity = 'Količina je veća od raspoloživog lagera.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:147:    if (price === null) nextErrors.sale_price_rsd = 'Prodajna cena mora biti veća od nule.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:149:      nextErrors.sale_price_rsd = `Cena po komadu ne sme biti veća od ${moneyRsd(options.product.catalog_unit_price_rsd)}.`;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:163:      sale_price_rsd: price,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:196:        <Text style={styles.meta}>Raspoloživ lager: {options.product.stock_quantity}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:250:          error={errors.sale_price_rsd}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:285:          ? `Evidentira se ${pendingPayload.quantity} kom. po ${moneyRsd(pendingPayload.sale_price_rsd)}. Lager će odmah biti umanjen.`
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:155:    setPurchasePriceRsd(detail.purchase_price_rsd === null ? '' : String(detail.purchase_price_rsd));
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:159:    setStockQuantity(String(detail.stock_quantity));
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:320:    if (purchasePriceRsd.trim() && purchase === null) nextErrors.purchase_price_rsd = 'Unesi ispravnu nabavnu cenu.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:323:    if (stock === null) nextErrors.stock_quantity = 'Lager mora biti ceo broj 0 ili veći.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:386:      purchase_price_rsd: purchase ?? undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:390:      stock_quantity: stock,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:562:        <TextField label="Nabavna cena (RSD)" value={purchasePriceRsd} onChangeText={setPurchasePriceRsd} keyboardType="decimal-pad" disabled={product.is_archived} error={errors.purchase_price_rsd} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:564:        <TextField label="Lager" value={stockQuantity} onChangeText={setStockQuantity} keyboardType="number-pad" disabled={product.is_archived || !product.capabilities.stock_adjust} error={errors.stock_quantity} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:351:    if (purchasePriceRsd.trim() && purchase === null) nextErrors.purchase_price_rsd = 'Unesi ispravnu nabavnu cenu.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:354:    if (stock === null) nextErrors.stock_quantity = 'Lager mora biti ceo broj 0 ili veći.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:431:      purchase_price_rsd: purchase ?? undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:435:      stock_quantity: stock,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:700:          error={errors.purchase_price_rsd}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:755:          error={errors.stock_quantity}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/index.tsx:47:        <Text style={styles.meta}>Lager: {product.stock_quantity}</Text>
--- CMS DASHBOARD / KPI / INVENTORY SIGNALS ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:36:                'order' => 'Postprodajni slučaj može se otvoriti samo za isporučenu ili kompletiranu sopstvenu porudžbinu.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:45:                    throw ValidationException::withMessages(['order' => 'Porudžbina više nije dostupna za postprodajni zahtev.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:109:                    'note' => 'Postprodajni slučaj je otvoren.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:115:                $this->audit->log('after_sales.created', 'Otvoren postprodajni slučaj '.$case->case_number, $case, null, $case->toArray(), ['order_id' => $lockedOrder->id], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:213:                        throw ValidationException::withMessages(['status' => 'Za izabrano rešenje prvo evidentirajte i izvršite odgovarajuću postprodajnu radnju.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:251:            $this->audit->log('after_sales.updated', 'Ažuriran postprodajni slučaj '.$locked->case_number, $locked, $before, $locked->fresh()->toArray(), null, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:371:                'event' => 'after_sales_created', 'title' => 'Novi postprodajni slučaj',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:54:            'sale_price_rsd' => round((float) ($input['sale_price_rsd'] ?? 0), 2),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:89:    /** @param array{product_id:int,buyer_name:string,buyer_phone:?string,quantity:int,sale_price_rsd:float,payment_method:string} $payload */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:113:        $salePrice = round($payload['sale_price_rsd'], 2);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:116:                'sale_price_rsd' => 'Prodajna cena mora biti veća od nule.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:128:                'quantity' => 'Nema dovoljno artikala na lageru za ovu direktnu prodaju.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:177:        if ((float) ($lockedProduct->purchase_price_rsd ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:178:            $purchaseUnit = round((float) $lockedProduct->purchase_price_rsd, 2);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:277:                'sale_price_rsd' => $salePrice,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:291:                'sale_price_rsd' => sprintf(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:307:                'sale_price_rsd' => 'EUR/RSD kurs mora biti podešen pre direktne prodaje EUR artikla.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:129:            throw ValidationException::withMessages(['amount_rsd' => 'Postprodajna radnja ne pripada izabranoj porudžbini.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:164:            'note' => 'Refundacija po postprodajnoj radnji '.$action->action_number.'. '.trim((string) $action->public_note),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:294:                $field => 'Direktna prodaja ima zaključan finansijski ledger. Refundacija se evidentira isključivo kroz odobrenu postprodajnu radnju.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/InventoryService.php:40:                    throw ValidationException::withMessages(['quantity_change' => 'Korekcija ne može spustiti lager ispod nule.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/InventoryService.php:56:                $this->audit->log('stock.adjusted', 'Korigovan lager '.$locked->sku, $locked, ['stock_quantity' => $before], ['stock_quantity' => $after], ['movement_id' => $movement->id, 'note' => trim($note)], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:581:            throw ValidationException::withMessages(['status' => 'Porudžbina nema Laravel rezervaciju lagera koja može biti vraćena.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:604:                'note' => 'Jednokratni povrat lagera za otkazanu porudžbinu '.$order->order_number,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:614:            throw ValidationException::withMessages(['order' => 'Direktna prodaja je završena poslovna evidencija. Korekcije se vode isključivo kroz postprodajni tok.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:628:            throw ValidationException::withMessages(['order' => 'Legacy porudžbine su istorijski read-only zapisi i ne mogu menjati Laravel lager.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:24:        'products' => ['id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd', 'price_amount', 'price_currency'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:238:            ->select('id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd', 'price_amount', 'price_currency', 'created_at')->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:245:                $product->purchase_price_rsd,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:278:            'slow_stock' => $positive->sortByDesc('days_since_sale')->take(20)->values()->all(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:119:                        Log::error('Automatska dopuna snapshot nabavne cene nije uspela.', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:240:        if (Schema::hasTable('products') && Schema::hasColumn('products', 'purchase_price_rsd')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:241:            $productCost = $this->positive(DB::table('products')->where('id', $productId)->value('purchase_price_rsd'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:301:            ($manual ? 'Ručna' : 'Automatska').' dopuna nabavne cene za stavku porudžbine #'.$item->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/ManagementReportPdfService.php:108:            ['Vrednost lagera', $this->money((float) ($inventory['value_rsd'] ?? 0)), 'Bez nabavne cene: '.(int) ($inventory['missing_cost_items'] ?? 0)],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/ManagementReportPdfService.php:109:            ['Spori lager', (string) ((int) ($inventory['slow_items'] ?? 0)).' stavki', 'Bez prodaje 90+ dana'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:220:        Mail::send('emails.management-report', ['delivery' => $delivery, 'summary' => $summary], function ($mail) use ($delivery, $attachments): void {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:127:                throw ValidationException::withMessages(['items' => sprintf('Nedovoljan lager za %s (%s). Dostupno: %d.', $product->name, $product->sku, $product->stock_quantity)]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:142:            $purchaseUnitRsd = $product->purchase_price_rsd !== null && (float) $product->purchase_price_rsd > 0
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:143:                ? round((float) $product->purchase_price_rsd, 2)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:182:                'note' => 'Rezervacija lagera za porudžbinu '.$orderNumber,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:116:                    'note' => 'Korekcija lagera kroz izmenu artikla.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:158:                'purchase_price_rsd' => $copyPrice ? $source->purchase_price_rsd : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:424:            'id','sku','name','slug','product_type_id','brand_id','product_line_id','model_name','price_amount','price_currency','purchase_price_rsd',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:135:                throw ValidationException::withMessages(['parts' => 'Radni nalog nema delove koji se izdaju sa lokalnog servisnog lagera.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:217:                    throw ValidationException::withMessages(['parts' => 'Servisni lager se promenio. Ponovo proverite rezervaciju delova.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:271:            if ($stockAfter < -0.0001) throw ValidationException::withMessages(['quantity_change' => 'Korekcija ne može spustiti servisni lager ispod nule.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:275:            $this->audit->log('service_part.adjusted', 'Korigovan servisni lager '.$locked->sku, $locked, ['stock_quantity' => $stockBefore], ['stock_quantity' => $stockAfter], ['movement_id' => $movement->id, 'note' => trim($note)], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:58:                throw ValidationException::withMessages(['items' => 'Jedna ili više stavki ne pripadaju ovom postprodajnom slučaju.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:117:                'Planirana postprodajna radnja '.$action->action_number,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:128:        $this->notify($action, $actor, 'Planirana postprodajna radnja', 'Radnja '.$action->action_number.' je planirana.', 'warning');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:172:            $this->audit->log('after_sales.action_started', 'Pokrenuta postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), null, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:176:        $this->notify($started, $actor, 'Postprodajna radnja je pokrenuta', 'Radnja '.$started->action_number.' je u toku.', 'info');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:226:            $this->audit->log('after_sales.action_completed', 'Izvršena postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), ['case_number' => $locked->case->case_number], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:230:        $this->notify($completed, $actor, 'Postprodajna radnja je izvršena', 'Radnja '.$completed->action_number.' je završena.', 'success');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:263:            $this->audit->log('after_sales.action_cancelled', 'Otkazana postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), null, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:267:        $this->notify($cancelled, $actor, 'Postprodajna radnja je otkazana', 'Radnja '.$cancelled->action_number.' je otkazana.', 'warning');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:278:            throw ValidationException::withMessages(['items' => 'Automatska promena lagera nije moguća jer jedna stavka nema lokalno povezan proizvod. Izaberite eksternu obradu lagera.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:285:            throw ValidationException::withMessages(['items' => 'Jedan od lokalnih proizvoda više nije dostupan za promenu lagera.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:295:                throw ValidationException::withMessages(['items' => 'Nema dovoljno lagera za zamenski artikal '.$product->sku.'. Dostupno: '.$product->stock_quantity.', potrebno: '.$quantity.'.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:313:                throw ValidationException::withMessages(['items' => 'Promena lagera bi spustila stanje '.$item->sku_snapshot.' ispod nule.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:349:            throw ValidationException::withMessages(['action_type' => 'Za izvršenje zamene ili povrata potrebna je dozvola za korekciju lagera.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:27:            'Popisi lagera' => 'inventory_count_items',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:58:                $blockers['Promene lagera'] = $count;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:478:            ['artikli', 'proizvodi', 'lager'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:494:            $add(true, 'Reklamacije', 'Otvori postprodajni centar.', route('admin.after-sales.index'), ['postprodaja', 'servis']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:496:            $add(true, 'Moje reklamacije', 'Otvori svoje postprodajne slučajeve.', route('after-sales.index'), ['postprodaja', 'servis']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:510:            'Otvori stanje i operacije lagera.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:512:            ['lager', 'stock'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:213:                    'title' => (int) $product->stock_quantity <= 0 ? 'Artikal je bez lagera' : 'Nizak lager',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:227:                            'action_label' => 'Otvori lager',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:259:                    'title' => 'Probijen rok postprodajnog slučaja',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:310:                    'title' => 'Probijen rok izvršne postprodajne radnje',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:447:                    'title' => $available <= 0 ? 'Servisni deo nije raspoloživ' : 'Nizak servisni lager',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:457:                            'url' => $alert->action_url, 'action_label' => 'Otvori servisni lager', 'icon' => 'boxes',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:714:                    'icon' => 'dashboard',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ModuleVisibilityService.php:12:        'after_sales' => ['label' => 'Reklamacije i servisi', 'description' => 'Postprodajni slučajevi, poruke i izvršne radnje.'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ModuleVisibilityService.php:14:        'service_parts' => ['label' => 'Servisni lager', 'description' => 'Servisni delovi, rezervacije i nabavka delova.'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ModuleVisibilityService.php:18:        'inventory' => ['label' => 'Napredni lager', 'description' => 'Ulazi robe, popisi i napredni lager workspace.'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:243:                'archive_reason' => 'Porudžbina ima aktivan postprodajni slučaj. Zatvori ga pre arhiviranja.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesAction.php:77:            'none' => 'Bez promene lagera',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesAction.php:80:            'restock' => 'Vraća se na raspoloživ lager',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesAction.php:82:            'scrap' => 'Otpis / ne vraća se na lager',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:18:        'price_currency', 'purchase_price_rsd', 'manual_commission_eur', 'description', 'notes', 'stock_quantity',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:27:            'purchase_price_rsd' => 'decimal:2',
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:77:                // Branding assets are optional and must never block login/dashboard rendering.
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:50:            'purchase_price_rsd' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:294:            'purchase_price_rsd' => $this->filled('purchase_price_rsd') ? str_replace(',', '.', (string) $this->input('purchase_price_rsd')) : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartController.php:49:                'value' => (float) (clone $base)->selectRaw('COALESCE(SUM(stock_quantity * average_cost_rsd),0) AS total')->value('total'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartController.php:75:        return back()->with('status', 'Servisni lager je korigovan.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DirectSaleController.php:25:            'sale_price_rsd' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:171:            return response($content, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="lager-izvestaj-'.now()->format('Ymd-His').'.csv"', 'X-Content-Type-Options' => 'nosniff']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:173:            $this->safeLog('CSV izvoz lagera nije uspeo.', $exception, $request->user());
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:174:            return response('Izvoz lagera trenutno nije dostupan. Pokrenite: php artisan app:payments-inventory-doctor --repair', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:195:            $this->safeLog('Sažetak lagera za Reports nije učitan.', $exception, $user);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:67:            $this->safeLog('Napredni lager nije mogao da se učita.', $exception, $request);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:70:                'Upit nad lagerom trenutno nije uspeo. Pokrenite payments/inventory doctor sa --repair opcijom.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:77:        abort_if($this->readinessIssues() !== [], 503, 'Napredni lager nije spreman. Pokrenite: php artisan app:payments-inventory-doctor --repair');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:100:        abort_if($this->readinessIssues() !== [], 503, 'Napredni lager nije spreman. Pokrenite: php artisan app:payments-inventory-doctor --repair');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:122:            return response('Izvoz lagera trenutno nije dostupan. Pokrenite: php artisan app:payments-inventory-doctor --repair', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:142:                'Content-Disposition' => 'attachment; filename="lager-'.now()->format('Ymd-His').'.csv"',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:146:            $this->safeLog('CSV lagera nije mogao da se generiše.', $exception, $request);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:148:            return response('Izvoz lagera trenutno nije dostupan.', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:24:            $query->whereNull('purchase_price_rsd')->orWhere('purchase_price_rsd', '<=', 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:27:            $query->whereNull('purchase_price_rsd')->orWhere('purchase_price_rsd', '<=', 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:33:                $query->whereNull('purchase_price_rsd')->orWhere('purchase_price_rsd', '<=', 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:39:                'id', 'name', 'sku', 'stock_quantity', 'price_amount', 'price_currency', 'purchase_price_rsd',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:69:            throw ValidationException::withMessages(['costs' => 'Unesite najmanje jednu nabavnu cenu.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:82:                $before = $product->purchase_price_rsd === null ? null : round((float) $product->purchase_price_rsd, 2);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:85:                    'purchase_price_rsd' => $cost,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:91:                    'Ažurirana nabavna cena artikla '.$product->sku,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:93:                    ['purchase_price_rsd' => $before],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:94:                    ['purchase_price_rsd' => $cost],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:105:            ->with('status', $changed > 0 ? 'Sačuvane su nabavne cene za '.$changed.' artikala.' : 'Nema promena za čuvanje.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:110:        abort_unless($request->user()?->hasRole('superadmin') === true, 403, 'Brzi unos nabavnih cena dostupan je samo Super Administratoru.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ManagementReportController.php:42:        return response()->view('admin.reports.management', compact('filters', 'issues', 'report', 'schedules', 'deliveries', 'suppliers', 'dimensions'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ManagementReportController.php:52:            return response('Upravljački CSV izveštaj trenutno nije dostupan. Pokrenite app:management-reports-doctor --repair.', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ManagementReportController.php:63:            return response('Upravljački PDF izveštaj trenutno nije dostupan. Pokrenite app:management-reports-doctor --repair.', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AfterSalesActionController.php:22:        return back()->with('status', 'Planirana je postprodajna radnja '.$action->action_number.'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AfterSalesActionController.php:28:        return back()->with('status', 'Postprodajna radnja je pokrenuta.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AfterSalesActionController.php:34:        return back()->with('status', 'Postprodajna radnja je izvršena i evidentirana.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AfterSalesActionController.php:40:        return back()->with('status', 'Postprodajna radnja je otkazana.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AfterSalesController.php:77:        return back()->with('status', 'Postprodajni slučaj je ažuriran.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldOperationsController.php:131:        return back()->with('status', 'Radni nalog i povezana postprodajna radnja su završeni.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php:69:        return back()->with('status', 'Delovi su primljeni i knjiženi na servisni lager.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:92:        return view('dashboard.index', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:195:            $this->reportDashboardWarning('management_report_stats', $exception);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:251:        $push($items, (bool) ($access['catalog_manage_products'] ?? false), (int) $productStats['out_of_stock'], 'Artikli bez lagera', 'Aktivni artikli trenutno nisu raspoloživi.', route('catalog.index', ['stock'=>'out']), 'warning', 'boxes');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:379:                'value_rsd' => (float) (clone $parts)->selectRaw('COALESCE(SUM(stock_quantity * average_cost_rsd),0) AS total')->value('total'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/CatalogOptionsController.php:62:                ['value' => 'low', 'label' => 'Nizak lager'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php:68:            'description' => 'Servisni lager i nabavka rezervnih delova.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php:155:                    'purchase_value_rsd' => (float) ($inventory['purchase_value_rsd'] ?? 0),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php:156:                    'sale_value_rsd' => (float) ($inventory['sale_value_rsd'] ?? 0),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php:160:                    'missing_sale_value_items' => (int) ($inventory['missing_sale_value_items'] ?? 0),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportController.php:27:    public function management(Request $request, ManagementReportService $reports): JsonResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportController.php:66:    public function managementCsv(Request $request, ManagementReportService $reports): Response
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportController.php:92:    public function managementPdf(Request $request, ManagementReportService $reports): Response
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/InventoryController.php:254:                abort(503, 'Napredni lager nije spreman. Pokrenite: php artisan app:payments-inventory-doctor --repair');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/InventoryController.php:258:                    abort(503, 'Napredni lager nije spreman. Pokrenite: php artisan app:payments-inventory-doctor --repair');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:302:            $blockingReason = 'Artikal trenutno nema raspoloživ lager.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:346:            'sale_price_rsd' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:363:                'sale_price_rsd' => (float) $data['sale_price_rsd'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:670:            'purchase_price_rsd' => $product->purchase_price_rsd !== null ? (float) $product->purchase_price_rsd : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:718:            'purchase_price_rsd' => $product->purchase_price_rsd !== null ? (float) $product->purchase_price_rsd : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php:253:                        'none' => 'Bez automatskog lager efekta',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php:254:                        'automatic' => 'Automatski lager efekat',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:80:        return redirect()->route('orders.show', $order)->with('status', 'Porudžbina '.$order->order_number.' je uspešno kreirana i lager je rezervisan.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AfterSalesController.php:45:        return redirect()->route('after-sales.show', $case)->with('status', 'Postprodajni slučaj '.$case->case_number.' je otvoren.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Auth/AuthenticatedSessionController.php:117:        return redirect()->route('dashboard');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Auth/GoogleWebAuthController.php:192:            return redirect()->intended(route('dashboard'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AfterSalesDoctorCommand.php:19:    protected $description = 'Proveri postprodajni modul, dozvole, privatne priloge i osnovne upite';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AfterSalesDoctorCommand.php:65:                $this->line('<fg=red>FAIL</> Nedostaju postprodajne dozvole.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AfterSalesDoctorCommand.php:68:                $this->line('<fg=green>PASS</> Postprodajne dozvole su dostupne.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AfterSalesDoctorCommand.php:74:            $this->line('<fg=green>PASS</> Osnovni SQL upiti postprodajnog modula rade.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDispatchCommand.php:12:    protected $signature = 'app:management-report-dispatch {--limit=50}';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:21:    protected $signature = 'app:payments-inventory-doctor {--repair : Pokreni migracije i CoreAccessSeeder} {--render : Renderuj napredni lager sa kompletnim layout-om}';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:22:    protected $description = 'Proveri PDF dokumente, payment ledger, IPS podatke, ulaz robe i popis lagera.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:104:            $this->info(sprintf('PASS SQL upiti su uspešni. Uplate=%d, ulazi=%d, popisi=%d, nizak lager=%d.', $paymentCount, $receiptCount, $countCount, $lowStock));
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:124:                $this->info('PASS Napredni lager i authenticated layout su uspešno renderovani.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:126:                $this->error('FAIL Render naprednog lagera nije uspeo: '.$exception::class.': '.$exception->getMessage());
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CustomerPortalDoctorCommand.php:56:            'dashboard', 'portal.messages.index', 'portal.messages.store', 'portal.messages.show', 'portal.messages.reply',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CustomerPortalDoctorCommand.php:126:            $html = view('dashboard.partials.customer-center', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CustomerPortalDoctorCommand.php:139:                $this->line('<fg=red>FAIL</> Početni dashboard nema očekivani integrisani Customer Portal sadržaj.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CustomerPortalDoctorCommand.php:158:            $this->line('<fg=green>PASS</> Integrisani korisnički centar na početnom dashboardu je validan.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:129:        'products' => ['id', 'product_type_id', 'brand_id', 'product_line_id', 'model_name', 'purchase_price_rsd', 'completeness_percent', 'name_is_manual', 'source_product_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:385:                $this->warn('Nedostaju core tabele za login/dashboard: '.implode(', ', $missingCoreTables));
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:388:                $this->warn('Nedostaju core kolone za login/dashboard: '.implode(', ', $missingLoginColumns));
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:549:        $this->error('Popravka nije kompletna. Pročitaj migracioni/seeder izlaz iznad; aplikacija ostaje u bezbednom fallback režimu umesto da dashboard vrati 500.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:21:    protected $description = 'Auditira i bezbedno dopunjava snapshotove nabavnih cena stavki porudžbina.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:81:            'Stavka #%d porudžbine #%d: nabavna cena %.2f RSD, ukupno %.2f RSD.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:99:            $this->info('PASS Sve stavke porudžbina imaju kompletan snapshot nabavne cene.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:105:        $this->warn('Nepotpuni snapshotovi nabavne cene: '.$missing.'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV215DoctorCommand.php:96:            'dashboard',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:19:    protected $signature = 'app:management-reports-doctor {--repair : Pokreni migracije i bezbedno dopuni nepotpune finansijske snapshotove} {--render : Generiši probni PDF}';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:20:    protected $description = 'Proverava profitabilnost, nabavne snapshotove, rasporede i izvoz upravljačkih izveštaja.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:34:                    $this->line('<fg=green>PASS</> Dopunjeni snapshotovi nabavne cene: '.$repair['repaired'].'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:48:            'products' => ['purchase_price_rsd'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:68:        foreach (['admin.reports.index', 'admin.reports.management.pdf', 'admin.reports.management.csv', 'admin.report-schedules.store'] as $route) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:81:        $this->line(($missingCosts > 0 ? '<fg=yellow>WARN</>' : '<fg=green>PASS</>').' Stavke bez kompletne nabavne cene: '.$missingCosts.'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:125:            $this->warn('Pokreni: php artisan app:management-reports-doctor --repair --render');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ServicePartsDoctorCommand.php:21:    protected $description = 'Proveri servisni lager, rezervacije, nabavku, dozvole i integraciju sa radnim nalozima';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ServicePartsDoctorCommand.php:65:                $this->line('<fg=red>FAIL</> Nedostaju dozvole servisnog lagera.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ServicePartsDoctorCommand.php:68:                $this->line('<fg=green>PASS</> Dozvole servisnog lagera su dostupne.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ServicePartsDoctorCommand.php:87:            $this->line('<fg=green>PASS</> SQL upiti i relacije servisnog lagera rade.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AuthDoctorCommand.php:23:        {--render-dashboard : Renderuj isti dashboard koji se otvara odmah nakon prijave}';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AuthDoctorCommand.php:79:            if ($this->option('render-dashboard')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AuthDoctorCommand.php:94:                    $this->failLine('Nema aktivnog korisnika za render početnog dashboarda.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AuthDoctorCommand.php:173:        if ($target !== null && $this->option('render-dashboard')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AuthDoctorCommand.php:209:            if (!str_contains($html, 'data-universal-dashboard-ready="1"') || !str_contains($html, 'Dobro došao')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AuthDoctorCommand.php:214:            $this->pass('Post-login dashboard i kompletan authenticated layout su uspešno renderovani.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AuthDoctorCommand.php:217:            $this->failLine('Post-login dashboard render nije uspeo: '.$exception::class.': '.$exception->getMessage());
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PerformanceDoctorCommand.php:79:        $dashboardSource = (string) @file_get_contents(app_path('Http/Controllers/DashboardController.php'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PerformanceDoctorCommand.php:80:        if (str_contains($dashboardSource, 'tableColumnsCache') && str_contains($dashboardSource, 'array_key_exists($table')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PerformanceDoctorCommand.php:82:            $checks[] = ['key' => 'dashboard_schema_cache', 'status' => 'passed', 'message' => 'Dashboard schema cache je uključen.'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PerformanceDoctorCommand.php:85:            $checks[] = ['key' => 'dashboard_schema_cache', 'status' => 'failed', 'message' => 'Dashboard schema cache nedostaje.'];
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:206:                <input type="checkbox" name="stock_alerts" value="1" @checked($notificationPreference->stock_alerts)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:207:                <span><strong>Lager</strong><small>Nizak i nulti lager.</small></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/components/icon.blade.php:15:    @case('dashboard')<path d="M4 13a8 8 0 1 1 16 0v6H4z"/><path d="m12 13 4-4M8 17h8"/><circle cx="12" cy="13" r="1"/>@break
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:86:        .rate-badge-sync.is-syncing,.dashboard-rate-widget.is-syncing,.exchange-rate-settings-sync.is-syncing{border-color:color-mix(in srgb,var(--primary) 55%,var(--line));box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 12%,transparent)}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:87:        .dashboard-rate-widget[data-exchange-rate-sync]{cursor:pointer;transition:border-color .16s ease,box-shadow .16s ease,transform .16s ease}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:88:        .dashboard-rate-widget[data-exchange-rate-sync]:hover{border-color:color-mix(in srgb,var(--primary) 42%,var(--line));transform:translateY(-1px)}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:89:        .dashboard-rate-widget[data-exchange-rate-sync]:focus-visible,.rate-badge-sync:focus-visible,.exchange-rate-settings-sync:focus-visible{outline:none;box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 22%,transparent)}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:98:        <a class="brand" href="{{ route('dashboard') }}" aria-label="{{ $siteName }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:159:            <a class="{{ request()->routeIs('dashboard', 'portal.messages.*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-link-content"><x-icon name="home" /><span>Početna</span></span></a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:189:                    <span data-module-visibility="{{ $moduleVisibility->enabled('service_parts') ? '1' : '0' }}">@can('service_parts.view')<a href="{{ route('admin.service-parts.index') }}"><x-icon name="boxes" />Servisni lager</a>@endcan</span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:212:                    @can('stock.view')<span data-module-visibility="{{ $moduleVisibility->enabled('inventory') ? '1' : '0' }}"><a href="{{ route('admin.inventory.index') }}"><x-icon name="boxes" />Napredni lager</a></span><a href="{{ route('admin.stock.index') }}"><x-icon name="cube" />Sva kretanja lagera</a>@endcan
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:489:                    bits.push(`lager ${Number(item.stock_quantity)}`);
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:630:        const dashboardLabel = document.querySelector('[data-exchange-rate-dashboard-label]');
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:634:            if (dashboardLabel) dashboardLabel.textContent = 'Sinhronizacija EUR/RSD kursa…';
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:640:            if (dashboardLabel) dashboardLabel.textContent = 'EUR/RSD kurs ažuriran';
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:646:            if (dashboardLabel) dashboardLabel.textContent = 'Greška pri sinhronizaciji';
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:651:        if (dashboardLabel) dashboardLabel.textContent = 'Aktuelni EUR/RSD kurs';
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:9:        <p>Izaberi SuperAdministratora ili Administratora od kog poručuješ robu. Porudžbina se njemu automatski dodeljuje, a lager se rezerviše transakcijski.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/create.blade.php:63:                                                    {{ $product->sku }} · {{ $product->name }} · lager {{ $product->stock_quantity }}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/show.blade.php:53:            <p>{{ $order['created_at'] ?? '—' }} · lager {{ $order['inventory_state'] ?? '—' }}</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/show.blade.php:282:                    <p class="muted">Otkazivanje vraća rezervisani lager tačno jednom.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/show.blade.php:289:                        <button class="button button-danger" type="submit" data-confirm="Otkazati porudžbinu i vratiti lager?">Otkaži porudžbinu</button>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/purchase-costs.blade.php:12:            <p>Brzi jednokratni unos koristi postojeće kanonsko polje <strong>purchase_price_rsd</strong>. Lager, prodajna cena i status se ovde ne menjaju.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/purchase-costs.blade.php:15:            <a class="button button-ghost" href="{{ route('dashboard') }}">Početna</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/purchase-costs.blade.php:17:                <a class="button button-ghost" href="{{ route('admin.products.purchase-costs') }}">Samo bez nabavne cene</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/purchase-costs.blade.php:28:        <div class="panel"><small>Nedostaje nabavna cena</small><strong style="display:block;font-size:1.65rem">{{ number_format((int) $missingTotal, 0, ',', '.') }}</strong><span class="purchase-cost-muted">Svi nearhivirani artikli</span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/purchase-costs.blade.php:29:        <div class="panel"><small>Nedostaje cena uz pozitivan lager</small><strong style="display:block;font-size:1.65rem">{{ number_format((int) $missingPositiveStock, 0, ',', '.') }}</strong><span class="purchase-cost-muted">Direktno utiče na tačnost lager KPI-ja</span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/purchase-costs.blade.php:34:            <h2>{{ $showAll ? 'Nema artikala za prikaz.' : 'Sve nabavne cene su popunjene.' }}</h2>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/purchase-costs.blade.php:57:                                    value="{{ old('costs.'.$product->id, $product->purchase_price_rsd !== null && (float) $product->purchase_price_rsd > 0 ? number_format((float) $product->purchase_price_rsd, 2, '.', '') : '') }}"
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:60:            <span class="ux-product-task-copy"><strong>Cena i lager</strong><small>cena, provizija i stanje</small></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:74:                <input type="search" data-ux-product-field-search placeholder="Npr. RAM, cena, SKU, lager..." autocomplete="off">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:342:                    <h2>Cena i lager</h2>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:345:                    <label><span>Nabavna cena RSD</span><input name="purchase_price_rsd" type="number" step="0.01" min="0" value="{{ old('purchase_price_rsd',$product->purchase_price_rsd) }}"><small>Koristi se za obračun marže i snapshotuje se pri prodaji.</small></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:348:                        <label><span>Količina</span><input name="stock_quantity" type="number" min="0" required value="{{ old('stock_quantity',$product->stock_quantity??0) }}"></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:349:                        <label><span>Prag niskog lagera</span><input name="low_stock_threshold" type="number" min="0" required value="{{ old('low_stock_threshold',$product->low_stock_threshold??1) }}"></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:351:                        <input type="hidden" name="stock_quantity" value="{{ $product->stock_quantity }}"><input type="hidden" name="low_stock_threshold" value="{{ $product->low_stock_threshold }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:352:                        <label><span>Količina</span><input type="number" value="{{ $product->stock_quantity }}" disabled><small class="muted">Za promenu je potrebna dozvola Korekcija lagera.</small></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:353:                        <label><span>Prag niskog lagera</span><input type="number" value="{{ $product->low_stock_threshold }}" disabled></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:376:                                        <small>Koristi arhiviranje da porudžbine, lager i izveštaji ostanu ispravni.</small>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:392:                                    <p class="muted">Live CMS će ukloniti sam artikal, slike i specifikacije; istorijske porudžbine, lager i garancije ostaju kao poslovni događaji, ali bez veze ka obrisanom artiklu, SKU-a i naziva izabranog artikla.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:557:                label: 'Cena i lager',
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/modules.blade.php:29:                    <p class="muted">Svi moduli su podrazumevano uključeni. Isključen modul se uklanja iz glavne navigacije i dashboard prečica; postojeći podaci ostaju netaknuti.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/automation.blade.php:10:        'low_stock' => 'Nizak lager',
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/automation.blade.php:26:<label class="check-card"><input type="checkbox" name="automation_low_stock_enabled" value="1" @checked(old('automation_low_stock_enabled',$settings['automation_low_stock_enabled'])==='1')><span><strong>Nizak lager</strong><small>Prag se čita iz svakog artikla.</small></span></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/orders/show.blade.php:54:                lager {{ $order['inventory_state'] ?? '—' }}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/index.blade.php:67:            <div><small>Nizak lager</small><strong>{{ number_format((int)($inventorySummary['low_stock'] ?? 0),0,',','.') }}</strong></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/index.blade.php:68:            <div><small>Bez lagera</small><strong>{{ number_format((int)($inventorySummary['out_of_stock'] ?? 0),0,',','.') }}</strong></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/index.blade.php:70:        @can('stock.view')<a class="button button-primary button-small" href="{{ route('admin.inventory.index') }}">Otvori napredni lager</a>@endcan
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:5:<div class="page-heading"><div><span class="eyebrow">Management Analytics</span><h1>Izveštaji i profitabilnost</h1><p>Promet, bruto marža, provizije, refundacije, servisni troškovi, lager i potraživanja na jednom mestu.</p></div>@can('reports.export')<div class="report-export-actions"><a class="button button-ghost" href="{{ route('admin.reports.management.csv',request()->query()) }}"><x-icon name="download" /> CSV</a><a class="button button-primary" target="_blank" rel="noopener" href="{{ route('admin.reports.management.pdf',request()->query()) }}"><x-icon name="file-text" /> PDF</a></div>@endcan</div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:7:@if($issues!==[])<div class="alert alert-warning"><strong>Upravljački izveštaji nisu spremni.</strong><span>{{ implode(' ',$issues) }}</span><code>php artisan app:management-reports-doctor --repair</code></div>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:26:<div class="analytics-card"><small>Pokrivenost nabavne cene</small><strong class="{{ $s['cost_coverage_percent']>=95?'analytics-good':'analytics-warning' }}">{{ number_format($s['cost_coverage_percent'],1,',','.') }}%</strong><em>{{ $s['missing_cost_lines'] }} stavki bez troška · {{ number_format($s['revenue_missing_cost_rsd'],2,',','.') }} RSD prometa</em></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:34:<section class="panel form-section"><h2>Kvalitet finansijskih podataka</h2><dl class="detail-list"><dt>Pokriven promet</dt><dd>{{ number_format($s['known_revenue_rsd'],2,',','.') }} RSD</dd><dt>Promet bez nabavne cene</dt><dd>{{ number_format($s['revenue_missing_cost_rsd'],2,',','.') }} RSD</dd><dt>Stavke bez troška</dt><dd>{{ $s['missing_cost_lines'] }}</dd><dt>Preporuka</dt><dd>@if($s['cost_coverage_percent']<95)<span class="analytics-warning">Dopuniti nabavne cene proizvoda.</span>@else<span class="analytics-good">Podaci su dovoljno pokriveni.</span>@endif</dd></dl></section></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:38:<div class="analytics-three"><section class="panel form-section"><h2>Lager i kapital</h2><dl class="detail-list"><dt>Vrednost lagera</dt><dd><strong>{{ number_format($report['inventory']['value_rsd'],2,',','.') }} RSD</strong></dd><dt>Komada</dt><dd>{{ $report['inventory']['units_count'] }}</dd><dt>Bez nabavne cene</dt><dd>{{ $report['inventory']['missing_cost_items'] }}</dd><dt>Spori lager 90+ dana</dt><dd>{{ $report['inventory']['slow_items'] }}</dd></dl><h3>Starost vrednosti lagera</h3><div class="analytics-aging">@foreach(['0_30'=>'0–30','31_60'=>'31–60','61_90'=>'61–90','91_180'=>'91–180','over_180'=>'180+'] as $key=>$label)<div><small>{{ $label }} dana</small><strong>{{ number_format($report['inventory']['aging'][$key]??0,0,',','.') }}</strong></div>@endforeach</div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:42:<section class="panel form-section"><div class="section-heading-row"><div><h2>Najveća vrednost lagera</h2><p class="muted">Artikli koji vezuju najviše kapitala.</p></div><a class="button button-ghost button-small" href="{{ route('admin.reports.inventory.csv') }}">Postojeći lager CSV</a></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal</th><th>Količina</th><th>Jedinični trošak</th><th>Vrednost</th><th>Starost</th></tr></thead><tbody>@forelse(array_slice($report['inventory']['top_value'],0,15) as $row)<tr><td>{{ $row['sku'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['quantity'] }}</td><td>{{ $row['has_cost']?number_format($row['unit_cost_rsd'],2,',','.').' RSD':'Nedostaje' }}</td><td>{{ number_format($row['value_rsd'],2,',','.') }} RSD</td><td>{{ $row['age_days'] }} dana</td></tr>@empty<tr><td colspan="6">Nema lagera.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:47:<label class="span-2"><span>Naziv rasporeda</span><input name="name" required placeholder="Nedeljni upravljački izveštaj"></label><label><span>Tip</span><select name="report_type"><option value="management_summary">Kompletan upravljački</option><option value="profitability">Profitabilnost</option><option value="inventory">Lager</option><option value="receivables">Potraživanja</option><option value="after_sales">Postprodaja</option></select></label><label><span>Učestalost</span><select name="frequency"><option value="daily">Dnevno</option><option value="weekly" selected>Nedeljno</option><option value="monthly">Mesečno</option></select></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:54:<section class="panel form-section"><div class="section-heading-row"><div><h2>Operativni izvozi</h2><p class="muted">Postojeći detaljni izvozi porudžbina, uplata i lagera ostaju dostupni.</p></div></div><div class="button-row">@can('reports.export')<a class="button button-ghost" href="{{ route('admin.reports.orders.csv',request()->query()) }}">Porudžbine CSV</a><a class="button button-ghost" target="_blank" href="{{ route('admin.reports.orders.pdf',request()->query()) }}">Porudžbine PDF</a><a class="button button-ghost" href="{{ route('admin.reports.payments.csv') }}">Uplate CSV</a>@endcan @can('inventory.export')<a class="button button-ghost" href="{{ route('admin.reports.inventory.csv') }}">Lager CSV</a>@endcan</div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/suppliers.blade.php:4:@include('admin.settings.partials.context-nav', ['settingsSection' => 'Poslovna pravila', 'settingsContextLabel' => 'Servisni lager', 'settingsContextUrl' => route('admin.service-parts.index')])
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/index.blade.php:2:@section('title', 'Servisni lager')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/index.blade.php:4:<div class="page-heading"><div><span class="eyebrow">Rezervni delovi</span><h1>Servisni lager</h1><p>Stanje, rezervacije, utrošak i korekcije delova za terenske radne naloge.</p></div><div class="header-button-row">@can('service_parts.procurement')<a class="button button-ghost" href="{{ route('admin.service-part-suppliers.index') }}">Dobavljači</a><a class="button button-primary" href="{{ route('admin.service-part-purchases.index') }}">Nabavke</a>@endcan</div></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/index.blade.php:5:<div class="stats-grid four-cards"><article class="stat-card"><span>Aktivni delovi</span><strong>{{ $stats['active'] }}</strong></article><article class="stat-card warning"><span>Nizak lager</span><strong>{{ $stats['low'] }}</strong></article><article class="stat-card info"><span>Sa rezervacijama</span><strong>{{ $stats['reserved_lines'] }}</strong></article><article class="stat-card"><span>Vrednost lagera</span><strong>{{ number_format($stats['value'],2,',','.') }} RSD</strong></article></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/index.blade.php:9:<label><span>Jedinica mere</span><input name="unit" required maxlength="30" value="{{ old('unit','kom') }}"></label><label><span>Početno stanje</span><input type="number" name="stock_quantity" min="0" step="0.001" value="{{ old('stock_quantity',0) }}"></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/index.blade.php:13:<section class="panel form-section"><h2>Pretraga i filteri</h2><form method="get" class="form-grid"><label><span>Šifra ili naziv</span><input name="q" value="{{ $q }}"></label><label><span>Prikaz</span><select name="filter"><option value="all" @selected($filter==='all')>Svi aktivni</option><option value="low" @selected($filter==='low')>Nizak lager</option><option value="available" @selected($filter==='available')>Raspoloživi</option><option value="inactive" @selected($filter==='inactive')>Neaktivni</option></select></label><button class="button button-ghost" type="submit">Primeni</button></form><div class="alpha-note">Raspoloživo = fizičko stanje minus količina rezervisana za aktivne radne naloge.</div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/index.blade.php:15:<section class="panel form-section"><h2>Delovi</h2><div class="table-wrap"><table><thead><tr><th>Deo</th><th>Stanje</th><th>Rezervisano</th><th>Raspoloživo</th><th>Minimum</th><th>Dobavljač</th><th>Izmena</th></tr></thead><tbody>@forelse($parts as $part)<tr class="{{ $part->isLowStock()?'row-warning':'' }}"><td><strong>{{ $part->name }}</strong><small>{{ $part->sku }} · {{ $part->unit }}</small></td><td>{{ number_format((float)$part->stock_quantity,3,',','.') }}</td><td>{{ number_format((float)$part->reserved_quantity,3,',','.') }}</td><td><strong>{{ number_format($part->availableQuantity(),3,',','.') }}</strong></td><td>{{ number_format((float)$part->minimum_quantity,3,',','.') }}</td><td>{{ $part->preferredSupplier?->name ?? '—' }}</td><td><details><summary class="button button-ghost button-small">Uredi</summary><div class="details-popover"><form method="post" action="{{ route('admin.service-parts.update',$part) }}" class="form-grid">@csrf @method('PUT')<input name="sku" value="{{ $part->sku }}" required><input name="name" value="{{ $part->name }}" required><input name="unit" value="{{ $part->unit }}" required><input type="number" step="0.001" min="0" name="minimum_quantity" value="{{ $part->minimum_quantity }}"><input type="number" step="0.01" min="0" name="average_cost_rsd" value="{{ $part->average_cost_rsd }}"><select name="preferred_supplier_id"><option value="">Bez dobavljača</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected($part->preferred_supplier_id===$supplier->id)>{{ $supplier->name }}</option>@endforeach</select><textarea name="notes">{{ $part->notes }}</textarea><label class="checkbox-row"><input type="checkbox" name="is_active" value="1" @checked($part->is_active)><span>Aktivan</span></label><button class="button button-primary" type="submit">Sačuvaj</button></form><hr><form method="post" action="{{ route('admin.service-parts.adjust',$part) }}" class="form-grid">@csrf<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><label><span>Korekcija (+/-)</span><input type="number" name="quantity_change" step="0.001" required></label><label><span>Razlog</span><textarea name="note" required minlength="5"></textarea></label><button class="button button-ghost" type="submit">Knjiži korekciju</button></form></div></details></td></tr>@empty<tr><td colspan="7">Nema rezervnih delova za izabrani filter.</td></tr>@endforelse</tbody></table></div>{{ $parts->links() }}</section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/purchase-requests.blade.php:4:<a class="back-link" href="{{ route('admin.service-parts.index') }}">← Servisni lager</a><div class="page-heading"><div><span class="eyebrow">Nabavka</span><h1>Zahtevi za nabavku</h1><p>Od nacrta do prijema delova na servisni lager.</p></div><a class="button button-ghost" href="{{ route('admin.service-part-suppliers.index') }}">Dobavljači</a></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/purchase-show.blade.php:5:<div class="settings-grid"><section class="panel form-section"><h2>Stavke</h2><div class="table-wrap"><table><thead><tr><th>Deo</th><th>Poručeno</th><th>Primljeno</th><th>Cena</th><th>Ukupno</th></tr></thead><tbody>@foreach($purchaseRequest->items as $item)<tr><td><strong>{{ $item->part?->name }}</strong><small>{{ $item->part?->sku }}</small></td><td>{{ number_format((float)$item->ordered_quantity,3,',','.') }} {{ $item->part?->unit }}</td><td>{{ number_format((float)$item->received_quantity,3,',','.') }}</td><td>{{ number_format((float)$item->unit_cost_rsd,2,',','.') }} RSD</td><td>{{ number_format((float)$item->ordered_quantity*(float)$item->unit_cost_rsd,2,',','.') }} RSD</td></tr>@endforeach</tbody></table></div></section><aside class="form-side"><section class="panel form-section"><h2>Podaci</h2><dl class="detail-list"><dt>Dobavljač</dt><dd>{{ $purchaseRequest->supplier?->name ?? '—' }}</dd><dt>Referenca</dt><dd>{{ $purchaseRequest->supplier_reference ?: '—' }}</dd><dt>Očekivano</dt><dd>{{ $purchaseRequest->expected_at?->format('d.m.Y') ?? '—' }}</dd><dt>Vrednost</dt><dd><strong>{{ number_format((float)$purchaseRequest->total_cost_rsd,2,',','.') }} RSD</strong></dd></dl>@if($purchaseRequest->notes)<p>{{ $purchaseRequest->notes }}</p>@endif</section><section class="panel form-section"><h2>Akcije</h2><div class="form-grid">@if($purchaseRequest->status==='draft')<form method="post" action="{{ route('admin.service-part-purchases.submit',$purchaseRequest) }}">@csrf<button class="button button-primary full-width">Označi kao poslato</button></form>@endif @if($purchaseRequest->status==='submitted')<form method="post" action="{{ route('admin.service-part-purchases.order',$purchaseRequest) }}">@csrf<button class="button button-primary full-width">Označi kao poručeno</button></form>@endif @if($purchaseRequest->status==='ordered')<form method="post" action="{{ route('admin.service-part-purchases.receive',$purchaseRequest) }}" data-confirm="Potvrditi prijem svih stavki na servisni lager?">@csrf<button class="button button-primary full-width">Primi sve stavke</button></form>@endif @unless($purchaseRequest->isTerminal())<form method="post" action="{{ route('admin.service-part-purchases.cancel',$purchaseRequest) }}">@csrf<label><span>Razlog otkazivanja</span><textarea name="cancellation_reason" required minlength="5"></textarea></label><button class="button button-danger full-width">Otkaži zahtev</button></form>@endunless</div></section></aside></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:2:@section('title', 'Napredni lager')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:14:            <h1>Ulaz robe, popis i stanje lagera</h1>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:18:            @can('inventory.export')<a class="button button-ghost" href="{{ route('admin.inventory.csv') }}"><x-icon name="download" /> CSV lagera</a>@endcan
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:24:        <div class="alert alert-danger"><strong>Napredni lager trenutno nije spreman.</strong><br>{{ $inventoryUnavailable }}<br><code>php artisan app:payments-inventory-doctor --repair</code></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:30:        <div class="report-summary-card"><span class="metric-icon metric-amber"><x-icon name="alert" /></span><div><small>Nizak lager</small><strong>{{ $lowCount }}</strong></div></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:31:        <div class="report-summary-card"><span class="metric-icon metric-red"><x-icon name="x-circle" /></span><div><small>Bez lagera</small><strong>{{ $outCount }}</strong></div></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:46:        <nav class="inventory-operation-tabs" aria-label="Operacija lagera">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:51:                <a class="{{ $mode === 'count' ? 'active' : '' }}" href="{{ route('admin.inventory.index', array_merge($baseQuery, ['mode' => 'count'])) }}"><x-icon name="check-circle" /> Popis lagera</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:94:                <div class="section-heading-row"><div><h2><x-icon name="check-circle" /> Popis lagera</h2><p class="muted">Prazna polja se preskaču. Upisana vrednost postaje novo stanje.</p></div><span class="count-pill">{{ $products->count() }} artikala</span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:119:                    <div class="inventory-submit-row"><button class="button button-primary" type="submit" data-confirm="Zaključiti popis i uskladiti lager?">Zaključi popis</button></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:131:    <section class="panel form-section"><div class="section-heading-row"><div><h2>Upozorenja za lager</h2><p class="muted">Artikli na ili ispod definisanog minimalnog praga.</p></div><span class="count-pill">{{ $lowStock->count() }}</span></div><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal</th><th>Stanje</th><th>Prag</th></tr></thead><tbody>@forelse($lowStock as $product)<tr><td data-label="SKU"><strong>{{ $product->sku }}</strong></td><td data-label="Artikal">{{ $product->name }}</td><td data-label="Stanje" class="text-danger"><strong>{{ $product->stock_quantity }}</strong></td><td data-label="Prag">{{ $product->low_stock_threshold }}</td></tr>@empty<tr><td colspan="4">Nema upozorenja.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/field-operations/index.blade.php:6:    <div class="header-button-row"><a class="button button-ghost" href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" /> Ekipe i partneri</a><a class="button button-primary" href="{{ route('admin.after-sales.index', ['execution_pending'=>'1']) }}"><x-icon name="cog" /> Postprodajne radnje</a></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/field-operations/show.blade.php:33:    <section class="panel form-section"><div class="section-heading-row"><div><h2>Rezervni delovi</h2><p class="muted">Lokalni delovi se rezervišu pre intervencije i skidaju sa lagera tek po stvarnom utrošku.</p></div><a class="button button-ghost button-small" href="{{ route('admin.service-parts.index') }}">Servisni lager</a></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/field-operations/show.blade.php:34:        @if($workOrder->parts->isNotEmpty())<div class="table-wrap"><table><thead><tr><th>Deo</th><th>Izvor</th><th>Traženo</th><th>Rezervisano</th><th>Utrošeno</th>@unless($workOrder->isTerminal())<th></th>@endunless</tr></thead><tbody>@foreach($workOrder->parts as $line)<tr><td><strong>{{ $line->part_name_snapshot }}</strong><small>{{ $line->part_sku_snapshot }}</small></td><td>{{ $line->supply_mode==='local_stock'?'Servisni lager':'Spoljna nabavka / doneto' }}</td><td>{{ number_format((float)$line->requested_quantity,3,',','.') }} {{ $line->unit_snapshot }}</td><td>{{ number_format((float)$line->reserved_quantity,3,',','.') }}</td><td>{{ number_format((float)$line->consumed_quantity,3,',','.') }}</td>@unless($workOrder->isTerminal())<td>@can('service_parts.manage')<form method="post" action="{{ route('admin.field-operations.parts.destroy',[$workOrder,$line]) }}">@csrf @method('DELETE')<button class="button button-danger button-small" type="submit" data-confirm="Ukloniti deo i osloboditi rezervaciju?">Ukloni</button></form>@endcan</td>@endunless</tr>@endforeach</tbody></table></div>@else<div class="alpha-note">Za ovaj radni nalog još nisu planirani rezervni delovi.</div>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/field-operations/show.blade.php:36:        <form method="post" action="{{ route('admin.field-operations.parts.store',$workOrder) }}" class="form-grid two-columns service-part-add-form">@csrf<label><span>Rezervni deo</span><select name="service_part_id" required><option value="">Izaberi deo</option>@foreach($serviceParts as $part)<option value="{{ $part->id }}">{{ $part->sku }} · {{ $part->name }} · rasp. {{ number_format($part->availableQuantity(),3,',','.') }} {{ $part->unit }}</option>@endforeach</select></label><label><span>Količina</span><input type="number" name="requested_quantity" min="0.001" step="0.001" required></label><label><span>Izvor</span><select name="supply_mode"><option value="local_stock">Lokalni servisni lager</option><option value="external">Spoljna nabavka / deo donosi ekipa</option></select></label><label><span>Napomena</span><input name="notes" maxlength="3000"></label><button class="button button-ghost full-width" type="submit">Dodaj ili izmeni deo</button></form>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/field-operations/show.blade.php:43:    <section class="panel form-section"><h2>Završi radni nalog</h2><p class="muted">Završavanje ovog naloga istovremeno završava povezanu postprodajnu radnju i primenjuje odobrene lager/finansijske efekte.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/field-operations/show.blade.php:51:            <label><span>Dodatni trošak delova RSD</span><input type="number" step="0.01" min="0" name="parts_cost_rsd" value="{{ old('parts_cost_rsd',0) }}"><small>Utrošak iz servisnog lagera obračunava se automatski.</small></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/field-operations/show.blade.php:54:            <button class="button button-primary button-large full-width" type="submit">Završi nalog i postprodajnu radnju</button>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/stock/index.blade.php:6:<section class="panel form-section"><h2>Trenutno stanje</h2><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal</th><th>Lager</th><th>Prag</th>@can('stock.adjust')<th>Korekcija</th>@endcan</tr></thead><tbody>@forelse($products as $product)<tr><td><strong>{{ $product->sku }}</strong></td><td>{{ $product->name }}</td><td><strong class="{{ $product->stock_quantity <= $product->low_stock_threshold ? 'text-danger' : 'text-success' }}">{{ $product->stock_quantity }}</strong></td><td>{{ $product->low_stock_threshold }}</td>@can('stock.adjust')<td><form class="inline-form" method="post" action="{{ route('admin.stock.adjust',$product) }}">@csrf<input type="hidden" name="idempotency_key" value="{{ $idempotencyKeys[$product->id] }}"><input type="number" name="quantity_change" required placeholder="+/-" class="ux-stock-max-width-90"><input name="note" required maxlength="1000" placeholder="Obavezan razlog"><button class="button button-ghost button-small" type="submit">Primeni</button></form></td>@endcan</tr>@empty<tr><td colspan="5">Nema artikala.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/stock/index.blade.php:7:<section class="panel form-section"><h2>Istorija promena</h2><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>Datum</th><th>Artikal</th><th>Tip / izvor</th><th>Promena</th><th>Pre</th><th>Posle</th><th>Porudžbina</th><th>Korisnik</th><th>Napomena</th></tr></thead><tbody>@forelse($movements as $row)<tr><td>{{ $row->created_at?->format('d.m.Y H:i') }}</td><td><strong>{{ $row->product?->sku }}</strong><small class="muted">{{ $row->product?->name }}</small></td><td>{{ $row->movement_type }}<small class="muted">{{ $row->source }}</small></td><td><strong class="{{ $row->quantity_change >= 0 ? 'text-success' : 'text-danger' }}">{{ $row->quantity_change > 0 ? '+' : '' }}{{ $row->quantity_change }}</strong></td><td>{{ $row->quantity_before }}</td><td>{{ $row->quantity_after }}</td><td>{{ $row->order?->order_number ?? '—' }}</td><td>{{ $row->user?->displayName() ?? '—' }}</td><td>{{ $row->note ?: '—' }}</td></tr>@empty<tr><td colspan="9">Nema promena lagera.</td></tr>@endforelse</tbody></table></div><div class="pagination-wrap">{{ $movements->links() }}</div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/after-sales/index.blade.php:4:<div class="page-heading"><div><span class="eyebrow">Postprodajna podrška</span><h1>Reklamacije, povrati i servisi</h1><p>Centralni red za obradu problema nakon isporuke.</p></div></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/after-sales/show.blade.php:12:        <div class="section-heading-row"><div><h2>Izvršne postprodajne radnje</h2><p class="muted">Servis, zamena, prijem vraćene robe i refundacija izvršavaju se kroz kontrolisane, auditovane radnje.</p></div><span class="status-badge">{{ $case->actions->whereNotIn('status', ['completed','cancelled'])->count() }} aktivnih</span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/after-sales/show.blade.php:46:                <label><span>Način obrade lagera</span><select name="inventory_handling"><option value="none">Bez promene lagera</option><option value="automatic" @selected(old('inventory_handling')==='automatic')>Automatski kroz lokalni lager</option><option value="external" @selected(old('inventory_handling')==='external')>Eksterno / bez automatske promene</option></select></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/management-report.blade.php:1:<!doctype html><html lang="sr"><body style="margin:0;background:#f3f5f8;font-family:Arial,sans-serif;color:#202735"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="padding:28px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;margin:auto;background:#fff;border-radius:16px;overflow:hidden"><tr><td style="background:#172033;color:#fff;padding:24px 30px"><strong style="font-size:20px">{{ config('app.name','Ald1n CMS') }}</strong><div style="color:#cbd5e1;margin-top:6px">Upravljački izveštaj</div></td></tr><tr><td style="padding:30px"><p style="margin-top:0">U prilogu se nalazi izveštaj za period <strong>{{ $delivery->period_from?->format('d.m.Y') }} – {{ $delivery->period_to?->format('d.m.Y') }}</strong>.</p><table width="100%" cellspacing="0" cellpadding="10" style="background:#f8fafc;border:1px solid #dce2ea;border-radius:12px"><tr><td>Prihod</td><td align="right"><strong>{{ number_format((float)($summary['revenue_rsd'] ?? 0),2,',','.') }} RSD</strong></td></tr><tr><td>Bruto dobit</td><td align="right"><strong>{{ number_format((float)($summary['gross_profit_rsd'] ?? 0),2,',','.') }} RSD</strong></td></tr><tr><td>Neto doprinos</td><td align="right"><strong>{{ number_format((float)($summary['net_contribution_rsd'] ?? 0),2,',','.') }} RSD</strong></td></tr><tr><td>Pokrivenost nabavne cene</td><td align="right"><strong>{{ number_format((float)($summary['cost_coverage_percent'] ?? 0),1,',','.') }}%</strong></td></tr></table><p style="color:#667085;font-size:12px;margin-bottom:0;margin-top:24px">Poruka je automatski poslata iz {{ config('app.name','Ald1n CMS') }} sistema.</p></td></tr></table></td></tr></table></body></html>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/portal/messages/index.blade.php:11:    <a class="button button-ghost" href="{{ route('dashboard') }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:5:    {{-- Safe module visibility: CSS-only dashboard hiding; no wrappers around existing Blade markup. --}}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:57:<section class="modern-dashboard-hero" data-universal-dashboard-ready="1">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:58:    <div class="dashboard-welcome">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:59:        <span class="dashboard-date">Univerzalni dashboard · {{ now()->translatedFormat('l, d. F Y.') }}</span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:66:        <div class="dashboard-quick-actions">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:105:            class="dashboard-rate-widget"
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:111:        <div class="dashboard-rate-widget">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:113:            <span class="dashboard-rate-icon"><x-icon name="coins" size="24" /></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:115:                <small data-exchange-rate-dashboard-label>Aktuelni EUR/RSD kurs</small>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:124:<section class="dashboard-kpi-grid" data-superadmin-inventory-valuation-v0-8="1">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:125:    <a class="dashboard-kpi-card" href="{{ route('admin.products.purchase-costs') }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:126:        <span class="dashboard-kpi-icon tone-violet"><x-icon name="boxes" /></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:127:        <div><small>Vrednost po nabavnoj ceni</small><strong>{{ number_format((float) ($inventoryValuation['purchase_value_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>{{ (int) ($inventoryValuation['missing_cost_total_items'] ?? 0) }} artikala bez nabavne cene</span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:129:    <a class="dashboard-kpi-card" href="{{ route('admin.inventory.index') }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:130:        <span class="dashboard-kpi-icon tone-blue"><x-icon name="money" /></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:131:        <div><small>Vrednost po prodajnoj ceni</small><strong>{{ number_format((float) ($inventoryValuation['sale_value_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>Trenutna prodajna vrednost lagera</span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:133:    <a class="dashboard-kpi-card" href="{{ route('admin.inventory.index') }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:134:        <span class="dashboard-kpi-icon tone-green"><x-icon name="chart" /></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:135:        <div><small>Ukupna očekivana zarada</small><strong>{{ number_format((float) ($inventoryValuation['expected_profit_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>{{ ($inventoryValuation['valuation_complete'] ?? false) ? 'Kompletna valuacija trenutnog lagera' : 'Privremena procena — dopunite nedostajuće nabavne cene ili kurs' }}</span></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:139:<section class="dashboard-kpi-grid">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:141:        <a class="dashboard-kpi-card" href="{{ route('orders.index') }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:142:            <span class="dashboard-kpi-icon tone-blue"><x-icon name="orders" /></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:145:        <a class="dashboard-kpi-card" href="{{ route('orders.index') }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:146:            <span class="dashboard-kpi-icon tone-amber"><x-icon name="hourglass" /></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:149:        <a class="dashboard-kpi-card" href="{{ route('orders.index') }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:150:            <span class="dashboard-kpi-icon tone-red"><x-icon name="wallet" /></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:153:        <a class="dashboard-kpi-card" href="{{ route('portal.messages.index') }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:154:            <span class="dashboard-kpi-icon tone-violet"><x-icon name="mail" /></span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:158:        <a class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}"><span class="dashboard-kpi-icon tone-blue"><x-icon name="chart" /></span><div><small>Prihod ovog meseca</small><strong>{{ number_format((float) ($reportSummary['revenue_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>{{ number_format((int) ($reportSummary['orders_count'] ?? 0), 0, ',', '.') }} završenih porudžbina</span></div></a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:159:        <a class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}"><span class="dashboard-kpi-icon tone-green"><x-icon name="money" /></span><div><small>Bruto dobit</small><strong>{{ number_format((float) ($reportSummary['gross_profit_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>Marža {{ number_format((float) ($reportSummary['gross_margin_percent'] ?? 0), 1, ',', '.') }}%</span></div></a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:160:        <a class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}"><span class="dashboard-kpi-icon tone-violet"><x-icon name="wallet" /></span><div><small>Neto doprinos</small><strong>{{ number_format((float) ($reportSummary['net_contribution_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>Pokrivenost troška {{ number_format((float) ($reportSummary['cost_coverage_percent'] ?? 0), 1, ',', '.') }}%</span></div></a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:161:        <a class="dashboard-kpi-card" href="{{ $access['receivables_manage'] ? route('admin.receivables.index') : $orderIndex }}"><span class="dashboard-kpi-icon tone-red"><x-icon name="receipt" /></span><div><small>Otvoreno potraživanje</small><strong>{{ number_format((float) ($reportSummary['outstanding_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>{{ number_format((int) $receivableStats['active'], 0, ',', '.') }} aktivnih predmeta</span></div></a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:163:        <a class="dashboard-kpi-card" href="{{ $orderIndex }}"><span class="dashboard-kpi-icon tone-blue"><x-icon name="orders" /></span><div><small>Nove porudžbine</small><strong>{{ number_format((int) $orderStats['new'], 0, ',', '.') }}</strong><span>Čekaju obradu</span></div></a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:164:        <a class="dashboard-kpi-card" href="{{ $orderIndex }}"><span class="dashboard-kpi-icon tone-amber"><x-icon name="hourglass" /></span><div><small>U obradi</small><strong>{{ number_format((int) $orderStats['processing'], 0, ',', '.') }}</strong><span>Aktivne porudžbine</span></div></a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:165:        <a class="dashboard-kpi-card" href="{{ $orderIndex }}"><span class="dashboard-kpi-icon tone-violet"><x-icon name="truck" /></span><div><small>Poslate</small><strong>{{ number_format((int) $orderStats['shipped'], 0, ',', '.') }}</strong><span>U procesu dostave</span></div></a>
SOURCE_TOPOLOGY_SCAN=PASS

============================================================
2. LARAVEL RUNTIME SCHEMA + DATA COVERAGE AUDIT - READ ONLY
============================================================
No syntax errors detected in /tmp/ald1n-v09-kpi-audit.A5k2vs/kpi-audit.php
EMBEDDED_KPI_AUDIT_PHP_LINT=PASS
PRODUCTS_COLUMN_COUNT=28
PRODUCTS_COLUMNS=["id","product_type_id","brand_id","product_line_id","model_name","sku","name","slug","price_amount","price_currency","manual_commission_eur","description","notes","stock_quantity","low_stock_threshold","status","created_by","updated_by","created_at","updated_at","deleted_at","legacy_checksum","legacy_synced_at","locally_modified_at","completeness_percent","name_is_manual","source_product_id","purchase_price_rsd"]
STOCK_COLUMN_CANDIDATES_FOUND=["stock_quantity"]
PURCHASE_PRICE_CANDIDATES_FOUND=["purchase_price_rsd"]
SALE_PRICE_CANDIDATES_FOUND=[]
STATUS_COLUMN_CANDIDATES_FOUND=["status","deleted_at"]
CURRENCY_COLUMN_CANDIDATES_FOUND=["price_currency"]
PRODUCT_COUNT=30
STOCK_PROFILE_stock_quantity=["positive_rows=24","sum=150"]
PURCHASE_PRICE_PROFILE_purchase_price_rsd=["positive=30","null=0","zero_or_negative=0"]
TABLE_PRESENT_settings=YES
TABLE_COLUMNS_settings=["id","setting_key","setting_value","updated_by","created_at","updated_at"]
TABLE_PRESENT_stock_movements=YES
TABLE_COLUMNS_stock_movements=["id","event_key","product_id","order_id","user_id","movement_type","source","quantity_change","quantity_before","quantity_after","note","metadata_json","created_at","stock_receipt_id","inventory_count_id"]
TABLE_PRESENT_inventory_counts=YES
TABLE_COLUMNS_inventory_counts=["id","count_number","status","scope_label","counted_on","note","total_variance","created_by","finalized_by","finalized_at","created_at","updated_at"]
TABLE_PRESENT_inventory_count_items=YES
TABLE_COLUMNS_inventory_count_items=["id","inventory_count_id","product_id","product_sku","product_name","system_quantity","counted_quantity","variance","note","created_at","updated_at"]
SERVICE_App\Services\ExchangeRateService=YES
SERVICE_METHODS_App\Services\ExchangeRateService=["__construct","configuration","saveManual","setAutomatic","updateAutomatically"]
SERVICE_App\Services\ManagementReportService=YES
SERVICE_METHODS_App\Services\ManagementReportService=["__construct","afterSales","build","csv","inventory","normalizeFilters","pdf","readinessIssues","receivables","segments","summary","teamPerformance","trend"]
SERVICE_App\Services\InventoryService=YES
SERVICE_METHODS_App\Services\InventoryService=["__construct","adjust"]
SERVICE_App\Services\AdvancedInventoryService=YES
SERVICE_METHODS_App\Services\AdvancedInventoryService=["__construct","finalizeCount","receive"]
USERS_ROLE_COLUMNS=["role_id"]
ROLES_COLUMNS=["id","name","slug","created_at"]
ROLE_VALUES=["Korisnik","Administrator","SuperAdmin"]
RELEVANT_PERMISSION_VALUES=[]
DATABASE_WRITES_DURING_AUDIT=0
LARAVEL_RUNTIME_SCHEMA_AUDIT=PASS

============================================================
3. ROUTE / API / SUPERADMIN ACCESS TOPOLOGY
============================================================


  The "--columns" option does not exist.



  GET|HEAD  api/v1/admin/after-sales .............................................................. api.v1.admin.after-sales.index › Api\V1\Admin\AfterSalesController@index
  GET|HEAD  api/v1/admin/after-sales/attachments/{attachment} ...................... api.v1.admin.after-sales.attachments.show › Api\V1\Admin\AfterSalesAttachmentController
  GET|HEAD  api/v1/admin/after-sales/{case} ......................................................... api.v1.admin.after-sales.show › Api\V1\Admin\AfterSalesController@show
  PATCH     api/v1/admin/after-sales/{case} ..................................................... api.v1.admin.after-sales.update › Api\V1\Admin\AfterSalesController@update
  POST      api/v1/admin/after-sales/{case}/actions ................................. api.v1.admin.after-sales.actions.store › Api\V1\Admin\AfterSalesActionController@store
  POST      api/v1/admin/after-sales/{case}/actions/{action}/cancel ............... api.v1.admin.after-sales.actions.cancel › Api\V1\Admin\AfterSalesActionController@cancel
  POST      api/v1/admin/after-sales/{case}/actions/{action}/complete ......... api.v1.admin.after-sales.actions.complete › Api\V1\Admin\AfterSalesActionController@complete
  POST      api/v1/admin/after-sales/{case}/actions/{action}/start .................. api.v1.admin.after-sales.actions.start › Api\V1\Admin\AfterSalesActionController@start
  POST      api/v1/admin/after-sales/{case}/messages ................................... api.v1.admin.after-sales.messages.store › Api\V1\Admin\AfterSalesController@message
  GET|HEAD  api/v1/admin/audit-events ............................................................ api.v1.admin.audit-events.index › Api\V1\Admin\AuditEventController@index
  GET|HEAD  api/v1/admin/audit-events/{event} ...................................................... api.v1.admin.audit-events.show › Api\V1\Admin\AuditEventController@show
  GET|HEAD  api/v1/admin/catalog/brands ...................................................... api.v1.admin.catalog.brands.index › Api\V1\Admin\CatalogBrandController@index
  POST      api/v1/admin/catalog/brands ...................................................... api.v1.admin.catalog.brands.store › Api\V1\Admin\CatalogBrandController@store
  GET|HEAD  api/v1/admin/catalog/brands/options .......................................... api.v1.admin.catalog.brands.options › Api\V1\Admin\CatalogBrandController@options
  PUT       api/v1/admin/catalog/brands/{brand} ............................................ api.v1.admin.catalog.brands.update › Api\V1\Admin\CatalogBrandController@update
  GET|HEAD  api/v1/admin/catalog/options ...................................................... api.v1.admin.catalog.options › Api\V1\Admin\CatalogProductController@options
  GET|HEAD  api/v1/admin/catalog/products ................................................ api.v1.admin.catalog.products.index › Api\V1\Admin\CatalogProductController@index
  POST      api/v1/admin/catalog/products ................................................ api.v1.admin.catalog.products.store › Api\V1\Admin\CatalogProductController@store
  GET|HEAD  api/v1/admin/catalog/products/archived ................................. api.v1.admin.catalog.products.archived › Api\V1\Admin\CatalogProductController@archived
  GET|HEAD  api/v1/admin/catalog/products/{product} ........................................ api.v1.admin.catalog.products.show › Api\V1\Admin\CatalogProductController@show
  PUT       api/v1/admin/catalog/products/{product} .................................... api.v1.admin.catalog.products.update › Api\V1\Admin\CatalogProductController@update
  POST      api/v1/admin/catalog/products/{product}/archive .......................... api.v1.admin.catalog.products.archive › Api\V1\Admin\CatalogProductController@archive
  POST      api/v1/admin/catalog/products/{product}/direct-sale ......... api.v1.admin.catalog.products.direct-sale.store › Api\V1\Admin\CatalogProductController@directSale
  GET|HEAD  api/v1/admin/catalog/products/{product}/direct-sale/options api.v1.admin.catalog.products.direct-sale.options › Api\V1\Admin\CatalogProductController@directSal…
  GET|HEAD  api/v1/admin/catalog/products/{product}/images ................... api.v1.admin.catalog.products.images.index › Api\V1\Admin\CatalogProductController@imageIndex
  POST      api/v1/admin/catalog/products/{product}/images ....................... api.v1.admin.catalog.products.images.store › Api\V1\Admin\CatalogProductController@images
  POST      api/v1/admin/catalog/products/{product}/images/reorder ....... api.v1.admin.catalog.products.images.reorder › Api\V1\Admin\CatalogProductController@imageReorder
  DELETE    api/v1/admin/catalog/products/{product}/images/{image} ....... api.v1.admin.catalog.products.images.destroy › Api\V1\Admin\CatalogProductController@imageDestroy
  POST      api/v1/admin/catalog/products/{product}/images/{image}/primary api.v1.admin.catalog.products.images.primary › Api\V1\Admin\CatalogProductController@imagePrimary
  POST      api/v1/admin/catalog/products/{product}/images/{image}/rotate .. api.v1.admin.catalog.products.images.rotate › Api\V1\Admin\CatalogProductController@imageRotate
  POST      api/v1/admin/catalog/products/{product}/restore .......................... api.v1.admin.catalog.products.restore › Api\V1\Admin\CatalogProductController@restore
  GET|HEAD  api/v1/admin/commissions .............................................................. api.v1.admin.commissions.index › Api\V1\Admin\CommissionController@index
  GET|HEAD  api/v1/admin/commissions.csv .............................................................. api.v1.admin.commissions.csv › Api\V1\Admin\CommissionController@csv
  GET|HEAD  api/v1/admin/commissions.pdf .............................................................. api.v1.admin.commissions.pdf › Api\V1\Admin\CommissionController@pdf
  POST      api/v1/admin/commissions/bulk-pay ................................................ api.v1.admin.commissions.bulk-pay › Api\V1\Admin\CommissionController@bulkPay
  GET|HEAD  api/v1/admin/commissions/{commission} ................................................... api.v1.admin.commissions.show › Api\V1\Admin\CommissionController@show
  PATCH     api/v1/admin/commissions/{commission}/status ................................ api.v1.admin.commissions.transition › Api\V1\Admin\CommissionController@transition
  GET|HEAD  api/v1/admin/couriers ................................................................ api.v1.admin.couriers.index › Api\V1\Admin\CourierServiceController@index
  POST      api/v1/admin/couriers ................................................................ api.v1.admin.couriers.store › Api\V1\Admin\CourierServiceController@store
  PUT       api/v1/admin/couriers/{courier} .................................................... api.v1.admin.couriers.update › Api\V1\Admin\CourierServiceController@update
  GET|HEAD  api/v1/admin/exchange-rate ........................................................ api.v1.admin.exchange-rate.index › Api\V1\Admin\ExchangeRateController@index
  PUT       api/v1/admin/exchange-rate/automatic ...................................... api.v1.admin.exchange-rate.automatic › Api\V1\Admin\ExchangeRateController@automatic
  PUT       api/v1/admin/exchange-rate/manual ............................................... api.v1.admin.exchange-rate.manual › Api\V1\Admin\ExchangeRateController@manual
  POST      api/v1/admin/exchange-rate/refresh ............................................ api.v1.admin.exchange-rate.refresh › Api\V1\Admin\ExchangeRateController@refresh
  GET|HEAD  api/v1/admin/field-service-teams ........................................ api.v1.admin.field-service-teams.index › Api\V1\Admin\FieldServiceTeamController@index
  GET|HEAD  api/v1/admin/field-work ........................................................... api.v1.admin.field-work.index › Api\V1\Admin\FieldOperationsController@index
  GET|HEAD  api/v1/admin/field-work/{workOrder} ................................................. api.v1.admin.field-work.show › Api\V1\Admin\FieldOperationsController@show
  POST      api/v1/admin/field-work/{workOrder}/cancel ...................................... api.v1.admin.field-work.cancel › Api\V1\Admin\FieldOperationsController@cancel
  POST      api/v1/admin/field-work/{workOrder}/complete ................................ api.v1.admin.field-work.complete › Api\V1\Admin\FieldOperationsController@complete
  POST      api/v1/admin/field-work/{workOrder}/en-route ................................. api.v1.admin.field-work.en-route › Api\V1\Admin\FieldOperationsController@enRoute
  POST      api/v1/admin/field-work/{workOrder}/on-site .................................... api.v1.admin.field-work.on-site › Api\V1\Admin\FieldOperationsController@onSite
  POST      api/v1/admin/field-work/{workOrder}/parts ................................ api.v1.admin.field-work.parts.store › Api\V1\Admin\FieldWorkOrderPartController@store
  POST      api/v1/admin/field-work/{workOrder}/parts/reserve .................... api.v1.admin.field-work.parts.reserve › Api\V1\Admin\FieldWorkOrderPartController@reserve
  DELETE    api/v1/admin/field-work/{workOrder}/parts/{line} ..................... api.v1.admin.field-work.parts.destroy › Api\V1\Admin\FieldWorkOrderPartController@destroy
  PATCH     api/v1/admin/field-work/{workOrder}/schedule ................................ api.v1.admin.field-work.schedule › Api\V1\Admin\FieldOperationsController@schedule
  GET|HEAD  api/v1/admin/foundation ....................................................................... api.v1.admin.foundation › Api\V1\Admin\FoundationController@show
  GET|HEAD  api/v1/admin/inventory ................................................................... api.v1.admin.inventory.index › Api\V1\Admin\InventoryController@index
  GET|HEAD  api/v1/admin/inventory.csv ................................................................... api.v1.admin.inventory.csv › Api\V1\Admin\InventoryController@csv
  POST      api/v1/admin/inventory/counts ..................................................... api.v1.admin.inventory.counts.store › Api\V1\Admin\InventoryController@count
  POST      api/v1/admin/inventory/receipts ............................................... api.v1.admin.inventory.receipts.store › Api\V1\Admin\InventoryController@receive
  GET|HEAD  api/v1/admin/orders ............................................................................. api.v1.admin.orders.index › Api\V1\Admin\OrderController@index
  GET|HEAD  api/v1/admin/orders/{order} ....................................................................... api.v1.admin.orders.show › Api\V1\Admin\OrderController@show
  POST      api/v1/admin/orders/{order}/accept .................................................... api.v1.admin.orders.accept › Api\V1\Admin\OrderMutationController@accept
  POST      api/v1/admin/orders/{order}/complete .............................................. api.v1.admin.orders.complete › Api\V1\Admin\OrderMutationController@complete
  PATCH     api/v1/admin/orders/{order}/deadlines ........................................... api.v1.admin.orders.deadlines › Api\V1\Admin\OrderMutationController@deadlines
  POST      api/v1/admin/orders/{order}/internal-notes ........................ api.v1.admin.orders.internal-notes.store › Api\V1\Admin\OrderMutationController@internalNote
  PATCH     api/v1/admin/orders/{order}/payment-status ............................. api.v1.admin.orders.payment-status › Api\V1\Admin\OrderMutationController@paymentStatus
  POST      api/v1/admin/orders/{order}/payments .................................... api.v1.admin.orders.payments.store › Api\V1\Admin\OrderMutationController@paymentStore
  POST      api/v1/admin/orders/{order}/payments/{payment}/reject ................. api.v1.admin.orders.payments.reject › Api\V1\Admin\OrderMutationController@paymentReject
  POST      api/v1/admin/orders/{order}/payments/{payment}/verify ................. api.v1.admin.orders.payments.verify › Api\V1\Admin\OrderMutationController@paymentVerify
  POST      api/v1/admin/orders/{order}/payments/{payment}/void ....................... api.v1.admin.orders.payments.void › Api\V1\Admin\OrderMutationController@paymentVoid
  PATCH     api/v1/admin/orders/{order}/reassign .............................................. api.v1.admin.orders.reassign › Api\V1\Admin\OrderMutationController@reassign
  POST      api/v1/admin/orders/{order}/reopen .................................................... api.v1.admin.orders.reopen › Api\V1\Admin\OrderMutationController@reopen
  POST      api/v1/admin/orders/{order}/shipment ........................................... api.v1.admin.orders.shipment.store › Api\V1\Admin\OrderShipmentController@store
  GET|HEAD  api/v1/admin/orders/{order}/shipment-proof ..................................... api.v1.admin.orders.shipment.proof › Api\V1\Admin\OrderShipmentController@proof
  PATCH     api/v1/admin/orders/{order}/status .................................................... api.v1.admin.orders.status › Api\V1\Admin\OrderMutationController@status
  GET|HEAD  api/v1/admin/receivables ............................................................. api.v1.admin.receivables.index › Api\V1\Admin\ReceivablesController@index
  GET|HEAD  api/v1/admin/receivables/export.csv ............................................................. api.v1.admin.receivables.csv › Admin\ReceivablesController@csv
  POST      api/v1/admin/receivables/scan .......................................................... api.v1.admin.receivables.scan › Api\V1\Admin\ReceivablesController@scan
  PUT       api/v1/admin/receivables/settings ................................. api.v1.admin.receivables.settings.update › Api\V1\Admin\ReceivablesController@updateSettings
  GET|HEAD  api/v1/admin/receivables/{receivable} .................................................. api.v1.admin.receivables.show › Api\V1\Admin\ReceivablesController@show
  PATCH     api/v1/admin/receivables/{receivable} .............................................. api.v1.admin.receivables.update › Api\V1\Admin\ReceivablesController@update
  POST      api/v1/admin/receivables/{receivable}/contacts ............................ api.v1.admin.receivables.contacts.store › Api\V1\Admin\ReceivablesController@contact
  PUT       api/v1/admin/receivables/{receivable}/plan ............................................. api.v1.admin.receivables.plan › Api\V1\Admin\ReceivablesController@plan
  POST      api/v1/admin/receivables/{receivable}/reminder ................................. api.v1.admin.receivables.reminder › Api\V1\Admin\ReceivablesController@reminder
  POST      api/v1/admin/report-deliveries/{delivery}/retry ............................. api.v1.admin.report-deliveries.retry › Api\V1\Admin\ReportScheduleController@retry
  GET|HEAD  api/v1/admin/report-schedules ................................................ api.v1.admin.report-schedules.index › Api\V1\Admin\ReportScheduleController@index
  POST      api/v1/admin/report-schedules ................................................ api.v1.admin.report-schedules.store › Api\V1\Admin\ReportScheduleController@store
  PATCH     api/v1/admin/report-schedules/{schedule} ................................... api.v1.admin.report-schedules.update › Api\V1\Admin\ReportScheduleController@update
  DELETE    api/v1/admin/report-schedules/{schedule} ................................. api.v1.admin.report-schedules.destroy › Api\V1\Admin\ReportScheduleController@destroy
  POST      api/v1/admin/report-schedules/{schedule}/run ..................................... api.v1.admin.report-schedules.run › Api\V1\Admin\ReportScheduleController@run
  PATCH     api/v1/admin/report-schedules/{schedule}/toggle ............................ api.v1.admin.report-schedules.toggle › Api\V1\Admin\ReportScheduleController@toggle
  GET|HEAD  api/v1/admin/reports/management ..................................................... api.v1.admin.reports.management › Api\V1\Admin\ReportController@management
  GET|HEAD  api/v1/admin/reports/management.csv .......................................... api.v1.admin.reports.management.csv › Api\V1\Admin\ReportController@managementCsv
  GET|HEAD  api/v1/admin/reports/management.pdf .......................................... api.v1.admin.reports.management.pdf › Api\V1\Admin\ReportController@managementPdf
  GET|HEAD  api/v1/admin/service-part-purchases ............................. api.v1.admin.service-part-purchases.index › Api\V1\Admin\ServicePartsController@purchasesIndex
  POST      api/v1/admin/service-part-purchases .............................. api.v1.admin.service-part-purchases.store › Api\V1\Admin\ServicePartsController@purchaseStore
  GET|HEAD  api/v1/admin/service-part-purchases/{purchaseRequest} .............. api.v1.admin.service-part-purchases.show › Api\V1\Admin\ServicePartsController@purchaseShow
  POST      api/v1/admin/service-part-purchases/{purchaseRequest}/cancel ... api.v1.admin.service-part-purchases.cancel › Api\V1\Admin\ServicePartsController@purchaseCancel
  POST      api/v1/admin/service-part-purchases/{purchaseRequest}/order ...... api.v1.admin.service-part-purchases.order › Api\V1\Admin\ServicePartsController@purchaseOrder
  POST      api/v1/admin/service-part-purchases/{purchaseRequest}/receive api.v1.admin.service-part-purchases.receive › Api\V1\Admin\ServicePartsController@purchaseReceive
  POST      api/v1/admin/service-part-purchases/{purchaseRequest}/submit ... api.v1.admin.service-part-purchases.submit › Api\V1\Admin\ServicePartsController@purchaseSubmit
  GET|HEAD  api/v1/admin/service-part-suppliers ............................. api.v1.admin.service-part-suppliers.index › Api\V1\Admin\ServicePartsController@suppliersIndex
  POST      api/v1/admin/service-part-suppliers .............................. api.v1.admin.service-part-suppliers.store › Api\V1\Admin\ServicePartsController@supplierStore
  PUT       api/v1/admin/service-part-suppliers/{supplier} ................. api.v1.admin.service-part-suppliers.update › Api\V1\Admin\ServicePartsController@supplierUpdate
  GET|HEAD  api/v1/admin/service-parts ................................................... api.v1.admin.service-parts.index › Api\V1\Admin\ServicePartsController@partsIndex
  POST      api/v1/admin/service-parts .................................................... api.v1.admin.service-parts.store › Api\V1\Admin\ServicePartsController@partStore
  PUT       api/v1/admin/service-parts/{part} ........................................... api.v1.admin.service-parts.update › Api\V1\Admin\ServicePartsController@partUpdate
  POST      api/v1/admin/service-parts/{part}/adjust .................................... api.v1.admin.service-parts.adjust › Api\V1\Admin\ServicePartsController@partAdjust
  GET|HEAD  api/v1/admin/stock-movements ................................................... api.v1.admin.stock-movements.index › Api\V1\Admin\InventoryController@movements
  POST      api/v1/admin/stock/{product}/adjust ........................................................ api.v1.admin.stock.adjust › Api\V1\Admin\InventoryController@adjust
  GET|HEAD  api/v1/admin/system-health ........................................................ api.v1.admin.system-health.index › Api\V1\Admin\SystemHealthController@index
  GET|HEAD  api/v1/admin/users ................................................................................ api.v1.admin.users.index › Api\V1\Admin\UserController@index
  POST      api/v1/admin/users ................................................................................ api.v1.admin.users.store › Api\V1\Admin\UserController@store
  GET|HEAD  api/v1/admin/users/options .................................................................... api.v1.admin.users.options › Api\V1\Admin\UserController@options
  GET|HEAD  api/v1/admin/users/{user} ........................................................................... api.v1.admin.users.show › Api\V1\Admin\UserController@show
  PUT       api/v1/admin/users/{user} ....................................................................... api.v1.admin.users.update › Api\V1\Admin\UserController@update
  GET|HEAD  api/v1/admin/warranties .................................................................. api.v1.admin.warranties.index › Api\V1\Admin\WarrantyController@index
  POST      api/v1/admin/warranties/backfill ................................................... api.v1.admin.warranties.backfill › Api\V1\Admin\WarrantyController@backfill
  GET|HEAD  api/v1/admin/warranties/rules ...................................................... api.v1.admin.warranties.rules.index › Api\V1\Admin\WarrantyController@rules
  POST      api/v1/admin/warranties/rules .................................................. api.v1.admin.warranties.rules.store › Api\V1\Admin\WarrantyController@storeRule
  PUT       api/v1/admin/warranties/rules/{rule} ......................................... api.v1.admin.warranties.rules.update › Api\V1\Admin\WarrantyController@updateRule
  GET|HEAD  api/v1/admin/warranties/{warranty} ......................................................... api.v1.admin.warranties.show › Api\V1\Admin\WarrantyController@show
  PUT       api/v1/admin/warranties/{warranty} ..................................................... api.v1.admin.warranties.update › Api\V1\Admin\WarrantyController@update
  GET|HEAD  api/v1/admin/warranties/{warranty}.pdf ....................................................... api.v1.admin.warranties.pdf › Api\V1\Admin\WarrantyController@pdf
  POST      api/v1/admin/warranties/{warranty}/maintenance/{record}/complete ....... api.v1.admin.warranties.maintenance.complete › Api\V1\Admin\WarrantyController@complete
  POST      api/v1/admin/warranties/{warranty}/maintenance/{record}/schedule ....... api.v1.admin.warranties.maintenance.schedule › Api\V1\Admin\WarrantyController@schedule
  POST      api/v1/admin/warranties/{warranty}/void .................................................... api.v1.admin.warranties.void › Api\V1\Admin\WarrantyController@void

                                                                                                                                                        Showing [128] routes

--- SUPERADMIN / ROLE / PERMISSION SOURCE SIGNALS ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:136:        if (!$actor->hasRole('admin', 'superadmin')) $visibility = 'public';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:155:                if ($actor->hasRole('admin', 'superadmin') && $visibility === 'public' && $locked->first_response_at === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:158:                if (!$actor->hasRole('admin', 'superadmin') && $locked->status === 'awaiting_customer') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:192:                $assignee = User::query()->where('status', 'active')->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->find((int) $data['assigned_to']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:276:            'closed' => $actor->hasRole('superadmin') ? ['under_review'] : [],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:337:                ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:350:            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:364:                ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:382:        $recipient = $actor->hasRole('admin', 'superadmin') ? $case->opener : $case->assignee;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:388:                'url' => $recipient->hasRole('admin', 'superadmin') ? route('admin.after-sales.show', $case) : route('after-sales.show', $case),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:32:        $staff = $user->hasRole('admin', 'superadmin');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesAccessService.php:17:        if ($user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesAccessService.php:33:        if ($user->hasRole('superadmin')) return true;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesAccessService.php:45:            && ($user->hasRole('superadmin')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:33:        abort_unless($actor->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:47:            'reassign' => $actor->hasRole('superadmin') && $this->allows($actor, 'orders.reassign'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:49:            'reopen' => $actor->hasRole('superadmin') && $this->allows($actor, 'orders.reopen'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:52:            'courier_settings' => $actor->hasRole('superadmin') && $this->allows($actor, 'system.manage_settings'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:294:                'reopened_by' => $this->userName($reopenedBy instanceof User ? $reopenedBy : null, 'SuperAdministrator'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:83:            'stock_alerts' => $user->hasRole('admin', 'superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:84:            'daily_digest' => $user->hasRole('admin', 'superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderAccessService.php:16:        if ($user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderAccessService.php:29:        return $user->hasRole('superadmin')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:38:        abort_unless($actor->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:182:        $issues[] = $this->issue('products_without_owner', 'Artikli bez vlasnika', 'info', $missingOwner, 'Artikle bez vlasnika može da uređuje samo SuperAdministrator.', false, $this->rows($ownerSamples), 'unassigned');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:460:        abort_unless($actor->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyAdminService.php:119:        if (!$actor->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:160:        if ($actor->hasRole('superadmin')) return;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:168:        if ($actor->hasRole('superadmin')) return;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:177:            'paid' => $actor->hasRole('superadmin') ? ['cancelled'] : [],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:370:        if ($user->hasRole('superadmin') && (int) $filters['supplier_user_id'] > 0) $query->where('supplier_user_id', (int) $filters['supplier_user_id']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogAccessService.php:16:        if ($user->hasRole('admin', 'superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogAccessService.php:55:     * - SuperAdministrator vidi sve operativne (nearhivirane) artikle;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogAccessService.php:63:            if ($user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogAccessService.php:82:        if ($user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogAccessService.php:110:        return $user->hasRole('superadmin')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CourierDirectoryService.php:121:        abort_unless($actor->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:124:            if ($user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:145:                'unassigned' => $user->hasRole('superadmin') ? $query->whereNull('created_by') : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionReportService.php:26:        if (!$actor->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionReportService.php:29:        return $this->filters($query, $filters, $actor->hasRole('superadmin'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php:77:        abort_unless($actor->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php:82:        if (!$newSupplier->hasRole('admin', 'superadmin') || $newSupplier->status !== 'active') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php:83:            throw ValidationException::withMessages(['supplier_user_id' => 'Izabrano odgovorno lice nije aktivan Administrator ili SuperAdministrator.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderIndexService.php:96:        if ($actor->hasRole('superadmin') && (int) ($filters['supplier_user_id'] ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderIndexService.php:136:        if (!$actor->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderIndexService.php:142:            ->whereHas('role', static fn (Builder $query): Builder => $query->whereIn('slug', ['superadmin', 'admin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:210:        if (!$actor instanceof User || !$actor->hasPermission('reports.view')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:211:            $actor = User::query()->where('status', 'active')->whereHas('role', static fn ($q) => $q->where('slug', 'superadmin'))->orderBy('id')->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderReportService.php:96:        if (!empty($filters['supplier_user_id']) && $user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdminUserService.php:68:            if ($locked->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdminUserService.php:69:                $removesEffectiveSuperAdmin = (string) $newRole->slug !== 'superadmin' || $nextStatus !== 'active';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdminUserService.php:75:                        ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PortalConversationService.php:197:            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:242:            ->whereHas('role', static fn (Builder $role) => $role->whereIn('slug', ['superadmin', 'admin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:256:            ->sortBy(static fn (User $candidate): int => $candidate->hasRole('superadmin') ? 0 : 1)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:259:            throw ValidationException::withMessages(['supplier_user_id' => 'Nema aktivnog SuperAdministratora ili Administratora koji može primiti porudžbinu.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:129:                $add($order->user->email, $order->user->displayName(), $order->user->id, $order->user->hasRole('admin', 'superadmin'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:280:            $add($order->user->email, $order->user->displayName(), $order->user->id, $order->user->hasRole('admin', 'superadmin'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:379:            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->exists();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:420:                'url' => $recipient->hasRole('admin', 'superadmin') ? route('admin.after-sales.show', $action->case) : route('after-sales.show', $action->case),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:301:            if (!$actor->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:508:            $actor->hasPermission('stock.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:516:            $actor->hasPermission('reports.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:267:                        return $user->hasRole('superadmin') || (int) $user->id === (int) $case->assigned_to;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:318:                        return $user->hasRole('superadmin') || (int) $user->id === (int) $action->assigned_to;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:366:                        return $user->hasRole('superadmin') || (int) $user->id === (int) $workOrder->action?->assigned_to;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:411:                        return $user->hasRole('superadmin') || (int) $user->id === (int) $workOrder->action?->assigned_to;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:676:            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:684:            return $user->hasRole('superadmin') || (int) $user->id === (int) $order->supplier_user_id;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:25:            'url' => $recipient->hasRole('admin', 'superadmin')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:108:                'stock_alerts' => $recipient->hasRole('admin', 'superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:109:                'daily_digest' => $recipient->hasRole('admin', 'superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:115:        if (!$actor->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/User.php:90:            if ($this->hasRole('admin', 'superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/User.php:125:            return $this->hasRole('admin', 'superadmin') ? ['*'] : [];
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:158:            if ($user->hasRole('superadmin', 'admin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:172:        Gate::define('catalog.manage_taxonomy', static fn (User $user): bool => $user->hasPermission('catalog.manage_taxonomy'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:195:        Gate::define('stock.view', static fn (User $user): bool => $user->hasPermission('stock.view'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:199:        Gate::define('reports.view', static fn (User $user): bool => $user->hasPermission('reports.view'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/BrandManagerRequest.php:16:        return $user !== null && $user->can('catalog.manage_taxonomy');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DirectSaleController.php:19:        abort_unless($actor !== null && $actor->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CourierServiceController.php:61:        abort_unless($request->user()?->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportScheduleController.php:57:        if ($delivery->schedule) $this->authorizeSchedule($request, $delivery->schedule); else abort_unless($request->user()?->hasRole('superadmin'), 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportScheduleController.php:65:        abort_unless($user->hasRole('superadmin') || (int) $schedule->created_by === (int) $user->id, 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:55:        if ($user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:138:            if (!$user->hasRole('superadmin')) $query->where('supplier_user_id', $user->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:205:        if (!$user->hasRole('superadmin')) $orders->where('supplier_user_id', $user->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:246:            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:264:        if (!$user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/BrandManagerController.php:76:        abort_unless($actor->can('catalog.manage_taxonomy'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php:377:        return $request->user()?->hasRole('superadmin') === true;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:95:                    ['source' => 'superadmin_fast_purchase_cost_entry'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductPurchaseCostController.php:110:        abort_unless($request->user()?->hasRole('superadmin') === true, 403, 'Brzi unos nabavnih cena dostupan je samo Super Administratoru.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CommissionController.php:36:                'users' => $actor->hasRole('superadmin') ? $this->users() : collect(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CommissionController.php:37:                'suppliers' => $actor->hasRole('superadmin') ? $this->suppliers() : collect(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CommissionController.php:117:        return User::query()->where('status', 'active')->whereHas('role', static fn ($role) => $role->whereIn('slug', ['admin', 'superadmin']))->with('role')->orderBy('first_name')->orderBy('last_name')->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/PortalConversationController.php:37:            'staffUsers' => User::query()->where('status', 'active')->whereHas('role', static fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))->orderBy('first_name')->orderBy('username')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/PortalConversationController.php:91:                ->whereHas('role', static fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ManagementReportController.php:31:                $schedules = ReportSchedule::query()->with('creator')->when(!$user->hasRole('superadmin'), static fn ($q) => $q->where('created_by', $user->id))->latest('id')->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ManagementReportController.php:34:                $deliveries = ReportDelivery::query()->with('schedule')->when(!$user->hasRole('superadmin'), static fn ($q) => $q->whereHas('schedule', static fn ($s) => $s->where('created_by', $user->id)))->latest('id')->limit(30)->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ManagementReportController.php:40:        $suppliers = $user->hasRole('superadmin') ? User::query()->where('status', 'active')->whereHas('role', static fn ($q) => $q->whereIn('slug', ['admin', 'superadmin']))->with('role')->orderBy('first_name')->orderBy('username')->get() : collect();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:102:            $suppliers = $actor->hasRole('superadmin') ? $this->suppliers() : collect();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:167:            'canPurge' => $request->user()->hasRole('superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:286:        abort_unless($request->user()->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:481:            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AfterSalesController.php:64:            'assignees' => User::query()->where('status', 'active')->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->with('role')->orderBy('first_name')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/WarrantyController.php:36:        if (!$request->user()->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/WarrantyController.php:54:        if (!$request->user()->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/WarrantyController.php:154:        if ($request->user()->hasRole('superadmin')) return;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ModuleSettingsController.php:49:        abort_unless($request->user()?->hasRole('superadmin') === true, 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldOperationsController.php:43:        if (!$request->user()->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldOperationsController.php:68:        if (!$request->user()->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SettingsHubController.php:23:        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SettingsHubController.php:29:            'catalog.manage_taxonomy',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReceivablesController.php:82:            'assignees' => User::query()->with('role')->where('status', 'active')->whereHas('role', fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))->orderBy('first_name')->orderBy('username')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReceivablesController.php:99:            'assignees' => User::query()->with('role')->where('status', 'active')->whereHas('role', fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))->orderBy('first_name')->orderBy('username')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReceivablesController.php:229:        if ($user->hasRole('superadmin')) return;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReceivablesController.php:302:        if (!$request->user()->hasRole('superadmin')) $query->where('supplier_user_id', $request->user()->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReceivablesController.php:308:        if ($request->user()->hasRole('superadmin')) return;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/FieldWorkOrderAttachmentController.php:20:        $isAdmin = $user->hasRole('admin', 'superadmin');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:47:            'stock_view' => $this->allows($user, 'stock.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:58:            'reports_view' => $this->allows($user, 'reports.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:72:        if ($user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:179:        if (!($access['reports_view'] ?? false)) return $fallback;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php:43:            'permissions' => ['reports.view', 'reports.export', 'reports.manage'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php:59:                'stock.view',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php:91:                'catalog.manage_taxonomy',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php:151:        if ($user->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CourierServiceController.php:43:        abort_unless($actor->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportController.php:122:        abort_unless($user->can('reports.view'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/InventoryController.php:84:                'can_view' => (bool) $actor?->can('stock.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:284:        abort_unless($actor->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:339:        abort_unless($actor->hasRole('superadmin'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CommissionController.php:50:                'can_filter_people' => $actor->hasRole('superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CommissionController.php:51:                'can_cancel_paid' => $actor->hasRole('superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CommissionController.php:190:            'users' => $actor->hasRole('superadmin') ? $this->users() : [],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CommissionController.php:191:            'suppliers' => $actor->hasRole('superadmin') ? $this->suppliers() : [],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CommissionController.php:213:            ->whereHas('role', static fn ($role) => $role->whereIn('slug', ['admin', 'superadmin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CommissionController.php:300:            'paid' => $actor->hasRole('superadmin') ? ['cancelled'] : [],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php:128:                'reassign' => $actor->hasRole('superadmin') && $actor->can('orders.reassign'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/UserController.php:177:        $effectiveAccess = in_array($roleSlug, ['admin', 'superadmin'], true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/UserController.php:205:            'last_active_superadmin_protected' => $roleSlug === 'superadmin'
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/UserController.php:228:            ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php:260:                    ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogBrandController.php:75:        abort_unless($actor->can('catalog.manage_taxonomy'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/WarrantyController.php:382:        if (!$actor->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:78:        abort_unless($actor->hasRole('superadmin') && $actor->can('orders.reassign'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReceivablesController.php:217:        if ($actor->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReceivablesController.php:229:        if ($actor->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReceivablesController.php:440:            ->whereHas('role', static fn (Builder $roles) => $roles->whereIn('slug', ['admin', 'superadmin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/GoogleAuthController.php:60:        if ($user->hasRole('admin', 'superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AuthTokenController.php:91:        if ($user->hasRole('admin', 'superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AfterSalesController.php:288:                'kind' => $message->user->hasRole('admin', 'superadmin') ? 'staff' : 'customer',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderOptionsController.php:21:            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderOptionsController.php:24:            ->sortBy(static fn (User $supplier): int => $supplier->hasRole('superadmin') ? 0 : 1)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:34:            'stock_alerts' => $user->hasRole('admin', 'superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:35:            'daily_digest' => $user->hasRole('admin', 'superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:69:                ->whereHas('role', static fn ($role) => $role->whereIn('slug', ['superadmin', 'admin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:72:                ->sortBy(static fn (User $supplier): int => $supplier->hasRole('superadmin') ? 0 : 1)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:87:            'isSuperAdministrator' => $user->hasRole('superadmin'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:142:        $canRecordDirectSale = $user->hasRole('superadmin')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/WarrantyController.php:39:            if (!$request->user()->hasRole('superadmin')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AfterSalesDoctorCommand.php:73:            User::query()->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->limit(1)->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:111:            $user = User::query()->where('status', 'active')->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))->with('role')->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ReportsDoctorCommand.php:72:                ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ReportsDoctorCommand.php:78:                $this->error('Nema aktivnog SuperAdministratora ili Administratora za stvarni reports upit.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OperationsDoctorCommand.php:81:        $actor = User::query()->where('status', 'active')->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))->with('role')->orderBy('id')->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:180:        'catalog.manage_taxonomy',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:185:        'stock.view',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:189:        'reports.view',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrdersDoctorCommand.php:146:            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrdersDoctorCommand.php:148:            ->orderByRaw("CASE WHEN roles.slug = 'superadmin' THEN 0 ELSE 1 END")
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:101:                ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:104:                $this->warn('Nema aktivnog SuperAdministratora za render test.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCreateDoctorCommand.php:57:            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CreateSuperAdminCommand.php:15:    protected $signature = 'app:create-superadmin
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CreateSuperAdminCommand.php:43:        $role = Role::query()->where('slug', 'superadmin')->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AuthDoctorCommand.php:82:                    ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:54:        $superadmin = User::query()
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:56:            ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:60:        if (!$superadmin instanceof User) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:61:            $this->line('<fg=red>FAIL</> Nema aktivnog SuperAdministratora za ownership audit.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:66:        if ($product instanceof Product && !$access->canManage($product, $superadmin)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:67:            $this->line('<fg=red>FAIL</> SuperAdministrator nema pravo upravljanja artiklom #'.$product->id.'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:70:        $this->line('<fg=green>PASS</> SuperAdministrator može da upravlja svim artiklima.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:74:            ->whereHas('role', static fn ($query) => $query->where('slug', 'admin'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:106:        $this->line('<fg=green>PASS</> Artikli bez vlasnika su SuperAdministrator-only: '.$withoutOwner.'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:118:            $request = $this->request('/catalog', $superadmin);
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:131:            $legacyRequest = $this->request('/admin/catalog', $superadmin);
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DetailPagesDoctorCommand.php:47:            ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DetailPagesDoctorCommand.php:53:            $this->line('<fg=red>FAIL</> Nema aktivnog SuperAdministratora za detail page audit.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AccessControlDoctorCommand.php:18:    protected $description = 'Proveri role, permission slugove, route zastitu, superadmin nalog i orphan pristupne zapise.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AccessControlDoctorCommand.php:40:            foreach (['user', 'admin', 'superadmin'] as $requiredRole) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AccessControlDoctorCommand.php:56:            $superadminCount = DB::table('users')
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AccessControlDoctorCommand.php:58:                ->where('roles.slug', 'superadmin')
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AccessControlDoctorCommand.php:61:            if ($superadminCount < 1) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AccessControlDoctorCommand.php:62:                $this->error('FAIL Ne postoji aktivan SuperAdministrator.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AccessControlDoctorCommand.php:65:                $this->info('PASS Aktivni SuperAdministratori: '.$superadminCount.'.');
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/users-admin-api.ts:35:  last_active_superadmin_protected: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-access.ts:4:  'catalog.manage_taxonomy',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-access.ts:13:  'stock.view',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-access.ts:17:  'reports.view',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-access.ts:41:export const ADMIN_ROLE_SLUGS = ['admin', 'superadmin'] as const;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/users-admin-form.tsx:52:  const roleHasFullAccess = selectedRole?.slug === 'admin' || selectedRole?.slug === 'superadmin';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/users-admin-form.tsx:108:      {initial?.last_active_superadmin_protected ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/users-admin-form.tsx:110:          <Text style={styles.guardTitle}>Zaštita poslednjeg aktivnog SuperAdministratora</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/users-admin-form.tsx:112:            Ovaj nalog ne može biti degradiran niti blokiran dok ne postoji drugi aktivni SuperAdministrator.
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:39:  const reportsAllowed = can('reports.view');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:213:              <View style={{ flex: 1 }}><Text style={styles.optionTitle}>{supplier.name}</Text><Text style={styles.optionCopy}>{supplier.role ?? 'administrator'}{supplier.phone ? ` · ${supplier.phone}` : ''}</Text></View>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/users/index.tsx:245:        {user.last_active_superadmin_protected ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:142:  const allowed = can('reports.view');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:96:  const canView = can('stock.view');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:58:  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:59:  const inventoryValuation = isSuperAdmin ? foundation.inventory_valuation : null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:121:        {can('catalog.manage_taxonomy') ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:140:        {isSuperAdmin ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:200:        {(can('stock.view') || can('stock.adjust') || can('inventory.receive') || can('inventory.count') || can('inventory.export')) ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:220:        {can('reports.view') ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:64:  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:83:    enabled: isSuperAdmin && validId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id]/direct-sale.tsx:128:  if (!isSuperAdmin) return <UnavailableState title="Direktna prodaja je dostupna samo SuperAdministratoru" />;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:103:  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:590:      {isSuperAdmin && !product.is_archived ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/[id].tsx:595:              SuperAdministrator može evidentirati prodaju ovog artikla direktno iz kataloga. Prodaja koristi postojeći DirectSaleService, umanjuje lager i kreira plaćenu/dostavljenu porudžbinu.
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/brands/index.tsx:33:  const allowed = can('catalog.manage_taxonomy');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/couriers/index.tsx:26:  const allowed = bootstrap?.user.role?.slug === 'superadmin';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/couriers/index.tsx:62:  if (!allowed) return <UnavailableState title="Kurirske službe su dostupne samo SuperAdministratoru" />;
--- EXISTING MOBILE ADMIN FOUNDATION / HOME API ---
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-api.ts:47:    const response = await apiRequest<{ data: AdminFoundation }> ('admin/foundation');
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/system-health-admin-api.ts:13:  metrics: { [key: string]: AdminSystemHealthValue };
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/system-health-admin-api.ts:21:  metrics: { [key: string]: AdminSystemHealthValue };
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:96:        <View style={styles.dashboardSection}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:126:              <View style={styles.kpiGrid}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:183:        <View style={styles.metrics}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:248:      style={({ pressed }) => [styles.kpiPressable, pressed && styles.pressed]}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:250:      <Card style={styles.dashboardMetricCard}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:252:        <Text style={styles.dashboardMetricLabel}>{label}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:253:        <Text style={styles.dashboardMetricValue} numberOfLines={2}>{value}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:254:        <Text style={styles.dashboardMetricMeta}>{meta}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:383:    dashboardSection: { gap: spacing.md },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:404:    kpiGrid: {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:409:    kpiPressable: { width: '48%', flexGrow: 1 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:410:    dashboardMetricCard: {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:415:    dashboardMetricLabel: { ...typography.small, color: theme.muted },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:416:    dashboardMetricValue: { ...typography.h3, color: theme.ink, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:417:    dashboardMetricMeta: { ...typography.small, color: theme.muted },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:465:    metrics: { flexDirection: 'row', gap: spacing.sm },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/system-health/index.tsx:103:  const metrics = Object.entries(report.metrics);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/system-health/index.tsx:168:          {metrics.length === 0 ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/system-health/index.tsx:171:            metrics.map(([key, value]) => (
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/SystemHealthController.php:41:                'metrics' => $this->mapValue($report['metrics'] ?? []),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/SystemHealthController.php:70:                    'metrics' => $this->mapValue($snapshot->getAttribute('metrics_json')),
ACCESS_TOPOLOGY_SCAN=PASS

============================================================
4. IMPLEMENTATION READINESS DECISION
============================================================
STOCK_COLUMN_CANDIDATES_FOUND=["stock_quantity"]
PURCHASE_PRICE_CANDIDATES_FOUND=["purchase_price_rsd"]
SALE_PRICE_CANDIDATES_FOUND=[]
KPI_IMPLEMENTATION_READINESS=BLOCKED_NO_SALE_PRICE_COLUMN_IDENTIFIED
TARGET_CALCULATION_PURCHASE=SUM_POSITIVE_STOCK_TIMES_CANONICAL_PURCHASE_PRICE
TARGET_CALCULATION_SALE=SUM_POSITIVE_STOCK_TIMES_CURRENT_EFFECTIVE_SALE_PRICE
TARGET_INCLUDES_INACTIVE_OR_ARCHIVED_WITH_POSITIVE_PHYSICAL_STOCK=YES
TARGET_MISSING_PURCHASE_PRICE_POLICY=EXCLUDE_FROM_VALUE_AND_EXPOSE_COVERAGE_COUNT_AND_VALUE_GAP
TARGET_MISSING_SALE_PRICE_POLICY=EXCLUDE_FROM_VALUE_AND_EXPOSE_COVERAGE_COUNT
TARGET_API_SECURITY=DO_NOT_RETURN_FINANCIAL_KPIS_TO_NON_SUPERADMIN
TARGET_MOBILE_LAYOUT=TWO_EQUAL_PRIORITY_TILES_BELOW_EXISTING_FOUR_BEFORE_FINANCIAL_PULSE

============================================================
5. FINAL
============================================================
SOURCE_CODE_CHANGES=0
DATABASE_WRITES=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=0
EAS_COMMANDS_RUN=NO
EAS_BUILD_REQUIRED=NO
REPORT=/home/icaffeco/ald1n-project/docs/operations/079-MOBILE-V0.9.0-SUPERADMIN-INVENTORY-VALUE-KPI-TOPOLOGY-AUDIT-BATCH4-20260822-121805.md
MOBILE_V0_9_SUPERADMIN_INVENTORY_VALUE_KPI_TOPOLOGY_AUDIT_BATCH4=PASS
NEXT_ACTION=UPLOAD_079_REPORT_TO_CHAT_THEN_IMPLEMENT_SERVER_SIDE_SUPERADMIN_KPIS_AND_TWO_MOBILE_HOME_TILES
PASS: v0.9 SuperAdmin inventory-value KPI topology audit completed

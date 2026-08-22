
============================================================
100 - MOBILE v0.9.0 DIRECT SALE + DEFERRED PAYMENT + RECEIVABLES TOPOLOGY AUDIT BATCH 5B
============================================================
DATE=Sat Aug 22 14:27:55 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=READ_ONLY_CURRENT_TOPOLOGY_BEFORE_DEFERRED_DIRECT_SALE_IMPLEMENTATION
TARGET=DIRECT_SALE_DEFERRED_PAYMENT_INSTALLMENT_COUNT_FINAL_DUE_DATE_RECEIVABLES_INTEGRATION
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
SOURCE_CODE_CHANGES=NO
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=NO
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO

============================================================
0. PREFLIGHT + 099 PREREQUISITE + CANONICAL NODE
============================================================
PHP_VERSION=8.4.24
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
PREREQUISITE_099=PASS
PREREQUISITE_099_REPORT=/home/icaffeco/ald1n-project/docs/operations/099-MOBILE-V0.9.0-PRODUCT-DETAIL-COMMISSION-DIRECT-SALE-ENTRY-IMPLEMENTATION-BATCH5A-V5-20260822-142320.md
OPENAPI_PRESTATE_PARITY=PASS_3_COPIES
CORE_PHP_LINT=PASS

============================================================
1. DIRECT SALE CURRENT AUTHORITY
============================================================
--- DIRECT SALE SERVICE PUBLIC METHODS / PAYMENT / COMPLETION SIGNALS ---
8:use App\Models\OrderDelivery;
10:use App\Models\OrderPayment;
21:    public function __construct(
26:        private readonly WarrantyService $warranties,
31:    public function record(Product $product, User $actor, array $input, string $idempotencyKey): Order
35:        $paymentMethod = trim((string) ($input['payment_method'] ?? ''));
38:                'payment_method' => 'Izabrani način plaćanja nije dozvoljen za direktnu prodaju.',
55:            'payment_method' => $paymentMethod,
69:            $this->warranties->ensureForOrder($order->loadMissing('user'), $actor);
71:            Log::warning('Direct sale warranty backfill failed', [
89:    /** @param array{product_id:int,buyer_name:string,buyer_phone:?string,quantity:int,sale_price_rsd:float,payment_method:string} $payload */
90:    private function recordInTransaction(Product $product, User $actor, array $payload, string $idempotencyKey): Order
161:            'payment_method' => $payload['payment_method'],
162:            'payment_status' => 'paid',
163:            'payment_state' => 'paid',
164:            'paid_total_rsd' => $lineTotal,
166:            'completed_at' => $soldAt,
198:            'commission_source_snapshot' => 'direct_sale',
199:            'commission_rate_percent_snapshot' => 0,
200:            'commission_unit_eur_snapshot' => 0,
201:            'commission_total_eur_snapshot' => 0,
228:        OrderPayment::query()->create([
234:            'payment_method' => $payload['payment_method'],
243:        OrderDelivery::query()->create([
269:                'payment_state' => 'paid',
271:                'completed_at' => $soldAt->toISOString(),
278:                'payment_method' => $payload['payment_method'],
--- DIRECT SALE API CONTROLLER VALIDATION / OPTIONS ---
278:    public function directSaleOptions(
284:        abort_unless($actor->hasRole('superadmin'), 403);
321:            'payment_methods' => [
333:    public function directSale(
336:        \App\Services\DirectSaleService $sales,
339:        abort_unless($actor->hasRole('superadmin'), 403);
347:            'payment_method' => ['required', \Illuminate\Validation\Rule::in(['cash', 'card', 'bank_transfer', 'other'])],
--- MOBILE DIRECT SALE SCREEN ---
11:import { SelectSheet } from '@/components/ui/select-sheet';
74:  const [paymentMethod, setPaymentMethod] = useState<AdminDirectSalePaymentMethod> ('cash');
78:  const [pendingPayload, setPendingPayload] = useState<AdminDirectSaleInput | null> (null);
97:    mutationFn: (input: AdminDirectSaleInput) => apiAdminCatalog.recordDirectSale(productId, input),
164:      payment_method: paymentMethod,
171:    const payload = pendingPayload;
187:        <Text style={styles.title}>Evidentiraj prodaju</Text>
208:      {!options.can_submit ? (
255:        <SelectSheet
257:          value={paymentMethod}
258:          options={options.payment_methods.map((method) => ({ value: method.value, label: method.label }))}
274:        disabled={!options.can_submit}
276:        Evidentiraj prodaju
284:        message={pendingPayload
285:          ? `Evidentira se ${pendingPayload.quantity} kom. po ${moneyRsd(pendingPayload.sale_price_rsd)}. Lager će odmah biti umanjen.`
--- MOBILE ADMIN CATALOG DIRECT SALE TYPES ---
115:export type AdminDirectSalePaymentMethod = 'cash' | 'card' | 'bank_transfer' | 'other';
117:export type AdminDirectSaleOptions = {
129:  payment_methods: Array<{ value: AdminDirectSalePaymentMethod; label: string }>;
135:export type AdminDirectSaleInput = {
140:  payment_method: AdminDirectSalePaymentMethod;
193:    const response = await apiRequest<{ data: AdminDirectSaleOptions }> (
198:  recordDirectSale: (productId: number, input: AdminDirectSaleInput) =>

============================================================
2. RECEIVABLES CURRENT AUTHORITY
============================================================
--- RECEIVABLES SERVICE PUBLIC METHODS / PLAN CONTRACT ---
25:    public function __construct(
32:    public function ready(): bool
35:            && Schema::hasTable('receivable_installments')
39:    public function ensureForOrder(Order $order, ?User $actor = null): ?ReceivableCase
45:            return $this->syncForOrder($order) ?? $existing;
53:            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
54:            $existing = ReceivableCase::query()->where('order_id', $locked->id)->lockForUpdate()->first();
63:                'next_action_at' => $locked->payment_due_at,
74:    public function update(ReceivableCase $case, User $actor, array $data): ReceivableCase
78:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
82:            $remaining = $this->remaining($locked->order);
108:            return $locked->fresh(['order.user', 'order.supplier', 'assignee', 'installments', 'contacts.user']) ?? $locked;
112:    /** @param list<array{due_at:string,amount_rsd:mixed,note?:string|null}> $installments */
113:    public function replacePlan(ReceivableCase $case, User $actor, array $installments): ReceivableCase
115:        return DB::transaction(function () use ($case, $actor, $installments): ReceivableCase {
117:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
118:            if (!$locked->order instanceof Order) throw ValidationException::withMessages(['installments' => 'Porudžbina nije dostupna.']);
119:            $remaining = $this->remaining($locked->order);
120:            if ($remaining <= 0.004) throw ValidationException::withMessages(['installments' => 'Porudžbina nema preostalo dugovanje.']);
121:            if ($installments === [] || count($installments) > 24) throw ValidationException::withMessages(['installments' => 'Unesi između 1 i 24 rate.']);
122:            if (ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->where('paid_amount_rsd', '>', 0)->exists()) {
123:                throw ValidationException::withMessages(['installments' => 'Plan sa već raspoređenom uplatom ne može se zameniti. Evidentiraj novi dogovor u komunikaciji.']);
129:            foreach ($installments as $index => $row) {
134:                    throw ValidationException::withMessages(['installments' => 'Sve rate moraju imati ispravan datum dospeća.']);
136:                if ($amount <= 0) throw ValidationException::withMessages(['installments' => 'Svaka rata mora imati iznos veći od nule.']);
137:                if ($due->lt(today())) throw ValidationException::withMessages(['installments' => 'Datum rate ne može biti u prošlosti.']);
138:                if ($previous !== null && $due->lt($previous)) throw ValidationException::withMessages(['installments' => 'Datumi rata moraju biti hronološki poređani.']);
145:                    'paid_amount_rsd' => 0,
151:                throw ValidationException::withMessages(['installments' => 'Zbir rata mora biti jednak preostalom dugu '.number_format($remaining, 2, ',', '.').' RSD.']);
157:            $metadata['plan_paid_baseline_rsd'] = round((float) $locked->order->paid_total_rsd, 2);
160:                'status' => 'installment_plan',
167:            $this->audit->log('receivable.plan_created', 'Kreiran plan otplate za '.$locked->case_number, $locked, after: ['installments' => $normalized, 'total_rsd' => $sum], user: $actor);
168:            return $this->syncForOrder($locked->order->fresh() ?? $locked->order) ?? $locked->fresh(['installments']) ?? $locked;
173:    public function addContact(ReceivableCase $case, User $actor, array $data): ReceivableContact
177:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
205:    public function sendReminder(ReceivableCase $case, User $actor, ?string $customMessage = null): ReceivableCase
209:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
211:            $remaining = $this->remaining($locked->order);
242:            return $locked->fresh(['order', 'installments', 'contacts.user', 'assignee']) ?? $locked;
247:    public function runAutomation(bool $force = false): array
256:            ->whereNotNull('payment_due_at')
265:            $case = $this->ensureForOrder($order);
268:            $case = $this->syncForOrder($order->fresh() ?? $order) ?? $case;
286:    public function syncForOrder(Order $order): ?ReceivableCase
294:            $locked = ReceivableCase::query()->lockForUpdate()->findOrFail($case->id);
296:            $freshOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
299:            $baseline = round((float) ($metadata['plan_paid_baseline_rsd'] ?? 0), 2);
302:            $installments = ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->orderBy('sequence_no')->lockForUpdate()->get();
303:            foreach ($installments as $installment) {
309:                    'paid_amount_rsd' => round($allocated, 2),
315:            $remaining = $this->remaining($freshOrder);
319:                $nextInstallment = $installments->first(static fn (ReceivableInstallment $item): bool => $item->status !== 'paid');
320:                $nextAction = $locked->promised_payment_at ?: $nextInstallment?->due_at ?: $freshOrder->payment_due_at;
322:                if ($locked->status === 'closed') $updates['status'] = $installments->isNotEmpty() ? 'installment_plan' : 'monitoring';
325:            return $locked->fresh(['order.user', 'order.supplier', 'installments', 'contacts.user', 'assignee']) ?? $locked;
329:    public function remaining(Order $order): float
334:    public function daysOverdue(Order $order): int
336:        if ($order->payment_due_at === null || !$order->payment_due_at->copy()->startOfDay()->lt(today())) return 0;
337:        return (int) $order->payment_due_at->copy()->startOfDay()->diffInDays(today());
340:    public function agingBucket(Order $order): string
342:        if ($order->payment_due_at === null || $order->payment_due_at->copy()->startOfDay()->gte(today())) return 'current';
356:        if ($order->payment_due_at === null) return null;
357:        $due = $order->payment_due_at->copy()->startOfDay();
394:            $locked = ReceivableCase::query()->lockForUpdate()->findOrFail($case->id);
396:            $freshOrder = Order::query()->with(['user.role', 'supplier.role'])->lockForUpdate()->findOrFail($order->id);
397:            if ($this->remaining($freshOrder) <= 0.004) return false;
398:            $dueKey = $freshOrder->payment_due_at?->format('Ymd') ?? 'none';
402:            $remaining = $this->remaining($freshOrder);
425:            $metadata['last_reminder_due_date'] = $freshOrder->payment_due_at?->toDateString();
453:        if ($order->payment_due_at === null) return null;
454:        $due = $order->payment_due_at->copy()->endOfDay();
464:        $due = $order->payment_due_at?->format('d.m.Y') ?? 'nije definisan';
465:        if ($order->payment_due_at?->isFuture()) {
--- PAYMENT SERVICE RECEIVABLES SYNC ---
9:use App\Models\OrderPayment;
16:final class OrderPaymentService
24:        private readonly ReceivablesService $receivables,
28:    public function submitProof(Order $order, UploadedFile $file, array $data, User $actor): OrderPayment
51:            $payment = DB::transaction(function () use ($order, $file, $data, $actor, $path): OrderPayment {
55:                $payment = OrderPayment::query()->create([
89:    public function record(Order $order, array $data, User $actor): OrderPayment
91:        $payment = DB::transaction(function () use ($order, $data, $actor): OrderPayment {
99:            $payment = OrderPayment::query()->create([
126:    public function recordAfterSalesRefundLocked(AfterSalesAction $action, Order $order, User $actor): OrderPayment
135:        $existing = OrderPayment::query()->where('after_sales_action_id', $action->id)->first();
136:        if ($existing instanceof OrderPayment) {
145:        $netPaid = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
154:        $payment = OrderPayment::query()->create([
175:    public function verify(OrderPayment $payment, User $actor): OrderPayment
177:        $verified = DB::transaction(function () use ($payment, $actor): OrderPayment {
178:            /** @var OrderPayment $locked */
179:            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
197:    public function reject(OrderPayment $payment, string $reason, User $actor): OrderPayment
199:        $rejected = DB::transaction(function () use ($payment, $reason, $actor): OrderPayment {
200:            /** @var OrderPayment $locked */
201:            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
214:    public function void(OrderPayment $payment, User $actor): OrderPayment
216:        $voided = DB::transaction(function () use ($payment, $actor): OrderPayment {
217:            /** @var OrderPayment $locked */
218:            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
235:    public function proof(OrderPayment $payment): ?string
257:            $this->receivables->syncForOrder($order->fresh() ?? $order);
268:        $net = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
271:        $refundTotal = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')->where('entry_type', 'refund')->sum('amount_rsd');
282:            'paid_total_rsd' => round($net, 2),
283:            'payment_state' => $state,
305:    private function notifyCustomer(OrderPayment $payment, string $title, string $message, string $severity): void
--- ORDER SERVICE RECEIVABLES ENSURE ---
30:        private readonly ReceivablesService $receivables,
99:            'payment_method' => (string) $data['payment_method'],
101:            'payment_due_at' => ($data['payment_method'] ?? null) === 'deferred_payment' ? (string) $data['payment_due_at'] : null,
227:                $this->receivables->ensureForOrder($fresh, $user);
267:        if (($data['payment_method'] ?? null) !== 'bank_transfer') {
ADMIN_RECEIVABLES_API_CONTROLLER_PRESENT=YES
44:    public function index(Request $request, ReceivablesService $service, SettingsService $settings): JsonResponse
99:    public function show(Request $request, ReceivableCase $receivable, ReceivablesService $service, SettingsService $settings): JsonResponse
110:                'max_installments' => 24,
121:    public function update(
132:    public function plan(
139:        $installments = $request->validated('installments');
140:        $updated = $service->replacePlan($receivable, $actor, is_array($installments) ? $installments : []);
144:    public function contact(
152:        $data['visible_to_customer'] = $request->boolean('visible_to_customer');
158:    public function reminder(
170:    public function updateSettings(UpdateReceivableSettingsRequest $request, SettingsService $settings): JsonResponse
197:    public function scan(Request $request, ReceivablesService $service): JsonResponse
200:        $result = $service->runAutomation(true);
211:        abort_unless($actor instanceof User && $actor->hasPermission('receivables.manage'), 403);
349:            'installments',
353:        $installments = $case->installments instanceof Collection
354:            ? $case->installments->sortBy('sequence_no')->values()
364:            'installments' => $installments
397:            'visible_to_customer' => (bool) $contact->visible_to_customer,
MOBILE_RECEIVABLES_API_PRESENT=YES
6:export type AdminReceivableUser = {
12:export type AdminReceivableOrder = {
16:  payment_state: string | null;
19:  payment_due_at: string | null;
20:  customer: AdminReceivableUser | null;
21:  supplier: AdminReceivableUser | null;
24:export type AdminReceivableSummary = {
30:  assigned_to: AdminReceivableUser | null;
32:  promised_payment_at: string | null;
37:  order: AdminReceivableOrder | null;
45:export type AdminReceivableInstallment = {
48:  due_at: string | null;
56:export type AdminReceivableContact = {
65:  user: AdminReceivableUser | null;
68:export type AdminReceivableDetail = AdminReceivableSummary & {
70:  created_by: AdminReceivableUser | null;
71:  updated_by: AdminReceivableUser | null;
72:  installments: AdminReceivableInstallment[];
73:  contacts: AdminReceivableContact[];
76:export type AdminReceivableStats = {
83:export type AdminReceivableSettings = {
96:export type AdminReceivableListParams = {
106:export type AdminReceivableListResponse = {
107:  data: AdminReceivableSummary[];
115:    stats: AdminReceivableStats;
122:    assignees: AdminReceivableUser[];
124:  settings: AdminReceivableSettings;
133:export type AdminReceivableDetailResponse = {
134:  data: AdminReceivableDetail;
137:    assignees: AdminReceivableUser[];
138:    settings: AdminReceivableSettings;
139:    max_installments: number;
149:export type AdminReceivableMutationResponse = {
150:  data: AdminReceivableDetail;
154:export type AdminReceivableUpdateInput = {
158:  promised_payment_at?: string | null;
162:export type AdminReceivablePlanInput = {
163:  installments: Array<{
164:    due_at: string;
170:export type AdminReceivableContactInput = {
178:export type AdminReceivableReminderInput = {
182:export type AdminReceivableSettingsInput = {
195:export type AdminReceivableSettingsResponse = {
196:  data: AdminReceivableSettings;
200:export type AdminReceivableScanResponse = {
211:export const apiAdminReceivables = {
212:  list: (params: AdminReceivableListParams = {}) =>
213:    apiRequest<AdminReceivableListResponse> (`admin/receivables${queryString({
223:    apiRequest<AdminReceivableDetailResponse> (`admin/receivables/${receivableId}`),
224:  update: (receivableId: number, input: AdminReceivableUpdateInput) =>
225:    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}`, {
229:  replacePlan: (receivableId: number, input: AdminReceivablePlanInput) =>
230:    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}/plan`, {
234:  addContact: (receivableId: number, input: AdminReceivableContactInput) =>
235:    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}/contacts`, {
239:  sendReminder: (receivableId: number, input: AdminReceivableReminderInput = {}) =>
240:    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}/reminder`, {
244:  updateSettings: (input: AdminReceivableSettingsInput) =>
245:    apiRequest<AdminReceivableSettingsResponse> ('admin/receivables/settings', {
249:  scan: () => apiRequest<AdminReceivableScanResponse> ('admin/receivables/scan', { method: 'POST' }),
257:export async function shareAdminReceivablesCsv(): Promise<void> {
262:  const response = await apiDownload(apiAdminReceivables.csvPath());

============================================================
3. ROUTE CONTRACT READ ONLY
============================================================

  GET|HEAD  api/v1/admin/catalog/products ................................................ api.v1.admin.catalog.products.index › Api\V1\Admin\CatalogProductController@index
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
  POST      api/v1/admin/catalog/products ................................................ api.v1.admin.catalog.products.store › Api\V1\Admin\CatalogProductController@store
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
  GET|HEAD  api/v1/admin/catalog/products/archived ................................. api.v1.admin.catalog.products.archived › Api\V1\Admin\CatalogProductController@archived
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
  GET|HEAD  api/v1/admin/catalog/products/{product} ........................................ api.v1.admin.catalog.products.show › Api\V1\Admin\CatalogProductController@show
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
  PUT       api/v1/admin/catalog/products/{product} .................................... api.v1.admin.catalog.products.update › Api\V1\Admin\CatalogProductController@update
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/catalog/products/{product}/archive .......................... api.v1.admin.catalog.products.archive › Api\V1\Admin\CatalogProductController@archive
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/catalog/products/{product}/direct-sale ......... api.v1.admin.catalog.products.direct-sale.store › Api\V1\Admin\CatalogProductController@directSale
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  GET|HEAD  api/v1/admin/catalog/products/{product}/direct-sale/options api.v1.admin.catalog.products.direct-sale.options › Api\V1\Admin\CatalogProductController@directSaleOptions
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
  GET|HEAD  api/v1/admin/catalog/products/{product}/images ................... api.v1.admin.catalog.products.images.index › Api\V1\Admin\CatalogProductController@imageIndex
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_images
  POST      api/v1/admin/catalog/products/{product}/images ....................... api.v1.admin.catalog.products.images.store › Api\V1\Admin\CatalogProductController@images
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_images
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:uploads
  POST      api/v1/admin/catalog/products/{product}/images/reorder ....... api.v1.admin.catalog.products.images.reorder › Api\V1\Admin\CatalogProductController@imageReorder
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_images
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  DELETE    api/v1/admin/catalog/products/{product}/images/{image} ....... api.v1.admin.catalog.products.images.destroy › Api\V1\Admin\CatalogProductController@imageDestroy
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_images
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/catalog/products/{product}/images/{image}/primary api.v1.admin.catalog.products.images.primary › Api\V1\Admin\CatalogProductController@imagePrimary
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_images
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/catalog/products/{product}/images/{image}/rotate .. api.v1.admin.catalog.products.images.rotate › Api\V1\Admin\CatalogProductController@imageRotate
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_images
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/catalog/products/{product}/restore .......................... api.v1.admin.catalog.products.restore › Api\V1\Admin\CatalogProductController@restore
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:catalog.manage_products
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write

                                                                                                                                                         Showing [15] routes


  GET|HEAD  api/v1/admin/receivables ............................................................. api.v1.admin.receivables.index › Api\V1\Admin\ReceivablesController@index
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:receivables.manage
  GET|HEAD  api/v1/admin/receivables/export.csv ............................................................. api.v1.admin.receivables.csv › Admin\ReceivablesController@csv
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:receivables.manage
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:exports
  POST      api/v1/admin/receivables/scan .......................................................... api.v1.admin.receivables.scan › Api\V1\Admin\ReceivablesController@scan
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:receivables.manage
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  PUT       api/v1/admin/receivables/settings ................................. api.v1.admin.receivables.settings.update › Api\V1\Admin\ReceivablesController@updateSettings
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:receivables.manage
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  GET|HEAD  api/v1/admin/receivables/{receivable} .................................................. api.v1.admin.receivables.show › Api\V1\Admin\ReceivablesController@show
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:receivables.manage
  PATCH     api/v1/admin/receivables/{receivable} .............................................. api.v1.admin.receivables.update › Api\V1\Admin\ReceivablesController@update
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:receivables.manage
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/receivables/{receivable}/contacts ............................ api.v1.admin.receivables.contacts.store › Api\V1\Admin\ReceivablesController@contact
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:receivables.manage
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  PUT       api/v1/admin/receivables/{receivable}/plan ............................................. api.v1.admin.receivables.plan › Api\V1\Admin\ReceivablesController@plan
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:receivables.manage
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/receivables/{receivable}/reminder ................................. api.v1.admin.receivables.reminder › Api\V1\Admin\ReceivablesController@reminder
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:receivables.manage
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write

                                                                                                                                                          Showing [9] routes


============================================================
4. OPENAPI CURRENT CONTRACT
============================================================
--- DIRECT SALE OPENAPI ---
235:  /api/v1/admin/catalog/products/{product}/direct-sale/options:
247:              schema: { $ref: '#/components/schemas/AdminDirectSaleOptionsEnvelope' }
250:  /api/v1/admin/catalog/products/{product}/direct-sale:
263:              $ref: '#/components/schemas/AdminDirectSaleInput'
269:              schema: { $ref: '#/components/schemas/AdminDirectSaleResponse' }
774:              required: [shipping_full_name, shipping_address, shipping_city, shipping_postal_code, shipping_phone, payment_method, items]
783:                payment_method: { type: string, enum: [cash_on_delivery, bank_transfer, deferred_payment] }
785:                payment_due_at: { type: [string, 'null'], format: date, description: Obavezno za deferred_payment; mora biti današnji ili budući datum. }
2184:              required: [entry_type, amount_rsd, payment_method, paid_at]
2188:                payment_method: { type: string, enum: [bank_transfer, cash, cash_on_delivery, card, other] }
2310:        - { in: query, name: status, schema: { type: string, enum: [monitoring, contacted, promised, installment_plan, escalated, disputed, closed] } }
2392:                status: { type: string, enum: [monitoring, contacted, promised, installment_plan, escalated, disputed, closed] }
2415:              required: [installments]
2417:                installments:
4420:        payment_method: { type: string, enum: [bank_transfer, cash, other] }
4424:      required: [commission_ids, payment_method]
4432:        payment_method: { type: string, enum: [bank_transfer, cash, other] }
4556:    AdminDirectSalePaymentMethod:
4559:    AdminDirectSaleOptions:
4561:      required: [product, payment_methods, idempotency_key, can_submit]
4576:        payment_methods:
4582:              value: { $ref: '#/components/schemas/AdminDirectSalePaymentMethod' }
4587:    AdminDirectSaleOptionsEnvelope:
4591:        data: { $ref: '#/components/schemas/AdminDirectSaleOptions' }
4592:    AdminDirectSaleInput:
4594:      required: [quantity, sale_price_rsd, payment_method, idempotency_key]
4600:        payment_method: { $ref: '#/components/schemas/AdminDirectSalePaymentMethod' }
4602:    AdminDirectSaleResult:
4614:    AdminDirectSaleResponse:
4619:        data: { $ref: '#/components/schemas/AdminDirectSaleResult' }
4942:      required: [id, number, entry_type, entry_label, status, amount_rsd, payment_method, has_proof]
4950:        payment_method: { type: string }
5024:          required: [id, order_number, status, payment_method, payment_status, payment_state, subtotal_rsd, paid_total_rsd, remaining_rsd]
5029:            payment_method: { type: string }
5035:            payment_due_at: { type: [string, 'null'], format: date-time }
--- RECEIVABLES OPENAPI ---
785:                payment_due_at: { type: [string, 'null'], format: date, description: Obavezno za deferred_payment; mora biti današnji ili budući datum. }
899:              required: [amount_rsd, paid_at, proof]
901:                amount_rsd: { type: number, minimum: 0.01, maximum: 9999999999.99 }
2184:              required: [entry_type, amount_rsd, payment_method, paid_at]
2187:                amount_rsd: { type: number, format: double, minimum: 0.01 }
2303:  /api/v1/admin/receivables:
2307:      operationId: listAdminReceivables
2319:  /api/v1/admin/receivables/export.csv:
2323:      operationId: exportAdminReceivablesCsv
2332:  /api/v1/admin/receivables/settings:
2336:      operationId: updateAdminReceivablesSettings
2359:  /api/v1/admin/receivables/scan:
2363:      operationId: scanAdminReceivables
2368:  /api/v1/admin/receivables/{receivable}:
2372:      operationId: getAdminReceivable
2382:      operationId: updateAdminReceivable
2402:  /api/v1/admin/receivables/{receivable}/plan:
2406:      operationId: replaceAdminReceivablePlan
2420:                  maxItems: 24
2423:                    required: [due_at, amount_rsd]
2425:                      due_at: { type: string, format: date }
2426:                      amount_rsd: { type: number, exclusiveMinimum: 0 }
2433:  /api/v1/admin/receivables/{receivable}/contacts:
2437:      operationId: addAdminReceivableContact
2457:  /api/v1/admin/receivables/{receivable}/reminder:
2461:      operationId: remindAdminReceivable
2713:                due_at: { type: string, format: date-time, nullable: true }
2771:                due_at: { type: string, format: date-time, nullable: true }
2772:                amount_rsd: { type: number, format: double, nullable: true }
3683:      required: [id, status, status_label, due_at, scheduled_at, completed_at, service_reference, result, notes, completer_name, can_schedule, can_complete]
3688:        due_at: { type: [string, 'null'], format: date }
4942:      required: [id, number, entry_type, entry_label, status, amount_rsd, payment_method, has_proof]
4949:        amount_rsd: { type: number }
4966:        due_at: { type: [string, 'null'], format: date }
5035:            payment_due_at: { type: [string, 'null'], format: date-time }
5074:        due_at: { type: [string, 'null'], format: date-time }
5119:        due_at: { type: [string, 'null'], format: date-time }
5120:        amount_rsd: { type: [number, 'null'] }
5280:        due_at: { type: [string, 'null'], format: date }

============================================================
5. RUNTIME DATABASE SCHEMA + ENUM CONTRACT READ ONLY
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.mobile-v0.9.0-direct-sale-deferred-audit-batch5b.f4oItm/runtime.php
TABLE_PRESENT_orders=YES
TABLE_COLUMNS_orders=["id","source_system","sales_channel","direct_sale_recorded_by","order_number","idempotency_key_hash","request_fingerprint","user_id","supplier_user_id","supplier_name_snapshot","supplier_email_snapshot","supplier_phone_snapshot","supplier_role_snapshot","assigned_at","status","inventory_state","inventory_reserved_at","inventory_returned_at","cancelled_at","cancelled_by","shipping_full_name","shipping_address","shipping_city","shipping_postal_code","shipping_phone","subtotal_rsd","eur_rsd_rate","customer_note","payment_method","payment_status","bank_account_id","bank_account_label_snapshot","bank_account_number_snapshot","bank_account_number_display_snapshot","payment_recipient_name_snapshot","payment_recipient_address_snapshot","payment_code_snapshot","payment_purpose_snapshot","payment_reference_snapshot","tracking_number","tracking_updated_at","tracking_updated_by","updated_by","created_at","updated_at","assigned_by","reassigned_at","accepted_by","accepted_at","expected_processing_at","expected_shipping_at","last_internal_note_at","payment_state","paid_total_rsd","payment_due_at","payment_verified_at","completed_at","completed_by","completion_note","reopened_at","reopened_by","reopen_reason","archived_at","archived_by","archive_reason","purged_at","purged_by","purge_reason"]
TABLE_PRESENT_order_payments=YES
TABLE_COLUMNS_order_payments=["id","order_id","after_sales_action_id","payment_number","entry_type","status","amount_rsd","payment_method","paid_at","reference","note","proof_path","proof_original_name","proof_mime_type","proof_size_bytes","submitted_by","verified_by","verified_at","rejected_by","rejected_at","rejection_reason","voided_by","voided_at","created_at","updated_at"]
TABLE_PRESENT_receivable_cases=YES
TABLE_COLUMNS_receivable_cases=["id","order_id","case_number","status","collection_stage","assigned_to","next_action_at","promised_payment_at","last_contact_at","last_reminder_stage","last_reminder_at","internal_note","metadata_json","created_by","updated_by","closed_at","created_at","updated_at"]
TABLE_PRESENT_receivable_installments=YES
TABLE_COLUMNS_receivable_installments=["id","receivable_case_id","sequence_no","due_at","amount_rsd","paid_amount_rsd","status","paid_at","note","created_at","updated_at"]

In Connection.php line 857:

  SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the ri
  ght syntax to use near '?' at line 1 (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: SHOW COLUMNS FROM `orders` LIKE payment_method)


In Connection.php line 435:

  SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the ri
  ght syntax to use near '?' at line 1


LARAVEL_RUNTIME_READ_ONLY_PROBE=PASS

============================================================
6. IMPLEMENTATION READINESS DECISION
============================================================
STANDARD_DIRECT_SALE_BEHAVIOR=PRESERVE_EXISTING_IMMEDIATE_PAID_AND_DELIVERED_FLOW
DEFERRED_DIRECT_SALE_TARGET_PAYMENT_STATE=UNPAID_OR_PARTIAL_SERVER_AUTHORITY_REQUIRED_NOT_CLIENT_DERIVED
DEFERRED_DIRECT_SALE_TARGET_DUE_DATE=FINAL_FULL_PAYMENT_DATE_TO_ORDERS_PAYMENT_DUE_AT
DEFERRED_DIRECT_SALE_TARGET_INSTALLMENTS=1_TO_24_SERVER_VALIDATED_PLAN_VIA_RECEIVABLES_SERVICE_REPLACE_PLAN
DEFERRED_DIRECT_SALE_TARGET_RECEIVABLE=ENSURE_FOR_ORDER_THEN_REPLACE_PLAN_REUSE_EXISTING_AUTHORITY
DEFERRED_DIRECT_SALE_COMMISSION=ZERO_UNCHANGED
DEFERRED_DIRECT_SALE_INVENTORY=SALE_STOCK_DECREMENT_UNCHANGED
PRODUCT_VARIANTS_REINTRODUCED=NO
IMPLEMENTATION_READINESS=PASS_TOPOLOGY_CAPTURED_FOR_BATCH5B_SOURCE_IMPLEMENTATION

============================================================
7. FINAL
============================================================
SOURCE_CODE_CHANGES=0
DATABASE_WRITES=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=0
OPENAPI_CHANGES=0
EAS_COMMANDS_RUN=NO
EAS_BUILD_REQUIRED=NO
REPORT=/home/icaffeco/ald1n-project/docs/operations/101-MOBILE-V0.9.0-DIRECT-SALE-DEFERRED-PAYMENT-RECEIVABLES-TOPOLOGY-AUDIT-BATCH5B-20260822-142755.md
MOBILE_V0_9_DIRECT_SALE_DEFERRED_PAYMENT_RECEIVABLES_TOPOLOGY_AUDIT_BATCH5B=PASS
NEXT_ACTION=UPLOAD_101_REPORT_TO_CHAT_THEN_IMPLEMENT_DEFERRED_DIRECT_SALE_WITH_EXISTING_RECEIVABLES_AUTHORITY
PASS: v0.9 Direct Sale deferred-payment Receivables topology audit Batch 5B completed

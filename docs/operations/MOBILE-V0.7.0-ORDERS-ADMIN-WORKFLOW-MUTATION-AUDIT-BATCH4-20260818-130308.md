============================================================
MOBILE v0.7.0 - ORDERS ADMIN WORKFLOW MUTATION AUDIT - BATCH 4
============================================================
READ-ONLY DISCOVERY OF EXISTING WEB WORKFLOWS, API MUTATION SURFACE, SERVICE AUTHORITIES, PERMISSIONS, AND MOBILE GAPS
DATE=Tue Aug 18 13:03:08 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-ORDERS-ADMIN-WORKFLOW-MUTATION-AUDIT-BATCH4-20260818-130308.md
MODE=READ_ONLY_WORKFLOW_MUTATION_DISCOVERY
SOURCE_WRITES_EXPECTED=0
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION_CHANGE=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
REPORT_GENERATION=ENABLED_DOCS_OPERATIONS

============================================================
0. CONCURRENCY + PREFLIGHT
============================================================
OTHER_ALD1N_LOCK_COUNT=0
CONCURRENCY_LOCK=ACQUIRED
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
NPM_10_CANONICAL_CLI=PASS
CURRENT_APP_VERSION=0.7.0
BATCH3_V2_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-ORDERS-ADMIN-READ-UI-BATCH3-V2-20260818-125355.md
BATCH3_V2_PREREQUISITE=PASS
OPENAPI_PRE_PARITY=PASS

============================================================
1. RUNTIME ROUTE SURFACE - ADMIN ORDERS
============================================================

  GET|HEAD       api/v1/admin/orders ........................................................................ api.v1.admin.orders.index › Api\V1\Admin\OrderController@index
  GET|HEAD       api/v1/admin/orders/{order} .................................................................. api.v1.admin.orders.show › Api\V1\Admin\OrderController@show

                                                                                                                                                          Showing [2] routes

API_ADMIN_ORDERS_ROUTE_LINES=2
API_ADMIN_ORDERS_GET_ROUTE_LINES=2
API_ADMIN_ORDERS_MUTATION_ROUTE_LINES=0

--- WEB /admin/orders ROUTES ---

  GET|HEAD   admin/orders ................................................................................................. admin.orders.index › Admin\OrderController@index
  GET|HEAD   admin/orders/archived .................................................................................. admin.orders.archived › Admin\OrderController@archived
  DELETE     admin/orders/archived/{orderId}/purge ........................................................................ admin.orders.purge › Admin\OrderController@purge
  POST       admin/orders/archived/{orderId}/restore .................................................................. admin.orders.restore › Admin\OrderController@restore
  GET|HEAD   admin/orders/{order} ........................................................................................... admin.orders.show › Admin\OrderController@show
  POST       admin/orders/{order}/accept ................................................................................ admin.orders.accept › Admin\OrderController@accept
  POST       admin/orders/{order}/archive ............................................................................. admin.orders.archive › Admin\OrderController@archive
  POST       admin/orders/{order}/complete .......................................................................... admin.orders.complete › Admin\OrderController@complete
  PATCH      admin/orders/{order}/deadlines ....................................................................... admin.orders.deadlines › Admin\OrderController@deadlines
  POST       admin/orders/{order}/documents ............................................................. admin.orders.documents.store › Admin\OrderDocumentController@store
  POST       admin/orders/{order}/documents/{document}/cancel ......................................... admin.orders.documents.cancel › Admin\OrderDocumentController@cancel
  POST       admin/orders/{order}/internal-notes ..................................................................... admin.orders.notes.store › Admin\OrderController@note
  POST       admin/orders/{order}/invoice.pdf .......................................................... admin.orders.invoice.pdf › Admin\OrderDocumentController@invoicePdf
  PATCH      admin/orders/{order}/payment ............................................................................. admin.orders.payment › Admin\OrderController@payment
  POST       admin/orders/{order}/payments ..................................................................... admin.orders.payments.store › Admin\PaymentController@store
  POST       admin/orders/{order}/payments/{payment}/reject .................................................. admin.orders.payments.reject › Admin\PaymentController@reject
  POST       admin/orders/{order}/payments/{payment}/verify .................................................. admin.orders.payments.verify › Admin\PaymentController@verify
  POST       admin/orders/{order}/payments/{payment}/void ........................................................ admin.orders.payments.void › Admin\PaymentController@void
  PATCH      admin/orders/{order}/reassign .......................................................................... admin.orders.reassign › Admin\OrderController@reassign
  POST       admin/orders/{order}/reopen ................................................................................ admin.orders.reopen › Admin\OrderController@reopen
  POST       admin/orders/{order}/shipment ............................................................... admin.orders.shipment.store › Admin\OrderShipmentController@store
  PATCH      admin/orders/{order}/status ................................................................................ admin.orders.status › Admin\OrderController@status
  PATCH      admin/orders/{order}/tracking .......................................................................... admin.orders.tracking › Admin\OrderController@tracking
  GET|HEAD   api/v1/admin/orders ............................................................................ api.v1.admin.orders.index › Api\V1\Admin\OrderController@index
  GET|HEAD   api/v1/admin/orders/{order} ...................................................................... api.v1.admin.orders.show › Api\V1\Admin\OrderController@show

                                                                                                                                                         Showing [25] routes

WEB_ADMIN_ORDERS_MUTATION_ROUTE_LINES=20

============================================================
2. BACKEND CONTROLLER METHOD INVENTORY
============================================================

--- API V1 Admin OrderController: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php ---
37:    public function index(Request $request, OrderIndexService $orders, OrderAccessService $access): JsonResponse
94:    public function show(

--- Web Admin OrderController: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php ---
35:    public function index(Request $request, OrderIndexService $orders, OrderAccessService $access): Response
70:    public function show(
155:    public function lastDetailRenderException(): ?Throwable
160:    public function archived(Request $request, \App\Services\OrderArchiveService $archives): \Illuminate\View\View
171:    public function archive(Request $request, Order $order, \App\Services\OrderArchiveService $archives): RedirectResponse
184:    public function restore(Request $request, int $orderId, \App\Services\OrderArchiveService $archives): RedirectResponse
192:    public function purge(Request $request, int $orderId, \App\Services\OrderArchiveService $archives): RedirectResponse
210:    public function status(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
218:    public function complete(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
246:    public function reopen(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
255:    public function payment(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
263:    public function tracking(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
271:    public function accept(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
277:    public function note(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
284:    public function reassign(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
296:    public function deadlines(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse

--- OrderPaymentController: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderPaymentController.php ---
17:    public function storeProof(Request $request, Order $order, OrderPaymentService $payments): RedirectResponse
30:    public function proof(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): BinaryFileResponse

--- OrderDeliveryController: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderDeliveryController.php ---
15:    public function proof(Request $request, Order $order, OrderAccessService $access): BinaryFileResponse

--- Admin PaymentController: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/PaymentController.php ---
18:    public function store(Request $request, Order $order, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
33:    public function verify(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
41:    public function reject(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
50:    public function void(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse

============================================================
3. SERVICE AUTHORITY INVENTORY
============================================================

--- OrderService: /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php ---
23:    public function __construct(
35:    public function create(User $user, array $data, string $idempotencyKey): Order

--- OrderWorkflowService: /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php ---
35:    public function __construct(
44:    public function cancelOwn(Order $order, User $user, ?string $note = null): Order
55:    public function changeStatus(Order $order, string $newStatus, User $actor, ?string $note = null): Order
175:    public function updatePaymentStatus(Order $order, string $paymentStatus, User $actor): Order
196:    public function updateTracking(Order $order, ?string $trackingNumber, User $actor): Order
226:    public function complete(
459:    public function reopen(Order $order, User $actor, string $reason): Order

--- OrderOperationalService: /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php ---
16:    public function __construct(
23:    public function accept(Order $order, User $actor): Order
52:    public function addInternalNote(Order $order, User $actor, string $note): OrderInternalNote
75:    public function reassign(Order $order, User $newSupplier, User $actor, string $reason): Order
140:    public function updateDeadlines(Order $order, User $actor, array $data): Order

--- OrderPaymentService: /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php ---
18:    public function __construct(
28:    public function submitProof(Order $order, UploadedFile $file, array $data, User $actor): OrderPayment
89:    public function record(Order $order, array $data, User $actor): OrderPayment
126:    public function recordAfterSalesRefundLocked(AfterSalesAction $action, Order $order, User $actor): OrderPayment
175:    public function verify(OrderPayment $payment, User $actor): OrderPayment
197:    public function reject(OrderPayment $payment, string $reason, User $actor): OrderPayment
214:    public function void(OrderPayment $payment, User $actor): OrderPayment
235:    public function proof(OrderPayment $payment): ?string
241:    public function recalculate(Order $order): Order

--- OrderTimelineService: /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderTimelineService.php ---
19:    public function build(Order $order, bool $includeInternal = true): Collection

--- OrderAccessService: /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderAccessService.php ---
14:    public function applyManagedScope(Builder $query, User $user): Builder
27:    public function canManage(Order $order, User $user): bool
33:    public function canView(Order $order, User $user): bool
38:    public function authorizeManage(Order $order, User $user): void
43:    public function authorizeView(Order $order, User $user): void

--- OrderDocumentService: /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php ---
20:    public function __construct(
31:    public function issue(Order $order, string $type, User $actor): OrderDocument
229:    public function cancel(OrderDocument $document, User $actor, string $reason): OrderDocument
292:    public function render(OrderDocument $document): string

============================================================
4. WORKFLOW SIGNAL MATRIX
============================================================
STATUS_WORKFLOW_SIGNAL_COUNT=258
PAYMENT_WORKFLOW_SIGNAL_COUNT=281
DELIVERY_WORKFLOW_SIGNAL_COUNT=151
ASSIGNMENT_WORKFLOW_SIGNAL_COUNT=34
INTERNAL_NOTE_WORKFLOW_SIGNAL_COUNT=74
DOCUMENT_WORKFLOW_SIGNAL_COUNT=187

--- STATUS / COMPLETION / REOPEN SIGNALS ---
FILE=/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php
141:    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
143:        ->middleware('permission:orders.cancel_own')
144:        ->name('orders.cancel');
260:            Route::patch('/orders/{order}/status', [AdminOrderController::class, 'status'])->whereNumber('order')->name('orders.status');
261:            Route::post('/orders/{order}/complete', [AdminOrderController::class, 'complete'])
262:                ->whereNumber('order')->middleware('permission:orders.confirm_delivery')->name('orders.complete');
263:            Route::post('/orders/{order}/reopen', [AdminOrderController::class, 'reopen'])
264:                ->whereNumber('order')->middleware('permission:orders.reopen')->name('orders.reopen');
295:            Route::post('/after-sales/{case}/actions/{action}/complete', [AdminAfterSalesActionController::class, 'complete'])
296:                ->whereNumber('case')->whereNumber('action')->name('after-sales.actions.complete');
297:            Route::post('/after-sales/{case}/actions/{action}/cancel', [AdminAfterSalesActionController::class, 'cancel'])
298:                ->whereNumber('case')->whereNumber('action')->name('after-sales.actions.cancel');
308:            Route::post('/field-operations/{workOrder}/complete', [FieldOperationsController::class, 'complete'])->whereNumber('workOrder')->middleware('throttle:uploads')->name('field-operations.complete');
309:            Route::post('/field-operations/{workOrder}/cancel', [FieldOperationsController::class, 'cancel'])->whereNumber('workOrder')->name('field-operations.cancel');
334:            Route::post('/warranties/{warranty}/maintenance/{record}/complete', [AdminWarrantyController::class, 'complete'])->whereNumber('warranty')->whereNumber('record')->name('warranties.maintenance.complete');
347:            Route::post('/service-part-purchases/{purchaseRequest}/cancel', [ServicePartPurchaseRequestController::class, 'cancel'])->whereNumber('purchaseRequest')->name('service-part-purchases.cancel');
357:            Route::patch('/commissions/{commission}/status', [CommissionController::class, 'transition'])->name('commissions.transition');
385:            Route::post('/orders/{order}/documents/{document}/cancel', [AdminOrderDocumentController::class, 'cancel'])->whereNumber('order')->whereNumber('document')->name('orders.documents.cancel');
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php
181:            ->with('status', 'Porudžbina '.$order->order_number.' je arhivirana.');
190:            ->with('status', 'Arhiviranje porudžbine '.$order->order_number.' je opozvano.');
208:            ->with('status', 'Porudžbina je trajno uklonjena iz operativnih i arhivskih prikaza. Poslovna istorija potrebna za integritet ostaje sačuvana.');
210:    public function status(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
213:        $data = $request->validate(['status' => ['required', Rule::in(['new', 'processing', 'confirmed', 'shipped', 'cancelled'])], 'note' => ['nullable', 'string', 'max:1000']]);
214:        $workflow->changeStatus($order, (string) $data['status'], $request->user(), $data['note'] ?? null);
215:        return back()->with('status', 'Status porudžbine je ažuriran.');
218:    public function complete(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
238:        $completed = $workflow->complete($order, $request->user(), $data, $request->file('delivery_proof'));
241:            'status',
242:            'Porudžbina '.$completed->order_number.' je kompletirana. Evidencija isporuke i konačno plaćanje su zaključani.',
246:    public function reopen(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
250:        $reopened = $workflow->reopen($order, $request->user(), (string) $data['reason']);
252:        return back()->with('status', 'Porudžbina '.$reopened->order_number.' je ponovo otvorena za kontrolisanu korekciju.');
258:        $data = $request->validate(['payment_status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])]]);
259:        $workflow->updatePaymentStatus($order, (string) $data['payment_status'], $request->user());
260:        return back()->with('status', 'Status plaćanja je ažuriran.');
268:        return back()->with('status', 'Tracking broj je ažuriran.');
274:        return back()->with('status', 'Porudžbina je preuzeta za obradu.');
281:        return back()->with('status', 'Interna napomena je sačuvana.');
293:        return back()->with('status', 'Porudžbina je dodeljena drugom odgovornom licu.');
303:        return back()->with('status', 'Očekivani rokovi su ažurirani.');
312:            'status' => ['nullable', Rule::in(['new', 'processing', 'confirmed', 'shipped', 'completed', 'cancelled'])],
313:            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'cancelled'])],
413:            .'<section class="card"><h1>'.e((string) $order->order_number).'</h1><p>'.e((string) $order->shipping_full_name).' · '.e($created).'</p><p>Status: '.e((string) $order->status).' · Ukupno: '.e(number_format((float) $order->subtotal_rsd, 2, ',', '.')).' RSD</p></section>'
463:        foreach (['items', 'documents', 'statusHistory', 'assignments', 'payments', 'internalNotes'] as $relation) {
469:        foreach (['user', 'supplier', 'bankAccount', 'acceptedBy', 'assignedBy', 'completedBy', 'reopenedBy', 'commission', 'delivery'] as $relation) {
480:            ->where('status', 'active')
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderPaymentController.php
27:        return back()->with('status', 'Potvrda uplate je poslata odgovornom licu na proveru.');
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/PaymentController.php
30:        return back()->with('status', 'Stavka '.$payment->payment_number.' je evidentirana i saldo porudžbine je preračunat.');
38:        return back()->with('status', 'Uplata je verifikovana.');
47:        return back()->with('status', 'Potvrda uplate je odbijena i korisnik je obavešten.');
55:        return back()->with('status', 'Stavka uplate je stornirana.');
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
76:            ->where('status', 'active')
104:            'status' => 'new',
116:            'payment_status' => 'pending',
227:            $aggregate = (int) ProductVariant::query()->where('product_id', $variantProductId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
236:            'status' => 'pending',
238:        DB::table('order_status_history')->insert([
241:            'old_status' => null,
242:            'new_status' => 'new',
252:            after: ['status' => $order->status, 'subtotal_rsd' => $order->subtotal_rsd, 'inventory_state' => $order->inventory_state],
282:            ->where('status', 'active')
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php
27:    private const TRANSITIONS = [
28:        'new' => ['processing', 'confirmed', 'cancelled'],
29:        'processing' => ['confirmed', 'cancelled'],
30:        'confirmed' => ['shipped', 'cancelled'],
31:        'shipped' => ['cancelled'],
32:        'cancelled' => [],
44:    public function cancelOwn(Order $order, User $user, ?string $note = null): Order
48:        if (!in_array($order->status, ['new', 'processing', 'cancelled'], true)) {
49:            throw ValidationException::withMessages(['status' => 'Sopstvena porudžbina može biti otkazana samo dok je nova ili u obradi.']);
52:        return $this->changeStatus($order, 'cancelled', $user, $note ?: 'Korisnik je otkazao porudžbinu.');
55:    public function changeStatus(Order $order, string $newStatus, User $actor, ?string $note = null): Order
57:        $updated = DB::transaction(function () use ($order, $newStatus, $actor, $note): Order {
61:            $this->assertNotCompleted($locked);
63:            $oldStatus = (string) $locked->status;
65:            if ($newStatus === 'shipped') {
66:                throw ValidationException::withMessages(['status' => 'Status Poslata se evidentira isključivo kroz Evidenciju slanja pošiljke.']);
69:            if ($oldStatus === $newStatus) {
70:                if ($newStatus === 'cancelled') $this->returnInventoryOnce($locked, $actor);
73:            if (!in_array($newStatus, self::TRANSITIONS[$oldStatus] ?? [], true)) {
74:                throw ValidationException::withMessages(['status' => sprintf('Prelaz statusa %s -> %s nije dozvoljen.', $oldStatus, $newStatus)]);
76:            if ($newStatus === 'cancelled') $this->returnInventoryOnce($locked, $actor);
79:                'status' => $newStatus,
80:                'cancelled_at' => $newStatus === 'cancelled' ? ($locked->cancelled_at ?? now()) : $locked->cancelled_at,
81:                'cancelled_by' => $newStatus === 'cancelled' ? ($locked->cancelled_by ?? $actor->id) : $locked->cancelled_by,
84:            DB::table('order_status_history')->insert([
87:                'old_status' => $oldStatus,
88:                'new_status' => $newStatus,
93:            if ($newStatus === 'cancelled' && $locked->commission !== null && $locked->commission->status !== 'cancelled') {
95:                $oldCommissionStatus = (string) $commission->status;
97:                    'status' => 'cancelled',
98:                    'status_note' => $note,
99:                    'status_updated_at' => now(),
100:                    'cancelled_by' => $actor->id,
101:                    'cancelled_at' => now(),
103:                DB::table('commission_status_history')->insert([
107:                    'old_status' => $oldCommissionStatus,
108:                    'new_status' => 'cancelled',
110:                    'metadata_json' => json_encode(['source' => 'order_cancellation'], JSON_THROW_ON_ERROR),
117:                'order.status_changed',
118:                'Promenjen status porudžbine '.$locked->order_number,
120:                before: ['status' => $oldStatus],
121:                after: ['status' => $locked->status, 'inventory_state' => $locked->inventory_state],
130:            $labels = ['new' => 'nova', 'processing' => 'u obradi', 'confirmed' => 'potvrđena', 'shipped' => 'poslata', 'cancelled' => 'otkazana'];
133:                'order.status_changed',
134:                'Status porudžbine je promenjen',
135:                sprintf('Porudžbina %s je sada %s.%s', $updated->order_number, $labels[$updated->status] ?? $updated->status, $note ? ' Napomena: '.$note : ''),
137:                ['severity' => $updated->status === 'cancelled' ? 'danger' : ($updated->status === 'shipped' ? 'success' : 'info')],
140:        if ($updated->status === 'cancelled' && $updated->commission?->user instanceof User) {
141:            $this->notifications->commission($updated->commission->user, 'commission.cancelled', 'Provizija je stornirana', 'Provizija je stornirana jer je porudžbina '.$updated->order_number.' otkazana.', $updated->commission, ['severity' => 'danger']);
143:        if ($updated->status === 'cancelled') {
147:                Log::warning('Warranty cancellation synchronization failed.', ['order_id' => $updated->id, 'exception' => $exception]);
150:        if ($updated->status === 'cancelled'
155:                'order.cancelled_by_customer',
163:        $statusLabels = ['new' => 'Nova', 'processing' => 'U obradi', 'confirmed' => 'Potvrđena', 'shipped' => 'Poslata', 'cancelled' => 'Otkazana'];
166:            'order_status_changed',
167:            'Promenjen status porudžbine '.$updated->order_number,
168:            'Novi status: '.($statusLabels[$updated->status] ?? $updated->status).($note ? '. Napomena: '.$note : '.'),
169:            ['status' => $updated->status, 'note' => $note, 'actor_id' => $actor->id, 'changed_at' => $updated->updated_at?->toISOString()],
175:    public function updatePaymentStatus(Order $order, string $paymentStatus, User $actor): Order
177:        $updated = DB::transaction(function () use ($order, $paymentStatus, $actor): Order {
181:            $this->assertNotCompleted($locked);
183:            $before = $locked->payment_status;
184:            $locked->update(['payment_status' => $paymentStatus, 'updated_by' => $actor->id]);
185:            $this->audit->log('order.payment_status_changed', 'Promenjen status plaćanja '.$locked->order_number, $locked, ['payment_status' => $before], ['payment_status' => $paymentStatus], user: $actor);
190:            $this->notifications->order($updated->user, 'order.payment_status_changed', 'Promenjen status plaćanja', 'Status plaćanja za '.$updated->order_number.' je '.$updated->payment_status.'.', $updated);
192:        $this->emails->orderChanged($updated, 'order_payment_changed', 'Promenjen status plaćanja '.$updated->order_number, 'Status plaćanja je '.$updated->payment_status.'.', ['payment_status' => $updated->payment_status, 'actor_id' => $actor->id, 'changed_at' => $updated->updated_at?->toISOString()]);
204:            $this->assertNotCompleted($locked);
226:    public function complete(
260:                if ($locked->completed_at !== null) {
261:                    return $locked->fresh(['user', 'supplier', 'completedBy', 'delivery.confirmer']) ?? $locked;
263:                if ($locked->status === 'cancelled') {
266:                if (!in_array((string) $locked->status, ['confirmed', 'shipped'], true)) {
342:                    ->where('status', 'verified')
359:                        'status' => 'verified',
373:                $oldStatus = (string) $locked->status;
375:                if ($oldStatus !== 'shipped') {
376:                    DB::table('order_status_history')->insert([
379:                        'old_status' => $oldStatus,
380:                        'new_status' => 'shipped',
388:                    'status' => 'shipped',
389:                    'completed_at' => now(),
390:                    'completed_by' => $actor->id,
394:                    'payment_status' => 'paid',
401:                    'order.completed',
404:                    before: ['status' => $oldStatus, 'payment_state' => $oldPaymentState, 'completed_at' => null],
406:                        'status' => 'shipped',
408:                        'completed_at' => $locked->completed_at?->toISOString(),
416:                return $locked->fresh(['user', 'supplier', 'completedBy', 'delivery.confirmer']) ?? $locked;
446:                'order.completed',
454:        $this->emails->orderChanged($updated, 'order_completed', 'Porudžbina '.$updated->order_number.' je kompletirana', 'Isporuka je evidentirana i porudžbina je završena.', ['completed_at' => $updated->completed_at?->toISOString(), 'actor_id' => $actor->id]);
459:    public function reopen(Order $order, User $actor, string $reason): Order
471:            if ($locked->completed_at === null) {
475:            $completedAt = $locked->completed_at?->toISOString();
476:            $completedBy = $locked->completed_by;
478:                'completed_at' => null,
479:                'completed_by' => null,
481:                'reopened_at' => now(),
482:                'reopened_by' => $actor->id,
483:                'reopen_reason' => $reason,
488:                'order.reopened',
491:                before: ['completed_at' => $completedAt, 'completed_by' => $completedBy],
492:                after: ['completed_at' => null, 'reopened_at' => $locked->reopened_at?->toISOString()],
497:            return $locked->fresh(['user', 'supplier', 'reopenedBy', 'delivery.confirmer']) ?? $locked;
503:                'order.reopened',
513:                'order.reopened',
521:        $this->emails->orderChanged($updated, 'order_reopened', 'Porudžbina '.$updated->order_number.' je ponovo otvorena', 'Porudžbina je ponovo otvorena radi korekcije. Razlog: '.$reason, ['reason' => $reason, 'actor_id' => $actor->id, 'reopened_at' => $updated->reopened_at?->toISOString()]);
582:            throw ValidationException::withMessages(['status' => 'Porudžbina nema Laravel rezervaciju lagera koja može biti vraćena.']);
589:            $eventKey = sprintf('order:%d:item:%d:cancel-return', $order->id, $item->id);
609:                'movement_type' => 'cancelled_order',
610:                'source' => 'order_cancellation',
619:            $aggregate = (int) ProductVariant::query()->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->sum('stock_quantity');
632:    private function assertNotCompleted(Order $order): void
634:        if ($order->completed_at !== null) {
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php
29:            $this->assertNotCompleted($locked);
63:            $this->assertNotCompleted($locked);
82:        if (!$newSupplier->hasRole('admin', 'superadmin') || $newSupplier->status !== 'active') {
90:            $this->assertNotCompleted($locked);
146:            $this->assertNotCompleted($locked);
174:    private function assertNotCompleted(Order $order): void
176:        if ($order->completed_at !== null) {
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php
33:        // financial ledger remains writable so old completed orders can be reconciled.
34:        if ($order->status === 'cancelled') {
59:                    'status' => 'submitted',
71:                $this->audit->log('order.payment_proof_submitted', 'Poslata potvrda uplate '.$payment->payment_number, $payment, after: ['amount_rsd' => $payment->amount_rsd, 'status' => 'submitted'], user: $actor);
83:        $this->emails->orderChanged($order->fresh(['user', 'supplier']) ?? $order, 'order_payment_changed', 'Poslata potvrda uplate za '.$order->order_number, sprintf('Poslata je potvrda uplate od %s RSD.', number_format((float) $payment->amount_rsd, 2, ',', '.')), ['payment_number' => $payment->payment_number, 'status' => $payment->status]);
95:            if ($locked->status === 'cancelled') {
103:                'status' => 'verified',
120:            $this->emails->orderChanged($payment->order, 'order_payment_changed', $entry.' za '.$payment->order->order_number, sprintf('%s %s RSD je evidentirana.', $entry, number_format((float) $payment->amount_rsd, 2, ',', '.')), ['payment_number' => $payment->payment_number, 'status' => $payment->status, 'entry_type' => $payment->entry_type]);
131:        if ($order->status === 'cancelled') {
145:        $netPaid = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
159:            'status' => 'verified',
183:            if ($locked->status === 'verified') return $locked;
184:            if ($locked->status !== 'submitted') throw ValidationException::withMessages(['payment' => 'Samo poslata potvrda može biti verifikovana.']);
185:            $locked->update(['status' => 'verified', 'verified_by' => $actor->id, 'verified_at' => now(), 'rejected_by' => null, 'rejected_at' => null, 'rejection_reason' => null]);
187:            $this->audit->log('order.payment_verified', 'Verifikovana uplata '.$locked->payment_number, $locked, before: ['status' => 'submitted'], after: ['status' => 'verified'], user: $actor);
205:            if ($locked->status !== 'submitted') throw ValidationException::withMessages(['reason' => 'Samo poslata potvrda može biti odbijena.']);
206:            $locked->update(['status' => 'rejected', 'rejected_by' => $actor->id, 'rejected_at' => now(), 'rejection_reason' => trim($reason)]);
207:            $this->audit->log('order.payment_rejected', 'Odbijena potvrda uplate '.$locked->payment_number, $locked, before: ['status' => 'submitted'], after: ['status' => 'rejected', 'reason' => trim($reason)], user: $actor);
222:            if ($locked->status === 'voided') return $locked;
223:            if ($locked->status !== 'verified') throw ValidationException::withMessages(['payment' => 'Samo verifikovana stavka može biti stornirana.']);
224:            $locked->update(['status' => 'voided', 'voided_by' => $actor->id, 'voided_at' => now()]);
226:            $this->audit->log('order.payment_voided', 'Stornirana uplata '.$locked->payment_number, $locked, before: ['status' => 'verified'], after: ['status' => 'voided'], user: $actor);
268:        $net = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
271:        $refundTotal = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')->where('entry_type', 'refund')->sum('amount_rsd');
274:        if ($order->status === 'cancelled') $state = 'cancelled';
284:            'payment_status' => $state === 'cancelled' ? 'cancelled' : ($state === 'refunded' ? 'refunded' : (in_array($state, ['paid', 'overpaid'], true) ? 'paid' : 'pending')),
298:        if ($order->completed_at !== null) {
309:            $this->notifications->order($payment->order->user, 'order.payment_'.$payment->status, $title, $message, $payment->order, ['icon' => 'wallet', 'severity' => $severity]);
312:            $this->emails->orderChanged($payment->order, 'order_payment_changed', $title.' · '.$payment->order->order_number, $message, ['payment_number' => $payment->payment_number, 'status' => $payment->status, 'entry_type' => $payment->entry_type]);
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderTimelineService.php
32:        foreach ($this->loadedMany($order, 'statusHistory') as $row) {
34:                'type' => 'status',
35:                'title' => 'Status: '.(string) $row->new_status,
36:                'description' => $row->note ?: 'Status porudžbine je promenjen.',
100:                'description' => number_format((float) $payment->amount_rsd, 2, ',', '.').' RSD · '.(string) $payment->status,
112:                    'title' => 'Provizija: '.(string) $row->new_status,
113:                    'description' => $row->note ?: 'Status provizije je promenjen.',
184:            $auditActions = ['order.payment_status_changed', 'order.tracking_changed', 'order.accepted', 'order.deadlines_changed', 'order.completed', 'order.reopened'];
220:            'order.payment_status_changed' => 'Plaćanje: '.(string) ($after['payment_status'] ?? ''),
224:            'order.completed' => 'Isporuka je završena, plaćanje je potvrđeno i porudžbina je zaključana.',
225:            'order.reopened' => 'Porudžbina je ponovo otvorena radi kontrolisane korekcije.',
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php
42:            ->where('status', 'issued')
61:                ->where('status', 'issued')
108:                'status' => 'issued',
135:                'payment_status_snapshot' => $locked->payment_status,
159:            if ($dueAt !== null && !in_array((string) $locked->payment_state, ['paid', 'overpaid', 'cancelled'], true)) {
169:                    'status' => 'issued',
229:    public function cancel(OrderDocument $document, User $actor, string $reason): OrderDocument
233:            throw ValidationException::withMessages(['cancellation_reason' => 'Unesite razlog storniranja dokumenta.']);
236:        $statusChanged = false;
237:        $cancelled = DB::transaction(function () use ($document, $actor, $reason, &$statusChanged): OrderDocument {
240:            if ($locked->status === 'cancelled') return $locked;
241:            if ($locked->status !== 'issued') {
245:                'status' => 'cancelled',
246:                'cancelled_by' => $actor->id,
247:                'cancelled_at' => now(),
248:                'cancellation_reason' => $reason,
250:            $statusChanged = true;
252:                'order.document_cancelled',
255:                before: ['status' => 'issued'],
256:                after: ['status' => 'cancelled', 'cancellation_reason' => $reason],
262:        $cancelled->loadMissing('order.user');
263:        if ($statusChanged && $cancelled->order?->user instanceof User) {
266:                    $cancelled->order->user,
267:                    'order.document_cancelled',
269:                    'Dokument '.$cancelled->document_number.' za porudžbinu '.$cancelled->order->order_number.' je storniran. Razlog: '.$reason,
270:                    $cancelled->order,
274:                Log::warning('Document cancellation notification failed.', [
275:                    'document_id' => $cancelled->id,
276:                    'document_number' => $cancelled->document_number,
281:        if ($statusChanged) {
283:                $this->emails->documentCancelled($cancelled, $reason);
285:                Log::warning('Document cancellation email enqueue failed.', ['document_id' => $cancelled->id, 'exception' => $exception]);
289:        return $cancelled;
312:            'cancellation_reason' => $document->cancellation_reason,
313:            'status' => $document->status,
338:            'payment_status_snapshot' => $document->payment_status_snapshot,
379:        $requiredColumns = ['document_type', 'revision_number', 'supersedes_document_id', 'cancellation_reason', 'document_number', 'order_id'];

--- PAYMENT SIGNALS ---
FILE=/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php
13:use App\Http\Controllers\Admin\BankAccountController;
31:use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
63:use App\Http\Controllers\OrderPaymentController;
64:use App\Http\Controllers\OrderShipmentProofController;
151:    Route::middleware('permission:payments.view_own')->group(function (): void {
152:        Route::get('/orders/{order}/payments/{payment}/proof', [OrderPaymentController::class, 'proof'])
153:            ->whereNumber('order')->whereNumber('payment')->name('orders.payments.proof');
155:    Route::post('/orders/{order}/payments/proof', [OrderPaymentController::class, 'storeProof'])
156:        ->whereNumber('order')->middleware(['permission:payments.upload_proof', 'throttle:uploads'])->name('orders.payments.proof.store');
157:    Route::get('/orders/{order}/delivery-proof', [OrderDeliveryController::class, 'proof'])
158:        ->whereNumber('order')->name('orders.delivery.proof');
159:    Route::get('/orders/{order}/shipment-proof', OrderShipmentProofController::class)
160:        ->whereNumber('order')->name('orders.shipment.proof');
265:            Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'payment'])->whereNumber('order')->name('orders.payment');
368:            Route::get('/reports/payments.csv', [ReportController::class, 'paymentsCsv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.payments.csv');
388:        Route::middleware('permission:payments.manage')->group(function (): void {
389:            Route::post('/orders/{order}/payments', [AdminPaymentController::class, 'store'])->whereNumber('order')->name('orders.payments.store');
390:            Route::post('/orders/{order}/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.verify');
391:            Route::post('/orders/{order}/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.reject');
392:            Route::post('/orders/{order}/payments/{payment}/void', [AdminPaymentController::class, 'void'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.void');
451:            Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
452:            Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
453:            Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
454:            Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php
10:use App\Services\IpsPaymentPayloadService;
77:        IpsPaymentPayloadService $ips,
137:                    'Pokrenite <code>php artisan app:orders-doctor --render --order-id='.(int) $order->getKey().'</code> za tačan uzrok.',
236:            'delivery_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
238:        $completed = $workflow->complete($order, $request->user(), $data, $request->file('delivery_proof'));
255:    public function payment(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
258:        $data = $request->validate(['payment_status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])]]);
259:        $workflow->updatePaymentStatus($order, (string) $data['payment_status'], $request->user());
313:            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'cancelled'])],
334:                    'Pokrenite <code>php artisan app:orders-doctor --render</code> i proverite Laravel log.',
370:                        'Pokrenite <code>php artisan app:orders-doctor --render --order-id='.(int) ($data['order']->id ?? 0).'</code> za tačan uzrok.',
463:        foreach (['items', 'documents', 'statusHistory', 'assignments', 'payments', 'internalNotes'] as $relation) {
469:        foreach (['user', 'supplier', 'bankAccount', 'acceptedBy', 'assignedBy', 'completedBy', 'reopenedBy', 'commission', 'delivery'] as $relation) {
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderPaymentController.php
8:use App\Models\OrderPayment;
10:use App\Services\OrderPaymentService;
15:final class OrderPaymentController extends Controller
17:    public function storeProof(Request $request, Order $order, OrderPaymentService $payments): RedirectResponse
21:            'paid_at' => ['required', 'date'],
24:            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
26:        $payments->submitProof($order, $request->file('proof'), $data, $request->user());
30:    public function proof(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): BinaryFileResponse
32:        abort_unless((int) $payment->order_id === (int) $order->id, 404);
34:        $path = $payments->proof($payment);
36:        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', basename((string) ($payment->proof_original_name ?: 'potvrda-uplate'))) ?: 'potvrda-uplate';
38:            'Content-Type' => $payment->proof_mime_type ?: 'application/octet-stream',
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderDeliveryController.php
15:    public function proof(Request $request, Order $order, OrderAccessService $access): BinaryFileResponse
19:        $disk = trim((string) ($delivery->proof_disk ?: 'local')) ?: 'local';
20:        $path = trim((string) $delivery->proof_path);
27:            basename((string) ($delivery->proof_original_name ?: 'dokaz-isporuke')),
31:            'Content-Type' => $delivery->proof_mime_type ?: 'application/octet-stream',
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/PaymentController.php
9:use App\Models\OrderPayment;
11:use App\Services\OrderPaymentService;
16:final class PaymentController extends Controller
18:    public function store(Request $request, Order $order, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
22:            'entry_type' => ['required', Rule::in(['payment', 'refund'])],
24:            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'cash_on_delivery', 'card', 'other'])],
25:            'paid_at' => ['required', 'date'],
29:        $payment = $payments->record($order, $data, $request->user());
30:        return back()->with('status', 'Stavka '.$payment->payment_number.' je evidentirana i saldo porudžbine je preračunat.');
33:    public function verify(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
35:        abort_unless((int) $payment->order_id === (int) $order->id, 404);
37:        $payments->verify($payment, $request->user());
41:    public function reject(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
43:        abort_unless((int) $payment->order_id === (int) $order->id, 404);
46:        $payments->reject($payment, (string) $data['reason'], $request->user());
50:    public function void(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
52:        abort_unless((int) $payment->order_id === (int) $order->id, 404);
54:        $payments->void($payment, $request->user());
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
7:use App\Models\BankAccount;
50:        return $order->load(['items.product', 'items.variant', 'commission', 'bankAccount', 'supplier']);
89:        $bankAccount = $this->resolveBankAccount($data);
90:        $fingerprint = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
110:            'shipping_postal_code' => (string) $data['shipping_postal_code'],
115:            'payment_method' => (string) $data['payment_method'],
116:            'payment_status' => 'pending',
117:            'bank_account_id' => $bankAccount?->id,
118:            'bank_account_label_snapshot' => $bankAccount?->label,
119:            'bank_account_number_snapshot' => $bankAccount?->account_number,
120:            'bank_account_number_display_snapshot' => $bankAccount?->account_number_display,
121:            'payment_recipient_name_snapshot' => $bankAccount?->recipient_name,
122:            'payment_recipient_address_snapshot' => $bankAccount?->recipient_address,
123:            'payment_code_snapshot' => $bankAccount?->payment_code,
130:            'payment_purpose_snapshot' => $bankAccount ? 'Plaćanje porudžbine' : null,
131:            'payment_reference_snapshot' => $bankAccount ? (string) $order->id : null,
306:    private function resolveBankAccount(array $data): ?BankAccount
308:        if (($data['payment_method'] ?? null) !== 'bank_transfer') {
312:        $account = BankAccount::query()->whereKey((int) ($data['bank_account_id'] ?? 0))->where('is_active', true)->first();
314:            throw ValidationException::withMessages(['bank_account_id' => 'Izabrani žiro račun nije aktivan.']);
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php
10:use App\Models\OrderPayment;
39:        private readonly IpsPaymentPayloadService $ips,
110:                    'metadata_json' => json_encode(['source' => 'order_cancellation'], JSON_THROW_ON_ERROR),
175:    public function updatePaymentStatus(Order $order, string $paymentStatus, User $actor): Order
177:        $updated = DB::transaction(function () use ($order, $paymentStatus, $actor): Order {
183:            $before = $locked->payment_status;
184:            $locked->update(['payment_status' => $paymentStatus, 'updated_by' => $actor->id]);
185:            $this->audit->log('order.payment_status_changed', 'Promenjen status plaćanja '.$locked->order_number, $locked, ['payment_status' => $before], ['payment_status' => $paymentStatus], user: $actor);
190:            $this->notifications->order($updated->user, 'order.payment_status_changed', 'Promenjen status plaćanja', 'Status plaćanja za '.$updated->order_number.' je '.$updated->payment_status.'.', $updated);
192:        $this->emails->orderChanged($updated, 'order_payment_changed', 'Promenjen status plaćanja '.$updated->order_number, 'Status plaćanja je '.$updated->payment_status.'.', ['payment_status' => $updated->payment_status, 'actor_id' => $actor->id, 'changed_at' => $updated->updated_at?->toISOString()]);
230:        ?UploadedFile $proof = null,
239:        $paymentNumber = null;
240:        $newProof = null;
241:        $oldProof = null;
248:                $proof,
249:                &$paymentNumber,
250:                &$newProof,
251:                &$oldProof,
297:                if ($proof instanceof UploadedFile) {
298:                    $newProof = $this->storeDeliveryProof($proof, $locked);
299:                    if ($delivery instanceof OrderDelivery && filled($delivery->proof_path)) {
300:                        $oldProof = [
301:                            'disk' => trim((string) ($delivery->proof_disk ?: 'local')) ?: 'local',
302:                            'path' => (string) $delivery->proof_path,
316:                if (is_array($newProof)) {
317:                    $deliveryPayload = array_replace($deliveryPayload, $newProof);
335:                        'has_proof' => filled($delivery->proof_path),
340:                $net = (float) OrderPayment::query()
349:                    if ((string) $locked->payment_method !== 'cash_on_delivery') {
355:                    $payment = OrderPayment::query()->create([
357:                        'payment_number' => $this->numbers->next('payment', (int) now()->format('Y')),
358:                        'entry_type' => 'payment',
361:                        'payment_method' => 'cash_on_delivery',
362:                        'paid_at' => $deliveredAt,
363:                        'reference' => 'COD-'.$locked->order_number,
369:                    $paymentNumber = (string) $payment->payment_number;
374:                $oldPaymentState = (string) $locked->payment_state;
386:                $state = $net > $total + 0.004 ? 'overpaid' : 'paid';
392:                    'paid_total_rsd' => round($net, 2),
393:                    'payment_state' => $state,
394:                    'payment_status' => 'paid',
395:                    'payment_verified_at' => $locked->payment_verified_at ?: now(),
404:                    before: ['status' => $oldStatus, 'payment_state' => $oldPaymentState, 'completed_at' => null],
407:                        'payment_state' => $state,
409:                        'cod_payment_number' => $paymentNumber,
419:            if (is_array($newProof)) {
420:                $this->deletePrivateFile((string) ($newProof['proof_disk'] ?? 'local'), (string) ($newProof['proof_path'] ?? ''));
425:        if (is_array($newProof) && is_array($oldProof)) {
426:            $newDisk = (string) ($newProof['proof_disk'] ?? 'local');
427:            $newPath = (string) ($newProof['proof_path'] ?? '');
428:            if ($oldProof['disk'] !== $newDisk || $oldProof['path'] !== $newPath) {
429:                $this->deletePrivateFile((string) $oldProof['disk'], (string) $oldProof['path']);
441:            if ($paymentNumber !== null) {
442:                $message .= ' Plaćanje pouzećem je evidentirano pod brojem '.$paymentNumber.'.';
527:    private function storeDeliveryProof(UploadedFile $proof, Order $order): array
529:        if (!$proof->isValid()) {
530:            throw ValidationException::withMessages(['delivery_proof' => 'Upload dokaza isporuke nije uspeo. Pokušaj ponovo sa ispravnim fajlom.']);
532:        if ((int) $proof->getSize() > 10 * 1024 * 1024) {
533:            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke ne sme biti veći od 10 MB.']);
537:        $mimeType = strtolower(trim((string) ($proof->getMimeType() ?: $proof->getClientMimeType())));
539:            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke mora biti validan PDF, JPG, PNG ili WebP fajl.']);
542:        $extension = strtolower((string) ($proof->extension() ?: $proof->getClientOriginalExtension() ?: 'bin'));
544:            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke mora biti PDF, JPG, PNG ili WebP fajl.']);
547:        $directory = 'delivery-proofs/'.(int) $order->id.'/'.now()->format('Y/m');
549:        $path = $proof->storeAs($directory, $filename, 'local');
551:            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke nije mogao biti bezbedno sačuvan.']);
555:            'proof_disk' => 'local',
556:            'proof_path' => $path,
557:            'proof_original_name' => mb_substr($proof->getClientOriginalName(), 0, 255),
558:            'proof_mime_type' => mb_substr($mimeType, 0, 120),
559:            'proof_size' => max(0, (int) $proof->getSize()),
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php
9:use App\Models\OrderPayment;
16:final class OrderPaymentService
22:        private readonly IpsPaymentPayloadService $ips,
28:    public function submitProof(Order $order, UploadedFile $file, array $data, User $actor): OrderPayment
31:        $this->assertOrderOpen($order, 'proof');
35:            throw ValidationException::withMessages(['proof' => 'Potvrda se ne može poslati za otkazanu porudžbinu.']);
37:        if ($order->payment_method !== 'bank_transfer') {
38:            throw ValidationException::withMessages(['proof' => 'Potvrda uplate je dostupna samo za uplatu na račun.']);
42:            'payment-proofs/order-'.$order->id,
47:            throw ValidationException::withMessages(['proof' => 'Čuvanje potvrde nije uspelo.']);
51:            $payment = DB::transaction(function () use ($order, $file, $data, $actor, $path): OrderPayment {
54:                $this->assertOrderOpen($locked, 'proof');
55:                $payment = OrderPayment::query()->create([
57:                    'payment_number' => $this->numbers->next('payment', (int) now()->format('Y')),
58:                    'entry_type' => 'payment',
61:                    'payment_method' => 'bank_transfer',
62:                    'paid_at' => $data['paid_at'],
65:                    'proof_path' => $path,
66:                    'proof_original_name' => $file->getClientOriginalName(),
67:                    'proof_mime_type' => $file->getMimeType(),
68:                    'proof_size_bytes' => $file->getSize(),
71:                $this->audit->log('order.payment_proof_submitted', 'Poslata potvrda uplate '.$payment->payment_number, $payment, after: ['amount_rsd' => $payment->amount_rsd, 'status' => 'submitted'], user: $actor);
72:                return $payment;
81:            $this->notifications->order($order->supplier, 'order.payment_proof_submitted', 'Nova potvrda uplate', sprintf('%s je poslao potvrdu uplate od %s RSD za %s.', $actor->displayName(), number_format((float) $payment->amount_rsd, 2, ',', '.'), $order->order_number), $order, ['icon' => 'wallet', 'severity' => 'warning']);
83:        $this->emails->orderChanged($order->fresh(['user', 'supplier']) ?? $order, 'order_payment_changed', 'Poslata potvrda uplate za '.$order->order_number, sprintf('Poslata je potvrda uplate od %s RSD.', number_format((float) $payment->amount_rsd, 2, ',', '.')), ['payment_number' => $payment->payment_number, 'status' => $payment->status]);
85:        return $payment;
89:    public function record(Order $order, array $data, User $actor): OrderPayment
91:        $payment = DB::transaction(function () use ($order, $data, $actor): OrderPayment {
98:            $type = (string) ($data['entry_type'] ?? 'payment');
99:            $payment = OrderPayment::query()->create([
101:                'payment_number' => $this->numbers->next($type === 'refund' ? 'refund' : 'payment', (int) now()->format('Y')),
105:                'payment_method' => (string) $data['payment_method'],
106:                'paid_at' => $data['paid_at'],
114:            $this->audit->log('order.payment_recorded', 'Evidentirana '.($type === 'refund' ? 'refundacija' : 'uplata').' '.$payment->payment_number, $payment, after: ['amount_rsd' => $payment->amount_rsd, 'entry_type' => $type], user: $actor);
115:            return $payment;
117:        $payment->loadMissing('order.user', 'order.supplier');
118:        if ($payment->order instanceof Order) {
119:            $entry = $payment->entry_type === 'refund' ? 'Refundacija' : 'Uplata';
120:            $this->emails->orderChanged($payment->order, 'order_payment_changed', $entry.' za '.$payment->order->order_number, sprintf('%s %s RSD je evidentirana.', $entry, number_format((float) $payment->amount_rsd, 2, ',', '.')), ['payment_number' => $payment->payment_number, 'status' => $payment->status, 'entry_type' => $payment->entry_type]);
121:            $this->syncReceivable($payment->order);
123:        return $payment;
126:    public function recordAfterSalesRefundLocked(AfterSalesAction $action, Order $order, User $actor): OrderPayment
135:        $existing = OrderPayment::query()->where('after_sales_action_id', $action->id)->first();
136:        if ($existing instanceof OrderPayment) {
145:        $netPaid = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
148:        if ($amount > round(max(0, $netPaid), 2) + 0.004) {
150:                'amount_rsd' => 'Refundacija ne može biti veća od trenutno neto uplaćenog iznosa '.number_format(max(0, $netPaid), 2, ',', '.').' RSD.',
154:        $payment = OrderPayment::query()->create([
157:            'payment_number' => $this->numbers->next('refund', (int) now()->format('Y')),
161:            'payment_method' => 'after_sales_refund',
162:            'paid_at' => now(),
170:        $this->audit->log('after_sales.refund_recorded', 'Evidentirana refundacija '.$payment->payment_number.' po radnji '.$action->action_number, $payment, after: ['amount_rsd' => $payment->amount_rsd, 'after_sales_action_id' => $action->id], user: $actor);
172:        return $payment;
175:    public function verify(OrderPayment $payment, User $actor): OrderPayment
177:        $verified = DB::transaction(function () use ($payment, $actor): OrderPayment {
178:            /** @var OrderPayment $locked */
179:            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
182:            $this->assertOrderOpen($order, 'payment');
184:            if ($locked->status !== 'submitted') throw ValidationException::withMessages(['payment' => 'Samo poslata potvrda može biti verifikovana.']);
187:            $this->audit->log('order.payment_verified', 'Verifikovana uplata '.$locked->payment_number, $locked, before: ['status' => 'submitted'], after: ['status' => 'verified'], user: $actor);
191:        $this->notifyCustomer($verified, 'Uplata je potvrđena', 'Potvrda uplate '.$verified->payment_number.' je verifikovana.', 'success');
197:    public function reject(OrderPayment $payment, string $reason, User $actor): OrderPayment
199:        $rejected = DB::transaction(function () use ($payment, $reason, $actor): OrderPayment {
200:            /** @var OrderPayment $locked */
201:            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
207:            $this->audit->log('order.payment_rejected', 'Odbijena potvrda uplate '.$locked->payment_number, $locked, before: ['status' => 'submitted'], after: ['status' => 'rejected', 'reason' => trim($reason)], user: $actor);
214:    public function void(OrderPayment $payment, User $actor): OrderPayment
216:        $voided = DB::transaction(function () use ($payment, $actor): OrderPayment {
217:            /** @var OrderPayment $locked */
218:            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
221:            $this->assertOrderOpen($order, 'payment');
223:            if ($locked->status !== 'verified') throw ValidationException::withMessages(['payment' => 'Samo verifikovana stavka može biti stornirana.']);
226:            $this->audit->log('order.payment_voided', 'Stornirana uplata '.$locked->payment_number, $locked, before: ['status' => 'verified'], after: ['status' => 'voided'], user: $actor);
229:        $this->notifyCustomer($voided, 'Uplata je stornirana', 'Finansijska stavka '.$voided->payment_number.' je stornirana.', 'warning');
235:    public function proof(OrderPayment $payment): ?string
237:        if (!$payment->proof_path || !Storage::disk('local')->exists($payment->proof_path)) return null;
238:        return Storage::disk('local')->path($payment->proof_path);
259:            \Illuminate\Support\Facades\Log::warning('Receivable synchronization failed after payment change.', [
268:        $net = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
271:        $refundTotal = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')->where('entry_type', 'refund')->sum('amount_rsd');
273:        $state = 'unpaid';
276:        elseif ($net <= 0.004) $state = 'unpaid';
278:        elseif ($net > $total + 0.004) $state = 'overpaid';
279:        else $state = 'paid';
282:            'paid_total_rsd' => round($net, 2),
283:            'payment_state' => $state,
284:            'payment_status' => $state === 'cancelled' ? 'cancelled' : ($state === 'refunded' ? 'refunded' : (in_array($state, ['paid', 'overpaid'], true) ? 'paid' : 'pending')),
285:            'payment_verified_at' => in_array($state, ['paid', 'overpaid'], true) ? ($order->payment_verified_at ?: now()) : null,
305:    private function notifyCustomer(OrderPayment $payment, string $title, string $message, string $severity): void
307:        $payment->loadMissing('order.user');
308:        if ($payment->order?->user instanceof User) {
309:            $this->notifications->order($payment->order->user, 'order.payment_'.$payment->status, $title, $message, $payment->order, ['icon' => 'wallet', 'severity' => $severity]);
311:        if ($payment->order instanceof Order) {
312:            $this->emails->orderChanged($payment->order, 'order_payment_changed', $title.' · '.$payment->order->order_number, $message, ['payment_number' => $payment->payment_number, 'status' => $payment->status, 'entry_type' => $payment->entry_type]);
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderTimelineService.php
81:            if (filled($delivery->proof_path)) {
94:        foreach ($this->loadedMany($order, 'payments') as $payment) {
95:            $verifier = $payment->relationLoaded('verifier') ? $payment->verifier?->displayName() : null;
96:            $submitter = $payment->relationLoaded('submitter') ? $payment->submitter?->displayName() : null;
98:                'type' => 'payment',
99:                'title' => ($payment->entry_type === 'refund' ? 'Refundacija' : 'Uplata').' '.(string) $payment->payment_number,
100:                'description' => number_format((float) $payment->amount_rsd, 2, ',', '.').' RSD · '.(string) $payment->status,
102:                'created_at' => $payment->verified_at ?: $payment->created_at,
184:            $auditActions = ['order.payment_status_changed', 'order.tracking_changed', 'order.accepted', 'order.deadlines_changed', 'order.completed', 'order.reopened'];
220:            'order.payment_status_changed' => 'Plaćanje: '.(string) ($after['payment_status'] ?? ''),
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php
88:            $dueDays = max(0, min(365, (int) ($settings['documents_payment_due_days'] ?? 7)));
129:                'customer_city' => trim($locked->shipping_postal_code.' '.$locked->shipping_city),
134:                'payment_method_snapshot' => $locked->payment_method,
135:                'payment_status_snapshot' => $locked->payment_status,
136:                'bank_account_snapshot' => $locked->bank_account_number_display_snapshot,
159:            if ($dueAt !== null && !in_array((string) $locked->payment_state, ['paid', 'overpaid', 'cancelled'], true)) {
160:                $locked->update(['payment_due_at' => $dueAt->copy()->endOfDay()]);
294:        $document->loadMissing(['supersedes', 'order.user', 'order.supplier', 'order.items', 'order.payments', 'order.delivery']);
296:        $document->loadMissing(['supersedes', 'order.user', 'order.supplier', 'order.items', 'order.payments', 'order.delivery']);
337:            'payment_method_snapshot' => $document->payment_method_snapshot,
338:            'payment_status_snapshot' => $document->payment_status_snapshot,
339:            'bank_account_snapshot' => $document->bank_account_snapshot,
344:            'paid_total_rsd' => (float) ($order?->paid_total_rsd ?? 0),
345:            'payment_state' => (string) ($order?->payment_state ?? 'unpaid'),

--- DELIVERY / TRACKING SIGNALS ---
FILE=/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php
25:use App\Http\Controllers\Admin\CourierServiceController;
37:use App\Http\Controllers\Admin\OrderShipmentController as AdminOrderShipmentController;
61:use App\Http\Controllers\OrderDeliveryController;
64:use App\Http\Controllers\OrderShipmentProofController;
157:    Route::get('/orders/{order}/delivery-proof', [OrderDeliveryController::class, 'proof'])
158:        ->whereNumber('order')->name('orders.delivery.proof');
159:    Route::get('/orders/{order}/shipment-proof', OrderShipmentProofController::class)
160:        ->whereNumber('order')->name('orders.shipment.proof');
262:                ->whereNumber('order')->middleware('permission:orders.confirm_delivery')->name('orders.complete');
266:            Route::patch('/orders/{order}/tracking', [AdminOrderController::class, 'tracking'])->whereNumber('order')->name('orders.tracking');
267:            Route::post('/orders/{order}/shipment', [AdminOrderShipmentController::class, 'store'])
268:                ->whereNumber('order')->middleware('throttle:admin-write')->name('orders.shipment.store');
377:            Route::post('/reports/deliveries/{delivery}/retry', [ReportScheduleController::class, 'retry'])->whereNumber('delivery')->middleware('throttle:admin-write')->name('report-deliveries.retry');
441:            Route::get('/couriers', [CourierServiceController::class, 'index'])->name('couriers.index');
442:            Route::post('/couriers', [CourierServiceController::class, 'store'])->middleware('throttle:admin-write')->name('couriers.store');
443:            Route::put('/couriers/{courier}', [CourierServiceController::class, 'update'])->whereNumber('courier')->middleware('throttle:admin-write')->name('couriers.update');
446:            Route::post('/order-emails/dispatch', [OrderEmailSettingsController::class, 'dispatch'])->middleware('throttle:admin-write')->name('order-emails.dispatch');
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php
213:        $data = $request->validate(['status' => ['required', Rule::in(['new', 'processing', 'confirmed', 'shipped', 'cancelled'])], 'note' => ['nullable', 'string', 'max:1000']]);
222:            'delivery_method' => $request->input('delivery_method', 'own_transport'),
229:            'delivery_method' => ['required', Rule::in(['own_transport', 'courier', 'customer_pickup', 'other'])],
233:            'delivery_reference' => ['nullable', 'string', 'max:190'],
234:            'delivery_note' => ['nullable', 'string', 'max:3000'],
236:            'delivery_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
238:        $completed = $workflow->complete($order, $request->user(), $data, $request->file('delivery_proof'));
263:    public function tracking(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
266:        $data = $request->validate(['tracking_number' => ['nullable', 'string', 'max:120']]);
267:        $workflow->updateTracking($order, filled($data['tracking_number'] ?? null) ? trim((string) $data['tracking_number']) : null, $request->user());
268:        return back()->with('status', 'Tracking broj je ažuriran.');
312:            'status' => ['nullable', Rule::in(['new', 'processing', 'confirmed', 'shipped', 'completed', 'cancelled'])],
469:        foreach (['user', 'supplier', 'bankAccount', 'acceptedBy', 'assignedBy', 'completedBy', 'reopenedBy', 'commission', 'delivery'] as $relation) {
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderDeliveryController.php
13:final class OrderDeliveryController extends Controller
18:        $delivery = $order->delivery()->firstOrFail();
19:        $disk = trim((string) ($delivery->proof_disk ?: 'local')) ?: 'local';
20:        $path = trim((string) $delivery->proof_path);
27:            basename((string) ($delivery->proof_original_name ?: 'dokaz-isporuke')),
31:            'Content-Type' => $delivery->proof_mime_type ?: 'application/octet-stream',
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/PaymentController.php
24:            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'cash_on_delivery', 'card', 'other'])],
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php
9:use App\Models\OrderDelivery;
30:        'confirmed' => ['shipped', 'cancelled'],
31:        'shipped' => ['cancelled'],
65:            if ($newStatus === 'shipped') {
130:            $labels = ['new' => 'nova', 'processing' => 'u obradi', 'confirmed' => 'potvrđena', 'shipped' => 'poslata', 'cancelled' => 'otkazana'];
137:                ['severity' => $updated->status === 'cancelled' ? 'danger' : ($updated->status === 'shipped' ? 'success' : 'info')],
163:        $statusLabels = ['new' => 'Nova', 'processing' => 'U obradi', 'confirmed' => 'Potvrđena', 'shipped' => 'Poslata', 'cancelled' => 'Otkazana'];
196:    public function updateTracking(Order $order, ?string $trackingNumber, User $actor): Order
198:        throw ValidationException::withMessages(['tracking_number' => 'Broj za praćenje se unosi isključivo kroz Evidenciju slanja pošiljke.']);
200:        $updated = DB::transaction(function () use ($order, $trackingNumber, $actor): Order {
206:            $before = $locked->tracking_number;
208:                'tracking_number' => $trackingNumber,
209:                'tracking_updated_at' => now(),
210:                'tracking_updated_by' => $actor->id,
213:            $this->audit->log('order.tracking_changed', 'Promenjen tracking '.$locked->order_number, $locked, ['tracking_number' => $before], ['tracking_number' => $trackingNumber], user: $actor);
217:        if ($updated->user instanceof User && filled($trackingNumber)) {
218:            $this->notifications->order($updated->user, 'order.tracking_changed', 'Dodat je tracking broj', 'Tracking broj za '.$updated->order_number.' je '.$trackingNumber.'.', $updated, ['severity' => 'success']);
220:        $trackingMessage = filled($trackingNumber) ? 'Broj za praćenje je '.$trackingNumber.'.' : 'Broj za praćenje je uklonjen.';
221:        $this->emails->orderChanged($updated, 'order_tracking_changed', 'Ažurirano praćenje porudžbine '.$updated->order_number, $trackingMessage, ['tracking_number' => $trackingNumber, 'actor_id' => $actor->id, 'changed_at' => $updated->updated_at?->toISOString()]);
225:    /** @param array<string,mixed>|string|null $deliveryData */
229:        array|string|null $deliveryData = [],
232:        if (is_string($deliveryData)) {
233:            $deliveryData = ['completion_note' => $deliveryData];
235:        if (!is_array($deliveryData)) {
236:            $deliveryData = [];
247:                $deliveryData,
255:                    ->with(['user', 'supplier', 'delivery'])
261:                    return $locked->fresh(['user', 'supplier', 'completedBy', 'delivery.confirmer']) ?? $locked;
266:                if (!in_array((string) $locked->status, ['confirmed', 'shipped'], true)) {
270:                $deliveryMethod = trim((string) ($deliveryData['delivery_method'] ?? 'own_transport'));
271:                if (!in_array($deliveryMethod, ['own_transport', 'courier', 'customer_pickup', 'other'], true)) {
272:                    throw ValidationException::withMessages(['delivery_method' => 'Izabran je nepodržan način isporuke.']);
276:                    $deliveredAt = Carbon::parse((string) ($deliveryData['delivered_at'] ?? now()));
281:                $recipientName = trim((string) ($deliveryData['recipient_name'] ?? ''));
285:                $recipientPhone = trim((string) ($deliveryData['recipient_phone'] ?? $locked->shipping_phone));
286:                $referenceInput = trim((string) ($deliveryData['delivery_reference'] ?? ''));
287:                $reference = $referenceInput !== '' ? $referenceInput : trim((string) $locked->tracking_number);
288:                $deliveryNote = trim((string) ($deliveryData['delivery_note'] ?? ''));
289:                $completionNote = trim((string) ($deliveryData['completion_note'] ?? $deliveryData['note'] ?? ''));
291:                /** @var OrderDelivery|null $delivery */
292:                $delivery = OrderDelivery::query()
298:                    $newProof = $this->storeDeliveryProof($proof, $locked);
299:                    if ($delivery instanceof OrderDelivery && filled($delivery->proof_path)) {
301:                            'disk' => trim((string) ($delivery->proof_disk ?: 'local')) ?: 'local',
302:                            'path' => (string) $delivery->proof_path,
307:                $deliveryPayload = [
308:                    'delivery_method' => $deliveryMethod,
313:                    'note' => $deliveryNote !== '' ? $deliveryNote : null,
317:                    $deliveryPayload = array_replace($deliveryPayload, $newProof);
320:                if ($delivery instanceof OrderDelivery) {
321:                    $delivery->update($deliveryPayload);
323:                    $delivery = OrderDelivery::query()->create(['order_id' => $locked->id] + $deliveryPayload);
327:                    'order.delivery_confirmed',
329:                    $delivery,
331:                        'delivery_method' => $deliveryMethod,
335:                        'has_proof' => filled($delivery->proof_path),
349:                    if ((string) $locked->payment_method !== 'cash_on_delivery') {
361:                        'payment_method' => 'cash_on_delivery',
375:                if ($oldStatus !== 'shipped') {
380:                        'new_status' => 'shipped',
388:                    'status' => 'shipped',
406:                        'status' => 'shipped',
410:                        'delivery_id' => $delivery->id,
416:                return $locked->fresh(['user', 'supplier', 'completedBy', 'delivery.confirmer']) ?? $locked;
497:            return $locked->fresh(['user', 'supplier', 'reopenedBy', 'delivery.confirmer']) ?? $locked;
527:    private function storeDeliveryProof(UploadedFile $proof, Order $order): array
530:            throw ValidationException::withMessages(['delivery_proof' => 'Upload dokaza isporuke nije uspeo. Pokušaj ponovo sa ispravnim fajlom.']);
533:            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke ne sme biti veći od 10 MB.']);
539:            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke mora biti validan PDF, JPG, PNG ili WebP fajl.']);
544:            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke mora biti PDF, JPG, PNG ili WebP fajl.']);
547:        $directory = 'delivery-proofs/'.(int) $order->id.'/'.now()->format('Y/m');
551:            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke nije mogao biti bezbedno sačuvan.']);
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderTimelineService.php
69:        if ($order->relationLoaded('delivery') && $order->delivery !== null) {
70:            $delivery = $order->delivery;
73:                'courier' => 'Kurirska služba',
76:            ][(string) $delivery->delivery_method] ?? (string) $delivery->delivery_method;
77:            $description = $method.' · primalac '.(string) $delivery->recipient_name;
78:            if (filled($delivery->reference)) {
79:                $description .= ' · referenca '.(string) $delivery->reference;
81:            if (filled($delivery->proof_path)) {
85:                'type' => 'delivery',
88:                'actor' => $delivery->relationLoaded('confirmer') ? $delivery->confirmer?->displayName() : null,
89:                'created_at' => $delivery->delivered_at ?: $delivery->created_at,
184:            $auditActions = ['order.payment_status_changed', 'order.tracking_changed', 'order.accepted', 'order.deadlines_changed', 'order.completed', 'order.reopened'];
221:            'order.tracking_changed' => 'Tracking: '.(string) ($after['tracking_number'] ?? 'uklonjen'),
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php
33:        if (!in_array($type, ['order_confirmation', 'proforma', 'invoice', 'delivery_note'], true)) {
57:            $locked = Order::query()->with(['user', 'supplier', 'items', 'delivery'])->lockForUpdate()->findOrFail($order->id);
89:            $dueAt = in_array($type, ['order_confirmation', 'delivery_note'], true) ? null : $issuedAt->copy()->addDays($dueDays);
90:            if ($type === 'delivery_note' && $locked->delivery === null) {
95:            $customerName = $type === 'delivery_note'
96:                ? (trim((string) $locked->delivery?->recipient_name) ?: trim((string) $locked->shipping_full_name) ?: 'Kupac')
98:            $customerPhone = $type === 'delivery_note'
99:                ? (trim((string) $locked->delivery?->recipient_phone) ?: $locked->shipping_phone)
142:                'delivery_method_snapshot' => $locked->delivery?->delivery_method,
143:                'delivery_recipient_snapshot' => $locked->delivery?->recipient_name,
144:                'delivered_at_snapshot' => $locked->delivery?->delivered_at,
145:                'delivery_reference_snapshot' => $locked->delivery?->reference,
146:                'delivery_note_snapshot' => $locked->delivery?->note,
187:                $labels = ['order_confirmation' => 'Potvrda porudžbine', 'proforma' => 'Predračun', 'invoice' => 'Račun', 'delivery_note' => 'Otpremnica'];
294:        $document->loadMissing(['supersedes', 'order.user', 'order.supplier', 'order.items', 'order.payments', 'order.delivery']);
296:        $document->loadMissing(['supersedes', 'order.user', 'order.supplier', 'order.items', 'order.payments', 'order.delivery']);
346:            'delivery_method_snapshot' => $document->delivery_method_snapshot,
347:            'delivery_recipient_snapshot' => $document->delivery_recipient_snapshot,
349:            'delivery_reference_snapshot' => $document->delivery_reference_snapshot,
350:            'delivery_note_snapshot' => $document->delivery_note_snapshot,
367:        if ($type === 'delivery_note') {
383:        if ($type === 'delivery_note') {
385:                'delivery_method_snapshot',
386:                'delivery_recipient_snapshot',
388:                'delivery_reference_snapshot',
389:                'delivery_note_snapshot',
426:        if ($type !== 'delivery_note' || !in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
433:            if (str_starts_with($definition, 'enum(') && !str_contains($definition, "'delivery_note'")) {
460:        if (in_array($type, ['proforma', 'invoice', 'delivery_note'], true)) {

--- ASSIGNMENT / CLAIM / DEADLINE SIGNALS ---
FILE=/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php
270:            Route::patch('/orders/{order}/deadlines', [AdminOrderController::class, 'deadlines'])->whereNumber('order')->name('orders.deadlines');
352:        Route::patch('/orders/{order}/reassign', [AdminOrderController::class, 'reassign'])
353:            ->whereNumber('order')->middleware('permission:orders.reassign')->name('orders.reassign');
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php
229:            'delivery_method' => ['required', Rule::in(['own_transport', 'courier', 'customer_pickup', 'other'])],
284:    public function reassign(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
292:        $operations->reassign($order, $supplier, $request->user(), (string) $data['reason']);
296:    public function deadlines(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
302:        $operations->updateDeadlines($order, $request->user(), $data);
463:        foreach (['items', 'documents', 'statusHistory', 'assignments', 'payments', 'internalNotes'] as $relation) {
469:        foreach (['user', 'supplier', 'bankAccount', 'acceptedBy', 'assignedBy', 'completedBy', 'reopenedBy', 'commission', 'delivery'] as $relation) {
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
103:            'assigned_at' => now(),
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php
271:                if (!in_array($deliveryMethod, ['own_transport', 'courier', 'customer_pickup', 'other'], true)) {
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php
8:use App\Models\OrderAssignment;
75:    public function reassign(Order $order, User $newSupplier, User $actor, string $reason): Order
98:            OrderAssignment::query()->create([
113:                'assigned_by' => $actor->id,
114:                'assigned_at' => now(),
115:                'reassigned_at' => now(),
120:            $this->audit->log('order.reassigned', 'Ponovo dodeljena porudžbina '.$locked->order_number, $locked, before: $before, after: ['supplier_user_id' => $newSupplier->id, 'supplier_name_snapshot' => $newSupplier->displayName()], metadata: ['reason' => $reason], user: $actor);
124:        $this->notifications->order($newSupplier, 'order.assigned', 'Dodeljena vam je porudžbina', 'Porudžbina '.$updated->order_number.' je dodeljena vama. Razlog: '.$reason, $updated, ['severity' => 'warning']);
126:            $this->notifications->order($oldSupplier, 'order.reassigned_away', 'Porudžbina je ponovo dodeljena', 'Porudžbina '.$updated->order_number.' više nije dodeljena vama.', $updated, [
135:        $this->emails->orderChanged($updated, 'order_reassigned', 'Promenjeno odgovorno lice za '.$updated->order_number, 'Novo odgovorno lice je '.$newSupplier->displayName().'. Razlog: '.$reason, ['supplier_user_id' => $newSupplier->id, 'reason' => $reason, 'actor_id' => $actor->id]);
140:    public function updateDeadlines(Order $order, User $actor, array $data): Order
156:            $this->audit->log('order.deadlines_changed', 'Promenjeni rokovi '.$locked->order_number, $locked, before: $before, after: ['expected_processing_at' => $locked->expected_processing_at?->toISOString(), 'expected_shipping_at' => $locked->expected_shipping_at?->toISOString()], user: $actor);
168:            $this->notifications->order($updated->user, 'order.deadlines_changed', 'Ažurirani su rokovi porudžbine', $message, $updated);
170:        $this->emails->orderChanged($updated, 'order_deadlines_changed', 'Ažurirani rokovi porudžbine '.$updated->order_number, $message, ['expected_processing_at' => $updated->expected_processing_at?->toISOString(), 'expected_shipping_at' => $updated->expected_shipping_at?->toISOString(), 'actor_id' => $actor->id]);
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderTimelineService.php
43:        foreach ($this->loadedMany($order, 'assignments') as $row) {
47:                'type' => 'assignment',
74:                'customer_pickup' => 'Lično preuzimanje',
184:            $auditActions = ['order.payment_status_changed', 'order.tracking_changed', 'order.accepted', 'order.deadlines_changed', 'order.completed', 'order.reopened'];
223:            'order.deadlines_changed' => 'Ažurirani su očekivani rokovi obrade i slanja.',
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php
111:                'due_at' => $dueAt?->toDateString(),
160:                $locked->update(['payment_due_at' => $dueAt->copy()->endOfDay()]);
315:            'due_at' => $document->due_at?->format('d.m.Y'),

--- INTERNAL NOTE SIGNALS ---
FILE=/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php
350:        Route::post('/orders/{order}/internal-notes', [AdminOrderController::class, 'note'])
351:            ->whereNumber('order')->middleware('permission:orders.internal_notes')->name('orders.notes.store');
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php
213:        $data = $request->validate(['status' => ['required', Rule::in(['new', 'processing', 'confirmed', 'shipped', 'cancelled'])], 'note' => ['nullable', 'string', 'max:1000']]);
214:        $workflow->changeStatus($order, (string) $data['status'], $request->user(), $data['note'] ?? null);
226:            'completion_note' => $request->input('completion_note', $request->input('note')),
234:            'delivery_note' => ['nullable', 'string', 'max:3000'],
235:            'completion_note' => ['nullable', 'string', 'max:1000'],
277:    public function note(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
279:        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);
280:        $operations->addInternalNote($order, $request->user(), (string) $data['note']);
463:        foreach (['items', 'documents', 'statusHistory', 'assignments', 'payments', 'internalNotes'] as $relation) {
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderPaymentController.php
23:            'note' => ['nullable', 'string', 'max:2000'],
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/PaymentController.php
27:            'note' => ['nullable', 'string', 'max:2000'],
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
114:            'customer_note' => $data['customer_note'] ?? null,
219:                'note' => 'Rezervacija lagera za porudžbinu '.$orderNumber,
243:            'note' => 'Porudžbina kreirana u Laravel produkcionom sistemu.',
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php
44:    public function cancelOwn(Order $order, User $user, ?string $note = null): Order
52:        return $this->changeStatus($order, 'cancelled', $user, $note ?: 'Korisnik je otkazao porudžbinu.');
55:    public function changeStatus(Order $order, string $newStatus, User $actor, ?string $note = null): Order
57:        $updated = DB::transaction(function () use ($order, $newStatus, $actor, $note): Order {
89:                'note' => $note,
98:                    'status_note' => $note,
109:                    'note' => $note,
122:                metadata: ['note' => $note],
135:                sprintf('Porudžbina %s je sada %s.%s', $updated->order_number, $labels[$updated->status] ?? $updated->status, $note ? ' Napomena: '.$note : ''),
145:                $this->warranties->voidForOrder($updated, $actor, $note ?: 'Porudžbina je otkazana.');
157:                'Korisnik je otkazao porudžbinu '.$updated->order_number.'.'.($note ? ' Razlog: '.$note : ''),
168:            'Novi status: '.($statusLabels[$updated->status] ?? $updated->status).($note ? '. Napomena: '.$note : '.'),
169:            ['status' => $updated->status, 'note' => $note, 'actor_id' => $actor->id, 'changed_at' => $updated->updated_at?->toISOString()],
233:            $deliveryData = ['completion_note' => $deliveryData];
288:                $deliveryNote = trim((string) ($deliveryData['delivery_note'] ?? ''));
289:                $completionNote = trim((string) ($deliveryData['completion_note'] ?? $deliveryData['note'] ?? ''));
313:                    'note' => $deliveryNote !== '' ? $deliveryNote : null,
364:                        'note' => 'Automatski evidentirano pri konačnom završetku isporuke.',
381:                        'note' => 'Isporuka je završena prilikom kompletiranja porudžbine.',
391:                    'completion_note' => $completionNote !== '' ? $completionNote : 'Isporuka je završena i porudžbina je kompletirana.',
412:                    metadata: ['note' => $completionNote !== '' ? $completionNote : null],
480:                'completion_note' => null,
614:                'note' => 'Jednokratni povrat lagera za otkazanu porudžbinu '.$order->order_number,
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php
9:use App\Models\OrderInternalNote;
52:    public function addInternalNote(Order $order, User $actor, string $note): OrderInternalNote
55:        $note = trim($note);
56:        if ($note === '') {
57:            throw ValidationException::withMessages(['note' => 'Interna napomena je obavezna.']);
60:        return DB::transaction(function () use ($order, $actor, $note): OrderInternalNote {
64:            $entry = OrderInternalNote::query()->create([
67:                'note' => $note,
69:            $locked->update(['last_internal_note_at' => now(), 'updated_by' => $actor->id]);
70:            $this->audit->log('order.internal_note_added', 'Dodata interna napomena '.$locked->order_number, $locked, after: ['note_id' => $entry->id], metadata: ['note' => $note], user: $actor);
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php
64:                    'note' => trim((string) ($data['note'] ?? '')) ?: null,
108:                'note' => trim((string) ($data['note'] ?? '')) ?: null,
164:            'note' => 'Refundacija po postprodajnoj radnji '.$action->action_number.'. '.trim((string) $action->public_note),
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderTimelineService.php
36:                'description' => $row->note ?: 'Status porudžbine je promenjen.',
57:            foreach ($this->loadedMany($order, 'internalNotes') as $row) {
59:                    'type' => 'internal_note',
61:                    'description' => (string) $row->note,
113:                    'description' => $row->note ?: 'Status provizije je promenjen.',
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php
33:        if (!in_array($type, ['order_confirmation', 'proforma', 'invoice', 'delivery_note'], true)) {
89:            $dueAt = in_array($type, ['order_confirmation', 'delivery_note'], true) ? null : $issuedAt->copy()->addDays($dueDays);
90:            if ($type === 'delivery_note' && $locked->delivery === null) {
95:            $customerName = $type === 'delivery_note'
98:            $customerPhone = $type === 'delivery_note'
141:                'note' => trim((string) ($settings['documents_default_note'] ?? '')) ?: null,
146:                'delivery_note_snapshot' => $locked->delivery?->note,
187:                $labels = ['order_confirmation' => 'Potvrda porudžbine', 'proforma' => 'Predračun', 'invoice' => 'Račun', 'delivery_note' => 'Otpremnica'];
340:            'note' => $document->note,
341:            'footer_note' => $settings['documents_footer_note'] ?? '',
350:            'delivery_note_snapshot' => $document->delivery_note_snapshot,
367:        if ($type === 'delivery_note') {
383:        if ($type === 'delivery_note') {
389:                'delivery_note_snapshot',
426:        if ($type !== 'delivery_note' || !in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
433:            if (str_starts_with($definition, 'enum(') && !str_contains($definition, "'delivery_note'")) {
460:        if (in_array($type, ['proforma', 'invoice', 'delivery_note'], true)) {

============================================================
5. PERMISSION / AUTHORIZATION SIGNALS
============================================================

--- ORDER-RELATED PERMISSION TOKENS ---
FILE=/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php
46:                Route::middleware('permission:commissions.manage')->group(function (): void {
54:                Route::middleware('permission:warranties.manage')->group(function (): void {
67:                Route::middleware('permission:reports.view')->group(function (): void {
69:                    Route::get('/reports/management.csv', [AdminReportController::class, 'managementCsv'])->middleware(['permission:reports.export', 'throttle:exports'])->name('reports.management.csv');
70:                    Route::get('/reports/management.pdf', [AdminReportController::class, 'managementPdf'])->middleware(['permission:reports.export', 'throttle:exports'])->name('reports.management.pdf');
72:                Route::middleware('permission:reports.manage')->group(function (): void {
81:                Route::get('/system-health', [AdminSystemHealthController::class, 'index'])->middleware('permission:system.health')->name('system-health.index');
82:                Route::middleware('permission:security.view')->group(function (): void {
86:                Route::middleware('permission:orders.manage')->group(function (): void {
87:                    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
88:                    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
92:            ->middleware('permission:catalog.manage_products')
99:            ->middleware(['permission:catalog.manage_images', 'throttle:uploads'])
115:        Route::middleware('permission:catalog.view')->group(function (): void {
122:            ->middleware('permission:orders.create')
123:            ->name('orders.options');
124:        Route::middleware('permission:orders.manage')->group(function (): void {
125:            Route::get('/orders/assigned', [OrderController::class, 'assigned'])->name('orders.assigned.index');
126:            Route::get('/orders/assigned/{order}', [OrderController::class, 'assignedShow'])->whereNumber('order')->name('orders.assigned.show');
128:        Route::middleware('permission:orders.view_own')->group(function (): void {
129:            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
130:            Route::get('/orders/{order}/post-create', [OrderController::class, 'postCreate'])->whereNumber('order')->name('orders.post-create');
131:            Route::get('/orders/{order}/delivery-proof', [OrderController::class, 'deliveryProof'])->whereNumber('order')->name('orders.delivery.proof');
132:            Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
135:            ->middleware(['permission:orders.create', 'throttle:orders'])
136:            ->name('orders.store');
139:            ->middleware('permission:orders.cancel_own')
140:            ->name('orders.cancel');
142:        Route::middleware('permission:payments.view_own')->group(function (): void {
144:                ->whereNumber('order')->whereNumber('payment')->name('orders.payments.proof');
148:            ->middleware(['permission:payments.upload_proof', 'throttle:uploads'])
149:            ->name('orders.payments.proof.store');
151:        Route::middleware('permission:invoices.view_own')->group(function (): void {
153:                ->whereNumber('order')->name('orders.documents.confirmation');
155:                ->whereNumber('order')->whereNumber('document')->name('orders.documents.show');
158:        Route::middleware('permission:after_sales.view_own')->group(function (): void {
164:        Route::middleware('permission:after_sales.create')->group(function (): void {
175:        Route::middleware('permission:warranties.view_own')->group(function (): void {
181:        Route::middleware('permission:commissions.view_own')->group(function (): void {
186:        Route::middleware('permission:notifications.view')->group(function (): void {
FILE=/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php
114:    Route::middleware('permission:orders.view_own')->group(function (): void {
121:    Route::middleware('permission:catalog.view')->group(function (): void {
133:    Route::middleware('permission:orders.view_own')->group(function (): void {
134:        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
135:        Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
137:    Route::middleware('permission:orders.create')->group(function (): void {
138:        Route::get('/order/new', [OrderController::class, 'create'])->name('orders.create');
139:        Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:orders')->name('orders.store');
143:        ->middleware('permission:orders.cancel_own')
144:        ->name('orders.cancel');
146:    Route::middleware('permission:invoices.view_own')->group(function (): void {
147:        Route::get('/orders/{order}/documents/confirmation', [OrderDocumentController::class, 'confirmation'])->whereNumber('order')->name('orders.documents.confirmation');
148:        Route::get('/orders/{order}/documents/{document}.pdf', [OrderDocumentController::class, 'show'])->whereNumber('order')->whereNumber('document')->name('orders.documents.show');
151:    Route::middleware('permission:payments.view_own')->group(function (): void {
153:            ->whereNumber('order')->whereNumber('payment')->name('orders.payments.proof');
156:        ->whereNumber('order')->middleware(['permission:payments.upload_proof', 'throttle:uploads'])->name('orders.payments.proof.store');
158:        ->whereNumber('order')->name('orders.delivery.proof');
160:        ->whereNumber('order')->name('orders.shipment.proof');
162:    Route::middleware('permission:after_sales.view_own')->group(function (): void {
168:    Route::middleware('permission:after_sales.create')->group(function (): void {
179:    Route::middleware('permission:warranties.view_own')->group(function (): void {
187:        ->middleware('permission:commissions.view_own')
190:    Route::middleware('permission:notifications.view')->group(function (): void {
197:        Route::middleware('permission:catalog.manage_products')->group(function (): void {
230:        Route::middleware('permission:catalog.manage_images')->group(function (): void {
238:        Route::middleware('permission:catalog.audit')->group(function (): void {
243:        Route::middleware('permission:catalog.manage_taxonomy')->group(function (): void {
253:        Route::middleware('permission:orders.manage')->group(function (): void {
254:            Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
255:            Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
256:            Route::get('/orders/archived', [AdminOrderController::class, 'archived'])->name('orders.archived');
257:            Route::post('/orders/{order}/archive', [AdminOrderController::class, 'archive'])->whereNumber('order')->name('orders.archive');
258:            Route::post('/orders/archived/{orderId}/restore', [AdminOrderController::class, 'restore'])->whereNumber('orderId')->name('orders.restore');
259:            Route::delete('/orders/archived/{orderId}/purge', [AdminOrderController::class, 'purge'])->whereNumber('orderId')->name('orders.purge');
260:            Route::patch('/orders/{order}/status', [AdminOrderController::class, 'status'])->whereNumber('order')->name('orders.status');
262:                ->whereNumber('order')->middleware('permission:orders.confirm_delivery')->name('orders.complete');
264:                ->whereNumber('order')->middleware('permission:orders.reopen')->name('orders.reopen');
265:            Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'payment'])->whereNumber('order')->name('orders.payment');
266:            Route::patch('/orders/{order}/tracking', [AdminOrderController::class, 'tracking'])->whereNumber('order')->name('orders.tracking');
268:                ->whereNumber('order')->middleware('throttle:admin-write')->name('orders.shipment.store');
269:            Route::post('/orders/{order}/accept', [AdminOrderController::class, 'accept'])->whereNumber('order')->name('orders.accept');
270:            Route::patch('/orders/{order}/deadlines', [AdminOrderController::class, 'deadlines'])->whereNumber('order')->name('orders.deadlines');
272:        Route::middleware('permission:receivables.manage')->group(function (): void {
283:        Route::middleware('permission:after_sales.manage')->group(function (): void {
290:        Route::middleware('permission:after_sales.execute')->group(function (): void {
300:        Route::middleware('permission:field_operations.view')->group(function (): void {
304:        Route::middleware('permission:field_operations.manage')->group(function (): void {
316:        Route::get('/service-parts', [ServicePartController::class, 'index'])->middleware('permission:service_parts.view')->name('service-parts.index');
317:        Route::middleware('permission:service_parts.manage')->group(function (): void {
325:        Route::middleware('permission:warranties.manage')->group(function (): void {
337:        Route::middleware('permission:service_parts.procurement')->group(function (): void {
351:            ->whereNumber('order')->middleware('permission:orders.internal_notes')->name('orders.notes.store');
353:            ->whereNumber('order')->middleware('permission:orders.reassign')->name('orders.reassign');
355:        Route::middleware('permission:commissions.manage')->group(function (): void {
362:        Route::middleware('permission:reports.view')->group(function (): void {
364:            Route::get('/reports/management.pdf', [ManagementReportController::class, 'pdf'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.management.pdf');
365:            Route::get('/reports/management.csv', [ManagementReportController::class, 'csv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.management.csv');
366:            Route::get('/reports/orders.pdf', [ReportController::class, 'pdf'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.orders.pdf');
367:            Route::get('/reports/orders.csv', [ReportController::class, 'csv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.orders.csv');
368:            Route::get('/reports/payments.csv', [ReportController::class, 'paymentsCsv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.payments.csv');
369:            Route::get('/reports/inventory.csv', [ReportController::class, 'inventoryCsv'])->middleware(['permission:inventory.export','throttle:exports'])->name('reports.inventory.csv');
371:        Route::middleware('permission:reports.manage')->group(function (): void {
379:        Route::middleware('permission:invoices.manage')->group(function (): void {
380:            Route::post('/orders/{order}/documents', [AdminOrderDocumentController::class, 'store'])->whereNumber('order')->name('orders.documents.store');
384:                ->name('orders.invoice.pdf');
385:            Route::post('/orders/{order}/documents/{document}/cancel', [AdminOrderDocumentController::class, 'cancel'])->whereNumber('order')->whereNumber('document')->name('orders.documents.cancel');
388:        Route::middleware('permission:payments.manage')->group(function (): void {
389:            Route::post('/orders/{order}/payments', [AdminPaymentController::class, 'store'])->whereNumber('order')->name('orders.payments.store');
390:            Route::post('/orders/{order}/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.verify');
391:            Route::post('/orders/{order}/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.reject');
392:            Route::post('/orders/{order}/payments/{payment}/void', [AdminPaymentController::class, 'void'])->whereNumber('order')->whereNumber('payment')->name('orders.payments.void');
394:        Route::get('/stock-movements', StockMovementController::class)->middleware('permission:stock.view')->name('stock.index');
395:        Route::post('/stock/{product}/adjust', StockAdjustmentController::class)->middleware('permission:stock.adjust')->name('stock.adjust');
397:        Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:stock.view')->name('inventory.index');
398:        Route::post('/inventory/receipts', [InventoryController::class, 'receive'])->middleware('permission:inventory.receive')->name('inventory.receive');
399:        Route::post('/inventory/counts', [InventoryController::class, 'count'])->middleware('permission:inventory.count')->name('inventory.count');
400:        Route::get('/inventory.csv', [InventoryController::class, 'csv'])->middleware(['permission:inventory.export','throttle:exports'])->name('inventory.csv');
402:        Route::middleware('permission:system.manage_users')->group(function (): void {
410:            Route::post('/customer-portal/users/{user}/orders/link', [AdminCustomerPortalController::class, 'linkOrder'])->whereNumber('user')->middleware('throttle:admin-write')->name('customer-portal.users.orders.link');
423:        Route::prefix('settings')->name('settings.')->middleware('permission:system.manage_settings')->group(function (): void {
424:            Route::get('/automation', [AutomationController::class, 'index'])->middleware('permission:automation.manage')->name('automation.index');
425:            Route::put('/automation', [AutomationController::class, 'update'])->middleware('permission:automation.manage')->name('automation.update');
426:            Route::post('/automation/run', [AutomationController::class, 'run'])->middleware('permission:automation.manage')->name('automation.run');
427:            Route::post('/automation/alerts/{alert}/resolve', [AutomationController::class, 'resolve'])->middleware('permission:automation.manage')->name('automation.alerts.resolve');
428:            Route::get('/system-health', [SystemHealthController::class, 'index'])->middleware('permission:system.health')->name('system-health.index');
429:            Route::post('/system-health/run', [SystemHealthController::class, 'run'])->middleware(['permission:system.health','throttle:admin-write'])->name('system-health.run');
430:            Route::post('/system-health/backup', [SystemHealthController::class, 'backup'])->middleware(['permission:backups.manage','throttle:backup'])->name('system-health.backup');
431:            Route::post('/system-health/prune', [SystemHealthController::class, 'prune'])->middleware(['permission:backups.manage','throttle:admin-write'])->name('system-health.prune');
457:        Route::get('/audit-log', AuditLogController::class)->middleware('permission:catalog.audit')->name('audit.index');
458:        Route::get('/audit-log.csv', [AuditLogController::class, 'csv'])->middleware(['permission:audit.export','throttle:exports'])->name('audit.csv');
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php
41:        abort_unless($actor->can('orders.manage'), 403);
105:        abort_unless($actor->can('orders.manage'), 403);
126:                'orders_manage' => $actor->can('orders.manage'),
127:                'internal_notes' => $actor->can('orders.internal_notes'),
128:                'reassign' => $actor->hasRole('superadmin') && $actor->can('orders.reassign'),
129:                'payments' => $actor->can('payments.manage'),
130:                'documents' => $actor->can('invoices.manage'),
131:                'confirm_delivery' => $actor->can('orders.confirm_delivery'),
132:                'reopen' => $actor->can('orders.reopen'),
FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php
164:        return view('admin.orders.archived', [
180:            ->route('admin.orders.archived')
189:            ->route('admin.orders.show', $order)
207:            ->route('admin.orders.archived')
325:            $html = view('admin.orders.index', $data)->with('errors', new ViewErrorBag())->render();
347:            $html = view('admin.orders.show', $data)->with('errors', new ViewErrorBag())->render();
406:        $back = ViewValue::route('admin.orders.index') ?? '/admin/orders';
455:            return Gate::forUser($actor)->allows($ability);
ORDERS_MANAGE_PERMISSION_SIGNAL_COUNT=6
ORDERS_REASSIGN_PERMISSION_SIGNAL_COUNT=2
ORDERS_INTERNAL_NOTES_PERMISSION_SIGNAL_COUNT=2
PAYMENTS_MANAGE_PERMISSION_SIGNAL_COUNT=2

============================================================
6. CURRENT MOBILE MUTATION GAP
============================================================
--- Mobile Admin Orders API ---
1:import { apiRequest, queryString } from '@/lib/api/client';
3:export type AdminOrdersPerPage = 20 | 40 | 50 | 100;
5:export type AdminOrdersUser = {
11:export type AdminOrderListItem = {
16:  status: string;
17:  is_completed: boolean;
18:  payment_status: string | null;
19:  payment_state: string | null;
24:  assigned_at: string | null;
28:  tracking_number: string | null;
33:export type AdminOrdersRequestParams = {
35:  status?: string;
36:  payment_status?: string;
46:export type AdminOrdersPagination = {
55:export type AdminOrdersNormalizedFilters = {
57:  status: string | null;
58:  payment_status: string | null;
68:export type AdminOrdersFilterOptions = {
69:  statuses: string[];
70:  payment_statuses: string[];
77:export type AdminOrdersListCapabilities = {
79:  workflow_mutations: boolean;
82:export type AdminOrderDetailScalar = string | number | boolean | null;
83:export type AdminOrderDetailValue =
87:export type AdminOrderDetailRecord = { [key: string]: AdminOrderDetailValue };
89:export type AdminOrderDetailCapabilities = {
91:  workflow_mutations: boolean;
93:  internal_notes: boolean;
94:  reassign: boolean;
95:  payments: boolean;
97:  confirm_delivery: boolean;
98:  reopen: boolean;
101:export type AdminOrdersListResponse = {
110:export type AdminOrderDetailResponse = {
118:    status: params.status,
119:    payment_status: params.payment_status,
130:export const apiAdminOrders = {
132:    apiRequest<AdminOrdersListResponse> (`admin/orders${requestQuery(params)}`),
134:    apiRequest<AdminOrderDetailResponse> (`admin/orders/${orderId}`),
--- Mobile Admin Orders Detail capability/action signals ---
66:  return `${new Intl.NumberFormat('sr-RS', { maximumFractionDigits: 2 }).format(value)} RSD`;
116:  const delivery = asRecord(data.delivery);
120:  const actions = asRecord(data.actions);
122:  const payments = asRecords(data.payments);
125:  const internalNotes = asRecords(data.internal_notes);
131:  const status = text(order, 'status_label', text(order, 'status'));
145:          <Text style={styles.status}>{status}</Text>
157:        <DetailRow label="Status" value={status} styles={styles} />
158:        <DetailRow label="Placanje" value={text(order, 'payment_state_label', text(order, 'payment_state', text(order, 'payment_status')))} styles={styles} />
159:        <DetailRow label="Nacin placanja" value={text(order, 'payment_method')} styles={styles} />
161:        <DetailRow label="Tracking" value={text(order, 'tracking_number')} styles={styles} />
163:        {boolValue(order, 'is_completed') ? <DetailRow label="Zavrsena" value="Da" styles={styles} /> : null}
173:          <DetailRow label="Napomena kupca" value={text(order, 'customer_note')} styles={styles} />
196:          <DetailRow label="Status" value={text(shipment, 'status')} styles={styles} />
199:          <DetailRow label="Tracking" value={text(shipment, 'tracking_number', text(shipment, 'reference'))} styles={styles} />
201:          <DetailRow label="Napomena" value={text(shipment, 'note')} styles={styles} />
205:      {delivery ? (
208:          <DetailRow label="Nacin" value={text(delivery, 'delivery_method_label', text(delivery, 'delivery_method'))} styles={styles} />
209:          <DetailRow label="Primalac" value={text(delivery, 'recipient_name')} styles={styles} />
210:          <DetailRow label="Telefon" value={text(delivery, 'recipient_phone')} styles={styles} />
211:          <DetailRow label="Referenca" value={text(delivery, 'reference')} styles={styles} />
212:          <DetailRow label="Isporuceno" value={formatDateTime(text(delivery, 'delivered_at'))} styles={styles} />
213:          <DetailRow label="Dokaz" value={boolValue(delivery, 'has_proof') ? text(delivery, 'proof_original_name', 'Postoji') : 'Nema'} styles={styles} />
217:      {payments.length > 0 ? (
219:          <Text style={styles.sectionTitle}>Uplate ({payments.length})</Text>
220:          {payments.map((payment, index) => (
221:            <View key={`${text(payment, 'id', String(index))}-${index}`} style={styles.listRow}>
223:                <Text style={styles.itemTitle}>{text(payment, 'number', `Uplata ${index + 1}`)}</Text>
224:                <Text style={styles.muted}>{text(payment, 'entry_type')} · {text(payment, 'status')}</Text>
225:                <Text style={styles.muted}>{formatDateTime(text(payment, 'paid_at'))}</Text>
227:              <Text style={styles.itemValue}>{text(payment, 'amount', moneyRsd(numberValue(payment, 'amount_rsd')))}</Text>
240:                <Text style={styles.muted}>{text(document, 'type')} · {text(document, 'status')}</Text>
251:          <DetailRow label="Status" value={text(receivable, 'status')} styles={styles} />
252:          <DetailRow label="Sledeca akcija" value={formatDateTime(text(receivable, 'next_action_at'))} styles={styles} />
253:          <DetailRow label="Obecano placanje" value={formatDateTime(text(receivable, 'promised_payment_at'))} styles={styles} />
260:          <DetailRow label="Status" value={text(commission, 'status')} styles={styles} />
269:          {internalNotes.map((note, index) => (
270:            <View key={`${text(note, 'id', String(index))}-${index}`} style={styles.note}>
271:              <Text style={styles.body}>{text(note, 'note')}</Text>
272:              <Text style={styles.muted}>{text(note, 'user_name', text(note, 'actor'))} · {formatDateTime(text(note, 'created_at'))}</Text>
299:        <Text style={styles.sectionTitle}>Server-driven capabilities</Text>
300:        <DetailRow label="Read" value={String(response.capabilities.read)} styles={styles} />
301:        <DetailRow label="Workflow mutations" value={String(response.capabilities.workflow_mutations)} styles={styles} />
302:        <DetailRow label="Interne napomene dozvola" value={String(response.capabilities.internal_notes)} styles={styles} />
303:        <DetailRow label="Reassignment dozvola" value={String(response.capabilities.reassign)} styles={styles} />
304:        <DetailRow label="Uplate dozvola" value={String(response.capabilities.payments)} styles={styles} />
305:        <DetailRow label="Dokumenti dozvola" value={String(response.capabilities.documents)} styles={styles} />
306:        <DetailRow label="Potvrda isporuke dozvola" value={String(response.capabilities.confirm_delivery)} styles={styles} />
307:        <DetailRow label="Reopen dozvola" value={String(response.capabilities.reopen)} styles={styles} />
309:          Dozvole i actions iz backend prezentera se prikazuju samo informativno. Ovaj Mobile batch nema mutation metode niti akcione tastere.
312:        {actions ? <Text style={styles.muted}>Action snapshot dostupan: da, read-only.</Text> : null}
341:    status: { ...typography.label, color: theme.primary },
359:    note: { gap: 4, paddingVertical: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line },
MOBILE_ADMIN_ORDERS_MUTATION_METHOD_SIGNAL_COUNT=0
MOBILE_ADMIN_ORDERS_MUTATION_API=ABSENT_EXPECTED_AFTER_BATCH3

============================================================
7. OPENAPI ADMIN ORDERS SURFACE
============================================================
OPENAPI_ADMIN_ORDERS_PATH_COUNT=0
OPENAPI_ADMIN_ORDERS_MUTATION_OPERATION_COUNT=0
OPENAPI_PARITY_DURING_AUDIT=PASS

============================================================
8. QUALITY BASELINE - READ ONLY
============================================================

> ald1n-mobile@0.7.0 typecheck
> tsc --noEmit

MOBILE_TYPECHECK=PASS
PASS package.json postoji.
PASS app.config.js postoji.
PASS eas.json postoji.
PASS .env.example postoji.
PASS assets/icon.png postoji.
PASS assets/adaptive-icon.png postoji.
PASS assets/splash-icon.png postoji.
PASS src/app/_layout.tsx postoji.
PASS src/app/(auth)/login.tsx postoji.
PASS src/app/(app)/(tabs)/home.tsx postoji.
PASS src/app/(app)/(tabs)/catalog.tsx postoji.
PASS src/app/(app)/(tabs)/orders.tsx postoji.
PASS src/app/(app)/(tabs)/notifications.tsx postoji.
PASS src/app/(app)/(tabs)/account.tsx postoji.
PASS src/app/(app)/product/[slug].tsx postoji.
PASS src/app/(app)/order/[id].tsx postoji.
PASS src/app/(app)/devices.tsx postoji.
PASS src/app/(app)/cart.tsx postoji.
PASS src/app/(app)/checkout.tsx postoji.
PASS src/app/(app)/notification-settings.tsx postoji.
PASS src/app/(app)/after-sales/index.tsx postoji.
PASS src/app/(app)/after-sales/[id].tsx postoji.
PASS src/app/(app)/after-sales/create/[orderId].tsx postoji.
PASS src/app/(app)/warranties/index.tsx postoji.
PASS src/app/(app)/warranties/[id].tsx postoji.
PASS src/app/(app)/commissions/index.tsx postoji.
PASS src/app/(app)/commissions/[id].tsx postoji.
PASS src/app/(app)/assigned-orders/index.tsx postoji.
PASS src/app/(app)/assigned-orders/[id].tsx postoji.
PASS src/features/warranties/warranty-pdf.ts postoji.
PASS src/features/orders/order-post-create-files.ts postoji.
PASS src/features/after-sales/attachment-picker.ts postoji.
PASS src/features/after-sales/attachment-download.ts postoji.
PASS src/lib/api/client.ts postoji.
PASS src/lib/api/endpoints.ts postoji.
PASS src/features/auth/auth-provider.tsx postoji.
PASS src/features/auth/google-auth.ts postoji.
PASS src/features/device/device-registrar.tsx postoji.
PASS src/features/cart/cart-provider.tsx postoji.
PASS src/features/notifications/push-service.ts postoji.
PASS src/features/notifications/push-notification-bridge.tsx postoji.
PASS docs/openapi.yaml postoji.
PASS tamagui.config.ts postoji.
PASS src/design/ald1n-tokens.generated.ts postoji.
PASS Generated design token fajlovi su sinhronizovani sa canonical JSON source-om.
PASS Tamagui onBrand koristi canonical onPrimary semantic token.
PASS package.json je validan JSON.
PASS eas.json je validan JSON.
PASS Expo SDK 57 verzija prati zvanični template.
PASS React Native verzija prati Expo SDK 57 template.
PASS Expo Router verzija je zaključana.
PASS Expo development client je uključen.
PASS SecureStore zavisnost postoji.
PASS TanStack Query zavisnost postoji.
PASS Minimalna Node.js verzija odgovara SDK 57 zahtevu.
PASS Aplikaciona package verzija je 0.7.0.
PASS package-lock release verzija je 0.7.0.
PASS expo-notifications prati SDK 57 preporučenu verziju.
PASS Expo Symbols je uključen za native Material/SF ikonice.
PASS Moderni Google Credential Manager bridge je uključen.
PASS Nitro Modules runtime je pinovan.
PASS Tamagui 2 runtime je pinovan.
PASS Tamagui Config v5 paket je pinovan.
PASS Tamagui Reanimated driver je pinovan.
PASS Expo System UI prati SDK 57 preporucenu verziju.
PASS Expo Status Bar prati SDK 57 preporucenu verziju.
PASS Expo FileSystem je direktno zakljucan za after-sales izbor priloga.
PASS Expo Sharing je zakljucan za bezbedno otvaranje privatnih after-sales priloga.
PASS Static colors consumeri su uklonjeni iz aplikacionog source-a.
PASS Legacy colors.* usage ne postoji van RN theme adaptera.
PASS Unsafe as never / as unknown as castovi ne postoje u source-u.
PASS 95 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
PASS 556 lokalnih @/ importa je razrešeno.
PASS Bearer token header je implementiran.
PASS Globalni 401 logout je implementiran.
PASS Request ID je sačuvan u API grešci.
PASS API timeout je implementiran.
PASS Secure auth lifecycle je implementiran.
PASS Neuspešan bootstrap posle logina vraća aplikaciju u bezbedno anonymous stanje.
PASS API klijent koristi auth/token ugovor.
PASS API klijent koristi auth/google ugovor.
PASS API klijent koristi bootstrap ugovor.
PASS API klijent koristi catalog/filters ugovor.
PASS API klijent koristi products ugovor.
PASS API klijent koristi orders/options ugovor.
PASS API klijent koristi Idempotency-Key ugovor.
PASS API klijent koristi orders ugovor.
PASS API klijent koristi notifications ugovor.
PASS API klijent koristi devices ugovor.
PASS API klijent koristi me/notification-preferences ugovor.
PASS API klijent koristi PATCH ugovor.
PASS Order API client exposes Assigned-to-me list/detail contract.
PASS Assigned Orders client reuses the canonical Order contract for list/detail.
PASS Assigned Orders customer/mobile contract adds discovery only and no workflow mutation methods.
PASS Order post-create API types cover summary, payment ledger and proof upload.
PASS Order API client covers post-create summary, proof upload and secure binary path contracts.
PASS Order post-create Mobile types do not expose internal actor IDs or storage paths.
PASS Order customer API client does not expose admin payment or delivery workflow actions.
PASS Order private-file paths are prepared for the existing authenticated apiDownload transport.
PASS Orders ekran otvara Assigned-to-me inbox samo korisniku sa orders.manage dozvolom.
PASS Assigned Orders lista koristi dedicated API, permission gate, detail rutu i server pagination.
PASS Assigned Order detalj koristi dedicated detail API i prikazuje canonical Order customer/assignment podatke.
PASS Assigned Orders UI ostaje read-only i ne izlaže owner post-create ili admin workflow mutacije/interne storage podatke.
PASS Order detalj prikazuje server-driven payment/document/delivery post-create summary.
PASS Order detalj šalje payment proof samo kada server capability to dozvoli i koristi server file limite.
PASS Order payment-proof picker koristi postojeći Expo FileSystem i server MIME/extension/size limite.
PASS Order privatni fajlovi koriste Bearer binary transport i provereni privatni cache.
PASS Order PDF/proof helper validira PDF i otvara privatne fajlove kroz postojeći Expo Sharing flow.
PASS Order private-file helper prihvata samo tipizovane customer API path buildere.
PASS Order detalj ne otvara privatne URL-ove direktno već koristi secure Bearer/cache/share helper.
PASS Post-create UI čuva postojeći customer cancel i After-sales create tok.
PASS Order customer post-create UI/helper ne izlažu admin akcije, actor ID-jeve ili storage putanje.
PASS API klijent sadrži after-sales ugovor.
PASS After-sales lista koristi API, dozvolu i detalj rutu.
PASS After-sales detalj prikazuje slučaj, radnje i javnu komunikaciju.
PASS After-sales detalj podržava slanje javne poruke samo kada je komunikacija otvorena.
PASS After-sales create ekran koristi server options, create endpoint, create dozvolu i izabrane stavke.
PASS After-sales attachment picker koristi Expo FileSystem i server limite bez novog picker paketa.
PASS API klijent podržava autentifikovan binary download uz postojeći Bearer lifecycle.
PASS After-sales privatni prilog se preuzima samo kroz očekivanu API putanju i čuva u provereni privatni cache.
PASS After-sales privatni prilog koristi Expo Sharing tek nakon provere platforme i dostupnosti sistema.
PASS After-sales detalj otvara privatne priloge kroz bezbedan Bearer download umesto direktnog privatnog URL-a.
PASS After-sales work-order tip izlaže javne field-work priloge.
PASS Secure attachment helper dozvoljava samo očekivanu field-work Bearer putanju i odvaja cache namespace.
PASS After-sales detalj prikazuje javnu terensku dokumentaciju i otvara je kroz postojeći secure flow.
PASS After-sales create ekran bira, prikazuje i šalje priloge prema server limitima.
PASS After-sales detail tip izlaže server-driven limite.
PASS After-sales message composer bira, prikazuje i šalje priloge prema server limitima.
PASS Order detalj otvara create-from-order ekran samo korisniku sa after_sales.create dozvolom.
PASS Orders ekran otvara after-sales listu samo korisniku sa view_own dozvolom.
PASS Warranty API tipovi pokrivaju listu, detalj i maintenance timeline.
PASS API klijent sadrži Warranty list/detail ugovor.
PASS Warranty lista koristi API, permission gate, detail rutu i maintenance summary.
PASS Warranty detalj prikazuje customer-safe garantni list, uslove, serijske brojeve, status i maintenance timeline.
PASS Warranty PDF se preuzima Bearer transportom, validira kao PDF i čuva u provereni privatni cache.
PASS Warranty PDF koristi postojeći Expo Sharing tek nakon platform/device provere.
PASS Warranty detalj otvara privatni PDF kroz bezbedan Bearer/cache/share flow bez direktnog URL-a.
PASS Orders ekran otvara Warranty listu samo korisniku sa warranties.view_own dozvolom.
PASS Commission API tipovi pokrivaju customer list/detail, statuse, summary i pagination ugovor.
PASS API klijent sadrži Commission list/filter/detail ugovor.
PASS Commission Mobile contract ne izlaže admin actor/history/payment-batch interne identifikatore.
PASS Commission lista koristi customer permission, q/status/date filtere, server summary, pagination i detail rutu.
PASS Commission detalj prikazuje customer-safe obračun, status, napomenu, isplatu i link ka porudžbini.
PASS Orders ekran otvara Commission listu samo korisniku sa commissions.view_own dozvolom.
PASS Commission customer UI ne izlaže admin/interne workflow identifikatore ili akcije.
PASS Lokalna korpa čuva proizvod, varijantu i količinu.
PASS Korpa se čisti pri odjavi/promeni korisnika.
PASS Checkout čuva stabilan idempotency ključ za retry istog payload-a.
PASS Checkout podržava uslovni izbor računa za bank transfer.
PASS Device heartbeat više ne gasi push registraciju pri svakom startu.
PASS Android kanal se kreira pre Expo push tokena.
PASS Expo push token koristi EAS projectId.
PASS Push token se registruje kao Expo device token.
PASS Push token se ne loguje u klijentu.
PASS Foreground i tap push listeneri su implementirani.
PASS Cold-start notification response se čisti nakon obrade.
PASS Push order deep link vodi na detalj porudžbine.
PASS Notification settings uređuju push i poslovne kategorije.
PASS Notification settings podržavaju per-device push uključivanje i isključivanje.
PASS Account ekran podrzava izmenu profila i lokalno osvezavanje bootstrap korisnika.
PASS Account ekran podrzava promenu lozinke i obaveznu ponovnu prijavu.
PASS Account ekran zahteva najmanje 12 znakova za novu lozinku.
PASS Account ekran proverava potvrdu nove lozinke.
PASS API klijent koristi PATCH /me za profil.
PASS API klijent koristi PUT /me/password za lozinku.
PASS Google Sign-In koristi web client ID iz google-services.json i vraća ID token backendu.
PASS Google login ima saved-account, registration/account-picker i explicit fallback tok.
PASS Google Sign-In dugme prati aktivnu light/dark temu.
PASS Bottom navigation ima Material 3 tonalni aktivni indikator.
PASS Tab badge koristi semantic danger/onDanger foreground par.
PASS UI koristi native Expo Symbols umesto tekstualnih pseudo-ikonica.
PASS Canonical packages/api-contract/openapi.yaml postoji.
PASS Mobile OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS CMS OpenAPI kopija postoji.
PASS CMS OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS OpenAPI documents Assigned-to-me list/detail routes.
PASS Assigned Orders OpenAPI documents permission denial and strict detail not-found behavior.
PASS Assigned Orders OpenAPI contains no workflow mutation operations.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/post-create:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/payments/proof:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/payments/{payment}/proof:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/documents/confirmation.pdf:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/documents/{document}.pdf:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/delivery-proof:.
PASS OpenAPI contains OrderPrivateFile: schema.
PASS OpenAPI contains OrderPaymentLedgerEntry: schema.
PASS OpenAPI contains OrderDocumentSummary: schema.
PASS OpenAPI contains OrderDeliverySummary: schema.
PASS OpenAPI contains OrderBankTransferSnapshot: schema.
PASS OpenAPI contains OrderPostCreateCapabilities: schema.
PASS OpenAPI contains OrderPaymentProofLimits: schema.
PASS OpenAPI contains OrderPostCreate: schema.
PASS Order post-create OpenAPI covers proof upload, binary downloads and private no-store cache policy.
PASS Order post-create OpenAPI does not expose internal actor/storage fields or admin workflow actions.
PASS OpenAPI dokumentuje Commission list/filter/detail, summary i pagination ugovor.
PASS Commission OpenAPI customer ugovor ne izlaže admin/interne identifikatore.
PASS OpenAPI dokumentuje Warranty list/detail i maintenance schema ugovor.
PASS OpenAPI dokumentuje privatni Warranty PDF Bearer download ugovor.
PASS OpenAPI AfterSalesCase detalj izlaže server-driven limite za poruke i priloge.
PASS OpenAPI work-order schema izlaže javne field-work priloge.
PASS OpenAPI field-work attachment ruta dokumentuje Bearer download ugovor.
PASS OpenAPI kopija sadrži /auth/token.
PASS OpenAPI kopija sadrži /auth/google.
PASS OpenAPI kopija sadrži /bootstrap.
PASS OpenAPI kopija sadrži /catalog/filters.
PASS OpenAPI kopija sadrži /products.
PASS OpenAPI kopija sadrži /orders/options.
PASS OpenAPI kopija sadrži Idempotency-Key.
PASS OpenAPI kopija sadrži /orders.
PASS OpenAPI kopija sadrži /notifications.
PASS OpenAPI kopija sadrži /devices.
PASS Deep-link scheme je postavljen.
PASS Android/iOS identifikatori su postavljeni.
PASS Expo Router typed routes su uključene.
PASS Dinamički EAS project ID je podržan.
PASS Expo app verzija je 0.7.0.
PASS Expo display naziv je Ald1n CMS bez Preview suffixa.
PASS Ald1n V2 logo je canonical icon/adaptive/splash/favicon asset.
PASS App runtime version fallback je 0.7.0.
PASS Account version fallback je 0.7.0.
PASS Android config podržava Firebase google-services.json kada postoji.
PASS App config uključuje Google Sign-In plugin kada je Firebase config prisutan.
PASS Expo userInterfaceStyle prati sistemsku light/dark temu.
PASS App theme mode je zakljucan na system.
PASS App theme resolver koristi React Native system color scheme.
PASS RN theme adapter koristi canonical onDanger semantic token.
PASS TamaguiProvider je povezan na root aplikacije.
PASS Root Tamagui, StatusBar i navigation background prate isti resolved scheme.
PASS Tamagui Config v5 i Reanimated driver su aktivni.
PASS Ald1n Light/Dark Tamagui palette su povezane.
PASS Tamagui onDanger koristi canonical onDanger semantic token.
PASS Product detail omogućava kopiranje ručno unetog opisa na Android/iOS.
PASS Mobile ima SDK 57 expo-clipboard zavisnost za kopiranje opisa.
PASS Admin Product Create ekran koristi catalog.manage_products i canonical admin catalog API.
PASS Admin Product Create prikazuje server validation grešku i posle uspeha otvara novi artikal.
PASS SelectSheet primitive postoji bez dodatnog native dependency-ja.
PASS API klijent sadrži Admin Catalog options/create ugovor.
PASS Mobile tipovi pokrivaju Admin Product Create metadata/input/response.
PASS Home prikazuje Dodaj artikal samo korisniku sa catalog.manage_products dozvolom.
PASS OpenAPI dokumentuje Admin Catalog options i product create rute.
PASS Admin Product Create renderuje dinamičke specifikacije, zavisne select opcije i detaljna polja.
PASS Admin Product Create fotografije su permission-gated i šalju se kroz canonical image API.
PASS Product image picker koristi postojeći Expo FileSystem i server-driven limite bez novog native dependency-ja.
PASS API klijent podržava multipart upload slika posle kreiranja artikla.
PASS Mobile tipovi pokrivaju dinamičke specifikacije, image limite i storage contract za sledeći specijalizovani korak.
PASS OpenAPI dokumentuje napredne spec metadata podatke i multipart product-image upload.
PASS Admin Product Create ima specijalizovani multi-disk repeater i skriva izvedeni total iz standardnih polja.
PASS Storage repeater šalje canonical specs/spec_lists/spec_capacities/spec_structured payload bez ručnog derived total-a.
PASS Mobile tipovi izlažu server-driven storage repeater i read-only derived metadata.
PASS OpenAPI dokumentuje server-driven storage repeater metadata i derived total polje.
PASS P2 Admin hub koristi centralni access helper, API i query-key foundation.
PASS P2 Admin access helper centralizuje administratorske dozvole i admin/superadmin role fallback.
PASS P2 Admin API helper koristi canonical /api/v1/admin foundation endpoint.
PASS P2 Admin query-key family je centralizovana.
PASS Home prikazuje centralni Admin entry kroz isti access helper.
PASS OpenAPI dokumentuje P2 Admin foundation endpoint i schema ugovor.
PASS P2 FilterBar ima chips, active count i clear contract.
PASS P2 DateTimeField je dependency-free kontrolisani date/datetime input.
PASS P2 MoneyField centralizuje decimalni unos i currency prikaz.
PASS P2 AsyncLookup je server-query friendly lookup bez duplog cache-a.
PASS P2 DataList je mobile-first virtualizovana lista sa refresh i empty state contractom.
PASS P2 ActionSheet koristi dependency-free Modal i aktuelni RN absoluteFill API.
PASS P2 ConfirmAction reuse-uje ActionSheet i odvaja confirm/cancel tok.
PASS P2 StatusTimeline ima reusable server-driven timeline contract.
PASS P2 postojeći SelectSheet i AppFeedback ostaju očuvani.
PASS P3 Admin Commissions API klijent pokriva list/detail/status/bulk-pay ugovor.
PASS P3 Admin Commissions CSV/PDF koristi relativnu API putanju i postojeći Bearer binary/cache/share flow.
PASS P3 Admin Commissions lista ima permission gate, filtere, bulk-pay i izvoze.
PASS P3 Admin Commissions detalj koristi server-driven prelaze i shared timeline.
PASS P3 Admin hub izlaže Provizije samo commissions.manage korisniku.
PASS P3 Admin Commissions query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan P3 Admin Commissions route surface.
PASS OpenAPI dokumentuje P3 Admin Commissions schema ugovor.
PASS P3 Admin Warranties API klijent pokriva list/detail/update/void/maintenance ugovor.
PASS P3 Admin Warranties lista ima permission gate, filtere, statistiku i detail rutu.
PASS P3 Admin Warranties detalj koristi server-side warranty i maintenance mutacije.
PASS P3 Admin hub izlaže Garancije samo warranties.manage korisniku.
PASS P3 Admin Warranties query keys su centralizovani.
PASS OpenAPI dokumentuje P3 Admin Warranties core route i schema ugovor.
PASS P3 Admin Warranties 2E zaključava rules/backfill i relativni Admin PDF API ugovor.
PASS P3 Admin Warranties 2E zaključava Rules UI i Backfill tok.
PASS P3 Admin Warranties 2E zaključava Rules navigaciju i Admin PDF UI entry.
PASS P3 Admin Warranties 2E zaključava secure relativni Admin PDF Bearer/cache/share flow.
PASS OpenAPI dokumentuje kompletan P3 Admin Warranties Rules/Backfill/Admin PDF ugovor.
PASS P3 Admin Reports 2G zaključava read/schedule Mobile API ugovor i relativne Admin putanje.
PASS P3 Admin Reports 2G zaključava secure CSV/PDF Bearer/cache/share export tok.
PASS P3 Admin Reports 2G zaključava management dashboard, permission gate i schedule manager UI.
PASS P3 Admin Reports 2G zaključava centralizovane Reports query-key ugovore.
PASS OpenAPI dokumentuje kompletan P3 Admin Reports read/export/schedule ugovor od 10 operacija.
PASS P3 Admin System Health 2C zaključava read-only Mobile API ugovor i relativnu admin/system-health putanju.
PASS P3 Admin System Health 2C zaključava centralizovani System Health query key.
PASS P3 Admin System Health 2C zaključava permission-gated read-only UI, refresh, checks, metrics i history tok.
PASS P3 Admin System Health 2C zaključava Admin hub ulaz samo za system.health.
PASS OpenAPI dokumentuje samo read-only P3 Admin System Health GET ugovor bez snapshot/backup/prune mutacija.
PASS P3 Admin Audit 2C zakljucava relativni read-only Mobile API ugovor bez raw user_agent/context_json polja.
PASS P3 Admin Audit 2C zakljucava centralizovane Audit list/detail query key ugovore.
PASS P3 Admin Audit 2C zakljucava security.view list/filter/pagination/refetch read-only UI.
PASS P3 Admin Audit 2C zakljucava permission-gated safe detail UI i server-driven read-only capabilities.
PASS P3 Admin Audit 2C zakljucava Admin hub ulaz samo za security.view.
PASS OpenAPI dokumentuje samo P3 Admin Audit read/filter list i safe detail ugovor bez export/mutation ruta.
PASS Product image upload koristi eksplicitni Expo fetch transport sa postojecim auth/error lifecycle-om.
PASS Product image multipart koristi pravi Expo File umesto legacy uri/name/type pseudo-fajla.
PASS Product image multipart ne postavlja rucno Content-Type boundary.
PASS Product image picker prihvata Android image provider fajl bez ekstenzije kada je MIME dozvoljen, uz zadrzan MIME/extension guard za ostale fajlove.
PASS iOS Google Sign-In koristi canonical GoogleService-Info.plist kroz Expo i Nitro config plugin.
PASS iOS GoogleService-Info.plist sadrži preview bundle, iOS OAuth, reversed scheme i web client ID za autoDetect.
PASS iOS koristi zaseban 1024x1024 opaque RGB app icon bez alpha/tRNS transparentnosti.

Ukupno FAIL: 0
MOBILE_PROJECT_VALIDATOR=PASS
PASS light primary/onPrimary contrast 5.78:1
PASS dark primary/onPrimary contrast 7.88:1
PASS up-to-date apps/mobile/current/src/design/ald1n-tokens.generated.ts
PASS up-to-date packages/web-theme/ald1n-violet.css
DESIGN_TOKEN_CHECK=PASS
PASS  postoji artisan
PASS  postoji composer.json
PASS  postoji composer.lock
PASS  postoji .env.example
PASS  postoji VERSION
PASS  postoji RELEASE-TAG
PASS  postoji UPGRADE-FROM
PASS  postoji docs/UPGRADE-V2.1-BETA1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA4.md
PASS  postoji docs/UPGRADE-V2.1-BETA5.md
PASS  postoji docs/UPGRADE-V2.1-BETA6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.4.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.5.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.8.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.9.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.10.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.11.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.12.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.13.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.15.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.16.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.20.md
PASS  postoji DATABASE-MIGRATION-REQUIRED.txt
PASS  postoji app/Models/Order.php
PASS  postoji app/Models/OrderItem.php
PASS  postoji app/Models/OrderDocument.php
PASS  postoji app/Models/OrderCommission.php
PASS  postoji app/Models/CommissionStatusHistory.php
PASS  postoji app/Models/CommissionPaymentBatch.php
PASS  postoji app/Models/OrderInternalNote.php
PASS  postoji app/Models/OrderAssignment.php
PASS  postoji app/Models/OrderStatusHistory.php
PASS  postoji app/Models/StockMovement.php
PASS  postoji app/Models/IdempotencyKey.php
PASS  postoji app/Models/OrderPayment.php
PASS  postoji app/Models/OrderDelivery.php
PASS  postoji app/Models/AfterSalesCase.php
PASS  postoji app/Models/AfterSalesCaseItem.php
PASS  postoji app/Models/AfterSalesMessage.php
PASS  postoji app/Models/AfterSalesAttachment.php
PASS  postoji app/Models/AfterSalesStatusHistory.php
PASS  postoji app/Models/AfterSalesAction.php
PASS  postoji app/Models/AfterSalesActionItem.php
PASS  postoji app/Models/FieldServiceTeam.php
PASS  postoji app/Models/FieldWorkOrder.php
PASS  postoji app/Models/FieldWorkOrderAttachment.php
PASS  postoji app/Models/ServicePartSupplier.php
PASS  postoji app/Models/ServicePart.php
PASS  postoji app/Models/FieldWorkOrderPart.php
PASS  postoji app/Models/ServicePartMovement.php
PASS  postoji app/Models/ServicePartPurchaseRequest.php
PASS  postoji app/Models/ServicePartPurchaseRequestItem.php
PASS  postoji app/Models/WarrantyRule.php
PASS  postoji app/Models/ProductWarranty.php
PASS  postoji app/Models/WarrantyMaintenanceRecord.php
PASS  postoji app/Models/OrderEmailOutbox.php
PASS  postoji app/Models/StockReceipt.php
PASS  postoji app/Models/StockReceiptItem.php
PASS  postoji app/Models/InventoryCount.php
PASS  postoji app/Models/InventoryCountItem.php
PASS  postoji app/Models/AutomationRun.php
PASS  postoji app/Models/OperationalAlert.php
PASS  postoji app/Models/NotificationPreference.php
PASS  postoji app/Models/BackupRun.php
PASS  postoji app/Models/SystemHealthSnapshot.php
PASS  postoji app/Models/SystemRuntimeState.php
PASS  postoji app/Models/SecurityEvent.php
PASS  postoji app/Services/OrderService.php
PASS  postoji app/Services/OrderWorkflowService.php
PASS  postoji app/Services/InventoryService.php
PASS  postoji app/Services/IdempotencyService.php
PASS  postoji app/Services/OrderPaymentService.php
PASS  postoji app/Services/IpsPaymentPayloadService.php
PASS  postoji app/Services/AdvancedInventoryService.php
PASS  postoji app/Services/LegacyReadOnlyGuard.php
PASS  postoji app/Services/OrderAccessService.php
PASS  postoji app/Services/OrderReportService.php
PASS  postoji app/Services/CommissionReportService.php
PASS  postoji app/Services/CommissionWorkflowService.php
PASS  postoji app/Services/OrderOperationalService.php
PASS  postoji app/Services/OrderTimelineService.php
PASS  postoji app/Services/OperationalNotificationService.php
PASS  postoji app/Notifications/OperationalNotification.php
PASS  postoji app/Services/OperationalAutomationService.php
PASS  postoji app/Services/AutomationReadinessService.php
PASS  postoji app/Services/BackupService.php
PASS  postoji app/Services/SystemHealthService.php
PASS  postoji app/Services/SecurityEventLogger.php
PASS  postoji app/Services/SensitiveDataSanitizer.php
PASS  postoji app/Services/OrderIndexService.php
PASS  postoji app/Services/OrderDetailService.php
PASS  postoji app/Services/OrderDetailPresenter.php
PASS  postoji app/Support/ViewValue.php
PASS  postoji app/Services/OrderDocumentService.php
PASS  postoji app/Services/AfterSalesAccessService.php
PASS  postoji app/Services/AfterSalesCaseService.php
PASS  postoji app/Services/AfterSalesActionService.php
PASS  postoji app/Services/FieldWorkOrderPlanner.php
PASS  postoji app/Services/FieldOperationsService.php
PASS  postoji app/Services/ServicePartsInventoryService.php
PASS  postoji app/Services/WarrantyService.php
PASS  postoji app/Services/OrderEmailOutboxService.php
PASS  postoji app/Services/OrderEmailDispatcher.php
PASS  postoji app/Services/NbsIpsQrService.php
PASS  postoji app/Services/DocumentNumberService.php
PASS  postoji app/Services/Pdf/SimplePdfWriter.php
PASS  postoji app/Services/Pdf/BusinessDocumentPdfService.php
PASS  postoji app/Services/Pdf/WarrantyCertificatePdfService.php
PASS  postoji app/Http/Requests/StoreOrderRequest.php
PASS  postoji app/Http/Requests/AdjustStockRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesMessageRequest.php
PASS  postoji app/Http/Requests/UpdateAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CompleteAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CancelAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/StoreFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/UpdateFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/ScheduleFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CompleteFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CancelFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/StoreServicePartRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartRequest.php
PASS  postoji app/Http/Requests/AdjustServicePartStockRequest.php
PASS  postoji app/Http/Requests/StoreServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/StoreFieldWorkOrderPartRequest.php
PASS  postoji app/Http/Requests/StoreServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/CancelServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/StoreWarrantyRuleRequest.php
PASS  postoji app/Http/Requests/UpdateProductWarrantyRequest.php
PASS  postoji app/Http/Requests/ScheduleWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Requests/CompleteWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Controllers/OrderController.php
PASS  postoji app/Http/Controllers/WarrantyController.php
PASS  postoji app/Http/Controllers/AfterSalesController.php
PASS  postoji app/Http/Controllers/AfterSalesAttachmentController.php
PASS  postoji app/Http/Controllers/FieldWorkOrderAttachmentController.php
PASS  postoji app/Http/Controllers/CommissionController.php
PASS  postoji app/Http/Controllers/NotificationController.php
PASS  postoji app/Http/Controllers/Api/V1/OrderController.php
PASS  postoji app/Http/Controllers/Admin/OrderController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesActionController.php
PASS  postoji app/Http/Controllers/Admin/FieldOperationsController.php
PASS  postoji app/Http/Controllers/Admin/FieldServiceTeamController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartSupplierController.php
PASS  postoji app/Http/Controllers/Admin/FieldWorkOrderPartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php
PASS  postoji app/Http/Controllers/Admin/WarrantyController.php
PASS  postoji app/Http/Controllers/Admin/CommissionController.php
PASS  postoji app/Http/Controllers/Admin/StockAdjustmentController.php
PASS  postoji app/Http/Controllers/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/ReportController.php
PASS  postoji app/Http/Controllers/Admin/DocumentSettingsController.php
PASS  postoji app/Http/Controllers/OrderPaymentController.php
PASS  postoji app/Http/Controllers/OrderDeliveryController.php
PASS  postoji app/Http/Controllers/Admin/PaymentController.php
PASS  postoji app/Http/Controllers/Admin/InventoryController.php
PASS  postoji app/Http/Controllers/Admin/AutomationController.php
PASS  postoji app/Http/Controllers/Admin/SystemHealthController.php
PASS  postoji app/Http/Controllers/Admin/TurnstileSettingsController.php
PASS  postoji app/Http/Controllers/Admin/OrderEmailSettingsController.php
PASS  postoji app/Http/Resources/OrderResource.php
PASS  postoji app/Console/Commands/OrdersDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCreateDoctorCommand.php
PASS  postoji app/Console/Commands/CatalogOwnershipDoctorCommand.php
PASS  postoji app/Console/Commands/DetailPagesDoctorCommand.php
PASS  postoji app/Console/Commands/ReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OperationsDoctorCommand.php
PASS  postoji app/Console/Commands/PaymentsInventoryDoctorCommand.php
PASS  postoji app/Console/Commands/RunOperationalAutomationCommand.php
PASS  postoji app/Console/Commands/AutomationDoctorCommand.php
PASS  postoji app/Console/Commands/CreateBackupCommand.php
PASS  postoji app/Console/Commands/BackupDoctorCommand.php
PASS  postoji app/Console/Commands/SystemHealthCommand.php
PASS  postoji app/Console/Commands/SchedulerHeartbeatCommand.php
PASS  postoji app/Console/Commands/TestDatabaseDoctorCommand.php
PASS  postoji app/Console/Commands/AfterSalesDoctorCommand.php
PASS  postoji app/Console/Commands/FieldOperationsDoctorCommand.php
PASS  postoji app/Console/Commands/ServicePartsDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesBackfillCommand.php
PASS  postoji app/Console/Commands/OrderEmailDispatchCommand.php
PASS  postoji app/Console/Commands/OrderEmailsDoctorCommand.php
PASS  postoji database/migrations/2026_07_22_000006_enable_production_orders_inventory.php
PASS  postoji database/migrations/2026_07_23_000007_repair_production_schema_beta5.php
PASS  postoji database/migrations/2026_07_23_000008_repair_authenticated_runtime_beta6.php
PASS  postoji database/migrations/2026_07_23_000009_create_reports_documents_and_supplier_assignment.php
PASS  postoji database/migrations/2026_07_23_000010_repair_reports_schema_beta1_2.php
PASS  postoji database/migrations/2026_07_23_000011_create_operational_orders_commissions_beta2.php
PASS  postoji database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php
PASS  postoji database/migrations/2026_07_23_000013_create_automation_alerts_beta4.php
PASS  postoji database/migrations/2026_07_23_000014_create_security_backup_health_beta6.php
PASS  postoji database/migrations/2026_07_29_000015_repair_order_documents_and_payments_beta7_5.php
PASS  postoji database/migrations/2026_07_30_000016_add_order_completion_beta7_7.php
PASS  postoji database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php
PASS  postoji database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php
PASS  postoji database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php
PASS  postoji database/migrations/2026_07_30_000020_create_after_sales_actions_beta7_11.php
PASS  postoji database/migrations/2026_07_30_000021_create_field_operations_beta7_12.php
PASS  postoji database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php
PASS  postoji database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php
PASS  postoji database/migrations/2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php
PASS  postoji database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php
PASS  postoji resources/views/orders/index.blade.php
PASS  postoji resources/views/admin/orders/show.blade.php
PASS  postoji resources/views/commissions/index.blade.php
PASS  postoji resources/views/notifications/index.blade.php
PASS  postoji resources/views/admin/commissions/index.blade.php
PASS  postoji resources/views/orders/create.blade.php
PASS  postoji resources/views/orders/show.blade.php
PASS  postoji resources/views/admin/reports/index.blade.php
PASS  postoji resources/views/admin/settings/documents.blade.php
PASS  postoji resources/views/admin/inventory/index.blade.php
PASS  postoji resources/views/admin/orders/partials/payments.blade.php
PASS  postoji resources/views/orders/partials/payments.blade.php
PASS  postoji resources/views/after-sales/index.blade.php
PASS  postoji resources/views/after-sales/create.blade.php
PASS  postoji resources/views/after-sales/show.blade.php
PASS  postoji resources/views/admin/after-sales/index.blade.php
PASS  postoji resources/views/admin/after-sales/show.blade.php
PASS  postoji resources/views/admin/field-operations/index.blade.php
PASS  postoji resources/views/admin/field-operations/show.blade.php
PASS  postoji resources/views/admin/field-operations/teams.blade.php
PASS  postoji resources/views/admin/service-parts/index.blade.php
PASS  postoji resources/views/admin/service-parts/suppliers.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-requests.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-show.blade.php
PASS  postoji resources/views/admin/settings/automation.blade.php
PASS  postoji resources/views/admin/settings/system-health.blade.php
PASS  postoji resources/views/admin/settings/turnstile.blade.php
PASS  postoji resources/views/admin/settings/order-emails.blade.php
PASS  postoji resources/views/emails/order-events.blade.php
PASS  postoji tests/Feature/AdminOrdersImageRotationTest.php
PASS  postoji tests/Feature/OperationalOrdersCommissionsTest.php
PASS  postoji tests/Feature/OperationalAutomationTest.php
PASS  postoji tests/Feature/PaymentsAdvancedInventoryTest.php
PASS  postoji tests/Feature/OrderDeliveryWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesActionExecutionTest.php
PASS  postoji tests/Feature/FieldOperationsWorkflowTest.php
PASS  postoji tests/Feature/ServicePartsWorkflowTest.php
PASS  postoji tests/Feature/InventoryWorkspaceUiTest.php
PASS  postoji tests/Feature/SecurityHealthBackupTest.php
PASS  postoji tests/Feature/MySqlTestDatabaseSafetyTest.php
PASS  postoji tests/Unit/SensitiveDataSanitizerTest.php
PASS  postoji tests/Feature/ProductionOrderInventoryTest.php
PASS  postoji tests/Feature/InventoryAdjustmentTest.php
PASS  postoji tests/Feature/ProductionPermissionsTest.php
PASS  postoji tests/Feature/DashboardLegacyDesignTest.php
PASS  postoji tests/Feature/ReportsDocumentsSupplierTest.php
PASS  postoji tests/Fixtures/pdf-logo.jpg
PASS  postoji tests/Unit/BusinessDocumentPdfServiceTest.php
PASS  postoji tests/Unit/DeliveryNoteMigrationContractTest.php
PASS  postoji tests/Unit/DocumentRevisionMigrationContractTest.php
PASS  postoji tests/Unit/CommissionReportPdfServiceTest.php
PASS  postoji tests/Feature/OrderEmailsIpsWarrantyTest.php
PASS  postoji tests/Unit/OrderEmailIpsMigrationContractTest.php
PASS  postoji tests/Unit/ReceivablesPermissionMigrationContractTest.php
PASS  postoji tests/Unit/LegacyReadOnlyGuardTest.php
PASS  postoji tests/Unit/OrderDetailPresenterTest.php
PASS  postoji tests/Unit/ViewValueTest.php
PASS  postoji tests/Feature/CatalogDetailPageTest.php
PASS  postoji tests/Feature/LoginDashboardFallbackTest.php
PASS  postoji resources/views/components/icon.blade.php
PASS  postoji app/Http/Middleware/EnsureRuntimeDirectories.php
PASS  postoji app/Http/Middleware/AttachRequestId.php
PASS  postoji app/Http/Middleware/SecurityHeaders.php
PASS  postoji app/Console/Commands/AuthDoctorCommand.php
PASS  postoji .env.testing.mysql.example
PASS  postoji phpunit.mysql.xml
PASS  postoji bin/php-lint.php
PASS  postoji bin/autoload-check.php
PASS  postoji bin/pdf-smoke.php
PASS  postoji bin/delivery-note-smoke.php
PASS  postoji bin/warranty-pdf-smoke.php
PASS  postoji bin/ips-qr-pdf-smoke.php
PASS  postoji storage/framework/cache/data/.gitignore
PASS  postoji storage/framework/sessions/.gitignore
PASS  postoji storage/framework/views/.gitignore
PASS  postoji storage/logs/.gitignore
PASS  postoji storage/app/backups/.gitignore
PASS  postoji config/backup.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.md
PASS  postoji docs/RELEASE-CHECK.md
PASS  postoji app/Console/Commands/ReleaseCheckCommand.php
PASS  postoji config/release.php
PASS  postoji bin/release-check-smoke.php
PASS  postoji tests/Unit/ReleaseCheckContractTest.php
PASS  postoji tests/Feature/ReleaseCheckCommandTest.php
PASS  postoji storage/app/release-check/.gitignore
PASS  postoji docs/UPGRADE-V2.1-BETA7.22.1.md
PASS  postoji bin/theme-css-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.21.md
PASS  postoji database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php
PASS  postoji app/Models/ReportSchedule.php
PASS  postoji app/Models/ReportDelivery.php
PASS  postoji app/Services/ManagementReportService.php
PASS  postoji app/Services/ReportScheduleService.php
PASS  postoji app/Services/Pdf/ManagementReportPdfService.php
PASS  postoji app/Http/Controllers/Admin/ManagementReportController.php
PASS  postoji app/Http/Controllers/Admin/ReportScheduleController.php
PASS  postoji app/Console/Commands/ManagementReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCostSnapshotsCommand.php
PASS  postoji app/Services/OrderItemCostSnapshotService.php
PASS  postoji app/Console/Commands/ManagementReportsDispatchCommand.php
PASS  postoji resources/views/admin/reports/management.blade.php
PASS  postoji resources/views/emails/management-report.blade.php
PASS  postoji tests/Feature/ManagementReportsProfitabilityTest.php
PASS  postoji tests/Unit/OrderCostSnapshotRepairContractTest.php
PASS  postoji bin/management-report-smoke.php
PASS  postoji bin/order-cost-snapshot-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php
PASS  postoji app/Services/ProductTemplateService.php
PASS  postoji app/Services/ProductCompletenessService.php
PASS  postoji app/Services/ProductBulkService.php
PASS  postoji app/Http/Controllers/Admin/ProductBulkController.php
PASS  postoji app/Console/Commands/SmartProductsDoctorCommand.php
PASS  postoji resources/views/admin/products/clone.blade.php
PASS  postoji resources/views/admin/products/bulk.blade.php
PASS  postoji tests/Unit/SmartProductManagementMigrationContractTest.php
PASS  postoji tests/Unit/SmartProductManagementUiContractTest.php
PASS  postoji tests/Feature/SmartProductManagementTest.php
PASS  postoji bin/smart-product-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.1.md
PASS  postoji docs/UPGRADE-V2.1-RC1.md
PASS  postoji docs/RC-OPERATIONS.md
PASS  postoji docs/UPGRADE-V2.1-STABLE.md
PASS  postoji docs/UPGRADE-V2.1.1.md
PASS  postoji docs/UPGRADE-V2.1.2.md
PASS  postoji docs/UPGRADE-V2.1.3.md
PASS  postoji docs/UPGRADE-V2.1.3.1.md
PASS  postoji docs/UPGRADE-V2.1.3.2.md
PASS  postoji docs/UPGRADE-V2.1.3.3.md
PASS  postoji docs/STABLE-OPERATIONS.md
PASS  postoji docs/BACKUP-RESTORE-DRILL.md
PASS  postoji bin/rc-hardening-smoke.php
PASS  postoji bin/stable-hardening-smoke.php
PASS  postoji bin/stable-maintenance-smoke.php
PASS  postoji bin/product-media-ux-smoke.php
PASS  postoji bin/product-announcement-smoke.php
PASS  postoji bin/catalog-settings-product-data-smoke.php
PASS  postoji bin/catalog-settings-integrity-hotfix-smoke.php
PASS  postoji bin/product-save-regex-hotfix-smoke.php
PASS  postoji bin/storage-capacity-total-smoke.php
PASS  postoji tests/Unit/ProductSaveRegexHotfixContractTest.php
PASS  postoji tests/Unit/StorageCapacityTotalContractTest.php
PASS  postoji tests/Unit/CatalogSettingsProductDataContractTest.php
PASS  postoji tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php
PASS  postoji app/Console/Commands/CatalogSettingsDoctorCommand.php
PASS  postoji app/Services/ProductTypeCategoryService.php
PASS  postoji app/Services/SpecificationFieldLifecycleService.php
PASS  postoji app/Services/StorageSpecificationService.php
PASS  postoji database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php
PASS  postoji database/migrations/2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php
PASS  postoji database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php
PASS  postoji public/assets/js/dictionary-sort-manager.js
PASS  postoji resources/views/admin/dictionary/product-type.blade.php
PASS  postoji tests/Unit/ProductMediaUxContractTest.php
PASS  postoji tests/Unit/ProductAnnouncementContractTest.php
PASS  postoji app/Services/ProductAnnouncementService.php
PASS  postoji tests/Unit/ReleaseCandidateHardeningContractTest.php
PASS  postoji tests/Unit/StableReleaseContractTest.php
PASS  postoji tests/Unit/StableMaintenanceContractTest.php
PASS  postoji app/Console/Commands/SecurityHardeningDoctorCommand.php
PASS  postoji app/Console/Commands/MigrationsDoctorCommand.php
PASS  postoji app/Console/Commands/AccessControlDoctorCommand.php
PASS  postoji app/Console/Commands/ReleaseIntegrityCommand.php
PASS  postoji app/Console/Commands/BackupVerifyCommand.php
PASS  postoji app/Console/Commands/ProductMediaDoctorCommand.php
PASS  postoji app/Http/Controllers/ProductMediaDownloadController.php
PASS  postoji public/assets/js/product-media-manager.js
PASS  postoji resources/views/admin/products/partials/image-card.blade.php
PASS  postoji resources/views/admin/products/partials/image-upload.blade.php
PASS  postoji bin/catalog-detail-smoke.php
PASS  postoji bin/detail-pages-doctor-smoke.php
PASS  postoji tests/Unit/CatalogDetailBladeContractTest.php
PASS  postoji tests/Unit/SystemHealthRemediationContractTest.php
PASS  postoji tests/Unit/DetailPagesDoctorContractTest.php
PASS  postoji database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php
PASS  postoji app/Models/SpecificationOption.php
PASS  postoji app/Services/SpecificationDependencyService.php
PASS  postoji app/Services/CatalogSpecificationFilterService.php
PASS  postoji app/Console/Commands/CatalogCorrelationsDoctorCommand.php
PASS  postoji resources/views/partials/correlated-specification-filters.blade.php
PASS  postoji resources/views/partials/correlated-specification-filter-script.blade.php
PASS  postoji tests/Unit/CorrelatedSpecificationsMigrationContractTest.php
PASS  postoji tests/Unit/CorrelatedSpecificationUiContractTest.php
PASS  postoji docs/UPGRADE-V2.1.4.md
PASS  postoji bin/cms-v2.1.4-smoke.php
PASS  postoji tests/Unit/CmsV214ContractTest.php
PASS  postoji app/Console/Commands/CmsV214DoctorCommand.php
PASS  postoji app/Services/ProductDeletionService.php
PASS  postoji database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php
PASS  postoji docs/UPGRADE-V2.1.4.1.md
PASS  postoji bin/product-type-page-render-hotfix-smoke.php
PASS  postoji tests/Unit/ProductTypePageRenderHotfixContractTest.php
PASS  postoji docs/UPGRADE-V2.1.5.md
PASS  postoji bin/cms-v2.1.5-smoke.php
PASS  postoji tests/Unit/CmsV215ContractTest.php
PASS  postoji app/Console/Commands/CmsV215DoctorCommand.php
PASS  postoji public/assets/js/ux-runtime.js
PASS  postoji resources/views/errors/minimal.blade.php
PASS  postoji resources/views/errors/403.blade.php
PASS  postoji resources/views/errors/404.blade.php
PASS  postoji resources/views/errors/419.blade.php
PASS  postoji resources/views/errors/429.blade.php
PASS  postoji resources/views/errors/500.blade.php
PASS  postoji resources/views/errors/503.blade.php
PASS  postoji database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php
PASS  postoji docs/UPGRADE-V2.2.0.md
PASS  postoji docs/openapi.yaml
PASS  postoji bin/cms-v2.2.0-smoke.php
PASS  postoji tests/Feature/MobileApiFoundationTest.php
PASS  postoji tests/Unit/MobileApiFoundationContractTest.php
PASS  postoji app/Console/Commands/CmsV220DoctorCommand.php
PASS  postoji app/Http/Controllers/Api/V1/BootstrapController.php
PASS  postoji app/Http/Controllers/Api/V1/MobileDeviceController.php
PASS  postoji app/Models/MobileDevice.php
PASS  postoji database/migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php
PASS  verzija je 2.2.0 Mobile API Foundation
PASS  release tag je v2.2.0
PASS  upgrade osnova je v2.1.6
PASS  composer.json validan
PASS  PHP minimum 8.4
PASS  Laravel 13
PASS  Composer lint/autoload/test/release skripte postoje
PASS  runtime verzija je 2.2.0
PASS  migracija sadrži idempotency_keys
PASS  migracija sadrži source_system
PASS  migracija sadrži inventory_state
PASS  migracija sadrži inventory_returned_at
PASS  migracija sadrži event_key
PASS  migracija sadrži orders_user_idempotency_unique
PASS  porudžbina zaključava proizvode
PASS  porudžbina umanjuje lager u transakciji
PASS  povrat lagera ima jedinstveni event key
PASS  idempotency koristi unique zapis i row lock
PASS  legacy porudžbine su blokirane
PASS  legacy SQL guard je registrovan pre izvršavanja
PASS  legacy MySQL sesija je READ ONLY
PASS  Redis je uklonjen iz database konfiguracije
PASS  Redis je uklonjen iz cache konfiguracije
PASS  Redis je uklonjen iz queue konfiguracije
PASS  login rate limiter koristi file store
PASS  dozvola orders.create
PASS  dozvola orders.view_own
PASS  dozvola orders.cancel_own
PASS  dozvola orders.manage
PASS  dozvola stock.view
PASS  dozvola stock.adjust
PASS  dozvola reports.view
PASS  dozvola reports.export
PASS  dozvola invoices.manage
PASS  dozvola invoices.view_own
PASS  web ruta orders.store
PASS  web ruta orders.cancel
PASS  web ruta admin.orders.status
PASS  web ruta admin.orders.payment
PASS  web ruta admin.orders.tracking
PASS  web ruta admin.stock.adjust
PASS  API porudžbine postoje
PASS  porudžbina ima dodeljenog SuperAdmin/Admin dobavljača
PASS  admin scope vidi samo njemu dodeljene porudžbine
PASS  izveštaji podržavaju filtere i CSV/PDF
PASS  poslovni dokumenti koriste nepromenljivi snapshot
PASS  PDF renderer je lokalni i bez Redis/eksternog servisa
PASS  brojevi dokumenata su transakcioni i jedinstveni
PASS  web rute imaju reports CSV/PDF i dokumente
PASS  reports stranica ima schema fallback umesto 500
PASS  reports render je unutar zaštićenog controller toka
PASS  reports view ima render marker i bezbedne URL-ove
PASS  reports export vraća kontrolisani 503
PASS  reports doctor izvršava repair i stvarne SQL upite
PASS  reports doctor renderuje controller Blade i layout
PASS  reports logging je best-effort
PASS  beta1.2 repair migracija je nedestruktivna
PASS  hamburger dugme postoji
PASS  mobilni meni ima kontrolni JavaScript
PASS  mobilni meni nema horizontalni scroll
PASS  direktne mobilne stavke koriste zajednički levi wrapper
PASS  Početna Provizije i Izveštaji su poravnati ulevo
PASS  CSS ima pouzdan cache busting
PASS  legacy desktop header ima dva reda
PASS  legacy mobilni header zadržava kurs temu nalog i hamburger
PASS  dashboard ima moderni hero KPI prioritete i module
PASS  dashboard CSS ima 4 desktop i 2 mobilne kolone
PASS  admin gridovi su poravnati na vrh
PASS  forme koriste sadržajnu visinu
PASS  deployment check ima bezbedan repair režim
PASS  deployment check razlikuje runtime zaštitu i grant warning
PASS  deployment check proverava i operativne kolone
PASS  dashboard koristi DB fallback umesto 500
PASS  login telemetry je best-effort
PASS  login hvata session i remember-token probleme
PASS  authenticated layout nema direktan SettingsService upit
PASS  authenticated layout koristi bezbedne user helper metode
PASS  dashboard logging ne može da obori fallback
PASS  runtime middleware prethodi session/cache middleware-u
PASS  deployment repair kreira runtime direktorijume i kompajlira Blade
PASS  auth doctor može da renderuje kompletan dashboard
PASS  Turnstile hvata sve transportne/JSON greške
PASS  Turnstile podešavanja imaju DB prioritet i env fallback
PASS  Turnstile secret se čuva šifrovano i ne izlaže kroz all
PASS  Turnstile admin ekran i ruta postoje
PASS  beta6 repair migracija popravlja core login šemu
PASS  static check razdvaja runtime i ZIP režim
PASS  operativna migracija sadrži order_internal_notes
PASS  operativna migracija sadrži order_assignments
PASS  operativna migracija sadrži commission_payment_batches
PASS  operativna migracija sadrži notifications
PASS  operativna migracija sadrži payment_batch_id
PASS  operativna migracija sadrži status_updated_at
PASS  operativna migracija sadrži last_internal_note_at
PASS  beta2 dozvola commissions.view_own
PASS  beta2 dozvola orders.reassign
PASS  beta2 dozvola orders.internal_notes
PASS  beta2 dozvola notifications.view
PASS  provizije imaju odobravanje isplatu storniranje i istoriju
PASS  masovna isplata koristi transakciju row lock i batch
PASS  korisnik vidi samo svoje provizije i minimum 20 EUR
PASS  interne napomene nisu u javnom timeline-u
PASS  ponovna dodela je ograničena na SuperAdministratora
PASS  preuzimanje i rokovi porudžbine imaju audit i obaveštenja
PASS  database notifikacije su neblokirajuće i mail je opcioni
PASS  operativni doctor proverava šemu SQL i render
PASS  admin provizije imaju filtere CSV PDF i masovnu isplatu
PASS  commission tabela nema unutrašnji vertikalni scroll pri obradi
PASS  obrada provizije koristi veliki viewport modal
PASS  commission modal ima naslov i eksplicitno zatvaranje
PASS  otvaranje commission modala zatvara prethodni
PASS  porudžbina ima timeline interne napomene preuzimanje rokove i reassignment UI
PASS  inbox obaveštenja podržava read i read-all
PASS  operativni feature testovi postoje
PASS  commission modal regresioni feature test postoji
PASS  beta3.1 migracija nema globalni use Throwable
PASS  beta3.1 migracija koristi potpuno kvalifikovani Throwable
PASS  PHP lint odbija warning deprecated i notice izlaz
PASS  beta3 migracija sadrži order_payments
PASS  beta3 migracija sadrži stock_receipts
PASS  beta3 migracija sadrži stock_receipt_items
PASS  beta3 migracija sadrži inventory_counts
PASS  beta3 migracija sadrži inventory_count_items
PASS  beta3 migracija sadrži payment_state
PASS  beta3 migracija sadrži paid_total_rsd
PASS  beta3 migracija sadrži payment_due_at
PASS  beta3 dozvola payments.manage
PASS  beta3 dozvola payments.upload_proof
PASS  beta3 dozvola payments.view_own
PASS  beta3 dozvola inventory.receive
PASS  beta3 dozvola inventory.count
PASS  beta3 dozvola inventory.export
PASS  uplate koriste transakciju row lock audit i saldo
PASS  potvrde uplate su privatne i autorizovane
PASS  IPS podaci koriste snapshot porudžbine
PASS  predračun i račun postavljaju dospeće porudžbine
PASS  ulaz robe i popis koriste idempotency transakciju i row lock
PASS  napredni lager ima readiness fallback umesto 500
PASS  reports beta3 sažeci i izvozi su zaštićeni
PASS  beta3 doctor proverava repair SQL i render
PASS  beta3 feature testovi pokrivaju uplate ulaz i popis
PASS  beta7.5 repair migracija obnavlja PDF i payment šemu
PASS  beta7.5 repair migracija je nedestruktivna
PASS  beta7.5 potvrda koristi site name fallback
PASS  beta7.5 ručno evidentiranje uplate ima regresioni test
PASS  beta7.5 doctor proverava dokument i payment tabele
PASS  beta7.6 PDF dozvoljava lokalno uvezene porudžbine
PASS  beta7.6 uplate dozvoljavaju lokalno uvezene porudžbine
PASS  beta7.6 legacy lager zaštita ostaje aktivna
PASS  beta7.7 migracija dodaje terminalno stanje porudžbine
PASS  beta7.7 PDF podešavanja imaju upload pregled i uklanjanje logotipa
PASS  beta7.7 PDF logo se ugrađuje kao lokalni JPEG
PASS  beta7.7 PDF ne prikazuje subagent email kupca
PASS  beta7.7 kompletiranje COD porudžbine evidentira preostali saldo
PASS  beta7.7 kompletirana porudžbina zaključava dalje izmene
PASS  beta7.7 kompletiranje je jasno dostupno u detalju i listi
PASS  beta7.8 migracija dodaje evidenciju isporuke i reopening stanje
PASS  beta7.8 kompletiranje čuva dokaz isporuke privatno
PASS  beta7.8 otpremnica koristi OTP broj i delivery snapshot
PASS  beta7.8 ponovno otvaranje je superadmin-only i auditovano
PASS  beta7.8 detalj prikazuje strukturiranu evidenciju isporuke
PASS  beta7.8 doctor proverava novu šemu i dozvole
PASS  beta7.8 feature testovi pokrivaju dokaz otpremnicu i reopening
PASS  beta7.8 UI ima delivery workflow responsive stilove
PASS  beta7.9 migracija uklanja legacy ENUM blokadu za delivery_note
PASS  beta7.9 servis radi schema preflight pre izdavanja otpremnice
PASS  beta7.9 pomoćni notification kvar ne obara izdat dokument, a IPS važi samo za finansijske dokumente
PASS  beta7.9 kontroleri vraćaju incident poruku umesto Error 500
PASS  beta7.9 doctor proverava stvarni MySQL tip dokumenta
PASS  beta7.9 ima migration contract i delivery note PDF smoke test
PASS  detail koristi eksplicitan slug upit
PASS  slug upit primenjuje objedinjeni visibility scope
PASS  API detail koristi isti slug upit
PASS  neispravna slika ne obara detail
PASS  detail filtrira slike bez validnog URL-a
PASS  katalog generiše eksplicitan slug link
PASS  detail ima interaktivnu thumbnail galeriju
PASS  detail ima fullscreen lightbox i zoom kontrole
PASS  gallery podržava tastaturu swipe i preload
PASS  gallery radi i sa jednom slikom
PASS  gallery CSS ima fullscreen viewport i responsive mobile
PASS  gallery feature testovi postoje
PASS  beta4 migracija sadrži automation_runs
PASS  beta4 migracija sadrži operational_alerts
PASS  beta4 migracija sadrži notification_preferences
PASS  beta4 nema Redis i koristi scheduler/file lock
PASS  beta4 detektuje nepreuzete porudžbine dospele obaveze i nizak lager
PASS  beta4 upozorenja su deduplikovana i razrešavaju se
PASS  notification preferences upravljaju kanalima i kategorijama
PASS  automation settings UI i ručno pokretanje postoje
PASS  automation doctor proverava repair scheduler i run
PASS  beta4 dozvola automation.manage postoji
PASS  beta4 feature testovi pokrivaju deduplikaciju i preference
PASS  beta5 inventory koristi jednu aktivnu operaciju
PASS  beta5 inventory čuva filter i limit nakon knjiženja
PASS  beta5 inventory nema unutrašnji vertikalni scrollbar
PASS  beta5 inventory responsive tabela koristi data-label kartice
PASS  beta5 feature test pokriva inventory workspace
PASS  beta6 migracija sadrži backup_runs
PASS  beta6 migracija sadrži system_health_snapshots
PASS  beta6 migracija sadrži system_runtime_states
PASS  beta6 migracija sadrži security_events
PASS  beta6 migracija sadrži system.health
PASS  beta6 migracija sadrži backups.manage
PASS  beta6 migracija sadrži audit.export
PASS  beta6 migracija sadrži security.view
PASS  beta6 backup koristi mysqldump bez lozinke u argumentima
PASS  beta6 backup odbija public putanju i pravi SHA-256 manifest
PASS  beta6 system health proverava scheduler backup migracije i legacy
PASS  beta6 security header-i i request ID postoje
PASS  beta6 audit koristi rekurzivnu sanitizaciju i request ID
PASS  beta6 rate limiter-i pokrivaju upload export admin i backup
PASS  beta6 test DB doctor ima višestruku zaštitu
PASS  beta6 system health UI i backup akcije postoje
PASS  beta6 scheduler ima heartbeat backup i health snapshot
PASS  beta6 feature i unit testovi postoje
PASS  beta7.1 orders ima readiness SQL i render zaštitu
PASS  beta7.1 orders doctor proverava isti browser render
PASS  beta7.1 orders recovery ne završava generičkim 500
PASS  beta7.1 edit artikla ima rotaciju ulevo i udesno
PASS  beta7.1 legacy rotacija koristi copy-on-write
PASS  beta7.1 rotacija koristi privremeni fajl i kontrolisani Imagick/GD fallback
PASS  beta7.1 feature testovi postoje
PASS  beta7.2 order detail koristi opcioni schema-aware loader
PASS  beta7.2 admin i user detail imaju protected render
PASS  beta7.2 admin i user detail imaju readiness markere
PASS  beta7.2 timeline i IPS ne mogu oboriti detalj
PASS  beta7.2 orders doctor renderuje oba detalja
PASS  beta7.2 detail-pages doctor proverava ključne detail stranice
PASS  beta7.2 feature testovi pokrivaju detail i opcione tabele
PASS  beta7.3 detail koristi scalar presenter umesto Eloquent objekata u Blade-u
PASS  beta7.3 presenter bezbedno obrađuje raw i zero datume
PASS  beta7.3 presenter bezbedno generiše named rute
PASS  beta7.3 detail view nema direktne auth, relation ili datetime pozive
PASS  beta7.3 admin i user detail imaju ne-503 read-only fallback
PASS  beta7.3 orders doctor prikazuje tačan exception uzrok za oba detaila
PASS  beta7.3 orders doctor nastavlja admin i user audit
PASS  beta7.3 payment i inventory Gates su definisani
PASS  beta7.3 presenter i ViewValue regresioni testovi postoje
PASS  beta7.10 migracija sadrži after_sales_cases
PASS  beta7.10 migracija sadrži after_sales_case_items
PASS  beta7.10 migracija sadrži after_sales_messages
PASS  beta7.10 migracija sadrži after_sales_attachments
PASS  beta7.10 migracija sadrži after_sales_status_history
PASS  beta7.10 ima tri postprodajne dozvole
PASS  beta7.10 pristup poštuje vlasnika dodeljenog admina i superadmin scope
PASS  beta7.10 slučaj zahteva isporučenu ili kompletiranu porudžbinu
PASS  beta7.10 čuva pogođene stavke snapshot i SLA rok
PASS  beta7.10 privatni prilozi proveravaju MIME veličinu i autorizaciju
PASS  beta7.10 javne i interne poruke su odvojene
PASS  beta7.10 statusni tok zahteva obrazloženje konačne odluke
PASS  beta7.10 automatizacija upozorava na probijene rokove slučaja
PASS  beta7.10 UI ima korisnički i administratorski postprodajni tok
PASS  beta7.10 doctor proverava šemu dozvole i SQL
PASS  beta7.10 feature test pokriva privatni prilog i obradu
PASS  beta7.10 privatni download zabranjuje browser cache
PASS  beta7.10 konkurentno zatvaranje ne propušta novu poruku
PASS  beta7.10 reopening zahteva razlog i čuva vreme prethodnog rešenja
PASS  beta7.10 nedodeljeni slučajevi obaveštavaju superadministratore
PASS  beta7.10 dashboard prikazuje aktivne probijene i waiting slučajeve
PASS  beta7.11 migracija sadrži after_sales_actions
PASS  beta7.11 migracija sadrži after_sales_action_items
PASS  beta7.11 migracija sadrži after_sales_action_id
PASS  beta7.11 migracija sadrži after_sales.execute
PASS  beta7.11 podržava četiri izvršne radnje
PASS  beta7.11 lager efekti su zaključani i idempotentni
PASS  beta7.11 refundacija je vezana za radnju i ograničena neto uplatom
PASS  beta7.11 slučaj čeka završetak aktivnih radnji
PASS  beta7.11 UI ima planiranje pokretanje izvršenje i otkazivanje
PASS  beta7.11 Gate i permission middleware štite izvršne kontrole
PASS  beta7.11 controller ima sve izvršne endpoint-e
PASS  beta7.11 automatizacija prati rok izvršne radnje
PASS  beta7.11 dashboard prikazuje radnje za izvršenje
PASS  beta7.11 testovi pokrivaju idempotentni lager povrat i refundaciju
PASS  beta7.12 migracija sadrži field_service_teams
PASS  beta7.12 migracija sadrži field_work_orders
PASS  beta7.12 migracija sadrži field_work_order_attachments
PASS  beta7.12 migracija sadrži field_operations.view
PASS  beta7.12 migracija sadrži field_operations.manage
PASS  beta7.12 fizičke radnje automatski dobijaju radni nalog
PASS  beta7.12 sprečava preklapanje termina iste ekipe
PASS  beta7.12 završetak zahteva dolazak na lokaciju
PASS  beta7.12 radni nalog čuva troškove kilometražu i privatne dokaze
PASS  beta7.12 UI ima kalendar ekipe i operativne statuse
PASS  beta7.12 rute i Gate štite terenske operacije
PASS  beta7.12 automatizacija prati neplanirane i probijene radne naloge
PASS  beta7.12 doctor proverava tabele dozvole rute i SQL
PASS  beta7.12 test pokriva auto nalog konflikt i on-site završetak
PASS  beta7.13 migracija sadrži service_part_suppliers
PASS  beta7.13 migracija sadrži service_parts
PASS  beta7.13 migracija sadrži field_work_order_parts
PASS  beta7.13 migracija sadrži service_part_movements
PASS  beta7.13 migracija sadrži service_part_purchase_requests
PASS  beta7.13 migracija sadrži service_part_purchase_request_items
PASS  beta7.13 migracija sadrži service_parts.view
PASS  beta7.13 migracija sadrži service_parts.manage
PASS  beta7.13 migracija sadrži service_parts.procurement
PASS  beta7.13 početno stanje ulazi u movement ledger
PASS  beta7.13 rezervacija ne umanjuje fizičko stanje
PASS  beta7.13 završetak skida stvarni utrošak i oslobađa ostatak
PASS  beta7.13 otkazivanje oslobađa sve rezervacije
PASS  beta7.13 kretanja servisnog lagera su idempotentna i ponovo proverena pod lockom
PASS  beta7.13 nacrt nabavke koristi konkurentno bezbedan privremeni broj
PASS  beta7.13 prijem nabavke računa ponderisanu prosečnu cenu
PASS  beta7.13 UI ima servisni lager dobavljače nabavku i utrošak
PASS  beta7.13 Gate i rute štite lager i nabavku
PASS  beta7.13 automatizacija prati nizak lager i kašnjenje nabavke
PASS  beta7.13 doctor proverava tabele dozvole rute i SQL
PASS  beta7.13 testovi pokrivaju ledger rezervaciju utrošak i ponderisanu cenu
PASS  beta7.13 UI ima responsive stilove servisnog lagera
PASS  beta7.14.1 migracija prvo obezbeđuje FK indeks
PASS  beta7.14 migracija uklanja unique order/type ograničenje
PASS  beta7.14 migracija uvodi revizije i vezu sa prethodnim dokumentom
PASS  beta7.14 servis vraća samo aktivan dokument ili izdaje novu reviziju
PASS  beta7.14 storniranje zahteva razlog i čuva audit podatak
PASS  beta7.14 model podržava supersedes relaciju
PASS  beta7.14 UI razlikuje aktivan dokument i novu reviziju
PASS  beta7.14 PDF prikazuje broj revizije
PASS  beta7.14 regresioni test pokriva ponovno izdavanje
PASS  beta7.15 migracija sadrži warranty_rules
PASS  beta7.15 migracija sadrži product_warranties
PASS  beta7.15 migracija sadrži warranty_maintenance_records
PASS  beta7.15 migracija sadrži warranties.view_own
PASS  beta7.15 migracija sadrži warranties.manage
PASS  beta7.15 pravila imaju product category global prioritet
PASS  beta7.15 kompletiranje automatski izdaje garanciju bez obaranja porudžbine
PASS  beta7.15 otkazivanje poništava aktivne garancije
PASS  beta7.15 GAR poslovni broj je registrovan
PASS  beta7.15 garancija čuva snapshot kupca artikla uslova i serijskih brojeva
PASS  beta7.15 preventivno održavanje generiše sledeći termin
PASS  beta7.15 zakazivanje ne menja vreme tokom provere datuma
PASS  beta7.15 backfill bira samo stavke bez garancije
PASS  beta7.15 PDF garantni list prikazuje ključne snapshot podatke
PASS  beta7.15 korisnički i administratorski prikazi postoje
PASS  beta7.15 rute Gates i administratorski scope štite garancije
PASS  beta7.15 automatizacija prati istek i održavanje
PASS  beta7.15 dashboard prikazuje garancije
PASS  beta7.15 doctor i backfill komande postoje
PASS  beta7.15 feature test pokriva automatsko izdavanje i prioritet pravila
PASS  beta7.15 warranty PDF smoke postoji
PASS  beta7.16 migracija uvodi outbox QR snapshot i dane garancije
PASS  beta7.16 migracija ima recovery putanju za delimičan MariaDB DDL
PASS  beta7.16 e-mail outbox ima dedupe intervale i pojedinačne primaoce
PASS  beta7.16 e-mail prima autor odgovorno lice i dodatne adrese
PASS  beta7.16 workflow šalje status tracking plaćanje i dokumente
PASS  beta7.16 dispatcher ima retry stuck recovery i zaštitu storniranog priloga
PASS  beta7.16 scheduler šalje outbox svake minute
PASS  beta7.16 admin podešava intervale događaje i dokumente
PASS  beta7.16 e-mail šablon ima događaje i bezbedan action link
PASS  beta7.16 NBS payload koristi zvanične oznake i RSD zarez
PASS  beta7.16 NBS servis koristi zvanični HTTPS endpoint i čuva privatni PNG snapshot
PASS  beta7.16 stornirani istorijski dokument ostaje pregledljiv bez ponovnog NBS poziva
PASS  beta7.16 finansijski dokument bez validnog NBS QR se ne izdaje
PASS  beta7.16 PDF crta PNG bez GD i prikazuje NBS IPS QR oznaku
PASS  beta7.16 IPS QR smoke potvrđuje sliku oznaku i tačan RSD iznos
PASS  beta7.16 garancija podržava kombinaciju meseci i dana
PASS  beta7.16 admin može kreirati porudžbinu
PASS  beta7.16 doctor proverava outbox SMTP scheduler NBS i garancijske dane
PASS  beta7.16 feature test pokriva admin porudžbinu događaje NBS QR i dane garancije
PASS  beta7.17 migracija uvodi predmete rate i komunikaciju naplate
PASS  beta7.17 migracija je recovery-safe za delimičan DDL
PASS  beta7.17 servis automatski otvara zatvara i usklađuje predmete
PASS  beta7.17 rate se raspoređuju prema stvarno plaćenom iznosu
PASS  beta7.17 automatske opomene koriste faze dedupe i outbox
PASS  beta7.17 admin ima aging pregled plan i evidenciju komunikacije
PASS  beta7.17 podmeni se zatvara klikom van escape i izborom stavke
PASS  beta7.17 checkbox i radio imaju normalnu globalnu veličinu
PASS  beta7.17 doctor proverava šemu dozvolu i scheduler
PASS  beta7.17.1 permission seed je schema-aware
PASS  beta7.17.1 seeder ne zahteva permissions.updated_at
PASS  beta7.17.2 hover podmeni ima grace period i click pin
PASS  beta7.17.2 CSS premošćava razmak do podmenija
PASS  beta7.18 migracija uvodi strukturirane opcije i korelacije
PASS  beta7.18 migracija je recovery-safe za MariaDB
PASS  beta7.18 procesor ima porodicu i tačan model
PASS  beta7.18 brend filtrira samo sopstvene linije
PASS  beta7.18 generičke zavisnosti imaju server validaciju i zaštitu ciklusa
PASS  beta7.18 forma skriva nepovezane opcije i čuva detalj
PASS  beta7.18 kataloški filteri podržavaju select range boolean text i detalj
PASS  beta7.18 oba kataloga koriste korelisane filtere
PASS  beta7.18 doctor proverava procesor linije veze i tipove
PASS  beta7.18.1 forma artikla ne koristi nedostupni index filter servis
PASS  beta7.18.1 jedinstveni katalog dobija podatke za korelisane filtere
PASS  beta7.18.1 šifarnici dobijaju podatke za roditelje i mape zavisnosti
PASS  beta7.19 migracija uvodi šablone kompletnost i poreklo klona
PASS  beta7.19 migracija je recovery-safe i obračunava postojeći katalog
PASS  beta7.19 template servis podržava alias placeholdere
PASS  beta7.19 completeness servis vraća nepotpun aktivan artikal u nacrt
PASS  beta7.19 kloniranje čuva novi SKU i nulti lager
PASS  beta7.19 clone checkboxi eksplicitno šalju nulu
PASS  beta7.19 bulk zahteva pregled i blokira praznu operaciju
PASS  beta7.19 bulk promena brenda čisti neusklađenu liniju
PASS  beta7.19 preview naziva uklanja method spoof
PASS  beta7.19 doctor proverava šemu rute i kompletnost
PASS  beta7.19 feature test pokriva naziv klon i bulk
PASS  beta7.20 migracija uvodi varijante specifikacije slike i snapshot
PASS  beta7.20 migracija je recovery-safe za MariaDB i proširuje istorijske module
PASS  beta7.20 varijanta ima SKU cenu lager status default i garanciju
PASS  beta7.20 default preferira aktivnu varijantu i roditelj sabira aktivan lager
PASS  beta7.20 serverska validacija štiti SKU i korelisane specifikacije
PASS  beta7.20 admin ima CRUD lager slike i default varijantu
PASS  beta7.20 UI filtrira zavisne specifikacije varijante
PASS  beta7.20 porudžbina čuva variant snapshot i vraća isti lager
PASS  beta7.20 postprodaja garancija i stock movement nose variant id
PASS  beta7.20 clone kopira varijante bez lagera i sa novim SKU
PASS  beta7.20 parent inventory korekcija je blokirana
PASS  beta7.20 filter i pretraga vide aktivne varijante
PASS  beta7.20 doctor proverava SKU default snapshot i aggregate
PASS  beta7.20 feature i smoke testovi postoje
PASS  beta7.17 feature test pokriva dedupe rate zatvaranje i UI regresiju
PASS  beta7.21 migracija uvodi nabavne snapshotove i rasporede
PASS  beta7.21 migracija je recovery-safe i permission schema-aware
PASS  beta7.21 marža koristi snapshot i prikazuje pokrivenost troška
PASS  beta7.21 filteri važe za KPI trend i segmente
PASS  beta7.21 dashboard pokriva lager potraživanja postprodaju i tim
PASS  beta7.21 PDF upravljačkog izveštaja postoji
PASS  beta7.21 raspored ima retry dedupe i zasebne primaoce
PASS  beta7.21 ekran je bezbedan pre migracije
PASS  beta7.21 UI ima CSV PDF rasporede i cost coverage
PASS  beta7.22.1 management analytics koristi aktivnu temu bez belog fallback-a
PASS  beta7.22.1 CSS kompatibilni aliasi postoje
PASS  beta7.21 feature test pokriva ekran export i raspored
PASS  beta7.22 portal servis i fallback podaci postoje
PASS  beta7.22 portal objedinjuje porudžbine dokumente uplate garancije i servis
PASS  beta7.22 report grouping je kompatibilan sa ONLY_FULL_GROUP_BY
PASS  beta7.22 dashboard ima trend prioritete brze akcije i operativne module
PASS  beta7.22.1 portal doctor prosleđuje ViewErrorBag
PASS  beta7.22.1 layout bezbedno proverava errors bag
PASS  beta7.23 release-check komanda ima profile i kontrolisane režime
PASS  beta7.23 release registry ima quick standard i full profile
PASS  beta7.23 release plan ne dispatchuje poslovne akcije
PASS  beta7.23 release metadata i atomski JSON report postoje
PASS  beta7.23 release rezultat ima READY i NOT READY ugovor
PASS  beta7.23 smoke i dokumentacija postoje
PASS  beta7.23.1 catalog detail nema problematične inline Blade lance
PASS  beta7.23.1 catalog detail Blade direktive su izbalansirane
PASS  beta7.23.1 variant detail Feature i smoke regresija postoje
PASS  beta7.23.1 health daje čitljive runtime remediation komande
PASS  beta7.23.2 detail doctor rešava controller zavisnosti kroz container
PASS  beta7.23.2 detail doctor nema direktan edit poziv sa jednim argumentom
PASS  beta7.23.2 detail doctor smoke i contract regresija postoje
PASS  beta7.24 migracija uvodi aktivacije sesije komunikaciju i order-link audit
PASS  beta7.24 aktivacioni token je hashiran jednokratan i vremenski ograničen
PASS  beta7.24 session registry koristi hash i podržava revoke
PASS  beta7.24 kupac vidi samo javne poruke a admin interne
PASS  beta7.24 portal rute aktivacija i admin centar postoje
PASS  beta7.24 komunikacija razdvaja public i internal
PASS  beta7.24 smoke i PHPUnit regresije postoje
PASS  beta7.24 maintenance čisti tokene i stare session evidencije
PASS  beta7.24 reinvite ne deaktivira aktivnog kupca i aktivacija nije cache-ovana
PASS  beta7.24.1 management repair obrađuje missing snapshotove
PASS  beta7.24.1 repair ne prepisuje kompletne snapshotove
PASS  beta7.24.1 repair je transakcioni i koristi row lock
PASS  beta7.24.1 kandidati imaju transparentan izvor
PASS  beta7.24.1 ručna finansijska promena zahteva razlog i audit
PASS  beta7.24.1 audit/repair komanda i regresije postoje
PASS  rc1 profil sadrzi final hardening provere
PASS  rc1 security doctor proverava production debug HTTPS session i public fajlove
PASS  rc1 migration doctor proverava pending SQL mode i foreign keys
PASS  rc1 access doctor proverava route permission i superadmin
PASS  rc1 release integrity proverava SHA-256 i path traversal
PASS  rc1 backup verify je read-only i proverava SQL gzip i file hash
PASS  rc1 smoke i contract regresije postoje
PASS  rc1 nema novu migration datoteku
PASS  stable profil je identican potvrdenom rc profilu
PASS  stable smoke i contract regresije postoje
PASS  stable početna je univerzalni dashboard sa integrisanim korisničkim centrom
PASS  stable nema zasebnu Moj portal stranicu ni stavku menija
PASS  stable nema novu migration datoteku
PASS  v2.1.2 obaveštenja o novom artiklu su opt-in i koriste outbox
PASS  v2.1.2 novi artikal se šalje aktivnim registrovanim korisnicima bez duplikata
PASS  v2.1.2 mail podešavanja i šablon podržavaju nove artikle
PASS  v2.1.2 product announcement regresije postoje
PASS  v2.1.2 nema novu migration datoteku
PASS  APP_ENV production
PASS  Redis nije obavezan za database queue
PASS  file session/cache/limiter i database queue
PASS  secret vrednosti su prazne
PASS  import ne upisuje legacy konekciju
INFO  ZIP hygiene provere su preskočene na instaliranoj aplikaciji; za raspakovani sanitized ZIP koristi --package.
PASS  v2.1.3 tipovi proizvoda imaju posebne stranice i Drag & Drop
PASS  v2.1.3 specifikaciona polja mogu trajno da se obrišu
PASS  v2.1.3 tip automatski određuje kategoriju
PASS  v2.1.3 diskovi imaju pojedinačne celobrojne GB kapacitete
PASS  v2.1.3 catalog settings doctor postoji
PASS  v2.1.3 grana ima tri kontrolisane migration datoteke
PASS  v2.1.3.3 ProductRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 ProductVariantRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 migracija povezuje listu diskova i izvedeni ukupni kapacitet
PASS  v2.1.3.3 stari kapacitet se bezbedno prenosi na prvi disk
PASS  v2.1.3.3 backend ne veruje ručnom ukupnom zbiru
PASS  v2.1.3.3 ukupni kapacitet je ispod diskova i readonly
PASS  v2.1.3.3 frontend sabira diskove i čuva početni legacy zbir
PASS  v2.1.3.3 proizvod i varijante dele isti storage model
PASS  v2.1.3.3 storage smoke i contract test postoje
PASS  v2.1.4 migracija dodaje model proizvoda i usklađuje šablone
PASS  v2.1.4 model se validira čuva i koristi u nazivu
PASS  v2.1.4 forma ima model proizvoda posle linije
PASS  v2.1.4 trajno brisanje ima SKU potvrdu i izbor brisanja slika
PASS  v2.1.4 poslovna istorija blokira destruktivno brisanje
PASS  v2.1.4 semantički sistem tastera pokriva sve uloge
PASS  v2.1.4 route i stable doctor postoje
PASS  v2.1.4 smoke i contract test postoje
PASS  v2.1.4.1 controller priprema i prosledjuje orderedFields
PASS  v2.1.4.1 Blade bezbedno inicijalizuje orderedFields
PASS  v2.1.4.1 doctor renderuje formulare svih tipova
PASS  v2.1.4.1 smoke i contract test postoje
PASS  v2.1.5 globalni UX runtime štiti submit i nesačuvane izmene
PASS  v2.1.5 mobilni action dock koristi originalni submit
PASS  v2.1.5 validacija i accessibility markeri postoje
PASS  v2.1.5 dugi formulari su eksplicitno označeni
PASS  v2.1.5 sistemske error stranice postoje
PASS  v2.1.5 migracija koristi postojeće snaga-napajanja polje
PASS  v2.1.5 migracija postavlja napajanje u sredinu
PASS  v2.1.5 doctor proverava Blade, route akcije i napajanje
PASS  v2.1.5 stable release koristi render i repair
PASS  v2.1.5 smoke i contract test postoje
PASS  v2.1.6 migracija kreira snapshot istoriju i ciljane indekse
PASS  v2.1.6 Data Quality audit pokriva katalog slike varijante i specifikacije
PASS  v2.1.6 repair je nedestruktivan i preračunava izvedene vrednosti
PASS  v2.1.6 performance doctor proverava indekse cache i SQL pragove
PASS  v2.1.6 dashboard kešira schema metadata po requestu
PASS  v2.1.6 Data Quality Center rute i prikaz postoje
PASS  v2.1.6 katalog ima quality filtere
PASS  v2.1.6 doctor renderuje centar i pokreće performance audit
PASS  v2.1.6 Stable release uključuje render repair i strict
PASS  v2.1.6 smoke i contract test postoje
PASS  v2.2.0 bootstrap device catalog order i notification rute postoje
PASS  v2.2.0 bootstrap vraća permissions features i app policy
PASS  v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv
PASS  v2.2.0 API greške imaju stabilan envelope
PASS  v2.2.0 OpenAPI i Stable doctor su povezani

Ukupno: 983, neuspešno: 0
CMS_STATIC_CHECK=PASS
OPENAPI_FINAL_PARITY=PASS

============================================================
9. GIT / SOURCE IMMUTABILITY
============================================================
TARGETED_GIT_DIFF_CHECK=PASS
SOURCE_WRITES_DURING_BATCH=0
DATABASE_WRITES_DURING_BATCH=0

============================================================
10. DECISION
============================================================
ORDERS_ADMIN_MUTATION_API_FOUND=NO
WEB_WORKFLOW_REUSE_REQUIRED=YES
MUTATION_BACKEND_STRATEGY=ADD_DEDICATED_AUTHORIZED_API_V1_ADMIN_ORDER_MUTATION_ENDPOINTS_THAT_REUSE_EXISTING_DOMAIN_SERVICES
DO_NOT_CALL_WEB_CONTROLLERS_FROM_MOBILE=YES
DO_NOT_DUPLICATE_BUSINESS_LOGIC=YES
NEXT_ACTION=PREPARE_V0_7_ORDERS_ADMIN_MUTATION_BACKEND_FOUNDATION_BATCH5
ORDERS_ADMIN_MUTATION_AUDIT_SCOPE=STATUS+PAYMENT+DELIVERY_TRACKING+ASSIGNMENT+INTERNAL_NOTES+COMPLETION_REOPEN+DOCUMENT_SIGNALS
BACKEND_CHANGES=NO
ROUTE_CHANGES=NO
MOBILE_RUNTIME_CHANGES=NO
UI_SCREEN_CHANGES=NO
OPENAPI_CHANGES=NO
VALIDATOR_CHANGES=NO
MIGRATIONS_RUN=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION=0.7.0
EAS_BUILD=NO
MOBILE_V0_7_ORDERS_ADMIN_WORKFLOW_MUTATION_AUDIT_BATCH4=PASS

PASS: MOBILE v0.7.0 ORDERS ADMIN WORKFLOW MUTATION AUDIT BATCH 4 COMPLETE

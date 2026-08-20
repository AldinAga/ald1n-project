
============================================================
MOBILE v0.7.0 - AFTER-SALES ADMIN READ-ONLY AUDIT - BATCH 1 V2
============================================================
DATE=Tue Aug 18 19:39:58 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-AFTER-SALES-ADMIN-AUDIT-BATCH1-V2-20260818-193958.md
BACKUP=/home/icaffeco/backups/releases/mobile-v0.7.0-after-sales-admin-audit-batch1-v2-20260818-193958
MODE=READ_ONLY_DISCOVERY_AND_CONTRACT_AUDIT
SOURCE_WRITES=NO
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION_CHANGE=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
RERUN_POLICY=SAFE_IDEMPOTENT_READ_ONLY
TARGET_WORKSTREAM=AFTER_SALES_ADMIN
EXPECTED_DOMAIN_SEQUENCE=AFTER_SALES_ADMIN_THEN_FIELD_OPERATIONS
BATCH1_V2_FIX=CORRECT_WEB_ROUTE_METRIC_8_ADMIN_PREFIXED_PLUS_1_SHARED_ATTACHMENT_OPERATION

============================================================
0. PREFLIGHT + ORDERS ADMIN FINAL CERTIFICATION PREREQUISITE
============================================================
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
CURRENT_APP_VERSION=0.7.0
CURRENT_PACKAGE_LOCK_VERSION=0.7.0
ORDERS_ADMIN_BATCH8_PASS_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-ORDERS-ADMIN-FINAL-CERTIFICATION-BATCH8-20260818-190044.md
ORDERS_ADMIN_V0_7_PREREQUISITE=PASS_COMPLETE
BATCH1_V1_INCIDENT_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-AFTER-SALES-ADMIN-AUDIT-BATCH1-20260818-192908.md
BATCH1_V1_INCIDENT=FALSE_EXPECTATION_9_ADMIN_PREFIXED_ROUTES_ACTUAL_8_PLUS_SHARED_ATTACHMENT
BATCH1_V1_SOURCE_MUTATION=NO_READ_ONLY_AUDIT

============================================================
1. READ-ONLY EVIDENCE SNAPSHOT + HASH BASELINE
============================================================
READ_ONLY_BACKUP_SNAPSHOT=PASS
READ_ONLY_HASH_BASELINE_FILE_COUNT=35
GIT_BASELINE_CAPTURED=YES

============================================================
2. CURRENT CUSTOMER AFTER-SALES MOBILE/API BASELINE
============================================================
CUSTOMER_AFTER_SALES_MOBILE_BASELINE=PASS_EXISTING_LIST_DETAIL_CREATE_MESSAGE_ATTACHMENT_FLOW

  GET|HEAD   api/v1/after-sales ............................................................................... api.v1.after-sales.index › Api\V1\AfterSalesController@index
  GET|HEAD   api/v1/after-sales/attachments/{attachment} .............................................. api.v1.after-sales.attachments.show › AfterSalesAttachmentController
  GET|HEAD   api/v1/after-sales/{case} .......................................................................... api.v1.after-sales.show › Api\V1\AfterSalesController@show
  POST       api/v1/after-sales/{case}/messages .................................................... api.v1.after-sales.messages.store › Api\V1\AfterSalesController@message

                                                                                                                                                          Showing [4] routes

CUSTOMER_AFTER_SALES_RUNTIME_DIRECT_ROUTE_COUNT=4
CUSTOMER_ORDER_AFTER_SALES_RUNTIME_ROUTE_COUNT=2
CUSTOMER_AFTER_SALES_RUNTIME_TOTAL_ROUTE_COUNT=6

============================================================
3. WEB ADMIN AFTER-SALES AUTHORITY SURFACE
============================================================
AFTER_SALES_WEB_AUTHORITY_PHP_LINT=PASS

  GET|HEAD   admin/after-sales .................................................................................. admin.after-sales.index › Admin\AfterSalesController@index
  GET|HEAD   admin/after-sales/{case} ............................................................................. admin.after-sales.show › Admin\AfterSalesController@show
  PATCH      admin/after-sales/{case} ......................................................................... admin.after-sales.update › Admin\AfterSalesController@update
  POST       admin/after-sales/{case}/actions ..................................................... admin.after-sales.actions.store › Admin\AfterSalesActionController@store
  POST       admin/after-sales/{case}/actions/{action}/cancel ................................... admin.after-sales.actions.cancel › Admin\AfterSalesActionController@cancel
  POST       admin/after-sales/{case}/actions/{action}/complete ............................. admin.after-sales.actions.complete › Admin\AfterSalesActionController@complete
  POST       admin/after-sales/{case}/actions/{action}/start ...................................... admin.after-sales.actions.start › Admin\AfterSalesActionController@start
  POST       admin/after-sales/{case}/messages ....................................................... admin.after-sales.messages.store › Admin\AfterSalesController@message

                                                                                                                                                          Showing [8] routes

WEB_ADMIN_AFTER_SALES_ROUTE_COUNT=8

  GET|HEAD       after-sales/attachments/{attachment} ........................................................ after-sales.attachments.show › AfterSalesAttachmentController
  GET|HEAD       api/v1/after-sales/attachments/{attachment} .......................................... api.v1.after-sales.attachments.show › AfterSalesAttachmentController

                                                                                                                                                          Showing [2] routes

SHARED_AFTER_SALES_ATTACHMENT_RUNTIME_ROUTE_COUNT=2
SHARED_AFTER_SALES_ATTACHMENT_AUTHORITY=PASS_AUTHORIZED_PRIVATE_NO_STORE
WEB_ADMIN_AFTER_SALES_OPERATION_COUNT=9
WEB_ADMIN_ROUTE_METRIC=PASS_8_ADMIN_PREFIXED_ROUTES_PLUS_1_SHARED_ATTACHMENT_OPERATION

--- WEB CONTROLLER PUBLIC METHODS ---
23:    public function index(Request $request, AfterSalesAccessService $access): View
56:    public function show(Request $request, AfterSalesCase $case, AfterSalesAccessService $access): View
74:    public function update(UpdateAfterSalesCaseRequest $request, AfterSalesCase $case, AfterSalesCaseService $service): RedirectResponse
80:    public function message(StoreAfterSalesMessageRequest $request, AfterSalesCase $case, AfterSalesCaseService $service): RedirectResponse

--- WEB ACTION CONTROLLER PUBLIC METHODS ---
19:    public function store(StoreAfterSalesActionRequest $request, AfterSalesCase $case, AfterSalesActionService $service): RedirectResponse
25:    public function start(Request $request, AfterSalesCase $case, AfterSalesAction $action, AfterSalesActionService $service): RedirectResponse
31:    public function complete(CompleteAfterSalesActionRequest $request, AfterSalesCase $case, AfterSalesAction $action, AfterSalesActionService $service): RedirectResponse
37:    public function cancel(CancelAfterSalesActionRequest $request, AfterSalesCase $case, AfterSalesAction $action, AfterSalesActionService $service): RedirectResponse

--- ACCESS SERVICE SIGNALS ---
15:    public function applyVisibleScope(Builder $query, User $user): Builder
17:        if ($user->hasRole('superadmin')) {
24:                    ->orWhereHas('order', static fn (Builder $orders) => $orders->where('supplier_user_id', $user->id));
31:    public function canView(AfterSalesCase $case, User $user): bool
33:        if ($user->hasRole('superadmin')) return true;
36:                || (int) $case->order?->supplier_user_id === (int) $user->id;
44:        return $user->hasPermission('after_sales.manage')
45:            && ($user->hasRole('superadmin')
47:                || (int) $case->order?->supplier_user_id === (int) $user->id);
52:        if (!$user->hasPermission('after_sales.create')) return false;
60:        abort_unless($this->canView($case, $user), 404);

--- CASE SERVICE PUBLIC METHODS / LOCK SIGNALS ---
25:    public function __construct(
27:        private readonly AuditLogger $audit,
32:    public function create(Order $order, User $actor, array $data, array $files = []): AfterSalesCase
43:                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
57:                    ->lockForUpdate()
74:                    'status' => 'open',
107:                    'from_status' => null,
108:                    'to_status' => 'open',
116:                $this->audit->log('after_sales.created', 'Otvoren postprodajni slučaj '.$case->case_number, $case, null, $case->toArray(), ['order_id' => $lockedOrder->id], $actor);
125:        $this->notifyCreated($case->load(['order', 'assignee']), $actor);
126:        return $case->load(['order', 'items', 'messages.user', 'attachments']);
130:    public function addMessage(AfterSalesCase $case, User $actor, string $body, string $visibility, array $files = []): AfterSalesMessage
142:            $message = DB::transaction(function () use ($case, $actor, $body, $visibility, $files, &$storedPaths): AfterSalesMessage {
143:                $locked = AfterSalesCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
149:                $message = $locked->messages()->create([
154:                $this->storeAttachments($locked, $message, $actor, $files, $storedPaths);
159:                if (!$actor->hasRole('admin', 'superadmin') && $locked->status === 'awaiting_customer') {
160:                    $from = $locked->status;
161:                    $locked->forceFill(['status' => 'under_review'])->save();
165:                $this->audit->log('after_sales.message', 'Dodata poruka u slučaju '.$locked->case_number, $locked, null, ['visibility' => $visibility], null, $actor);
166:                return $message;
173:        $this->notifyMessage($case->fresh(['order', 'opener', 'assignee']), $actor, $visibility);
174:        return $message->load(['user', 'attachments']);
178:    public function update(AfterSalesCase $case, User $actor, array $data): AfterSalesCase
183:            $locked = AfterSalesCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
185:            $from = $locked->status;
186:            $to = (string) $data['status'];
193:                $assignee = User::query()->where('status', 'active')->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->find((int) $data['assigned_to']);
200:                    ->whereIn('status', ['planned', 'in_progress'])
203:                    throw ValidationException::withMessages(['status' => 'Slučaj ne može biti završen dok postoje planirane ili aktivne izvršne radnje.']);
211:                        ->where('status', 'completed')
214:                        throw ValidationException::withMessages(['status' => 'Za izabrano rešenje prvo evidentirajte i izvršite odgovarajuću postprodajnu radnju.']);
232:                'status' => $to,
252:            $this->audit->log('after_sales.updated', 'Ažuriran postprodajni slučaj '.$locked->case_number, $locked, $before, $locked->fresh()->toArray(), null, $actor);
254:            return $locked->fresh(['order', 'items', 'messages.user', 'attachments', 'history.actor', 'opener', 'assignee']);
257:        $this->notifyStatus($updated, $actor);
280:            throw ValidationException::withMessages(['status' => 'Prelaz iz trenutnog u izabrani status nije dozvoljen.']);
288:            'from_status' => $from,
289:            'to_status' => $to,
298:    private function storeAttachments(AfterSalesCase $case, ?AfterSalesMessage $message, User $actor, array $files, array &$storedPaths): void
302:                throw ValidationException::withMessages(['attachments' => 'Jedan od priloga nije ispravno otpremljen.']);
306:                throw ValidationException::withMessages(['attachments' => 'Dozvoljeni su PDF, JPG, PNG i WebP prilozi.']);
309:                throw ValidationException::withMessages(['attachments' => 'Svaki prilog može imati najviše 10 MB.']);
313:            if (!is_string($path) || $path === '') throw ValidationException::withMessages(['attachments' => 'Prilog nije mogao biti bezbedno sačuvan.']);
317:                'message_id' => $message?->id,
337:                ->where('status', 'active')
350:            ->where('status', 'active')
357:    private function notifyCreated(AfterSalesCase $case, User $actor): void
364:                ->where('status', 'active')
374:                'message' => $case->case_number.' · '.$case->subject,
380:    private function notifyMessage(AfterSalesCase $case, User $actor, string $visibility): void
386:                'event' => 'after_sales_message', 'title' => 'Nova poruka u slučaju '.$case->case_number,
388:                'message' => $case->subject,
395:    private function notifyStatus(AfterSalesCase $case, User $actor): void
399:                'event' => 'after_sales_status', 'title' => 'Ažuriran slučaj '.$case->case_number,
401:                'message' => 'Novi status: '.$case->status,

--- ACTION SERVICE PUBLIC METHODS / SIDE EFFECT SIGNALS ---
14:use App\Models\FieldWorkOrder;
24:    public function __construct(
26:        private readonly OrderPaymentService $payments,
27:        private readonly AuditLogger $audit,
28:        private readonly OperationalNotificationService $notifications,
29:        private readonly FieldWorkOrderPlanner $fieldWorkOrders,
34:    public function create(AfterSalesCase $case, User $actor, array $data): AfterSalesAction
41:            $lockedCase = AfterSalesCase::query()->with(['order', 'items'])->lockForUpdate()->findOrFail($case->id);
56:                ->lockForUpdate()
63:            $inventoryHandling = $this->inventoryHandling($type, (string) ($data['inventory_handling'] ?? 'none'));
65:            if ($type === 'refund' && ($amount === null || $amount <= 0)) {
66:                throw ValidationException::withMessages(['amount_rsd' => 'Za refundaciju unesite iznos veći od nule.']);
78:                'inventory_handling' => $inventoryHandling,
111:                    'stock_effect' => $this->stockEffect($type, $inventoryHandling, $disposition),
118:            $this->audit->log(
135:    public function start(AfterSalesCase $case, AfterSalesAction $action, User $actor, bool $fromWorkOrder = false): AfterSalesAction
141:            $locked = AfterSalesAction::query()->with(['case.order', 'workOrder'])->lockForUpdate()->findOrFail($action->id);
149:            if (!$fromWorkOrder && in_array($locked->action_type, FieldWorkOrderPlanner::PHYSICAL_ACTIONS, true)
150:                && $locked->workOrder instanceof FieldWorkOrder) {
175:            $this->audit->log('after_sales.action_started', 'Pokrenuta postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), null, $actor);
184:    public function complete(AfterSalesCase $case, AfterSalesAction $action, User $actor, array $data, bool $fromWorkOrder = false): AfterSalesAction
192:                ->lockForUpdate()
198:            if (!$fromWorkOrder && in_array($locked->action_type, FieldWorkOrderPlanner::PHYSICAL_ACTIONS, true)
199:                && $locked->workOrder instanceof FieldWorkOrder && $locked->workOrder->status !== 'completed') {
207:            $order = Order::query()->lockForUpdate()->findOrFail($locked->case->order_id);
208:            if ($locked->action_type === 'refund') {
210:                $payment = $this->payments->recordAfterSalesRefundLocked($locked, $order, $actor);
211:                $locked->payment_id = $payment->id;
212:            } elseif ($locked->inventory_handling === 'automatic') {
229:            $this->audit->log('after_sales.action_completed', 'Izvršena postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), ['case_number' => $locked->case->case_number], $actor);
230:            return $locked->fresh(['case.order', 'items.stockMovement', 'assignee', 'payment', 'workOrder.team']);
237:    public function cancel(AfterSalesCase $case, AfterSalesAction $action, User $actor, string $reason): AfterSalesAction
243:            $locked = AfterSalesAction::query()->with(['case.order', 'workOrder'])->lockForUpdate()->findOrFail($action->id);
260:            if ($locked->workOrder instanceof FieldWorkOrder && $locked->workOrder->status !== 'completed') {
266:            $this->audit->log('after_sales.action_cancelled', 'Otkazana postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), null, $actor);
286:        $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
292:        $variants = ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
330:            $existing = StockMovement::query()->where('event_key', $eventKey)->first();
350:                'event_key' => $eventKey,
381:        if ($type === 'refund' && !$actor->hasPermission('payments.manage')) {
382:            throw ValidationException::withMessages(['action_type' => 'Za izvršenje refundacije potrebna je dozvola za upravljanje uplatama.']);
401:            throw ValidationException::withMessages(['action_type' => 'Zamena, povrat robe ili refundacija mogu se planirati tek kada je slučaj odobren.']);
420:    private function inventoryHandling(string $type, string $requested): string
452:            $this->notifications->send($recipient, [
AFTER_SALES_BACKEND_AUTHORITY=PASS_REUSE_ACCESS_CASE_ACTION_SERVICES_REQUIRED

============================================================
4. REQUEST / BUSINESS INPUT CONTRACT DISCOVERY
============================================================

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/UpdateAfterSalesCaseRequest.php ---
15:    public function rules(): array
18:            'status' => ['required', Rule::in(['open', 'under_review', 'awaiting_customer', 'approved', 'in_service', 'resolved', 'rejected', 'closed'])],
19:            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
20:            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
21:            'due_at' => ['nullable', 'date'],
22:            'resolution_type' => ['nullable', Rule::in(['repair', 'replacement', 'partial_refund', 'full_refund', 'return', 'inspection', 'rejected', 'other'])],
23:            'resolution_summary' => ['nullable', 'string', 'max:10000'],
24:            'note' => ['nullable', 'string', 'max:3000'],

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreAfterSalesMessageRequest.php ---
15:    public function rules(): array
20:            'attachments' => ['nullable', 'array', 'max:6'],
21:            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreAfterSalesActionRequest.php ---
19:    public function rules(): array
22:            'action_type' => ['required', Rule::in(array_keys(AfterSalesAction::typeLabels()))],
23:            'inventory_handling' => ['nullable', Rule::in(['none', 'automatic', 'external'])],
24:            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
25:            'scheduled_at' => ['nullable', 'date'],
26:            'scheduled_end_at' => ['nullable', 'date', 'after:scheduled_at'],
27:            'field_service_team_id' => ['nullable', 'integer', 'exists:field_service_teams,id'],
28:            'due_at' => ['nullable', 'date'],
29:            'amount_rsd' => ['nullable', 'numeric', 'min:0.01', 'max:999999999999.99'],
30:            'reference' => ['nullable', 'string', 'max:190'],
31:            'public_note' => ['nullable', 'string', 'max:5000'],
32:            'internal_note' => ['nullable', 'string', 'max:5000'],
33:            'items' => ['required', 'array'],
34:            'items.*.selected' => ['nullable', 'boolean'],
35:            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
36:            'items.*.disposition' => ['nullable', Rule::in(array_keys(AfterSalesAction::dispositionLabels()))],

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/CompleteAfterSalesActionRequest.php ---
17:    public function rules(): array
20:            'reference' => ['nullable', 'string', 'max:190'],
21:            'completion_note' => ['nullable', 'string', 'max:5000'],

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/CancelAfterSalesActionRequest.php ---
17:    public function rules(): array
19:        return ['cancellation_reason' => ['required', 'string', 'min:5', 'max:3000']];
AFTER_SALES_REQUEST_CONTRACT_DISCOVERY=PASS

============================================================
5. DATABASE READ-ONLY DOMAIN PROBE
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-after-sales-admin-audit-batch1-v2.0nZ5Jj/after-sales-db-probe.php
TABLE_after_sales_cases=PASS
TABLE_after_sales_case_items=PASS
TABLE_after_sales_messages=PASS
TABLE_after_sales_attachments=PASS
TABLE_after_sales_status_history=PASS
TABLE_after_sales_actions=PASS
TABLE_after_sales_action_items=PASS
AFTER_SALES_CASE_COUNT=0
AFTER_SALES_ACTION_COUNT=0
AFTER_SALES_ATTACHMENT_COUNT=0
DATABASE_WRITES_DURING_PROBE=0
AFTER_SALES_DATABASE_READ_ONLY_PROBE=PASS

============================================================
6. CURRENT ADMIN API / OPENAPI GAP
============================================================

   ERROR  Your application doesn't have any routes matching the given criteria.  

ADMIN_AFTER_SALES_RUNTIME_ROUTE_COUNT=0
OPENAPI_PRE_PARITY=PASS
OPENAPI_ADMIN_AFTER_SALES_PATH_COUNT=0
PLANNED_PATH_MISSING=/api/v1/admin/after-sales:
PLANNED_PATH_MISSING=/api/v1/admin/after-sales/{case}:
PLANNED_PATH_MISSING=/api/v1/admin/after-sales/{case}/messages:
PLANNED_PATH_MISSING=/api/v1/admin/after-sales/{case}/actions:
PLANNED_PATH_MISSING=/api/v1/admin/after-sales/{case}/actions/{action}/start:
PLANNED_PATH_MISSING=/api/v1/admin/after-sales/{case}/actions/{action}/complete:
PLANNED_PATH_MISSING=/api/v1/admin/after-sales/{case}/actions/{action}/cancel:
PLANNED_PATH_MISSING=/api/v1/admin/after-sales/attachments/{attachment}:
ADMIN_AFTER_SALES_API_STATE=ABSENT_CLEAN_GAP

============================================================
7. CURRENT MOBILE ADMIN AFTER-SALES GAP
============================================================
ADMIN_AFTER_SALES_API_PRESENT=NO
ADMIN_AFTER_SALES_ACTIONS_PRESENT=NO
ADMIN_AFTER_SALES_ATTACHMENT_PRESENT=NO
ADMIN_AFTER_SALES_LIST_PRESENT=NO
ADMIN_AFTER_SALES_DETAIL_PRESENT=NO
ADMIN_AFTER_SALES_MOBILE_CANDIDATE_FILE_COUNT_PRESENT=0
ADMIN_HUB_AFTER_SALES_SIGNAL_COUNT=0
ADMIN_QUERY_KEY_AFTER_SALES_SIGNAL_COUNT=0
ADMIN_AFTER_SALES_PERMISSION_FOUNDATION=PASS_MANAGE_AND_EXECUTE
ADMIN_AFTER_SALES_MOBILE_STATE=ABSENT_CLEAN_GAP

============================================================
8. PERMISSION / SECURITY / SIDE-EFFECT CONTRACT
============================================================

--- PERMISSION SIGNALS IN CMS ---
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:178:        Route::middleware('permission:after_sales.view_own')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:184:        Route::middleware('permission:after_sales.create')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:163:    Route::middleware('permission:after_sales.view_own')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:169:    Route::middleware('permission:after_sales.create')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:284:        Route::middleware('permission:after_sales.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:291:        Route::middleware('permission:after_sales.execute')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:182:        Gate::define('after_sales.create', static fn (User $user): bool => $user->hasPermission('after_sales.create'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:183:        Gate::define('after_sales.view_own', static fn (User $user): bool => $user->hasPermission('after_sales.view_own'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:184:        Gate::define('after_sales.manage', static fn (User $user): bool => $user->hasPermission('after_sales.manage'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:185:        Gate::define('after_sales.execute', static fn (User $user): bool => $user->hasPermission('after_sales.execute'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:116:                $this->audit->log('after_sales.created', 'Otvoren postprodajni slučaj '.$case->case_number, $case, null, $case->toArray(), ['order_id' => $lockedOrder->id], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:138:        if ($visibility === 'internal' && !$actor->hasPermission('after_sales.manage')) abort(403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesAccessService.php:44:        return $user->hasPermission('after_sales.manage')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesAccessService.php:52:        if (!$user->hasPermission('after_sales.create')) return false;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:50:            'after_sales_manage' => $this->allows($actor, 'after_sales.manage'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:180:            'after_sales_create' => $this->allows($actor, 'after_sales.create'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php:42:            'after_sales' => app(ModuleVisibilityService::class)->enabled('after_sales') && ($has('after_sales.view_own') || $has('after_sales.create')) ,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:376:        if (!$actor->hasPermission('after_sales.execute')) abort(403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:74:        if ($actor->hasPermission('after_sales.manage') || $actor->hasPermission('after_sales.view_own')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:366:        $managed = $actor->hasPermission('after_sales.manage');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:367:        $own = $actor->hasPermission('after_sales.view_own');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:493:        if ($actor->hasPermission('after_sales.manage')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:495:        } elseif ($actor->hasPermission('after_sales.view_own')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:50:            'after_sales_manage' => $this->allows($user, 'after_sales.manage'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:51:            'after_sales_view_own' => $this->allows($user, 'after_sales.view_own'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php:77:            'permissions' => ['after_sales.manage', 'after_sales.execute'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AfterSalesAttachmentController.php:19:        if ($attachment->message?->visibility === 'internal' && !$request->user()->hasPermission('after_sales.manage')) {
AFTER_SALES_FIELD_WORK_SIDE_EFFECT=PRESENT
AFTER_SALES_PAYMENT_REFUND_SIDE_EFFECT=PRESENT
DIRECT_MODEL_UPDATE_IN_NEW_ADMIN_API_ALLOWED=NO
BACKEND_SERVICE_REUSE_REQUIRED=YES

============================================================
9. TARGET ADMIN API PLAN FROM ARCHITECTURAL CONTRACT
============================================================
PLANNED_ADMIN_AFTER_SALES_API_BEGIN
GET   /api/v1/admin/after-sales
GET   /api/v1/admin/after-sales/{case}
PATCH /api/v1/admin/after-sales/{case}
POST  /api/v1/admin/after-sales/{case}/messages
POST  /api/v1/admin/after-sales/{case}/actions
POST  /api/v1/admin/after-sales/{case}/actions/{action}/start
POST  /api/v1/admin/after-sales/{case}/actions/{action}/complete
POST  /api/v1/admin/after-sales/{case}/actions/{action}/cancel
GET   /api/v1/admin/after-sales/attachments/{attachment}
PLANNED_ADMIN_AFTER_SALES_API_END
PLANNED_ADMIN_AFTER_SALES_OPERATION_COUNT=9
ADMIN_READ_PERMISSION=after_sales.manage
ADMIN_EXECUTE_PERMISSION=after_sales.execute
ACCESS_AUTHORITY=AfterSalesAccessService
CASE_MUTATION_AUTHORITY=AfterSalesCaseService
ACTION_MUTATION_AUTHORITY=AfterSalesActionService
PRIVATE_ATTACHMENT_POLICY=BEARER_AUTH_PRIVATE_NO_DIRECT_STORAGE_PATH

============================================================
10. GIT SAFETY + READ-ONLY IMMUTABILITY RECERTIFICATION
============================================================
TARGETED_GIT_DIFF_CHECK=PASS
READ_ONLY_HASH_RECERTIFICATION=PASS_NO_MANAGED_SOURCE_DRIFT_DURING_AUDIT
GIT_VISIBLE_STATE=UNCHANGED_DURING_AUDIT

============================================================
11. FINAL AFTER-SALES ADMIN AUDIT DECISION
============================================================
ORDERS_ADMIN_V0_7_PROGRESS=100_PERCENT_COMPLETE
AFTER_SALES_ADMIN_WORKSTREAM=SELECTED_NEXT
AFTER_SALES_ADMIN_AUDIT_BATCH1=PASS_V2
AFTER_SALES_ADMIN_WEB_AUTHORITY=PASS_8_ADMIN_PREFIXED_ROUTES_9_HTTP_OPERATIONS_WITH_SHARED_ATTACHMENT
SOURCE_WRITES_DURING_BATCH=0
DATABASE_WRITES_DURING_BATCH=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION=0.7.0
EAS_BUILD=NO
NEXT_ACTION=PREPARE_V0_7_AFTER_SALES_ADMIN_API_FOUNDATION_BATCH2
MOBILE_V0_7_AFTER_SALES_ADMIN_AUDIT_BATCH1=PASS
MOBILE_V0_7_AFTER_SALES_ADMIN_AUDIT_BATCH1_V2=PASS
REPORT_READY_TO_UPLOAD=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-AFTER-SALES-ADMIN-AUDIT-BATCH1-V2-20260818-193958.md
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-AFTER-SALES-ADMIN-AUDIT-BATCH1-V2-20260818-193958.md

PASS: MOBILE v0.7.0 AFTER-SALES ADMIN READ-ONLY AUDIT BATCH 1 V2 COMPLETE

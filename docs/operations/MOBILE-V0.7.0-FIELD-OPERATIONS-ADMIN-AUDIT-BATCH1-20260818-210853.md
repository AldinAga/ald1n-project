============================================================
MOBILE v0.7.0 - FIELD OPERATIONS ADMIN READ-ONLY AUDIT - BATCH 1
============================================================
DATE=Tue Aug 18 21:08:53 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-FIELD-OPERATIONS-ADMIN-AUDIT-BATCH1-20260818-210853.md
BACKUP=/home/icaffeco/backups/releases/mobile-v0.7.0-field-operations-admin-audit-batch1-20260818-210853
MODE=READ_ONLY_DISCOVERY_AND_CONTRACT_AUDIT
TARGET_WORKSTREAM=FIELD_OPERATIONS_ADMIN
SOURCE_WRITES=NO
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION_CHANGE=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
RERUN_POLICY=SAFE_IDEMPOTENT_READ_ONLY
PRIOR_WORKSTREAM=AFTER_SALES_ADMIN
EXPECTED_DOMAIN_SEQUENCE=FIELD_OPERATIONS_THEN_RECEIVABLES
HISTORICAL_TARGET_PLAN_DATE=2026-08-09
HISTORICAL_TARGET_PLAN_NOTE=RECONCILE_WITH_CURRENT_WEB_AUTHORITY_BEFORE_API_MUTATION

============================================================
0. PREFLIGHT + AFTER-SALES ADMIN FINAL CERTIFICATION PREREQUISITE
============================================================
CONCURRENCY_LOCK=ACQUIRED
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
CURRENT_APP_VERSION=0.7.0
CURRENT_PACKAGE_LOCK_VERSION=0.7.0
AFTER_SALES_BATCH4_LATEST_ATTEMPT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-AFTER-SALES-ADMIN-FINAL-CERTIFICATION-BATCH4-20260818-204447.md
AFTER_SALES_BATCH4_CANONICAL_PASS_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-AFTER-SALES-ADMIN-FINAL-CERTIFICATION-BATCH4-20260818-204447.md
AFTER_SALES_ADMIN_V0_7_PREREQUISITE=PASS_100_PERCENT_COMPLETE

============================================================
1. READ-ONLY EVIDENCE SNAPSHOT + HASH BASELINE
============================================================
READ_ONLY_BACKUP_SNAPSHOT=PASS
READ_ONLY_HASH_BASELINE_FILE_COUNT=32
GIT_BASELINE_CAPTURED=YES

============================================================
2. CURRENT WEB FIELD OPERATIONS AUTHORITY SURFACE
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldOperationsController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldServiceTeamController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldWorkOrderPartController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/FieldWorkOrderAttachmentController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/FieldOperationsService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/FieldWorkOrderPlanner.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ScheduleFieldWorkOrderRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/CompleteFieldWorkOrderRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/CancelFieldWorkOrderRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreFieldServiceTeamRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/UpdateFieldServiceTeamRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreFieldWorkOrderPartRequest.php
FIELD_OPERATIONS_WEB_AUTHORITY_PHP_LINT=PASS
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-field-operations-admin-audit-batch1.20260818-210853.3789243/route-probe.php
ROUTE=GET|HEAD|admin/field-operations|admin.field-operations.index|App\Http\Controllers\Admin\FieldOperationsController@index|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.view
ROUTE=GET|HEAD|admin/field-operations/{workOrder}|admin.field-operations.show|App\Http\Controllers\Admin\FieldOperationsController@show|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.view
ROUTE=POST|admin/field-operations/{workOrder}/cancel|admin.field-operations.cancel|App\Http\Controllers\Admin\FieldOperationsController@cancel|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.manage
ROUTE=POST|admin/field-operations/{workOrder}/complete|admin.field-operations.complete|App\Http\Controllers\Admin\FieldOperationsController@complete|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.manage,Illuminate\Routing\Middleware\ThrottleRequests:uploads
ROUTE=POST|admin/field-operations/{workOrder}/en-route|admin.field-operations.en-route|App\Http\Controllers\Admin\FieldOperationsController@enRoute|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.manage
ROUTE=POST|admin/field-operations/{workOrder}/on-site|admin.field-operations.on-site|App\Http\Controllers\Admin\FieldOperationsController@onSite|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.manage
ROUTE=POST|admin/field-operations/{workOrder}/parts|admin.field-operations.parts.store|App\Http\Controllers\Admin\FieldWorkOrderPartController@store|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:service_parts.manage
ROUTE=POST|admin/field-operations/{workOrder}/parts/reserve|admin.field-operations.parts.reserve|App\Http\Controllers\Admin\FieldWorkOrderPartController@reserve|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:service_parts.manage
ROUTE=DELETE|admin/field-operations/{workOrder}/parts/{line}|admin.field-operations.parts.destroy|App\Http\Controllers\Admin\FieldWorkOrderPartController@destroy|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:service_parts.manage
ROUTE=PATCH|admin/field-operations/{workOrder}/schedule|admin.field-operations.schedule|App\Http\Controllers\Admin\FieldOperationsController@schedule|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.manage
ROUTE=GET|HEAD|admin/field-service-teams|admin.field-service-teams.index|App\Http\Controllers\Admin\FieldServiceTeamController@index|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.manage
ROUTE=POST|admin/field-service-teams|admin.field-service-teams.store|App\Http\Controllers\Admin\FieldServiceTeamController@store|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.manage
ROUTE=PUT|admin/field-service-teams/{team}|admin.field-service-teams.update|App\Http\Controllers\Admin\FieldServiceTeamController@update|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.manage
ROUTE=DELETE|admin/field-service-teams/{team}|admin.field-service-teams.destroy|App\Http\Controllers\Admin\FieldServiceTeamController@destroy|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession,App\Http\Middleware\RequirePermission:field_operations.manage
ROUTE=GET|HEAD|api/v1/field-work-order-attachments/{attachment}|api.v1.field-work-order-attachments.show|App\Http\Controllers\FieldWorkOrderAttachmentController|api,Illuminate\Auth\Middleware\Authenticate:sanctum,App\Http\Middleware\EnsureActiveUser
ROUTE=GET|HEAD|field-work-order-attachments/{attachment}|field-work-order-attachments.show|App\Http\Controllers\FieldWorkOrderAttachmentController|web,Illuminate\Auth\Middleware\Authenticate,App\Http\Middleware\EnsureActiveUser,App\Http\Middleware\EnsureTrackedPortalSession
WEB_FIELD_OPERATIONS=10
WEB_FIELD_SERVICE_TEAMS=4
SHARED_FIELD_WORK_ATTACHMENTS=2
API_ADMIN_FIELD_WORK=0
API_ADMIN_FIELD_OPERATIONS=0
API_ADMIN_FIELD_SERVICE_TEAMS=0
WEB_FIELD_OPERATIONS_VIEW_PERMISSION=2
WEB_FIELD_OPERATIONS_MANAGE_PERMISSION=9
WEB_SERVICE_PARTS_MANAGE_PERMISSION=3
CANCEL=YES
PARTS_RESERVE=YES
TEAMS_STORE=YES
TEAMS_UPDATE=YES
TEAMS_DESTROY=YES
SHARED_API_ATTACHMENT=YES
WEB_FIELD_OPERATIONS_AUTHORITY=PASS
WEB_FIELD_OPERATIONS_ROUTE_COUNT=10
WEB_FIELD_SERVICE_TEAM_ROUTE_COUNT=4
SHARED_FIELD_WORK_ATTACHMENT_ROUTE_COUNT=2

--- FIELD OPERATIONS CONTROLLER PUBLIC METHODS ---
26:    public function index(Request $request): View
95:    public function show(Request $request, FieldWorkOrder $workOrder, AfterSalesAccessService $access): View
110:    public function schedule(ScheduleFieldWorkOrderRequest $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse
116:    public function enRoute(Request $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse
122:    public function onSite(Request $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse
128:    public function complete(CompleteFieldWorkOrderRequest $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse
134:    public function cancel(CancelFieldWorkOrderRequest $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse

--- FIELD SERVICE TEAM CONTROLLER PUBLIC METHODS ---
19:    public function index(Request $request): View
38:    public function store(StoreFieldServiceTeamRequest $request, AuditLogger $audit): RedirectResponse
50:    public function update(UpdateFieldServiceTeamRequest $request, FieldServiceTeam $team, AuditLogger $audit): RedirectResponse
65:    public function destroy(Request $request, FieldServiceTeam $team, AuditLogger $audit): RedirectResponse

--- FIELD WORK ORDER PART CONTROLLER PUBLIC METHODS ---
17:    public function store(StoreFieldWorkOrderPartRequest $request, FieldWorkOrder $workOrder, ServicePartsInventoryService $service): RedirectResponse
23:    public function reserve(Request $request, FieldWorkOrder $workOrder, ServicePartsInventoryService $service): RedirectResponse
30:    public function destroy(Request $request, FieldWorkOrder $workOrder, FieldWorkOrderPart $line, ServicePartsInventoryService $service): RedirectResponse

============================================================
3. SERVICE AUTHORITY + SIDE-EFFECT DISCOVERY
============================================================
--- FIELD OPERATIONS SERVICE ---
20:    public function __construct(
21:        private readonly AfterSalesAccessService $access,
22:        private readonly AfterSalesActionService $actions,
24:        private readonly AuditLogger $audit,
25:        private readonly OperationalNotificationService $notifications,
26:        private readonly ServicePartsInventoryService $serviceParts,
30:    public function schedule(FieldWorkOrder $workOrder, User $actor, array $data): FieldWorkOrder
33:        return DB::transaction(function () use ($workOrder, $actor, $data): FieldWorkOrder {
34:            $locked = FieldWorkOrder::query()->with(['action.case.order', 'team'])->lockForUpdate()->findOrFail($workOrder->id);
38:            $this->audit->log('field_work_order.scheduled', 'Raspoređen radni nalog '.$updated->work_order_number, $updated, $before, $updated->toArray(), null, $actor);
43:    public function markEnRoute(FieldWorkOrder $workOrder, User $actor): FieldWorkOrder
48:    public function markOnSite(FieldWorkOrder $workOrder, User $actor): FieldWorkOrder
53:    /** @param array<string,mixed> $data @param array<int,UploadedFile> $attachments */
54:    public function complete(FieldWorkOrder $workOrder, User $actor, array $data, array $attachments = []): FieldWorkOrder
57:        $stored = $this->storeUploads($workOrder, $attachments, (string) ($data['attachment_visibility'] ?? 'internal'));
60:            $completed = DB::transaction(function () use ($workOrder, $actor, $data, $stored): FieldWorkOrder {
63:                $locked = FieldWorkOrder::query()->with($relations)->lockForUpdate()->findOrFail($workOrder->id);
65:                if ($locked->status === 'completed') return $locked;
66:                if ($locked->status === 'cancelled') {
93:                    'status' => 'completed',
94:                    'completed_at' => now(),
104:                    'cancelled_at' => null,
105:                    'cancellation_reason' => null,
115:                $this->audit->log('field_work_order.completed', 'Završen radni nalog '.$locked->work_order_number, $locked, $before, $locked->toArray(), ['attachment_count' => count($stored)], $actor);
116:                return $locked->fresh(['team', 'action.case.order', 'attachments']);
123:        $this->notifyCustomer($completed, 'Terenska intervencija je završena', 'Radni nalog '.$completed->work_order_number.' je završen.', 'success');
124:        return $completed;
127:    public function cancel(FieldWorkOrder $workOrder, User $actor, string $reason): FieldWorkOrder
130:        $cancelled = DB::transaction(function () use ($workOrder, $actor, $reason): FieldWorkOrder {
131:            $locked = FieldWorkOrder::query()->with(['action.case.order', 'team'])->lockForUpdate()->findOrFail($workOrder->id);
133:            if ($locked->status === 'cancelled') return $locked;
134:            if ($locked->status === 'completed') {
135:                throw ValidationException::withMessages(['cancellation_reason' => 'Završen radni nalog se ne može otkazati.']);
138:            $this->actions->cancel($locked->action->case, $locked->action, $actor, $reason);
141:                'status' => 'cancelled', 'cancelled_at' => now(), 'status_by' => $actor->id,
142:                'cancellation_reason' => trim($reason), 'updated_by' => $actor->id,
144:            $this->audit->log('field_work_order.cancelled', 'Otkazan radni nalog '.$locked->work_order_number, $locked, $before, $locked->toArray(), null, $actor);
147:        $this->notifyCustomer($cancelled, 'Terenski termin je otkazan', 'Radni nalog '.$cancelled->work_order_number.' je otkazan. Bićete kontaktirani radi novog dogovora.', 'warning');
148:        return $cancelled;
154:        $updated = DB::transaction(function () use ($workOrder, $actor, $target): FieldWorkOrder {
155:            $locked = FieldWorkOrder::query()->with(['action.case.order', 'team'])->lockForUpdate()->findOrFail($workOrder->id);
180:            $this->audit->log('field_work_order.'.$target, 'Promenjen status radnog naloga '.$locked->work_order_number, $locked, $before, $locked->toArray(), null, $actor);
207:                throw ValidationException::withMessages(['attachments' => 'Svaki prilog može imati najviše 10 MB.']);
211:                throw ValidationException::withMessages(['attachments' => 'Dozvoljeni su PDF, JPG, PNG i WebP fajlovi.']);
216:                throw ValidationException::withMessages(['attachments' => 'Prilog nije mogao biti sačuvan.']);
241:        $this->notifications->send($customer, [

--- FIELD WORK ORDER PLANNER ---
9:use App\Models\FieldWorkOrder;
16:final class FieldWorkOrderPlanner
21:    public function ensureForAction(AfterSalesAction $action, User $actor, array $data = []): ?FieldWorkOrder
24:        if (!Schema::hasTable('field_work_orders') || !Schema::hasTable('field_service_teams')) {
33:        $teamId = $this->validatedTeamId($data['field_service_team_id'] ?? null);
35:        $workOrder = FieldWorkOrder::query()->firstOrNew(['after_sales_action_id' => $action->id]);
43:                    default => 'planned',
56:        $this->assertNoConflict($teamId, $start, $end, $workOrder->exists ? $workOrder->id : null);
58:            'field_service_team_id' => $teamId,
59:            'planned_start_at' => $start,
60:            'planned_end_at' => $end,
70:        return $workOrder->fresh(['team', 'attachments']);
74:    public function schedule(FieldWorkOrder $workOrder, User $actor, array $data): FieldWorkOrder
80:        $start = $this->date($data['planned_start_at'] ?? null);
81:        $end = $this->date($data['planned_end_at'] ?? null);
82:        $teamId = $this->validatedTeamId($data['field_service_team_id'] ?? null);
84:        $this->assertNoConflict($teamId, $start, $end, $workOrder->id);
87:            'field_service_team_id' => $teamId,
88:            'planned_start_at' => $start,
89:            'planned_end_at' => $end,
102:        return $workOrder->fresh(['team', 'action.case.order']);
105:    public function assertNoConflict(?int $teamId, ?CarbonImmutable $start, ?CarbonImmutable $end, ?int $exceptId = null): void
107:        if ($teamId === null || $start === null || $end === null) return;
109:        $conflict = FieldWorkOrder::query()
110:            ->where('field_service_team_id', $teamId)
111:            ->whereIn('status', ['planned', 'en_route', 'on_site'])
112:            ->whereNotNull('planned_start_at')
113:            ->whereNotNull('planned_end_at')
114:            ->where('planned_start_at', '<', $end)
115:            ->where('planned_end_at', '>', $start)
119:        if ($conflict instanceof FieldWorkOrder) {
121:                'field_service_team_id' => 'Izabrana ekipa je već zauzeta u tom terminu radnim nalogom '.$conflict->work_order_number.'.',
130:        $team = FieldServiceTeam::query()->lockForUpdate()->find($id);
131:        if (!$team instanceof FieldServiceTeam || !$team->is_active) {
132:            throw ValidationException::withMessages(['field_service_team_id' => 'Izaberite aktivnu terensku ekipu ili servisnog partnera.']);
140:            throw ValidationException::withMessages(['planned_end_at' => 'Početak i kraj termina moraju biti uneti zajedno.']);
143:            throw ValidationException::withMessages(['planned_end_at' => 'Kraj termina mora biti posle početka termina.']);
146:            throw ValidationException::withMessages(['planned_end_at' => 'Jedan terenski termin ne može trajati duže od 24 sata.']);

--- SERVICE PARTS INVENTORY SERVICE ---
7:use App\Models\FieldWorkOrder;
8:use App\Models\FieldWorkOrderPart;
21:    public function __construct(
28:    public function createPart(User $actor, array $data): ServicePart
30:        return DB::transaction(function () use ($actor, $data): ServicePart {
35:                'reserved_quantity' => 0,
43:                $this->movement(
55:    public function addToWorkOrder(FieldWorkOrder $workOrder, User $actor, array $data): FieldWorkOrderPart
59:        return DB::transaction(function () use ($workOrder, $actor, $data): FieldWorkOrderPart {
60:            $lockedWorkOrder = FieldWorkOrder::query()->with('action.case.order')->lockForUpdate()->findOrFail($workOrder->id);
66:            $part = ServicePart::query()->lockForUpdate()->findOrFail((int) $data['service_part_id']);
71:            $existing = FieldWorkOrderPart::query()
74:                ->lockForUpdate()
79:            if ($existing instanceof FieldWorkOrderPart) {
80:                if ((float) $existing->reserved_quantity > $requested && $mode === 'local_stock') {
83:                if ((float) $existing->reserved_quantity > 0 && $existing->supply_mode !== $mode) {
97:            $line = FieldWorkOrderPart::query()->create([
105:                'reserved_quantity' => 0,
106:                'consumed_quantity' => 0,
117:    public function reserveWorkOrderParts(FieldWorkOrder $workOrder, User $actor): FieldWorkOrder
121:        return DB::transaction(function () use ($workOrder, $actor): FieldWorkOrder {
122:            $lockedWorkOrder = FieldWorkOrder::query()->with('action.case.order')->lockForUpdate()->findOrFail($workOrder->id);
128:            $lines = FieldWorkOrderPart::query()
132:                ->lockForUpdate()
139:                $missing = round((float) $line->requested_quantity - (float) $line->reserved_quantity, 3);
141:                $part = ServicePart::query()->lockForUpdate()->findOrFail($line->service_part_id);
149:                $reservedBefore = (float) $part->reserved_quantity;
150:                $reservedAfter = round($reservedBefore + $missing, 3);
151:                $part->forceFill(['reserved_quantity' => $reservedAfter, 'updated_by' => $actor->id])->save();
152:                $line->forceFill(['reserved_quantity' => round((float) $line->reserved_quantity + $missing, 3), 'updated_by' => $actor->id])->save();
153:                $this->movement($part, $lockedWorkOrder, null, $actor, 'reservation', 0, $missing, $stockBefore, $stockBefore, $reservedBefore, $reservedAfter, 'Rezervacija za '.$lockedWorkOrder->work_order_number, 'reserve:'.$line->id.':'.$line->reserved_quantity);
156:            $this->audit->log('service_parts.reserved', 'Rezervisani delovi za '.$lockedWorkOrder->work_order_number, $lockedWorkOrder, null, null, ['line_count' => $lines->count()], $actor);
161:    public function removeFromWorkOrder(FieldWorkOrder $workOrder, FieldWorkOrderPart $line, User $actor): void
164:        DB::transaction(function () use ($workOrder, $line, $actor): void {
165:            $lockedWorkOrder = FieldWorkOrder::query()->with('action.case.order')->lockForUpdate()->findOrFail($workOrder->id);
168:            $lockedLine = FieldWorkOrderPart::query()->where('field_work_order_id', $lockedWorkOrder->id)->lockForUpdate()->findOrFail($line->id);
169:            if ((float) $lockedLine->consumed_quantity > 0) throw ValidationException::withMessages(['parts' => 'Utrošen deo se ne može ukloniti iz radnog naloga.']);
170:            if ($lockedLine->usesLocalStock() && (float) $lockedLine->reserved_quantity > 0) {
171:                $part = ServicePart::query()->lockForUpdate()->findOrFail($lockedLine->service_part_id);
173:                $reservedBefore = (float) $part->reserved_quantity;
174:                $release = min($reservedBefore, (float) $lockedLine->reserved_quantity);
175:                $reservedAfter = round($reservedBefore - $release, 3);
176:                $part->forceFill(['reserved_quantity' => $reservedAfter, 'updated_by' => $actor->id])->save();
177:                $this->movement($part, $lockedWorkOrder, null, $actor, 'release', 0, -$release, $stockBefore, $stockBefore, $reservedBefore, $reservedAfter, 'Oslobađanje rezervacije', 'remove:'.$lockedLine->id);
186:    public function finalizeWorkOrderParts(FieldWorkOrder $workOrder, User $actor, array $consumption): float
189:        $lines = FieldWorkOrderPart::query()
192:            ->lockForUpdate()
199:            $consumed = array_key_exists((string) $line->id, $consumption)
202:            if ($consumed < 0 || $consumed - $requested > 0.0001) {
207:                if ((float) $line->reserved_quantity + 0.0001 < $requested) {
210:                if ($consumed - (float) $line->reserved_quantity > 0.0001) {
213:                $part = ServicePart::query()->lockForUpdate()->findOrFail($line->service_part_id);
215:                $reservedBefore = (float) $part->reserved_quantity;
216:                if ($stockBefore + 0.0001 < $consumed || $reservedBefore + 0.0001 < (float) $line->reserved_quantity) {
219:                $stockAfter = round($stockBefore - $consumed, 3);
220:                $reservedAfter = round($reservedBefore - (float) $line->reserved_quantity, 3);
221:                $part->forceFill(['stock_quantity' => $stockAfter, 'reserved_quantity' => max(0, $reservedAfter), 'updated_by' => $actor->id])->save();
222:                if ($consumed > 0) {
223:                    $this->movement($part, $workOrder, null, $actor, 'consumption', -$consumed, -(float) $line->reserved_quantity, $stockBefore, $stockAfter, $reservedBefore, max(0, $reservedAfter), 'Utrošak na '.$workOrder->work_order_number, 'consume:'.$line->id);
225:                    $this->movement($part, $workOrder, null, $actor, 'release', 0, -(float) $line->reserved_quantity, $stockBefore, $stockAfter, $reservedBefore, max(0, $reservedAfter), 'Nije utrošeno na '.$workOrder->work_order_number, 'release-final:'.$line->id);
229:            $line->forceFill(['consumed_quantity' => $consumed, 'reserved_quantity' => 0, 'updated_by' => $actor->id])->save();
230:            $totalCost += $consumed * (float) $line->unit_cost_snapshot_rsd;
236:    public function releaseWorkOrderReservations(FieldWorkOrder $workOrder, User $actor, string $reason): void
239:        $lines = FieldWorkOrderPart::query()
242:            ->where('reserved_quantity', '>', 0)
244:            ->lockForUpdate()
248:            $part = ServicePart::query()->lockForUpdate()->findOrFail($line->service_part_id);
250:            $reservedBefore = (float) $part->reserved_quantity;
251:            $release = min($reservedBefore, (float) $line->reserved_quantity);
252:            $reservedAfter = max(0, round($reservedBefore - $release, 3));
253:            $part->forceFill(['reserved_quantity' => $reservedAfter, 'updated_by' => $actor->id])->save();
254:            $line->forceFill(['reserved_quantity' => 0, 'updated_by' => $actor->id])->save();
255:            $this->movement($part, $workOrder, null, $actor, 'release', 0, -$release, $stockBefore, $stockBefore, $reservedBefore, $reservedAfter, $reason, 'cancel-release:'.$line->id);
259:    public function adjust(ServicePart $part, User $actor, float $quantityChange, string $note, string $idempotencyKey): ServicePartMovement
261:        return DB::transaction(function () use ($part, $actor, $quantityChange, $note, $idempotencyKey): ServicePartMovement {
263:            $existing = ServicePartMovement::query()->where('event_key', $eventKey)->first();
265:            $locked = ServicePart::query()->lockForUpdate()->findOrFail($part->id);
266:            $existing = ServicePartMovement::query()->where('event_key', $eventKey)->first();
269:            $reservedBefore = (float) $locked->reserved_quantity;
272:            if ($stockAfter + 0.0001 < $reservedBefore) throw ValidationException::withMessages(['quantity_change' => 'Korekcija ne može spustiti stanje ispod rezervisane količine.']);
274:            $movement = $this->movement($locked, null, null, $actor, 'manual_adjustment', $quantityChange, 0, $stockBefore, max(0, $stockAfter), $reservedBefore, $reservedBefore, trim($note), $eventKey, true);
275:            $this->audit->log('service_part.adjusted', 'Korigovan servisni lager '.$locked->sku, $locked, ['stock_quantity' => $stockBefore], ['stock_quantity' => $stockAfter], ['movement_id' => $movement->id, 'note' => trim($note)], $actor);
276:            return $movement;
281:    public function createPurchaseRequest(User $actor, array $data): ServicePartPurchaseRequest
283:        return DB::transaction(function () use ($actor, $data): ServicePartPurchaseRequest {
319:    public function transitionPurchaseRequest(ServicePartPurchaseRequest $purchaseRequest, User $actor, string $target, ?string $reason = null): ServicePartPurchaseRequest
321:        return DB::transaction(function () use ($purchaseRequest, $actor, $target, $reason): ServicePartPurchaseRequest {
322:            $locked = ServicePartPurchaseRequest::query()->with(['items.part', 'supplier'])->lockForUpdate()->findOrFail($purchaseRequest->id);
361:            if (ServicePartMovement::query()->where('event_key', $eventKey)->exists()) continue;
362:            $part = ServicePart::query()->lockForUpdate()->findOrFail($item->service_part_id);
366:            $reservedBefore = (float) $part->reserved_quantity;
373:            $this->movement($part, null, $purchaseRequest, $actor, 'purchase_receipt', $qty, 0, $stockBefore, $stockAfter, $reservedBefore, $reservedBefore, 'Prijem po '.$purchaseRequest->request_number, $eventKey, true, (float) $item->unit_cost_rsd);
377:    private function authorizeWorkOrder(FieldWorkOrder $workOrder, User $actor): void
396:    private function movement(
398:        ?FieldWorkOrder $workOrder,
403:        float $reservedChange,
406:        float $reservedBefore,
407:        float $reservedAfter,
414:        return ServicePartMovement::query()->firstOrCreate(['event_key' => $eventKey], [
419:            'movement_type' => $type,
421:            'reserved_change' => round($reservedChange, 3),
424:            'reserved_before' => round($reservedBefore, 3),
425:            'reserved_after' => round($reservedAfter, 3),
FIELD_OPERATIONS_COMPLETE_AUTHORITY=PASS_REUSE_FIELD_OPERATIONS_SERVICE
FIELD_OPERATIONS_TRANSACTION_LOCK_AUTHORITY=PASS
SERVICE_PARTS_SIDE_EFFECT_AUTHORITY=PASS_REUSE_SERVICE_PARTS_INVENTORY_SERVICE
LIVE_WEB_ATTACHMENT_UPLOAD_MODE=COMPLETE_REQUEST_ATTACHMENTS
DIRECT_MODEL_MUTATION_IN_NEW_ADMIN_API_ALLOWED=NO
BACKEND_SERVICE_REUSE_REQUIRED=YES

============================================================
4. REQUEST / BUSINESS INPUT CONTRACT DISCOVERY
============================================================

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ScheduleFieldWorkOrderRequest.php ---
11:    public function authorize(): bool
16:    /** @return array<string,mixed> */
17:    public function rules(): array
20:            'field_service_team_id' => ['nullable', 'integer', 'exists:field_service_teams,id'],
21:            'planned_start_at' => ['nullable', 'date'],
22:            'planned_end_at' => ['nullable', 'date', 'after:planned_start_at'],
23:            'route_reference' => ['nullable', 'string', 'max:190'],
24:            'public_note' => ['nullable', 'string', 'max:5000'],
25:            'internal_note' => ['nullable', 'string', 'max:5000'],

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/CompleteFieldWorkOrderRequest.php ---
12:    public function authorize(): bool
17:    /** @return array<string,mixed> */
18:    public function rules(): array
21:            'route_reference' => ['nullable', 'string', 'max:190'],
22:            'completion_result' => ['required', 'string', 'min:5', 'max:10000'],
23:            'travel_km' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
24:            'travel_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
25:            'labor_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
26:            'parts_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
27:            'part_consumption' => ['nullable', 'array', 'max:200'],
28:            'part_consumption.*' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
29:            'attachment_visibility' => ['nullable', Rule::in(['internal', 'public'])],
30:            'attachments' => ['nullable', 'array', 'max:8'],
31:            'attachments.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp'],

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/CancelFieldWorkOrderRequest.php ---
11:    public function authorize(): bool
16:    /** @return array<string,mixed> */
17:    public function rules(): array
19:        return ['cancellation_reason' => ['required', 'string', 'min:5', 'max:3000']];

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreFieldServiceTeamRequest.php ---
13:    public function authorize(): bool
18:    /** @return array<string,mixed> */
19:    public function rules(): array
22:            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('field_service_teams', 'code')],
23:            'name' => ['required', 'string', 'max:190'],
24:            'team_type' => ['required', Rule::in(array_keys(FieldServiceTeam::typeLabels()))],
25:            'contact_person' => ['nullable', 'string', 'max:190'],
26:            'phone' => ['nullable', 'string', 'max:80'],
27:            'email' => ['nullable', 'email:rfc', 'max:190'],
28:            'vehicle_registration' => ['nullable', 'string', 'max:80'],
29:            'service_area' => ['nullable', 'string', 'max:255'],
30:            'is_active' => ['nullable', 'boolean'],
31:            'notes' => ['nullable', 'string', 'max:5000'],

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/UpdateFieldServiceTeamRequest.php ---
11:final class UpdateFieldServiceTeamRequest extends FormRequest
13:    public function authorize(): bool
18:    /** @return array<string,mixed> */
19:    public function rules(): array
23:            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('field_service_teams', 'code')->ignore($team?->id)],
24:            'name' => ['required', 'string', 'max:190'],
25:            'team_type' => ['required', Rule::in(array_keys(FieldServiceTeam::typeLabels()))],
26:            'contact_person' => ['nullable', 'string', 'max:190'],
27:            'phone' => ['nullable', 'string', 'max:80'],
28:            'email' => ['nullable', 'email:rfc', 'max:190'],
29:            'vehicle_registration' => ['nullable', 'string', 'max:80'],
30:            'service_area' => ['nullable', 'string', 'max:255'],
31:            'is_active' => ['nullable', 'boolean'],
32:            'notes' => ['nullable', 'string', 'max:5000'],

--- REQUEST: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreFieldWorkOrderPartRequest.php ---
12:    public function authorize(): bool { return $this->user()?->hasPermission('service_parts.manage') === true; }
13:    public function rules(): array
16:            'service_part_id' => ['required', 'integer', 'exists:service_parts,id'],
17:            'requested_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
18:            'supply_mode' => ['required', Rule::in(['local_stock', 'external'])],
19:            'notes' => ['nullable', 'string', 'max:3000'],
FIELD_OPERATIONS_REQUEST_CONTRACT_DISCOVERY=PASS

============================================================
5. DATABASE READ-ONLY DOMAIN PROBE
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-field-operations-admin-audit-batch1.20260818-210853.3789243/field-operations-db-probe.php
TABLE_field_service_teams=PASS
TABLE_field_work_orders=PASS
TABLE_field_work_order_attachments=PASS
TABLE_field_work_order_parts=PASS
TABLE_service_parts=PASS
TABLE_service_part_movements=PASS
FIELD_SERVICE_TEAM_COUNT=0
FIELD_WORK_ORDER_COUNT=0
FIELD_WORK_ORDER_PART_COUNT=0
FIELD_WORK_ORDER_ATTACHMENT_COUNT=0
SERVICE_PART_COUNT=0
DATABASE_WRITES_DURING_PROBE=0
FIELD_OPERATIONS_DATABASE_READ_ONLY_PROBE=PASS

============================================================
6. CURRENT ADMIN API + OPENAPI GAP
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-field-operations-admin-audit-batch1.20260818-210853.3789243/openapi-probe.php
OPENAPI_PRE_PARITY=PASS
OPENAPI_ADMIN_FIELD_OPERATIONS_PATH_COUNT=0
OPENAPI_ADMIN_FIELD_OPERATIONS_OPERATION_COUNT=0
OPENAPI_SHARED_FIELD_WORK_ATTACHMENT_PATH_COUNT=1
ADMIN_FIELD_OPERATIONS_RUNTIME_ROUTE_COUNT=0
OPENAPI_ADMIN_FIELD_OPERATIONS_PATH_COUNT=0
OPENAPI_ADMIN_FIELD_OPERATIONS_OPERATION_COUNT=0
OPENAPI_SHARED_FIELD_WORK_ATTACHMENT_PATH_COUNT=1
ADMIN_FIELD_OPERATIONS_API_STATE=ABSENT_CLEAN_GAP

============================================================
7. CURRENT MOBILE ADMIN FIELD OPERATIONS GAP
============================================================
ADMIN_FIELD_OPERATIONS_MOBILE_CANDIDATE_FILE_COUNT_PRESENT=0
ADMIN_HUB_FIELD_OPERATIONS_SIGNAL_COUNT=0
ADMIN_QUERY_KEY_FIELD_OPERATIONS_SIGNAL_COUNT=0
CUSTOMER_FIELD_WORK_ATTACHMENT_MOBILE_SIGNAL_FILE_COUNT=1
ADMIN_FIELD_OPERATIONS_PERMISSION_FOUNDATION=PASS_VIEW_AND_MANAGE
ADMIN_FIELD_OPERATIONS_MOBILE_STATE=ABSENT_CLEAN_GAP

============================================================
8. ATTACHMENT PRIVACY + PERMISSION BOUNDARY
============================================================
--- FIELD WORK ATTACHMENT CONTROLLER SIGNALS ---
10:use Illuminate\Support\Facades\Storage;
19:        $user = $request->user();
20:        $isAdmin = $user->hasRole('admin', 'superadmin');
22:            $access->authorizeView($case, $user);
24:            abort_unless($attachment->visibility === 'public' && $access->canView($case, $user), 404);
26:        abort_unless(Storage::disk('local')->exists($attachment->path), 404);
27:        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
29:            'Cache-Control' => 'private, no-store, max-age=0',
FIELD_WORK_ATTACHMENT_AUTHORIZATION_SIGNAL=PASS_PRESENT
FIELD_WORK_ATTACHMENT_CACHE_POLICY=PASS_PRIVATE_NO_STORE_SIGNAL
PARTS_PERMISSION_BOUNDARY=service_parts.manage
FIELD_OPERATIONS_READ_PERMISSION=field_operations.view
FIELD_OPERATIONS_MANAGE_PERMISSION=field_operations.manage
TEAM_MANAGEMENT_PERMISSION=field_operations.manage

============================================================
9. HISTORICAL API PLAN VS CURRENT WEB AUTHORITY RECONCILIATION
============================================================
HISTORICAL_FIELD_OPERATIONS_API_PLAN_BEGIN
GET    /api/v1/admin/field-work
GET    /api/v1/admin/field-work/{workOrder}
GET    /api/v1/admin/field-service-teams
PATCH  /api/v1/admin/field-work/{workOrder}/schedule
POST   /api/v1/admin/field-work/{workOrder}/en-route
POST   /api/v1/admin/field-work/{workOrder}/on-site
POST   /api/v1/admin/field-work/{workOrder}/complete
POST   /api/v1/admin/field-work/{workOrder}/parts
DELETE /api/v1/admin/field-work/{workOrder}/parts/{part}
POST   /api/v1/admin/field-work/{workOrder}/attachments
GET    /api/v1/admin/field-work/attachments/{attachment}
HISTORICAL_FIELD_OPERATIONS_API_PLAN_END
HISTORICAL_FIELD_OPERATIONS_API_OPERATION_COUNT=11
CURRENT_WEB_EXTRA_CANCEL_ACTION=YES
CURRENT_WEB_EXTRA_PARTS_RESERVE_ACTION=YES
CURRENT_WEB_TEAM_CREATE_ACTION=YES
CURRENT_WEB_TEAM_UPDATE_ACTION=YES
CURRENT_WEB_TEAM_DELETE_ACTION=YES
CURRENT_SHARED_API_FIELD_ATTACHMENT=YES
HISTORICAL_PLAN_SCOPE_STATE=STALE_REQUIRES_CURRENT_WEB_AUTHORITY_RECONCILIATION
API_DESIGN_RULE=DO_NOT_INVENT_STANDALONE_ATTACHMENT_UPLOAD_IF_COMPLETE_REQUEST_IS_CURRENT_AUTHORITY
API_DESIGN_RULE=DO_NOT_DROP_CANCEL_OR_PARTS_RESERVE_IF_CURRENT_WEB_WORKFLOW_REQUIRES_THEM
API_DESIGN_RULE=PART_MUTATIONS_MUST_PRESERVE_service_parts.manage_BOUNDARY
API_DESIGN_RULE=TEAM_MUTATIONS_MUST_NOT_BE_ADDED_BLINDLY_JUST_BECAUSE_WEB_CRUD_EXISTS
API_DESIGN_RULE=REUSE_SHARED_SECURE_ATTACHMENT_DOWNLOAD_WHERE_AUTHORIZATION_SEMANTICS_ARE_SUFFICIENT
API_DESIGN_RULE=REUSE_FIELD_OPERATIONS_SERVICE_AND_SERVICE_PARTS_INVENTORY_SERVICE_FOR_SIDE_EFFECTS

============================================================
10. READ-ONLY IMMUTABILITY + GIT RECERTIFICATION
============================================================
READ_ONLY_HASH_RECERTIFICATION=PASS_NO_MANAGED_SOURCE_DRIFT_DURING_AUDIT
GIT_VISIBLE_STATE=UNCHANGED_DURING_AUDIT

============================================================
11. FINAL FIELD OPERATIONS ADMIN AUDIT DECISION
============================================================
AFTER_SALES_ADMIN_V0_7_PROGRESS=100_PERCENT_COMPLETE
FIELD_OPERATIONS_ADMIN_WORKSTREAM=SELECTED_NEXT
FIELD_OPERATIONS_ADMIN_AUDIT_BATCH1=PASS
WEB_FIELD_OPERATIONS_ROUTE_COUNT=10
WEB_FIELD_SERVICE_TEAM_ROUTE_COUNT=4
SHARED_FIELD_WORK_ATTACHMENT_ROUTE_COUNT=2
ADMIN_FIELD_OPERATIONS_API_STATE=ABSENT_CLEAN_GAP
ADMIN_FIELD_OPERATIONS_MOBILE_STATE=ABSENT_CLEAN_GAP
CURRENT_WEB_AUTHORITY_RECONCILIATION=REQUIRED_BEFORE_MUTATING_API_BATCH
SOURCE_WRITES_DURING_BATCH=0
DATABASE_WRITES_DURING_BATCH=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION=0.7.0
EAS_BUILD=NO
FIELD_OPERATIONS_ADMIN_V0_7_PROGRESS=25_PERCENT_BY_4_GATE_PLAN
MOBILE_V0_7_FIELD_OPERATIONS_ADMIN_AUDIT_BATCH1=PASS
NEXT_ACTION=PREPARE_V0_7_FIELD_OPERATIONS_API_FOUNDATION_BATCH2_WITH_RECONCILED_CURRENT_WEB_SCOPE
REPORT_READY_TO_UPLOAD=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-FIELD-OPERATIONS-ADMIN-AUDIT-BATCH1-20260818-210853.md
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-FIELD-OPERATIONS-ADMIN-AUDIT-BATCH1-20260818-210853.md

PASS: MOBILE v0.7.0 FIELD OPERATIONS ADMIN READ-ONLY AUDIT BATCH 1 COMPLETE

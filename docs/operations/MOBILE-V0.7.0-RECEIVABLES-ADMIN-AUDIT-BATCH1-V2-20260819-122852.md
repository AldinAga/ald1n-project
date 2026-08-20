============================================================
MOBILE v0.7.0 - RECEIVABLES ADMIN READ-ONLY AUDIT - BATCH 1 V2
============================================================
DATE=Wed Aug 19 12:28:53 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-RECEIVABLES-ADMIN-AUDIT-BATCH1-V2-20260819-122852.md
EVIDENCE=/home/icaffeco/backups/releases/mobile-v0.7.0-receivables-admin-audit-batch1-v2-20260819-122852
MODE=READ_ONLY_DISCOVERY_AND_CONTRACT_AUDIT
TARGET_WORKSTREAM=RECEIVABLES_ADMIN
SOURCE_WRITES_EXPECTED=0
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION_CHANGE=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
RERUN_POLICY=SAFE_IDEMPOTENT_READ_ONLY
PRIOR_WORKSTREAM=FIELD_OPERATIONS_ADMIN
EXPECTED_GATE_PLAN=AUDIT_API_FOUNDATION_MOBILE_CLIENT_UI_FINAL_CERTIFICATION

============================================================
0. PREFLIGHT + FIELD OPERATIONS V6 AUTHORITATIVE PREREQUISITE
============================================================
PREFLIGHT_COMMAND_bash=PASS
PREFLIGHT_COMMAND_php=PASS
PREFLIGHT_COMMAND_grep=PASS
PREFLIGHT_COMMAND_sed=PASS
PREFLIGHT_COMMAND_awk=PASS
PREFLIGHT_COMMAND_find=PASS
PREFLIGHT_COMMAND_sha256sum=PASS
PREFLIGHT_COMMAND_sort=PASS
PREFLIGHT_COMMAND_wc=PASS
PREFLIGHT_COMMAND_tr=PASS
PREFLIGHT_COMMAND_head=PASS
PREFLIGHT_COMMAND_tail=PASS
PREFLIGHT_COMMAND_cat=PASS
PREFLIGHT_COMMAND_mktemp=PASS
PREFLIGHT_COMMAND_date=PASS
PREFLIGHT_COMMAND_mkdir=PASS
PREFLIGHT_COMMAND_rmdir=PASS
PREFLIGHT_COMMAND_git=PASS
PREFLIGHT_COMMAND_cmp=PASS
PREFLIGHT_COMMAND_cp=PASS
PREFLIGHT_COMMAND_rm=PASS
PREFLIGHT_COMMAND_cut=PASS
PREFLIGHT_COMMAND_tee=PASS
PREFLIGHT_COMMAND_diff=PASS
PREFLIGHT_COMMAND_timeout=PASS
PREFLIGHT_COMMAND_dirname=PASS
PREFLIGHT_COMMAND_basename=PASS
CONCURRENCY_LOCK=ACQUIRED
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
CURRENT_APP_VERSION=0.7.0
CURRENT_PACKAGE_LOCK_VERSION=0.7.0
FIELD_OPERATIONS_V6_PASS_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-FIELD-OPERATIONS-ADMIN-FINAL-CERTIFICATION-BATCH4-V6-20260819-121410.md
FIELD_OPERATIONS_ADMIN_V0_7_PREREQUISITE=PASS_100_PERCENT_COMPLETE
PRIOR_RECEIVABLES_BATCH1_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-RECEIVABLES-ADMIN-AUDIT-BATCH1-20260819-122521.md
PRIOR_RECEIVABLES_BATCH1_FAILURE=CSV_EXPORT_THROTTLE_RESOLVED_MIDDLEWARE_CLASS_FALSE_NEGATIVE
PRIOR_RECEIVABLES_BATCH1_MUTATION_STATE=READ_ONLY_NO_SOURCE_DATABASE_SCHEMA_MIGRATION_OR_DEPENDENCY_WRITES
RECEIVABLES_BATCH1_V2_FIX=CSV_EXPORT_THROTTLE_CLASSIFIER_ACCEPTS_ALIAS_OR_RESOLVED_LARAVEL_MIDDLEWARE_CLASS

============================================================
1. READ-ONLY AUTHORITY BASELINE + ROUTE CACHE + GIT SNAPSHOT
============================================================
READ_ONLY_AUTHORITY_HASH_BASELINE=PASS
AUTHORITY_BASELINE_FILE_COUNT=29
GIT_BASELINE_CAPTURED=YES
ROUTE_CACHE_BASELINE_FILE_COUNT=0

============================================================
2. CURRENT WEB RECEIVABLES AUTHORITY SURFACE
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000026_create_receivables_collection_beta7_17.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReceivablesController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ReceivablesDoctorCommand.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableCase.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableInstallment.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableContact.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php
RECEIVABLES_CORE_PHP_SYNTAX=PASS_13_FILES
ROUTE_LIST_JSON=PASS_WITHIN_90_SECONDS
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-receivables-admin-audit-batch1-v2.20260819-122852.2532779/receivables-route-probe.php
WEB_ROUTE=GET|HEAD|admin/receivables|admin.receivables.index|App\Http\Controllers\Admin\ReceivablesController@index|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=GET|HEAD|admin/receivables/export.csv|admin.receivables.csv|App\Http\Controllers\Admin\ReceivablesController@csv|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage|Illuminate\Routing\Middleware\ThrottleRequests:exports
WEB_ROUTE=POST|admin/receivables/scan|admin.receivables.scan|App\Http\Controllers\Admin\ReceivablesController@scan|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=PUT|admin/receivables/settings|admin.receivables.settings.update|App\Http\Controllers\Admin\ReceivablesController@updateSettings|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=GET|HEAD|admin/receivables/{receivable}|admin.receivables.show|App\Http\Controllers\Admin\ReceivablesController@show|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=PATCH|admin/receivables/{receivable}|admin.receivables.update|App\Http\Controllers\Admin\ReceivablesController@update|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=POST|admin/receivables/{receivable}/contacts|admin.receivables.contacts.store|App\Http\Controllers\Admin\ReceivablesController@contact|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=PUT|admin/receivables/{receivable}/plan|admin.receivables.plan|App\Http\Controllers\Admin\ReceivablesController@plan|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=POST|admin/receivables/{receivable}/reminder|admin.receivables.reminder|App\Http\Controllers\Admin\ReceivablesController@reminder|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_RECEIVABLES_ROUTE_COUNT=9
WEB_RECEIVABLES_PERMISSION_COUNT=9
WEB_RECEIVABLES_CSV_EXPORT_THROTTLE_COUNT=1
WEB_RECEIVABLES_CSV_EXPORT_THROTTLE_CLASSIFIER=PASS_ALIAS_OR_RESOLVED_CLASS
WEB_RECEIVABLES_REQUIRED_ROUTE_MISSING_COUNT=0
API_ADMIN_RECEIVABLES_ROUTE_COUNT=0
API_ADMIN_RECEIVABLES_GET_ROUTE_COUNT=0
API_ADMIN_RECEIVABLES_MUTATION_ROUTE_COUNT=0
API_ADMIN_RECEIVABLES_SANCTUM_COUNT=0
API_ADMIN_RECEIVABLES_ACTIVE_COUNT=0
API_ADMIN_RECEIVABLES_PERMISSION_COUNT=0
RECEIVABLES_ROUTE_PROBE_FINAL_SENTINEL=PASS
WEB_RECEIVABLES_AUTHORITY=PASS_EXACT_9_ROUTES
WEB_RECEIVABLES_PERMISSION_BOUNDARY=PASS_9_OF_9_receivables.manage
WEB_RECEIVABLES_CSV_EXPORT=PASS_throttle_exports_ALIAS_OR_RESOLVED_CLASS
RECEIVABLES_BATCH1_V2_CSV_THROTTLE_FIX=PASS_ALIAS_OR_RESOLVED_LARAVEL_MIDDLEWARE_CLASS

--- RECEIVABLES CONTROLLER PUBLIC METHODS ---
51:    public function index(Request $request, SettingsService $settings, ReceivablesService $service): View
88:    public function show(Request $request, ReceivableCase $receivable): View
103:    public function update(UpdateReceivableCaseRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
110:    public function plan(StoreReceivablePlanRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
117:    public function contact(StoreReceivableContactRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
126:    public function reminder(SendReceivableReminderRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
133:    public function updateSettings(UpdateReceivableSettingsRequest $request, SettingsService $settings): RedirectResponse
146:    public function scan(Request $request, ReceivablesService $service): RedirectResponse
155:    public function csv(Request $request, ReceivablesService $service): StreamedResponse

============================================================
3. SERVICE AUTHORITY + INTEGRATION + BUSINESS CONTRACT DISCOVERY
============================================================
--- RECEIVABLES SERVICE PUBLIC METHODS / BUSINESS SIGNALS ---
20:    public const STATUSES = ['monitoring', 'contacted', 'promised', 'installment_plan', 'escalated', 'disputed', 'closed'];
22:    public function __construct(
29:    public function ready(): bool
32:            && Schema::hasTable('receivable_installments')
36:    public function ensureForOrder(Order $order, ?User $actor = null): ?ReceivableCase
42:            return $this->syncForOrder($order) ?? $existing;
48:        return DB::transaction(function () use ($order, $actor): ReceivableCase {
50:            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
51:            $existing = ReceivableCase::query()->where('order_id', $locked->id)->lockForUpdate()->first();
58:                'collection_stage' => 0,
71:    public function update(ReceivableCase $case, User $actor, array $data): ReceivableCase
73:        return DB::transaction(function () use ($case, $actor, $data): ReceivableCase {
75:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
84:            if ($status === 'closed' && $remaining > 0.004) {
87:            $promisedAt = filled($data['promised_payment_at'] ?? null)
88:                ? Carbon::parse((string) $data['promised_payment_at'])
90:            if ($status === 'promised' && $promisedAt === null) {
91:                throw ValidationException::withMessages(['promised_payment_at' => 'Za status „Obećana uplata” unesi obećani datum plaćanja.']);
98:                'next_action_at' => filled($data['next_action_at'] ?? null) ? Carbon::parse((string) $data['next_action_at']) : ($promisedAt ?: null),
99:                'promised_payment_at' => $promisedAt,
102:                'closed_at' => $status === 'closed' ? now() : null,
105:            return $locked->fresh(['order.user', 'order.supplier', 'assignee', 'installments', 'contacts.user']) ?? $locked;
109:    /** @param list<array{due_at:string,amount_rsd:mixed,note?:string|null}> $installments */
110:    public function replacePlan(ReceivableCase $case, User $actor, array $installments): ReceivableCase
112:        return DB::transaction(function () use ($case, $actor, $installments): ReceivableCase {
114:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
115:            if (!$locked->order instanceof Order) throw ValidationException::withMessages(['installments' => 'Porudžbina nije dostupna.']);
117:            if ($remaining <= 0.004) throw ValidationException::withMessages(['installments' => 'Porudžbina nema preostalo dugovanje.']);
118:            if ($installments === [] || count($installments) > 24) throw ValidationException::withMessages(['installments' => 'Unesi između 1 i 24 rate.']);
119:            if (ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->where('paid_amount_rsd', '>', 0)->exists()) {
120:                throw ValidationException::withMessages(['installments' => 'Plan sa već raspoređenom uplatom ne može se zameniti. Evidentiraj novi dogovor u komunikaciji.']);
126:            foreach ($installments as $index => $row) {
131:                    throw ValidationException::withMessages(['installments' => 'Sve rate moraju imati ispravan datum dospeća.']);
133:                if ($amount <= 0) throw ValidationException::withMessages(['installments' => 'Svaka rata mora imati iznos veći od nule.']);
134:                if ($due->lt(today())) throw ValidationException::withMessages(['installments' => 'Datum rate ne može biti u prošlosti.']);
135:                if ($previous !== null && $due->lt($previous)) throw ValidationException::withMessages(['installments' => 'Datumi rata moraju biti hronološki poređani.']);
142:                    'paid_amount_rsd' => 0,
148:                throw ValidationException::withMessages(['installments' => 'Zbir rata mora biti jednak preostalom dugu '.number_format($remaining, 2, ',', '.').' RSD.']);
154:            $metadata['plan_paid_baseline_rsd'] = round((float) $locked->order->paid_total_rsd, 2);
157:                'status' => 'installment_plan',
158:                'collection_stage' => max(1, (int) $locked->collection_stage),
162:                'closed_at' => null,
164:            $this->audit->log('receivable.plan_created', 'Kreiran plan otplate za '.$locked->case_number, $locked, after: ['installments' => $normalized, 'total_rsd' => $sum], user: $actor);
165:            return $this->syncForOrder($locked->order->fresh() ?? $locked->order) ?? $locked->fresh(['installments']) ?? $locked;
170:    public function addContact(ReceivableCase $case, User $actor, array $data): ReceivableContact
172:        return DB::transaction(function () use ($case, $actor, $data): ReceivableContact {
174:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
191:                'collection_stage' => max(1, (int) $locked->collection_stage),
195:                $this->emails->receivableMessage($locked->order, $locked, $contact->subject ?: 'Obaveštenje o plaćanju', $contact->note, 'manual-'.$contact->id);
202:    public function sendReminder(ReceivableCase $case, User $actor, ?string $customMessage = null): ReceivableCase
204:        return DB::transaction(function () use ($case, $actor, $customMessage): ReceivableCase {
206:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
213:            $this->emails->receivableReminder(
233:                'collection_stage' => max((int) $locked->collection_stage, 1),
239:            return $locked->fresh(['order', 'installments', 'contacts.user', 'assignee']) ?? $locked;
243:    /** @return array{examined:int,cases_created:int,reminders:int,closed:int,skipped_promises:int} */
244:    public function runAutomation(bool $force = false): array
246:        $result = ['examined' => 0, 'cases_created' => 0, 'reminders' => 0, 'closed' => 0, 'skipped_promises' => 0];
262:            $case = $this->ensureForOrder($order);
265:            $case = $this->syncForOrder($order->fresh() ?? $order) ?? $case;
266:            if ($case->status === 'closed') {
267:                $result['closed']++;
271:            if (!$force && $this->settings->get('receivables_pause_on_promise', '1') === '1' && $case->promised_payment_at?->isFuture()) {
275:            $stage = $this->eligibleReminderStage($order);
283:    public function syncForOrder(Order $order): ?ReceivableCase
289:        return DB::transaction(function () use ($case, $order): ReceivableCase {
291:            $locked = ReceivableCase::query()->lockForUpdate()->findOrFail($case->id);
293:            $freshOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
296:            $baseline = round((float) ($metadata['plan_paid_baseline_rsd'] ?? 0), 2);
297:            $allocatable = max(0, $paid - $baseline);
299:            $installments = ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->orderBy('sequence_no')->lockForUpdate()->get();
300:            foreach ($installments as $installment) {
301:                $allocated = min((float) $installment->amount_rsd, $allocatable);
302:                $allocatable = max(0, $allocatable - $allocated);
303:                $isPaid = $allocated + 0.004 >= (float) $installment->amount_rsd;
304:                $status = $isPaid ? 'paid' : ($installment->due_at?->isPast() ? 'overdue' : 'pending');
305:                $installment->update([
306:                    'paid_amount_rsd' => round($allocated, 2),
308:                    'paid_at' => $isPaid ? ($installment->paid_at ?: now()) : null,
314:                $locked->update(['status' => 'closed', 'closed_at' => $locked->closed_at ?: now(), 'next_action_at' => null, 'promised_payment_at' => null]);
316:                $nextInstallment = $installments->first(static fn (ReceivableInstallment $item): bool => $item->status !== 'paid');
317:                $nextAction = $locked->promised_payment_at ?: $nextInstallment?->due_at ?: $freshOrder->payment_due_at;
318:                $updates = ['next_action_at' => $nextAction, 'closed_at' => null];
319:                if ($locked->status === 'closed') $updates['status'] = $installments->isNotEmpty() ? 'installment_plan' : 'monitoring';
322:            return $locked->fresh(['order.user', 'order.supplier', 'installments', 'contacts.user', 'assignee']) ?? $locked;
326:    public function remaining(Order $order): float
331:    public function daysOverdue(Order $order): int
337:    public function agingBucket(Order $order): string
351:    private function eligibleReminderStage(Order $order): ?int
389:        return DB::transaction(function () use ($case, $order, $stage, $force): bool {
391:            $locked = ReceivableCase::query()->lockForUpdate()->findOrFail($case->id);
393:            $freshOrder = Order::query()->with(['user.role', 'supplier.role'])->lockForUpdate()->findOrFail($order->id);
397:            if (!$force && ReceivableContact::query()->where('event_key', $eventKey)->exists()) return false;
405:            $queued = $this->emails->receivableReminder($freshOrder, $locked, $stage, $subject, $message);
409:                ['event_key' => $eventKey],
423:            $metadata['last_reminder_event_key'] = $eventKey;
426:                'collection_stage' => max((int) $locked->collection_stage, $this->stageOrdinal($stage)),

--- RECEIVABLES CONTROLLER DELEGATION / VALIDATION SIGNALS ---
16:use App\Services\ReceivablesService;
32:        'contacted' => 'Kontaktiran kupac',
34:        'installment_plan' => 'Plan otplate',
51:    public function index(Request $request, SettingsService $settings, ReceivablesService $service): View
62:                'settings' => $this->settingsValues($settings),
81:            'settings' => $this->settingsValues($settings),
88:    public function show(Request $request, ReceivableCase $receivable): View
91:        $receivable->load(['order.user', 'order.supplier', 'order.documents', 'order.payments', 'assignee', 'creator', 'updater', 'installments', 'contacts.user']);
97:            'agingBucket' => $receivable->order instanceof Order ? app(ReceivablesService::class)->agingBucket($receivable->order) : 'current',
98:            'remaining' => $receivable->order instanceof Order ? app(ReceivablesService::class)->remaining($receivable->order) : 0.0,
103:    public function update(UpdateReceivableCaseRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
106:        $service->update($receivable, $request->user(), $request->validated());
110:    public function plan(StoreReceivablePlanRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
113:        $service->replacePlan($receivable, $request->user(), $request->validated('installments'));
117:    public function contact(StoreReceivableContactRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
120:        $data = $request->validated();
126:    public function reminder(SendReceivableReminderRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
129:        $service->sendReminder($receivable, $request->user(), $request->validated('message'));
133:    public function updateSettings(UpdateReceivableSettingsRequest $request, SettingsService $settings): RedirectResponse
135:        $data = $request->validated();
136:        foreach (['receivables_enabled', 'receivables_auto_create_cases', 'receivables_auto_reminders_enabled', 'receivables_pause_on_promise', 'receivables_send_creator', 'receivables_send_supplier'] as $key) {
139:        $stages = array_values(array_unique(array_map('intval', preg_split('/[\s,;]+/', (string) $data['receivables_reminder_stages']) ?: [])));
141:        $data['receivables_reminder_stages'] = implode(',', $stages);
142:        $settings->putMany($data, $request->user()->id);
146:    public function scan(Request $request, ReceivablesService $service): RedirectResponse
151:            $result['examined'], $result['cases_created'], $result['reminders'], $result['closed'],
155:    public function csv(Request $request, ReceivablesService $service): StreamedResponse
166:            fputcsv($out, ['Predmet', 'Porudžbina', 'Kupac', 'Rok', 'Kašnjenje dana', 'Aging', 'Ukupno RSD', 'Plaćeno RSD', 'Preostalo RSD', 'Status', 'Odgovorno lice', 'Sledeća akcija', 'Obećana uplata'], ';', '"', '\\');
171:                fputcsv($out, [
188:        }, 'potrazivanja-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
194:        return $request->validate([
264:            'plans' => (clone $base)->where('status', 'installment_plan')->count(),
314:    private function settingsValues(SettingsService $settings): array
317:            'receivables_enabled' => '1', 'receivables_auto_create_cases' => '1', 'receivables_auto_reminders_enabled' => '1',
318:            'receivables_due_soon_days' => '3', 'receivables_reminder_stages' => '0,3,7,15,30', 'receivables_pause_on_promise' => '1',
321:        foreach ($defaults as $key => $default) $defaults[$key] = (string) $settings->get($key, $default);
328:        return ['active' => 0, 'promised' => 0, 'plans' => 0, 'actions_overdue' => 0, 'total_remaining' => 0.0, 'overdue_remaining' => 0.0];

--- RECEIVABLES DOCTOR SIGNALS ---
14:    protected $signature = 'app:receivables-doctor {--scan : Pokreni kontrolisanu proveru predmeta i automatskih opomena}';
27:            if (!Schema::hasTable($table)) {
30:            $missing = array_values(array_filter($columns, static fn (string $column): bool => !Schema::hasColumn($table, $column)));
34:        $permission = Schema::hasTable('permissions') && DB::table('permissions')->where('slug','receivables.manage')->exists();
35:        $this->line(($permission?'<fg=green>PASS</>':'<fg=red>FAIL</>').' receivables.manage');
37:        $schedule = is_file(base_path('routes/console.php')) && str_contains((string) file_get_contents(base_path('routes/console.php')), "app:automation-run");

--- ORDER EMAIL OUTBOX RECEIVABLES SIGNALS ---
88:    public function receivableReminder(Order $order, ReceivableCase $case, int $stage, string $subject, string $message): int
90:        return $this->queueReceivableEvent($order, $case, 'receivable_reminder', $subject, $message, 'stage-'.$stage.'-'.($order->payment_due_at?->format('Ymd') ?? 'none'), $stage);
93:    public function receivableMessage(Order $order, ReceivableCase $case, string $subject, string $message, string $fingerprint): int
95:        return $this->queueReceivableEvent($order, $case, 'receivable_message', $subject, $message, $fingerprint, null);
98:    private function queueReceivableEvent(
139:                $dedupe = hash('sha256', implode('|', [
144:                    ['dedupe_key' => $dedupe],
160:                        'metadata_json' => ['receivable_case_id' => $case->id, 'case_number' => $case->case_number, 'stage' => $stage],
168:                'receivable_case_id' => $case->id,
212:                        ['dedupe_key' => $fingerprint],

--- PAYMENT / ORDER / DOCUMENT RECEIVABLES INTEGRATION SIGNALS ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:24:        private readonly ReceivablesService $receivables,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:257:            $this->receivables->syncForOrder($order->fresh() ?? $order);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:30:        private readonly ReceivablesService $receivables,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:219:                $this->receivables->ensureForOrder($fresh, $user);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:28:        private readonly ReceivablesService $receivables,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:219:                    $this->receivables->ensureForOrder($document->order, $actor);

--- OPERATIONAL AUTOMATION RECEIVABLES SIGNALS ---
29:        private readonly ReceivablesService $receivables,
34:        $task = $digest ? 'operational_daily_digest' : 'operational_alert_scan';
65:                $summary = $this->scan($force);
108:    private function scan(bool $force): array
117:        $reminderHours = $this->intSetting('automation_alert_reminder_hours', 24, 1, 720);
136:                $reminderHours,
162:                [$created, $sent] = $this->recordOrderAlert($order, 'processing_overdue', 'danger', 'Rok obrade je istekao', $order->order_number.' nije obrađena u očekivanom roku.', $force, $reminderHours, $seen);
166:                [$created, $sent] = $this->recordOrderAlert($order, 'shipping_overdue', 'danger', 'Rok slanja je istekao', $order->order_number.' nije poslata u očekivanom roku.', $force, $reminderHours, $seen);
190:                    $reminderHours,
219:                if ($this->shouldNotify($alert, $force, $reminderHours)) {
265:                if ($this->shouldNotify($alert, $force, $reminderHours)) {
316:                if ($this->shouldNotify($alert, $force, $reminderHours)) {
364:                if ($this->shouldNotify($alert, $force, $reminderHours)) {
409:                if ($this->shouldNotify($alert, $force, $reminderHours)) {
453:                if ($this->shouldNotify($alert, $force, $reminderHours)) {
488:                if ($this->shouldNotify($alert, $force, $reminderHours)) {
523:                if ($this->shouldNotify($alert, $force, $reminderHours)) {
563:                if ($this->shouldNotify($alert, $force, $reminderHours)) {
586:        if ($this->receivables->ready()) {
587:            $collection = $this->receivables->runAutomation($force);
588:            $examined += $collection['examined'];
589:            $notifications += $collection['reminders'];
590:            $types['receivable_cases_created'] = $collection['cases_created'];
591:            $types['receivable_reminders'] = $collection['reminders'];
592:            $types['receivable_cases_closed'] = $collection['closed'];
604:    private function recordOrderAlert(Order $order, string $type, string $severity, string $title, string $message, bool $force, int $reminderHours, array &$seen): array
620:        if ($this->shouldNotify($alert, $force, $reminderHours)) {
659:    private function shouldNotify(OperationalAlert $alert, bool $force, int $reminderHours): bool
663:            || $alert->last_notified_at->lte(now()->subHours($reminderHours));

--- CONSOLE SCHEDULER RECEIVABLES/AUTOMATION SIGNALS ---
13:    ->dailyAt('06:15')
16:Schedule::command('app:automation-run')
17:    ->hourlyAt(10)
20:Schedule::command('app:automation-run --digest')
21:    ->dailyAt('08:05')
39:Schedule::command('app:scheduler-heartbeat')
44:    ->dailyAt('03:45')
47:Schedule::command('app:backup-create --type=daily')
48:    ->dailyAt('02:30')
56:    ->dailyAt('07:45')
RECEIVABLES_SERVICE_AUTHORITY=PASS_EXISTING_BUSINESS_LOGIC_REUSE_REQUIRED
RECEIVABLES_PAYMENT_ALLOCATION_AUTHORITY=PASS_VERIFIED_PAYMENT_TO_OLDEST_INSTALLMENTS_CONTRACT_PRESENT
RECEIVABLES_OUTBOX_AUTHORITY=PASS_REMINDER_AND_MESSAGE_PATHS_PRESENT
RECEIVABLES_AUTOMATION_AUTHORITY=PASS_EXISTING_OPERATIONAL_AUTOMATION_INTEGRATION_PRESENT
NEW_ADMIN_API_DIRECT_MODEL_BUSINESS_MUTATION_ALLOWED=NO

============================================================
4. MIGRATION + TEST + UI CONTRACT RECERTIFICATION
============================================================
RECEIVABLES_PERMISSION_SEEDER=PASS_SCHEMA_AWARE_UPDATED_AT
RECEIVABLES_MIGRATION_CONTRACT=PASS_BETA7_17_RECOVERY_SAFE_SCHEMA_AWARE
RECEIVABLES_WEB_UI_CONTRACT=PASS_AGING_PLAN_COMMUNICATION
RECEIVABLES_STATIC_REGRESSION_CONTRACT=PASS_3_KEY_TEST_MARKERS

============================================================
5. DATABASE READ-ONLY SCHEMA + INTEGRITY PROBE
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-receivables-admin-audit-batch1-v2.20260819-122852.2532779/receivables-db-probe.php
RECEIVABLES_DB_PROBE_RUNTIME=STARTING_TIMEOUT_90_SECONDS
RECEIVABLES_DB_PROBE_STAGE=COMPOSER_AUTOLOAD_BEGIN
RECEIVABLES_DB_PROBE_STAGE=COMPOSER_AUTOLOAD_PASS
RECEIVABLES_DB_PROBE_STAGE=BOOTSTRAP_BEGIN
RECEIVABLES_DB_PROBE_STAGE=BOOTSTRAP_PASS
RECEIVABLES_DB_PROBE_STAGE=CONNECTION_PASS
RECEIVABLES_TABLE_COUNT=3
TABLE_RECEIVABLE_CASES=PASS_EXISTS
TABLE_RECEIVABLE_INSTALLMENTS=PASS_EXISTS
TABLE_RECEIVABLE_CONTACTS=PASS_EXISTS
COLUMNS_RECEIVABLE_CASES=id,order_id,case_number,status,collection_stage,assigned_to,next_action_at,promised_payment_at,last_contact_at,last_reminder_stage,last_reminder_at,internal_note,metadata_json,created_by,updated_by,closed_at,created_at,updated_at
COLUMNS_RECEIVABLE_INSTALLMENTS=id,receivable_case_id,sequence_no,due_at,amount_rsd,paid_amount_rsd,status,paid_at,note,created_at,updated_at
COLUMNS_RECEIVABLE_CONTACTS=id,receivable_case_id,user_id,order_email_outbox_id,event_key,channel,direction,subject,note,visible_to_customer,is_automatic,contacted_at,created_at,updated_at
RECEIVABLES_REQUIRED_COLUMN_MISSING_COUNT=0
RECEIVABLES_MIGRATION_RECORD_COUNT=1
RECEIVABLES_MANAGE_PERMISSION_COUNT=1
RECEIVABLE_CASE_COUNT=1
RECEIVABLE_INSTALLMENT_COUNT=0
RECEIVABLE_CONTACT_COUNT=1
DUPLICATE_RECEIVABLE_CASE_NUMBER_GROUPS=0
ORPHAN_RECEIVABLE_CASES=0
ORPHAN_RECEIVABLE_INSTALLMENTS=0
ORPHAN_RECEIVABLE_CONTACTS=0
CASE_STATUS_CONTACTED=1
COLLECTION_STAGE_1=1
DATABASE_WRITES_DURING_RECEIVABLES_DB_PROBE=0
RECEIVABLES_DB_PROBE_FINAL_SENTINEL=PASS
RECEIVABLES_DB_PROBE_RUNTIME=PASS_COMPLETED_WITHIN_90_SECONDS
RECEIVABLES_DATABASE_SCHEMA=PASS_3_TABLES_REQUIRED_COLUMNS
RECEIVABLES_DATABASE_INTEGRITY=PASS_UNIQUE_CASE_NUMBERS_ZERO_ORPHANS
DATABASE_WRITES_DURING_DB_PROBE=0

============================================================
6. CUSTOMER-SAFE RECEIVABLES BOUNDARY
============================================================
--- ORDER DETAIL CUSTOMER RECEIVABLES SIGNALS ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:40:        $this->loadOne($order, 'receivableCase', 'receivable_cases', ['id', 'order_id'], $warnings);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:41:        if ($order->relationLoaded('receivableCase') && $order->receivableCase !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:44:                if ($this->hasColumns('receivable_installments', ['id', 'receivable_case_id'])) $relations[] = 'installments';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:45:                if ($this->hasColumns('receivable_contacts', ['id', 'receivable_case_id'])) $relations['contacts'] = static fn ($query) => $query->where('visible_to_customer', true)->orderByDesc('contacted_at')->limit(20);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:46:                if ($relations !== []) $order->receivableCase->load($relations);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:48:                $warnings[] = 'Plan naplate trenutno nije potpuno dostupan.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:49:                $this->safeLog($order, 'receivableCase', $exception);
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:146:    public function receivableCase(): HasOne
CUSTOMER_RECEIVABLES_ORDER_DETAIL=PASS_EXISTING_SUMMARY_INTEGRATION
CUSTOMER_RECEIVABLES_CONTACT_PRIVACY=PASS_VISIBLE_TO_CUSTOMER_ONLY
ADMIN_API_PRIVACY_RULE=DO_NOT_EXPOSE_INTERNAL_ACTOR_OR_RAW_OUTBOX_STORAGE_DETAILS_TO_CUSTOMER_SURFACE

============================================================
7. CURRENT ADMIN API + OPENAPI GAP
============================================================
OPENAPI_PRE_PARITY=PASS_CANONICAL_CMS_MOBILE
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-receivables-admin-audit-batch1-v2.20260819-122852.2532779/receivables-openapi-probe.php
OPENAPI_ADMIN_RECEIVABLES_PATH_COUNT=0
OPENAPI_ADMIN_RECEIVABLES_OPERATION_COUNT=0
OPENAPI_BROAD_RECEIVABLE_TEXT_SIGNAL_COUNT=14
RECEIVABLES_OPENAPI_PROBE_FINAL_SENTINEL=PASS
ADMIN_RECEIVABLES_RUNTIME_ROUTE_COUNT=0
OPENAPI_ADMIN_RECEIVABLES_PATH_COUNT=0
OPENAPI_ADMIN_RECEIVABLES_OPERATION_COUNT=0
ADMIN_RECEIVABLES_API_STATE=ABSENT_CLEAN_GAP

============================================================
8. CURRENT MOBILE RECEIVABLES ADMIN GAP
============================================================
MOBILE_RECEIVABLES_CANDIDATE_ABSENT=/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts
MOBILE_RECEIVABLES_CANDIDATE_ABSENT=/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx
MOBILE_RECEIVABLES_CANDIDATE_ABSENT=/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx
ADMIN_RECEIVABLES_MOBILE_CANDIDATE_FILE_COUNT_PRESENT=0
ADMIN_ACCESS_RECEIVABLES_MANAGE_SIGNAL_COUNT=1
ADMIN_HUB_RECEIVABLE_SIGNAL_COUNT=0
ADMIN_QUERY_KEY_RECEIVABLE_SIGNAL_COUNT=0
ADMIN_API_RECEIVABLE_SIGNAL_COUNT=1
DEDICATED_ADMIN_RECEIVABLES_API_PATH_SIGNAL_FILE_COUNT=0
MOBILE_BROAD_RECEIVABLE_SIGNAL_FILE_COUNT=8
ADMIN_RECEIVABLES_MOBILE_STATE=ABSENT_CLEAN_GAP_WITH_PERMISSION_FOUNDATION

--- MOBILE RECEIVABLES BROAD SIGNAL SAMPLE ---
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-api.ts:15:  | 'receivables'
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-access.ts:38:  'receivables.manage',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:7:  | 'receivables'
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:148:  receivables: AdminReportReceivables;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:82:  receivable_updates: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:144:                  label="Otvoreno potraživanje"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:146:                  meta={`${report.receivables.open_orders} otvorenih porudžbina`}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:31:  { key: 'receivable_updates', title: 'Potraživanja', copy: 'Rate, naplata i dospela potraživanja.' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:118:  const receivable = asRecord(data.receivable);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:249:      {receivable ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:252:          <DetailRow label="Status" value={text(receivable, 'status')} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:253:          <DetailRow label="Sledeca akcija" value={formatDateTime(text(receivable, 'next_action_at'))} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:254:          <DetailRow label="Obecano placanje" value={formatDateTime(text(receivable, 'promised_payment_at'))} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:66:  { value: 'receivables', label: 'Potraživanja' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1084:  const receivables = report.receivables;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1090:        <MetricCard label="Otvorene porudžbine" value={String(receivables.open_orders)} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1091:        <MetricCard label="Ukupno otvoreno" value={formatMoney(receivables.outstanding_rsd, 'RSD')} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1095:        {Object.entries(receivables.aging).map(([key, value]) => (

============================================================
9. CURRENT WEB AUTHORITY TO FUTURE ADMIN API RECONCILIATION
============================================================
CURRENT_WEB_RECEIVABLES_OPERATION_COUNT=9
CURRENT_WEB_RECEIVABLES_SCOPE_BEGIN
GET    /admin/receivables
GET    /admin/receivables/export.csv
PUT    /admin/receivables/settings
POST   /admin/receivables/scan
GET    /admin/receivables/{receivable}
PATCH  /admin/receivables/{receivable}
PUT    /admin/receivables/{receivable}/plan
POST   /admin/receivables/{receivable}/contacts
POST   /admin/receivables/{receivable}/reminder
CURRENT_WEB_RECEIVABLES_SCOPE_END
API_DESIGN_RULE=REUSE_RECEIVABLES_SERVICE_AS_FINAL_BUSINESS_AUTHORITY
API_DESIGN_RULE=REUSE_ORDER_EMAIL_OUTBOX_FOR_REMINDERS_AND_MESSAGES
API_DESIGN_RULE=REUSE_VERIFIED_PAYMENT_LEDGER_ALLOCATION_DO_NOT_CREATE_SYNTHETIC_PAYMENTS
API_DESIGN_RULE=PRESERVE_RECEIVABLES_MANAGE_PERMISSION_ON_ALL_ADMIN_OPERATIONS
API_DESIGN_RULE=PRESERVE_CUSTOMER_VISIBLE_TO_CUSTOMER_CONTACT_FILTER
API_DESIGN_RULE=DO_NOT_RUN_AUTOMATION_OR_SEND_REMINDERS_DURING_READ_ONLY_AUDIT
API_DESIGN_RULE=SERVER_DRIVEN_AGING_STAGE_STATUS_AND_SETTINGS_VALUES
API_DESIGN_RULE=NO_NEW_NATIVE_DEPENDENCY_REQUIRED_FOR_RECEIVABLES_FOUNDATION

============================================================
10. READ-ONLY IMMUTABILITY + ROUTE CACHE + GIT RECERTIFICATION
============================================================
READ_ONLY_HASH_RECERTIFICATION=PASS_NO_AUTHORITY_SOURCE_DRIFT
ROUTE_CACHE_STATE=PASS_EXACTLY_PRESERVED
GIT_VISIBLE_STATE=UNCHANGED_DURING_AUDIT

============================================================
11. FINAL RECEIVABLES ADMIN AUDIT DECISION
============================================================
PRODUCT_VARIANTS_DECOMMISSION_PROGRESS=100_PERCENT_COMPLETE
FIELD_OPERATIONS_ADMIN_V0_7_PROGRESS=100_PERCENT_COMPLETE
FIELD_OPERATIONS_V6_AUTHORITATIVE_PREREQUISITE=PASS
RECEIVABLES_ADMIN_WORKSTREAM=SELECTED_NEXT
RECEIVABLES_ADMIN_AUDIT_BATCH1=PASS
RECEIVABLES_ADMIN_AUDIT_BATCH1_V2=PASS
PRIOR_RECEIVABLES_BATCH1_FAILURE=CSV_EXPORT_THROTTLE_RESOLVED_MIDDLEWARE_CLASS_FALSE_NEGATIVE
RECEIVABLES_BATCH1_V2_CSV_THROTTLE_FIX=PASS_ALIAS_OR_RESOLVED_LARAVEL_MIDDLEWARE_CLASS
WEB_RECEIVABLES_AUTHORITY=PASS_EXACT_9_ROUTES
RECEIVABLES_SERVICE_AUTHORITY=PASS_EXISTING_BUSINESS_LOGIC_REUSE_REQUIRED
RECEIVABLES_DATABASE_SCHEMA=PASS_3_TABLES_REQUIRED_COLUMNS
RECEIVABLES_DATABASE_INTEGRITY=PASS_UNIQUE_CASE_NUMBERS_ZERO_ORPHANS
CUSTOMER_RECEIVABLES_CONTACT_PRIVACY=PASS_VISIBLE_TO_CUSTOMER_ONLY
ADMIN_RECEIVABLES_API_STATE=ABSENT_CLEAN_GAP
ADMIN_RECEIVABLES_MOBILE_STATE=ABSENT_CLEAN_GAP_WITH_PERMISSION_FOUNDATION
SOURCE_WRITES_DURING_BATCH=0
DATABASE_WRITES_DURING_BATCH=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION=0.7.0
EAS_BUILD=NO
RECEIVABLES_ADMIN_V0_7_PROGRESS=25_PERCENT_BY_4_GATE_PLAN
MOBILE_V0_7_RECEIVABLES_ADMIN_AUDIT_BATCH1=PASS
MOBILE_V0_7_RECEIVABLES_ADMIN_AUDIT_BATCH1_V2=PASS
NEXT_ACTION=PREPARE_V0_7_RECEIVABLES_ADMIN_API_FOUNDATION_BATCH2_WITH_RECONCILED_CURRENT_WEB_SCOPE
REPORT_READY_TO_UPLOAD=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-RECEIVABLES-ADMIN-AUDIT-BATCH1-V2-20260819-122852.md
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-RECEIVABLES-ADMIN-AUDIT-BATCH1-V2-20260819-122852.md

PASS: MOBILE v0.7.0 RECEIVABLES ADMIN READ-ONLY AUDIT BATCH 1 V2 COMPLETE

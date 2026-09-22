============================================================
289 - MOBILE v1.0.0 PUSH FOREGROUND + STARTUP PERFORMANCE READ-ONLY AUDIT - BATCH67
============================================================
DATE=Fri Aug 28 15:45:13 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=MEASURE_AUTHENTICATED_STARTUP_IO_DEVICE_PUSH_REGISTRATION_SPLASH_READINESS_AND_FOREGROUND_PUSH_NETWORK_FANOUT_BEFORE_NEXT_OPTIMIZATION_PATCH
SOURCE_MUTATION=NO
DATABASE_WRITES=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
EAS_BUILD_COMMANDS_RUN=0
BUILD14_CREATED=NO
DEVICE_RUNTIME_TIMING=NOT_FAKED_STATIC_AND_SERVER_SOURCE_AUDIT_ONLY

============================================================
0. AUTHORITY PREFLIGHT
============================================================
REPORT288_AUTHORITY=PASS_SHA256_309abdb5a9659679172de69c72bcf07e83f08cb93002ea25235be8d7eb39e684
SOURCE_AUTHORITY_PRE=PASS_HEAD_REMOTE_TREES_HTACCESS_AND_STARTUP_TARGET_BLOBS
CANONICAL_BUILD13_PRE=PASS_SHA256

============================================================
1. AUTHENTICATED STARTUP READINESS GRAPH
============================================================
ROOT_APP_READY_MOUNTS_DEVICE_REGISTRAR=YES
ROOT_APP_READY_MOUNTS_PUSH_BRIDGE=YES
SPLASH_HIDE_GATES_AUTH_STATUS_AND_PREFERENCES=YES
AUTH_EXISTING_SESSION_STATUS_AUTHENTICATED_BEFORE_BOOTSTRAP_COMPLETES=YES
AUTH_STATUS_SET_LINE=112
AUTH_BOOTSTRAP_AWAIT_LINE=114
PREFERENCES_USER_ID_DERIVED_FROM_BOOTSTRAP=YES
PREFERENCES_PROVIDER_KEYED_BY_USER_ID=YES
PREFERENCES_SECURESTORE_HYDRATION_AFTER_USER_ID=YES
INDEX_REDIRECT_USES_AUTH_STATUS_ONLY=YES
AUTHENTICATED_APP_LAYOUT_GATE_USES_STATUS_BEFORE_BOOTSTRAP_FIELDS=YES
SPLASH_CAN_HIDE_BEFORE_EXISTING_SESSION_BOOTSTRAP_DATA_READY=YES_STATIC_CONTROL_FLOW
POTENTIAL_STARTUP_DOUBLE_PRESENTATION=ANONYMOUS_PREFS_READY_THEN_AUTH_STATUS_HOME_THEN_BOOTSTRAP_USER_PREFS_REMOUNT

============================================================
2. STARTUP SECURESTORE + DEVICE/PUSH IO GRAPH
============================================================
STARTUP_AUTH_TOKEN_SECURESTORE_GET_CALLS=1
AUTH_PROVIDER_BOOTSTRAP_CALL_SITES=3
DEVICE_REGISTRAR_DEVICE_POST_CALL_SITES=1
DEVICE_REGISTRAR_INSTALLATION_ID_READ_CALLS=1
DEVICE_REGISTRAR_SERVER_DEVICE_ID_WRITE_CALLS=1
PUSH_BRIDGE_PERMISSION_PRECHECK_CALLS=1
PUSH_BRIDGE_REGISTER_CURRENT_DEVICE_CALLS=1
PUSH_SERVICE_PERMISSION_READ_CALL_SITES=2
PUSH_SERVICE_EXPO_TOKEN_CALL_SITES=1
PUSH_SERVICE_SERVER_DEVICE_ID_READ_CALLS=2
PUSH_SERVICE_SERVER_DEVICE_ID_WRITE_CALLS=2
PUSH_SERVICE_DEVICE_PATCH_CALL_SITES=1
PUSH_SERVICE_DEVICE_POST_FALLBACK_CALL_SITES=1
USER_PREFERENCES_SECURESTORE_READ_CALLS=1
PUSH_STARTUP_PERMISSION_STATE_READ_DUPLICATED=YES
DEVICE_METADATA_COLLECTION_DUPLICATED_BETWEEN_REGISTRAR_AND_PUSH=YES
AUTHENTICATED_STARTUP_HAS_TWO_DEVICE_WRITE_PIPELINES=YES
FRESH_LOGIN_RACE_RISK=DEVICE_REGISTRAR_POST_AND_PUSH_UPDATE_OR_REGISTER_CAN_RUN_CONCURRENTLY_AFTER_STATUS_AUTHENTICATED
RETURNING_SESSION_EXPECTED_DEVICE_WRITES=DEVICE_REGISTRAR_POST_PLUS_PUSH_PATCH_WHEN_PERMISSION_GRANTED
FRESH_SESSION_WITHOUT_STORED_SERVER_DEVICE_ID_POTENTIAL_WRITES=TWO_DEVICE_POSTS_UNLESS_BACKEND_UPSERTS_INSTALLATION_ID
PUSH_EXPO_TOKEN_FETCH_ON_EACH_AUTHENTICATED_MOUNT_WHEN_GRANTED=YES

============================================================
3. FOREGROUND PUSH NETWORK FANOUT
============================================================
PUSH_RECEIVE_INVALIDATES_NOTIFICATIONS=YES
PUSH_RECEIVE_REFRESHES_BOOTSTRAP=YES
PUSH_STARTUP_INVALIDATES_DEVICES_AFTER_REGISTRATION=YES
FOREGROUND_PUSH_MINIMUM_NETWORK_REQUESTS=1_BOOTSTRAP
FOREGROUND_PUSH_MAX_IMMEDIATE_NETWORK_REQUESTS=2_BOOTSTRAP_PLUS_NOTIFICATIONS_WHEN_QUERY_ACTIVE
FOREGROUND_PUSH_NOTIFICATIONS_WHEN_QUERY_INACTIVE=MARK_STALE_WITHOUT_IMMEDIATE_REFETCH
UNREAD_ACTION_CONTEXT_CONSUMER_CALLS=2
PUSH_BRIDGE_DIRECT_LOCAL_UNREAD_UPDATE=NO
FOREGROUND_PUSH_UNREAD_CORRECTNESS_AUTHORITY=CURRENTLY_BOOTSTRAP_REFRESH

============================================================
4. CMS DEVICE REGISTRATION IDEMPOTENCY / UPSERT EVIDENCE
============================================================
CMS_INSTALLATION_ID_REFERENCE_COUNT=8
CMS_UPDATE_OR_CREATE_CALL_COUNT=8
CMS_FIRST_OR_CREATE_CALL_COUNT=9
CMS_GENERIC_DEVICES_REFERENCE_COUNT=30
CMS_INSTALLATION_ID_FILES_BEGIN
app/Http/Controllers/Api/V1/MobileDeviceController.php
app/Http/Requests/Api/V1/StoreMobileDeviceRequest.php
app/Http/Resources/Api/V1/MobileDeviceResource.php
app/Models/MobileDevice.php
CMS_INSTALLATION_ID_FILES_END
CMS_UPDATE_OR_CREATE_FILES_BEGIN
app/Console/Commands/CreateSuperAdminCommand.php
app/Console/Commands/RunOperationalAutomationCommand.php
app/Console/Commands/SchedulerHeartbeatCommand.php
app/Http/Controllers/AccountController.php
app/Services/CatalogSyncService.php
app/Services/PortalSessionService.php
app/Services/SettingsService.php
app/Services/UserNotificationPreferenceService.php
CMS_UPDATE_OR_CREATE_FILES_END
CMS_DEVICE_REGISTER_UPSERT_EVIDENCE=LIKELY_PRESENT_REVIEW_REPORT_CONTEXT_BEFORE_PATCH
CMS_DEVICE_RELEVANT_LINES_BEGIN
app/Http/Controllers/Api/V1/MobileDeviceController.php:8:use App\Http\Requests\Api\V1\StoreMobileDeviceRequest;
app/Http/Controllers/Api/V1/MobileDeviceController.php:9:use App\Http\Requests\Api\V1\UpdateMobileDeviceRequest;
app/Http/Controllers/Api/V1/MobileDeviceController.php:10:use App\Http\Resources\Api\V1\MobileDeviceResource;
app/Http/Controllers/Api/V1/MobileDeviceController.php:11:use App\Models\MobileDevice;
app/Http/Controllers/Api/V1/MobileDeviceController.php:22:final class MobileDeviceController extends Controller
app/Http/Controllers/Api/V1/MobileDeviceController.php:26:        $query = $request->user()->mobileDevices()->latest('last_seen_at')->latest('id');
app/Http/Controllers/Api/V1/MobileDeviceController.php:31:        return MobileDeviceResource::collection($query->limit(100)->get());
app/Http/Controllers/Api/V1/MobileDeviceController.php:34:    public function store(StoreMobileDeviceRequest $request): JsonResponse
app/Http/Controllers/Api/V1/MobileDeviceController.php:38:        $device = $user->mobileDevices()->firstOrNew(['installation_id' => $values['installation_id']]);
app/Http/Controllers/Api/V1/MobileDeviceController.php:94:                    $this->revokeDuplicatePushTokens($pushHashToClaim, (int) $user->id, (string) $values['installation_id']);
app/Http/Controllers/Api/V1/MobileDeviceController.php:107:        return (new MobileDeviceResource($device->fresh()))
app/Http/Controllers/Api/V1/MobileDeviceController.php:112:    public function update(UpdateMobileDeviceRequest $request, MobileDevice $mobileDevice): MobileDeviceResource
app/Http/Controllers/Api/V1/MobileDeviceController.php:114:        $this->ensureOwner($request, $mobileDevice);
app/Http/Controllers/Api/V1/MobileDeviceController.php:120:            && (blank($mobileDevice->push_token_hash) || $values['push_provider'] !== $mobileDevice->push_provider)) {
app/Http/Controllers/Api/V1/MobileDeviceController.php:130:            $provider = $values['push_provider'] ?? $mobileDevice->push_provider;
app/Http/Controllers/Api/V1/MobileDeviceController.php:154:            DB::transaction(function () use ($mobileDevice, $attributes, $pushHashToClaim, $request): void {
app/Http/Controllers/Api/V1/MobileDeviceController.php:159:                        (string) $mobileDevice->installation_id,
app/Http/Controllers/Api/V1/MobileDeviceController.php:162:                $mobileDevice->forceFill($attributes)->save();
app/Http/Controllers/Api/V1/MobileDeviceController.php:169:        return new MobileDeviceResource($mobileDevice->fresh());
app/Http/Controllers/Api/V1/MobileDeviceController.php:172:    public function destroy(Request $request, MobileDevice $mobileDevice): JsonResponse
app/Http/Controllers/Api/V1/MobileDeviceController.php:174:        $this->ensureOwner($request, $mobileDevice);
app/Http/Controllers/Api/V1/MobileDeviceController.php:175:        $tokenId = (int) ($mobileDevice->personal_access_token_id ?? 0);
app/Http/Controllers/Api/V1/MobileDeviceController.php:178:        DB::transaction(function () use ($mobileDevice, $tokenId, $user): void {
app/Http/Controllers/Api/V1/MobileDeviceController.php:179:            $mobileDevice->forceFill([
app/Http/Controllers/Api/V1/MobileDeviceController.php:195:    private function ensureOwner(Request $request, MobileDevice $mobileDevice): void
app/Http/Controllers/Api/V1/MobileDeviceController.php:197:        abort_unless((int) $mobileDevice->user_id === (int) $request->user()->id, 404);
app/Http/Controllers/Api/V1/MobileDeviceController.php:202:        MobileDevice::query()
app/Http/Controllers/Api/V1/MobileDeviceController.php:206:                    ->orWhere('installation_id', '!=', $installationId);
app/Http/Requests/Api/V1/StoreMobileDeviceRequest.php:10:final class StoreMobileDeviceRequest extends FormRequest
app/Http/Requests/Api/V1/StoreMobileDeviceRequest.php:20:            'installation_id' => ['required', 'uuid'],
app/Http/Requests/Api/V1/StoreMobileDeviceRequest.php:36:            'installation_id' => strtolower(trim((string) $this->input('installation_id'))),
app/Http/Resources/Api/V1/MobileDeviceResource.php:11:final class MobileDeviceResource extends JsonResource
app/Http/Resources/Api/V1/MobileDeviceResource.php:20:            'installation_id' => $this->installation_id,
app/Models/MobileDevice.php:11:final class MobileDevice extends Model
app/Models/MobileDevice.php:16:        'installation_id',
CMS_DEVICE_RELEVANT_LINES_END

============================================================
5. PUSH PAYLOAD UNREAD / CACHE AUTHORITY AUDIT
============================================================
CMS_FILES_WITH_UNREAD_TEXT=11
CMS_FILES_WITH_EXPO_TEXT=10
CMS_PUSH_NOTIFICATION_CANDIDATE_FILES_BEGIN
app/Console/Commands/DeploymentCheckCommand.php
app/Console/Commands/MobilePushDispatchCommand.php
app/Console/Commands/MobilePushDoctorCommand.php
app/Console/Commands/OperationsDoctorCommand.php
app/Http/Controllers/Admin/ReportController.php
app/Http/Controllers/Api/V1/AccountController.php
app/Http/Controllers/Api/V1/Admin/ReportController.php
app/Http/Controllers/Api/V1/AuthTokenController.php
app/Http/Controllers/Api/V1/GlobalSearchController.php
app/Http/Controllers/Api/V1/MobileDeviceController.php
app/Http/Requests/Api/V1/StoreMobileDeviceRequest.php
app/Http/Requests/Api/V1/UpdateMobileDeviceRequest.php
app/Http/Resources/Api/V1/MobileDeviceResource.php
app/Models/MobileDevice.php
app/Models/MobilePushOutbox.php
app/Services/AccountSessionService.php
app/Services/CustomerPortalAdminService.php
app/Services/ExpoPushTransport.php
app/Services/MobilePushDispatcher.php
app/Services/MobilePushOutboxService.php
app/Services/OperationalNotificationService.php
app/Services/PasswordResetService.php
app/Services/Pdf/BusinessDocumentPdfService.php
app/Services/ProductAnnouncementService.php
app/Services/TotalProductPurgeService.php
CMS_PUSH_NOTIFICATION_CANDIDATE_FILES_END
CMS_PUSH_UNREAD_RELEVANT_LINES_BEGIN
app/Console/Commands/MobilePushDispatchCommand.php:13:    protected $description = 'Pošalji dospele Expo push poruke iz mobilnog outbox-a i proveri dospele push receipte.';
app/Console/Commands/MobilePushDoctorCommand.php:14:    protected $signature = 'app:mobile-push-doctor {--strict : Zahtevaj aktivan MOBILE_PUSH_ENABLED i Expo provider}';
app/Console/Commands/MobilePushDoctorCommand.php:22:            app_path('Services/ExpoPushTransport.php'),
app/Console/Commands/MobilePushDoctorCommand.php:26:            database_path('migrations/2026_08_07_000042_create_mobile_push_outbox_phase3b.php'),
app/Console/Commands/MobilePushDoctorCommand.php:41:        $provider = (string) config('mobile.push.provider', 'expo');
app/Console/Commands/MobilePushDoctorCommand.php:42:        if ($provider !== 'expo') {
app/Console/Commands/MobilePushDoctorCommand.php:43:            $this->error('FAIL MOBILE_PUSH_PROVIDER mora biti expo za ovu fazu.');
app/Console/Commands/MobilePushDoctorCommand.php:46:            $this->info('PASS Expo push provider je izabran.');
app/Console/Commands/MobilePushDoctorCommand.php:60:            $registered = MobileDevice::query()->whereNull('revoked_at')->whereNotNull('push_token_hash')->count();
app/Console/Commands/OperationsDoctorCommand.php:10:use Database\Seeders\CoreAccessSeeder;
app/Console/Commands/OperationsDoctorCommand.php:36:        'commission_status_history' => ['commission_id', 'metadata_json'],
app/Console/Commands/OperationsDoctorCommand.php:38:        'notifications' => ['id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at'],
app/Console/Commands/OperationsDoctorCommand.php:90:            $unread = $actor->unreadNotifications()->count();
app/Console/Commands/OperationsDoctorCommand.php:92:            $this->line(sprintf('Korisnik #%d, provizije=%d, isplaćeno=%.2f EUR, prvi page=%d, nepročitano=%d.', $actor->id, $summary['count'], $summary['paid_eur'], $page->count(), $unread));
app/Http/Controllers/Admin/CustomerPortalController.php:57:                ->withCount(['publicMessages as unread_staff_count' => static fn ($messages) => $messages->whereNull('read_by_staff_at')])
app/Http/Controllers/Admin/CustomerPortalController.php:71:                'unread_messages' => $conversationAvailable ? PortalMessage::query()->where('visibility', 'public')->whereNull('read_by_staff_at')->count() : 0,
app/Http/Controllers/Admin/CustomerPortalController.php:81:        $data = $request->validate([
app/Http/Controllers/Admin/CustomerPortalController.php:90:        $result = $portal->createCustomer($data, $request->user());
app/Http/Controllers/Admin/CustomerPortalController.php:149:        $data = $request->validate([
app/Http/Controllers/Admin/CustomerPortalController.php:153:            'move_related_portal_data' => ['nullable', 'boolean'],
app/Http/Controllers/Admin/CustomerPortalController.php:157:            (int) $data['order_id'],
app/Http/Controllers/Admin/CustomerPortalController.php:158:            (string) $data['reason'],
app/Http/Controllers/Admin/CustomerPortalController.php:160:            $request->boolean('move_related_portal_data'),
app/Http/Controllers/Admin/ReportController.php:40:            return $this->renderProtected($request, $user, $this->fallbackData($request, $filters, $issues));
app/Http/Controllers/Admin/ReportController.php:49:            return $this->renderProtected($request, $user, $this->fallbackData($request, $filters, [
app/Http/Controllers/Admin/ReportController.php:79:            'canExportReports' => $user->hasPermission('reports.export'),
app/Http/Controllers/Admin/ReportController.php:88:            return $this->unavailableExport($issues);
app/Http/Controllers/Admin/ReportController.php:112:            return $this->unavailableExport($issues);
app/Http/Controllers/Admin/ReportController.php:247:    private function fallbackData(Request $request, array $filters, array $issues): array
app/Http/Controllers/Admin/ReportController.php:269:            'canExportReports' => false,
app/Http/Controllers/Admin/ReportController.php:274:    /** @param array<string,mixed> $data */
app/Http/Controllers/Admin/ReportController.php:275:    private function renderProtected(Request $request, User $user, array $data): Response
app/Http/Controllers/Admin/ReportController.php:282:            $html = view('admin.reports.index', $data)->render();
app/Http/Controllers/Admin/ReportController.php:316:    private function unavailableExport(array $issues): Response
app/Http/Controllers/Api/V1/AccountController.php:16:use Illuminate\Database\Eloquent\Model;
app/Http/Controllers/Api/V1/AccountController.php:78:        $data = $request->validated();
app/Http/Controllers/Api/V1/AccountController.php:79:        if (!Hash::check((string) $data['current_password'], (string) $user->password_hash)) {
app/Http/Controllers/Api/V1/AccountController.php:86:            'password_hash' => Hash::make((string) $data['password']),
app/Http/Controllers/Api/V1/AccountController.php:93:                'push_token' => null,
app/Http/Controllers/Api/V1/AccountController.php:94:                'push_token_hash' => null,
app/Http/Controllers/Api/V1/AccountController.php:106:            metadata: ['all_api_tokens_revoked' => true],
app/Http/Controllers/Api/V1/AccountController.php:120:            'data' => $sessions->state($request->user(), $this->currentTokenId($request)),
app/Http/Controllers/Api/V1/AccountController.php:137:            metadata: ['kind' => $kind, 'session_id' => $session, 'reauthenticate' => $result['reauthenticate']],
app/Http/Controllers/Api/V1/AccountController.php:145:            'data' => $result,
app/Http/Controllers/Api/V1/AccountController.php:160:            metadata: $result,
app/Http/Controllers/Api/V1/AccountController.php:168:            'data' => $result,
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:57:                ->withCount(['publicMessages as unread_staff_count' => static fn ($messages) => $messages->whereNull('read_by_staff_at')])
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:65:            'data' => [
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:72:                    'unread_messages' => $conversationAvailable ? PortalMessage::query()->where('visibility', 'public')->whereNull('read_by_staff_at')->count() : 0,
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:85:        $data = $request->validate([
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:95:        $result = $portal->createCustomer($data, $request->user());
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:102:            'data' => $this->userSummary($user),
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:123:            ->map(fn (Order $order): array => $this->orderPayload($order));
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:138:                ->map(fn (Order $order): array => $this->orderPayload($order, true));
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:161:            'data' => [
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:184:            'data' => ['expires_at' => $token->expires_at?->toIso8601String()],
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:196:        $data = $request->validate([
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:200:            'move_related_portal_data' => ['nullable', 'boolean'],
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:205:            (int) $data['order_id'],
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:206:            (string) $data['reason'],
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:207:            (bool) ($data['confirm_reassign'] ?? false),
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:208:            (bool) ($data['move_related_portal_data'] ?? false),
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:216:            'data' => [
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:218:                'order' => $this->orderPayload($result['order']),
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:237:            'data' => $result,
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:262:    private function orderPayload(Order $order, bool $includeOwner = false): array
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:264:        $payload = [
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:273:            $payload['owner'] = $order->user ? [
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:278:        return $payload;
app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php:302:            'unread_staff_count' => (int) ($conversation->unread_staff_count ?? 0),
app/Http/Controllers/Api/V1/Admin/ReportController.php:57:            'data' => $report,
app/Http/Controllers/Api/V1/Admin/ReportController.php:61:                'export' => $user->can('reports.export'),
app/Http/Controllers/Api/V1/Admin/ReportController.php:69:        $user = $this->exportActor($request);
app/Http/Controllers/Api/V1/Admin/ReportController.php:73:            return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:84:            return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:95:        $user = $this->exportActor($request);
app/Http/Controllers/Api/V1/Admin/ReportController.php:99:            return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:110:            return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:121:        $user = $this->operationalExportActor($request, 'reports.export');
app/Http/Controllers/Api/V1/Admin/ReportController.php:122:        if ($reports->readinessIssues() !== []) return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:127:            return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:137:        $user = $this->operationalExportActor($request, 'reports.export');
app/Http/Controllers/Api/V1/Admin/ReportController.php:138:        if ($reports->readinessIssues() !== []) return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:143:            return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:153:        $user = $this->operationalExportActor($request, 'reports.export');
app/Http/Controllers/Api/V1/Admin/ReportController.php:158:            return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:168:        $this->operationalExportActor($request, 'inventory.export');
app/Http/Controllers/Api/V1/Admin/ReportController.php:173:            return $this->unavailableExport();
app/Http/Controllers/Api/V1/Admin/ReportController.php:181:    private function operationalExportActor(Request $request, string $permission): User
app/Http/Controllers/Api/V1/Admin/ReportController.php:209:    private function exportActor(Request $request): User
app/Http/Controllers/Api/V1/Admin/ReportController.php:212:        abort_unless($user->can('reports.export'), 403);
app/Http/Controllers/Api/V1/Admin/ReportController.php:243:    private function unavailableExport(): Response
app/Http/Controllers/Api/V1/AuthTokenController.php:13:use Illuminate\Database\Eloquent\Model;
app/Http/Controllers/Api/V1/AuthTokenController.php:48:            'user' => $this->userPayload($user),
app/Http/Controllers/Api/V1/AuthTokenController.php:58:        return response()->json(['data' => $this->userPayload($user) + [
app/Http/Controllers/Api/V1/AuthTokenController.php:74:                            'push_token' => null,
app/Http/Controllers/Api/V1/AuthTokenController.php:75:                            'push_token_hash' => null,
app/Http/Controllers/Api/V1/AuthTokenController.php:154:    private function userPayload(User $user): array
app/Http/Controllers/Api/V1/BootstrapController.php:14:use Illuminate\Database\Eloquent\Model;
app/Http/Controllers/Api/V1/BootstrapController.php:29:        $unreadCount = 0;
app/Http/Controllers/Api/V1/BootstrapController.php:54:            // Presentation metadata is best-effort; bootstrap/auth must remain available.
app/Http/Controllers/Api/V1/BootstrapController.php:58:            $unreadCount = $user->unreadNotifications()->count();
app/Http/Controllers/Api/V1/BootstrapController.php:77:        return response()->json(['data' => [
app/Http/Controllers/Api/V1/BootstrapController.php:81:            'notification_counts' => [
app/Http/Controllers/Api/V1/BootstrapController.php:82:                'unread' => $unreadCount,
app/Http/Controllers/Api/V1/GlobalSearchController.php:30:        $payload = $search->search($actor, $query, 5);
app/Http/Controllers/Api/V1/GlobalSearchController.php:33:        foreach (($payload['items'] ?? []) as $item) {
app/Http/Controllers/Api/V1/GlobalSearchController.php:57:        foreach (($payload['sections'] ?? []) as $section) {
app/Http/Controllers/Api/V1/GlobalSearchController.php:71:        return $this->respond((string) ($payload['query'] ?? $query), $items, $sections);
app/Http/Controllers/Api/V1/GlobalSearchController.php:76:     * existing Expo Router destination. Search/ranking/permissions stay in the
app/Http/Controllers/Api/V1/GlobalSearchController.php:143:            'data' => [
app/Http/Controllers/Api/V1/MobileDeviceController.php:13:use Illuminate\Database\Eloquent\Model;
app/Http/Controllers/Api/V1/MobileDeviceController.php:14:use Illuminate\Database\QueryException;
app/Http/Controllers/Api/V1/MobileDeviceController.php:66:        if (array_key_exists('push_token', $values)) {
app/Http/Controllers/Api/V1/MobileDeviceController.php:67:            $pushToken = $values['push_token'];
app/Http/Controllers/Api/V1/MobileDeviceController.php:76:            $attributes['push_token'] = $pushToken;
app/Http/Controllers/Api/V1/MobileDeviceController.php:77:            $attributes['push_token_hash'] = $pushHash;
app/Http/Controllers/Api/V1/MobileDeviceController.php:119:            && !array_key_exists('push_token', $values)
app/Http/Controllers/Api/V1/MobileDeviceController.php:120:            && (blank($mobileDevice->push_token_hash) || $values['push_provider'] !== $mobileDevice->push_provider)) {
app/Http/Controllers/Api/V1/MobileDeviceController.php:122:                'push_token' => ['Novi push token je obavezan kada se postavlja ili menja push provider.'],
app/Http/Controllers/Api/V1/MobileDeviceController.php:127:        if (array_key_exists('push_token', $values)) {
app/Http/Controllers/Api/V1/MobileDeviceController.php:128:            $pushToken = $values['push_token'];
app/Http/Controllers/Api/V1/MobileDeviceController.php:136:            $values['push_token_hash'] = $pushHash;
app/Http/Controllers/Api/V1/MobileDeviceController.php:145:            $values['push_token'] = null;
app/Http/Controllers/Api/V1/MobileDeviceController.php:146:            $values['push_token_hash'] = null;
app/Http/Controllers/Api/V1/MobileDeviceController.php:181:                'push_token' => null,
app/Http/Controllers/Api/V1/MobileDeviceController.php:182:                'push_token_hash' => null,
app/Http/Controllers/Api/V1/MobileDeviceController.php:203:            ->where('push_token_hash', $pushHash)
app/Http/Controllers/Api/V1/MobileDeviceController.php:210:                'push_token' => null,
app/Http/Controllers/Api/V1/MobileDeviceController.php:211:                'push_token_hash' => null,
app/Http/Controllers/Api/V1/MobileDeviceController.php:230:        if (in_array($sqlState, ['23000', '23505'], true) && str_contains($message, 'push_token_hash')) {
app/Http/Controllers/Api/V1/MobileDeviceController.php:232:                'push_token' => ['Push token je istovremeno registrovan na drugoj instalaciji. Ponovite registraciju.'],
app/Http/Controllers/Api/V1/NotificationController.php:19:        if ($request->boolean('unread')) {
app/Http/Controllers/Api/V1/NotificationController.php:38:        $count = $request->user()->unreadNotifications()->count();
app/Http/Controllers/Api/V1/NotificationController.php:40:            $request->user()->unreadNotifications()->update(['read_at' => now()]);
app/Http/Controllers/Api/V1/NotificationController.php:43:        return response()->json(['data' => [
app/Http/Controllers/Api/V1/NotificationController.php:45:            'unread' => 0,
app/Http/Controllers/Api/V1/PortalConversationController.php:30:            ->withCount(['publicMessages as unread_count' => static fn ($messages) => $messages
app/Http/Controllers/Api/V1/PortalConversationController.php:52:            'data' => $conversations,
app/Http/Controllers/Api/V1/PortalConversationController.php:65:        $data = $request->validate([
app/Http/Controllers/Api/V1/PortalConversationController.php:72:        if (!empty($data['order_id'])) {
app/Http/Controllers/Api/V1/PortalConversationController.php:76:                ->find((int) $data['order_id']);
app/Http/Controllers/Api/V1/PortalConversationController.php:87:                (string) $data['subject'],
app/Http/Controllers/Api/V1/PortalConversationController.php:88:                (string) $data['body'],
app/Http/Controllers/Api/V1/PortalConversationController.php:95:        return response()->json(['data' => $this->detailPayload($conversation, $user)], 201);
app/Http/Controllers/Api/V1/PortalConversationController.php:114:        return response()->json(['data' => $this->detailPayload($conversation, $user)]);
app/Http/Controllers/Api/V1/PortalConversationController.php:126:        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:10000']]);
app/Http/Controllers/Api/V1/PortalConversationController.php:129:            $conversations->customerReply($conversation, $user, (string) $data['body']);
app/Http/Controllers/Api/V1/PortalConversationController.php:139:        return response()->json(['data' => $this->detailPayload($conversation, $user)]);
app/Http/Controllers/Api/V1/PortalConversationController.php:154:            'unread_count' => (int) ($conversation->unread_count ?? 0),
app/Http/Controllers/Api/V1/PortalConversationController.php:161:    private function detailPayload(PortalConversation $conversation, User $user): array
app/Http/Controllers/DashboardController.php:81:        $portal = $this->portalData($user, $access, $portalService);
app/Http/Controllers/DashboardController.php:115:    private function portalData(User $user, array $access, CustomerPortalService $portalService): array
app/Http/Controllers/DashboardController.php:129:                'unread_messages' => 0,
app/Http/Controllers/DashboardController.php:148:            $data = $portalService->build($user);
app/Http/Controllers/DashboardController.php:149:            $data['notificationPreference'] = $portalService->preference($user);
app/Http/Controllers/DashboardController.php:151:            return array_replace($fallback, $data, [
app/Http/Controllers/NotificationController.php:17:            'unreadCount' => $request->user()->unreadNotifications()->count(),
app/Http/Controllers/NotificationController.php:25:        $url = trim((string) ($entry->data['url'] ?? ''));
app/Http/Controllers/NotificationController.php:31:        $request->user()->unreadNotifications()->update(['read_at' => now()]);
app/Http/Controllers/PortalConversationController.php:26:                ->withCount(['publicMessages as unread_count' => static fn ($query) => $query
app/Http/Controllers/PortalConversationController.php:45:        $data = $request->validate([
app/Http/Controllers/PortalConversationController.php:52:        if (!empty($data['order_id'])) {
app/Http/Controllers/PortalConversationController.php:53:            $order = Order::query()->where('user_id', $user->id)->findOrFail((int) $data['order_id']);
app/Http/Controllers/PortalConversationController.php:59:                (string) $data['subject'],
app/Http/Controllers/PortalConversationController.php:60:                (string) $data['body'],
app/Http/Controllers/PortalConversationController.php:91:        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:10000']]);
app/Http/Controllers/PortalConversationController.php:94:            $service->customerReply($conversation, $request->user(), (string) $data['body']);
app/Http/Requests/Api/V1/StoreMobileDeviceRequest.php:23:            'push_provider' => ['nullable', 'required_with:push_token', Rule::in(['expo', 'fcm', 'apns'])],
app/Http/Requests/Api/V1/StoreMobileDeviceRequest.php:24:            'push_token' => ['nullable', 'required_with:push_provider', 'string', 'max:4096'],
app/Http/Requests/Api/V1/StoreMobileDeviceRequest.php:39:        foreach (['device_name', 'push_provider', 'push_token', 'app_version', 'build_number', 'locale', 'timezone'] as $field) {
app/Http/Requests/Api/V1/UpdateMobileDeviceRequest.php:21:            'push_provider' => ['sometimes', 'nullable', Rule::in(['expo', 'fcm', 'apns'])],
app/Http/Requests/Api/V1/UpdateMobileDeviceRequest.php:22:            'push_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
app/Http/Requests/Api/V1/UpdateMobileDeviceRequest.php:34:        foreach (['device_name', 'push_provider', 'push_token', 'app_version', 'build_number', 'locale', 'timezone'] as $field) {
app/Http/Resources/Api/V1/MobileDeviceResource.php:7:use Illuminate\Database\Eloquent\Model;
app/Http/Resources/Api/V1/MobileDeviceResource.php:24:            'push_registered' => filled($this->push_token_hash),
app/Models/MobileDevice.php:7:use Illuminate\Database\Eloquent\Model;
app/Models/MobileDevice.php:8:use Illuminate\Database\Eloquent\Relations\BelongsTo;
app/Models/MobileDevice.php:20:        'push_token',
app/Models/MobileDevice.php:21:        'push_token_hash',
CMS_PUSH_UNREAD_RELEVANT_LINES_END
PUSH_PAYLOAD_UNREAD_AUTHORITY=REQUIRES_REPORT_LINE_REVIEW_NO_ASSUMPTION

============================================================
6. STARTUP QUERY / STORAGE CALL SITE INVENTORY
============================================================
SECURESTORE_GETITEM_SOURCE_CALL_COUNT=4
SECURESTORE_SETITEM_SOURCE_CALL_COUNT=4
SECURESTORE_DELETEITEM_SOURCE_CALL_COUNT=2
API_DEVICE_REGISTER_SOURCE_CALL_COUNT=2
API_DEVICE_UPDATE_SOURCE_CALL_COUNT=2
API_BOOTSTRAP_SOURCE_CALL_COUNT=3
GET_EXPO_PUSH_TOKEN_SOURCE_CALL_COUNT=1
GET_PUSH_PERMISSIONS_SOURCE_CALL_COUNT=2
STARTUP_TARGET_CALL_SITES_BEGIN
src/features/auth/auth-provider.tsx:59:    const next = await api.auth.bootstrap();
src/features/auth/auth-provider.tsx:87:      const data = await api.auth.bootstrap();
src/features/auth/auth-provider.tsx:89:      setStatus('authenticated');
src/features/auth/auth-provider.tsx:106:      const token = await tokenStore.get();
src/features/auth/auth-provider.tsx:112:      setStatus('authenticated');
src/features/auth/auth-provider.tsx:114:        const data = await api.auth.bootstrap();
src/features/device/device-registrar.tsx:8:import { getInstallationId, setServerDeviceId } from '@/lib/storage';
src/features/device/device-registrar.tsx:19:        const installationId = await getInstallationId();
src/features/device/device-registrar.tsx:22:        const device = await api.devices.register({
src/features/device/device-registrar.tsx:31:        if (!cancelled) await setServerDeviceId(device.id);
src/features/notifications/push-notification-bridge.tsx:8:import { getPushPermissionState, registerCurrentDeviceForPush } from '@/features/notifications/push-service';
src/features/notifications/push-notification-bridge.tsx:86:        const permission = await getPushPermissionState();
src/features/notifications/push-service.ts:9:import { getInstallationId, getServerDeviceId, setServerDeviceId } from '@/lib/storage';
src/features/notifications/push-service.ts:33:export async function getPushPermissionState(): Promise<PushPermissionState> {
src/features/notifications/push-service.ts:34:  const permission = await Notifications.getPermissionsAsync();
src/features/notifications/push-service.ts:62:  const installationId = await getInstallationId();
src/features/notifications/push-service.ts:63:  return api.devices.register({
src/features/notifications/push-service.ts:71:  const serverDeviceId = await getServerDeviceId();
src/features/notifications/push-service.ts:74:      return await api.devices.update(serverDeviceId, input);
src/features/notifications/push-service.ts:81:  await setServerDeviceId(device.id);
src/features/notifications/push-service.ts:95:  let permission = await Notifications.getPermissionsAsync();
src/features/notifications/push-service.ts:107:  const token = (await Notifications.getExpoPushTokenAsync({ projectId: projectId() })).data;
src/features/notifications/push-service.ts:117:  await setServerDeviceId(device.id);
src/features/notifications/push-service.ts:122:  const serverDeviceId = await getServerDeviceId();
src/features/notifications/push-service.ts:125:  return api.devices.update(serverDeviceId, {
src/features/preferences/app-preferences.tsx:13:  getUserAppPreferences,
src/features/preferences/app-preferences.tsx:63:    void getUserAppPreferences(userId)
src/app/_layout.tsx:33:    if (status !== 'hydrating' && hydrated) void SplashScreen.hideAsync();
STARTUP_TARGET_CALL_SITES_END

============================================================
7. TARGETED OPTIMIZATION DECISION
============================================================
PRIMARY_OPTIMIZATION_CANDIDATE=COALESCE_DEVICE_IDENTITY_AND_PUSH_REGISTRATION_TO_AVOID_DUPLICATE_AUTHENTICATED_STARTUP_DEVICE_WRITES
PRIMARY_EXPECTED_EFFECT=ONE_DEVICE_IDENTITY_PIPELINE_THEN_OPTIONAL_PUSH_ENRICHMENT_INSTEAD_OF_PARALLEL_POST_PLUS_PATCH_OR_POST
SECONDARY_OPTIMIZATION_CANDIDATE=REMOVE_DUPLICATE_PUSH_PERMISSION_READ_BETWEEN_BRIDGE_PRECHECK_AND_PUSH_SERVICE
TERTIARY_OPTIMIZATION_CANDIDATE=SEPARATE_SESSION_TOKEN_RESOLUTION_FROM_BOOTSTRAP_READY_STATE_TO_AVOID_PREMATURE_AUTHENTICATED_SHELL_PRESENTATION
FOREGROUND_PUSH_CHANGE_RECOMMENDATION=DO_NOT_REMOVE_BOOTSTRAP_REFRESH_UNTIL_REPORT_PROVES_AUTHORITATIVE_UNREAD_DATA_EXISTS_IN_PUSH_PAYLOAD_OR_LIGHTWEIGHT_ENDPOINT
GLOBAL_QUERY_CACHE_CHANGE_RECOMMENDATION=NONE_KEEP_BATCH65_POLICY
NEXT_PATCH_SCOPE_RECOMMENDATION=USE_REPORT289_TO_BUILD_MINIMUM_BATCH68_STARTUP_DEVICE_PUSH_DEDUP_PATCH_WITHOUT_EAS_BUILD_IF_BACKEND_IDEMPOTENCY_AND_VALIDATOR_GUARDS_SUPPORT_IT

============================================================
8. FINAL READ-ONLY CERTIFICATION
============================================================
MOBILE_VALIDATE=PASS
MOBILE_TYPECHECK=PASS_TSC_NO_EMIT
CMS_STATIC_CHECK=PASS_983_OF_983
CLEAN_STABLE_VERIFY=PASS_RUN88
CANONICAL_BUILD13=PASS_PRESERVED_SHA256
SOURCE_AUTHORITY_POST=PASS_ONLY_CANONICAL_HTACCESS_DRIFT
STRICT_PARITY=PRESERVED_62_OF_62_100_PERCENT
PRODUCT_VARIANTS_REINTRODUCED=NO
EAS_BUILD_REQUIRED_NOW=NO
BUILD14_CREATED=NO
OPTIMIZATION_SOURCE_MUTATION_THIS_BATCH=NO
BATCH67_RESULT=PASS_PUSH_FOREGROUND_STARTUP_READ_ONLY_AUDIT_COMPLETE
NEXT_ACTION=ANALYZE_REPORT289_AND_BUILD_MINIMUM_BATCH68_STARTUP_DEVICE_PUSH_OPTIMIZATION_PATCH_WITHOUT_NEW_EAS_BUILD
REPORT289_BODY_SHA256=4f6ca3b68f50adbd3810d127c94f11b081bf6a6ba0077ac5ded19e848768f225

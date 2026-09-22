============================================================
123 - MOBILE v0.9.0 PUSH PRODUCTION + PRODUCT ANNOUNCEMENT READ-ONLY AUDIT - BATCH 8
============================================================
DATE=Sun Aug 23 00:46:35 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/123-MOBILE-V0.9.0-PUSH-PRODUCTION-PRODUCT-ANNOUNCEMENT-READ-ONLY-AUDIT-BATCH8-20260823-004635.md
PURPOSE=AUDIT_PRODUCTION_PUSH_RUNTIME_AND_NEW_PRODUCT_ANNOUNCEMENT_TO_PUSH_INTEGRATION
MODE=READ_ONLY_EXCEPT_OPERATION_REPORT_ARTIFACT
APPLICATION_SOURCE_WRITES_EXPECTED=0
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
OPENAPI_CHANGES=0
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
PUSH_MESSAGES_SENT_BY_AUDIT=0
PUSH_DISPATCH_COMMAND_EXECUTED=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN

============================================================
0. PREFLIGHT + IMMUTABLE SOURCE BASELINE
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/MobilePushOutboxService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/MobilePushDispatcher.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ExpoPushTransport.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAnnouncementService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/MobilePushDoctorCommand.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/MobilePushDispatchCommand.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/Api/V1/NotificationResource.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/config/mobile.php
AUDITED_SOURCE_FILE_COUNT=14
SOURCE_BASELINE_HASH=PASS
MOBILE_VERSION_BASELINE=PASS_0_9_0

============================================================
1. PRODUCTION PUSH CONFIG AUTHORITY - NO SECRET VALUES
============================================================
No syntax errors detected in /tmp/ald1n-mobile-v0.9.0-push-product-announcement-audit.20260823-004635.1028219/config-probe.php
APP_ENV=production
MOBILE_PUSH_ENABLED=true
MOBILE_PUSH_PROVIDER=expo
MOBILE_PUSH_EXPO_ACCESS_TOKEN=EMPTY_OR_MISSING
MOBILE_PUSH_SEND_URL_CONFIGURED=YES
MOBILE_PUSH_RECEIPTS_URL_CONFIGURED=YES
QUEUE_CONNECTION=database
CACHE_STORE=redis
SESSION_DRIVER=file
PUSH_RUNTIME_STATE=ENABLED_EXPO

============================================================
2. PUSH DOCTOR + DISPATCH COMMAND + SCHEDULER VISIBILITY
============================================================
PASS mobile_push_outbox tabela postoji.
PASS Expo push provider je izabran.
PASS Mobile push delivery je aktivan.
Registrovani aktivni push uređaji: 2
Push outbox pending: 0
Push outbox failed: 3
MOBILE_PUSH_DOCTOR_STRICT_RC=0
MOBILE_PUSH_DOCTOR_STRICT=PASS
MOBILE_PUSH_DISPATCH_COMMAND=PASS_REGISTERED
MOBILE_PUSH_DISPATCH_COMMAND_EXECUTED=NO
ARTISAN_SCHEDULE_LIST_RC=0
MOBILE_PUSH_SCHEDULE_ENTRY=PASS_PRESENT
6:  *   * * * *  php artisan app:mobile-push-dispatch --limit=100 .................................................................................... Next Due: za 21 sekundu
LARAVEL_SCHEDULER_CRON=PASS_PRESENT

============================================================
3. PUSH DELIVERY SOURCE CONTRACT
============================================================
--- MobilePushOutboxService relevant signals ---
22:            ->where('notifications_enabled', true)
23:            ->where('push_provider', 'expo')
42:                'event' => $this->nullableText($data['event'] ?? null, 120),
60:            'event',
62:            'route',
63:            'order_id',
65:            'after_sales_case_id',
66:            'warranty_id',
67:            'field_work_order_id',
68:            'commission_id',
76:        if (!isset($result['route'])) {
77:            $route = $this->mobileRoute($data);
78:            if ($route !== null) {
79:                $result['route'] = $route;
90:            'order_id' => '/orders/%d',
91:            'after_sales_case_id' => '/after-sales/%d',
92:            'warranty_id' => '/warranties/%d',
93:            'field_work_order_id' => '/field-work/%d',
94:            'commission_id' => '/commissions/%d',
--- MobilePushDispatcher receipt/retry/invalidation signals ---
17:    /** @return array{processed:int,sent:int,retried:int,failed:int} */
20:        $result = ['processed' => 0, 'sent' => 0, 'retried' => 0, 'failed' => 0];
25:        $this->failStaleProcessingRows();
55:                        'receipt_due_at' => now()->addMinutes(max(1, (int) config('mobile.push.expo.receipt_delay_minutes', 15))),
62:                if (($outcome['error_code'] ?? null) === 'DeviceNotRegistered') {
66:                if ($outcome['status'] === 'retry') {
67:                    if ($this->retry($row, (string) ($outcome['error'] ?? 'Privremena Expo Push greška.'))) {
70:                        $result['failed']++;
75:                $this->fail($row, (string) ($outcome['error'] ?? 'Expo Push poruka je odbijena.'));
76:                $result['failed']++;
78:                if ($this->retry($row, $exception->getMessage())) {
81:                    $result['failed']++;
94:    /** @return array{checked:int,delivered:int,retried:int,failed:int,missing:int} */
97:        $result = ['checked' => 0, 'delivered' => 0, 'retried' => 0, 'failed' => 0, 'missing' => 0];
105:            ->whereNotNull('receipt_due_at')
106:            ->where('receipt_due_at', '<=', now())
115:        $outcome = $this->transport->receipts($rows->pluck('provider_ticket_id')->filter()->values()->all());
117:            $message = (string) ($outcome['error'] ?? 'Expo receipt servis trenutno nije dostupan.');
120:                    'receipt_due_at' => now()->addMinutes(5),
127:        $receipts = $outcome['receipts'] ?? [];
131:            $receipt = $receipts[$ticketId] ?? null;
132:            if (!is_array($receipt)) {
138:            $row->receipt_check_count++;
139:            $row->receipt_checked_at = now();
140:            if (($receipt['status'] ?? null) === 'ok') {
144:                    'receipt_due_at' => null,
151:            $code = trim((string) ($receipt['details']['error'] ?? ''));
152:            $message = trim((string) ($receipt['message'] ?? ($code !== '' ? $code : 'Expo Push receipt je vratio grešku.')));
153:            if ($code === 'DeviceNotRegistered') {
156:            if ($code === 'MessageRateExceeded' && $this->retryFromReceipt($row, $message)) {
161:            $this->fail($row, $message);
162:            $result['failed']++;
168:    private function failStaleProcessingRows(): void
174:                'status' => 'failed',
175:                'failed_at' => now(),
181:    private function retry(MobilePushOutbox $row, string $message): bool
186:            $this->fail($row, $message);
200:    private function retryFromReceipt(MobilePushOutbox $row, string $message): bool
210:            'receipt_due_at' => null,
219:        $row->receipt_check_count++;
220:        $row->receipt_checked_at = now();
223:            $this->fail($row, 'Expo Push receipt nije pronađen pre isteka 24h prozora.');
227:        $row->receipt_due_at = now()->addMinutes(5);
228:        $row->last_error = 'Expo Push receipt još nije dostupan.';
232:    private function fail(MobilePushOutbox $row, string $message): void
235:            'status' => 'failed',
236:            'failed_at' => now(),
237:            'receipt_due_at' => null,
248:            'push_provider' => null,
249:            'push_token' => null,
250:            'push_token_hash' => null,
251:            'notifications_enabled' => false,
--- ExpoPushTransport validation/send signals ---
13:final class ExpoPushTransport
15:    /** @return array{status:'ok'|'retry'|'error',ticket_id?:string,error?:string,error_code?:string} */
16:    public function send(MobilePushOutbox $row): array
21:            return ['status' => 'error', 'error' => 'Push uređaj više nije aktivan ili nema Expo token.', 'error_code' => 'DeviceNotRegistered'];
25:            return ['status' => 'error', 'error' => 'Sačuvani Expo push token nema očekivani format.', 'error_code' => 'DeviceNotRegistered'];
39:            $response = $this->request()->post((string) config('mobile.push.expo.send_url'), $payload);
41:            return ['status' => 'retry', 'error' => $exception->getMessage()];
43:            return ['status' => 'retry', 'error' => $exception->getMessage()];
47:            return ['status' => 'retry', 'error' => 'Expo Push HTTP '.$response->status().'.'];
58:            return ['status' => 'retry', 'error' => 'Expo Push odgovor ne sadrži validan ticket.'];
68:            return ['status' => 'retry', 'error' => $message, 'error_code' => $code];
74:    /** @param list<string> $ticketIds @return array{status:'ok'|'retry'|'error',receipts?:array<string,array<string,mixed>>,error?:string} */
86:            return ['status' => 'retry', 'error' => $exception->getMessage()];
88:            return ['status' => 'retry', 'error' => $exception->getMessage()];
92:            return ['status' => 'retry', 'error' => 'Expo receipt HTTP '.$response->status().'.'];
101:            : ['status' => 'retry', 'error' => 'Expo receipt odgovor nema data mapu.'];
108:            ->timeout(max(3, (int) config('mobile.push.expo.timeout_seconds', 10)));
110:        $accessToken = trim((string) config('mobile.push.expo.access_token', ''));
PUSH_RECEIPT_INVALID_TOKEN_AND_RATE_LIMIT_HANDLING=PASS_SOURCE_SIGNAL

============================================================
4. PRODUCT ANNOUNCEMENT CURRENT CONTRACT + CALL SITES
============================================================
--- ProductAnnouncementService relevant source ---
7:use App\Models\OrderEmailOutbox;
29:    public function queueForNewlyPublished(Product $product, ?User $actor = null): int
67:                ->where('status', 'active')
74:                        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
78:                        $row = OrderEmailOutbox::query()->firstOrCreate(
80:                                'dedupe_key' => hash('sha256', 'product_published|'.$product->id.'|'.$recipient->id),
88:                                'event_type' => 'product_published',
--- queueForNewlyPublished call sites ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:65:        $announcementCount = $announcements->queueForNewlyPublished($product, $request->user());
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:93:            ? $announcements->queueForNewlyPublished($updated, $request->user())
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:207:        $announcementCount = $announcements->queueForNewlyPublished($product, $request->user());
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:235:            ? $announcements->queueForNewlyPublished($updated, $actor)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductStatusService.php:79:            ? $this->announcements->queueForNewlyPublished($updated, $actor)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAnnouncementService.php:29:    public function queueForNewlyPublished(Product $product, ?User $actor = null): int
PRODUCT_ANNOUNCEMENT_EMAIL_OUTBOX=PASS_EXISTING
PRODUCT_ANNOUNCEMENT_PUSH_BRIDGE=MISSING_NO_SOURCE_SIGNAL
WEB_PRODUCT_CREATE_OR_ACTIVATION_ANNOUNCEMENT_HOOK=PASS_PRESENT
MOBILE_ADMIN_PRODUCT_CREATE_ANNOUNCEMENT_HOOK=PASS_PRESENT

============================================================
5. PRODUCT PUSH DEEP-LINK / NOTIFICATION ROUTING AUDIT
============================================================
--- Backend NotificationResource route signals ---
24:            'route' => $this->mobileRoute($data),
36:        $explicit = trim((string) ($data['route'] ?? ''));
42:            'order_id' => '/orders/%s',
43:            'after_sales_case_id' => '/after-sales/%s',
44:            'warranty_id' => '/warranties/%s',
45:            'field_work_order_id' => '/field-work/%s',
46:            'commission_id' => '/commissions/%s',
61:            'order_id' => 'order',
62:            'after_sales_case_id' => 'after_sales_case',
63:            'warranty_id' => 'warranty',
64:            'field_work_order_id' => 'field_work_order',
65:            'commission_id' => 'commission',
79:        unset($data['url'], $data['_in_app'], $data['_email'], $data['_push']);
--- Backend MobilePushOutboxService route signals ---
62:            'route',
76:        if (!isset($result['route'])) {
77:            $route = $this->mobileRoute($data);
78:            if ($route !== null) {
79:                $result['route'] = $route;
--- Mobile notification-routing.ts signals ---
5:      kind: 'order';
21:  orderId?: unknown;
22:  route?: unknown;
83:function orderIdFromRoute(
94:        /^\/orders?\/(\d+)\/?(?:\?.*)?$/i
113:        /^\/after-sales\/(\d+)\/?(?:\?.*)?$/i
133:    'order.reassigned_away'
148:      target.type === 'order'
151:        kind: 'order',
185:      input.orderId
190:      kind: 'order',
195:  const routeOrderId =
196:    orderIdFromRoute(
197:      input.route
200:  if (routeOrderId) {
202:      kind: 'order',
203:      id: routeOrderId
207:  const routeAfterSalesCaseId =
209:      input.route
212:  if (routeAfterSalesCaseId) {
215:      id: routeAfterSalesCaseId
228:    'event' | 'target' | 'route' | 'data'
243:    orderId:
245:        'order_id'
248:    route:
249:      notification.route
266:    orderId:
267:      data['order_id'],
269:    route:
270:      data['route']
--- Mobile PushNotificationBridge signals ---
2:import * as Notifications from 'expo-notifications';
3:import { router } from 'expo-router';
7:import { resolvePushNotificationNavigation } from '@/features/notifications/notification-routing';
8:import { getPushPermissionState, registerCurrentDeviceForPush } from '@/features/notifications/push-service';
21:function openNotificationResponse(response: Notifications.NotificationResponse): void {
22:  const requestId = response.notification.request.identifier;
26:  const data = response.notification.request.content.data ?? {};
30:    router.push({
38:    router.push({
45:  router.push('/notifications');
57:      void queryClient.invalidateQueries({ queryKey: ['notifications'] });
62:    void Notifications.getLastNotificationResponseAsync().then(async (response) => {
63:      if (!response) return;
64:      openNotificationResponse(response);
75:    if (status !== 'authenticated' || !supportedPlatform || !hasFeature('push_registration')) return;
BACKEND_EXPLICIT_PRODUCT_NOTIFICATION_ROUTE_SIGNAL=0
MOBILE_EXPLICIT_PRODUCT_NOTIFICATION_ROUTE_SIGNAL=0
MOBILE_GENERIC_NOTIFICATION_ROUTE_SIGNAL=1
PRODUCT_PUSH_DEEP_LINK_READINESS=GENERIC_ROUTE_PRESENT_BACKEND_PAYLOAD_REVIEW_REQUIRED

============================================================
6. LIVE DATABASE RECIPIENT + OUTBOX STATE - READ ONLY
============================================================
No syntax errors detected in /tmp/ald1n-mobile-v0.9.0-push-product-announcement-audit.20260823-004635.1028219/db-probe.php
DB_DRIVER=mysql
TABLE_USERS=YES
TABLE_MOBILE_DEVICES=YES
TABLE_NOTIFICATION_PREFERENCES=YES
TABLE_MOBILE_PUSH_OUTBOX=YES
TABLE_ORDER_EMAIL_OUTBOX=YES
ACTIVE_USERS=13
MOBILE_DEVICES_TOTAL=11
ACTIVE_EXPO_DEVICE_CANDIDATES=2
ACTIVE_USERS_PUSH_PREF_ENABLED=1
ACTIVE_USERS_PUSH_PREF_DISABLED=8
ACTIVE_USERS_PUSH_PREF_MISSING=4
PUSH_ELIGIBLE_DEVICES_WITH_USER_PREF=1
PUSH_ELIGIBLE_DISTINCT_USERS_WITH_USER_PREF=1
MOBILE_PUSH_OUTBOX_TOTAL=14
MOBILE_PUSH_OUTBOX_STATUS_DELIVERED=11
MOBILE_PUSH_OUTBOX_STATUS_FAILED=3
PRODUCT_PUBLISHED_PUSH_OUTBOX_ROWS=0
PRODUCT_PUBLISHED_EMAIL_OUTBOX_ROWS=232
PRODUCT_PUBLISHED_EMAIL_STATUS_SENT=232
PRODUCT_EMAIL_NEW_ITEMS_ENABLED=1
PRODUCT_EMAIL_NEW_ITEMS_INTERVAL_MINUTES=0

============================================================
7. PRODUCT ANNOUNCEMENT IMPLEMENTATION GAP CLASSIFICATION
============================================================
PUSH_PRODUCTION_RUNTIME=ACTIVE_AND_STRICT_DOCTOR_PASS
CURRENT_PUSH_ELIGIBLE_DEVICE_RESULT=1
CURRENT_PRODUCT_EMAIL_ANNOUNCEMENT_ROWS=232
CURRENT_PRODUCT_PUSH_ANNOUNCEMENT_ROWS=0
NEW_PRODUCT_PUSH_IMPLEMENTATION_REQUIRED=YES
RECOMMENDED_REUSE=OperationalNotificationService_PLUS_MobilePushOutboxService
MOBILE_SOURCE_CHANGE_FOR_PRODUCT_PUSH_DEEP_LINK=UNKNOWN_TEST_GENERIC_ROUTE_CONTRACT
TARGET_DELIVERY_POLICY=ACTIVE_USERS_WITH_PUSH_ENABLED_PREFERENCE_AND_ACTIVE_EXPO_DEVICE_ONLY
TARGET_PRODUCT_TRIGGER=PRODUCT_BECOMES_ACTIVE_PUBLISHED_NOT_DRAFT_SAVE
TARGET_DELIVERY_ARCHITECTURE=DB_OUTBOX_PLUS_SCHEDULED_DISPATCHER_NO_INLINE_EXPO_HTTP
TARGET_DEDUPE=ONE_PRODUCT_PUBLISHED_EVENT_PER_USER_DEVICE_CONTRACT_TO_BE_DEFINED
TARGET_PUSH_COPY=TITLE_NEW_PRODUCT_IN_CATALOG_BODY_PRODUCT_NAME_TAP_OPENS_PRODUCT_DETAIL

============================================================
8. READ-ONLY IMMUTABILITY VERIFICATION
============================================================
AUDITED_APPLICATION_SOURCE_IMMUTABILITY=PASS
DATABASE_WRITES=0_BY_AUDIT
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
PUSH_MESSAGES_SENT_BY_AUDIT=0
PUSH_DISPATCH_COMMAND_EXECUTED=NO
APPLICATION_SOURCE_WRITES=0
OPERATION_REPORT_ARTIFACT_ONLY=/home/icaffeco/ald1n-project/docs/operations/123-MOBILE-V0.9.0-PUSH-PRODUCTION-PRODUCT-ANNOUNCEMENT-READ-ONLY-AUDIT-BATCH8-20260823-004635.md

============================================================
9. FINAL AUDIT STATUS
============================================================
AUDIT_EXECUTION=PASS
MOBILE_V0_9_PUSH_PRODUCTION_PRODUCT_ANNOUNCEMENT_READ_ONLY_AUDIT_BATCH8=PASS
NEXT_ACTION=UPLOAD_REPORT_AND_BUILD_MINIMAL_IMPLEMENTATION_FROM_OBSERVED_RUNTIME_GAPS
EXIT_CODE=0
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/123-MOBILE-V0.9.0-PUSH-PRODUCTION-PRODUCT-ANNOUNCEMENT-READ-ONLY-AUDIT-BATCH8-20260823-004635.md

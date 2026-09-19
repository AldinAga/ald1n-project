# Build18 Customer 360 Data & API Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the canonical Customer 360 admin read model, append-only internal CRM notes, and unlinked-buyer discovery on top of the existing Customer Portal authority, while shipping matching Mobile API/type/query-key parity in the same Batch170 source commit.

**Architecture:** Extend the existing `CustomerPortalController` / `CustomerPortalAdminService` namespace rather than creating a new CRM subsystem. A focused `Customer360Service` composes read-only aggregates and timeline data from canonical domain tables; `customer_crm_notes` is the only new persistence table. Laravel route/schema changes are mirrored immediately into all three OpenAPI copies and the Mobile API contract layer; visible Mobile workspace work remains the mandatory Batch171 continuation and the overall feature is not considered complete before that passes.

**Tech Stack:** Laravel 13 / PHP 8.4, Eloquent, Laravel migrations, Sanctum/permission middleware, existing AuditLogger, OpenAPI YAML, React Native 0.86 / Expo SDK 57 / TypeScript / TanStack Query.

**Spec:** `docs/superpowers/specs/2026-09-19-build18-customer360-data-api-design.md`

## Global Constraints

- Reuse `User`, `Order`, Customer Portal services/routes, After-sales, Warranties, Receivables, and Portal Conversations as canonical authorities.
- Do not create a second customer master table or a parallel `/api/v1/admin/crm` namespace.
- Do not auto-create or auto-match users from Direct Sale / `user_id = null` orders.
- `ManagementReportService` remains the future profitability authority; Batch170 must not calculate COGS, gross margin, net contribution, LTV, GMROI, or inventory-turnover metrics.
- `customer_crm_notes` is append-only through the API: create/read only, no edit/delete endpoint.
- Existing `system.manage_users`, active-auth middleware, `customer_portal` module visibility, and `throttle:admin-write` patterns remain authoritative.
- Product Variants remain fully decommissioned.
- OpenAPI must remain byte-identical in CMS, Mobile, and `packages/api-contract`.
- Every functional Laravel/CMS addition in Batch170 must have matching Mobile API/types/query-key/validator parity in the same source commit.
- Batch171 is a mandatory continuation that exposes these contracts in the visible Mobile Customer 360 workspace; Customer 360 is not feature-complete until Batch171 passes.
- No EAS, OTA, native build, or Google Play action in Batch170.
- Hosting operation-report sequence remains numeric; after Report451, implementation uses the next available report number.
- Bash execution keeps the project guardrails: `clear` immediately after shebang/comments, no `python3`, no `/dev/fd`, no process substitution, no blind `git pull`, explicit RC handling, canonical Node/npm, and known `.htaccess` drift only.

## Review Focus

- A customer with only cancelled/archived/no qualifying orders must return zero revenue/AOV/outstanding and nullable last purchase, without division errors.
- An order whose `paid_total_rsd` exceeds `subtotal_rsd` must contribute zero, never negative, to outstanding balance.
- Unlinked-buyer search must never infer `suggested_user_id`, mutate `orders.user_id`, or return already-linked orders.
- CRM-note text containing leading/trailing whitespace or reaching size limits must be trimmed/validated while audit logging avoids broad duplication of note contents.
- A Laravel route/schema change must fail validation if any OpenAPI copy or Mobile client/type/query-key contract is missing, preventing backend-only drift.

---

### Task 1: Customer CRM note persistence and model authority

**Files:**
- Create: `apps/cms/current/database/migrations/2026_09_19_120000_create_customer_crm_notes_table.php`
- Create: `apps/cms/current/app/Models/CustomerCrmNote.php`
- Modify: `apps/cms/current/app/Models/User.php`
- Create: `apps/cms/current/bin/customer-360-contract-smoke.php`

**Interfaces:**
- Consumes: existing `users.id`, `User` customer/staff roles, Laravel timestamps.
- Produces: `CustomerCrmNote` model with `customer()` and `author()` relations; `User::crmNotes()` relation; schema used by Tasks 2-4.

- [ ] **Step 1: Add RED schema/model assertions to the focused smoke**

Create `apps/cms/current/bin/customer-360-contract-smoke.php` with explicit checks that initially fail because `customer_crm_notes` and `CustomerCrmNote` do not exist. The smoke must count checks/failures and end with a stable summary marker such as:

```php
<?php

declare(strict_types=1);

use App\Models\CustomerCrmNote;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$checks = 0;
$failures = 0;
$check = static function (bool $condition, string $message) use (&$checks, &$failures): void {
    $checks++;
    if ($condition) {
        echo 'PASS '.$message.PHP_EOL;
        return;
    }
    $failures++;
    echo 'FAIL '.$message.PHP_EOL;
};

$check(Schema::hasTable('customer_crm_notes'), 'customer_crm_notes table exists');
$check(class_exists(CustomerCrmNote::class), 'CustomerCrmNote model exists');
$check(method_exists(User::class, 'crmNotes'), 'User exposes crmNotes relation');

echo 'CUSTOMER360_CONTRACT_SMOKE='.$checks.'_CHECKS_'.($checks - $failures).'_PASS_'.$failures.'_FAIL'.PHP_EOL;
exit($failures === 0 ? 0 : 1);
```

- [ ] **Step 2: Run the focused smoke and verify RED**

Run from `apps/cms/current`:

```bash
php bin/customer-360-contract-smoke.php
```

Expected: non-zero RC with the new table/model/relation checks failing; no business-data mutation.

- [ ] **Step 3: Add the additive migration**

Implement exactly one new table:

```php
Schema::create('customer_crm_notes', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('author_user_id')->constrained('users')->restrictOnDelete();
    $table->text('body');
    $table->timestamps();
    $table->index(['user_id', 'created_at']);
});
```

Rollback drops only `customer_crm_notes`.

- [ ] **Step 4: Add the focused model and User relation**

`CustomerCrmNote` must use explicit fillable/casts only for this table and expose:

```php
public function customer(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}

public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'author_user_id');
}
```

`User` gets:

```php
public function crmNotes(): HasMany
{
    return $this->hasMany(CustomerCrmNote::class, 'user_id')->latest('created_at')->latest('id');
}
```

- [ ] **Step 5: Extend the smoke to verify append-only schema semantics**

Add checks that the model maps `user_id`, `author_user_id`, `body`, has no soft-delete contract, and no update/delete route exists yet.

- [ ] **Step 6: Run migration + focused smoke in the safe batch harness**

Use the project’s normal Laravel migration command only after backup/preflight gates in the executable batch. Expected smoke summary after implementation: zero failures.

- [ ] **Step 7: Commit the persistence unit**

```bash
git add apps/cms/current/database/migrations/2026_09_19_120000_create_customer_crm_notes_table.php \
        apps/cms/current/app/Models/CustomerCrmNote.php \
        apps/cms/current/app/Models/User.php \
        apps/cms/current/bin/customer-360-contract-smoke.php
git commit -m "feat(cms): add customer CRM note persistence"
```

### Task 2: Customer 360 read-model service

**Files:**
- Create: `apps/cms/current/app/Services/Customer360Service.php`
- Modify: `apps/cms/current/bin/customer-360-contract-smoke.php`

**Interfaces:**
- Consumes: `User`, operational `Order`, `ProductWarranty`, `AfterSalesCase`, `PortalConversation`, `PortalMessage`, `PortalOrderLinkHistory`, `CustomerCrmNote`.
- Produces: `Customer360Service::build(User $customer): array` and `Customer360Service::unlinkedBuyers(string $search = '', int $limit = 50): Collection`.

- [ ] **Step 1: Add RED aggregate tests to the smoke**

Create fixture-safe assertions that use transaction rollback and dynamically-created rows rather than hard-coded historical IDs. Cover:

```text
orders_count excludes cancelled and archived/non-operational rows
lifetime_revenue_rsd sums qualifying subtotal_rsd
average_order_value_rsd is zero when count=0
outstanding_rsd clamps each order at zero
last_purchase_at is null when no qualifying order exists
```

- [ ] **Step 2: Run the smoke and verify aggregate checks fail before service creation**

```bash
php bin/customer-360-contract-smoke.php
```

Expected: new Customer360 service checks fail; pre-existing checks remain green.

- [ ] **Step 3: Implement `Customer360Service::build()`**

Use `Order::query()->operational()->where('user_id', $customer->id)->where('status', '!=', 'cancelled')` as the commercial base query. Return exactly:

```php
[
    'summary' => [
        'orders_count' => int,
        'lifetime_revenue_rsd' => float,
        'average_order_value_rsd' => float,
        'outstanding_rsd' => float,
        'last_purchase_at' => ?string,
        'active_after_sales_count' => int,
        'active_warranties_count' => int,
        'open_conversations_count' => int,
        'unread_staff_messages_count' => int,
    ],
    'timeline' => array,
    'crm_notes' => array,
]
```

Do not return profitability placeholders.

- [ ] **Step 4: Implement merged timeline without a copied CRM-event table**

Normalize order, order-link-history, CRM-note, after-sales, and portal-conversation events to:

```php
[
    'type' => string,
    'occurred_at' => ?string,
    'title' => string,
    'summary' => ?string,
    'order_id' => ?int,
    'conversation_id' => ?int,
    'after_sales_case_id' => ?int,
    'actor' => ?array,
]
```

Sort descending by `occurred_at`, cap to a server constant such as `TIMELINE_LIMIT = 50`, and never fabricate missing timestamps.

- [ ] **Step 5: Implement `unlinkedBuyers()`**

Query operational orders where `user_id IS NULL`; allow `q` over order number and existing shipping/buyer snapshot columns only. Return canonical order facts and never inferred customer IDs or confidence fields.

- [ ] **Step 6: Add review-focus tests**

Explicitly test overpaid orders, no-order customers, `user_id != null` exclusion from unlinked buyers, and no `suggested_user_id`/match-score keys.

- [ ] **Step 7: Run focused smoke to GREEN**

```bash
php bin/customer-360-contract-smoke.php
```

Expected: all Customer360 service checks pass and test-created rows are rolled back.

- [ ] **Step 8: Commit the read-model unit**

```bash
git add apps/cms/current/app/Services/Customer360Service.php \
        apps/cms/current/bin/customer-360-contract-smoke.php
git commit -m "feat(cms): add Customer 360 read model"
```

### Task 3: CRM-note mutation authority, audit, and controller routes

**Files:**
- Modify: `apps/cms/current/app/Services/CustomerPortalAdminService.php`
- Modify: `apps/cms/current/app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php`
- Modify: `apps/cms/current/routes/api.php`
- Modify: `apps/cms/current/bin/customer-360-contract-smoke.php`

**Interfaces:**
- Consumes: `Customer360Service`, `CustomerCrmNote`, existing `AuditLogger`, `ModuleVisibilityService`, `system.manage_users` route group.
- Produces: extended customer detail payload, `POST /api/v1/admin/customer-portal/users/{user}/crm-notes`, `GET /api/v1/admin/customer-portal/unlinked-buyers`.

- [ ] **Step 1: Add RED route/mutation assertions**

The smoke must prove the two new route names/URIs are initially absent and then verify permission/module/throttle placement after implementation.

- [ ] **Step 2: Add `CustomerPortalAdminService::appendCrmNote()`**

Implement:

```php
public function appendCrmNote(User $customer, string $body, User $actor): CustomerCrmNote
```

Behavior:
- call existing customer-role guard,
- `trim()` body,
- reject length `< 2` or `> 5000` through validation,
- create exactly one `CustomerCrmNote`,
- log `customer_crm.note_created`, referencing note/customer IDs and length rather than duplicating full body in generic audit metadata,
- no update/delete path.

- [ ] **Step 3: Extend customer detail through `Customer360Service`**

Inject `Customer360Service` into `CustomerPortalController::show()` and add:

```php
'customer_360' => $customer360->build($user),
```

Keep existing `customer`, `orders`, `order_search`, `active_web_sessions`, `conversations`, and `status_labels` payloads intact for compatibility.

- [ ] **Step 4: Add CRM-note POST action**

Validate:

```php
'body' => ['required', 'string', 'min:2', 'max:5000']
```

Return HTTP 201 with the serialized note and author summary.

- [ ] **Step 5: Add unlinked-buyers GET action**

Accept optional `q` and return a bounded collection from `Customer360Service::unlinkedBuyers()`. Do not mutate or infer ownership.

- [ ] **Step 6: Add routes inside existing `permission:system.manage_users` group**

```php
Route::get('/customer-portal/unlinked-buyers', [AdminCustomerPortalController::class, 'unlinkedBuyers'])
    ->name('customer-portal.unlinked-buyers.index');
Route::post('/customer-portal/users/{user}/crm-notes', [AdminCustomerPortalController::class, 'storeCrmNote'])
    ->whereNumber('user')
    ->middleware('throttle:admin-write')
    ->name('customer-portal.users.crm-notes.store');
```

Do not introduce `/crm`.

- [ ] **Step 7: Add smoke checks for authorization and append-only policy**

Prove customer role guard, route middleware, 201 creation, audit marker, no CRM-note update/delete route, and unchanged `linkOrder` ownership mutation path.

- [ ] **Step 8: Run focused smoke and CMS static gate**

```bash
php bin/customer-360-contract-smoke.php
php bin/static-check.php
```

Expected: Customer360 smoke zero failures; static gate remains the documented canonical count or the batch explicitly records a justified new count.

- [ ] **Step 9: Commit the HTTP/mutation unit**

```bash
git add apps/cms/current/app/Services/CustomerPortalAdminService.php \
        apps/cms/current/app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php \
        apps/cms/current/routes/api.php \
        apps/cms/current/bin/customer-360-contract-smoke.php
git commit -m "feat(cms): expose Customer 360 admin API"
```

### Task 4: OpenAPI canonical contract

**Files:**
- Modify: `packages/api-contract/openapi.yaml`
- Modify: `apps/cms/current/docs/openapi.yaml`
- Modify: `apps/mobile/current/docs/openapi.yaml`

**Interfaces:**
- Consumes: Task 3 HTTP routes and payload shapes.
- Produces: canonical schemas/routes consumed by Mobile and validators.

- [ ] **Step 1: Add a RED parity/route check before editing YAML**

In the executable batch, assert the new route/schema markers are absent before this task while the Laravel routes already exist, demonstrating contract drift that this task closes.

- [ ] **Step 2: Define schemas in canonical `packages/api-contract/openapi.yaml`**

Add concrete schemas for:

```text
AdminCustomer360Summary
AdminCustomer360TimelineItem
AdminCustomerCrmNote
AdminCustomerCrmNoteCreateRequest
AdminCustomerUnlinkedBuyer
```

Extend the existing Admin Customer Portal detail response with `customer_360`, whose object contains `summary`, `timeline`, and `crm_notes`.

- [ ] **Step 3: Document new routes**

Document:

```text
GET  /api/v1/admin/customer-portal/unlinked-buyers
POST /api/v1/admin/customer-portal/users/{user}/crm-notes
```

Include authentication, `system.manage_users`, customer-role 404/validation behavior, `throttle:admin-write` semantics on POST, and standard error envelopes.

- [ ] **Step 4: Copy canonical OpenAPI bytes to CMS and Mobile**

Use a byte-preserving copy from `packages/api-contract/openapi.yaml` to the other two paths. Do not hand-edit three files independently.

- [ ] **Step 5: Verify exact SHA parity**

```bash
sha256sum packages/api-contract/openapi.yaml \
  apps/cms/current/docs/openapi.yaml \
  apps/mobile/current/docs/openapi.yaml
```

Expected: all three SHA-256 values identical.

- [ ] **Step 6: Commit OpenAPI authority**

```bash
git add packages/api-contract/openapi.yaml \
        apps/cms/current/docs/openapi.yaml \
        apps/mobile/current/docs/openapi.yaml
git commit -m "docs(api): add Customer 360 contracts"
```

### Task 5: Mandatory Mobile contract parity for every new Laravel addition

**Files:**
- Modify: `apps/mobile/current/src/features/admin/customer-portal-admin-api.ts`
- Modify: `apps/mobile/current/src/features/admin/admin-query-keys.ts`
- Modify: `apps/mobile/current/scripts/validate-project.mjs`

**Interfaces:**
- Consumes: Task 4 OpenAPI contract.
- Produces: typed Mobile client access to Customer 360 detail, CRM-note creation, and unlinked buyers; stable query keys for Batch171 UI.

- [ ] **Step 1: Add RED Mobile validator assertions**

Before modifying the client, extend `validate-project.mjs` with checks requiring the new Customer360 types/methods/query keys. Run from `apps/mobile/current` and confirm the new checks fail while existing checks remain intact.

- [ ] **Step 2: Extend TypeScript models**

Add explicit types matching OpenAPI, for example:

```ts
export type AdminCustomer360Summary = {
  orders_count: number;
  lifetime_revenue_rsd: number;
  average_order_value_rsd: number;
  outstanding_rsd: number;
  last_purchase_at: string | null;
  active_after_sales_count: number;
  active_warranties_count: number;
  open_conversations_count: number;
  unread_staff_messages_count: number;
};

export type AdminCustomerCrmNote = {
  id: number;
  body: string;
  author: { id: number; name: string };
  created_at: string | null;
};
```

Also define timeline and unlinked-buyer types, and add `customer_360` to `AdminPortalUserDetail`.

- [ ] **Step 3: Add client methods for every new Laravel route**

Add:

```ts
unlinkedBuyers: async (q = '') => {
  const response = await apiRequest<{ data: AdminCustomerUnlinkedBuyer[] }>(
    `admin/customer-portal/unlinked-buyers${queryString({ q: q || undefined })}`,
  );
  return response.data;
},
appendCrmNote: async (userId: number, body: string) => {
  const response = await apiRequest<{ data: AdminCustomerCrmNote }>(
    `admin/customer-portal/users/${userId}/crm-notes`,
    { method: 'POST', body: { body } },
  );
  return response.data;
},
```

Reuse canonical `apiRequest`; no parallel fetch client.

- [ ] **Step 4: Add query keys used by Batch171**

Add stable keys such as:

```ts
customerPortalUnlinkedBuyers: (q: string) => [...adminQueryKeys.customerPortalRoot(), 'unlinked-buyers', q] as const,
customerPortalUser360: (id: number) => [...adminQueryKeys.customerPortalUserRoot(id), 'customer-360'] as const,
```

Avoid a second CRM query-key family.

- [ ] **Step 5: Run Mobile typecheck and full validator**

Use canonical Node/npm from `apps/mobile/current`:

```bash
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node \
  /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js run typecheck

/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs
```

Expected: typecheck RC 0 and `Ukupno FAIL: 0`.

- [ ] **Step 6: Commit Mobile contract parity**

```bash
git add apps/mobile/current/src/features/admin/customer-portal-admin-api.ts \
        apps/mobile/current/src/features/admin/admin-query-keys.ts \
        apps/mobile/current/scripts/validate-project.mjs
git commit -m "feat(mobile): add Customer 360 API parity"
```

### Task 6: Full Batch170 regression, migration safety, and release checkpoint

**Files:**
- Verify all Task 1-5 files.
- Operation report: `docs/operations/452-BATCH170-CUSTOMER360-DATA-API-IMPLEMENTATION-${TS}.md` generated by the executable batch, assuming Report451 is the PASS predecessor.

**Interfaces:**
- Consumes: all prior tasks.
- Produces: one reviewed Batch170 source state ready for mandatory Batch171 Mobile workspace implementation.

- [ ] **Step 1: Start from exact remote authority and backup policy**

The executable batch must fetch `origin/main`, require local==remote expected head, validate known `.htaccess` drift, preserve/report the current predecessor operation report, and verify exactly two restore-ready Stable backups before migration.

- [ ] **Step 2: Prove migration is additive**

Before applying it, inspect that the migration creates only `customer_crm_notes`. After migration, verify existing `users` and `orders` counts are unchanged and the new table exists. Do not auto-backfill or link orders.

- [ ] **Step 3: Run complete backend gates**

From `apps/cms/current`:

```bash
php bin/customer-360-contract-smoke.php
php bin/static-check.php
```

Expected: zero focused failures; canonical static gate zero failures.

- [ ] **Step 4: Run complete Mobile parity gates**

From `apps/mobile/current`:

```bash
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node \
  /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js run typecheck
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs
```

Expected: RC 0 and `Ukupno FAIL: 0`.

- [ ] **Step 5: Verify OpenAPI parity and negative Product Variants guard**

Require exact three-copy SHA equality and assert no active Product Variant route/type/UI contract was introduced.

- [ ] **Step 6: Review diff scope and staged whitelist**

Only Customer360 migration/model/service/controller/admin-service/routes/smoke, three OpenAPI copies, Mobile Customer Portal API/query keys/validator, and the current operation report policy files may differ. Known `.htaccess` drift must remain unstaged.

- [ ] **Step 7: Remote race guard, final source commit, push, and post-fetch equality**

Use explicit `git fetch origin main`, require remote unchanged, `git diff --cached --check`, commit/push, re-fetch, and require local HEAD == remote HEAD.

- [ ] **Step 8: Record Batch170 as foundation PASS, not overall CRM feature completion**

Final report must state:

```text
BATCH170_RESULT=PASS
CUSTOMER360_BACKEND_FOUNDATION=PASS
MOBILE_CONTRACT_PARITY=PASS
MOBILE_VISIBLE_WORKSPACE=PENDING_MANDATORY_BATCH171
CUSTOMER360_OVERALL_FEATURE_COMPLETE=NO_UNTIL_BATCH171_PASS
PROFITABILITY_AUTHORITY=DEFERRED_TO_MANAGEMENT_REPORT_SERVICE_BATCH172_173
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO
NEXT_ACTION=BATCH171_CUSTOMER360_MOBILE_WORKSPACE
```

- [ ] **Step 9: Do not run EAS/OTA/native build**

Batch170 is API/data-contract work only. Native Build18 remains deferred to the planned release-lock batch.

## Self-Review Result

- Spec coverage: Customer identity reuse, aggregates, append-only notes, timeline, unlinked buyers, permissions, no auto-match, no profitability duplication, OpenAPI, Product Variants guard, and mandatory Laravel-to-Mobile parity all map to explicit tasks.
- Instruction completeness scan: every code-edit step contains concrete route, method, type, or command content; Batch171/172/173 boundaries are explicit feature stages rather than omitted Batch170 work.
- Type consistency: `Customer360Service::build(User): array`, `Customer360Service::unlinkedBuyers(string,int): Collection`, `CustomerPortalAdminService::appendCrmNote(User,string,User): CustomerCrmNote`, and Mobile contract names are consistent across tasks.
- Review Focus coverage: zero-order/AOV, overpayment clamp, no inferred unlinked matches, note validation/audit handling, and backend-to-Mobile parity each have an owning test/gate.
- Scope check: Batch170 remains one coherent foundation subsystem; visible Mobile Customer 360 is intentionally isolated as mandatory Batch171, while profitability remains isolated in Batch172/173 to keep a single financial truth source.

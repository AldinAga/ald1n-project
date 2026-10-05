# Batch516 Financial State Canonicalization Implementation Plan

> Execute task-by-task from fresh repository/runtime authority. Do not assume this planning commit is still HEAD.

**Goal:** Make `order_payments` the sole financial ledger authority and centralize every derived order financial field in one projector, while removing manual payment-status mutation, making IPS reads pure, preserving Mobile/OpenAPI parity, and repairing only safe derived production drift.

**Architecture:** `OrderFinancialStateService` owns derivation/projection of `paid_total_rsd`, `payment_state`, `payment_status`, and `payment_verified_at`. Existing payment/workflow/Direct Sale/amendment services retain ownership of their canonical business mutations but delegate financial projection. `OrderFinancialReconciliationService` performs best-effort downstream IPS/receivables synchronization after canonical state is committed. GET paths never persist IPS state.

**Spec:** `docs/superpowers/specs/2026-10-05-batch516-financial-state-canonicalization-design.md`

## Locked authority and constraints

- Reconstruct fresh GitHub `main`, `docs/operations/000-LATEST.md`, latest operations report, and `AGENTS.md` before execution.
- Current application source authority entering Batch516 is `5ceb1cbc43aaeaa132fe9abc2e4a1ea8e2555d27`; later docs-only commits are not application-source authority.
- App version remains `1.0.0`.
- runtimeVersion remains `1.0.0-build17`.
- Last production Android build remains Build22 / versionCode 22.
- Build23 remains deferred.
- No EAS build, EAS submit, OTA publish, Google Play action, version bump, native dependency change, or runtimeVersion change.
- No schema migration is planned. If schema change becomes necessary, stop before mutation and revise the approved design.
- Product Variants remain decommissioned.
- CMS static target remains exactly `983/983`.
- Production PHPUnit is reported as NOT_RUN unless a real runner is proven available and executed.
- Preserve known worktree/runtime residue; never clean blindly.
- Every executable batch uses backup/rollback, exact scope manifests, live report logging, and state-aware recovery semantics from `AGENTS.md`.

---

## Task 1 - Dependency-free RED contract and canonical projector

**Create**
- `apps/cms/current/app/Services/OrderFinancialStateService.php`
- `apps/cms/current/bin/batch516-financial-state-contract.php`

**Modify**
- repository tests where appropriate for CI/dev coverage

### Required behavior

- [ ] Add RED contract assertions proving the canonical projector does not exist on pre-Batch516 source.
- [ ] Implement `derive(Order $order): array`.
- [ ] Implement `projectLocked(Order $order): Order`.
- [ ] Implement `project(Order $order): Order` with transaction + row lock.
- [ ] Use only verified `order_payments` rows.
- [ ] Refund rows subtract from verified net.
- [ ] Preserve RSD tolerance around 0.004.
- [ ] Cancelled order projects `payment_state=cancelled` without erasing verified net.
- [ ] Map compatibility `payment_status` exactly:
  - cancelled -> cancelled
  - refunded -> refunded
  - paid/overpaid -> paid
  - partial/unpaid -> pending
- [ ] `payment_verified_at` exists only while fully paid/overpaid; preserve existing value while state remains fully paid.
- [ ] Projector has no email, notification, document, receivable, commission, or IPS side effects.

### Tests

Cover unpaid, partial, paid, overpaid, refunded, cancelled with zero payments, cancelled with verified payments, and timestamp preservation/reset.

---

## Task 2 - Replace duplicated financial formulas in runtime mutation flows

**Primary files**
- `OrderPaymentService.php`
- `OrderWorkflowService.php`
- Direct Sale service(s)
- Direct Sale price-correction service
- `OrderAmendmentService.php`
- after-sales refund path if projection is not already delegated through `OrderPaymentService`

### Required behavior

- [ ] `OrderPaymentService` delegates projection to `OrderFinancialStateService::projectLocked()`.
- [ ] Existing `recalculateForAmendmentLocked()` remains as a compatibility delegate, not a second formula.
- [ ] Order cancellation projects canonical financial state inside the locked transaction after operational status changes.
- [ ] COD completion may create the canonical verified payment row but does not manually write derived financial fields.
- [ ] Non-COD completion checks canonical derived state rather than trusting stale cached compatibility status.
- [ ] Immediate Direct Sale creates verified ledger row then projects.
- [ ] Deferred Direct Sale creates no synthetic payment row and projects unpaid/pending.
- [ ] Direct Sale price correction mutates only canonical subtotal/payment facts, then projects.
- [ ] Batch515 amendments remain semantically unchanged and delegate to the projector.
- [ ] No runtime service outside the projector owns the financial-state formula.

---

## Task 3 - Decommission manual payment-status mutation

**Laravel Web/API**
- remove Web `PATCH admin/orders/{order}/payment`;
- remove API `PATCH api/v1/admin/orders/{order}/payment-status`;
- remove corresponding controller actions;
- remove `OrderWorkflowService::updatePaymentStatus()`.

**Mobile**
- remove `paymentStatus()` API client mutation;
- remove manual "Status plaćanja" action panel;
- remove mutation-specific helpers/types while keeping derived display fields.

**OpenAPI**
- remove mutation path/schema references;
- retain readable/filterable `payment_status` and `payment_state`;
- add `refunded` to all valid filter/read domains.

### Acceptance

- [ ] No manual payment-status mutation route exists in Web or API.
- [ ] Real payment actions remain record/verify/reject/void/approved after-sales refund.
- [ ] Mobile parity closes in the same Batch516 acceptance chain.

---

## Task 4 - Split IPS read and write behavior

**Modify**
- `IpsPaymentPayloadService.php`
- user/admin Web order-detail controllers/presenters
- financial mutation/reconciliation callers

### Interface

```php
public function forDisplay(Order $order, ?float $amountRsd = null): ?string;
public function refreshCache(Order $order, ?float $amountRsd = null): ?string;
```

### Required behavior

- [ ] `forDisplay()` computes payload without insert/update/delete.
- [ ] Terminal, zero-outstanding, or non-bank-transfer states return null.
- [ ] GET user/admin order detail uses pure display method.
- [ ] `refreshCache()` is mutation/reconciliation-only.
- [ ] IPS refresh failure never rolls back a valid canonical payment mutation.
- [ ] Failed refresh clears stale derived cache where required by existing Batch511 semantics.

---

## Task 5 - Add downstream reconciliation coordinator

**Create**
- `apps/cms/current/app/Services/OrderFinancialReconciliationService.php`

### Responsibilities

- [ ] refresh/clear IPS derived cache;
- [ ] call `ReceivablesService::syncForOrder()`;
- [ ] log subsystem failures with order ID;
- [ ] never mutate payment ledger;
- [ ] never own payment-state formula;
- [ ] never cause a committed canonical ledger mutation to be rolled back solely because downstream reconciliation fails.

All payment/workflow/Direct Sale/amendment flows invoke reconciliation only after canonical transaction success.

---

## Task 6 - Add dry-run-first financial doctor and safe repair path

**Create**
- Artisan command for:
  - `php artisan app:financial-state-doctor`
  - `php artisan app:financial-state-doctor --order-id=<id>`
  - `php artisan app:financial-state-doctor --apply`

### Default dry-run

Report:
- total Laravel orders;
- verified payment rows;
- derived-field mismatches;
- paid-total versus verified-net mismatches;
- Direct Sale ledger mismatches;
- receivable plan/state/allocation mismatches;
- IPS terminal/amount/outstanding mismatches;
- submitted payments;
- historical provenance anomalies.

### Apply safety

- [ ] Never hardcode Orders 28/134 as the only candidates.
- [ ] Stop before mutation on malformed ledger, paid-total/net mismatch, Direct Sale mismatch, or unexpected anomaly.
- [ ] Allow only derived-field/cache repair when canonical ledger facts are internally consistent.
- [ ] Require verified DB backup immediately before `--apply`.
- [ ] Re-run dry-run after apply and require zero canonical mismatch.
- [ ] Historical COD provenance anomalies remain report-only.

---

## Task 7 - Preserve Laravel validation error bags

Audit normal HTTP order/report rendering and remove explicit replacement of Laravel's session `ViewErrorBag` where it can overwrite validation feedback.

- [ ] User order detail preserves redirect validation errors.
- [ ] Admin order list/detail preserves validation errors.
- [ ] Report HTTP rendering preserves validation errors.
- [ ] CLI-only rendering may inject an empty error bag where no HTTP session exists.

---

## Task 8 - Mobile, OpenAPI and static guard parity

### Mobile
- [ ] Remove manual payment-status mutation UX/API.
- [ ] Keep derived status/state display.
- [ ] Keep record/verify/reject/void payment UX.
- [ ] Typecheck PASS.
- [ ] validator exact zero FAIL.

### OpenAPI
- [ ] Canonical contract removes manual mutation.
- [ ] `refunded` appears in valid payment status/filter domain.
- [ ] all three OpenAPI copies are byte-identical.

### CMS static / contract
- [ ] CMS static remains exactly 983/983; replace stale sentinels instead of only increasing count.
- [ ] Contract asserts projector uniqueness.
- [ ] Contract forbids runtime direct writes to the four derived fields outside explicitly allowed neutral bootstrap/default contexts.
- [ ] Contract asserts manual routes/actions are absent.
- [ ] Contract asserts GET detail does not persist IPS.
- [ ] Contract asserts normal HTTP paths do not force empty `ViewErrorBag`.
- [ ] Contract asserts Product Variants remain decommissioned.

---

## Task 9 - One consolidated production Batch516 runner

**Execution artifact**
- `/home/icaffeco/ald1n-project/incoming/ald1n-batch516-financial-state-canonicalization.sh`

**Success report**
- next sequential operation-report number, title containing `BATCH516-FINANCIAL-STATE-CANONICALIZATION`
- update `docs/operations/000-LATEST.md`

### Preflight before mutation

- [ ] clear terminal;
- [ ] fetch origin/main;
- [ ] record branch/local/remote;
- [ ] safe fast-forward only when ancestry + expected delta are proven;
- [ ] reconstruct `000-LATEST.md`, latest report, `AGENTS.md`, design and implementation plan;
- [ ] classify known worktree residue without deleting it;
- [ ] exact machine-stable path manifests, never `git status --short` as scope authority;
- [ ] temp and locks outside worktree;
- [ ] canonical Node/npm paths;
- [ ] no `python3`, `/dev/fd`, process substitution;
- [ ] no EAS/OTA/Play command path;
- [ ] bind prior authoritative report and source commit.

### TDD/source phase

- [ ] backup every touched tracked file and track every new-file path;
- [ ] run dependency-free RED and prove expected failure reason;
- [ ] apply exact source patch;
- [ ] GREEN contract;
- [ ] PHP lint;
- [ ] route contract;
- [ ] CMS static 983/983;
- [ ] Mobile typecheck;
- [ ] Mobile validator zero FAIL;
- [ ] Expo install check;
- [ ] Expo Doctor;
- [ ] OpenAPI three-copy parity;
- [ ] `git diff --check`;
- [ ] exact source-scope allowlist;
- [ ] PHPUnit truthfully recorded.

### Production-data gate

- [ ] run fresh read-only financial census;
- [ ] stop before DB mutation on unsafe/unexpected anomaly;
- [ ] create and verify manual DB backup;
- [ ] run doctor `--apply` only for allowed derived drift;
- [ ] post-repair dry-run requires zero canonical mismatch;
- [ ] Direct Sale mismatch remains zero;
- [ ] receivable mismatches remain zero;
- [ ] IPS mismatches remain zero.

### Commit/report phase

- [ ] commit source only after all required source + data gates pass;
- [ ] commit message: `refactor(finance): canonicalize order financial state`;
- [ ] push and verify local/remote source authority;
- [ ] refresh Laravel route/runtime cache only as required by changed routes;
- [ ] write immutable operation report;
- [ ] update `000-LATEST.md`;
- [ ] checkpoint docs separately where practical;
- [ ] keep only current/immediate predecessor report locally per hosting policy.

### Required final report markers

```text
BATCH_RESULT=
FAILED_STAGE=
SOURCE_MUTATION=
COMMIT_CREATED=
PUSH_COMPLETED=
SOURCE_COMMIT=
DB_BACKUP=
FINANCIAL_CENSUS_BEFORE=
FINANCIAL_REPAIR_APPLIED=
FINANCIAL_CENSUS_AFTER=
CMS_STATIC=
MOBILE_TYPECHECK=
MOBILE_VALIDATOR=
EXPO_CHECK=
EXPO_DOCTOR=
OPENAPI_PARITY=
PHPUNIT_FEATURE_TESTS=
EAS_BUILD_STARTED=NO
EAS_SUBMIT_STARTED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO
BUILD23_DEFERRED=YES
NEXT_ACTION=
```

## Recovery rule

Do not pre-create V2/V3/V4 variants. If Batch516 fails:
1. read exact operation report;
2. determine whether source mutation, commit, push, DB repair, or cache refresh already succeeded;
3. bind the failed report SHA-256;
4. create one targeted recovery batch that continues from the last verified state and never repeats already-successful work.

## Final acceptance

Batch516 is PASS only when:
- canonical financial mismatch = 0;
- Direct Sale mismatch = 0;
- receivable mismatch = 0;
- IPS mismatch = 0;
- manual payment-status mutation is absent Web/API/Mobile;
- GET order detail is IPS-write-free;
- all derived-state writes are centralized;
- safe cancelled-order drift is repaired without rewriting ledger rows;
- validation errors survive HTTP redirects/renders;
- CMS static = 983/983;
- Mobile/Expo/OpenAPI gates pass;
- no Build23/EAS/OTA/Play action occurred.

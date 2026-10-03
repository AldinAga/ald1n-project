# Batch515 Customer Order Amendment Consolidated Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement one production-safe customer order amendment workflow across Laravel Web and Expo Mobile that allows products, quantities, delivery data, and notes to be edited until shipment, while preserving inventory, document, payment, receivables, commission, concurrency, audit, and admin workflow integrity.

**Architecture:** A focused `OrderAmendmentService` owns the mutation and delegates version checking, immutable document lifecycle, payment recalculation, receivable invalidation, and commission reconciliation to explicit services. One shared `OrderVersionService` supplies optimistic-concurrency tokens to customer and admin flows. Document PDFs are decoupled from live order items through a new immutable `order_document_items` snapshot table before customer amendments are enabled.

**Tech Stack:** Laravel 13 / PHP 8.4 / Eloquent / MySQL; Expo SDK 57 / React Native 0.86.3 / TypeScript; OpenAPI YAML; existing Ald1n static validators and dependency-free PHP contract harnesses.

**Spec:** `docs/superpowers/specs/2026-10-03-batch515-customer-order-amendment-consolidated-workflow-design.md`

## Global Constraints

- Start execution by reconstructing fresh GitHub `main`, `docs/operations/000-LATEST.md`, latest operations reports, and Project rules; never assume this planning commit is still HEAD.
- App version remains `1.0.0`; runtimeVersion remains `1.0.0-build17`; Build23 remains deferred.
- Do not run EAS build, EAS submit, OTA publish, or Google Play actions in Batch515.
- Product Variants stay decommissioned.
- CMS static target remains exactly `983/983`; update an existing sentinel rather than incrementing the count for the UTF-8 fix.
- Production does not currently expose a valid PHPUnit runner; never label PHPUnit PASS unless an actual test binary/command is proven available and executed.
- The production batch may apply exactly one expected Batch515 migration, only after all source gates and DB backup pass.
- No destructive down-migration is executed automatically.
- Existing `apps/cms/current/public/.htaccess`, numbered untracked `docs/operations/*.md`, and known `error_log-*.gz` residue are classified read-only; any other unexpected source/runtime diff blocks execution.
- No intermediate source commit is pushed while tasks are incomplete. The controlled server batch keeps backups/checkpoints and creates one source commit only after all global gates pass, followed by one operations-doc commit.
- All shell status banners and the final PASS/FAIL block write to stderr so terminal visibility does not depend on stdout routing.
- TDD RED commands that are expected to return non-zero must execute inside `if ...; then ... else rc=$?; fi`, never as a simple command under an active ERR trap.

## Review Focus

1. **Two clients editing the same order:** stale `expected_edit_token` must return 409 and produce zero order/item/stock/document/payment side effects — covered in Task 3.
2. **Admin acts on an order changed after page load:** stale confirm/shipment token must block action before status/shipment mutation — covered in Task 5.
3. **Existing issued invoice:** amendment must preserve immutable historical document items, cancel the active document, and allow a later revision without rendering live amended items — covered in Task 2 and Task 4.
4. **Archived/inaccessible existing item:** customer may keep/decrease/remove it but cannot increase it; newly added items must currently be public/accessible — covered in Task 3.
5. **Subtotal changes after partial payment/installment setup:** real payment ledger is preserved, payment state is recalculated, and obsolete installment plan cannot remain mismatched — covered in Task 4.

---

### Task 1: Version Tokens, Request Contract, and Production TDD Harness

**Files:**
- Create: `apps/cms/current/app/Services/OrderVersionService.php`
- Create: `apps/cms/current/app/Http/Requests/UpdateOwnOrderRequest.php`
- Create: `apps/cms/current/bin/batch515-order-amendment-contract.php`
- Create: `apps/cms/current/tests/Feature/OrderAmendmentTest.php`
- Modify: `apps/cms/current/app/Http/Resources/OrderResource.php`

**Interfaces:**
- Produces: `OrderVersionService::token(Order $order): string`
- Produces: `OrderVersionService::assertFresh(Order $order, string $expectedToken, string $field = 'expected_edit_token'): void`
- Produces: `UpdateOwnOrderRequest::rules(): array`
- Produces: customer order resource fields `edit_token: string` and `capabilities.can_amend: bool`
- Consumes later: Tasks 3, 5, and 6 use the same token semantics.

- [ ] **Step 1: Add failing repository feature tests for version/capability rules**

Add tests proving:
- owner open Laravel order exposes non-empty `edit_token` and `can_amend=true`;
- shipped/completed/cancelled/direct-sale/legacy orders expose `can_amend=false`;
- token changes when editable shipping/note/item quantity/order status changes;
- token is deterministic for unchanged order state.

- [ ] **Step 2: Extend the dependency-free Batch515 contract harness in RED mode**

The harness must fail on current main because `OrderVersionService`, amendment request, API PATCH route, document-item snapshot model, Mobile edit screen, stale-admin token fields, and UTF-8 canonical strings are absent. Run it using direct PHP, not PHPUnit.

Run:
`php apps/cms/current/bin/batch515-order-amendment-contract.php`

Expected before implementation: non-zero with explicit `CONTRACT_FAIL` lines.

- [ ] **Step 3: Implement `OrderVersionService`**

Token input is canonical JSON of order ID, status, completed/shipment lock state, editable shipping fields, note, subtotal, updated_at, and item snapshot state ordered deterministically by product/item ID. Use SHA-256 and Unicode-safe JSON flags.

`assertFresh()` throws a conflict-class exception handled as HTTP 409 rather than a 422 validation error.

- [ ] **Step 4: Implement `UpdateOwnOrderRequest` and resource capability**

Rules require:
- `expected_edit_token`;
- at least one item;
- each `product_id` positive integer and distinct;
- each quantity integer >= 1;
- existing shipping field length rules consistent with order creation;
- nullable note capped consistently with existing order create flow.

The request does not authorize payment-method/supplier/sales-channel changes.

- [ ] **Step 5: Run syntax/resource contract checks**

Run PHP lint for the new files and the dependency-free harness. PHPUnit tests remain committed for CI/dev but are not reported as production PASS unless runner availability is independently proven.

---

### Task 2: Immutable Order Document Item Snapshots

**Files:**
- Create: `apps/cms/current/database/migrations/2026_10_03_000200_create_order_document_items_batch515.php`
- Create: `apps/cms/current/app/Models/OrderDocumentItem.php`
- Modify: `apps/cms/current/app/Models/OrderDocument.php`
- Modify: `apps/cms/current/app/Services/OrderDocumentService.php`
- Modify: `apps/cms/current/tests/Feature/ReportsDocumentsSupplierTest.php`

**Interfaces:**
- Produces: `OrderDocument::items(): HasMany`
- Produces: immutable `OrderDocumentItem` snapshot rows
- Produces: `OrderDocumentService::invalidateIssuedForAmendmentLocked(Order $order, User $actor, string $reason): Collection`
- Task 4 consumes the invalidation method inside the amendment transaction.

- [ ] **Step 1: Add failing document lifecycle tests**

Add tests proving:
- issuing a document stores document-item rows;
- PDF render data comes from document-item snapshots after live order items change;
- amendment invalidation cancels issued confirmation/proforma/invoice;
- later issue creates revision N+1 and `supersedes_document_id`;
- cancelled historical document retains its immutable item snapshot.

- [ ] **Step 2: Implement the migration**

Create `order_document_items` with:
- `order_document_id` FK cascade;
- sequence number;
- nullable product ID;
- SKU/name snapshots;
- quantity;
- unit/line RSD values;
- unique `(order_document_id, sequence_no)`;
- product index.

Backfill existing document rows from each document's current order items during migration and make the process idempotent for partial/retry-safe deployment.

- [ ] **Step 3: Implement document model relations and issuance snapshot**

`OrderDocumentService::issue()` writes the document item snapshots in the same transaction as the document itself. Existing revision behavior remains authoritative.

- [ ] **Step 4: Switch PDF rendering to immutable document items**

`OrderDocumentService::render()` loads `items` on the document and passes those rows to `BusinessDocumentPdfService`; remove the live `order.items` dependency from document line rendering.

- [ ] **Step 5: Implement amendment invalidation primitive**

`invalidateIssuedForAmendmentLocked()` locks issued documents for the order, marks them cancelled with reason exactly:

`Porudžbina je izmenjena od strane kupca pre slanja.`

It records audit/history in-transaction and schedules notification/email only after commit. It must not auto-issue replacement documents.

- [ ] **Step 6: Verify migration statically before any production apply**

Run:
- PHP lint;
- migration `--pretend` or equivalent schema SQL inspection supported by the production runtime;
- direct contract harness assertions for table/model/relation/render-source semantics.

Expected: no production DB mutation at this task checkpoint.

---

### Task 3: Transactional Customer Amendment Core and Inventory Delta

**Files:**
- Create: `apps/cms/current/app/Services/OrderAmendmentService.php`
- Modify: `apps/cms/current/app/Services/OrderService.php` only if a small existing snapshot helper must be exposed/reused; do not duplicate product snapshot logic
- Modify: `apps/cms/current/tests/Feature/OrderAmendmentTest.php`

**Interfaces:**
- Produces: `OrderAmendmentService::canAmend(Order $order, User $actor): bool`
- Produces: `OrderAmendmentService::amend(Order $order, User $actor, array $data, string $idempotencyKey): Order`
- Consumes: `OrderVersionService`, `IdempotencyService`, `CatalogAccessService`, current order snapshot/commission calculation helpers
- Task 4 extends the same transaction with document/payment/receivable/commission reconciliation.

- [ ] **Step 1: Add failing core amendment tests**

Cover:
- owner succeeds; non-owner 404;
- shipped/completed/cancelled/direct-sale/legacy rejected;
- stale token returns 409 with zero writes;
- retry with same idempotency key/payload replays without duplicate stock movements;
- same key with changed payload is rejected by existing idempotency semantics;
- quantity increase consumes only delta;
- decrease/remove returns only delta;
- insufficient stock rolls back entire amendment;
- duplicate product IDs rejected;
- order cannot end with zero items;
- existing line preserves price/purchase/commission snapshots;
- newly added line takes current catalog snapshot;
- confirmed amendment returns status to processing;
- archived/inaccessible existing item may stay/decrease/remove but not increase;
- newly added/increased item must be currently accessible/public.

- [ ] **Step 2: Implement `canAmend()`**

Require ownership, Laravel source, non-direct-sale, status `new|processing|confirmed`, no completion, no shipment row, and no shipped/cancelled lock state.

- [ ] **Step 3: Implement idempotent locked amendment entrypoint**

Use existing `IdempotencyService::run()` with per-order scope `order.amend:{order_id}`. Inside that transaction:
- lock order;
- reload required relations;
- re-check editability;
- verify `expected_edit_token`;
- lock affected product rows in ascending ID order.

- [ ] **Step 4: Implement delta inventory and item rebuild**

For each product, compute `new_quantity - old_quantity`.
- positive delta validates current accessibility/public state and available stock before decrement;
- negative delta increments stock;
- zero creates no movement.

Write deterministic `order_amendment` stock movements with unique event keys derived from amendment idempotency identity + product ID.

Preserve commercial snapshots on existing lines; use the same canonical snapshot logic as order creation for new lines.

- [ ] **Step 5: Recalculate subtotal/status and write audit/history**

Recompute line totals/subtotal. If prior status was confirmed and any editable field/item changed, move to processing and add order status history explaining re-confirmation is required. Write `order.customer_amended` audit metadata with before/after fields, item deltas, and subtotal delta.

- [ ] **Step 6: Verify RED-to-GREEN core contract**

Run dependency-free contract harness and PHP lint. If a real PHPUnit runner exists in the environment, execute only the new feature test and record actual evidence; otherwise mark it NOT_RUN, never PASS.

---

### Task 4: Documents, Payments, Receivables, and Commission Reconciliation

**Files:**
- Modify: `apps/cms/current/app/Services/OrderAmendmentService.php`
- Modify: `apps/cms/current/app/Services/OrderPaymentService.php`
- Modify: `apps/cms/current/app/Services/ReceivablesService.php`
- Modify: `apps/cms/current/app/Services/CommissionWorkflowService.php`
- Modify: `apps/cms/current/tests/Feature/OrderAmendmentTest.php`
- Modify: `apps/cms/current/tests/Feature/ReceivablesCollectionTest.php`
- Modify: `apps/cms/current/tests/Feature/OperationalOrdersCommissionsTest.php`

**Interfaces:**
- Produces: `OrderPaymentService::recalculateForAmendmentLocked(Order $order): void`
- Produces: `ReceivablesService::invalidatePlanForOrderAmendmentLocked(Order $order, User $actor, array $context): void`
- Produces: `CommissionWorkflowService::reconcileAfterCustomerAmendmentLocked(Order $order, User $actor, bool $itemsChanged): void`
- Consumes: Task 2 document invalidation and Task 3 core amendment transaction.

- [ ] **Step 1: Add failing financial/document side-effect tests**

Cover:
- active confirmation/proforma/invoice cancelled when document-relevant data changes;
- verified/submitted payment rows remain unchanged;
- payment state recalculates to partial/paid/overpaid from new subtotal;
- subtotal change invalidates mismatched installment plan while preserving real order payments;
- allocation rows tied to obsolete plan are removed/reset consistently;
- contact/note-only edit does not reset approved commission;
- item/quantity edit recalculates pending commission;
- approved item-changing commission returns to pending with history;
- paid/cancelled commission blocks item-structure rewrite without partial writes.

- [ ] **Step 2: Expose in-transaction payment recalculation**

Refactor existing private recalculation logic into a caller-safe locked method rather than copying payment-state logic into `OrderAmendmentService`. Do not synchronize receivables before the outer amendment transaction is committed.

- [ ] **Step 3: Implement receivable-plan invalidation**

Only when subtotal changes and a receivable case exists:
- capture audit snapshot of case/installments/allocations;
- preserve `order_payments`;
- clear obsolete derived allocations;
- clear/replace obsolete installment rows under lock;
- set case back to monitoring/non-plan state;
- synchronize remaining balance after amendment commit.

- [ ] **Step 4: Implement commission reconciliation**

Item/quantity change:
- pending -> recalculated pending;
- approved -> recalculated pending with history;
- paid/cancelled -> reject amendment before financial/item writes commit.

Contact/address/note-only change leaves existing approved commission status intact.

- [ ] **Step 5: Wire document invalidation and post-commit notifications**

Call Task 2 invalidation primitive only when document-relevant content changed. Any notifications/emails are queued/sent after successful transaction commit.

- [ ] **Step 6: Verify atomic failure behavior**

Contract/test cases must show that a financial integrity rejection leaves:
- order fields/items unchanged;
- stock unchanged;
- document statuses unchanged;
- payment ledger unchanged;
- commission/receivable state unchanged.

---

### Task 5: Customer Web/API Endpoints and Admin Stale-Action Protection

**Files:**
- Modify: `apps/cms/current/routes/web.php`
- Modify: `apps/cms/current/routes/api.php`
- Modify: `apps/cms/current/app/Http/Controllers/OrderController.php`
- Modify: `apps/cms/current/app/Http/Controllers/Api/V1/OrderController.php`
- Modify: `apps/cms/current/app/Services/OrderDetailPresenter.php`
- Create: `apps/cms/current/resources/views/orders/edit.blade.php`
- Modify: `apps/cms/current/resources/views/orders/show.blade.php`
- Modify: `apps/cms/current/app/Services/OrderWorkflowService.php`
- Modify: `apps/cms/current/app/Services/OrderShipmentService.php`
- Modify: `apps/cms/current/app/Http/Controllers/Admin/OrderController.php`
- Modify: `apps/cms/current/app/Http/Controllers/Admin/OrderShipmentController.php`
- Modify: `apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php`
- Modify: `apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderShipmentController.php`
- Modify: `apps/cms/current/resources/views/admin/orders/show.blade.php`
- Modify: `apps/cms/current/tests/Feature/OrderAmendmentTest.php`

**Interfaces:**
- Produces Web routes: `GET /orders/{order}/edit`, `PATCH /orders/{order}`
- Produces API route: `PATCH /api/v1/orders/{order}`
- Produces admin form/API field: `order_version_token`
- Consumes: Tasks 1-4 domain services.

- [ ] **Step 1: Add failing route/controller tests**

Cover:
- Web edit/update owner flow;
- API amendment with `Idempotency-Key`;
- missing/stale token errors;
- admin confirmation with stale token rejected;
- admin shipment with stale token rejected;
- fresh admin token succeeds and Batch513 shipment semantics remain unchanged.

- [ ] **Step 2: Add customer Web endpoints**

`OrderController::edit()` prepares current order, accessible catalog/search context, token, and idempotency UUID. `OrderController::update()` delegates exclusively to `OrderAmendmentService` and redirects to detail with a clear success message.

Use existing catalog quick-search endpoint for adding products rather than rendering a full-catalog select per line.

- [ ] **Step 3: Add customer API endpoint**

`Api\V1\OrderController::update()` verifies owner semantics through the domain service, reads `Idempotency-Key`, delegates to `amend()`, and returns fresh `OrderResource`.

- [ ] **Step 4: Update customer detail presenter/view**

Show one `Uredi porudžbinu` action when `can_amend=true` with copy:

`Porudžbinu možeš menjati sve dok pošiljka ne bude poslata.`

Remove action after lock and show concise locked explanation.

- [ ] **Step 5: Add stale admin token to confirmation and shipment**

Admin detail presenter/API exposes `order_version_token`. Web hidden fields and Mobile admin API payloads carry it.

`OrderWorkflowService::changeStatus(..., ?string $expectedOrderToken = null)` requires/matches token when target status is `confirmed`.

`OrderShipmentService::record(..., ?string $expectedOrderToken = null)` verifies token after locking order and before shipment/status mutation.

On stale token return 409/clear refresh instruction, not 422.

- [ ] **Step 6: Verify existing delivery/completion flow is unchanged**

No new stale-token requirement is added to final delivery completion in this batch unless it is already implied by the current confirmed/shipped state; the Batch513 dual-action layout and COD semantics remain intact.

---

### Task 6: Mobile Customer Amendment UX and Admin Token Transport

**Files:**
- Modify: `apps/mobile/current/src/types/api.ts`
- Modify: `apps/mobile/current/src/lib/api/endpoints.ts`
- Modify: `apps/mobile/current/src/app/(app)/order/[id].tsx`
- Create: `apps/mobile/current/src/app/(app)/order/[id]/edit.tsx`
- Modify: `apps/mobile/current/src/features/admin/orders-admin-api.ts`
- Modify: `apps/mobile/current/src/features/admin/orders-admin-actions.tsx`
- Modify: `apps/mobile/current/scripts/validate-project.mjs`

**Interfaces:**
- Produces: `UpdateOrderInput` with `expected_edit_token`, items, shipping fields, note
- Produces: `api.orders.update(orderId, input, idempotencyKey)`
- Produces: Mobile admin status/shipment inputs with `order_version_token`
- Consumes: Task 5 API contract.

- [ ] **Step 1: Extend Mobile types and endpoint**

Order type gains:
- `edit_token`;
- `capabilities.can_amend`;
- customer note if not already exposed in the typed order detail required by edit screen.

`api.orders.update()` sends PATCH JSON plus `Idempotency-Key` header through existing `apiRequest`.

- [ ] **Step 2: Add customer detail action**

Show one full-width `Uredi porudžbinu` button only while capability allows. After lock, omit button and show concise locked explanation.

- [ ] **Step 3: Implement edit screen**

Three sections only:
1. current items with `-`, quantity, `+`, remove;
2. delivery fields;
3. note.

Product add flow searches existing catalog API by SKU/name and adds one result at a time; do not preload the whole catalog into a select.

Primary action text exactly:

`Sačuvaj izmene porudžbine`

- [ ] **Step 4: Handle stale conflict explicitly**

On `ApiError.status === 409`, show that the order changed elsewhere and require refresh/reload instead of silently resubmitting stale data. Generate a new idempotency key only for a newly confirmed save attempt, not while retrying the same network operation.

- [ ] **Step 5: Transport admin version token**

Mobile admin confirmation and shipment actions submit the `order_version_token` received from admin order detail. After any successful mutation, invalidate/refetch admin order detail before enabling the next version-sensitive action.

- [ ] **Step 6: Add validator sentinels**

Update existing Mobile validator checks for:
- customer edit route;
- exact save label;
- `edit_token`;
- idempotency header;
- stale 409 handling;
- admin token on confirm/shipment;
- preservation of Batch513 vertical shipment/delivery actions.

---

### Task 7: OpenAPI Contract, Laravel UTF-8 Repair, and Static Regression Guards

**Files:**
- Modify: `packages/api-contract/openapi.yaml`
- Sync exact bytes to: `apps/cms/current/docs/openapi.yaml`
- Sync exact bytes to: `apps/mobile/current/docs/openapi.yaml`
- Modify: `apps/cms/current/resources/views/admin/products/form.blade.php`
- Modify: `apps/cms/current/bin/static-check.php`
- Modify: `apps/cms/current/bin/batch515-order-amendment-contract.php`

**Interfaces:**
- Produces canonical public contract for `PATCH /api/v1/orders/{order}`, amendment payload/response, 409 conflict semantics, and admin version-token fields.
- Preserves OpenAPI three-copy byte parity.

- [ ] **Step 1: Add OpenAPI amendment schema/path**

Document:
- `expected_edit_token`;
- items;
- shipping fields;
- note;
- `Idempotency-Key` header;
- success order resource fields;
- 404/409/422 behavior.

Also describe admin `order_version_token` where admin mutation schemas are defined.

- [ ] **Step 2: Fix product form encoding at source**

Replace entity/ASCII variants with literal UTF-8:
- `Sačuvaj kao nacrt`;
- `Sačuvaj izmene`;
- `Sačuvaj izmene artikla`;
- `Sve izmene na ovoj stranici čuvaju se jednim klikom.`;
- `Sačuvaj novi artikal kada završiš unos.`

Do not use raw Blade output.

- [ ] **Step 3: Update one existing static sentinel**

Keep total CMS static checks exactly 983. Sentinel must fail if the escaped entity/ASCII regression returns and pass only for canonical UTF-8 strings.

- [ ] **Step 4: Sync OpenAPI copies and verify hashes**

Copy canonical contract byte-for-byte to CMS and Mobile mirrors. Verify identical SHA-256 for all three.

- [ ] **Step 5: Run dependency-free GREEN contract**

Expected output: zero `CONTRACT_FAIL`; include explicit checks for document snapshot renderer, order amendment route/service/token, Mobile edit screen/admin token, and UTF-8 copy.

---

### Task 8: Controlled Production Migration, Global Verification, Commit, and Operations Report

**Files/artifacts:**
- Create execution artifact outside repo: `/mnt/data/ald1n-batch515-customer-order-amendment-consolidated-workflow.sh`
- Create on success: `docs/operations/515-CUSTOMER-ORDER-AMENDMENT-CONSOLIDATED-WORKFLOW-<timestamp>.md`
- Modify on success: `docs/operations/000-LATEST.md`

**Interfaces:**
- Consumes all Tasks 1-7.
- Produces one source commit only after all source gates and migration invariants pass, then one docs commit.
- Produces no EAS/OTA/Play action.

- [ ] **Step 1: Build robust runner preflight**

Runner must:
- fetch `origin/main`;
- verify exact source authority including approved spec/plan commits;
- classify known `.htaccess`, numbered operation reports, and `error_log-*.gz` residue as read-only;
- fail on any other unexpected status;
- create backup/rollback copies for every touched tracked source file plus migration-new-file manifest;
- print START and every stage to stderr.

- [ ] **Step 2: Execute TDD RED safely**

Run the dependency-free contract harness before patch inside an `if/else` capture so expected non-zero does not trigger ERR finalization.

Expected: RED because implementation is absent.

- [ ] **Step 3: Apply the exact planned source changes**

Patch only plan-listed source files. No unrelated refactor, package update, version bump, build setting change, or production release action.

- [ ] **Step 4: Run all source gates before DB mutation**

Required:
- dependency-free contract GREEN;
- PHP syntax/lint on changed PHP;
- autoload/route checks actually supported by production;
- migration pretend/schema inspection;
- CMS static `983/983`;
- Mobile TypeScript;
- Mobile validator;
- Expo `install --check`;
- Expo Doctor;
- OpenAPI hash parity;
- `git diff --check`;
- exact source-scope allowlist.

Report PHPUnit as `NOT_RUN_PRODUCTION_NO_DEV_DEPENDENCIES` unless a real runner is proven and executed.

- [ ] **Step 5: Take DB backup and apply exactly one expected migration**

Only after Step 4 PASS:
- run verified DB backup;
- record backup path/hash/size;
- confirm migration pending list contains exactly the expected Batch515 migration for this batch;
- run only the standard controlled Laravel migration gate;
- never run destructive down migration automatically.

- [ ] **Step 6: Verify post-migration invariants**

Check read-only:
- table exists;
- expected unique/FK/indexes exist;
- existing order documents have document-item snapshots where source order items existed;
- no unrelated migration applied;
- current one production edit candidate remains logically consistent;
- active invoice history is preserved, not deleted.

- [ ] **Step 7: Re-run high-value post-migration gates**

Re-run:
- dependency-free contract GREEN;
- CMS static 983/983;
- Mobile TypeScript/validator;
- OpenAPI parity;
- targeted read-only domain census for shipment lock/document snapshots.

- [ ] **Step 8: Commit/push source only after full PASS**

One source commit message:

`feat(orders): allow customer amendments before shipment`

Push main and verify remote HEAD equals the source commit.

- [ ] **Step 9: Write operations report and update canonical state**

Report exact:
- pre/post source commit;
- migration name/result;
- DB backup;
- document backfill counts;
- all gates;
- PHPUnit truth state;
- `EAS_BUILD_STARTED=NO`;
- `EAS_SUBMIT_STARTED=NO`;
- `OTA_PUBLISHED=NO`;
- `GOOGLE_PLAY_ACTION=NO`;
- `BUILD23_DEFERRED=YES`.

Update `000-LATEST.md` to identify Batch515 source authority and keep Build22 as last production build.

- [ ] **Step 10: Final terminal result**

PASS block must include:
- RESULT;
- BATCH=515;
- source/docs commits;
- migration;
- DB backup;
- CMS static;
- Mobile validator/typecheck;
- OpenAPI parity;
- PHPUnit execution truth;
- Build23 deferred;
- report path.

FAIL finalizer must run on every failure path and include stage, reason, source commit, migration-run state, build/submit states, and report path if created.

## Self-Review

- **Spec coverage:** All approved spec areas map to Tasks 1-8: customer edit, concurrency, idempotency, inventory, documents, payments, receivables, commission, Web, Mobile, admin stale protection, OpenAPI, UTF-8 repair, production verification, migration safety, and deferred Build23.
- **Step scan:** Each step has one checkable deliverable; implementation details are fixed where the spec requires exact semantics without transcribing full code.
- **Type consistency:** `OrderVersionService`, `OrderAmendmentService`, `expected_edit_token`, `order_version_token`, and Mobile `UpdateOrderInput` names are consistent across all tasks.
- **Review Focus coverage:** stale customer edit, stale admin action, invoice snapshot/revision, archived item quantity rules, and payment/installment subtotal changes all have named tests in their owning tasks.
- **Proportion:** The plan is intentionally detailed because the change crosses financial/document/inventory boundaries, but implementation bodies remain delegated to the executor rather than embedded here.

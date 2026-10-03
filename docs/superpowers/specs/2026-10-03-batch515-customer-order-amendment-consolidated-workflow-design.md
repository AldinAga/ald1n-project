# Batch515 — Customer Order Amendment Consolidated Workflow Design

**Date:** 2026-10-03  
**Status:** Design approved in conversation; written-spec review pending  
**Target repository:** `AldinAga/ald1n-project`  
**Canonical branch:** `main`

## 1. Goal

Allow a customer to correct and amend their own Laravel order — including products and quantities — until the order has actually entered the shipped/completed/cancelled state, while preserving inventory, document, payment, receivables, commission, audit, and admin workflow integrity across Laravel Web and the Expo mobile app.

Batch515 also includes the already-approved Laravel product-form UTF-8 copy repair for the broken `Sa&#269;uvaj izmene` rendering, with regression coverage so the encoding defect does not return.

## 2. User intent

The customer needs a forgiving correction window after order creation because they may:

- forget a note;
- mistype recipient/contact/address data;
- choose the wrong product;
- enter the wrong quantity;
- add or remove a product before shipment.

The interface should remain simple: one `Uredi porudžbinu` action, one coherent edit screen, and one `Sačuvaj izmene porudžbine` action.

The customer should not need to understand internal statuses such as `new`, `processing`, or `confirmed`. The business rule visible to the customer is:

> Porudžbinu možeš menjati sve dok pošiljka ne bude poslata.

## 3. Evidence and constraints from the root audit

The root audit established:

- canonical source was unchanged by the audit;
- Batch514 failed in its runner before feature implementation because the intentional TDD RED non-zero exit was intercepted by an active ERR trap;
- production has 42 orders, 41 Laravel orders, and exactly one current amendment candidate;
- that candidate already has one issued invoice;
- the candidate has no approved/paid/cancelled commission, no submitted/verified payment, no receivable case, no installment plan, no payment allocations, no non-public product, and no missing product;
- no duplicate order/product groups were found;
- `order_shipments.order_id` and `stock_movements.event_key` uniqueness are present;
- CMS static, Mobile TypeScript, Mobile validator, Expo check/doctor, and OpenAPI parity pass;
- the production runtime does not expose a usable `artisan test` command, so previous audit labels that called those test invocations PASS are not valid evidence of executed PHPUnit tests;
- active order documents are reused and document rendering currently reads live order items.

These findings make document lifecycle, stale-edit protection, and production-safe verification part of Batch515 rather than optional follow-up work.

## 4. Scope

### 4.1 Customer-editable fields before lock

The customer may change:

- order items;
- quantities;
- recipient full name;
- recipient phone;
- shipping address;
- shipping city;
- shipping postal code;
- customer note.

The customer may not change through this flow:

- payment method;
- responsible supplier/admin;
- sales channel;
- order owner;
- direct-sale records;
- completed/cancelled/imported legacy records.

### 4.2 Lock rule

Customer amendment is allowed only for a Laravel, non-direct-sale order owned by the authenticated user when all of the following are true:

- status is one of `new`, `processing`, `confirmed`;
- `completed_at IS NULL`;
- no `order_shipments` row exists;
- status is not `shipped` or `cancelled`.

The lock is therefore not based on shipment-row existence alone. Existing completion logic may legitimately produce a shipped/completed order without a separate shipment row, so status/completion must also lock amendments.

### 4.3 Explicit non-goals

Batch515 does not:

- change the payment method;
- introduce Product Variants;
- redesign Direct Sale;
- allow editing imported legacy orders;
- automatically submit an Android build;
- publish OTA updates;
- submit to Google Play;
- change app version/runtimeVersion/versionCode;
- add automatic invoice issuance after amendment.

Build23 remains deferred.

## 5. Amendment architecture

Introduce a focused `OrderAmendmentService` as the single domain boundary for customer amendments.

Responsibilities:

1. authorize ownership and editable state;
2. acquire the order row lock;
3. verify optimistic-concurrency token;
4. acquire all affected product row locks in deterministic product-ID order;
5. validate product visibility and stock;
6. calculate inventory deltas;
7. rebuild order item snapshots;
8. recalculate subtotal and commission snapshots;
9. synchronize order status;
10. invalidate active order documents when required;
11. recalculate payment state without modifying payment ledger entries;
12. invalidate/reset receivable plans when subtotal changes;
13. reconcile commission workflow;
14. append status/audit/history records;
15. return the freshly loaded order.

The service executes the business mutation inside one database transaction. Optional outbound notifications/email happen only after successful commit.

## 6. Idempotency and optimistic concurrency

Two different protections are required.

### 6.1 Idempotency

The API amendment endpoint accepts an `Idempotency-Key` and uses the existing `IdempotencyService` under a per-order amendment scope.

Purpose:

- retry after mobile/network timeout must not create duplicate stock movements;
- repeated identical save must replay the completed mutation;
- reusing the same key with different payload remains rejected by the existing idempotency contract.

### 6.2 Optimistic concurrency token

Order detail exposes an `edit_token` generated from the current editable order state.

The token covers at minimum:

- order ID;
- status;
- completion/shipment lock state;
- editable shipping/contact fields;
- customer note;
- subtotal;
- deterministic ordered item state including product ID, quantity, and price/commission snapshots;
- an order freshness component such as `updated_at`.

Web and Mobile submit `expected_edit_token`.

After `lockForUpdate()`, the service recomputes the canonical token. If it does not match, no mutation occurs and the request returns HTTP 409 with a user-readable stale-data message.

This prevents lost updates across:

- two browser tabs;
- Web and Mobile simultaneously;
- retry from a stale screen;
- customer amendment occurring while an admin is viewing an older order version.

## 7. Admin stale-state protection

The same order-version token is included on administrative actions whose correctness depends on the order version the administrator reviewed.

At minimum:

- confirming an order;
- recording shipment sent.

If the order changed after the admin loaded the screen, the operation is rejected and the admin is instructed to refresh and review the new version.

A customer amendment of an already `confirmed` order moves it back to `processing`. The existing shipment service therefore continues to require an explicit new confirmation before shipment.

Status behavior:

- `new` + amendment -> remains `new`;
- `processing` + amendment -> remains `processing`;
- `confirmed` + amendment -> becomes `processing` and gets history explaining that customer changes require re-confirmation.

## 8. Inventory rules

Inventory is changed by delta rather than by full release/re-reserve.

For each affected product:

`delta = new_quantity - old_quantity`

- positive delta consumes stock;
- negative delta returns stock;
- zero delta creates no movement.

All affected product rows are locked in ascending product-ID order before availability checks and mutations.

Stock movements use a dedicated amendment source/type and a deterministic event key tied to the idempotent amendment operation and product. Existing unique `stock_movements.event_key` remains the last-line duplicate protection.

The complete amendment transaction rolls back if any required positive delta cannot be reserved.

### 8.1 Existing non-public/archived products

If a product was already on the order and later became non-public/archived/inaccessible:

- the current quantity may remain unchanged;
- quantity may be reduced;
- the item may be removed;
- quantity may not be increased.

A product newly added to the order, or an existing product whose quantity is increased, must currently be active, not archived, visible to the customer, and have sufficient stock.

## 9. Order item snapshot policy

An existing item whose quantity changes preserves its original commercial snapshot:

- SKU/name/type/brand/line snapshot;
- unit price original;
- unit price RSD;
- purchase price snapshot;
- commission snapshot/source.

Only quantity and derived line totals change.

A newly added product receives a fresh commercial snapshot from the current catalogue state at amendment time.

This prevents an old order line from silently repricing merely because quantity changed.

## 10. Document lifecycle and immutable document items

The current document model stores document-level snapshots but document PDF rendering reads live order items. Batch515 must remove that coupling before customer amendments are enabled.

### 10.1 New table

Add `order_document_items` with immutable per-document item snapshots. Minimum fields:

- ID;
- `order_document_id`;
- sequence number;
- nullable `product_id`;
- SKU snapshot;
- name snapshot;
- quantity;
- unit price RSD;
- line total RSD;
- timestamps.

Indexes/constraints:

- FK to `order_documents` with cascade on delete;
- unique `(order_document_id, sequence_no)`;
- index `product_id` where useful.

### 10.2 Issue behavior

When a document is issued, its item snapshots are written in the same transaction as the document.

Document rendering must render `document.items`, never current `order.items`.

### 10.3 Existing-document backfill

The migration backfills item snapshots for existing documents from the order items that exist at migration time.

This is safe as an enabling migration because customer amendment functionality does not exist before Batch515. The migration report must record how many document rows and document-item rows were backfilled.

### 10.4 Amendment invalidation

If an amendment changes any document-relevant order content (items, quantity, recipient/address/contact details, or subtotal), active customer/business documents for that order are invalidated using the existing document cancellation/revision lifecycle:

- active `order_confirmation` -> cancelled;
- active `proforma` -> cancelled;
- active `invoice` -> cancelled.

Cancellation reason:

`Porudžbina je izmenjena od strane kupca pre slanja.`

No replacement document is issued automatically.

When an authorized user later issues that document type again, existing revision logic creates revision N+1 and links `supersedes_document_id`.

The implementation uses the application's existing internal document cancellation semantics. This design does not make a claim about external fiscal/legal invoice cancellation requirements.

A completed delivery-note state is already outside the amendment window.

## 11. Payment ledger behavior

Existing `order_payments` are immutable from the amendment flow.

After the new subtotal is stored, existing payment recalculation logic recomputes:

- `paid_total_rsd`;
- `payment_state`;
- `payment_status`;
- verified-at state;
- IPS terminal payload/cache state where existing payment service semantics require it.

Examples:

- subtotal 100,000 -> 120,000 with 60,000 verified remains 60,000 paid and becomes partial;
- subtotal 100,000 -> 50,000 with 60,000 verified becomes overpaid.

No submitted or verified payment is deleted or changed by the amendment.

## 12. Receivables/installment-plan behavior

When subtotal does not change, amendment does not rewrite a receivable plan.

When subtotal changes and a receivable case exists:

1. capture an audit snapshot of current receivable case, installments, and allocations;
2. preserve real order payment ledger entries;
3. clear derived payment allocations tied to the obsolete installment plan;
4. remove/replace obsolete installment rows according to existing plan-replacement constraints;
5. return the receivable case to a non-plan monitoring state;
6. synchronize remaining balance from the amended order;
7. require an administrator to create a new installment plan for the new debt.

The amendment must never silently keep an installment plan whose sum no longer equals the remaining debt.

## 13. Commission behavior

If item structure does not change, contact/address/note-only edits do not reset an approved commission.

If products or quantities change:

- pending commission is recalculated and stays pending;
- approved commission is recalculated and moves back to pending, with commission history noting customer amendment;
- paid/cancelled commission represents an integrity boundary: item/quantity amendment is rejected for SuperAdmin intervention rather than rewriting paid/cancelled financial history.

The production audit currently found no candidate with approved or paid commission, so this rule is preventive rather than required to repair current data.

## 14. API contract

Canonical OpenAPI remains authoritative and all three tracked copies must stay byte-identical.

Add customer amendment endpoint:

`PATCH /api/v1/orders/{order}`

Request includes:

- `expected_edit_token`;
- item list `[{product_id, quantity}]`;
- editable shipping fields;
- `customer_note`.

The HTTP request uses `Idempotency-Key`.

Successful response returns the updated order resource, including a fresh `edit_token` and amendment capability state.

Expected error classes:

- 404: order not owned/visible;
- 409: stale edit token or stale admin token;
- 422: order locked, insufficient stock, inaccessible product increase/addition, financial integrity boundary, invalid fields;
- standard auth/permission responses for unauthenticated/unauthorized access.

Web route uses the same domain service rather than duplicating logic.

## 15. Customer Web UX

Order detail shows a compact amendment card only while amendment is allowed:

`Uredi porudžbinu`

Supporting copy:

`Porudžbinu možeš menjati sve dok pošiljka ne bude poslata.`

The edit screen has only three user-facing sections:

1. **Stavke** — current lines with minus/quantity/plus/remove;
2. **Dostava** — recipient/contact/address fields;
3. **Napomena** — customer note.

Adding a product uses catalogue search by name/SKU, not a full-catalogue select rendered once per line.

Primary action:

`Sačuvaj izmene porudžbine`

On stale-token conflict, the form does not silently overwrite. It shows a refresh/review instruction.

After shipment/completion/cancellation, edit action is removed and the detail explains the order is locked.

## 16. Mobile UX

Mobile mirrors the same three-section mental model.

Order detail exposes one full-width `Uredi porudžbinu` action while capability allows it.

Edit screen:

- current order items with quantity controls and remove;
- search/add product flow;
- delivery fields;
- customer note;
- one full-width `Sačuvaj izmene porudžbine` button.

The screen carries the order `edit_token` loaded with the order. A 409 stale response forces refresh instead of preserving a stale overwrite path.

No new EAS build is triggered by Batch515.

## 17. Administrator UX

Admin order detail highlights customer amendments in timeline/audit history.

For a customer change after confirmation, the admin sees that re-confirmation is required.

Shipment/confirmation forms carry the admin version token. A stale admin action shows a clear refresh-and-review message instead of applying the action against an unseen order revision.

The Batch513 dual-action shipment/delivery layout remains intact.

## 18. Product-form UTF-8 correction

In `resources/views/admin/products/form.blade.php`, remove legacy HTML-entity/ASCII copy patterns that produce literal entity output inside escaped Blade expressions.

Canonical UTF-8 strings include:

- `Sačuvaj kao nacrt`;
- `Sačuvaj izmene`;
- `Sačuvaj izmene artikla`;
- `Sve izmene na ovoj stranici čuvaju se jednim klikom.`;
- `Sačuvaj novi artikal kada završiš unos.`

Do not use raw Blade `{!! !!}` as an encoding workaround.

Update an existing static regression check rather than increasing the canonical CMS static count; target remains `983/983`.

## 19. Production-safe verification strategy

Production does not currently provide a usable PHPUnit runner through `artisan test`, so the Batch515 runner must not claim PHPUnit PASS when tests were not executed.

### 19.1 Required repository tests

Add normal PHPUnit feature tests for development/CI covering at minimum:

- owner can amend an open Laravel order;
- non-owner cannot;
- shipped/completed/cancelled/direct-sale/legacy cannot;
- quantity increase/decrease adjusts stock exactly once;
- retry/idempotency does not double-move stock;
- stale edit token returns 409 and writes nothing;
- archived existing item may decrease/remove but not increase;
- new inaccessible item cannot be added;
- existing line preserves price snapshot;
- new line gets current snapshot;
- confirmed amendment returns order to processing;
- documents are invalidated and revisions work;
- document PDF uses immutable document-item snapshot;
- payment ledger is preserved and state recalculates;
- receivable plan cannot remain mismatched after subtotal change;
- approved commission returns to pending on item change;
- paid commission blocks item-structure rewrite;
- stale admin confirm/shipment is rejected.

These tests are committed even when production cannot execute them.

### 19.2 Production runner gates

The production Batch515 shell runner uses:

1. source authority and worktree residue classification;
2. backup of every touched source/config/migration file;
3. dependency-free contract TDD RED executed in an `if ...; then ... else rc=$?; fi` construct so intentional RED cannot be intercepted by ERR trap;
4. implementation;
5. dependency-free contract GREEN;
6. PHP syntax/lint;
7. autoload/route contract checks that are actually available in production;
8. migration `--pretend`/schema readiness checks without claiming PHPUnit;
9. CMS static `983/983`;
10. Mobile TypeScript;
11. Mobile validator;
12. Expo `install --check`;
13. Expo Doctor;
14. OpenAPI three-copy parity;
15. `git diff --check`;
16. exact source-scope validation;
17. only after all source gates pass: database backup;
18. apply exactly the expected migration;
19. post-migration schema/backfill invariants;
20. source commit/push and operations report only on successful gates.

The report must explicitly state:

`PHPUNIT_FEATURE_TESTS=NOT_RUN_PRODUCTION_NO_DEV_DEPENDENCIES`

unless a real test runner has independently become available and its execution is evidenced.

## 20. Migration safety

The implementation is allowed one controlled schema migration for immutable document item snapshots.

Before migration:

- all source gates pass;
- exact migration name/hash is recorded;
- DB backup completes successfully;
- migration status is captured.

After migration:

- `order_document_items` exists with expected constraints;
- every existing active/cancelled order document that had order items at migration time has matching document-item snapshot rows;
- document renderer no longer depends on live order items;
- no unrelated migrations were run.

If source gates fail, migration does not run.

If backup or migration invariants fail, the batch stops and reports the exact stage. Build/submit/OTA/Play remain untouched.

## 21. Audit and notifications

Successful amendment writes a dedicated audit event such as:

`order.customer_amended`

Audit metadata records:

- actor;
- before/after editable fields;
- item quantity deltas;
- subtotal before/after;
- status before/after;
- invalidated document IDs/revisions;
- commission transition if any;
- receivable plan invalidation if any.

Admin timeline should surface a concise customer-amendment entry.

Notifications/email must not be sent before transaction commit.

## 22. Rollback philosophy

Source rollback restores all touched files if implementation/validation fails before commit.

Because `order_document_items` is an audit-preserving snapshot table, rollback after a successfully applied production migration must not destructively delete document history. Application rollback must be forward-compatible with the added table.

No automatic destructive down-migration is executed by the batch.

## 23. Release state

Batch515 is source/schema work only.

It must finish with:

- `EAS_BUILD_STARTED=NO`;
- `EAS_SUBMIT_STARTED=NO`;
- `OTA_PUBLISHED=NO`;
- `GOOGLE_PLAY_ACTION=NO`;
- `BUILD23_DEFERRED=YES`.

A fresh release-readiness batch is required later, after all grouped functionality is complete, before any Build23 command is authorized.

## 24. Acceptance criteria

Batch515 is complete only when:

1. customer can amend products, quantities, delivery data, and note while the order is open;
2. amendment is locked at shipped/completed/cancelled state;
3. inventory deltas are atomic and idempotent;
4. stale user/admin screens cannot overwrite or ship unseen revisions;
5. item-price snapshots remain stable for existing lines;
6. document PDFs render immutable document-item snapshots;
7. active documents affected by amendment are cancelled/revisionable rather than silently reused;
8. payments are preserved and recalculated;
9. receivable plans cannot remain inconsistent with the amended balance;
10. commission workflow remains financially consistent;
11. Web and Mobile expose the same simple amendment mental model;
12. Laravel product-save labels render correct UTF-8 text;
13. canonical OpenAPI copies remain identical;
14. CMS static remains exactly `983/983`;
15. all production-available gates pass without falsely claiming unavailable PHPUnit execution;
16. no EAS build, submit, OTA, or Google Play action occurs.

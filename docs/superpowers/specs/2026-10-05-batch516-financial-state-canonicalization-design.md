# Batch516 Financial State Canonicalization - Design

Status: Conversational design approved; written-spec review pending.

## 1. Context and source authority

Batch516 follows the completed Batch515 customer-order amendment work.

At design time:

- GitHub `main`: `3deccc70356eeba1c119060adeaf1b180aa4905f`
- current application source authority: `5ceb1cbc43aaeaa132fe9abc2e4a1ea8e2555d27`
- app version: `1.0.0`
- runtimeVersion: `1.0.0-build17`
- last production Android build: Build22 / versionCode 22
- Build23 remains deferred by the user

The Batch516 read-only production audit from 2026-10-05 found:

- 42 orders and 44 payment rows;
- 2 canonical ledger mismatches, both cancelled COD orders (IDs 28 and 134);
- 0 Direct Sale ledger mismatches;
- 0 receivable plan/state/allocation mismatches;
- 0 IPS cache terminal/amount/outstanding mismatches;
- 0 submitted payments;
- 2 historical COD provenance anomalies whose verified ledger net is financially consistent;
- one historical manual payment-status audit entry;
- one active Web manual payment-status route and one active API manual payment-status route;
- two GET order-detail paths that call IPS persistence;
- HTTP order/report rendering paths that replace the existing Laravel error bag.

No source, database, migration, Composer, EAS, OTA or Google Play mutation was performed by the audit.

## 2. Goal

Create one canonical financial-state authority for Laravel orders so that payment state can no longer diverge from the verified payment ledger.

After Batch516:

1. `order_payments` is the authoritative ledger for money received/refunded.
2. `orders.paid_total_rsd`, `orders.payment_state`, `orders.payment_status`, and `orders.payment_verified_at` are derived projections.
3. No Web, API, Mobile, Direct Sale, completion, correction, amendment or cancellation flow may independently decide those derived fields.
4. GET requests do not mutate IPS cache state.
5. Receivables and IPS remain downstream derived systems and can be reconciled without changing canonical ledger facts.
6. Current benign production drift is repaired through the same projector used by normal runtime flows.
7. Build23 is not created, submitted or published.

## 3. Non-goals

Batch516 does not:

- replace the payment ledger with event sourcing;
- remove payment compatibility columns from `orders`;
- rewrite historical payment rows merely to normalize provenance;
- rewrite the two historical COD provenance anomalies when their verified net already reconciles;
- redesign receivables plans;
- alter customer amendment rules from Batch515;
- introduce Product Variants;
- create an Android production build, OTA update, Play submission, or versionCode change.

No schema migration is planned. If implementation discovers a schema requirement, the work must stop and the written design must be revised before a migration is introduced.

## 4. Canonical financial model

### 4.1 Authoritative facts

The canonical inputs are:

- `orders.status`
- `orders.subtotal_rsd`
- `orders.payment_method`
- verified rows in `order_payments`
- payment row `entry_type` (`payment` or `refund`)

Only `order_payments.status = verified` participates in net paid value.

`submitted`, `rejected`, and `voided` rows remain audit/history records and do not contribute to paid total.

### 4.2 Derived projection

A new focused service, `OrderFinancialStateService`, owns financial projection.

Conceptual API:

```php
final class OrderFinancialStateService
{
    /** @return array{paid_total_rsd:float,payment_state:string,payment_status:string,payment_verified_at:mixed} */
    public function derive(Order $order): array;

    public function projectLocked(Order $order): Order;

    public function project(Order $order): Order;
}
```

`derive()` reads canonical facts and returns the expected projection without writing.

`projectLocked()` is used by callers that already hold the order row lock.

`project()` is the safe transaction/lock convenience method for controlled repairs or standalone reconciliation.

The projector must not send email, create notifications, change documents, allocate receivables, or write IPS cache. Its only job is the canonical derived order fields.

### 4.3 State rules

The projection uses the existing production semantics:

```text
if order.status == cancelled
    payment_state = cancelled
else if verified net < 0
    payment_state = refunded
else if verified refund gross > 0 and verified net <= 0
    payment_state = refunded
else if verified net <= 0
    payment_state = unpaid
else if verified net < subtotal
    payment_state = partial
else if verified net > subtotal
    payment_state = overpaid
else
    payment_state = paid
```

Money comparisons use the existing RSD tolerance of approximately 0.004.

`paid_total_rsd` is always the verified net, including cancelled orders. Cancellation never erases payment history.

Compatibility `payment_status` becomes:

```text
cancelled -> cancelled
refunded  -> refunded
paid      -> paid
overpaid  -> paid
partial   -> pending
unpaid    -> pending
```

Because `refunded` is a valid projector output, all order/report/API filter validation that currently accepts only `pending|paid|cancelled` must also accept `refunded`.

`payment_verified_at` is populated only while state is `paid` or `overpaid`. Existing non-null value is preserved while the order remains fully paid; otherwise the projector may use the relevant verified payment timestamp. It becomes null when the order is no longer fully paid.

## 5. Mutation flow integration

### 5.1 OrderPaymentService

`OrderPaymentService` remains the owner of payment-ledger mutations:

- record payment;
- submit proof;
- verify proof;
- reject proof;
- void payment;
- after-sales refund.

It stops containing its own financial-state formula and delegates to `OrderFinancialStateService::projectLocked()`.

The existing Batch515 caller-safe method `recalculateForAmendmentLocked()` remains available for compatibility but becomes a thin delegate to the projector.

### 5.2 Order cancellation

When an order becomes `cancelled`:

1. lock order;
2. perform existing inventory/commission/warranty cancellation semantics;
3. set operational order status;
4. run the financial projector before transaction completion;
5. after commit, reconcile downstream IPS/receivables.

This closes the exact class of drift currently visible on Orders 28 and 134.

### 5.3 COD completion

COD completion may create the missing verified COD payment row exactly as today.

After any payment row is created, completion must run the projector instead of manually writing:

- `paid_total_rsd`;
- `payment_state`;
- `payment_status`;
- `payment_verified_at`.

For non-COD completion, eligibility must be checked against canonical derived state rather than trusting a potentially stale cached status.

Operational completion fields (`status`, `completed_at`, delivery record, etc.) remain owned by the workflow service.

### 5.4 Direct Sale

Direct Sale remains superadmin-only and keeps its existing ledger/business rules.

For an immediate-payment Direct Sale:

1. create order with neutral payment defaults;
2. create verified `order_payments` row;
3. project canonical financial state before returning the transaction.

For deferred Direct Sale:

1. create order with neutral payment defaults;
2. do not synthesize a payment row;
3. project `unpaid/pending`;
4. keep existing receivable/deferred-plan behavior.

Direct Sale no longer writes paid derived fields independently.

### 5.5 Direct Sale price correction

The correction service may continue to update the single verified payment amount and order subtotal under its current integrity gates.

It must stop manually assigning derived payment fields. After changing canonical subtotal/payment facts, it runs the projector.

Preconditions should use canonical ledger facts/derived calculation rather than trusting stale compatibility fields.

### 5.6 Customer amendments

Batch515 semantics remain unchanged.

After subtotal/item changes, the existing amendment reconciliation path delegates to the same projector. Payment rows remain untouched.

## 6. Manual payment-status decommission

The system must no longer expose "mark payment paid/pending/cancelled" as a separate business mutation.

Remove:

- Web `PATCH admin/orders/{order}/payment`;
- API `PATCH api/v1/admin/orders/{order}/payment-status`;
- corresponding controller actions;
- `OrderWorkflowService::updatePaymentStatus()`;
- Mobile `paymentStatus()` API client method;
- Mobile "Status plaćanja" action panel and related status mutation types/helpers;
- stale OpenAPI route/schema references;
- static-validator expectations for the removed mutation.

The payment-status field remains readable/filterable as a derived compatibility projection.

Real payment changes continue to use ledger operations: record, verify, reject, void, and approved after-sales refund.

## 7. IPS read/write separation

GET order-detail routes must be read-only.

`IpsPaymentPayloadService` is split conceptually into two behaviors:

```php
public function forDisplay(Order $order, ?float $amountRsd = null): ?string;
public function refreshCache(Order $order, ?float $amountRsd = null): ?string;
```

`forDisplay()`:

- computes current payload from order state;
- returns null for non-bank-transfer, terminal, or zero-outstanding states;
- never inserts, updates, or deletes `order_ips_qr`;
- never changes canonical financial state.

`refreshCache()`:

- is called only after a financial mutation or controlled repair;
- updates/deletes the derived IPS cache;
- preserves the Batch511 rule that IPS cache failure must never roll back a valid canonical payment mutation;
- clears stale cache if refresh fails.

User and admin Web GET detail controllers switch from `persist()` to pure display behavior.

API GET remains read-only.

## 8. Downstream financial reconciliation

A separate focused `OrderFinancialReconciliationService` coordinates downstream systems after canonical state is committed.

Responsibilities:

1. refresh or clear derived IPS cache;
2. call `ReceivablesService::syncForOrder()`;
3. log failures with order ID and subsystem;
4. never rewrite payment-ledger rows;
5. never independently calculate order payment state.

Canonical payment/order projection succeeds or fails atomically inside the financial mutation transaction. Downstream reconciliation does not get authority to roll back a valid ledger mutation solely because IPS or receivables refresh failed.

To avoid silent permanent drift, Batch516 adds a dry-run-first operational doctor/reconciler, conceptually:

```bash
php artisan app:financial-state-doctor
php artisan app:financial-state-doctor --order-id=<id>
php artisan app:financial-state-doctor --apply
```

Default mode is read-only.

`--apply` is an explicit operator action and may:

- project derived order fields from canonical facts;
- refresh IPS cache;
- resync receivables.

The command reports before/after counts and refuses unsafe ledger mutations.

No automatic cron repair is added in Batch516.

## 9. Production data repair

The implementation batch performs a fresh read-only census before any mutation.

At audit time the expected benign mismatch set is Orders 28 and 134, both:

- Laravel orders;
- cancelled;
- COD;
- verified net = 0;
- `paid_total_rsd` already equals verified net;
- only `payment_state` / `payment_status` are stale.

The implementation must not blindly hardcode those IDs as the only repairable state.

Repair policy:

1. dry-run all Laravel order projections;
2. allow automatic repair only when ledger facts themselves are internally consistent and drift is limited to derived order fields/cache;
3. if any `paid_total_rsd` versus verified-net mismatch, malformed payment ledger, Direct Sale ledger mismatch, or unexpected financial anomaly exists, stop before database mutation;
4. after DB backup and source gates, apply the projector to the allowed drift set;
5. rerun census and require zero canonical ledger mismatches.

The two historical COD provenance anomalies are report-only because their verified financial net is already consistent. Batch516 does not fabricate or rewrite historical payment-method provenance.

## 10. Laravel validation error preservation

HTTP rendering must not replace Laravel's existing validation error bag.

Remove explicit empty `ViewErrorBag` replacement from normal HTTP response paths used by:

- user order detail;
- admin order list/detail;
- report rendering where it can overwrite session validation errors.

CLI doctor commands may continue injecting an empty error bag when they render Blade outside a normal HTTP session.

This change is included because Batch516 removes the manual status flow and relies more heavily on precise ledger-operation validation messages.

## 11. API, Mobile and OpenAPI behavior

### API

- manual payment-status mutation endpoint is removed;
- real payment mutation endpoints stay unchanged;
- read payloads continue exposing `payment_status` and `payment_state`;
- filter domain includes `refunded`;
- no API consumer may set derived financial state directly.

### Mobile

- remove the manual "Status plaćanja" action;
- keep payment entry/verify/reject/void UX;
- keep derived payment status/state display;
- update types and validator guards accordingly.

### OpenAPI

Canonical contract and both mirrors remain byte-identical.

The contract removes the manual payment-status mutation and documents the derived payment-status domain, including `refunded`.

## 12. Static safeguards

CMS static checks and the dependency-free Batch516 contract harness must enforce:

- `OrderFinancialStateService` is the only application service containing the canonical state formula;
- forbidden direct writes to the four derived order fields are absent from runtime services except explicitly allowed bootstrap/default contexts;
- manual payment-status routes/actions are absent;
- GET order detail does not call IPS cache persistence;
- HTTP order/report render paths do not force a new empty `ViewErrorBag`;
- OpenAPI mirrors match;
- Product Variants remain decommissioned.

CMS static target remains exactly `983/983`; existing sentinels are replaced, not merely accumulated.

## 13. Tests and verification

Repository tests should cover at minimum:

- unpaid;
- partial;
- paid;
- overpaid;
- refunded;
- cancelled with zero payments;
- cancelled with verified payments preserved in net total;
- verify -> paid;
- void -> partial/unpaid;
- refund transitions;
- COD completion;
- immediate Direct Sale;
- deferred Direct Sale;
- Direct Sale price correction;
- Batch515 subtotal amendment;
- manual payment-status route removal;
- pure GET IPS display behavior;
- IPS mutation refresh behavior;
- receivable synchronization failure not rolling back canonical payment ledger;
- data-repair dry run.

Production runtime does not currently have PHPUnit dev dependencies. The implementation report must therefore distinguish:

```text
PHPUNIT_FEATURE_TESTS=NOT_RUN_PRODUCTION_NO_DEV_DEPENDENCIES
```

unless an actual runner is proven and executed.

Production-valid gates include:

- dependency-free Batch516 RED/GREEN contract harness;
- PHP lint;
- route contract;
- CMS static `983/983`;
- Mobile TypeScript;
- Mobile validator zero FAIL;
- Expo install check;
- Expo Doctor;
- OpenAPI 3-copy parity;
- `git diff --check`;
- exact source-scope whitelist;
- read-only financial census;
- DB backup before repair;
- controlled repair;
- post-repair financial census with zero canonical mismatch;
- IPS and receivable invariant checks.

## 14. Rollout and rollback

Batch516 is intended as one consolidated implementation batch, not a chain of pre-planned recovery batches.

Proposed execution report:

`docs/operations/516-FINANCIAL-STATE-CANONICALIZATION-<timestamp>.md`

Proposed source commit message:

`refactor(finance): canonicalize order financial state`

Sequence:

1. reconstruct fresh `main`, `000-LATEST.md`, current operations and Project rules;
2. verify known worktree residue without deleting it;
3. create source backups;
4. run TDD/contract RED;
5. apply source changes;
6. run source gates;
7. run fresh read-only production financial census;
8. stop if unsafe/unexpected anomalies are present;
9. take DB backup;
10. apply only canonical derived-state repair;
11. run post-repair census;
12. commit/push source only after all required gates pass;
13. refresh any runtime cache required by changed routes;
14. write operations report and update `000-LATEST.md`;
15. leave Build23 deferred.

No EAS build, submit, OTA publish or Google Play mutation is authorized by Batch516.

Rollback must restore source from the batch backup/previous commit and restore database only if the controlled derived-field repair itself must be reverted. Payment ledger rows are not rewritten by the repair, so rollback scope is intentionally narrow.

## 15. Acceptance criteria

Batch516 is complete only when all of the following are true:

1. canonical ledger mismatch count is zero for Laravel orders;
2. Direct Sale ledger mismatch count remains zero;
3. receivable plan/state/allocation mismatch counts remain zero;
4. IPS terminal/amount/outstanding mismatch counts remain zero;
5. manual payment-status Web/API routes are absent;
6. Mobile manual payment-status mutation UI/API is absent;
7. no GET order detail writes IPS cache;
8. derived payment-state writes are centralized in the projector;
9. Orders 28 and 134, if still present and unchanged in meaning, project to cancelled/cancelled without altering their payment ledger;
10. historical COD provenance anomalies remain documented but untouched;
11. validation errors survive normal HTTP redirects/renders;
12. CMS static remains 983/983;
13. Mobile, Expo and OpenAPI gates pass;
14. no production Android build or Play action occurs;
15. Build23 remains deferred for further grouped functionality.

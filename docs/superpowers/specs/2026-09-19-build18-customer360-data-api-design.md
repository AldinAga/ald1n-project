# Build18 Customer 360 Data & API Foundation Design

Date: 2026-09-19
Repository: `AldinAga/ald1n-project`
Approved baseline: `e5bbdc248fdfc43da5af36ff096f99f8b2bcd054`
Planned implementation batch: Batch170
Planned implementation report after this spec checkpoint: next sequential report number

## 1. Objective

Extend the existing Customer Portal administration into a Customer 360 read model and internal CRM-note capability without creating a parallel customer identity, a parallel CRM namespace, or duplicate profitability logic.

Customer 360 must remain an administrative view over existing canonical business authorities:

- `users` / `User` for registered customer identity,
- `orders` / `Order` for purchases and balances,
- existing Customer Portal services and API routes for customer administration,
- After-sales, Warranties, Receivables, and Portal Conversations for customer activity,
- `ManagementReportService` as the future profitability authority in Batch172/173.

## 2. Non-goals for Batch170

Batch170 does not:

- create a second customer or CRM master table,
- introduce a new `/api/v1/admin/crm` namespace,
- auto-create portal users from Direct Sale or anonymous orders,
- auto-link customers by matching phone, email, name, or other heuristic,
- calculate gross profit, net contribution, LTV, GMROI, or other profitability metrics,
- change Mobile Customer 360 UI,
- restore Product Variants,
- run EAS, OTA, native build, or Google Play actions.

Mobile Customer 360 workspace belongs to Batch171. Profitability authority and integration belong to Batch172/173.

## 3. Existing authority to reuse

### 3.1 Customer administration

`CustomerPortalController` already owns the Admin API surface for customer list/detail, customer creation, invitations, order linking, and session revocation under the existing `system.manage_users` permission boundary and `customer_portal` module visibility.

`CustomerPortalAdminService` remains the mutation authority for customer creation, order linking, invitation, and session revocation.

### 3.2 Customer-facing aggregation

`CustomerPortalService` already aggregates customer-facing orders, documents, payments, warranties, after-sales, service appointments, installments, timeline entries, and conversations. Batch170 must not duplicate customer-facing behavior. Customer 360 may reuse the same canonical tables and domain semantics but is an admin read model with internal-only fields.

### 3.3 Profitability

`ManagementReportService` remains the only planned profitability authority. Batch170 must not implement parallel COGS/margin/net-contribution calculations.

## 4. Architecture

### 4.1 Read-model service

Introduce a focused `Customer360Service` as an admin read-model composer. It does not own customer identity and does not write business data.

Responsibilities:

- compute customer commercial aggregates from canonical operational orders,
- load customer service/after-sales/communication counters,
- build a merged administrative timeline from existing authoritative records,
- return CRM notes from the dedicated internal-note table,
- expose unlinked buyer/order candidates without identity inference.

The service must be query-only except for delegating CRM-note reads. CRM-note creation stays in the existing admin customer workflow and is audited.

### 4.2 Internal CRM notes

Add one dedicated table for internal notes, proposed name:

`customer_crm_notes`

Fields:

- `id` bigint primary key,
- `user_id` foreign key to the customer `users.id`,
- `author_user_id` foreign key to the staff `users.id`,
- `body` text,
- `created_at`,
- `updated_at`.

Batch170 behavior is append-only through the API: create and read only. No edit/delete endpoint is introduced. This avoids silent historical rewriting and keeps the initial CRM scope minimal.

The API validates trimmed note text, minimum 2 characters, maximum 5000 characters.

Every note creation emits an audit event, using the existing audit infrastructure, with event key similar to `customer_crm.note_created` and without copying sensitive free-text into broad metadata beyond what is necessary.

### 4.3 No duplicated timeline table

Customer 360 timeline is assembled at read time. Do not create a generic CRM event table that copies other domain events.

Initial timeline sources:

- non-cancelled linked orders,
- `PortalOrderLinkHistory`,
- `customer_crm_notes`,
- After-sales cases,
- Portal conversations.

Each item has a stable shape:

- `type`,
- `occurred_at`,
- `title`,
- `summary`,
- optional source identifiers such as `order_id`, `conversation_id`, or `after_sales_case_id`,
- optional actor summary for internal notes/linking where already authoritative.

Items are sorted descending by `occurred_at` and bounded to a server-controlled limit.

## 5. Customer 360 commercial aggregates

For a registered customer, use operational orders with `orders.user_id = customer.id`.

Cancelled orders are excluded from commercial totals.

The first Customer 360 summary contains:

- `orders_count`: count of linked, operational, non-cancelled orders,
- `lifetime_revenue_rsd`: sum of `subtotal_rsd` across those orders,
- `average_order_value_rsd`: `lifetime_revenue_rsd / orders_count`, or `0` when there are no counted orders,
- `outstanding_rsd`: sum of `max(subtotal_rsd - paid_total_rsd, 0)` across linked operational non-cancelled orders,
- `last_purchase_at`: latest purchase/order timestamp among counted orders, nullable,
- `active_after_sales_count`: customer-linked after-sales cases not in terminal closed/rejected states,
- `active_warranties_count`: active non-expired warranties linked through the customer/order ownership authority,
- `open_conversations_count`: portal conversations not closed,
- `unread_staff_messages_count`: public portal messages unread by staff for this customer.

Batch170 intentionally does not return fake or placeholder profitability fields.

## 6. Unlinked buyer/order candidates

Orders with `user_id = null` must not be automatically converted to registered customers and must not be matched automatically to a user.

Add a read-only endpoint under the existing Customer Portal namespace for unlinked buyer/order candidates. The endpoint returns only canonical order snapshot facts, such as:

- order id and number,
- status,
- buyer/shipping full name,
- buyer/shipping email when available,
- buyer/shipping phone when available,
- subtotal,
- creation timestamp.

It may support a plain server-side `q` search over order number and existing buyer snapshot fields.

The endpoint must not return `suggested_user_id`, confidence scores, inferred matches, or automatic merge actions.

The existing explicit `linkOrder` workflow remains the only ownership-link mutation path.

## 7. API design

Keep all Batch170 additions inside the existing namespace and permission group:

`/api/v1/admin/customer-portal/...`

Planned additions:

- extend `GET /admin/customer-portal/users/{user}` with a `customer_360` object and `crm_notes` collection,
- `POST /admin/customer-portal/users/{user}/crm-notes` to append one internal CRM note,
- `GET /admin/customer-portal/unlinked-buyers` for read-only unlinked order/buyer candidates.

Do not create a parallel `/crm` root.

All routes remain protected by the existing authenticated/active middleware, `system.manage_users`, and `customer_portal` module visibility. CRM-note creation additionally uses the existing `throttle:admin-write` pattern.

## 8. Controller and service boundaries

`CustomerPortalController` remains the HTTP authority for customer detail and customer-level admin actions.

Recommended implementation split:

- `Customer360Service`: read-model composition,
- `CustomerCrmNote` model: table mapping,
- `CustomerPortalAdminService`: add a narrow append-note mutation method or delegate to a small dedicated note service if that keeps mutation code clearer,
- `CustomerPortalController`: serialize Customer 360 detail and accept note creation,
- existing `linkOrder` remains unchanged and authoritative.

Do not put aggregate SQL or note-write business logic directly in React Native or duplicate it in a second controller family.

## 9. OpenAPI contract

Update all three canonical OpenAPI copies byte-identically:

1. `apps/cms/current/docs/openapi.yaml`
2. `apps/mobile/current/docs/openapi.yaml`
3. `packages/api-contract/openapi.yaml`

Document:

- Customer 360 summary schema,
- Customer 360 timeline item schema,
- CRM note schema and create request,
- unlinked buyer/order candidate schema,
- extended customer detail response,
- new CRM-note POST route,
- unlinked-buyers GET route,
- existing permission expectations and error envelopes.

No Product Variant fields may appear.

## 10. Database and migration rules

Create exactly one new migration for `customer_crm_notes` unless implementation evidence proves an existing suitable internal-note table can be safely reused without changing semantics.

Migration requirements:

- additive only,
- foreign keys/indexes for `user_id`, `author_user_id`, and timeline ordering,
- no destructive rewrite of existing customer/order data,
- safe rollback drops only the new table,
- no automatic backfill required.

The migration must not alter order ownership or create users.

## 11. Testing strategy

Batch170 must use TDD and include a focused contract/feature test surface proving at least:

1. Customer 360 aggregates include only linked operational non-cancelled orders.
2. Outstanding amount cannot become negative per order.
3. AOV is zero for a customer with no counted orders.
4. Last purchase is nullable when no qualifying order exists.
5. CRM notes are internal admin data and require `system.manage_users`.
6. CRM-note create is append-only and audited.
7. Customer role validation remains enforced.
8. Unlinked-buyers endpoint returns only `user_id = null` orders.
9. Unlinked-buyers endpoint does not infer or mutate customer ownership.
10. Existing `linkOrder` remains the only explicit ownership mutation path.
11. Existing Customer Portal create/invite/session/conversation flows remain intact.
12. Product Variants remain decommissioned.
13. Three OpenAPI copies remain byte-identical.

Canonical quality gates after implementation:

- `php bin/static-check.php` => `Ukupno: 983, neuspešno: 0` or the new expected count if Batch170 intentionally expands the canonical static gate and documents that expansion,
- focused CMS tests/smoke => zero failures,
- Mobile typecheck => RC 0 because Batch170 must update Mobile contract types/client parity,
- Mobile validator from `apps/mobile/current` => `Ukupno FAIL: 0`,
- exact three-copy OpenAPI SHA parity.

## 12. Laravel -> Mobile parity rule

Every functional Laravel/CMS change in this project must have an adequate Mobile application counterpart. A backend batch is not considered feature-complete in isolation.

For Batch170 this requires, in the same source commit:

- Mobile API response/request types for every new Customer 360, CRM-note, and unlinked-buyer contract,
- Mobile API client methods for every new Laravel route that the application will consume,
- central query-key support for the new customer-detail and unlinked-buyer reads,
- Mobile validator assertions that pin those contracts and keep Product Variants decommissioned,
- exact OpenAPI parity across CMS, Mobile, and `packages/api-contract`.

Batch170 does not redesign the Customer Portal screens. Batch171 is a mandatory continuation of the same Customer 360 feature and consumes these contracts to build the visible Mobile workspace. Customer 360 is not considered fully feature-complete until Batch171 passes.

Infrastructure-only Laravel changes may be exempt from UI work only when the batch explicitly documents why no Mobile behavior exists to mirror.

## 13. Future profitability integration

Batch172 extends the existing `ManagementReportService` with the approved advanced profitability dimensions.

Batch173 integrates those canonical profitability outputs into Customer 360. Customer 360 must consume that service/API authority rather than calculate margin, COGS, contribution, LTV, turnover, or GMROI itself.

This separation is mandatory to prevent two financial truth sources.

## 14. Acceptance criteria for Batch170

Batch170 is complete only when:

- existing Customer Portal behavior is preserved,
- every new Laravel Customer 360 API contract has matching Mobile types/client/query-key/validator parity in the same Batch170 source commit,
- Customer 360 data is available for registered customers,
- internal CRM notes can be appended and read by authorized staff,
- unlinked buyers/orders can be discovered without automatic matching,
- no parallel customer identity or `/crm` namespace exists,
- profitability remains deferred to the Management Reports authority,
- no Product Variants return,
- tests and canonical gates pass,
- OpenAPI copies are byte-identical,
- source is committed/pushed with a clean allowlisted worktree except known `.htaccess` drift and the current operation report,
- no EAS/OTA/native-build/Google-Play action is performed,
- Batch171 remains mandatory before the overall Customer 360 feature is considered complete in the application.

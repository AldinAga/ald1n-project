# Laravel Admin Order Buyer-First Web Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the end recipient's shipping contact details the first right-side desktop card and the first mobile-web content card; order remaining Laravel admin order sections by operational lifecycle, without touching existing mutations.

**Architecture:** Adapt the existing Blade order detail and canonical CSS, not the presenter/backend contract. Move the one existing buyer card into a responsive first-row grid next to a compact, status-derived "Sledeći korak" card. Preserve the existing Blade forms, IDs, endpoints, CSRF and all authorization conditions; keep logistics actions distinct from delivery confirmation and payment.

**Tech Stack:** Laravel 13/PHP 8.4, Blade, canonical `public/assets/css/ald1n-ui-v2.css`, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-10-10-ald1n-mobile-operational-workspace-v2-design.md`, especially §3.3 and §6.0/W0.

## Global Constraints
- Base on freshly verified GitHub `main`, `AGENTS.md`, latest `docs/operations`; never reset/merge an existing dirty production checkout.
- Scope: `apps/cms/current/resources/views/admin/orders/show.blade.php`, canonical CSS, and targeted tests (or a small new Blade partial only if justified).
- Do NOT alter source presenter snapshots, database schema, permissions, payment semantics, routes/API, native/release files, Docker/hosting or production.
- Preserve `#order-workspace-customer`, `#order-workspace-items`, `#order-workspace-payments`, `#order-workspace-logistics-actions`, `#order-workspace-documents`, `#order-workspace-timeline`, and other existing fragment targets.
- Never conflate the end-recipient shipping snapshot with the order-creator user or supplier/responsible person.
- Do not invent a recipient email: canonical shipping snapshot currently has name, phone, address, city/postal code and note, but not a buyer email.
- Local audit tests are evidence; production deployment requires independent owner gate.

## Review Focus
1. For a narrow desktop viewport (1024–1280px), buyer card must be right-first without horizontal overflow; assert layout CSS and rendered DOM ordering.
2. On one-column mobile Web, buyer name/phone/address/note must come before action forms and items in accessible DOM order; regression test.
3. Missing phone/address/note must render neutral placeholders and no malformed `tel:` links; regression test.
4. A Direct Sale order must not acquire fake courier workflow; keep existing completion/commission presentation semantics; regression test.
5. Completed/COD/partially paid orders retain original action eligibility and exact payment/shipment/delivery forms, including CSRF and confirmations; regression test.

---

### Task 1: Buyer card is first on desktop-right and mobile-top

**Files:**
- Modify: `apps/cms/current/resources/views/admin/orders/show.blade.php` (current main around lines 155–209 and 632–736).
- Modify: `apps/cms/current/public/assets/css/ald1n-ui-v2.css` (existing `.order-workspace-*` rules around 2644–2687).
- Test: `apps/cms/current/tests/Feature/OperationalOrdersCommissionsTest.php` (or dedicated `tests/Feature/AdminOrderBuyerFirstLayoutTest.php` using same fixture conventions).

**Interfaces:**
- Consumes: presenter order keys `shipping_full_name`, `shipping_phone`, `shipping_address`, `shipping_postal_code`, `shipping_city`, `customer_note`.
- Produces: one HTML section with `id="order-workspace-customer"` inside a `data-buyer-first-layout` grid and safe `tel:` / copy actions only if value exists; no API changes.

- [ ] **Step 1: Write failing rendered-response tests**: `test_admin_order_buyer_card_precedes_action_and_items_in_dom` asserting buyer section occurs exactly once, before the command/actions and items; assert recipient data, name, phone, postal code and customer note; assert assignee name is outside buyer card. Add a missing-phone test and a responsive CSS contract check for grid areas.
- [ ] **Step 2: Run failing tests**: `cd apps/cms/current && php artisan test --filter AdminOrderBuyerFirstLayoutTest` (use the actual chosen class); expected failing assertions on current markup.
- [ ] **Step 3: Modify Blade/CSS**: wrap the compact existing command and moved buyer section into the new `order-priority-layout`. Place buyer first in DOM; use CSS grid areas to position buyer **right** and next step **left** on desktop. Collapse to one column buyer-first below desktop breakpoint. Move original `Dostava` section without cloning; preserve anchor. Remove unneeded full-width command height above buyer. Render `tel:` only for sanitizable non-placeholder numeric phone.
- [ ] **Step 4: Run target tests (GREEN)**: same command, expected PASS.
- [ ] **Step 5: Commit focused change**: `git add apps/cms/current/resources/views/admin/orders/show.blade.php apps/cms/current/public/assets/css/ald1n-ui-v2.css apps/cms/current/tests/Feature/AdminOrderBuyerFirstLayoutTest.php && git diff --cached --check && git commit -m "feat(cms): prioritize end-customer contact in admin order detail"` (stage actual file set, no wildcard).

### Task 2: Reorder subsequent cards along real order-processing lifecycle

**Files:**
- Modify: same Blade/CSS and layout test from Task 1.
- Verify: `apps/cms/current/tests/Feature/OrderDeliveryWorkflowTest.php`.

**Interfaces:**
- Consumes: existing `$actions`, `$permissions`, `$urls`, `$shipment`, `$delivery`, `$order`, saved form defaults.
- Produces: flow **buyer → next allowed action → items/inventory → ownership → payment → shipment/tracking → real delivery → documents → timeline/history → secondary/danger actions**. No new mutation endpoint or payment state machine.

- [ ] **Step 1: Add failing layout/behavior tests** asserting anchor targets are unique, contact first, items before historical timeline, payment terms before detailed shipping evidence, and shipment and delivery forms stay separate (different action/confirmation strings). Add tests for Direct Sale and COD vs bank transfer existing behavior.
- [ ] **Step 2: Run tests and confirm RED** on current layout.
- [ ] **Step 3: Group existing sections without duplicating forms**. Replace long action stack with compact links to original anchor positions; move shipment/delivery detailed sections into operational chronology. Leave existing dangerous operations collapsed with original confirmations and CSRF. Make advanced forms accessible by their existing anchors.
- [ ] **Step 4: Re-run tests and inspect rendered layout** at wide/narrow viewport where browser access exists; ensure focus/reading order follows buyer-first order and no unexpected sticky obstruction. PASS requires checks for order status new, shipped, completed and direct sale.
- [ ] **Step 5: Commit targeted changes**, with exact file allowlist and `git diff --check`.

### Task 3: Canonical regression, isolation and delivery report

**Files:**
- Create (execution): one numbered `docs/operations/<next>-<batch>-ORDER-BUYER-FIRST-WEB-REPORT.md` as required by latest project rules; derive batch/report number from current operations, not this plan.
- No unrelated application edits.

- [ ] **Step 1:** Run `php bin/static-check.php` from `apps/cms/current`, expect `Ukupno: 983, neuspešno: 0` on canonical host; run targeted Laravel feature tests, verify no API/OpenAPI drift.
- [ ] **Step 2:** Confirm `git diff --check`, exact changed-file allowlist; no native, auth, database or release edits.
- [ ] **Step 3:** Record source/target SHAs, test commands, PASS/FAIL, blockers and nondeployment status. Commit on a new isolated feature branch after gates; do not fast-forward main or deploy without separate owner-authorized merge/deployment procedure.

**Stop condition:** Any need to alter Laravel domain state, purchase/commission/payment semantics, API response shape or production database requires its own newly scoped batch.

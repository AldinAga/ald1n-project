# Ald1n CMS Liquid Glass — Batch 2 Archive + Header Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (native execution) and superpowers:test-driven-development. Follow checkbox (`- [ ]`) tasks, RED before GREEN.

**Goal:** Redesign archived-order irreversible confirmation outside cramped table cells and ensure authenticated EUR/RSD rate and Korpa are permanently visible on all desktop/mobile header widths.

**Architecture:** Keep existing server-side OrderArchiveService, routes, CSRF/DELETE contract, immutable retention and role checks; move only the SuperAdmin purge form into a per-order native dialog rendered outside scrollable tables and opened by a non-submitting control. Extend the existing non-generated Liquid Glass CSS layer, add tiny scoped Blade event handlers and contract tests, preserve all settings synchronization and cart authorization.

**Tech Stack:** Laravel 13, PHP 8.4, Blade, native HTML dialog and CSS, PHPunit, MariaDB 10.11.19 disposable CI.

**Spec:** docs/superpowers/specs/2026-10-18-liquid-glass-cms-design.md (owner header visibility override 2026-10-18).

## Global Constraints

- Do not repeat Batch 1; continue exactly on draft PR #29 branch `design/liquid-glass-cms-20261018`, based on GitHub main `ecfeb8e55a4af44467069bbcea578c1b95ccc528`. Keep main and production untouched.
- **Non-negotiable header:** EUR/RSD and Korpa both visible for all authenticated roles at 1440, 1250, 1024, 768, 620, 390, 320px, with menu closed. Only authorized users have rate sync / functioning cart, others see rate as read-only and an accessible non-clickable cart icon. Do not change permission middleware.
- Archive deletion may NEVER be invoked by tests on business/production data; GET/Blade render and isolated disposable CI only. Retain name=purge_reason, name=confirmation, DELETE method, CSRF, exact order number/confirmation and server-side checks.
- Scope only: `resources/views/admin/orders/archived.blade.php`, `resources/views/layouts/app.blade.php`, `public/assets/css/ald1n-liquid-glass.css` and test files (plus docs). No generated ald1n-ui-v2.css, app.css, DB schema or service/backend logic edits.
- Keep muted grayscale/taupe glass, responsive focus, modal keyboard close + focus return; no new JavaScript dependencies. Preserve existing rate sync and header interactions. Avoid triggers causing accidental form submits.
- CI uses isolated MariaDB as MySQL-specific migrations fail under SQLite; run `php artisan test ...` on actual Laravel and `php bin/static-check.php`, plus source diff guards. No remote hosting scripts / EAS / OTA / Android builds / Google Play.
- Operator-supplied full live mobile/tablet visual screenshots are acceptance evidence; do not claim browser QA PASS without observed screenshots.

## Review Focus

1. At 621–1250px a legacy media rule hides EUR/RSD; scoped later CSS must override it even when the menu is closed.
2. At 320px logo, EUR/RSD, Korpa, global search and hamburger must fit in the **visible first row**; non-essential user/notif/theme controls may collapse, no horizontal scrollbar.
3. Archived orders have multiple rows: each dialog/form/order number must target the correct distinct order, never a stale shared id/action or nested form.
4. SuperAdmin is the sole persona with purge dialog/route; staff still see restore action but cannot see functional purge control, and unauthorized cart is not clickable.
5. Dialog Escape/Cancel/focus/submit: cancel never invokes DELETE, duplicate submits blocked by existing global runtime, server verifies exact confirmation regardless of JS.

---

### Task 1 — Permanent EUR/RSD and cart header visibility

**Files:** `resources/views/layouts/app.blade.php`, `public/assets/css/ald1n-liquid-glass.css`, `tests/Feature/DashboardLegacyDesignTest.php`, `tests/Unit/LiquidGlassFoundationContractTest.php`.

**Interfaces:** Consume existing `data-exchange-rate-sync` button, read-only rate span, `orders.create` guarded `cart.index` link, role middleware; produce always-visible `.header-rate-persistent` and `.header-cart-button` markup and scoped CSS with `@media (max-width:1250px)` and `@media (max-width:620px)`.

- [ ] Write PHPUnit Feature test for authenticated SuperAdmin GET /: asserts exact EUR/RSD text/value, `header-rate-persistent`, `header-cart-button`, cart.index href, accessible labels, order of header asset; tests with non-cart-permission role verify a disabled non-link cart, not a privilege leak. Add Unit CSS contract test asserting breakpoint and display !important override.
- [ ] Run focused CI test at existing baseline HEAD; record **RED** due to missing marker/disabled markup and override.
- [ ] Implement minimal Blade change: add marker class to both rate variants and add `@else` non-clickable cart placeholder for users without `orders.create`; do not touch CSRF, sync URL or cart permission route.
- [ ] Implement later CSS overrides for persistent controls at <=1250 and <=620, compact logo and optional controls at <=620 and <=390. Keep global search and hamburger accessible; avoid global z-index/clipping modifications.
- [ ] Run focused Feature/Unit CI; expect GREEN, inspect regression menu state. Commit explicit paths only.

### Task 2 — Archived order modal separation and safety

**Files:** `resources/views/admin/orders/archived.blade.php`, `public/assets/css/ald1n-liquid-glass.css`, `tests/Feature/ArchivedOrdersLiquidGlassTest.php`, `tests/Unit/LiquidGlassFoundationContractTest.php` (optional focused assertions).

**Interfaces:** Read existing view variables $orders paginator, $query, $canPurge; preserve routes `admin.orders.restore`, `admin.orders.purge` with orderId, POST+@method('DELETE'), `purge_reason` and `confirmation`. New per-order dialog unique ids e.g. `orderPurgeDialog-{{ $order->id }}`. JS uses `[data-order-purge-open]` `[data-order-purge-close]`, dialogs rendered outside table.

- [ ] Write failing new PHPUnit Feature rendering test (GET as seeded SuperAdmin + disposable archived order or direct Blade render) and contract assertions: narrow action cell has restore form and modal opener but no nested purge form; outside table per-order dialog contains exact number, 2 required inputs, CSRF, DELETE method, cancel type=button; staff cannot access purge UI; no form posted.
- [ ] Verify RED on previous source.
- [ ] Rewrite archive row action markup to compact restore button + danger opener; move full purge dialog to after closing table (outside overflow:auto). Dialog layout: danger header + warning + retained-history note; labelled reason textarea; labelled exact order number; cancel; final destructive CTA. All ids order-specific, no nested forms. Use only server-supplied escaped Blade values.
- [ ] Add minimal inline Blade JS inside `@push('scripts')`: event delegation for opener/closer, native `showModal()`, Escape handled natively, focus returns to opener on close and form inputs reset; no scripts making POST/DELETE, no changing global `data-confirm`.
- [ ] Add CSS scoped to `.orders-archive-page`, `.order-archive-purge-dialog`; fully responsive modal width, spacing, 44px buttons, safe-area margin/padding, focus contrast, opaque readability in light/dark. At reduced transparency, solid background; forced-colors retains outlines.
- [ ] Verify GREEN with real PHP Feature and Unit tests and negative role test. Commit explicit changes only.

### Task 3 — Integrated Batch 2 gates and review

**Files:** Tests and existing CI `.github/workflows/liquid-glass-cms-qa.yml` (only if necessary to add focused Test filename); docs/issue reference.

- [ ] Ensure CI runs `php artisan test -c phpunit.mysql.xml tests/Feature/ArchivedOrdersLiquidGlassTest.php --no-ansi`, alongside Batch1 Unit and Dashboard feature suites, and `php bin/static-check.php` with zero failed checks.
- [ ] Verify generated CSS and token blobs remain identical to main, no new migrations/controllers/routes, and the draft PR remains unmerged. Inspect seven original Batch1 files + authorized new paths only.
- [ ] Perform viewport/browser screenshot review (1440,1024,768,620,390,320) in both themes: header no lost EUR/RSD or cart, dialogs per order, keyboard focus/ESC and background scroll, optional controls have a route from hamburger. If unavailable, explicitly PENDING (never PASS).
- [ ] Update draft PR #29 and Issue #28 with exact commit, CI run and remaining visual-review gate. Do not merge or deploy; proceed to Batch3 using same feature branch.

## Deliverable

Batch2 source + focused automated QA green; authenticated visual viewport gate clearly identified. No production deployment, DB changes, backup or release action. Batch3 remains sticky editor and Batch4 final integration/release.

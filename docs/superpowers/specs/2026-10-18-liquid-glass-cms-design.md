# Ald1n CMS Liquid Glass — Design Specification

**Status:** Owner-confirmed four-batch scope; revised neutral/glass palette direction 2026-10-18. The implementation plan still requires owner review before source changes.
**Date:** 2026-10-18
**Source baseline:** `ecfeb8e55a4af44467069bbcea578c1b95ccc528` (merged PR #27, CMS release Reports742–744).
**Precondition:** Report745 / Batch738 PASS: fresh Laravel backup ID 158 verified, previous keeper 157 verified, stable retention exactly two, conservative Deep Cleanup completed. No further cleanup required to begin this UI work.
**Reference:** [Issue #28](https://github.com/AldinAga/ald1n-project/issues/28); owner's two annotated UI screenshots from 2026-10-17.

## Intent and boundaries

Modernize the **existing Laravel web CMS** into a premium, restrained "Liquid Glass" operator UI, improving hierarchy, layout and responsiveness without sacrificing legibility or changing how the system operates. This is a visual/refinement phase—not a rewrite and **not** a Mobile Expo redesign.

Target: authenticated CMS shell and common presentation components, archived-order irreversible-action workflow, product-create/edit sticky bottom actions. Leave the business domain, permissions, API contracts, database schema, batch state, background jobs and build/OTA state untouched.

**Owner override (2026-10-18):** There is **no requirement** to preserve the former orange Ald1n UI palette. Use muted, low-saturation graphite / smoky blue-gray surfaces and soft taupe-bronze UI accents; ambient glows or "mrlje" must be subdued, soft-edged, and non-saturated. Keep semantic success/warning/danger colors distinguishable and maintain accessible contrast. This applies to CMS visual styling only: do not modify official brand assets or substitute the unrelated Numanović furniture brand.

## Updated color direction — approved 2026-10-18

The subdued Liquid Glass concept replaces the previous requirement to retain the existing orange CMS primary color. Use a **non-generated** CSS overlay for palette overrides; preserve the generated token file and avoid touching the existing theme preference/JS controller.

| CSS role | Dark | Light |
|---|---|---|
| Background | `#13181C` | `#F3F5F6` |
| Elevated surface | `#1B2329` | `#FCFDFE` |
| Surface muted | `#232D34` | `#EEF1F3` |
| Text | `#E9EEF1` | `#24313A` |
| Muted text | `#B6C2C9` | `#52606A` |
| Hairline border | `#36434C` | `#CBD3D9` |
| Primary accent | `#BFA590` | `#68584D` |
| Primary on-color | `#172027` | `#FFFFFF` |

The initial static contrast checks for accent vs background and muted text vs surface exceed WCAG AA text contrast on this selected palette. Recheck rendered colors and states in browser screenshots; these checks alone cannot establish whole-UI accessibility. Keep ambient glows to low-opacity **gray-blue and taupe**, without saturated orange/blue/red radiating blobs or distracting gradients. A flat calm fallback is fully acceptable.

## Architecture

The authenticated shell `apps/cms/current/resources/views/layouts/app.blade.php` currently loads `public/assets/css/app.css` followed by the generated `public/assets/css/ald1n-ui-v2.css`. Both are established production layers; the latter explicitly declares itself auto-generated from canonical design tokens.

1. Add a small, purpose-built, **versioned non-generated** stylesheet `apps/cms/current/public/assets/css/ald1n-liquid-glass.css` after the existing two, with cache-busting `filemtime` and a deterministic fallback. Do not hand-edit the generated `ald1n-ui-v2.css`.
2. Use existing CSS variables and two existing `data-theme` modes (`light`, `dark`); honor `data-theme-mode=auto` through its existing resolver. Avoid a competing theme state or a new CSS/JS dependency.
3. Implement isolated, class-scoped styling for the header/navigation, elevated panels, filters, tables, dialogs, action bars and form focus states. Keep dense table cells and editable fields near opaque for accessibility.
4. Use bounded background blur only on floating/elevated surfaces (header, menus, drawers, action bars, dialog shell), with solid fallback if backdrop-filter is unavailable, and reduced-transparency/reduced-motion fallbacks.
5. Introduce no large global z-index or new overlay that blocks menus, search, notifications, native selects or validation; document z-index/safe-area boundaries and test these cases.

## Visual language

- Appearance: restrained frosted surface, subtle specular highlight and a thin contrasting hairline border; content must remain readable on both themes.
- Hierarchy: a low-noise app background, elevated app shell and navigation, clear page headings, cohesive filter/table/panel grouping; primary buttons use a high-contrast **muted neutral-warm accent**, secondary controls quieter, irreversible controls consistently danger-coded.
- Radius/spacing: reuse token system with coherent 12–20px surfaces; avoid wrapping buttons simply to fill an arbitrary width.
- Typography: current sans/system stack; no remote font loading, no font binaries and no drastic spacing changes to existing forms.
- Accessibility: visible focus outline, text contrast, >=44px touch targets where applicable, keyboard-accessible actions, no color-only status meaning, no horizontal overflow at 320px.
- Responsiveness: desktop 1440px, tablet 1024px/768px, phone 390px/320px. Respect `env(safe-area-inset-bottom)`, keyboard resize and content padding beneath sticky UI.

## Owner mandatory header invariant — 2026-10-18, Batch 2

**EUR/RSD exchange-rate indicator and Korpa icon MUST remain visible in the authenticated header at every responsive breakpoint, including 1250/1024/768/620/390/320 px and while the mobile menu is closed.** No display:none, conditional hiding, clipped/overflowed toolbar or move to the hamburger menu for these two controls. Preserve live rate value and the existing sync button for authorized users (read-only rate for others). Preserve cart badge count for users with orders.create. For authenticated roles lacking orders.create, render an always-visible **non-interactive cart icon with an accessible label** instead of exposing an unauthorized cart link; permission middleware stays intact. Optional header controls may collapse at very narrow widths while global search and hamburger remain accessible. This owner override supersedes earlier optional compact-header behavior and is part of Batch 2, not a fifth batch. Verify in actual responsive browser review.

## Owner header override — 2026-10-18, Batch 3 (supersedes Batch 2 cart placeholder)

The authenticated header MUST retain the three controls **Pretraga, EUR/RSD kurs, Korpa** at their existing physical/DOM positions and relative sequence, at desktop, tablet and narrow/mobile widths **with the hamburger closed**. Do not relocate the search, exchange rate or cart into the mobile menu, swap their order, or hide them with responsive CSS. Preserve the existing header layout grouping: rate first, cart next if authorized, global search in its current place, hamburger last on mobile. Existing optional alerts/account/theme controls may collapse only at the breakpoint needed for space.

**Correction to previous Batch 2 policy:** Korpa is **not rendered at all** for a user without `orders.create`: no link, icon, disabled placeholder or badge. Maintain the existing `@can('orders.create')` guard around the real cart and preserve cart permission middleware. This correction takes priority over the earlier “visible non-clickable cart icon” instruction.

Header global search remains a visible focusable button for authenticated users in its original slot; its API and result permissions remain enforced server-side. Authorized admins retain clickable EUR/RSD sync; other authenticated users see the read-only currency indicator. Do not introduce a new secondary search control or duplicate rate/cart.

Responsive visual acceptance must confirm the rate, cart (authorized only), and search in the original positions at 1440/1250/1024/768/620/390/**320px**, under both light and dark themes. Auto theme and open/closed menu must not change the rule. **Only actual browser screenshots or computed layout inspection can complete the physical-visibility gate**; PHPUnit source/DOM tests alone are not proof that controls fit without clipping.

## Required screen A — archived orders / destructive action

**Source:** `apps/cms/current/resources/views/admin/orders/archived.blade.php`.
**Current behavior:** six-column archive table, standard reversible restore button, SuperAdmin-only inline `<details>` irreversible removal form (`purge_reason`, exact `confirmation`, `DELETE` method) inside a narrow action cell.

Desired outcome:

- Keep archive metadata readable, grouped and searchable on desktop/mobile.
- Separate `Opozovi arhiviranje` as a normal secondary action from `Trajno obriši porudžbinu` as an isolated irreversible action; no crowded forms inside narrow table cells.
- Place destructive confirmation in a sufficiently wide, accessible separate disclosure/dialog/card: heading, clear irreversible consequences, retention explanation, required reason textarea, required exact order-number input, final danger CTA. Ensure a dismiss/close path that does not submit.
- Preserve existing `admin.orders.restore` and `admin.orders.purge` endpoints, CSRF, method override, posted field names, server-side authorization, validation, confirmation and audit/retention rules. No real order deletion in tests.
- Avoid accidental destructive action and duplicate submission; leave archived orders out of operational lists as before.

## Required screen B — product editor bottom actions

**Source:** `apps/cms/current/resources/views/admin/products/form.blade.php`.
**Current behavior:** `.product-editor-action-bar` contains a message and actions for `Direktna prodaja` (contextual link), `Sačuvaj kao nacrt` (submit `name=save_draft`, `formnovalidate`) and primary `Sačuvaj izmene`/`Kreiraj artikal`.

Desired outcome:

- Desktop: information left, grouped right-aligned actions, consistent height and spacing. Primary save must be most visually salient but not unnecessarily wide; draft quieter; direct sale tertiary/contextual.
- Tablet and mobile: intentional stacking, no truncated labels, no collisions, no horizontal scrolling. When form is long, sticky action bar stays visible without covering final fields, dropdowns, validation and errors.
- Add safe-area padding and document scroll/focus behavior. The bar must not obscure the on-screen keyboard or validation jump.
- Preserve create vs edit label/state and the exact submit semantics: draft bypasses client validation only as currently intended, primary save validates, direct sale remains a link with existing guards (not an implicit submit).
- Preserve the duplicate inline save controls if required by the current form; avoid changing request payload or authorization.

## Four-batch execution, only after design/plan review

1. **Batch 1 — Foundation:** CSS overlay, shell inclusion, light/dark/auto and reduced-effects fallbacks, shared elevations and layout, focused CSS + Blade contract tests. No production deployment.
2. **Batch 2 — Archived orders:** danger/disclosure re-layout, keyboard/mobile behavior, existing backend-contract tests (no real destructive operation). Build on the same isolated feature branch.
3. **Batch 3 — Product form:** sticky-bar markup and CSS layout, semantic button priority, safe-area and validation behavior, create/edit tests.
4. **Batch 4 — Integration and release QA:** full `php bin/static-check.php`, focused PHP tests, visual viewport snapshots (desktop/tablet/mobile, light/dark), accessibility/regression checks, independent PR review and a separately approved safe hosting deployment when gates pass.

Each batch is an **implementation unit**, not an opportunity for exploratory host cleanup or repeated SQL restores. The pre-Liquid Glass Report745 is final for its hygiene scope.

## Gates and verification

- Develop on a new isolated GitHub branch based on exact `ecfeb8e55a4af44467069bbcea578c1b95ccc528`, **never** by overwriting the dirty/stale physical host checkout. Do not run git reset/clean/pull against production.
- The owner-approved visual direction does not authorize product deployment. GitHub QA, code review, exact source/host preflight and explicit release approval precede any production overlay.
- No new migrations, composer/npm installation, Live DB writes, restore-test DB mutation, Android EAS OTA/native build, Google Play or iOS actions in this phase.
- New Laravel visual pages must continue to render existing labels, CSRF and request contracts; no `purge` or real data-altering QA.
- Verify generated CSS remains untouched and new stylesheet actually loads **after** it.
- Acceptance: visual screenshots/screens of both required sections in each responsive breakpoint, readable dark/light themes, keyboard/focus and negative/error path tests, and no mobile API behavior regression. If visual-browser automation isn't accessible, record the missing visual evidence explicitly instead of claiming PASS.
- Preserve post-release audit exceptions (independent historical PR27 approval / provider DBA placeholder) as historical facts, without reopening successful deployment.

## Explicitly out of scope

- Full rework of all bespoke CMS pages, Laravel services, authorization, app data model, business deletes, new page navigation architecture or rewriting the CSS build pipeline.
- Changes to official logo/brand assets, Mobile Expo Liquid Glass, Android/iOS release, OAuth and payments changes.
- Removing backup #158/#157 or cleaning unknown directories/reports until archived and authorized.

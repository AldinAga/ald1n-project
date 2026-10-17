# Ald1n CMS Liquid Glass — Design Specification

**Status:** Owner-approved visual direction and four-batch scope; written design awaiting owner review.
**Date:** 2026-10-18
**Source baseline:** `ecfeb8e55a4af44467069bbcea578c1b95ccc528` (merged PR #27, CMS release Reports742–744).
**Precondition:** Report745 / Batch738 PASS: fresh Laravel backup ID 158 verified, previous keeper 157 verified, stable retention exactly two, conservative Deep Cleanup completed. No further cleanup required to begin this UI work.
**Reference:** [Issue #28](https://github.com/AldinAga/ald1n-project/issues/28); owner's two annotated UI screenshots from 2026-10-17.

## Intent and boundaries

Modernize the **existing Laravel web CMS** into a premium, restrained "Liquid Glass" operator UI, improving hierarchy, layout and responsiveness without sacrificing legibility or changing how the system operates. This is a visual/refinement phase—not a rewrite and **not** a Mobile Expo redesign.

Target: authenticated CMS shell and common presentation components, archived-order irreversible-action workflow, product-create/edit sticky bottom actions. Leave the business domain, permissions, API contracts, database schema, batch state, background jobs and build/OTA state untouched.

Preserve Ald1n's actual brand palette from `packages/design-tokens/ald1n-operator.json` (light primary `#C45116`, dark primary `#FF8A3D`, associated text/surface/danger tokens). Do **not** substitute Numanović's separate purple furniture brand identity.

## Architecture

The authenticated shell `apps/cms/current/resources/views/layouts/app.blade.php` currently loads `public/assets/css/app.css` followed by the generated `public/assets/css/ald1n-ui-v2.css`. Both are established production layers; the latter explicitly declares itself auto-generated from canonical design tokens.

1. Add a small, purpose-built, **versioned non-generated** stylesheet `apps/cms/current/public/assets/css/ald1n-liquid-glass.css` after the existing two, with cache-busting `filemtime` and a deterministic fallback. Do not hand-edit the generated `ald1n-ui-v2.css`.
2. Use existing CSS variables and two existing `data-theme` modes (`light`, `dark`); honor `data-theme-mode=auto` through its existing resolver. Avoid a competing theme state or a new CSS/JS dependency.
3. Implement isolated, class-scoped styling for the header/navigation, elevated panels, filters, tables, dialogs, action bars and form focus states. Keep dense table cells and editable fields near opaque for accessibility.
4. Use bounded background blur only on floating/elevated surfaces (header, menus, drawers, action bars, dialog shell), with solid fallback if backdrop-filter is unavailable, and reduced-transparency/reduced-motion fallbacks.
5. Introduce no large global z-index or new overlay that blocks menus, search, notifications, native selects or validation; document z-index/safe-area boundaries and test these cases.

## Visual language

- Appearance: restrained frosted surface, subtle specular highlight and a thin contrasting hairline border; content must remain readable on both themes.
- Hierarchy: a low-noise app background, elevated app shell and navigation, clear page headings, cohesive filter/table/panel grouping; primary buttons prominently orange, secondary controls quieter, irreversible controls consistently danger-coded.
- Radius/spacing: reuse token system with coherent 12–20px surfaces; avoid wrapping buttons simply to fill an arbitrary width.
- Typography: current sans/system stack; no remote font loading, no font binaries and no drastic spacing changes to existing forms.
- Accessibility: visible focus outline, text contrast, >=44px touch targets where applicable, keyboard-accessible actions, no color-only status meaning, no horizontal overflow at 320px.
- Responsiveness: desktop 1440px, tablet 1024px/768px, phone 390px/320px. Respect `env(safe-area-inset-bottom)`, keyboard resize and content padding beneath sticky UI.

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
- Branding changes, Mobile Expo Liquid Glass, Android/iOS release, OAuth and payments changes.
- Removing backup #158/#157 or cleaning unknown directories/reports until archived and authorized.

# Ald1n CMS Liquid Glass — Batch 3 Product Editor and Header Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans and superpowers:test-driven-development. Tasks use checkboxes (- [ ]) and real RED → GREEN cycles.

**Goal:** Refine the Laravel product create/edit sticky actions into a compact accessible Liquid Glass footer, and enforce the owner's original header position and role-visibility rules.

**Architecture:** Keep the existing authenticated header element sequence. Remove Batch2's unauthorized cart placeholder; always display search and rate and display cart only under the original orders.create permission. Reorganize existing editor button markup without changing routes/submit fields; add CSS only to the non-generated glass overlay. Avoid creating the shared UX mobile dock when a form already has its own sticky bar.

**Tech Stack:** Laravel 13/PHP 8.4, Blade, CSS, native JS, PHPUnit 12, disposable MariaDB 10.11.19 CI.

**Spec:** docs/superpowers/specs/2026-10-18-liquid-glass-cms-design.md (Batch3 owner header override supersedes Batch2 placeholder).

## Global Constraints

- Continue Draft PR #29 isolated branch design/liquid-glass-cms-20261018 from main ecfeb8e55a4af44467069bbcea578c1b95ccc528. No repeat Batch1/2, production or build mutations.
- Header must retain original markup order: rate, authorized cart, other existing controls, search, mobile hamburger. Search and rate always visible in authenticated header, cart present only when orders.create. No disabled cart placeholder, no header navigation reorder.
- Preserve the cart badge and route authorization, original sync action permission, search API permission and button behavior.
- Keep product form action, CSRF, PUT on edit, save_draft name/value=1 with formnovalidate, primary submit without formnovalidate, guarded direct sale as link; preserve sidebar duplicate save controls and unsaved-change guard.
- Do not modify generated CSS, design tokens, database/schema, backend product services/routes, Android, OTA, backup, production or live data. Do not git pull/reset/clean on hosting.
- Test-only CI runs on disposable MariaDB; no real destructive actions. Browser visual/keyboard/viewport pass must be actually observed; otherwise pending.

## Review Focus

1. Unauthorized role has no cart *at all*, not even placeholder; authorized role retains badge link (Task1).
2. Rate/cart/search retain original locations on 1440/1250/1024/768/620/390/320 widths with hamburger closed; never hidden by media styles (Task1 and visual gate).
3. Create/edit submit semantics unchanged, primary highest visual priority, direct sale not submit and SuperAdmin guard preserved (Task2).
4. No second fixed dock overlays existing sticky editor; keep unsaved-changes and primary save keyboard handlers (Task3).
5. Keyboard/focus/validation and safe-area never hide final fields; 44px controls, no horizontal overflow at 320px, contrast/reduced-effects fallback (Task2/Task4).

---

### Task 1 — Header correction in original order

**Files:** Modify apps/cms/current/resources/views/layouts/app.blade.php, apps/cms/current/tests/Feature/DashboardLegacyDesignTest.php, apps/cms/current/public/assets/css/ald1n-liquid-glass.css, apps/cms/current/tests/Unit/LiquidGlassFoundationContractTest.php.

**Interfaces:** Existing rate/sync and search elements, @can('orders.create') on real cart link; no new endpoint.

- [ ] Step 1 RED: Feature tests: SuperAdmin has rate, cart and search in original DOM sequence, one of each and intact cart count; non-buying role contains zero header-cart-button elements, icons or cart href, yet shows rate and search. CSS contract asserts mobile search explicit display and removed header-cart-unavailable.
- [ ] Step 2: Run disposable CI test-only commit and capture expected failures.
- [ ] Step 3 GREEN: Delete @else disabled cart span. Keep original authorized link and existing positions. Remove obsolete CSS, add scoped responsive search display and no CSS order rearrangement.
- [ ] Step 4: Run real focused feature/unit tests and commit exact files.

### Task 2 — Sticky editor visual hierarchy

**Files:** Modify apps/cms/current/resources/views/admin/products/form.blade.php, apps/cms/current/public/assets/css/ald1n-liquid-glass.css; create apps/cms/current/tests/Feature/ProductEditorLiquidGlassTest.php; add unit CSS contract.

**Interfaces:** Existing GET admin.products.create and admin.products.edit, existing product-editor-form data-ux-sticky-actions, name=save_draft, data-product-save-primary, direct-sale context.

- [ ] Step 1 RED: Feature tests render create and edit in MariaDB isolation. Assert new grouping classes, valid primary submit without button-large, draft formnovalidate and value=1, guarded direct-sale link only for authorized active edit; no HTTP POST/DELETE. CSS unit tests require sticky, compact primary, tablet/mobile stacking and safe-area focus.
- [ ] Step 2: Run real CI on test-only commit, expect failures caused by missing layout changes.
- [ ] Step 3 GREEN: Add liquid-glass-product-editor-form class, group tertiary direct sale and secondary draft adjacent to compact primary; do not change sidebar buttons or route/request contracts. Add scoped CSS overlay desktop left-copy/right-controls, mobile primary full row and contextual secondary wrapping, 44px min-height, opaque fallbacks, scroll-margin and safe-area.
- [ ] Step 4: Run real tests, verify create/edit copy and no field overlap assumptions, commit.

### Task 3 — No duplicate mobile dock

**Files:** Modify apps/cms/current/public/assets/js/ux-runtime.js, unit contract and CI.

**Interfaces:** Existing createMobileActionDock(); skip only candidate forms containing data-product-editor-action-bar while preserving form's data-ux-sticky-actions, protectForms(), protectUnsavedChanges() and keyboard-save listeners.

- [ ] Step 1 RED: Write unit contract demanding a skip guard in createMobileActionDock only, with data-ux-sticky-actions preserved on editor Blade.
- [ ] Step 2: Run isolated real PHPUnit on test-only commit, expect failure.
- [ ] Step 3 GREEN: Add single candidate-selection exclusion for native editor bar; other forms unchanged. Ensure no new runtime dependency.
- [ ] Step 4: Run PHPUnit and Node 22 syntax check, commit.

### Task 4 — Batch3 integrated QA (no deploy)

**Files:** Modify .github/workflows/liquid-glass-cms-qa.yml (add focused ProductEditorLiquidGlassTest and node --check), docs PR description.

- [ ] Run real PHP lint + PHPUnit with phpunit.mysql.xml, previously passing Unit/Header/Archive, new Product Editor tests, canonical php bin/static-check.php and generated assets diff guard. Record exact run ID/counts.
- [ ] Verify changed-file manifest vs main; PR #29 stays draft and unmerged; do not alter migrations/services/permission middleware or production.
- [ ] Inspect authenticated browser at 1440/1250/1024/768/620/390/320, dark/light/auto, original header control locations, form validation, keyboard and sticky overlap. If no browser, clearly mark PHYSICAL_VISUAL=PENDING.
- [ ] Update Issue #28 and PR #29 with exact source commit, CI evidence, remaining Batch4 full review/release gates. No new backup, deploy, mobile build or Play action.

## Completion contract

Batch3 is source/CI complete only after exact-head successful real CI. No claims of visual pass without screenshots. User's no-cart-without-permission instruction overrides all earlier Batch2 placeholder text.

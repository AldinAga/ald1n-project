# Liquid Glass CMS — Batch 4 Integrated Browser and Release QA Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans and superpowers:test-driven-development. Track RED and GREEN evidence; never skip release gates.

**Goal:** Verify and, only when tests expose a concrete failure, minimally correct the Batch 1–3 Liquid Glass CMS in a disposable authenticated Chromium+Laravel environment, collecting objective screenshots and exact-commit CI receipts without production changes.

**Architecture:** Add a test-only Laravel fixture seeder and Playwright-driven Chromium smoke suite. Run after canonical PHPUnit/static gates against a newly reset explicitly named disposable MariaDB 10.11.19 database in GitHub Actions. Use a throwaway PHP artisan server bound to localhost, real login/session flow, measured CSS DOM geometry and masked synthetic test data, plus uploaded screenshots and JSON results. Keep CI fixture scripts entirely within tests/Browser, outside production release scope.

**Tech Stack:** Laravel 13/PHP8.4, PHPUnit, MariaDB 10.11.19, Node 22 Playwright Chromium headless, GitHub Actions. No production Node dependencies.

**Spec:** docs/superpowers/specs/2026-10-18-liquid-glass-cms-design.md. Owner final header rule: **Pretraga and EUR/RSD always visible at original positions; Korpa visible in original slot only if orders.create, absent otherwise**.

## Global Constraints

- Branch remains `design/liquid-glass-cms-20261018`, Draft PR #29, GitHub main `ecfeb8e55a4af44467069bbcea578c1b95ccc528`. All live hosting, provider DBA and Android OTA/build states untouched.
- Only disposable CI DB `ald1n_liquid_ci` at 127.0.0.1 is allowed to be migrated/seeded; require hard environment and hostname sentinels, otherwise fail closed. Never use .env or credentials from production. No order/product DELETE, POST updates, rate sync, or business data mutations while browsing.
- Synthetic users: SuperAdmin with buy permission and normal `user` without; synthetic active product and archived cancelled order. Fixture credentials must remain ephemeral; exclude fixture JSON and session data from artifacts.
- Retain existing owner-required original header order; do not fake browser tests using source-string assertions alone. Measure viewport geometry, computed display, overflow and interactive access on 1440/1250/1024/768/620/390/320, light/dark/auto.
- Screenshots and JSON QA receipt come from real headless Chromium rendering and are uploaded to the specific GitHub Actions job as private CI artifacts. Do not invent or manually stamp PASS.
- Preserve full previous PHPUnit/unit/feature/static and duplicate-dock tests. No source code changes unless an observed failing browser scenario justifies focused RED→GREEN correction.
- If downloading Playwright Chromium is unsupported in CI, record `VISUAL_BROWSER=HOLD` and a precise reason, never source-only PASS; do not touch production.
- Final PR review must be independent: absence of reviewer approval remains HOLD. This implementation batch does not authorize merging or deploying. The live-provider DBA audit exception remains historical; no new DBA signoff is inferred.

## Review Focus

1. 320px and 390px normal user: header rate and search visible/in viewport, cart entirely absent (including tooltip/disabled icon), hamburger usable; no hidden overflow.
2. 620–1250px SuperAdmin: rate/cart/search all visible at their **original** DOM and physical slots, not clipped, overlapped, moved into menu or obscured by active search overlay.
3. Archive SuperAdmin: correct per-order dialog opens outside table; Escape and Cancel dismiss without submitting; focus returns; inputs readable and modal within mobile viewport. User without role does not see the danger UI.
4. Product editor create/edit: sticky save/draft/Direct Sale remain correct without a second mobile dock, controls fit 320px, focus/keyboard and validation remain usable; no submit triggered.
5. Dark/light/auto themes: resolved palette and accessible focus are visible; reduced-motion/transparency and forced-colors fallbacks work; no excessive scrollbars or JS runtime exceptions.

---

### Task 1 — Browser harness (test-first setup)

**Files:** Create apps/cms/current/tests/Browser/seed-liquid-glass-preview.php, apps/cms/current/tests/Browser/liquid-glass-browser-smoke.cjs; modify .github/workflows/liquid-glass-cms-qa.yml.

**Interfaces:** PHP fixture script gets `LIQUID_GLASS_FIXTURES_PATH` from CI, creates users/product/archive **only** under exact DB/APP_ENV guards. Browser script gets `LIQUID_GLASS_FIXTURES_PATH`, `LIQUID_GLASS_ARTIFACT_DIR`, `PLAYWRIGHT_NODE_ROOT`, `LIQUID_GLASS_BASE_URL`; emits `visual-report.json` and screenshots, returns nonzero on any measurable defect.

- [ ] Step 1: Write smoke assertions and fixture set requiring real login, CSS load, permissioned header DOM and bounding rectangles for 7 widths x 3 themes. When invoked without valid fixture, fail before any browser actions (RED).
- [ ] Step 2: Add safe fixture seeder and CI setup only on localhost disposable DB; run PHPUnit baseline and all fixtures in isolated environment. Browser requires genuine Laravel session, not a generated static HTML mock.
- [ ] Step 3: Run Playwright Chromium with screenshot artifact upload `if: always()` and preservation of original test command RC. If any test fails, preserve failed screenshots and failure details, not `PASS`.
- [ ] Step 4: Verify report includes exact source SHA, all measured selectors, persona/theme/viewport matrix and screenshots (without secrets); keep CI workflow read-only permissions.

### Task 2 — Browser behavior across three UI surfaces

**Files:** Modify apps/cms/current/tests/Browser/liquid-glass-browser-smoke.cjs and only source paths needed to fix proven actual defects (if any).

**Interfaces:** Navigate authenticated `/`, `/admin/products/create`, synthetic edit route and `/admin/orders/archived`. Never submit destructive or product writes.

- [ ] Step 1: Assert dark/light/auto header geometry and no clipping at 1440,1250,1024,768,620,390,320; open/close search and mobile hamburger; confirm original order/permissions.
- [ ] Step 2: Assert archive modal works with Escape/Cancel and per-order inputs, viewport bounds and no form submission.
- [ ] Step 3: Assert create/edit sticky action labels and links, lack of duplicated UX dock, input focus accessibility and viewport fit.
- [ ] Step 4: On genuine RED, identify exact computed CSS/JS root cause, fix minimally, rerun to GREEN on same isolated workflow; otherwise do not modify business source.
- [ ] Step 5: Capture representative screenshots dark/light desktop/mobile and archive/editor in artifact bundle. Validate no production resources required.

### Task 3 — Final integrated quality, independent review and no-deploy handoff

**Files:** Review existing unit/feature coverage and docs; update PR #29 and Issue #28 with evidence (no generated CSS/DB changes).

- [ ] Step 1: Run exact-head full focused PHPUnit, `php bin/static-check.php` (0 failures), `node --check` for both tracked JS scripts, generated-assets no-diff guard and existing MariaDB integration workflow.
- [ ] Step 2: Inspect PR file manifest; no app service, auth route or backend mutation, no production secret, no user credentials in artifact; verify snapshots actual screenshot files.
- [ ] Step 3: Seek independent code review via GitHub review mechanism where a real eligible reviewer is available, never self-approve or fabricate signoff. If unavailable, flag independent-review HOLD.
- [ ] Step 4: Report exact commit, gate table, real browser/screenshots/remaining issues in draft PR #29 and issue #28. No merge, production overlay, SQL/DDL, EAS OTA/build or Play deployment.
- [ ] Step 5: Request separate explicit release approval only after all requirements and provider/host preflight are evidenced; keep historical DBA/PR27 audit exception accurately documented.

## Completion contract

`PASS_SOURCE_QA` only with exact-head PHPUnit/static+Node+existing integration; `PASS_ISOLATED_BROWSER_QA` only with authenticated Playwright results and screenshot artifact proof. Overall production `GO` remains blocked until independent review, live-specific preflight and separately approved release. No fifth implementation batch is implied by an objective QA failure; fixes belong to this Batch4.

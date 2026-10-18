# Ald1n CMS Dashboard D1 — Six KPI and Working Period Controls Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (\`- [ ]\`) syntax for tracking.

**Goal:** Ship an isolated, role-safe D1 dashboard redesign with exactly six real SuperAdmin KPI cards, a working 7/30/custom GET period selector and compact Liquid Glass layout, while preserving current-state receivables/inventory semantics and existing user dashboards.

**Architecture:** A small pure date-range resolver feeds existing Laravel \`ManagementReportService::summary()\`/\`trend()\`; a narrowly scoped controller query supplies current outstanding receivables without applying historical period. Existing one-call \`inventory()\` supplies the two inventory KPIs. A focused Blade/CSS presentation scopes new layout to SuperAdmin and keeps the current chart, real priorities and customer role views intact.

**Tech Stack:** Laravel 13, PHP 8.4, Blade, MariaDB 10.11.19, existing R5 Liquid Glass CSS/SVG renderer, vanilla HTML GET forms, existing authenticated Playwright/Chromium.

**Spec:** \`docs/superpowers/specs/2026-10-18-ald1n-dashboard-six-kpi-brand-mix-design.md\` (approved by owner). This plan covers **D1 only**, not D2 previous-period comparisons or D3 brand pie / advanced graphs.

## Global Constraints

- Re-read current \`main\`, \`AGENTS.md\`, \`docs/operations/000-LATEST.md\`, newest relevant owner/host reports and branch/PR status **before executing**. Planning baseline: \`1810f4ed2be4d351eefa1ed9e7d45cfeefc3498f\`; never treat this SHA as permanently current. Design-only branch: \`design/dashboard-six-kpi-brand-mix-20261018\`.
- R5 six-color palette and validated SVG refractive glass untouched. Do not revisit R6 dark theme issue #36 here.
- No database changes, React/Vue, new JS libraries, Android/Expo/EAS or production/build mutations. Do not merge without a separate source and deployment approval.
- SuperAdmin alone gets the exact six-card set. Other staff/customers keep existing role-aware layout; all reports queries remain authorized and use completed Laravel order snapshots.
- 7 days, 30 days (default), custom inclusive dates in \`Europe/Belgrade\`. The selector **must be functional in D1**; no dummy buttons. No percent-change badge before D2 supplies correct comparison data.
- First three KPIs respect selected period. Open receivables and purchase/sales stock valuation are labeled **current snapshot** and do not vary with date filter.
- Reuse existing \`ManagementReportService::inventory()\` only once per authorized request; no N+1 or new full-table PHP materialization.
- Keep original dashboard header links, order routes, EUR/RSD refresh, priority ordering, archiving, and RBAC unchanged.
- Required verification only: targeted date/controller tests (including legacy fallbacks), MariaDB SQL for added aggregate, canonical \`php bin/static-check.php\` (expected 983 PASS / 0 FAIL), authenticated Chromium for dark/light desktop+mobile and existing key affordances. CI runs on isolated DB. Skip unrelated whole-repo, mobile and Play checks.

## Review Focus

1. A crafted \`?period[]=30d\` or \`?date_from[]=\` is never cast to a valid date or rendered as unsafe HTML; Task 1 test verifies safe rejection and visible error.
2. An invalid custom date or start after end never silently displays 30-day data as if custom; Task 1 test checks error plus withheld financial figures.
3. An archived/cancelled/fully-paid order never inflates **current open** receivables; Task 2 test uses archived + paid + real outstanding fixtures.
4. Empty/missing report tables or missing inventory costs never get fabricated numeric zeros in SuperAdmin cards; Task 2 + 3 tests confirm unavailable/incomplete display and HTTP 200 fallback.
5. A restricted customer must not receive SuperAdmin valuation or financial data even in hidden markup, HTML comments or script payloads; Task 3 + 4 tests assert explicit absence in two role views.

---

## File map

- Create \`apps/cms/current/app/Support/DashboardPeriod.php\` — pure, strict period parsing/validation and selected dates.
- Create \`apps/cms/current/tests/Unit/DashboardPeriodTest.php\` — 7/30/custom/date-boundary/invalid-query tests.
- Modify \`apps/cms/current/app/Http/Controllers/DashboardController.php\` — period-aware report summary/trend and read-only current outstanding for SuperAdmin only.
- Create \`apps/cms/current/tests/Feature/DashboardD1Test.php\` — authenticated SuperAdmin/customer financial, snapshot, readiness and RBAC tests.
- Modify \`apps/cms/current/resources/views/dashboard/index.blade.php\` — exactly six SuperAdmin cards, functional GET controls, compact header, above-the-fold priorities, retained existing financial chart; keep role-specific original branches.
- Create \`apps/cms/current/public/assets/css/ald1n-dashboard-d1.css\` — page-specific responsive layout additions, after existing R5 stylesheet via \`@push('styles')\`; avoid editing generated \`ald1n-ui-v2.css\`.
- Modify \`apps/cms/current/tests/Browser/liquid-glass-browser-smoke.cjs\` — add D1 invariants to already-running authenticated screenshots; do not add an independent full browser suite.
- Modify \`.github/workflows/liquid-glass-cms-qa.yml\` — add the new two PHPUnit test files to the existing PHP/MariaDB gate, while preserving all existing mandatory CI checks.

### Task 1: Validated, non-inert dashboard period and canonical reporting

**Files:**
- Create: \`apps/cms/current/app/Support/DashboardPeriod.php\`
- Create: \`apps/cms/current/tests/Unit/DashboardPeriodTest.php\`
- Modify: \`apps/cms/current/app/Http/Controllers/DashboardController.php:31-112,171-198\`
- Test: \`apps/cms/current/tests/Unit/DashboardPeriodTest.php\`

**Interfaces:**
- Produce: \`DashboardPeriod::resolve(array $query, CarbonImmutable $today): array\`, shape \`['period' => '7d'|'30d'|'custom', 'date_from' => string, 'date_to' => string, 'error' => ?string]\`.
- Consume in controller: resolve using \`CarbonImmutable::today('Europe/Belgrade')\`; expose view key \`dashboardPeriod\`.
- Change \`DashboardController::reportStats(User $user, array $access, ManagementReportService $reports, array $dashboardPeriod): array\` to use resolved inclusive dates with \`scope='completed'\`, \`group_by='brand'\`. Never add raw user filters to SQL.

- [ ] **Step 1: Write the failing tests.** Freeze today at 2026-10-18 Europe/Belgrade. Assert \`7d\` yields \`2026-10-12\` through \`2026-10-18\`, \`30d\`/default \`2026-09-19\` through \`2026-10-18\`; \`custom\` accepts valid \`2026-09-01..2026-09-30\`. Reject impossible date, swapped bounds, unknown period, array parameters and missing custom endpoints with non-empty \`error\`.
- [ ] **Step 2: Run isolated PHPUnit RED:** \`php artisan test -c phpunit.mysql.xml tests/Unit/DashboardPeriodTest.php --no-ansi\` (CI DB only). Expected: missing resolver / failing assertions.
- [ ] **Step 3: Implement resolver and controller integration.** Strict ISO \`YYYY-MM-DD\` parsing with round-trip date validation; no PHP loose casts or silent coercion. For invalid custom selection return an error without issuing financial period queries; keep a safe computed range internally but present financial KPIs/chart as unavailable until user fixes it. Presets ignore unused custom query fields. Preserve role and report-readiness guards.
- [ ] **Step 4: Run unit test GREEN** with same command, plus \`php -l\` on both PHP files.
- [ ] **Step 5: Commit only Task 1 files** using message \`feat(cms/dashboard-d1): validate 7-30-custom reporting period\`.

### Task 2: Current-state receivables + financial/stock data integrity

**Files:**
- Modify: \`apps/cms/current/app/Http/Controllers/DashboardController.php:60-111\`
- Create: \`apps/cms/current/tests/Feature/DashboardD1Test.php\`

**Interfaces:**
- Produce: view key \`dashboardOutstandingNow\` of \`?float\`; return \`null\` when unavailable, not zero.
- Add private \`currentOutstanding(User $user): ?float\` in controller. Enforce SuperAdmin gate at call site. In a bounded SQL aggregate use \`Order::query()->operational()\`, exclude cancelled and non-Laravel source rows to match the existing management reporting authority; sum \`GREATEST(0, subtotal_rsd - COALESCE(paid_total_rsd,0))\`. No \`date_from/date_to\` filter; excludes archived rows. Log and return \`null\` on missing table or database failure.
- Reuse existing \`$inventoryValuation = $reports->inventory()\` exactly once when SuperAdmin; never recompute for different dates.

- [ ] **Step 1: Write failing authenticated feature tests.** Seed SuperAdmin and restricted user, create outstanding/current, archived, cancelled and fully paid order fixtures. Assert open balance aggregates correctly and is invariant between \`period=7d\` and \`period=30d\`. Assert inventory fields are present exclusively for SuperAdmin, and their values do not vary with date. Simulate report-not-ready and missing-table conditions; assert HTTP 200 and a non-numeric unavailable marker.
- [ ] **Step 2: Run feature test RED:** \`php artisan test -c phpunit.mysql.xml tests/Feature/DashboardD1Test.php --no-ansi\`.
- [ ] **Step 3: Add minimal controller computation** with the above exact source/operational gates and view data keys; no archive/business behavior changes.
- [ ] **Step 4: Run feature test GREEN**, keeping fixture expectations tied to actual authorized database values.
- [ ] **Step 5: Commit Task 2 source and tests** with \`feat(cms/dashboard-d1): expose current open receivables safely\`.

### Task 3: Six-card premium layout + useful periods, no placeholder controls

**Files:**
- Modify: \`apps/cms/current/resources/views/dashboard/index.blade.php:58-202,234-266\`
- Create: \`apps/cms/current/public/assets/css/ald1n-dashboard-d1.css\`
- Test: \`apps/cms/current/tests/Feature/DashboardD1Test.php\`

**Interfaces:**
- Consume view keys \`dashboardPeriod\`, \`reportStats\`, \`inventoryValuation\`, \`dashboardOutstandingNow\`.
- Define a SuperAdmin-only \`dashboard-d1-shell\` marker + \`dashboard-kpi-grid--d1\` so R5 baseline effects remain, but other roles retain existing layouts. Add \`data-dashboard-d1-kpi\` to exactly six anchors. No extra seventh "expected profit" KPI on SuperAdmin (keep that metric in management reports).

- [ ] **Step 1: Extend feature tests RED.** Assert exactly six \`data-dashboard-d1-kpi\` elements in SuperAdmin HTML and all six exact labels, no duplicate legacy SuperAdmin KPI row. Restricted/customer HTML has zero D1 cards. Assert \`period=7d\`, \`period=30d\`, and a custom range reflect selected dates in a real GET form; invalid custom yields visible \`role="alert"\` and no misleading financial values. Assert zero/unknown data differs from "Nije dostupno".
- [ ] **Step 2: Run feature test RED** with same Task 2 command.
- [ ] **Step 3: Update Blade:** compact existing hero and keep EUR/RSD sync + permissions; shift existing \`priorityActions\` panel above KPIs in a compact, fully linked strip; render six SuperAdmin cards in order **Prihod, Bruto dobit, Neto doprinos, Potraživanja, Vrednost po nabavnoj ceni, Vrednost po prodajnoj ceni**. First three follow period, remaining three labeled "Stanje sada" / "Trenutno otvoreno". Use accessible native GET presets/custom date form, with labels, \`aria-current\` or comparable selected indicators and no inert widgets. Retain the existing functional bar chart until D3; do NOT render fabricated brand pie/pseudo-trends.
- [ ] **Step 4: Add CSS scoped to \`.dashboard-d1-shell\` and link with \`@push('styles')\` to the post-R5 style stack. Grid: 3 columns desktop, 2 tablet, 1 phone; no overflow for long RSD, retain glass cards and reduced-motion/transparency handling. Collapse extensive modules inside keyboard-accessible native \`<details>\` on SuperAdmin only, leaving other roles unchanged. Run feature tests GREEN and \`php -l\` on modified PHP.
- [ ] **Step 5: Commit UI, CSS and focused tests** with \`feat(cms/dashboard-d1): six glass KPI layout and live filters\`.

### Task 4: Only essential CI/browser regression gates, draft PR, stop before deploy

**Files:**
- Modify: \`apps/cms/current/tests/Browser/liquid-glass-browser-smoke.cjs\`
- Modify: \`.github/workflows/liquid-glass-cms-qa.yml\`
- Test: authenticated Chromium + canonical static-check via existing isolated CI

**Interfaces:** No runtime API changes. Reuse existing CI \`ald1n_liquid_ci\` database, two existing browser personas and R5 theme tests; assert new D1 marker/count on SuperAdmin, no D1 KPI markup on user view, real preset selection and no horizontal overflow at 1440px/390px in dark/light.

- [ ] **Step 1: Add failing Chromium assertions** to existing \`liquid-glass-browser-smoke.cjs\`: SuperAdmin has exactly six D1 cards, 30-day default selected, all three controls exist; 7-day GET actually updates selected span; user has no D1 cards; preserve legacy glass/KPI selection/headers.
- [ ] **Step 2: Run relevant isolated browser test in CI RED** (assertions expected to fail before UI) or cite the already-captured pre-UI RED trace if tests were added before Task 3.
- [ ] **Step 3: Register the new Unit and Feature files** with two explicit \`php artisan test -c phpunit.mysql.xml ... --no-ansi\` steps in existing \`.github/workflows/liquid-glass-cms-qa.yml\`. Keep PHP 8.4 and MariaDB 10.11.19. Do not create a new dashboard-specific workflow or run mobile checks.
- [ ] **Step 4: CI GREEN:** \`php artisan test -c phpunit.mysql.xml tests/Unit/DashboardPeriodTest.php --no-ansi\`; \`php artisan test -c phpunit.mysql.xml tests/Feature/DashboardD1Test.php --no-ansi\`; \`php bin/static-check.php\` expected \`Ukupno: 983, neuspešno: 0\`; existing authenticated Chromium dark/light, SuperAdmin/user, viewport 1440/390 and no overflow/errors. Validate exact PR SHA and any static count changes as appropriate; if a failure is real, fix just that failure and rerun.
- [ ] **Step 5: Commit and open D1-only Draft PR** with changed-file allowlist, exact-source CI, sample dark/light desktop/mobile screenshots, source SHA, deferred D2/D3 scope and explicit \`PRODUCTION_PROMOTED=NO\`. No merge, database deploy or host script in D1 without separate approval.

## D1 acceptance and handoff

- PASS only if: six real SuperAdmin KPI cards in order; preset/custom GET controls change financial reporting and show invalid-range errors; current stock and receivables unaffected by filter; role isolation and existing customer dashboard proven; authenticated dark/light phone/desktop screenshots legible; no static gate regressions.
- D2 subsequently adds rigorous prior-period percent deltas and deeper financial semantics; D3 adds true per-brand two-metric pie, correctly aggregated Top 5 + Ostali and improved revenue/profit chart. No blank chart, fake value, misleading date badge or non-working button in D1.
- Follow \`AGENTS.md\` for the mandatory numbered operation receipt when a source batch is executed. Since this is a plan-only document, **no server script, host execution or production report is created here**.

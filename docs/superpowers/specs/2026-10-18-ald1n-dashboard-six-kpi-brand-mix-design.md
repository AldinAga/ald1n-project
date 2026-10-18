# Ald1n CMS Dashboard 10/10 — six KPI, period controls and brand pie chart

**Status:** Owner approved the dashboard layout and six KPI requirements in conversation on 2026-10-18; this written spec awaits a concise owner review before a source implementation plan. No source or production changes are authorized by this document.

**Authority:** GitHub main at design start: 1810f4ed2be4d351eefa1ed9e7d45cfeefc3498f, AGENTS.md, docs/operations/000-LATEST.md, owner-supplied latest relevant host report #752. CMS Laravel 13 / PHP 8.4, MariaDB 10.11.19, Blade; existing Liquid Glass R5 visual baseline. Recent R5 GitHub merge is not evidence of a live host release.

## Purpose and success criteria

Make the authenticated Ald1n CMS home page a practical, premium, readable business command center: exactly six primary business KPI cards for SuperAdmin, one shared reporting-period selector, an accurate brand sales share pie chart with revenue/units switch, and immediate access to operational priorities. Preserve the real Ald1n domain, roles, routes, data sources, archive rules, direct sales and R5 Liquid Glass dark/light appearance. No fictitious demo information in production.

## 1. Layout and hierarchy

- Reduce the tall welcome hero into a compact title, brief greeting, useful actions and the existing EUR/RSD exchange display/synchronization, respecting existing role checks.
- Place a compact "Fokus danas" priority strip where important operational issues are quickly visible; reuse existing DashboardController::priorityActions() and its severity ordering, links and permissions.
- Directly under the global date selector, render the **six SuperAdmin KPI cards** in this fixed order. Desktop grid 3 columns x 2 rows; tablet 2 columns; phone 1 column. Good wrapping for RSD numbers without horizontal overflow.
- Below the cards, place the main revenue/gross-profit time-series chart (larger) and brand pie (smaller), followed by recent orders and the accessible quick-actions area. Keep the comprehensive module list available in a collapsible section, with keyboard access.
- Financial cards may show prior-period percent deltas and tiny trend cues only when a mathematically meaningful comparison is available. No fabricated growth percentages. Existing ManagementReportService::comparison() is the reference.
- Keep all R5 colors and existing SVG refraction mechanism, both themes, focus behavior and reduced-motion/transparency preferences. Do not adopt React/Vue or replace Blade.

## 2. Exactly six SuperAdmin KPI cards

1. **Prihod** — completed-order revenue for selected period, RSD, from canonical management report summary.
2. **Bruto dobit** — gross profit for selected period, RSD, canonical snapshot/coverage rules; indicate incomplete cost coverage if material.
3. **Neto doprinos** — net contribution for selected period, RSD, existing canonical finance/report formulas.
4. **Potraživanja** — total *currently open* collectible balance from the existing operational/ledger-derived order authority, filtered by user's authorized business scope, NOT from the selected historical date interval. Label "Trenutno otvoreno".
5. **Vrednost po nabavnoj ceni** — current purchase-cost inventory valuation, RSD, reusing ManagementReportService::inventory(), with missing purchase-cost caveat.
6. **Vrednost po prodajnoj ceni** — current sales-price inventory valuation, RSD, from the same one-time inventory() result, with missing prices/exchange-rate caveat.

Metrics 1–3 change with date selection; metrics 4–6 remain a live snapshot and are distinctly labeled "Stanje sada" or "Trenutno otvoreno". Never misrepresent an all-time/current inventory value as an historical value. When report readiness or a required conversion/cost is missing, show an honest unavailable/incomplete state instead of an ungrounded zero.

**Authorization contract:** existing inventory valuation is SuperAdmin-only. Therefore the complete six-card set is SuperAdmin-only, preserving non-SuperAdmin's existing role-appropriate cards, customer portal and all relevant report permissions. No escalation or exposure of financial/inventory fields through markup, JSON, APIs or chart payloads. Brand charts require the existing reports.view authority (or stronger) and retain supplier/customer scoping.

## 3. Shared reporting period

One accessible top-level selector: **7 dana**, **30 dana**, **Prilagođeno**. Default 30 days. The preset includes today and previous 6/29 calendar days in Europe/Belgrade. Custom is a native date-from/date-to pair with apply control; both endpoints inclusive, only valid ISO dates and start <= end accepted with visible, accessible validation. Persist selected period in the dashboard URL using safe GET parameters; no mutation of data.

Use the same validated period/scope for financial KPIs 1–3, the trend chart, the brand pie chart and prior-period comparisons. Receivables and inventory remain current snapshots despite filter changes. Display a clear label so users can distinguish both time bases. Direct sale and other relevant completed orders use the canonical existing reporting scope.

## 4. Brand sales share — real pie chart

Heading "Najprodavaniji brendovi". Default **Promet (RSD)** and switch **Komadi**. Both modes use the identical selected period and the same completed-order item snapshots. Revenue percent = brand revenue / sum of all brands' revenue x 100; units percent = brand units / sum of all sold units x 100. Denominator includes all brands, even those not in Top 5. Show Top 5 *for the currently selected metric* plus "Ostali" for the remainder; display exact underlying RSD/units alongside readable one-decimal percentages. Unknown/blank snapshot brand is normalized to "Bez podatka"; show it as an explicit brand when it ranks among Top 5.

Important: ManagementReportService::segments(..., 'brand') orders by revenue and LIMIT 30, so it CANNOT be used unchanged to rank top-five by units or to compute the complete percentage denominator. Implement a read-only, RBAC-scoped brand aggregation based on the canonical orderIds()/item snapshot/filter logic; compute both metrics independently, then the two correct Top 5 + rest series. An empty dataset displays "Nema prodaje u izabranom periodu" rather than 0/0 or misleading chart slices. Guard against negative/zero totals and percent rounding drift; never fabricate percentages.

Switching the metric updates the chart and legend without making unrelated data queries. Clicking a named segment opens the existing filtered reports UI with compatible date/brand params; preserve period where supported. Do not silently drop scope or reveal unauthorized orders.

## 5. Charts, UI and performance

Trend chart should present revenue and gross profit as readable, visually distinct series (blue + teal), honest axis units, keyboard-accessible alternative summary, empty states and responsive tooltips. Pie chart uses the six functional R5 accents as category colors, not semantic good/bad labels for arbitrary brands; legends carry brand names and values. Use a locally bundled, legally compatible vanilla JS chart solution (Chart.js 4.x is suitable); avoid CDN/runtime downloads and any React dependency. No chart if required data is unavailable.

Do not load entire order-item tables into PHP for arbitrary date ranges. Prefer bounded grouped SQL aggregates and pass small authorized serialized datasets to the view with safe escaping. Reuse existing management-report snapshot methods; avoid re-running full inventory valuation on every chart toggle or making N+1 queries. R5 glass/backdrop paint must not cover focus elements or cause mobile overflow.

## 6. Source boundaries and minimal necessary verification

Expected scoped source surfaces: apps/cms/current/app/Http/Controllers/DashboardController.php, a focused dashboard read-only aggregation service or small ManagementReportService method, apps/cms/current/resources/views/dashboard/index.blade.php, scoped CMS CSS/JS assets, and focused PHP/Chromium tests. No migrations, inventory mutation, changes to financial ledger, new client framework or mobile changes.

Implementation can ship in three independent source-only batches:
- **D1:** responsive six-card layout, period UI and real role-aware display, preserving existing business rules.
- **D2:** validated date selections, period-comparison financial aggregates and separate live receivables; targeted DB tests.
- **D3:** accurate both-mode Top 5 brand series, pie + improved trend chart, keyboard/mobile dark/light QA.

**Do not skip necessary gates:** source main/diff preflight and RBAC boundaries; targeted tests for revenue/units denominators, period bounds/current-value separation and non-SuperAdmin access; real MariaDB 10.11.19 aggregation verification if SQL changes; canonical CMS static gate (983 checks, 0 FAIL); authenticated Chromium dark/light desktop+phone smoke of changed dashboard. Existing CI may exercise more; do not expand unrelated manual work. Skip unrelated mobile TypeScript, Expo, EAS, Google Play and full-system rebuild tests. Any future merge or production CSS/Blade/JS deploy needs its own explicit approval and existing exact-source, backup, checksum, receipt and rollback gates.

## 7. Required acceptance scenarios

1. SuperAdmin sees exactly six cards with authorized, correctly labeled values; toggling date never changes the current inventory snapshot or open-receivables snapshot.
2. The date preset 7/30 and custom inclusive dates produce accurate canonical completed-order totals; invalid dates do not silently change meaning.
3. Top 5 by revenue can differ from Top 5 by units, and each has an accurate denominator across **all** brands, with "Ostali" and "Bez podatka" handling.
4. Zero sales/cost coverage gaps/invalid conversion produce honest empty or warning states, not divide-by-zero or fictional data.
5. Restricted users never see SuperAdmin-only valuation values or unauthorized data in HTML, JSON, charts, API or export.
6. Dark/light at desktop and phone show no horizontal overflow or broken glass; keyboard focus, chart legend and calendar remain usable.

## Explicit exclusions

No change to Liquid Glass R6 dark micro-polish Issue #36, no redesign of other CMS pages, no chart embedding in Android, no new subscription/licensing modules, no migration, no production release in this design-only stage. Documentation and code are independent of whether the prior R5 production kit was actually executed.

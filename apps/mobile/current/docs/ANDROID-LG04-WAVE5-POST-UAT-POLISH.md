# LG-04 Wave5 — Navigation + Orders UX polish after UAT screenshots (source-only)

**Status:** Isolated Draft PR #55, stacked on PR #51 (Wave4), API/UX source only.
**User approval:** 20 October 2026, all 4 proposed visual/UX improvements approved. **No further EAS Build at all until the entire LG is implemented and application is release-ready.**

## UI changes

- Home: previously existing authorized quick actions remain primary bounded Liquid Glass; **My Activities** and the **Administration** link gain **soft** glass 48dp icon affordance. Amounts, labels, text, focus tiles and KPI surfaces remain fully opaque.
- Admin hub: 26 existing role/module-filtered navigation rows get **soft** 48dp native glass icon, with the original permission checks and exact route destinations preserved. Full row is a single accessible tap target; no nested presses or duplicate routes.
- Admin orders overview: remove literal diagnostic text `Detail capability...` and internal developer explanatory copy; show a compact, legible 2×2 dashboard: total orders, current page/total pages, unaccepted and overdue counts from **existing server response**, not calculated locally. Preserve tabs, search, pagination, filters, attention, archive and every permission.
- Order cards: show first **available immutable order-item snapshot product name**, fallback SKU when product_name absent, and additional line count. Count refers to order line-items, not sum of quantities; no fabricated article names. Customer, payment, status pill, total and date remain visible; raw stock/source debug-like text is removed from the compact card only, not from business data or detail.
- Bottom navigation: increase the order-list scroll end inset to 200 for safe content clearance; keep five canonical navigation tabs and their native GL-03 glass effects unchanged.
- Accessibility: optional Android API33+ native icon, solid fallback on older OS or accessibility preferences, Reduce Motion no press scale, no glass on critical text/data, no new native dependency. Soft icon lens density may require GPU/performance verification on phones.

## Backend + roll-out dependency

- Authorized `GET /api/v1/admin/orders` only: `OrderIndexService::paginate` eager-loads the **existing** `Order.items` relation with only `id, order_id, product_name, product_sku` for visible orders, avoiding N+1 list lookup. `OrderController::presentListOrder` returns additive `item_summary: {name: string|null, additional_count:number, count:number}` from snapshots. If first item has empty name/SKU, select first available later item; preserve count of all rows. No schema, migration, product, inventory or payment write.
- Mobile `item_summary` is optional until an authorized server deployment happens. Old production server returns no field: mobile shows **"Naziv artikla nije dostupan"**, without crashing or fabricating a name. For full new UX, **CMS endpoint must be deployed before final mobile release** under separate guarded production deploy authorization. No deployment is authorized by this Draft.
- All three OpenAPI files describe same additive snapshot summary: `apps/mobile/current/docs/openapi.yaml`, `apps/cms/current/docs/openapi.yaml`, `packages/api-contract/openapi.yaml` (byte-identical).
- Read-only Laravel unit tests exercise two line items, no relationship, SKU fallback, blank first row with later valid item. No database mutation.
- PR54 charger catalog is **independent** (based on main) and must not be unintentionally folded into LG PR chain. Any rollout/provisioning separately permission-gated.

## Release gates

- **ZERO EAS builds** (internal, APK, Play beta AAB, production), OTA, submit, Play or merge until user explicitly resumes after full Liquid Glass implementation.
- No new Android package/SDK/runtime/lockfile changes in Wave5.
- Exact-source Node22 TypeScript + validator + full Release25 + Wave5 test and PHP8.4 order-summary unit and static gate must PASS from actual PR head; screenshots/source review are not physical device acceptance.
- Physical 320/390dp, dark/light, font scaling, accessibility, older Android fallback, native lens performance with many Admin rows, order card fallback and bottom nav clearance remain **UAT HOLD** until user-authorized final test build.

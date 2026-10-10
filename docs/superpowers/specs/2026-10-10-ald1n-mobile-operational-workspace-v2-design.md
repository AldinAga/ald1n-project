# Ald1n Mobile — Operational Workspace UX 2.0 and Legacy Order Notification Recovery

**Status:** DESIGN DRAFT — OWNER REVIEW REQUIRED. No application implementation, production/OTA/build/Play action or database mutation is authorized by this document.

**Date:** 2026-10-10  
**Repository:** AldinAga/ald1n-project  
**Verified parent GitHub main:** a6da85b9e35fb6043d5d498af1403487d48cc43b  
**Scope:** Ald1n Mobile UI/UX and, only if proven necessary for the reported notification bug, narrow Laravel API corrections. Laravel web CMS UX is explicitly deferred.  
**Operational authority:** AGENTS.md; docs/operations/000-LATEST.md (its Build25 pointer is older than main); latest checked numbered scope docs/operations/590-BATCH556-BUILD25-SEPARATED-PHASE-SCOPE.md; latest live GitHub main remains the source authority. Re-check all authorities at every executable batch.

## 1. Agreed user intent and success

The owner approved the visual concept with:
- Compact contextual **Brze akcije** near the top of an operational detail view;
- a modern **Radni prostor** grid with icons, clear selected state, and short labels, three columns and normally two rows on phones;
- a contextual next step, not buried below histories or long record details;
- use of the approach throughout Ald1n Mobile **where it improves usability**, not a blanket skin on every screen;
- retention of the last five days of native/release optimizations and Google Play hardening without unintended build or production effects.

The owner also reported that opening older **Notification -> Order #134** fails with Laravel's "No query results for model [App\Models\Order] 134", while newer orders open. This is a correctness issue preceding optional visual rollout. Do not infer that Order #134 is archived or deleted without the actual authorized read-only production diagnosis.

**Success:** A user can access important authorized actions near the top, understand their current workspace and next action, complete existing workflows without losing input, and open older supported notifications or receive a precise, safe explanation and relevant fallback. Preserve current permissions, financial semantics, order history, and reliable Android release configuration.

## 2. Boundaries and non-goals

### Included
1. Diagnosing Order #134 and correcting legacy/new notification routing and safe unavailable states.
2. Shared Expo/React Native visual primitives with no new native dependencies.
3. Admin order detail as the reference implementation, followed by other existing admin workspace screens.
4. Targeted customer order and product/report navigation improvements only where the existing flow supports them.
5. Regression tests for routing, workflow continuity, access control, form-state preservation, and responsive UX.

### Excluded
- Laravel web CMS visual redesign; a separate approved UX phase can address that later.
- Redesigning the five global bottom navigation entries: **Početna, Katalog, Porudžbine, Obaveštenja, Nalog**.
- A universal restructure of every screen, authentication flow, cart/checkout, native code, or release controller.
- New mobile native icon/navigation/modal libraries.
- Business-domain changes, financial calculations, unsafe automated order transitions, product variants, or destructive production data repair.
- Incidental changes to app/native identity, runtimeVersion, applicationId, versionCode, EAS credentials, R8/AGP/Gradle or Google Play release state.

If an implementation need crosses these exclusions, STOP and obtain a new explicit scoped design/release approval.

## 3. Source findings (read-only, not a live production diagnosis)

### 3.1 Legacy notification -> order

- Mobile src/features/notifications/notification-routing.ts resolves an order target to the ordinary 'order' destination in most cases, and sends selected operational automation events or explicit /admin/orders/{id} routes to 'admin_order'.
- Mobile in-app notifications.tsx and push-notification-bridge.tsx already have separate handling for /order/[id] and /admin/orders/[id].
- Laravel app/Http/Resources/Api/V1/NotificationResource.php currently synthesizes /orders/{id} from an order_id when no explicit route is stored. Laravel OperationalNotificationService::order already generates admin or normal web URLs for certain recipients, but NotificationResource does not use those URLs as the mobile route.
- app/Models/Order.php overrides resolveRouteBindingQuery to include archived_at IS NULL. Therefore an archived order can throw the displayed 404-style binding exception even if still stored.
- The archived orders API uses OrderArchiveService's managed scope and supports listing/restoration; it does **not** currently expose an unrestricted archived detail API. Purged orders and permission restrictions must remain protected.
- The existing customer, assigned and admin order routes have different access and business semantics. Never blindly re-route every order notification to the admin screen.

**Level of certainty:** high for the source behavior, unknown for the actual production state of #134 until live authorized diagnosis.

### 3.2 Workspace and action placement

- Admin order detail renders the 'Tok i akcije' content only when workspace === 'activity'. AdminOrderActions then appears **after** notes and timeline; key controls are below long content.
- The existing order screen has six workspace identifiers: overview/customer/fulfillment/finance/documents/activity. Existing Batch86/Batch518B validators depend on those identifiers, capabilities and post-shipment continuation.
- Admin after-sales, field operations, receivables, warranties and reports also use FilterBar/FilterChip for named workspace selections; their existing state identifiers and forms must be preserved.
- Catalog create/edit are long-lived forms and must not be naively unmounted by workspace switching.
- The global five-tab navigation is already stable; avoid remapping its keys or selected context.

## 4. Design decisions and alternatives

### Considered
A. Individually add cards/chips to every screen: smallest immediate diff, but inconsistent and expensive to maintain.
B. **Chosen:** two controlled reusable visual primitives + screen-owned existing state and workflow handlers. Consistent UX with low migration risk and no new native dependency.
C. Full navigation/router rewrite: highest complexity, unnecessary native/release exposure and regression risk.

### Chosen architecture
- New presentational Mobile component **WorkspaceGrid** in src/components/workspaces/workspace-grid.tsx.
  - Props: typed options (stable id, short label, existing GlyphName, optional non-sensitive count/hint, disabled), activeId and onSelect.
  - Controlled by each screen's existing workspace union/state; no global state or new routes.
  - Mobile layout: normally 3 columns; adapt to narrow width / large system font without text truncation that removes meaning. Support 5/7+ items with additional rows instead of forcing exactly six entries.
  - One active option with both visible and accessibility selected state. Minimum practical 44–48 dp targets and sensible screen-reader labels.
- New presentational **QuickActionHub** in src/components/workspaces/quick-action-hub.tsx.
  - Receives actions/handlers derived by a screen from **server capabilities, current state and existing permission checks**.
  - Shows one prominent permitted next step and up to four frequent compact actions; an accessible **Sve akcije** disclosure exposes the rest without very long buttons or duplicate actions.
  - Visual state, disabled/pending, and confirmation UX must reflect real mutation state. It never owns domain mutations or invents action eligibility.
  - No action should fire until directly tapped; toggling navigation never mutates server data.
- Extend the current Expo Symbols Glyph mapping only for supported icon names required by the selected screen; respect established light/dark theme, React Native StyleSheet primitives and current typography.
- Keep current action panels/handlers as **one mounted instance** near the top of the screen, rather than duplicating action forms or mutations in the quick actions and old activity section.
- Contextual **Sledeći korak** is retained and integrated without duplicate network calls or competing two-primary-action cards.

### Data and state lifecycle
- Server responses remain the authority for capabilities, permissions, state transitions and money.
- Screens keep their existing workspace discriminant values and React Query keys. UI components receive props and callbacks, not API clients.
- Switching workspaces must not discard unsaved input. Keep form state in the screen/feature owner or keep form mounted; never reset state on tab change without explicit discard confirmation.
- On successful mutation, invalidate/refetch existing query keys; preserve the existing shipment -> fulfillment with immediate tracking copy/open continuation. Error leaves the form and its values available.
- Where forms need scrolling, use the existing Screen/ScrollView patterns with keyboard/safe-area handling. Avoid new global scroll hacks and expensive repeated rendering.

## 5. Notification/Order #134 recovery (first functional slice)

### Diagnostic gate — no mutation
1. On a separately authorized read-only production diagnostic, inspect Order ID 134's existence, archived_at, purged_at, role/scope relationship; inspect the exact notification event, target, route, stored URL and current recipient permission. Collect only necessary redacted fields. Do not expose private customer data in reports.
2. Reproduce with both the legacy notification and a known working new notification; identify the exact HTTP endpoint/status, user role and route chosen by Mobile.
3. Classify root cause **before** changing code: wrong destination, missing record, archived record, purged record, or scope/permission mismatch. 404 alone is not proof of archive/deletion.

### Routing fix
- Prefer explicit, typed mobile destination context at notification creation/serialization. Use existing trusted first-party stored metadata for old notifications; validate route prefix and positive numeric IDs and do not trust arbitrary URLs.
- Preserve customer and assigned-order destinations; use admin order detail only when the notification has operational/admin intent **and the recipient can manage orders**. Server access checks remain authoritative on every destination.
- Both in-app click and push tap use the same destination resolver and regression suite; no unrelated in-app notification is routed to an order merely because it has an order_id field.
- For an archived order, do not remove the Order model's archived-binding filter or route users to active mutation endpoints. Offer a scoped archive path or a minimally-scoped read-only archived destination **only if** its need and permission contract are proven. Existing archive list can serve as a safe fallback; do not pretend it can search raw numeric IDs when it filters other fields.
- Purged/deleted/inaccessible should show useful neutral unavailable feedback (e.g. "Porudžbina više nije dostupna") with navigation back to notifications or an authorized order list. Do not leak record existence to unauthorized users. Never show raw Laravel exception text as the primary user-facing message.
- If a new Laravel endpoint is indispensable, first define a least-privilege read-only response with applied managed scope and indistinguishable non-access statuses, then update all three OpenAPI copies byte-for-byte and add Laravel feature tests. No production migration or repair by default.

### Acceptance
- Test old admin operational, normal customer and supplier/assigned notifications, new admin notification, malformed ID/route, stale reassignment, 403/404, archived and purged paths.
- Specifically re-check #134 on a permitted physical device/account after a separately authorized deployment; do not claim production bug solved from unit tests alone.

## 6. Progressive screen coverage

### Phase W1 — reference: Admin Order Detail
Target: src/app/(app)/admin/orders/[id].tsx and src/features/admin/orders-admin-actions.tsx.
- Compact identifier, current state and refresh in the header.
- Display allowed quick operational actions and the actionable next step immediately under header. Order-related actions include statuses, notes, reassignment, payments, shipment/delivery, existing payment handling, proof and tracking, conditional on real capabilities/status.
- Replace purely horizontal workspace chips with an accessible 3-column icon grid: **Pregled, Kupac, Isporuka, Finansije, Dokumenti, Istorija**. Existing internal keys stay unchanged; activity key now labels history and notes. Domain operations are surfaced at top, not buried inside history.
- Keep workflow actions (especially shipment + delivery) distinguishable; dangerous actions must retain confirmation/reason requirements, never be executed from one icon tap.
- Preserve Batch518A post-shipment navigation, tracking safe HTTPS check and Batch518B Next Step; update historical validator assertions by replacing/strengthening expected presentation assertions, not deleting prior behavior coverage.

### Phase W2 — existing operational workspaces
Apply shared primitives with **screen-specific options and actions**, retaining original identifiers:
- Admin After Sales: case, messages, actions, history.
- Admin Field Operations: schedule/team, execution, parts, documents.
- Admin Receivables: case, payment plan, payments, communication, reminders, audit. More than six items can use a third row.
- Admin Warranties: details, maintenance, documents, void (void is clearly dangerous and not a promoted one-tap quick action).

For each screen, re-check current capability flags, success-navigation and draft form preservation before migrating. Do not rewrite unrelated workflow handlers.

### Phase W3 — conditional adoption
- Reports: replace the current workspace filter navigation where the grid reduces navigation effort while keeping report periods, filter state, exports and query caching stable. Avoid a very tall icon wall.
- Admin product creation/edit: use a compact section jump navigator (scroll/focus to mounted sections) rather than conditionally unmounting form subsections. Keep save/create, image picker, Direct Sale and archive workflows unchanged. Protect draft values and unsaved-change warnings.
- Customer order detail/Home/Admin hub: only targeted shortcuts when tests demonstrate less navigation, no duplication of existing Next Step and Home quick actions.
- Other screens: audit only; explicitly exclude low-value cosmetic conversion.

These phases are separately scoped implementation units. Assign actual batch numbers only after reconstructing fresh main + operations reports at execution time; never repeat completed batches.

## 7. Mobile-native/release invariants

Current locked source contract at parent:
- App Ald1n CMS, version 1.0.0, runtimeVersion 1.0.0-build17, package com.ald1n.mobile; Expo SDK57, React Native 0.86.3.
- Native snapshot validator expects AGP 8.12.0, Gradle 9.3.1, Kotlin 2.1.20, NDK 27.1.12297006, compile/target SDK 36, minSDK 24 and Java 17.
- Release R8 minify/resource shrink, optimized resource shrinking/full mode and proguard-android-optimize.txt remain unchanged.
- Android signing/Digital Asset Links/Restore Credentials, Play upload certificate verification, ELF/ABI/16 KB acceptance, source/identity and EAS remote gates remain intact.
- Use canonical CloudLinux Node and npm, pinned eas-cli@24.7.0 and current AGENTS.md command/flag policy. Never use expo install --fix; use expo install --check for compatibility.
- The latest checked scope Report590 splits **build-only** and **submit-only** phases and requires independent exact owner approvals plus artifact/device evidence. Do not bypass release25 fail-closed controller.
- A historical report read verified remote versionCode 24 at its own audit time, but UX work must **not** infer current remote versionCode or authorization from that past observation; read EAS authority fresh only in an independently approved release gate.
- This design does not require a new native module or runtime change. If implementation reveals native/config/dependency changes, stop and seek a separate native/Play assessment.

**Hard boundary:** do not touch app.config.js, eas.json, Android Gradle/AGP/R8 config, signing credentials, native modules, release25 scripts/controller, package dependencies/lock or production build/OTA/submit state as a side effect of UX changes.

## 8. Verification and release gates

### Per implementation slice
- Fresh remote main and operations report reconstruction; inspect diff, exact source and commit SHA, no production-host checkout reset/merge.
- Negative-first focused TDD regression for changed behavior, RED on unpatched source, GREEN after minimal patch.
- Mobile TypeScript RC 0; Mobile validator with literal 'Ukupno FAIL: 0'; associated component/route regression tests; Expo install --check and Expo Doctor where executable on canonical hosting.
- Verify the three tracked OpenAPI copies remain byte-identical; if a Laravel contract is actually changed, update/test all three; canonical CMS static gate expected 983/983 and PHP/Laravel feature tests for the endpoint.
- Structural negative assertions: no Product Variants, no broken cart/order Direct Sale, commission, typed routes or backup/restore controls; no native-critical file drift.
- Confirm button accessibility, safe area, large font, dark/light themes, no horizontal overflow, screen-reader selected states and no action form remount/unsaved-value loss.
- Static/perf checks for unnecessary extra queries or repeated re-renders when navigating grids.
- Git diff file allowlist and diff --check; no release or automatic CI cost dispatch.

### Physical acceptance (separately authorized)
- Android API24–32 and 33+ representative devices where release gate requires them; verify navigation, large text, keyboard, in-app and push notification opening, order 134 scenario, role-dependent actions, form state and network failures.
- Reproduce the original issues first. Do not perform risky/destructive live financial mutations on arbitrary production records.
- A JS-only OTA may be an option **after** runtime/native compatibility and fresh source/owner release gate; do not publish automatically. New AAB and Play submit are independent owner approvals and are not implied by this design acceptance.

## 9. Risks / mitigations / stop criteria

- **Wrong-role deep link:** typed destination, server permission, regression matrix; fail closed.
- **Archived order false 404:** investigate #134 before implementing special handling; preserve archived filter and controlled access.
- **Form loss / scroll trapping:** mounted draft state and deterministic on-success continuation; physical touch/keyboard regression.
- **UI clutter:** no more than one primary step + four visible quick actions; disclosure for less frequent actions; do not force 3x2 on modules that lack six meaningful workspaces.
- **Stale test fingerprints:** targeted update of presentation assertions with behavior equivalence; leave historical guardrails intact.
- **Native/release regression:** hard source-diff denylist, separate Build25 sign-off and zero unapproved EAS/Play/OTA actions.
- **Scope growth:** if a module requires new domain routes/migrations or a common state architecture rewrite, stop and create a separate design.

## 10. Execution handoff and review gates

1. **This written design must be reviewed and approved by the owner.** Design branch/commit are documentation only.
2. On approval, use the writing-plans process to create a concrete ordered implementation plan and request review of execution method; do not jump straight to broad code edits.
3. First implementation batch: notification/Order #134 diagnostic + routing recovery only, including tests. Next: shared primitives + admin order reference UX. Then independent W2 and W3 batches as useful.
4. Every batch must reconstruct main, recent operations reports and AGENTS.md, name its precise file allowlist/tests, and preserve Build25 approvals.
5. Final owner-facing report must separate **implemented, tested, deployment-ready, deployed, physically verified**. Never call a spec or source-only patch a production fix.

**Reviewer check:** Does the owner approve Mobile-only rollout, optional *narrow* Laravel API correction for #134, reused 3-column workspace grid, a compact top action hub, safe staged rollout, and absolutely no build/OTA/Play operations at this stage?

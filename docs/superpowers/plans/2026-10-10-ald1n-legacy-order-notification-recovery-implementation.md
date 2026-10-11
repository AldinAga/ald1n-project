# Ald1n Legacy Order Notification Recovery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Correct older notification-to-order navigation, including reported #134, without leaking order existence, bypassing archive/access controls or changing unrelated order semantics.

**Architecture:** Verify the concrete state of #134 and its stored notification before changing behavior; add focused RED/GREEN tests around the shared in-app/push resolver and, only when justified, narrowly fix server notification mobile metadata. Treat 403/404 and archived data as distinct internal causes but equally safe user-facing fallbacks when access isn't allowed.

**Tech Stack:** Laravel 13 PHP 8.4, Expo SDK57, TypeScript, existing Mobile notification resolver.

**Spec:** `docs/superpowers/specs/2026-10-10-ald1n-mobile-operational-workspace-v2-design.md`, especially §5.

## Global Constraints
- **No production DB update/deletion or broad probing without explicit read-only access authority.** Current status of #134 is NOT proven by screenshot or source code.
- Never remove the `Order::resolveRouteBindingQuery` `archived_at IS NULL` safeguard. Never give an unauthorized actor a record's existence details.
- Keep routing for customer/assigned/admin roles distinct; do not route every `order.created` notification to admin merely because it carries an `order_id`.
- Reuse existing notification source fields `event`, `target`, `route`, `data` and existing Mobile route handling. If adding metadata, specify backward compatibility and update all three OpenAPI copies only when API contract changes.
- Keep push and in-app resolution identical; no native, SDK, EAS, versionName/versionCode or Play change in this batch.
- Entire scope isolated from production Build25.

## Review Focus
1. Old notification from an assigned admin now lacking access: never open another person's order; give safe unavailable response.
2. Archived but not purged #134: show only authorized archival destination, no unrestricted detail binding.
3. Completely purged or nonexistent ID: neutral fallback, no raw Laravel stack or existence disclosure.
4. Notification with malformed/negative/noninteger order ID or hostile URL: no unsafe navigation.
5. In-app click and push tap from the *same* notification context resolve identically; old customer notifications still use their own screen.

---

### Task 1: Read-only root-cause evidence and regression fixtures

**Files:**
- Create during execution: one numbered `docs/operations/<next>-...-ORDER134-DIAGNOSIS.md` with redacted minimal evidence, exact source SHA and findings.
- Test: `apps/mobile/current/scripts/notification-order-recovery.test.mjs` (new).

**Interfaces:**
- Input to `resolveBusinessNotificationNavigation`: notification `event/route/target/data`; input to `resolvePushNotificationNavigation`: push payload `Record<string,unknown>`.
- Outcome: `order`, `admin_order`, `notifications` fallback, or existing supported destination type.

- [ ] **Step 1:** Read current `main`, reports, Laravel `Order` binding, `NotificationResource` and both notification navigation paths; document expected permission paths.
- [ ] **Step 2:** When a separately authorized read-only production probe is available, collect **redacted** #134 `archived_at/purged_at/order_id/recipient role/notification event/route/target` and actual GET endpoint/status. No customer PII in logs; never assume archive.
- [ ] **Step 3: Write regression fixtures** from confirmed source behavior, plus synthetic normal customer, explicit admin route, reassigned-away, malformed route, archived/unavailable and nonexistent records.
- [ ] **Step 4: Run RED** `cd apps/mobile/current && node --test scripts/notification-order-recovery.test.mjs`; expected failing on confirmed misrouting. If live evidence isn't authorized, stop any root-cause claim and proceed only with provably safe synthetic resolver tests.

### Task 2: Correct typed route intent without changing authorization semantics

**Files:**
- Modify: `apps/mobile/current/src/features/notifications/notification-routing.ts`.
- Modify only if necessary: `src/app/(app)/(tabs)/notifications.tsx` and `src/features/notifications/push-notification-bridge.tsx`.
- Test: `scripts/notification-order-recovery.test.mjs`.

**Interfaces:**
- Existing exports: `resolveBusinessNotificationNavigation(notification)`, `resolvePushNotificationNavigation(data)`.
- If needed, add a single pure `resolveNotificationNavigation(input)` policy helper or export a pure normalizer for testing; keep the existing discriminated `NotificationDestination` union.
- Accept explicit trusted `/admin/orders/{positive-int}` as admin; only use verified operational event intent or verified server mobile destination metadata for historical `/orders/{id}`. Ambiguous legacy case must fail safely, not infer privilege from ID alone.

- [ ] **Step 1: Add failing tests** for every Review Focus routing class and in-app/push parity.
- [ ] **Step 2: RED** by running targeted test against unpatched source; save output.
- [ ] **Step 3: Change only the typed resolver/consumers** to preserve trustworthy context and neutral fallback, no arbitrary external navigation; no new mutation.
- [ ] **Step 4: GREEN** targeted test and Mobile TypeScript.
- [ ] **Step 5: Commit** exact paths on isolated feature branch.

### Task 3 (conditional): Server mobile notification route metadata / archive read-only path

**Files:**
- Modify only if Task 1 demonstrates server metadata is incorrect: `apps/cms/current/app/Http/Resources/Api/V1/NotificationResource.php`, and if needed notification payload creator `app/Services/OperationalNotificationService.php`.
- Test: dedicated `apps/cms/current/tests/Feature/NotificationRouteRecoveryTest.php`.
- If a new API endpoint is unavoidable, add narrow controller, managed scoped authorization plus byte-identical changes in `apps/cms/current/docs/openapi.yaml`, `apps/mobile/current/docs/openapi.yaml`, `packages/api-contract/openapi.yaml`.

**Interfaces:** Only trusted server-created metadata selects an admin/mobile destination; schema remains backward compatible for old payloads. Archived/purged must not restore active-route binding.

- [ ] **Step 1: Write failing PHP feature tests** for admin event, normal customer event, archived/unauthorized, malicious route, purged record; unauthorized status must not disclose existence.
- [ ] **Step 2: RED** `cd apps/cms/current && php artisan test --filter NotificationRouteRecoveryTest`.
- [ ] **Step 3: Implement minimal presenter/resource correction**, not a permissive archive query or broad role bypass. Implement read-only archive destination only if approved proof shows that fallback listing is inadequate.
- [ ] **Step 4: GREEN**, canonical CMS static expected 983/983 and OpenAPI parity when relevant; no production data mutation.
- [ ] **Step 5: Commit** exact scoped code, tests and updated contracts. If no server fix needed, record Task 3 as **not applicable**, not as implemented.

### Task 4: Safe UI fallback and physical verification

**Files:**
- Modify if required: Mobile `src/components/ui/states.tsx`, `src/app/(app)/admin/orders/[id].tsx` and ordinary `src/app/(app)/order/[id].tsx` (only in safe 403/404 response handling).
- Test: `scripts/notification-order-recovery.test.mjs` plus existing UI validator contracts.

- [ ] **Step 1: RED test** that a known 403/404 produces a user-readable safe fallback, preserves navigation to notifications/list and does not render the raw Laravel exception; preserve unknown 500 errors for support diagnostics.
- [ ] **Step 2: Implement minimal UI mapping** without falsely claiming a deleted order or bypassing policy; offer "Nazad na obaveštenja" and authorized list.
- [ ] **Step 3: GREEN** Node test, Mobile TypeScript/validator, CMS static/OpenAPI if changed.
- [ ] **Step 4:** Following separate authorized deployment, physical Android test for user-reported #134 plus newer and assigned/customer notifications; do not claim prod fixed before this acceptance.
- [ ] **Step 5:** Operation report with confirmed cause, source SHA, test/output, pending read-only/device blockers, BUILD_CREATED=NO, GOOGLE_PLAY_ACTION=NO.

# Ald1n Mobile Operational Workspace 2.0 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make buyer details and permission-aware next actions immediately accessible in Ald1n Mobile admin orders, then migrate existing operational workspaces to a consistent compact icon-grid navigation while preserving forms and Android native/release state.

**Architecture:** Two presentational reusable React Native components (`WorkspaceGrid`, `QuickActionHub`) controlled by each existing screen; one screen-owned action-panel state per workflow. A dedicated `OrderBuyerCard` displays existing Laravel shipping snapshot in a compact first card. Screen flows preserve current React Query keys, domain mutations, stable five-tab navigation and draft state.

**Tech Stack:** Expo SDK57, React Native 0.86.3, TypeScript, React Query, Expo Symbols, current `src/constants/theme.ts` design tokens.

**Spec:** `docs/superpowers/specs/2026-10-10-ald1n-mobile-operational-workspace-v2-design.md`, especially §§4–8.

## Global Constraints
- Always reconstruct fresh main, AGENTS.md and latest docs/operations before implementing; start an isolated feature branch from verified SHA.
- Android production source app version currently `1.0.0`, runtimeVersion `1.0.0-build17`; **v1.5.0 is only a future release target** and is handled in a separate release-version plan.
- Do not touch native project/config, `app.config.js`, `eas.json`, Gradle/AGP/R8, ABI/NDK, package.json/lock, auth, Play or Build25 release controller in a UI batch.
- Preserve five bottom tabs and existing `OrderWorkspace` ids: `overview|customer|fulfillment|finance|documents|activity`.
- Keep existing `AdminOrderActions` business handlers, forms, permission checks, successful shipment → fulfillment continuation, tracking HTTPS validation, payment-proof file upload and finance semantics.
- Do not conditionally unmount dirty forms without safeguarding drafts, including after a failed network write.
- No new native/UI dependencies; no Product Variants; no automatic OTA/build/submit. Runtime compatibility must be attested in a later release gate.

## Review Focus
1. Missing buyer phone/address/note: keep truthful placeholders, do not show creator/assignee contact instead; regression test.
2. Large font/320px phone: grid tiles accessible and not clipped; automated style assertion and physical QA.
3. Role with no permissions: no unauthorized actions/buttons; test actual capabilities, disabled/loading states.
4. Switching workspaces while a form is dirty: no lost input or multiple mutation instances; action state test.
5. Shipped/COD/completed status: shipment and delivery are separate, tracking appears promptly after mutation, no false payment finalization; regression tests.

---

### Task 1: Shared WorkspaceGrid and QuickActionHub primitives

**Files:**
- Create: `apps/mobile/current/src/components/workspaces/workspace-grid.tsx`.
- Create: `apps/mobile/current/src/components/workspaces/quick-action-hub.tsx`.
- Modify: `apps/mobile/current/src/components/ui/glyph.tsx` only for needed verified Expo Symbol mappings.
- Test: `apps/mobile/current/scripts/workspace-v2-contract.test.mjs` (dependency-free Node/AST-like source and typed contract tests; if a React Native component runner already exists at execution time, use that instead).

**Interfaces:**
- `WorkspaceOption<T extends string> = { id:T; label:string; glyph: GlyphName; disabled?:boolean; a11yLabel?: string }`.
- `WorkspaceGrid<T extends string>({options:readonly WorkspaceOption<T>[],selected:T,onSelect:(id:T)=>void})` is purely navigational, no API/client.
- `QuickAction = { id:string; label:string; glyph: GlyphName; disabled?:boolean; pending?:boolean; kind?:'primary'|'secondary'|'danger'; onPress:()=>void }`.
- `QuickActionHub({actions:readonly QuickAction[],title?:string,maxVisible?:number})` shows at most four frequent secondary actions plus one primary and a disclosure; does not invent availability.

- [ ] **Step 1: Write failing tests**: option rendering contract requires `accessibilityRole="button"`, `accessibilityState.selected`, no disabled call; quick action disclosure; exactly one primary and no automatic mutation; no third-party dependency.
- [ ] **Step 2: RED**: `cd apps/mobile/current && node --test scripts/workspace-v2-contract.test.mjs`, expected failure before components exist.
- [ ] **Step 3: Implement primitives** using `Pressable`, `View`, `Text`, `Glyph`, theme tokens and responsive styles; default three columns with more rows when count >6; accessible touch targets, large-text and keyboard/screen-reader support. Avoid extra data fetches.
- [ ] **Step 4: GREEN**: same Node test plus canonical `npm run typecheck`.
- [ ] **Step 5: Commit** explicit three source paths and one test after diff gate.

### Task 2: Buyer-first Admin Order reference screen

**Files:**
- Create: `apps/mobile/current/src/features/admin/order-buyer-card.tsx`.
- Modify: `apps/mobile/current/src/app/(app)/admin/orders/[id].tsx`.
- Test: same `scripts/workspace-v2-contract.test.mjs` plus existing navigation contract/validator tests.

**Interfaces:**
- `OrderBuyerCard({ name:string,phone?:string,address?:string,postalCode?:string,city?:string,note?:string })` accepts the **shipping recipient**, not `supplier`/`customer.name` as a substitute. Use `Linking.openURL('tel:...')` with strict validation and `expo-clipboard` already in app; never request new permission.
- `WorkspaceGrid<OrderWorkspace>` keeps the six existing keys. Labels: Pregled, Kupac, Isporuka, Finansije, Dokumenti, Istorija; active key continues to be `activity`.
- First card is buyer, followed by Next Step, QuickActionHub, workspace navigation; all currently permitted actions remain available.

- [ ] **Step 1: Add failing tests** for buyer-card first position, true `shipping_*` field access and missing data, retention of existing six keys and shipment success, no new native imports.
- [ ] **Step 2: RED**: `node --test scripts/workspace-v2-contract.test.mjs`; expected failure on old screen.
- [ ] **Step 3: Implement buyer card + icon-grid navigation** with no API changes and no duplicate buyer fields. Keep cached query and existing workspace data/controls; implement tight one-column layout on narrow devices.
- [ ] **Step 4: GREEN**: targeted Node test, `npm run typecheck`, `node scripts/batch518b-navigation-ux-consolidation-contract.mjs .`, `node scripts/validate-project.mjs`; historical display-label assertions may need only precise replacement, not removal of old behavior tests.
- [ ] **Step 5: Commit** exact changed files.

### Task 3: Lift AdminOrderActions above history without remounting its forms

**Files:**
- Modify: `apps/mobile/current/src/features/admin/orders-admin-actions.tsx`.
- Modify: `apps/mobile/current/src/app/(app)/admin/orders/[id].tsx`.
- Test: `apps/mobile/current/scripts/workspace-v2-contract.test.mjs`.

**Interfaces:**
- `AdminOrderActionKind` is the existing `Panel` union without null; `AdminOrderActionsHandle.open(action: AdminOrderActionKind):void` exposes existing panels to context Next Step, if needed. The screen owns one `ref`; actions stay in **one mounted AdminOrderActions instance**.
- `QuickActionHub` receives actions created from `capabilities` and `actionFlag`; it never substitutes its own domain policy.
- `onShipmentSuccess={() => setWorkspace('fulfillment')}` and HTTPS-only tracking remain unchanged.

- [ ] **Step 1: Add failing tests**: one mounted component, top placement before timeline, capability-gated quick actions, panel opened from top Next Step, form draft retained across workspace taps, no direct API write on navigation-only tap.
- [ ] **Step 2: Confirm RED** with `node --test scripts/workspace-v2-contract.test.mjs`.
- [ ] **Step 3: Refactor only presentation of existing buttons into QuickActionHub**, expose explicit action panel opener by ref or a screen-owned `requestedAction` prop consumed once. Reuse existing form state and `execute` calls; avoid wholesale domain rewrite. Keep confirmation for destructive operations and preserve separate shipping vs final delivery stack.
- [ ] **Step 4: Confirm GREEN**, typecheck, full validator (`Ukupno FAIL: 0`), original navigation contract, and physical-device draft/next-step checks when separately approved.
- [ ] **Step 5: Commit** scoped code/tests after diff gate.

### Task 4: Extend controlled icon-grid to operational detail workspaces

**Files:**
- Modify: `src/app/(app)/admin/after-sales/[id].tsx`, `field-operations/[id].tsx`, `receivables/[id].tsx`, `warranties/[id].tsx`.
- Test: `scripts/workspace-v2-contract.test.mjs`, existing `scripts/validate-project.mjs`.

**Interfaces:**
- Each screen keeps its existing union ids and `useState` workspace and all permission checks; consumes Task 1 primitives.
- No new data fetching, mutation workflows, routes or new backend dependencies.

- [ ] **Step 1: Add screen-specific failing tests** that assert existing workspace ids, action eligibility, and draft preservation for each module.
- [ ] **Step 2: RED** targeted tests against unchanged screen.
- [ ] **Step 3: Replace only `FilterBar/FilterChip` workspace choice and oversized action layouts where beneficial**. Seven receivables tabs must use additional row; do not hardcode six. Void/danger stays secondary. Keep visible panel state across tab selection.
- [ ] **Step 4: GREEN**, typecheck and validator; manual accessibility/long-text checks on representative small Android.
- [ ] **Step 5: Commit** per-screen cohesive scope; do not group independently failing modules into one partial PASS.

### Task 5: Conditional Report/Product UX and final audit

**Files:**
- Modify only if independent regression shows reduced navigation: `src/app/(app)/admin/reports/index.tsx`, `admin/catalog/create.tsx`, `admin/catalog/[id].tsx`.
- Test: prior screen contracts, `scripts/workspace-v2-contract.test.mjs`, validator.

**Interfaces:**
- Report period/filter/export behavior unchanged.
- Product editor uses scroll-to-mounted-sections, not hidden unmounted form subtrees; save remains accessible, Direct Sale and image picker untouched.

- [ ] **Step 1: Record current navigation/copy/save issues and write failing UX contract per selected screen**; skip screens with no proven benefit.
- [ ] **Step 2: RED** each actual selected screen.
- [ ] **Step 3: Apply minimal compatible navigation improvement**, preserve all form state, query keys and filters.
- [ ] **Step 4: GREEN** and full Mobile checks; accessibility matrix, no native config diff; acceptance on physical device before any deployment.
- [ ] **Step 5: Commit each independent screen batch**, report actual implemented vs explicitly skipped screens.

### Task 6: Consolidated Mobile source-only gates

- [ ] **Step 1:** Run canonical CloudLinux `$NODE_BIN "$NPM_CLI" run typecheck`; `$NODE_BIN scripts/validate-project.mjs` with exact `Ukupno FAIL: 0` parser, `expo install --check`, Expo Doctor where available and contract tests for changed scopes.
- [ ] **Step 2:** Verify three OpenAPI copies remain byte-identical; no CMS domain changes, package dependencies, native files, runtimeVersion or EAS drift. Preserve R8/Gradle/AGP/API36 and 16 KB gate configurations.
- [ ] **Step 3:** Produce machine-readable operation report, exact SHA/diff, test logs and manual test blockers, on isolated branch. No OTA/EAS build/Google Play action without separate authorization.

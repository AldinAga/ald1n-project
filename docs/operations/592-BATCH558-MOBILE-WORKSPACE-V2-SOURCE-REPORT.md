# Report592 / Batch558 — Ald1n Mobile Workspace UX 2.0 (SOURCE CANDIDATE)

**Date:** 2026-10-10  
**Source authority:** main `a6da85b9e35fb6043d5d498af1403487d48cc43b`, latest Project AGENTS.md, Report590 and UX2.0 spec/implementation plan.  
**Design parent:** `320eaf41544c820f5cbb909edd728671363f8568`.  
**Implementation branch:** `feat/ald1n-mobile-workspace-v2-20261010`.  
**Last implementation commit before report:** `9cac01a39618c524f4d0c4329ec6cdd001632804`  
**App version in active source:** 1.0.0; **proposed next release:** Android v1.5.0; runtimeVersion unchanged `1.0.0-build17`; remote versionCode NOT REATTESTED by this batch.  
**Build25 / native / production:** untouched. `BUILD_CREATED=NO`, `OTA_PUBLISHED=NO`, `GOOGLE_PLAY_ACTION=NO`.

## Implemented source changes
- `src/features/admin/order-buyer-card.tsx`: new first shipping-recipient card (name, phone, postal address, note) from `order.shipping_*`; call only on validated normalized `tel:`; copy via already installed `expo-clipboard`. Do not substitute order creator/supplier info.
- `src/components/workspaces/workspace-grid.tsx`: reusable controlled, accessible, responsive 3x2 icon grid, adapts to 2 columns for small screen/large font.
- `src/components/workspaces/quick-action-hub.tsx`: compact, accessible action disclosure (4 default visible + `Sve akcije`), presentation-only, never independently performs mutations.
- `src/features/admin/orders-admin-actions.tsx`: old action buttons now feed `QuickActionHub`; shipping vs real delivery buttons kept separate; existing payment, tracking, shipment, refund, reopen, note and reassignment forms reused in **one mounted instance**. One-time next-step panel request guard prevents re-opening after refetch.
- `src/app/(app)/admin/orders/[id].tsx`: first buyer card, then contextual server-action-driven next step, then `AdminOrderActions`, then `WorkspaceGrid`, before any selected workspace content/history. New order prioritizes `accept` when permitted, not automatic shipment. Preserves six old workspace state IDs, `onShipmentSuccess={() => setWorkspace('fulfillment')}`, tracking security and archive sections.
- `src/components/ui/glyph.tsx`: only phone/copy symbol mappings added.
- `scripts/validate-project.mjs`: replace stale presentation-label fingerprint with stronger modern UI ordering check; retain the historic six-workspace, permissions, document and product-only guards.
- New source regression scripts: `scripts/workspace-v2-buyer-contract.test.mjs`, `scripts/workspace-v2-actions-contract.test.mjs`.

## Test evidence and limitations
- RED checks against original main: buyer-first/modern tile navigation absent (3 failures); actions located below workspace/activity (2 failures); old Batch86 label validator would fail after intentional label changes.
- Code Mode source-contract evaluation on branch: **22/22 PASS** (not compiler or actual Node test runner). Includes snapshot source fields, accessibility, permission-driven actions, action singleton, existing document/ship continuation, validator updated.
- **NOT RUN / NOT CLAIMED**: on-host `node --test`, TypeScript `npm run typecheck`, `node scripts/validate-project.mjs` with `Ukupno FAIL: 0`, `expo install --check`, Expo Doctor, signed Android/native build, physical device/large font/OTA.
- Current sandbox lacks the private repository's complete files/dependencies; remote GitHub contents access is available. Need canonical isolated worktree for tests before readiness/merge.

## Canonical validation instructions (isolated worktree, not dirty production checkout)
- Use canonical Node `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node`, npm CLI `/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js`. Do not run `expo install --fix` or modify pinned dependencies.
- From `apps/mobile/current` run:
  `$NODE_BIN --test scripts/workspace-v2-buyer-contract.test.mjs scripts/workspace-v2-actions-contract.test.mjs`
  `$NODE_BIN $NPM_CLI run typecheck`
  `$NODE_BIN scripts/validate-project.mjs` — parse exact final `Ukupno FAIL: 0` rather than relying on exit code alone.
  `$NODE_BIN $NPM_CLI exec -- expo install --check` and Expo Doctor if the canonical environment allows it.
- Check legacy `batch518a-apk-upload-order-continuation-contract.mjs`, `batch518b-navigation-ux-consolidation-contract.mjs`, real tracking/ship/delivery states, form drafts after workspace switch, role capabilities.
- Confirm no change to `app.config.js`, `eas.json`, `package.json`/lock, AGP/Gradle/R8/NDK/Java/ABI/16 KB/SDK36, signing, Play or release25 controller.

## Remaining and next sequential work
1. Canonical TypeScript/Node/validator RED→GREEN run, repair any actual compiler/regression failures on this branch, recheck exact diff.
2. Separate Laravel Web source candidate validation Report591 and visual acceptance; do not merge without tests.
3. **Order #134 incident**: read-only authorized production lookup of order/archive/purge/recipient and notification route; root cause not established. Do not claim bug solved or bypass archived binding.
4. Subsequent independent Mobile W2 modules: After Sales, Field Operations, Receivables, Warranties, then optional Reports/Product editor.
5. Android version `1.5.0` convergence in separate gated release branch **after** feature acceptance and fresh EAS remote code/runtime/signer/native approval; no Build25 or Play submit implied.

**Gate/result:** SOURCE IMPLEMENTED; CANONICAL/DEVICE VERIFICATION BLOCKED BY ENVIRONMENT; DEPLOYMENT NOT AUTHORIZED.

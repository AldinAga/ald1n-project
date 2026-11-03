# Laravel CMS mobile menu — isolated source fix and acceptance gate

Date: 2026-11-03

Status: **SOURCE IMPLEMENTED ON ISOLATED GITHUB BRANCH; BROWSER CI AND PRODUCTION UAT PENDING**.
No production deployment, migration, DB write, mobile OTA, EAS build or Google Play action.

## Authority reconstructed

- Canonical initial GitHub `main`: `f4b732c599cf973b6005766fa32575bed32a2493`.
- GitHub `AGENTS.md` and `docs/operations/000-LATEST.md` read.
- Google Drive canonical `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`, modified 2026-11-03, read.
- Existing B1074 full-suite remains HOLD (80 legacy failing identities); no tests waived and no authorizations inferred.
- CloudLinux production checkout historically dirty and must not be reset, pulled or cleaned.
- This fix addresses Laravel CMS in a mobile browser, not Expo Android bottom navigation.

## Reproduced source design cause

Generated `ald1n-ui-v2.css` contains the legacy BATCH149G_V6 2-column mobile layout even for 360px and below:
`minmax(0,1.25fr) minmax(102px,.75fr)`; V7 then enlarges the settings controls.
The sticky Liquid Glass header may produce translucent menu reading surfaces.
The existing authenticated Chromium browser smoke checks hamburger opening but not full drawer geometry, settings disclosure, reading opacity or menu-specific horizontal clipping.

This is an **evidence-based source diagnosis**, not yet a verified production browser reproduction.

## Exact proposed changes (isolated branch only)

1. `apps/cms/current/public/assets/css/ald1n-ui-refinement.css`: append targeted, last-loaded `@media (max-width:820px)` override. Open drawer becomes a single, full-width, vertically scrolling grid: navigation, collapsible settings, logout. Keep dropdown links in document flow, wrap labels, >=44px navigation targets, solid reading surface when open. Tablet 821-1250px stays unchanged.
2. `apps/cms/current/resources/views/layouts/app.blade.php`: replace only the quick-settings section wrapper/heading with native `<details><summary>` and a non-interactive chevron. Preserve theme and currency control data attributes, RBAC, navigation links, logout form, AJAX/search, exchange sync, cart and user identity.
3. `apps/cms/current/tests/Browser/liquid-glass-browser-smoke.cjs`: extend existing disposable authenticated Chromium harness to widths 820, 620, 390, 320px; test vertical geometry, alpha-opaque menu, zero menu horizontal scroll, native disclosure closed/open/reclosed, visible theme/currency options and in-flow dropdown. Save small-viewport screenshots.

**Generated design tokens/theme CSS, Laravel routes, API, DB, auth, permissions, mobile Expo and native build identities are NOT changed.**

## Regressions and release gates

- Source anchors verified on isolated branch after application: three intended paths, details wrapper, source load order, media rule, checks and tested viewport list present.
- **Pending:** actual GitHub Actions Chromium + PHPUnit/static on the PR merge ref; no real PASS claimed.
- **Pending:** live browser visual/functional UAT on production-sized viewport and both themes after separately gated deployment.
- **Important:** source-only PR cannot prove deployed source equals current dirty CloudLinux working tree. Before any production change, compare exact live file hashes, back up touched files, ensure rollback, preserve `.htaccess` and all staged/unstaged/untracked material. No blind `git pull`, `reset`, `clean`, entire-branch merge or cache action prior to explicit authorized deployment.

## Previous recurring error and prevention

Earlier responsive design attempted to squeeze a side-by-side settings panel into phone width, then added more overrides. This new change **does not touch generated theme CSS**. Chromium tests now guard the actual end-user layout and disclosure behavior, instead of accepting a hamburger-open flag as complete UX validation. Keep this gate for future responsive changes.

## Next safe action

Await exact PR browser CI result. If red, distinguish test environment errors from real browser/source failures, make a bounded correction on this same branch and rerun; avoid any production release until it passes. For green CI, request/attain separate production-safe activation gate and physical UAT. Keep all B1074 and storefront production HOLD rules intact.

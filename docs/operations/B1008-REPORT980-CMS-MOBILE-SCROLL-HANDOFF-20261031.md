# B1008 — Report980 cleanup PASS and CMS mobile quick-actions deployment handoff (2026-10-31)

## Completed maintenance: B1007

- Authentic host report: `980-B1007-EXACT-NINE-GENERATED-CACHE-CLEANUP-20261031-074432.md`.
- Original SHA256: `7dd71033eff52d4af464557ce8911a6b09094905a2eb85ab1b9123b543f2e294`.
- Result `PASS_B1007_EXACT_GENERATED_CACHE_CLEANUP`: all nine known generated cache-only trees deleted; 0 HELD and 0 missing.
- Inclusive account inode count: `1,082,590` -> `741,810`, NET 340,780 freed (31.47% reduction).
- Per-target sum was 340,784; discrepancy of 4 from net not attributed to a proven cause. No backup/current CMS/mobile/Node22/B990/other site touched by script.
- Current independent off-host SQL/private restore remains NOT independently proven; protect all backups.

## B1008 source state

- GitHub source PR #120: https://github.com/AldinAga/ald1n-project/pull/120
- CI: 11/11 checks successful, including PHP 8.4/MariaDB 10.11 isolated dashboard tests and CMS checks.
- Merged `main` commit: `1a7000dc9e6215759a7ae0710789d94d79ebb9d6`.
- Changed application file only: `apps/cms/current/public/assets/css/ald1n-dashboard-d1.css`.
- Removed mobile horizontal rail from dashboard D1 quick actions. Responsive 2-column grid <=519px and 3 columns 520-720px; labels wrap; 44px minimum touch targets; no page-wide overflow hiding.
- No Blade/routes/data/DB/EAS/OTA/Google Play changes.

## Local QA and limits

- Isolated Chromium/Playwright 14/14 PASS for viewport widths 320,375,390,430,520,720,768 in dark/light, verifying all seven representative links, no horizontal overflow, touch targets.
- Local guarded CSS deployment smoke fixtures 8/8 PASS, including a successful one-file change and failure/rollback cases.
- These are isolated QA fixtures, not authenticated production CMS screenshots or device UAT.

## ONE owner SSH operation (after copying the verified .sh into canonical `incoming/`)

`bash /home/icaffeco/ald1n-project/incoming/B1008-CMS-MOBILE-QUICK-ACTIONS-CSS-DEPLOY.sh --run`

When the exact change and rollback path are presented and CMS/API HTTP preflight passes, type:

`DEPLOY_B1008_CMS_MOBILE_ACTION_GRID`

- Script full SHA256: `e4900e5c14890ebaef58ef66e135cd6c1dc2037fe74ae6c555352dddd2d504bc`
- Script normalized internal self SHA256: `b7bb0099e944df690e10ee1311f0eeac6e10ccac7f054e85e469064746825b54`
- Source ZIP (Drive): https://drive.google.com/file/d/1aJ5Yybfx3ELJXtf9SR68obrmQlyYkGV0/view
- Raw script (Drive): https://drive.google.com/file/d/1dIzBYLuebS7mD3UDCSmn3_JNmXSWgSiu/view
- Script updates exactly one CSS file. No `git pull`, `reset`, `clean`, composer/npm/EAS, DB, mobile or CloudLinux Node changes. It refuses wrong source/owner/hash/Blade cache buster, unexpected HTTP, other path, or declined approval. Rolls back on failure after CSS replacement; keeps backup under `incoming/b1008-css-rollback/`.
- This writes the next numbered host operations report, expected Report981 if there is no concurrent later report.
- **Real host B1008 is NOT RUN and the production mobile UI is NOT YET verified.** Request actual report plus owner's mobile screenshot/refresh confirmation after deployment.

## Subsequent gated work

1. EAS Node22 independence: inspect and replace Android/iOS wrappers hardcoding `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node` with verified independent Node22/npm10. Test read-only EAS CLI; no production build.
2. Only after step 1 PASS, deregister the **stopped** `mobile-build.ald1n.com` CloudLinux Node application through Selector (do NOT delete folder first) and assess exact old root for safe retirement with archived source/rollback.

Functional acceptance ledger remains program 7/17, CMS 7/15, initial UI 6/6; B1007 maintenance does not artificially increment milestones.

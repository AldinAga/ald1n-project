# B1008 — Report980 cleanup PASS / CMS mobile dashboard quick-action rail fix (2026-10-31)

## Host outcome
Authentic [Report980](980-B1007-EXACT-NINE-GENERATED-CACHE-CLEANUP-20261031-074432.md), SHA256 `7dd71033eff52d4af464557ce8911a6b09094905a2eb85ab1b9123b543f2e294`, at UTC 2026-10-31T07:44:32Z records `PASS_B1007_EXACT_GENERATED_CACHE_CLEANUP`: nine historical generated-only directory trees deleted, 0 held/0 missing. Host `/home/icaffeco` inclusive inodes before 1,082,590; after 741,810; **net -340,780 = -31.47%**. The per-root sum 340,784 differs by 4 from net, cause not established. SQL/private offhost restore still unverified; all backups excluded.

## B1008 source fix
The owner's mobile CMS dashboard screenshot showed horizontal scroll beneath quick buttons. The cause is the dashboard D1 CSS `@media (max-width: 720px)` selector that overrode base grid with `flex-wrap: nowrap; overflow-x:auto`.
On branch `fix/b1008-cms-mobile-dashboard-quick-actions-20261031`, only `apps/cms/current/public/assets/css/ald1n-dashboard-d1.css` application source is changed:
- grid 2 cols at <=519px; 3 cols at 520–720px
- all quick action anchors visible (no nested horizontal rail), text wraps, min 44px target
- no global overflow clipping, no HTML/routes/permissions modified
- asset uses `filemtime()` cache buster in existing dashboard Blade.

## QA
Local isolated Chromium/Playwright representative fixture `B1008-mobile-layout-qa.py`: **14/14 PASS**, 320/375/390/430/520/720/768px x dark/light, no document or action row overflow; all seven synthetic anchors in bounds and mobile hit targets >=44px. Fixture is NOT an authenticated CMS production screenshot. QA script, JSON, 320/390 previews archived in Google Drive `Ald1n-mobile/03-REPORTS` and `04-SCRIPTS`.
Source PR should trigger `.github/workflows/ald1n-dashboard-integration-qa.yml` (PHP 8.4/MariaDB 10.11 Dashboard D1–D3 and static check). CI pending until observed. No production CSS deployed from PR.

## Host and follow-ups
Do not perform blind git pull/reset on dirty hosting checkout. Any host CSS activation requires one-file change matching exact old selector and rollback copy, live CMS/API HTTP pre/post baseline, owner SSH execution, and authenticated mobile visual acceptance before marking shipped. After that: independent Node22/npm10 EAS CLI read-only validation and source path decoupling, then remove stopped old CloudLinux Selector app and only verified stale root. No EAS/OTA/Play/build/DB migration in B1008.

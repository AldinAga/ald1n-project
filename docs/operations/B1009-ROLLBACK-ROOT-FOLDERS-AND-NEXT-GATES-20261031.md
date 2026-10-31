# B1009 — CSS rollback root cause and bounded legacy-root retirement plan (2026-10-31)

## Verified status

- Actual operator-supplied `981-B1008-CMS-MOBILE-QUICK-ACTIONS-DEPLOY-20261031-080055.md` is only ONE line: `- Gate: HOLD_B1008_POST_DEPLOY_CSS_ROLLED_BACK`; its original SHA256 is `ead1222c436509fef8a2bf52016beaaf9117f05b35e27ff479168c9cf2de3eea`. It does not state the failure stage. Thus the exact *first* failure on CloudLinux cannot be determined from Report981 alone.
- Independently verified deterministic B1008 source bug: it requires a globally counted `grep -Fc 'grid-template-columns: repeat(3, minmax(0, 1fr))'` equal to 1. Actual merged GitHub `apps/cms/current/public/assets/css/ald1n-dashboard-d1.css` includes that string at THREE lines (154, 178, 440). Therefore, whenever B1008 reaches that gate, it rejects even the correct deployed CSS and triggers rollback. The earlier HTTP gate may also fail independently; the tiny Report981 does not distinguish it.
- The original UI source fix PR120 is already merged, all 11 GitHub checks PASS, and 14/14 isolated viewport/theme browser tests PASS, but the host B1008 activation is NOT PASS.
- New **single-file standalone Bash B1009**, no `.py` or ZIP required on hosting, exactly patches one CSS file, checks the specific quick-action block + adjacent 520–720px rule rather than a global three-column string count, and verifies the deployed SHA. It records the actual named failure stage, HTTP before/after and rollback SHA in the numbered operations report. No `git pull/reset/clean`, no Node/EAS/OTA/DB.
- Source `B1009-CMS-MOBILE-QUICK-ACTIONS-CSS-DEPLOY.sh`, full SHA256 `8e60b23217deeae24e162b706fcd224355c1f2bec3c2ddcaec7e88f29e707c2d`; normalized embedded self-SHA256 `e582e45ad2c9fcfb58f5f5bbb9a8bdff164eb69dd100fe5461b87dfe0c11e670`.
- B1009 **local 9/9 synthetic fixtures PASS**: successful exact one-file activation, realistic pre-existing third-party grid declarations, wrong approval, stale old CSS, missing Blade cache-buster, symlink, duplicate old block, post-HTTP 503 with VERIFIED rollback and specific reason, and selfhash tamper. **Actual host B1009 NOT YET RUN.**
- Next one command (after uploading only the `.sh` to the canonical `incoming/`):
  `bash /home/icaffeco/ald1n-project/incoming/B1009-CMS-MOBILE-QUICK-ACTIONS-CSS-DEPLOY.sh --run`
  If the script presents the exact CSS/rollback scope and the operator wants the change, type `DEPLOY_B1009_CMS_MOBILE_ACTION_GRID`. Preserve and return the next numbered host report, plus authenticated mobile screenshot (actual CSS live) before claiming production UI fixed.

## Owner-supplied `/home/icaffeco` hidden-folder screenshots, evidence vs assumptions

The screenshots show directory entries with size 4 KB (directory metadata, NOT recursive content size), names and dates only. They DO NOT establish that entire trees are empty, backed up off-host, unused by process/cron, or safely disposable. **Nothing on this list is approved for blind deletion from screenshots alone.** Exact 34 unique visible entries are grouped by preliminary decision:

### Conditional candidates, only after exact live-use/unique-source/remote-evidence proof

- `.ald1n-b788-DEOR8kRD`, `.ald1n-b789-AdjAQkkR`, `.ald1n-b790-0DXed8GC`, `.ald1n-b791-6JhKmiYQ`, `.ald1n-b792-wEgbF3a9`, `.ald1n-b793-2ml5YbAl`, `.ald1n-b794-EuFVT4cd`, `.ald1n-b795-6ebefa91d9a94c63`, `.ald1n-b796-1YxlwQc`, `.ald1n-b796-eBmla8UC`, `.ald1n-b797-opxutBwW`, `.ald1n-b798-NEzzFnps`, `.ald1n-b803.BHDwOLDn`: probable historical isolated QA/CI experiment directories by name only; *no confirmed disposal yet*.
- `.ald1n-batch528.8n4nK8`, `.ald1n-batch528r.T84mxi`: older batch checkout candidates; preserve any unique signed artifacts/reports first.
- `.ald1n-dashboard-d123-snapshots`, `.ald1n-dashboard-d123-staging`, `.ald1n-dashboard-default-snapshots`, `.ald1n-dashboard-default-staging`, `.ald1n-liquid-glass-snapshots`, `.ald1n-retirement-clone-snapshots`, `.ald1n-user-retirement-card-ui-snapshots`: historical UI staged/snapshot candidates; inspect current references and independently verify rollback/evidence archive before deleting any one root.

### Retain / hold pending specific release, recovery or independent Node evidence

- `.ald1n-b651-cms-rollback-20261013-082609`, `.ald1n-ux2-backups`: backup/rollback content; do not remove without independently attested restore and supersession.
- `.ald1n-b990-one-build-08f8e09eeb74`, `.ald1n-eas-candidates`, `.ald1n-release-evidence`, `.ald1n-release-staging`, `.ald1n-v15-batch785-eas-json-20261021T125707Z`, `.ald1n-v15-release-20261021T122039Z`: could contain active or only verifiable Android/EAS release and one-shot build state; retain until the exact current version/source/commit and remaining release gates are closed and signed artifacts recovered. B1007 removed only old generated node_modules from a V15 tree, NOT its parent source/evidence.
- `.ald1n-toolchains`: protect absolutely until Node22/npm/EAS is decoupled from the old CloudLinux `nodevenv` and an independent CLI passes. This folder may be relevant to that work.
- `.ald1n-offhost-export-b994-20261030`, `.ald1n-offhost-export-b995-20261030`: encrypted off-host archiving/export staging; protect until independent Drive-side complete byte and restore verification (current SQL/private restore UNVERIFIED). No wildcard deletion of backups.
- `.ald1n-sdd`: role not proven from available evidence; unknown means HOLD until source/content/runtime verified.

## Mandatory next sequence once B1009 host and screenshot PASS

1. EAS Node22 isolation: independently locate Node22/npm10 (possibly under protected `.ald1n-toolchains`), confirm exact EAS CLI 25 toolchain and rewrite the mobile `package.json` hard-coded old `nodevenv/mobile-build.ald1n.com/22/bin/node` usage in a reviewed GitHub source PR; validate non-mutating EAS CLI reads only, no native production build, OTA or Play.
2. During the same maintenance phase, perform **ONE bounded old-root classification and cleanup operation**, not repeated generic audits: for every owner-visible explicit path, take exact path/uid/dev:inode/symlinks/mount/cwd/open-FD/cron/service references, count and checksum any unique sources, cross-check live release/rollback/export requirements and independently archived reports. Immediately delete ONLY exact candidates that pass every gate, with one operator confirmation. Incomplete/unknown folders remain held and are reported with an exact reason. Keep host's other websites/mail/cPanel, current CMS/mobile, SQL/private and unverified backups excluded. No size or savings should be claimed until actual after-state.
3. After EAS CLI independence has independently passed and legacy service remains STOPPED, deregister the `mobile-build.ald1n.com` CloudLinux Node.js app via the Selector UI (not `rm -rf` first). Check CMS/API and EAS CLI; verify restart/rollback plan and snapshot of old startup config/source; remove only exact old Node app root files shown safe by the bounded inventory. Do not delete independent Node22/toolchain, do not touch domain docroots for other sites.

No host folder deletion, cPanel DESTROY, production CSS deployment, EAS build or source release happened in B1009 authoring. GitHub and Drive archiving are required; post-host B1009 report/owner screenshot is the next input.

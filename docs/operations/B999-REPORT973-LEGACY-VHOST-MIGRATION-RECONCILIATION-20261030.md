# B999 — Report973, migration reconciliation and removal of stale health dependency

Date: 2026-10-30 (Europe/Belgrade). GitHub source authority at intake: `2d60f6a1d065869894680841d4cd1bec9a3f2834`.

## Authenticated new evidence

- Owner supplied original `973-B998-MOBILE-BUILD-HTTP-READONLY-DIAGNOSTIC-20261030-162132.md`, size 1712 bytes, SHA256 `9b97a8a9d269b8a54b662ad0184db544bcfae8c1b72f2d56a27fc0da820464f1`.
- Report973: original `cms.ald1n.com` GET returned HTTP **302** with curl **0**. Exact/no-curlrc/IPv4/HTTP1.1 attempts for `mobile-build.ald1n.com` all returned HTTP **000**, curl **6**, DNS resolution failure. No production mutation or cache cleanup.
- B997 Report972: `HOLD_B997_MOBILE_BUILD_VHOST_HEALTH_BASELINE; DEL=0; SKIP=0`. B998 Report973: `HOLD_B998_MOBILE_VHOST_BASELINE_UNRESOLVED`. Neither report proves inode reclamation.
- Owner confirms historical migration: build domain/folder `mobile-build.ald1n.com` is retired; current project source and CMS deployment are `/home/icaffeco/ald1n-project/apps/mobile/current` and `.../apps/cms/current` with real server backend `https://cms.ald1n.com/api/v1`. Canonical GitHub `AGENTS.md` agrees on source roots.
- **Important distinction:** code migration is established, but neither DNS failure nor owner statement independently proves every old cPanel vhost binding, open process, scheduled job, or `nodevenv/mobile-build.ald1n.com/22` executable is gone. That Node22 path remains the documented build toolchain until replaced and independently verified.

## Disposition

1. Mark **legacy mobile-build hostname GET** as an obsolete B997 check. It does not measure current Android production health and cannot justify falsely diagnosing a failure of the current app. Retire it from the *next revised* controller only, not by patching the immutable earlier script.
2. Replace it with the actual CMS root and authenticated Laravel API-route baseline (source `apps/cms/current/routes/api.php` declares `GET /api/v1/bootstrap` with Sanctum middleware). No credentials in probe; valid route must be reachable and consistently return a non-5xx, non-404 status, including expected auth rejection.
3. Keep exact nine B997 generated-only paths and all the source/uid/mount/cron/process/cwd/FD/age/symlink/inode gates and explicit typed deletion confirmation. Revalidate runtime source, real vhosts, and original B995 operations ciphertext. Make per-target before/after CMS/API status comparisons. Do not substitute a DNS override for a health check.
4. Exclude old domain root, old nodevenv, current B990 release/isolate, live CMS/vendor/storage, `/home/icaffeco/backups`, other hosted sites, global npm, shared cPanel/mail and any unclassified directory.
5. Do not delete last verified local SQL/private recovery while remote current restore proof remains missing. The archived B995 221 MB encrypted human scripts/reports is **not** a current database backup.

## Gate and deliverable

This is documentation/source reconciliation only; a replacement deletion controller has not been authorized or executed by this handoff. A prior attempt to publish a revised destructive executable was rejected by tool safety checks and was not rerouted. Existing scripts **B997, B997R and B998 must not be rerun**. One new exact-source, reviewed controller would require fresh safety tests, canonical `incoming/` placement and one new numbered host report before any inode gain can be claimed. No app, DB, Play, OTA, EAS, old vhost, backups or cache directories were modified.

Functional acceptance remains 7/17 = 41.2% overall, CMS 7/15 = 46.7%, original UI 6/6 = 100%.

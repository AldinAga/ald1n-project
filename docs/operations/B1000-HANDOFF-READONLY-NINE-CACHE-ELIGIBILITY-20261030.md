# B1000 — Nine exact generated-cache targets, CloudLinux eligibility preflight

**Date:** 2026-10-30, Europe/Belgrade. **Canonical GitHub source before B1000:** `80d9ebba6d72944262e6047ce693fa08b24a3a84`.

## Evidence received; no cleanup yet

- B992 authentic Report969 measured **1,114,125 account-home inclusive inode entries** (not a cPanel quota). Five September npm-cache trees and four detached Expo test node_modules directories are previously enumerated non-production *candidates*; ~340,000 is potential, **not actual reclamation**.
- B997 authentic Report972: `HOLD_B997_MOBILE_BUILD_VHOST_HEALTH_BASELINE; DEL=0; SKIP=0`, before enumeration/deletion.
- B998 authentic Report973 SHA256 `9b97a8a9d269b8a54b662ad0184db544bcfae8c1b72f2d56a27fc0da820464f1`: `cms.ald1n.com` HTTP302/curl0, retired `mobile-build.ald1n.com` HTTP000/curl6 across exact/IPv4/HTTP1.1/no-curlrc. A retired DNS hostname is **not** an active application health check.
- B999 GitHub main / Google Drive runbook now reconcile that the canonical source is `/home/icaffeco/ald1n-project`; no code or production release changes.
- B995 full encrypted operator scripts/reports off-host archive SHA matched, **but** B959/current SQL and private files do not yet have independently attested current restore-ready offhost coverage. Backups remain protected.

## B1000 preflight script — no deletion

- Exact immutable operator file: `B1000-ALD1N-NINE-CACHE-READONLY-ELIGIBILITY.sh`, UTF-8, 11,457 bytes, SHA256 **`56093d768ae66fc00f13b104709fa8171ffc2eeaceecaa71ad7870d26bc9cdc1`**. Normalized built-in SHA256 **`752451d86c364d592c32d9938f258ace2bb82678e59ab7f4aecec3d368616aed`**.
- SHA selfcheck, `--selftest`, tamper rejection, noncanonical `--run` rejection, source report hash, fixed exact nine targets, no deletion commands, and file manifest/static safety: **19/19 local checks PASS**. These checks are **not CloudLinux execution evidence**.
- Reads `/proc/self/mountinfo` (no unavailable `findmnt`), snapshots same-user process arguments/cron/cwd/open-fd, verifies exact source paths, ownership/dev/inode count/range and age. Reads current `https://cms.ald1n.com/` and auth-protected `https://cms.ald1n.com/api/v1/bootstrap` (Accept JSON, no credentials); returns only HTTP code and curl RC. HTTP 404/5xx or transport failure prevents a candidate from being marked eligible for review.
- The only host write is the automatically numbered next `docs/operations` report. No cache deletion, renames, mounts, Laravel/DB writes, EAS/OTA/Play, package installs, service restarts or changes to other websites.
- Even `ELIGIBLE_FOR_SEPARATE_REVIEW_ONLY` **never authorizes deletion**. The script does not independently verify every cPanel vhost mapping or cross-user process; any destructive operation needs a separately approved and validated exact-path controller. It is not appropriate to rerun B997/B997R/B998 or create a fake DNS record.

## Single owner command

Place the exact script from the supplied ZIP or GitHub in canonical:

`/home/icaffeco/ald1n-project/incoming/B1000-ALD1N-NINE-CACHE-READONLY-ELIGIBILITY.sh`

Execute:

```bash
bash /home/icaffeco/ald1n-project/incoming/B1000-ALD1N-NINE-CACHE-READONLY-ELIGIBILITY.sh --run
```

Return the next numbered operation report, expected `974-B1000-...md` if nothing else has been generated in the meantime. Preserve source SHA and report verbatim for GitHub + Google Drive archival. Do not rerun closed batches.

## Protected assets / following operation

Do not touch `apps/cms/current` vendor/storage, active mobile source, B990 release and one-shot state, `mobile-build.ald1n.com` old vhost root, `nodevenv/mobile-build.ald1n.com/22`, unrelated websites, `public_html`, cPanel/CageFS/mail, global npm, backups, .ssh, secrets or the operator history. Neither an inactive DNS name nor a source migration proves the old Node22 path is disposable.

The **next change after this preflight** is a separate strictly bounded owner-authorized cleanup of *only* the subset proven eligible, with fresh per-target checks, before/after CMS+API status, exact inode deltas, separate host report and independent GitHub/Drive archival. Do not report inode reduction until that actual host deletion evidence exists.

Functional acceptance ledger is unchanged: **program 7/17 = 41.2%; CMS 7/15 = 46.7%; initial UI 6/6 = 100%**. Hosting hygiene is not counted as a functional milestone.

# B993 — Owner-wide `/home/icaffeco/` minimal Ald1n production-only footprint policy

**Date:** 2026-10-30 (Europe/Belgrade). **Type:** scope/retention directive and documentation; **NO deletion or CloudLinux host execution**.

## Explicit owner decision

The owner states: “Svaki Fajl koji se nalazi na putanji /home/icaffeco/ i vezan je sa našim projektom, a nije nam u produkciji i nije neophodan za stabilan rad aplikacije brišemo.”

This means **scan all Ald1n-associated files and trees anywhere under `/home/icaffeco/`**; remove **every precisely classified, demonstrably redundant nonproduction path**, including development clones, test/install dependencies, caches, old release states, duplicate backups, generated artifacts, inactive websites and archived local reports. Do not constrain analysis merely to `/home/icaffeco/ald1n-project`. Do **not** delete unrelated account services or unrelated project files. Classification must follow actual service/cron/vhost/worker and source/recovery dependency evidence; old age, large size, nonproduction name and untracked Git status are not proofs by themselves.

**Important distinction:** a source snapshot/backup might not directly serve HTTP but can still be essential for safe operation/disaster recovery. Owner also requests eventual deletion of local backup inventories; that goal requires **independently verified outside-host restore material**. Retain the current two restore-ready stable copies under older §26 until that gate actually passes, then a separate approved exact-scope batch can remove redundant local backups while retaining independent recovery. B959 authentic Report959 says `OFFSITE_GOOGLE_DRIVE_UPLOAD=NOT_PERFORMED`, encrypted backup `backup174-offsite-main807f4bc8.tar.gpg` remained only local as of its report. Do not assume external backup exists.

## Authority and implementation

- Fresh GitHub `main` before this change `4f5c3e02a9c065e6fcce5bc34c8d0aebd2765726`. Canonical [AGENTS.md](../../AGENTS.md) §23 and §26 reviewed; new B993 subsection inserted under §26. Source/API/mobile code unchanged.
- [B992 prepared read-only host audit](B992-HOST-INODE-FORENSIC-AUDIT-HANDOFF-20261030.md), exact Bash [source](B992-CLOUDLINUX-HOST-INODE-FORENSIC-AUDIT.sh), full SHA256 `22664737c61e3175f00fba0377c6774cd3e46a71a16a5e95d14b546019fdd10d`. **No genuine B992 CloudLinux host report has been provided** as of this B993 policy change; therefore current inode counts, live path usage and deletion candidates remain **UNVERIFIED**. Do not launch a blind cleanup script.
- Prior [2026-08-11 inode audit](https://drive.google.com/file/d/14UuoulRQiDibFFPG4N7Pmm-TMCDlBt22/view) reported net 72,227 shared-filesystem inodes recovered, including 57,562 `node_modules` entries removed. Historical, not current cPanel account quota.
- The most valuable inode targets are usually trees containing *many* files (`node_modules`, old QA releases, cache trees), whereas a single 855 MB GPG tar uses one inode. The exact current counts must govern priorities.
- Keep active vhost document root, Laravel vendor if active, configuration/secrets, active `storage` and media, database data/restore material, relevant cron and workers, current EAS idempotency/build release evidence until complete and archived, other account services.
- Release past local report history only after GitHub archive proof, retain current/predecessor while referenced, and do not delete local source snapshot before independently proving no runtime/rollback dependency.

## Execution order (no unnecessary repeated audits)

1. **Execute existing B992 once** from canonical `incoming/`; it performs account-wide read-only measurements and writes the next numbered `docs/operations` report. Do not change/re-run B992 merely to reflect this B993 policy.
2. Analyze original B992 host receipt and cPanel account inode quota. Construct exact path manifest with `DELETE_VERIFIED_NONPRODUCTION`, `WAIT_FOR_OFFSITE_OR_ACTIVE_DEPENDENCY`, `KEEP_PRODUCTION_OR_UNKNOWN`. Rank by **independent inode counts**, disk bytes and active reference safety; note nested directory totals must not be added together.
3. Prepare and execute bounded, reversible-first **actual cleanup** only for verified category 1, with exact path manifest, no symlink/mount crossing, GitHub/Drive archive checkpoint, actual before/after inode metrics and production health checks. Do not require renewed blanket owner approval for items already covered by this B993 policy; *specific* gated evidence remains mandatory.
4. Do not touch backups that are last recovery points until encryption/offsite bytes and restore readiness are verified, and then schedule exact archive pruning with separate backup metadata/DB safety gate.

## Current status

- **OWNER_SCOPE_DIRECTIVE:** ACCEPTED.
- **CANONICAL_AGENTS_POLICY:** UPDATED (docs only).
- **B992_REAL_HOST_AUDIT:** NOT_RUN/NO_REPORT_PROVIDED.
- **INODE_SAVINGS:** UNKNOWN.
- **FILE_DELETION:** NONE.
- **PRODUCTION_SOURCE/DB/APP:** NOT_MODIFIED.
- **OFFSITE_RECOVERY:** NOT_ATTESTED.
- **NEXT_ARTIFACT:** actual numbered B992 host operations report (and, if available, cPanel inode count/quota evidence).

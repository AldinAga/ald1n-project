# B992 — CloudLinux / cPanel full-account inode forensics and deletion manifest preparation

**Date:** 2026-10-30 (Europe/Belgrade). **Scope:** source/recovery-evidence reconstruction, local immutable Bash QA, ready-to-run **read-only** host census. **No CloudLinux host scan or deletion executed by agent.**

## Owner report and operational reality

Owner reports the new Android app has been installed from Google Play and works on device. This is a **user-reported operational UAT**, not an independent signing/Play 16KiB binary audit. Android production source and working APK are not a deletion authorization for recovery material. User urgently reports hosting **file/inode limit pressure** and asks to permanently remove anything not serving production, including local backups.

**Safety boundary:** GitHub [AGENTS.md](../../AGENTS.md) §23 prohibits unrestricted destructive cleanup, deletion of unknown/untracked items, credentials, uploads, customer data and verified backups. §26 requires **two restore-ready stable backups** and checkpointing historical operations reports to GitHub before local pruning. The user's newer request is an explicit proposal to switch to **zero LOCAL backups**. That can only be implemented **after** verified independent OFF-HOST encrypted recovery/restore material; never erase last recoverable data just to relieve an inode quota. Note: a backup stored elsewhere on the same hosting account/disk is NOT offsite. No policy override has been applied to the canonical runtime.

## Real historical inode evidence (not current usage)

[2026-08-11 inode cleanup report](https://drive.google.com/file/d/14UuoulRQiDibFFPG4N7Pmm-TMCDlBt22/view) records GNU `df -i` usage changing **40,241,020 → 40,168,793**, a **72,227 inode** net reduction on the shared filesystem during previous cleanup, and **57,562** removed generated mobile `node_modules` entries. It also measured npm cache and backup/release directories. **These August numbers are historical and shared-filesystem figures, not the current cPanel account inode quota**; cannot extrapolate to 2026-10-30 without a fresh owner-account census. Old cleaning also shrank local .md history; under current §26 the GitHub exact-content archive must precede any new report-history purge.

[2026-10-28 B959 offsite status](https://drive.google.com/file/d/1zwq8prMlISxi15bjjoDeiiU0CE6UfsF4/view) and [authentic Report959](https://drive.google.com/file/d/1xfffMzSjOeo5f0Nm44yG-xdMermeq-iH/view) attest an encrypted AES256/GPG rollback package:
- exact private existing **on-host** path `/home/icaffeco/.ald1n-release-staging/b959-807f4bc855d1efe3df6d89b9d034e3b113077f2d/backup174-offsite-main807f4bc8.tar.gpg`;
- size **855,727,996 bytes**, SHA256 **`eaae607af4c6f00ba8161214a4528f5c0c282a63886d1217ec9ecb68162dd914`**;
- full encrypted-tar roundtrip in B959 PASS and prior independent SQL/private-file restore evidence;
- **`OFFSITE_GOOGLE_DRIVE_UPLOAD=NOT_PERFORMED`** in the authentic report; no subsequently verified remote encrypted blob located. Do NOT remove this file, SQL/business backups, private-file rollbacks or source restore archives until a real private offsite file and independent restore viability are attested. No passwords/keys/SQL or private files should enter GitHub/ChatGPT.

## B992 exact source / delivery

Script: `B992-CLOUDLINUX-HOST-INODE-FORENSIC-AUDIT.sh`, **full UTF-8 file SHA-256 `22664737c61e3175f00fba0377c6774cd3e46a71a16a5e95d14b546019fdd10d`**, embedded normalized integrity hash `a9f0f458863108642877cdc2047cd9966fc26aaeabae4582b69cead7558f34bc`. Full byte count **13,302**.

Sources archived:
- Exact [GitHub shell source](B992-CLOUDLINUX-HOST-INODE-FORENSIC-AUDIT.sh) and [Google Drive raw text/plain source](https://drive.google.com/file/d/1_vfKqkA_aID_o2bLrFIm94tQSHXr1avr/view); Drive source size verified as 13,302 bytes. GitHub fetch content and Drive fetch content matched **exactly**.
- Independently rendered SHA-256 and Bash source are available in the user-facing `.sh` attachment for canonical host transfer; *do not* paste normalized rich document formatting into shell.

**Local validation (not actual CloudLinux scan):**
- `bash -n` PASS.
- `--selftest` SHA integrity plus Bash syntax PASS.
- Single-character mutated copy `--selftest` **RC2 `HOLD_B992_SCRIPT_INTEGRITY_MISMATCH`**.
- Wrong invocation `--unknown` **RC2** and noncanonical `--run` **RC2** before any scan/write.
- No `rm`, `find -delete`, cache cleaners, `git reset/clean/pull`, Laravel DB changes, MySQL commands, service reloads or production EAS operation. At host execution, only one new numbered `docs/operations` Markdown report is created. GNU du/sort may use ephemeral OS scratch as usual; no application artifact is altered.

## Audit contents and confidence

Run under the real `icaffeco` CloudLinux account; require canonical `incoming/` path and next numeric report after the last `>=967`. No symlink following and `--one-file-system` only. Throttled `nice`/time-bounded GNU du full account walk produces 100 largest **inclusive** inode subtrees to depth 4; nested counts must not be summed.

Separately enumerate inode/size counts for **protected** production CMS/webroots, project Git, mail, cPanel-related state, account Node runtime, private storage, verified backup roots/rollbacks and B990 idempotency state; **review** old B984/B990 isolated checkouts, `node_modules`, npm/composer/yarn/pnpm/Gradle caches, obsolete release backups, local temp/trash/log folders, and canonical reports/incoming; classify rather than delete. Verify shallow symlink topology (not authoritative proof of real HTTP vhost). Discover named high-inode dependency trees at depth ≤6. Count backup root subdirectories without exposing filenames; verify local B959 encrypted artifact size/hash if present, but mark offsite upload and current verified-backup count **NOT_ATTESTED**. Suppress cron contents and private data; only note whether the cron authority exists. Print Linux `df -i` as **shared filesystem context, not actual cPanel account quota**.

The script is explicitly **read-only inventory**, not a safe-automatic-deletion script. It reports partial/HOLD on unreadable/racing/time-limited scans. Post-audit formulate tiered deletion manifest `SAFE_TEMP`, `CONDITIONAL_NEEDS_OFFSITE`, `PROTECT` with path, exact file-count savings, active-process/docroot references and rollback constraints; derive actual deletion from host proof, not historical assumptions. Candidate absence is not authorization.

## Exactly one owner SSH invocation, after transferring exact bytes

Place complete `.sh` file at:

`/home/icaffeco/ald1n-project/incoming/B992-CLOUDLINUX-HOST-INODE-FORENSIC-AUDIT.sh`

Then:

```bash
bash /home/icaffeco/ald1n-project/incoming/B992-CLOUDLINUX-HOST-INODE-FORENSIC-AUDIT.sh --run
```

The script writes the next monotonic `docs/operations/<NNN>-B992-HOST-INODE-FORENSIC-<datetime>.md`, displays its exact path and actual PASS/HOLD status. Owner supplies this report for a **separate, exact-scope, preflight-gated** cleanup batch, not a generic `rm -rf`.

**Current host inode count, account quota, exact number of safe deletions, offsite-copy status as of Oct 30, real active vhost mapping and expected reclamation remain UNVERIFIED.** No deletion performed.

## Post-audit sequence

1. Cross-check reported counts with cPanel account **File Usage / inode quota**, identify top 10 dominant *independent* subtrees; verify correct active docroot, cron, worker and media ownership before any purge.
2. Tier 1: generated, provably unused isolated test/build dependency trees and disposable caches with their deletion path pinned to the actual host tree, report expected reclaimed entries and zero active references; preserve B990 build evidence/state until it is archived.
3. Tier 2: stale release archives, old reports only after GitHub archive, obsolete backups with canonical `backup_runs` policy checks and a proven independent offsite/restore option. Owner-requested zero local backups requires explicit final recovery gate.
4. Each deletion batch must report before/after account inode counts, exact deleted paths, restore/recovery checks and production health probes. Stop on any path/race/symlink/manifest mismatch.

User urgency is acknowledged; **do not convert urgency into destructive shortcuts**.

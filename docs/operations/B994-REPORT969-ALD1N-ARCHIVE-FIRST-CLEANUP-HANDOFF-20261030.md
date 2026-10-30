# B994 — Archive every Ald1n operations script/report off-host before inode cleanup

**Date:** 2026-10-30 (Europe/Belgrade). **Status:** owner genuine [Report969 B992](969-B992-HOST-INODE-FORENSIC-20261030-132747.md) PASS_READONLY_MEASUREMENT, but **no actual deletion**; B994 prepared archive operator script and private Drive target.

## Source of truth and scope

Owner supplied five File Manager screenshots and original 24,962-byte B992 CloudLinux report. The report was archived **byte-preserved** to GitHub under `docs/operations/969-B992-HOST-INODE-FORENSIC-20261030-132747.md` and as [Google Drive Report969](https://docs.google.com/document/d/1MJVMLcAxhIdNIW4QodDeezjs-mzkkk_IekZWc_akiBo/edit) with exact readback.

- `du --inodes -x` finds **1,114,125 inclusive home-account entries**. The system-wide `df -i` number is NOT cPanel's account inode quota.
- Eight **mutually independent** high-inode Ald1n paths in Report969 contain **501,634 entries** (45% of full account census). Counts are *potential reclaim*, not verified deletions:
  1. `ald1n-project/tmp` — 112,499.
  2. `ald1n-workspace-v2-audit-M0VH9I` — 60,360.
  3. `ald1n-release-b984-20261029` — 60,192.
  4. `ald1n-release-b990-20261030` — 60,189.
  5. `.ald1n-v15-release-20261021T122039Z` — 60,052.
  6. `.ald1n-eas-candidates` — 59,998.
  7. `ald1n-project/apps/mobile/current/node_modules` — 57,681.
  8. `tmp/ald1n` — 30,663.
- **Protect pending live-process and actual vhost proof:** `mobile-build.ald1n.com` (43,774, including 43,430 `node_modules`), `apps/cms/current` live Laravel source/vendor/media, `nodevenv`, `public_html`, `mail`, `etc`, `.ssh`, CMS storage, `.ald1n-b990-one-build-08f8e09eeb74` (idempotency state), `.ald1n-release-evidence` (possible signed artifacts), `.ald1n-toolchains` (possible Android test tools).
- **Exclude all unrelated domains and shared facilities:** `zenskefarmerice.com`, `essenza-online.com`, `adalyatobacco.rs`, `importane.rs`, `mandza.co.rs`, `smart-elektrotechnik.net`, `sprintertravel.rs`, `turkovic.netovi`, unrelated `erp`, root `public_html`, `mail`, `lscache`, `.cagefs`, `.cpanel`, global `.npm` and `.trash` until separately attributed. Those directories may contain much larger inode counts but user forbids modifying other websites. `.npm/_npx` has 96,959 entries yet cannot be globally deleted on present evidence.
- B992 confirms `/home/icaffeco/backups/current` 3,865 entries, `/home/icaffeco/backups/releases` 498; **no verified backup_runs/current restore inventory**. B959 encrypted backup GPG exists locally, bytes 855,727,996 and SHA256 `eaae607af4c6f00ba8161214a4528f5c0c282a63886d1217ec9ecb68162dd914`, while its **Google Drive upload is NOT_ATTESTED**. Backups must be verified by actual project ownership, privacy and independent recovery before final pruning.

## Screenshot directory review — classification is preliminary

**Project-specific candidates for eventual exact-path checks:** numbered `.ald1n-b651-...`, `.ald1n-b788...` through `.ald1n-b803...`; `.ald1n-batch528.*`, dashboard staging/snapshots, liquid-glass snapshots, retirement snapshots, `.ald1n-v15-batch785-eas-json-...`, older `.ald1n-ux2-backups`, `ald1n-b632-blade-...`, B984 and B990 clones, workspace audit, old Expo release. **Not automatically disposable:** `.ald1n-b990-one-build-...` exact build intent, `.ald1n-release-evidence`, `.ald1n-release-staging`, `.ald1n-toolchains`, `.ald1n-sdd`, `ald1n-chargers-protected-backups` and `ald1n-portfolio.*` (could be a separate project). All uncertain/active data require further ownership and reference proof. The screenshot `mobile-build.ald1n.com` is a potential active vhost; none of its children are deleted merely because they include `node_modules` or `tmp`.

## Mandatory archive-first action — B994 source (NO DELETE)

- Prepared `B994-ALD1N-OFFHOST-OPS-ARCHIVE.sh`; full SHA256 **`33688a1eef350d1daff970aaafd0ea299f6530b6122f3f77fe889d6736186c28`**; embedded normalized selfhash **`0644fcc5ec56a685ad51d6855786bb779b486e0daf444d35c4a07334ccb2721f`**, exact **10,861 bytes**.
- Test: local `bash -n` PASS; local `--selftest` PASS; tamper `--selftest` RC2 `HOLD_B994_SCRIPT_INTEGRITY_MISMATCH`; wrong invocation `--unknown` RC2; noncanonical `--run` RC2. No real host B994 run as of document creation.
- The shell script scans **Ald1n-owned named top-level roots only**, including hidden `.ald1n-*`, `ald1n-project`, `ald1n-release-*`, `ald1n-workspace-*`, `mobile-build.ald1n.com` and `tmp/ald1n`. Excludes ambiguous `ald1n-portfolio*` and `ald1n-chargers-protected-backups*` until separate project attribution; excludes generic `/home/icaffeco/backups/current` and `backups/releases` until project-specific manifests. No scans/writes in other domains, `.npm`, cPanel, mail, shared root `tmp`.
- For **human-owned scripts and operational reports**, selects `.sh/.bash/.md/.txt/.log/.mjs/.cjs/.py/.ps1/.bat/.report/.jsonl/.yaml/.yml`, named JSON/XML reports and script-dir PHP/JS/TS, plus **all files** in `ald1n-project/docs/operations/` and `ald1n-project/incoming/`. Excludes generated third-party `node_modules`, `vendor`, `.git`, `dist`, `build`, `storage`, `public` etc, which are not unique project operator reports or scripts. Creates an **inside-archive** NUL-path SHA256 manifest and scoped-root log. Any unreadable/symlink/source-mutation/oversize/manifest failure -> HOLD.
- Uses streaming GNU `tar --gzip` into GPG AES256 symmetric `.tar.gz.gpg`; **no unencrypted TAR** on disk. Uses owner-entered passphrase via GPG pinentry, never exposes it to GitHub, ChatGPT, scripts or terminal report. Verifies decryption and TAR structure before PASS. Archive path is private under `/home/icaffeco/.ald1n-offhost-export-b994-20261030/`; print exact actual file bytes/SHA256. Creates only its own scratch/export and canonical next-number operations report; **deletes ZERO existing host project files**.
- Owner must upload the **encrypted binary**, not decrypted contents or passphrase, by SFTP/cPanel File Manager to the private dedicated Google Drive folder: [SCRIPTS-REPORTS-ENCRYPTED](https://drive.google.com/drive/folders/1sl0VNgX-cX8fywqg-4TmS8TeiidFwqJ2). Google Drive archive is complete only **after independent remote exact size and integrity comparison**. This step cannot be performed by the GitHub/Drive agent on the customer's CloudLinux filesystem without an SSH connector; the owner transfers the binary once.
- This is a history/archive operation, NOT backup of current SQL/private media. For the requested ZERO LOCAL BACKUPS end state, separately upload current encrypted restore-capable backup into [RECOVERY-BACKUPS-ENCRYPTED](https://drive.google.com/drive/folders/1RuCzzkWAKXr3k1jQ1rN8gy5jgD2srsfI), validate bytes/checksum and restore readiness; B959 historical GPG data might be stale relative to live October 30 SQL.

### Operator single canonical command

Transfer the exact downloaded Bash file to:
`/home/icaffeco/ald1n-project/incoming/B994-ALD1N-OFFHOST-OPS-ARCHIVE.sh`

```bash
bash /home/icaffeco/ald1n-project/incoming/B994-ALD1N-OFFHOST-OPS-ARCHIVE.sh --run
```

The Bash output includes new `<NNN>-B994-...` report, `ENCRYPTED_ARCHIVE_PATH`, `ENCRYPTED_ARCHIVE_BYTES`, `ENCRYPTED_ARCHIVE_SHA256`, `INTERNAL_MANIFEST_SHA256` and `OFFHOST_GOOGLE_DRIVE_UPLOAD=NOT_PERFORMED`. Send the report **and the private Google Drive file link after upload**; never send password. We will independently verify the raw uploaded binary and only then author a path-pinned, fail-closed cleaning script that starts with independent high-inode obsolete trees and re-checks production health.

**What is not yet done:** host B994 execution, bulk off-host transfer from host, deletion, account cPanel inode quota verification, active vhost/cron/worker assertions, current SQL private backup off-site verification. Nothing destructive has been done by this batch.

## B994 immutable current-report correction

To prevent a self-mutating artifact, **the newly created B994 report is excluded from its own GPG source snapshot** while it is being appended. Earlier operation reports, including original Report969, are archived. The owner must deliver the generated B994 host report separately afterward; GitHub and Google Drive must receive byte-identical copies **before** any historical local report pruning. This correction is encoded in the final immutable B994 script, full SHA256 `33688a1eef350d1daff970aaafd0ea299f6530b6122f3f77fe889d6736186c28`, size 10,861 bytes, [final raw Drive source](https://drive.google.com/file/d/1RVCz_JNtPjJnezyHsK761llevF8Jpuvb/view). The previous Drive script was renamed SUPERSEDED and **must not** be executed.

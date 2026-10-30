# B997 — Encrypted offsite ops archive verified; controlled generated-cache inode cleanup Wave 1

**Date:** 2026-10-30 (Europe/Belgrade). **Source:** GitHub `main` `2e0637fbb562b6cd4c9990b3519e78617dc9b61f` before B997, canonical [AGENTS.md](../../AGENTS.md) §§23/26 B993/B994, B992 authentic [Report969](969-B992-HOST-INODE-FORENSIC-20261030-132747.md) and B995 authentic [Report971](971-B995-ALD1N-OFFHOST-OPS-ARCHIVE-20261030-140251.md), canonical Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`. **No CloudLinux B997 operation has been run by assistant.**

## 1. Independent Drive offsite verification — PASS, full binary bytes

Owner reported transferring both encrypted artifacts to Google Drive. Verified via authenticated connector:

- [B995 operations archive Drive file ID `1zhm08VTs3tpbvVXUxQaCGCX50WuS0q9D`](https://drive.google.com/file/d/1zhm08VTs3tpbvVXUxQaCGCX50WuS0q9D/view), in owner-only [scripts/reports encrypted Drive folder](https://drive.google.com/drive/folders/1sl0VNgX-cX8fywqg-4TmS8TeiidFwqJ2): **221,001,337 bytes**. Downloaded the **actual 221 MB encrypted binary** from Google Drive back into an isolated verification container and computed SHA256 **`cba29ee1c6b708a3c01010aee25f738524b9fceb8a11876369b587063d273f1a`**; matches authentic host Report971 **exactly**. Original local GPG decrypt and TAR structure and source before-after file hashing passed B995. **Offsite bytes and identity independently PASS**. No GPG secret was accessed. Preserve owner's passphrase outside hosting in password manager; decrypt/restore still depends on it. Historical current Report971 was separately archived GitHub/Drive (not self-included).
- [B959 SQL/private recovery encrypted archive Drive file ID `1uWLkhRAkn-W88IGZWc9zQusrKyU8KCLB`](https://drive.google.com/file/d/1uWLkhRAkn-W88IGZWc9zQusrKyU8KCLB/view): **855,727,996 bytes**, matching owner 2026-10-28 historical host byte count, **not independently SHA256-verified**: connector raw streaming limit is 268,435,456 bytes (413 for 855MB), so only metadata/owner testimony and earlier host-local SHA `eaae607af4c6f00ba8161214a4528f5c0c282a63886d1217ec9ecb68162dd914`. Moved this existing Drive blob from scripts folder to private [encrypted recovery backups folder](https://drive.google.com/drive/folders/1RuCzzkWAKXr3k1jQ1rN8gy5jgD2srsfI) **without modifying bytes**; parent/size verified. Its data may be stale for newer Oct30 CMS DB/media changes, so **ZERO LOCAL BACKUPS gate stays HOLD** until current independently restore-ready offhost SQL/private material and retention authority. No backups touched on CloudLinux.

## 2. B997 Wave 1 — exact generated-cache targets only

Authenticated Report969 counted 1,114,125 inclusive home inode entries (NOT cPanel account quota); approximately 501,634 in eight mutually independent candidate subtrees, **not automatically safe to delete**.

B997 selects **nine hard-coded generated cache roots** only, and preserves their parents, source trees and release evidence:

Five September `npm-cache` trees under `/home/icaffeco/ald1n-project/tmp/`:
- `deferred-payment-batch149b-v2-20260909-212958/npm-cache` (baseline 21,946 inode entries);
- `deferred-payment-batch149b-v3-20260909-214009/npm-cache` (21,943);
- `consolidated-device-acceptance-batch153-20260911-134823/npm-cache` (21,943);
- `consolidated-device-acceptance-batch153-v2-20260911-140016/npm-cache` (21,946);
- `consolidated-device-acceptance-batch153-v3-20260911-142606/npm-cache` (21,943).

Four obsolete detached Expo test `node_modules` only (source trees retained):
- `/home/icaffeco/ald1n-release-b984-20261029/apps/mobile/current/node_modules`;
- `/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/node_modules`;
- `/home/icaffeco/.ald1n-v15-release-20261021T122039Z/apps/mobile/current/node_modules`;
- `/home/icaffeco/.ald1n-eas-candidates/lg03-play-testing-58b5e45b6b53a60e547e225f03ab43dd9ca85361/apps/mobile/current/node_modules`.

Gross potential from those nine generated trees is roughly **340,000 inode entries**, **subject to actual host recount and nine individual gates**; do not promise or assume recovered count. B990 checked-out release source and one-shot build state remain untouched. Unrelated hosted websites, `public_html`, `mail`, `erp`, cPanel/CageFS, `lscache`, global `~/.npm`, `mobile-build.ald1n.com` (including its active node_modules), `nodevenv`, CMS `vendor/storage/public`, git/credentials, `/home/icaffeco/backups`, B959 GPG recovery archives, and all human scripts/reports **are excluded by construction**.

## 3. Immutable operator source, tests and gates

- Local runnable source `B997-ALD1N-VERIFIED-GENERATED-INODE-CLEANUP.sh`, SHA256 **`fe68b16fd46ea0177a6e634307c4d62bf57157197564a128b08b1a5b83757263`**, **13,596 bytes**; internal normalized selfcheck SHA **`650bbeb8c1cdd1a88ce19f92abb310a1914d44c9fa20070c400f5affda19087f`**. Owner's full byte-exact source in [Google Drive text/plain archive](https://drive.google.com/file/d/1HByH-p9GiGI-4bebp4u06CIcG3F21hCp/view) (file `.txt` only due upload MIME restriction, bytes are executable `.sh`); authenticated download/re-hash matched local SHA256 **exactly**. No code bytes or passwords sent to external domains.
- Local Bash syntax PASS and `--selftest` PASS; tampered source `--selftest` exit 2, wrong canonical invocation exit 2. Isolated sandbox positive test: all **9/9 generated targets deleted** with per-path logs and before/after inode reconciliation; fake CMS/mobile-build status stable. Independent negative sandbox test: one target replaced by symlink into fake unrelated domain, **symlink HOLD**, **8/9 safe targets deleted**, unrelated domain sentinel file unchanged. **These are local simulated tests, not live CloudLinux results**.
- Production host runtime safeguards: enforce canonical incoming script path and SHA, exact `icaffeco` user, same filesystem + owner, canonical physical dirs and no symlink root; only exact paths above; compare dynamic `du --inodes` against bounded Report969-like counts (per npm cache 15,000–28,000 and per old Expo node_modules 40,000–75,000); recent source changes (45 min), cron and process args references, refreshed process command lines + self-owned process cwd/open descriptors, current mountpoints, HTTP status before and after for both cms.ald1n.com and mobile-build.ald1n.com; any candidate failing gate **SKIPPED**, never blindly deleted.
- Interactive TTY requires exact typed `DELETE_B997_VERIFIED_GENERATED_CACHES` after operator sees enumerated eligible dirs and total. Before each target, stat inode count and physical path is re-validated; single-directory same-parent quarantine move and `rm --one-file-system` applied to generated cache **only**; a failure stops with numbered canonical report and no automatic retry.
- Records numeric operations report `<NEXT>-B997-VERIFIED-GENERATED-INODE-CLEANUP-<UTC>.md`, home inclusive before/after measured inode counts, expected removed per path, HTTP statuses, skipped/HOLD statuses. The report itself is saved locally and must be uploaded separately to GitHub+Drive after creation.

### Owner's single canonical operation

Download exact Bash source into `/home/icaffeco/ald1n-project/incoming/B997-ALD1N-VERIFIED-GENERATED-INODE-CLEANUP.sh` and execute:

```bash
bash /home/icaffeco/ald1n-project/incoming/B997-ALD1N-VERIFIED-GENERATED-INODE-CLEANUP.sh --run
```

Do not paste a reconstructed Drive text preview (may normalize line endings); use supplied `.sh` file (full hash above). If any gate HOLD, **do not bypass**. No other files should be removed by this first wave.

## 4. Following waves, not authorized by B997

After authentic CloudLinux B997 report and offsite archive record: classify and remove entire definitively unused old clone/preview trees and screenshot-named hidden `.ald1n-b*` folders after vhost/cron/storage/release-state scans; archive current/predecessor reports separately; delete obsolete backup rows/folders **only after current encrypted independent offsite restore check** and exact ownership; never modify other websites/shared global cache. Verify real cPanel File Usage quota independent of shared `df -i`.

**B997 CloudLinux execution: NOT RUN by assistant. No live production, Play, OTA or other-site mutation by preparation.**

## Final B997 source and tightened live-reference gate

Final operator source was strengthened to refuse pre-existing HTTP 5xx and to detect process/cron/cwd/FD references to the **cache parent checkout directory**, not only the individual cache itself. Local positive 9/9 generated-tree deletion simulation PASS and unrelated-site symlink attack simulation PASS (8/9 removed, unrelated sentinel untouched). Final SHA256 `fe68b16fd46ea0177a6e634307c4d62bf57157197564a128b08b1a5b83757263`, 13,596 bytes, [verified final raw Drive source](https://drive.google.com/file/d/1HByH-p9GiGI-4bebp4u06CIcG3F21hCp/view). The initial Drive source was renamed `SUPERSEDED-DO-NOT-RUN`; **do not use it**. No CloudLinux deletion has been executed.

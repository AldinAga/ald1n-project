# B997R — CloudLinux `findmnt` unavailable: pre-mutation HOLD and bounded compatibility recovery

Date: 2026-10-30 (Europe/Belgrade)
Status: PRE-MUTATION HOLD IDENTIFIED; COMPATIBILITY SOURCE LOCALLY TESTED; HOST RUN PENDING.

## Actual operator evidence

Owner's exact host stdout: `HOLD_B997_MISSING_TOOL_findmnt` (SSH prompt immediately returned).

The authenticated final B997 script (`B997-ALD1N-VERIFIED-GENERATED-INODE-CLEANUP.sh`, SHA256 `fe68b16fd46ea0177a6e634307c4d62bf57157197564a128b08b1a5b83757263`) checks `findmnt` in its mandatory tool loop at lines 50-52. That loop precedes all archive verification, scratch creation, operations report creation, inode measurement and any quarantine/removal. Therefore **this particular attempt did not reach a deletion operation and produced no B997 numbered report**. Absence of `findmnt` does not establish that mountpoints are absent or that cleanup is safe.

## Authoritative source and existing gates

- GitHub `main` at assessment: `84d8ebf37f1ab664480f07b8629ebd314639d6e5`.
- `AGENTS.md` and `docs/operations/000-LATEST.md` require fail-closed mount/symlink/owner/process/cron checks, exact nine generated cache targets, protected production and offhost backups, and no broad cleanup.
- B995 encrypted offsite *scripts/reports* archive: **221,001,337 bytes**; previously independently verified Drive SHA256 `cba29ee1c6b708a3c01010aee25f738524b9fceb8a11876369b587063d273f1a`. The encrypted current SQL/private recovery backup has NOT achieved equivalent independent remote restore attestation. Therefore no local SQL/private backup deletion.
- Original B997's final script is preserved byte-for-byte; its SHA256 and normalized self-test are required by the recovery before any run.

## Recovery source

`B997R-FINDMNT-MOUNTINFO-COMPAT.sh` is an immutable, separately self-hashed compatibility entrypoint. It MUST be uploaded to `/home/icaffeco/ald1n-project/incoming/B997R-FINDMNT-MOUNTINFO-COMPAT.sh`; the original B997 file MUST also remain in canonical `incoming` with exact SHA above.

When the external `findmnt` binary is unavailable, the wrapper provides a strictly limited Bash `findmnt -rn -o TARGET` compatibility function that reads Linux `/proc/self/mountinfo` field 5. It validates mountinfo record structure, rejects unreadable/empty/malformed mount tables, exports the function into the original B997 child Bash, and otherwise fails closed. Linux mount targets are kernel-octal-escaped for special characters; **all nine immutable B997 target paths contain no spaces/backslashes**, so mountpoints on or under each target retain their unchanged ASCII prefix, including nested bind mounts on the same filesystem. No mount is traversed or modified. If a genuine `findmnt` binary is available, the wrapper defers to the original implementation.

This recovery does not contain file deletion logic. It requires original B997 SHA256, canonical paths, user and self-hash and then executes original `B997 --run` **with all existing controls unchanged**. It does NOT auto-confirm a deletion, bypass a failed check, run a different target list, restart an interrupted cleanup, change Android/Play/OTA, or alter Laravel source.

## Local evidence (not production success)

- `bash -n`: PASS; wrapper `--selftest` / Bash function child import: PASS.
- **24/24** local compatibility/negative QA checks PASS, including original SHA mismatch rejection, script tamper rejection, wrong invocation, real parser read, nested mount, escaped nested mount, malformed mountinfo, empty mountinfo and simulated child Bash with `findmnt` omitted from PATH.
- No `python3`, `/dev/fd`, process substitution, `git reset/clean/push`, EAS invocation, or file-removal command inside the delivered wrapper.
- This is local compatibility QA. **Real CloudLinux mountinfo readability, actual user/process state, nine candidate lstat/inode bounds, interactive deletion gate, before/after metrics and CMS/mobile-build health have NOT yet passed**.

## Single operator invocation (after placing exact wrapper .sh in canonical incoming)

```bash
bash /home/icaffeco/ald1n-project/incoming/B997R-FINDMNT-MOUNTINFO-COMPAT.sh --run
```

The original B997 will display the nine exact candidates and require manual typing `DELETE_B997_VERIFIED_GENERATED_CACHES` if all checks permit it. If any gate holds, do not retry automatically and do not work around that gate. Return the exact automatically numbered `docs/operations` host report, including successful, skipped, partial-deletion or HOLD results. A pre-report wrapper failure may produce only `HOLD_B997R_*` stdout/stderr, which should be returned exactly.

## Scope boundary and follow-up

Only five specifically enumerated September 2026 `npm-cache` trees and four detached Expo QA `node_modules` trees are candidates. Around 340,000 inode entries are **potential**, not recovered. All backups, production CMS/vendor/storage, B990 release state, public websites, other customer sites, active mobile-build vhost, cPanel, mail, global npm, operator scripts and reports remain excluded.

Following a real host receipt, archive the original host report on GitHub and private Drive; compare measured before/after counts. Only later prepare separate, gated retired-source/backup cleanup. No actual filesystem changes are claimed from B997R preparation.

## Project acceptance ledger (unchanged)

Program **7/17 = 41.2%**; CMS **7/15 = 46.7%**; original UI milestone **6/6 = 100%**. This maintenance recovery does not close a functional milestone. Android v1.5 production signed-binary/Play/UAT status must be independently verified before any new build or release claim. SDK58 is not started by this recovery.

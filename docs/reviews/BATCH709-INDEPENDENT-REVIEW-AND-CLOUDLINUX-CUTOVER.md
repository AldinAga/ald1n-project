# Batch709 — independent-review handoff and CloudLinux cutover contract

2026-10-17. **DRAFT / NO PRODUCTION RELEASE AUTHORIZATION.**

## Authority

| Source | Exact SHA | Status |
|---|---|---|
| main | `bc7fdd3580c02ac3d45fd1b76b3b09aaf24bd60d` | existing production authority; not assumed deployed |
| PR16 user retirement | `0ccf871451ee7efeebbb257282c2615d8eab172e` | DRAFT |
| PR20 Laravel/MySQL baseline | `f3cf3045fa3c0dd052c21aae0200147f8702c2dc` | DRAFT |
| PR21 ENUM migration | `c210e91e587fc00d87b0c729f7b4ed1572b48bbc` | DRAFT |
| PR22 MariaDB parity | `aab218a07017f1be11f5386559b054afe7fb796e` | DRAFT |
| PR23 QA controller | `d80ad94e6fadb9f8bffc177d23e1a63c843fd26d` | DRAFT |
| PR24 staging preflight | `2701959df7d7e4f085b10d596387c610b9e54863` | DRAFT |

Consolidated issue: [Issue #25](https://github.com/AldinAga/ald1n-project/issues/25). There is **no review by an independent authorized second person** at the time of preparing this packet. Source author comments do not count.

## Review findings, prioritized

1. **BLOCKER — independent review absent.** Require at least one nonauthor, current-head APPROVED review of the feature/migration chain, plus user-specific authority for sensitive user-retirement/security impacts. Review PR #16 user retirement and PR #20+#21+#22 MariaDB migration together, then QA/controller PR #23+#24. The reviewer must verify no unresolved important/critical comments.
2. **BLOCKER — production MDL/long transactions unavailable.** Report702: actual database version `10.11.19-MariaDB-cll-lve`, `performance_schema=OFF`, insufficient global process/InnoDB transaction visibility. CI verified `ALGORITHM=INSTANT` and an approximate 5s metadata-lock timeout on isolated MariaDB 10.11.19; this does **not** prove the CloudLinux binary or current lock state. Require privileged DBA/hosting-provider attestation or an explicit separately approved owner risk decision; never auto-upgrade privileges, enable system-wide instrumentation or run test ALTER on production.
3. **BLOCKER — backups are integrity verified, not restore verified.** Report702: backup IDs 156/155 were the latest *at that time*. Refresh at release time. The first-party `app:backup-verify` validates manifests/SQL gzip/private file hashes; it is not a restore. **Owner explicitly deferred restore drill**; do not run it or assert PASS without owner instruction.
4. **BLOCKER — current host is dirty/stale.** Source `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`, 13 tracked unstaged + 2 staged, Report699 eight protected private overrides. Do not `git pull`, `reset`, `clean`, `rsync --delete`, overwrite `.env`, `storage`, live `public/.htaccess`, or source-controlled report/history.
5. **BLOCKER — fixed physical DocumentRoot needs proven cutover mode.** cPanel UI shows configured `cms.ald1n.com` root `/home/icaffeco/ald1n-project/apps/cms/current/public` (account home abbreviated in UI). That proves *configuration*, not Apache runtime. A physical `current` directory cannot support an atomic symlink flip without a separately approved topology migration; an overlay copy is also non-atomic. Require explicit maintenance window and rollback plan before any path switch.
6. **REVIEW FINDING — legacy restore runbook uses a stale canonical path.** `apps/cms/current/docs/BACKUP-RESTORE-DRILL.md` under main contains `cd /home/icaffeco/cms.ald1n.com`; the project AGENTS contract instead requires `/home/icaffeco/ald1n-project/apps/cms/current`. Reviewers must not execute the legacy path; reconcile documentation in a separately scoped approved change.
7. **REVIEW FINDING — do not automatically create backup as a read-only verification.** `BackupService::create()` calls `prune()` after a successful creation; pruning can delete superseded backup records/files once two verified backups exist. Batch710 explicitly does **not** call `app:backup-create`, `prune`, delete or restore.
8. **ROLLBACK blocker — append-only ENUM migration `down()` intentionally throws.** Do not run `migrate:rollback` on production expecting automatic reversal of this schema change; require an independently reviewed backup-backed roll-forward or original-enum-provenance manual rollback. This is not inherently a bug but a specific operational prerequisite.

## Validated safety contract and layout decision

`scripts/batch709/cutover-contract.mjs` is a pure, dry-run release-readiness contract. It validates exact SHA, QA, independently signed review, the matching cPanel/Apache root, the protected `.htaccess` SHA, eight private snapshot attestations, two unique logically verified backups with a latest age <24 hours, explicit restore proof, privileged MDL evidence, MariaDB server version and owner-approved maintenance window. Even when its inputs all pass, **it never mutates production and returns no deploy commands**.

With a normal physical `current` directory, a safe *atomic symlink* cutover **does not currently exist**. No code here performs a two-rename swap, live overlay, maintenance toggle or migrations. A final Batch711 can only be designed after host Batch710 provides facts and the owner separately approves a compatible cutover and restore/rollback strategy.

## Batch710 — one operator-only read-only SSH step

File expected under canonical `/home/icaffeco/ald1n-project/incoming/ald1n-b710-release-gates-readonly-v1.sh` (SHA-256 to be supplied separately after local verification). It reads Report706 byte-exact, checks the host's actual MariaDB and backup_runs state, runs first-party read-only `app:backup-verify` for the two newest completed backup records, inventories current/sibling directory/symlink topology and records a numbered report under `docs/operations` without modifying production. It will not generate another redundant cPanel UAPI call; owner UI screenshot already attested the configured DocumentRoot.

## Final mandatory human sign-offs

- Independent second-person security/code APPROVED reviews on exact source. **Author/self-review does not count.**
- Hosting provider DBA signoff on live metadata-lock contention/transaction visibility and safe window.
- Owner explicit resume of isolated restore drill, or separately documented approved alternative risk decision and recovery strategy; deferred does not equal PASS.
- Owner acceptance of tested host staging topology/maintenance/rollback procedure, exact SHA and release time.

**Release remains HOLD** even when offline tests pass. There is no production deploy adapter installed here.

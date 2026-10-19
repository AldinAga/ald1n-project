# ECC -> Ald1n CloudLinux compatibility contract (strict)

**Authority:** Ald1n root `AGENTS.md` (sections 1A–1B, 2–26), current GitHub `main`, `docs/operations/000-LATEST.md` and the newest applicable numbered host report. Upstream [affaan-m/ECC](https://github.com/affaan-m/ECC) commit `76aaa77df6adc5b889e689007bf99b9a4f74af34` provides **ideas only**; it is never allowed to override project, host, release or user permissions. Derived integration stays MIT-licensed.

## 0. Read and decide BEFORE any batch

1. Fetch the **current remote `main` SHA**, current `AGENTS.md`, `docs/operations/000-LATEST.md`, and most recent relevant `docs/operations` reports. Determine current PR, source SHA, CI, production deployed file state, and which batches already PASS. User-supplied host reports override guesses about the host; do not silently confuse CI with live production.
2. Define exact **allowed source paths**, branch, expected base SHA, reversible mutation scope, and separate approvals for GitHub merge, production deployment, DB migration, Android build, EAS OTA and Google Play. A request to adopt ECC **does not** authorize any of those other actions.
3. If host evidence, exact pin, checksum, required report or toolchain is missing: `HOLD`, no workaround that weakens the gate. Preserve a dirty/stale host Git checkout. Never run host `git pull`, `reset`, `clean`, forced checkout or broad sync. Use GitHub isolated branch / disposable CI.
4. Status is **source-specific**: include `SOURCE_COMMIT`, `BATCH_RESULT`, `FAILED_STAGE`, `SOURCE_MUTATION`, `COMMIT_CREATED`, `PUSH_COMPLETED`, `PRODUCTION_PROMOTED`, `BUILD_CREATED`, `GOOGLE_PLAY_ACTION`, `NEXT_ACTION`.

## 1. Immutable host/profile

| Item | Canonical rule |
| --- | --- |
| Host | CloudLinux/cPanel on Unlimited.rs, restricted shared hosting |
| Root | `/home/icaffeco/ald1n-project` |
| CMS | `apps/cms/current`, Laravel 13, PHP 8.4, canonical webroot current/public |
| DB | MariaDB `10.11.19-MariaDB-cll-lve`; Laravel PDO driver name `mysql` is not evidence of MySQL server |
| Mobile | Expo SDK 57, RN 0.86, Expo Router, exact app identity preserved |
| Node | `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node` |
| npm CLI | `/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js` |
| JDK | Java 17; verify `jar`, `keytool`, `jarsigner` and real execution before any signature/native test |
| EAS | `eas-cli@25.0.0` via pinned Node/npm `exec`; project-scoped commands from `apps/mobile/current` |
| Android baseline | app `1.0.0`, versionCode `25`, runtimeVersion `1.0.0-build17`, production profile/channel; re-attest for a production build |
| CSS/UI release | Never treat a GitHub merge or CI screenshot as production deployment |

No global tool upgrades, dependency "fix-all" automation, unexpected native dependency changes, Node/npm PATH assumptions, or EAS releases. Never use `expo install --fix` on this hosting. Full native compile only in approved disposable environment with real JDK17 and the exact dependency graph.

## 2. Operator script + evidence paths are mandatory

- **Only host operator executable location:** `/home/icaffeco/ald1n-project/incoming/<script>`. Upload script there *before* sharing any executable command; verify script **SHA-256 against the expected digest** and `bash -n`. Missing script/checksum: `HOLD`; never regenerate a digest from arbitrary/unverified bytes.
- **Only batch report location:** `/home/icaffeco/ald1n-project/docs/operations/<SEQ>-<name>.md`. Sequence = next unused number after the maximum of live hosting and remote GitHub reports. Preserve original bytes and predecessor fingerprint; upload historical evidence to GitHub before any rotation.
- Start host Bash operator scripts with nonfatal terminal clear per root `AGENTS.md` section 26, show live output, capture **underlying command RC rather than `tee` RC**, and report exact source SHA, script hash, scopes, checks and failures.
- Never put runtime scratch, npm caches, temporary report clones, locks, or generated build products into the host Git worktree. Do not overwrite `.env`, `.htaccess`, `vendor`, `storage`, uploaded images, backups, secrets, EAS config or credentials.
- **CloudLinux hard bans inside host operator scripts:** `python3`, `/dev/fd`, shell process substitution (both input and output), Bash descriptor relay tricks. Plain ASCII flags; prefer regular files/named FIFOs and explicit RC handling. Avoid `set -e`/ERR traps masking expected `RC=1`; avoid `set -u` surprises on literal dollar signs.
- No automatic cleanup. Preserve exactly two verified stable backups under the current backup policy; never delete any backup or current/predecessor report without separate evidence and authorization.

## 3. Exact quality gates

- **CMS:** canonical `php bin/static-check.php` (expected `983` checks, `0` failures), scoped Laravel tests, and authentic Chromium screenshots across supported themes/roles/widths as needed. PHP lint **alone is not equivalent**. Use disposable CI for database-backed tests.
- **MariaDB migrations:** only when explicitly approved. Test the real MariaDB 10.11.19 SQL and quoted nullable ENUM defaults; independently assess metadata locks/privileges. Lack of metadata-lock visibility is `HOLD`, not permission to migrate. Generic ECC `php artisan migrate` examples are prohibited in the host command path.
- **Mobile:** pinned Node/npm `run typecheck`, project validator with exact `Ukupno FAIL: 0` parsing, real JDK17 signed/native checks when relevant. Three OpenAPI contracts must remain byte identical after approved contract updates. Preserve disabled Product Variants, roles, archive, Direct Sale and cross-platform parity.
- **Behavior fixes:** prove RED -> minimal correction -> GREEN -> broader gates. Never reuse unrelated successful CI as proof of a changed commit.
- **Release:** verify source, allowed files, backup and rollback; `CMS_HTTP_LOGIN_BEFORE=200` is *only* a pre-deployment smoke, not a deployment or full authenticated UAT.

## 4. Current Liquid Glass 2.0 evidence / active HOLD

Operator Report749 (`749-CMS-LIQUID-GLASS-2-preflight-20261018T135231Z.md`, 2026-10-18) attests:
- Expected CMS application source from GitHub main `e8c5cfdacd1da503142e8e29a8ba5fd017776224`.
- Seven CMS files checked against baseline/source Git blobs, `CMS_HTTP_LOGIN_BEFORE=200`, `PREFLIGHT=PASS_SOURCE_AND_RUNTIME_GATES`, `EXIT_RC=0`.
- **`RELEASE=HOLD_AWAITING_SEPARATE_OWNER_DEPLOY_AUTHORIZATION`**: no deployment.
- Executable `incoming/ald1n-liquid-glass-2-cms-controlled-release.sh`, expected SHA256 `a31c945f8981c7d20f49a85a5d1b1450e710f19a25136356b04666f637595146`.

**Critical main-SHA coupling:** merging this docs/skills-only ECC PR would advance `main` beyond the release kit's exact pinned commit even though CMS application bytes could remain unchanged. Do not merge ECC into `main` until the operator's pinned Liquid Glass release gate is resolved or a separate source-aware attestation/update explicitly permits docs-only main movement. Never silently repin an approved release script.

## 5. ECC adoption model

- Retain **curated, sanitized skills** under `.agents/skills`. Use the central rules in this document. Source references to upstream ECC are for consultation, **not** auto-executed instructions.
- Project `.codex/agents/` are **read-only** assistants; GitHub connectors may read and prepare PRs only within separately approved scope. No claim of runtime agent activation without observing the host client.
- Do **not** add or execute ECC `install.sh`, project hooks, external MCP servers, automatic package installation, environment migrations, global settings, `eval`-based commands or third-party telemetry in production.
- An ECC upgrade requires a reviewed upstream SHA, license check, bounded diff, static policy test, exact-source CI, and explicit approval.

## 6. Procedure for the next development batch

1. State verified source/head and most recent operation report.
2. Pick the matching curated ECC skill and read this contract; state the exact intended file allowlist and branch.
3. Implement a small change on an isolated branch, avoid production/migrations/releases.
4. Run source-specific tests and policy gate. If error: diagnose logs, targeted fix; if PASS: proceed to a PR.
5. Update evidence with source SHA and real results; request independent merge/production authorization only when each applicable gate is satisfied.

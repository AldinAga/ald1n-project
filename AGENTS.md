# AGENTS.md — Ald1n CMS & Mobile execution guardrails

This file is the canonical working contract for automated/agent-assisted changes in the **Ald1n CMS & Mobile** repository.

Its purpose is practical: prevent repeated recovery batches caused by known CloudLinux, Expo/EAS, Git, shell, validator, and release-environment edge cases.

**Read this file before preparing or executing any batch.** If a historical operation report conflicts with this file, first determine whether the report documents a newer intentional change. Do not silently override a newer approved rule.

Last consolidated: **2026-10-23** (owner execution autonomy reaffirmed; older technical guardrails retained).

---

## 0. Owner execution autonomy and communication contract (permanent, 2026-10-23)

**Do the work, do not narrate the work.** For every Ald1n development request, the agent independently reads fresh GitHub `main`, this file, `docs/operations/000-LATEST.md`, current operations evidence, and canonical Google Drive guidance; then performs available source edits, GitHub commits/PR work, CI testing, review, and documentation/Drive archiving **without asking the owner for routine approval or explaining each planned engineering stage**.

- Deliver the completed, verified result, or one precise blocker/required operator action. Keep the owner-facing response compact; no extensive plans, status essays, tutorials, repeated historical summaries, or requests to choose obvious implementation steps.
- **Involve the owner only when an action genuinely requires their SSH execution on CloudLinux** (or when an external privileged action cannot be performed using available authorized tools). In the ordinary SSH case deliver a tested, integrity-self-checking executable under the canonical `incoming/` path and **one copy/paste SSH invocation**, then request the automatically numbered `docs/operations` report. No separate checksum command.
- Perform all GitHub source/branch/PR, CI, read-only analysis, project documents, and permissible Google Drive organization automatically before the SSH handoff. Preserve any tool safety blocks and never route around denied upload/edit operations.
- This autonomy does **not** waive release gates: fail closed on unresolved evidence, protect the dirty production checkout, do not deploy, perform data-destructive work, publish OTA, run EAS native builds, or submit Google Play without applicable authorization. No fake PASS. Do not request owner involvement just to repeat completed work.
- If a genuine required operator action is not yet prepared, continue available work first; report the exact blocker if and only if it remains at the end.

---

## 1. Repository and runtime authority

Repository:

```text
/home/icaffeco/ald1n-project
```

Primary locations:

```text
CMS:    /home/icaffeco/ald1n-project/apps/cms/current
Mobile: /home/icaffeco/ald1n-project/apps/mobile/current
Docs:   /home/icaffeco/ald1n-project/docs/operations
Incoming executable batches:
        /home/icaffeco/ald1n-project/incoming
```

Production mobile identity:

```text
Display name:    Ald1n CMS
Expo owner:      ald1n
Expo slug:       ald1n-mobile
Expo project ID: d43b3866-6838-4217-a23e-3dc7f2cc76cc
Android package: com.ald1n.mobile
Production API:  https://cms.ald1n.com/api/v1
App version:     1.0.0
Current runtime: 1.0.0-build17
```

Current platform baseline:

```text
CMS:    Laravel 13 / PHP 8.4
Mobile: Expo SDK 57 / React Native 0.86 / TypeScript
```

Do not change these identities incidentally inside maintenance, bugfix, documentation, or OTA batches.

---

## 1A. Mandatory operator batch artifacts (project-owner policy, 2026-10-12)

**Permanent rule for EVERY Ald1n CMS/Mobile chat, batch, sub-batch, recovery, QA, CLI/version audit, and agent/operator handoff. Read and enforce it before providing, uploading, generating or running ANY batch execution commands. No implicit exceptions.**

### Operator-delivered and executable scripts — one location ONLY

```text
/home/icaffeco/ald1n-project/incoming
```

- All new or revised operator-facing scripts delivered for manual execution on this hosting (e.g. `.sh`, `.php`, `.mjs`, `.js`, or helper files that are part of that operator batch) must be uploaded/placed in **this exact directory** before execution.
- Every copy/paste execution command MUST refer to `/home/icaffeco/ald1n-project/incoming/<script-name>`, never `$HOME/<script-name>`, a root-level stray script, or an ad-hoc script in the CMS/Mobile source.
- Inspect and verify the script's content, expected SHA-256 and canonical location before running. If it is missing, report HOLD; do not silently fall back to a legacy copy at another path.
- The root `/incoming/` is intentionally Git-ignored: do NOT change `.gitignore`, force-add runtime scripts or perform a broad git sync as a workaround. Tracked application source/test scripts within their actual packages are a different category and must **not** be moved out of their source locations; this policy governs delivered operator-execution artifacts.

### Batch-generated reports and terminal logs — one location ONLY

```text
/home/icaffeco/ald1n-project/docs/operations
```

- Every newly generated post-execution batch report, smoke/QA receipt, full terminal log, failure report, and compatibility-lab result MUST be **created at this exact directory**. Do not save batch reports/logs to `$HOME`, `/tmp`, `incoming`, or unrelated app directories.
- Preserve the existing sequential numeric report prefix rule in section 26: first resolve the highest report number from current GitHub `main` AND any local `docs/operations` reports, then assign the next unused number, without overwriting or reusing old reports. Distinguish **Batch number** from **Report sequence number**.
- A report must capture the exact source SHA/branch, script name and SHA-256, controls executed, real return codes, PASS/FAIL/HOLD gates, mutation status, and any limitations. Terminal output must be visible LIVE and recorded in that report (`tee`; preserve the audited command's RC, not `tee`'s RC).
- For read-only QA, only write the designated operation report into the project working tree. Temporary extraction, npm cache, lock and scratch artifacts go outside the repository as required by section 9 and must not be used as a report destination.
- The GitHub historical report/archive and local current/predecessor retention rules of section 26 remain in force: checkpoint report evidence to GitHub before pruning archived local copies. Never delete uncheckpointed reports.
- If canonical `incoming` or `docs/operations` is unavailable, or the report sequence cannot be established safely, fail CLOSED and explain the blocker; do not invent a different destination.

### Canonical operator invocation (applies to Batch632 and future batches)

```bash
ROOT=/home/icaffeco/ald1n-project
SCRIPT="$ROOT/incoming/<batch-script>.sh"
test -f "$SCRIPT" || { echo "HOLD: missing canonical batch script: $SCRIPT"; exit 2; }
# Verify the exact advertised SHA-256 BEFORE running.
# The script must write its numbered terminal report to "$ROOT/docs/operations".
bash "$SCRIPT"
```

Do not retroactively move or rewrite completed production source, release archives or historic reports to implement this path policy. This is a delivery/execution/output rule for current and future batches.

---

## 1B. Immutable CloudLinux/Unlimited.rs execution profile (owner re-confirmed 2026-10-16)

This is an entrypoint rule for EVERY NEW Ald1n chat and operator batch, not an optional reminder.

- Hosting: **Unlimited.rs CloudLinux/cPanel**, restricted shared hosting. Canonical project: `/home/icaffeco/ald1n-project`, Laravel `apps/cms/current`, Expo mobile `apps/mobile/current`.
- Production DB is MariaDB `10.11.19-MariaDB-cll-lve` (owner Report702, 2026-10-17), although Laravel PDO reports `mysql`. Before schema/ENUM DDL, test the exact MariaDB major/minor/patch family separately; MySQL 8.4 CI alone is insufficient. MariaDB quotes literal `COLUMN_DEFAULT` but returns nullable SQL `NULL` as unquoted text `NULL`. On shared hosting, lack of permission to see metadata locks is HOLD, not proof that locks are absent; do not enable global probes or change DB privileges automatically.
- **2026-10-21 PR78 bounded online DDL lesson (source-only CI [39639301065](https://github.com/AldinAga/ald1n-project/actions/runs/39639301065)):** for proposed online ALTERs of existing populated **MariaDB/InnoDB** production tables, do not rely on an unrestricted `Schema::table` that could use a table copy or wait indefinitely. First verify real storage engine and the exact MariaDB version family, then require explicitly supported `ALGORITHM=INSTANT, LOCK=NONE` for the proposed operation and a bounded *session* `lock_wait_timeout` (verified at 5 seconds for the warranty preference column), restoring the previous value after either success or failure. If INSTANT/LOCK=NONE cannot be proven for the target table/operation, **HOLD** rather than silently selecting slower/locking DDL. Before approving a production migration, test a *populated* disposable MariaDB instance and a second connection holding a metadata lock; verify failure within the bound without schema mutation, safe retry, idempotency and preservation of existing rows. For user opt-in/consent columns, default to **OFF** for existing and new users and do not automatically drop a column containing enabled consents on rollback. Passing disposable CI does not attest the CloudLinux-modified MariaDB binary, fresh production backup/restore, or authorize production schema change.
- **2026-10-21 MySQL 8.4 SQL 1221 cross-family rule (PR78 recovery):** Laravel's PDO driver name is `mysql` for **both** MySQL and MariaDB, but their INSTANT DDL lock syntax differs. **MariaDB 10.11** accepts `ALGORITHM=INSTANT, LOCK=NONE`; **MySQL 8.4** rejects this combination with SQL 1221 and requires `ALGORITHM=INSTANT` with its default lock strategy (do not set `LOCK=NONE`). Use the real `SELECT VERSION()` to choose the *exact* separately tested SQL, and fail closed on unknown versions; retain InnoDB preflight and bounded/restored metadata-lock timeout. This is not permission for a slower `COPY`/`INPLACE` fallback. Real MySQL8.4 negative evidence [CI 39639308774](https://github.com/AldinAga/ald1n-project/actions/runs/39639308774), corrected MySQL8.4 **and** MariaDB10.11.19 success [CI 39640326290](https://github.com/AldinAga/ald1n-project/actions/runs/39640326290). No production DDL was run.

- Node: CloudLinux **Node.js 22** environment `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node`; npm CLI `/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js`. Always check these exact executables before use. Do not assume shell `node`/`npm` are resolved. No global npm upgrade (`npm install -g`) and no generic `expo install --fix`; never upgrade npm solely because it prints a notice.
- EAS CLI: pinned `eas-cli@25.0.0` invoked through the canonical Node22/npm exec, subject to the separately approved authenticated production release gates. A CI PASS is not permission to publish OTA, build, or Play submission.
- JDK: JDK **17** is mandatory for native/signature regression tests; on this host `java`, `jar`, `jarsigner`, and `keytool` may be absent from PATH entirely. Check executable availability and real smoke functionality. If unavailable, a verified portable Temurin JDK17 may be installed in private `$HOME/.ald1n-toolchains` with SHA-256 verification, non-root, no global environment edits. Never bypass failed signature tests.
- **Owner UX rule (2026-10-21): do not require or suggest a separate manual `sha256sum` command for ordinary operator batch execution.** File integrity checks, when needed, must be done automatically inside the script and reported without a second operator command. This changes the user-facing workflow, NOT the requirement to protect executable source integrity or release provenance. Give the owner one straightforward invocation from the canonical `incoming/` path.
- **Mandatory CloudLinux delivery review (2026-10-21):** before giving any operator script, statically reject the patterns `<(`, `>(`, `/dev/fd`, `exec > >(`, `done < <(` and any shell process substitution, and run `bash -n` or `sh -n` matching its interpreter. Prior Batch687 and the 2026-10-21 v1.5 batch both failed with `/dev/fd/63`; never repeat this failure. Use `tee` by ordinary pipe with explicit `PIPESTATUS` or a regular report file, not process substitution.
- **Hard banned shell features** on this host: process substitution `<( ... )` / `>( ... )`, reliance on `/dev/fd`, `python3`, and bash fd relay tricks. The 2026-10-16 Batch687 V3 failed at `/dev/fd/63` because its author used `done < <(find ...)` and `exec > >(tee ...)` despite the existing section 10 ban. All future executable scripts MUST pass a static scan for these constructs before delivery, plus `bash -n`.
- Reporting: scripts exclusively under `incoming/`; logs/reports exclusively under `docs/operations/`, sequential prefix determined from GitHub `main` **and** local reports. For terminal logs, use a real named FIFO or normal files with explicit exit-code capture; no fd substitution.
- Preserve dirty/stale host Git trees; source of truth is latest remote `main` and current operations. No blind `git pull`/`reset`/`clean`, automatic overwrite or production rollout. Remote docs-only advancement after an approved application commit must be distinguished from application drift, never silently ignored.
- For CloudLinux-related failures, prefer **one consolidated host audit/recovery batch**, record actual observed versions, paths, RCs and blockers. Do not chain speculative workaround batches.
- **2026-10-21 Report784 recovery — isolated Expo checkout:** `eas whoami` may PASS before app dependencies exist, while `eas project:info` FAILS loading `expo-router` in `app.config.js`. In a **fresh isolated clone** requiring later TypeScript/validator/Android export, run the approved exact Node22/npm10 **lockfile install `npm ci` before any project-scoped EAS command that loads Expo config plugins**. First perform static source/ref/config checks, then install already-required QA dependencies, then run EAS `project:info` and remote-version reads from the isolated app root. Do not run `eas project:info` on the pristine checkout before dependency install and do not re-clone/re-fetch on a recovery that can safely reuse an exact-SHA clean isolate. Do not install packages merely for an unrelated read-only compatibility query. Keep the isolated checkout under `$HOME` **outside** `/home/icaffeco/ald1n-project`; never create `.ald1n-v15-isolated-*` inside the production repo. Report784 already proved EAS account login and exact source SHA; preserve that evidence and resume at the failed stage.

- Production Android baseline until re-attested: app 1.0.0, versionCode 25, runtime 1.0.0-build17, production channel, com.ald1n.mobile. The latest OTA group and deployed source must always be reverified before mutation. iOS stays deferred by owner.
- The user expects these rules to be retrieved from GitHub `AGENTS.md` at the **start** of every new Ald1n project chat/batch, together with `docs/operations/000-LATEST.md` and recent reports.

---

## 2. Permanent product rules

These are not optional cleanup targets or legacy TODOs.

- **Product Variants are permanently decommissioned** from active UI, routes, API contracts, services, Mobile types, and operational workflows. Do not restore variant editor/workflows unless explicitly requested by the project owner.
- Mobile cart/order creation remains **product-only**.
- Multiple taps are allowed generally; only active network mutations may use deliberate single-flight/idempotency protection.
- Direct Sale remains available from product detail for authorized users.
- Deferred Direct Sale supports installments and final due date according to the existing server contract.
- Commission remains visible in catalog/product detail where already implemented.
- `Uredi artikal` remains the final admin action on product detail after Direct Sale.
- **Warranty expiry opt-in:** `warranty_expiry_alerts` defaults OFF and is independent of ordinary `warranty_updates`. Preserve strict immutable order-item/order ownership. The owner clarified the future expiry recipient policy on 2026-10-22: originating selling user plus active, individually opted-in SuperAdministrator, with no unrelated recipient or broadcast; see section 30. This narrowly scoped administrator exception is a pending implementation, not evidence that deployed owner-only code already supports it. Historical preference RED/GREEN: [39643629087](https://github.com/AldinAga/ald1n-project/actions/runs/39643629087), [39643826340](https://github.com/AldinAga/ald1n-project/actions/runs/39643826340). Do not activate global/bulk expiry delivery as part of preference wiring.

A batch that unexpectedly reintroduces Product Variants must fail.

---

## 3. CloudLinux Node/npm authority

Do not assume the shell's default `node`, `npm`, or `eas` binaries are the project authority.

Canonical Node binary:

```bash
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
```

Canonical npm CLI:

```bash
NPM_CLI=/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js
```

Use them explicitly:

```bash
"$NODE_BIN" "$NPM_CLI" run typecheck
```

Do not depend on an interactive shell profile, `nvm`, user aliases, or a globally installed `eas` command.

---

## 4. Expo dependency rule on this hosting

### Never use this for dependency repair/install

```bash
expo install --fix
```

On this CloudLinux/NodeJS Selector environment, Expo's internal npm child process can fail when a physical `node_modules` directory is involved.

Approved pattern:

1. Expo may be used for **compatibility checking** such as `expo install --check`.
2. Actual dependency installation/update is performed through the canonical Node/npm CLI path above.
3. Do not create/install `node_modules` merely to perform a release compatibility check.
4. Do not run broad dependency repair commands during unrelated bugfix batches.

---

## 5. EAS CLI authority — critical

Do **not** require or probe for a physical/global `eas` binary as a hard prerequisite.

Canonical pinned EAS CLI version for new repository operations (read-only GitHub Actions validated 2026-10-16):

```text
eas-cli@25.0.0
```

Upgrade from 24.8.0 to **25.0.0**: exact-source Node 22.16 read-only compatibility [CI 38862601089](https://github.com/AldinAga/ald1n-project/actions/runs/38862601089) PASS (CLI version and Update, republish, channel, project, build and submit help/flag contracts). Upstream [v25.0.0 release notes](https://github.com/expo/eas-cli/releases/tag/v25.0.0) list simulator-preview link sharing as the published breaking change. **Authenticated production exact-group read on hosting with CLI 25: PASS** (owner-executed Report683, 2026-10-16; source `6a02eec0d2a800de7db1996adfdc2fd0e69d0cac`, exact production group `37ae8af0-b950-4549-8476-c960e1350866`, no mutation). The production channel/latest GraphQL mapping was **not** rechecked by Report683 and remains a separate gate for every future EAS publish. Do not install globally, run builds/OTA or modify production as part of this upgrade. Future versions require their own isolated compatibility and fail-closed tests.

Approved invocation:

```bash
EAS_SPEC="eas-cli@25.0.0"
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas --version
```

All EAS operations must use this pinned npm-exec path unless the project owner explicitly changes the authority.

### Project-scoped EAS commands must run from the Mobile app root

This is mandatory:

```bash
cd /home/icaffeco/ald1n-project/apps/mobile/current
```

Then run project-scoped commands such as:

```bash
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas update:list ...
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas update:view ...
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas update ...
"$NODE_BIN" "$NPM_CLI" exec --yes --package "$EAS_SPEC" -- eas update:republish ...
```

Do not execute project-scoped EAS commands from the monorepo root.

Known historical failure prevented by this rule: `eas update:list` failed when launched from the monorepo root even though authentication worked.

### Command-specific flag policy for eas-cli@25.0.0

The flags are not uniform across EAS subcommands. Use the behavior proven on this hosting, not a blanket flag template.

- `eas update:view GROUP_ID --json` is the canonical exact-group read. **Do not add `--non-interactive`**: eas-cli 24.7.0 rejects that flag for `update:view`.
- CLI25 help confirms Update/list/channel flag availability, but collection queries are **not** standalone release authority: earlier collection reads failed even under CLI24.8.0. Use exact-group `update:view` and authenticated basic channel/latest GraphQL, and fail closed on ambiguity.
- `eas update ... --environment production --json --non-interactive` and `eas update:republish ... --json --non-interactive` were production-proven under **CLI24.8.0** in Report682. **CLI25 has only read-only compatibility PASS so far**; its first production mutation requires separate release gates and exact-group verification. Expo export warnings about `CI=1` do not prove success/failure; judge real RC and returned update JSON.
- Do not globally install a newer EAS CLI merely because the CLI prints an upgrade notice. Test a new major version through the same pinned npm-exec mechanism in a dedicated read-only compatibility audit first; only then change the canonical pin in this file.

---

### iOS profile credential safety (Batch684, 2026-10-16)

- Before any `npm run build:ios:preview` or `npm run build:ios:production`, execute the project-pinned **read-only** preflight `node scripts/ios-release-readiness.mjs --profile PROFILE` (now in the checked-in iOS build wrappers). No iOS build is authorized merely by a config-only PASS.
- **Batch685 profile split:** the tracked `GoogleService-Info.plist` is used **only** by iOS preview (`com.ald1n.mobile.preview`). iOS production (`com.ald1n.mobile`) and development (`com.ald1n.mobile.development`) must each supply a distinct real Firebase iOS plist through EAS File environment variable `ALD1N_IOS_GOOGLE_SERVICES_FILE` in that profile environment. Use secret/file visibility; never commit a production plist or repurpose preview. Local preflight requires an absolute file path outside the repository. `PASS_CONFIG_ONLY` does not prove remote EAS secret registration, Firebase Console identity or Apple signing. See `apps/mobile/current/docs/IOS-FIRST-BUILD-READINESS.md`.
- `PASS_CONFIG_ONLY` is not evidence of Apple Developer credentials, provisioning, TestFlight delivery, real device login, or notification entitlement. Keep Apple signing and device UAT as separately attested gates. Do not change Build25 Android runtime, native dependencies or production OTA to fix iOS credentials.
- **OWNER-DEFERRED (2026-10-16, until expressly resumed):** do NOT attempt or repeatedly request either (1) Firebase Console registration/download of original production iOS `GoogleService-Info.plist` for `com.ald1n.mobile` in `ald1nmobileapk`, or (2) Expo production EAS File/Secret upload as `ALD1N_IOS_GOOGLE_SERVICES_FILE`. These are intentionally postponed; when the owner later asks what to do, present these exact two instructions. Production iOS remains HOLD and no signing/TestFlight/submission bypass is permitted.
- **Batch686 independent next step:** a separate `ios-simulator` profile inherits **only** preview identity and tracked preview plist (`com.ald1n.mobile.preview`), EAS channel `preview`, `ios.simulator=true`, and `autoIncrement=false`. A simulator .app build needs no Apple Developer account. Validate exact GitHub source, Expo CLI25 identity, TypeScript/Release25/JDK17, immutable Android baseline, config-only iOS preflight and canonical operator receipt **before submitting one EAS cloud simulator build**. Simulator builds are not physical iPhone UAT or TestFlight.
- Liquid Glass remains the final project phase, after functional UAT and the first iOS build.

---

## 6. OTA / Build policy

Before deciding that a native build is required, compare native/runtime-critical files against the active native build source authority.

For JS/TS-only changes compatible with the current runtime:

```text
runtimeVersion = 1.0.0-build17
```

prefer **EAS Update OTA** instead of a new native build.

Do not create Build18 merely because source code changed.

A new native build is justified only when native/config/dependency/runtime compatibility requires it.

Do not submit to Google Play unless the requested task explicitly requires Play action.

### OTA release pattern

For SDK 57, use the production environment explicitly:

```bash
eas update ... --environment production --non-interactive
```

Recommended safe sequence:

1. Verify source/runtime/config authority.
2. Publish an isolated candidate update.
3. Capture the **candidate group ID directly from `eas update --json`** when possible.
4. Verify that exact group with `eas update:view GROUP_ID --json`.
5. Re-check production authority before promotion.
6. Promote the exact verified candidate with `eas update:republish --group ... --destination-channel production --platform android ...`.
7. Capture the production group from republish JSON.
8. Verify the exact production group with `eas update:view`.
9. Do not use collection/list queries as the only source of truth when an exact-group query is available.

If an EAS collection query is temporarily unavailable but exact-group authority works, do not destroy a valid candidate or republish blindly.

---

### Workspace V2 Build25 OTA release authority (2026-10-12)

- Android Build25: versionName 1.0.0, versionCode 25, runtimeVersion 1.0.0-build17, production channel, package com.ald1n.mobile.
- Workspace V2 source SHA: 52850d185d2c26d0a72a26d64520528714fe2f01. Candidate group: 8f27e122-317b-4db1-93a6-9bb95653de26 on workspace-v2-uat-20261012.
- Verified production republish group: 27dd4b5d-63b3-4902-9b0b-5f289076f0e6; Android update 01a12d9b-a56a-75e0-bd8d-be25ed4acb4a. Exact group re-read matched source SHA/runtime/production branch.
- Prior production group / rollback candidate: 3488c7cc-ac63-472d-bd7c-27e5abc677bd (Google Sign-In fix). Re-attest state before any rollback. No automatic rollback.
- Owner confirmed physical Android UAT in conversation; exact device matrix/telemetry not independently documented. Release technically closed, no new build, versionCode increase, or Play submit.
- Operations report: docs/operations/632-WORKSPACE-V2-BUILD25-PRODUCTION-OTA-CLOSURE-20261012.md.

## 7. State-aware recovery — never repeat successful work

Every recovery batch must determine what already succeeded before taking action.

At minimum inspect and record:

```text
SOURCE_MUTATION
COMMIT_CREATED
PUSH_COMPLETED
CANDIDATE_PUBLISHED
CANDIDATE_GROUP_ID
PRODUCTION_PROMOTED
PRODUCTION_GROUP_ID
FAILED_STAGE
```

Rules:

- If source was already committed and pushed, **do not patch/commit the same fix again**.
- If a candidate was already published and verified, reuse its exact group when safe.
- If production was already promoted and only post-validation failed, do not blindly republish or rollback.
- If a preflight failed before mutation, recovery starts from the unchanged source state.
- Bind the exact previous report SHA-256 before recovery so the recovery script proves which state it is continuing from.

Recovery must continue from the last verified state, not restart the whole batch by default.

---

## 8. Git preflight and direct-GitHub-write recovery

Before any source mutation:

```bash
git fetch origin main
```

Record:

```text
BRANCH
LOCAL_HEAD
REMOTE_HEAD
```

Do not immediately mutate when local and remote differ.

### Safe fast-forward policy

If local is behind remote:

1. Confirm local is an ancestor of remote.
2. Confirm local has no unpushed commits.
3. Inspect the exact remote-only commits/files.
4. Confirm the remote delta is expected and safe.
5. Then use:

```bash
git merge --ff-only origin/main
```

Never use a blind `git pull` in a batch.

This is especially important because documentation may be pushed directly through GitHub while the server checkout remains one commit behind.

### Known runtime drift

The following production `.htaccess` runtime drift is historically allowed only when both hashes match exactly:

```text
apps/cms/current/public/.htaccess
file SHA-256: d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
diff SHA-256: 8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
```

If either hash changes, treat it as new drift and stop for review.

Do not stage this known runtime drift unless explicitly requested.

---

## 9. Batch temporary files must stay outside the Git worktree

Do not place runtime scratch directories such as:

```text
.batch123-...
.batch159-...
```

inside `/home/icaffeco/ald1n-project`.

A previous checkpoint batch created its own temp directory inside the repo and then detected its own files as unexpected untracked contamination.

Use an external location, for example:

```bash
TMP_DIR="$HOME/.ald1n-batch${BATCH_NO}-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$TMP_DIR"
```

Locks used only by the batch should also live outside the repository unless they are intentionally tracked project files.

---

## 10. Shell portability rules for this hosting

Batch scripts must be executable through:

```bash
bash /home/icaffeco/ald1n-project/incoming/<script>.sh
```

Permanent restrictions:

- Do not use `python3` in operational batch scripts.
- Do not use `/dev/fd`.
- Do not use process substitution:

```bash
<(command)
>(command)
```

- Use plain ASCII shell command flags.
- Prefer portable temporary files/FIFOs over shell-specific descriptor tricks.

### `set -e`, `ERR`, and expected non-zero return codes

Do not design a batch where an expected `RC=1` can be converted into a fatal error by global `set -e` or an `ERR` trap.

Examples of commands where `1` can be expected/meaningful:

```text
grep    -> no matches
git grep -> no matches
git diff --quiet -> differences exist
```

Capture and classify the return code explicitly.

Recommended pattern:

```bash
set +e
command_here
rc=$?
set -e   # only if this batch intentionally uses errexit

case "$rc" in
  0) ... ;;
  1) ... expected semantic ... ;;
  *) fail ... ;;
esac
```

A simpler and often safer policy for these operational scripts is to avoid global `set -e` entirely and use explicit RC checks for critical commands.

### `set -u` and literal dollar signs

If `set -u` is active, escape literal `$` characters inside double-quoted fingerprints.

Wrong:

```bash
grep -Fq "'automation.'.$type" file.php
```

This expands shell variable `$type` and may abort the script.

Correct:

```bash
grep -Fq "'automation.'.\$type" file.php
```

Or use single quotes where practical.

---

## 11. Terminal logging contract

All operational batches must show detailed progress **live in the terminal** and save the same audit output to the report.

Do not hide important command output only inside temporary files.

Preferred logging helper:

```bash
log() {
  printf '%s\n' "$*" | tee -a "$REPORT"
}
```

For normal commands:

```bash
command 2>&1 | tee -a "$REPORT"
rc=${PIPESTATUS[0]}
```

Always preserve the real command return code rather than `tee`'s return code.

### JSON-producing commands

When stdout must remain valid JSON while also being visible live, do not corrupt stdout by adding prefixes or mixed stderr.

Use separate stdout/stderr capture and, where needed, named FIFOs + `tee` so:

- raw stdout JSON remains parseable,
- stderr remains separate,
- both streams are visible live,
- both are persisted in the report.

No process substitution and no `/dev/fd`.

---

## 12. Validator parsing rules

Never classify a batch as failed simply because the text `FAIL` appears somewhere in validator output.

The valid success summary contains:

```text
Ukupno FAIL: 0
```

Therefore this is forbidden as a success/failure parser:

```bash
grep -Fq 'FAIL' validator.log
```

Use explicit parser rules:

1. Require the exact zero-failure summary.
2. Detect only actual failure lines/structured markers.
3. Treat informational historical text containing `FAIL` as non-fatal.

Do the same for other tools whose success output can contain words such as `error`, `fail`, or `warning` in explanatory text.

---

## 13. Canonical quality gates

### CMS

Canonical full CMS static gate:

```bash
cd /home/icaffeco/ald1n-project/apps/cms/current
php bin/static-check.php
```

Current expected result:

```text
Ukupno: 983, neuspešno: 0
```

Do **not** replace this gate with:

```bash
php bin/php-lint.php
```

`php-lint.php` is not equivalent to the canonical static gate.

### Mobile TypeScript

```bash
cd /home/icaffeco/ald1n-project/apps/mobile/current
"$NODE_BIN" "$NPM_CLI" run typecheck
```

Expected: RC 0.

### Mobile validator

```bash
"$NODE_BIN" scripts/validate-project.mjs
```

Expected summary:

```text
Ukupno FAIL: 0
```

### OpenAPI parity

There are exactly three active tracked OpenAPI copies:

```text
apps/cms/current/docs/openapi.yaml
apps/mobile/current/docs/openapi.yaml
packages/api-contract/openapi.yaml
```

They must remain byte-identical unless an approved batch is intentionally changing the API contract and updates all three together.

---

## 14. TDD for bugfixes and behavior changes

For a behavioral bugfix:

1. Add/create the smallest behavioral regression test first.
2. Run it against the existing source.
3. Confirm it fails for the expected reason (**RED**).
4. Apply the minimal production fix.
5. Run the same test and confirm it passes (**GREEN**).
6. Run broader TypeScript/validator/CMS/OpenAPI/project guards.
7. Only then commit/push.

Do not call a test “TDD” if the test was first run only after the production change.

If the project has no convenient test runner for the isolated behavior, a dependency-free Node/PHP behavioral runner is acceptable when it directly imports/exercises real production code.

---

## 15. Source-diff scope before commit

Before commit, print and review:

```bash
git status --short
git diff -- <expected paths>
git diff --check
```

Then verify the changed-file set exactly matches the approved scope.

A batch that intended to change 3 Mobile files must not silently include:

- operation reports,
- `.htaccess`,
- backup files,
- credentials,
- unrelated formatting,
- generated caches,
- temporary scripts.

Stage explicit paths, not `git add -A`, unless the approved task is specifically a controlled checkpoint of known reports.

---

## 16. Commit/push policy

Before push:

1. Re-fetch remote.
2. Ensure remote did not move unexpectedly.
3. Ensure staged scope is exact.
4. Run `git diff --cached --check`.
5. Commit with a task-specific message.
6. Push `main`.
7. Fetch again.
8. Require `LOCAL_HEAD == REMOTE_HEAD` for the new commit.

If push succeeds but a later OTA/release step fails, recovery must preserve that commit and continue release work. Do not create a duplicate source commit.

---

## 17. Operation reports are state authority

Every executable batch must produce an operation report under:

```text
docs/operations/
```

Important state markers should be machine-readable, one per line where practical:

```text
BATCH_RESULT=
FAILED_STAGE=
SOURCE_MUTATION=
COMMIT_CREATED=
PUSH_COMPLETED=
SOURCE_COMMIT=
OTA_REQUIRED=
CANDIDATE_PUBLISHED=
CANDIDATE_GROUP_ID=
PRODUCTION_PROMOTED=
PRODUCTION_GROUP_ID=
BUILD_CREATED=
GOOGLE_PLAY_ACTION=
NEXT_ACTION=
```

For recovery batches, bind the exact previous report SHA-256 before doing work.

A report must be detailed enough that the next batch can safely determine the exact system state without guessing.

---

## 18. Default PASS / FAIL continuation rule

Project-owner preference:

- **FAIL report** -> diagnose exact root cause and create the targeted recovery batch immediately.
- **PASS report** -> certify it and create the next planned batch immediately.
- If the next step is manual/physical acceptance -> create an executable preflight/checklist batch rather than only prose.

Do not ask the project owner to restate this rule on every batch.

Do not create unnecessary recovery generations for known hosting behaviors that this file already documents.

---

## 19. Pre-flight checklist before every mutation batch

A new batch should answer these **before touching source**:

```text
[ ] Am I on branch main?
[ ] Did I fetch origin/main?
[ ] Are local/remote heads equal?
[ ] If not equal, did I prove a safe fast-forward rather than blindly pull?
[ ] Are there staged changes? If yes, stop unless they are explicitly expected.
[ ] Is the known .htaccess drift still exactly the known hash/diff?
[ ] Is my temp directory outside the Git worktree?
[ ] Am I using canonical Node/npm paths?
[ ] If EAS is needed, am I using npm exec eas-cli@25.0.0?
[ ] Will every project-scoped EAS command run from apps/mobile/current?
[ ] Does the script avoid python3, /dev/fd, and process substitution?
[ ] Can any expected RC=1 be accidentally trapped by set -e/ERR?
[ ] Can set -u expand any literal $ text in grep/regex fingerprints?
[ ] Does validator parsing explicitly accept "Ukupno FAIL: 0"?
[ ] Is the previous report SHA bound for recovery work?
[ ] Do I know whether source/commit/candidate/production actions already happened?
[ ] Is a new native build actually required, or is Build17 OTA compatible?
[ ] Is Google Play action explicitly requested? If not, do not perform it.
```

A batch should fail in preflight **before mutation** when one of these cannot be proven safely.

---

## 20. Pre-release checklist for Mobile OTA

Before candidate publish:

```text
[ ] TypeScript RC=0
[ ] Mobile validator: Ukupno FAIL: 0
[ ] CMS static: 983/983, 0 failed
[ ] Product Variants guard PASS
[ ] OpenAPI parity PASS when API surface is relevant
[ ] app name = Ald1n CMS
[ ] slug = ald1n-mobile
[ ] owner = ald1n
[ ] project ID = d43b3866-6838-4217-a23e-3dc7f2cc76cc
[ ] Android package = com.ald1n.mobile
[ ] runtime = 1.0.0-build17 for Build17-compatible OTA
[ ] production API = https://cms.ald1n.com/api/v1
[ ] source commit pushed and local/remote synchronized
[ ] EAS whoami succeeds through pinned npm-exec authority
```

After publish:

```text
[ ] Candidate group ID captured
[ ] Exact candidate update:view matches expected commit/runtime/platform
[ ] Production authority rechecked before promotion
[ ] Exact candidate promoted, not a guessed/latest group
[ ] Production group ID captured
[ ] Exact production update:view matches expected commit/runtime/platform
[ ] No unexpected native build
[ ] No unexpected Google Play action
```

---

## 21. Physical-device acceptance

For user-visible Mobile changes, use physical-device acceptance when the behavior cannot be fully certified by static/automated checks alone.

Typical OTA boot sequence:

1. Force-close app.
2. Open and allow OTA retrieval.
3. Wait for update/application initialization.
4. Force-close again.
5. Reopen.
6. Exercise the exact original failure scenario.

Acceptance should test the actual reported problem first, then a small regression smoke set.

Do not perform destructive production business mutations merely to prove UI controls exist. If no safe test record exists, record the limitation explicitly rather than modifying arbitrary live business data.

---

## 22. Backup rules

Before data-destructive or high-risk production operations, use the existing first-party backup flow where appropriate.

Canonical CMS manual backup:

```bash
cd /home/icaffeco/ald1n-project/apps/cms/current
php artisan app:backup-create --type=manual
```

Verify it:

```bash
php artisan app:backup-verify
```

Do not report a backup as restore-ready until verification passes.

Do not expose `.env`, Google credentials, tokens, passwords, private keys, or backup secrets in terminal/report output.

---

## 23. Cleanup safety

Never use an unrestricted destructive command such as:

```bash
git clean -fdx
```

for routine cleanup.

Inventory first. Delete only explicitly classified temporary/runtime junk.

Do not delete:

- `.env`,
- Google/Firebase credentials,
- uploads/product images,
- private business documents,
- active Laravel storage data,
- the current uncheckpointed operation report or an immediate predecessor report still required by an active recovery,
- valid verified backups,
- EAS credentials,
- unknown files merely because they are untracked.

Use quarantine/backup before deleting uncertain runtime artifacts.

---

## 24. Lessons already learned — do not rediscover via failed batches

These failures have already happened and are now encoded as permanent guards:

1. **Project EAS command from monorepo root** -> collection query failed. Run from Mobile root.
2. **Physical/global `eas` binary assumption** -> `EAS_CLI_MISSING`. Use pinned npm-exec `eas-cli@25.0.0`.
3. **Local repo one documentation commit behind GitHub** -> preflight mismatch. Verify ancestry/delta and `git merge --ff-only` when safe.
4. **Temp directory created inside repo** -> checkpoint detected its own temp files as contamination. Temp/lock outside worktree.
5. **Broad grep for `FAIL`** -> false failure on `Ukupno FAIL: 0`. Parse exact failure semantics.
6. **`git grep` no-match RC=1 under ERR/errexit** -> false fatal failure. Capture expected RC explicitly.
7. **`set -u` + literal `$type` inside double-quoted grep fingerprint** -> unbound shell variable abort. Escape literal dollars/use single quotes.
8. **Assuming a single OpenAPI location** -> topology failure. Active topology is exactly three tracked copies.
9. **Using `php bin/php-lint.php` as full CMS authority** -> wrong gate. Canonical full gate is `php bin/static-check.php`.
10. **Restarting recovery from scratch** -> risks duplicate commits/candidates/promotions. Recover from recorded state markers.
11. **`eas update:view ... --non-interactive` on eas-cli 24.7.0** -> hard CLI failure. Use exact-group `update:view GROUP_ID --json` without `--non-interactive`.
12. **Historical operations pointer mistaken for CURRENT authority** (Report636) -> Release regression tests must distinguish the leading live `000-LATEST.md` authority from explicitly marked old Build25 pre-OTA snapshots. Preserve historical evidence separately; never revert the current index to satisfy a stale old-header test.
13. **No JDK17 signing tools on the CloudLinux shell PATH** (Report636) -> Real `jar`/`keytool`/`jarsigner` regression stays HOLD until actual supported Java 17 tools are present, locally or in an approved isolated JDK17 CI runner. Do not silently mock, skip, globally install tools or label cryptographic verification PASS. Re-probe before assuming this environment finding remains true.
14. **Report641 process-substitution portability regression** -> A Bash loop reading from `<(git ls-files -z ...)` failed on CloudLinux as `/dev/fd/63: No such file or directory` BEFORE any backup verification. Despite the existing section 10 prohibition, the delivered script had not been checked for the forbidden construct. In all future `incoming/` scripts, write NUL-delimited command output to a private temporary file outside the repo, then `while IFS= read -r -d '' item; do ...; done < "$TMP/paths.bin"`. Run an automated pre-delivery forbidden-syntax scan and at least one isolated successful loop fixture. On failure, preserve original numbered report, archive it byte-exact to GitHub and advance to the next report number. Never classify unexecuted backup verification as PASS.

Any new repeated infrastructure failure should be added to this section after its root cause is proven.

---

## 25. Batch design principle

A good Ald1n batch is:

- bounded,
- state-aware,
- verbose,
- reversible before mutation,
- explicit about authority,
- conservative with production data,
- strict about changed-file scope,
- idempotent or recovery-safe,
- aware of this CloudLinux hosting environment,
- able to explain exactly what succeeded if a later stage fails.

The goal is not merely “eventually PASS.” The goal is that known environment constraints are checked **before** execution so a normal change does not require V2/V3/V4/V5 recoveries for already-understood reasons.

---

## 26. Owner-approved hosting hygiene and report numbering

These rules are permanent from **2026-09-19** and supersede older operation-report/backup retention behavior where they conflict.

### Terminal clear at the beginning of every Bash batch

Every executable operational Bash batch must clear the terminal before logging, preflight, or mutations. Immediately after the shebang/comments, use:

```bash
clear 2>/dev/null || printf '\033c'
```

A failed `clear` in a non-interactive shell must not abort the batch.

### Sequential operation-report number prefix

Every newly generated operation report must begin with a monotonically increasing numeric prefix followed by `-`.

Sequence authority when this rule was adopted:

```text
Last historical report number: 444
First report under this rule: 445
```

After 445, increment by exactly one for every new report (`446-...`, `447-...`, etc.). Recovery attempts consume their own next number; never reuse a previous report number. After Report447 the next report is Report448.

### Stable backup retention = exactly two

Hosting must retain exactly the two newest restore-ready stable backups after cleanup. Before deleting older backups:

1. Inventory the canonical Laravel `backup_runs` authority and canonical backup directory.
2. Verify candidate backups with `php artisan app:backup-verify`.
3. If fewer than two restore-ready backups exist, create and verify replacement manual backup(s) first.
4. Only after two verified backups are proven may older canonical backup rows/directories and known legacy release snapshots be removed.
5. Never delete either selected stable backup during the same cleanup.
6. Backup cleanup may mutate backup metadata only; it must not modify business records.

Do not retain 7 daily + 4 weekly backups merely because older default configuration allowed it. Owner policy is two stable backups total unless explicitly changed later. Every future housekeeping/release batch must verify the retention state and remove only newly accumulated superseded backups after proving the two keepers.

### Operation reports: GitHub is the historical archive, hosting is an active workspace

The production hosting checkout must not accumulate historical operation reports.

- Keep locally only the current report and, when required for state binding/recovery, the immediate predecessor report.
- Before removing a report not otherwise present on GitHub, checkpoint it to GitHub first.
- Historical reports may be removed from the hosting working tree after GitHub authority is proven.
- Large report-history cleanup must create a dedicated Git archive tag/commit before deletion so evidence remains recoverable.
- Never delete the current uncheckpointed report or a predecessor that an active recovery still binds by SHA-256.

### Contract smokes must not depend on disposable historical fixtures

Read-only contract and health smokes must validate current contracts and immutability, not require a historical test record to exist forever. Total Product Purge contract validation must not fail merely because historical Product ID 19 was legitimately removed later. A fixture may be inspected when present, but its absence is not itself a contract failure.

### Laravel -> Mobile feature parity is mandatory

This is a permanent project-owner rule from **2026-09-19**.

Every functional Laravel/CMS feature change must be carried through to the Mobile application where that capability is user-facing or consumed by Mobile. A backend feature is not considered fully closed merely because the Laravel implementation passes.

For every applicable Laravel feature change, the implementation/release chain must explicitly account for:

1. Laravel route/service/model/database behavior and permissions.
2. Canonical OpenAPI changes across exactly the three active copies when the API surface changes.
3. Mobile API client/types/query-key/validator parity in the same backend/API batch whenever Mobile consumes the contract.
4. Mobile UX/workspace parity in the same batch or in an explicitly named immediately-following batch that remains part of the same feature acceptance chain.
5. Regression guards proving that Product Variants remain decommissioned and existing permission/business authorities are reused instead of duplicated.

Do not create a parallel Mobile business rule to compensate for missing Laravel authority. Do not create a parallel Laravel endpoint tree when an existing canonical feature namespace can be extended safely. Infrastructure-only backend work that has no Mobile-visible or Mobile-consumed behavior may be documented as not applicable, but that exception must be explicit in the operation report.

A feature that intentionally splits backend/API work and Mobile UI work across consecutive batches remains **in progress** until the Mobile parity batch passes.

### Git scope authority uses machine-stable manifests

This is a permanent operational rule from **2026-09-19**, adopted after Reports451-454 demonstrated that human-readable Git status rendering is not a safe machine authority for exact scope decisions.

Do not parse `git status --short` to decide whether an allowlist passes. It may apply rename detection or collapse untracked directories and therefore render a valid file set differently from the semantic file set.

For automated scope authority use these exact sources:

1. Staged paths: `git diff --cached --name-only --no-renames` and, when status letters are required, `git diff --cached --name-status --no-renames`.
2. Tracked unstaged paths: `git diff --name-only --no-renames`.
3. Untracked paths: `git ls-files --others --exclude-standard`.
4. Normalize each list with stable sorting and compare it against an explicit expected manifest.
5. `git status --short` may still be printed for human diagnostics, but it must never be the pass/fail authority for exact path scope.

Do not broaden cleanup to compensate for a manifest mismatch. Fail before mutation unless every unexpected path is classified explicitly.

### Immutable operation-report whitespace evidence

Operation reports archive raw terminal evidence and must preserve their bytes. Do not rewrite or trim historical report lines merely to satisfy whitespace style checks.

Before every commit, still execute `git diff --cached --check`. The normal rule remains RC=0 for source, configuration, specifications, `AGENTS.md`, and ordinary documentation.

A non-zero result is permitted only for a dedicated evidence-only archival commit when all of the following are true:

1. The staged manifest is exact and every staged addition/modification is under `docs/operations/`; a deliberate rotation deletion under the same directory is also allowed.
2. The full `git diff --cached --check` output is captured in the current operation report.
3. `git diff --cached --check -- . ':(exclude)docs/operations/**'` returns RC=0.
4. No product source, configuration, spec, `AGENTS.md`, generated contract, or other non-report path is staged in that commit.
5. The report files are committed byte-for-byte; do not normalize historical terminal whitespace.

This exception is for immutable operation-report evidence only and must never be used to waive whitespace errors in product/source or authority documentation commits.

---

## 27. Updating this file

Update `AGENTS.md` whenever a newly proven hosting/release rule becomes permanent.

When updating:

1. Base the rule on verified evidence from an operation report or reproducible environment behavior.
2. State the failure pattern and the approved replacement pattern.
3. Avoid hardcoding transient OTA group IDs or one-time report filenames as permanent rules.
4. Keep stable runtime/tool/path authorities explicit.
5. Commit the documentation change separately when practical.

This file is intended to remain the first operational reference for future Ald1n CMS & Mobile work.

## 21. AAB27 signed binary 16KB page-size gate (2026-10-21)

- Play rejected Android v1.5.0 versionCode 27 (EAS UUID `e3bf78a2-0410-4fe5-a279-e0af5624ca0d`, source `deb98808afe2568ed06da1c684b9b51d60734b12`, runtime `1.5.0-native1`) for 16KB memory-page incompatibility. Do NOT advise bypass via 'Ipak nastavi' or reuploading unchanged AAB27. Build FINISHED means signed, not Play eligible.
- Read-only signed-artifact CI [39671783825](https://github.com/AldinAga/ald1n-project/actions/runs/39671783825) AAB SHA-256 `f684779aa472366c359f28ee95c60648ee6739259de752e59d0f2b0e4fed36bf`: 55 packaged 64-bit ELF libs checked. 54 have LOAD >= `0x4000`; one FAIL is `base/lib/arm64-v8a/libnativegauss.so` LOAD `0x1000`.
- Source is transitively `com.github.QWEA0:liquidglass:v2.0.0`, through `react-native-android-liquid-glass@0.2.1`. Independently verified newer JitPack `v2.0.11` AAR SHA-256 `8218f8075c3c447241f2a1e96d502fc48718c6d007e9e05347bb9669901f13a9`, arm64 `libnativegauss.so` LOAD `0x4000` in [CI 39672535294](https://github.com/AldinAga/ald1n-project/actions/runs/39672535294). Pin `rootProject.ext.liquidGlassVersion` in root Android Gradle through existing Expo config plugin before RN Fabric bridge Gradle evaluation, not just app module. Do not make an unneeded Expo SDK/RN major upgrade.
- Mandatory new AAB gate per [Android 16KB docs](https://developer.android.com/guide/practices/page-sizes): inspect all 64-bit native ELF `LOAD p_align>=0x4000` in actual signed AAB, check its bundle config for `PAGE_ALIGNMENT_16K`, generate APK with bundletool and run `zipalign -c -P 16 -v 4`, plus supported device 16KB physical/emulator smoke where available. Vendor AAR or source unit PASS alone is not Play compatibility.
- Native replacement requires a different Expo `runtimeVersion` (candidate `1.5.0-native2`, old AAB27 `1.5.0-native1`), fresh authenticated EAS remote code attestation and strictly higher new `versionCode`, preserved signing, post-build bundle verification and an **independently authorized one-time build**. No automatic Play upload/rollout/OTA, no silent CloudLinux production mutation.
- Source fix [PR82](https://github.com/AldinAga/ald1n-project/pull/82) is an isolated source PR until real compiler and binary checks pass. Do NOT claim new AAB produced from it without a confirmed final EAS UUID and artifact scan.

## 28. SDK 58 owner-deferred upgrade sequencing (2026-10-21)

- Expo SDK 58 is officially stable (2026-10-15); it targets RN 0.88 / React 19.3, Android compileSdk 37 and Node 22.13+ per https://expo.dev/changelog/sdk-58 and https://docs.expo.dev/versions/v58.0.0/ . This is **readiness context, not authorization** for dependency or native release changes.
- Owner requested **prepare for SDK 58 only AFTER current planned tasks**. First resolve the actual CloudLinux Laravel deployment, verify the Android 16 KB signed AAB fix / Google Play artifact state, and reconstruct the latest approved mobile source; do not assume the current `main` mobile v1.0.0 config represents the separately built Android v1.5 release branch.
- Owner **reported manual Google Play publication**; do not call this independently verified and do not treat publication as evidence that AAB27's known 4 KB `libnativegauss.so` problem was fixed. Preserve section 21 signed-binary release gate.
- Use an isolated exact-source SDK58 branch with the existing Node22/npm10, lockfile/validator, JDK17 and no-`expo install --fix` CloudLinux rules. Review RN0.88 Strict TypeScript, Expo Router navigation and native dependency compatibility; reserve a new runtimeVersion and new signed AAB for a separately authorized build. See `apps/mobile/current/docs/SDK58-UPGRADE-READINESS.md`.


## 29. UI794 delivery and browser regression guard (2026-10-22)

- Reproduce whole-document horizontal overflow with actual scrollbar/clientWidth geometry before changing CSS. Dashboard viewport paint belongs on body; a centered 100vw pseudo-element was proven to overflow when a classic vertical scrollbar is present. Do not hide document overflow as a substitute for a root-cause fix.
- Test delivered operator bytes under the exact advertised canonical basename, plus wrong-name and tamper negative cases, before delivery. File rename does not change the content hash; distinguish path validation from byte-integrity checks.
- Browser tests must follow approved UI semantics while retaining security coverage. Moving a retirement preview link to a separate danger zone requires new geometry checks, not removal of permission/confirmation/no-submit tests. Real public checkout is a separate business/security scope, not an incidental UI route-unlocking change.

## 30. Owner clarification: operator orders, payment warranties and recipient isolation (2026-10-22)

Read `docs/operations/000-LATEST.md` and its current progress/requirements report before every new task. Report795 closed all six prior UI checks; do not repeat successful deployment/acceptance batches. The current expanded scope ledger is 7/17 closed milestones (41.2%); CMS-only is 7/15 (46.7%); the original six UI tasks are 6/6 (100%). These are dated, equal-weight acceptance counts, NOT effort/time or total historical-project percentages. Recompute from evidence using the same explicit ledger; version any scope change.

- SuperAdministrator has an operational customer/user order context, never a personal shopping/"My orders" flow. Keep real users' scoped orders and direct sales with the real buyer and recording administrator. Remove role-inappropriate UI and server entrypoints, not historical orders, commissions or ownership data.
- Public catalog target is `https://cms.ald1n.com/katalog`; current internal `/catalog` remains authenticated. The full-retail guest storefront/cart/checkout/protected follow-up is still NOT implemented/activated by this documentation change. Never expose internal prices/commissions or grant a guest administrator identity.
- Liquid Glass animation must be shared, subtle and readable: surface highlight on interaction, no moving/dimmed text, perpetual whole-page shimmer or pointer interception. Honor keyboard focus, touch, reduced-motion/transparency and high-contrast fallbacks.
- A confirmed incoming payment must automatically issue eligible warranties, including direct sales, without waiting solely for delivery/completion. No duplicate certificate on retries, no issuance from an unverified upload/refund/zero payment, no invented duration or silent historical backfill. Owner resolved issuance timing on 2026-10-22: issue after the FIRST verified positive incoming payment, including a deposit or first installment and SuperAdministrator direct sales; full settlement is NOT required. Do not ask this again. Issuance date and contractual validity start remain distinct: preserve delivery-based validity and test a truthful pre-delivery/pending-activation representation rather than silently starting the clock at a deposit. This payment behavior is NOT implemented by B796.
- Expiry notifications default OFF per recipient and remain independent from ordinary warranty updates. Preserve existing explicit opt-ins. The allowed recipient set is the originating selling user proven through matching order-item/order/user lineage PLUS an active SuperAdministrator with their OWN opt-in. This is the owner's new, narrowly scoped extension of the previous strict-owner-only expiry rule; current deployed code has NOT yet been changed to implement it. No unrelated user/admin/assignee or global broadcast. Validate lineage, role URLs, channels and per-recipient dedupe again at queued dispatch; one recipient's toggle never enables another's. Direct sales without a selling user notify only eligible opted-in SuperAdministrators.
- Add recorded shipment number, courier and safe tracking action to the first buyer/contact/delivery panel. Reuse canonical shipment data, show truthful empty states and do not fabricate a carrier URL.
- Persist report evidence and requirements on GitHub and the canonical Drive runbook/plan. Keep application implementation, tests, production activation, owner acceptance and independent PHP-FPM attestation distinct. New requirements are PENDING until actually implemented and verified. No extra CloudLinux diagnostic script merely to repeat completed acceptance.


## 31. B796 operator, optical-layer and test-evidence guard (2026-10-22)

- PR88 merged source `6f81f2bcfca1559dae92c39be323a1c827c31239` implements Report796 R1/R3/R6. Production B796 PASS is owner-attested in Report798 (10 exact writes, runtime closure 683, static 983/983, maintenance released, Scheduler resumed, HTTP endpoints 200, exit0); accidental second Report799 is PASS_ALREADY_APPLIED NO_MUTATION (CHANGED=0, exit0). Both raw reports are privately archived on the owner's Drive, linked from 000-LATEST. DO NOT rerun this script or manufacture a rollback. Host activation is distinct from missing independent PHP-FPM/OPcache provenance and pending owner visual acceptance. Canonical operator basename `ald1n-b796-laravel-operator-workspace-v1.sh`; bind both original receipts, 683 runtime paths, ten allowed writes. No migration, payment/warranty activation or public checkout in this slice.
- Do not let Gate administrator bypass or wildcard permission projection resurrect the SuperAdministrator's personal purchasing. Preserve ordinary-buyer cart and operational direct-sale coverage. Do not alter historical owner/financial records to remove a UI concept.
- A generic CSS child rule must exclude vendor `.lg-layer`/`.lg-sheen` from `position:relative`; they must remain absolute. Test initialized optical layers, not just static fixture markup. Surface reflection must respect reduced motion/transparency/forced colors.
- Validate tracking URLs against browser-normalized short/octal/hex local IP forms as well as scheme/credentials/control characters. Use the existing canonical carrier snapshot, never a guessed destination.
- Record both full-suite baseline debt and scoped green evidence: B796 has the same 42 generic-suite problems before/after, not a globally green suite; its 70-test final scoped regression and all eight applicable PR workflows passed. Preserve named failure evidence; do not delete tests to obtain PASS.
- Owner's first-payment decision above is authoritative; R4/R5 remain separate pending work. Keep current 17-milestone denominator: 7/17 closed (41.2%), CMS7/15 (46.7%), originalUI6/6 (100%); merged source alone does not close M08/M09/M12.

- Report798 and Report799 are the authoritative B796 host receipts. Check both before any subsequent Laravel batch. Report798 SHA256 `2b2536a7852000db202317b02f2878e75b985cf3e8a24d12ef1a0dbc46cf798d`; Report799 SHA256 `f84e9b0ff37d2eee25939e6798b6c283ecfb74ae94ed0e60e0ac472d628dc25b`. A second invocation against an identical target must remain an idempotent no-op rather than repeat maintenance/source writes. The declared CLOSED ledger remains 7/17 (41.2%) pending owner acceptance of M08/M09/M12. R4/R5 first-positive-payment warranty and recipient isolation, then R13-R15 public full-price storefront, remain next; do not activate any without distinct gates.


## 32. PDF completeness and prospective warranty terms (B797, 2026-10-23)

- PR90 source126dd53eed17796733bc2a8b4cd7009d3a90ae41 fixes PDF layout/terms only; Report800 records evidence. Production B797 remains PENDING. B796 was already applied in Report798 and its accidental second invocation799 was an idempotent no-op. No replay of completed batches/migrations.
- Never draw user/product/SKU/serial/terms strings without measured width and vertical page budget. Preserve paragraphs and split unbroken tokens; dynamic row/card height must support page breaks and repeated headers. Do not truncate legal terms, notes, line items or management segments to a fixed count. Preserve original logo aspect and QR clear area. Test all PDF families with normal, extreme, empty and multi-page synthetic data, extract full end-marker content and inspect every rendered page. No actual customer data or standalone font redistribution in fixtures/delivery.
- Default warranty conditions apply to new blank rule/form/certificate snapshots only; retain existing custom/issued terms, blank historic snapshots and dates. Do not mass backfill legal restrictions or assume a newly generated document can change an already-agreed contract. A ten-day commercial field cannot curtail consumer statutory rights; cause-based physical-damage exclusions must not defeat unrelated defects or complaint intake. Actual issuer/contact/procedure data and merchant legal review are separate from technical PDF correctness. Never promise liability immunity.
- Canonical delivery is incoming/ald1n-b797-laravel-pdf-warranty-terms-v1.sh with automatic integrity,686-path closure/13 explicit runtime writes and bound original Reports798/799. No schema migration, payment-trigger/expiry activation, backup-create-prune, historical rewrite or Mobile/build/OTA/Play. Source snapshot and owned maintenance/Scheduler recovery remain mandatory; actual host receipt is distinct from sourceQA.
- All8 final PR workflows and40scopedtests/337assertions PASS; static983/983;20PDF/91pages/7215spans;9deliverygroups. Whole suite retains42 inherited problems (baseline43), zero new identities: do not report wholly green tests or discard named failures. Author review is not independent review. CI bootstrap's test stages passed but final token push was rejected for adding a workflow; preserve already-created tested commit and use authorized SHA-guarded ref rather than rerun work or elevate token privileges.
- First confirmed positive incoming payment remains resolved owner policy, including deposit/first installment/direct sale, NOT paid-in-full. R4/R5 is next and NOT implemented by B797. Maintain delivery-based validity, immutable lineage and independent default-OFF expiry opt-ins. Public storefront and native releases remain separately gated. Preserve17milestone denominator; track this additional PDF correction separately until a documented scope revision.

## 33. Batch798: first positive payment and isolated expiry (2026-10-23)

- PR91 merged exact CMS source `c4a5f060cc00b5d1be0e4d8068309524a4b99c99` for R4/R5 with 9/9 relevant CI checks PASS. Scoped PHP8.4/MariaDB10.11.19 regressions 24 tests/180 assertions; static983/983. This is **SOURCE_QA_PASS, HOST_PENDING** until owner supplies numbered receipt. See `docs/operations/803-BATCH798-SOURCE-DELIVERY-CERTIFICATION-20261023.md`. Do not confuse source merge with CloudLinux deployment.
- Owner's FIRST genuinely verified positive incoming payment (deposit/first installment/direct sale) requires one numbered warranty issuance per eligible order-item, not paid-in-full. An undelivered item gets **issued_pending_delivery** with frozen conditions and explicitly no start/end date; after verified physical delivery the same number activates in `product_warranties` with actual delivered start. Never fabricate date, backfill mass history, restart a warranty clock or issue from unverified evidence/refund/zero. Separate additive `warranty_issuances` ledger must be migrated only after two verified current backups, bounded MariaDB metadata lock, maintenance and worker quiescence; preserve historical rows.
- Expiry R5 sends only to immutable origin seller and independently opted-in active SuperAdministrator recipients, not all admins or arbitrary email/assignee. Default OFF remains, previous explicit consent preserved; separate per-recipient dedupe windows and channel/opt-out rechecks before queued push dispatch. Each user controls only own switch. Legacy source tests that asserted the old owner-only behavior must be updated with actual new privacy tests, not bypassed. MariaDB FK mismatch negative tests must use valid alternate orders, not invalid IDs that the DB rightly rejects.
- CloudLinux Batch798 canonical `incoming/ald1n-b798-laravel-first-payment-warranty-expiry-v1.sh`, script SHA256 `0cd2a59b548b5fcb64ba129382a313ebf8fe2494234dd0ebc56702adb0189b89`, pinned target `c4a5f060cc00b5d1be0e4d8068309524a4b99c99`; **690 runtime paths / 17 approved source writes / exactly one additive new table**, first-party two-backup check + identity recheck, bounded migration, source-only rollback. Git object retrieval uses read-only `git show` of the pinned target after fresh fetch, full blob verification, no checkout/pull/reset/clean, keep index HEAD and .htaccess protected. Local original Report801 required SHA256 `e4c6cb7210f1f12263664fb52e9e3e4a8b304b3017bc90f915c0837aeacec53f`. Next operator action is a **single** SSH invocation then return complete host report. Do not request separate SHA commands, UI acceptance repeats or legacy batch replays.
- User-facing instruction: do not narrate multi-stage engineering plans; perform research/CI/source/docs/review automatically and provide **only the necessary tested SSH script/command and ask for its report**. No unsupported claim of production success.

## 34. B798 owner receipt and next public release boundary (2026-10-23)

- Original host Report804 SHA256 dcdba7223eed93b7897d8868d15630b37ef3c1521ed99d7bb2425a5ed37c97ab proves BATCH798_FINAL=PASS, BATCH798_EXIT_RC=0. This supersedes the B798 host-PENDING dated section33 and Report803. See docs/operations/805-BATCH798-PRODUCTION-CERTIFICATION-20261023.md. Do not rerun completed 798 or earlier. Canonical raw receipt remains on owner host; only redacted summary on GitHub/Drive.
- 690-path closure, 17 verified source writes, one new empty issuance table, 983/983 static, PHP8.4.26, existing restore-ready backups164/163, owned maintenance/scheduler release and preserved Git HEAD/index/.htaccess. No historical money or customer writes; default expiry opt-in stays OFF. Host source proof is NOT independent PHP-FPM source attestation and does not claim production customer purchase/notification UAT. Fixed progress ledger remains 7/17.
- After R4/R5, public retail release M13-M15 must be complete before activation: /katalog public products, /korpa session cart, /porucivanje full configured retail guest checkout, revocable time-scoped private order follow-up. No fake privileged guest, commission/cost leaks, guessed merchant policies, unverified payment, or numeric-ID order authorization. Preserve internal authenticated /catalog and payment/stock/idempotency constraints. Require isolated MariaDB+browser QA and gated host activation. Android16KB signed artifact M16, then SDK58 M17, are separate.
- Owner messages: no plan narration. Provide only an actually required tested SSH command and request numbered report; autonomously handle GitHub, CI and permissible Drive docs.

## 35. B799 staged public storefront source (2026-10-23)

- Current source branch: `feat/b799-public-retail-catalog-cart-checkout` at exact `4be2ff7e568f26f34ca8d59ba3d268a5a80a29bf`; [PR93](https://github.com/AldinAga/ald1n-project/pull/93) is DRAFT, NOT merged or deployed. B799 M13 public strict-view-model products, priced at full configured RSD/EUR retail; category/brand/stock filters and public-only media path checks. M14 session cart reprices server-side on reads, never reserves stock. Public feature flag defaults OFF, internal RBAC/checkout unchanged.
- Actual isolated source CI [39925140339](https://github.com/AldinAga/ald1n-project/actions/runs/39925140339): MariaDB10.11.19/PHP8.4 9 tests/66 assertions, 983/983 static PASS. Evidence [Report806](docs/operations/806-BATCH799-PUBLIC-STOREFRONT-M13-M14-SOURCE-QA-20261023.md) plus private owner's Google Drive. This is not a production receipt.
- Complete M15 in the *same branch* before considering merge or CloudLinux. No guest account with fake privileges, no posted prices, no commission or purchase cost leak, no unsecured order number-based access; checkout must atomically reserve actual stock and use the operational order/shipping/payment lifecycle. Protect guest status, docs and tracking with high-entropy expiring/revocable per-order token. No guessed shipping costs, taxes, statutory terms or merchant configuration; incomplete readiness is a release HOLD, not an implicit zero-fee agreement. Test real MariaDB concurrency/idempotency, guest cross-order denial, revoke/expiry, CSRF/throttle, document privacy, optional cancellation and no-payments-without-proof. Do not activate partial checkout.
- Never rerun B798/earlier or assume source main matches host dirty checkout. No user SSH command for this source-only candidate. Final activation requires distinct exact-source batch with bounded source scope, owned maintenance/scheduler, current verified backups, preserved host index/HEAD/.htaccess and original Report804. Prohibit real customer test orders/emails in production QA. CLOSED ledger remains unchanged.

## 36. Batch800: COD verified payment must atomically issue warranty; guest private warranty delivery (2026-10-24)

- Report809 is the current source QA and RED/GREEN authority for existing draft PR93 exact source `e453f91a1dcbd0f038305e1c9a417165f158dbea`. Source PASS `14/14` exact-head CI; MariaDB10.11.19/PHP8.4 retail `31 tests / 378 assertions`, warranty `25 / 195`, PDF `42 / 353`, static `983/983`. This is **not production or merchant acceptance**.
- **Permanent first-positive-payment invariant:** In `OrderWorkflowService::complete()` an administrator's verified COD receipt is a real positive payment and MUST call `WarrantyIssuanceService::issueForVerifiedPaymentLocked()` **inside the same transaction** before committing the receipt; otherwise a COD payment commits without its unique numbered immutable R4 issuance (proven RED from CI job120799791092). Delivery activates the same number, conditions and delivered-at coverage without issuing again. Repeat completion never duplicates receipt/issuance/warranty. Bank transfer remains unpaid and cannot complete until independently verified in financial ledger; first verified incoming transfer issues pending without fictional start/end until delivery. Maintain these regressions for normal authenticated and anonymous retail orders.
- **Permanent guest document/privacy invariant:** A public guest's 256-bit revocable/expiring order capability may show only that order's active/pending, non-void numbered warranties and PDF, with matching order ID, order-item ID, activated warranty ID and certificate number. Do NOT expose internal `/warranties` permissions or unguarded IDs, and enforce no-store/no-referrer. Reuse canonical `TrackingUrl::safe()` **and HTTPS-only** on public courier tracking links; reject internal IP, numeric/octal encodings, localhost, userinfo and HTTP.
- Approved initial **read-only host provenance batch source**: `tools/operator-source/ald1n-b800-host-readonly-release-audit-v1.sh`, pinned immutable GitHub commit `9961450962578ba9b3af80924a9c39456dc7abb2`, SHA256 `dc89cc5413c66ef690044ee1b22c8491f00f7537f7cd27a7480c5ef17eb3d604`. Always install it inside canonical `incoming/` via one checksum-self-validating command. Script writes only one sequential operation report and preserves dirty Git index with `GIT_OPTIONAL_LOCKS=0`. A read-only check does NOT independently prove PHP-FPM/OPcache, legal terms, restore-ready backups, email delivery or actual live customer UAT, and must NEVER enable the storefront.
- Hold PR93 DRAFT/source-only until owner merchant/legal/shipping/payment policy, host dirty-state and true restoration gates, authenticated release acceptance and separate controlled source deployment. Never replay B798 or earlier. User receives only the essential single SSH command and numbered report handoff, not multi-stage narration.


## 37. B801: owner Report810 host authority and explicit merchant-policy approval guard (2026-10-24)

- Original owner [Report810](docs/operations/810-B800-HOST-READONLY-PREFLIGHT-20261024-064254.md) is **B800_READONLY_PREFLIGHT=PASS**, immutable SHA256 `5213acf1acdcf7e4ce4b32499294908624adb1ece2cb4cef9782874adb5b8029`, actual host PHP CLI 8.4.27, MariaDB 10.11.19, Git HEAD `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`, **84 tracked dirty paths**, protected public .htaccess SHA256 `d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef`. Two B801 public tables absent; warranty_issuances present; storefront code absent and flags OFF. Merchant company identity is SET, but legal/privacy URLs, shipping, public payment methods and support NOT CONFIGURED; actual content approval, two current restore-ready backups, independent PHP-FPM/OPcache and customer/mail UAT NOT proven. Host Report810 made **zero source/DB writes**, not a production acceptance or deployment gate. Do not ask to rerun the audit or invent different host facts.
- B801 source-only development continues in **existing draft PR93**, not a replacement branch. Merchant policy requires an explicitly edited set of real merchant identity, HTTPS terms/privacy URLs, shipping description, **explicitly approved ZERO fee only** while the existing order ledger cannot separately charge delivery, real supported COD/active-bank-transfer selections and a verified support mailbox. Do not guess/legal-author these fields, infer free delivery, or silently enable flags from old PDF identity. SuperAdmin may save a DRAFT and must separately affirm the exact snapshot; no old signature can authorize changed policy, bank activation, seller role/status or merchant document identity.
- **Permanent same-term consent rule:** The user GET checkout must show and embed the current SHA256 approved merchant fingerprint, POST must match it again server-side before any order/stock commit, and each new order's immutable `public_checkout_attempts` evidence must snapshot its exact approved policy digest. An altered/stale checkout must 422 without partial order, inventory or idempotency writes. Guest privacy, secure receipt token, statutory rights, no fake buyer and default-OFF ENV release flags remain.
- **Transaction cache lesson (RED/GREEN B801):** `SettingsService::putMany()` invalidates the shared 10-minute settings cache inside a nested/outer DB transaction. A subsequent readiness read may cache uncommitted draft policy values; on validation exception and outer rollback these stale phantom values survive. Approved pattern: wrap the outer `DB::transaction(...)` in `try/finally` and `SettingsService::forgetCache()` AFTER the outer transaction resolves, on **both commit and rollback**. Prove invalid approval never leaves partial stored policy rows or cached approval; maintain strict SuperAdmin RBAC regression.
- **Production release is STILL HOLD after B801 code/CI:** Before merging/deploying, reconcile source, original owner Report804/810, current dirty host, true ready/restorable backup evidence, legal/payment/shipping approval and controlled CloudLinux migration/release gate. If host action is actually required, supply one preverified immutable `incoming/` script, one SSH invocation and request its numbered `docs/operations` report; do not silently flip public flags or perform EAS/OTA/Play. No owner involvement merely for development CI.

## B809 owner override — PUBLIC PURCHASE REQUIRES REGISTERED KUPAC (2026-10-24)

**This supersedes every earlier B799–B808 source, runbook or acceptance line authorizing anonymous/guest ordering.** No person may CREATE/POST a new public sale without registration and successful authentication as an active non-retired Kupac/customer account. Anonymous visitors may browse public products and maintain a tentative cart, but checkout GET must redirect to login and POST must not create any attempt, order, payment, document, commission or stock movement; for JSON requests return HTTP401. Signed-in internal operators (SubAgent/user, Admin, SuperAdmin) remain HTTP403 while storefront ON and must use existing internal ordering flows. Storefront OFF remains HTTP404 for everybody.

Create self-service Kupac registration with pending status, no internal permissions/group, and one-time e-mail activation before password/login; never accept client-supplied role/status. Registration should not disclose existing email status or leak activation tokens via untrusted Host headers. A new order MUST persist authenticated customer user_id and use that customer's confirmed registered email for receipt; form tampering cannot redirect bearer receipt links to a third-party mailbox. Enforce identity both controller and transactional domain service, including nonce replay. Existing historical receipt links may remain read-only for backwards compatibility; never issue a NEW anonymous capability.

B809 source implementation currently in DRAFT PR93 SHA 01c07c6738fd8aa91d541ffcae238d000d3c3529, **QA=HOLD**: historical guest-positive tests and disposable browser fixtures still expect anonymous checkout, and attempted bulk test adaptation was blocked by provider safety checks. Do not bypass that denial via alternate packaging, SSH, connector or path. Do not merge, deploy, enable storefront or claim acceptance until independent adapted tests, all exact-head workflows, payment, warranty, concurrency and role tests PASS, and the full security/merchant/restore/FPM gates finish. No owner SSH action for this source-only stage. Accepted milestones remain 7/17=41.2% program and 7/15=46.7% CMS.

## Permanent B810 registration sender rule — 2026-10-24

- For self-service Kupac/customer signup and email activation, ONLY a SuperAdministrator may independently choose and attest the outbound `From` email and sender display name, inside Admin > Settings > Javna prodavnica > "Email za potvrdu registracije Kupca". This form is separate from merchant terms and the global storefront switch, and must not silently revoke merchant acceptance or flip public flags.
- New public registrations require a syntactically valid, SuperAdmin-attested sender identity whose exact email/name SHA256 fingerprint still matches settings and whose attesting SuperAdmin remains active/non-retired. Absent, corrupt, unsafe, inactive or production non-deliverable `log/array` transport: refuse new registrations BEFORE creating pending accounts or activation tokens. Never fall back to an unapproved sender.
- Only activation notifications generated from PUBLIC customer self-registration use the selected From header; employee-admin invitations, order confirmations, SMTP auth and transport keep their previous settings. No user-submitted sender headers; block CRLF/control injection; use trusted app.url for activation URLs, never untrusted request Host. New customers remain `pending` and cannot order until one-time email activation and actual login.
- A From header is NOT evidence the SMTP provider accepts that mailbox. Check authorized provider identity, SPF/DKIM/DMARC and actual received email before production ON. Do not store SMTP secrets on this screen or send real tests from CI. Preserve the B809 registered-only checkout owner override over B799-B808 guest assumptions.
- B810 scoped code/test source `60de67cd83c0e86ed014588c0517986ba011cb16`, evidence Report820 6 PHPUnit tests/76 assertions PASS, but OLD B809 guest CI scripts still fail. Full CI/release **HOLD**; the previous provider safety-denied legacy fixture adaptation is NOT to be reattempted or routed around. No deploy, SSH, mail, mobile build, public ON or inode cleanup. Accepted program 7/17=41.2%, CMS 7/15=46.7%.

**B810 exact-source QA update:** c8730eaf90ee0dcbdb86185d3269f5808fd1b0eb; integrated B809 6/84 + B810 6/76 = **12 tests / 160 assertions PASS** from immutable synthetic MariaDB JUnit. Previous B799–B808 anonymous checkout fixtures were NOT modified or bypassed; release remains HOLD. See [Report820](docs/operations/820-BATCH810-SUPERADMIN-KUPAC-REGISTRATION-MAIL-SENDER-20261024.md) and archived CI proof.

**B810 final CI gate:** source c8730eaf90ee0dcbdb86185d3269f5808fd1b0eb has exact-head 9/14 workflows green, five FAIL (older guest assumptions), full suite baseline451/44 => candidate495/90, 48 newly failing old test identities, none B809/B810. Scoped registered-only+sender tests remain 12/160 PASS. Global CI / release HOLD, no skip. [Evidence](https://drive.google.com/file/d/1ebDgoUQ-LorBlEyNbJOxDJjZTKymKDfH/view).


## B815 tracked-session security and denied test-fixture correction — 2026-10-24

- Draft PR93 source `b392583714b522bab6fa1fc4af042a9510787f92` added session credential proof scoped to authenticated user; `PortalSessionService::validateAndTouch` refreshes the current DB password hash/status, rejects expired/rotated hashes, fails closed when the session registry is missing, and no longer grants protected access after tracking exceptions. Public checkout enforces the same session gate **only after** the storefront OFF/404 check. These are source-only changes, NOT production acceptance.
- Targeted B815 MariaDB session test `PortalSessionCredentialBoundaryB815Test.php` is independently tested and is distinct from `BuyerCheckoutSessionBoundaryB815Test.php`.
- A proposed correction to the newly added `apps/cms/current/tests/Feature/BuyerCheckoutSessionBoundaryB815Test.php` was DENIED by provider safety checks. The existing file calls an unsupported `SettingsService::put` method in its storefront-OFF case. **Do not retry or route around this rejected edit via a new script, connector, GitHub path, patch, CI exclusion, deleted test, alternate fixture or archive.** Preserve the failing test as an explicit release HOLD until a permitted, independently reviewed resolution exists.
- Earlier denied B805 archive and B809 legacy guest-positive test rewrites remain denied. Never skip old tests or claim scoped PASS equals global approval. Any future fixes require full exact-head CI, merchant, SMTP, WSTG/ASVS, backups, UAT and rollout gates; storefront production B798 stays OFF. No owner SSH required for the B815 source-only stage.

## B859–B861 confirmed full-scope delta and Android native source gate — 2026-10-25

- New [Report849](docs/operations/849-BATCH859-861-M16-COMPOSER-FULL-REGRESSION-20261025.md) supersedes stale in-progress CI statuses in Reports847–848. PR82 exact head `6d11b766131ca3cfa946a984b1cc9ed834c00239`: [40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557) **fully SUCCESS** for disposable Kotlin/Fabric, all native ELF 16KiB/ZIP, bundleDebug, embedded-JS debug signer and Android15 16KiB offline emulator. **NOT a signed production EAS AAB, physical-device UAT, Play acceptance, or M16 milestone acceptance.** Do not rerun unchanged expensive native compiler batches.
- Draft PR100 exact head `cf225ba8556270df8c44577070d1f6478b8ac69d`: [B859 CI 40201308807](https://github.com/AldinAga/ald1n-project/actions/runs/40201308807) **PASS only for dependency delta**, full 574 PHP case identities before Composer lock patch and 574 after, **100 failing identities before -> 98 after, ZERO new failures and TWO fixed**, parser 13/13 PASS. This does not imply the full suite is green or the source/CloudLinux production was patched.
- [Actual B803 CI 40194344679](https://github.com/AldinAga/ald1n-project/actions/runs/40194344679) still yields **56 new failing identities** between 451-case runtime baseline and 574-case candidate. Only objective symptom counts are proven: 40 HTTP302-vs200, 9 HTTP403-vs200, 3 HTTP404-vs200, 3 ValidationExceptions, 1 B815 unsupported fixture method. The blocked B815 correction must **not** be retried, routed around, dropped, or excluded; same for earlier B805/B809 safety-denied fixture edits. Never convert these observations into assumed waivers.
- Source-only Composer advisory patch on Draft PR100 yielded **0 advisories**, 41/398 targeted test PASS but remains unmerged/un-deployed. B844 368 OWASP item statuses remain UNASSESSED/NOT_RUN. Global CMS release HOLD, public storefront OFF; M16 signed EAS/physical/Play HOLD, SDK58 deferred. Owner functional acceptance remains **7/17=41.2%**. Google Drive evidence links in Report849.

## B862–B865 OWASP full requirement-text integrity and assessment-queue contract — 2026-10-25

- [Report850](docs/operations/850-BATCH862-865-OWASP-FULL-REQUIREMENT-REVIEW-QUEUE-20261025.md) fixes an important *scope gap* in B844: the original 253 ASVS records incorrectly duplicated chapter names in `title` instead of retaining each distinct official `req_description`. Never claim a per-item ASVS assessment based on chapter headings alone. The new Draft PR100 source `d8142bb61edd0dd5737f065a1b1c53e5782bf942` preserves verbatim requirement text, chapter/section metadata and 115 WSTG checklist items under unchanged pinned official Git blob hashes.
- [B844/B862 exact-head CI #40203101606](https://github.com/AldinAga/ald1n-project/actions/runs/40203101606) **23/23 fail-closed offline tests PASS**, validating 368 distinct items, 70 L1+183 L2 ASVS descriptions, 115 WSTG titles, complete JSON/CSV, and rejecting false PASS/N/A/source drift. [Full review-ready 368-row JSON/CSV/source archive on Google Drive](https://drive.google.com/file/d/1KQmGNINDRYoMCOsV2tkNvMISfA3am9cx/view?usp=drivesdk). All 368 remain **UNASSESSED/NOT_RUN**, 0 verified security PASS. Machine-suggested SEC chapter categorization is NOT reviewed applicability, proven attack-surface coverage, or automated per-control PASS.
- Formal milestone ledger remains **7/17=41.2%**, CMS 7/15=46.7%, UI 6/6=100%; registered-only storefront OFF and its 56 B803 new failures remain HOLD. Preserve provider safety denials on B805/B809/B815, including the denied standalone HTTP/CSRF probe; do not retry via alternate scripts or tests. Composer locked zero-advisory patch is still Draft/un-deployed; Android M16 emulator technical PASS but signed EAS/physical/Play HOLD. No host SSH unless a fully prepared canonical `incoming/` operator script is actually necessary.

## B866–B871 security/CSP and Composer clean-lock idempotence — 2026-10-25

- [Report851](docs/operations/851-BATCH866-871-HTTP-CSP-COMPOSER-NONREGRESSION-20261025.md), exact source **Draft PR100 `1f63318f1f1c2a76544c53d540f39dbb9307a56c`**. `SecurityHeaders.php` CSP `base-uri 'self'` violates pinned ASVS5 `v5.0.0-3.4.3`; corrected on Draft branch to `base-uri 'none'`, preserving existing directives. **DO NOT mark ASVS 3.4.3 PASS:** enforced script-src/default-src policy and browser nonce/hash migration remain unresolved. [Issue #101](https://github.com/AldinAga/ald1n-project/issues/101) tracks browser/UAT-safe completion.
- [Exact-head B866 HTTP CI #40204443961](https://github.com/AldinAga/ald1n-project/actions/runs/40204443961) **52/52 tests, 515 assertions PASS**, actual isolated Laravel/MariaDB login, password, storefront OFF/404 private capability, header/CORS, plus unchanged B836/B847/B843. [Drive archive](https://drive.google.com/file/d/1Ik3FQVtUvfS5qXK99Sbd5d5obNzuNGUl/view?usp=drivesdk).
- B858 repeat blocker solved: former unconditional `composer update` created bot lock-only commit on every PR push despite 0 advisories, shifting source head and invalidating CI provenance. **Permanent policy: `composer audit --locked` FIRST**; a genuinely clean lock must use only `composer install` with zero lock diff/no push, while true advisory findings may invoke narrowly gated update. Bad JSON/network/tool codes = HOLD. [Latest B858 CI #40204444046](https://github.com/AldinAga/ald1n-project/actions/runs/40204444046) 0 advisories, 41/398 PHP tests PASS, `B858_LOCK_ALREADY_CURRENT_NO_PUSH`. [Drive proof](https://drive.google.com/file/d/1xQ2AnWAktNbnGVS_bHqDqcf3LNKR0cTr/view?usp=drivesdk).
- Former B859 **574×2** lock-only comparison is immutable historical PASS for pre-CSP source and correctly FAILS when PHP code changed; **do not weaken its source-diff allowlist**. Rebased active job B871 against **exact pre-bot `aaef347...`** with B866 PHP source/test already present, all **585×2 same identities**. [B871 exact-head CI #40204443889](https://github.com/AldinAga/ald1n-project/actions/runs/40204443889) **100 old failing identities → 98 new, ZERO new, two fixed**, 13/13 negative tests PASS. [Drive full 585×2 JUnit](https://drive.google.com/file/d/1RCTEATmsQQSoflueUhtZ_OiDRrBavhvW/view?usp=drivesdk).
- [B803 current full CI #40204443858](https://github.com/AldinAga/ald1n-project/actions/runs/40204443858) remains **451/44 old baseline → 585/98 candidate, 56 new failure identities HOLD**. 368 WSTG/ASVS full item assessments UNASSESSED/NOT_RUN. No B805/B809/B815 denied fixture adaptation, no standalone HTTP CSRF workaround, no production/SSH/EAS/Play/source merge. 16KiB emulator technical PASS is not M16 accepted; SDK58 deferred. Formal owner milestones remain **7/17=41.2%**, CMS **7/15=46.7%**.

## B872–B876 independent public no-script CSP and MariaDB customer-role ID stability — 2026-10-25

- [Report852](docs/operations/852-BATCH872-876-PUBLIC-NOSCRIPT-ROLE-ID-20261025.md), child **Draft PR102 exact `5315d72a7b918f7a9f0104a820656d0a6920fe0d`**, branched from untouched PR100 `1f633...`, not merged. New `PublicNoScriptCsp` tightens `GET 200 text/html` only on catalog/detail/cart/checkout/registered receipt, with enforced `default-src 'none'; script-src 'none'`, local CSS and X-Frame-Options DENY. **Do NOT apply globally** to Turnstile registration, login, auth, Admin, errors, PDFs/API until scoped nonce/hash and real browser checks exist; full ASVS V3.4.3 is not PASS. [Issue101](https://github.com/AldinAga/ald1n-project/issues/101) remains open.
- [Exact-head B872–B876 CI #40209090924](https://github.com/AldinAga/ald1n-project/actions/runs/40209090924) **68/68 real Laravel PHP, 776 assertions, 8/8 adversarial six-view scan, 6/6 real Chromium desktop/mobile script injection BLOCKED + CSS loaded**, with original B836/B847/B843/B866 unchanged. [Full Drive CI/JUnit/screenshots](https://drive.google.com/file/d/1Nx_rAxClIGNeEGryYyDDKk2RlmyJ7Qss/view?usp=drivesdk).
- Critical repeated-run blocker actually found and fixed (B876): schema `roles.id` and `users.role_id` are **UNSIGNED TINYINT max255**; `CoreAccessSeeder updateOrInsert(['slug'=>'customer'])` without PK consumes MySQL AUTO_INCREMENT even when `RefreshDatabase` transaction rolls back, causing four new `WarrantyTerms797Test` **SQLSTATE[22003]** on the first 598-case B803 run (60 new failures). Seeder now **preserves existing customer role PK/user FKs** and inserts only missing customer role using first unused explicit ID4..255, failing if no ID remains. Three new MariaDB invariants check idempotent seed AUTO_INCREMENT, historic id12 buyer FK, and occupied id4 preservation. Do not change existing production roles, applied migration or user IDs.
- [Current complete B803 #40209090923](https://github.com/AldinAga/ald1n-project/actions/runs/40209090923) **451 baseline/44 failing → 601 candidate/98 failing**, **56 new identities EXACTLY matching previous parent PR100**, **4 additional SQL overflow identities FIXED**, **0 additional new identities**. Full-suite still RED, global public-storefront RELEASE HOLD, 368 OWASP item reviews UNASSESSED/NOT_RUN, no protection test rewrites or denied HTTP CSRF probes. [Before evidence](https://drive.google.com/file/d/1JZhnTDKYCL5ntee6n2QmTswyI6sit32B/view?usp=drivesdk) · [After evidence](https://drive.google.com/file/d/1Ql9HsCx7BW4gUtgByPCyEsXxcv7lMpsu/view?usp=drivesdk).
- JUnit identities may be dot-delimited `Tests.Feature...` rather than backslash-namespaced. Validators must **fail closed on 100% case identities/counts** but normalize either delimiter for comparison without dropping, reclassifying, editing or skipping tests. Original B872 first CI correctly STOPPED on parser mismatch despite 65 PHP tests PASS; only parser fixed.
- Formal acceptance stays **7/17=41.2%**, CMS **7/15=46.7%**; M16 Android signed Play + physical UAT and M17 SDK58 remain HOLD. No SSH, production, Play, OTA, deployment or merge; `000-LATEST.md` direct edit remains provider-denied and untouched.

## B877–B881 secure public dynamic media cache, Host-safe cart redirects and exact 56 B803 census — 2026-10-25

- [Report853](docs/operations/853-BATCH877-881-MEDIA-CART-CACHE-56-TRIAGE-20261025.md) records source **Draft child PR103 `985c289e5d2dc1f8532af4ea6b87eb18ba630478`** stacked on PR102 `5315d72...`. Only `PublicShopController.php`, `SecurityHeaders.php`, and new B877 tests changed in CMS; original locked Composer/registered buyer/denied B805/B809/B815 source fixtures untouched.
- **Recurring emergency OFF/CDN gap:** formerly dynamic `public-shop.media` sent `Cache-Control: public,max-age=3600` even though origin returns 404 on OFF. **Permanent invariant:** dynamic gated images **200 and route-resolved 404** must use `private,no-store,max-age=0`, `Pragma no-cache`, `X-Robots-Tag noindex`; never restore public 1h cache without explicit reviewed OFF/CDN revalidation. Formerly cached production/edge entries are not retroactively purged by code; separate scoped CDN invalidation/readback gate required before any ON.
- **Cart redirects Host-safe:** all `add/update/remove` return **relative** `Location: /korpa` via `route(...,false)` and native RedirectResponse; add preserves normal session flash. Reject any future change that builds absolute Location from client Host/Forwarded authority. Do not disable Laravel CSRF/session/capability guards to green historical guest tests.
- [Exact PR103 HTTP CI #40211193045](https://github.com/AldinAga/ald1n-project/actions/runs/40211193045) **77/77 tests, 930 assertions PASS** with nine B877 + 68 existing unchanged B836/B843/B847/B866/B872/B876; 0 skipped/warning/failed/duplicate case IDs; [Junit/source/validator on Drive](https://drive.google.com/file/d/1NvzbDRIJUX61BRhh2AX8W7VnzAlaWNsp/view?usp=drivesdk).
- [Full B803 exact HEAD #40211193029](https://github.com/AldinAga/ald1n-project/actions/runs/40211193029) baseline **451/44 problems** vs candidate **610/98 problems**, **56 new failing identities EXACTLY unchanged** from PR102, no added/removed identities. [Full source/JUnit ZIP Drive](https://drive.google.com/file/d/1y8h1b0l4GaaUBOQIy-x8lXwGoKAONPeb/view?usp=drivesdk). 56 observed symptoms: **40 HTTP 302 instead of 200, 9 403, 3 404, 3 ValidationException, 1 B815 undefined method**. **DO NOT claim 56 "waived"/green.** Older anonymous checkout and bearer-only receipts conflict with newer registered + session-gated policy. Fix true code defects only; forbidden test fixture edits stay forbidden. [Issue #104](https://github.com/AldinAga/ald1n-project/issues/104) tracks requirement reconciliation.
- Full 368 OWASP WSTG/ASVS controls still **UNASSESSED/NOT_RUN**, SEC02/22 source HTTP partial only; global Laravel release HOLD, public storefront CloudLinux OFF, no production/SSH/merge/EAS/OTA/Play. Android M16 signed/physical/Play and SDK58/M17 HOLD. Accepted formal progress **7/17=41.2%**, CMS **7/15=46.7%**.

## B883–B887 verified local auth redirect source, 73 exact HTTP PASS and 627-case full HOLD — 2026-10-25

- [Report854](docs/operations/854-BATCH883-887-AUTH-LOCAL-REDIRECTS-20261025.md) final source **Draft PR105 `601ea692dcfd34f922d7f6544089a622521b8a3b`** stacked on PR103 `985c289...`. Exact 10 CMS changed paths: login, forgot/reset and activation Controllers/FormRequests, SecurityHeaders logout privacy, 17 new synthetic tests. Composer.lock, buyer-only policy, existing protected B805/B809/B815 fixtures, customer/production DB and Android code **untouched**.
- Permanent **auth redirect rule:** do NOT use `back()` on sensitive login, password reset/forgot or activation POST. Browser-controlled Referer / Host / Forwarded authority may influence destination; use native `RedirectResponse(route(...,false))` with same session. For errors that call `onlyInput()`, must `setRequest($request)` first or HTTP500. Laravel standard FormRequest `getRedirectUrl()` may **absolutize a relative path** to canonical `APP_URL`; for strict relative HTML validation errors use audited `LocalAuthFormRequest::failedValidation()`, validate the local `/path`, whitelist only safe old-input fields, preserve standard **JSON 422** and never flash bearer token/password. Include logout in sensitive `no-referrer/private,no-store` headers. Reset/activation token path accepts **only exactly 80 ASCII alphanumeric**; no external origin or token logs.
- [First RED B883 CI #40213320653](https://github.com/AldinAga/ald1n-project/actions/runs/40213320653) **71/10 new failures** led to real fixes; [first Drive evidence](https://drive.google.com/file/d/1YWkDrZjd1z6JpdY9qDMmcUXGANUm89n6/view?usp=drivesdk). [Second CI #40213837492](https://github.com/AldinAga/ald1n-project/actions/runs/40213837492) PHP **73/785 PASS**, but independent validator **HOLD** stale 71 unique-ID constant. [Second Drive evidence](https://drive.google.com/file/d/11LNlcWWf9MmsskAsVlxtWWqktB5GrLXc/view?usp=drivesdk). Validators must derive **total and distinct** case count from exact expected class counts + hard checkpoint, never drop/rename/suppress tests.
- [Final exact source B883 CI #40214113159](https://github.com/AldinAga/ald1n-project/actions/runs/40214113159) **73/73 real HTTP tests, 785 assertions PASS**, 17 malicious Host/Referer inputs plus 56 unchanged auth/security cases, no skips/failures/warnings, exact 10 path boundary PASS. [Drive JUnit, SHA256 and source](https://drive.google.com/file/d/1e3pBkHYyMtrsoFmXv2f5G394mm7EwiVk/view?usp=drivesdk).
- [Full B803 exact source #40214113299](https://github.com/AldinAga/ald1n-project/actions/runs/40214113299) production baseline **451/44 fail+errors** vs source candidate **627/98**, **56 new fail identities exactly unchanged** from PR103 (0 added/0 removed), global CI **FAIL / RELEASE HOLD**, no waivers. [Original 451+627 JUnit and full logs Drive](https://drive.google.com/file/d/1JwaLWb7h-W0Yq1y26Q7ESujhsCpFSDQm/view?usp=drivesdk). Old guest-success tests may violate newer owner-mandated Kupac authentication — do not weaken it or bypass the safety-denied B805/B809/B815 test change. [Issue104](https://github.com/AldinAga/ald1n-project/issues/104) remains OPEN.
- 368 OWASP full controls independent review **UNASSESSED/NOT_RUN**, current scoped SEC02/SEC07/SEC22 partial only; CloudLinux production B798 public storefront OFF, no SSH/deploy/merge/SMTP/customer data/EAS/OTA/Play. Signed M16 versionCode>27/device/Play and SDK58/M17 HOLD. Formal owner acceptance unchanged **7/17=41.2%**, CMS **7/15=46.7%**, original UI **6/6**. No `000-LATEST.md` provider-denied rewrite attempt.

## B890 feature-test cookie provenance and registered Kupac session fail-closed (2026-10-25)

- Laravel 13 HTTP feature tests **do not automatically emulate a persistent browser across calls**. `MakesHttpRequests::withCookie()` encrypts its value again; when replaying a **real already-encrypted** `Set-Cookie` returned by login, use `withUnencryptedCookie(name, encryptedValue)` to pass it through unchanged. JSON `postJson()` drops test-client cookies unless `withCredentials()` is set. Without BOTH controls, a test can keep the PHP Auth guard principal yet use a different session ID and produce misleading HTTP302/401/409 outcomes. Assert real session registry rows; never loosen server authorization just to green synthetic cookie mistakes. No actual customer cookies or secrets in CI logs.
- Registered active Kupac checkout is bound to a tracked login row; loss/revocation of that row must **not** auto-bootstrap a new session from a cached Auth principal. Preserve only independently legitimate Laravel remember-cookie semantics. [Source-only Draft PR106](https://github.com/AldinAga/ald1n-project/pull/106) and [8/8 PHP8.4/MariaDB10.11.19 HTTP, 85 assertions](https://github.com/AldinAga/ald1n-project/actions/runs/40218242903) are **scoped PASS**, not a waiver of [56 B803 historical failure identities](https://github.com/AldinAga/ald1n-project/issues/104), not 368-item security acceptance or storefront/host/Android release approval. Keep Storefront OFF, no production mutation.

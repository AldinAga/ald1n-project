# AGENTS.md — Ald1n CMS & Mobile execution guardrails

This file is the canonical working contract for automated/agent-assisted changes in the **Ald1n CMS & Mobile** repository.

Its purpose is practical: prevent repeated recovery batches caused by known CloudLinux, Expo/EAS, Git, shell, validator, and release-environment edge cases.

**Read this file before preparing or executing any batch.** If a historical operation report conflicts with this file, first determine whether the report documents a newer intentional change. Do not silently override a newer approved rule.

Last consolidated: **2026-09-19**.

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

## 2. Permanent product rules

These are not optional cleanup targets or legacy TODOs.

- **Product Variants are permanently decommissioned** from active UI, routes, API contracts, services, Mobile types, and operational workflows. Do not restore variant editor/workflows unless explicitly requested by the project owner.
- Mobile cart/order creation remains **product-only**.
- Multiple taps are allowed generally; only active network mutations may use deliberate single-flight/idempotency protection.
- Direct Sale remains available from product detail for authorized users.
- Deferred Direct Sale supports installments and final due date according to the existing server contract.
- Commission remains visible in catalog/product detail where already implemented.
- `Uredi artikal` remains the final admin action on product detail after Direct Sale.

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

Canonical EAS CLI version:

```text
eas-cli@24.7.0
```

Approved invocation:

```bash
EAS_SPEC="eas-cli@24.7.0"
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

### Command-specific flag policy for eas-cli@24.7.0

The flags are not uniform across EAS subcommands. Use the behavior proven on this hosting, not a blanket flag template.

- `eas update:view GROUP_ID --json` is the canonical exact-group read. **Do not add `--non-interactive`**: eas-cli 24.7.0 rejects that flag for `update:view`.
- `eas update:list ... --json --non-interactive` is proven working from the Mobile root.
- `eas update ... --environment production --json --non-interactive` and `eas update:republish ... --json --non-interactive` are proven working in the Build17 OTA flow. Expo export may print a warning recommending `CI=1`; a warning alone is not a failed EAS publish. Judge the command by its real return code and returned update JSON.
- Do not globally install a newer EAS CLI merely because the CLI prints an upgrade notice. Test a new major version through the same pinned npm-exec mechanism in a dedicated read-only compatibility audit first; only then change the canonical pin in this file.

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
[ ] If EAS is needed, am I using npm exec eas-cli@24.7.0?
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
2. **Physical/global `eas` binary assumption** -> `EAS_CLI_MISSING`. Use pinned npm-exec `eas-cli@24.7.0`.
3. **Local repo one documentation commit behind GitHub** -> preflight mismatch. Verify ancestry/delta and `git merge --ff-only` when safe.
4. **Temp directory created inside repo** -> checkpoint detected its own temp files as contamination. Temp/lock outside worktree.
5. **Broad grep for `FAIL`** -> false failure on `Ukupno FAIL: 0`. Parse exact failure semantics.
6. **`git grep` no-match RC=1 under ERR/errexit** -> false fatal failure. Capture expected RC explicitly.
7. **`set -u` + literal `$type` inside double-quoted grep fingerprint** -> unbound shell variable abort. Escape literal dollars/use single quotes.
8. **Assuming a single OpenAPI location** -> topology failure. Active topology is exactly three tracked copies.
9. **Using `php bin/php-lint.php` as full CMS authority** -> wrong gate. Canonical full gate is `php bin/static-check.php`.
10. **Restarting recovery from scratch** -> risks duplicate commits/candidates/promotions. Recover from recorded state markers.
11. **`eas update:view ... --non-interactive` on eas-cli 24.7.0** -> hard CLI failure. Use exact-group `update:view GROUP_ID --json` without `--non-interactive`.

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

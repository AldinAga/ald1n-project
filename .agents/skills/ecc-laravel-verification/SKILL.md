---
name: ecc-laravel-verification
description: "Review and verify Laravel CMS features on Ald1n CloudLinux using ECC-informed disposable PHPUnit, static, MariaDB and browser gates. Use for Laravel 13 PHP 8.4 changes, PHP QA, API parity, bugfixes and pre-merge readiness, never for unsanctioned production maintenance."
---

# ECC Laravel verification — Ald1n safe edition

ECC original: [laravel-verification](https://github.com/affaan-m/ECC/blob/76aaa77df6adc5b889e689007bf99b9a4f74af34/skills/laravel-verification/SKILL.md), MIT. This replaces upstream generic runnable deployment examples with host-safe verification.

## Non-negotiable Ald1n entry gate

First read current remote `main`, root `AGENTS.md`, `docs/operations/000-LATEST.md`, newest numbered operation report and [the strict CloudLinux contract](../../../docs/ECC-CLOUDLINUX-CONTRACT.md). The Ald1n instructions have precedence over all upstream ECC guidance. Report exact source SHA; do not treat a previous CI PASS as proof for new code.

**Host rules:** CloudLinux/Unlimited.rs restricted shell, canonical `/home/icaffeco/ald1n-project`. Host operator scripts and copy/paste commands must use `incoming/` only, with checked SHA-256 and `bash -n`; operation receipts exclusively `docs/operations/`, numbered using highest host + GitHub reports. Ban host process substitution, `/dev/fd`, `python3`, generic dependency upgrades, forced Git cleanup, migrations/caches and unreconciled writes. Preserve dirty production checkout, secrets, .env/.htaccess, storage and backups. All production/app-build/OTA/Play mutations require independent user approval.

**Current release state:** owner Report749 verified Liquid Glass 2.0 source and HTTP login preflight; **deployment remains HOLD**. ECC docs changes do not authorize deployment and must not silently move the pinned release source.

## Execution: isolated source QA, never arbitrary production maintenance

1. Record the exact branch/commit and changed-path allowlist. Compare `main` and the latest relevant operations report; account for stale/dirty hosting checkout.
2. On a **disposable CI checkout**, verify PHP `8.4`, Laravel `13`, Composer lock integrity, then run the existing canonical CMS static gate: `php bin/static-check.php`. Expected signature: `Ukupno: 983, neuspešno: 0`. Do not substitute PHP lint.
3. On the same exact commit, run the **relevant** existing Laravel tests. Only use a disposable DB fixture; MariaDB 10.11.19 is the production-compatible family. Capture exact tests/assertions and failures; a successful generic MySQL test is insufficient to approve MariaDB ENUM/DDL.
4. For HTTP/Blade/API changes use authenticated role-aware browser/HTTP checks and the user-facing parity matrix. Preserve archive visibility rules, Direct Sale, decommissioned variants and the three identical OpenAPI copies.
5. Run Composer audit and checks on a disposable CI runner if needed and record results; **do not** install/update Composer dependencies on production while verifying.
6. For migrations/schema work require a separate owner-authorized batch, version-matched MariaDB 10.11.19 validation, lock-visibility and rollback/backup evidence. Lack of privileged metadata-lock visibility is HOLD. This generic verification skill provides **no authority** to run migrations on production.
7. For production-readiness, review existing caches, scheduler and queue state through explicitly approved *read-only* operator scripts. Never mutate Laravel cache, config, routes or queue state from this skill.
8. For behavior changes: RED test, minimal fix, GREEN test, broader exact-source gates; commit only reviewed allowlisted paths.

## Evidence and outcome

Report `SOURCE_COMMIT`, test names/counts, static gate, disposable DB family, `WEB_BROWSER_QA`, `OPENAPI_PARITY`, roles, `BATCH_RESULT`, `FAILED_STAGE`, `MUTATION_SCOPE`, next gate. `PASS_SOURCE_QA` is **not** proof of deployment or live database change. No silent production work.

## ECC design principles retained

- Check environmental assumptions before deeper verification.
- Verify business behavior before claiming coverage.
- Review input validation, authorization, session integrity and migration safety.
- Keep build artifacts and logs outside the production Git worktree.

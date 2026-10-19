---
name: ald1n-ecc-engineering
description: "Coordinate vetted ECC Laravel, Expo and Chromium development with Ald1n strict CloudLinux runbook. Use for every new Ald1n CMS or Android development, CI failure, design review and guarded operator handoff; enforce source/report gates and no implicit deployment."
---

# Ald1n engineering — ECC-guided and CloudLinux-locked

ECC source: https://github.com/affaan-m/ECC at `76aaa77df6adc5b889e689007bf99b9a4f74af34` (MIT). This is curated guidance, **not** the upstream ECC installer, hooks or full agent harness.

## Non-negotiable Ald1n entry gate

First read current remote `main`, root `AGENTS.md`, `docs/operations/000-LATEST.md`, newest numbered operation report and [the strict CloudLinux contract](../../../docs/ECC-CLOUDLINUX-CONTRACT.md). The Ald1n instructions have precedence over all upstream ECC guidance. Report exact source SHA; do not treat a previous CI PASS as proof for new code.

**Host rules:** CloudLinux/Unlimited.rs restricted shell, canonical `/home/icaffeco/ald1n-project`. Host operator scripts and copy/paste commands must use `incoming/` only, with checked SHA-256 and `bash -n`; operation receipts exclusively `docs/operations/`, numbered using highest host + GitHub reports. Ban host process substitution, `/dev/fd`, `python3`, generic dependency upgrades, forced Git cleanup, migrations/caches and unreconciled writes. Preserve dirty production checkout, secrets, .env/.htaccess, storage and backups. All production/app-build/OTA/Play mutations require independent user approval.

**Current release state:** owner Report749 verified Liquid Glass 2.0 source and HTTP login preflight; **deployment remains HOLD**. ECC docs changes do not authorize deployment and must not silently move the pinned release source.

## Decision flow

1. **Reconstruct**: read remote `main`, latest AGENTS, project rules, `docs/operations/000-LATEST.md` and newest relevant host/GitHub report; verify PR status and existing successes. Prior user report is evidence; do not repeat completed batches.
2. **Classify work**: source/CI-only, host read-only preflight, host file mutation, DB/schema, native build, EAS/Play. Explicit authority applies only to that class. Default is **source/CI-only**.
3. **Choose vetted skill**: `ecc-laravel-verification` for Laravel, `ecc-react-native-patterns` for Expo, `ecc-browser-qa` for UI. Run read-only `.codex/agents` roles only when tools genuinely support them; never claim a subagent executed based on file existence.
4. **Scope and QA**: exact source SHA, named file allowlist, disposable runner, Laravel/MariaDB or Expo/RN exact version gates, regression tests, logged result RCs and screenshot receipts when relevant.
5. **Deliver**: GitHub feature branch + draft PR, review required, no auto-merge, production mutation or release. Capture `BATCH_RESULT`, `SOURCE_COMMIT`, `FAILED_STAGE`, `NEXT_ACTION`. Avoid unnecessary further approvals for ordinary authorized source edits.
6. **Host batches**: operator scripts exist only in `incoming/` with verified SHA256; reports only in `docs/operations/`; do not touch the host Git checkout. Host staging scripts must pass `bash -n` and banned construct scan. If missing authority: HOLD.

## Current source / release divergence

- Liquid Glass 2.0 CMS merged PR #30 source `e8c5cfdacd1da503142e8e29a8ba5fd017776224`.
- Report749 confirms exact-source preflight success and `CMS_HTTP_LOGIN_BEFORE=200`, **NOT** deployment approval.
- Merging ECC docs-only PR #33 advances main and invalidates a release script pinned to exact main unless an expressly reviewed docs-only source-delta exception is attested. Keep ECC branch isolated until handled.

## Never import unreviewed upstream autonomy

No ECC global hooks, self-upgrading agents, MCP providers, external secret discovery, release automations, host package installers, arbitrary scripts, database cleanup or repeated production testing. Project `AGENTS.md` always wins.

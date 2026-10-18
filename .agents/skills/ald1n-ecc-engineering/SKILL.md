---
name: ald1n-ecc-engineering
description: "Coordinate ECC-derived read-only agents and project-local skills for Ald1n Laravel CMS, Expo Android, browser QA and safe release batches. Use before Ald1n development, debugging, code review, CI failures, or guarded deployment planning."
---

# Ald1n ECC engineering

Upstream: https://github.com/affaan-m/ECC at `76aaa77df6adc5b889e689007bf99b9a4f74af34` (MIT). This skill selects vetted ECC practices; it does not execute the upstream installer, hooks or MCP configuration.

## Required authority
1. Re-read the current GitHub `main`, root `AGENTS.md`, `docs/operations/000-LATEST.md`, and the most recent relevant numbered report before each new batch.
2. Treat Ald1n project rules as authoritative over ALL ECC generic instructions. No stale source, no repeated finished batches, no production changes without explicit gates.
3. For Laravel: use `ecc-laravel-verification` only in disposable CI, respect Laravel 13, PHP 8.4, MariaDB 10.11.19 and webroot invariants.
4. For Expo: use `ecc-react-native-patterns`, preserve Expo SDK57/RN0.86 identities and route/role contracts; native modules require isolated Gradle proof, not OTA-only assumptions.
5. For CSS and UI: use `ecc-browser-qa`; require actual authenticated Chromium screenshots, themes and roles, not just CSS visual guesses.
6. For errors: read the exact failing job logs and commit SHA first; fix the root cause, then report actual rerun results.

## Agent roles
Use the project `.codex/agents/` read-only roles explorer, reviewer and docs_researcher if the Codex client supports multi-agent. Never claim these roles are running unless the active tool actually launched them.

## Release boundaries
Every host-executable script must live in `/home/icaffeco/ald1n-project/incoming/`, with SHA-256 verified. All operation receipts live in `/home/icaffeco/ald1n-project/docs/operations/`, with sequential numbering. Preserve host dirty checkout, secrets, .htaccess and database; never use git reset/pull/clean on hosting. No automatic build, OTA, merge or deploy. A missing checksum is HOLD, not a waiver.

Read `docs/ECC-ADOPTION-20261018.md` before optional plugin installation. Explain approvals and blockers plainly.

---
name: ecc-browser-qa
description: "Test Ald1n CMS Blade and Liquid Glass interfaces with authenticated Chromium screenshots, role and theme checks, accessibility and regression guards. Use after CMS UI changes, responsive CSS changes or before approved CMS deployment, without affecting production data."
---

# ECC browser QA — Ald1n safe edition

ECC original: [browser-qa](https://github.com/affaan-m/ECC/blob/76aaa77df6adc5b889e689007bf99b9a4f74af34/skills/browser-qa/SKILL.md), MIT. Use a disposable local/browser CI environment; real production browser interaction requires explicit read-only owner-approved scope.

## Non-negotiable Ald1n entry gate

First read current remote `main`, root `AGENTS.md`, `docs/operations/000-LATEST.md`, newest numbered operation report and [the strict CloudLinux contract](../../../docs/ECC-CLOUDLINUX-CONTRACT.md). The Ald1n instructions have precedence over all upstream ECC guidance. Report exact source SHA; do not treat a previous CI PASS as proof for new code.

**Host rules:** CloudLinux/Unlimited.rs restricted shell, canonical `/home/icaffeco/ald1n-project`. Host operator scripts and copy/paste commands must use `incoming/` only, with checked SHA-256 and `bash -n`; operation receipts exclusively `docs/operations/`, numbered using highest host + GitHub reports. Ban host process substitution, `/dev/fd`, `python3`, generic dependency upgrades, forced Git cleanup, migrations/caches and unreconciled writes. Preserve dirty production checkout, secrets, .env/.htaccess, storage and backups. All production/app-build/OTA/Play mutations require independent user approval.

**Current release state:** owner Report749 verified Liquid Glass 2.0 source and HTTP login preflight; **deployment remains HOLD**. ECC docs changes do not authorize deployment and must not silently move the pinned release source.

## Required browser evidence

1. Record exact branch SHA, target URL and environment authority (`ISOLATED_BROWSER_ONLY` vs authenticated production). Do not claim production UAT from a local preview.
2. Reuse the existing Chromium browser harness from `apps/cms/current/tests/Browser/` when applicable. At minimum cover width **1440, 1024, 768, 390, 320** where page supports it, with `dark`, `light`, `auto`, and authorized SuperAdmin vs restricted user. Record precisely which combinations executed.
3. Capture actual PNG screenshots or artifact IDs; compare header/menu/cart/search positioning, RSD/EUR interactive control, archive and product editor affordances, responsive overflow, keyboard focus, accessibility, dark/light contrast, forced/reduced transparency behavior, console and HTTP errors.
4. For Liquid Glass, test CSS and JS initialization rather than inferring refraction from opacity. Assert SVG glass renderer/layer when supported, fallback without SVG, sticky header and input interaction; check actual generated screenshots manually for legibility and layering artifacts.
5. All database-backed flows in CI use disposable fixtures, not live orders/customers. Never delete, create or edit production business data during a visual acceptance run.
6. Fail exact-source QA on missing image, JS error, actionable UI regression or incomplete role coverage. Report screenshot artifact location, link, viewport matrix, failure count, and source commit. A browser PASS does not authorize deployment.

## ECC design principles retained

- Focus on user-visible changes rather than decorative snapshots only.
- Capture reproducible failure conditions and narrow regression cases.
- Use human screenshot review for real visual acceptance; automation alone is not aesthetic approval.

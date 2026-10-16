# Report697 — PR16 / main CI integration and CloudLinux Report696 reconciliation

**Date:** 2026-10-16. **Decision:** PASS_ISOLATED_SOURCE_INTEGRATION; **HOLD_PRODUCTION_RELEASE**.

## Grounded host evidence
- User-executed report: `696-PR16-CLOUDLINUX-TRACKED-DRIFT-READONLY-20261016-204213.md`.
- Script: `incoming/ald1n-pr16-cloudlinux-tracked-drift-classification-v1.sh`.
- Script SHA-256: `edb41df3364ef54c9b7388b420b5505f25df179b47a9c077a7cfb1450cef31da`.
- Verified report696 pinned GitHub main `bc7fdd3580c02ac3d45fd1b76b3b09aaf24bd60d` and PR16 `dce9eb9120bc85183385b39b52b5e8e7c86d4487`.
- On-host Git HEAD remains stale `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`; 14 tracked dirty paths: **10 worktree bytes match remote main**, **4 local unique overrides**, zero unknown paths and zero missing paths. No Git refs/index/worktree changed by operator audit.
- Four local unique overrides, **must preserve and independently checkpoint before any production integration**:
  1. `apps/cms/current/public/.htaccess` — critical live webroot configuration;
  2. `docs/operations/000-LATEST.md` — local operational authority;
  3. `docs/operations/548-BATCH525R2-BUILD23-GRADLE-VERSIONNAME-FIX-RECOVERY-20261007-100059.md`;
  4. `docs/operations/549-BATCH526-BUILD24-PRODUCTION-AUTO-SUBMIT-20261007-115241.md` — locally unique staged/unstaged record; not on remote main.
- Two prior independent owner-executed reports: 694 identifies `RESTRICT` vs `NO ACTION` InnoDB aliases; 695 attests live 121/121 InnoDB user FKs, 7/7 historical non-FK columns, zero unknown user-like columns, production retirement migration pending; no database mutation.
- Live production counts last read: 20 users, 53 orders, 17 commissions, 54 warranties, one active SuperAdmin (metadata only).

## Exact-main integration result, entirely in disposable CI
- PR16 prior code head `dce9eb9120bc85183385b39b52b5e8e7c86d4487`.
- CI workflow update/source SHA `3352d77af170d15a538f58c8c07fe48ab487829f`.
- Pin `main=bc7fdd3580c02ac3d45fd1b76b3b09aaf24bd60d` and original PR merge base `e0adb7cf8b9a2d569043ce826b98624c12385f81`.
- All **28 PR16 changed paths** had identical old-base and current-main blob IDs before the temporary integration. Main introduced six nonoverlapping paths/changes in two commits (Batch687 catalog/AGENTS).
- CI in temporary GitHub Actions worktree: exact PR checkout, `fetch-depth: 0`, verify pinned `origin/main`, perform `git merge --no-commit --no-ff` with pinned main, verify detached HEAD remains PR SHA; source merge PASS without conflicts. **No GitHub main merge or remote branch ref update by the CI jobs.**
- Guards prove merged `apps/cms/current/app/Services/CatalogQueryService.php` **equals current main** and merged `apps/cms/current/public/.htaccess` equals current main. These are isolated-source guarantees; they do **not** license replacing the live host .htaccess override.
- Exact-source CI: https://github.com/AldinAga/ald1n-project/actions/runs/38964466623 — both jobs **PASS**:
  - Android/Expo Release25 **168/168 PASS**; TypeScript and Mobile validator `Ukupno FAIL: 0`.
  - Laravel MySQL 8.4 / PHP8.4 `UserRetirementMySqlIntegrationTest`: **18/18, 176 assertions PASS** on dedicated `ald1n_retirement_ci`.
  - Related Laravel HTTP/security: **12/12, 68 assertions PASS**, same disposable DB.
  - Full canonical Laravel CMS static `php bin/static-check.php`: **983 checks, zero failed**; PHP lint, autoload and routes PASS.
  - Three-way OpenAPI byte parity PASS and retired-feature assertions PASS.
- New CI workflow tests PR16 **combined with exact main** rather than solely old PR base. Tested integration is uncommitted in CI; a future production release must use a **separately recorded immutable merged commit**, then re-attest that exact commit before deployment.

## Outstanding hard release gates
1. **No destructive/sync operations on dirty host checkout.** Design staged immutable-source deployment to external worktree/build directory, classify/checkpoint all four local overrides (including unique .htaccess and unarchived docs); do not blindly reset, pull, checkout, overwrite or git clean.
2. **Backup restore readiness not attested**; previous restore drill was explicitly deferred by owner. Verify backup using approved existing first-party flow and obtain separate owner authorization before any restore drill/high-risk release.
3. Broad **full Laravel end-to-end feature suite** still not fully cleared: targeted MySQL HTTP/security suite and full CMS static pass, but historical unrelated suite failures exist.
4. Merge PR16 into current main only after owner-approved release gates; **PR16 remains DRAFT**. New migration `retired_at` must not run on production before controlled migration preflight/rollback.
5. Verify current EAS production/latest authority and exact OTA group prior to any Android update. Build25 baseline `1.0.0` / versionCode `25` / runtime `1.0.0-build17`, previously reported prod group `c00f227a-9136-4287-a857-bf961d1e35d1`. Physical Android PR16 UAT occurs only after a separately authorized candidate environment/test target; no real customer retirement for tests.

**Production DB writes:** ZERO. **Production CMS/Git mutation:** ZERO. **Android native/EAS OTA/Google Play action:** ZERO. **PR16 merge:** NO. **Next action:** safe explicit source-release plan, not another FK audit.

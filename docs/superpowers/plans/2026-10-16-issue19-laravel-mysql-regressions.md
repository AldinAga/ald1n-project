# Issue #19 — Laravel/MySQL Baseline Repair Implementation Plan

> For agentic workers: implement task-by-task with test-driven development and verified exact-source CI; do not change production.

**Goal:** Restore the independently broken PHPUnit baseline on Laravel 13 / PHP 8.4 / MySQL 8.4 without changing business policy or contaminating PR #16.

**Architecture:** Work exclusively on `fix/issue19-laravel-mysql-baseline-20261016` derived from verified `main=bc7fdd3580c02ac3d45fd1b76b3b09aaf24bd60d`. Reuse red test cases proven in cross-comparison run [38968526991](https://github.com/AldinAga/ald1n-project/actions/runs/38968526991). Change tests where expectations/fixtures are stale, and production code only where a genuine domain/API defect is demonstrated with a failing test. Run full scope and compare against the original source. Never overwrite dirty CloudLinux checkout.

**Tech stack:** Laravel 13, PHP 8.4, PHPUnit 12, MySQL 8.4, GitHub Actions. Original 32-file matrix: 88 PASS / 26 FAIL on both `main` and `PR16 + main`.

**Spec:** [Issue #19](https://github.com/AldinAga/ald1n-project/issues/19), AGENTS.md, Report699, canonical CI [38968526991](https://github.com/AldinAga/ald1n-project/actions/runs/38968526991).

## Global constraints
- NO app production, database, EAS, APK or Play mutations. NO PR16 edits or merge.
- Honor product-only catalog and preserved legacy order/commission/warranty history.
- Distinguish stale expected values from genuine Laravel failures before each change; no cosmetic behavior changes.
- For a code change, reproduce RED on disposable MySQL before GREEN. Keep tests and real behavior synchronized.
- Preserve host snapshot: `/home/icaffeco/backups/pr16-local-overrides/checkpoint-20261016-211139-2157930`.

## Review focus
1. New SKU sequence must remain six-digit brand-prefixed and uniqueness-preserving.
2. A one-image gallery retains fullscreen access without a previous/next UI button; inline JS selectors do not count as DOM buttons.
3. Taxonomy migrations may seed common specification fields; test fixtures must be idempotent without suppressing nonunique domain errors.
4. After-sales inventory movements must use valid accounting types and retain action source/audit idempotency.
5. Auth sessions and payment states must use current contracts; fail-closed permissions may not be relaxed for tests.

### Task 1: Catalog baseline (5 failures)
**Files:** `tests/Feature/AdminProductManagementTest.php`, `CatalogDetailPageTest.php`, `SmartProductManagementTest.php`, possibly existing fixture helpers.
- [ ] Confirm source SKU generator and rendered gallery behavior.
- [ ] Update obsolete expectation to six-digit brand SKU; verify RED original, GREEN candidate.
- [ ] Change gallery check to test actual rendered navigation button, not the inline JS selector string; verify.
- [ ] Make specification fixture reuse existing seeded `procesor` field, retaining real distinct validation; run 7-file catalog scope and review all 5 failures.

### Task 2: Orders and finance baseline (6 failures)
**Files:** Existing `OrderDeliveryWorkflowTest.php`, `OrderEmailsIpsWarrantyTest.php`, `PaymentsAdvancedInventoryTest.php`, `ReportsDocumentsSupplierTest.php`; production code only after contract evidence.
- [ ] Trace each 405/redirect mismatch to the canonical routes and request validation.
- [ ] Determine actual stock receipt and payment storage expectations, update only stale fixtures.
- [ ] Make schema-incomplete test isolate dependent FK table without dropping production schema in normal flows.
- [ ] Run the complete 7-file MySQL scope and inspect assertions.

### Task 3: After-sales baseline (6 failures)
**Files:** `AfterSalesActionExecutionTest.php`, `AfterSalesWorkflowTest.php`, `FieldOperationsWorkflowTest.php`, `ServicePartsWorkflowTest.php`, `WarrantiesPreventiveMaintenanceTest.php`; service/migration if confirmed.
- [ ] Trace invalid `movement_type` into table constraints and inventory ledger semantics; RED reproducer first.
- [ ] Fix real production invalid movement write with additive compatible schema or correct documented ledger type, not silent drop.
- [ ] Repair isolated after-sales fixtures/route expectations while preserving idempotency.
- [ ] Run 6-file scope, verify ledger/audit and warranties.

### Task 4: Portal and system baseline (9 failures)
**Files:** `CustomerPortalDashboardTest.php`, `DashboardLegacyDesignTest.php`, `LoginDashboardFallbackTest.php`, `MobileApiFoundationTest.php`, `SecurityHealthBackupTest.php`.
- [ ] Align legacy `payment_status` fixture to current database enum.
- [ ] Pin actual dashboard HTML and intentional behavior under missing `settings`/roles.
- [ ] Verify device registration/session revocation and notifications JSON against current authenticated API.
- [ ] Run 12-file scope and assert fail-closed behavior.

### Task 5: Excluded legacy tests and release gates
**Files:** `AdminOrdersImageRotationTest.php`, `OperationalOrdersCommissionsTest.php`; CI workflow and operations report.
- [ ] Reproduce and repair FK-dependent schema-drop test in isolated MySQL only.
- [ ] Update obsolete commission copy assertion only after checking correct business minimum.
- [ ] Run all 34 Feature files and all relevant Unit tests on disposable MySQL; record exact PASS/FAIL, no hidden skips.
- [ ] Run repository PHP static, OpenAPI, Android Release25 as needed if code changes.
- [ ] Keep PR16 draft and all host release gates HOLD until owner authorization; create reviewable draft PR for Issue19 branch and report remaining failures honestly.

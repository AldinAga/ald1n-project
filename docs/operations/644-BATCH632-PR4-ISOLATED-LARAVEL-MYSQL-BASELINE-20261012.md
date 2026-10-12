# Report 644 — Batch632 Order #134 PR4 isolated Laravel/MySQL integration and pristine-main baseline

Date: 2026-10-12 (Europe/Belgrade)
Authority: GitHub PR #4 https://github.com/AldinAga/ald1n-project/pull/4
Report location: `docs/operations/644-BATCH632-PR4-ISOLATED-LARAVEL-MYSQL-BASELINE-20261012.md`
Scope: source-only audit of route recovery; no production deployment, DB access, native build, OTA or Play action

## Owner skip of unrelated restore drill
- The owner expressly skipped the previously suggested isolated backup restore preflight. Report644 records **PR4 test QA**, NOT execution of any backup import or restore.
- Backup runs #151/#150 maintain PASS on manifest/SQL/attachments/database metadata from Reports642/643; **restore-ready is not attested**. No removal of the old third backup, and 82 unarchived operations reports remain protected.

## Immutable source and isolated test environment
- PR4 candidate source: `25d29162c01938b7a58abe6914fee403beb72879` (five changed CMS source/test paths, Draft).
- Verified current main at QA start: `64dedd1bd2df40024fedcbae6c2e9d1ffacfe485`. Main advanced 44 commits from PR4's old merge base but **zero overlap** with five PR4 paths.
- GitHub Actions audit workflow branches (CI-only; no main/PR4 source mutation):
  - PR4 test-only audit workflow HEAD `6ba6754a44b1c58ef348133738d804cb5675eb36`, checkout pinned to exact PR4 candidate.
  - Pristine main baseline workflow HEAD `1808675a5cfbcb63935743b5a1036d4e776d93fe`, checkout pinned to exact main SHA.
- Each runner used PHP 8.4.26, MySQL 8.0.46 ephemeral service and a uniquely named `*_test` schema with dedicated ephemeral DB identity, Composer from lock file, `app:test-database-doctor` PASS, and Laravel `phpunit.mysql.xml` tests. Production/legacy schemas and credentials were never used. Zero workflow artifacts uploaded.

## First broad integration evidence (not all PASS)
- https://github.com/AldinAga/ald1n-project/actions/runs/38256868139 — initial PR4 candidate 11 tests, 61 assertions, **8 PASS / 3 FAIL**, RC1. Five-file PHP lint and actual-source notification smoke **14/14 PASS**, test-db doctor PASS.
- The three failed methods are all **pre-existing** `MobileApiFoundationTest` methods, not the PR4 additions:
  1. `test_mobile_device_can_be_registered_updated_listed_and_revoked`: expected 201, received 422 (push token conflict).
  2. `test_re_registering_installation_replaces_old_session_and_device_revoke_removes_current_session`: old personal-access-token row remains.
  3. `test_notifications_can_be_listed_and_marked_as_read`: expected read flag `true`, received `null`.
- An intermediate retry failed only because PHPUnit 12 does not recognize CLI `--no-ansi`, which was corrected to `--colors=never` in the **audit workflow only**, without changing PR4 or main application source.

## Exact changed-feature tests — PASS
- https://github.com/AldinAga/ald1n-project/actions/runs/38257422989 — exact PR4 source: **3/3 PASS, 12 assertions** with real MySQL, exit 0. Tests:
  - `test_legacy_admin_notification_recovers_matching_order_route`
  - `test_legacy_notification_rejects_mismatched_admin_order_id`
  - `test_order_notifications_use_role_specific_routes_for_inbox_and_push`
- Confirmed expected route for admin/superadmin inbox and push, user route separation, and mismatched historical IDs. CI source and index immutability PASS; zero artifacts.

## Independent unchanged-main reproduction — PASS as regression diagnosis only
- https://github.com/AldinAga/ald1n-project/actions/runs/38257664993 — pristine main source without three new PR4 tests; **8 tests, 49 assertions, 5 PASS / 3 FAIL**, controlled PHPUnit RC1. The exact same three method names above failed.
- **GitHub Actions run concluded SUCCESS because its acceptance criterion was successfully reproducing these three known baseline failures**, not because the original eight tests passed. No tests were skipped or silently reclassified.
- The three pre-existing failures need their own issue/recovery scope and are **not proof the PR4 route fix is defective**, though the broader Laravel feature suite remains non-green.

## Gates and next step
- PR4 real new Laravel MySQL regression tests **PASS 3/3**, dependency-free notification smoke 14/14 PASS, canonical CMS static 983/983 PASS previously recorded in Report633.
- PR4 remains **DRAFT**, not merged, not deployed. Candidate is not accepted for live behavior: Order #134 role/archived/purged state, live authenticated API permission matrix (200 vs 403/404), and physical Android notification cold/warm-start acceptance remain **HOLD**.
- Next permitted scope: read-only live Order #134 role/status/permitted-resource diagnosis with PII-redacted reporting; no DB mutations. Existing live CMS checkout remains dirty/stale, so do not sync/reset. Do not use this report as authorization for production fixes or build.
- No source modification, backup deletion/creation, migrations on production, EAS pin change, native build, OTA or Play action.

REPORT644_NEW_PR4_LARAVEL_MYSQL=3_OF_3_PASS
REPORT644_PR4_MYSQL_ASSERTIONS=12_PASS
REPORT644_BASELINE_UNPATCHED_MAIN=5_OF_8_PASS_THREE_EXISTING_FAIL
REPORT644_FULL_LARAVEL_FEATURE_SUITE=HOLD
REPORT644_PR4_SOURCE_HEAD=25d29162c01938b7a58abe6914fee403beb72879
REPORT644_PR4_MERGED=NO
REPORT644_ORDER134_LIVE_HTTP=HOLD
REPORT644_ANDROID_UAT=HOLD
REPORT644_RESTORE_TEST=OWNER_DEFERRED
REPORT644_PRODUCTION_MUTATIONS=NONE

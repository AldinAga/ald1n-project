============================================================
MOBILE v0.7.0 - RECEIVABLES ADMIN READ-ONLY AUDIT - BATCH 1
============================================================
DATE=Wed Aug 19 12:25:21 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-RECEIVABLES-ADMIN-AUDIT-BATCH1-20260819-122521.md
EVIDENCE=/home/icaffeco/backups/releases/mobile-v0.7.0-receivables-admin-audit-batch1-20260819-122521
MODE=READ_ONLY_DISCOVERY_AND_CONTRACT_AUDIT
TARGET_WORKSTREAM=RECEIVABLES_ADMIN
SOURCE_WRITES_EXPECTED=0
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION_CHANGE=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
RERUN_POLICY=SAFE_IDEMPOTENT_READ_ONLY
PRIOR_WORKSTREAM=FIELD_OPERATIONS_ADMIN
EXPECTED_GATE_PLAN=AUDIT_API_FOUNDATION_MOBILE_CLIENT_UI_FINAL_CERTIFICATION

============================================================
0. PREFLIGHT + FIELD OPERATIONS V6 AUTHORITATIVE PREREQUISITE
============================================================
PREFLIGHT_COMMAND_bash=PASS
PREFLIGHT_COMMAND_php=PASS
PREFLIGHT_COMMAND_grep=PASS
PREFLIGHT_COMMAND_sed=PASS
PREFLIGHT_COMMAND_awk=PASS
PREFLIGHT_COMMAND_find=PASS
PREFLIGHT_COMMAND_sha256sum=PASS
PREFLIGHT_COMMAND_sort=PASS
PREFLIGHT_COMMAND_wc=PASS
PREFLIGHT_COMMAND_tr=PASS
PREFLIGHT_COMMAND_head=PASS
PREFLIGHT_COMMAND_tail=PASS
PREFLIGHT_COMMAND_cat=PASS
PREFLIGHT_COMMAND_mktemp=PASS
PREFLIGHT_COMMAND_date=PASS
PREFLIGHT_COMMAND_mkdir=PASS
PREFLIGHT_COMMAND_rmdir=PASS
PREFLIGHT_COMMAND_git=PASS
PREFLIGHT_COMMAND_cmp=PASS
PREFLIGHT_COMMAND_cp=PASS
PREFLIGHT_COMMAND_rm=PASS
PREFLIGHT_COMMAND_cut=PASS
PREFLIGHT_COMMAND_tee=PASS
PREFLIGHT_COMMAND_diff=PASS
PREFLIGHT_COMMAND_timeout=PASS
PREFLIGHT_COMMAND_dirname=PASS
PREFLIGHT_COMMAND_basename=PASS
CONCURRENCY_LOCK=ACQUIRED
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
CURRENT_APP_VERSION=0.7.0
CURRENT_PACKAGE_LOCK_VERSION=0.7.0
FIELD_OPERATIONS_V6_PASS_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-FIELD-OPERATIONS-ADMIN-FINAL-CERTIFICATION-BATCH4-V6-20260819-121410.md
FIELD_OPERATIONS_ADMIN_V0_7_PREREQUISITE=PASS_100_PERCENT_COMPLETE

============================================================
1. READ-ONLY AUTHORITY BASELINE + ROUTE CACHE + GIT SNAPSHOT
============================================================
READ_ONLY_AUTHORITY_HASH_BASELINE=PASS
AUTHORITY_BASELINE_FILE_COUNT=29
GIT_BASELINE_CAPTURED=YES
ROUTE_CACHE_BASELINE_FILE_COUNT=0

============================================================
2. CURRENT WEB RECEIVABLES AUTHORITY SURFACE
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000026_create_receivables_collection_beta7_17.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReceivablesController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ReceivablesDoctorCommand.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableCase.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableInstallment.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableContact.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php
RECEIVABLES_CORE_PHP_SYNTAX=PASS_13_FILES
ROUTE_LIST_JSON=PASS_WITHIN_90_SECONDS
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-receivables-admin-audit-batch1.20260819-122521.2523016/receivables-route-probe.php
WEB_ROUTE=GET|HEAD|admin/receivables|admin.receivables.index|App\Http\Controllers\Admin\ReceivablesController@index|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=GET|HEAD|admin/receivables/export.csv|admin.receivables.csv|App\Http\Controllers\Admin\ReceivablesController@csv|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage|Illuminate\Routing\Middleware\ThrottleRequests:exports
WEB_ROUTE=POST|admin/receivables/scan|admin.receivables.scan|App\Http\Controllers\Admin\ReceivablesController@scan|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=PUT|admin/receivables/settings|admin.receivables.settings.update|App\Http\Controllers\Admin\ReceivablesController@updateSettings|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=GET|HEAD|admin/receivables/{receivable}|admin.receivables.show|App\Http\Controllers\Admin\ReceivablesController@show|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=PATCH|admin/receivables/{receivable}|admin.receivables.update|App\Http\Controllers\Admin\ReceivablesController@update|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=POST|admin/receivables/{receivable}/contacts|admin.receivables.contacts.store|App\Http\Controllers\Admin\ReceivablesController@contact|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=PUT|admin/receivables/{receivable}/plan|admin.receivables.plan|App\Http\Controllers\Admin\ReceivablesController@plan|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_ROUTE=POST|admin/receivables/{receivable}/reminder|admin.receivables.reminder|App\Http\Controllers\Admin\ReceivablesController@reminder|web|Illuminate\Auth\Middleware\Authenticate|App\Http\Middleware\EnsureActiveUser|App\Http\Middleware\EnsureTrackedPortalSession|App\Http\Middleware\RequirePermission:receivables.manage
WEB_RECEIVABLES_ROUTE_COUNT=9
WEB_RECEIVABLES_PERMISSION_COUNT=9
WEB_RECEIVABLES_CSV_EXPORT_THROTTLE_COUNT=0
WEB_RECEIVABLES_REQUIRED_ROUTE_MISSING_COUNT=0
API_ADMIN_RECEIVABLES_ROUTE_COUNT=0
API_ADMIN_RECEIVABLES_GET_ROUTE_COUNT=0
API_ADMIN_RECEIVABLES_MUTATION_ROUTE_COUNT=0
API_ADMIN_RECEIVABLES_SANCTUM_COUNT=0
API_ADMIN_RECEIVABLES_ACTIVE_COUNT=0
API_ADMIN_RECEIVABLES_PERMISSION_COUNT=0
RECEIVABLES_ROUTE_PROBE_FINAL_SENTINEL=PASS
FAIL: Receivables CSV export throttle contract changed

ROLLBACK=NOT_REQUIRED_READ_ONLY_AUDIT_NO_APPLICATION_SOURCE_OR_DATABASE_WRITES

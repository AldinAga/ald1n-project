============================================================
182 - MOBILE v1.0 AUDIT-01 CSV EXPORT PARITY - BATCH 29
============================================================
DATE=Tue Aug 25 12:29:34 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=IMPLEMENT_CONFIRMED_AUDIT_01_CSV_EXPORT_PARITY_GAP_USING_EXISTING_SANITIZED_SECURITY_EVENT_AUTHORITY
PARITY_FAMILY=AUDIT-01
SOURCE_MUTATION=YES_CONTROLLED
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
STRICT_PARITY_PRE=42_OF_62_COMPLETE_STRICT_67_7_PERCENT
STRICT_PARITY_TARGET_ON_PASS=43_OF_62_COMPLETE_STRICT_69_4_PERCENT
EXPECTED_PRE_BATCH_HEAD=db97b6ab0853a18b97598e5fb006135d275181ad
SOURCE_BRANCH=main
REMOTE=origin
LOCAL_HEAD_PRE=db97b6ab0853a18b97598e5fb006135d275181ad
REMOTE_HEAD_PRE=db97b6ab0853a18b97598e5fb006135d275181ad
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
NPM_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/npm
REPORT_181=/home/icaffeco/ald1n-project/docs/operations/181-MOBILE-V1.0-FULL-SAFE-GITHUB-CHECKPOINT-BATCH28A-V2-20260825-120654.md
BACKUP=/home/icaffeco/backups/releases/mobile-v1.0-audit-01-csv-export-parity-batch29-20260825-122930
REPORT=/home/icaffeco/ald1n-project/docs/operations/182-MOBILE-V1.0-AUDIT-01-CSV-EXPORT-PARITY-BATCH29-20260825-122930.md
HARD_PRECONDITIONS=PASS
AUDIT_01_GAP_REAUDIT=PASS_CONFIRMED_REAL_CSV_EXPORT_GAP_NOT_FALSE_NEGATIVE
PRESTATE_TRACKED_WORKTREE=CLEAN
PRESTATE_REAL_GIT_INDEX=CLEAN
PRESTATE_UNTRACKED=PASS_ONLY_REPORT_181_PLUS_CURRENT_182_AFTER_REPORT_CREATION
REPORT_181_EVIDENCE_CHAIN=PASS_CHECKPOINT_db97b6ab0853a18b97598e5fb006135d275181ad_PARITY_42_OF_62
BATCH18_AUDIT_01_GAP_EVIDENCE=PASS_AUDIT_CSV_MISSING
NPM_LAUNCHER_RESOLUTION=PASS_PROVEN_NODE_BIN_NPM_BIN_PATTERN
BACKUP_CREATED=YES
PATCHER_RESULT=PASS
SOURCE_PATCH=PASS
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/api.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AuditEventController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/SecurityEventReadService.php
AUDIT_01_SOURCE_CONTRACT=PASS_LIST_DETAIL_SANITIZED_CSV_EXPORT
AUDIT_01_PERMISSION_CONTRACT=PASS_SECURITY_VIEW_PLUS_AUDIT_EXPORT
AUDIT_01_EXPORT_RATE_LIMIT=PASS_THROTTLE_EXPORTS
OPENAPI_THREE_COPY_SYNC=PASS
SOURCE_SCOPE_GUARD=PASS_11_SOURCE_FILES
GIT_DIFF_CHECK_POSTPATCH=PASS

  GET|HEAD       api/v1/admin/audit-events ....................................................... api.v1.admin.audit-events.index › Api\V1\Admin\AuditEventController@index
                 ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
                 ⇂ Illuminate\Routing\Middleware\SubstituteBindings
                 ⇂ App\Http\Middleware\EnsureActiveUser
                 ⇂ App\Http\Middleware\RequirePermission:security.view
  GET|HEAD       api/v1/admin/audit-events/{event} ................................................. api.v1.admin.audit-events.show › Api\V1\Admin\AuditEventController@show
                 ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
                 ⇂ Illuminate\Routing\Middleware\SubstituteBindings
                 ⇂ App\Http\Middleware\EnsureActiveUser
                 ⇂ App\Http\Middleware\RequirePermission:security.view

                                                                                                                                                          Showing [2] routes

============================================================
FAILURE CERTIFICATION
============================================================
BATCH29_RESULT=FAIL
FAILED_STAGE=RUNTIME_ROUTE_COLLECTION
FAILED_LINE=888
EXIT_CODE=1
SOURCE_AUTO_RESTORED=YES
COMMITTED=0
PUSHED=0
REPORT=/home/icaffeco/ald1n-project/docs/operations/182-MOBILE-V1.0-AUDIT-01-CSV-EXPORT-PARITY-BATCH29-20260825-122930.md

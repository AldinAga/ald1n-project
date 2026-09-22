============================================================
220 - MOBILE v1.0 SYSTEM SETTINGS PARITY - BATCH 37
============================================================
DATE=Wed Aug 26 00:41:48 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=IMPLEMENT_SET_02_SET_04_SET_05_SET_08_SET_09_SET_10_SYSTEM_SETTINGS_PARITY
PARITY_FAMILIES=SET-02+SET-04+SET-05+SET-08+SET-09+SET-10
SOURCE_MUTATION=YES_CONTROLLED
DATABASE_WRITES_EXPECTED=0_DURING_BATCH_VALIDATION
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
STRICT_PARITY_PRE=55_OF_62_COMPLETE_STRICT_88_7_PERCENT
STRICT_PARITY_TARGET_ON_PASS=61_OF_62_COMPLETE_STRICT_98_4_PERCENT
EXPECTED_PRE_BATCH_HEAD=0cc77c716f8665c1e2699f772f832d6b9913e9c7
REPORT_219=/home/icaffeco/ald1n-project/docs/operations/219-MOBILE-V1.0-FULL-SAFE-GITHUB-CHECKPOINT-BATCH36A-V2-20260826-002055.md
REPORT=/home/icaffeco/ald1n-project/docs/operations/220-MOBILE-V1.0-SYSTEM-SETTINGS-PARITY-BATCH37-20260826-004148.md
BATCH37_TEMP_WORKSPACE=PASS_OUTSIDE_REPOSITORY:/tmp/ald1n-batch37.QfZgYv
BATCH37_LOCK=PASS_OUTSIDE_REPOSITORY:/tmp/ald1n-batch37-system-settings.lock
BACKUP=/home/icaffeco/backups/releases/mobile-v1.0-system-settings-parity-batch37-20260826-004148
CONCURRENCY_LOCK=ACQUIRED
SOURCE_BRANCH=main
REMOTE=origin
LOCAL_HEAD_PRE=0cc77c716f8665c1e2699f772f832d6b9913e9c7
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
REMOTE_HEAD_PRE=0cc77c716f8665c1e2699f772f832d6b9913e9c7
REMOTE_SYNC_PRE=PASS
PRE_HEAD_CHECKPOINT_SCOPE=PASS_REPORT_217_PLUS_FAILED_218
PRESTATE_TRACKED_WORKTREE=CLEAN
PRESTATE_REAL_GIT_INDEX=CLEAN
PRESTATE_UNTRACKED_ONLY_REPORT_219_PLUS_CURRENT_220=PASS
REPORT_219_SHA256=74c00d3afe1ad9e993cd5023431a45e5589ce452a0a418d9c867183272cfdf12
REPORT_219_TRAILING_WHITESPACE_LINES=0
REPORT_219_EVIDENCE_CHAIN=PASS_BATCH36A_V2_ORDER_REPORT_OPS_CHECKPOINT_PARITY_55_OF_62_NEXT_SYSTEM_SETTINGS
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
NPM_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/npm
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
COMMERCIAL_SELLING_RATE_AUTHORITY_PRECHECK=PASS_NBS_COMMERCIAL_SELLING
EXCHANGE_AUTHORITY_IMMUTABLE_HASH_BEFORE=e4ad9f3bbee61d8f57339a98fe7461b39fa434896463f57ce17e1fbf8423732d
SYSTEM_SETTINGS_SHARED_AUTHORITIES_PRECHECK=PASS
HARD_PRECONDITIONS=PASS
BACKUP_CREATED=YES_7_EXISTING_SOURCE_AND_REPORT_219
PATCHER_SHA256=95d898385843730a9b9ebc3088beb0ccb02808aba4f5c22ab026dfe2cd22ca76
PATCHER_SYNTAX=PASS
PATCHER_RESULT=PASS
SOURCE_PATCH=PASS
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/api.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/SystemSettingsController.php
SYSTEM_SETTINGS_PHP_SYNTAX=PASS
SYSTEM_SETTINGS_VALIDATOR_SYNTAX=PASS
PASS TS syntax: /home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/system-settings-admin-api.ts
PASS TS syntax: /home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/settings/index.tsx
PASS TS syntax: /home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/settings/automation.tsx
PASS TS syntax: /home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/settings/turnstile.tsx
PASS TS syntax: /home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/settings/appearance.tsx
PASS TS syntax: /home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/settings/order-emails.tsx
PASS TS syntax: /home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/settings/documents.tsx
PASS TS syntax: /home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/settings/bank-accounts.tsx
SYSTEM_SETTINGS_TYPESCRIPT_SYNTAX=PASS_8_FILES
SYSTEM_SETTINGS_OPENAPI_THREE_COPY_SYNC=PASS
SYSTEM_SETTINGS_CONTROLLER_ROUTE_REFS=20
PRODUCT_VARIANTS_NEGATIVE_SOURCE_GUARD=PASS
SOURCE_SCOPE_GUARD=PASS_EXACT
SOURCE_SCOPE_GUARD=PASS_16_SOURCE_FILES_PLUS_REPORT_219_AND_220
SOURCE_TREE_HASH_BEFORE_GATES=dfa895de04782fbf4ab374083bb00dd905adb508c18cd25a7548072f5835ff3b
ROUTE_CACHE_PREEXISTED=1

   INFO  Route cache cleared successfully.


   INFO  Routes cached successfully.

RUNTIME_ROUTE_CACHE_REFRESH=PASS_CLEAR_AND_REBUILD

  GET|HEAD  api/v1/admin/system-settings/appearance ....................... api.v1.admin.system-settings.appearance.index › Api\V1\Admin\SystemSettingsController@appearance
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
  POST      api/v1/admin/system-settings/appearance ................ api.v1.admin.system-settings.appearance.update › Api\V1\Admin\SystemSettingsController@updateAppearance
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/system-settings/appearance/assets/{asset}/remove api.v1.admin.system-settings.appearance.assets.remove › Api\V1\Admin\SystemSettingsController@removeAppearanceAsset
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  GET|HEAD  api/v1/admin/system-settings/automation ....................... api.v1.admin.system-settings.automation.index › Api\V1\Admin\SystemSettingsController@automation
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
  PUT       api/v1/admin/system-settings/automation ................ api.v1.admin.system-settings.automation.update › Api\V1\Admin\SystemSettingsController@updateAutomation
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/system-settings/automation/alerts/{alert}/resolve api.v1.admin.system-settings.automation.alerts.resolve › Api\V1\Admin\SystemSettingsController@resolveAutomationAlert
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ App\Http\Middleware\RequirePermission:automation.manage
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/system-settings/automation/run .................. api.v1.admin.system-settings.automation.run › Api\V1\Admin\SystemSettingsController@runAutomation
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ App\Http\Middleware\RequirePermission:automation.manage
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  GET|HEAD  api/v1/admin/system-settings/bank-accounts ............... api.v1.admin.system-settings.bank-accounts.index › Api\V1\Admin\SystemSettingsController@bankAccounts
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
  POST      api/v1/admin/system-settings/bank-accounts ........... api.v1.admin.system-settings.bank-accounts.store › Api\V1\Admin\SystemSettingsController@storeBankAccount
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  PATCH     api/v1/admin/system-settings/bank-accounts/{bankAccount} api.v1.admin.system-settings.bank-accounts.update › Api\V1\Admin\SystemSettingsController@updateBankAccount
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  DELETE    api/v1/admin/system-settings/bank-accounts/{bankAccount} api.v1.admin.system-settings.bank-accounts.destroy › Api\V1\Admin\SystemSettingsController@destroyBankAccount
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  GET|HEAD  api/v1/admin/system-settings/documents .......................... api.v1.admin.system-settings.documents.index › Api\V1\Admin\SystemSettingsController@documents
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
  POST      api/v1/admin/system-settings/documents ................... api.v1.admin.system-settings.documents.update › Api\V1\Admin\SystemSettingsController@updateDocuments
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  DELETE    api/v1/admin/system-settings/documents/logo ...... api.v1.admin.system-settings.documents.logo.remove › Api\V1\Admin\SystemSettingsController@removeDocumentLogo
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  GET|HEAD  api/v1/admin/system-settings/order-emails .................. api.v1.admin.system-settings.order-emails.index › Api\V1\Admin\SystemSettingsController@orderEmails
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
  PUT       api/v1/admin/system-settings/order-emails ........... api.v1.admin.system-settings.order-emails.update › Api\V1\Admin\SystemSettingsController@updateOrderEmails
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/system-settings/order-emails/dispatch api.v1.admin.system-settings.order-emails.dispatch › Api\V1\Admin\SystemSettingsController@dispatchOrderEmails
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST      api/v1/admin/system-settings/order-emails/retry ....... api.v1.admin.system-settings.order-emails.retry › Api\V1\Admin\SystemSettingsController@retryOrderEmails
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  GET|HEAD  api/v1/admin/system-settings/turnstile .......................... api.v1.admin.system-settings.turnstile.index › Api\V1\Admin\SystemSettingsController@turnstile
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
  PUT       api/v1/admin/system-settings/turnstile ................... api.v1.admin.system-settings.turnstile.update › Api\V1\Admin\SystemSettingsController@updateTurnstile
            ⇂ api
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write

                                                                                                                                                         Showing [20] routes

SYSTEM_SETTINGS_ACTIVE_ROUTE_COUNT=20
SYSTEM_SETTINGS_ACTIVE_ROUTES=PASS_20_ROUTES
No syntax errors detected in /tmp/ald1n-batch37.QfZgYv/runtime-probe.php
SYSTEM_SETTINGS_AUTOMATION_READY=YES
SYSTEM_SETTINGS_REQUIRED_AUTHORITIES=PASS
DATABASE_WRITE_QUERY_COUNT=0
SYSTEM_SETTINGS_RUNTIME_READ_ONLY=PASS_ZERO_DB_WRITES
DATABASE_WRITES_DURING_BATCH_RUNTIME=0
SYSTEM_SETTINGS_MUTATION_ENDPOINTS_EXECUTED_DURING_VALIDATION=NO
------------------------------------------------------------
RUN=MOBILE_TYPECHECK

> ald1n-mobile@0.9.0 typecheck
> tsc --noEmit

src/features/admin/system-settings-admin-api.ts(135,29): error TS2769: No overload matches this call.
  Overload 1 of 3, '(options?: PickSingleFileOptions | undefined): Promise<PickSingleFileResult>', gave the following error.
    Type 'boolean' is not assignable to type 'false'.
  Overload 2 of 3, '(options?: PickMultipleFilesOptions | undefined): Promise<PickMultipleFilesResult>', gave the following error.
    Type 'boolean' is not assignable to type 'true'.
  Overload 3 of 3, '(initialUri?: string | undefined, mimeType?: string | undefined): Promise<File | File[]>', gave the following error.
    Argument of type '{ mimeTypes: string[]; multipleFiles: boolean; }' is not assignable to parameter of type 'string'.
FAILED_STAGE=MOBILE_TYPECHECK
FAILED_LINE=508
BATCH37_RESULT=FAIL
MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37=FAIL
COMMITTED=0
PUSHED=0
ROUTE_CACHE_MUTATED=1
SOURCE_ROLLBACK=PASS_BEST_EFFORT_PRECOMMIT
EXIT_CODE=2
REPORT=/home/icaffeco/ald1n-project/docs/operations/220-MOBILE-V1.0-SYSTEM-SETTINGS-PARITY-BATCH37-20260826-004148.md
REPORT_220_TRAILING_WHITESPACE_LINES=0

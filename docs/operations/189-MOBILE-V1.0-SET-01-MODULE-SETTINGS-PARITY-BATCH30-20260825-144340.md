============================================================
189 - MOBILE v1.0 SET-01 MODULE SETTINGS PARITY - BATCH 30
============================================================
DATE=Tue Aug 25 14:43:44 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=IMPLEMENT_CONFIRMED_SET_01_REMOTE_MODULE_ENABLE_DISABLE_PARITY_USING_EXISTING_CMS_MODULE_VISIBILITY_AUTHORITY
PARITY_FAMILY=SET-01
SOURCE_MUTATION=YES_CONTROLLED
DATABASE_WRITES_EXPECTED=0_DURING_BATCH_VALIDATION
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
STRICT_PARITY_PRE=43_OF_62_COMPLETE_STRICT_69_4_PERCENT
STRICT_PARITY_TARGET_ON_PASS=44_OF_62_COMPLETE_STRICT_71_0_PERCENT
EXPECTED_PRE_BATCH_HEAD=f8ca873e66017ffa51e4908eb06118ab87abdd02
SOURCE_BRANCH=main
REMOTE=origin
LOCAL_HEAD_PRE=f8ca873e66017ffa51e4908eb06118ab87abdd02
REMOTE_HEAD_PRE=f8ca873e66017ffa51e4908eb06118ab87abdd02
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
NPM_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/npm
REPORT_188=/home/icaffeco/ald1n-project/docs/operations/188-MOBILE-V1.0-FULL-SAFE-GITHUB-CHECKPOINT-BATCH29A-V2-20260825-142648.md
BACKUP=/home/icaffeco/backups/releases/mobile-v1.0-set-01-module-settings-parity-batch30-20260825-144340
REPORT=/home/icaffeco/ald1n-project/docs/operations/189-MOBILE-V1.0-SET-01-MODULE-SETTINGS-PARITY-BATCH30-20260825-144340.md
HARD_PRECONDITIONS=PASS
SET_01_GAP_REAUDIT=PASS_CONFIRMED_REAL_API_AND_MOBILE_MUTATION_GAP_NOT_FALSE_NEGATIVE
PRESTATE_TRACKED_WORKTREE=CLEAN
PRESTATE_REAL_GIT_INDEX=CLEAN
PRESTATE_UNTRACKED=PASS_ONLY_REPORT_188_PLUS_CURRENT_189
REPORT_188_EVIDENCE_CHAIN=PASS_CHECKPOINT_f8ca873e66017ffa51e4908eb06118ab87abdd02_PARITY_43_OF_62
BATCH18_SET_01_GAP_EVIDENCE=PASS_API_AND_MOBILE_SETTINGS_MODULES_MISSING
SET_01_WEB_AUTHORITY=PASS_EXISTING_MODULE_VISIBILITY_SERVICE_PLUS_SETTINGS_SERVICE_PLUS_AUDIT_LOGGER
NPM_LAUNCHER_RESOLUTION=PASS_PROVEN_NODE_BIN_NPM_BIN_PATTERN
BACKUP_CREATED=YES
PATCHER_RESULT=PASS
SOURCE_PATCH=PASS
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/api.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ModuleSettingsController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FoundationController.php
SET_01_SOURCE_CONTRACT=PASS_GET_PUT_SUPERADMIN_MODULE_VISIBILITY_AUTHORITY
SET_01_PERMISSION_CONTRACT=PASS_SYSTEM_MANAGE_SETTINGS_PLUS_SUPERADMIN
SET_01_AUDIT_CONTRACT=PASS_SETTINGS_MODULES_UPDATED
SET_01_FOUNDATION_VISIBILITY=PASS_OPTIONAL_ADMIN_MODULES_RESPECT_SAME_AUTHORITY
OPENAPI_THREE_COPY_SYNC=PASS
SOURCE_SCOPE_GUARD=PASS_11_SOURCE_FILES
GIT_DIFF_CHECK_POSTPATCH=PASS

  GET|HEAD  api/v1/admin/settings/modules ................................................ api.v1.admin.settings.modules.index › Api\V1\Admin\ModuleSettingsController@index
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ Illuminate\Routing\Middleware\SubstituteBindings
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
  PUT       api/v1/admin/settings/modules .............................................. api.v1.admin.settings.modules.update › Api\V1\Admin\ModuleSettingsController@update
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
            ⇂ Illuminate\Routing\Middleware\SubstituteBindings
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings

                                                                                                                                                          Showing [2] routes

SET_01_SOURCE_ONLY_ROUTE_COLLECTION=PASS_GET_PUT_PERMISSION_THROTTLE

   ERROR  Your application doesn't have any routes matching the given criteria.

RUNTIME_ROUTE_CACHE_STALE_DETECTED=YES_NEW_BATCH30_ROUTE

   INFO  Route cache cleared successfully.


   INFO  Routes cached successfully.

RUNTIME_ROUTE_CACHE_REFRESH=PASS_CLEAR_AND_REBUILD

  GET|HEAD  api/v1/admin/settings/modules ................................................ api.v1.admin.settings.modules.index › Api\V1\Admin\ModuleSettingsController@index
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ Illuminate\Routing\Middleware\SubstituteBindings
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
  PUT       api/v1/admin/settings/modules .............................................. api.v1.admin.settings.modules.update › Api\V1\Admin\ModuleSettingsController@update
            ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
            ⇂ Illuminate\Routing\Middleware\SubstituteBindings
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings

                                                                                                                                                          Showing [2] routes

SET_01_RUNTIME_ROUTE_COLLECTION=PASS_ACTIVE_ROUTE_CACHE_GET_PUT
No syntax errors detected in /tmp/ald1n-batch30-set01.9MYUbx/set01-read-runtime.php

In Connection.php line 857:

  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'is_active' in 'WHERE' (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: select * fro
  m `users` where `is_active` = 1)


In Connection.php line 435:

  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'is_active' in 'WHERE'


============================================================
FAILURE CERTIFICATION
============================================================
BATCH30_RESULT=FAIL
FAILED_STAGE=READ_ONLY_RUNTIME_MODULE_SETTINGS_PROBE
FAILED_LINE=1140
EXIT_CODE=1
SOURCE_AUTO_RESTORED=YES
COMMITTED=0
PUSHED=0
ROUTE_CACHE_MUTATED=1
REPORT=/home/icaffeco/ald1n-project/docs/operations/189-MOBILE-V1.0-SET-01-MODULE-SETTINGS-PARITY-BATCH30-20260825-144340.md

============================================================
199 - MOBILE v1.0 CUSTOMER PORTAL PARITY - BATCH 33
============================================================
DATE=Tue Aug 25 18:00:56 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=IMPLEMENT_PORTAL_01_AND_PORTAL_ADMIN_01_AS_ONE_COHESIVE_PARITY_BUNDLE_REUSING_EXISTING_LARAVEL_PORTAL_AUTHORITIES
PARITY_FAMILIES=PORTAL-01_PORTAL-ADMIN-01
SOURCE_MUTATION=YES_CONTROLLED
DATABASE_WRITES_EXPECTED=0_DURING_BATCH_VALIDATION
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
STRICT_PARITY_PRE=47_OF_62_COMPLETE_STRICT_75_8_PERCENT
STRICT_PARITY_TARGET_ON_PASS=49_OF_62_COMPLETE_STRICT_79_0_PERCENT
EXPECTED_PRE_BATCH_HEAD=d3de78a72bc29bd119de7b3339481f0a182eb5e0
REPORT_198=/home/icaffeco/ald1n-project/docs/operations/198-MOBILE-V1.0-FULL-SAFE-GITHUB-CHECKPOINT-BATCH32A-V2-20260825-173237.md
REPORT=/home/icaffeco/ald1n-project/docs/operations/199-MOBILE-V1.0-CUSTOMER-PORTAL-PARITY-BATCH33-20260825-180056.md
BACKUP=/home/icaffeco/backups/releases/mobile-v1.0-customer-portal-parity-batch33-20260825-180056
CONCURRENCY_LOCK=ACQUIRED
SOURCE_BRANCH=main
REMOTE=origin
LOCAL_HEAD_PRE=d3de78a72bc29bd119de7b3339481f0a182eb5e0
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
REMOTE_HEAD_PRE=d3de78a72bc29bd119de7b3339481f0a182eb5e0
REMOTE_SYNC_PRE=PASS
PRESTATE_TRACKED_WORKTREE=CLEAN
PRESTATE_REAL_GIT_INDEX=CLEAN
PRESTATE_UNTRACKED_ONLY_REPORT_198_PLUS_CURRENT_199=PASS
REPORT_198_SHA256_BEFORE=84e862ca152d2e1c497c0aa1920b96dd2a672c05a2a3b15972c5b54cf7ef5c7a
REPORT_198_TRAILING_WHITESPACE_LINES_BEFORE=0
REPORT_198_EVIDENCE_CHAIN=PASS_CHECKPOINT_D3DE78A_PARITY_47_OF_62_NEXT_CUSTOMER_PORTAL
HARD_PRECONDITIONS=PASS
BACKUP_CREATED=YES
PAYLOAD_EXTRACTION=PASS_SHA256_VERIFIED
PATCHER_RESULT=PASS
SOURCE_PATCH=PASS
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/api.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalAdminService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CustomerPortalController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/PortalConversationController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/PortalConversationController.php
CUSTOMER_PORTAL_PHP_SYNTAX=PASS
OPENAPI_PREEXISTING_FORMAT_DRIFT_REPAIRED=PASS_PURCHASE_COST_AND_MODULE_SETTING_KEY_LINE_BREAKS
PORTAL_01_SOURCE_CONTRACT=PASS_EXISTING_PORTAL_CONVERSATION_SERVICE_PLUS_CUSTOMER_API_AND_MOBILE_UI
PORTAL_ADMIN_01_SOURCE_CONTRACT=PASS_SHARED_CUSTOMER_PORTAL_ADMIN_SERVICE_PLUS_ADMIN_API_AND_MOBILE_UI
CUSTOMER_PORTAL_OPENAPI_THREE_COPY_SYNC=PASS
SOURCE_SCOPE_GUARD=PASS_21_SOURCE_FILES
GIT_DIFF_CHECK_POSTPATCH=PASS_TRACKED_DIFF
SOURCE_TREE_HASH_BEFORE_GATES=4ce13e791aebe841c6cfbbcaffff56161f0925093e1da23f847fc2b64592eb16
ROUTE_CACHE_PREEXISTED=1

   ERROR  Your application doesn't have any routes matching the given criteria.


   ERROR  Your application doesn't have any routes matching the given criteria.

RUNTIME_ROUTE_CACHE_STALE_DETECTED=YES_NEW_BATCH33_ROUTES

   INFO  Route cache cleared successfully.


   INFO  Routes cached successfully.

RUNTIME_ROUTE_CACHE_REFRESH=PASS_CLEAR_AND_REBUILD

  GET|HEAD   api/v1/portal/messages ............................................................... api.v1.portal.messages.index › Api\V1\PortalConversationController@index
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.view_own
  POST       api/v1/portal/messages ............................................................... api.v1.portal.messages.store › Api\V1\PortalConversationController@store
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:portal-messages
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.view_own
  GET|HEAD   api/v1/portal/messages/{conversation} .................................................. api.v1.portal.messages.show › Api\V1\PortalConversationController@show
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.view_own
  POST       api/v1/portal/messages/{conversation} ................................................ api.v1.portal.messages.reply › Api\V1\PortalConversationController@reply
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:portal-messages
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.view_own

                                                                                                                                                          Showing [4] routes


  GET|HEAD   api/v1/admin/customer-portal ................................................. api.v1.admin.customer-portal.index › Api\V1\Admin\CustomerPortalController@index
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:system.manage_users
  GET|HEAD   api/v1/admin/customer-portal/conversations/{conversation} .... api.v1.admin.customer-portal.conversations.show › Api\V1\Admin\PortalConversationController@show
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:system.manage_users
  PATCH      api/v1/admin/customer-portal/conversations/{conversation} api.v1.admin.customer-portal.conversations.update › Api\V1\Admin\PortalConversationController@update
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:system.manage_users
  POST       api/v1/admin/customer-portal/conversations/{conversation}/reply api.v1.admin.customer-portal.conversations.reply › Api\V1\Admin\PortalConversationController@reply
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:portal-messages
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:system.manage_users
  POST       api/v1/admin/customer-portal/users ..................................... api.v1.admin.customer-portal.users.store › Api\V1\Admin\CustomerPortalController@store
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:system.manage_users
  GET|HEAD   api/v1/admin/customer-portal/users/{user} ................................ api.v1.admin.customer-portal.users.show › Api\V1\Admin\CustomerPortalController@show
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:system.manage_users
  POST       api/v1/admin/customer-portal/users/{user}/invite ..................... api.v1.admin.customer-portal.users.invite › Api\V1\Admin\CustomerPortalController@invite
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:system.manage_users
  POST       api/v1/admin/customer-portal/users/{user}/orders/link ........ api.v1.admin.customer-portal.users.orders.link › Api\V1\Admin\CustomerPortalController@linkOrder
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:system.manage_users
  DELETE     api/v1/admin/customer-portal/users/{user}/sessions . api.v1.admin.customer-portal.users.sessions.destroy › Api\V1\Admin\CustomerPortalController@revokeSessions
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ Illuminate\Routing\Middleware\SubstituteBindings
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:system.manage_users

                                                                                                                                                          Showing [9] routes

CUSTOMER_PORTAL_ACTIVE_ROUTE_COLLECTION=PASS_CUSTOMER_4_ADMIN_9_PERMISSION_THROTTLES
CUSTOMER_PORTAL_REQUIRED_TABLES=FAIL_MISSING_portal_activation_tokens
FAIL_REASON=CUSTOMER_PORTAL_RUNTIME_READ_ONLY_PROBE_FAILED
SOURCE_AUTO_RESTORED=YES
FAILED_STAGE=READ_ONLY_RUNTIME_PROBE
FAILED_LINE=714
BATCH33_RESULT=FAIL
MOBILE_V1_0_CUSTOMER_PORTAL_PARITY_BATCH33=FAIL
COMMITTED=0
PUSHED=0
ROUTE_CACHE_MUTATED=1
EXIT_CODE=1
REPORT=/home/icaffeco/ald1n-project/docs/operations/199-MOBILE-V1.0-CUSTOMER-PORTAL-PARITY-BATCH33-20260825-180056.md

============================================================
197 - MOBILE v1.0 FULL SAFE GITHUB CHECKPOINT - BATCH 32A
DATE=Tue Aug 25 17:27:47 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=REPORT_ONLY_CHECKPOINT_AFTER_AUTH_ACCOUNT_SECURITY_PARITY_BATCH32_PASS
CHECKPOINT_TYPE=REPORT_ONLY
CHECKPOINT_SCOPE=REPORT_196_ONLY
SOURCE_MUTATION=NO
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
STRICT_PARITY=47_OF_62_COMPLETE_STRICT_75_8_PERCENT
EXPECTED_PRE_CHECKPOINT_HEAD=e03068a7878bdf3a413128dd40dba24ace32cd86
REPORT_196=/home/icaffeco/ald1n-project/docs/operations/196-MOBILE-V1.0-AUTH-ACCOUNT-SECURITY-PARITY-BATCH32-20260825-171545.md
REPORT=/home/icaffeco/ald1n-project/docs/operations/197-MOBILE-V1.0-FULL-SAFE-GITHUB-CHECKPOINT-BATCH32A-20260825-172747.md
CONCURRENCY_LOCK=ACQUIRED
SOURCE_BRANCH=main
LOCAL_HEAD_PRE=e03068a7878bdf3a413128dd40dba24ace32cd86
REMOTE=origin
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
REMOTE_HEAD_PRE=e03068a7878bdf3a413128dd40dba24ace32cd86
REMOTE_SYNC_PRE=PASS
PRESTATE_TRACKED_WORKTREE=CLEAN
PRESTATE_REAL_GIT_INDEX=CLEAN
PRESTATE_UNTRACKED_ONLY_REPORT_196_PLUS_CURRENT_197=PASS
REPORT_196_SHA256_BEFORE=e2e2b9a0cb5f24c7cc7a8dff325f674480ea7a0928eb0a103735a1a8806a6053
REPORT_196_TRAILING_WHITESPACE_LINES_BEFORE=0
REPORT_196_TRAILING_WHITESPACE_LINES_AFTER=0
REPORT_196_WHITESPACE_NORMALIZATION=PASS_TRAILING_ONLY_IF_NEEDED_HASH_UNCHANGED
REPORT_196_EVIDENCE_CHAIN=PASS_AUTH_02_AUTH_03_ACCOUNT_02_COMPLETE_PARITY_47_OF_62
QUALITY_GATES_REUSED=PASS_FROM_REPORT_196_EXACT_SOURCE_HEAD_UNCHANGED
FEATURE_COMMIT_SCOPE=PASS_REPORTS_194_195_PLUS_20_SOURCE_FILES
FEATURE_COMMIT_PATH_COUNT=22

  POST       api/v1/auth/password/forgot ................................................................ api.v1.auth.password.forgot › Api\V1\AuthRecoveryController@forgot
             ⇂ api
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:password-reset-link
  POST       api/v1/auth/password/reset ................................................................... api.v1.auth.password.reset › Api\V1\AuthRecoveryController@reset
             ⇂ api
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:password-reset

                                                                                                                                                          Showing [2] routes


  GET|HEAD   api/v1/auth/customer-activation ......................................... api.v1.auth.customer-activation.state › Api\V1\AuthRecoveryController@activationState
             ⇂ api
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:customer-activation
  POST       api/v1/auth/customer-activation ................................................ api.v1.auth.customer-activation.store › Api\V1\AuthRecoveryController@activate
             ⇂ api
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:customer-activation

                                                                                                                                                          Showing [2] routes


  GET|HEAD       api/v1/me/sessions ........................................................................... api.v1.me.sessions.index › Api\V1\AccountController@sessions
                 ⇂ api
                 ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
                 ⇂ App\Http\Middleware\EnsureActiveUser
  DELETE         api/v1/me/sessions/others ........................................................ api.v1.me.sessions.others › Api\V1\AccountController@revokeOtherSessions
                 ⇂ api
                 ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
                 ⇂ App\Http\Middleware\EnsureActiveUser
                 ⇂ Illuminate\Routing\Middleware\ThrottleRequests:api-sensitive
  DELETE         api/v1/me/sessions/{kind}/{session} ................................................... api.v1.me.sessions.destroy › Api\V1\AccountController@revokeSession
                 ⇂ api
                 ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
                 ⇂ App\Http\Middleware\EnsureActiveUser
                 ⇂ Illuminate\Routing\Middleware\ThrottleRequests:api-sensitive

                                                                                                                                                          Showing [3] routes

FAILED_STAGE=ACTIVE_ROUTE_RECHECK
FAIL_REASON=ACCOUNT_SESSIONS_SANCTUM_MISSING
BATCH32A_RESULT=FAIL
MOBILE_V1_0_FULL_SAFE_GITHUB_CHECKPOINT_BATCH32A=FAIL
EXIT_CODE=1

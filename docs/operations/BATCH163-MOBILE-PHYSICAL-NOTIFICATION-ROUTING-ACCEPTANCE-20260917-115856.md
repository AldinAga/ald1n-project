# Batch163 - Physical notification routing acceptance
TIMESTAMP=20260917-115856
TERMINAL_OUTPUT_MODE=VERBOSE_LIVE_MIRROR_TO_TERMINAL_AND_REPORT
BUG=payment_overdue_notification_routes_superadmin_to_customer_order_404
SCOPE=read_only_production_authority_plus_physical_device_acceptance

============================================================
PREFLIGHT - BIND BATCH162 V5 PASS AUTHORITY
============================================================
REPORT_V5=/home/icaffeco/ald1n-project/docs/operations/BATCH162-V5-MOBILE-OPERATIONAL-NOTIFICATION-ADMIN-ORDER-ROUTING-UPDATE-VIEW-FLAG-RECOVERY-20260917-114858.md
REPORT_V5_SHA=19e5163431e4b590005feb690b7ce56e61ad6282d3271b83956418a855f65758
REPORT_V5_BINDING=PASS

============================================================
GIT AUTHORITY - READ ONLY
============================================================
RUN=git_fetch
COMMAND=git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch=0
BRANCH=main
LOCAL_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
REMOTE_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
STAGED_COUNT=0
RC_MOBILE_WORKTREE_DIFF=0
RC_SOURCE_FIX_ANCESTOR_MAIN=0
RC_MOBILE_TREE_EQUAL_SOURCE_FIX=0
MOBILE_TREE_EQUAL_TO_FIX_SOURCE=PASS
HTACCESS_FILE_SHA=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
KNOWN_HTACCESS_DRIFT=PASS

============================================================
SOURCE ROUTING SENTINELS - READ ONLY
============================================================
SOURCE_ROUTING_SENTINELS=PASS

============================================================
PRODUCTION OTA AUTHORITY - READ ONLY
============================================================
RUN=eas_version
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

eas-cli/23.2.0 linux-x64 node-v22.23.2
RC_eas_version=0
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

{
  "name": "production",
  "id": "019fff34-708f-7613-99e7-c68e72f58ff1",
  "currentPage": [
    {
      "branch": "production",
      "message": "\"Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858\" (7 minutes ago by ald1n)",
      "runtimeVersion": "1.0.0-build17",
      "isRollBackToEmbedded": false,
      "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
      "platforms": "android"
    }
  ]
}
RC_production_update_list=0
RUN=json_helper_syntax
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch163-20260917-115856/verify-production.mjs
RC_json_helper_syntax=0
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

[
  {
    "id": "01a0aec7-188f-78d1-8ad5-ea9a03fc1884",
    "createdAt": "2026-09-17T09:51:18.671Z",
    "group": "9a3774f5-99fe-4008-8c85-2cad8b5c5a2e",
    "branch": "production",
    "message": "Promote Batch162 V5 notification admin-order routing fix d73fde2 20260917-114858",
    "runtimeVersion": "1.0.0-build17",
    "platform": "android",
    "manifestPermalink": "https://u.expo.dev/update/01a0aec7-188f-78d1-8ad5-ea9a03fc1884",
    "isRollBackToEmbedded": false,
    "gitCommitHash": "277118631d11828162e6d2992dd02d9c1eaddaf6"
  }
]
RC_production_update_view=0
RUN=verify_production
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch163-20260917-115856/verify-production.mjs /home/icaffeco/.ald1n-batch163-20260917-115856/production-list.json /home/icaffeco/.ald1n-batch163-20260917-115856/production-view.json 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e 1.0.0-build17 277118631d11828162e6d2992dd02d9c1eaddaf6
PRODUCTION_GROUP_VERIFIED=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
PRODUCTION_RUNTIME_VERIFIED=1.0.0-build17
PRODUCTION_COMMIT_VERIFIED=277118631d11828162e6d2992dd02d9c1eaddaf6
RC_verify_production=0
PRODUCTION_OTA_AUTHORITY=PASS_READ_ONLY
EAS_UPDATE_VIEW_FLAG_POLICY=JSON_ONLY_NO_NON_INTERACTIVE_FOR_23_2_0

============================================================
PHYSICAL DEVICE ACCEPTANCE - ORIGINAL REPORTED BUG
============================================================
This stage does NOT create orders, payments, receivables, notifications, builds, or OTA updates.
Marking an existing inbox notification as read is an expected normal app-side effect.

Device model [Galaxy S26 Ultra]: DEVICE_MODEL_SELECTED=Galaxy S26 Ultra
Android version [16]: ANDROID_VERSION_SELECTED=16

PHYSICAL STEPS:
1. Force-close Ald1n CMS on the phone.
2. Open Ald1n CMS and leave it open briefly so the production Build17 OTA can be fetched.
3. Force-close it again, then reopen it.
4. Open Obaveštenja.
5. Find the existing notification 'Dospelo neplaćeno potraživanje'. It may already be marked as read; that is fine.
6. Tap that notification.
7. Expected: Admin order detail opens. It must NOT open the customer /order detail and must NOT show 404 / 'Traženi resurs nije pronađen'.
8. Confirm the admin order page actually renders useful order content and Back navigation works.

Did tapping 'Dospelo neplaćeno potraživanje' open the ADMIN order detail? [PASS/FAIL]: INBOX_PAYMENT_OVERDUE=PASS
Did the admin order detail render real order content and remain usable? [PASS/FAIL]: ADMIN_DETAIL_CONTENT=PASS
Was the previous 404 / 'Traženi resurs nije pronađen' completely absent? [PASS/FAIL]: NO_404=PASS

============================================================
OPTIONAL PHYSICAL REGRESSION SMOKE
============================================================
These checks are optional because the corresponding live notifications may not currently exist.
If an ordinary customer/order notification is available, does it still open the normal order detail correctly? Use NA if none exists. [PASS/FAIL/NA]: NORMAL_ORDER_REGRESSION=PASS
If the same/another operational order push is still present in the Android notification tray, does tapping it open Admin order detail? Use NA if none exists. [PASS/FAIL/NA]: PUSH_OPERATIONAL_REGRESSION=PASS

============================================================
FINAL READ-ONLY GIT RECHECK
============================================================
RUN=final_git_fetch
COMMAND=git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_final_git_fetch=0
FINAL_LOCAL_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
FINAL_REMOTE_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
RC_FINAL_MOBILE_WORKTREE_DIFF=0

============================================================
FINAL SUMMARY
============================================================
BATCH163_RESULT=PASS_PHYSICAL_NOTIFICATION_ROUTING_ACCEPTANCE
FAILED_STAGE=NONE
SOURCE_MUTATION=NO
COMMIT_CREATED=NO
PUSH_COMPLETED=NO_NEW_PUSH
OTA_MUTATION=NO
PRODUCTION_OTA_GROUP=9a3774f5-99fe-4008-8c85-2cad8b5c5a2e
OTA_RUNTIME=1.0.0-build17
SOURCE_FIX_COMMIT=d73fde25892c984a23486125dd0f516008a782bd
MAIN_HEAD=277118631d11828162e6d2992dd02d9c1eaddaf6
DEVICE_MODEL=Galaxy S26 Ultra
ANDROID_VERSION=16
INBOX_PAYMENT_OVERDUE=PASS
ADMIN_DETAIL_CONTENT=PASS
NO_404=PASS
NORMAL_ORDER_REGRESSION=PASS
PUSH_OPERATIONAL_REGRESSION=PASS
BUILD18=NO
GOOGLE_PLAY_ACTION=NO
ORIGINAL_BUG_REPRODUCED_AFTER_FIX=NO
NEXT_ACTION=RUN_BATCH164_POST_NOTIFICATION_FIX_CHECKPOINT_AND_GUARDRAIL_UPDATE
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/BATCH163-MOBILE-PHYSICAL-NOTIFICATION-ROUTING-ACCEPTANCE-20260917-115856.md


============================================================
462 - BATCH170 TASK3 V2 ROUTE CACHE TARGETED RECOVERY
============================================================
TIMESTAMP=20260919-143437
EXPECTED_HEAD=16d8ea40a6819a409fd855513a2cfe77272b1c82
RECOVERY_OF_REPORT=461
RECOVERY_OF_FAILED_STAGE=TASK3_TDD_GREEN
ROOT_CAUSE_HYPOTHESIS=STALE_LARAVEL_ROUTE_CACHE
EXECUTION_METHOD=NATIVE_OWNER_APPROVED
TASK=3_CRM_MUTATION_CONTROLLER_ROUTES
IMPLEMENTATION_ACTION=YES_CONTINUE_UNCOMMITTED_TASK3
API_SURFACE_CHANGE=YES_TASK3_LARAVEL
OPENAPI_PARITY=PENDING_MANDATORY_TASK4_AFTER_TASK3_PASS
MOBILE_CONTRACT_PARITY=PENDING_MANDATORY_TASK5_AFTER_TASK4
MOBILE_VISIBLE_WORKSPACE=PENDING_MANDATORY_BATCH171
PERSISTENT_DATABASE_WRITES=NO_TEST_FIXTURES_TRANSACTION_ROLLBACK
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO

============================================================
0. PREFLIGHT - BIND REPORT461 AND EXACT FAILED TASK3 WORKTREE
============================================================

============================================================
RUN - git_fetch_preflight
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_preflight=0
BRANCH=main
LOCAL_HEAD=16d8ea40a6819a409fd855513a2cfe77272b1c82
REMOTE_HEAD=16d8ea40a6819a409fd855513a2cfe77272b1c82
REPORT461_SHA_ACTUAL=c0c074e83380898666c2a1563f87e1ad441a0dbe91b57c3d0433e3beeb68866d
REPORT461_SHA_EXPECTED=c0c074e83380898666c2a1563f87e1ad441a0dbe91b57c3d0433e3beeb68866d
SPEC_SHA_ACTUAL=e0a239cded5e07058b18f2a4893bd8c375b515e30113cbc14e28634d0c5a98ce
PLAN_SHA_ACTUAL=c4cdecb459f201f6c87bd4da3f2eea7c7bc971e7654cbe913c1d88a077675768
CUSTOMER360_SERVICE_SHA_ACTUAL=6f5e1c6f6a0af10bfb3d76e11adf9a1c776c14f9fce7526e546ae848e2714271
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalAdminService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CustomerPortalController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/api.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/bin/customer-360-contract-smoke.php
HTACCESS_SHA_ACTUAL=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA_ACTUAL=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
PREFLIGHT_MACHINE_MANIFESTS=PASS_EXACT_REPORT461_FAILED_STATE

============================================================
1. ARCHIVE REPORT461 FAIL EVIDENCE AND ROTATE REPORT460
============================================================
rm 'docs/operations/460-BATCH170-TASK2-V2-NULL-ASSERTION-RECOVERY-20260919-140949.md'
D	docs/operations/460-BATCH170-TASK2-V2-NULL-ASSERTION-RECOVERY-20260919-140949.md
A	docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md
EVIDENCE_STAGE_SCOPE=PASS_EXACT_REPORT_ROTATION
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:54: trailing whitespace.
++++++COMMAND=git fetch origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:56: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:58: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:60: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:62: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:64: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:66: trailing whitespace.
++++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:68: trailing whitespace.
++++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:70: trailing whitespace.
++++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:72: trailing whitespace.
++++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:74: trailing whitespace.
++++++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:76: trailing whitespace.
+++++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:78: trailing whitespace.
+++++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:80: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:82: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:84: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:86: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:88: trailing whitespace.
++++++++++++++++⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:90: trailing whitespace.
++++++++++++++++⠋ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:92: trailing whitespace.
++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:94: trailing whitespace.
++++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:96: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view --help 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:98: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:100: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas whoami 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:102: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:104: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:106: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas --version 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:108: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas whoami 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:110: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:112: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:114: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update --help 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:116: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:republish --help 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:118: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:120: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:122: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:124: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:126: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:128: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:130: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:132: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:134: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:136: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:138: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:140: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:142: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:144: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:146: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:148: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:150: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:152: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:154: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:156: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:158: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:160: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:162: trailing whitespace.
+++++++                                                                                                                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:164: trailing whitespace.
+++++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT' (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: select `id`, `pa  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:166: trailing whitespace.
+++++++  th` from `backup_runs` where `status` = completed order by `id` desc)                                                                                                      
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:168: trailing whitespace.
+++++++                                                                                                                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:170: trailing whitespace.
+++++++                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:172: trailing whitespace.
+++++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT'  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:174: trailing whitespace.
+++++++                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:176: trailing whitespace.
+++++++COMMAND=git fetch origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:178: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:180: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:182: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:184: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:186: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:188: trailing whitespace.
++++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:190: trailing whitespace.
++++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:192: trailing whitespace.
++++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:194: trailing whitespace.
++++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:196: trailing whitespace.
++++++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:198: trailing whitespace.
+++++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:200: trailing whitespace.
+++++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:202: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:204: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:206: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:208: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:210: trailing whitespace.
++++++++++++++++⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:212: trailing whitespace.
++++++++++++++++⠋ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:214: trailing whitespace.
++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:216: trailing whitespace.
++++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:218: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view --help 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:220: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:222: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas whoami 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:224: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:226: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:228: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas --version 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:230: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas whoami 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:232: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:234: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:236: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update --help 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:238: trailing whitespace.
+++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:republish --help 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:240: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:242: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:244: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:246: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:248: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:250: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:252: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:254: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:256: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:258: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:260: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:262: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:264: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:266: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:268: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:270: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:272: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:274: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:276: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:278: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:280: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:282: trailing whitespace.
+++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:284: trailing whitespace.
+++++++                                                                                                                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:286: trailing whitespace.
+++++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT' (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: select `id`, `pa  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:288: trailing whitespace.
+++++++  th` from `backup_runs` where `status` = completed order by `id` desc)                                                                                                      
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:290: trailing whitespace.
+++++++                                                                                                                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:292: trailing whitespace.
+++++++                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:294: trailing whitespace.
+++++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT'  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:296: trailing whitespace.
+++++++                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:298: trailing whitespace.
+++++++COMMAND=git fetch origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:300: trailing whitespace.
+++++++COMMAND=git fetch origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:302: trailing whitespace.
+++++++COMMAND=php /home/icaffeco/.ald1n-batch170-v5-20260919-122715/backup-state.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:304: trailing whitespace.
+++++++COMMAND=php artisan app:backup-verify --run=116 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:306: trailing whitespace.
+++++++COMMAND=php artisan app:backup-verify --run=115 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:308: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:310: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:312: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:314: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:316: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:318: trailing whitespace.
+++++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:320: trailing whitespace.
+++++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:322: trailing whitespace.
+++++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:324: trailing whitespace.
+++++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:326: trailing whitespace.
+++++++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:328: trailing whitespace.
++++++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:330: trailing whitespace.
++++++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:332: trailing whitespace.
+++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:334: trailing whitespace.
+++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:336: trailing whitespace.
+++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:338: trailing whitespace.
+++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:340: trailing whitespace.
+++++++++++++++++⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:342: trailing whitespace.
+++++++++++++++++⠋ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:344: trailing whitespace.
+++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:346: trailing whitespace.
+++++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:348: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view --help 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:350: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:352: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas whoami 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:354: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:356: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:358: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas --version 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:360: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas whoami 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:362: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:364: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:366: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update --help 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:368: trailing whitespace.
++++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:republish --help 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:370: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:372: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:374: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:376: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:378: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:380: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:382: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:384: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:386: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:388: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:390: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:392: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:394: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:396: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:398: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:400: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:402: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:404: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:406: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:408: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:410: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:412: trailing whitespace.
++++++++++++++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:414: trailing whitespace.
++++++++                                                                                                                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:416: trailing whitespace.
++++++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT' (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: select `id`, `pa  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:418: trailing whitespace.
++++++++  th` from `backup_runs` where `status` = completed order by `id` desc)                                                                                                      
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:420: trailing whitespace.
++++++++                                                                                                                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:422: trailing whitespace.
++++++++                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:424: trailing whitespace.
++++++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT'  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:426: trailing whitespace.
++++++++                                                                             
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:428: trailing whitespace.
++++++++COMMAND=git fetch origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:430: trailing whitespace.
++++++COMMAND=git fetch origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:432: trailing whitespace.
++++++COMMAND=git commit -m docs:\ archive\ Customer360\ checkpoint\ recovery\ evidence 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:434: trailing whitespace.
++++++COMMAND=git push origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:436: trailing whitespace.
++++++COMMAND=git fetch origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:438: trailing whitespace.
++++++COMMAND=php /home/icaffeco/.ald1n-batch170-v6-20260919-123753/patch-agents.php /home/icaffeco/ald1n-project/AGENTS.md 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:440: trailing whitespace.
++++++COMMAND=git fetch origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:442: trailing whitespace.
++++++COMMAND=git commit -m docs:\ lock\ Customer360\ architecture\ and\ checkpoint\ policies 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:444: trailing whitespace.
++++++COMMAND=git push origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:446: trailing whitespace.
++++++COMMAND=git fetch origin main 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:448: trailing whitespace.
++++   INFO  Running migrations.  
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:450: trailing whitespace.
++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:452: trailing whitespace.
++++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:454: trailing whitespace.
++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:456: trailing whitespace.
++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:458: trailing whitespace.
++ 
docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md:460: trailing whitespace.
++ 
RC_evidence_full_diffcheck=2
RC_evidence_nonreport_diffcheck=0

============================================================
RUN - git_fetch_evidence_race
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_evidence_race=0

============================================================
RUN - evidence_commit
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git commit -m docs: archive Customer360 Task3 route-cache failure
[main e68253a] docs: archive Customer360 Task3 route-cache failure
 2 files changed, 733 insertions(+), 2355 deletions(-)
 delete mode 100644 docs/operations/460-BATCH170-TASK2-V2-NULL-ASSERTION-RECOVERY-20260919-140949.md
 create mode 100644 docs/operations/461-BATCH170-TASK3-CRM-MUTATION-CONTROLLER-ROUTES-20260919-142901.md
RC_evidence_commit=0

============================================================
RUN - evidence_push
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git push origin main
To github.com:AldinAga/ald1n-project.git
   16d8ea4..e68253a  main -> main
RC_evidence_push=0

============================================================
RUN - evidence_postfetch
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_evidence_postfetch=0
EVIDENCE_COMMIT=e68253aa8a7cff13c6ddd2a338d2b11062d50153

============================================================
2. TDD RED - REPRODUCE REPORT461 ROUTE-ONLY FAILURE
============================================================

============================================================
RUN - customer360_task3_recovery_red
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php bin/customer-360-contract-smoke.php
PASS customer_crm_notes table exists
PASS CustomerCrmNote model exists
PASS User exposes crmNotes HasMany relation
PASS CustomerCrmNote maps explicit table
PASS CustomerCrmNote fillable is exact
PASS CustomerCrmNote foreign IDs cast to integer
PASS CustomerCrmNote customer relation uses user_id
PASS CustomerCrmNote author relation uses author_user_id
PASS CustomerCrmNote has no soft delete contract
PASS customer_crm_notes has no deleted_at column
PASS No CRM note update route exists
PASS No CRM note delete route exists
PASS Customer360Service exists
PASS Customer360Service exposes build
PASS Customer360Service exposes unlinkedBuyers
PASS Customer360 timeline is server-capped at 50
PASS Customer360 read model has no profitability placeholders
PASS Customer360 excludes cancelled and archived orders
PASS Customer360 lifetime revenue sums qualifying subtotal
PASS Customer360 average order value is revenue divided by qualifying orders
PASS Customer360 outstanding clamps overpaid order at zero
PASS Customer360 last purchase uses newest qualifying order
PASS Customer360 counts active after-sales cases
PASS Customer360 counts active warranties
PASS Customer360 counts open portal conversations
PASS Customer360 counts unread customer-originated staff messages
PASS Customer360 no-order customer has zero orders
PASS Customer360 no-order customer has zero average order value
PASS Customer360 no-order customer has null last purchase
PASS Customer360 timeline is capped at 50 events
PASS Customer360 timeline uses canonical event shape
PASS Customer360 timeline merges canonical event sources
PASS Customer360 returns internal CRM notes
PASS Customer360 unlinked buyers includes operational user_id null order
PASS Customer360 unlinked buyers excludes linked order
PASS Customer360 unlinked buyers excludes archived order
PASS Customer360 unlinked buyers exposes no inferred matching fields
PASS Customer360 unlinked buyers does not leak ownership suggestion
PASS Customer360 unlinked buyers returns canonical order facts only
PASS CustomerPortalAdminService exposes appendCrmNote
PASS Admin customer portal exposes storeCrmNote action
PASS Admin customer portal exposes unlinkedBuyers action
PASS Customer detail includes Customer360 payload
PASS Customer detail preserves legacy payload keys
PASS CRM note controller returns HTTP 201
FAIL CRM note POST route exists
FAIL CRM note POST route URI is canonical
FAIL CRM note POST route keeps system.manage_users permission
FAIL CRM note POST route uses admin-write throttle
FAIL Unlinked buyers GET route exists
FAIL Unlinked buyers GET route URI is canonical
FAIL Unlinked buyers GET route keeps system.manage_users permission
PASS Existing explicit linkOrder ownership route remains unchanged
PASS No parallel CRM route namespace exists
PASS CRM note append trims body and creates exactly one note
PASS CRM note append records author identity
PASS CRM note append emits customer_crm.note_created audit marker
PASS CRM note audit stores IDs and length without duplicating note body
PASS CRM note append rejects body shorter than 2 characters
PASS CRM note append rejects body longer than 5000 characters
PASS CRM note append rejects non-customer target
CUSTOMER360_CONTRACT_SMOKE=61_CHECKS_54_PASS_7_FAIL
RC_customer360_task3_recovery_red=1
TDD_RED=PASS_REPRODUCED_REPORT461_54_61_ROUTE_ONLY_FAILURE

============================================================
3. ROOT CAUSE PROOF - SOURCE HAS ROUTES BUT RUNTIME USES STALE ROUTE CACHE
============================================================
No syntax errors detected in /home/icaffeco/.ald1n-batch170-task3-v2-20260919-143437/route-cache-state.php

============================================================
RUN - route_cache_before
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php /home/icaffeco/.ald1n-batch170-task3-v2-20260919-143437/route-cache-state.php /home/icaffeco/ald1n-project/apps/cms/current
RC_route_cache_before=255

============================================================
FINAL SUMMARY
============================================================
BATCH170_TASK3_V2_RESULT=FAIL
REPORT_NUMBER=462
FAILED_STAGE=ROUTE_CACHE_DIAGNOSIS
FAIL_MESSAGE=Could not inspect route cache state
EVIDENCE_COMMIT=e68253aa8a7cff13c6ddd2a338d2b11062d50153
EVIDENCE_PUSH=YES
TASK3_SOURCE_COMMIT=NONE
TASK3_SOURCE_PUSH=NO
ROUTE_CACHE_REFRESH=NOT_RUN
API_SURFACE_CHANGE=IN_PROGRESS_OR_NONE_AT_FAILURE
OPENAPI_PARITY=PENDING_MANDATORY_TASK4
MOBILE_CONTRACT_PARITY=PENDING_MANDATORY_TASK5
MOBILE_VISIBLE_WORKSPACE=PENDING_MANDATORY_BATCH171
CUSTOMER360_OVERALL_FEATURE_COMPLETE=NO
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO
NEXT_ACTION=TARGETED_RECOVERY_FROM_RECORDED_FAILED_STAGE
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/462-BATCH170-TASK3-V2-ROUTE-CACHE-RECOVERY-20260919-143437.md

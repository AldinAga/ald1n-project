
============================================================
460 - BATCH170 TASK2 V2 NULL ASSERTION TARGETED RECOVERY
============================================================
TIMESTAMP=20260919-140949
EXPECTED_HEAD=15b416e1bc50e6195ba30a2b69d7ad26d16715de
RECOVERY_OF_REPORT=459
RECOVERY_OF_FAILED_STAGE=TASK2_TDD_GREEN
ROOT_CAUSE=PHP_NULL_COALESCING_OPERATOR_REPLACED_VALID_NULL_WITH_TEST_FALLBACK
RECOVERY_STRATEGY=REPRODUCE_38_39_THEN_PATCH_ONLY_ASSERTION_AND_REQUIRE_39_39
EXECUTION_METHOD=NATIVE_OWNER_APPROVED
TASK=2_CUSTOMER360_READ_MODEL_SERVICE
IMPLEMENTATION_ACTION=YES_CONTINUE_UNCOMMITTED_TASK2
API_SURFACE_CHANGE=NO
MOBILE_CONTRACT_CHANGE=NO_NOT_APPLICABLE_UNTIL_TASK3_HTTP_CONTRACT
PERSISTENT_DATABASE_WRITES=NO_FIXTURES_TRANSACTION_ROLLBACK
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO

============================================================
0. PREFLIGHT - BIND REPORT459 AND EXACT FAILED WORKTREE
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
LOCAL_HEAD=15b416e1bc50e6195ba30a2b69d7ad26d16715de
REMOTE_HEAD=15b416e1bc50e6195ba30a2b69d7ad26d16715de
REPORT459_SHA_ACTUAL=938a8b4a6f04263f03687ddfadefdcf53b5e1484b676e32af3622d01e17b70eb
REPORT459_SHA_EXPECTED=938a8b4a6f04263f03687ddfadefdcf53b5e1484b676e32af3622d01e17b70eb
SPEC_SHA_ACTUAL=e0a239cded5e07058b18f2a4893bd8c375b515e30113cbc14e28634d0c5a98ce
PLAN_SHA_ACTUAL=c4cdecb459f201f6c87bd4da3f2eea7c7bc971e7654cbe913c1d88a077675768
CUSTOMER360_SERVICE_SHA_ACTUAL=6f5e1c6f6a0af10bfb3d76e11adf9a1c776c14f9fce7526e546ae848e2714271
CUSTOMER360_SERVICE_SHA_EXPECTED=6f5e1c6f6a0af10bfb3d76e11adf9a1c776c14f9fce7526e546ae848e2714271
CUSTOMER360_SMOKE_FAILED_SHA_ACTUAL=051bac71f35830b36772023460aaa3182125aa960cf577d7d0e2eea0dd53905a
CUSTOMER360_SMOKE_FAILED_SHA_EXPECTED=051bac71f35830b36772023460aaa3182125aa960cf577d7d0e2eea0dd53905a
HTACCESS_SHA_ACTUAL=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA_ACTUAL=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
PREFLIGHT_MACHINE_MANIFESTS=PASS_EXACT_REPORT459_FAILED_STATE

============================================================
1. ARCHIVE REPORT459 FAIL EVIDENCE AND ROTATE REPORT458
============================================================
rm 'docs/operations/458-BATCH170-TASK1-V2-PLAN-WRITE-RECOVERY-20260919-134207.md'
D	docs/operations/458-BATCH170-TASK1-V2-PLAN-WRITE-RECOVERY-20260919-134207.md
A	docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md
EVIDENCE_STAGE_SCOPE=PASS_EXACT_REPORT_ROTATION
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:53: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:55: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:57: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:59: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:61: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:63: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:65: trailing whitespace.
++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:67: trailing whitespace.
++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:69: trailing whitespace.
++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:71: trailing whitespace.
++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:73: trailing whitespace.
++++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:75: trailing whitespace.
+++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:77: trailing whitespace.
+++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:79: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:81: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:83: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:85: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:87: trailing whitespace.
++++++++++++++⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:89: trailing whitespace.
++++++++++++++⠋ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:91: trailing whitespace.
++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:93: trailing whitespace.
++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:95: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view --help 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:97: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:99: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas whoami 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:101: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:103: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:105: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas --version 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:107: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas whoami 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:109: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:111: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:113: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update --help 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:115: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:republish --help 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:117: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:119: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:121: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:123: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:125: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:127: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:129: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:131: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:133: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:135: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:137: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:139: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:141: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:143: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:145: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:147: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:149: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:151: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:153: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:155: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:157: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:159: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:161: trailing whitespace.
+++++                                                                                                                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:163: trailing whitespace.
+++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT' (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: select `id`, `pa  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:165: trailing whitespace.
+++++  th` from `backup_runs` where `status` = completed order by `id` desc)                                                                                                      
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:167: trailing whitespace.
+++++                                                                                                                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:169: trailing whitespace.
+++++                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:171: trailing whitespace.
+++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT'  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:173: trailing whitespace.
+++++                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:175: trailing whitespace.
+++++COMMAND=git fetch origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:177: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:179: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:181: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:183: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:185: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:187: trailing whitespace.
++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:189: trailing whitespace.
++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:191: trailing whitespace.
++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:193: trailing whitespace.
++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:195: trailing whitespace.
++++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:197: trailing whitespace.
+++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:199: trailing whitespace.
+++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:201: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:203: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:205: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:207: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:209: trailing whitespace.
++++++++++++++⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:211: trailing whitespace.
++++++++++++++⠋ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:213: trailing whitespace.
++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:215: trailing whitespace.
++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:217: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view --help 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:219: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:221: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas whoami 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:223: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:225: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:227: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas --version 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:229: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas whoami 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:231: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:233: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:235: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update --help 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:237: trailing whitespace.
+++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:republish --help 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:239: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:241: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:243: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:245: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:247: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:249: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:251: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:253: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:255: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:257: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:259: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:261: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:263: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:265: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:267: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:269: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:271: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:273: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:275: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:277: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:279: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:281: trailing whitespace.
+++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:283: trailing whitespace.
+++++                                                                                                                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:285: trailing whitespace.
+++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT' (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: select `id`, `pa  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:287: trailing whitespace.
+++++  th` from `backup_runs` where `status` = completed order by `id` desc)                                                                                                      
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:289: trailing whitespace.
+++++                                                                                                                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:291: trailing whitespace.
+++++                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:293: trailing whitespace.
+++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT'  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:295: trailing whitespace.
+++++                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:297: trailing whitespace.
+++++COMMAND=git fetch origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:299: trailing whitespace.
+++++COMMAND=git fetch origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:301: trailing whitespace.
+++++COMMAND=php /home/icaffeco/.ald1n-batch170-v5-20260919-122715/backup-state.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:303: trailing whitespace.
+++++COMMAND=php artisan app:backup-verify --run=116 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:305: trailing whitespace.
+++++COMMAND=php artisan app:backup-verify --run=115 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:307: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:309: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:311: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:313: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:315: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:317: trailing whitespace.
+++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:319: trailing whitespace.
+++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:321: trailing whitespace.
+++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:323: trailing whitespace.
+++++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:325: trailing whitespace.
+++++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:327: trailing whitespace.
++++++++++++++++   INFO  Compiled views cleared successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:329: trailing whitespace.
++++++++++++++++   INFO  Blade templates cached successfully.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:331: trailing whitespace.
+++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:333: trailing whitespace.
+++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:335: trailing whitespace.
+++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:337: trailing whitespace.
+++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:339: trailing whitespace.
+++++++++++++++⠋ Exporting...⠙ Exporting...⠹ Exporting...⠸ Exporting...⠼ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:341: trailing whitespace.
+++++++++++++++⠋ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:343: trailing whitespace.
+++++++++++++++⠋ Exporting...⠙ Exporting...[expo-cli] 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:345: trailing whitespace.
+++++++++++++++⠋ Uploading...⠙ Uploading...⠹ Uploading...⠋ Uploading assetmap.json (0 / 11.0 KB)⠸ Uploading...⠙ Uploading assetmap.json (11.0 KB / 11.0 KB)⠼ Uploading...⠹ Uploading assetmap.json (11.0 KB / 11.0 KB)⠴ Uploading...⠸ Uploading assetmap.json (11.0 KB / 11.0 KB)⠦ Uploading...⠼ Uploading assetmap.json (11.0 KB / 11.0 KB)⠧ Uploading...✔ Uploaded assetmap.json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:347: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view --help 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:349: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas --version 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:351: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas whoami 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:353: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:355: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@23.2.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:357: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas --version 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:359: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas whoami 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:361: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:list --branch production --limit 1 --json --non-interactive 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:363: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:view 9a3774f5-99fe-4008-8c85-2cad8b5c5a2e --json 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:365: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update --help 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:367: trailing whitespace.
++++++++++++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package eas-cli@24.7.0 -- eas update:republish --help 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:369: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:371: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:373: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:375: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:377: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:379: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:381: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:383: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:385: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:387: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:389: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:391: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:393: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:395: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:397: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:399: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:401: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:403: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:405: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:407: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:409: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:411: trailing whitespace.
++++++++++++++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:413: trailing whitespace.
++++++                                                                                                                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:415: trailing whitespace.
++++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT' (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: select `id`, `pa  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:417: trailing whitespace.
++++++  th` from `backup_runs` where `status` = completed order by `id` desc)                                                                                                      
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:419: trailing whitespace.
++++++                                                                                                                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:421: trailing whitespace.
++++++                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:423: trailing whitespace.
++++++  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'path' in 'SELECT'  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:425: trailing whitespace.
++++++                                                                             
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:427: trailing whitespace.
++++++COMMAND=git fetch origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:429: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:431: trailing whitespace.
++++COMMAND=git commit -m docs:\ archive\ Customer360\ checkpoint\ recovery\ evidence 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:433: trailing whitespace.
++++COMMAND=git push origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:435: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:437: trailing whitespace.
++++COMMAND=php /home/icaffeco/.ald1n-batch170-v6-20260919-123753/patch-agents.php /home/icaffeco/ald1n-project/AGENTS.md 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:439: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:441: trailing whitespace.
++++COMMAND=git commit -m docs:\ lock\ Customer360\ architecture\ and\ checkpoint\ policies 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:443: trailing whitespace.
++++COMMAND=git push origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:445: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:447: trailing whitespace.
++   INFO  Running migrations.  
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:449: trailing whitespace.
++ 
docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md:451: trailing whitespace.
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
COMMAND=git commit -m docs: archive Customer360 Task2 test failure
[main 572ff49] docs: archive Customer360 Task2 test failure
 2 files changed, 656 insertions(+), 2958 deletions(-)
 delete mode 100644 docs/operations/458-BATCH170-TASK1-V2-PLAN-WRITE-RECOVERY-20260919-134207.md
 create mode 100644 docs/operations/459-BATCH170-TASK2-CUSTOMER360-READ-MODEL-20260919-140334.md
RC_evidence_commit=0

============================================================
RUN - evidence_push
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git push origin main
To github.com:AldinAga/ald1n-project.git
   15b416e..572ff49  main -> main
RC_evidence_push=0

============================================================
RUN - evidence_postfetch
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_evidence_postfetch=0
EVIDENCE_COMMIT=572ff49075d2faee25fdd80b0b7cdb1b5402c70e

============================================================
2. ROOT-CAUSE PROOF - NULL COALESCING ASSERTION IS LOGICALLY WRONG
============================================================
No syntax errors detected in /home/icaffeco/.ald1n-batch170-task2-v2-20260919-140949/null-assertion-proof.php

============================================================
RUN - null_assertion_proof
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch170-task2-v2-20260919-140949/null-assertion-proof.php
BROKEN_ASSERT_RESULT=FALSE
FIXED_ASSERT_RESULT=TRUE
RC_null_assertion_proof=0
ROOT_CAUSE_CONFIRMED=TEST_ASSERTION_USED_NULL_COALESCING_WHICH_REPLACES_NULL_WITH_FALLBACK
No syntax errors detected in /home/icaffeco/.ald1n-batch170-task2-v2-20260919-140949/db-state.php

============================================================
RUN - db_state_before_recovery
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php /home/icaffeco/.ald1n-batch170-task2-v2-20260919-140949/db-state.php /home/icaffeco/ald1n-project/apps/cms/current
USERS_COUNT=19
ORDERS_COUNT=27
CUSTOMER_CRM_NOTES_COUNT=0
AFTER_SALES_CASES_COUNT=0
PRODUCT_WARRANTIES_COUNT=30
PORTAL_CONVERSATIONS_COUNT=0
PORTAL_MESSAGES_COUNT=0
PORTAL_ORDER_LINK_HISTORY_COUNT=0
RC_db_state_before_recovery=0

============================================================
3. TDD RED - REPRODUCE REPORT459 38 OF 39 FAILURE
============================================================

============================================================
RUN - customer360_task2_recovery_red
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
FAIL Customer360 no-order customer has null last purchase
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
CUSTOMER360_CONTRACT_SMOKE=39_CHECKS_38_PASS_1_FAIL
RC_customer360_task2_recovery_red=1
TDD_RED=PASS_REPRODUCED_REPORT459_38_39

============================================================
4. GREEN FIX - PATCH ONLY THE INVALID TEST ASSERTION
============================================================
No syntax errors detected in /home/icaffeco/.ald1n-batch170-task2-v2-20260919-140949/patch-smoke.php

============================================================
RUN - patch_smoke_null_assertion
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch170-task2-v2-20260919-140949/patch-smoke.php /home/icaffeco/ald1n-project/apps/cms/current/bin/customer-360-contract-smoke.php
SMOKE_NULL_ASSERTION_PATCH=PASS_EXACTLY_ONCE
RC_patch_smoke_null_assertion=0
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/bin/customer-360-contract-smoke.php
CUSTOMER360_SERVICE_SHA_AFTER_PATCH=6f5e1c6f6a0af10bfb3d76e11adf9a1c776c14f9fce7526e546ae848e2714271
CUSTOMER360_SMOKE_SHA_AFTER_PATCH=a3b0ebe84c33939926861bba645d79ca7b99092903c6a066a13eb0c7c6f34a25
CUSTOMER360_SMOKE_SHA_FIXED_EXPECTED=a3b0ebe84c33939926861bba645d79ca7b99092903c6a066a13eb0c7c6f34a25
SERVICE_PRODUCTION_BEHAVIOR_CHANGE=NO_SHA_IDENTICAL

============================================================
5. TDD GREEN - REQUIRE ALL 39 CUSTOMER360 CHECKS
============================================================

============================================================
RUN - customer360_task2_recovery_green
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
CUSTOMER360_CONTRACT_SMOKE=39_CHECKS_39_PASS_0_FAIL
RC_customer360_task2_recovery_green=0
TDD_GREEN=PASS_39_39

============================================================
RUN - db_state_after_recovery
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php /home/icaffeco/.ald1n-batch170-task2-v2-20260919-140949/db-state.php /home/icaffeco/ald1n-project/apps/cms/current
USERS_COUNT=19
ORDERS_COUNT=27
CUSTOMER_CRM_NOTES_COUNT=0
AFTER_SALES_CASES_COUNT=0
PRODUCT_WARRANTIES_COUNT=30
PORTAL_CONVERSATIONS_COUNT=0
PORTAL_MESSAGES_COUNT=0
PORTAL_ORDER_LINK_HISTORY_COUNT=0
RC_db_state_after_recovery=0
TASK2_FIXTURE_ROLLBACK=PASS_COUNTS_IDENTICAL

============================================================
6. CMS STATIC, OPENAPI PARITY, TASK SCOPE GUARDS
============================================================

============================================================
RUN - cms_static_green
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php bin/static-check.php
PASS  postoji artisan
PASS  postoji composer.json
PASS  postoji composer.lock
PASS  postoji .env.example
PASS  postoji VERSION
PASS  postoji RELEASE-TAG
PASS  postoji UPGRADE-FROM
PASS  postoji docs/UPGRADE-V2.1-BETA1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA4.md
PASS  postoji docs/UPGRADE-V2.1-BETA5.md
PASS  postoji docs/UPGRADE-V2.1-BETA6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.4.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.5.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.8.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.9.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.10.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.11.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.12.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.13.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.15.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.16.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.20.md
PASS  postoji DATABASE-MIGRATION-REQUIRED.txt
PASS  postoji app/Models/Order.php
PASS  postoji app/Models/OrderItem.php
PASS  postoji app/Models/OrderDocument.php
PASS  postoji app/Models/OrderCommission.php
PASS  postoji app/Models/CommissionStatusHistory.php
PASS  postoji app/Models/CommissionPaymentBatch.php
PASS  postoji app/Models/OrderInternalNote.php
PASS  postoji app/Models/OrderAssignment.php
PASS  postoji app/Models/OrderStatusHistory.php
PASS  postoji app/Models/StockMovement.php
PASS  postoji app/Models/IdempotencyKey.php
PASS  postoji app/Models/OrderPayment.php
PASS  postoji app/Models/OrderDelivery.php
PASS  postoji app/Models/AfterSalesCase.php
PASS  postoji app/Models/AfterSalesCaseItem.php
PASS  postoji app/Models/AfterSalesMessage.php
PASS  postoji app/Models/AfterSalesAttachment.php
PASS  postoji app/Models/AfterSalesStatusHistory.php
PASS  postoji app/Models/AfterSalesAction.php
PASS  postoji app/Models/AfterSalesActionItem.php
PASS  postoji app/Models/FieldServiceTeam.php
PASS  postoji app/Models/FieldWorkOrder.php
PASS  postoji app/Models/FieldWorkOrderAttachment.php
PASS  postoji app/Models/ServicePartSupplier.php
PASS  postoji app/Models/ServicePart.php
PASS  postoji app/Models/FieldWorkOrderPart.php
PASS  postoji app/Models/ServicePartMovement.php
PASS  postoji app/Models/ServicePartPurchaseRequest.php
PASS  postoji app/Models/ServicePartPurchaseRequestItem.php
PASS  postoji app/Models/WarrantyRule.php
PASS  postoji app/Models/ProductWarranty.php
PASS  postoji app/Models/WarrantyMaintenanceRecord.php
PASS  postoji app/Models/OrderEmailOutbox.php
PASS  postoji app/Models/StockReceipt.php
PASS  postoji app/Models/StockReceiptItem.php
PASS  postoji app/Models/InventoryCount.php
PASS  postoji app/Models/InventoryCountItem.php
PASS  postoji app/Models/AutomationRun.php
PASS  postoji app/Models/OperationalAlert.php
PASS  postoji app/Models/NotificationPreference.php
PASS  postoji app/Models/BackupRun.php
PASS  postoji app/Models/SystemHealthSnapshot.php
PASS  postoji app/Models/SystemRuntimeState.php
PASS  postoji app/Models/SecurityEvent.php
PASS  postoji app/Services/OrderService.php
PASS  postoji app/Services/OrderWorkflowService.php
PASS  postoji app/Services/InventoryService.php
PASS  postoji app/Services/IdempotencyService.php
PASS  postoji app/Services/OrderPaymentService.php
PASS  postoji app/Services/IpsPaymentPayloadService.php
PASS  postoji app/Services/AdvancedInventoryService.php
PASS  postoji app/Services/LegacyReadOnlyGuard.php
PASS  postoji app/Services/OrderAccessService.php
PASS  postoji app/Services/OrderReportService.php
PASS  postoji app/Services/CommissionReportService.php
PASS  postoji app/Services/CommissionWorkflowService.php
PASS  postoji app/Services/OrderOperationalService.php
PASS  postoji app/Services/OrderTimelineService.php
PASS  postoji app/Services/OperationalNotificationService.php
PASS  postoji app/Notifications/OperationalNotification.php
PASS  postoji app/Services/OperationalAutomationService.php
PASS  postoji app/Services/AutomationReadinessService.php
PASS  postoji app/Services/BackupService.php
PASS  postoji app/Services/SystemHealthService.php
PASS  postoji app/Services/SecurityEventLogger.php
PASS  postoji app/Services/SensitiveDataSanitizer.php
PASS  postoji app/Services/OrderIndexService.php
PASS  postoji app/Services/OrderDetailService.php
PASS  postoji app/Services/OrderDetailPresenter.php
PASS  postoji app/Support/ViewValue.php
PASS  postoji app/Services/OrderDocumentService.php
PASS  postoji app/Services/AfterSalesAccessService.php
PASS  postoji app/Services/AfterSalesCaseService.php
PASS  postoji app/Services/AfterSalesActionService.php
PASS  postoji app/Services/FieldWorkOrderPlanner.php
PASS  postoji app/Services/FieldOperationsService.php
PASS  postoji app/Services/ServicePartsInventoryService.php
PASS  postoji app/Services/WarrantyService.php
PASS  postoji app/Services/OrderEmailOutboxService.php
PASS  postoji app/Services/OrderEmailDispatcher.php
PASS  postoji app/Services/NbsIpsQrService.php
PASS  postoji app/Services/DocumentNumberService.php
PASS  postoji app/Services/Pdf/SimplePdfWriter.php
PASS  postoji app/Services/Pdf/BusinessDocumentPdfService.php
PASS  postoji app/Services/Pdf/WarrantyCertificatePdfService.php
PASS  postoji app/Http/Requests/StoreOrderRequest.php
PASS  postoji app/Http/Requests/AdjustStockRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesMessageRequest.php
PASS  postoji app/Http/Requests/UpdateAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CompleteAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CancelAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/StoreFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/UpdateFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/ScheduleFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CompleteFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CancelFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/StoreServicePartRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartRequest.php
PASS  postoji app/Http/Requests/AdjustServicePartStockRequest.php
PASS  postoji app/Http/Requests/StoreServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/StoreFieldWorkOrderPartRequest.php
PASS  postoji app/Http/Requests/StoreServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/CancelServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/StoreWarrantyRuleRequest.php
PASS  postoji app/Http/Requests/UpdateProductWarrantyRequest.php
PASS  postoji app/Http/Requests/ScheduleWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Requests/CompleteWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Controllers/OrderController.php
PASS  postoji app/Http/Controllers/WarrantyController.php
PASS  postoji app/Http/Controllers/AfterSalesController.php
PASS  postoji app/Http/Controllers/AfterSalesAttachmentController.php
PASS  postoji app/Http/Controllers/FieldWorkOrderAttachmentController.php
PASS  postoji app/Http/Controllers/CommissionController.php
PASS  postoji app/Http/Controllers/NotificationController.php
PASS  postoji app/Http/Controllers/Api/V1/OrderController.php
PASS  postoji app/Http/Controllers/Admin/OrderController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesActionController.php
PASS  postoji app/Http/Controllers/Admin/FieldOperationsController.php
PASS  postoji app/Http/Controllers/Admin/FieldServiceTeamController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartSupplierController.php
PASS  postoji app/Http/Controllers/Admin/FieldWorkOrderPartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php
PASS  postoji app/Http/Controllers/Admin/WarrantyController.php
PASS  postoji app/Http/Controllers/Admin/CommissionController.php
PASS  postoji app/Http/Controllers/Admin/StockAdjustmentController.php
PASS  postoji app/Http/Controllers/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/ReportController.php
PASS  postoji app/Http/Controllers/Admin/DocumentSettingsController.php
PASS  postoji app/Http/Controllers/OrderPaymentController.php
PASS  postoji app/Http/Controllers/OrderDeliveryController.php
PASS  postoji app/Http/Controllers/Admin/PaymentController.php
PASS  postoji app/Http/Controllers/Admin/InventoryController.php
PASS  postoji app/Http/Controllers/Admin/AutomationController.php
PASS  postoji app/Http/Controllers/Admin/SystemHealthController.php
PASS  postoji app/Http/Controllers/Admin/TurnstileSettingsController.php
PASS  postoji app/Http/Controllers/Admin/OrderEmailSettingsController.php
PASS  postoji app/Http/Resources/OrderResource.php
PASS  postoji app/Console/Commands/OrdersDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCreateDoctorCommand.php
PASS  postoji app/Console/Commands/CatalogOwnershipDoctorCommand.php
PASS  postoji app/Console/Commands/DetailPagesDoctorCommand.php
PASS  postoji app/Console/Commands/ReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OperationsDoctorCommand.php
PASS  postoji app/Console/Commands/PaymentsInventoryDoctorCommand.php
PASS  postoji app/Console/Commands/RunOperationalAutomationCommand.php
PASS  postoji app/Console/Commands/AutomationDoctorCommand.php
PASS  postoji app/Console/Commands/CreateBackupCommand.php
PASS  postoji app/Console/Commands/BackupDoctorCommand.php
PASS  postoji app/Console/Commands/SystemHealthCommand.php
PASS  postoji app/Console/Commands/SchedulerHeartbeatCommand.php
PASS  postoji app/Console/Commands/TestDatabaseDoctorCommand.php
PASS  postoji app/Console/Commands/AfterSalesDoctorCommand.php
PASS  postoji app/Console/Commands/FieldOperationsDoctorCommand.php
PASS  postoji app/Console/Commands/ServicePartsDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesBackfillCommand.php
PASS  postoji app/Console/Commands/OrderEmailDispatchCommand.php
PASS  postoji app/Console/Commands/OrderEmailsDoctorCommand.php
PASS  postoji database/migrations/2026_07_22_000006_enable_production_orders_inventory.php
PASS  postoji database/migrations/2026_07_23_000007_repair_production_schema_beta5.php
PASS  postoji database/migrations/2026_07_23_000008_repair_authenticated_runtime_beta6.php
PASS  postoji database/migrations/2026_07_23_000009_create_reports_documents_and_supplier_assignment.php
PASS  postoji database/migrations/2026_07_23_000010_repair_reports_schema_beta1_2.php
PASS  postoji database/migrations/2026_07_23_000011_create_operational_orders_commissions_beta2.php
PASS  postoji database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php
PASS  postoji database/migrations/2026_07_23_000013_create_automation_alerts_beta4.php
PASS  postoji database/migrations/2026_07_23_000014_create_security_backup_health_beta6.php
PASS  postoji database/migrations/2026_07_29_000015_repair_order_documents_and_payments_beta7_5.php
PASS  postoji database/migrations/2026_07_30_000016_add_order_completion_beta7_7.php
PASS  postoji database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php
PASS  postoji database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php
PASS  postoji database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php
PASS  postoji database/migrations/2026_07_30_000020_create_after_sales_actions_beta7_11.php
PASS  postoji database/migrations/2026_07_30_000021_create_field_operations_beta7_12.php
PASS  postoji database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php
PASS  postoji database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php
PASS  postoji database/migrations/2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php
PASS  postoji database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php
PASS  postoji resources/views/orders/index.blade.php
PASS  postoji resources/views/admin/orders/show.blade.php
PASS  postoji resources/views/commissions/index.blade.php
PASS  postoji resources/views/notifications/index.blade.php
PASS  postoji resources/views/admin/commissions/index.blade.php
PASS  postoji resources/views/orders/create.blade.php
PASS  postoji resources/views/orders/show.blade.php
PASS  postoji resources/views/admin/reports/index.blade.php
PASS  postoji resources/views/admin/settings/documents.blade.php
PASS  postoji resources/views/admin/inventory/index.blade.php
PASS  postoji resources/views/admin/orders/partials/payments.blade.php
PASS  postoji resources/views/orders/partials/payments.blade.php
PASS  postoji resources/views/after-sales/index.blade.php
PASS  postoji resources/views/after-sales/create.blade.php
PASS  postoji resources/views/after-sales/show.blade.php
PASS  postoji resources/views/admin/after-sales/index.blade.php
PASS  postoji resources/views/admin/after-sales/show.blade.php
PASS  postoji resources/views/admin/field-operations/index.blade.php
PASS  postoji resources/views/admin/field-operations/show.blade.php
PASS  postoji resources/views/admin/field-operations/teams.blade.php
PASS  postoji resources/views/admin/service-parts/index.blade.php
PASS  postoji resources/views/admin/service-parts/suppliers.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-requests.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-show.blade.php
PASS  postoji resources/views/admin/settings/automation.blade.php
PASS  postoji resources/views/admin/settings/system-health.blade.php
PASS  postoji resources/views/admin/settings/turnstile.blade.php
PASS  postoji resources/views/admin/settings/order-emails.blade.php
PASS  postoji resources/views/emails/order-events.blade.php
PASS  postoji tests/Feature/AdminOrdersImageRotationTest.php
PASS  postoji tests/Feature/OperationalOrdersCommissionsTest.php
PASS  postoji tests/Feature/OperationalAutomationTest.php
PASS  postoji tests/Feature/PaymentsAdvancedInventoryTest.php
PASS  postoji tests/Feature/OrderDeliveryWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesActionExecutionTest.php
PASS  postoji tests/Feature/FieldOperationsWorkflowTest.php
PASS  postoji tests/Feature/ServicePartsWorkflowTest.php
PASS  postoji tests/Feature/InventoryWorkspaceUiTest.php
PASS  postoji tests/Feature/SecurityHealthBackupTest.php
PASS  postoji tests/Feature/MySqlTestDatabaseSafetyTest.php
PASS  postoji tests/Unit/SensitiveDataSanitizerTest.php
PASS  postoji tests/Feature/ProductionOrderInventoryTest.php
PASS  postoji tests/Feature/InventoryAdjustmentTest.php
PASS  postoji tests/Feature/ProductionPermissionsTest.php
PASS  postoji tests/Feature/DashboardLegacyDesignTest.php
PASS  postoji tests/Feature/ReportsDocumentsSupplierTest.php
PASS  postoji tests/Fixtures/pdf-logo.jpg
PASS  postoji tests/Unit/BusinessDocumentPdfServiceTest.php
PASS  postoji tests/Unit/DeliveryNoteMigrationContractTest.php
PASS  postoji tests/Unit/DocumentRevisionMigrationContractTest.php
PASS  postoji tests/Unit/CommissionReportPdfServiceTest.php
PASS  postoji tests/Feature/OrderEmailsIpsWarrantyTest.php
PASS  postoji tests/Unit/OrderEmailIpsMigrationContractTest.php
PASS  postoji tests/Unit/ReceivablesPermissionMigrationContractTest.php
PASS  postoji tests/Unit/LegacyReadOnlyGuardTest.php
PASS  postoji tests/Unit/OrderDetailPresenterTest.php
PASS  postoji tests/Unit/ViewValueTest.php
PASS  postoji tests/Feature/CatalogDetailPageTest.php
PASS  postoji tests/Feature/LoginDashboardFallbackTest.php
PASS  postoji resources/views/components/icon.blade.php
PASS  postoji app/Http/Middleware/EnsureRuntimeDirectories.php
PASS  postoji app/Http/Middleware/AttachRequestId.php
PASS  postoji app/Http/Middleware/SecurityHeaders.php
PASS  postoji app/Console/Commands/AuthDoctorCommand.php
PASS  postoji .env.testing.mysql.example
PASS  postoji phpunit.mysql.xml
PASS  postoji bin/php-lint.php
PASS  postoji bin/autoload-check.php
PASS  postoji bin/pdf-smoke.php
PASS  postoji bin/delivery-note-smoke.php
PASS  postoji bin/warranty-pdf-smoke.php
PASS  postoji bin/ips-qr-pdf-smoke.php
PASS  postoji storage/framework/cache/data/.gitignore
PASS  postoji storage/framework/sessions/.gitignore
PASS  postoji storage/framework/views/.gitignore
PASS  postoji storage/logs/.gitignore
PASS  postoji storage/app/backups/.gitignore
PASS  postoji config/backup.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.md
PASS  postoji docs/RELEASE-CHECK.md
PASS  postoji app/Console/Commands/ReleaseCheckCommand.php
PASS  postoji config/release.php
PASS  postoji bin/release-check-smoke.php
PASS  postoji tests/Unit/ReleaseCheckContractTest.php
PASS  postoji tests/Feature/ReleaseCheckCommandTest.php
PASS  postoji storage/app/release-check/.gitignore
PASS  postoji docs/UPGRADE-V2.1-BETA7.22.1.md
PASS  postoji bin/theme-css-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.21.md
PASS  postoji database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php
PASS  postoji app/Models/ReportSchedule.php
PASS  postoji app/Models/ReportDelivery.php
PASS  postoji app/Services/ManagementReportService.php
PASS  postoji app/Services/ReportScheduleService.php
PASS  postoji app/Services/Pdf/ManagementReportPdfService.php
PASS  postoji app/Http/Controllers/Admin/ManagementReportController.php
PASS  postoji app/Http/Controllers/Admin/ReportScheduleController.php
PASS  postoji app/Console/Commands/ManagementReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCostSnapshotsCommand.php
PASS  postoji app/Services/OrderItemCostSnapshotService.php
PASS  postoji app/Console/Commands/ManagementReportsDispatchCommand.php
PASS  postoji resources/views/admin/reports/management.blade.php
PASS  postoji resources/views/emails/management-report.blade.php
PASS  postoji tests/Feature/ManagementReportsProfitabilityTest.php
PASS  postoji tests/Unit/OrderCostSnapshotRepairContractTest.php
PASS  postoji bin/management-report-smoke.php
PASS  postoji bin/order-cost-snapshot-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php
PASS  postoji app/Services/ProductTemplateService.php
PASS  postoji app/Services/ProductCompletenessService.php
PASS  postoji app/Services/ProductBulkService.php
PASS  postoji app/Http/Controllers/Admin/ProductBulkController.php
PASS  postoji app/Console/Commands/SmartProductsDoctorCommand.php
PASS  postoji resources/views/admin/products/clone.blade.php
PASS  postoji resources/views/admin/products/bulk.blade.php
PASS  postoji tests/Unit/SmartProductManagementMigrationContractTest.php
PASS  postoji tests/Unit/SmartProductManagementUiContractTest.php
PASS  postoji tests/Feature/SmartProductManagementTest.php
PASS  postoji bin/smart-product-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.1.md
PASS  postoji docs/UPGRADE-V2.1-RC1.md
PASS  postoji docs/RC-OPERATIONS.md
PASS  postoji docs/UPGRADE-V2.1-STABLE.md
PASS  postoji docs/UPGRADE-V2.1.1.md
PASS  postoji docs/UPGRADE-V2.1.2.md
PASS  postoji docs/UPGRADE-V2.1.3.md
PASS  postoji docs/UPGRADE-V2.1.3.1.md
PASS  postoji docs/UPGRADE-V2.1.3.2.md
PASS  postoji docs/UPGRADE-V2.1.3.3.md
PASS  postoji docs/STABLE-OPERATIONS.md
PASS  postoji docs/BACKUP-RESTORE-DRILL.md
PASS  postoji bin/rc-hardening-smoke.php
PASS  postoji bin/stable-hardening-smoke.php
PASS  postoji bin/stable-maintenance-smoke.php
PASS  postoji bin/product-media-ux-smoke.php
PASS  postoji bin/product-announcement-smoke.php
PASS  postoji bin/catalog-settings-product-data-smoke.php
PASS  postoji bin/catalog-settings-integrity-hotfix-smoke.php
PASS  postoji bin/product-save-regex-hotfix-smoke.php
PASS  postoji bin/storage-capacity-total-smoke.php
PASS  postoji tests/Unit/ProductSaveRegexHotfixContractTest.php
PASS  postoji tests/Unit/StorageCapacityTotalContractTest.php
PASS  postoji tests/Unit/CatalogSettingsProductDataContractTest.php
PASS  postoji tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php
PASS  postoji app/Console/Commands/CatalogSettingsDoctorCommand.php
PASS  postoji app/Services/ProductTypeCategoryService.php
PASS  postoji app/Services/SpecificationFieldLifecycleService.php
PASS  postoji app/Services/StorageSpecificationService.php
PASS  postoji database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php
PASS  postoji database/migrations/2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php
PASS  postoji database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php
PASS  postoji public/assets/js/dictionary-sort-manager.js
PASS  postoji resources/views/admin/dictionary/product-type.blade.php
PASS  postoji tests/Unit/ProductMediaUxContractTest.php
PASS  postoji tests/Unit/ProductAnnouncementContractTest.php
PASS  postoji app/Services/ProductAnnouncementService.php
PASS  postoji tests/Unit/ReleaseCandidateHardeningContractTest.php
PASS  postoji tests/Unit/StableReleaseContractTest.php
PASS  postoji tests/Unit/StableMaintenanceContractTest.php
PASS  postoji app/Console/Commands/SecurityHardeningDoctorCommand.php
PASS  postoji app/Console/Commands/MigrationsDoctorCommand.php
PASS  postoji app/Console/Commands/AccessControlDoctorCommand.php
PASS  postoji app/Console/Commands/ReleaseIntegrityCommand.php
PASS  postoji app/Console/Commands/BackupVerifyCommand.php
PASS  postoji app/Console/Commands/ProductMediaDoctorCommand.php
PASS  postoji app/Http/Controllers/ProductMediaDownloadController.php
PASS  postoji public/assets/js/product-media-manager.js
PASS  postoji resources/views/admin/products/partials/image-card.blade.php
PASS  postoji resources/views/admin/products/partials/image-upload.blade.php
PASS  postoji bin/catalog-detail-smoke.php
PASS  postoji bin/detail-pages-doctor-smoke.php
PASS  postoji tests/Unit/CatalogDetailBladeContractTest.php
PASS  postoji tests/Unit/SystemHealthRemediationContractTest.php
PASS  postoji tests/Unit/DetailPagesDoctorContractTest.php
PASS  postoji database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php
PASS  postoji app/Models/SpecificationOption.php
PASS  postoji app/Services/SpecificationDependencyService.php
PASS  postoji app/Services/CatalogSpecificationFilterService.php
PASS  postoji app/Console/Commands/CatalogCorrelationsDoctorCommand.php
PASS  postoji resources/views/partials/correlated-specification-filters.blade.php
PASS  postoji resources/views/partials/correlated-specification-filter-script.blade.php
PASS  postoji tests/Unit/CorrelatedSpecificationsMigrationContractTest.php
PASS  postoji tests/Unit/CorrelatedSpecificationUiContractTest.php
PASS  postoji docs/UPGRADE-V2.1.4.md
PASS  postoji bin/cms-v2.1.4-smoke.php
PASS  postoji tests/Unit/CmsV214ContractTest.php
PASS  postoji app/Console/Commands/CmsV214DoctorCommand.php
PASS  postoji app/Services/ProductDeletionService.php
PASS  postoji database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php
PASS  postoji docs/UPGRADE-V2.1.4.1.md
PASS  postoji bin/product-type-page-render-hotfix-smoke.php
PASS  postoji tests/Unit/ProductTypePageRenderHotfixContractTest.php
PASS  postoji docs/UPGRADE-V2.1.5.md
PASS  postoji bin/cms-v2.1.5-smoke.php
PASS  postoji tests/Unit/CmsV215ContractTest.php
PASS  postoji app/Console/Commands/CmsV215DoctorCommand.php
PASS  postoji public/assets/js/ux-runtime.js
PASS  postoji resources/views/errors/minimal.blade.php
PASS  postoji resources/views/errors/403.blade.php
PASS  postoji resources/views/errors/404.blade.php
PASS  postoji resources/views/errors/419.blade.php
PASS  postoji resources/views/errors/429.blade.php
PASS  postoji resources/views/errors/500.blade.php
PASS  postoji resources/views/errors/503.blade.php
PASS  postoji database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php
PASS  postoji docs/UPGRADE-V2.2.0.md
PASS  postoji docs/openapi.yaml
PASS  postoji bin/cms-v2.2.0-smoke.php
PASS  postoji tests/Feature/MobileApiFoundationTest.php
PASS  postoji tests/Unit/MobileApiFoundationContractTest.php
PASS  postoji app/Console/Commands/CmsV220DoctorCommand.php
PASS  postoji app/Http/Controllers/Api/V1/BootstrapController.php
PASS  postoji app/Http/Controllers/Api/V1/MobileDeviceController.php
PASS  postoji app/Models/MobileDevice.php
PASS  postoji database/migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php
PASS  verzija je 2.2.0 Mobile API Foundation
PASS  release tag je v2.2.0
PASS  upgrade osnova je v2.1.6
PASS  composer.json validan
PASS  PHP minimum 8.4
PASS  Laravel 13
PASS  Composer lint/autoload/test/release skripte postoje
PASS  runtime verzija je 2.2.0
PASS  migracija sadrži idempotency_keys
PASS  migracija sadrži source_system
PASS  migracija sadrži inventory_state
PASS  migracija sadrži inventory_returned_at
PASS  migracija sadrži event_key
PASS  migracija sadrži orders_user_idempotency_unique
PASS  porudžbina zaključava proizvode
PASS  porudžbina umanjuje lager u transakciji
PASS  povrat lagera ima jedinstveni event key
PASS  idempotency koristi unique zapis i row lock
PASS  legacy porudžbine su blokirane
PASS  legacy SQL guard je registrovan pre izvršavanja
PASS  legacy MySQL sesija je READ ONLY
PASS  Redis cache konekcije postoje u database konfiguraciji
PASS  Redis cache store postoji u cache konfiguraciji
PASS  queue ostaje bez Redis konekcije
PASS  cache limiter zadržava file fallback i podržava runtime Redis override
PASS  dozvola orders.create
PASS  dozvola orders.view_own
PASS  dozvola orders.cancel_own
PASS  dozvola orders.manage
PASS  dozvola stock.view
PASS  dozvola stock.adjust
PASS  dozvola reports.view
PASS  dozvola reports.export
PASS  dozvola invoices.manage
PASS  dozvola invoices.view_own
PASS  web ruta orders.store
PASS  web ruta orders.cancel
PASS  web ruta admin.orders.status
PASS  web ruta admin.orders.payment
PASS  web ruta admin.orders.tracking
PASS  web ruta admin.stock.adjust
PASS  API porudžbine postoje
PASS  porudžbina ima dodeljenog SuperAdmin/Admin dobavljača
PASS  admin scope vidi samo njemu dodeljene porudžbine
PASS  izveštaji podržavaju filtere i CSV/PDF
PASS  poslovni dokumenti koriste nepromenljivi snapshot
PASS  PDF renderer je lokalni, embedded TrueType i ToUnicode
PASS  brojevi dokumenata su transakcioni i jedinstveni
PASS  web rute imaju reports CSV/PDF i dokumente
PASS  reports stranica ima schema fallback umesto 500
PASS  reports render je unutar zaštićenog controller toka
PASS  reports view ima render marker i bezbedne URL-ove
PASS  reports export vraća kontrolisani 503
PASS  reports doctor izvršava repair i stvarne SQL upite
PASS  reports doctor renderuje controller Blade i layout
PASS  reports logging je best-effort
PASS  beta1.2 repair migracija je nedestruktivna
PASS  hamburger dugme postoji
PASS  mobilni meni ima kontrolni JavaScript
PASS  mobilni meni nema horizontalni scroll
PASS  direktne mobilne stavke koriste zajednički levi wrapper
PASS  Početna Provizije i Izveštaji su poravnati ulevo
PASS  CSS ima pouzdan cache busting
PASS  legacy desktop header ima dva reda
PASS  legacy mobilni header zadržava kurs temu nalog i hamburger
PASS  dashboard ima moderni hero KPI prioritete i module
PASS  dashboard CSS ima 4 desktop i 2 mobilne kolone
PASS  admin gridovi su poravnati na vrh
PASS  forme koriste sadržajnu visinu
PASS  deployment check ima bezbedan repair režim
PASS  deployment check razlikuje runtime zaštitu i grant warning
PASS  deployment check proverava i operativne kolone
PASS  dashboard koristi DB fallback umesto 500
PASS  login telemetry je best-effort
PASS  login hvata session i remember-token probleme
PASS  authenticated layout nema direktan SettingsService upit
PASS  authenticated layout koristi bezbedne user helper metode
PASS  dashboard logging ne može da obori fallback
PASS  runtime middleware prethodi session/cache middleware-u
PASS  deployment repair kreira runtime direktorijume i kompajlira Blade
PASS  auth doctor može da renderuje kompletan dashboard
PASS  Turnstile hvata sve transportne/JSON greške
PASS  Turnstile podešavanja imaju DB prioritet i env fallback
PASS  Turnstile secret se čuva šifrovano i ne izlaže kroz all
PASS  Turnstile admin ekran i ruta postoje
PASS  beta6 repair migracija popravlja core login šemu
PASS  static check razdvaja runtime i ZIP režim
PASS  operativna migracija sadrži order_internal_notes
PASS  operativna migracija sadrži order_assignments
PASS  operativna migracija sadrži commission_payment_batches
PASS  operativna migracija sadrži notifications
PASS  operativna migracija sadrži payment_batch_id
PASS  operativna migracija sadrži status_updated_at
PASS  operativna migracija sadrži last_internal_note_at
PASS  beta2 dozvola commissions.view_own
PASS  beta2 dozvola orders.reassign
PASS  beta2 dozvola orders.internal_notes
PASS  beta2 dozvola notifications.view
PASS  provizije imaju odobravanje isplatu storniranje i istoriju
PASS  masovna isplata koristi transakciju row lock i batch
PASS  korisnik vidi samo svoje provizije i podrazumevanih 10 procenata
PASS  interne napomene nisu u javnom timeline-u
PASS  ponovna dodela je ograničena na SuperAdministratora
PASS  preuzimanje i rokovi porudžbine imaju audit i obaveštenja
PASS  database notifikacije su neblokirajuće i mail je opcioni
PASS  operativni doctor proverava šemu SQL i render
PASS  admin provizije imaju filtere CSV PDF i masovnu isplatu
PASS  commission tabela nema unutrašnji vertikalni scroll pri obradi
PASS  obrada provizije koristi veliki viewport modal
PASS  commission modal ima naslov i eksplicitno zatvaranje
PASS  otvaranje commission modala zatvara prethodni
PASS  porudžbina ima timeline interne napomene preuzimanje rokove i reassignment UI
PASS  inbox obaveštenja podržava read i read-all
PASS  operativni feature testovi postoje
PASS  commission modal regresioni feature test postoji
PASS  beta3.1 migracija nema globalni use Throwable
PASS  beta3.1 migracija koristi potpuno kvalifikovani Throwable
PASS  PHP lint odbija warning deprecated i notice izlaz
PASS  beta3 migracija sadrži order_payments
PASS  beta3 migracija sadrži stock_receipts
PASS  beta3 migracija sadrži stock_receipt_items
PASS  beta3 migracija sadrži inventory_counts
PASS  beta3 migracija sadrži inventory_count_items
PASS  beta3 migracija sadrži payment_state
PASS  beta3 migracija sadrži paid_total_rsd
PASS  beta3 migracija sadrži payment_due_at
PASS  beta3 dozvola payments.manage
PASS  beta3 dozvola payments.upload_proof
PASS  beta3 dozvola payments.view_own
PASS  beta3 dozvola inventory.receive
PASS  beta3 dozvola inventory.count
PASS  beta3 dozvola inventory.export
PASS  uplate koriste transakciju row lock audit i saldo
PASS  potvrde uplate su privatne i autorizovane
PASS  IPS podaci koriste snapshot porudžbine
PASS  predračun i račun postavljaju dospeće porudžbine
PASS  ulaz robe i popis koriste idempotency transakciju i row lock
PASS  napredni lager ima readiness fallback umesto 500
PASS  reports beta3 sažeci i izvozi su zaštićeni
PASS  beta3 doctor proverava repair SQL i render
PASS  beta3 feature testovi pokrivaju uplate ulaz i popis
PASS  beta7.5 repair migracija obnavlja PDF i payment šemu
PASS  beta7.5 repair migracija je nedestruktivna
PASS  beta7.5 potvrda koristi site name fallback
PASS  beta7.5 ručno evidentiranje uplate ima regresioni test
PASS  beta7.5 doctor proverava dokument i payment tabele
PASS  beta7.6 PDF dozvoljava lokalno uvezene porudžbine
PASS  beta7.6 uplate dozvoljavaju lokalno uvezene porudžbine
PASS  beta7.6 legacy lager zaštita ostaje aktivna
PASS  beta7.7 migracija dodaje terminalno stanje porudžbine
PASS  beta7.7 PDF podešavanja imaju upload pregled i uklanjanje logotipa
PASS  beta7.7 PDF logo se ugrađuje kao lokalni JPEG
PASS  beta7.7 PDF ne prikazuje subagent email kupca
PASS  beta7.7 kompletiranje COD porudžbine evidentira preostali saldo
PASS  beta7.7 kompletirana porudžbina zaključava dalje izmene
PASS  beta7.7 kompletiranje je jasno dostupno u detalju i listi
PASS  beta7.8 migracija dodaje evidenciju isporuke i reopening stanje
PASS  beta7.8 kompletiranje čuva dokaz isporuke privatno
PASS  beta7.8 otpremnica koristi OTP broj i delivery snapshot
PASS  beta7.8 ponovno otvaranje je superadmin-only i auditovano
PASS  beta7.8 detalj prikazuje strukturiranu evidenciju isporuke
PASS  beta7.8 doctor proverava novu šemu i dozvole
PASS  beta7.8 feature testovi pokrivaju dokaz otpremnicu i reopening
PASS  beta7.8 UI ima delivery workflow responsive stilove
PASS  beta7.9 migracija uklanja legacy ENUM blokadu za delivery_note
PASS  beta7.9 servis radi schema preflight pre izdavanja otpremnice
PASS  beta7.9 pomoćni notification kvar ne obara izdat dokument, a IPS važi samo za finansijske dokumente
PASS  beta7.9 kontroleri vraćaju incident poruku umesto Error 500
PASS  beta7.9 doctor proverava stvarni MySQL tip dokumenta
PASS  beta7.9 ima migration contract i delivery note PDF smoke test
PASS  detail koristi eksplicitan slug upit
PASS  slug upit primenjuje objedinjeni visibility scope
PASS  API detail koristi isti slug upit
PASS  neispravna slika ne obara detail
PASS  detail filtrira slike bez validnog URL-a
PASS  katalog generiše eksplicitan slug link
PASS  detail ima interaktivnu thumbnail galeriju
PASS  detail ima fullscreen lightbox i zoom kontrole
PASS  gallery podržava tastaturu swipe i preload
PASS  gallery radi i sa jednom slikom
PASS  gallery CSS ima fullscreen viewport i responsive mobile
PASS  gallery feature testovi postoje
PASS  beta4 migracija sadrži automation_runs
PASS  beta4 migracija sadrži operational_alerts
PASS  beta4 migracija sadrži notification_preferences
PASS  beta4 nema Redis i koristi scheduler/file lock
PASS  beta4 detektuje nepreuzete porudžbine dospele obaveze i nizak lager
PASS  beta4 upozorenja su deduplikovana i razrešavaju se
PASS  notification preferences upravljaju kanalima i kategorijama
PASS  automation settings UI i ručno pokretanje postoje
PASS  automation doctor proverava repair scheduler i run
PASS  beta4 dozvola automation.manage postoji
PASS  beta4 feature testovi pokrivaju deduplikaciju i preference
PASS  beta5 inventory koristi jednu aktivnu operaciju
PASS  beta5 inventory čuva filter i limit nakon knjiženja
PASS  beta5 inventory nema unutrašnji vertikalni scrollbar
PASS  beta5 inventory responsive tabela koristi data-label kartice
PASS  beta5 feature test pokriva inventory workspace
PASS  beta6 migracija sadrži backup_runs
PASS  beta6 migracija sadrži system_health_snapshots
PASS  beta6 migracija sadrži system_runtime_states
PASS  beta6 migracija sadrži security_events
PASS  beta6 migracija sadrži system.health
PASS  beta6 migracija sadrži backups.manage
PASS  beta6 migracija sadrži audit.export
PASS  beta6 migracija sadrži security.view
PASS  beta6 backup koristi mysqldump bez lozinke u argumentima
PASS  beta6 backup odbija public putanju i pravi SHA-256 manifest
PASS  beta6 system health proverava scheduler backup migracije i legacy
PASS  beta6 security header-i i request ID postoje
PASS  beta6 audit koristi rekurzivnu sanitizaciju i request ID
PASS  beta6 rate limiter-i pokrivaju upload export admin i backup
PASS  beta6 test DB doctor ima višestruku zaštitu
PASS  beta6 system health UI i backup akcije postoje
PASS  beta6 scheduler ima heartbeat backup i health snapshot
PASS  beta6 feature i unit testovi postoje
PASS  beta7.1 orders ima readiness SQL i render zaštitu
PASS  beta7.1 orders doctor proverava isti browser render
PASS  beta7.1 orders recovery ne završava generičkim 500
PASS  beta7.1 edit artikla ima rotaciju ulevo i udesno
PASS  beta7.1 legacy rotacija koristi copy-on-write
PASS  beta7.1 rotacija koristi privremeni fajl i kontrolisani Imagick/GD fallback
PASS  beta7.1 feature testovi postoje
PASS  beta7.2 order detail koristi opcioni schema-aware loader
PASS  beta7.2 admin i user detail imaju protected render
PASS  beta7.2 admin i user detail imaju readiness markere
PASS  beta7.2 timeline i IPS ne mogu oboriti detalj
PASS  beta7.2 orders doctor renderuje oba detalja
PASS  beta7.2 detail-pages doctor proverava ključne detail stranice
PASS  beta7.2 feature testovi pokrivaju detail i opcione tabele
PASS  beta7.3 detail koristi scalar presenter umesto Eloquent objekata u Blade-u
PASS  beta7.3 presenter bezbedno obrađuje raw i zero datume
PASS  beta7.3 presenter bezbedno generiše named rute
PASS  beta7.3 detail view nema direktne auth, relation ili datetime pozive
PASS  beta7.3 admin i user detail imaju ne-503 read-only fallback
PASS  beta7.3 orders doctor prikazuje tačan exception uzrok za oba detaila
PASS  beta7.3 orders doctor nastavlja admin i user audit
PASS  beta7.3 payment i inventory Gates su definisani
PASS  beta7.3 presenter i ViewValue regresioni testovi postoje
PASS  beta7.10 migracija sadrži after_sales_cases
PASS  beta7.10 migracija sadrži after_sales_case_items
PASS  beta7.10 migracija sadrži after_sales_messages
PASS  beta7.10 migracija sadrži after_sales_attachments
PASS  beta7.10 migracija sadrži after_sales_status_history
PASS  beta7.10 ima tri postprodajne dozvole
PASS  beta7.10 pristup poštuje vlasnika dodeljenog admina i superadmin scope
PASS  beta7.10 slučaj zahteva isporučenu ili kompletiranu porudžbinu
PASS  beta7.10 čuva pogođene stavke snapshot i SLA rok
PASS  beta7.10 privatni prilozi proveravaju MIME veličinu i autorizaciju
PASS  beta7.10 javne i interne poruke su odvojene
PASS  beta7.10 statusni tok zahteva obrazloženje konačne odluke
PASS  beta7.10 automatizacija upozorava na probijene rokove slučaja
PASS  beta7.10 UI ima korisnički i administratorski postprodajni tok
PASS  beta7.10 doctor proverava šemu dozvole i SQL
PASS  beta7.10 feature test pokriva privatni prilog i obradu
PASS  beta7.10 privatni download zabranjuje browser cache
PASS  beta7.10 konkurentno zatvaranje ne propušta novu poruku
PASS  beta7.10 reopening zahteva razlog i čuva vreme prethodnog rešenja
PASS  beta7.10 nedodeljeni slučajevi obaveštavaju superadministratore
PASS  beta7.10 dashboard prikazuje aktivne probijene i waiting slučajeve
PASS  beta7.11 migracija sadrži after_sales_actions
PASS  beta7.11 migracija sadrži after_sales_action_items
PASS  beta7.11 migracija sadrži after_sales_action_id
PASS  beta7.11 migracija sadrži after_sales.execute
PASS  beta7.11 podržava četiri izvršne radnje
PASS  beta7.11 lager efekti su zaključani i idempotentni
PASS  beta7.11 refundacija je vezana za radnju i ograničena neto uplatom
PASS  beta7.11 slučaj čeka završetak aktivnih radnji
PASS  beta7.11 UI ima planiranje pokretanje izvršenje i otkazivanje
PASS  beta7.11 Gate i permission middleware štite izvršne kontrole
PASS  beta7.11 controller ima sve izvršne endpoint-e
PASS  beta7.11 automatizacija prati rok izvršne radnje
PASS  beta7.11 dashboard prikazuje radnje za izvršenje
PASS  beta7.11 testovi pokrivaju idempotentni lager povrat i refundaciju
PASS  beta7.12 migracija sadrži field_service_teams
PASS  beta7.12 migracija sadrži field_work_orders
PASS  beta7.12 migracija sadrži field_work_order_attachments
PASS  beta7.12 migracija sadrži field_operations.view
PASS  beta7.12 migracija sadrži field_operations.manage
PASS  beta7.12 fizičke radnje automatski dobijaju radni nalog
PASS  beta7.12 sprečava preklapanje termina iste ekipe
PASS  beta7.12 završetak zahteva dolazak na lokaciju
PASS  beta7.12 radni nalog čuva troškove kilometražu i privatne dokaze
PASS  beta7.12 UI ima kalendar ekipe i operativne statuse
PASS  beta7.12 rute i Gate štite terenske operacije
PASS  beta7.12 automatizacija prati neplanirane i probijene radne naloge
PASS  beta7.12 doctor proverava tabele dozvole rute i SQL
PASS  beta7.12 test pokriva auto nalog konflikt i on-site završetak
PASS  beta7.13 migracija sadrži service_part_suppliers
PASS  beta7.13 migracija sadrži service_parts
PASS  beta7.13 migracija sadrži field_work_order_parts
PASS  beta7.13 migracija sadrži service_part_movements
PASS  beta7.13 migracija sadrži service_part_purchase_requests
PASS  beta7.13 migracija sadrži service_part_purchase_request_items
PASS  beta7.13 migracija sadrži service_parts.view
PASS  beta7.13 migracija sadrži service_parts.manage
PASS  beta7.13 migracija sadrži service_parts.procurement
PASS  beta7.13 početno stanje ulazi u movement ledger
PASS  beta7.13 rezervacija ne umanjuje fizičko stanje
PASS  beta7.13 završetak skida stvarni utrošak i oslobađa ostatak
PASS  beta7.13 otkazivanje oslobađa sve rezervacije
PASS  beta7.13 kretanja servisnog lagera su idempotentna i ponovo proverena pod lockom
PASS  beta7.13 nacrt nabavke koristi konkurentno bezbedan privremeni broj
PASS  beta7.13 prijem nabavke računa ponderisanu prosečnu cenu
PASS  beta7.13 UI ima servisni lager dobavljače nabavku i utrošak
PASS  beta7.13 Gate i rute štite lager i nabavku
PASS  beta7.13 automatizacija prati nizak lager i kašnjenje nabavke
PASS  beta7.13 doctor proverava tabele dozvole rute i SQL
PASS  beta7.13 testovi pokrivaju ledger rezervaciju utrošak i ponderisanu cenu
PASS  beta7.13 UI ima responsive stilove servisnog lagera
PASS  beta7.14.1 migracija prvo obezbeđuje FK indeks
PASS  beta7.14 migracija uklanja unique order/type ograničenje
PASS  beta7.14 migracija uvodi revizije i vezu sa prethodnim dokumentom
PASS  beta7.14 servis vraća samo aktivan dokument ili izdaje novu reviziju
PASS  beta7.14 storniranje zahteva razlog i čuva audit podatak
PASS  beta7.14 model podržava supersedes relaciju
PASS  beta7.14 UI razlikuje aktivan dokument i novu reviziju
PASS  beta7.14 PDF prikazuje broj revizije
PASS  beta7.14 regresioni test pokriva ponovno izdavanje
PASS  beta7.15 migracija sadrži warranty_rules
PASS  beta7.15 migracija sadrži product_warranties
PASS  beta7.15 migracija sadrži warranty_maintenance_records
PASS  beta7.15 migracija sadrži warranties.view_own
PASS  beta7.15 migracija sadrži warranties.manage
PASS  beta7.15 pravila imaju product category global prioritet
PASS  beta7.15 kompletiranje automatski izdaje garanciju bez obaranja porudžbine
PASS  beta7.15 otkazivanje poništava aktivne garancije
PASS  beta7.15 GAR poslovni broj je registrovan
PASS  beta7.15 garancija čuva snapshot kupca artikla uslova i serijskih brojeva
PASS  beta7.15 preventivno održavanje generiše sledeći termin
PASS  beta7.15 zakazivanje ne menja vreme tokom provere datuma
PASS  beta7.15 backfill bira samo stavke bez garancije
PASS  beta7.15 PDF garantni list prikazuje ključne snapshot podatke
PASS  beta7.15 korisnički i administratorski prikazi postoje
PASS  beta7.15 rute Gates i administratorski scope štite garancije
PASS  beta7.15 automatizacija prati istek i održavanje
PASS  beta7.15 dashboard prikazuje garancije
PASS  beta7.15 doctor i backfill komande postoje
PASS  beta7.15 feature test pokriva automatsko izdavanje i prioritet pravila
PASS  beta7.15 warranty PDF smoke postoji
PASS  beta7.16 migracija uvodi outbox QR snapshot i dane garancije
PASS  beta7.16 migracija ima recovery putanju za delimičan MariaDB DDL
PASS  beta7.16 e-mail outbox ima dedupe intervale i pojedinačne primaoce
PASS  beta7.16 e-mail prima autor odgovorno lice i dodatne adrese
PASS  beta7.16 workflow šalje status tracking plaćanje i dokumente
PASS  beta7.16 dispatcher ima retry stuck recovery i zaštitu storniranog priloga
PASS  beta7.16 scheduler šalje outbox svake minute
PASS  beta7.16 admin podešava intervale događaje i dokumente
PASS  beta7.16 e-mail šablon ima događaje i bezbedan action link
PASS  beta7.16 NBS payload koristi zvanične oznake i RSD zarez
PASS  beta7.16 NBS servis koristi zvanični HTTPS endpoint i čuva privatni PNG snapshot
PASS  beta7.16 stornirani istorijski dokument ostaje pregledljiv bez ponovnog NBS poziva
PASS  beta7.16 finansijski dokument trazi NBS QR samo za pozitivan neplaceni saldo
PASS  beta7.16 PDF crta PNG bez GD i prikazuje NBS IPS QR oznaku
PASS  beta7.16 IPS QR smoke potvrđuje sliku oznaku i tačan RSD iznos
PASS  beta7.16 garancija podržava kombinaciju meseci i dana
PASS  beta7.16 admin može kreirati porudžbinu
PASS  beta7.16 doctor proverava outbox SMTP scheduler NBS i garancijske dane
PASS  beta7.16 feature test pokriva admin porudžbinu događaje NBS QR i dane garancije
PASS  beta7.17 migracija uvodi predmete rate i komunikaciju naplate
PASS  beta7.17 migracija je recovery-safe za delimičan DDL
PASS  beta7.17 servis automatski otvara zatvara i usklađuje predmete
PASS  beta7.17 rate se raspoređuju prema stvarno plaćenom iznosu
PASS  beta7.17 automatske opomene koriste faze dedupe i outbox
PASS  beta7.17 admin ima aging pregled plan evidenciju komunikacije i direktnu uplatu
PASS  beta7.17 podmeni se zatvara klikom van escape i izborom stavke
PASS  beta7.17 checkbox i radio imaju normalnu globalnu veličinu
PASS  beta7.17 doctor proverava šemu dozvolu i scheduler
PASS  beta7.17.1 permission seed je schema-aware
PASS  beta7.17.1 seeder ne zahteva permissions.updated_at
PASS  beta7.17.2 hover podmeni ima grace period i click pin
PASS  beta7.17.2 CSS premošćava razmak do podmenija
PASS  beta7.18 migracija uvodi strukturirane opcije i korelacije
PASS  beta7.18 migracija je recovery-safe za MariaDB
PASS  beta7.18 procesor ima porodicu i tačan model
PASS  beta7.18 brend filtrira samo sopstvene linije
PASS  beta7.18 generičke zavisnosti imaju server validaciju i zaštitu ciklusa
PASS  beta7.18 forma skriva nepovezane opcije i čuva detalj
PASS  beta7.18 kataloški filteri podržavaju select range boolean text i detalj
PASS  beta7.18 oba kataloga koriste korelisane filtere
PASS  beta7.18 doctor proverava procesor linije veze i tipove
PASS  beta7.18.1 forma artikla ne koristi nedostupni index filter servis
PASS  beta7.18.1 jedinstveni katalog dobija podatke za korelisane filtere
PASS  beta7.18.1 šifarnici dobijaju podatke za roditelje i mape zavisnosti
PASS  beta7.19 migracija uvodi šablone kompletnost i poreklo klona
PASS  beta7.19 migracija je recovery-safe i obračunava postojeći katalog
PASS  beta7.19 template servis podržava alias placeholdere
PASS  beta7.19 completeness servis vraća nepotpun aktivan artikal u nacrt
PASS  beta7.19 kloniranje čuva novi SKU i nulti lager
PASS  beta7.19 clone checkboxi eksplicitno šalju nulu
PASS  beta7.19 bulk zahteva pregled i blokira praznu operaciju
PASS  beta7.19 bulk promena brenda čisti neusklađenu liniju
PASS  beta7.19 preview naziva uklanja method spoof
PASS  beta7.19 doctor proverava šemu rute i kompletnost
PASS  beta7.19 feature test pokriva naziv klon i bulk
PASS  beta7.20 istorijska migracija ostaje sačuvana kao migration history
PASS  Product Variants forward decommission migracija postoji jednom
PASS  Product Variants decommission migracija ima recovery-safe rollback rekonstrukciju
PASS  Product Variants runtime klase su fizički uklonjene
PASS  Product Variants admin UI fajlovi su fizički uklonjeni
PASS  Porudžbine su product-only bez variant identiteta i snapshotova
PASS  Postprodaja garancija i stock movement su product-only
PASS  Inventory je product-only bez variants_enabled grane
PASS  Kataloški query filter i detalj su product-only
PASS  Product slike i model su product-only
PASS  Clone vise ne nudi niti obrađuje kopiranje varijanti
PASS  Product Variants Feature test sada proverava retired route i uklonjenu šemu
PASS  Product Variants UI contract sada zahteva potpuno uklonjen variant UI
PASS  Product Variants decommission smoke postoji kao završni regresioni guard
PASS  beta7.17 feature test pokriva dedupe rate zatvaranje i UI regresiju
PASS  beta7.21 migracija uvodi nabavne snapshotove i rasporede
PASS  beta7.21 migracija je recovery-safe i permission schema-aware
PASS  beta7.21 marža koristi snapshot i prikazuje pokrivenost troška
PASS  beta7.21 filteri važe za KPI trend i segmente
PASS  beta7.21 dashboard pokriva lager potraživanja postprodaju i tim
PASS  beta7.21 PDF upravljačkog izveštaja postoji
PASS  beta7.21 raspored ima retry dedupe i zasebne primaoce
PASS  beta7.21 ekran je bezbedan pre migracije
PASS  beta7.21 UI ima CSV PDF rasporede i cost coverage
PASS  beta7.22.1 management analytics koristi aktivnu temu bez belog fallback-a
PASS  beta7.22.1 CSS kompatibilni aliasi postoje
PASS  beta7.21 feature test pokriva ekran export i raspored
PASS  beta7.22 portal servis i fallback podaci postoje
PASS  beta7.22 portal objedinjuje porudžbine dokumente uplate garancije i servis
PASS  beta7.22 report grouping je kompatibilan sa ONLY_FULL_GROUP_BY
PASS  beta7.22 dashboard ima trend prioritete brze akcije i operativne module
PASS  beta7.22.1 portal doctor prosleđuje ViewErrorBag
PASS  beta7.22.1 layout bezbedno proverava errors bag
PASS  beta7.23 release-check komanda ima profile i kontrolisane režime
PASS  beta7.23 release registry ima quick standard i full profile
PASS  beta7.23 release plan ne dispatchuje poslovne akcije
PASS  beta7.23 release metadata i atomski JSON report postoje
PASS  beta7.23 release rezultat ima READY i NOT READY ugovor
PASS  beta7.23 smoke i dokumentacija postoje
PASS  beta7.23.1 catalog detail je product-only i nema retired variant Blade markere
PASS  beta7.23.1 catalog detail Blade direktive su izbalansirane
PASS  beta7.23.1 product-only detail Feature i smoke regresija postoje
PASS  beta7.23.1 health daje čitljive runtime remediation komande
PASS  beta7.23.2 detail doctor rešava controller zavisnosti kroz container
PASS  beta7.23.2 detail doctor nema direktan edit poziv sa jednim argumentom
PASS  beta7.23.2 detail doctor smoke i contract regresija postoje
PASS  beta7.24 migracija uvodi aktivacije sesije komunikaciju i order-link audit
PASS  beta7.24 aktivacioni token je hashiran jednokratan i vremenski ograničen
PASS  beta7.24 session registry koristi hash i podržava revoke
PASS  beta7.24 kupac vidi samo javne poruke a admin interne
PASS  beta7.24 portal rute aktivacija i admin centar postoje
PASS  beta7.24 komunikacija razdvaja public i internal
PASS  beta7.24 smoke i PHPUnit regresije postoje
PASS  beta7.24 maintenance čisti tokene i stare session evidencije
PASS  beta7.24 reinvite ne deaktivira aktivnog kupca i aktivacija nije cache-ovana
PASS  beta7.24.1 management repair obrađuje missing snapshotove
PASS  beta7.24.1 repair ne prepisuje kompletne snapshotove
PASS  beta7.24.1 repair je transakcioni i koristi row lock
PASS  beta7.24.1 kandidati imaju transparentan product-only izvor
PASS  beta7.24.1 ručna finansijska promena zahteva razlog i audit
PASS  beta7.24.1 audit/repair komanda i regresije postoje
PASS  rc1 profil sadrzi final hardening provere
PASS  rc1 security doctor proverava production debug HTTPS session i public fajlove
PASS  rc1 migration doctor proverava pending SQL mode i foreign keys
PASS  rc1 access doctor proverava route permission i superadmin
PASS  rc1 release integrity proverava SHA-256 i path traversal
PASS  rc1 backup verify je read-only i proverava SQL gzip i file hash
PASS  rc1 smoke i contract regresije postoje
PASS  rc1 nema novu migration datoteku
PASS  stable profil je identican potvrdenom rc profilu
PASS  stable smoke i contract regresije postoje
PASS  stable početna je univerzalni dashboard sa integrisanim korisničkim centrom
PASS  stable nema zasebnu Moj portal stranicu ni stavku menija
PASS  stable nema novu migration datoteku
PASS  v2.1.2 obaveštenja o novom artiklu su opt-in i koriste outbox
PASS  v2.1.2 novi artikal se šalje aktivnim registrovanim korisnicima bez duplikata
PASS  v2.1.2 mail podešavanja i šablon podržavaju nove artikle
PASS  v2.1.2 product announcement regresije postoje
PASS  v2.1.2 nema novu migration datoteku
PASS  APP_ENV production
PASS  Redis nije obavezan za database queue
PASS  file session/cache/limiter i database queue
PASS  secret vrednosti su prazne
PASS  import ne upisuje legacy konekciju
INFO  ZIP hygiene provere su preskočene na instaliranoj aplikaciji; za raspakovani sanitized ZIP koristi --package.
PASS  v2.1.3 tipovi proizvoda imaju posebne stranice i Drag & Drop
PASS  v2.1.3 specifikaciona polja mogu trajno da se obrišu
PASS  v2.1.3 tip automatski određuje kategoriju
PASS  v2.1.3 diskovi imaju pojedinačne celobrojne GB kapacitete
PASS  v2.1.3 catalog settings doctor postoji
PASS  v2.1.3 grana ima tri kontrolisane migration datoteke
PASS  v2.1.3.3 ProductRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 ProductVariantRequest je retired a ProductRequest zadržava validan SKU regex
PASS  v2.1.3.3 migracija povezuje listu diskova i izvedeni ukupni kapacitet
PASS  v2.1.3.3 stari kapacitet se bezbedno prenosi na prvi disk
PASS  v2.1.3.3 backend ne veruje ručnom ukupnom zbiru
PASS  v2.1.3.3 ukupni kapacitet je ispod diskova i readonly
PASS  v2.1.3.3 frontend sabira diskove i čuva početni legacy zbir
PASS  v2.1.3.3 product-only storage model zadržava izvedeni zbir bez variant servisa
PASS  v2.1.3.3 storage smoke i contract test postoje
PASS  v2.1.4 migracija dodaje model proizvoda i usklađuje šablone
PASS  v2.1.4 model se validira čuva i koristi u nazivu
PASS  v2.1.4 forma ima model proizvoda posle linije
PASS  v2.1.4 trajno brisanje ima SKU potvrdu i izbor brisanja slika
PASS  v2.1.4 poslovna istorija blokira destruktivno brisanje
PASS  v2.1.4 semantički sistem tastera pokriva sve uloge
PASS  v2.1.4 route i stable doctor postoje
PASS  v2.1.4 smoke i contract test postoje
PASS  v2.1.4.1 controller priprema i prosledjuje orderedFields
PASS  v2.1.4.1 Blade bezbedno inicijalizuje orderedFields
PASS  v2.1.4.1 doctor renderuje formulare svih tipova
PASS  v2.1.4.1 smoke i contract test postoje
PASS  v2.1.5 globalni UX runtime štiti submit i nesačuvane izmene
PASS  v2.1.5 mobilni action dock koristi originalni submit
PASS  v2.1.5 validacija i accessibility markeri postoje
PASS  v2.1.5 dugi formulari su eksplicitno označeni
PASS  v2.1.5 sistemske error stranice postoje
PASS  v2.1.5 migracija koristi postojeće snaga-napajanja polje
PASS  v2.1.5 migracija postavlja napajanje u sredinu
PASS  v2.1.5 doctor proverava Blade, route akcije i napajanje
PASS  v2.1.5 stable release koristi render i repair
PASS  v2.1.5 smoke i contract test postoje
PASS  v2.1.6 migracija kreira snapshot istoriju i ciljane indekse
PASS  v2.1.6 Data Quality audit pokriva product-only katalog slike i specifikacije
PASS  v2.1.6 repair je nedestruktivan i preračunava izvedene vrednosti
PASS  v2.1.6 performance doctor proverava indekse cache i SQL pragove
PASS  v2.1.6 dashboard kešira schema metadata po requestu
PASS  v2.1.6 Data Quality Center rute i prikaz postoje
PASS  v2.1.6 katalog ima quality filtere
PASS  v2.1.6 doctor renderuje centar i pokreće performance audit
PASS  v2.1.6 Stable release uključuje render repair i strict
PASS  v2.1.6 smoke i contract test postoje
PASS  v2.2.0 bootstrap device catalog order i notification rute postoje
PASS  v2.2.0 bootstrap vraća permissions features i app policy
PASS  v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv
PASS  v2.2.0 API greške imaju stabilan envelope
PASS  v2.2.0 OpenAPI i Stable doctor su povezani

Ukupno: 983, neuspešno: 0
RC_cms_static_green=0
CMS_STATIC=PASS_983_983
OPENAPI_CMS_SHA=6a4ab29bee43be3af1fa9ff58466039ab5bf33cf3d7375e1d8dbb21e9f838577
OPENAPI_MOBILE_SHA=6a4ab29bee43be3af1fa9ff58466039ab5bf33cf3d7375e1d8dbb21e9f838577
OPENAPI_PACKAGE_SHA=6a4ab29bee43be3af1fa9ff58466039ab5bf33cf3d7375e1d8dbb21e9f838577
OPENAPI_PARITY=PASS_UNCHANGED_3_COPIES
TASK2_SCOPE_GUARDS=PASS_NO_PROFITABILITY_NO_MATCH_INFERENCE

============================================================
7. EXACT SOURCE STAGE, DIFF CHECK, PRODUCT VARIANTS NEGATIVE GUARD
============================================================
diff --git a/apps/cms/current/app/Services/Customer360Service.php b/apps/cms/current/app/Services/Customer360Service.php
new file mode 100644
index 0000000..e8343f5
--- /dev/null
+++ b/apps/cms/current/app/Services/Customer360Service.php
@@ -0,0 +1,352 @@
+<?php
+
+declare(strict_types=1);
+
+namespace App\Services;
+
+use App\Models\AfterSalesCase;
+use App\Models\CustomerCrmNote;
+use App\Models\Order;
+use App\Models\PortalConversation;
+use App\Models\PortalMessage;
+use App\Models\PortalOrderLinkHistory;
+use App\Models\ProductWarranty;
+use App\Models\User;
+use Illuminate\Database\Eloquent\Builder;
+use Illuminate\Support\Carbon;
+use Illuminate\Support\Collection;
+use Illuminate\Support\Facades\Schema;
+use Throwable;
+
+final class Customer360Service
+{
+    private const TIMELINE_LIMIT = 50;
+    private const CRM_NOTES_LIMIT = 50;
+    private const UNLINKED_BUYERS_MAX_LIMIT = 100;
+
+    /** @return array{summary:array<string,int|float|string|null>,timeline:array<int,array<string,mixed>>,crm_notes:array<int,array<string,mixed>>} */
+    public function build(User $customer): array
+    {
+        $commercialOrders = $this->commercialOrders($customer);
+        $ordersCount = (int) (clone $commercialOrders)->count();
+        $lifetimeRevenue = (float) (clone $commercialOrders)->sum('subtotal_rsd');
+        $outstanding = (float) ((clone $commercialOrders)
+            ->selectRaw('COALESCE(SUM(CASE WHEN subtotal_rsd > COALESCE(paid_total_rsd, 0) THEN subtotal_rsd - COALESCE(paid_total_rsd, 0) ELSE 0 END), 0) AS total')
+            ->value('total') ?? 0);
+        $lastPurchase = (clone $commercialOrders)->orderByDesc('created_at')->value('created_at');
+
+        return [
+            'summary' => [
+                'orders_count' => $ordersCount,
+                'lifetime_revenue_rsd' => $lifetimeRevenue,
+                'average_order_value_rsd' => $ordersCount > 0 ? $lifetimeRevenue / $ordersCount : 0.0,
+                'outstanding_rsd' => $outstanding,
+                'last_purchase_at' => $this->timestamp($lastPurchase),
+                'active_after_sales_count' => $this->activeAfterSalesCount($customer),
+                'active_warranties_count' => $this->activeWarrantiesCount($customer),
+                'open_conversations_count' => $this->openConversationsCount($customer),
+                'unread_staff_messages_count' => $this->unreadStaffMessagesCount($customer),
+            ],
+            'timeline' => $this->timeline($customer),
+            'crm_notes' => $this->crmNotes($customer),
+        ];
+    }
+
+    /** @return Collection<int,array<string,mixed>> */
+    public function unlinkedBuyers(string $search = '', int $limit = 50): Collection
+    {
+        $limit = max(1, min($limit, self::UNLINKED_BUYERS_MAX_LIMIT));
+        $emailColumn = Schema::hasColumn('orders', 'shipping_email') ? 'shipping_email' : null;
+
+        $query = Order::query()->operational()->whereNull('user_id');
+        $search = trim($search);
+        if ($search !== '') {
+            $query->where(static function (Builder $orders) use ($search, $emailColumn): void {
+                $orders->where('order_number', 'like', '%'.$search.'%')
+                    ->orWhere('shipping_full_name', 'like', '%'.$search.'%')
+                    ->orWhere('shipping_phone', 'like', '%'.$search.'%')
+                    ->orWhere('shipping_address', 'like', '%'.$search.'%')
+                    ->orWhere('shipping_city', 'like', '%'.$search.'%')
+                    ->orWhere('shipping_postal_code', 'like', '%'.$search.'%');
+
+                if ($emailColumn !== null) {
+                    $orders->orWhere($emailColumn, 'like', '%'.$search.'%');
+                }
+            });
+        }
+
+        return $query->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get()
+            ->map(static fn (Order $order): array => [
+                'id' => (int) $order->id,
+                'number' => (string) $order->order_number,
+                'status' => (string) $order->status,
+                'name' => $order->shipping_full_name !== null ? (string) $order->shipping_full_name : null,
+                'email' => $emailColumn !== null && $order->getAttribute($emailColumn) !== null
+                    ? (string) $order->getAttribute($emailColumn)
+                    : null,
+                'phone' => $order->shipping_phone !== null ? (string) $order->shipping_phone : null,
+                'subtotal_rsd' => (float) $order->subtotal_rsd,
+                'created_at' => $order->created_at?->toIso8601String(),
+            ]);
+    }
+
+    private function commercialOrders(User $customer): Builder
+    {
+        return Order::query()
+            ->operational()
+            ->where('user_id', $customer->id)
+            ->where('status', '!=', 'cancelled');
+    }
+
+    private function activeAfterSalesCount(User $customer): int
+    {
+        return $this->safeCount('after_sales_cases', static fn (): int => AfterSalesCase::query()
+            ->whereHas('order', static fn (Builder $orders): Builder => $orders
+                ->operational()
+                ->where('user_id', $customer->id)
+                ->where('status', '!=', 'cancelled'))
+            ->whereNotIn('status', ['closed', 'rejected'])
+            ->count());
+    }
+
+    private function activeWarrantiesCount(User $customer): int
+    {
+        return $this->safeCount('product_warranties', static fn (): int => ProductWarranty::query()
+            ->ownedByOrderCustomer((int) $customer->id)
+            ->whereHas('order', static fn (Builder $orders): Builder => $orders
+                ->operational()
+                ->where('status', '!=', 'cancelled'))
+            ->where('status', 'active')
+            ->whereDate('expires_at', '>=', today())
+            ->count());
+    }
+
+    private function openConversationsCount(User $customer): int
+    {
+        return $this->safeCount('portal_conversations', static fn (): int => PortalConversation::query()
+            ->where('user_id', $customer->id)
+            ->where('status', '!=', 'closed')
+            ->count());
+    }
+
+    private function unreadStaffMessagesCount(User $customer): int
+    {
+        if (!Schema::hasTable('portal_conversations') || !Schema::hasTable('portal_messages')) {
+            return 0;
+        }
+
+        try {
+            return (int) PortalMessage::query()
+                ->whereHas('conversation', static fn (Builder $conversations): Builder => $conversations->where('user_id', $customer->id))
+                ->where('visibility', 'public')
+                ->where('sender_id', $customer->id)
+                ->whereNull('read_by_staff_at')
+                ->count();
+        } catch (Throwable) {
+            return 0;
+        }
+    }
+
+    /** @return array<int,array<string,mixed>> */
+    private function crmNotes(User $customer): array
+    {
+        if (!Schema::hasTable('customer_crm_notes')) {
+            return [];
+        }
+
+        try {
+            return CustomerCrmNote::query()
+                ->where('user_id', $customer->id)
+                ->with('author:id,first_name,last_name,username')
+                ->latest('created_at')
+                ->latest('id')
+                ->limit(self::CRM_NOTES_LIMIT)
+                ->get()
+                ->map(fn (CustomerCrmNote $note): array => [
+                    'id' => (int) $note->id,
+                    'body' => (string) $note->body,
+                    'created_at' => $note->created_at?->toIso8601String(),
+                    'author' => $this->actor($note->author),
+                ])
+                ->all();
+        } catch (Throwable) {
+            return [];
+        }
+    }
+
+    /** @return array<int,array<string,mixed>> */
+    private function timeline(User $customer): array
+    {
+        $events = collect();
+
+        try {
+            $this->commercialOrders($customer)->latest('created_at')->limit(self::TIMELINE_LIMIT)->get()
+                ->each(function (Order $order) use ($events): void {
+                    $events->push($this->event(
+                        'order',
+                        $order->created_at,
+                        'Porudžbina '.$order->order_number,
+                        (string) $order->status,
+                        (int) $order->id,
+                    ));
+                });
+        } catch (Throwable) {
+        }
+
+        if (Schema::hasTable('portal_order_link_history')) {
+            try {
+                PortalOrderLinkHistory::query()
+                    ->where(static function (Builder $links) use ($customer): void {
+                        $links->where('to_user_id', $customer->id)->orWhere('from_user_id', $customer->id);
+                    })
+                    ->with(['order:id,order_number', 'actor:id,first_name,last_name,username'])
+                    ->latest('created_at')
+                    ->limit(self::TIMELINE_LIMIT)
+                    ->get()
+                    ->each(function (PortalOrderLinkHistory $link) use ($events): void {
+                        $events->push($this->event(
+                            'order_link',
+                            $link->created_at,
+                            'Povezivanje porudžbine '.($link->order?->order_number ?? '#'.$link->order_id),
+                            $link->reason !== null ? (string) $link->reason : null,
+                            (int) $link->order_id,
+                            actor: $this->actor($link->actor),
+                        ));
+                    });
+            } catch (Throwable) {
+            }
+        }
+
+        if (Schema::hasTable('customer_crm_notes')) {
+            try {
+                CustomerCrmNote::query()
+                    ->where('user_id', $customer->id)
+                    ->with('author:id,first_name,last_name,username')
+                    ->latest('created_at')
+                    ->latest('id')
+                    ->limit(self::TIMELINE_LIMIT)
+                    ->get()
+                    ->each(function (CustomerCrmNote $note) use ($events): void {
+                        $events->push($this->event(
+                            'crm_note',
+                            $note->created_at,
+                            'CRM napomena',
+                            (string) $note->body,
+                            actor: $this->actor($note->author),
+                        ));
+                    });
+            } catch (Throwable) {
+            }
+        }
+
+        if (Schema::hasTable('after_sales_cases')) {
+            try {
+                AfterSalesCase::query()
+                    ->whereHas('order', static fn (Builder $orders): Builder => $orders->operational()->where('user_id', $customer->id))
+                    ->latest('updated_at')
+                    ->limit(self::TIMELINE_LIMIT)
+                    ->get()
+                    ->each(function (AfterSalesCase $case) use ($events): void {
+                        $events->push($this->event(
+                            'after_sales',
+                            $case->updated_at,
+                            'Slučaj '.$case->case_number,
+                            trim((string) $case->status.' · '.(string) $case->subject),
+                            (int) $case->order_id,
+                            afterSalesCaseId: (int) $case->id,
+                        ));
+                    });
+            } catch (Throwable) {
+            }
+        }
+
+        if (Schema::hasTable('portal_conversations')) {
+            try {
+                PortalConversation::query()
+                    ->where('user_id', $customer->id)
+                    ->latest('last_message_at')
+                    ->latest('id')
+                    ->limit(self::TIMELINE_LIMIT)
+                    ->get()
+                    ->each(function (PortalConversation $conversation) use ($events): void {
+                        $events->push($this->event(
+                            'conversation',
+                            $conversation->last_message_at ?? $conversation->created_at,
+                            (string) $conversation->subject,
+                            (string) $conversation->status,
+                            $conversation->order_id !== null ? (int) $conversation->order_id : null,
+                            (int) $conversation->id,
+                        ));
+                    });
+            } catch (Throwable) {
+            }
+        }
+
+        return $events
+            ->sortByDesc(static fn (array $event): string => (string) ($event['occurred_at'] ?? ''))
+            ->take(self::TIMELINE_LIMIT)
+            ->values()
+            ->all();
+    }
+
+    /** @return array{type:string,occurred_at:?string,title:string,summary:?string,order_id:?int,conversation_id:?int,after_sales_case_id:?int,actor:?array} */
+    private function event(
+        string $type,
+        mixed $occurredAt,
+        string $title,
+        ?string $summary,
+        ?int $orderId = null,
+        ?int $conversationId = null,
+        ?int $afterSalesCaseId = null,
+        ?array $actor = null,
+    ): array {
+        return [
+            'type' => $type,
+            'occurred_at' => $this->timestamp($occurredAt),
+            'title' => $title,
+            'summary' => $summary,
+            'order_id' => $orderId,
+            'conversation_id' => $conversationId,
+            'after_sales_case_id' => $afterSalesCaseId,
+            'actor' => $actor,
+        ];
+    }
+
+    /** @return array{id:int,name:string}|null */
+    private function actor(?User $user): ?array
+    {
+        if (!$user instanceof User) {
+            return null;
+        }
+
+        return ['id' => (int) $user->id, 'name' => $user->displayName()];
+    }
+
+    private function timestamp(mixed $value): ?string
+    {
+        if ($value === null || $value === '') {
+            return null;
+        }
+
+        try {
+            return $value instanceof \DateTimeInterface
+                ? Carbon::instance($value)->toIso8601String()
+                : Carbon::parse((string) $value)->toIso8601String();
+        } catch (Throwable) {
+            return null;
+        }
+    }
+
+    private function safeCount(?string $table, callable $callback): int
+    {
+        if ($table !== null && !Schema::hasTable($table)) {
+            return 0;
+        }
+
+        try {
+            return (int) $callback();
+        } catch (Throwable) {
+            return 0;
+        }
+    }
+}
diff --git a/apps/cms/current/bin/customer-360-contract-smoke.php b/apps/cms/current/bin/customer-360-contract-smoke.php
index 18bc2e5..ebc815b 100644
--- a/apps/cms/current/bin/customer-360-contract-smoke.php
+++ b/apps/cms/current/bin/customer-360-contract-smoke.php
@@ -2,12 +2,22 @@
 
 declare(strict_types=1);
 
+use App\Models\AfterSalesCase;
 use App\Models\CustomerCrmNote;
+use App\Models\Order;
+use App\Models\OrderItem;
+use App\Models\PortalConversation;
+use App\Models\PortalMessage;
+use App\Models\PortalOrderLinkHistory;
+use App\Models\ProductWarranty;
 use App\Models\User;
+use App\Services\Customer360Service;
 use Illuminate\Contracts\Console\Kernel;
 use Illuminate\Database\Eloquent\Relations\BelongsTo;
 use Illuminate\Database\Eloquent\Relations\HasMany;
 use Illuminate\Database\Eloquent\SoftDeletes;
+use Illuminate\Support\Carbon;
+use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Schema;
 
 require dirname(__DIR__).'/vendor/autoload.php';
@@ -50,5 +60,214 @@ $check($tableExists && !Schema::hasColumn('customer_crm_notes', 'deleted_at'), '
 $check(!str_contains($routesSource, 'customer-portal.users.crm-notes.update'), 'No CRM note update route exists');
 $check(!str_contains($routesSource, 'customer-portal.users.crm-notes.destroy'), 'No CRM note delete route exists');
 
+$serviceExists = class_exists(Customer360Service::class);
+$serviceSourcePath = dirname(__DIR__).'/app/Services/Customer360Service.php';
+$serviceSource = is_file($serviceSourcePath) ? file_get_contents($serviceSourcePath) : false;
+$serviceSource = is_string($serviceSource) ? $serviceSource : '';
+$check($serviceExists, 'Customer360Service exists');
+$check($serviceExists && method_exists(Customer360Service::class, 'build'), 'Customer360Service exposes build');
+$check($serviceExists && method_exists(Customer360Service::class, 'unlinkedBuyers'), 'Customer360Service exposes unlinkedBuyers');
+$check($serviceSource !== '' && str_contains($serviceSource, 'TIMELINE_LIMIT = 50'), 'Customer360 timeline is server-capped at 50');
+$check($serviceSource === '' || (!str_contains($serviceSource, "'gross_profit") && !str_contains($serviceSource, "'net_contribution") && !str_contains($serviceSource, "'ltv'") && !str_contains($serviceSource, "'gmroi'")), 'Customer360 read model has no profitability placeholders');
+
+if (!$serviceExists) {
+    $check(false, 'Customer360 aggregate fixture executes');
+    $check(false, 'Customer360 unlinked-buyer fixture executes');
+} else {
+    DB::beginTransaction();
+    try {
+        $token = 'C360'.strtoupper(bin2hex(random_bytes(5)));
+        $seedCustomer = User::query()
+            ->whereHas('role', static fn ($roles) => $roles->where('slug', 'user'))
+            ->first();
+        if (!$seedCustomer instanceof User) {
+            throw new RuntimeException('No registered customer template is available for transaction fixture');
+        }
+        $actor = User::query()
+            ->whereHas('role', static fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))
+            ->first() ?? $seedCustomer;
+        $orderTemplate = Order::query()->first();
+        $itemTemplate = OrderItem::query()->first();
+        if (!$orderTemplate instanceof Order || !$itemTemplate instanceof OrderItem) {
+            throw new RuntimeException('Order and order-item templates are required for transaction fixture');
+        }
+
+        $cloneCustomer = static function (User $seed, string $suffix) use ($token): User {
+            $customer = $seed->replicate();
+            $customer->username = strtolower($token.'_'.$suffix);
+            $customer->email = strtolower($token.'_'.$suffix).'@example.invalid';
+            $customer->phone = null;
+            $customer->status = 'active';
+            $customer->save();
+            return $customer;
+        };
+
+        $cloneOrder = static function (
+            Order $template,
+            string $suffix,
+            ?int $userId,
+            float $subtotal,
+            float $paid,
+            string $status,
+            string $createdAt,
+            bool $archived = false,
+        ) use ($token): Order {
+            $order = $template->replicate();
+            $order->order_number = $token.'-'.$suffix;
+            $order->idempotency_key_hash = null;
+            $order->request_fingerprint = null;
+            $order->user_id = $userId;
+            $order->status = $status;
+            $order->subtotal_rsd = $subtotal;
+            $order->paid_total_rsd = $paid;
+            $order->shipping_full_name = $token.' Buyer';
+            $order->shipping_phone = $token;
+            $order->archived_at = $archived ? now() : null;
+            $order->archived_by = null;
+            $order->archive_reason = null;
+            $order->purged_at = null;
+            $order->purged_by = null;
+            $order->purge_reason = null;
+            $order->cancelled_at = $status === 'cancelled' ? now() : null;
+            $order->cancelled_by = null;
+            $order->completed_at = null;
+            $order->completed_by = null;
+            $order->created_at = Carbon::parse($createdAt);
+            $order->updated_at = Carbon::parse($createdAt);
+            $order->save();
+            return $order;
+        };
+
+        $customer = $cloneCustomer($seedCustomer, 'main');
+        $emptyCustomer = $cloneCustomer($seedCustomer, 'empty');
+        $orderOne = $cloneOrder($orderTemplate, 'Q1', (int) $customer->id, 1000.0, 200.0, 'processing', '2026-09-01 10:00:00');
+        $orderTwo = $cloneOrder($orderTemplate, 'Q2', (int) $customer->id, 500.0, 800.0, 'processing', '2026-09-02 11:00:00');
+        $cloneOrder($orderTemplate, 'CANCELLED', (int) $customer->id, 700.0, 0.0, 'cancelled', '2026-09-03 12:00:00');
+        $cloneOrder($orderTemplate, 'ARCHIVED', (int) $customer->id, 900.0, 0.0, 'processing', '2026-09-04 13:00:00', true);
+        $unlinked = $cloneOrder($orderTemplate, 'UNLINKED', null, 1234.0, 0.0, 'new', '2026-09-05 14:00:00');
+        $archivedUnlinked = $cloneOrder($orderTemplate, 'UNLINKED-ARCHIVED', null, 4321.0, 0.0, 'new', '2026-09-06 15:00:00', true);
+
+        CustomerCrmNote::query()->create([
+            'user_id' => $customer->id,
+            'author_user_id' => $actor->id,
+            'body' => 'Task2 transaction CRM note '.$token,
+        ]);
+
+        PortalOrderLinkHistory::query()->create([
+            'order_id' => $orderOne->id,
+            'from_user_id' => null,
+            'to_user_id' => $customer->id,
+            'changed_by' => $actor->id,
+            'reason' => 'Task2 transaction link '.$token,
+            'created_at' => now(),
+        ]);
+
+        AfterSalesCase::query()->create([
+            'case_number' => $token.'-CASE',
+            'order_id' => $orderOne->id,
+            'opened_by' => $customer->id,
+            'assigned_to' => $actor->id,
+            'case_type' => 'complaint',
+            'priority' => 'normal',
+            'status' => 'open',
+            'subject' => 'Task2 '.$token,
+            'description' => 'Customer360 transaction fixture',
+        ]);
+
+        $conversation = PortalConversation::query()->create([
+            'user_id' => $customer->id,
+            'order_id' => $orderOne->id,
+            'assigned_to' => $actor->id,
+            'created_by' => $customer->id,
+            'subject' => 'Task2 '.$token,
+            'status' => 'waiting_staff',
+            'priority' => 'normal',
+            'last_message_at' => now(),
+        ]);
+        PortalMessage::query()->create([
+            'conversation_id' => $conversation->id,
+            'sender_id' => $customer->id,
+            'visibility' => 'public',
+            'body' => 'Task2 unread customer message '.$token,
+            'sent_at' => now(),
+            'read_by_customer_at' => now(),
+            'read_by_staff_at' => null,
+            'created_at' => now(),
+        ]);
+
+        $orderItem = $itemTemplate->replicate();
+        $orderItem->order_id = $orderOne->id;
+        $orderItem->product_sku = $token.'-SKU';
+        $orderItem->product_name = 'Task2 warranty product';
+        $orderItem->save();
+        ProductWarranty::query()->create([
+            'warranty_number' => $token.'-WAR',
+            'order_id' => $orderOne->id,
+            'order_item_id' => $orderItem->id,
+            'product_id' => $orderItem->product_id,
+            'user_id' => $customer->id,
+            'warranty_rule_id' => null,
+            'status' => 'active',
+            'starts_at' => today()->subDay(),
+            'expires_at' => today()->addYear(),
+            'duration_months' => 12,
+            'maintenance_interval_months' => null,
+            'customer_name_snapshot' => $customer->displayName(),
+            'customer_address_snapshot' => null,
+            'customer_city_snapshot' => null,
+            'customer_postal_code_snapshot' => null,
+            'customer_phone_snapshot' => null,
+            'product_sku_snapshot' => $orderItem->product_sku,
+            'product_name_snapshot' => $orderItem->product_name,
+            'quantity' => 1,
+            'serial_numbers_json' => null,
+            'terms_snapshot' => 'Task2 transaction warranty',
+            'created_by' => $actor->id,
+        ]);
+
+        $service = new Customer360Service();
+        $result = $service->build($customer);
+        $summary = $result['summary'] ?? [];
+        $check(($summary['orders_count'] ?? null) === 2, 'Customer360 excludes cancelled and archived orders');
+        $check(abs((float) ($summary['lifetime_revenue_rsd'] ?? -1) - 1500.0) < 0.001, 'Customer360 lifetime revenue sums qualifying subtotal');
+        $check(abs((float) ($summary['average_order_value_rsd'] ?? -1) - 750.0) < 0.001, 'Customer360 average order value is revenue divided by qualifying orders');
+        $check(abs((float) ($summary['outstanding_rsd'] ?? -1) - 800.0) < 0.001, 'Customer360 outstanding clamps overpaid order at zero');
+        $check(is_string($summary['last_purchase_at'] ?? null) && str_starts_with((string) $summary['last_purchase_at'], '2026-09-02'), 'Customer360 last purchase uses newest qualifying order');
+        $check(($summary['active_after_sales_count'] ?? null) === 1, 'Customer360 counts active after-sales cases');
+        $check(($summary['active_warranties_count'] ?? null) === 1, 'Customer360 counts active warranties');
+        $check(($summary['open_conversations_count'] ?? null) === 1, 'Customer360 counts open portal conversations');
+        $check(($summary['unread_staff_messages_count'] ?? null) === 1, 'Customer360 counts unread customer-originated staff messages');
+
+        $empty = $service->build($emptyCustomer)['summary'] ?? [];
+        $check(($empty['orders_count'] ?? null) === 0, 'Customer360 no-order customer has zero orders');
+        $check(abs((float) ($empty['average_order_value_rsd'] ?? -1)) < 0.001, 'Customer360 no-order customer has zero average order value');
+        $check(array_key_exists('last_purchase_at', $empty) && $empty['last_purchase_at'] === null, 'Customer360 no-order customer has null last purchase');
+
+        $timeline = $result['timeline'] ?? [];
+        $expectedTimelineKeys = ['type', 'occurred_at', 'title', 'summary', 'order_id', 'conversation_id', 'after_sales_case_id', 'actor'];
+        $check(count($timeline) <= 50, 'Customer360 timeline is capped at 50 events');
+        $check(collect($timeline)->every(static fn (array $event): bool => array_keys($event) === $expectedTimelineKeys), 'Customer360 timeline uses canonical event shape');
+        $timelineTypes = collect($timeline)->pluck('type');
+        $check($timelineTypes->contains('order') && $timelineTypes->contains('order_link') && $timelineTypes->contains('crm_note') && $timelineTypes->contains('after_sales') && $timelineTypes->contains('conversation'), 'Customer360 timeline merges canonical event sources');
+        $check(collect($result['crm_notes'] ?? [])->contains(static fn (array $note): bool => str_contains((string) ($note['body'] ?? ''), $token)), 'Customer360 returns internal CRM notes');
+
+        $candidates = $service->unlinkedBuyers($token, 50);
+        $candidateIds = $candidates->pluck('id')->map(static fn ($id): int => (int) $id);
+        $check($candidateIds->contains((int) $unlinked->id), 'Customer360 unlinked buyers includes operational user_id null order');
+        $check(!$candidateIds->contains((int) $orderOne->id), 'Customer360 unlinked buyers excludes linked order');
+        $check(!$candidateIds->contains((int) $archivedUnlinked->id), 'Customer360 unlinked buyers excludes archived order');
+        $check($candidates->every(static fn (array $candidate): bool => !array_key_exists('suggested_user_id', $candidate) && !array_key_exists('match_score', $candidate) && !array_key_exists('confidence', $candidate)), 'Customer360 unlinked buyers exposes no inferred matching fields');
+        $check($candidates->every(static fn (array $candidate): bool => !array_key_exists('user_id', $candidate)), 'Customer360 unlinked buyers does not leak ownership suggestion');
+        $check($candidates->every(static fn (array $candidate): bool => array_keys($candidate) === ['id', 'number', 'status', 'name', 'email', 'phone', 'subtotal_rsd', 'created_at']), 'Customer360 unlinked buyers returns canonical order facts only');
+    } catch (\Throwable $exception) {
+        echo 'CUSTOMER360_FIXTURE_EXCEPTION='.get_class($exception).': '.$exception->getMessage().PHP_EOL;
+        $check(false, 'Customer360 transaction fixture completes without exception');
+    } finally {
+        if (DB::transactionLevel() > 0) {
+            DB::rollBack();
+        }
+    }
+}
+
 echo 'CUSTOMER360_CONTRACT_SMOKE='.$checks.'_CHECKS_'.($checks - $failures).'_PASS_'.$failures.'_FAIL'.PHP_EOL;
 exit($failures === 0 ? 0 : 1);
SOURCE_STAGED_SCOPE=PASS_EXACT_2_FILES
PRODUCT_VARIANTS_STAGED_GUARD=PASS

============================================================
8. REMOTE RACE, COMMIT, PUSH, POSTFETCH
============================================================

============================================================
RUN - git_fetch_source_race
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_source_race=0

============================================================
RUN - source_commit
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git commit -m feat(cms): add Customer360 read model
[main 1c1e5d9] feat(cms): add Customer360 read model
 2 files changed, 571 insertions(+)
 create mode 100644 apps/cms/current/app/Services/Customer360Service.php
RC_source_commit=0

============================================================
RUN - source_push
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git push origin main
To github.com:AldinAga/ald1n-project.git
   572ff49..1c1e5d9  main -> main
RC_source_push=0

============================================================
RUN - source_postfetch
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_source_postfetch=0
FINAL_LOCAL_HEAD=1c1e5d9393f328f6796304b081a9471891dabf92
FINAL_REMOTE_HEAD=1c1e5d9393f328f6796304b081a9471891dabf92

============================================================
9. FINAL WORKTREE CERTIFICATION
============================================================
FINAL_WORKTREE_MANIFESTS=PASS_KNOWN_HTACCESS_PLUS_CURRENT_REPORT460

============================================================
FINAL SUMMARY
============================================================
BATCH170_TASK2_V2_RESULT=PASS_RECOVERED_FROM_REPORT459
REPORT_NUMBER=460
FAILED_STAGE=NONE
ROOT_CAUSE=PHP_NULL_COALESCING_ASSERTION_FALSE_NEGATIVE
EVIDENCE_COMMIT=572ff49075d2faee25fdd80b0b7cdb1b5402c70e
TASK2_SOURCE_COMMIT=1c1e5d9393f328f6796304b081a9471891dabf92
PUSH_COMPLETED=YES
CUSTOMER360_READ_MODEL=PASS
CUSTOMER360_SERVICE_SHA=6f5e1c6f6a0af10bfb3d76e11adf9a1c776c14f9fce7526e546ae848e2714271
CUSTOMER360_SERVICE_UNCHANGED_DURING_RECOVERY=PASS
CUSTOMER360_SMOKE_SHA=a3b0ebe84c33939926861bba645d79ca7b99092903c6a066a13eb0c7c6f34a25
TEST_ASSERTION_FIX=PASS_ARRAY_KEY_EXISTS_AND_STRICT_NULL
TDD_RED=PASS_REPRODUCED_REPORT459_38_39
TDD_GREEN=PASS_39_39
TASK2_FIXTURE_ROLLBACK=PASS_COUNTS_IDENTICAL
CMS_STATIC=PASS_983_983
OPENAPI_PARITY=PASS_UNCHANGED_3_COPIES
API_SURFACE_CHANGE=NO
MOBILE_CONTRACT_PARITY=NOT_APPLICABLE_NO_API_SURFACE_CHANGE_TASK2
MOBILE_VISIBLE_WORKSPACE=PENDING_MANDATORY_BATCH171
CUSTOMER360_OVERALL_FEATURE_COMPLETE=NO
PRODUCT_VARIANTS=DECOMMISSIONED_GUARD_PASS
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO
REPORT_SEQUENCE_POLICY=ENABLED_NEXT_AFTER_460_IS_461
NEXT_ACTION=REPORT461_BATCH170_TASK3_CRM_MUTATION_CONTROLLER_ROUTES
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/460-BATCH170-TASK2-V2-NULL-ASSERTION-RECOVERY-20260919-140949.md

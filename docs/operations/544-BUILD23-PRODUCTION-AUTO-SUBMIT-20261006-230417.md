============================================================
544 - BUILD23 PRODUCTION AUTO-SUBMIT + 16 KB ARTIFACT ACCEPTANCE
============================================================
TIMESTAMP=20261006-230417
ROOT=/home/icaffeco/ald1n-project
PURPOSE=OWNER_AUTHORIZED_EXACTLY_ONE_BUILD23_PRODUCTION_AUTOSUBMIT_AND_FINAL_AAB_16KB_AUDIT
OWNER_AUTHORIZATION=EXPLICIT_USER_APPROVAL_IN_CHAT_2026_10_06
EXPECTED_DOCS_HEAD=3a5bc9be733cd3a49d217236fa5966cc762a80e1
CANONICAL_SOURCE_AUTHORITY=8a776f8b8820f51791d990ae436c782944525eac
EXPECTED_MOBILE_TREE=7d4056c540f6c0b9ced1b9a6a4a406fe475c0024
REPORT543_EXPECTED_SHA256=bf72c409873c4f6e06d5f6bf5b769f62f9574b8f9263f84c4aed84edc4693e59
APP_VERSION=1.0.0
PLANNED_VERSION_CODE=23
RUNTIME_VERSION=1.0.0-build17
BUILD_PROFILE=production
CHANNEL=production
SUBMIT_PROFILE=production
PLAY_TRACK=production
EAS_CLI_AUTHORITY=24.8.0_NEWER_OPERATIONS_AUTHORITY_OVERRIDES_STALE_AGENTS_24.7.0
EXACT_BUILD_COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package="eas-cli@24.8.0" -- eas build --platform android --profile production --auto-submit-with-profile production --non-interactive --wait --json
SECOND_BUILD_AUTO_RETRY=FORBIDDEN
OTA_PUBLISHED=NO
DATABASE_CHANGED=NO

GOOGLE_PLAY_RELEASE_NOTES_BEGIN
Poboljšana kompatibilnost i stabilnost aplikacije na Android uređajima.
Ažurirane sistemske komponente u okviru Expo SDK 57.
Dodatna interna poboljšanja pouzdanosti i performansi.
GOOGLE_PLAY_RELEASE_NOTES_END

============================================================
PHASE authority-reconstruction-and-owner-authorization
============================================================
+ git -C /home/icaffeco/ald1n-project fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
BRANCH=main
LOCAL_HEAD=3a5bc9be733cd3a49d217236fa5966cc762a80e1
REMOTE_HEAD=3a5bc9be733cd3a49d217236fa5966cc762a80e1
MOBILE_TREE_SOURCE=7d4056c540f6c0b9ced1b9a6a4a406fe475c0024
MOBILE_TREE_HEAD=7d4056c540f6c0b9ced1b9a6a4a406fe475c0024
BUILD_GIT_HEAD=3a5bc9be733cd3a49d217236fa5966cc762a80e1
CANONICAL_APP_SOURCE_COMMIT=8a776f8b8820f51791d990ae436c782944525eac
OWNER_AUTHORIZATION_GATE=PASS
REPORT543_ACTUAL_SHA256=bf72c409873c4f6e06d5f6bf5b769f62f9574b8f9263f84c4aed84edc4693e59
REPORT543_BINDING=PASS_EXACT_READY_FOR_OWNER_AUTHORIZATION

============================================================
PHASE worktree-and-release-input-preflight
============================================================

============================================================
PHASE failure
============================================================
BATCH_RESULT=FAIL
FAILED_STAGE=worktree
FAIL_REASON=Unexpected untracked files exist before build
BUILD_CREATION_ATTEMPTED=NO
NEW_BUILD_STARTED=NO
EAS_BUILD_ID=NONE
EAS_BUILD_STATUS=NONE
EAS_BUILD_GIT_COMMIT=NONE
SUBMISSION_ID=NONE
SUBMISSION_STATUS=NONE
AAB_PATH=NONE
AAB_SHA256=NONE
BUNDLETOOL_ALIGNMENT=NOT_RUN
ELF_16KB_STATUS=NOT_RUN
SECOND_BUILD_AUTO_RETRY=FORBIDDEN
OTA_PUBLISHED=NO
DATABASE_CHANGED=NO
REPORT=544-BUILD23-PRODUCTION-AUTO-SUBMIT-20261006-230417.md

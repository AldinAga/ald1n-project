
============================================================
480 - BATCH178 V2 EXISTING BUILD RECOVERY
============================================================
TIMESTAMP=20260924-090456
TASK=RECOVER_SINGLE_FINAL_EAS_PRODUCTION_BUILD_AFTER_LOCAL_SIGHUP
REPORT_NUMBER=480
REPORT_REVISION=V2
RECOVERY_REASON=V1_LOCAL_SHELL_SIGHUP_AFTER_REMOTE_BUILD_CREATED
EXPECTED_HEAD=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176
EXPECTED_PARENT=a3bf34c263f8b9c3cf3d2f376d80337950e96d72
FROZEN_REPOSITORY_BASE=a3bf34c263f8b9c3cf3d2f376d80337950e96d72
FROZEN_APPLICATION_SOURCE=0e1035d0ab60108e0648336f14e5b61daed74e3d
EXPECTED_BUILD_ID=95172337-cf7b-4614-8e17-b7e4e9b955ab
EXPECTED_ANDROID_VERSION_CODE=18
APPLICATION_SOURCE_MUTATION=NO
DATABASE_MUTATION=NO
OTA_ACTION=NO
EAS_SUBMIT_COMMANDS_RUN=0
GOOGLE_PLAY_ACTION=NO
EAS_BUILD_CREATION_COMMANDS_RUN_THIS_RECOVERY=0
REPORT_ARCHIVE_POLICY=APPEND_ONLY
REPORT_CANONICAL_DIRECTORY=/home/icaffeco/ald1n-project/docs/operations

============================================================
0. EXACT V1 FAILURE AUTHORITY, GIT STATE AND APPEND-ONLY REPORT SEQUENCE
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
LOCAL_HEAD=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176
REMOTE_HEAD=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176
HEAD_PARENT=a3bf34c263f8b9c3cf3d2f376d80337950e96d72
HEAD_SUBJECT=docs: freeze v1.0 final release candidate
PRIOR_FAILED_REPORT=/home/icaffeco/ald1n-project/docs/operations/480-BATCH178-SINGLE-FINAL-EAS-PRODUCTION-BUILD-FAILED-20260923-234016.md
PRIOR_FAILED_REPORT_SHA256=fee2804c0f40a33fd1c5e04c6dddd46eb0252b556bf5e15943272d83fa8e9037
PRIOR_FAILED_REPORT_EXPECTED_SHA256=fee2804c0f40a33fd1c5e04c6dddd46eb0252b556bf5e15943272d83fa8e9037
PRIOR_FAILED_V1_EVIDENCE=PASS_BUILD_CREATED_BEFORE_LOCAL_SIGHUP
V1_MOBILE_GATE_EVIDENCE=PASS_REUSED_TYPECHECK_VALIDATOR_RELEASE_METADATA
NUMBERED_REPORTS_IN_OPERATIONS_BEFORE=489
NUMBERED_REPORTS_IN_OPERATIONS_EXPECTED_BEFORE=489
NUMBERED_REPORTS_IN_OPERATIONS_EXPECTED_AFTER=490
EXISTING_REPORT480_COUNT=1
WORKTREE_DIRTY_BEFORE_BEGIN
 M apps/cms/current/public/.htaccess
?? docs/operations/480-BATCH178-SINGLE-FINAL-EAS-PRODUCTION-BUILD-FAILED-20260923-234016.md
WORKTREE_DIRTY_BEFORE_END
HTACCESS_SHA256_BEFORE=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
FROZEN_APPLICATION_SOURCE_TO_CURRENT_HEAD=PASS_NO_APPLICATION_DIFF

============================================================
1. EAS TOOLING, AUTH AND REMOTE VERSION 18 AUTHORITY
============================================================

============================================================
RUN - eas_version
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=eas_mobile --version
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

eas-cli/23.2.0 linux-x64 node-v22.23.2
RC_eas_version=0
EAS_CLI_VERSION_PIN=PASS_23.2.0

============================================================
RUN - eas_whoami
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=eas_mobile whoami
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

(node:74268) [UnparsedCommand] Warning: Command account:view did not parse its arguments. Did you forget to call 'this.parse'?
(Use `node --trace-warnings ...` to show where the warning was created)
ald1n
pruzljanin@gmail.com

Accounts:
• ald1n (Role: Owner)
• ald1ns-team (Role: Owner)
RC_eas_whoami=0
EAS_AUTH=PASS

============================================================
RUN - eas_remote_version_current
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=eas_mobile build:version:get --platform android --profile production --json --non-interactive
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

Resolved "production" environment for the build. Learn more: https://docs.expo.dev/eas/environment-variables/#setting-the-environment-for-your-builds
Environment variables with visibility "Plain text" and "Sensitive" loaded from the "production" environment on EAS: EXPO_PUBLIC_API_URL, EXPO_PUBLIC_APP_ENV.
Environment variables loaded from the "production" build profile "env" configuration: EXPO_PUBLIC_APP_ENV, EXPO_PUBLIC_API_URL.
The following environment variables are defined in both the "production" build profile "env" configuration and the "production" environment on EAS: EXPO_PUBLIC_API_URL, EXPO_PUBLIC_APP_ENV. The values from the build profile configuration will be used.

{
  "versionCode": "18"
}
RC_eas_remote_version_current=0
EAS_REMOTE_ANDROID_VERSION_CURRENT=18
REMOTE_VERSION_AUTHORITY=PASS_V1_ALREADY_INCREMENTED_17_TO_18

============================================================
2. RESOLVE EXACT EXISTING BUILD18 - NO NEW BUILD COMMAND
============================================================

============================================================
RUN - eas_build_view_1
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=eas_mobile build:view 95172337-cf7b-4614-8e17-b7e4e9b955ab --json
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

⠋ Fetching the build…⠙ Fetching the build…⠹ Fetching the build…⠸ Fetching the build…✔ Found a matching build for the project @ald1n/ald1n-mobile
{
  "id": "95172337-cf7b-4614-8e17-b7e4e9b955ab",
  "status": "FINISHED",
  "platform": "ANDROID",
  "artifacts": {
    "buildUrl": "https://expo.dev/artifacts/eas/6TzqkM331qdIKDtnzj-APO3Vyo7DLuResXTnTMCMefc.aab",
    "applicationArchiveUrl": "https://expo.dev/artifacts/eas/6TzqkM331qdIKDtnzj-APO3Vyo7DLuResXTnTMCMefc.aab"
  },
  "fingerprint": {
    "id": "01a08277-bdf5-78da-9692-00c552a55160",
    "hash": "3f0f98cf14a1e8056536ce33d3091757130cb273"
  },
  "initiatingActor": {
    "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
    "displayName": "ald1n"
  },
  "logFiles": [
    "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/95172337-cf7b-4614-8e17-b7e4e9b955ab/2026-09-23T21%3A42%3A20Z-fbda5ac5-d2b7-4ea8-960e-57d50d5cfc89.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070538Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=61ce962cb70d3a573214f3fd5a837b57fa981ad4f775ca24580c1170c0c7667a093ce08912bb85da3f17053cc26d7059c06315452858acaeff962d8e758fd906d03f1615a27ea1aa0b6c4db63a23d877caa7860b823973448c9d77a93506bcc8dee78370504eacaabe4fcda6ba0e1a583524c44b9511183b99939b97e03f5fb97d54c2950341793ceb26d3dbbd1417f6472b513eadbae8f676326869a5b9fd397c4ca60e5e627ea7d9eb06843997697b848d6d07e418e62e9539fbb2a35bb6354309280cac4429b81654005f92a6336d9434925f833829750dceff35d78664080ee6ae593f6d6b8ef475ff8741d91f52821829efd9e9a869f4fa2ea00a03279f"
  ],
  "app": {
    "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
    "name": "ald1n-mobile",
    "slug": "ald1n-mobile",
    "ownerAccount": {
      "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
      "name": "ald1n"
    }
  },
  "updateChannel": {
    "id": "019fff34-7160-73a1-a86b-139b903939f7",
    "name": "production"
  },
  "distribution": "STORE",
  "buildProfile": "production",
  "appIdentifier": "com.ald1n.mobile",
  "sdkVersion": "57.0.0",
  "appVersion": "1.0.0",
  "appBuildVersion": "18",
  "runtime": {
    "id": "01a08277-bde9-759f-b365-c6083fd7793a",
    "version": "1.0.0-build17"
  },
  "gitCommitHash": "1aa4eaf45e02cdb8918fbd43608beb4ad6bea176",
  "gitCommitMessage": "docs: freeze v1.0 final release candidate",
  "priority": "HIGH",
  "createdAt": "2026-09-23T21:42:16.974Z",
  "updatedAt": "2026-09-23T22:06:04.312Z",
  "message": "Batch178 final v1.0 frozen production build 1aa4eaf4 20260923-234016",
  "completedAt": "2026-09-23T22:06:04.088Z",
  "expirationDate": "2026-10-23T21:42:17.003Z",
  "isForIosSimulator": false,
  "metrics": {
    "buildWaitTime": 3249,
    "buildQueueTime": 5316,
    "buildDuration": 1418549
  }
}
RC_eas_build_view_1=0
ID=95172337-cf7b-4614-8e17-b7e4e9b955ab
STATUS=FINISHED
PLATFORM=ANDROID
DISTRIBUTION=STORE
PROFILE=production
APP_VERSION=1.0.0
APP_BUILD_VERSION=18
GIT_COMMIT=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176
RUNTIME_VERSION=1.0.0-build17
CHANNEL=production
MESSAGE=Batch178 final v1.0 frozen production build 1aa4eaf4 20260923-234016
ARTIFACT_URL=https://expo.dev/artifacts/eas/6TzqkM331qdIKDtnzj-APO3Vyo7DLuResXTnTMCMefc.aab
BUILD_VIEW_ATTEMPT=1 STATUS=FINISHED
EXISTING_BUILD18_EXACT_AUTHORITY=PASS_ID_STATUS_PLATFORM_PROFILE_VERSION_RUNTIME_COMMIT

============================================================
RUN - eas_build_list_verify
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=eas_mobile build:list --platform android --build-profile production --limit 50 --json --non-interactive
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

[
  {
    "id": "95172337-cf7b-4614-8e17-b7e4e9b955ab",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/6TzqkM331qdIKDtnzj-APO3Vyo7DLuResXTnTMCMefc.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/6TzqkM331qdIKDtnzj-APO3Vyo7DLuResXTnTMCMefc.aab"
    },
    "fingerprint": {
      "id": "01a08277-bdf5-78da-9692-00c552a55160",
      "hash": "3f0f98cf14a1e8056536ce33d3091757130cb273"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/95172337-cf7b-4614-8e17-b7e4e9b955ab/2026-09-23T21%3A42%3A20Z-fbda5ac5-d2b7-4ea8-960e-57d50d5cfc89.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=6f1c4338e8fb25df8c4c674b0e8bc8af912c098813e02aab0514ca028f9d41c91c50d70a3fd0edeadea60c68493f11b211d287d7d8adb1e8fc98bb5c87320b1fc1da97b37234a89cfb7b8bf5b68295bee48c33864c8bb01e9fc38e7fb6482c9dad1f637872ec41e4f06337621697e8fda6ce933a07d77ee4423baabf7efb7d0e9f6f1e7d849cee31354dc9e184cb3a65dd3a17595bbe16165f0da82a315b38b66fe57368ae26f35cfb8df16f811d873bd4b5154306d94be8fc6a5303cbff6d819a94b904001ff31dc4f5d255ab7308bbbe2964d31febe9ccd74b0ab9ee38e0c386688f2cd2f240af2f11842ae9d31e91655b9ffd723204f6f7cd9aa9c7b5a173"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "18",
    "runtime": {
      "id": "01a08277-bde9-759f-b365-c6083fd7793a",
      "version": "1.0.0-build17"
    },
    "gitCommitHash": "1aa4eaf45e02cdb8918fbd43608beb4ad6bea176",
    "gitCommitMessage": "docs: freeze v1.0 final release candidate",
    "priority": "HIGH",
    "createdAt": "2026-09-23T21:42:16.974Z",
    "updatedAt": "2026-09-23T22:06:04.312Z",
    "message": "Batch178 final v1.0 frozen production build 1aa4eaf4 20260923-234016",
    "completedAt": "2026-09-23T22:06:04.088Z",
    "expirationDate": "2026-10-23T21:42:17.003Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 3249,
      "buildQueueTime": 5316,
      "buildDuration": 1418549
    }
  },
  {
    "id": "7f3b4381-a7f9-4d01-9312-2b7754b8cb01",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/vEV2WAnfEUfKhkOmscQKZvFmmU0F_KtNXTOne-qYglw.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/vEV2WAnfEUfKhkOmscQKZvFmmU0F_KtNXTOne-qYglw.aab"
    },
    "fingerprint": {
      "id": "01a08277-bdf5-78da-9692-00c552a55160",
      "hash": "3f0f98cf14a1e8056536ce33d3091757130cb273"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/7f3b4381-a7f9-4d01-9312-2b7754b8cb01/2026-09-08T19%3A21%3A25Z-5f7e9597-469a-4060-b50a-30cfa161f828.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=10059fee4bd5578eb697cc8680ed68eda1d8fdb6f4953a6d3cbe9eace06c6f58a40a3c3b636ddc4ac4a334373d2f66980956519bbdd9560444d384fcc56774b6ee2827f700fece0b56d73439f768a167a37f0c90d5f8fe05b49fe9ab061e8245c8b00d3a5e1b98c0f3e77445de4af3adfeba703770b1cd83f1ec5964742b7a4cf56adb837f817352c157283be39f9623faffd6cc006310b44120662d0ef10a9e13994bebca07faaee29d8d4bad0ae975358f16ba6c4c0f68a5da6c7ef4d5a72c18d67ae0c44e910b509f2e272b8ef91f13d4193aa5831a1cd4478ddd251ccb7eed0714749979c90d905b4689a270c209d89524e0816210765cd875354c8f6766"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "17",
    "runtime": {
      "id": "01a08277-bde9-759f-b365-c6083fd7793a",
      "version": "1.0.0-build17"
    },
    "gitCommitHash": "ff3153405873a338bb4435de926cf9ac1b77615e",
    "gitCommitMessage": "fix(build17): finalize catalog media hotfix after preflight recovery",
    "priority": "HIGH",
    "createdAt": "2026-09-08T19:21:20.579Z",
    "updatedAt": "2026-09-08T19:44:00.914Z",
    "completedAt": "2026-09-08T19:44:00.719Z",
    "expirationDate": "2026-10-08T19:21:20.616Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 4569,
      "buildQueueTime": 5165,
      "buildDuration": 1350406
    }
  },
  {
    "id": "d6bc1409-92b4-4d18-a252-a1ed9c5b6463",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/sDYKT5J9p5Kh5UB5G_4sGxv6Z9ZyK4hecwBaIBrLxkM.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/sDYKT5J9p5Kh5UB5G_4sGxv6Z9ZyK4hecwBaIBrLxkM.aab"
    },
    "fingerprint": {
      "id": "01a07c03-993f-77d9-9809-43130bfbce28",
      "hash": "422caadd3f540649d8f88fb969083c82ce048b28"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/d6bc1409-92b4-4d18-a252-a1ed9c5b6463/2026-09-07T13%3A16%3A50Z-24e8d897-35aa-40db-aef8-387fc8dbe649.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=260fa6de1dd9ee65aecd7616b7fe04cc5ee47b11aa87001fe307f87a466b11f558b0e1bd476e1be59b5ee10e7c3e31140eb4ec679f1ea64cd2bb7f89516dca8a40c67c24a946602163da8d92fe294a72c44994f7486c7db8bc91807b30e48b4eb6bffb29a77de6773a6fb75b49311afb8c635a16cd2e7fb49116f94dd283275e428526fb9c183eee39c96c960706956bfd963e275a5eecc50749869e8abee27a6cca02c35a7071aec661b9a4fdf2198478c09a2140e4526e4811354f4ef0aa23113de9171bca30ab45cecb50ba9902c9f2c1b077c85a18564baec3d1e2cdbc6427936a0ff7be5b04f7d8e25f13af7b16fdc8df31e1c510d1617ce69ad092a4be"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "16",
    "runtime": {
      "id": "01a03d1f-1d87-7e7b-8142-559e177f44d3",
      "version": "1.0.0"
    },
    "gitCommitHash": "7a35dd1d07fbb3d3da65d48b8237a85bccf04bbd",
    "gitCommitMessage": "chore(build16): align native palette for Build16 release",
    "priority": "HIGH",
    "createdAt": "2026-09-07T13:16:45.704Z",
    "updatedAt": "2026-09-07T13:42:29.764Z",
    "message": "Build16 final operator redesign source 7a35dd1d07fbb3d3da65d48b8237a85bccf04bbd",
    "completedAt": "2026-09-07T13:42:29.573Z",
    "expirationDate": "2026-10-07T13:16:45.742Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 4666,
      "buildQueueTime": 5394,
      "buildDuration": 1533809
    }
  },
  {
    "id": "d338c00c-4120-4277-9677-b1883eead02a",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/P4bDZ5QgtFE7jGCsYZV2lYAGUvklz1otBXKW8JPmKqc.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/P4bDZ5QgtFE7jGCsYZV2lYAGUvklz1otBXKW8JPmKqc.aab"
    },
    "fingerprint": {
      "id": "01a05792-570c-78fd-bba3-517010400466",
      "hash": "31bf30a47bd1d5bafc1b2731d0fcc79f1ecacf08"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/d338c00c-4120-4277-9677-b1883eead02a/2026-08-31T11%3A26%3A48Z-f1eb9466-5881-45f7-ab2a-a81d9d558604.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=73ba1a0de2fecd1a3e39eaf97ecf1ffd52b33351d04cf8d7586555715c7b1767c99337844646afb56b4df9aadc046909a66ab89767f6bb8f182706a220a143f6b2d64a47f153fdd2c053ba115b495b85f585f93f17967af1f613c1aaeb244943f3390561419dfbcab0265e72b84cbae26a2735a08e1e97f46e478759543ed14caa423c3127769caf28b5b6a7daae70de72a941fb85e0d92d12e0785c91013253dddfde624468db19d68320926bd37808ce239eab31b9a6b4a0bc3dfef81b29efb57490bcaaaee08c63090c8a5cd5156e457c6f5c546d50088ce7752700a82b3a21db3994756d96f258ab18b5718a70d030c9aeebdc54cad34f22697f4926c378"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "15",
    "runtime": {
      "id": "01a03d1f-1d87-7e7b-8142-559e177f44d3",
      "version": "1.0.0"
    },
    "gitCommitHash": "3b89d7fa72dd8d84547e477e50d65b89081828d7",
    "gitCommitMessage": "perf(mobile): optimize product image delivery",
    "priority": "HIGH",
    "createdAt": "2026-08-31T11:26:43.383Z",
    "updatedAt": "2026-08-31T11:50:51.081Z",
    "completedAt": "2026-08-31T11:50:50.885Z",
    "expirationDate": "2026-09-30T11:26:43.459Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 4775,
      "buildQueueTime": 4809,
      "buildDuration": 1437918
    }
  },
  {
    "id": "d139532d-9d91-4afd-b72b-74a473cd3232",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/OPq9Qgf3JsyZ_1PCFPMj4v-3HO0LP8TATL9G6UB7BYg.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/OPq9Qgf3JsyZ_1PCFPMj4v-3HO0LP8TATL9G6UB7BYg.aab"
    },
    "fingerprint": {
      "id": "01a04fe4-391b-7d17-ad6b-771113da6996",
      "hash": "49b582079834f9982f82c9f4dd6b762ab35d7dbb"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/d139532d-9d91-4afd-b72b-74a473cd3232/2026-08-29T23%3A39%3A13Z-5d2481fb-0b16-42b6-8416-2022db54aa89.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=2de2883fba38db97e98593c1771be711209a18dcff0891bf288e5d42f37e82e5684c22813e5ab1a8847f2f9974f6695cdb5952f9de233d1e4fadbc2b736fb25b1763e72b0d80ae33ba97eb6ba10db66cac38231ff273792df5054e2bdcba2b341257807c497836e777fff65329c3193401e9612d07bba66edf3b7dd76b1db00d2a45b58fab211e80ea932358fb0be67a399158b118b4c890e42e29e2dc5d439b98919132147e82bfd11271ebcad85f0ad4b144439ec5b3a1b081544c0ff5455d5691db39eb205ca29a5b18fab3b6ca5f8d9eea4cbfb3617a5593ef5f93f2faf9ae75f8959b43664ea58ebea37618ee688e4e4f028c177bcd21d2d1f7098a60b1"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "14",
    "runtime": {
      "id": "01a03d1f-1d87-7e7b-8142-559e177f44d3",
      "version": "1.0.0"
    },
    "gitCommitHash": "f0ade090f0f0fe176a3d5479429605b94e730836",
    "gitCommitMessage": "docs: certify Expo SDK 57 alignment before Build 14",
    "priority": "HIGH",
    "createdAt": "2026-08-29T23:39:11.978Z",
    "updatedAt": "2026-08-30T00:02:22.542Z",
    "completedAt": "2026-08-30T00:02:22.335Z",
    "expirationDate": "2026-09-28T23:39:12.016Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 1156,
      "buildQueueTime": 4776,
      "buildDuration": 1384425
    }
  },
  {
    "id": "97859c47-1199-4a82-b782-00d28f6f98c1",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/FBgk95DqNRrBaZbtLhv-SGs0JlUAiA-e2WVGPQNdfw8.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/FBgk95DqNRrBaZbtLhv-SGs0JlUAiA-e2WVGPQNdfw8.aab"
    },
    "fingerprint": {
      "id": "01a042a3-a3e8-7a61-b684-29530c84f551",
      "hash": "58b34d4e6db1fec8e02af6af65a838302263a4ff"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/97859c47-1199-4a82-b782-00d28f6f98c1/2026-08-27T20%3A10%3A32Z-79cd5f99-5bb0-43ee-98c9-1f059852974d.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=2e02e61d0a660f465871ecbb3dc87bd03aa0528c77218bba13f36a8444b8abfc9166c80e4dc273c72c36bedb5ccafc24dceafac2cfd5e91fc6ae44c51e8c160c153d6ab358584f3c316870b8f4c27c6c5d55dbfd028a60e6175beafc0e51b6320d73fbb40611a9d1ede82dafaf8472b0ab9e66c23ee3e2af99b99141cce28b19d9e305a2200e8f12c0aa591e329f757ad7df4d190876cf782af353f264ef6026266b507c997c1cf8e1267db855a9b733052e373ad1078957d68198c235bfb32c3657a06de49e8a96755ac82ff947b8babd0e5ffe3ea597265a5f602603c44fc8695e4cd9789efc138907344856045afb6eeaa29d6746ad0b71b526f5a868d2cd"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "13",
    "runtime": {
      "id": "01a03d1f-1d87-7e7b-8142-559e177f44d3",
      "version": "1.0.0"
    },
    "gitCommitHash": "61aa8bef2e2729ff280c8f910b4c2921a70a296c",
    "gitCommitMessage": "docs: certify admin order PDF hotfix before Android Build 13",
    "priority": "HIGH",
    "createdAt": "2026-08-27T20:10:30.955Z",
    "updatedAt": "2026-08-27T20:32:58.695Z",
    "completedAt": "2026-08-27T20:32:58.480Z",
    "expirationDate": "2026-09-26T20:10:30.984Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 1384,
      "buildQueueTime": 5314,
      "buildDuration": 1340827
    }
  },
  {
    "id": "1a5b21b6-4744-4c82-9d6f-9276e96708d9",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/icTB4ZGvP0_EquNOxEFykiIuZ7UNIAdrTxk-3Bk4dMs.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/icTB4ZGvP0_EquNOxEFykiIuZ7UNIAdrTxk-3Bk4dMs.aab"
    },
    "fingerprint": {
      "id": "01a042a3-a3e8-7a61-b684-29530c84f551",
      "hash": "58b34d4e6db1fec8e02af6af65a838302263a4ff"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/1a5b21b6-4744-4c82-9d6f-9276e96708d9/2026-08-27T18%3A55%3A53Z-e6ad3240-fc43-4259-a261-03269b39c3cc.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=852b3fa7888f67628d2dbacac041598ea5c56071fb20b694ce63aa496345ba643d8d89913fef46af8f1600eca07962dea0e94887669b06fa8e19018e31278a3b46ca58bcd7a45733c792a026346fe222278e47072f10d86bbc7ecfddc821c2ec2005b163d915208ab4e5ca21f3e08d32ddf23b73c6955f5e2b85451fded019d6b6adfe76c8095e319e795575f11a0191dd27aae7dcc575404c103409ea15d0223b3a005fb2286bddab92fd2d5461f67e42ca6d0b6672ed387b4b908292b73ce4b6b6544b0d661205fc9e0f9b6481b9e8ff559d0380c62efbfae116a04133d5275a5edcf94cd401f0eb172b7e881fe39523e1e24dca60d97555fc751b08f4ae6b"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "12",
    "runtime": {
      "id": "01a03d1f-1d87-7e7b-8142-559e177f44d3",
      "version": "1.0.0"
    },
    "gitCommitHash": "03a65d3e65160fb1f5286e55e8911c039ee7b592",
    "gitCommitMessage": "docs: certify Batch50 V4 before Android Build 12",
    "priority": "HIGH",
    "createdAt": "2026-08-27T18:55:49.940Z",
    "updatedAt": "2026-08-27T19:20:33.924Z",
    "message": "Ald1n CMS v1.0.0 Build 12 - personalization, centered Home navigation, theme/currency and loading motion",
    "completedAt": "2026-08-27T19:20:33.711Z",
    "expirationDate": "2026-09-26T18:55:49.974Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 2283,
      "buildQueueTime": 6321,
      "buildDuration": 1475167
    }
  },
  {
    "id": "8fb88863-6ddb-425b-8fd8-3e96b5ca1d9a",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/y75-gcVw2OHx5rZUHICpP8uR7BD1j2GN8wo4t5nN1A4.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/y75-gcVw2OHx5rZUHICpP8uR7BD1j2GN8wo4t5nN1A4.aab"
    },
    "fingerprint": {
      "id": "01a042a3-a3e8-7a61-b684-29530c84f551",
      "hash": "58b34d4e6db1fec8e02af6af65a838302263a4ff"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/8fb88863-6ddb-425b-8fd8-3e96b5ca1d9a/2026-08-27T11%3A43%3A10Z-7c330142-aaf3-45be-83f9-5da102bf7538.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=09f0095f6078f8c57ae00fae4a1cc561ba0c35fc0711591ee41ea669cb2244bbc81f073ef1a4d58cb32cb10218d9de1ddde62adf2f815bd6b9662a980444ae308f9c8adedcf657528fbb2cf2ddae1c1ad618b9af7f68e73476d81243a57e65c8c2150011b5071e7cf03a96c3f1c2e02bd74a8e3b783502ef6fa4bb460a9c50585362a6e99d7a2dee0ca9a49aee0d71451e3f5abf3ff00da5623614a948df784159a3f0ec68f19da36df1e9a14fe6cefd8457a751dcee2c23bfee4d610a6b836dfbab5dfd64352caf3a30206bfe2b9c11280eb821099e291a9a96e79111ccbe133d5612b169f97cbc05dee2531ddc0db375a9c282513e35882e3eaa9faa0ca782"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "11",
    "runtime": {
      "id": "01a03d1f-1d87-7e7b-8142-559e177f44d3",
      "version": "1.0.0"
    },
    "gitCommitHash": "f844326d79aeb73fe0ff11427cadacfe047d72de",
    "gitCommitMessage": "docs: certify bottom tab polish before Android Build 11",
    "priority": "HIGH",
    "createdAt": "2026-08-27T11:43:06.163Z",
    "updatedAt": "2026-08-27T12:06:55.160Z",
    "message": "Ald1n CMS v1.0.0 Build 11 - bottom tab active state polish",
    "completedAt": "2026-08-27T12:06:54.961Z",
    "expirationDate": "2026-09-26T11:43:06.192Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 3072,
      "buildQueueTime": 5573,
      "buildDuration": 1420153
    }
  },
  {
    "id": "b2289a4b-5a73-4f09-b737-72fc7d5f3ab6",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/RRa2J0MO5Y8zKIYlH8DlAPkT9ASEIZrLAms_g_KpHFE.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/RRa2J0MO5Y8zKIYlH8DlAPkT9ASEIZrLAms_g_KpHFE.aab"
    },
    "fingerprint": {
      "id": "01a042a3-a3e8-7a61-b684-29530c84f551",
      "hash": "58b34d4e6db1fec8e02af6af65a838302263a4ff"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/b2289a4b-5a73-4f09-b737-72fc7d5f3ab6/2026-08-27T09%3A53%3A41Z-14bcb036-e01f-4511-af6c-14834acdffd9.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=2b87904f64a9b8db1aeb3864cfcd4af1523b2fc5ea5ea2d2e6d5f9b90e75b417238c4946dd6fc004cf0daaa4566bc142250d451690040055f64c94d09f8a96953e7af9ace8a2c2ce840f573636f34e4bd75d975cd369b66b72d704969e23aa9a6d3d0ef574151907f971d6a2278cb02e72c2f845b20d251e16d2959bc6ec44c5b836c2bd6a2b9c7e3316bd33715ec027c3e14b36d816bd8733e1d1cb85b40ac7f9fbe4688a1a9820ce9b71da35b6632cb3dc830094e19b93e4ee1d3f68c11d5d6c33ecc0e16fd7f5296c5df1c7c44af5d1a2cf03c4c9547c7df5536101fb5491cdc1ac5a009c8b8ba86fd37b655adcc90218f5253bb73641c1f51cfd1b84a8b8"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "10",
    "runtime": {
      "id": "01a03d1f-1d87-7e7b-8142-559e177f44d3",
      "version": "1.0.0"
    },
    "gitCommitHash": "158f40d9c5ca61b2cd20ce0a1dd8b03608255b7e",
    "gitCommitMessage": "docs: certify Batch45 V5 before Android Build 10",
    "priority": "HIGH",
    "createdAt": "2026-08-27T09:53:35.688Z",
    "updatedAt": "2026-08-27T10:17:46.954Z",
    "message": "Ald1n CMS v1.0.0 Build 10 - catalog edit handoff performance + contrast hotfix",
    "completedAt": "2026-08-27T10:17:46.754Z",
    "expirationDate": "2026-09-26T09:53:35.712Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 3625,
      "buildQueueTime": 6351,
      "buildDuration": 1441090
    }
  },
  {
    "id": "30c4b443-334b-4da4-82e6-ec324e133dcc",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/cz-7o_LbQAFJRJtrE8tkziMxQteAHuJ_7zuTxRwe4zg.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/cz-7o_LbQAFJRJtrE8tkziMxQteAHuJ_7zuTxRwe4zg.aab"
    },
    "fingerprint": {
      "id": "01a03d1f-1d8c-7495-bff3-b4a7a121f3be",
      "hash": "37108ce0b54de5c79a53de656efcd6cf0c975736"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/30c4b443-334b-4da4-82e6-ec324e133dcc/2026-08-26T13%3A50%3A30Z-7b7bec28-9eb3-45c5-b8d2-093e8c96d4df.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=583e04a1df925c15641af43abf2a40d9a492f77e78b78ab3b8ff184f89f0aa2d3dd2e1aa5c60aa104c74490c2c756838a5a601729ff5bb3c1e2a9d27cce6b7c67d3d2abce1c4e70082b00d124811ab189c659156d3113a04108b4aba9ced7ba9b2384e99272fabc2187cf988311b3eda1584b8cdb65caff3ae1a0260c5809e4164e6a6479baaa644b4f935a3c8ec92fb9747f1200a8056dbb258cdc752d3a356b9283c4b1ebdbaa30aa02197243d7a31823294d94d6416351aa527772b9e5330946e7e193af3663e1c4cee71e708158bc485e854cda8eadaaf1fd4272d5473178842199fadb801475ba4593a66ee40c2afd5d7bda2fecc53c37a994117804bc1"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "9",
    "runtime": {
      "id": "01a03d1f-1d87-7e7b-8142-559e177f44d3",
      "version": "1.0.0"
    },
    "gitCommitHash": "e593fa500baf15290b147e491ef2aadb4e405d55",
    "gitCommitMessage": "docs: certify product edit scroll hotfix before Android Build 9",
    "priority": "HIGH",
    "createdAt": "2026-08-26T13:50:28.642Z",
    "updatedAt": "2026-08-26T14:15:24.165Z",
    "message": "Ald1n CMS v1.0.0 Build 9 - product edit scroll hotfix",
    "completedAt": "2026-08-26T14:15:23.971Z",
    "expirationDate": "2026-09-25T13:50:28.674Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 647,
      "buildQueueTime": 6299,
      "buildDuration": 1488383
    }
  },
  {
    "id": "71956351-4380-4a3d-8ea1-b7a728e04b8e",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/Re9h6zPMRgQKRzlbN60pkp9hkOae1RLosIIq247KTiM.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/Re9h6zPMRgQKRzlbN60pkp9hkOae1RLosIIq247KTiM.aab"
    },
    "fingerprint": {
      "id": "01a03d1f-1d8c-7495-bff3-b4a7a121f3be",
      "hash": "37108ce0b54de5c79a53de656efcd6cf0c975736"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/71956351-4380-4a3d-8ea1-b7a728e04b8e/2026-08-26T08%3A10%3A49Z-7135e1b4-48ed-46f8-a008-5f81056438e6.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=765e1606bfbed3da2e53c74b13b1ba9d50be9fb9a5cf3758046e42c50ba4b4f6ae648c85f123c75abc0990c67eee56febe0ddfb4cd465e150873428c8404a1548e121c5c89043d24f61c51862bd7af900eb77e5efe22ab9654540b5436a9ff42a8573aec8c8e833d0dac719ad351aa8a67d11bfcb29ebcc53002a897d00ef0deb3d2eda313c5e6c126852128814a4b104410b9cd355a17ab10ac2675cdf59bd02a7413bfd168972be76e206657d2771e755b89ca9187b2cba52feb39223f01bbc09e5da013475727e774ee48447dae7c894a8bfbfa59dc9e4d8afa6ca681020d3363cf5d394bab0a520b575839a90c43fe2642bd0b4157e29ce64203c1224a34"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "1.0.0",
    "appBuildVersion": "8",
    "runtime": {
      "id": "01a03d1f-1d87-7e7b-8142-559e177f44d3",
      "version": "1.0.0"
    },
    "gitCommitHash": "ec92abed3e57c7a1e577b1cd75c7a7751bf4e458",
    "gitCommitMessage": "docs: record final pre-AAB checkpoint and npx preflight failure",
    "priority": "HIGH",
    "createdAt": "2026-08-26T08:10:44.450Z",
    "updatedAt": "2026-08-26T08:34:07.198Z",
    "message": "Ald1n CMS v1.0.0 final production AAB - strict parity 62/62",
    "completedAt": "2026-08-26T08:34:07.034Z",
    "expirationDate": "2026-09-25T08:10:44.485Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 4673,
      "buildQueueTime": 4826,
      "buildDuration": 1393085
    }
  },
  {
    "id": "8dbe2ea5-d666-4d3c-9e88-9e6c1b6f85f6",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/q1NCurw-HLn1YZE9_LOj147v84tpQlqfcgE1rA3CFSc.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/q1NCurw-HLn1YZE9_LOj147v84tpQlqfcgE1rA3CFSc.aab"
    },
    "fingerprint": {
      "id": "01a030ac-a157-7b4f-9099-ae061c5c8aa3",
      "hash": "75308ff6b734e4d056160fd9c78549dc0b14f9f6"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/8dbe2ea5-d666-4d3c-9e88-9e6c1b6f85f6/2026-08-23T22%3A10%3A17Z-3e1e167f-114c-4b5a-bf1f-958df760948b.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=236b7460d74c02481d50236517f7d65a4593de00f810d79c9b03fe7a25ff5bddc12bc524990410d0b2e1d8d37e389b7f9dcb3c8cb2ff3d78f35009359752c8b1fac41396ab61ee0eb8c299b862c325ad4fa226cd2bb2291d2828b9edb150d32feb9a0869c2abfa767ae73aa364329428b7ba207c20ed8075ffc4ca010b1a4fb987077688aff7bcd3cc20275fecb90358e75f6b2e25047b6556b805b433b680892b33c8ebfda9305476892003dca93b7ea4616940419c557d8f85d6a165239f46e3889653b1c6141039e34579e70231f3fc3c699917d434a0b5432e6543d0889ba4b83baced407861daf321d6eeb4135d19519db4c987c5b31e0471dacf9e15bb"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "0.9.0",
    "appBuildVersion": "7",
    "runtime": {
      "id": "01a030ac-a14e-71a8-809a-16536182ad94",
      "version": "0.9.0"
    },
    "gitCommitHash": "9c6aad3e19455704bda487fd71edc6a476132d96",
    "gitCommitMessage": "v0.9.0 final prebuild evidence readiness PASS",
    "priority": "HIGH",
    "createdAt": "2026-08-23T22:10:14.964Z",
    "updatedAt": "2026-08-23T22:35:47.193Z",
    "completedAt": "2026-08-23T22:35:47.030Z",
    "expirationDate": "2026-09-22T22:10:14.990Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 2289,
      "buildQueueTime": 4593,
      "buildDuration": 1525184
    }
  },
  {
    "id": "fb4fd813-479b-47de-8f3b-9fc97395d38f",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/yHCCPJ1AlJlYjQI0Cbly9FcQ3qutMEDVI5EAC6-lB1Y.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/yHCCPJ1AlJlYjQI0Cbly9FcQ3qutMEDVI5EAC6-lB1Y.aab"
    },
    "fingerprint": {
      "id": "01a02467-0c91-756e-8c1b-a8bfbe45d1cc",
      "hash": "8895fe60023c30477cbd43427468c30039346697"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/fb4fd813-479b-47de-8f3b-9fc97395d38f/2026-08-21T13%3A02%3A23Z-4e73b180-e76a-4c31-8558-6df06f91d587.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=03dbed5ede5745d9cb7e906ab1fc7743c415e729bdaee63e5d4f4cc38c716bdadcb8ebc529767c2e3b0ad380d8f8903c85fcd7f05007cfd1c542789575649b9136e20fad20aaae81c557775e4bd62bcc1730dc1bba11ae89cab24994f737b8e3e82350379d7317ad8dd3f7f6c3b117ce40e18b91ffb455c829c5e04853a1af33ed9a8374e4292eead0f3aed2c489f1252fa7793fd2d9c7e34577d0bc7d39860328916293dd99545b39fd710719b06e646ee13b3340c312dcafe421c641e26f034809545dfd3dbc8d67d257b6791ae7f055f7115ee592cd4acfee2ed57b453dca23fa141cb0b283b2aa62f7899a7fca9a8e7a4d12cdc71ec8bfcbe03483e7a83e"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "0.8.0",
    "appBuildVersion": "5",
    "runtime": {
      "id": "01a02467-0c8b-7775-8b32-a86767f5216d",
      "version": "0.8.0"
    },
    "gitCommitHash": "dd41e0c61629e5b56da629bb053b448eaa76a034",
    "gitCommitMessage": "v0.8 release readiness PASS - quarantine prior 008 home npm contamination",
    "priority": "NORMAL",
    "createdAt": "2026-08-21T12:58:48.279Z",
    "updatedAt": "2026-08-21T13:21:20.598Z",
    "message": "v0.8.0 final certified Android production AAB",
    "completedAt": "2026-08-21T13:21:20.407Z",
    "expirationDate": "2026-09-20T12:58:48.331Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 3868,
      "buildQueueTime": 216467,
      "buildDuration": 1131793
    }
  },
  {
    "id": "3b4cc02e-aa53-4a84-885a-b72236908b41",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/6K_8WIPEjxhgntHgdOcPgbofpiABcmrRvIBUXDfJvxQ.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/6K_8WIPEjxhgntHgdOcPgbofpiABcmrRvIBUXDfJvxQ.aab"
    },
    "fingerprint": {
      "id": "01a01c0a-5a66-7952-ab86-e7aefadc0f62",
      "hash": "82c4829b30ed51548cbbbc8ad045863911347e7e"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/3b4cc02e-aa53-4a84-885a-b72236908b41/2026-08-19T22%3A00%3A38Z-62c5b938-c02d-44a3-9991-6bb2adf20575.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=95976eac5f75d2248cedd299326af5940c961f865888b7e9c5aed077a05dec15dc28e3b8f9cf787cd4e02b294641638defa1dcddb3f022f5231db96c89b38201689920619e1551c160220be74a541f57919c9ccfa5fe9f93c0cda5ceaba7e44fc839799034c9d48124b144847977ab0a3c0af7106a054c44469a5dbc57ba0336ceab2b52677444219432e8cec0c83456336f828da8f6eb619444c99d6f992a59626e290ce82b51c0e30a3714dbd6e9d903fe4695c396341ac6c4a027954e051ae3f18b470c944845d9e41ed611cc62b64c184be09cecb6f13c5adb330fd4d7985b18ec23cd053e8549b7da52d29dc4548e43b7ba3da35b4d9f378971c83045bd"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "0.7.0",
    "appBuildVersion": "4",
    "runtime": {
      "id": "01a01c0a-5a5f-7001-9759-58463207c164",
      "version": "0.7.0"
    },
    "gitCommitHash": "32c603979250cd8617d1331946bd5de034735e5c",
    "gitCommitMessage": "chore: add disposable project workspace",
    "priority": "NORMAL",
    "createdAt": "2026-08-19T22:00:35.647Z",
    "updatedAt": "2026-08-19T22:17:23.530Z",
    "message": "v0.7.0 final certified Android production AAB",
    "completedAt": "2026-08-19T22:17:23.376Z",
    "expirationDate": "2026-09-18T22:00:35.678Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 1685,
      "buildQueueTime": 5951,
      "buildDuration": 1000093
    }
  },
  {
    "id": "0d4c0d34-cc92-46dc-9c37-f1a12c551a63",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/al7WDFaeLmgL4BJHeLGArnb5RNCC81fv1aAHODkuTGs.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/al7WDFaeLmgL4BJHeLGArnb5RNCC81fv1aAHODkuTGs.aab"
    },
    "fingerprint": {
      "id": "01a010fb-e31e-7bb7-82f8-af4f17a40589",
      "hash": "fff2ecbb02d1494416c4efd979cc690c540827c7"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/0d4c0d34-cc92-46dc-9c37-f1a12c551a63/2026-08-17T18%3A29%3A53Z-0fbb636a-5f6b-4428-b899-7da21d4c88b2.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=62571e640a5cf0fa24d816a9de2fee08d673d41583de1342d0b36123b97b337c98e5fc601d53ac03ca43d34b0030883868fdeb3557347db355381b3fc88c6624a5afbd2a94f3645bb5e7dea5a302b7378db4619fc7e9b97e26ec567e31e1ca5342d2ab75ad92caa93047161f168a28ca2f99aee392c2b4c1c6837d509357728a5bea1a1dd812fb0e2215e730931f67ba2be10b895c063ad55694060d71b1f9fb15db0812557144ead2934bef749171ac0ce72d031da3e94b44df466d0a8e1adb5c488849def2ee58a26bcbcbf36545216dc682ebdd09a4cd8d9597734c06fbe01c5a654d2e49ff332039914e75293b8c25ab09178344440ec12e15a5ef03eec4"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "0.6.0",
    "appBuildVersion": "3",
    "runtime": {
      "id": "019ff6c5-dee0-7c5b-af28-5009ecf77b1e",
      "version": "0.6.0"
    },
    "gitCommitHash": "32c603979250cd8617d1331946bd5de034735e5c",
    "gitCommitMessage": "chore: add disposable project workspace",
    "priority": "NORMAL",
    "createdAt": "2026-08-17T18:28:58.231Z",
    "updatedAt": "2026-08-17T18:49:04.316Z",
    "completedAt": "2026-08-17T18:49:04.128Z",
    "expirationDate": "2026-09-16T18:28:58.260Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 2087,
      "buildQueueTime": 57316,
      "buildDuration": 1146494
    }
  },
  {
    "id": "bc0a0c59-4181-4bb8-a0f7-f0279fca96fe",
    "status": "FINISHED",
    "platform": "ANDROID",
    "artifacts": {
      "buildUrl": "https://expo.dev/artifacts/eas/nmAClXfxgSipQUdPCyQM22Ktct1CZY-9kGxe_HxnmTc.aab",
      "applicationArchiveUrl": "https://expo.dev/artifacts/eas/nmAClXfxgSipQUdPCyQM22Ktct1CZY-9kGxe_HxnmTc.aab"
    },
    "fingerprint": {
      "id": "019fff35-063e-72a4-8d4b-ac9a91a457aa",
      "hash": "009d5779bcc09929d727f8a8e56e27d4835adddc"
    },
    "initiatingActor": {
      "id": "da308684-07a9-4dfe-8cbb-aae0af6479b0",
      "displayName": "ald1n"
    },
    "logFiles": [
      "https://storage.googleapis.com/eas-workflows-production/logs/d43b3866-6838-4217-a23e-3dc7f2cc76cc/bc0a0c59-4181-4bb8-a0f7-f0279fca96fe/2026-08-14T07%3A38%3A15Z-32f50fa4-024d-4fc5-bea1-8758774d310d.txt?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=www-production%40exponentjs.iam.gserviceaccount.com%2F20260924%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260924T070549Z&X-Goog-Expires=900&X-Goog-SignedHeaders=host&X-Goog-Signature=90de63b6ce5f8d027aa8cf6867bce55ee41ff0b962e5c5de6aeb0a9f4280db4a7c7ac95f64c2781a5076f417de6b5e0449250caa519e54ccc3db4a785ca15ae3bd8dcd6c5bdbbed8c13ab0b31ed727e8bfadbfd3c3a99aa5161543ebd316a5d6760e8d5df5598b5914a7314f3f253114889fa53e85c4656bac71c611ae8523169996411b6f6bf9b468e3388962533fc6af8fb01c095d65e76bb2e3b39d1652eeca20cd5aaed08456b5d8b5839b4abdfa22edde31395ecc02b8a975e401e16a58390bf6f21cea54fbfdc55ee91de85cdd1cedd266d2ddc44ee5bdeff8a36ba742740babe2d4bab356222e4e97709f2e7572cba69d6cf4e1077e373f0b6fc70852"
    ],
    "app": {
      "id": "d43b3866-6838-4217-a23e-3dc7f2cc76cc",
      "name": "ald1n-mobile",
      "slug": "ald1n-mobile",
      "ownerAccount": {
        "id": "68885a1a-cfb1-4a59-849b-c96e985bfdf3",
        "name": "ald1n"
      }
    },
    "updateChannel": {
      "id": "019fff34-7160-73a1-a86b-139b903939f7",
      "name": "production"
    },
    "distribution": "STORE",
    "buildProfile": "production",
    "appIdentifier": "com.ald1n.mobile",
    "sdkVersion": "57.0.0",
    "appVersion": "0.6.0",
    "appBuildVersion": "2",
    "runtime": {
      "id": "019ff6c5-dee0-7c5b-af28-5009ecf77b1e",
      "version": "0.6.0"
    },
    "gitCommitHash": "32c603979250cd8617d1331946bd5de034735e5c",
    "gitCommitMessage": "chore: add disposable project workspace",
    "priority": "NORMAL",
    "createdAt": "2026-08-14T07:38:12.894Z",
    "updatedAt": "2026-09-18T18:37:00.324Z",
    "completedAt": "2026-08-14T07:57:04.061Z",
    "expirationDate": "2026-09-13T07:38:12.919Z",
    "isForIosSimulator": false,
    "metrics": {
      "buildWaitTime": 1476,
      "buildQueueTime": 25854,
      "buildDuration": 1103837
    }
  }
]
RC_eas_build_list_verify=0
CURRENT_HEAD_BUILD_MATCH_COUNT=1
EXPECTED_BUILD_ID_MATCH_COUNT=1
DUPLICATE_BUILD_GUARD=PASS_EXACTLY_ONE_EXISTING_BUILD_FOR_FROZEN_HEAD
EAS_BUILD_CREATION_COMMANDS_RUN_THIS_RECOVERY=0

============================================================
3. DOWNLOAD AND VERIFY FINAL BUILD18 AAB BYTE AUTHORITY
============================================================

============================================================
RUN - download_aab
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=curl -fL --retry 3 --retry-delay 2 -o /home/icaffeco/.ald1n-batch178-v2-existing-build-recovery-20260924-090456/Ald1n-CMS-v1.0.0-production-vc18-95172337-cf7b-4614-8e17-b7e4e9b955ab.aab https://expo.dev/artifacts/eas/6TzqkM331qdIKDtnzj-APO3Vyo7DLuResXTnTMCMefc.aab
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
  0     0    0     0    0     0      0      0 --:--:-- --:--:-- --:--:--     0100    81    0    81    0     0    385      0 --:--:-- --:--:-- --:--:--   387
100   618  100   618    0     0   1204      0 --:--:-- --:--:-- --:--:--  1204
  0 79.4M    0  704k    0     0   864k      0  0:01:34 --:--:--  0:01:34  864k100 79.4M  100 79.4M    0     0  48.7M      0  0:00:01  0:00:01 --:--:-- 96.6M
RC_download_aab=0
DOWNLOADED_AAB_SHA256=74dbb83780c0406c48bc89ec0fb23e7aaff9f0251a9c6dde05b12b0698fb378a
DOWNLOADED_AAB_SIZE_BYTES=83321037
AAB_ARCHIVE_CREATED=YES

============================================================
RUN - aab_zip_test
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=unzip -tq /home/icaffeco/backups/releases/Ald1n-CMS-v1.0.0-production-vc18-95172337-cf7b-4614-8e17-b7e4e9b955ab.aab
No errors detected in compressed data of /home/icaffeco/backups/releases/Ald1n-CMS-v1.0.0-production-vc18-95172337-cf7b-4614-8e17-b7e4e9b955ab.aab.
RC_aab_zip_test=0
AAB_PATH=/home/icaffeco/backups/releases/Ald1n-CMS-v1.0.0-production-vc18-95172337-cf7b-4614-8e17-b7e4e9b955ab.aab
AAB_SHA256=74dbb83780c0406c48bc89ec0fb23e7aaff9f0251a9c6dde05b12b0698fb378a
AAB_SIZE_BYTES=83321037
AAB_AUTHORITY=PASS_FRESH_DOWNLOAD_SHA256_SIZE_ZIP_MANIFEST_DEX

============================================================
4. POST-RECOVERY SOURCE IMMUTABILITY, REMOTE VERSION AND GIT RACE
============================================================

============================================================
RUN - eas_remote_version_after
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=eas_mobile build:version:get --platform android --profile production --json --non-interactive
★ eas-cli@24.7.0 is now available.
To upgrade, run:
npm install -g eas-cli
Proceeding with outdated version.

Resolved "production" environment for the build. Learn more: https://docs.expo.dev/eas/environment-variables/#setting-the-environment-for-your-builds
Environment variables with visibility "Plain text" and "Sensitive" loaded from the "production" environment on EAS: EXPO_PUBLIC_API_URL, EXPO_PUBLIC_APP_ENV.
Environment variables loaded from the "production" build profile "env" configuration: EXPO_PUBLIC_APP_ENV, EXPO_PUBLIC_API_URL.
The following environment variables are defined in both the "production" build profile "env" configuration and the "production" environment on EAS: EXPO_PUBLIC_API_URL, EXPO_PUBLIC_APP_ENV. The values from the build profile configuration will be used.

{
  "versionCode": "18"
}
RC_eas_remote_version_after=0
EAS_REMOTE_ANDROID_VERSION_AFTER=18
EAS_REMOTE_VERSION_TRANSITION=PASS_V1_17_TO_18_EXACTLY_ONCE_V2_NO_INCREMENT
APPLICATION_SOURCE_IMMUTABILITY=PASS
HTACCESS_SHA256_AFTER=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
PRIOR_FAILED_REPORT_SHA256_AFTER=fee2804c0f40a33fd1c5e04c6dddd46eb0252b556bf5e15943272d83fa8e9037
PREEXISTING_UNRELATED_DIRTY_STATE=PRESERVED_EXACT_STATUS_AND_CONTENT_HASH

============================================================
RUN - final_race_fetch
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_final_race_fetch=0
FINAL_RACE_LOCAL=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176
FINAL_RACE_REMOTE=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176

============================================================
5. RELEASE NOTES FOR GOOGLE PLAY INTERNAL TESTING
============================================================
RELEASE_NOTES_SR_BEGIN
Ald1n CMS v1.0.0 - finalni release kandidat
- Pojednostavljen i ujednacen mobilni interfejs sa novom Build18 UX organizacijom.
- Prosiren Customer360 pregled kupaca, CRM beleznice i profitabilnost po kupcu.
- Dodati napredni prodajni, profitabilnosni i lager izvestaji.
- Unapredjen katalog, galerija slika, administracija artikala i bezbedni Total Product Purge workflow.
- Prosireni tokovi porudzbina, direktne prodaje, provizija, potrazivanja, garancija i postprodaje.
- Zavrsna API/OpenAPI uskladjenost i stabilizacioni release guardovi.
RELEASE_NOTES_SR_END

============================================================
6. FINAL BUILD18 V2 RECOVERY SUMMARY
============================================================
BATCH178_RESULT=PASS_SINGLE_FINAL_EAS_PRODUCTION_BUILD_V2_EXISTING_BUILD_RECOVERY
BATCH178_V2_RESULT=PASS_EXISTING_BUILD18_RECOVERED_AFTER_LOCAL_SIGHUP
REPORT_NUMBER=480
REPORT_REVISION=V2
RECOVERY_REASON=V1_LOCAL_SHELL_SIGHUP_AFTER_REMOTE_BUILD_CREATED
PRIOR_FAILED_REPORT_PRESERVED=480-BATCH178-SINGLE-FINAL-EAS-PRODUCTION-BUILD-FAILED-20260923-234016.md
PRIOR_FAILED_REPORT_SHA256=fee2804c0f40a33fd1c5e04c6dddd46eb0252b556bf5e15943272d83fa8e9037
BUILD_REPOSITORY_COMMIT=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176
FROZEN_REPOSITORY_BASE=a3bf34c263f8b9c3cf3d2f376d80337950e96d72
FROZEN_APPLICATION_SOURCE_COMMIT=0e1035d0ab60108e0648336f14e5b61daed74e3d
EAS_CLI_VERSION_PIN=23.2.0
ORIGINAL_BUILD_CREATION_COMMANDS_RUN=1
EAS_BUILD_CREATION_COMMANDS_RUN_THIS_RECOVERY=0
EAS_BUILD_ID=95172337-cf7b-4614-8e17-b7e4e9b955ab
EAS_BUILD_STATUS=FINISHED
EAS_BUILD_PLATFORM=ANDROID
EAS_BUILD_DISTRIBUTION=STORE
EAS_BUILD_PROFILE=production
EAS_BUILD_APP_VERSION=1.0.0
EAS_BUILD_VERSION_CODE=18
EAS_BUILD_GIT_COMMIT=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176
EAS_BUILD_RUNTIME_VERSION=1.0.0-build17
EAS_BUILD_CHANNEL=production
EAS_REMOTE_ANDROID_VERSION_BEFORE_V1=17
EAS_REMOTE_ANDROID_VERSION_AFTER=18
EAS_REMOTE_VERSION_TRANSITION=PASS_V1_17_TO_18_EXACTLY_ONCE_V2_NO_INCREMENT
AAB_PATH=/home/icaffeco/backups/releases/Ald1n-CMS-v1.0.0-production-vc18-95172337-cf7b-4614-8e17-b7e4e9b955ab.aab
AAB_SHA256=74dbb83780c0406c48bc89ec0fb23e7aaff9f0251a9c6dde05b12b0698fb378a
AAB_SIZE_BYTES=83321037
AAB_AUTHORITY=PASS_DOWNLOADED_SHA256_SIZE_ZIP_MANIFEST_DEX
MOBILE_TYPECHECK=PASS_REUSED_FROM_EXACT_V1_REPORT_SOURCE_IMMUTABLE
MOBILE_VALIDATOR=PASS_ZERO_FAIL_REUSED_FROM_EXACT_V1_REPORT_SOURCE_IMMUTABLE
PRODUCT_VARIANTS=DECOMMISSIONED_GUARD_PRESERVED
APPLICATION_SOURCE_MUTATION=NO
DATABASE_MUTATION=NO
OTA_ACTION=NO
EAS_SUBMIT_COMMANDS_RUN=0
GOOGLE_PLAY_ACTION=NO
PREEXISTING_HTACCESS=PRESERVED_UNCHANGED
FINAL_BUILD_DELIVERY_STATUS=AAB_READY_FOR_GOOGLE_PLAY_INTERNAL_TESTING
NUMBERED_REPORTS_IN_OPERATIONS_BEFORE=489
NUMBERED_REPORTS_IN_OPERATIONS_EXPECTED_AFTER=490
REPORT_SEQUENCE_POLICY=APPEND_ONLY_NEXT_REPORT_NUMBER_481
NEXT_OPERATIONAL_BATCH=BATCH179_GOOGLE_PLAY_INTERNAL_AND_FINAL_PHYSICAL_DEVICE_ACCEPTANCE
NEXT_ACTION=UPLOAD_FINAL_VC18_AAB_TO_GOOGLE_PLAY_INTERNAL_THEN_RUN_FINAL_DEVICE_ACCEPTANCE
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/480-BATCH178-SINGLE-FINAL-EAS-PRODUCTION-BUILD-V2-EXISTING-BUILD-RECOVERY-20260924-090456.md

============================================================
7. DOCS-ONLY RECOVERY CHECKPOINT COMMIT AND PUSH
============================================================

============================================================
RUN - docs_race_fetch
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_docs_race_fetch=0
DOCS_RACE_LOCAL=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176
DOCS_RACE_REMOTE=1aa4eaf45e02cdb8918fbd43608beb4ad6bea176

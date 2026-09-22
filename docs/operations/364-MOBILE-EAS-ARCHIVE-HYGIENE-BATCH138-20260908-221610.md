# Report364 - Mobile EAS Archive Hygiene Batch138 V1

- Timestamp: 20260908-221610
- Purpose: synchronize root .easignore with canonical root .gitignore, preserve app build inputs, inspect the real Android archive without creating an EAS build, and commit only after archive/security/version gates pass
- Expected source authority: ff3153405873a338bb4435de926cf9ac1b77615e
- Build creation: FORBIDDEN
- OTA publish: FORBIDDEN
- Google Play submit: FORBIDDEN
- Database writes: NO

============================================================
0. SOURCE + TOOLCHAIN PREFLIGHT
============================================================
LOCAL_HEAD=ff3153405873a338bb4435de926cf9ac1b77615e
REMOTE_HEAD=ff3153405873a338bb4435de926cf9ac1b77615e
SOURCE_HEAD=PASS_EXACT_BUILD17_COMMIT
ROOT_GITIGNORE_BLOB=5ed94c7287f2b0bf42249f3de8cdd7157de91f40
ROOT_EASIGNORE_BLOB=0d4e4f55e44908b7c0837d8624f16cd6cd50f9ce
APP_EASIGNORE_BLOB=fbc79dcda0fff9458defde75a9f32aaeb85f5fdb
IGNORE_BASELINE=PASS_EXACT_GITHUB_AUTHORITY
FAIL: direct npm CLI missing

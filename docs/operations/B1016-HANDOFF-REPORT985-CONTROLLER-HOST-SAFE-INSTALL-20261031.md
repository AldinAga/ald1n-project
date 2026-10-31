# B1016 — B1015 Report985-controlled Build25 source restoration (2026-10-31)

## Actual owner evidence

Authentic uploaded owner Report985 `985-B1015-EAS25-HOST-SOURCE-RECONCILIATION-20261031-100848.md`, SHA256 `73bf15f877dec1c2c5bd0b2f13599a14f05a67821a690c8f835e8e32f3376b98`, states `RESULT=PASS_B1015_EAS25_HOST_SOURCE_ONLY`, `Terminal RC: 0`, direct submit `BLOCKED_78_PASS`, and three exact post-install source SHA256 values:

- package.json: `a378eac2694e1fc37b1ad2249bff92e7d2ac919a6861bef154b410055bc56d79`
- eas.json: `ac85460ecf05bafa008a3d2ba812eee9b6357d65f38bdc734254acdd3aac7e38`
- scripts/submit-android-production.mjs: `7ee6fed82901dd73c1bd6687adb8aa63d10e7f83d920b0fa51b39dcadb54886b`

Existing source HEAD remains `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`; actual host has many dirty CMS changes, protected. Build25 wrapper/controller still absent. Node ALT 22.23.4 and npm 10.9.9 verified. B1015 did not authorize native build, OTA, or Google Play submit.

## B1016 artifact

`B1016-ALD1N-BUILD25-CONTROLLER-HOST-SAFE-INSTALL.sh`

- Full script SHA256: `19ca09da3b05035393a1395f1a85641a64759d1d2bcfd09fdca7cb84038c5f10`
- Normalized self-integrity: `a8a01d4cc645202c7f84ac1b10b78f8d5573c355b60a48e299fed0ec07a8dac3`
- Download artifact is one self-contained Bash script, no GitHub network requirement, no npm installation; user places under canonical `/home/icaffeco/ald1n-project/incoming/` without renaming.
- Approved next SSH: `bash /home/icaffeco/ald1n-project/incoming/B1016-ALD1N-BUILD25-CONTROLLER-HOST-SAFE-INSTALL.sh --run`
- Required exact owner phrase after READY: `APPLY_B1016_CONTROLLER_FILES_ONLY`.

Four pinned remote GitHub main source blobs, verified byte-for-byte against Git SHA-1 objects:

| Destination under apps/mobile/current/scripts | Git blob SHA-1 | File SHA256 |
|---|---|---|
| build25-release.sh | `04dbdb47dcf6f23cdc0a33962bc75ddf274d7e5c` | `5dc4f3c81531667bcc268ac36344cfaba12b71b46fa1ea92e80b94325b95797b` |
| build25-release-controller.mjs | `4a0c094c8ce47ccc513f271eb4350d05e39ee631` | `83488227d561388c09744a7ba8ce10a06678075cf346ffd8868f0355cede690d` |
| release25/submit-gate.mjs | `1e5e3623593cf28a08b0bf6b047bda6e4984f849` | `4166ea792655892910a83a5331dd4aef629768cf0fd2bf26ac4c9867a8eb06c0` |
| release25/separated-phases.mjs | `f5487c5850b21162f739c5081b40e78e9317d2e0` | `8fa9204251bb2bc1739f4f468dbe4a1ea465f182fe78da54b4a9d0ac269267ac` |

## Scope and safety

B1016 creates only absent files that precisely match pinned GitHub source; any differing existing file or symlink is HOLD, never overwritten. Creates the `scripts/release25` directory only if absent. No modifications to `package.json`, `eas.json`, `submit-android-production.mjs`, CMS, Git refs, old roots, backups, npm installation, EAS build, OTA or Google Play. Independent offline SHA256 and Git blob checks, Node22/npm checks, original Report985 file checksum and three post-B1015 checksum guards. Creation uses exclusive hard-link installation to avoid clobbering target paths; on a failure after creation, deletes only its own unchanged newly installed files and removes its own new directory if empty. Does not remove pre-existing user files. Requires operator approval phrase before any source mutation; writes a sequential numbered operations report with captured stdout/return code.

Controller comes from GitHub main and its direct CLI invocation always returns `BUILD25_BLOCKED`, exit code 78; importing it and calling preflight without verified authority returns `BLOCKED` without invoking a preflight adapter. Direct Play submit helper still blocks `--execute` with exit 78. This does NOT certify a production release. Production native signer, 16 KiB AAB/device tests, source authority, remote build versions, and Play gates remain HOLD.

## Local isolated tests (not host proof)

- Git blob SHA1 verification: 4/4 PASS; Bash `-n`, MJS syntax, static banned-shell scan PASS.
- Full synthetic host install: PASS; all four source files created byte-identical; Node import/unauthorized CLI negative tests PASS, no builds.
- Second run of same fixture: PASS, all four existing files exact -> skipped (idempotent).
- Wrong owner approval: HOLD, no mutation.
- Symlink target in release25: HOLD, no mutation.
- Drift in B1015 package SHA: HOLD, no mutation.
- Tampered script selfhash: HOLD before report/mutation.
- Injected failure after four new files installed: HOLD, 4/4 created files removed and new directory removed, rollback PASS.

Host B1016 not executed; no claim of host PASS until new numbered owner report is received.

## Milestones

Formal acceptance unchanged: total 7/17=41.2%; CMS 7/15=46.7%. B1015 host source wiring PASS; B1016 planned source/controller delivery local QA PASS only. Next after a host B1016 PASS: isolated npm12.2.1/EAS CLI26 compatibility test in private home without global changes; Android 16 KB signed evidence/device+Play gate before SDK58. The previous B1013/R2/R3 scripts must not be rerun.

# B1013 — Report984 host EAS source decoupling; no build or legacy deletion (2026-10-31)

## New verified authority

- Owner CloudLinux Report984 (`984-B1011-ALT-NODE22-EAS25-PATH-20261031-090936.md`), SHA256 `b7b26555dbaef81c02e7bd3febb4a3e1669afed274a9d6a1c03e62836bcaeed0`, UTC `2026-10-31T09:09:36Z`: ALT Node v22.23.4 PASS, npm v10.9.9 PASS, `/usr/bin/env node` PASS and pinned `eas-cli@25.0.0 --version` PASS rc0 with a private child PATH. Host package SHA `ca996886464fc3fd33a90f1d0330e3d08101b9440eba5b2a7fe7880add6b5845` before and after. This verifies CLI version only, NOT a native build, app functionality, EAS login, rollout, OTA or Play status.
- GitHub [PR121](https://github.com/AldinAga/ald1n-project/pull/121) merged source as commit `78edbe5fb3c936c56c884acf408c883b7f08d24c`. Both named checks `node22-source-contract` and `mariadb-integration` PASS. Six optional Android/iOS EAS npm build scripts, Build25 owner-locked shell, and disabled submit helper now use `/opt/alt/alt-nodejs22/root/bin/node` and independent child PATH. Version 1.0.0, runtime 1.0.0-build17, pinned EAS25, release lock and disabled direct submission remain unchanged. This was source-only; it has NOT been deployed to the dirty CloudLinux checkout.
- Actual host package has **NO old nodevenv path**, whereas the prior GitHub package had it, and its SHA differs. No full host package overwrite, git pull/reset/clean, or copied GitHub package is allowed.

## One self-contained operator script

- Filename: `B1013-ALD1N-EAS-ALT-NODE22-HOST-SAFE-DECOUPLE.sh`
- Full SHA256: `c2c118d61d3e2f96d5b83c4a958df2c92b4e4254607291a86fa15aa52ac084bd`
- Normalized embedded SHA256: `3d4e0ac92af1ca2baa7405bef0b5f1e68abd4613c400604f56d5efa84f78c8f8`
- Local standalone bash syntax, hash and targeted Node source-transform fixtures: 11/11 PASS. Real host B1013 **NOT YET RUN**.
- No auxiliary .py or JSON files needed on the host. Transfer ONLY the Bash file into `/home/icaffeco/ald1n-project/incoming/`, then execute:

```bash
bash /home/icaffeco/ald1n-project/incoming/B1013-ALD1N-EAS-ALT-NODE22-HOST-SAFE-DECOUPLE.sh --run
```

If source and CLI preflights pass and the three exact staged SHA comparisons look correct, the owner types:

`APPLY_B1013_EAS_ALT_NODE22_ONLY`

### Controls and exact effects

- Verify account UID, script normalized SHA, authentic Report984 SHA, before-host package SHA, target ownership/no symlink, ALT Node/npm exact versions, CMS/API anonymous HTTP401 baseline.
- Read actual host `package.json`, preserve all other package values/scripts semantically, and update **only six recognized EAS build script values** to the independent ALT Node22/npm10/EAS25 with explicit subprocess PATH. Preserve their exact iOS readiness preflight and platform/profile; fail closed if unfamiliar content/extra flags.
- Change only the exact old node binary line and child PATH in the existing Build25 shell and the disabled submit helper; require exact release lock and direct submit ban.
- Prepare staged files, syntax-check, show SHA before/after; save three rollback originals; require literal interactive owner approval. Replace three files individually, verify new SHA, execute EAS `--version` **only**, and compare anonymous CMS/API HTTP before/after. Any failed runtime gate restores the three original files; never run EAS build/submit/OTA/Play.
- On PASS or post-preflight HOLD, log the exact stage and rollback to the next unused numbered host operations report in `/home/icaffeco/ald1n-project/docs/operations/`; do not assume report sequence number.
- **No old directories are deleted by B1013.** This is a source toolchain gate, not cleanup or CloudLinux Selector de-registration.

## Fixed next sequence, no repeated audits

1. After authentic B1013 host PASS, use Report983's **existing** inventory: 22 named historical directories, 19 exist, 3 already missing, about 4,500 estimated inodes / 159 MiB. These remain REVIEW_ONLY until each unique source is independently archived to private off-host Google Drive with recoverable hash and no running process/cron/FD/mount/vhost or identity drift. No full-home re-audit. Only exact validated obsolete directories may be deleted in one bounded final wave.
2. Unregister the STOPPED `mobile-build.ald1n.com` Node service via CloudLinux Selector only after host EAS independence PASS; preserve its actual `app.js` and `package.json` prior to de-register. The old app root and `nodevenv` are NOT deleted by the source gate. Remove exact old app root only after Selector de-registration and independent rollback/backup.
3. Protect B990 one-build state, all EAS/V15/release artifacts, `.ald1n-toolchains`, encrypted B994/B995 offhost exports, all SQL/private backups (current off-host restore still unverified), unrelated websites and the current CMS/mobile source. Prior B1007 cache cleanup was fully PASS (340,780 net inodes reclaimed). B1009 CSS deployed on host; authenticated mobile horizontal-scroll visual UAT still pending.

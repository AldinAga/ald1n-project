# B1010 — Report982 CSS activation PASS; one combined Node22 independence + exact legacy-root disposition gate (2026-10-31)

## Verified outcome of B1009

Authentic host `982-B1009-CMS-MOBILE-QUICK-ACTIONS-DEPLOY-20261031-081313.md` has SHA256 `e7b7d28ad469cab58485ca5632a9fc7aeef62886ec76733059a6f8b338addb3a` (885 bytes). It says `PASS_B1009_GUARDED_CSS_ONLY_DEPLOY`, with dashboard CSS SHA `fe4c3b378419215d6ca3827c96f6002927736d434f0405b72ba2022f5c7140d2` -> `15c8b880f9b4cfaa696cfbbd2c4ddbd2a2e8eced6903fb7b7e16a10d8b74e67b`, a rollback file preserved and CMS/API 401 -> 401. **CSS deployed; actual logged-in mobile screen UAT remains pending.** Do not rerun B1008 or B1009. No Android build, EAS write, OTA, Play, DB migration, Node Selector change, or extra folder deletion is implied.

## Why one more targeted host gate is necessary before old Node application removal

The source has the old app-linked Node22 path in multiple EAS scripts (`apps/mobile/current/package.json`, `scripts/build25-release.sh`, `scripts/submit-android-production.mjs`); merely stopping the web process does not prove that these build CLI usages survive CloudLinux Selector DESTROY. Current independent ALT-NodeJS22 candidates on CloudLinux can be `/opt/alt/alt-nodejs22/root/bin/node` or `/opt/alt/alt-nodejs22/root/usr/bin/node`, but the exact binary and npm CLI must be **proven on this user's host**, not guessed from vendor docs. Pinned `eas-cli@25.0.0` must report its own version without starting a native build or modifying production. Current host's Node app `mobile-build.ald1n.com` remains `STOPPED`, not deleted.

## One executable, no auxiliary files in incoming

`B1010-ALD1N-INDEPENDENT-NODE22-AND-ROOT-DISPOSITION.sh` is a **standalone, strict read-only gate for pre-existing files**, and needs no Python script or ZIP. Full SHA256: `57aeeb99f425cce87a1f6aa4fb52875a272bedc75c63453fb26e49e1c9ebab3e`. Normalized self-SHA256: `701efec2075240fef135dd5bef849b39b96c1b857a37000bf95132a81b7516e3`. Isolated QA **13/13 PASS**, including real synthetic Node/npm/EAS success without deleting unique source, absent Node/HOLD, failing EAS/HOLD, symlink/HOLD, immutable report numbering, and selfhash.

Operator copies this exact `.sh` into `/home/icaffeco/ald1n-project/incoming/`, then runs only:

`bash /home/icaffeco/ald1n-project/incoming/B1010-ALD1N-INDEPENDENT-NODE22-AND-ROOT-DISPOSITION.sh --run`

The script checks its own integrity, records legacy app.js/package hashes without exposing source/secrets, chooses an **independent** ALT Node22 and npm10 rather than the old `nodevenv`, and invokes only `eas --version` through pinned npm-exec in a self-created temporary cache which is then removed. It reads all **22** previously listed conditional historical QA/staging roots, recording exact dev:inode/UID, count and size if safely measurable; it never deletes or moves one of those paths. Other **12** root paths including B990, EAS/V15 release, `.ald1n-toolchains`, offhost B994/B995 archives, rollback backups, and unknown `.ald1n-sdd` remain excluded. If any dependency is unproven, `HOLD` is an honest gate; the script classifies common error causes instead of printing secrets.

Only a new numbered Markdown host operations report and temporary npm cache created by B1010 may be written; all other existing files are read only. No CloudLinux Selector `DESTROY` or active production mutation is authorized by B1010. Node.js 22/npm10 source may be package-installed system level; EAS cli `npm exec` caches are isolated to avoid long-term inode growth. No installation of global npm and no EAS native build/OTA/Play. No `findmnt` or `python3` required.

## The next actionable step after host evidence

After an authentic B1010 PASS proving the exact alternate Node22/npm10/EAS25 runtime and confirming the 22 conditional root inventories, create one reviewed source PR changing **all actual old `nodevenv` references including the production release shell/controller and disabled submit helper**, with existing release-gate tests and no build dispatch. When the PR is green, a final exact-path cleanup operation can remove *only* redundant generated material/old roots whose unique source has been separately preserved and whose active process/cron/FD/mount/cPanel/symlink gates pass. Preserve/restore full backup and signed release evidence. De-register the stopped old web service **through CloudLinux Selector** before deleting its actual application root; never delete its directory first. No entire folder is pre-approved by B1010 alone. Avoid a new generalized audit loop; reuse this bounded evidence for the one final destructive batch.

Functional acceptance unchanged: overall 7/17, CMS 7/15, first UI 6/6. B1007 reclaimed 340,780 net inode entries and should not be re-run. Current SQL/private offsite restore is not independently attested.

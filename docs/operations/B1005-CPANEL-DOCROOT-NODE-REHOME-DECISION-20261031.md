# B1005 — cPanel domain / CloudLinux Node mapping and two-stage Node relocation decision (2026-10-31)

**Source authority at intake:** GitHub main `ac14a41de7cc5fc29af8af1363b15505bc45f7c9`, `AGENTS.md`, latest Report979, current ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK, and three owner-supplied cPanel screenshots (two Domains > Document Root listings and one CloudLinux Setup Node.js App list). This is a **documentation/decision-only update**; no host action or source/build/release change was made.

## New live account facts from screenshots

- Domain document roots visible in the user's cPanel include `/ald1n-project/apps/cms/current/public` (current Laravel CMS), `/mobile-build.ald1n.com` (legacy Node app vhost), `/public_html`, `/erp/public`, and unrelated domains' roots (some duplicated for associated entries): `/adalyatobacco.rs`, `/arsa-arch.de`, `/arsa-arch.rs`, `/essenza-online.com`, `/hastal.rs`, `/importane.rs`, `/mandza.co.rs`, `/smart-elektrotechnik.net`. The screenshots show document-root paths, not every complete domain-to-root mapping in a machine-verifiable export.
- **CloudLinux Setup Node.js App lists one application as `started (v22.23.4)`, mode `development`, App URI `mobile-build.ald1n.com/`, Application Root `/home/icaffeco/mobile-build.ald1n.com`.** A DNS resolution failure for that hostname in Report973 does not imply the app is stopped or that its old root may be deleted.
- None of the **nine exact generated-only roots** enumerated in [Report975](975-B1001-CONSOLIDATED-CLEANUP-READONLY-AUDIT-20261030-192243.md) is directly a displayed cPanel Document Root or the displayed Node Application Root. This removes the *visual* unknown about these root names only. It does **not** replace live process/FD/cron/symlink/race checks or prove complete account config.
- Authentic [Report979](979-B1004-EXACT-NINE-GENERATED-CACHE-CLEANUP-20261030-224227.md) remains **9/9 HOLD, 0 deleted**. No cache cleanup has been claimed or attempted by this documentation step.

## Source architecture and non-obvious blocker

The GitHub `apps/mobile/current` app is **Expo Router / React Native** (package.json `main: expo-router/entry`), backed by `https://cms.ald1n.com/api/v1` Laravel. It is NOT a standalone cPanel Node.js server. The repository does contain `apps/mobile/current/app.js`, a tiny default HTTP `It works!` server, but this does NOT prove that the old `/home/icaffeco/mobile-build.ald1n.com` runtime executes the same code.

The mobile package.json explicitly hardcodes `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node` in multiple EAS Android/iOS commands; `AGENTS.md` canonically records this Node22 executable and npm CLI. **Moving/reconfiguring the Node app root can recreate or change the `nodevenv` location, thereby breaking critical EAS build gates even if the Laravel CMS remains healthy.** The old Node22 toolchain path must remain available until all source references and exact build smoke tests have been validated. EAS/OTA/Google Play production gates are unchanged.

## Decision and maximum two operational stages (NOT YET APPROVED FOR EXECUTION)

**First choose migration versus retirement based on actual old app startup file/config.** The owner can view `Setup Node.js App > pencil (Edit)` to identify the configured Application Startup File and names (not secret values) of environment variables, or inspect the old app's own package.json / entry file in read-only mode. If the old service is merely the default health stub and has no remaining consumer, it may be more appropriate to retire the service after decoupling build toolchain, rather than transplanting an unnecessary web server.

If the service is actually required and the owner still wants it inside the monorepo, the safe plan is:

**Stage 1, non-disruptive staging/verification:** create a dedicated **non-Expo** directory e.g. `/home/icaffeco/ald1n-project/services/mobile-build` (subject to repository tracking/ignore and owner permissions), stage only the app's verified source, startup configuration and permitted secrets, **not** stale node_modules or symlinked `nodevenv`. Keep the old cPanel app running and old root untouched. Independently confirm startup behavior, package dependencies, Node22 CLI, EAS hardcoded path impacts, cPanel routing and rollback snapshot before applying any switch. Account inode constraints may make a second node_modules install undesirable until the old nine generated roots have been reclaimed.
  
**Stage 2, controlled switch and rollback gate:** use CloudLinux `Setup Node.js App > Edit` Application Root when supported by the installed selector or its documented `cloudlinux-selector set --interpreter nodejs --new-app-root` interface **only after** stage 1 PASS. Keep App URI/Node version/startup/config stable unless purposefully changed. Recreate/validate the appropriate Node virtual environment, update pinned EAS CLI toolchain paths in tracked source with CI testing and full release gates, start/restart only once, check actual service and build CLI, and roll back the app root if startup/build health fails. **Do not blindly move the directory**, assume the command copies files, change `apps/mobile/current` to a webserver root, stop existing app, or delete the original folder/Node22 runtime before an independent rollback check.

Official CloudLinux support: [Node.js Selector UI](https://docs.cloudlinux.com/cloudlinuxos/lve_manager/) and [CLI set --new-app-root](https://docs.cloudlinux.com/cloudlinuxos/command-line_tools/).

## Next

User asked *whether* migration is possible in one or two steps. **Yes, technically possible as two gated operational stages, but NOT a one-click file rename**, and do not schedule it before obtaining the old app startup/config and validating the hardcoded EAS toolchain. No automated relocation or new deletion controller authorized by this report. Existing protected roots: all current Laravel CMS, Expo/mobile, B990 release, backups/SQL/private, all unrelated hosted websites/cPanel/mail and global npm. Current SQL/private off-host restore remains independently UNVERIFIED. Project acceptance remains program 7/17, CMS 7/15, original UI 6/6.

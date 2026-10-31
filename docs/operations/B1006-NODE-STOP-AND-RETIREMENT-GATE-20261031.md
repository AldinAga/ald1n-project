# B1006 — CloudLinux Node.js STOP: current APK/CMS still work; destructive retirement gated (2026-10-31)

## New owner evidence and strict interpretation

On 2026-10-31 owner supplied two sequential cPanel CloudLinux `Setup Node.js App > Edit` screenshots for URI `mobile-build.ald1n.com/`:

- **Before:** Node.js `22.23.4`, `development`, Application Root `mobile-build.ald1n.com`, Startup File `app.js`, no environment variables listed, and `STOP APP` button.
- **After:** same app configuration but with **`START APP`** button, indicating the user has stopped this Node.js web process. The owner independently reports Laravel CMS and an already-installed Android APK still working with the web process stopped.
- This **supports** that the *tested* CMS and APK functionality does not require this Node web process. It does **not** prove that other webhook/cron/build/one-off workflows are independent or that the old directory contains no unique server code. The old `app.js` actual bytes and `package.json` have **not** been read directly from the host.
- The current GitHub `apps/mobile/current/package.json` `main=expo-router/entry` confirms that the Expo app is a native client, not the cPanel Node/Passenger web app. However Android/iOS EAS scripts still explicitly call **`/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node`**. The script path is a *real dependency on the old CloudLinux managed Node22 virtual environment*, not proof the HTTP web process must remain running.
- Official CloudLinux [Selector CLI documentation](https://docs.cloudlinux.com/cloudlinuxos/command-line_tools/) documents `nodevenv/<app_root>/<version>`, app stop and destroy as different operations. [cPanel support](https://support.cpanel.net/hc/en-us/articles/4402553527831-No-such-file-or-directory-when-attempting-to-remove-a-NodeJS-application-via-CloudLinux) cautions that deleting the application directory before removing its Selector entry can break subsequent app management. Therefore **STOP succeeded for tested paths; DESTROY / directory deletion remains HOLD**.

## Two gated operational stages if the owner wants permanent retirement

**1. Decouple and preserve:** Keep old Node app stopped, old directory and nodevenv untouched. Verify old startup `app.js`, `package.json` and any unique operator source in a read-only way and independently archive anything non-generated to a private recoverable off-host artifact. Locate a working independent Node22/npm10 executable, change the pinned EAS build-tool invocation path through reviewed source changes and isolated test gates (Node `--version`, EAS CLI `--version`, `eas whoami`, project info when appropriate). Do not run a production build, submit, OTA, npm global update, or edit the dirty production checkout without release gates.

**2. De-register and clean only after step 1 PASS:** Remove the **stopped Node web application** through CloudLinux's Selector `DESTROY`/Remove function (not `rm -rf` first); verify EAS build-tool tests and CMS/API remain functional, and separately review domain document-root binding. Only then consider deleting the exact old `/home/icaffeco/mobile-build.ald1n.com` tree, after manifest/source/backup check, without touching shared/global Node22 or `ald1n-project`. Rollback should be possible using preserved config/source. If EAS checks fail after app deletion, restore the Node environment before any additional cleanup.

## Independent nine-cache cleanup

Report979 still records 9/9 `HOLD_CPANEL_BINDINGS_UNVERIFIED` and `DELETED=0`; the previous reported potential **340,784 inode entries is NOT achieved**. User's screenshot showed none of the nine candidate roots as a web document root or this configured Node App Root; however stale UAPI failures and live process/cron/mount/symlink gates must be addressed in any final bounded deletion; **do not reuse B1002–B1004** or mistake STOP of old app for clearance of the nine separate cache roots. Current Laravel/source, Android/B990, old nodevenv, all unrelated sites and SQL/private backups remain excluded.

## Operation status

**Documentation only.** No cPanel DESTROY, host file deletion, Node migration, EAS build, source release, production CMS update or Google Play action was performed in this turn. The owner already stopped the Node web process manually. All acceptance counters unchanged.

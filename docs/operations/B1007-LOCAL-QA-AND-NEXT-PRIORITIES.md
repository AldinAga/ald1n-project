# B1007 - Local isolated verification (2026-10-31)

- Canonical previous report: Report975, 9 exact owner-reviewed generated-only roots, 340,784 potential inode entries.
- Authentic latest deletion HOLD: Report979 SHA256 `36c5565ee78c8ffa0bbd397a0f6d768d339f8abe2d7cf8d74f4b8fe2dc843952`, 9/9 HOLD, deleted=0.
- Evidence adopted: owner cPanel Domains > Document Root and CloudLinux Node.js app screenshots dated 2026-10-31; no candidate path is one of the displayed roots. This is *static owner-supplied* evidence, not a live cPanel API response; the owner reaffirms it before deletion.
- Source: `B1007-ALD1N-VERIFIED-9-CACHES-CLEANUP.sh`
- SHA256: `5db971ea324b7d9215091fe325ae188aaa6ae6d8f75025d0268eabf52d10842c`
- Embedded normalized digest: `b135bfef9fe64a7a0c32372e2b7d43c0fd65bae9d7e2da8d91fcc1d968384294`
- Fixed allowlist: 5 isolated September npm-cache + 4 old Expo node_modules, exact identity `dev:inode:uid` and inclusive counts from Report975. Candidate count **9**, potential inodes **340784**.
- Local fixture suite: **45/45 PASS** with Bash syntax, valid selfhash, typed confirmation, real deletion of 9 synthetic folders and preservation of a separate sentinel website; negative cases for missing/altered predecessor, noncanonical executable, no TTY, cancel, HTTP503, symlink, inode drift, cPanel docroot collision, cron-use, archive missing, source tamper and recent modifications.
- Full negative tests simulated on isolated local temporary directories. **Actual CloudLinux execution PENDING.** No SQL/PHP app, EAS build or production services modified.
- Live-time gates remain: B995 encrypted operations archive SHA and size, report979 SHA, user/UID, current CMS+API HTTP 401, exact inode IDs + counts, mount registry, cron/process/cwd/open fd scan, owner, 45min activity, visual docroot collision, explicit owner confirmation, quarantine same-parent and GNU one-file-system removal, per-target HTTP checks.
- Unverified share-host cross-user process access and entire domain config may not be visible through ordinary account; any newly configured docroots require owner to decline typed confirmation. **No manual broad deletion**.
- No backup, B990, legacy Node22 or `mobile-build.ald1n.com`, shared/global npm, other domains or parent source trees included in allowlist.

## Next two priorities (do not modify production before B1007 report)

1. **CMS responsive dashboard horizontal scrolling:** confirmed source `apps/cms/current/public/assets/css/ald1n-dashboard-d1.css`, media `@media(max-width:720px)` `.dashboard-quick-actions` explicitly sets `flex-wrap:nowrap` and `overflow-x:auto`. Next UI batch should replace intentional horizontal strip with compact responsive wrap/grid and assess keyboard/screen-reader behavior, no page-wide `overflow:hidden` band-aid. Verify at 320, 375, 390, 430, 768px in dark/light. Layout `apps/cms/current/resources/views/dashboard/index.blade.php` contains action links. Gate on current CMS deployment process/rollback.
2. **EAS independent Node22:** mobile `package.json` hardcodes `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node`; inspect/replace with verified independent Node22/npm and test EAS CLI in isolation without production builds. **Only then** deregister stopped Node app through CloudLinux panel and archive/remove exact old root with rollback evidence.

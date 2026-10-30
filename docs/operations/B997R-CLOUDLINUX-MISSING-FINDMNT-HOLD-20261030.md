# B997R — CloudLinux findmnt preflight HOLD (2026-10-30)

Owner terminal: `HOLD_B997_MISSING_TOOL_findmnt`.

Verified original B997 SHA256 `fe68b16fd46ea0177a6e634307c4d62bf57157197564a128b08b1a5b83757263`; the `findmnt` dependency check occurs before any deletion, scratch, numbered report, or mount snapshot. Consequently the observed attempt did not delete files and generated no B997 report.

Local B997R compatibility wrapper: `B997R-FINDMNT-MOUNTINFO-COMPAT.sh`, SHA256 `4eea8c191a8ad67f0da9bae902fd8cd8cae414f655d6d866bed556ee851c0d87`. It checks the original's full hash and selftest, and supplies only `findmnt -rn -o TARGET` from validated `/proc/self/mountinfo` when the external binary is missing. Original B997 nine candidate boundaries and all owner confirmation/mount/cron/process/HTTP checks remain active. Local QA 24/24 PASS, CloudLinux result PENDING. This file documents tested source; it does not constitute an operator host report.

Do not delete current backups: B995 offhost scripts/reports 221,001,337 bytes SHA verified, but current production SQL/private recovery is not independently proven restorable off-host. Protect active CMS/vendor/storage, B990 and mobile-build, unrelated websites, mail, cPanel and global npm.

Operator wrapper must be transferred byte-exact to canonical `/home/icaffeco/ald1n-project/incoming/B997R-FINDMNT-MOUNTINFO-COMPAT.sh`, along with immutable original B997, then executed in SSH as `bash /home/icaffeco/ald1n-project/incoming/B997R-FINDMNT-MOUNTINFO-COMPAT.sh --run`. Do not assume deletion success; archive authentic next numbered host report first.

Current acceptance: program 7/17 (41.2%); CMS 7/15 (46.7%); initial UI 6/6 (100%). Maintenance is outside functional milestones.

# B997 — verified Ald1n-only generated inode cleanup (not backups, production or other sites)
- UTC: 2026-10-30T16:09:18Z
- Source: /home/icaffeco/ald1n-project/incoming/B997-ALD1N-VERIFIED-GENERATED-INODE-CLEANUP.sh; self-hash PASS
- B995 remote encrypted archive: Drive file ID 1zhm08VTs3tpbvVXUxQaCGCX50WuS0q9D; independently downloaded and SHA256 matched in B997 handoff
- Host B995 encrypted archive SHA256: cba29ee1c6b708a3c01010aee25f738524b9fceb8a11876369b587063d273f1a; SIZE: 221001337; matched
- Archive covers operator scripts/reports; current SQL and media NOT covered and therefore protected.
- Scope: exact old September 2026 npm-cache trees and old detached Expo test checkout node_modules only.
- Absolutely excluded: other websites, public_html, mail, cPanel/CageFS, global .npm, /home/icaffeco/backups, CMS/vendor/storage, mobile-build.ald1n.com, B990 release checkout, secrets, source .git.
- All deletion operations are gated per exact directory, using same-parent quarantine, GNU rm --one-file-system and pre/post public CMS/mobile-build HTTP comparison.
- Gate: HOLD_B997_MOBILE_BUILD_VHOST_HEALTH_BASELINE; DEL=0; SKIP=0

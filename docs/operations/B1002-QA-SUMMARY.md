# B1002 exact-nine generated-only cleanup: local verification

Date: 2026-10-30.
Source: `B1002-ALD1N-EXACT-NINE-GENERATED-CACHE-CLEANUP.sh`.

- Real host state: authentic B1001 Report975 (2026-10-30 19:22:43Z), 9/9 review-eligible exact roots, 340,784 inclusive inode entries; actual deleted=0. Report SHA256 `8cde5830136190477031a0c968d3a1201ff079d182bd488732bce7c5c430b038`.
- CloudLinux B1002 execution: NOT RUN. Exact physical inode gain unknown.
- Audit scope: same nine pinned old npm-cache (5) and historical Expo isolated node_modules (4), never other directories.
- Static/isolated local fixture tests: **26/26 PASS**. Includes positive deletion of nine synthetic test-only cache roots following typed confirmation, parent directory and non-cache data retained; no-TTY hold; source selfhash; wrong-path hold; missing cPanel proof hold; cPanel docroot collision hold; HTTP503 hold; symlink to unrelated test site preserved; active cron path hold.
- Genuine CloudLinux requires username `icaffeco`, UID `1109`, exact pinned Report975 digest, original B995 ciphertext size+SHA, current CMS and Android API GET HTTP401, live cPanel UAPI DomainInfo `format=list` mapping parseable via python3, and fresh per-target mounts/processes/cron/FDs/owners/recency/identity/inodes. The script may HOLD if mappings/proc coverage are incomplete. No assertion of global cPanel or cross-user process visibility is made.
- User must type `DELETE_B1002_VERIFIED_GENERATED_CACHES` in an interactive SSH terminal; no auto-confirm. Each deletion uses same-parent rename and GNU `rm --one-file-system`, with post-target CMS/API comparison, numbered report and final account-home inode count.
- Any B1002 HOLD is a safety stop. Do not manually substitute `rm -rf`, spoof old DNS, or execute earlier `SUPERSEDED-DO-NOT-RUN` B1001 prototype.
- Current SQL/private off-host restore unverified; ALL backups protected. Old domain/nodevenv, current CMS/mobile/B990, other domains, global npm, cPanel/mail untouched.

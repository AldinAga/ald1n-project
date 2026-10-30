# B1003 — Report976 closure, repaired cPanel UAPI parser, single guarded cleanup

Owner preference: **no repeated read-only batches**. Do not repeat B997–B1002. All checks for the nine exact generated cache directories run inside one operator command; if they pass, interactive owner approval unlocks only these paths. No general removal of older folders, backup trees, live hosting, Node22 or B990.

## Authenticated latest real host evidence

- Report976: `976-B1002-EXACT-NINE-GENERATED-CACHE-CLEANUP-20261030-193110.md`, SHA256 `cc57ee5705c79e1f071077ebf01c10f2f800c3e603ebdb3a16143c4208610a20`. `DELETED=0 HELD=9 MISSING=0`, all `HOLD_CPANEL_BINDINGS_UNVERIFIED`.
- Authentic Report975: nine exact candidate identity+inode pins, 340,784 potential inode entries; **no real deletion**.
- B995 encrypted scripts/reports offsite verified, but current SQL/private offsite restore **NOT independently attested**; do not touch any backup roots.
- B990/current mobile, CMS/vendor/storage, old mobile-build webroot and Node22, global `.npm`, all other domains, cPanel/mail, release evidence and all nonallowlisted paths **PROTECTED**.

## Determinate cause and fixed procedure

B1002 parser read `payload.status` and `payload.data` only, while cPanel UAPI via CLI returns `payload.result.status` and `.data`; as a result its docroot mapping was never established even when `uapi` executed. Independent official cPanel instructions also say CloudLinux UAPI binary may require `/usr/local/cpanel/bin/uapi`. Sources: https://api.docs.cpanel.net/cpanel/introduction and https://api.docs.cpanel.net/specifications/cpanel.openapi/domain-information/domaininfo-domains_data.

B1003 checks full UAPI wrapper, non-empty complete docroot list, then falls back to read-only `DomainLookup getdocroots` if primary schema can't be parsed. It never proceeds on unknown/missing roots. Same original nine exact absolute paths and Report975 identity/inode pins, authenticated predecessor Report976, B995 local SHA/size, account UID, recent changes, mounts, symlinks, owners, visible processes/crontab/FDs, CMS/API 401 before and after. Same-parent quarantine, one-file-system deletion after explicit typed confirmation. cPanel domain paths are used only for collision comparisons; not logged.

## One actual owner step

[Source file on GitHub](../operations/B1003-ALD1N-FIXED-CPANEL-EXACT-CACHE-CLEANUP.sh), SHA256 `dd5d22fd251606c57d0ce43eb4f6c68592c43cc5bb59e9216190a6d9c6c6e2b6`. [One ZIP package on Google Drive](https://drive.google.com/file/d/1N9xdpJDWMo0ToJFGC8pcIf7QezQmbUNe/view).

Place exact source in `/home/icaffeco/ald1n-project/incoming/` and execute:

```bash
bash /home/icaffeco/ald1n-project/incoming/B1003-ALD1N-FIXED-CPANEL-EXACT-CACHE-CLEANUP.sh --run
```

If script reports READY paths, operator must review and type `DELETE_B1003_VERIFIED_GENERATED_CACHES`; otherwise it aborts with no deletion. New report should be #977 if no other batch ran, but use actual numbered report. Record true post-delete inode delta; do not infer actual freed numbers from preflight. If B1003 still reports cPanel HOLD, **do not start another generic audit**: request one control-panel docroot evidence or grant correct UAPI metadata access, and limit any continuation to same nine paths.

QA: `29/29` local synthetic PASS, not real CloudLinux PASS. Remaining unverified folders stay protected. No production build/EAS/OTA/Play change, functional program 7/17 and CMS 7/15 unchanged.

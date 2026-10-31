# B1015 — B1014-derived CloudLinux EAS25 host source reconciliation (PREPARED, HOST NOT EXECUTED)
Date: 2026-10-31.

## Authenticated owner evidence
B1014 owner report `B1014-HOST-READONLY-AUDIT-20261031-100232.txt`, SHA256 `13bd1cdbef70cc460649a3882dd1aded7ecaa621afe13f0806a6aafeebd7b86d`.
Host Git main HEAD `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6` (older than remote), and numerous dirty CMS files — PROTECTED. Exact host target SHA256: mobile package `ca996886464fc3fd33a90f1d0330e3d08101b9440eba5b2a7fe7880add6b5845`, eas.json `b3cd822dd3b5578f40b61dff2539915f94691583414a05b45b83aff198443ad5`, submit helper `e2ba079fb30ef70bad323b33fbb0b4fefc73d5b5b207fd447ce758982703faee`. `scripts/build25-release.sh` and `scripts/build25-release-controller.mjs` both MISSING (regular files, not symlinks); previous B1013 3-file guard could not apply. Node22.23.4 / npm10.9.9 PASS. Host EAS pin 24.8.0, mobile EAS scripts `npx eas-cli@latest`; direct-submit helper from legacy commit not hard-disabled.

## B1015 scope (NOT YET HOST VERIFIED)
One operator script `B1015-ALD1N-EAS25-HOST-SAFE-RECONCILE.sh`, delivered as a conversation attachment only, intended for exact `/home/icaffeco/ald1n-project/incoming/`, full SHA256 `188fb0bb3a176cca50aea5d11e47aca73d46130a54d1bdc5bc9b1d6e7f5e23dd`; normalized self-integrity `9ac5b33c26e3b77ea8c3f8dc015f905e71cc3e6011ef97d4b02abc2534735814`. The script requires verified B1014 SHA baselines, source HEAD and owner identity, CloudLinux ALT Node and npm versions, source guard identity, and an exact source-only authorization phrase. It stages only three existing mobile files: package EAS scripts pinned to independent ALT Node22/EAS CLI25 (production build routed to absent gated controller and thus safely unavailable), eas.json CLI version pinned 25 (existing profiles preserved), submit helper direct execute hard-disabled and child PATH corrected. Private backups and rollback on errors; numbered operations report. Does NOT install npm, publish EAS builds/OTA/Play, modify CMS, touch Git HEAD, or delete roots. Source-only synthetic fixture PASS, negative approval PASS, injected post-mutation rollback PASS; no CloudLinux host PASS attested.

**Important remaining gate**: Build25 wrapper/controller are still absent; this batch deliberately does not install or bypass the missing release controller, so even a source-only PASS does **not** authorize any release. Later independent guarded controller+dependency deployment and UAT required.

## Next owner action
Copy script unmodified into `/home/icaffeco/ald1n-project/incoming/`, then run:
`bash /home/icaffeco/ald1n-project/incoming/B1015-ALD1N-EAS25-HOST-SAFE-RECONCILE.sh --run`
Review preflight and type `APPLY_B1015_EAS25_SOURCE_ONLY` only if scope is correct; send numbered report. No additional commands needed. Prior B1013/R2/R3 HOLDs are obsolete and must not be rerun.

Program acceptance unchanged 7/17 = 41.2%; CMS 7/15 = 46.7%. 

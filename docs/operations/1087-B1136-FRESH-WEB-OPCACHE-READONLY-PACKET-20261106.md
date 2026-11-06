# Report1087 — B1136 fresh web OPcache status packet, 2026-11-06

## Current decision and scope

Owner explicitly revoked the previous usage ceiling for this requested recheck: “čim sam ti rekao proveri, znači da odobravam, i da ne treba da gledaš na limit”. No account reset or credit purchase. Scope is fresh authenticated web OPcache status, not installation, restart, Redis clearing, migration or public Storefront activation.

Actual1085 recorded cacheFull=true/freeMemoryBytes=32; actual1086 only refused terminal installer reentry. Those are historical observations, not the present cache state. B1134/B1135 remain rolled back before source/vendor switch. Do not rerun either installer or delete their pointers.

## Frozen implementation and actual checks

- Helper branch `codex/b1131-stage-helper-ci-20261104`, frozen head `1230430d8c6042f973e71ab779913719aa136d35`, tree `a1c4320f88e431019cbfd6367da8578c752efd29`.
- [GitHub run42222389201](https://github.com/AldinAga/ald1n-project/actions/runs/42222389201), job127907349549: all steps SUCCESS. Application source untouched; only helper tools/workflow on the helper branch.
- Provider artifact13503623593, ZIP SHA256 `2444d5b6dc5db3139cfc70ac562b2ed71230d70589111922da69bca15f1daa89`, verified before inspecting receipt. Three actual-front HTTP samples per native PHP profile: enabled READABLE/free65134920; disabled DISABLED/free=null; full FULL/cacheFull=true/free7512. Numbers belong to disposable CI, NOT hosting.
- Wrong authentication/action, tampered signed envelope, stale receipt, foreign maintenance collision/replacement refusal, ordinary HTTP continuity, owned cleanup and repeated orphan recovery passed. Exact tracked public/index.php with a synthetic normal application; helper samples do not bootstrap that app.
- Independent frozen review: 0 findings; all7source/test/workflow blobs matched fresh remote tree. Real shared-host LSAPI, actual cached legacy maintenance barrier and whole production-domain run were not simulated; HTTP/nonce mismatch fails closed.
- Root audited archive membership, every size/hash, frozen PHP Git blobs, mechanically substituted wrapper provenance, normalized self checksum, package/loader checksums, bash syntax and prohibited shell patterns. No local PHP/app runtime executed.

## Manual packet

`B1136-WEB-OPCACHE-READ-1230430.zip`: 12817bytes, exactly8files, 28428uncompressedbytes, all under incoming/, SHA256 `3b6c7a67353c18021e8a140cac2ccb650242cce04d8f08b5d0a04912633b3cdc`.

Contains b1136-opcache-read.sh/.php, b1136-control.php, immutable hash-named probe PHP, package.json, package.sha256, provenance.json and README. Does not redeliver or overwrite B1134/B1135 files. No production secrets, DB data or vendor payload.

Owner unzips at `/home/icaffeco/ald1n-project`, then one command:

```bash
bash /home/icaffeco/ald1n-project/incoming/b1136-opcache-read.sh
```

Integrity checks run automatically. Script fetches main and resolves next report number from remote+local reports with reservation; live output and actual RC go to canonical docs/operations. Report1087 must be checkpointed before exposing executable download; no additional coordinator allocation while owner runs.

The helper temporarily publishes only its own inode/target/hash-bound maintenance.php symlink to an immutable incoming bridge. No down file; ordinary requests return to existing CMS. Fixed random path, token, signed expiring state, fresh nonce, actual index/document-root/source hashes guard each snapshot. Three scalar snapshots cover SAPI/PID, enabled/full/free/used/cache counts/restart flags. Unknowns are null, not fabricated zeros. No reset/invalidate/warm operation. Normal login checks do execute existing application traffic.

Cleanup revokes its envelope and removes only exact owned links; foreign replacements remain untouched. Reruns recover its own interrupted probe and take fresh samples. No guaranteed absence of transient impact: stale cached earlier maintenance code may cause HOLD; cleanup and login results must be inspected. No source/vendor/scheduler/private-data or prior installer-state mutation is performed by this packet.

## Status and next evidence

PREPARATION=PASS; CI=PASS; REVIEW=PASS; ARCHIVE_AUDIT=PASS. HOST_EXECUTION=PENDING; CURRENT_HOST_OPCACHE=UNKNOWN; INSTALLATION=NOT_PERFORMED; PUBLIC_ACTIVATION=NOT_PERFORMED. Even READABLE samples are status only, not installation approval or proof that a restart occurred. Wait for all WEB_OPCACHE_SNAPSHOT_ lines and EXIT=/REPORT= (or the whole numbered report); decide the next bounded continuation from those actual facts.

Documentation base main `fe6c1f87d67556e23cb9271ac286e0fda91f2fd2`, CMS tree unchanged `da8ac0c953e3c6741b921bb9fa660f80d247c55e`. Original dirty local HEAD `acb8b9c42ec9254b1a27567abca29a26c1f733f8` and index SHA256 `7d18075dc901fc60ee96294b4145b327a734935281836abc4d246ccd8d0a01f5` preserved. No reset/clean/stash, database probe, deployment, service restart or cache clearing performed.

# Report1082 — B1134 verified controlled OFF installer; owner execution pending

Date: 2026-11-05. This is package-delivery evidence, not a production execution receipt.

## Source and preserved state

- Fresh origin/main: `18fe5bd8844fbf0282d3ac87408274d7d1c742a3`; CMS tree `da8ac0c953e3c6741b921bb9fa660f80d247c55e`.
- PR135 already merged as `ddb0b0be03a7d7164ad7a45cfef705da98c1bf9c`. The runtime producer source `ef3f927b77ba573881c2b6dba341732c417e6725` has that identical CMS tree. Helper CI branch is separate and is not application main.
- Final helper/test head: `a699ebcada9409369adc8c2491bbcc3b7947e91a`; executable behavior/source reviewed at `dc5d9d297fe3b3eda8e9edfd844287cc81db8846`. The last commit changes only a negative-test HTTP expectation (correct denial503 rather than403).
- Reuse successful host Report1077 stage `/home/icaffeco/.ald1n-stage/b1131-ef3f927.6hh7mU/runtime`; no new staging/vendor upload or repeated B1133 inventory/DB confirmation.
- Dirty local branch `fix/b1075-storefront-recovery-20261103`, HEAD `acb8b9c42ec9254b1a27567abca29a26c1f733f8`, index SHA256 `7d18075dc901fc60ee96294b4145b327a734935281836abc4d246ccd8d0a01f5` preserved. No reset/clean/stash/checkout or local PHP/application tests.

## Actual verification

[Final focused GitHub run41995128883](https://github.com/AldinAga/ald1n-project/actions/runs/41995128883), job127136346658: every step SUCCESS on exact a699 head.

Provider artifact13399753033 SHA256 `1a3e7e4cc3daa200bcb97a463076e8c1b127e18ed068867dbee377fd3b0e3207` verified BEFORE inspecting its eight evidence members.

- OFF overrides ON environment while preserving other settings; 3 positive/2 negative primitive cases.
- Real private-file writes survive switch and rollback; environment, uploads, sessions, logs/cache and custom .htaccess preserved; fresh release-local compiled views; ownership and rename-gap recovery.
- Actual coordinator class: success/reentry, failure after rename/reentry, interrupted two-rename gap/reentry, and lost pause receipt with transient restore failure. Real Git/files/journal/8531-file manifest; HTTP/process/scheduler transport boundaries are EXPLICIT DOUBLES, separately integration-tested.
- Real Laravel first-party scheduler with persistent file cache: prior pause state, foreign owner rejection, idempotent pause/restore, relocated backend identity, missing parent receipt, and failed-cache-write handling. Explicit false-return adapter test proves unsuccessful ownership writes never pause and unsuccessful terminal writes retain ownership.
- Real public/index.php HTTP dispatch with memory AND disk OPcache and timestamp checking OFF: conservative old schema/no migrations, login page/admin redirect, ON-env forced OFF, private GET while public503, no maintenance cookie to browser, fresh views, PRE/POST force invalidation, rollback/private-write preservation, revoked dispatcher and no public alias.
- Unsafe OPcache file-existence override rejected before code switch.
- Actual receipt validator: 2 valid bound RPC/GET cases, 8 foreign-parent/namespace/cache/front negatives. Process classifier: 4 positive/6 negative cases.
- Real CloudLinux LSAPI topology is NOT attested by cli-server CI: invocation requires actual-index calibration, stable single supported process group, bound replies/shared sentinel and quiescence; unknown profile HOLD. No process killing, cron editing or silent bypass.
- Both independent scoped final reviews: 0 remaining findings, conditional on exact GREEN and final-byte integrity. Root independently verified seven delivered PHP Git blobs against final GitHub contents and reviewed wrapper provenance.

TDD evidence retained: scheduler/lost-pause defects RED at41993312255/job127130456899 before minimal fixes, then GREEN41993473179; cache false-return/H1/H2 RED41994634730/job127134743485 before fixes. Attempt41994889028 correctly denied unsafe mode503; only fixture expected403. Final a699 corrects that expectation; executable bytes unchanged. Earlier failed fixture setup and historical REDs are not represented as successful checks.

## Audited operator package

Filename: `B1134-CONTROLLED-OFF-INSTALL-a699ebc.zip` (local `D:/GPT/`).
ZIP SHA256: `74c00ab2874248fd3dbf6b15fcd1f18653819a16974dc5d15e112857abdde61b`.
35,188 bytes; exactly12 unique files, all under `incoming/`; 101,484 uncompressed bytes.
No vendor payload, production .env, credentials, DB dump, public alias, fixture Python, or unrelated application changes.
Actual wrapper SHA256: `b4761a8b645636d47db8612ba3b999d0256005967f8af6201799ed0d2b8ad987`.
Normalized self-integrity SHA256: `586be573d77d2d469efea4e54ee482ad2dd7d6715145aba76544db0aed18a58c`.
Root independently checked every ZIP member size/hash, exact remote source blobs, only pinned wrapper substitutions, checksum/loader manifests, LF, bash -n and banned host-shell constructs. No local PHP/runtime execution.

Raspakovati u `/home/icaffeco/ald1n-project`, tako da fajlovi budu direktno u `incoming/`. Jedina SSH komanda:

```bash
bash /home/icaffeco/ald1n-project/incoming/b1134-controlled-install.sh
```

Integrity checks are automatic. Runtime reports allocate the next free prefix from fresh main AND local docs/operations, live output with actual worker RC, canonical report path only. This numbered checkpoint is published before root shares the executable download. Do not allocate another coordinator report while owner execution is in progress. Request final EXIT=/REPORT= or full report afterward; EXIT2 is HOLD.

## Behavior and remaining boundaries

The installer prepares complete code/vendor beside active current, preserves original release/private bytes, owns maintenance and scheduler pause, invalidates real web caches while targets exist, performs journaled TWO directory renames, warms new technical caches, proves private HTTP OFF and restores only owned prior scheduling/maintenance. This is not a single atomic transaction. Interrupted/failed states are recovered from owned state, not blindly reapplied.

The retained original release is a PERMANENT private-data dependency: never delete/move/prune it. Its real .env/storage/uploads targets must be included in future backups; current-only archives may capture links. Owner's existing backup174 attestation is preserved; no repeated historical backup investigation was performed.

Technical application boot, package/view cache generation and first-party scheduler cache reads/writes are authorized and accurately disclosed. No direct production DB metadata/business queries, migrations, flag changes, .env edits, email transmission, EAS/build/OTA/Play action or public Storefront activation.

Runtime OFF clamp is separate from main source: original new-runtime config is retained in immutable stage with distinct original/old/clamped hashes. Future activation must restore the NEW approved config, never old production config, only after required remaining host/mail/buyer/security acceptance gates. Existing B1099/B1101/public holds are not waived. Main's previous753/15194 application PASS is retained; no broad repeat.

Owner explicitly raised Weekly maximum to90%, superseding80/70. Actual account read84% at this checkpoint. No reset/credit purchase. Preserve closure reserve near88%. User declined pause; goal remains unfinished, not complete. Production installation and actual-host gate results are PENDING OWNER EXECUTION.

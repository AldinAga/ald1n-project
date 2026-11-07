# B1140 — verified observation-only visibility diagnostic package

Report sequence1097; batchB1140; checkpoint2026-11-08. Fresh authority before numbering: GitHub main `0d909d43f814ac37d45df22fe99ead9adcf8ab54`, highest remote operation report1096, highest local `docs/operations` report1075 in each Windows project. This is a package-delivery checkpoint, not a host-execution report. The hosting wrapper independently reserves the next actual report number under the canonical shared lock after a fresh remote/local census.

## Actual hosting state and unresolved question

Owner Report1096 raw SHA-256 `0b62589448a5b0c6f192ecf31d98c8865d5d1ac5697c6dc0a9a3b12f366452a2` recorded the one approved account refresh as acknowledged, then `WEB_VISIBILITY_TIMEOUT`, dispatch revocation, scheduler release RC0 and `ROLLED_BACK_PRE_SWITCH`; original application code/vendor untouched; exit2. No code/vendor switch, installation or public activation succeeded. The accepted one-shot account refresh is consumed and is not repeated by this diagnostic.

Owner supplied retained visibility JSON SHA-256 `a41ac904d2c5f38a8064515f44313e73b1db62e633ee44b9c177e4cb137df62c`: matching nonce, stage `done`, PID1845127, namespace `pid:[4026531836]`, UID1109. This proves the diagnostic shutdown callback reached its final write. It does not prove successful HTTP completion, CLI observation of both held phases, recognized process comm/title, or the same process entering idle after native teardown. Actual hosting cause remains **UNKNOWN**. Do not replace that uncertainty with a guessed timeout increase or weakened installer gate.

## Exact source, regression evidence and independent review

- Frozen helper source `75f78839ba401ab1ab7aa235620a783fa3a29086`, tree `7f47af0b23ee616a39355f1e297250e8b19eb4a5`, dedicated branch `codex/b1131-stage-helper-ci-20261104`. It is not merged application code in main; this checkpoint changes documentation only.
- Corrected expected-reason RED [run42423083891](https://github.com/AldinAga/ald1n-project/actions/runs/42423083891), source `c31a4ed3b24a437f8377624d869a3943c65d7305`, artifact13594845044, provider ZIP SHA-256 `d56ec014c9d2c36e7383c3ec4fa1474a667f0d058cb385079f1d2bfc63227241`: real trusted local HTTPS/native PHP reaches HTTP200 and retained DONE while the unchanged frozen method reports generic `WEB_VISIBILITY_TIMEOUT` at16.098s. Literal loopback and proxy isolation prevent a real CMS request. This is a counterexample, not attribution of the hosting failure. Earlier run42422877373 failed at fixture parsing and remains preserved; it is not counted as behavioral RED.
- Intermediate `0e709...` and `4387b879e28c269c8c10ed81b7468b075cca8d51` GREEN runs are historical, not final package authority. Two Important review findings at4387 were fixed: bounded child-process output drains both pipes after exit with its combined cap, and diagnostic counterfactuals remain honestly unknown rather than implying that the exact frozen calibration ran. Forty quick-exit tail/overflow checks cover the first correction; README semantics distinguish helper work from normal application HTTP.
- Final [run42424332335](https://github.com/AldinAga/ald1n-project/actions/runs/42424332335), job128559256857, completed **SUCCESS** at exact75f7 source. Artifact13594742646 (`b1140-observation-evidence`,1443bytes), provider ZIP SHA-256 `0f25fe63fab2f0645b6165aa12028a5a154e1645de93eff3ff18db4c0b9890fa` verified before inspecting its two bounded receipts.
- Independent final behavior/security review **PASS,0 Critical/0 Important/0 Minor**, review SHA-256 `d207ee7cd7b12d5b2de18c2dd4afc5957bd9e94f9feb627a85b9dcefa0db8500`. Nine selected final remote source/workflow/fixture blobs and unchanged dependencies were independently verified. Exact candidate comparison against B1139 contains eleven expected diagnostic/workflow/retained-RED paths, no CMS application/vendor delta.

Focused verification covers partial markers; nonce/namespace refusal; wrong title and PID reuse; remembered identity at DONE; bounded process timeout/overflow and quick-exit tails; private-string omission; real sanitized `ps` classification; missing processes; HTTP200/curl0 with deliberately incompatible `other_php` category under enabled and disabled cache; all three marker stages; fifteen-second bounded observation; normal traffic; wrong authentication/action; tampered envelope; stale snapshot; foreign maintenance collision; exact-owned cleanup; repeated/interrupted publication recovery; bash syntax and banned-shell scans. No broad unchanged application suite was replayed for this native diagnostic-only change.

## Package and root audit

Final ZIP `B1140-VISIBILITY-OBSERVE-75f7883.zip`: **17964bytes,9 regular root-relative incoming members,43312 uncompressed bytes**. SHA-256 `3cd59151b7e7ce5f8cfd87d21665443fb203fd30c06022a4304e7026f7ae701e`. [Project Drive package](https://drive.google.com/file/d/1JtjmNw8FPRE1aBVZAN89pybSR9nSYC_h/view).

Root independently audited ZIP membership/path/size, frozen source blobs, wrapper substitutions and normalized self-integrity, seven checksummed payload/metadata members, loader hashes, existing dependency pin and reviewed README SHA-256 `d442ef39f0020079693f9350c3659e1c013562f9ea931713a34d61eeb99ccaf5`. Root audit **PASS**; delivery-file proof SHA-256 `e80ad566fb61841b7e38eb0c54a758c13b5d70c7265cdd8d5669ad109f6624f9`.

Exactly nine new files: entry, control, observer, content-hashed endpoint, wrapper, metadata, checksum manifest, README and provenance, all under `incoming/b1140-*`. Existing `incoming/b1135-web-client.php` is not redelivered; loader automatically requires SHA-256 `546a77f1229af2813da3379159b8cb8f6d8057964e5400c78c49dc676894da34`. Do not overwrite existing same-name B1140 files blindly. Old terminal states/candidate and retained private-file targets remain protected.

The first local ZIP attempt failed because Windows PowerShell required an explicit built-in `System.IO.Compression` assembly load. The local packaging helper was corrected; the failed stage and zero-byte ZIP were preserved under distinct failed-artifact names. Final exclusive ZIP assembly and independent audit succeeded. Shipped source was unchanged; no source CI replay or local PHP execution was needed.

## Operator handoff and limitations

Unzip the archive's `incoming/` subtree into `/home/icaffeco/ald1n-project/` preserving subfolders. Run only:

```bash
bash /home/icaffeco/ald1n-project/incoming/b1140-visibility-observe.sh
```

The script checks integrity automatically, streams live output and preserves the actual command RC in its numbered `docs/operations` report. Return that report from the printed `REPORT=` path. Do not return state.json, web.json, signing-key, .env, raw process listings or a whole private directory.

The bounded diagnostic makes one held authenticated request through the existing actual front controller using a temporary exact-owned pass-through link. Ordinary traffic continues through the existing application; the native helper itself does not bootstrap Laravel. It separates transport/stage/own-process/identity/title/idle facts and one bounded `ps` snapshot using sanitized fields only. `psSampledThroughout=false`; `exactFrozenCalibrationExecuted=false`; `frozenLoopPredicateSatisfied=null` and `calibrationWouldPass=null`. These facts deliberately prevent claiming exact original calibration success from partial observations. `OBSERVATION_COLLECTED_NOT_DEPLOYMENT_APPROVAL` is a completed diagnostic, never installation approval. All full installer/cache/parent/group gates remain unchanged and unexecuted.

No restart, account signal, installation, source/vendor switch, scheduler mutation, production DB query/migration, flag write, EAS/OTA/Play or public opening is performed by this packet. Normal login checks may execute the existing application and its usual database/cache access. No cache sentinel residency prerequisite for this observation waives a release condition. No new source or helper copies are written into active application source. Exact-owned cleanup and access revocation are required; failures report HOLD.

Permanent owner workflow `AGENTS.md`0ZE remains authoritative: bounded single writer, frozen independent review, coordinator package audit, relevant ECC development/debugging/TDD/security/verification, no overlapping changes or redundant completed checks. Existing project/manual-upload/private-file/release contracts continue to apply.

**Not checked here:** real CloudLinux/LSAPI execution, exact hosting root cause, full installer/cache/group calibration and production installation/activation. They remain unproven until the owner returns the new report and the remaining acceptance gates pass. This checkpoint does not mark Storefront stable, installed or public.

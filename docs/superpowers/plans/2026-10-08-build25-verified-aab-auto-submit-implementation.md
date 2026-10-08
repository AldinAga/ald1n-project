# Build25 Verified-AAB AutoSubmit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementirati fail-closed P0 release kontroler koji rezerviše najviše jedan EAS production build, kriptografski i native verifikuje TAČAN AAB i zatim bez ručnog koraka šalje TAČNO taj AAB na Google Play, samo kada su svi prethodni release gate-ovi prošli.

**Architecture:** Mali Node 22 moduli razdvajaju konfiguracioni autoritet, read-only preflight, atomarno stanje/lock, EAS transport, proveru binarnog paketa i uslovljeni submit. CloudLinux proces izvršava komande iz `apps/mobile/current` pod vlasničkim `flock`-om i vodi neizbrisivi journal izvan worktree-a; GitHub Actions služi samo za kasniji završni native dokaz, ne za dnevne P0 probe. Dva javna puta za Play submit ne smeju postojati: stari submit helper mora koristiti istu acceptance barijeru.

**Tech Stack:** Node.js 22.23.3 (built-in `node:test`, `fs`, `crypto`, `child_process`), POSIX Bash, `flock`, Git, EAS CLI 24.8.0 *tek nakon read-only authority reconciliation*, JDK17 `jarsigner`, `bundletool`, `readelf` i SHA-256. Nema novih npm dependency-ja.

**Spec:** `docs/superpowers/specs/2026-10-08-build25-verified-aab-auto-submit-design.md` (odobren od vlasnika 2026-10-08).

## Global Constraints

- Pri SVAKOM izvršavanju preflight ponovo učitati live `origin/main`, važeći `AGENTS.md`, poslednje `docs/operations` i aktuelni EAS remote state; početni istorijski SHA nije budući hardcoded authority.
- Istorijsko stanje 2026-10-08: main `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`; izolovani native PASS `7ff4a5ee1dabfb09131893041de33731d26b11d7`; successful Actions run `37759246073`. Main nema Kotlin/splash korekcije iz audita; taj audit artifact ima debug signing i nikada se ne šalje na Play.
- Paket `com.ald1n.mobile`, ime `Ald1n CMS`, Expo owner `ald1n`, project `d43b3866-6838-4217-a23e-3dc7f2cc76cc`, app version trenutno `1.0.0`, runtime `1.0.0-build17` (nije potvrđena native/OTA kompatibilnost), `production` profile/channel, API `https://cms.ald1n.com/api/v1`. Nijedna identifikaciona vrednost se ne menja usput.
- CloudLinux: Node `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node`, npm CLI `/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js`; sve EAS komande iz `/home/icaffeco/ald1n-project/apps/mobile/current`, kroz pinovani `npm exec`. `AGENTS.md` trenutno navodi 24.7.0, `eas.json` i submit helper 24.8.0: do dokazane reconciliacije nema release dispatch-a.
- GitHub CI minuti i artifact storage su prema korisniku na 95%: **nema novih hosted workflow runova, velikih evidence upload-ova, ponavljanja ranijih native poslova ni brisanja istorijskih dokaza u ovom planu**. Sve RED/GREEN probe koristiti lokalne stubove / privremene neprodukcione fixture-e.
- `BUILD25_AUTHORIZED=NO`; nema EAS build/submit/OTA, nema Play/DB/production signing mutacija, nema `main` source push-a u ovom implementacionom ciklusu. Komande navedene za eventualni Build25 su **reference budućeg autorizovanog izvršavanja**, ne instrukcija da se sada pokrenu.
- Pre production build-a svi release-blocking A03–A14 nalazi moraju biti označeni dokazima pojedinačno; ovaj plan direktno rešava **A03/A04/A05**, ostali ostaju posebni testabilni batch-evi/planovi. P0 controller PASS ne implicira BUILD25_READY.
- Nema `eas-cli@latest`, `--auto-submit`, `--latest` za submit, `git clean -fdx`, `python3`, `/dev/fd`, Bash process substitution, ni globalnog bypass-a Lint/R8. Ne dirati `eas.json` target track `production` ni `releaseStatus=completed`.
- Svaki stvarni production build mora isporučiti: tačnu build komandu, app version, udaljeni `versionCode`, runtimeVersion, finalni source SHA, profile/channel, tačni Build/Submit ID, SHA256 AAB-a i copy/paste Google Play **„Napomene o verziji“**.
- Operativni izveštaji: numerisani sledećim slobodnim `docs/operations/NNN-...` (nakon svežeg inventara); terminal output uživo + report, prethodni report SHA binding, bez tajni ili signed URL-ova. Privremeni fajlovi i lock van repo-a.
- Release source se razvija u izolovanom eksternom worktree-u sa kanonskim HEAD/index/hash preflightom, samo eksplicitno dozvoljeni fajlovi po commitu i `git diff --cached --check`.

## File map (scope ovog implementacionog plana)

- `apps/mobile/current/scripts/release25/authority.mjs` + `authority.test.mjs`: identitet, release policy, konfliktnost CLI pinova.
- `apps/mobile/current/scripts/release25/journal.mjs` + `journal.test.mjs`: trajne faze, atomarni zapis, oporavak.
- `apps/mobile/current/scripts/release25/preflight.mjs` + `preflight.test.mjs`: read-only Git/EAS/source/quality/tool gates.
- `apps/mobile/current/scripts/release25/eas-transport.mjs` + `eas-transport.test.mjs`: jedina EAS komunikaciona granica.
- `apps/mobile/current/scripts/release25/verify-signature.mjs` + `verify-signature.test.mjs`: integritet/potpis i upload cert.
- `apps/mobile/current/scripts/release25/verify-native.mjs` + `verify-native.test.mjs`: bundle manifest, 16 KB, ELF, ABI, runtime/mapping/device receipt.
- `apps/mobile/current/scripts/release25/submit-gate.mjs` + `submit-gate.test.mjs`: sealed AAB hash + exact Build ID submit policy.
- `apps/mobile/current/scripts/build25-release-controller.mjs` + `scripts/release25/controller.test.mjs`: preflight/simulate/execute state machine, samo jedna operativna ulazna tačka.
- `apps/mobile/current/scripts/build25-release.sh`: Hosting `flock` wrapper, live report i bezbedan cleanup.
- Modify `apps/mobile/current/scripts/submit-android-production.mjs`, `scripts/validate-project.mjs`, `package.json` i (posle utvrđene authority) `AGENTS.md`: ukloniti svaki stari bypass bez regresije postojećeg mobilnog validatora.
- Novi release source Kotlin/splash patch, Restore lifecycle, CMS race, EAS builder parity i završni native CI **nisu** sadržani u ovom P0 controller patch set-u.

## Review Focus — dodatni rubni slučajevi koje testovi moraju pokriti

1. Prethodni proces je umro, `flock` oslobodio OS lock, ali journal kaže `BUILD_DISPATCH_INTENT_PERSISTED`: drugi proces **ne sme** ponovo pozvati EAS Build (Task 2 + Task 8).
2. Temporary AAB je zamenjen symlink-om ili hardlink-om nakon prihvatanja: fizički sealed fajl mora ostati isti, bez podataka iz drugih korisničkih putanja (Task 5 + Task 7).
3. EAS command izlazi RC=0 uz skraćen, nevalidan ili netačan JSON `buildId` / `sourceCommit`: **nije PASS**, nema submit-a (Task 4).
4. Build je FINISHED, ali proof `mapping.txt`/16 KB ili runtime/device receipt nedostaje: **stop pre submit-a** (Task 6 + Task 8).
5. Posle pozitivnog acceptance-a neko promeni `origin/main` ili Play track/submit profil: ponovni authority gate mora zaustaviti submit (Task 3 + Task 7).

---

### Task 1: Zaključati kanonski release identitet i EAS CLI authority

**Files:** Create `scripts/release25/authority.mjs`, `scripts/release25/authority.test.mjs`; modify `AGENTS.md` *samo nakon read-only potvrde*; relevant `package.json` production script tek u Task 7.

**Interfaces:** `validateAuthority({agentsCliVersion,easCliVersion,helperCliVersion,packageName,appVersion,runtimeVersion,channel,track}) -> {ok,reason,identity?}`. `identity` je strukturisani manifest koji kasnije koristi preflight.

- [ ] Write RED tests `rejects_conflicting_pins` (24.7.0 ≠ 24.8.0 => `CLI_VERSION_CONFLICT`), `rejects_wrong_production_identity` (pogrešan package/channel/track) i `accepts_consistent_pin` (jedna ista verifikovana verzija).
- [ ] Run `"$NODE_BIN" --test scripts/release25/authority.test.mjs` iz Mobile root-a. Expected: RED samo zbog neimplementiranog modula/pravila.
- [ ] Implementirati `validateAuthority`; prilikom implementacije uraditi **read-only** proveru postojećeg host CLI 24.8.0, uporediti potpisane prethodne operation dokaze 492/493, i samo ako je potvrđeno, uskladiti `AGENTS.md` sa efektivnim `eas.json`. Ako read-only dokaz nije dostupan, gate ostaje `BLOCKED_CLI_AUTHORITY` bez menjanja pinova.
- [ ] Repeat isti test (GREEN) i `"$NODE_BIN" scripts/validate-project.mjs`. Expected: testovi 0 fail / validator `Ukupno FAIL: 0`, inače popraviti u scope-u.
- [ ] Commit samo dozvoljene Task 1 fajlove uz `git diff --cached --check`; upisati na kojoj verziji je authority zasnovan.

### Task 2: Atomic owned lock + journal koji preživljava pad

**Files:** Create `scripts/release25/journal.mjs`, `scripts/release25/journal.test.mjs`, `scripts/build25-release.sh`.

**Interfaces:** `createJournal({stateDir,attemptId,sourceSha})`, `transitionJournal(journal, expectedStage, nextStage, patch)`, `readJournal(stateDir)`, shell `flock -n LOCKFILE "$NODE_BIN" ...`. Stanja po specifikaciji uključuju `DISPATCH_OUTCOME_UNKNOWN`; journal čuva samo redaktovane metapodatke.

- [ ] Write RED tests `parallel_controller_only_one_dispatch` (dva child procesa, samo jedan lock), `failed_lock_acquire_cannot_unlink_owner_lock`, `orphan_intent_does_not_retry`, `atomic_partial_write_keeps_last_good_state`, `rejects_invalid_state_transition`, `lock_on_unsupported_filesystem_blocks`.
- [ ] Run `"$NODE_BIN" --test scripts/release25/journal.test.mjs` (RED). Testirati shell syntax zasebno: `bash -n scripts/build25-release.sh`.
- [ ] Implementirati protected out-of-worktree dir sa 0700, journal/lock 0600, atomarni same-filesystem temp write → fsync → rename → directory fsync gde podržano, bez uklanjanja lock fajla od strane nevlasnika. Proveriti `flock` semantiku na ciljnom CloudLinux hostu pre enablement-a; ako nema dokazive atomarnosti, fail-closed.
- [ ] Repeat test (GREEN); pregledati stvarno stanje pre/posle dva paralelna fake poziva i crash simulation-a.
- [ ] Commit samo Task 2 fajlove.

### Task 3: Preflight koji ne menja source, EAS remote version ni CMS

**Files:** Create `scripts/release25/preflight.mjs`, `scripts/release25/preflight.test.mjs`.

**Interfaces:** `collectPreflight({repoRoot,expectedSourceSha,authority,gitReader,easReader,toolProbe,acceptanceIndex}) -> Promise<{ok,blockers,evidence}>`. Pure adapters bez globalnih side effect-a, testni adapteri čitaju fixture.

- [ ] Write RED tests `blocks_dirty_or_divergent_canonical_index`, `allows_only_exact_known_htaccess_drift`, `blocks_missing_native_patch_or_open_release_finding`, `blocks_missing_signing_or_bundletool`, `blocks_remote_version_change`, `rejects_wrong_project_id_or_api` i `source_changed_since_preflight_blocks`.
- [ ] Run `"$NODE_BIN" --test scripts/release25/preflight.test.mjs` (RED).
- [ ] Implementirati read-only Git status manifest putem `git diff --cached --name-only --no-renames`, `git diff --name-only --no-renames`, `git ls-files --others --exclude-standard`, fetch/ancestry proveru bez reset/clean; read-only Expo public config, CMS static/TS/validator/OpenAPI evidence parsing i check-listu primenljivih A01–A14 blokada. Bez hardkodovanog starog SHA ili assumed remote versionCode.
- [ ] GREEN test, zatim lokalni `typecheck`, `validate-project.mjs` i CMS `php bin/static-check.php` samo ako raspoloživi runtime postoji; SKIPPED = NOT_READY, ne PASS.
- [ ] Commit samo Task 3 fajlove.

### Task 4: EAS transport sa jasnim read-only i mutation granicama

**Files:** Create `scripts/release25/eas-transport.mjs`, `scripts/release25/eas-transport.test.mjs`.

**Interfaces:** `createEasTransport({nodeBin,npmCli,easVersion,mobileRoot,spawn,fetchArtifact})` sa `readRemoteVersion()`, `readBuild(buildId)`, `listBuildsForReconciliation(intent)`, `startProductionBuild(intent,authorization)`, `downloadAabForBuild(buildId,target)`, `readSubmission(submissionId)`. Mutation je dozvoljena jedino sa dispatch tokenom iz journal-a.

- [ ] Write RED tests `remote_version_is_read_only`, `rejects_latest_and_unpinned_cli`, `rejects_mutation_without_authorization`, `rejects_zero_rc_bad_json`, `reconciles_unknown_build_without_redispatch`, `build_errored_cannot_submit` i `rejects_foreign_build_id_or_wrong_git_sha`.
- [ ] Run `"$NODE_BIN" --test scripts/release25/eas-transport.test.mjs` (RED).
- [ ] Implementirati tačne EAS CLI argumente iz Mobilnog root-a i canonical Node/npm, razdvojiti JSON stdout od stderr, proveriti RC/UUID/izvorni SHA. Read-only: `eas build:version:get --platform android --profile production --json` i `eas build:view BUILD_ID --json`; build dispatch tek kada authorized: `eas build --platform android --profile production --non-interactive --wait --json`, **bez `--auto-submit`**. Artifact URL dobiti iz exact build response-a, nikad iz input URL-a; koristiti HTTPS i ne logovati signed URL.
- [ ] GREEN test; `DISPATCH_OUTCOME_UNKNOWN` posle timeout-a nikad ne šalje novi EAS zahtev bez nezavisne reconciliacije.
- [ ] Commit samo Task 4 fajlove.

### Task 5: Stvarna kriptografska i paketna provera AAB-a

**Files:** Create `scripts/release25/verify-signature.mjs`, `scripts/release25/verify-signature.test.mjs`.

**Interfaces:** `verifySignature({sealedAabPath,expectedSha256,expectedUploadCertSha256,runTool}) -> Promise<{ok,reason,sha256,certSha256?}>`.

- [ ] Write RED tests `tampered_signed_payload_rejected_even_with_valid_zip_crc`, `rejects_wrong_upload_certificate`, `rejects_unsigned_entries_or_signature_error`, `rejects_apk_or_empty_bundle`, `rejects_symlink_and_nonregular_file` i `accepts_valid_signed_fixture`.
- [ ] Run `"$NODE_BIN" --test scripts/release25/verify-signature.test.mjs` (RED). Negative crypto fixture mora koristiti stvarni `jarsigner` nad privremenim signed JAR/AAB-like test sadržajem, zatim izmenu byte payload-a bez menjanja X.509 metadata; ne koristiti production key. Ako JDK nedostaje, test je BLOCKED, nikad virtuelni crypto PASS.
- [ ] Implementirati ZIP format/path-integrity, stvarni `jarsigner -verify` izlaz+RC i identitet stvarnog potpisnika po poznatom **upload key** SHA-256 (ne Google Play app-signing SHA-256); izračunati hash pre/posle.
- [ ] GREEN test sa dokazom da tampered fixture pada, a kontrolni signed fixture prolazi; nikada ne logovati key ili signed URL.
- [ ] Commit samo Task 5 fajlove.

### Task 6: Manifest, 16 KB, četiri ABI i release evidence

**Files:** Create `scripts/release25/verify-native.mjs`, `scripts/release25/verify-native.test.mjs`.

**Interfaces:** `verifyNativeAab({sealedAabPath,expectedIdentity,expectedVersionCode,expectedAbiSet,expectedRuntime,evidence,runTool}) -> Promise<{ok,blockers,observed}>`.

- [ ] Write RED tests `rejects_wrong_manifest_identity_version`, `rejects_missing_fourth_abi`, `rejects_missing_16k_zip_alignment`, `rejects_elf_load_below_16k`, `rejects_missing_mapping`, `rejects_missing_runtime_evidence`, `rejects_device_receipt_absence` i `passes_complete_fake_tool_fixture`.
- [ ] Run `"$NODE_BIN" --test scripts/release25/verify-native.test.mjs` (RED). Pozitivni mocked fixture nije produkcioni artifact PASS.
- [ ] Implementirati `bundletool validate`, parsiranje manifest/config iz stvarnog `bundletool` poziva i pregled bezbedno ekstrahovanih `.so` pomoću `readelf`. Uporediti \`minSdk\`, \`targetSdk\`, app package, versionName, remote-confirmed versionCode, potrebne `arm64-v8a`, `armeabi-v7a`, `x86`, `x86_64` ABI i 16 KB uslove; runtimeVersion dokaz iz Expo Updates podataka, a ne pretpostavljeno iz AndroidManifest-a. Mapiranje R8 i device receipt moraju biti vezani za finalni source i production build; nedostupni artefakti znače BLOCKED.
- [ ] GREEN test; posebno verifikovati da `mapping.txt` i device acceptance nisu lažno označeni kao PASS kada nedostaju.
- [ ] Commit samo Task 6 fajlove.

### Task 7: Ukloniti stari direktni Play submit bypass

**Files:** Create `scripts/release25/submit-gate.mjs`, `scripts/release25/submit-gate.test.mjs`; modify `scripts/submit-android-production.mjs`, `scripts/validate-project.mjs` i `package.json` (samo production relevantne putanje).

**Interfaces:** `submitAcceptedAab({journal,approval,build,sealedAabPath,expectedSha256,authority,easTransport}) -> Promise<{status,submissionId?}>`; stari CLI `--execute <BUILD_ID>` bez acceptance/ownership-a mora ostati odbijen.

- [ ] Write RED tests `old_execute_cannot_bypass_acceptance`, `rejects_build_id_or_sha_mismatch`, `rejects_modified_aab_after_acceptance`, `rejects_changed_main_or_play_profile`, `rejects_submission_without_device_gate`, `submit_timeout_never_autoretries` i `positive_submission_exact_verified_path_once`.
- [ ] Run `"$NODE_BIN" --test scripts/release25/submit-gate.test.mjs` (RED).
- [ ] Implementirati jedinu fail-closed barijeru: recheck sealed regular AAB sa 0700 parent/0600 file, stvarni hash, final source SHA, same Build ID, finalized signature/native/device evidence, submit profil i journal intent. Predviđeni operativni submit koristi **\`eas submit --platform android --profile production --path <sealed-verified.aab> --non-interactive --wait\`** i čuva exact original Build ID u journal-u; lokalni `--path` prolazi kao CLI metoda tek nakon dokazne parity provere na pinovanoj verziji. Bez proverene parity ili ako file identity nije moguće osigurati, STOP (nema alternativnog silent `--id` submit-a).
- [ ] Izmeniti legacy helper i source-string validator zajedno: `submit:android:production` bez acceptance dokaza ne može da izvrši pravi submit; ukloniti/pinovati direktni `eas-cli@latest` production build script. Ažurirane validator assertions moraju proveravati **barijeru**, ne samo prisustvo stringa `--id`.
- [ ] GREEN tests, `"$NODE_BIN" scripts/validate-project.mjs` (`Ukupno FAIL: 0`), lokalni typecheck. Commit samo Task 7 fajlove.

### Task 8: Controller state machine, siguran recovery i operativni report

**Files:** Create `scripts/build25-release-controller.mjs`, `scripts/release25/controller.test.mjs`; modify `scripts/build25-release.sh` nastao u Task 2.

**Interfaces:** CLI `preflight --source-sha SHA` (read-only), `simulate --fixture FIXTURE` (bez EAS/Play), `execute --authorization-file FILE` (budući release, samo pod validnim `flock`-om). `runController({mode,authority,preflight,journal,eas,signatureVerifier,nativeVerifier,submitter}) -> Promise<ReleaseResult>`.

- [ ] Write RED tests `preflight_and_simulation_zero_remote_mutations`, `no_execute_without_exact_authorization`, `one_build_then_one_submit`, `build_error_blocks_submit`, `orphan_intent_blocks_dispatch`, `wrong_artifact_blocks_submit`, `final_sha_or_version_change_blocks_submit`, `submission_unknown_blocks_retry` i `redacts_credentials_from_report`.
- [ ] Run `"$NODE_BIN" --test scripts/release25/controller.test.mjs` (RED).
- [ ] Implementirati state-machine redosled iz specifikacije, journal transition samo iz očekivanog stage-a i live terminal report. Pre EAS write zahtevati vlasnički `flock` i owner authorization vezan za tačan source SHA, EAS remote authority i dozvolu za najviše jedan build. Sačuvati report i artifact SHA bez signed URL / tokena. Napraviti STOP gde je stanje nepoznato.
- [ ] GREEN test; `bash -n scripts/build25-release.sh`; dva nezavisna simulator procesa moraju proizvesti najviše jedan fake build i jedan fake submit.
- [ ] Commit samo Task 8 fajlove.

### Task 9: Besplatni završni testovi i release handoff (bez build-a)

**Files:** Modify samo potrebne test fixtures / `docs/operations/NNN-...` report i eventualnu novu read-only evidence checklistu `docs/superpowers/plans/2026-10-08-build25-remaining-release-findings.md` ako je potrebna kao zasebno odobrena specifikacija (ne implementirati A06–A14 u ovom planu).

**Interfaces:** Test komande ispod i manifest dokaza `RELEASE_CONTROLLER_READY` koji izričito razlikuje code readiness od EAS authorization.

- [ ] Run `"$NODE_BIN" --test scripts/release25/*.test.mjs` iz Mobile root-a. Expected: 0 fail; potvrditi RED/GREEN za izmenjene funkcije, ne samo post-fix PASS.
- [ ] Run `"$NODE_BIN" scripts/validate-project.mjs` (Expected `Ukupno FAIL: 0`), `"$NODE_BIN" "$NPM_CLI" run typecheck` (RC0), `bash -n scripts/build25-release.sh` (RC0), a gde raspoloživo i CMS `php bin/static-check.php` (983/983) i OpenAPI parity; svaki SKIPPED = NOT_VERIFIED.
- [ ] Run samo fake/dry-run `preflight` / `simulate`, uključujući hostile fixture sa pogrešnim hashom i paralelnim pozivima. Expected: **zero EAS build/submit dispatch**, čitljivi redaktovani logovi.
- [ ] Inspect `git diff --check`, `git diff --cached --check`, machine-stable changed/untracked manifest i novouvezene skripte. Potvrditi odsustvo nepotrebnih dependency, app version/runtime, CMS/DB ili Google Play izmena.
- [ ] Zapisati operativni report sa novim slobodnim brojem, finalnim implementacionim source SHA, test RC-ovima, statusom A03/A04/A05 i preostalim A06–A14 blokadama; commit samo očekivani evidence. **Ne pokretati** `eas build`, `eas submit`, `eas update` ni GitHub native CI.

## Posle ovog plana: odvojeni release-readiness i Build25 dispatch

Ovo su **posebna odobravanja**, ne implicitni sledeći koraci:

1. Ispraviti i pojedinačno testirati A06/A07 Restore logout/401/password reset/revocation; A08 kompatibilnost aktivnog OTA+starog klijenta sa order API-jem; A09 file publication race; A10 canonical Git/reports; A11 native runtime boundary; A12 finalni signing, 16 KB i fizički device acceptance; A13 permissions/transakcioni testovi; A14 builder/toolchain parity. Otvorene nalaze ne preimenovati u PASS.
2. U jednoj **novoj kumulativnoj finalnoj source grani** integrisati već potvrđene Kotlin/splash korekcije, izmenjeni release controller i završene funkcionalne popravke. Testirati diff i Git tree; audit commit nije automatski production source.
3. Kada GH headroom dozvoli jedan kompletan native gate nad **finalnim** commitom (ne starim audit sha), pokrenuti ga jednom; pri 95% korisnički prijavljenoj potrošnji ne preskakati ga ni potajno ponavljati.
4. Tek sa svim pre-build PASS markerima, read-only EAS account/version/signing proverom i eksplicitnom release autorizacijom sme se izvršiti **jedan** production build bez `--auto-submit`, pa acceptance tačnog produkcionog AAB-a. Posle njegovog PASS-a sledi automatski submit po sealed `--path`. Ako je bilo šta UNKNOWN/FAIL/SKIPPED, stop bez dodatnog versionCode troška.
5. Stvarni Build25 execution report mora sadržati komandu, app version, remote-confirmed versionCode, runtimeVersion, source SHA, profile/channel, Build ID, AAB SHA256, submit ID/status, i copy/paste Play **„Napomene o verziji“**. \`eas submit FINISHED\` ne mora značiti da je izdanje već javno vidljivo.

**Current gate:** `NATIVE_AUDIT_PASS=YES`; `RELEASE_CONTROLLER_NOT_IMPLEMENTED`; `BUILD25_AUTHORIZED=NO`; `EAS_BUILD_STARTED=NO`; `EAS_SUBMIT_STARTED=NO`; `GITHUB_NEW_CI_RUNS_STARTED=NO`.

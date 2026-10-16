# Ald1n Build23–24 / Batch528 native CI: postmortem i trajne lekcije

**Datum pregleda:** 2026-10-08. **Status:** native audit na izolovanom kandidatu PASS; **Build25 / produkcioni submit BLOCKED**.  
**GitHub main u trenutku rekonstrukcije:** `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.  
**Audit branch / kandidat:** `audit/batch528-native-release-gate` / `7ff4a5ee1dabfb09131893041de33731d26b11d7`.  
**Native CI:** [run 37759246073](https://github.com/AldinAga/ald1n-project/actions/runs/37759246073), job `113251347290`, SUCCESS, `LAST_STAGE=complete`, `EXIT_CODE=0`.  
**Evidence artifact:** `11544652025`, SHA256 `d4d535fb3c5ee94b15d24237e2b42dfec05877de504a026ab1df11a9bdeef95f`; audit-only debug signing, **NE slati na Play**.

## Dokazani problemi koji su kočili projekat

| # | Blokada / uzrok | Status i trajno pravilo |
| --- | --- | --- |
| 1 | **Build23 EAS ERRORED:** lokalni Expo Android library modul nije imao propisanu `defaultConfig.versionName`/verzionu metapodatkovnu konfiguraciju. | Ispravljeno u source-u pre Build24; pre EAS slanja proveriti stvarni generated Gradle modul, ne samo manifest/string validator. |
| 2 | **Build24 EAS ERRORED:** `clearRestoreCredential` Kotlin Coroutine overload ambiguity i neadekvatan `null` rezultat. | Audit kandidat koristi eksplicitnu zero-argument coroutine sa `Unit` završetkom; dokazati stvarni Kotlin RED/GREEN na zaključanim Expo zavisnostima. |
| 3 | **False readiness iz statičkih testova:** prethodni Android smoke i dependency snapshot nisu kompajlirali pun release. | `expo-doctor`, TypeScript, prebuild, manifest i graph nisu zamena za `:app:assembleRelease`, `:app:bundleRelease`, R8, mapping i Lint nad tačnim commitom. |
| 4 | **Host state / cleanup:** staged i unstaged reports, poznati `.htaccess` drift i historijski tragovi blokirali su Batch527. | Nikada reset/clean na slepo; čitati operativni report i sačuvati HEAD/status/index/diff; menjati u eksternom izolovanom worktree-u. |
| 5 | **Neispravno vlasništvo release lock-a:** drugi runner je mogao da ukloni lock prvog pri neuspelom acquire-u. | Lock sme očistiti samo njegov dokazani vlasnik; dupli EAS dispatch mora biti idempotentan i ponovno pokretanje ne sme automatski kreirati novi build. |
| 6 | **CloudLinux Node path / inventory:** default shell nije nalazio Node; diff provera je propuštala untracked report; dependency inventory je eager-resolve-ovao Android artefakte. | Koristiti `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node` (izmereno v22.23.3), machine-stable tracked+untracked manifest, Gradle `configuration.incoming.resolutionResult`. |
| 7 | **Gradle composite build:** globalni audit init skript zahtevao je `:app` i u React Native included build-u, gde on ne postoji. | Native snapshot vezati isključivo za generated glavni `:app`, uz negativne composite testove; ne pretvarati izostanak target-a u SKIPPED PASS. |
| 8 | **GitHub runner disk exhausted:** tokom native C++ `armeabi-v7a` kompajliranja `No space left on device`. | Pre C++ zabeležiti slobodan prostor; očistiti isključivo klasifikovane nepotrebne alate sa ephemeral hosted runnera; zadržati sve četiri ABI. |
| 9 | **CMake bootstrap izostao:** cleanup je čuvao samo CMake 3.22.1, ali ga `sdkmanager` prethodno nije instalirao; guard je pravilno odbio nedostajući Ninja. | Eksplicitno instalirati i proveriti `cmake;3.22.1`, NDK 27.1.12297006, platform 36 i build-tools 36.0.0 **pre** čišćenja diska. |
| 10 | **Android Lint engine / Worklets 0.10.1:** `:react-native-worklets:lintAnalyzeRelease` pao sa `Cannot find a KaModule for the VirtualFile`. | Evidencija [Android bug 430991549](https://issuetracker.google.com/issues/430991549); izolovan, verzijski proveravan workaround samo u ephemeral dependency kopiji, nikad globalno gašenje Lint-a. |
| 11 | **Isti Lint engine bug / Reanimated 4.5.1:** nakon Worklets workaround-a pao je `:react-native-reanimated:lintAnalyzeRelease`. | Oba proverena modula imaju uski audit-only izuzetak; ne tvrditi da je kompletan third-party Lint pokriven. App Lint je obavezan. |
| 12 | **Pogrešan CI redosled:** R8/release je završavao 30–40 min pre nego što bi se videla fatalna Lint greška. | Redosled: source contracts → prebuild/resource guards → snapshot → **app Lint** → Kotlin RED/GREEN → release/R8 → artifact inventory. |
| 13 | **Expo SDK57 splash API33 greška:** `android:windowSplashScreenBehavior=icon_preferred` ubačen u neversionisani `res/values/styles.xml` iako aplikacija podržava minSdk24. | Native config plugin premešta samo taj atribut u `res/values-v33`; odmah posle prebuild-a proveriti obe XML lokacije, a splash na uređajima API24–32 i 33+. Nema povećanja minSdk niti `NewApi` suppression. |
| 14 | **CI deprecations i JVM performance warning:** Node20-backed GitHub Actions, `punycode`, `url.parse()`, Gradle9 `--warning-mode all` upozorenja i daemon heap/metaspace restart poruka. | **Nisu bili fatalni uzroci prethodnih build padova.** Odvojeno analizirati poreklo, preći na verifikovane Node24-native actions i server-parity Node22.23.3; ne skrivati warninge, ne raditi slepi AGP9/Gradle10 upgrade. |

Izveštaji na audit grani: `docs/operations/551`, `552`, `555`, `558`, `559`, `560`, `561`, `562`. Precizni pojedinačni dokazi i veze nalaze se u tim dokumentima i u [dubinskom auditu](https://github.com/AldinAga/ald1n-project/blob/7ff4a5ee1dabfb09131893041de33731d26b11d7/docs/superpowers/specs/2026-10-07-build22-24-deep-audit.md). Odsutni istorijski brojevi u toj listi nisu dokaz da njihove recovery generacije nikada nisu postojale.

## Šta je zapravo dokazano 2026-10-08

- Native GitHub CI `37759246073`: **SUCCESS**, Kotlin RED reproduciran, kandidat GREEN, `:app:lintRelease` stvarno izvršen, `:app:minifyReleaseWithR8`, `:app:assembleRelease`, `:app:bundleRelease`, `mapping.txt`, audit APK/AAB, `EXIT_CODE=0`.
- Ovaj dokaz je vezan **isključivo za audit SHA `7ff4a5ee...`**; GitHub `main` je ostao `b5b942e...` i nema nove produkcione binarne distribucije.
- U GitHub CI app Lint PASS postoje samo dokumentovani privremeni exception-i za Worklets/Reanimated Lint engine crash; ne tvrditi zero third-party Lint exclusions.
- Audit artifact koristi debug signing i nije Play upload payload.
- **Poslednja ranije izmerena** EAS remote `versionCode=24` (Report550); ovo nije novo merenje. Sledeći build nije automatski `25` dok se EAS remote authority ponovo ne očita.
- `APP_VERSION=1.0.0`, `runtimeVersion=1.0.0-build17`, package `com.ald1n.mobile`, profile/channel `production/production` su dokumentovana postojeća podešavanja, ali runtime-kompatibilnost i finalni production source nisu sertifikovani.

## Nezatvoreni release rizici — Build25 BLOCKED

Izvorni [Build22–24 audit A01–A14](https://github.com/AldinAga/ald1n-project/blob/7ff4a5ee1dabfb09131893041de33731d26b11d7/docs/superpowers/specs/2026-10-07-build22-24-deep-audit.md) ostaje release authority. Native uspeh znatno unapređuje A01/A02, ali ne zatvara automatski A04–A14:

- **A04 P0:** stari `--auto-submit` put zakazuje slanje pre finalne verifikacije artefakta. Dozvoliti samo atomizovan, bezbedan sled **exact production build → artifact/signature/ABI audit PASS → submit exact BUILD_ID**, uz automatizaciju *posle* gate-a; ne koristiti preuranjeni auto-submit.
- **A05 P1:** proveriti kriptografski potpis payload-a (`jarsigner -verify` sa odgovarajućom semantikom), ne samo X.509 cert ili ZIP CRC; jasno razdvojiti upload signer od Google Play app-signing signer-a.
- **A06/A07 P1:** Restore Credentials race, logout/401 cleanup, password-change/session revocation i reauthentication; testirati fallback i realni device.
- **A08 P1:** kompatibilnost Build22/aktivnog OTA klijenta i novog order confirmation `order_version_token` API-ja; bez vraćanja zaobilaznih finansijskih endpointa.
- **A09 P1:** konkurentna publikacija slika može obrisati target koji je objavio drugi poziv.
- **A10 P1:** canonical hosting staged/index/residue i arhivski dokazni zapis nisu forenzički reconciled.
- **A11 P1 pre-OTA:** runtimeVersion kompatibilnost sa promenjenim native modulima još nije dokazana.
- **A12 P1 release:** realni final-binary ABI, 16KB page-size, R8/mapping, signature i fizički Android device acceptance nisu zatvoreni na produkcionom artefaktu.
- **A13 P1/P2:** nedovoljni behavioral/test-database dokazi za nove poslovne tokove, permisije i transakcije.
- **A14 P2:** EAS builder image/profil/CLI reproducibility i `eas-cli@latest` alternativne skripte; `AGENTS.md` i `eas.json` prikazuju različite CLI pinove i moraju se eksplicitno reconciled-ovati, ne pretpostaviti.

## Nepromenljiva pravila za buduće Ald1n batch-eve

1. **Svaki novi batch:** učitati live GitHub `main`, poslednje `docs/operations`, `AGENTS.md`, EAS remote authority (ako je release), i čitati tačan prethodni report SHA. Ne koristiti stale commit/version.
2. **FAIL → root cause → najmanji RED/GREEN test → uski recovery.** Posle više neuspeha istog tipa preispitati arhitekturu gate-a, a ne serijski povećavati generacije skripata.
3. **Jeftine provere prve.** Source, manifest, config, Expo compatibility i generated resources moraju pasti pre Gradle, Lint pre R8, production submit posle artifact audita.
4. **CI mora razlikovati** `SOURCE_CHECK_PASS`, `APP_LINT_PASS_WITH_KNOWN_EXCEPTION`, `NATIVE_COMPILE_R8_PASS`, `SIGNED_PRODUCTION_ARTIFACT_PASS`, `RELEASE_AUTHORIZED`. Nijedan od ovih statusa ne implicira automatski naredni.
5. **Samo verzijski pinovani kompatibilni alati**, pinovani GitHub Action commit SHA; bez deprecated runtime-a gde postoji kompatibilna stabilna zamena. Gradle warninge dijagnostikovati, ne sakrivati.
6. **No duplicate builds:** race-free owned lock, idempotentni release dispatch; nemoj ponavljati EAS build posle nepoznatog API ishoda dok se remote lista ne proveri.
7. **Release metapodaci pri svakom stvarnom production buildu:** tačna pokretačka komanda, app version, remote versionCode, runtimeVersion, source SHA, profile/channel i copy/paste Play Napomene o verziji.
8. **GitHub native audit PASS nije Google Play autorizacija**; ne slati audit-only debug-signed artefakte.
9. Ovo je trajna incident dokumentacija; svaki naredni release plan mora se pozvati na nju i uključiti status svih otvorenih A nalaza.

**Aktuelna odluka:** `NATIVE_CI_PASS=YES`; `BUILD25_AUTHORIZED=NO`; `EAS_BUILD_STARTED=NO`; `EAS_SUBMIT_STARTED=NO`; `RELEASE_READINESS=BLOCKED_OPEN_AUDIT_FINDINGS`.

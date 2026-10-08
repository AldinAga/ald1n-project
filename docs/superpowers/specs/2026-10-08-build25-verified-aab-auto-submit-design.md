# Ald1n Build25 — Safe verified-AAB automated Google Play submission (design)

**Datum:** 2026-10-08. **Faza:** DESIGN / OWNER REVIEW. **Nema odobrenja za production build, EAS submit ili OTA.**

## 1. Odobreni cilj i pravilo troškova

Vlasnik projekta je odobrio **potpuno automatizovano slanje na Google Play, ali isključivo nakon uspešne nezavisne verifikacije tačnog produkcionog AAB-a**. Cilj je zatvoriti release-safety nalaze A03 (lock ownership/duple akcije), A04 (preuranjeni AutoSubmit) i A05 (false-positive digitalni potpis), pripremiti finalni source commit i zatim napraviti najviše **jedan** odobreni Build25 pokušaj.

Vlasnik prijavljuje da su GitHub Actions workflow minuti i artifact storage na približno **95%** iskorišćenja (nije nezavisno očitano kroz GitHub billing API). Zbog toga:

- Ne pokretati nove GitHub-hosted native workflowe tokom dizajna, razrade koda i lokalnih regression testova.
- Bez automatskog re-run-a prošlih native poslova, upload-a novih velikih GitHub evidence ZIP-ova ili čišćenja ranijih artefakata bez kopije/hash-a i posebne odluke.
- Koristiti izvorne Node/PHP/Bash testove i izolovane test adaptere, bez pokretanja EAS build/submit. Jedan **finalni** GitHub native CI gate planirati tek kada je finalni source stabilan i kada je Actions kvota/prostor potvrđen; ako je nedostupan, označiti native parity kao BLOCKED i čekati oslobađanje kapaciteta umesto zaobilaženja gate-a.
- EAS cloud build/storage/billing je zasebna usluga: ne pretpostavljati da su GitHub kvote i EAS kvote iste.

## 2. Dokazano početno stanje (ne koristiti kao budući stale authority)

- GitHub \`main\` na dan dizajna: \`b5b942e645d28d3ed5f248eaae4180ebe9a54ee6\`.
- Uspešni izolovani native audit commit: \`7ff4a5ee1dabfb09131893041de33731d26b11d7\`; GitHub Actions run \`37759246073\` i job \`113251347290\` završeni SUCCESS / EXIT_CODE=0; dokazani App Lint (uz uske Worklets/Reanimated exception-e), Kotlin RED/GREEN, izvršen R8, assembleRelease, bundleRelease i mapping.
- Taj commit **nije** \`main\` niti odobreni EAS production source. Audit AAB je debug-signed i nikad se ne šalje na Play.
- U \`main\` je \`apps/mobile/current/eas.json\`: \`cli.version=24.8.0\`, \`appVersionSource=remote\`, production \`autoIncrement=true\`, \`channel=production\`; submit Android \`track=production\`, \`releaseStatus=completed\`.
- \`apps/mobile/current/scripts/submit-android-production.mjs\` u \`main\` zahteva eksplicitan EAS Build ID i proverava identitet/konfiguraciju, ali **ne zahteva** dokaz kriptografskog integriteta produkcionog AAB-a pre \`--execute\`. Zbog toga samostalna postojeća \`--execute\` putanja nije prihvatljiv završni release gate.
- \`AGENTS.md\` još navodi \`eas-cli@24.7.0\`, dok operacioni izveštaji 492/493 i source \`eas.json\` navode 24.8.0. Razliku razrešiti read-only proverom verzije/komandi pa promeniti pravilo ili konfiguraciju jedinstveno; **ne pretpostaviti automatski**.
- Ranije dokumentovan Android remote versionCode je \`24\`, ali nije sada sveže očitan. Build nazvati **25** tek kada read-only EAS authority potvrdi da je sledeći kod stvarno 25.
- Dubinski audit \`docs/superpowers/specs/2026-10-07-build22-24-deep-audit.md\` ostavlja A04/A05 i druge nalaze otvorenim. \`docs/operations/562-...\` i \`docs/incident-reviews/2026-10-08-build23-24-batch528-native-ci-postmortem.md\` su dopunski dokazi.

## 3. Izbor arhitekture

Razmotrene alternative:

1. **\`eas build --auto-submit\`**: NE, jer submission postaje zakazan pre lokalnog provere AAB-a (potvrđeni A04).
2. **Odvojeni on-host release controller — IZABRANO**: kontrolisani single dispatch, čekanje EAS build rezultata, download konkretnog AAB-a, strogi audit, i automatski EAS Submit tek posle dokazivog PASS-a.
3. **Novi EAS Workflow sa ugrađenim acceptance jobovima**: potencijalno dobro za budućnost, ali trenutno širi implementaciju, infrastrukturu i potencijalni trošak. Ne uvoditi ga samo da se reši A04.

Controller pokreće operativni owner sa CloudLinux hosta, u jednom izolovanom release workspace-u; nikada ne menja CMS podatke, ne koristi GitHub Actions kao runtime release kontrolera i ne zahteva full Android kompilaciju na CloudLinux hostu.

## 4. Granice i moduli

### 4.1 Read-only preflight bez production write-a

Kontroler najpre obavezno:

- Fetchuje \`origin/main\`, proverava lokalni HEAD/index/status i klasifikovani hosting drift bez nasilnog \`reset\` ili \`clean\`; precizno prati finalni source commit i mobilni source tree.
- Proverava release provenance (tačan finalni source SHA, uključene Kotlin/splash popravke i dodatne prihvaćene promene) i završne dokaze za primenljive A01–A14 nalaze.
- Proverava compile/runtime/platform konfig: \`com.ald1n.mobile\`, app version, Android target/min SDK, Expo project, production API, \`production\` profil/channel i očekivani track/status.
- Read-only preko odabranog pinovanog EAS CLI i iz \`apps/mobile/current\` čita account/project, trenutni EAS remote Android versionCode, postojeće build/submission ID-jeve i kredencijale/bezbedne metapodatke; **ne pokreće** \`build:version:set\`, submit ni bilo kakvu mutaciju.
- Proverava da li su raspoloživi Java/JDK \`jarsigner\`, SHA256 alat, \`bundletool\` i binarni alati za 16 KB/ELF inspekciju, pristup temp prostoru i korisnički očekivanom javnom upload cert otisku. Ne instalira alat na produkcioni hosting bez zasebnog odobrenja. Ako nema prihvatljive read-only inspekcije, stop **pre EAS build-a**.
- Proverava fizički/device acceptance plan i otvorene audit nalaze: ako zahtevani release-blocking P0/P1 gate nije prošao, \`RELEASE_READINESS=BLOCKED\`. Izuzeci za third-party Android Lint ostaju eksplicitni.

### 4.2 Jedan owned lock i trajno stanje

Primeniti per-project \`flock\` ili drugo dokazano atomarno zaključavanje sa identitetom vlasnika i testiranom semantikom na hostingu; samo vlasnik oslobađa lock. Izvan Git worktree-a voditi permission-restricted, atomarno zapisan i ispitivljiv release journal sa \`attempt_id\`, final source SHA, remote versionCode, build ID, submission ID, artifact hash, timestampima i fazama.

Faze: \`PREFLIGHT_PASS\` → \`BUILD_DISPATCH_INTENT_PERSISTED\` → \`BUILD_ID_KNOWN\` → \`BUILD_FINISHED\` → \`AAB_VERIFIED\` → \`SUBMIT_DISPATCH_INTENT_PERSISTED\` → \`SUBMISSION_ID_KNOWN\` → \`SUBMIT_FINISHED\` → \`FINAL_STATUS_VERIFIED\`.

Ako se EAS poziv prekine posle poslatog zahteva, ali pre poznatog Build/Submission ID-ja, stanje je \`DISPATCH_OUTCOME_UNKNOWN\`: **nikad automatski novi build/submit**. Prvo je potrebna read-only remote reconciliacija; kada je nemoguće dokazati identitet, stop i traži eksplicitnu odluku. EXIT cleanup ne sme ukloniti tuđi lock niti izbrisati journal.

### 4.3 Production build — samo nakon P0 source gate-a

Jedan eksplicitni \`eas build --platform android --profile production --non-interactive --wait --json\` iz finalnog izvornog checkout-a, sa jedinstvenim odgovarajućim **pinovanim EAS CLI-jem**, **bez \`--auto-submit\`**. Journal beleži tačnu stvarnu komandu i dispatch rezultat. Potrošeni \`versionCode\` ostaje potrošen i ako se build završi ERRORED; nikad ne obećavati da se Build25 može ponoviti bez versionCode promene.

Posle build-a dobiti i verifikovati tačan Build ID, build profil, izvorni commit, status, vremensku vezu sa namerom dispatch-a, versionName i remote versionCode; nema \`--latest\`.

### 4.4 Produkcioni AAB acceptance — obavezno pre submit-a

Preuzeti .aab **isključivo iz konkretnog verifikovanog EAS Build ID-ja** i upisati SHA-256 pre i posle inspekcije. Fail-closed proveriti:

1. Artifact je regularan ne-prazan AAB, bez path traversal/ZIP integritet grešaka; provenance je tačan EAS Build ID i immutable inspected file.
2. \`jarsigner -verify\` proverava stvarni potpis sadržaja, sve relevantne potpisane stavke i signer identity; razlikuje legitimnu Android upload-key semantiku od lažnog PASS-a na X.509 metadata i ZIP CRC. Porediti **upload-key** fingerprint sa nezavisnim očekivanim otiskom; Google Play **app-signing** fingerprint je drugačiji.
3. \`bundletool validate\` i manifest čitanje potvrđuju \`com.ald1n.mobile\`, app version, **stvarni** \`versionCode\`, target/min SDK, legitimne permission/intent filtere i production konfiguraciju.
4. \`bundletool dump config\` pokazuje ispravno 16 KB bundle page alignment; proveriti ELF \`LOAD\` segment alignment za svaki uključeni \`.so\`, potrebne četiri ABI i potrebnu runtime/native kompatibilnost; nijedna od ovih provera pojedinačno nije pun dokaz device funkcionalnosti.
5. R8/minify i mapping evidence prate isti finalni source i prihvaćeni native audit; ako je production builder odstupio ili nedostaje potreban dokaz, \`BLOCKED\`.
6. Ako je za ovaj release neophodan fizički device acceptance na stvarnom binary-ju, to je odvojen uslov koji mora biti dokazano ispunjen **pre** bilo kakvog AutoSubmit-a. Bez odgovarajućeg dostupnog uređaja/test-farme automatski pipeline se zaustavlja na tom gate-u; ne izdaje lažni PASS.

Testovi moraju dokazati da promenjeni payload sa i dalje validnim ZIP CRC-om i istim cert metadata *pada* na kriptografskom verifieru.

### 4.5 Automatizovani submit tek posle acceptance PASS-a

Controller automatski nastavlja samo ako dokumentovan acceptance za **isti Build ID + isti AAB SHA-256 + isti finalni source SHA** ima PASS. \`--auto-submit\` pri buildu nije dozvoljen.

Podržani Expo CLI načini su \`eas submit --platform android --profile production --id <BUILD_ID> ...\` ili \`--path <TAČNO_VERIFIKOVANI_AAB>\`. Konačni implementacioni izbor mora dokazati byte-identity, odnosno da EAS Submit zaista koristi baš prethodno verifikovani AAB; preferirati verified immutable local path ako se preko istog pinovanog CLI-ja dokaže kompatibilnost, a u journal upisivati pripadajući Build ID. Nikad \`--latest\`, proizvoljni URL ili ponovno slanje po nepouzdanom timeout-u.

Pre submit-a ponovo pročitati EAS status i release source authority (bez promene source-a). Google Play submit profil mora ostati \`production\`, \`track=production\`, \`releaseStatus=completed\`, prema unapred odobrenoj politici vlasnika. Po završetku zapisati submission ID i status; **EAS Submit FINISHED nije dokaz da je Play release već javno dostupan**, jer Play review / managed publishing mogu biti zasebni.

### 4.6 Izlazni podaci i operativni trag

Svaki stvarni production build report mora navesti: tačnu build komandu, \`app version\`, daljinski potvrđen \`versionCode\`, \`runtimeVersion\`, **final source commit**, \`profile/channel\`, build ID, artifact SHA-256, upload cert otisak, AAB acceptance, submission ID/status i copy/paste Google Play **„Napomene o verziji“**. Ne unositi privatne kredencijale, key fajlove i signed URL u report.

Svi izveštaji koriste sledeći slobodan numerički \`docs/operations\` broj nakon svežeg inventara i poštuju \`AGENTS.md\` pravilo report retention-a. Ne mešati novi report u stare neizmenljive evidence fajlove.

## 5. Izolovani testovi pre bilo kakvog build-a

**Bez GitHub-hosted native runa** i bez EAS mutacije:

- Dva paralelna release zahteva: samo jedan zaključava i sme da stigne do dispatch-a; odbijeni ne dira lock prvog.
- Timeout pre/posle slanja build zahteva; status UNKNOWN ne sme ponoviti build niti versionCode.
- EAS BUILD ERRORED; zabranjeno auto-submit.
- Build ID se razlikuje od zapisane namere ili build source SHA; zabranjeno acceptance/submit.
- Potpisan bundle sa izmenjenim payload-om, validnim ZIP CRC-om i sačuvanim cert metadata; verifier FAIL.
- Pogrešan upload-key fingerprint, \`versionCode\`, package, runtime, ABI/16 KB, APK umesto AAB, nedostajući mapping; FAIL.
- Nepostojeći \`bundletool\` / JDK / prostor na disku; stop **pre build dispatch-a**.
- Race: artifact promenjen posle hash provere; pre-submit hash recheck obavezno FAIL.
- EAS submit timeout ili izgubljen response posle dispatch-a; nema duplog submission-a.
- Namerno nedostajući device approval ili otvoren release-blocking audit nalaz; submit nikada ne kreće.
- Pozitivni fixture: dokazani verified AAB + exact build ID + svi prethodni gate-ovi; samo jedan submit.
- CLI / input authority: mora odbiti neusaglašene pinove \`AGENTS.md\` i \`eas.json\` dok se verzija namerno ne uskladi.

Test fixture mora da koristi fake EAS transport, fake signer/bundle inspector i izolovane tmp direktorijume; nikada ne treba pravi Play nalog za regresione provere.

## 6. Minimalan redosled release aktivnosti

1. Prihvatiti ovaj dizajn; potom zaseban implementacioni plan i review.
2. Implementirati A03/A04/A05 controller sa RED/GREEN fixture testovima na odvojenoj grani, ne na kanonskom host checkout-u; rešiti EAS CLI authority konflikt.
3. Zatvoriti preostale stvarne release blockers (A06/A07 auth/restore, A08 klijentska kompatibilnost, A09 konkurentne slike, A10 source/index, A11 runtime, A12 ABI/device i relevantne A13/A14), sa eksplicitnom klasifikacijom svakog. Nisu svi isti prioritet, ali nijedan nedokazani P0/P1 ne može biti prećutno proglašen zatvorenim.
4. Napraviti **jedan** kumulativni finalni source kandidat; review diff vs trenutno testirani audit commit; proći jeftine lokalne statičke/funkcionalne gate-ove.
5. Rezervisati završni native CI tek kada je finalni commit zaključan **i** kada GitHub minut/storage headroom dopuštaju realan 60–70-minutni run i njegov verifikacioni artifact; ako ne, čekati, ne preskakati.
6. Pred production EAS build potvrditi live remote \`versionCode\`, signing i builder authority. Tek tada eksplicitno autorizovati jedan Build25 pokušaj.
7. Izvršiti build bez auto-submit, production AAB acceptance i potpuno automatski Play submit nakon PASS-a; za svaki korak snimiti ID, sha, status i Napomene o verziji.

## 7. Granice i STOP uslovi

- **Sada:** \`BUILD25_AUTHORIZED=NO\`, \`EAS_BUILD_STARTED=NO\`, \`EAS_SUBMIT_STARTED=NO\`, \`OTA_PUBLISHED=NO\`, \`MAIN_SOURCE_MUTATED=NO\`.
- Ovaj dokument ne daje dozvolu za CI workflow, produkciono EAS slanje, promenu Google Play trake, brisanje GitHub artefakata ili uređajni test na produkcionim poslovnim podacima.
- Zahtevi za prilagođavanje globalnog Gradle/AGP/Expo/native toolchain-a, GitHub Node20 Actions warnings ili podizanje lokalnog Docker stack-a nisu deo ovog P0 controller implementacionog scope-a.
- Ako se pre implementacije GitHub main, operacioni reporti, \`eas.json\` ili realni remote versionCode promene, obavezna je **ponovna** rekonstrukcija autoriteta — istorijske SHA vrednosti gore ostaju samo evidencija, ne automatski preflight expected values.

## Izvori

- GitHub canonical \`AGENTS.md\`, \`apps/mobile/current/eas.json\`, \`apps/mobile/current/scripts/submit-android-production.mjs\` i audit \`docs/superpowers/specs/2026-10-07-build22-24-deep-audit.md\`.
- Reports \`551\`–\`562\` na \`audit/batch528-native-release-gate\`; CI \`37759246073\`.
- [Expo CLI reference: build download / submit exact ID or path](https://docs.expo.dev/eas/cli/).
- [Expo remote app versions / autoIncrement](https://docs.expo.dev/build-reference/app-versions/).
- [Android 16 KB native page-size guidance](https://developer.android.com/guide/practices/page-sizes).
- [GitHub artifact retention and irreversible deletion](https://docs.github.com/en/actions/how-tos/manage-workflow-runs/remove-workflow-artifacts).

**Review decision needed:** vlasnik treba da potvrdi sadržaj *ovog pisanog dizajna* pre pisanja implementacionog plana i bilo kakvog source patch-a.

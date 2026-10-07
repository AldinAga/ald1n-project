# Ald1n: dubinski audit Build22 -> Build24

**Datum:** 07.10.2026.  
**Rezim:** READ_ONLY / SOURCE_REVIEW / LOCAL_REPRODUCTIONS.  
**Odluka:** BLOCKED_FOR_NEXT_PRODUCTION_BUILD_AND_RELEASE.  
**Novi production build, submit, OTA, DB write, Git commit/push:** nisu pokretani tokom ovog audita.

Ovaj dokument nije autorizacija za Build25, niti deployment skripta. Ne treba ponavljati Batch526 ili Batch527. Izvrstan rezultat statickog validatora nije dokaz da Android release varijanta moze da se kompajlira, da R8 ne uklanja potreban kod ili da Restore Credentials bezbedno radi na uredjaju.

## 1. Izvrsni zakljucak

Postoje potvrdjeni problemi, a ne samo nedostatak apsolutne sigurnosti. Kotlin uzrok pada Build24 jos je u kanonskom source-u. Report550 pokazuje da Batch527 nije stigao do patch-a. Pored toga, ovaj audit je reprodukovao greske u vlasnistvu nad lock-om, proveri potpisa, Restore lifecycle-u i paralelnoj publikaciji slika. Poredjenje Build22 API klijenta i novog backend-a otkriva i ugovornu nekompatibilnost pri potvrdi porudzbine.

Ranija oznaka spremnosti bila je presiroka: prebuild, typecheck, source string assertions i dependency graph nisu zamena za stvarnu release kompilaciju. Nema osnova za tvrdnju da ce sledeci EAS build sigurno uspeti. Potrebno je zatvoriti dokazive probleme i dobiti stvaran native CI rezultat nad TACNIM sledecim candidate commit-om, bez trosenja novog production versionCode-a za dijagnostiku.

Ovo nije tvrdnja da su R8 ili AGP uzrok dokumentovanih padova. Build23 je pao na konfiguraciji lokalnog Expo modula. Build24 je pao na Kotlin overload-u. Preostali R8 i artifact rizici jos nisu prihvatljivo ispitani na novom candidate-u.

## 2. Rekonstruisano stanje i granice autoriteta

| Stavka | Dokazano stanje |
| --- | --- |
| Repository | AldinAga/ald1n-project |
| Build22 source | e082641bd4e58c320d4faca0dd50997deca7f416 |
| Build22 EAS ID | b32bfcbd-5f4c-45f5-95a9-5b756749b31a |
| Build22 | Poslednji dokazani uspesni production binary u pregledanim evidencijama |
| Build23 | ERRORED; problem android.defaultConfig.versionName; bez AAB-a |
| Source posle Gradle metadata popravke | 7066a7aee04a7bdb2c37e862905f74306737e78b |
| GitHub main tokom audita | b5b942e645d28d3ed5f248eaae4180ebe9a54ee6 |
| Kanonski Mobile tree | e24cf1c0c04bf6e784abbbe396b14ee42753ecb0 |
| Build24 EAS ID | f9af75fe-3a53-4548-a4d7-8d6d5b530ec4 |
| Build24 | ERRORED; Kotlin compileReleaseKotlin; bez AAB-a |
| Build24 AutoSubmit ID | 9ba6f205-c69b-41b3-be81-1b8e0f3d1fce |
| AutoSubmit poslednje izmereno stanje | CANCELED, Report550 |
| Poslednji izmereni remote versionCode | 24, Report550; nije novo nezavisno EAS merenje ovog audita |
| App / runtime / package | 1.0.0 / 1.0.0-build17 / com.ald1n.mobile |
| Profile / channel | production / production |
| Batch527 u Report550 | FAIL u worktree-preflight; SOURCE_PATCHED=NO |
| Build25 | Nije autorizovan ovim auditom |

Report550 je vazniji od stare uspesne projekcije u 000-LATEST za runtime stanje. GitHub main je vazniji od necommitovanog lokalnog 000-LATEST za source autoritet. Ni jedan od njih se ne sme zameniti pretpostavkom da je planirani patch vec primenjen.

Report550 SHA-256: `5a6e1246dce5c4022936ffaf077703d7dbb9a24b4c317b7c02b471fe3532fb20`.

Report549 SHA-256: `55ac9f4f2d47369ab5322ec26e493804d6cfb95d5075f7b81ea7d1ce911dc2df`.

Report550 zabelezuje staged `docs/operations/000-LATEST.md`, staged-plus-modified Report549, poznati unstaged Report548 tail, poznati .htaccess drift i istorijske untracked izvestaje. To nije cist worktree. Nista od toga nije resetovano ili obrisano tokom ovog audita.

## 3. Metod i sta ovaj audit zaista dokazuje

Pregledana je razlika Build22 source-a i aktuelnog main-a, native konfiguracija i istorijski R8/AGP audit, kljucne nove auth/backend putanje, relevantni operations izvestaji, oba poslednja runnera, novije funkcije porudzbina i publikacije slika, kao i zvanicna Android/Expo dokumentacija. Poredjenje commit-a obuhvata 36 commit-a, ukljucujuci dokumentacione checkpoint-e; taj broj nije broj funkcionalnih izmena.

Izvrseno je 14 izolovanih test-scenarija. Njihovi JSON rezultati i reprodukcioni kod prate dokument. Za dva produkciona source fajla testirana su tacna tela fajlova, sa proverom Git blob hash-a pre testiranja:

- Restore TypeScript: `80d22380100e063bd5b58ae69653da7ea42a7899`.
- ProductImagePublicationService PHP: `014f529e857098c9900da3e0a354019db477d023`.

Kotlin probe koristi Kotlin 1.9.0 i minimalne stubove Expo overload potpisa. Build24 log navodi Kotlin 2.1.20. Probe dokazuje jezicki uzrok i razlikuje predloge popravke; NE dokazuje da je ceo Android projekat sa stvarnim zavisnostima uspesno kompajliran.

TypeScript test koristi stvarni pregledani source, uz simulirane Android, storage i HTTP adaptere. PHP test koristi stvarni pregledani servis, stvaran privremeni filesystem i simulirane DB/Eloquent adaptere, sa kontrolisanim Fiber redosledom dva poziva. To nisu probe na produkcionoj bazi ili stvarnom Google Credential Manager-u.

Potpis je testiran na sintetickom JAR-u sa privremenim test sertifikatom. Produkcioni kljucevi nisu korisceni. Test kljuc je obrisan zajedno sa privremenim direktorijumom.

Nisu izvrseni: puna Android release kompilacija na novom source-u, aktuelni EAS build, novi submit, fizički device acceptance, kompletni transakcioni CMS testovi sa test bazom, niti nezavisni novi artifact audit Build22. Pokusaj pristupa istorijskom Build22 AAB linku nije dao upotrebljiv binary kroz dostupne alate. Nisam dobio dokaz da je svaki source fajl i svaka moguca kombinacija podataka formalno verifikovana. Takva tvrdnja ne sledi iz statickog audita.

Prioriteti P0/P1/P2 su moja tehnicka trijaza. Statusi POTVRDJENO, LOKALNO_REPRODUKOVANO i NEDOKAZANO razdvajaju dokaz od procene rizika.

## 4. Registar glavnih nalaza

| ID | Prioritet | Oblast | Status | Posledica |
| --- | --- | --- | --- | --- |
| A01 | P0 | Kotlin clearRestoreCredential | Potvrdjeno u EAS logu; lokalno reprodukovano | Postojeci source ne prolazi release Kotlin kompilaciju |
| A02 | P0 | Native CI / readiness | Potvrdjena rupa u dokazima | Prebuild i graph PASS su tretirani kao build readiness |
| A03 | P0 | Ownership lock-a | Lokalno reprodukovano za 526 i 527 | Drugi runner brise lock prvog |
| A04 | P0 release | AutoSubmit i artifact gate | Potvrdjeno u runneru | Submit nije blokiran kasnijim lokalnim auditom |
| A05 | P1 | Signature audit | Lokalno reprodukovan false-positive | Isti sertifikat i validan ZIP nisu dokaz potpisa sadrzaja |
| A06 | P1 | Restore lifecycle | Lokalno reprodukovano vise uslova | Posao posle logout-a, preskocen clear, dupli create |
| A07 | P1 | Password change / reauthentication | Potvrdjena razlika u source-u | Nema jedinstvenog cleanup-a Restore kljuca |
| A08 | P1 | Build22 i novi API | Potvrdjena ugovorna razlika | Stari zahtev za potvrdu nema obavezni version token |
| A09 | P1 | Publikacija slika | Lokalno reprodukovano | Gubitnicki poziv uklanja fajl pobednickog poziva |
| A10 | P1 | Ops checkpoint / forensic state | Potvrdjeno u 549/550 | Staged residue blokira recovery; pogresan submission summary |
| A11 | P1 pre-OTA | runtimeVersion | Nedokazana kompatibilnost | Isti runtime label obuhvata razlicite native mogucnosti |
| A12 | P1 release | 16 KB / R8 / device acceptance | Nedostaje stvaran dokaz za novi binary | Ne sme biti zbirni PASS |
| A13 | P1/P2 | Testovi novih poslovnih tokova | Potvrdjeno slabo pokrice u pregledanom testu | String assertions ne dokazuju transakcije i dozvole |
| A14 | P2 | Reproducibilnost / alati / archive | Potvrdjene konfiguracione slabosti | Alternativni latest CLI i nepinovani builder ulazi |

### A01. Kotlin greska je i dalje u kanonskom source-u

**Tvrdnja koju analiziram:** minimalni clear coroutine blok moze biti osnov za sledeci build.

**Sta je proverljivo:** Report549 prijavljuje ambiguity izmedju `suspend () -> R` i `suspend (P0) -> R`, pa suspend poziv clearCredentialState ne dobija ispravan kontekst. Report550 potvrduje da popravka nije primenjena. Minimalni test reprodukuje obe greske.

**Sta je pretpostavka ili misljenje:** da dodavanje samo strelice resava sve. Drugi probe pokazuje da `{ -> }` sa zadrzanim `return@Coroutine null` moze da proizvede `Nothing?` / reified type gresku.

**Moguci problem ili protivargument:** lokalni probe nije Android build. U konkretnom predlogu treba eksplicitna nulta arnost i odgovarajuci povratni tip, bez nepotrebnog null povratka. To jos mora potvrditi stvarni Expo/Kotlin toolchain.

**Zakljucak:** postojece pokretanje novog EAS builda je neopravdano. Predlog `{ -> ... }` sa Unit zavrsetkom prolazi samo izolovani jezicki probe. Nije primenjen u produkciji.

**Nivo sigurnosti:** visok za postojece greske; ogranicen na izolovani probe za predlog popravke.

Dokazi: Report549 32813-32841; Report550 181-198; `native-and-lock-results.json`.

### A02. Stari native audit nije release kompilacija i nije prenosiv na novi source

**Tvrdnja koju analiziram:** raniji Native PASS zatvara rizik za novi release.

**Sta je proverljivo:** workflow na `audit/android-native-497` checkout-uje taj branch i proverava hardkodovane Build22 blobove. Pokrece `ald1nNativeSnapshot497C` i `ald1nReleaseGraph497C`, a ne punu release Kotlin/Java/R8 kompilaciju. Batch527 dozvoljava da lokalni Kotlin compile bude SKIPPED kada hosting nema SDK/Javu, a nastavlja ka source checkpoint-u.

**Sta je pretpostavka ili misljenje:** da staticki pregled, Expo Doctor ili prebuild mogu da zamene compiler.

**Moguci problem ili protivargument:** Build22 je zaista bio FINISHED, sto potvrduje tadašnji binary, ali ne nov lokalni modul niti novu dependency konfiguraciju. Stari workflow ne sme ponovo da sertifikuje sebe kao dokaz za novi main.

**Zakljucak:** potreban je novi audit candidate-a sa tacnim commit SHA, stvarnim zavisnostima i release zadacima. SKIPPED native compile znaci NOT_READY, ne PASS.

**Nivo sigurnosti:** visok.

### A03. Runner koji nije vlasnik moze da ukloni tudji lock

**Tvrdnja koju analiziram:** lock u poslednjim runnerima garantuje samo jedno pokretanje.

**Sta je proverljivo:** Batch526 postavlja EXIT cleanup koji bezuslovno poziva rmdir pre pokusaja mkdir lock-a. Batch527 ima istu gresku sa rm -rf. Kada drugi proces ne dobije lock i izadje, cleanup uklanja postojeći lock. Oba minimalna scenarija vracaju rc=73 uz `first_runner_lock_survived=false`.

**Sta je pretpostavka ili misljenje:** da ce ovo uvek napraviti dupli build. To nije dokazano; remote version provera cesto moze dodatno da zaustavi drugi poziv.

**Moguci problem ili protivargument:** provera versionCode-a i build liste je read-then-act, a ne atomarna rezervacija. Postoji prozor u kome dva nezavisna runnera mogu proci citanje pre nepovratnog zahteva.

**Zakljucak:** cleanup mora zahtevati dokaz vlasnistva. Potrebni su zajednicki release lock, owner token/FD locking i trajan zapis dispatch pokusaja pre EAS poziva. Nepoznat ishod nikada ne sme automatski izazvati novi build.

**Nivo sigurnosti:** visok za lock bug; nema tvrdnje da se dupli build vec dogodio.

### A04. AutoSubmit se desava pre lokalnog bezbednosnog prihvatanja artefakta

**Tvrdnja koju analiziram:** post-build artifact audit moze da spreci neproveren production submit u sadasnjem runneru.

**Sta je proverljivo:** AutoSubmit se zakazuje pri EAS build pozivu. Runner ceka submission i tek zatim preuzima i proverava binary. Moze zavrsiti exit 0 sa `PASS_BUILD24_PRODUCTION_AUTOSUBMIT_ARTIFACT_AUDIT_PENDING`.

**Sta je pretpostavka ili misljenje:** da je PENDING u nazivu dovoljno za bezbedan release. To je samo oznaka, a ne prepreka vec zakazanom slanju.

**Moguci problem ili protivargument:** EAS submit FINISHED ne dokazuje da je Play release odmah javno dostupan; review i managed publishing su zasebni. Ipak, lokalni audit izveden posle submit-a vise ne kontrolise taj submit.

**Zakljucak:** zadrzati automatizaciju, ali redosled mora biti candidate/build -> stvarni artifact audit -> exact-build-ID submit. Druga mogucnost je provereni cloud gate koji zaista prethodi uspesnom zavrsetku build job-a. Nijedna promena production release moda nije izvrsena u ovom auditu.

**Nivo sigurnosti:** visok.

### A05. Sertifikat iz META-INF nije verifikacija digitalnog potpisa

**Tvrdnja koju analiziram:** ZIP integrity i ocekivani fingerprint dokazuju potpis AAB sadrzaja.

**Sta je proverljivo:** runner izvlaci PKCS7 cert, uzima prvi X.509 cert i poredi fingerprint. U testu sam promenio payload potpisanog JAR-a i ponovo ga zapakovao. ZIP CRC je ostao validan, sertifikat isti, ali jarsigner je prijavio digest error i rc=1.

**Sta je pretpostavka ili misljenje:** da bi Google Play prihvatio takav paket. To NIJE tvrdnja audita; Google ima sopstvenu proveru. Neispravna je nasa lokalna logika prihvatanja.

**Moguci problem ili protivargument:** i jarsigner rezultat zahteva pravilno tumacenje: unsigned entries, potpis, ocekivani signer, istek/lanac poverenja i poznati self-signed upload cert nisu ista stvar. Ne treba slepo ignorisati ni sva upozorenja ni automatski odbaciti svaki self-signed Android cert.

**Zakljucak:** potrebno je kriptografski verifikovati payload i potpis, zatim proveriti identitet stvarnog potpisnika. Upload cert i Play App Signing cert moraju ostati jasno razdvojeni.

**Nivo sigurnosti:** visok; `signature-check-results.json`.

### A06. Restore lifecycle ima ponovljive trke i nepokrivene storage greske

**Tvrdnja koju analiziram:** Restore je best-effort dodatak koji ne moze da poremeti logout/401/normalnu prijavu.

**Sta je proverljivo:** stvarni pregledani TS source poziva SecureStore get i delete izvan odgovarajucih try blokova. Dva paralelna ensure poziva oba ulaze u native create. Poziv clear ne invalidira posao registracije koji je vec pokrenut. U auth-provider-u 401 prvo ceka clearRestoreCredential, pa tek zatim clearSession.

**Sta je pretpostavka ili misljenje:** da je dokazana zloupotreba naloga. Nije. API i platforma su simulirani. Poseban test sa odbijenom server registracijom i dalje pokazuje mogucnost ponovo napravljenog platform kljuca, ali ne i uspesnu neautorizovanu prijavu.

**Moguci problem ili protivargument:** normalni server 401 moze odbiti zakasnelu registraciju. To ne resava izostanak cleanup-a platform kljuca, neuhvaceni Promise rejection ili nedostupnost lokalne sesije zbog storage greske.

**Zakljucak:** potrebni su session generation/account binding, single-flight, kontrolisano ponistavanje zakasnelih rezultata, nezavisni/finally cleanup koraci i vremenski ogranicen best-effort native poziv. Lokalne token/UI reference moraju biti invalidirane i kada platforma ne uspe da obrise kljuc. Retry/telemetrija ne smeju otkrivati tajne.

**Nivo sigurnosti:** visok za reprodukovanu kontrolu toka; stvarna ucestalost i device-specific posledice nisu izmerene.

### A07. Promena lozinke koristi cleanup koji ne brise Restore kljuc

**Tvrdnja koju analiziram:** promena lozinke zaista zahteva novu prijavu kao sto UI i API saopstavaju.

**Sta je proverljivo:** account/security.tsx posle password promene poziva requireReauthentication. Taj helper u auth-provider-u ne poziva clearRestoreCredential. Backend AccountController menja password_changed_at i brise API tokene, ali u toj metodi ne opoziva RestoreCredential zapise. Restore verify se zasniva na sacuvanom kljucu i aktivnom korisniku.

**Sta je pretpostavka ili misljenje:** tacan trenutak u kome GMS ponovo nudi restore assertion posle te promene. Nisam testirao realan uredjaj/restore sa ovim novim binary-jem.

**Moguci problem ili protivargument:** regularni user-managed passkey ne mora biti opozvan promenom lozinke. Medjutim, ovde je sistem-managed Restore credential, a aplikacija eksplicitno obecava reauthentication. Politika mora biti definisana i dosledna, ne preuzeta bez razlike iz regularnih passkeys pravila.

**Zakljucak:** objediniti logout, HTTP401, password change i session revoke. Testirati lokalno ciscenje i backend epoch/revocation politiku, ukljucujuci situaciju kada uredjaj ne dobije 401 pre ponovnog pokretanja aplikacije.

**Nivo sigurnosti:** visok za nepovezane cleanup puteve; srednji za kompletan device scenario do E2E potvrde.

### A08. Build22 i novi backend nemaju isti ugovor za potvrdu porudzbine

**Tvrdnja koju analiziram:** backend promene mogu se smatrati bezbednim za poslednji objavljeni klijent bez cross-version testiranja.

**Sta je proverljivo:** Build22 orders-admin-api.ts salje status i note, bez order_version_token. Novi OrderWorkflowService pri prelasku u confirmed bezuslovno trazi assertFresh. OrderVersionService odbija prazan token. Build22 takodje sadrzi PATCH payment-status poziv koji vise nije u pregledanom novom admin route bloku.

**Sta je pretpostavka ili misljenje:** da svaki trenutno instalirani Build22 koristi bas originalni JS bundle. Aktivni OTA nije nezavisno inventarisan u ovom auditu; moze promeniti klijentski sloj bez promene binary versionCode-a.

**Moguci problem ili protivargument:** uklanjanje manuelnog payment status-a je namerna finansijska zastita. Resenje NIJE vracanje zaobilaznog puta mimo ledger-a ili iskljucivanje version kontrole porudzbine.

**Zakljucak:** napraviti matricu Build22 binary + stvarni aktivni OTA + novi backend. Predvideti eksplicitnu migracionu/upgrade poruku i bezbednu kompatibilnost dok novi klijent nije uspesno distribuiran. Zaustaviti nejasne 404/409 greske kao jedini UX prelaska.

**Nivo sigurnosti:** visok za razliku API ugovora; srednji za stvarni obuhvat korisnika bez OTA/device inventara.

### A09. Konkurentna publikacija slike moze obrisati uspesno objavljen fajl

**Tvrdnja koju analiziram:** DB lock u ProductImagePublicationService dovoljno stiti objavljivanje.

**Sta je proverljivo:** kopiranje/rename na deterministicku zajednicku putanju izvrsava se pre DB row lock-a. Dva poziva mogu oba procitati legacy. Prvi zatim prebaci DB na public. Drugi otkrije public i baci exception, ali njegov catch sa switched=false uklanja zajednicki target.

**Sta je pretpostavka ili misljenje:** da se to vec dogodilo u produkciji ili objasnjava sve ranije nedostajuce slike. Takav incident nije dokazan.

**Moguci problem ili protivargument:** mozda trenutni caller serijalizuje normalni batch. Sam servis nije konkurentno bezbedan i buduci poziv ili paralelni proces lako menja tu pretpostavku. Legacy original u testu ostaje sacuvan; gubi se javna kopija na koju DB sada pokazuje.

**Zakljucak:** zakljucati/vlasnicki vezati ceo publication postupak ili koristiti immutable content-addressed cilj, zatim atomarni DB switch. Cleanup sme obrisati samo fajl koji taj poziv poseduje. Potreban je regresioni test za dva konkurentna poziva.

**Nivo sigurnosti:** visok za reprodukovani interleaving; `image-publication-results.json`.

### A10. Dokumentacioni checkpoint proizvodi stanje koje sledeci recovery odbija

**Tvrdnja koju analiziram:** operacioni izvestaji pouzdano predstavljaju kanonsko stanje i mogu bezbedno da se arhiviraju.

**Sta je proverljivo:** Report549 sadrzi raw EAS spinner output i trailing whitespace. Checkpoint primenjuje git diff --cached --check na taj sirovi dokaz i staje, ostavljajuci staged latest i Report549. Report550 zatim staje na tom poznatom staged residue-u. Summary549 kaze SUBMISSION_ID=NONE iako raniji log belezi scheduler ID; Report550 ga razresava kao CANCELED.

**Sta je pretpostavka ili misljenje:** da treba izbrisati ili preformatirati stare reportove. To bi narusilo dokazne hashove i append-only pravilo.

**Moguci problem ili protivargument:** brojni istorijski untracked dokumenti nisu isto sto i proizvoljan source drift. Mora se odvojiti klasifikovan incident residue od neocekivane mutacije, bez blanket allowlista ili git reset/clean.

**Zakljucak:** raw log van source worktree-a, zatvoren jednom i hashovan; sazet strukturiran report odvojeno; source whitespace check samo na source/promenisanim uredjenim dokumentima; seal-before-stage; checkpoint izlaz u zaseban trag. Pre recovery-ja sacuvati tacan index i samo ciljano razresiti dokazano poznate putanje. Ispraviti i brojac cmd logova u Batch527: povecanje u command substitution subshell-u ne ostaje u roditelju.

**Nivo sigurnosti:** visok za dokumentovane greske.

### A11. Isti runtime label nije dokaz native kompatibilnosti

**Tvrdnja koju analiziram:** runtimeVersion 1.0.0-build17 moze ostati zajednicki bez dodatnog dokaza.

**Sta je proverljivo:** isti string se koristi i uz novi lokalni native modul i promenjene native dependency patch verzije. OptionalNativeModule wrapper jeste bezbedniji od obaveznog require-a i sprecava jednu klasu gresaka kada modul nedostaje.

**Sta je pretpostavka ili misljenje:** da stari binary zato sigurno pada ili da je potpuno kompatibilan. Nijedno nije dokazano.

**Moguci problem ili protivargument:** optional guard moze da omoguci namernu kompatibilnost pojedine opcije. Ne dokazuje kompatibilnost svih novih JS/native biblioteka pod istim runtime-om.

**Zakljucak:** pre bilo kakvog sledeceg production OTA-a potrebna je eksplicitna kompatibilnosna matrica ili nova runtime granica/fingerprint politika. Runtime se ne menja automatski u ovom auditu.

**Nivo sigurnosti:** visok za nedostatak zasebne runtime granice; srednji za stvarnu kompatibilnost dok se ne testira.

### A12-A14. Ostale rupe u prihvatanju i ponovljivosti

**Tvrdnja koju analiziram:** svi ostali PASS markeri zajedno uklanjaju relevantne release rizike.

**Sta je proverljivo:** nema novog AAB-a za 23/24, pa nije proverena njegova stvarna ABI/16KB kompatibilnost. Pregledani OrderAmendmentTest prvenstveno proverava rezoluciju servisa i source stringove, a ne konkurentne transakcije. package.json sadrzi build skripte sa eas-cli@latest iako kanonski put koristi 24.8.0. Builder image nije eksplicitno pinovan u pregledanom eas.json. .easignore nema eksplicitnu native-directory politiku. Batch526 ne izvrsava sve sveze quality komande koje je prateci prethodni opis obecavao.

**Sta je pretpostavka ili misljenje:** da svaka od ovih stavki mora izazvati sledeci pad. Ne mora. Zajedno, medjutim, ne opravdavaju tvrdnju o potpunoj release spremnosti.

**Moguci problem ili protivargument:** lockfile stabilizuje npm zavisnosti, ali ne sve ulaze buildera, generisani native projekat, lokalne konfiguracione fajlove ili svaki tranzitivni Maven izbor. Dependency graph success ne izvrsi R8, JNI ni runtime.

**Zakljucak:** ukinuti alternativne nepinovane putanje za autorizovane release-e, proveriti tacan upload archive, proveriti efektivan native graph, dodati behavioral testove i zadrzati svaki kriticni SKIPPED kao BLOCKED.

**Nivo sigurnosti:** visok za konfiguracione i dokazne rupe; srednji za njihove konkretne buduce posledice.

## 5. R8, Gradle, AGP i 16 KB: precizna procena

| Stavka | Nadjeno | Ispravna interpretacija |
| --- | --- | --- |
| R8 minify | true u konfiguraciji i istorijskom CI snapshot-u | Dobar source/config uslov, ne dokaz izvrsenog R8 za novi candidate |
| Resource shrinking | true | Potrebna provera resursa u optimizovanoj aplikaciji |
| Default ProGuard | proguard-android-optimize.txt | Namerno primenjen optimizovani default; ne vracati legacy bez dokaza |
| Optimized resource shrinking | android.r8.optimizedResourceShrinking=true | Odgovarajuci opt-in za AGP 8.12; stari experimental warning nije sam uzrok pada |
| R8 full mode | property UNSET u starom auditu | Odsustvo false nije isto sto i ugasen full mode; proveriti efektivno stanje |
| AGP | 8.12.0 u ranijem stvarnom audit snapshot-u | Ne nadogradjivati proizvoljno na AGP9 radi resavanja Kotlin koda |
| Gradle | 9.3.1 u pregledanim native evidencijama | AGP dokumentovani minimum 8.13 nije dokaz da je 9.3.1 sam po sebi nekompatibilan |
| JDK | 17 u istorijskom CI auditu | Zahtev/profil treba vezati za sledeci CI/EAS builder, ne za lokalni PATH |
| compileSdk / targetSdk | 36 / 36 | Sacuvati i potvrditi u merged manifest-u/binary-ju |
| minSdk | 24 u istorijskom snapshot-u | Restore zahteva API28+, pa fallback ispod toga mora ostati validan |
| Kotlin / KSP | 2.1.20 / 2.1.20-2.0.1 u Build24 logu | Novi probe mora koristiti stvarno ove/zakljucane dependency verzije |
| NDK | 27.1.12297006 u Build24 logu | Sama verzija ne dokazuje 16KB; potreban pregled link flags i svih prebuilt biblioteka |
| Material / Fresco | 1.13.0 / 3.6.0 u starom graph auditu | Istorijski graph rezultat; treba ponoviti za novi candidate |
| arm64 / x86_64 ELF | Nema novog binary-ja | Nije testirano na 23/24, nema PASS |
| ZIP 16KB poravnanje | Nema generisanih APK-ova za novi candidate | PAGE_ALIGNMENT_16K sam ne zamenjuje APK zipalign proveru |
| Uredjaj/emulator 16KB | Nema novog acceptance dokaza | Ne sme se izvesti iz linker parametra |

Zvanicna Android dokumentacija potvrduje kombinaciju minify, shrinkResources i optimizovanog ProGuard default-a. Optimized resource shrinking ima poseban opt-in na AGP8.12/8.13. To podrzava postojeci pristup, ali ne sertifikuje njegov konkretni rezultat. Za native biblioteke treba zasebno proveriti ELF segmente, ZIP pakovanje i rad u 16KB okruzenju. Ne menjati NDK, AGP, Gradle i Expo istovremeno na osnovu jednog upozorenja.

Za R8 treba zadrzati mapping i relevantne native simbole, pregledati consumer rules i stvarne missing-class/missing-rule rezultate. Blanket `-keep class ** { *; }` ili globalni `-dontwarn` nije prihvatljiv nacin da se dobije zeleni build. Posebno su vazni Expo Modules/JNI, Nitro, Google sign-in, Credential Manager, Reanimated/Worklets i klase koje se ucitavaju refleksijom.

## 6. Nove funkcije i obavezno regresiono pokrice

| Oblast | Sta pregled podrzava | Sta ostaje za dokaz |
| --- | --- | --- |
| SDK57 patch alignment | Zakljucane verzije u source-u; istorijski Expo/Doctor PASS | Svezi clean install i actual release dependency resolution |
| Adaptive UI / edge-to-edge | Uklonjen portrait lock; SafeArea levo/desno; katalog menja FlatList key sa brojem kolona | Rotacija, landscape sa tastaturom, tablet, velik font, tastatura i navigation rail |
| Upload dokaza placanja | Novi tok koristi pravi Expo File/multipart transport | Stvarni content URI, Drive provider, PDF/JPEG/WebP, MIME bez ekstenzije, 10MB granica, timeout i 401 |
| Shipment / delivery akcije | Odvojene poslovne akcije i verzionisanje potvrde | Stari Build22 zahtev, stale token, dvostruki submit, vec poslata porudzbina, COD |
| Customer amendment | Order/product lock, sortirano zakljucavanje proizvoda, snapshot cena, delta lager i post-commit obavestenja postoje | Dva kupca/procesa, poslednji komad, amendment naspram slanja/placanja, isti idempotency key |
| Finansijsko stanje | Verified payment/refund ledger i centralna projekcija postoje | Partial/paid/overpaid/refunded/cancelled, preplata posle smanjenja porudzbine, dupli verify/refund |
| Dokumenta | Snapshot pristup i invalidacija po izmeni postoje u pregledanim putanjama | Stari dokument ne menja istorijski sadrzaj; novi snapshot/revizija, prava pristupa PDF-u |
| Slike | Public publication i derivati | Potvrdjena konkurentna cleanup greska; validan public GET/thumbnail/display i fallback |
| Tracking Push/E-mail/Both | Evidentiran dodatni kanal i notification preference izmena | Sve tri kombinacije, bez duplikata; samo odgovarajuci primalac; oznacavanje slanja nije dato kupcu |
| Restore create / get | Pravi Android Restore tip i Laravel WebAuthn tok | R8 release pozivi, cloud E2EE fallback, GMS, realno vracen assertion i server verifikacija |
| Restore logout / 401 / password | Postoje odvojeni cleanup putevi | Potvrdjene race/exception rupe i nedostajuci reauthentication clear |
| DAL | Tacan Play fingerprint i dedicated metadata plugin u evidenciji | Potpis Play instalacije, domain/RP/origin, HTTPS bez preusmeravanja; pozitivan i negativan test |
| SecureStore backup | ConfigureAndroidBackup=true je predvidjen i ne treba proizvoljno gasiti backup | Merged backup XML mora iskljuciti SecureStore za cloud i device transfer |
| OTA | Optional native module umanjuje jedan rizik | Matrica starih binary-ja i aktivnih JS update-a; runtime izolacija/kompatibilnost |

Za backend Restore dodatno treba testirati istekao/pogresan/replay challenge, pogresan origin/RP, neaktivan korisnik, nalog A/nalog B, opozvana sesija i rate limit. Cache::pull se ne sme automatski smatrati atomarnim consume-once mehanizmom: pregledani Laravel Repository koristi get pa forget. To je zahtev za concurrency test i zakljucavanje/atomarni consume, ne tvrdnja da je u produkciji vec izveden uspesan replay.

Restore-only userVerification=discouraged je u skladu sa Android GetRestoreCredentialOption ponasanjem; ne treba ga menjati na required samo da bi licio na interaktivni passkey login. E2eeUnavailableException fallback sa cloud=true na false i zasebna restore tabela su dobri smerovi. Ne treba zameniti laravel/passkeys/web-auth kriptografsku proveru prostom proverom JSON polja.

## 7. Dokazi iz lokalnih reprodukcija

| Scenario | Rezultat | Granica dokaza |
| --- | --- | --- |
| K1 Originalna coroutine bez eksplicitne arnosti | Compiler rc=1, overload + suspend greska | Minimalni Kotlin stub, ne ceo Android |
| K2 Eksplicitna arnost i Unit | Compiler rc=0 | Samo izolovani predlog |
| K3 Eksplicitna arnost, ali null ostaje | Compiler rc=1, Nothing? reified | Dokazuje da strelica sama nije dovoljan fixture fix |
| L1 Batch526 drugi runner | rc=73, prvi lock uklonjen | Reprodukcija tacne cleanup/mkdir logike |
| L2 Batch527 drugi runner | rc=73, prvi lock uklonjen | Ista ogranicena reprodukcija |
| R1 Registracija zavrsi posle clear-a | Ponovo postoje platform key i synced flag uz simuliran uspesan API | Nije dokaz stvarne neautorizovane prijave |
| R2 SecureStore delete odbijen | Native clear nije pozvan | Stvarni TS uz simuliran storage error |
| R3 SecureStore get odbijen | ensure odbija Promise mimo catch-a | Stvarni TS |
| R4 Dva paralelna ensure poziva | Dva create i dva register poziva | Simulirana native/API brzina |
| R5 Platform create uspe, register mreza padne | Platform key bez synced flag-a | Ne tvrdi automatski oporavak; potreban retry protokol |
| R6 Kasna registracija, server odbije opozvan token | Platform key opet postoji, server register nije prihvacen | Orphan key scenario, ne auth bypass dokaz |
| R7 401 handler sa neuspesnim clear-om | clearSession korak nije dostignut | Tacan sequential-await redosled; mocked cleanup |
| S1 Izmenjen signed JAR payload | ZIP i cert isti/validni, jarsigner rc=1 | Sinteticki cert, ne produkcioni AAB |
| I1 Dva publication poziva | Prvi uspe, drugi obrise target, DB ostane public | Tacan PHP servis, stvaran FS, DB adapter simuliran |

Rezultati se nalaze u `native-and-lock-results.json`, `restore-lifecycle-results.json`, `signature-check-results.json` i `image-publication-results.json`. Zavrsna ponovljena provera svih 14 ocekivanih ishoda evidentirana je u `verification-summary.json`. Oznaka reproduced=true znaci da je problem reprodukovan, a ne da je aplikacija ispravna.

## 8. Obavezni redosled zatvaranja

### Gate 0: bezbedno poravnanje dokaza i index-a

Prvo sacuvati neizmenjene Report549/550, index snapshot i pre/post hashove. Razresavati samo tacno poznate staged docs putanje. Ne koristiti blanket clean/reset/stash niti preskakati proizvoljan drift. Odvojiti raw log i strukturirani izvestaj. Ispravno zapisati Build24 scheduler kao CANCELED, ne NONE.

### Gate 1: source popravke i regresioni testovi

Kotlin clear overload i povratni tip, Restore lifecycle/reauthentication, lock ownership i audit signature redosled su minimalne kriticne popravke. Image publication i Build22/backend prelaz moraju dobiti odvojene behavioral testove i bezbednu ispravku/plan. Promena production stanja zahteva svoj kontrolisan batch; ovaj dokument nista od toga nije primenio.

### Gate 2: tacan native candidate u izolovanom CI okruzenju

Checkout tacnog commit SHA, zakljucane zavisnosti, bez production .env/baze/Play kljuceva. Koristiti stvarni Android/JDK/Gradle/AGP/Kotlin/NDK toolchain i produkcione feature flags. Ephemeral native generisanje mora biti idempotentno i povezano sa tacnim source-om. Proveriti autolinking lokalnog modula, resolved Maven graph i merged manifest/resurse.

Pokriti stvarni release graph, ukljucujuci kompilaciju lokalnog modula, release Java/Kotlin, lint, R8 i bundleRelease sa audit signing konfiguracijom. Ne oslanjati se na imena taskova napamet; zadaci moraju biti dokazani u efektivnom Gradle graph-u. R8 mora stvarno biti ukljucen. Audit build nije EAS production build i ne sme povecati remote versionCode ili ista submitovati.

### Gate 3: artifact i device provere

Kriptografski potpis + signer identitet, package/version/runtime/channel, bundletool validacija, svi relevantni ABI moduli, ELF alignment, generisani split/universal APK i ZIP alignment. Testirati optimizovani runtime, ne samo debug. Pravi Android Restore E2E zahteva odgovarajuci GMS, backup scenario i potpis koji odgovara DAL-u. Debug/audit potpis nije Play App Signing cert; ti testovi nisu medjusobno zamenljivi.

### Gate 4: kontrolisana production autorizacija i automatizacija

Tek posle zatvorenih pre-production gate-ova moze se razmatrati zasebna autorizacija jednog EAS production builda. Ni tada se ne tvrdi da su cloud infrastruktura, mreza i Play review matematicki garantovani. Submit mora zavisiti od prihvatanja TACNOG nastalog artefakta, ne od starog source PASS-a ili najnovijeg builda iz liste. Fizicki Play-distributed acceptance i eventualni test-track pre production-a moraju imati jasno dogovoren obim; ovaj audit ne menja track niti zakazuje release.

### Gate 5: trajna evidencija

Zasebno belezenje source SHA, tree SHA, builder verzija, stvarnog build ID, stvarnog submission ID, AAB hash-a, rezultata signature/16KB/runtime provera i poslednjeg remote versionCode-a. Report se zatvara pre staginga; ne upisuje svoj checkpoint output u vec stage-ovanu kopiju.

## 9. Masinski citljiv rezime odluke

```text
AUDIT_MODE=READ_ONLY_WITH_ISOLATED_LOCAL_TESTS
CANONICAL_MAIN_AT_AUDIT=b5b942e645d28d3ed5f248eaae4180ebe9a54ee6
CANONICAL_SOURCE=7066a7aee04a7bdb2c37e862905f74306737e78b
REPORT550_RESULT=FAIL_BEFORE_SOURCE_PATCH
KOTLIN_FIX_APPLIED_TO_MAIN=NO
LAST_OBSERVED_EAS_ANDROID_VERSION_CODE=24
BUILD24_STATUS=ERRORED
BUILD24_AUTOSUBMIT_STATUS=CANCELED
BUILD24_AAB=NONE
LOCAL_REPRODUCTION_SCENARIOS=14
FULL_CURRENT_ANDROID_RELEASE_COMPILE=NOT_EXECUTED
NEW_PRODUCTION_BUILD=NO
NEW_SUBMIT=NO
NEW_OTA=NO
PRODUCTION_DATABASE_WRITES=NO
PRODUCTION_SOURCE_WRITES=NO
READY_FOR_NEXT_PRODUCTION_BUILD=NO
NEXT_PRODUCTION_BUILD_AUTHORIZED=NO
```

## 10. Izvori i proverljivost

### Projektni izvori

Svi source navodi, osim eksplicitnog Build22 poredenja i istorijskog audit branch-a, vezani su za `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6` u `AldinAga/ald1n-project`.

Pregledane kljucne putanje: AGENTS.md; docs/operations/000-LATEST.md; istorijski 497 native audit i 502 acceptance; reports 543/548/549/550; app.config.js; eas.json; package.json i dependency razlika; plugins/with-android-native-modernization.js; plugins/with-restore-credential-association.js; lokalni Restore Gradle/Kotlin/TS modul; src/features/auth/restore-credentials.ts; auth-provider.tsx; src/lib/api/client.ts; account/security.tsx; Build22 orders-admin-api.ts; novi Admin/OrderMutationController, OrderVersionService i OrderWorkflowService; RestoreCredentialController; ApiTokenIssuerService; AuthTokenController; AccountController; AppServiceProvider; User; OrderAmendmentService; OrderAmendmentTest; OrderFinancialStateService; ProductImagePublicationService; admin routes; .easignore i .gitignore. Batch526 i Batch527 runneri pregledani su iz dostavljenih fajlova.

Istorijski workflow: `.github/workflows/android-native-497-audit.yml` na `audit/android-native-497`. On nije novi audit candidate-a i njegov PASS se ne nasledjuje posle native izmena.

### Spoljna dokumentacija, proverena tokom audita

- Android R8: `https://developer.android.com/topic/performance/app-optimization/enable-app-optimization`
- Android AGP8.12: `https://developer.android.com/build/releases/agp-8-12-0-release-notes`
- Android 16KB: `https://developer.android.com/guide/practices/page-sizes`
- Android Restore: `https://developer.android.com/identity/sign-in/restore-credentials-implementation`
- Android GetRestoreCredentialOption: `https://developer.android.com/reference/kotlin/androidx/credentials/GetRestoreCredentialOption`
- Expo runtime: `https://docs.expo.dev/eas-update/runtime-versions/`
- Expo AutoSubmit: `https://docs.expo.dev/build/automate-submissions/`
- Expo infrastructure: `https://docs.expo.dev/build-reference/infrastructure/`
- Expo SecureStore: `https://docs.expo.dev/versions/latest/sdk/securestore/`
- Expo Modules API: `https://docs.expo.dev/modules/module-api/`
- Oracle jarsigner: `https://docs.oracle.com/en/java/javase/21/docs/specs/man/jarsigner.html`
- Laravel passkeys-server VerifyPasskey v0.2.1 i Laravel Cache Repository 13.x, pregledani kao primarni source.

Ovaj izvestaj nije univerzalna bezbednosna sertifikacija. Visoka sigurnost odnosi se na navedene dokaze i reprodukcije. Preostali E2E, CI i production acceptance dokazi ostaju otvoreni i eksplicitno blokiraju odgovarajuce sledece korake.

# B1074 — nezavisna analiza 98 regresija i verifikacija namenske retirement CI baze

**Datum:** 2026-11-03. **Konačni status:** `PASS_SCOPED_RETIREMENT_18_OF_18_AND_DIAGNOSTICS__HOLD_FULL_PHPUNIT_80`. **Okruženje:** GitHub Actions, PHP 8.4 / PHPUnit 12.5.31, dva odvojena jednokratna MariaDB 10.11.19 servera. Bez CloudLinux/prod/OTA/EAS/deploy operacija.

## 1. Evidencija i izvor

- Izvorna grana: Draft PR #93, tačan HEAD `acb8b9c42ec9254b1a27567abca29a26c1f733f8`, aplikacioni izvor **nije menjan** u ovom batch-u.
- Referentni B1073 full-suite CI: [run #41657873121](https://github.com/AldinAga/ald1n-project/actions/runs/41657873121). Original 553 testova / 4.285? Ne: PRETHODNI B1073 original imao je **553 / 4.127 tvrdnji / 10 grešaka / 89 failures / 99 identiteta**. Prethodni B1073 patched **564 / 4.412 tvrdnji / 9 grešaka / 89 failures / 98 identiteta**. Prethodno je 18 retirement testova završavalo na identitetskom DB guardu pre poslovnih provera.
- B1074 stvarni novi CI: [run #41718864135](https://github.com/AldinAga/ald1n-project/actions/runs/41718864135), workflow commit `54db3489cf25cbededc3d17702b2372fb9564997`.
- Izolovani workflow `B1074-PR93-CI-RETIREMENT-DB-FULL-REGRESSION.yml`, SHA-256 `58347975f1eeafc5a8fd905823e7dde7739f1831ca9e0c41039c5abbc2221d9b`, Git blob `f212f2f5d6e8b9e99f91e27415f10a90b92a58b4`.
- Originalni B1071 i B1073 *unapplied* patch-evi imaju identičan sadržaj i proverene SHA-256 pre testiranja. Nisu menjani GitHub app source, PR #93, nijedan B805/B809/B815 istorijski test, ni CloudLinux checkout.
- Arhiva sirovog B1074 JUnit XML/logova/comparison.json SHA-256 `fc430d94cfbfca488e4cf668e6ad3ba1bfe9262e3a5607533cea3e45853e42fd` (link iz Google Drive arhive naveden u kanonskoj evidenciji). Artefakt je nezavisno raspakovan i ponovo izračunat.

## 2. Dokazan osnovni uzrok: 18 lažnih CI neuspeha zbog identiteta šeme

`UserRetirementMySqlIntegrationTest::setUp()` iz originalnog PR #93 zahteva **isključivo** `DB::getDatabaseName() === 'ald1n_retirement_ci'`. Prethodni workflow pokretao je obe MariaDB instance i PHPUnit sa `DB_DATABASE=ald1n_test`. Time su **svih 18** testova padali na identitetskom sigurnosnom guardu pre testiranja poslovnog ponašanja.

B1074 je ispravio samo konfiguraciju dve **jednokratne CI baze** i posebnu fail-closed proveru `SELECT DATABASE()`. Istorijski testovi i njihova zaštita ostali su netaknuti. Lokalni CI ugovor RED na starom workflow-u / GREEN na novom, lokalni YAML i ugrađeni Python parser sintaksno ispravni. Zatim na stvarnom CI:

- Dve fizički odvojene MariaDB 10.11.19 konekcije (port 3306 i 3307) identifikovane sa `SELECT DATABASE() = ald1n_retirement_ci`.
- Originalni **retirement integracioni suite 18/18 PASS, 176 tvrdnji, exit 0**, sa punim stvarnim MariaDB ponašanjem.
- Oba puna PHPUnit skupa uključila su svih 18 testova sa novim identitetom baze, bez `--filter`, `--exclude-group` ili isključivanja testova.
- U odnosu na B1073 sada ima **tačno 18 manje neuspešnih identiteta u patched full suite-u** (98 → 80). Nijedna nova regresija u odnosu na stari B1073.

**Zaključak za ovaj uzrok:** dokazan CI konfiguracioni propust, ispravljen u novom izolovanom workflow-u. To nije promena poslovne logike aplikacije niti potvrda spremnosti za produkciju. **Sigurnost: visoka.**

## 3. Stvarni puni PHPUnit rezultati

| Metrika | Originalni PR #93 | PR #93 + B1071 + B1073 |
|---|---:|---:|
| Izvršeni testovi | 553 | 564 |
| Tvrdnje | 4.285 | 4.570 |
| Greške (errors) | 10 | 9 |
| Neuspešne tvrdnje/testovi (failures) | 71 | 71 |
| Ukupno neuspešnih identiteta | 81 | **80** |
| Preskočeni testovi | 0 | 0 |
| PHPUnit exit code | 2 | 2 |

Dodatno: **B1073 9 HTTP/MariaDB no-write Feature testova / 261 tvrdnja PASS**, B1071 **2 Unit testa / 15 tvrdnji PASS**, retirement **18 testova / 176 tvrdnji PASS**. Ti se testovi ponovo pojavljuju u punom skupu, zato njihove brojeve ne treba sabirati sa 564.

Poređenje kompletnog originalnog i patched JUnit identiteta u ovom istom B1074 CI prolazu: **0 novih**, **1 manje** (`OrderDetailPresenterTest::test_presenter_survives_zero_dates_and_uses_only_scalar_view_data`). Taj poslednji test je nepovezan sa B1074 konfiguracijom, a nije menjan ni njegov produkcioni kod. **Ne proglašavati ga trajno ispravljenim** bez reproducibilnog uzroka.

**Istorijski B803**: svih **56/56** identiteta iz nepovredivog spiska je zapravo izvršeno na obe strane, **0 preskočeno**, i svih **56/56 i dalje padaju** u patched full suite-u. Tih 56 čini *podskup* ukupno 80; nikada ih ne sabirati kao odvojenu kategoriju niti proglašavati waived.

**Global CI status:** FAILURE po namernom `HOLD_HISTORICAL_FULL_SUITE_FAILURES` završnom gate-u; ne predstavljati kao zelen suite. **Pokrivenost bez neuspešnog identiteta:** 484/564 = **85,8%** izvršenih test slučajeva; ovo nije metrika produkcionog kvaliteta niti procenat prihvaćenog projekta.

## 4. Preostalih 80 — ponovljiva klasifikacija simptoma, ne pretpostavljenih uzroka

| JUnit simptom | Neuspešni identiteti | Evidence / sledeća akcija |
|---|---:|---|
| HTTP 200 očekivano, HTTP 302/403/404 stvarno | **52** | Od toga 40×302, 9×403, 3×404. Analizirati važeći *registrovani i verifikovani kupac* policy, test fixture auth/session i OFF boundary. Ne uklanjati auth da bi legacy guest test postao zelen. |
| Druge neuspešne tvrdnje i source-contract asercije | **19** | Pregledati stvarne API/UI/price/commission uslove, ne zadovoljavati ih isključivo dodavanjem neupotrebljenih stringova. |
| Druge izvršne greške | **9** | `DirectSaleService` nedostajuća dva pristupa (3 metode/reflection slučaja), `PublicOrderAccessService` 3 nevažeća toka, `SettingsService::put` 1, `DeliveryNoteMigration` app bootstrap 1, `LegacyReadOnlyGuard` PHPUnit data provider 1. Za sve utvrditi istinski funkcionalni zahtev pre patch-a. |
| Neusklađen DB naziv retirement testova | **0** | Zatvoreno B1074 CI konfiguracijom; prethodno 18. |
| **Ukupno** | **80** | Bez testnih izuzeća. |

Ove klasifikacije su načinjene automatizovanim čitanjem **svih 564 stvarnih JUnit `testcase` elemenata**, dodatno nezavisno ukrštene sa JUnit comparator JSON-om. HTTP rezultat ne dokazuje sam po sebi da je autentikaciona kontrola neispravna; to je hipoteza za istraživanje. **Sigurnost: visoka za brojanje; srednja za procenu poslovnog uzroka HTTP razlika.**

## 5. Obim, HOLD i bezbednosni uslovi

**PASS:** namenski retirement suite 18/18, tačno dve izolovane MariaDB baze, B1073 9/9 no-write Feature, B1071 2/2 Unit, identitet svih 56 B803 testova bez preskakanja, integritet izvornog koda/patch-eva, nula novih regresija i neizmenjena produkcija.

**HOLD:** puni 80 neuspešnih identiteta (56 B803 podskup), M14/M15 formalni owner UAT i prihvatanje, M13 realni enabled Chromium/Firefox i produkcioni FPM+OPcache source proof, SEC23/CSP i 368 WSTG/ASVS, kupac/merchant/legal/payment/idempotency/concurrency sigurnost, B1065 originalni QA cleanup. B1069 ostaje zabranjen. StoreFront/checkout produkcija ostaje OFF.

**Formalni prihvaćeni napredak ne menja se ovim batch-em:** program **11/17=64,7%**, CMS **10/15=66,7%**, originalni UI **6/6=100%**, M14/M15 **0/2=0%**. **Smanjenje regresionog duga:** 18/98=**18,4%** prethodno neuspešnih identiteta uklonjeno korekcijom testnog okruženja. Ne predstavlja 18,4 procentna poena završetka projekta.

## 6. Naredni bezbedan objedinjeni source-only razvoj

1. **B1075 auth/checkout politika:** dokumentovati i reprodukovati 52 statusna neslaganja kroz konkretne HTTP zahteve i proveriti istinsku razliku između starih anonimnih testova i kanonskog registered+verified buyer modela. Bez opasnog uključivanja guest checkout-a i bez izmene zaštićenih B805/B809/B815 testova.
2. **B1075 izvršne greške:** ispitati `DirectSaleService` price ceiling (4 originalna testa uključujući 3 ReflectionException), `SettingsService`, `PublicOrderAccessService` i PHPUnit data-provider inicijalizaciju. Svaki stvarni bug prvo reprodukovati RED, minimalni izvorni GREEN, pa ceo CI bez izuzeća.
3. Svaki novi patch izolovati i pinovati na PR93 exact commit, jedan objedinjeni testni run/izveštaj, računati promenu svih failing test identiteta. Bez produkcionih migracija/deploy/build/Play/OTA.

**Ovaj B1074 je CI-only napredak, a ne produkcioni deploy niti source merge.**

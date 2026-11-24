# B1158: 114 fajlova (10 novih, 104 izmijenjenih), migracije: `2026_11_24_000001_add_catalog_view_notes_permission`

Autor: Ald1n <https://ald1n.com>

Ovaj README se generiše iz `README-SR.tpl.md` komandom `php tools/storefront/b1158/generate.php <SHA>`. Ne uređuje se ručno.

Paket prebacuje produkciju sa stanja B1157 (Izvještaj 1123, 8620 fajlova, 597 ruta, `origin/main` 2ed96694) na `origin/main` 03410366 (`03410366666becb3a6f84a793b3a70fd59976473`).

## Šta se mijenja

- 114 fajlova u `apps/cms/current`: 10 novih, 104 izmijenjenih. Testovi i dokumentacija se ne instaliraju. Grupe: A10 B13 C25 D1 E1 F64.
- Fajlovi koje je main obrisao ostaju na serveru, jer ništa na serveru ne brišemo: `bin/catalog-grid-responsive-smoke.php`, `public/assets/css/ald1n-dashboard-d1.css`, `public/assets/css/ald1n-user-retirement-action.css`, `resources/views/admin/access-control/partials/workspace-style.blade.php` (broj: 4).
- Stablo posle instalacije ima 8630 fajlova (8620 + 10 novih).
- Rute: 597 postaje 597 (razlika 0). Ova brojka se u generatoru procjenjuje iz razlike deklaracija `Route::` osim ako je zadat `--routes-after`; CI je mjeri na stvarnoj aplikaciji i pada ako se razlikuje.
- Migracije: `2026_11_24_000001_add_catalog_view_notes_permission`. Izvršene posle migracije: 76 postaje 77.
- Nema izmjena `composer`, `.env` ni `config`.

## Redoslijed

1. Preflight (samo čitanje): Izvještaj 1123 i stanje B1157, APP_URL tačno `https://cms.ald1n.com`, 76 migracija izvršene i nijedna na čekanju, rezervne kopije, prozor 02:30 i nedjelja 03:10, digest svakog izmijenjenog fajla, novi fajlovi ne postoje.
2. Kopija svakog izmijenjenog fajla u privatni prostor B1158.
3. Fajlovi po grupama, sa pauzom i provjerom da sajt radi poslije svake: A novi fajlovi (neaktivni), B biblioteke, C kontroleri i bin alati, D `AppServiceProvider`, E rute, F prikazi.
4. Tek kad je svaki fajl na mjestu: svježa rezervna kopija baze (`app:backup-create` pa `app:backup-verify`), ponovna provjera te kopije, tačno nove migracije na čekanju, pa jedan poziv `artisan migrate --force --realpath --path=…`. Nikad `fresh`, `refresh`, `rollback` ni `reset`, nikad `down()`, nikad brisanje tabele.
5. Provjera poslije migracije: 77 izvršenih, 0 na čekanju, nove tabele i kolone postoje, nijedan red u tabelama obavještenja nije dodat tokom migracije (u produkciji se prijavljuje samo kao INFO).
6. Prijem: kompajliranje svih 67 prikaza, stilovi, rute, ponovno stablo od 8630 fajlova.

Ako payload nema migracija (migracije: nema), koraci 4 i 5 se preskaču: nema rezervne kopije baze ni migracije, a vraćanje je običan povratak fajlova (`PASS_ROLLED_BACK_BY_OWNER`).

## Trajanje i prozor rezervne kopije

Mjerodavna brojka je `requiredWindowSeconds` u izvještaju; ispod su vrijednosti iz pregleda B1156 (formula je u B1158 ista).

- Sa poslednjom rezervnom kopijom od oko 300 s: oko 10200 s (2 h 50 min).
- Sa podrazumijevanih 2400 s po kopiji: oko 16479 s (4 h 35 min). Alat tada odbija start (`HOLD_PREFLIGHT_BLOCKED` sa `SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_...`) i ništa nije promijenjeno.

Instalacija se blokira kad bi prozor zašao u rezervnu kopiju: svaki dan u 02:30 i nedjeljom u 03:10 (Europe/Belgrade). Zato ne počinjite kasnije od oko 23:00. Stvarno trajanje na produkciji nije izmjereno.

## Prije pokretanja provjerite sami

1. Postavite `b1158-operator.zip` i `b1158-operator.zip.sha256` u `/home/icaffeco`. Komanda mora da ispiše `b1158-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1158-operator.zip.sha256
   ```

2. Tačno jedan izvještaj `1123-B1157-INSTALLER-*.md`, sa `B1157_FINAL=PASS_INSTALLED_B1157_OVERLAY_AND_MIGRATIONS_SHOP_STATE_UNCHANGED`, `B1157_PHASE="INSTALLED_PASS"` i `EXIT=0`. Skripta traži da njegov SHA-256 i id stanja `~/.ald1n-b1157-state/<id>` budu oni upisani u `Payload.php` (`generate.php --chain-report`). Dok pinovi nisu upisani, kraj je `HOLD` sa `B1157_REPORT_PINS_NOT_FILLED_IN` bez izmjena.
3. Pomoćni fajlovi iz ranijih paketa su u `incoming/` nepromijenjeni (`b1157-coordinator.php`, `b1157-payload.php`, `b1157-app-check.php` i stariji). Ako nešto fali ili je izmijenjeno, kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY` bez izmjena.
4. `APP_URL` je tačno `https://cms.ald1n.com`, prodavnica je uključena, saobraćaj mali, a do rezervne kopije ima dovoljno vremena.

## Komande (cPanel terminal, nalog `icaffeco`)

```
unzip -o /home/icaffeco/b1158-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1158-installer.sh
bash /home/icaffeco/ald1n-project/incoming/b1158-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1158-installer.sh --rollback --ignore-schedule
```

Prva raspakuje, druga instalira, treća i četvrta vraćaju fajlove (migracija se nikad ne poništava). Izvještaj se zove `docs/operations/<broj>-B1158-INSTALLER-<datum>-<vrijeme>.md`, gdje je broj najveći postojeći plus 1 (očekuje se 1124). Radnik radi odvojeno od SSH sesije; ako sesija padne, pratite izvještaj komandom `tail -f`. Posljednja linija je `EXIT=0` za uspjeh.

## Pravila povlačenja unazad

- GREŠKA PRE komande migracije, uključujući poslije upisa `MIGRATE_INTENT` a prije same komande, i neuspjela rezervna kopija: svi fajlovi se vraćaju obrnutim redom, migracioni fajlovi se brišu, baza nije dirnuta. Završetak: `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`.
- PREKID (kill, pad sesije ili servera) od `MIGRATE_INTENT` nadalje: ista komanda nastavlja samo UNAPRIJED (migracije se mogu ponoviti; prijem se ponavlja). Nikad opšte vraćanje. Ako nastavak nije moguć, kraj je `HOLD_MIGRATION_NOT_RESUMED`.
- Prijem ne uspije POSLIJE izvršene migracije: fajlovi se vraćaju, ali migracioni fajlovi OSTAJU (njihovi redovi su u tabeli `migrations`). Završetak: `HOLD_ROLLED_BACK_SCHEMA_AHEAD`; baza je ispred koda, ništa se ne poništava. Ponovno pokretanje se odbija (`HOLD_SCHEMA_AHEAD`); potreban je novi instalater.
- Prijem ne može da se ocijeni (npr. stalni 429) a fajlovi su tačno oni iz paketa: kraj je `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`; ista komanda ponavlja samo prijem.
- Vraćanje koje stane na pola: `HOLD_ROLLBACK_INCOMPLETE` sa `RECOVERY_NOTE` i blokom komandi za ručno završavanje; blok nikad ne briše migracione fajlove.
- `--rollback` radi isto: ako je migracija već izvršena, fajlovi se vraćaju a migracioni fajlovi ostaju (`PASS_ROLLED_BACK_FILES_SCHEMA_AHEAD`).

## Poznata ograničenja

1. Između fajlova i migracije novi kod radi nad starom šemom. Generator ne može da dokaže koje staze to podnose; CI pokreće stranice oba korisnika u svakoj tački. Pokrenite instalaciju u satu sa malo saobraćaja.
2. Alat nikad ne prijavljuje korisnika, pa strane iza prijave proverava vlasnik (kontrolna tabla, lista porudžbina i jedna porudžbina, forma uplate, potraživanja, forma proizvoda).
3. Aplikacija čuva samo 2 najnovije provjerene rezervne kopije. Kopiju koju je napravio ovaj alat (ključ u izvještaju, `DB_BACKUP`) sačuvajte van servera prije sljedeće noćne.
4. Kratke prolazne greške 500 do oko 2 minuta poslije svake grupe nisu isključene (`realpath_cache_ttl`); alat provjerava samo stranice bez prijave. Prikaz koji zove rutu koju payload briše ide u grupu C prije ruta (npr. `row-action.blade.php`).
5. Redoslijed grupa je izveden pravilom (nove datoteke, biblioteke, kontroleri, provider, rute, prikazi; unutar grupe pozvani prije pozivaoca). Pravilo ne vidi da li stari prikaz pada na novu promjenljivu kontrolera; CI to hvata tek kad stranica ima podatke.
6. Cloudflare se ne dira i nije potrebno čišćenje keša.

## Šta se mijenja za korisnike

Nove komponente administracije (CSS i prikazi), jedna migracija podataka (`catalog.view_notes`, bez promjene šeme) i izmjena `CoreAccessSeeder.php` koji se ne pokreće. `composer.lock` je izuzet: `vendor/web-auth/webauthn-lib` na hostu ostaje 5.3.9 dok se posebno ne ažurira. Obrisani fajlovi iz main-a ostaju na serveru.

## Šta NIJE provjeravano ovim alatom

Prijavljene strane i POST tokovi; slanje pošte; PDF; stvarna jačina opterećenja; stvarno trajanje na produkciji.

## Kako se pravi paket za novi SHA

```
php tools/storefront/b1158/generate.php <ciljni SHA> --report docs/operations/1123-B1157-INSTALLER-<datum>-<vrijeme>.md [--routes-after <broj>] [--pin <putanja>=<A-F>]
git add tools/storefront/b1158 && git commit
```

Generator piše `Payload.php` i ovaj README; workflow `b1158-installer.yml` čita sve iz `Payload.php` i pada ako on nije tačno izlaz generatora (`--check`).

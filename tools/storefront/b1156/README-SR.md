# B1156: provizije, isplata, storno i zaduženja, nove porudžbine i migracije (69 fajlova + 2 migracije)

Autor: Ald1n <https://ald1n.com>

Ovaj paket prebacuje produkciju sa stanja B1155 (Izveštaj 1121, 8583 fajla, 570 ruta) na stanje `origin/main` 94de4be5. Isti je tip instalatera kao B1155, uz jednu novost: prvi put posle B1145 pokreće migracije baze.

## Šta se menja

- 69 fajlova u `apps/cms/current`: 13 novih, 56 izmenjenih. Testovi i dokumentacija se ne instaliraju.
- 2 fajla koje je main obrisao ostaju na serveru, jer ništa na serveru ne brišemo: `resources/views/dashboard/partials/operator-orders.blade.php` i `resources/views/dashboard/partials/kpi-delta.blade.php`. Novi kod ih ne uključuje. Stablo posle instalacije ima 8596 fajlova (8583 + 13).
- 9 novih ruta (6 web, 3 API): 570 postaje 579.
- 2 migracije: `2026_11_16_000001_create_commission_adjustments` (nove tabele `commission_adjustments` i `commission_debit_settlements`, kolone `auto_approved_at`, `debit_eur`, `net_eur`) i `2026_11_16_000002_backfill_commissions_for_payout` (provizije na čekanju čija je porudžbina plaćena ili isporučena postaju „Za isplatu“, bez obaveštenja).
- Nema izmena `composer`, `.env` ni `config`. Podrazumevana stopa provizije (10 %) i broj dana za kašnjenje isporuke (2) su podrazumevane vrednosti u kodu, ne redovi u bazi.

## Redosled

1. Preflight (samo čitanje): Izveštaj 1121 i stanje B1155, APP_URL tačno `https://cms.ald1n.com`, 71 migracija izvršena i nijedna na čekanju, rezervne kopije, prozor 02:30 i nedelja 03:10, digest svakog izmenjenog fajla, novi fajlovi ne postoje.
2. Kopija svakog izmenjenog fajla u privatni prostor B1156.
3. Fajlovi po grupama, sa pauzom i proverom da sajt radi posle svake: A nova 13 (neaktivna), B biblioteke, C kontroleri, bin alati i admin stilovi, D `AppServiceProvider`, E rute, F prikazi.
4. Tek kad je svaki fajl na mestu: sveža rezervna kopija baze (`app:backup-create` pa `app:backup-verify`), ponovna provera te kopije, tačno dve migracije na čekanju, pa jedan poziv `artisan migrate --force --realpath --path=… --path=…`. Nikad `fresh`, `refresh`, `rollback` ni `reset`, nikad `down()`, nikad brisanje tabele.
5. Provera posle migracije: 73 izvršene, 0 na čekanju, tabele i kolone postoje, premešten je tačno onaj skup provizija koji pravilo vlasnika daje (plaćena ili isporučena porudžbina, nije otkazana), nijedan red u tabelama obaveštenja.
6. Prijem: kompajliranje svih 33 prikaza, stilovi, rute, ponovno stablo od 8596 fajlova.

## Trajanje

Oko 35 do 60 minuta (tri duga čekanja pre grupa D, E i F, čekanje posle migracije, rezervna kopija i provera). Instalater odbija da počne ako bi mu ukupno potrebno vreme zalazilo u prozor rezervne kopije (02:30 svaki dan, 03:10 nedeljom); u izveštaju piše `requiredWindowSeconds`.

## Pre pokretanja proverite sami

1. Postavite `b1156-operator.zip` i `b1156-operator.zip.sha256` u `/home/icaffeco`. Komanda mora da ispiše `b1156-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1156-operator.zip.sha256
   ```

2. Tačno jedan izveštaj `1121-B1155-INSTALLER-*.md`, sa `B1155_FINAL=PASS_INSTALLED_DASHBOARD_DROPDOWNS_REGISTRATION_SUBAGENT_REPORT_REDIRECTS_SHOP_STATE_UNCHANGED` i `EXIT=0`. Skripta traži da njegov SHA-256 bude `1fa75edfdaca0ec8810a54ffa0900666db03f316c37922063102a59b4ff19267` (digest git bloba; Windows kopija sa CRLF daje drugu vrednost). Stanje `~/.ald1n-b1155-state/48389cb1f7b6f435878fff98655ff202` mora biti `INSTALLED_PASS`.
3. Pomoćni fajlovi iz ranijih paketa su u `incoming/` nepromenjeni. Posebno `b1155-coordinator.php` i `b1155-app-check.php` moraju da daju `bff6b32639318b7b1f174c675aa7aa336c8e9efdae10b6fdd4dfd167f95c8e34` i `ada769856ff6d7c498bb952cb2f1df12e11f8079cccae7f7740e79a27c96613e` (isti članovi kao u paketu čiji je zbir `40916a069bbef1deeb8ff7b3f9cc5783b608fd4682d6eaaa7b1254a23c75f0fa` upisan u Izveštaj 1121, dokazano u CI). Ako nešto fali ili je izmenjeno, kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY` bez izmena.
4. `APP_URL` je tačno `https://cms.ald1n.com`, prodavnica je uključena, saobraćaj mali, a do rezervne kopije (02:30, nedeljom 03:10, Europe/Belgrade) ima dovoljno vremena.

## Komande (cPanel terminal, nalog `icaffeco`)

```
unzip -o /home/icaffeco/b1156-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1156-installer.sh
bash /home/icaffeco/ald1n-project/incoming/b1156-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1156-installer.sh --rollback --ignore-schedule
```

Prva raspakuje, druga instalira, treća i četvrta vraćaju fajlove (migracija se nikad ne poništava). Izveštaj se zove `docs/operations/1122-B1156-INSTALLER-<datum>-<vreme>.md`. Radnik radi odvojeno od SSH sesije; ako sesija padne, pratite izveštaj komandom `tail -f`. Poslednja linija je `EXIT=0` za uspeh.

## Pravila povlačenja unazad

- Greška PRE `MIGRATE_INTENT` (uključujući neuspelu rezervnu kopiju): svi fajlovi se vraćaju obrnutim redom, dva migraciona fajla se brišu, baza nije dirnuta. Završetak: `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`.
- Prekid (kill) ili greška od `MIGRATE_INTENT` nadalje: ista komanda nastavlja samo UNAPRED (migracije proveravaju šta već postoji, pa se mogu ponoviti). Nikad opšte vraćanje.
- Prijem ne uspe POSLE izvršene migracije: fajlovi se vraćaju, ali dva migraciona fajla OSTAJU (njihovi redovi su u tabeli `migrations`). Završetak: `HOLD_ROLLED_BACK_SCHEMA_AHEAD`; baza je ispred koda, ništa se ne poništava. Ponovno pokretanje se odbija (`HOLD_SCHEMA_AHEAD`); potreban je novi instalater.
- `--rollback` radi isto: ako je migracija već izvršena, fajlovi se vraćaju a migracioni fajlovi ostaju (`PASS_ROLLED_BACK_FILES_SCHEMA_AHEAD`).

Zašto je stari kod bezbedan sa novom šemom: stari kod čita provizije i serije isplata preko Eloquent modela i eksplicitnih lista kolona; nove tabele i kolone su samo dodate (`auto_approved_at` može biti NULL, `debit_eur` ima podrazumevanu vrednost 0, `net_eur` može biti NULL). CI to dokazuje: posle vraćanja fajlova stari kod služi kontrolnu tablu, listu provizija i porudžbine sa već premeštenim redovima.

## Poznata ograničenja (iskreno)

1. Između fajlova i migracije (nekoliko minuta) novi kod radi nad starom šemom. Skoro sve staze imaju zaštitu (`CommissionMoney::hasAdjustments()`, `Schema::hasTable`). Dve vrste radnji NEMAJU zaštitu i u tom prozoru bi dale grešku 500 (transakcija se poništava, podaci se ne kvare): (a) izmena stavki porudžbine preko `OrderAmendmentService::amend`, koju mogu pokrenuti i kupac i sub-agent, na webu i u mobilnoj aplikaciji (stiže do upisa `auto_approved_at`, `CommissionWorkflowService.php:273`), i (b) vraćanje provizije u „Na čekanju“, koje pada ranije u `hasOpenDebits()`. Dakle nije samo administratorska radnja. Pokrenite instalaciju u satu sa malo saobraćaja.
2. Alat nikad ne prijavljuje korisnika, pa strane iza prijave (kontrolna tabla, provizije, porudžbine, zvonce) proverava vlasnik: otvorite kontrolnu tablu, listu porudžbina i jednu porudžbinu, stranu Provizije i zvonce obaveštenja, na računaru i na telefonu.
3. Stalne kopije: aplikacija čuva samo 2 najnovije proverene rezervne kopije. Kopiju koju je napravio ovaj alat (ključ u izveštaju, `DB_BACKUP`) sačuvajte van servera pre sledeće noćne.
4. Cloudflare se ne dira i nije potrebno čišćenje keša (`?v=<vreme fajla>` u pogledima).

## Šta NIJE proveravano ovim alatom

Prijavljene strane i POST tokovi; slanje pošte; PDF; stvarna jačina opterećenja. CI proverava dodatno: prijavljene strane SuperAdministratora i sub-agenta u svakom koraku, stari kod nad novom šemom, skup premeštenih provizija po pravilu vlasnika, odsustvo reda u tabelama obaveštenja, zadržane „siročiće“ i broj ruta.

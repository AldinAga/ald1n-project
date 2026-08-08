# Centralni release check

Od verzije **v2.1.0-beta7.23**, a u RC obliku od **v2.1.0-rc1**, sve ključne produkcione provere mogu da se pokrenu jednom komandom:

```bash
php artisan app:release-check
```

Komanda ne šalje e-mailove, ne izvršava outbox dispatch, ne pokreće operativnu automatizaciju, ne radi warranty backfill i ne kreira backup. Podrazumevani režim je dijagnostički i ne menja poslovne podatke.

## Profili

### Quick

```bash
php artisan app:release-check --profile=quick
```

Proverava deployment, zdravlje sistema, autentifikaciju, izveštaje i Customer Portal. Koristi se za kratku proveru posle manje izmene.

### Standard

```bash
php artisan app:release-check --profile=standard
```

Podrazumevani profil. Proverava platformu, katalog, porudžbine, lager, izveštaje, portal, postprodaju, garancije, e-mail outbox, potraživanja i automatizaciju.

### Full

```bash
php artisan app:release-check --profile=full --render
```

Dodaje legacy proveru, legacy medijske fajlove i regresiju pojedinačnih stranica. Opcija `--render` uključuje Blade, Dashboard, PDF i detail-page render provere.

### RC

```bash
php artisan app:release-check --profile=rc --render --strict --snapshot
```

RC profil sadrzi ceo full profil i dodatno proverava release SHA-256 integritet, security hardening, migrations/SQL runtime, role/permission/rute i poslednji backup. Namenjen je zavrsnoj acceptance proveri i mora se pokrenuti bez `--repair`.

## Produkcioni deploy

Za postavljanje novog paketa preporučena je komanda:

```bash
php artisan app:release-check --profile=full --repair --render --snapshot
```

Opcije:

- `--repair` — pokreće postojeći `app:deployment-check --repair`, a zatim normalizacije Smart Products, Product Variants i Management Reports modula;
- `--render` — renderuje ključne Blade stranice i PDF izveštaje;
- `--snapshot` — čuva System Health snapshot u bazi;
- `--strict` — uključuje strogu proveru legacy grantova i svaki `WARN` tretira kao neuspeh;
- `--list` — prikazuje plan bez izvršavanja;
- `--no-report` — ne čuva JSON izveštaj;
- `--report=naziv.json` — zadaje bezbedan naziv izveštaja unutar release-check direktorijuma.

Pre primene repair režima može se pregledati tačan plan:

```bash
php artisan app:release-check --profile=full --repair --render --snapshot --list
```

## Runtime CRITICAL rezultati

`app:release-check` namerno ne kreira backup, ne pokreće automatizaciju i ne upisuje lažni scheduler heartbeat. Ako `app:system-health` prijavi ove stavke, prvo otkloni stvarno operativno stanje:

```bash
php artisan app:scheduler-heartbeat
php artisan app:automation-run
php artisan app:backup-create --type=manual
```

Hosting mora zatim svakog minuta izvršavati:

```cron
* * * * * cd /home/icaffeco/cms.ald1n.com && php artisan schedule:run >> /home/icaffeco/cms.ald1n.com/storage/logs/scheduler.log 2>&1
```

Jednokratni `app:scheduler-heartbeat` služi samo za proveru upisa. Stvarnu ispravnost dokazuje novi heartbeat koji nastane kroz `schedule:run` u narednim minutima.

## Rezultati

Komanda završava jednim od rezultata:

- `RC READY` - rc profil nema gresaka niti upozorenja;
- `READY FOR PRODUCTION` — ostali profili nemaju grešaka niti upozorenja;
- `READY WITH WARNINGS` — provere su prošle, ali postoje upozorenja koja treba pregledati;
- `NOT READY` — najmanje jedna provera nije prošla.

Exit kod je `0` kada nema FAIL rezultata i `1` kada sistem nije spreman. Nepoznat profil vraća exit kod `2`.

## JSON izveštaji

Izveštaji se čuvaju u:

```text
storage/app/release-check/
```

Za svako pokretanje nastaje timestampovani JSON, a `storage/app/release-check/latest.json` uvek predstavlja poslednji rezultat. Upis je atomski kako polovičan JSON ne bi bio objavljen ako dođe do prekida procesa.

Izveštaj sadrži:

- aktivnu verziju i release tag;
- profil i izabrane opcije;
- početak, kraj i trajanje;
- komandu, parametre, exit kod i status svake provere;
- sanitizovan izlaz bez apsolutne aplikacione putanje;
- zbir PASS, WARN i FAIL rezultata.

## Strogi režim

```bash
php artisan app:release-check --profile=rc --render --strict --snapshot
```

Koristi se za RC acceptance i pre Stable verzije. U ovom režimu širi legacy grantovi i svaki jasan `WARN` zaustavljaju release. Za redovan beta deploy nije obavezno koristiti `--strict`, ali sva upozorenja treba pregledati.

## Dodatna statička provera

```bash
php bin/release-check-smoke.php
```

Ova provera ne zahteva Laravel runtime ni bazu. Validira profile, registry komandi, bezbedne opcije, verziju paketa, dokumentaciju i Composer skripte.


## Stable profil

Od verzije `v2.1.0` dostupan je finalni profil:

```bash
php artisan app:release-check --profile=stable --render --strict --snapshot
```

Stable profil sadrzi sve RC hardening i poslovne provere. Bez WARN i FAIL rezultata zavrsava porukom `RELEASE CHECK: STABLE READY`, a JSON statusom `ready_for_stable`.

## Stable Maintenance v2.1.1

Stable profil od verzije `v2.1.1` dodatno uključuje dve render provere:

```bash
php artisan app:order-create-doctor --render
php artisan app:catalog-ownership-doctor --render
```

Prva provera otvara kompletan `/order/new` tok kroz Laravel container i potvrđuje da Blade, mapa varijanti, bankovni računi i dobavljači mogu da se renderuju. Druga potvrđuje jedinstveni `/catalog`, pravilo da Administrator vidi i uređuje samo artikle koje je kreirao, SuperAdministrator pristup kompletnom katalogu i kompatibilni redirect `/admin/catalog` ka `/catalog`.

Finalna provera za ovo izdanje je:

```bash
php artisan app:release-check --profile=stable --render --strict --snapshot --report=v2.1.1-stable-acceptance.json
```

Uspešan završetak je `RELEASE CHECK: STABLE READY` bez WARN i FAIL rezultata.


## Product media v2.1.2

Stable profil od verzije `v2.1.2` uključuje `app:product-media-doctor`. Provera potvrđuje rute, storage kolone, bezbedan download pune rezolucije i dostupnost Imagick ili GD obrade za JPEG, PNG i WebP.

```bash
php artisan app:product-media-doctor
php artisan app:release-check --profile=stable --render --strict --snapshot --report=v2.1.2-stable-acceptance.json
```

## Catalog Settings & Product Data v2.1.3

Stable profil od verzije `v2.1.3` ukljucuje `app:catalog-settings-doctor`. Provera potvrđuje novu semu, posebne slug stranice tipova, Drag & Drop rute, trajno brisanje specifikacija, mapiranje tipa na kategoriju i uskladjenost postojecih artikala.

```bash
php artisan app:catalog-settings-doctor --repair
php artisan app:release-check --profile=stable --render --strict --snapshot --report=v2.1.3-stable-acceptance.json
```

`--repair` menja samo kategorije artikala za tipove koji vec imaju izabranu automatsku kategoriju i pokusava bezbedno mapiranje nepovezanog tipa. Ako aktivan tip i dalje nema kategoriju, strict release se zaustavlja dok se mapiranje ne izabere na posebnoj stranici tog tipa.

## v2.1.3.1 catalog integrity acceptance

Pre strict Stable proverе pokrenuti `php artisan migrate --force` i `php artisan app:catalog-settings-doctor --repair`. Ponovljeni `php artisan app:catalog-settings-doctor` mora prijaviti da svi aktivni tipovi imaju automatsku kategoriju i da nema zastarelih specifikacionih referenci.

## v2.1.3.2 product-save acceptance

Pre Stable strict provere pokrenuti `php bin/product-save-regex-hotfix-smoke.php`, zatim ručno sačuvati postojeći proizvod i potvrditi da nema Error 500. Hotfix ne dodaje migraciju baze.

## v2.1.3.3 storage-capacity acceptance

Pre strict Stable proverе pokrenuti:

```bash
php artisan migrate --force
php artisan app:catalog-settings-doctor --repair
php bin/storage-capacity-total-smoke.php
```

Doctor mora potvrditi da su povezani izvori diskova i izvedeni ukupni kapaciteti usklađeni na proizvodima i varijantama. Posle ručne provere jednog postojećeg artikla završiti sa `app:release-check --profile=stable --render --strict --snapshot`.

## v2.1.5 UX, Mobile & Runtime acceptance

Pre Stable strict provere pokrenuti:

```bash
php artisan migrate --force
php bin/cms-v2.1.5-smoke.php
php bin/product-type-page-render-hotfix-smoke.php
php artisan app:cms-v2-1-5-doctor --render --repair
```

Doctor mora potvrditi globalni UX runtime, ključne rute bez dupliranih imena, postojanje controller klasa i metoda za sve route akcije, Blade compile, render svih sistemskih error stranica i da je postojeće polje `snaga-napajanja` povezano sa tipom `desktop-racunar` u srednjoj zoni njegovih specifikacija.

Finalna komanda je:

```bash
php artisan app:release-check --profile=stable --render --strict --snapshot --repair --report=v2.1.5-stable-acceptance.json
```

## v2.1.6

Stable profil uključuje `cms_v216`, koji pokreće `app:cms-v2-1-6-doctor`. Uz `--render`, `--repair` i `--strict` proverava indekse, cache integraciju, reprezentativne SQL upite, Data Quality audit i render centra.

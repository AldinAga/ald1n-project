# Upgrade v2.0.0-beta3 → v2.0.0-beta4

Beta4 vraća dizajn početne stranice sa legacy PHP sistema i uvodi deployment repair tok za ranije prijavljene provere:

- `FAIL operations tables`;
- `FAIL system permissions`;
- `FAIL legacy grants read-only`.

## Pre zamene fajlova

1. Napraviti backup aplikacije, `.env` fajla i baze `icaffeco_lrvl`.
2. Potvrditi da je instalirana verzija `2.0.0-beta3`.
3. Ne prepisivati `.env`, `storage/` ni korisnički upload sadržaj.

## Primena Upgrade ZIP-a

Raspakovati beta4 Upgrade paket preko postojeće beta3 aplikacije, zatim pokrenuti:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:deployment-check --repair
php artisan legacy:check
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

`app:deployment-check --repair` je obavezan u ovom upgrade-u. Komanda:

1. pokreće sve nedostajuće Laravel migracije;
2. izvršava `CoreAccessSeeder` idempotentno;
3. ponovo proverava operativne tabele i svih 16 sistemskih dozvola;
4. potvrđuje read-only session i SQL guard legacy konekcije.

## Tumačenje legacy grant rezultata

Na shared/cPanel hostingu isti DB nalog ponekad dobija šire grantove nego što aplikacija zahteva. Beta4 više ne označava to kao deployment FAIL ukoliko su potvrđeni:

- `PASS legacy session read-only`;
- `PASS legacy SQL guard`.

Tada se prikazuje WARN sa pronađenim privilegijama. Za potpuno strogu least-privilege proveru koristiti:

```bash
php artisan app:deployment-check --strict-legacy-grants
php artisan legacy:check --strict-grants
```

Najbezbednija konfiguracija je i dalje zaseban `LEGACY_DB_USERNAME` sa samo `SELECT` i `SHOW VIEW` pravima nad `icaffeco_cms`.

## Vizuelni smoke test

### Desktop

- prvi header red prikazuje logo/naziv levo, a kurs, Auto/Tamna/Svetla režim i nalog desno;
- drugi red prikazuje ikonice i stavke Početna, Artikli, Upravljanje porudžbinama, Provizije, Administracija, Korisnici i Podešavanja;
- Odjava je izdvojena desno crvenim outline dugmetom;
- hero prikazuje verzijski badge, dobrodošlicu, četiri obojene akcije i EUR/RSD karticu;
- KPI kartice su raspoređene u četiri kolone.

### Mobilni

- top bar prikazuje logo, kurs, temu, avatar i hamburger bez horizontalnog skrola;
- drawer sadrži kompletnu navigaciju i Odjavu;
- hero akcije su vertikalne kao na legacy stranici;
- KPI kartice su raspoređene u dve kolone;
- testirati širine 320, 390, 483, 768 i 1250 px.

## Rollback

Vratiti backup beta3 fajlova i pokrenuti:

```bash
php artisan optimize:clear
php artisan optimize
```

Beta4 ne uvodi novu poslovnu tabelu niti menja podatke porudžbina/lagera. `--repair` samo primenjuje ranije postojeće migracije koje nedostaju i idempotentno dopunjava sistemske dozvole.

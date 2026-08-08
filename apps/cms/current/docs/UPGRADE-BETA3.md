# Upgrade v2.0.0-beta2 → v2.0.0-beta3

Ovo je hotfix za HTTP 500 na pojedinačnim stranicama artikala. Ne uvodi novu baznu migraciju niti menja transakcijsku logiku porudžbina i lagera.

## Pre zamene fajlova

1. Napraviti backup aplikacije i `.env` fajla.
2. Potvrditi da je trenutno instalirana verzija `2.0.0-beta2`.
3. Sačuvati `storage/`, `.env` i korisnički upload sadržaj.

## Primena Upgrade ZIP-a

1. Raspakovati paket preko postojeće beta2 aplikacije.
2. Obavezno pokrenuti:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan app:deployment-check
php artisan optimize
```

`php artisan migrate --force` nije potreban zbog beta3 hotfixa, ali ga je bezbedno zadržati u standardnoj deploy proceduri.

## Obavezni smoke test

Otvoriti najmanje sledeće stranice kao SuperAdmin i kao korisnik koji ima `catalog.view`:

- `/catalog/ram-memorija-ddr4-8gb-samsung-skhynix-micron`
- `/catalog/dell-latitude-5440`
- `/catalog/asus-tuf-gaming-a15-fa506iu`

Potvrditi:

- odgovor je HTTP 200 i prikazuju se naziv, SKU, lager i provizija;
- postojeće slike se prikazuju, a neispravna slika ne obara stranicu;
- nepostojeći slug vraća HTTP 404;
- korisnik van dozvoljenih kategorija dobija HTTP 404;
- lista `/catalog` i link „Detalji“ i dalje vode na slug URL;
- mobilni hamburger i kompaktne admin forme iz beta2 ostaju ispravni.

## Rollback

Vratiti backup beta2 fajlova i pokrenuti `php artisan optimize:clear`. Baza ne zahteva rollback.

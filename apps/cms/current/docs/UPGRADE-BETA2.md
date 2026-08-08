# Upgrade v2.0.0-beta1 → v2.0.0-beta2

Ovo je UI patch izdanje. Ne uvodi novu baznu migraciju niti menja produkcionu logiku porudžbina i lagera.

## Pre zamene fajlova

1. Napraviti backup aplikacije i `.env` fajla.
2. Potvrditi da je trenutno instalirana verzija `2.0.0-beta1`.
3. Ne menjati niti prepisivati produkcioni `.env`.

## Primena Upgrade ZIP-a

1. Raspakovati paket preko postojeće aplikacije uz očuvanje strukture direktorijuma.
2. Pokrenuti:

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

`php artisan migrate --force` nije potreban zbog beta2 izmena, ali ga je bezbedno pokrenuti u standardnoj deploy proceduri jer nema nove beta2 migracije.

## Obavezni UI smoke test

- mobilni header na 320 px, 390 px, 768 px i 1024 px prikazuje hamburger;
- stranica nema horizontalni scrollbar;
- Artikli, Korisnici i Podešavanja otvaraju podmenije unutar hamburger menija;
- kurs, promena teme, korisnički profil i Odjava dostupni su u mobilnom meniju;
- panel Dodaj korisnika je kompaktan i ne rasteže se do kraja liste;
- Dodaj artikal i Izmeni artikal nemaju veštački visoke inpute ili prazne grid redove.

## Rollback

Vratiti backup beta1 fajlova i pokrenuti `php artisan optimize:clear`. Baza ne zahteva rollback jer beta2 ne dodaje migracije.

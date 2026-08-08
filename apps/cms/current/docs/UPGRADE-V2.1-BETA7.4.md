# Upgrade na v2.1.0-beta7.4

Ova verzija dodaje administratorsko podešavanje Cloudflare Turnstile zaštite bez ručnog menjanja `.env` fajla.

## Šta je dodato

- nova stranica **Podešavanja → Cloudflare Turnstile**;
- unos i izmena `Site Key` i `Secret Key` vrednosti;
- uključivanje i isključivanje Turnstile zaštite iz administracije;
- podešavanje očekivanog hostname-a;
- vrednosti iz baze imaju prioritet nad `.env` konfiguracijom;
- `.env` ostaje fallback kada u bazi još nema Turnstile podešavanja;
- Secret Key se čuva šifrovano pomoću Laravel `APP_KEY` vrednosti;
- Secret Key se ne vraća u Blade prikaze, audit log niti zajednički niz podešavanja;
- login i zahtev za resetovanje lozinke odmah koriste sačuvanu konfiguraciju;
- deployment check proverava efektivnu konfiguraciju iz baze ili `.env` fajla.

## Deploy

```bash
cd /home/icaffeco/cms.ald1n.com

composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear
php artisan app:deployment-check
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

Nakon prijave otvorite:

```text
Podešavanja → Cloudflare Turnstile
```

Unesite Site Key, Secret Key i domen aplikacije, zatim uključite Turnstile i sačuvajte podešavanja.

## Važna napomena

`APP_KEY` mora ostati nepromenjen. Promena `APP_KEY` vrednosti onemogućava dešifrovanje već sačuvanog Secret Key-a, pa ga u tom slučaju treba ponovo uneti.

Nova migracija baze nije potrebna, jer se koriste postojeća `settings` tabela i postojeći sistem dozvola.

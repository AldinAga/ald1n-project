# Upgrade na v2.1.0-beta7.1

## Svrha

Hotfix uklanja HTTP 500 sa administratorske liste porudžbina i dodaje rotaciju fotografija direktno na ekranu izmene artikla.

## Upgrade osnova

Paket se primenjuje preko potvrđene `v2.1.0-beta7` instalacije.

## Deploy

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:orders-doctor --repair --render
php artisan app:deployment-check
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

Očekivani završetak doctor komande:

```text
PASS Orders šema je kompletna.
PASS Orders SQL upiti su uspešni.
PASS Orders kontroler, Blade i authenticated layout su uspešno renderovani.
```

## Rotacija slika

Na `Izmeni artikal` svaka postojeća slika ima rotaciju ulevo i udesno za 90°. Za legacy fotografiju sistem pravi lokalnu kopiju u Laravel storage-u i ne menja izvorni legacy fajl niti legacy bazu.

Rotacija zahteva aktivnu PHP GD ekstenziju. Ako nije dostupna, korisnik dobija kontrolisanu poruku umesto HTTP 500.

## Baza

Nova migracija nije potrebna.

# Upgrade na v2.1.0-beta7.3

Ovo je hotfix za produkcioni slučaj u kom je lista `/admin/orders` prolazila render proveru, ali je `/admin/orders/1` završavao u HTTP 503 recovery režimu.

## Ispravke

- datumi sa nevažećim ili MySQL zero vrednostima više se ne čitaju kroz Eloquent datetime cast direktno iz Blade-a;
- named rute u detalju porudžbine generišu se kroz bezbedni helper i ne obaraju celu stranicu kada opciona ruta nije dostupna;
- admin i korisnički detalj imaju minimalni read-only fallback koji vraća HTTP 200 i prikazuje osnovne podatke/stavke umesto 503;
- fallback se jasno označava response header-om i doctor ga i dalje tretira kao neuspeh punog prikaza;
- `app:orders-doctor` sada prijavljuje tačnu exception klasu, poruku, fajl i liniju za admin i korisnički detalj;
- doctor više ne prekida audit na prvoj detail grešci, već proverava oba prikaza;
- uklonjeni su direktni `@can`, `auth()` i rizični datetime formati iz detail Blade toka;
- definisane su sve payment/inventory Gate sposobnosti koje već postoje u sistemu dozvola;
- dodat je regresioni test za zero/invalid datume i nedostajuću named rutu.

## Deploy

```bash
cd /home/icaffeco/cms.ald1n.com

composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear
php artisan app:orders-doctor --repair --render --order-id=1
php artisan app:detail-pages-doctor --order-id=1
php artisan app:deployment-check

php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php

php artisan optimize
```

Očekivani rezultat:

```text
PASS Administratorska lista porudžbina je uspešno renderovana.
PASS Administratorski detalj porudžbine #1 je uspešno renderovan.
PASS Korisnički detalj porudžbine #1 je uspešno renderovan.
```

Ako puni prikaz ipak aktivira fallback, doctor sada odmah ispisuje:

```text
UZROK <ExceptionClass>: <poruka>
Lokacija: <fajl>:<linija>
```

Nova migracija baze nije potrebna.

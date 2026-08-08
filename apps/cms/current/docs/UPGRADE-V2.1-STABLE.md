# Upgrade na v2.1.0 Stable

## Polazna verzija

UPGRADE paket je namenjen isključivo potvrđenoj verziji `v2.1.0-rc1`. Stable nema novu migration datoteku.

## Šta se menja

- početna ruta `/` postaje univerzalni dashboard;
- sadržaj nekadašnje stranice „Moj portal“ prikazuje se na početnoj strani prema dozvolama korisnika;
- posebna `/portal` stranica se uklanja, a stari URL trajno preusmerava na `/`;
- poruke, nalog, porudžbine, dokumenti, garancije i servis ostaju dostupni kroz postojeće autorizovane rute.

## Kratak postupak

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

Raspakuj UPGRADE paket preko postojećih fajlova, pa pokreni:

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr

# Ukloni obsolete fajlove stare zasebne portal stranice.
rm -f app/Http/Controllers/CustomerPortalController.php \
  resources/views/portal/index.blade.php

/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan app:migrations-doctor --strict
/usr/local/bin/php bin/customer-portal-smoke.php
/usr/local/bin/php bin/customer-portal-2-smoke.php
/usr/local/bin/php bin/stable-hardening-smoke.php
/usr/local/bin/php artisan app:release-check --profile=stable --render --strict --snapshot --report=v2.1.0-stable-acceptance.json
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
/usr/local/bin/php artisan optimize
```

Očekivani rezultat:

```text
RELEASE CHECK: STABLE READY
```

## Browser provera

- prijava vodi na `/`;
- početna prikazuje `Univerzalni dashboard`;
- kupac na početnoj vidi samo svoje porudžbine, dokumente, poruke, garancije i servis;
- zaposleni vide poslovne KPI i module prema svojim pravima;
- `/portal` preusmerava na `/`;
- u glavnom meniju više nema posebne stavke „Moj portal“.

## Rollback

Stable nema novu migraciju. Ako deploy fajlova ne uspe pre novih poslovnih upisa, vrati RC1 FULL paket, pokreni `optimize:clear`, Composer autoload i RC release check. Ne vraćaj bazu na stariji backup ako su posle deploy-a nastale nove porudžbine, uplate, poruke ili audit zapisi.

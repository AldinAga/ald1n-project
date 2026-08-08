# Upgrade na v2.1.0-beta4

Ova verzija uvodi operativnu automatizaciju, deduplikovana upozorenja, korisničke preference obaveštenja i scheduler evidenciju. Redis ostaje isključen.

## Pre deploya

Napraviti backup aplikacije, produkcionog `.env` fajla, upload sadržaja i baze `icaffeco_lrvl`.

Raspakovati `ald1n-cms-laravel-v2.1.0-beta4-upgrade-from-v2.1.0-beta3.2.zip` preko postojeće beta3.2 instalacije. Ne prepisivati `.env`, `storage/app/public`, `public/storage` niti privatne potvrde uplata.

## Komande

```bash
cd /home/icaffeco/cms.ald1n.com
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:automation-doctor --repair --run
php artisan app:payments-inventory-doctor --render
php artisan app:operations-doctor --render
php artisan app:reports-doctor --render
php artisan app:deployment-check --repair
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

## Cron

Na hostingu treba da postoji samo jedan Laravel scheduler cron:

```cron
* * * * * cd /home/icaffeco/cms.ald1n.com && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler pokreće operativni scan svakog sata u 10. minutu i dnevni pregled u 08:05 po vremenskoj zoni `Europe/Belgrade`.

## Očekivana provera

```text
PASS Automation tabele i kolone postoje.
PASS automation.manage dozvola
PASS Automation SQL upiti su uspešni.
PASS scheduler definicije
PASS Ručni automation scan: success.
```

# Upgrade na v2.1.0-beta7.12

Ova verzija se postavlja preko `v2.1.0-beta7.11` i uvodi terenske ekipe, operativni kalendar i radne naloge.

## Nova migracija

```text
2026_07_30_000021_create_field_operations_beta7_12.php
```

Migracija kreira:

- `field_service_teams`;
- `field_work_orders`;
- `field_work_order_attachments`;
- dozvole `field_operations.view` i `field_operations.manage`.

Postojeće fizičke postprodajne radnje (`service_visit`, `replacement_dispatch`, `return_receipt`) dobijaju nedestruktivno formiran radni nalog. Postojeće porudžbine, uplate, reklamacije, lager i dokumenti ostaju sačuvani.

## Komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
php artisan app:deployment-check
php artisan app:after-sales-doctor
php artisan app:field-operations-doctor
php artisan app:automation-doctor
php artisan app:payments-inventory-doctor
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

## Posle deploy-a

1. Otvorite **Terenske operacije → Terenske ekipe** i unesite interne ekipe ili spoljne servisne partnere.
2. Otvorite neraspoređene radne naloge i dodelite ekipu, početak i kraj termina.
3. Proverite da cron i dalje pokreće `php artisan schedule:run` svakog minuta.
4. Pokrenite `php artisan app:field-operations-doctor` i očekujte sve `PASS` rezultate.

## Važna poslovna pravila

- ista ekipa ne može imati dva aktivna naloga u preklapajućem terminu;
- ekipa ne može krenuti bez dodeljenog termina;
- nalog se može završiti tek nakon evidentiranog dolaska na lokaciju;
- fizička postprodajna radnja završava se kroz radni nalog;
- prilozi se čuvaju na privatnom `local` disku i nemaju javni URL;
- troškovi radnog naloga ne menjaju automatski prodajnu cenu, uplatu ili proviziju.

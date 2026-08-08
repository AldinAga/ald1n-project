# Nadogradnja na v2.1.0-beta7.21

## Polazna verzija

UPGRADE paket se primenjuje isključivo preko **v2.1.0-beta7.20**. Za drugu ili nepoznatu verziju koristi FULL paket.

## Backup

Pre raspakivanja napravi backup baze, `.env` fajla, `storage/app` direktorijuma i postojeće aplikacije.

## Komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
php artisan app:deployment-check
php artisan app:management-reports-doctor --render
php bin/management-report-smoke.php
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

## Nova migracija

`2026_07_31_000030_create_management_reports_beta7_21.php`

Migracija je ponovljiva za MariaDB 10.11. Dodaje nedostajuće kolone pojedinačno, čuva postojeće porudžbine i ne briše finansijsku istoriju.

## Finansijski snapshot

Za nove porudžbine nabavna cena se uzima sa konkretne varijante, a zatim sa roditeljskog proizvoda. Čuva se na stavci porudžbine i kasnija promena kataloga ne menja istorijsku maržu.

Za stare porudžbine migracija koristi trenutno dostupnu nabavnu cenu samo kao najbolju procenu. Izvor je označen sa `migration_variant` ili `migration_product`. Kada trošak nije poznat, ostaje `missing` i izveštaj smanjuje procenat pokrivenosti.

## Formule

- poznati prihod = ukupni prihod − prihod stavki bez nabavne cene;
- bruto dobit = poznati prihod − nabavna vrednost;
- neto doprinos = bruto dobit − provizije − refundacije − završeni servisni troškovi;
- marže se računaju samo nad poznatim prihodom.

## Zakazano slanje

Hosting cron mora pokretati `php artisan schedule:run` svakog minuta. Aplikacija na svakih pet minuta obrađuje rasporede komandom `app:management-report-dispatch --limit=100`.

## Recovery

```bash
php artisan app:management-reports-doctor --repair --render
```

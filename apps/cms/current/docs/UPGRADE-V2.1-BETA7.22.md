# Nadogradnja na v2.1.0-beta7.22

Polazna verzija: **v2.1.0-beta7.21**  
Ciljna verzija: **v2.1.0-beta7.22**

## Pre nadogradnje

Napravi backup baze, `.env` fajla, `storage/app` direktorijuma i kompletne aplikacije. UPGRADE paket postavljaj samo preko beta7.21 instalacije. Za druge verzije koristi FULL paket.

## Glavne izmene

- Customer Portal na `/portal` sa podacima ograničenim na prijavljenog korisnika;
- dokumenti, uplate, plan otplate, garancije, postprodaja, servisni termini i vremenska linija na jednom mestu;
- nove korisničke preference za dokumente, postprodaju, garancije, servis i naplatu;
- potpuno modernizovan Dashboard sa KPI karticama, trendom, prioritetima, poslednjim porudžbinama i brzim akcijama;
- MySQL/MariaDB `ONLY_FULL_GROUP_BY` hotfix za profitabilnost po segmentima i trend izveštaja;
- fallback prikaz bez Error 500 kada opcioni portal ili reporting podaci još nisu dostupni.

## Nova migracija

```text
2026_07_31_000031_create_customer_portal_beta7_22.php
```

Migracija dodaje sledeće kolone u `notification_preferences`:

- `document_updates`;
- `after_sales_updates`;
- `warranty_updates`;
- `service_updates`;
- `receivable_updates`.

Migracija je idempotentna i bezbedna za ponovno pokretanje na MariaDB 10.11.

## Komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
php artisan app:deployment-check
php artisan app:management-reports-doctor --repair --render
php artisan app:customer-portal-doctor --repair --render
php bin/report-grouping-smoke.php
php bin/customer-portal-smoke.php
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

## Provera report hotfix-a

Komanda:

```bash
php artisan app:management-reports-doctor --repair --render
```

više ne sme da prijavi SQL grešku `1055 ... isn't in GROUP BY`. Upozorenje o stavkama bez nabavne cene nije greška i ostaje vidljivo dok se odgovarajućem proizvodu ili varijanti ne unese nabavna cena.

## Cron

Postojeći Laravel scheduler ostaje obavezan:

```cron
* * * * * cd /putanja/do/aplikacije && php artisan schedule:run >> /dev/null 2>&1
```

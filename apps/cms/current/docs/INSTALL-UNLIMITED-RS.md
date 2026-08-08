# Instalacija i upgrade v2.0.0-beta6 na Unlimited.rs

## Putanje

```text
Stari CMS:        https://ald1n.com/cms
Laravel projekat: /home/icaffeco/cms.ald1n.com
Document Root:    /home/icaffeco/cms.ald1n.com/public
```

## Baze

```text
mysql  -> icaffeco_lrvl  -> READ/WRITE; produkcione porudžbine i lager
legacy -> icaffeco_cms   -> isključivo SELECT i SHOW VIEW
```

Za legacy konekciju koristiti poseban MySQL nalog sa samo `SELECT` i `SHOW VIEW`. Postojeći korisnik sa `ALL PRIVILEGES` nije DB-level read-only i ne treba ga koristiti u `LEGACY_DB_USERNAME`.

## Upgrade sa beta5 na beta6

Raspakovati Upgrade ZIP preko postojeće aplikacije. Ne prepisivati produkcioni `.env`, korisničke upload fajlove, `public/storage` niti postojeće runtime podatke.

```bash
cd /home/icaffeco/cms.ald1n.com
mkdir -p storage/framework/sessions storage/framework/cache/data storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear
php artisan app:deployment-check --repair
php artisan app:auth-doctor Ald1n --render-dashboard
php artisan legacy:check

php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan app:deployment-check

php artisan optimize
```

Beta6 sadrži obaveznu, nedestruktivnu repair migraciju `2026_07_23_000008_repair_authenticated_runtime_beta6.php`. Komanda `app:deployment-check --repair` pokreće migracije, dopunjava osnovne dozvole, kreira i proverava runtime direktorijume i kompajlira Blade view-e.

Ako je aplikacija stavljena u maintenance režim pre deploya, po uspešnoj proveri pokrenuti:

```bash
php artisan up
```

## Runtime dozvole

File session, cache, kompajlirani view-i i logovi zahtevaju sledeće upisive putanje:

```text
storage/framework/sessions
storage/framework/cache/data
storage/framework/views
storage/logs
bootstrap/cache
```

Uobičajena cPanel/Unlimited.rs popravka je:

```bash
mkdir -p storage/framework/sessions storage/framework/cache/data storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Ne koristiti `chmod -R 777`.

## Redis

Redis je isključen. U produkcionom `.env` ostaviti:

```dotenv
SESSION_DRIVER=file
CACHE_STORE=file
CACHE_LIMITER=file
QUEUE_CONNECTION=sync
```

## Scheduler cron

```cron
* * * * * cd /home/icaffeco/cms.ald1n.com && php artisan schedule:run >> /dev/null 2>&1
```

## Testovi

Brza Feature suite koristi SQLite in-memory samo ako je `pdo_sqlite` dostupan:

```bash
php artisan test --testsuite=Feature
```

MySQL suite mora koristiti posebnu test bazu, nikada produkcionu `icaffeco_lrvl` bazu:

```bash
cp .env.testing.mysql.example .env.testing
# Popuniti isključivo kredencijale zasebne test baze.
php artisan test -c phpunit.mysql.xml --testsuite=Feature
```

## Posle deploya

Otvoriti login stranicu, prijaviti se, zatim proveriti početnu stranicu, katalog, pojedinačni artikal, porudžbine i lager. Za precizan post-login dijagnostički rezultat koristiti:

```bash
php artisan app:auth-doctor Ald1n --render-dashboard
```

Komanda ne traži niti prikazuje lozinku. Ako prijavi `FAIL`, sačuvati samo klasu i poruku exception-a; ne slati `.env` niti kredencijale baze.

## Servisni lager od v2.1.0-beta7.13

Nakon migracije pokrenuti:

```bash
php artisan app:service-parts-doctor
```

Ako prijavi nedostajuću šemu ili dozvole, koristiti:

```bash
php artisan app:service-parts-doctor --repair
```

## MySQL hotfix v2.1.0-beta7.14.1

Ako je migracija revizija pala sa greškom 1553, postavite beta7.14.1 i ponovo pokrenite `php artisan migrate --force`. Hotfix kreira zaseban indeks nad `order_id` pre uklanjanja starog unique indeksa. Ne uklanjajte ručno foreign key i ne menjajte migrations tabelu.

## Revizije dokumenata od v2.1.0-beta7.14

Nakon postavljanja beta7.14 obavezno pokrenite `php artisan migrate --force`. Migracija uklanja staro unique ograničenje nad `order_id + document_type`. Bez migracije aplikacija će prikazati kontrolisanu poruku da ponovno izdavanje nakon storniranja nije spremno, umesto generičke SQL greške.

## E-mail porudžbina i NBS IPS QR od v2.1.0-beta7.16

U produkcionom `.env` podesiti SMTP i NBS endpoint:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_USERNAME=""
MAIL_PASSWORD=""
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
NBS_IPS_QR_GENERATE_URL="https://nbs.rs/QRcode/api/qr/v1/generate/320?lang=sr_RS_Latn"
NBS_IPS_QR_TIMEOUT=20
```

Hosting cron mora ostati aktivan svakog minuta jer obrađuje e-mail outbox:

```cron
* * * * * cd /home/icaffeco/cms.ald1n.com && php artisan schedule:run >> /dev/null 2>&1
```

Posle migracije pokrenuti:

```bash
php artisan app:order-emails-doctor
php artisan app:order-email-dispatch --limit=500
```

Primaoci, intervali, događaji i PDF prilozi podešavaju se u **Podešavanja → E-mail porudžbina**. Svaki primalac dobija zasebnu poruku. SMTP kvar se beleži u outbox-u i ne vraća poslovnu transakciju.

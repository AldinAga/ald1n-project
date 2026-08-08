# Nadogradnja na v2.2.0 — Mobile API Foundation

Polazna verzija: **v2.1.6**

Ovo izdanje ne menja postojeći Blade frontend. Dodaje stabilnu API osnovu za zasebnu React Native / Expo aplikaciju, registraciju Android/iOS uređaja, mobilna obaveštenja, profile i database queue infrastrukturu.

## Pre instalacije

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

Sačuvati postojeći `.env`, `storage` i korisničke fajlove. Paket raspakovati preko kompletne instalacije v2.1.6.

## Obavezne `.env` izmene

```env
APP_URL=https://cms.ald1n.com
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=90
QUEUE_FAILED_DRIVER=database-uuids

MOBILE_PUSH_ENABLED=false
MOBILE_PUSH_PROVIDER=expo
MOBILE_ANDROID_MIN_VERSION=1.0.0
MOBILE_ANDROID_LATEST_VERSION=1.0.0
MOBILE_IOS_MIN_VERSION=1.0.0
MOBILE_IOS_LATEST_VERSION=1.0.0
```

`APP_URL` mora biti javni HTTPS URL backend-a jer native klijent dobija apsolutne URL-ove slika.

`MOBILE_PUSH_ENABLED` ostaje `false` dok Expo/FCM/APNs slanje ne bude povezano u narednoj fazi. v2.2.0 registruje i bezbedno čuva push tokene, ali ne tvrdi da je produkciona push isporuka već aktivna.

## Testiranje i produkciona instalacija

`php artisan test` zavisi od `require-dev` paketa (`phpunit` i `nunomaduro/collision`). Zato se PHPUnit ne pokreće posle `composer install --no-dev`, jer tada Artisan `test` komanda namerno nije registrovana.

Test suite pokrenuti na lokalnom/staging okruženju pre uklanjanja razvojnih paketa:

```bash
composer install --optimize-autoloader
/usr/local/bin/php artisan test
```

Na produkciji zatim instalirati bez razvojnih paketa:

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
/usr/local/bin/php artisan route:clear
/usr/local/bin/php artisan migrate --force
```

PHPUnit se ne pokreće posle `composer install --no-dev`. Na produkciji se koriste dependency-free smoke provere i Artisan doctor/release-check komande.

Migracije:

- `000039` — `mobile_devices`;
- `000040` — `notification_preferences.push_enabled`;
- `000041` — `jobs`, `job_batches` i `failed_jobs`.

Sve tri migracije su aditivne. Ne menjaju postojeće porudžbine, artikle, dokumente ni istoriju poslovnih operacija.

## Queue worker

Database queue nema smisla bez stalnog workera. Na cPanel/server okruženju pokrenuti Supervisor, systemd ili pouzdan cron watchdog koji održava:

```bash
/usr/local/bin/php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=120 --max-time=3600
```

Nakon svakog deploy-a:

```bash
/usr/local/bin/php artisan queue:restart
```

## Provere

```bash
/usr/local/bin/php bin/cms-v2.2.0-smoke.php
/usr/local/bin/php artisan app:cms-v2-2-0-doctor --strict
/usr/local/bin/php artisan route:list --path=api/v1
```

Ručno proveriti:

1. `POST /api/v1/auth/token`;
2. `GET /api/v1/bootstrap`;
3. `POST /api/v1/devices` sa test instalacionim UUID-om;
4. `GET /api/v1/catalog/filters`;
5. `GET /api/v1/orders/options`;
6. `GET /api/v1/notifications`;
7. standardni error envelope sa `code` i `request_id`.

OpenAPI ugovor je u `docs/openapi.yaml`.

## Završna Stable provera

```bash
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
/usr/local/bin/php artisan app:release-check \
  --profile=stable \
  --render \
  --strict \
  --snapshot \
  --repair \
  --report=v2.2.0-stable-acceptance.json
/usr/local/bin/php artisan optimize
```

Očekivani rezultat: `RELEASE CHECK: STABLE READY`.


## Production readiness hotfix

Na velikim/shared hosting filesystemima nizak procenat slobodnog prostora nije sam po sebi kritičan kada postoje stotine gigabajta slobodnog prostora. Disk health sada kombinuje apsolutni i relativni prag. Primer `750,52 GB slobodno (9,6%)` je HEALTHY, dok stvarno nizak apsolutni prostor ostaje WARNING/CRITICAL.

Pre Stable provere obavezno:

```bash
# U .env:
QUEUE_CONNECTION=database

/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php bin/v2.2.0-production-readiness-hotfix-smoke.php
/usr/local/bin/php artisan app:cms-v2-2-0-doctor --strict

# Zadrži pre-upgrade v2.1.6 backup, ali napravi i svež v2.2.0 backup:
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify

/usr/local/bin/php artisan app:release-check --profile=stable --strict
/usr/local/bin/php artisan optimize
```

Alternativno, nakon raspakivanja hotfix PATCH-a pokreni:

```bash
chmod 700 fix-v2.2.0-production-readiness.sh
./fix-v2.2.0-production-readiness.sh
```

Skripta prvo čuva kopiju `.env`, a zatim izvodi isti redosled provera.

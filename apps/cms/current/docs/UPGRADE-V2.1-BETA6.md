# Upgrade na v2.1.0-beta6

## Svrha

Beta6 uvodi produkcioni security/audit sloj, privatne MySQL i upload backup-e, System Health ekran, scheduler heartbeat i bezbedno pokretanje Feature testova nad posebnom MySQL test bazom.

## Upgrade osnova

Paket se primenjuje preko potvrđene `v2.1.0-beta5` instalacije.

## Pre deploya

1. Napraviti postojeći ručni backup baze i aplikacije.
2. Kreirati privatni backup direktorijum izvan web root-a:

```bash
mkdir -p /home/icaffeco/backups/cms.ald1n.com
chmod 750 /home/icaffeco/backups/cms.ald1n.com
```

3. U produkcioni `.env` dodati:

```dotenv
BACKUP_PATH=/home/icaffeco/backups/cms.ald1n.com
BACKUP_MYSQLDUMP_BINARY=mysqldump
BACKUP_DAILY_RETENTION=7
BACKUP_WEEKLY_RETENTION=4
```

## Deploy

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:deployment-check --repair
php artisan app:scheduler-heartbeat
php artisan app:backup-doctor
php artisan app:system-health --snapshot
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

## Cron

Jedini obavezni cron ostaje:

```cron
* * * * * cd /home/icaffeco/cms.ald1n.com && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler sada pokreće heartbeat svakog minuta, dnevni backup u 02:30, nedeljni backup nedeljom u 03:10 i System Health snapshot u 07:45.

## MySQL Feature testovi

Kopirati `.env.testing.mysql.example` u `.env.testing`, upisati isključivo test kredencijale i generisati poseban APP_KEY:

```bash
cp .env.testing.mysql.example .env.testing
php artisan --env=testing key:generate
php artisan --env=testing app:test-database-doctor
php artisan --env=testing app:test-database-doctor --migrate-fresh
php artisan --env=testing test -c phpunit.mysql.xml --testsuite=Feature
```

Komanda odbija rad ako `APP_ENV` nije `testing`, naziv baze se ne završava sa `_test`, potvrda imena baze nije identična ili `ALLOW_TEST_DATABASE_RESET=true` nije postavljen.

## Nova migracija

`2026_07_23_000014_create_security_backup_health_beta6.php` dodaje:

- `backup_runs`;
- `system_health_snapshots`;
- `system_runtime_states`;
- `security_events`;
- `audit_logs.level` i `audit_logs.request_id`;
- dozvole `system.health`, `backups.manage`, `audit.export` i `security.view`.

Migracija je nedestruktivna.

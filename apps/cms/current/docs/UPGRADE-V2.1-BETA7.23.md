# Upgrade na v2.1.0-beta7.23

## Namena paketa

Beta7.23 je paket **Production Stabilization & QA**. Ne uvodi novi poslovni modul niti menja strukturu baze. Uvodi centralni release orkestrator koji objedinjuje postojeće deployment, health i module doctor komande.

## Šta je dodato

- nova Artisan komanda `app:release-check`;
- profili `quick`, `standard` i `full`;
- opcije za kontrolisani repair, render, health snapshot i strict režim;
- provera usklađenosti `config/app.php`, `VERSION`, `RELEASE-TAG` i `CHANGELOG.md`;
- kontrola da je svaka planirana Artisan komanda stvarno registrovana;
- zbirni PASS/WARN/FAIL rezultat i pouzdan exit kod za deployment skripte;
- atomski JSON izveštaji u `storage/app/release-check`;
- `latest.json` kao poslednji rezultat;
- dependency-free `bin/release-check-smoke.php`;
- PHPUnit contract i `--list` Feature testovi;
- Composer skripte `release:check`, `release:check:full` i `smoke:release`.

## Bezbednost izvršavanja

Podrazumevani release check ne izvršava poslovne akcije. Registry namerno ne prosleđuje opcije:

- `--dispatch`;
- `--create-test`;
- `--backfill`;
- `--run`;
- `--force`.

Zbog toga komanda ne šalje outbox poruke, ne kreira backup, ne pokreće automatizaciju i ne generiše masovni warranty backfill.

`--repair` se pokreće samo kada ga administrator eksplicitno navede. Tada se izvršavaju deployment migracije i seeder, Smart Product normalizacija, Product Variant agregati i Management Reports finansijski snapshot repair.

## Pre postavljanja

1. Napravi backup baze i aplikacije.
2. Potvrdi da je instalirana `v2.1.0-beta7.22.1`.
3. Ne prepisuj produkcioni `.env`.
4. Raspakuj UPGRADE paket preko postojeće aplikacije.

## Deploy komande

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:release-check --profile=full --repair --render --snapshot
php bin/release-check-smoke.php
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

Ako prvo želiš samo pregled planiranih akcija:

```bash
php artisan app:release-check --profile=full --repair --render --snapshot --list
```

## Tumačenje rezultata

- `READY FOR PRODUCTION`: deploy provera je čista;
- `READY WITH WARNINGS`: komanda vraća uspešan exit kod, ali upozorenja moraju da budu pregledana;
- `NOT READY`: jedna ili više provera nisu prošle, komanda vraća exit kod 1.

Za strogu proveru:

```bash
php artisan app:release-check --profile=full --render --strict
```

## Gde je izveštaj

```text
storage/app/release-check/latest.json
```

Apsolutne aplikacione i storage putanje su sanitizovane u JSON izlazu. Izveštaj ne sadrži `.env` vrednosti niti SMTP lozinku.

## Rollback

Paket nema migraciju baze, pa je rollback aplikacioni:

1. vrati fajlove iz `v2.1.0-beta7.22.1` paketa ili backup-a;
2. pokreni `php artisan optimize:clear`;
3. pokreni `php artisan app:deployment-check`;
4. pokreni `php artisan app:customer-portal-doctor --render`;
5. pokreni `php artisan optimize`.

# Upgrade na v2.1.0-rc1

Ovo je prvi Release Candidate za Ald1n CMS v2.1.0. Paket ne dodaje novi poslovni modul i nema novu migration datoteku. Fokus je na finalnom hardeningu, integritetu paketa, pristupnim pravima, migration stanju i verifikaciji backupa.

## Upgrade osnova

Upgrade paket je namenjen iskljucivo verziji:

```text
v2.1.0-beta7.24.1
```

Pre postavljanja potvrdi:

```bash
/usr/local/bin/php artisan app:version
```

## Pre postavljanja

1. Napravi rucni backup postojece verzije.
2. Sacuvaj poslednji `storage/app/release-check/latest.json`.
3. Proveri da hosting cron svakog minuta pokrece `schedule:run`.
4. Ne brisi `.env`, `storage/app`, korisnicke priloge ili backup direktorijum.

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-doctor
```

## Postavljanje

Raspakuj UPGRADE ZIP preko postojece aplikacije, zatim pokreni:

```bash
cd /home/icaffeco/cms.ald1n.com

composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr

/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear

# Nova RC1 komanda sada može detaljno da proveri pre-deploy backup.
/usr/local/bin/php artisan app:backup-verify --allow-older-version

# RC1 nema novu migration datoteku, ali pending stanje mora biti nula.
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan app:migrations-doctor --strict

# Kreiraj svez backup sa RC1 verzijom i proveri njegov sadrzaj.
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify

# Dependency-free regresije.
/usr/local/bin/php bin/rc-hardening-smoke.php
/usr/local/bin/php bin/release-check-smoke.php
/usr/local/bin/php bin/php-lint.php
/usr/local/bin/php bin/autoload-check.php
/usr/local/bin/php bin/static-check.php

# Finalni RC acceptance check bez automatskih repair promena.
/usr/local/bin/php artisan app:release-check \
  --profile=rc \
  --render \
  --strict \
  --snapshot \
  --report=rc1-acceptance.json

/usr/local/bin/php artisan optimize
```

Ocekivani zavrsetak:

```text
RELEASE CHECK: RC READY
```

## Nove read-only provere

- `app:release-integrity` proverava sve fajlove iz `MANIFEST-SHA256.txt`;
- `app:security-hardening-doctor` proverava production okruzenje, debug, HTTPS, session zastitu, `.env` dozvole i public direktorijum;
- `app:migrations-doctor --strict` proverava pending migracije, orphan DB zapise, SQL mode, charset i foreign keys;
- `app:access-control-doctor` proverava role, permissions, route middleware, superadmin i orphan pristupne zapise;
- `app:backup-verify` proverava manifest, SQL gzip, SHA-256 i privatne fajlove poslednjeg backupa.

Ove komande ne menjaju poslovne podatke i ne kreiraju backup.

## Vazna napomena o integritetu

`app:release-integrity` ce prijaviti FAIL ako je bilo koji release fajl rucno menjan na serveru. Ne menjaj fajl samo da bi provera prosla. Uporedi ga sa RC1 paketom i utvrdi da li je izmena legitimna ili posledica nepotpunog deploy-a.

## Rollback

RC1 nema novu migration datoteku, pa rollback koda znaci vracanje FULL paketa `v2.1.0-beta7.24.1`, zatim:

```bash
/usr/local/bin/php artisan optimize:clear
composer dump-autoload --optimize --strict-psr
/usr/local/bin/php artisan app:release-check --profile=full --render --strict --snapshot
/usr/local/bin/php artisan optimize
```

Ako je tokom RC rada doslo do poslovnih upisa, ne vracaj bazu na stariji backup bez posebne analize, jer bi time izgubio novije porudzbine, uplate, poruke i audit podatke.

# Upgrade v2.0.0-alpha2.1 -> v2.0.0-alpha2.2

## Obuhvat

Alpha2.2 uklanja tehničke/staging detalje sa login stranice, uvodi pouzdanije pronalaženje korisnika, kontrolisani oporavak korisnika iz legacy baze i dijagnostiku za prijavu, e-mail i cache.

Nema nove DB migracije.

## Instalacija

1. Napravi backup Laravel baze `icaffeco_lrvl`, projekta i `.env` fajla.
2. Raspakuj upgrade direktno preko `/home/icaffeco/cms.ald1n.com`.
3. Pokreni:

```bash
cd /home/icaffeco/cms.ald1n.com
composer install
php artisan optimize:clear
php artisan migrate:status
php artisan app:version
```

Verzija mora biti `2.0.0-alpha2.2`.

## Oporavak prijave

Prvo proveri nalog:

```bash
php artisan app:auth-doctor Ald1n
```

Ako korisnik postoji samo u legacy bazi, sledeća komanda će ponuditi kontrolisani uvoz tog korisnika i zatim promenu lozinke:

```bash
php artisan app:reset-user-password Ald1n
```

Komanda je case-insensitive i prihvata korisničko ime ili e-mail.

## E-mail

```bash
php artisan app:mail-doctor
```

Posle podešavanja `MAIL_*` vrednosti:

```bash
php artisan optimize:clear
php artisan app:send-test-mail TVOJ_EMAIL
```

## Cache

Ne uključuj Redis/Memcached dok test ne prođe:

```bash
php artisan app:cache-doctor --store=redis
php artisan app:cache-doctor --store=memcached
```

Detalji su u `docs/CACHE-AND-MAIL.md`.

## Testovi

```bash
composer test
php bin/static-check.php
php bin/domain-smoke.php
php bin/catalog-admin-smoke.php
php artisan app:deployment-check
```

Tek kada sve prođe:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize
```

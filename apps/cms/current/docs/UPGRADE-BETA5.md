# Upgrade v2.0.0-beta4 → v2.0.0-beta5

Beta5 rešava dve odvojene situacije prijavljene nakon beta4 deploya:

1. `bin/static-check.php` je pokrenut nad živom produkcionom instalacijom i pogrešno je tretirao obavezni produkcioni `.env`, runtime logove/sesije i Turnstile secret kao sadržaj sanitized ZIP-a;
2. nakon uspešne provere lozinke login je mogao vratiti HTTP 500 ako operativna šema/dozvole nisu kompletno migrirane ili ako neobavezni telemetry upis nije uspeo.

## Pre zamene fajlova

1. Napraviti backup aplikacije, `.env` fajla i baze `icaffeco_lrvl`.
2. Potvrditi da je instalirana verzija `2.0.0-beta4`.
3. Ne prepisivati `.env`, `storage/`, `public/storage` niti korisničke upload fajlove.

## Primena Upgrade ZIP-a

Raspakovati beta5 Upgrade paket preko beta4 aplikacije, zatim pokrenuti:

```bash
cd /home/icaffeco/cms.ald1n.com
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:deployment-check --repair
php artisan legacy:check
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan app:deployment-check
php artisan optimize
```

Beta5 uvodi repair migraciju `2026_07_23_000007_repair_production_schema_beta5.php`. Ona ponovo koristi idempotentne definicije operativnih tabela i produkcionih kolona, ali ne briše podatke.

## Static check režimi

Na produkcionoj instalaciji koristiti:

```bash
php bin/static-check.php
```

U tom režimu ZIP hygiene provere se prikazuju kao `INFO` i ne proveravaju produkcioni `.env`, logove, sesije ili secret vrednosti. Ti fajlovi pripadaju živom serveru.

Samo nad raspakovanim sanitized ZIP paketom u posebnom privremenom folderu koristiti:

```bash
php bin/static-check.php --package
```

Tada odsustvo `.env`, backup fajlova, runtime podataka i privatnog Turnstile ključa mora biti `PASS`.

## Login i dashboard fallback

- validna lozinka i aktivan nalog više se ne obaraju ako `last_login_at` ne može da se upiše;
- password rehash se pokušava bez prekidanja uspešne prijave;
- dashboard proverava kolone pre upita;
- neuspeli statistički segment se loguje i vraća nulte vrednosti, dok ostatak početne stranice ostaje dostupan;
- permission fallback sprečava da nepotpune permission tabele obore layout.

Ovo je availability zaštita, ne zamena za migracije. Posle prijave i dalje mora proći:

```bash
php artisan app:deployment-check
```

## Legacy grant upozorenje

Pronađeni grant:

```text
GRANT ALL PRIVILEGES ON `icaffeco_cms`.* TO `icaffeco_cms`@`localhost`
```

nije least-privilege read-only konfiguracija. Standardna provera ga prikazuje kao upozorenje samo kada su potvrđeni:

- `PASS legacy session read-only`;
- `PASS legacy SQL guard`.

Za stvarnu DB-level zaštitu u cPanel-u napraviti poseban korisnički nalog za `LEGACY_DB_USERNAME` i dodeliti mu samo `SELECT` i `SHOW VIEW` nad bazom `icaffeco_cms`. Ako hosting dozvoljava direktno upravljanje grantovima:

```sql
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'legacy_readonly_user'@'localhost';
GRANT SELECT, SHOW VIEW ON `icaffeco_cms`.* TO 'legacy_readonly_user'@'localhost';
FLUSH PRIVILEGES;
```

Ne izvršavati `REVOKE` nad nalogom koji koristi stari CMS ako taj stari CMS i dalje mora da upisuje u istu bazu. U tom slučaju obavezno napraviti poseban read-only korisnik samo za Laravel `legacy` konekciju.

## Smoke test nakon deploya

1. otvoriti `/login`;
2. prijaviti se kao SuperAdmin;
3. potvrditi da `/` vraća dashboard bez HTTP 500;
4. proveriti katalog i sva tri ranije prijavljena detail URL-a;
5. pokrenuti `php artisan app:deployment-check` i potvrditi `PASS operations tables` i `PASS system permissions`;
6. proveriti `storage/logs/laravel-*.log` samo ako neka stavka i dalje ne prolazi.

## Rollback

Vratiti backup beta4 fajlova i pokrenuti:

```bash
php artisan optimize:clear
php artisan optimize
```

Repair migracija je namerno nepovratna jer samo dopunjava nedostajuću produkcionu strukturu i ne briše podatke.

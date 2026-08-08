# Upgrade v2.0.0-beta5 → v2.0.0-beta6

Beta6 je post-login runtime hotfix. U beta5 su dashboard kontroler i permission modeli već imali fallback, ali je authenticated layout i dalje sadržao dva nezaštićena DB/service poziva. Pored toga, paket nije čuvao sve direktorijume potrebne file session/cache/view/log sistemu.

Bez produkcionog exception loga nije moguće utvrditi koji je problem aktiviran na konkretnom hostingu. Ovaj upgrade uklanja sve identifikovane tačke i dodaje komandu koja renderuje identičan post-login response iz CLI-ja.

## Pre zamene fajlova

1. Napraviti backup aplikacije, `.env` i baze `icaffeco_lrvl`.
2. Potvrditi da je instalirana verzija `2.0.0-beta5`.
3. Ne brisati niti prepisivati produkcioni `.env`, `storage/app/public`, `public/storage` i korisničke upload fajlove.
4. Raspakovati Upgrade ZIP preko postojeće instalacije, uključujući `.gitignore` placeholdere u runtime direktorijumima.

## Obavezne komande

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

Zameniti `Ald1n` stvarnim korisničkim imenom ili e-mailom.

## Šta `--repair` radi u beta6

- kreira `storage/framework/sessions`, `storage/framework/cache/data`, `storage/framework/views`, `storage/logs` i `bootstrap/cache` ako nedostaju;
- pokušava dozvole `0775` i stvarni write probe u svakom direktorijumu;
- pokreće sve Laravel migracije sa `--force`;
- primenjuje `2026_07_23_000008_repair_authenticated_runtime_beta6.php`;
- pokreće `CoreAccessSeeder` i kada je zaseban migracioni korak prijavio problem;
- izvršava `view:clear` i `view:cache` kako bi se greške u kompletnom Blade stablu otkrile pre browser prijave;
- proverava core login/settings šemu, operativne tabele/kolone i sistemske dozvole.

## Novi runtime middleware

`EnsureRuntimeDirectories` je globalno postavljen pre session/cache middleware-a. Time se direktorijumi popravljaju pre nego što login throttle ili session handler pokušaju upis. Kada server korisnik nema pravo da ih kreira ili menja, response je HTTP 503 sa listom relativnih direktorijuma i komandom:

```bash
php artisan app:deployment-check --repair
```

Ako ni CLI repair ne može da ih učini upisivim, podesiti vlasnika/dozvole kroz hosting File Manager ili podršku hostinga. PHP/FPM korisnik mora imati write pristup navedenim direktorijumima.

## `app:auth-doctor --render-dashboard`

Komanda proverava:

- file session/cache/view/log direktorijume;
- Laravel i legacy DB konekcije;
- ciljani Laravel nalog, status i hash format;
- dostupnost role podatka;
- kompletno renderovanje `DashboardController`, `dashboard.index`, view composer-a i `layouts.app`.

Ne prikazuje lozinku niti hash. Primer:

```bash
php artisan app:auth-doctor Ald1n --render-dashboard
```

Ako rezultat nije PASS, kopirati samo FAIL liniju sa exception klasom i porukom. Ne slati `.env`, lozinke, APP_KEY, Turnstile secret ni DB credentials.

## Beta5 layout problem koji je uklonjen

Beta5 footer je posle uspešnog fallback view composer-a ponovo radio:

```php
app(SettingsService::class)->renderTemplate(...)
```

Time je nedostupna `settings` tabela ili file cache mogao ponovo izazvati HTTP 500. Beta6 footer koristi samo već pripremljene vrednosti i čisti `strtr`, bez DB/service poziva. Direktan lazy role upit je zamenjen odbrambenim `roleName()` helper-om.

## `remember_token`

Ako je korisnik označio „Zapamti me“, a `users.remember_token` nedostaje, beta5 je mogao pasti pri `Auth::login`. Beta6 proverava kolonu pre persistent login-a i nastavlja sa običnom session prijavom. Repair migracija potom dodaje kolonu.

## Static check

Na produkcionoj instalaciji:

```bash
php bin/static-check.php
```

Nad raspakovanim sanitized paketom:

```bash
php bin/static-check.php --package
```

Package režim dozvoljava `.gitignore` placeholdere, ali odbija stvarne log/session/cache/view fajlove.

## Legacy grant upozorenje

`ALL PRIVILEGES` nad `icaffeco_cms` ostaje upozorenje, ne uzrok post-login 500 greške. Runtime read-only session i SQL guard ostaju obavezni. Za potpuni least-privilege napraviti zaseban legacy korisnik sa `SELECT` i `SHOW VIEW`.

## Rollback

Vratiti backup beta5 aplikacionih fajlova i pokrenuti:

```bash
php artisan optimize:clear
php artisan optimize
```

Beta6 repair migracija je nedestruktivna i nema destructive rollback.

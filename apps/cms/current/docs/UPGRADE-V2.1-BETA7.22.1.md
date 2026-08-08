# Nadogradnja na v2.1.0-beta7.22.1

Ovaj hotfix se postavlja preko **v2.1.0-beta7.22** i ne uvodi novu migraciju baze.

## 1. Uzrok Customer Portal doctor greške

Web zahtevi prolaze kroz Laravel `ShareErrorsFromSession` middleware, koji svim Blade view-ovima deli `$errors` (`ViewErrorBag`). Komanda `app:customer-portal-doctor --render` je view renderovala direktno iz CLI procesa, bez tog middleware-a.

`resources/views/layouts/app.blade.php` je zato došao do `$errors->any()` i prekinuo render porukom `Undefined variable $errors`.

Hotfix:

- prosleđuje prazan `ViewErrorBag` iz `CustomerPortalDoctorCommand`;
- layout dodatno proverava `isset($errors)` radi bezbednih CLI, PDF i drugih direktnih rendera.

## 2. Uzrok CSS greške na /admin/reports

Management analytics kartice koristile su `var(--panel-bg,#fff)` i `var(--border-color,#dce2ea)`, ali aktivna tema definiše `--panel` i `--line`. Pošto stare promenljive nisu postojale, browser je u tamnoj temi koristio fallback `#fff`, dok je tekst nasledio svetlu boju iz tamne teme. Rezultat su bile bele kartice sa gotovo nevidljivim sadržajem.

Hotfix:

- prebacuje management kartice i grafikone na kanonske theme varijable;
- dodaje kompatibilne aliase za starije nazive promenljivih;
- ispravlja iste reference u Service Parts, Product Variants i Catalog prikazu;
- dodaje automatsku proveru nedefinisanih CSS promenljivih.

## 3. Postavljanje

```bash
php artisan optimize:clear
php artisan view:clear
php artisan app:customer-portal-doctor --repair --render
php bin/customer-portal-smoke.php
php bin/theme-css-smoke.php
php bin/php-lint.php
php bin/static-check.php
php artisan optimize
```

Ako migracija iz beta7.22 ranije nije izvršena, pre provera pokrenuti:

```bash
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
```

Posle postavljanja uraditi hard refresh (`Ctrl+F5`) na `/admin/reports`.

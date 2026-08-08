# Upgrade v2.1.0-beta1.2 → v2.1.0-beta1.3

Ovaj patch ispravlja preostali HTTP 500 na `/admin/reports` u render fazi. Baza i SQL mogu biti potpuno ispravni, a da Blade ili authenticated layout ipak padnu nakon što kontroler vrati `View`. Beta1.3 zato renderuje kompletan odgovor unutar zaštićenog toka.

## Postupak

```bash
cd /home/icaffeco/cms.ald1n.com
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:reports-doctor --repair
php artisan app:deployment-check --repair
php artisan app:reports-doctor --render
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

Očekivani rezultat:

```text
PASS Sve potrebne reports tabele i kolone postoje.
PASS Reports SQL upiti su uspešni.
PASS Reports kontroler, Blade i kompletan authenticated layout su uspešno renderovani.
```

Nova migracija baze nije dodata. Postojeća repair migracija `000010` ostaje bezbedna i idempotentna.

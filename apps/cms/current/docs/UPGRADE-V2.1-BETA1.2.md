# Upgrade v2.1.0-beta1.1 → v2.1.0-beta1.2

Ovaj patch rešava HTTP 500 na `/admin/reports` i popravlja delimično primenjenu reports/documents šemu.

## Postupak

```bash
cd /home/icaffeco/cms.ald1n.com
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:reports-doctor --repair
php artisan app:deployment-check --repair
php artisan app:reports-doctor
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

Očekivani završni rezultat:

```text
PASS Sve potrebne reports tabele i kolone postoje.
PASS Reports SQL upiti su uspešni.
```

Ako komanda prijavi `FAIL Reports SQL upit nije uspeo`, pošaljite samo tu liniju i exception poruku bez `.env` ili kredencijala.

## Bezbednost

Migracija `000010` je nedestruktivna. Ne briše porudžbine, stavke, provizije ni izdate dokumente.

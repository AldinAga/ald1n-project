# Upgrade v2.0.0-alpha2.2 -> v2.0.0-alpha2.2.1

Ovo je hitna kompatibilnosna zakrpa za fatalnu grešku prilikom Composer `post-autoload-dump` / Artisan bootstrap-a.

## Uzrok

`AuthDoctorCommand` je definisao privatnu metodu `fail()`, dok roditeljska Laravel/Symfony Console klasa već ima javnu metodu istog imena. PHP 8.4 zbog uže vidljivosti prekida učitavanje klase fatalnom greškom.

## Instalacija

Raspakovati upgrade preko:

```text
/home/icaffeco/cms.ald1n.com
```

Zatim pokrenuti:

```bash
cd /home/icaffeco/cms.ald1n.com
composer dump-autoload --optimize
php artisan optimize:clear
php artisan app:version
php artisan app:auth-doctor
composer test
php bin/static-check.php
```

Očekivana verzija je `2.0.0-alpha2.2.1`.

## Baza i konfiguracija

Nema nove migracije baze. Ne menjati produkcioni `.env`.

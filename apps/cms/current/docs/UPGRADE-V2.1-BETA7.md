# Upgrade na v2.1.0-beta7

## Svrha

Beta7 uvodi profesionalnu galeriju na pojedinačnoj stranici proizvoda: thumbnail listanje, fullscreen lightbox, zoom, tastaturnu navigaciju i swipe na mobilnim uređajima.

## Upgrade osnova

Paket se primenjuje preko potvrđene `v2.1.0-beta6` instalacije.

## Deploy

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:deployment-check
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

Nakon deploya izvršiti `Ctrl + F5` ili očistiti browser cache. CSS koristi `filemtime` cache-busting, ali hard refresh odmah uklanja eventualno sačuvani prethodni stil.

## Baza

Nova migracija nije potrebna. Postojeće fotografije i redosled ostaju nepromenjeni.

## Kompatibilnost

Galerija koristi samo ugrađeni JavaScript i CSS. Nisu dodate eksterne biblioteke, CDN resursi, Redis niti novi queue zahtevi.

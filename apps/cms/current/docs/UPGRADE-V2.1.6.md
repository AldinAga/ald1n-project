# Nadogradnja na v2.1.6 — Performance & Data Quality

Polazna verzija: **v2.1.5**

## Pre instalacije

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

## Instalacija

Raspakuj UPGRADE paket preko postojeće aplikacije, bez brisanja `.env`, `storage` i korisničkih fajlova.

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
/usr/local/bin/php artisan route:clear
/usr/local/bin/php artisan migrate --force
```

## Provere i bezbedna popravka

```bash
/usr/local/bin/php bin/cms-v2.1.6-smoke.php
/usr/local/bin/php artisan app:performance-doctor --strict --json=v2.1.6-performance.json
/usr/local/bin/php artisan app:data-quality-doctor --repair --json=v2.1.6-data-quality.json
/usr/local/bin/php artisan app:cms-v2-1-6-doctor --render --repair --strict
```

`--repair` primenjuje samo nedestruktivne popravke: usklađivanje kategorija, uklanjanje zastarelih specifikacionih veza, preračunavanje izvedenih vrednosti i normalizaciju glavnih slika/podrazumevanih varijanti. Ne briše artikle, slike ni poslovnu istoriju.

## Završna Stable provera

Najpre napravi svež backup verzije 2.1.6, zatim:

```bash
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
/usr/local/bin/php artisan app:release-check \
  --profile=stable \
  --render \
  --strict \
  --snapshot \
  --repair \
  --report=v2.1.6-stable-acceptance.json
/usr/local/bin/php artisan optimize
```

Očekivano: `RELEASE CHECK: STABLE READY`.

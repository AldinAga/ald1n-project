# Cache, session i e-mail konfiguracija — v2.0.0-beta6

## Produkcioni runtime

Redis i Memcached nisu deo Production Orders & Inventory izdanja. Preporučena konfiguracija je:

```dotenv
SESSION_DRIVER=file
CACHE_STORE=file
CACHE_LIMITER=file
QUEUE_CONNECTION=sync
```

Obavezni upisivi direktorijumi:

```text
storage/framework/sessions
storage/framework/cache/data
storage/framework/views
storage/logs
bootstrap/cache
```

Beta6 Full i Upgrade ZIP sadrže `.gitignore` placeholdere kako direktorijumi ne bi nestali pri arhiviranju. Stvarni session, cache, compiled view i log fajlovi nisu deo sanitized paketa.

Popravka i provera:

```bash
php artisan optimize:clear
php artisan app:deployment-check --repair
php artisan app:cache-doctor --store=file
```

Globalni runtime middleware pre svakog HTTP zahteva proverava direktorijume. Ako nisu upisivi, vraća HTTP 503 sa repair instrukcijom pre nego što session ili login throttle izazovu neobrađenu grešku.

Database cache se može koristiti samo uz eksplicitno izabran store i odgovarajuću tabelu. Redis vrednosti iz ranijih Alpha dokumenata ne vraćati u `.env`.

## E-mail

Za sendmail:

```dotenv
MAIL_MAILER=sendmail
MAIL_SENDMAIL_PATH="/usr/sbin/sendmail -bs -i"
MAIL_FROM_ADDRESS=noreply@ald1n.com
MAIL_FROM_NAME="Ald1n CMS"
```

Za SMTP koristiti tačne podatke naloga dobijene od hostinga. Lozinka se unosi samo u serverski `.env`.

Provera:

```bash
php artisan optimize:clear
php artisan app:mail-doctor
php artisan app:send-test-mail TVOJ_EMAIL
```

## Beta4 scheduler i obaveštenja

Operativni scan koristi file cache lock i sync notification delivery. Redis i queue worker nisu potrebni. In-app kanal je podrazumevan; e-mail zahteva oba uslova:

1. `OPERATIONAL_EMAIL_NOTIFICATIONS=true` u produkcionom `.env`;
2. korisnik je uključio e-mail na stranici Moj nalog.

Cron poziva samo Laravel scheduler:

```cron
* * * * * cd /home/icaffeco/cms.ald1n.com && php artisan schedule:run >> /dev/null 2>&1
```

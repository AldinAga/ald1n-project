# Upgrade v2.0.0-alpha2 -> v2.0.0-alpha2.1

## 1. Backup

Sačuvaj:

- bazu `icaffeco_lrvl`;
- `/home/icaffeco/cms.ald1n.com`;
- serverski `.env`.

Stari CMS i baza `icaffeco_cms` se ne menjaju.

## 2. Kopiranje

Raspakuj upgrade paket direktno preko:

```text
/home/icaffeco/cms.ald1n.com
```

Paket ne sadrži `.env`, `vendor`, uploadove, logove, sesije ni cache.

## 3. Komande

```bash
cd /home/icaffeco/cms.ald1n.com
composer install
php artisan optimize:clear
php artisan migrate:status
php artisan route:list | grep -E 'forgot-password|reset-password|login'
```

Alpha2.1 nema novu DB migraciju. Postojeća tabela `password_reset_tokens` mora biti prikazana kao postojeća kroz deployment check.

## 4. Hitno vraćanje prijave

Pre SMTP konfiguracije možeš odmah postaviti novu lozinku postojećem korisniku:

```bash
php artisan app:reset-user-password Ald1n
```

Komanda bezbedno traži novu lozinku dva puta i ne upisuje je u shell history.

## 5. SMTP

U `.env` dodaj stvarne podatke mailbox-a koji šalje reset poruke:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=
MAIL_HOST=SMTP_HOST
MAIL_PORT=587
MAIL_USERNAME=SMTP_KORISNIK
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@ald1n.com
MAIL_FROM_NAME="Ald1n CMS"
```

Za SMTPS/port 465 postavi `MAIL_SCHEME=smtps` i odgovarajući port, prema parametrima mailbox provajdera.

Posle izmene:

```bash
php artisan optimize:clear
php artisan app:send-test-mail TVOJ_EMAIL
```

## 6. Testovi

```bash
composer test
php bin/static-check.php
php bin/domain-smoke.php
php bin/catalog-admin-smoke.php
php artisan app:deployment-check
```

## 7. Ručna provera

1. Otvori `/login` i proveri link **Zaboravili ste lozinku?**.
2. Pošalji zahtev za e-mail postojećeg korisnika.
3. Proveri da poruka stiže i da link otvara `/reset-password/{token}`.
4. Postavi novu lozinku.
5. Prijavi se novom lozinkom.
6. Potvrdi da isti reset link više ne radi.

## 8. Produkcijska optimizacija

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize
```

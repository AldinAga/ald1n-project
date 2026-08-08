# Upgrade na v2.1.0-beta7.5

Ova verzija ispravlja izdavanje PDF potvrde porudžbine i ručno evidentiranje uplata na instalacijama na kojima je finansijska šema delimično primenjena.

## Šta je ispravljeno

- PDF potvrda porudžbine radi i kada polje `documents_company_name` nije posebno popunjeno. Koristi se `site_name`, a zatim naziv Laravel aplikacije.
- Dodata je nedestruktivna repair migracija za:
  - `document_counters`;
  - `order_documents`;
  - `order_payments`;
  - `orders.payment_state`;
  - `orders.paid_total_rsd`;
  - `orders.payment_due_at`;
  - `orders.payment_verified_at`.
- Obnavljaju se potrebne dozvole za dokumente i uplate.
- Doctor komanda sada proverava i PDF dokument šemu.

## Deploy

Napraviti backup baze, aplikacije, `.env` fajla i `storage` direktorijuma, zatim raspakovati paket preko verzije v2.1.0-beta7.4.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan app:payments-inventory-doctor --repair --render
php artisan app:reports-doctor --repair --render
php artisan app:deployment-check --repair
php artisan optimize
```

## Provera

1. Kreirati test porudžbinu i otvoriti **Potvrda PDF**.
2. Otvoriti porudžbinu kao dodeljeni Administrator.
3. U sekciji **Uplate i saldo** evidentirati test uplatu.
4. Proveriti da se stavka pojavila u tabeli i da su `Verifikovano`, `Preostalo` i status salda ažurirani.

Migracija ne briše postojeće dokumente, uplate niti finansijsku istoriju.

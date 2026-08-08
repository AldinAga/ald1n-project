# Upgrade na v2.1.0-beta7.6

Ovo je aplikacioni hotfix za slučaj kada klik na **Potvrda PDF** ili **Evidentiraj stavku** kratko učita stranicu i zatim vrati korisnika na isti prikaz bez rezultata.

## Uzrok

Administratorski ekran je prikazivao PDF i payment akcije i za lokalno uvezene porudžbine, ali su `OrderDocumentService` i `OrderPaymentService` odbijali svaki zapis sa `source_system = legacy`. Laravel je validacionu grešku pretvarao u redirect nazad, pa je izgledalo kao da dugme ne radi.

## Ispravka

- PDF potvrda, predračun i račun rade i za lokalno uvezene porudžbine.
- Ručna uplata/refundacija radi i za lokalno uvezene porudžbine.
- Završene/poslate porudžbine mogu dobiti PDF i payment stavke.
- Otkazane porudžbine i dalje ne prihvataju novu uplatu.
- Legacy baza ostaje strogo read-only; menjaju se samo lokalne Laravel tabele `order_documents`, `order_payments` i lokalni payment saldo porudžbine.
- Operacije lagera, rezervacija i povrata za legacy porudžbine ostaju blokirane.

## Deploy

Ova verzija nema novu migraciju baze. Raspakovati je preko v2.1.0-beta7.5 i pokrenuti:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:deployment-check
php artisan optimize
```

Ako migracija iz v2.1.0-beta7.5 još nije primenjena, dodatno pokrenuti:

```bash
php artisan migrate --force
php artisan app:payments-inventory-doctor --repair --render
php artisan app:reports-doctor --repair --render
```

## Provera

1. Otvoriti stariju ili uvezenu završenu porudžbinu.
2. Kliknuti **Potvrda PDF** i proveriti da browser otvara PDF.
3. U sekciji **Uplate i saldo** evidentirati test uplatu.
4. Proveriti novu stavku, `Verifikovano`, `Preostalo` i status salda.

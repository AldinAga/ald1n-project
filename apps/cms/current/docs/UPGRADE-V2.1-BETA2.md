# Upgrade v2.1.0-beta1.3 → v2.1.0-beta2

## Pre primene

1. Napraviti backup baze `icaffeco_lrvl`, aplikacije, `.env` i upload sadržaja.
2. Potvrditi da je instalirana `v2.1.0-beta1.3`.
3. Ne prepisivati `.env`, `storage/app/public`, `public/storage` i korisničke upload fajlove.

## Primena

Raspakovati Upgrade ZIP preko postojeće aplikacije i pokrenuti komande iz `DEPLOY-COMMANDS.txt`.

Najvažnija komanda je:

```bash
php artisan app:operations-doctor --repair --render
```

Očekivani završetak:

```text
PASS Operational Orders & Commissions šema je kompletna.
PASS Operativne dozvole postoje.
PASS Provizije, scope i notifications upiti su uspešni.
PASS Administratorska stranica provizija je uspešno renderovana.
```

## Nova šema

Migracija `2026_07_23_000011_create_operational_orders_commissions_beta2.php` dodaje:

- operativne kolone na `orders`;
- payment batch podatke na `order_commissions`;
- `commission_status_history.metadata_json`;
- `order_internal_notes`;
- `order_assignments`;
- `commission_payment_batches`;
- standardnu Laravel `notifications` tabelu;
- dozvole `commissions.view_own`, `orders.reassign`, `orders.internal_notes`, `notifications.view`.

Migracija je nedestruktivna i nema destructive rollback.

## Smoke test

1. Korisnik kreira porudžbinu i vidi je u „Moje porudžbine“.
2. Dodeljeni Administrator dobija obaveštenje i preuzima porudžbinu.
3. Administrator dodaje internu napomenu; korisnik je ne vidi.
4. Administrator postavlja rokove i tracking; korisnik dobija obaveštenje.
5. Provizija se odobrava i isplaćuje sa referencom.
6. Korisnik vidi status u „Moje provizije“.
7. SuperAdministrator ponovo dodeljuje test porudžbinu drugom Administratoru.
8. CSV/PDF izvoz provizija se otvara bez greške.

## Rollback

Vratiti backup aplikacije i baze. Zbog očuvanja audit istorije migracija nema automatski destructive `down()`.

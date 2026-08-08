# Phase 3A — Cart + Create Order

## Backend contract potvrđen 2026-08-07

Produkcioni Laravel ima:

- `GET /api/v1/orders/options` uz `orders.create` permission;
- `POST /api/v1/orders` uz `orders.create` permission i `throttle:orders`;
- `Idempotency-Key` podršku kroz `StoreOrderRequest`, `OrderService` i `IdempotencyService`;
- validaciju varijante, duplikata konfiguracije, količine i bankovnog računa.

## Idempotency pravilo klijenta

Prvi submit koristi `idempotency_key` dobijen iz `/orders/options`. Ako isti payload dobije timeout/mrežnu grešku, ponovni submit koristi isti ključ. Ako korisnik izmeni payload nakon neuspelog pokušaja, klijent generiše novi UUID pre narednog submit-a.

## Cart pravilo

Ista kombinacija `product_id + product_variant_id` postoji samo jednom u korpi. Ponovno dodavanje povećava količinu do trenutnog lokalno poznatog lagera. Server ostaje autoritet i ponovo proverava lager/cenu/kurs unutar transakcije pri kreiranju porudžbine.

## Ograničenje v0.2.0

Korpa je session-local/in-memory. Ne uvodi se nova storage dependency dok real-device Phase 3A tok ne prođe acceptance test.

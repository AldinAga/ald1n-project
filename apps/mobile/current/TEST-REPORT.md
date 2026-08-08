# Ald1n Mobile v0.2.0 Phase 3A — test report

Datum: 2026-08-07

## Potvrđeno pre izrade patch-a

Produkcioni backend preflight potvrđuje:

- `GET /api/v1/orders/options`;
- `POST /api/v1/orders`;
- `orders.create` permission middleware;
- `throttle:orders` na create endpoint-u;
- `StoreOrderRequest` prima `Idempotency-Key` header;
- `OrderService` koristi `IdempotencyService`.

## Statička provera patch-a

`npm run validate` ekvivalent je pokrenut nad v0.2.0 source-om koristeći TypeScript parser iz radnog okruženja.

Rezultat:

```text
37 TypeScript/TSX fajlova prolazi sintaksnu proveru
159 lokalnih @/ importa je razrešeno
cart/checkout/idempotency provere PASS
Ukupno FAIL: 0
```

Canonical `package-lock.json` ostaje prenosiv i nema `nodevenv`/`/home/icaffeco` resolved putanje. Dependency verzije nisu menjane:

```text
react-native-reanimated 4.5.1
react-native-worklets   0.10.1
expo-updates            ~57.0.12
```

## Nije moguće izvršiti u artefact radnom okruženju

Puni `npm ci` i `tsc --noEmit` nisu završeni u ovom radnom okruženju jer interni npm gateway nije imao `zod@4.4.3` tarball i vratio je HTTP 404. To nije greška projekta niti je lockfile menjan zbog toga.

Zato su obavezni server-side acceptance koraci:

```text
npm ci PASS
typecheck PASS
validate PASS
Expo Doctor 20/20
EAS Android preview build PASS
real-device Cart -> Checkout -> Create Order PASS
```

Tačne komande su u `PHASE3A-APPLY-AND-TEST.md`.

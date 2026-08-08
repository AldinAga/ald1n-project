# Arhitektura mobilnog klijenta

```text
src/app                 Expo Router ekrani i layout-i
src/components          reusable UI i domain kartice
src/constants           design tokens
src/features/auth       sesija, bootstrap i permission lifecycle
src/features/device     registracija mobilne instalacije
src/lib/api             HTTP client i endpoint wrapperi
src/lib/storage.ts      SecureStore adapter
src/types/api.ts        TypeScript ugovor sa Laravel API-jem
```

## Auth tok

1. `POST /auth/token` bez Authorization headera.
2. Token se čuva u SecureStore-u.
3. `GET /bootstrap` učitava korisnika, dozvole i feature flagove.
4. Svaki zahtev čita aktuelni token iz SecureStore-a.
5. HTTP 401 briše lokalnu sesiju i Query cache.
6. `DELETE /auth/token` opoziva trenutni Sanctum token i vezani uređaj.

## Device tok

- installation UUID se generiše jednom kroz `expo-crypto`;
- čuva se u SecureStore-u;
- šalju se platforma, model, app verzija, build, locale i timezone;
- push token se u Fazi 2 ne traži i `notifications_enabled` ostaje `false`;
- backend uređaj se osvežava idempotentno po installation UUID-u.

# Ald1n CMS Laravel v2.2.0

**Mobile API Foundation** izdanje priprema postojeći Laravel backend za zasebnu React Native / Expo aplikaciju, bez prepisivanja stabilnog Blade frontenda.

## Glavne mogućnosti

- verzionisan `/api/v1` bootstrap sa korisnikom, dozvolama, feature flagovima i mobile app verzijama;
- mobilni profil, promena lozinke i notification preference;
- registracija, osvežavanje i opoziv Android/iOS instalacija;
- šifarnici i filteri kataloga;
- opcije za kreiranje porudžbine;
- lista i read/read-all tok poslovnih obaveštenja;
- jedinstveni API error envelope sa `code`, `errors` i `request_id`;
- OpenAPI 3.1 ugovor u `docs/openapi.yaml`;
- database queue tabele kao osnova za buduću push i background obradu.

## Važna granica izdanja

v2.2.0 bezbedno registruje push tokene, ali produkciona Expo/FCM/APNs isporuka još nije uključena. `MOBILE_PUSH_ENABLED` treba da ostane `false` do naredne push faze.

## Provera

```bash
php bin/cms-v2.2.0-smoke.php
php artisan app:cms-v2-2-0-doctor --strict
php artisan route:list --path=api/v1
```

Detaljno postavljanje: `docs/UPGRADE-V2.2.0.md`.

Smernice za novi Expo klijent: `docs/MOBILE-CLIENT-FOUNDATION.md`.


## Production readiness hotfix

Za već instaliranu v2.2.0 verziju prvo primeni `PATCH-INSTRUCTIONS-V2.2.0-READINESS.txt`. Hotfix uklanja false-positive disk CRITICAL na velikom shared filesystemu i dokumentuje tačan redosled queue, backup i Stable provera.

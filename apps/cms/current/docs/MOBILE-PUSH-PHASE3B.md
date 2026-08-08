# Mobile Push Phase 3B

Phase 3B uvodi pouzdan Expo push outbox za postojeće `mobile_devices` registracije.

## Bezbedan tok

1. Mobilni klijent eksplicitno traži notification permission i registruje Expo push token.
2. Backend čuva token enkriptovan, a hash koristi za deduplikaciju uređaja.
3. `OperationalNotificationService` poštuje postojeće category preference i `push_enabled`.
4. Kada je `MOBILE_PUSH_ENABLED=true`, push poruka se upisuje u `mobile_push_outbox` unutar istog poslovnog DB konteksta.
5. Scheduler svake minute pokreće `app:mobile-push-dispatch`.
6. Dispatcher šalje Expo ticket i proverava receipt nakon konfigurisanog delay-a (podrazumevano 15 minuta).
7. `DeviceNotRegistered` automatski uklanja zastareli token sa uređaja.
8. Privremeni HTTP 429/5xx i `MessageRateExceeded` koriste kontrolisani retry/backoff.

## Aktivacija

Pre aktivacije ostaviti:

```env
MOBILE_PUSH_ENABLED=false
MOBILE_PUSH_PROVIDER=expo
```

Nakon migracije, Firebase/FCM V1 podešavanja, real-device registracije i provere scheduler-a:

```env
MOBILE_PUSH_ENABLED=true
MOBILE_PUSH_PROVIDER=expo
```

Zatim:

```bash
php artisan optimize:clear
php artisan app:mobile-push-doctor --strict
php artisan schedule:list | grep mobile-push
```

`MOBILE_PUSH_EXPO_ACCESS_TOKEN` je opcion i koristi se samo ako je u Expo projektu uključena dodatna push access-token zaštita. Ne stavljati ga u source.

# Ald1n CMS — Phase 3B Push Delivery candidate test report

Datum: 2026-08-07

## Status

Candidate je pripremljen nad v2.2.0 Mobile API Foundation source baseline-om. Nije namenjen direktnom produkcionom raspakivanju dok `PUSH-BACKEND-PREFLIGHT.sh` ne potvrdi da postojeći produkcioni fajlovi koje patch menja imaju očekivane SHA-256 vrednosti ili dok se ne uradi kontrolisani merge nad novijom produkcionom kopijom.

## Dodato

- `mobile_push_outbox` DB tabela;
- encrypted device token se čita samo u trenutku slanja, token se ne kopira u outbox;
- `MobilePushOutboxService` za enqueue po aktivnom uređaju;
- `ExpoPushTransport` za Expo push tickets i receipts;
- `MobilePushDispatcher` sa kontrolisanim retry/backoff pravilima;
- `DeviceNotRegistered` automatski uklanja zastareli device token;
- receipt provera nakon podrazumevanih 15 minuta;
- scheduler `app:mobile-push-dispatch --limit=100` svake minute;
- `app:mobile-push-doctor` dijagnostika;
- optional `MOBILE_PUSH_EXPO_ACCESS_TOKEN` podrška;
- Unit/Feature testovi za osnovni push delivery tok.

## Pouzdanost

HTTP 429/5xx i `MessageRateExceeded` se tretiraju kao privremeni problemi i koriste backoff. Terminalne ticket/receipt greške se beleže kao failed. Stari `processing` redovi se ne šalju automatski ponovo nakon prekida procesa, kako ne bi nastao mogući duplikat posle nepoznatog stanja mrežnog slanja.

Expo receipt `ok` u ovom modelu znači da je FCM/APNs prihvatio poruku; ne predstavlja potvrdu da ju je fizički uređaj prikazao korisniku.

## Izvršene provere u artifact okruženju

- `php -l` nad svim novim/izmenjenim PHP fajlovima: PASS;
- candidate patch je generisan kao diff prema v2.2.0 baseline-u;
- precondition SHA-256 manifest je uključen za svaki postojeći fajl koji patch menja.

Puni Laravel test suite nije izvršen u artifact okruženju jer sanitizovani source nema `vendor/`. Pre deployment-a na serveru obavezno pokrenuti dostupni test suite sa dev dependency-jima ili najmanje odgovarajuće Phase 3B Feature/Unit testove u development kopiji.

## Aktivacija

Patch se prvo instalira sa `MOBILE_PUSH_ENABLED=false`. Tek kada migracija, scheduler, Firebase/FCM V1, mobilna v0.3 registracija i push doctor prođu, vrednost se menja na `true`.

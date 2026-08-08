# Ald1n Mobile v0.3.0 — Phase 3B

Expo / React Native / TypeScript klijent za **Ald1n CMS Laravel v2.2.0+ Mobile API**.

## Uključeno

- sve iz v0.2.0 Phase 3A: auth, bootstrap, katalog, korpa, checkout, create order, orders, notifications, account i devices;
- eksplicitni push permission onboarding;
- Expo push token registracija sa EAS `projectId`;
- Android `business-updates` notification channel;
- silent token refresh kada je permission već odobren;
- ekran `Obaveštenja i push` sa globalnim kanalima i poslovnim kategorijama;
- per-device uključivanje/isključivanje push registracije;
- deep-link ponašanje za push ka porudžbini i notification inbox-u;
- EAS/Firebase priprema bez čuvanja private FCM service-account ključa u source-u.

## Version

```text
app            0.3.0
Expo SDK       57
React Native   0.86.2
API            https://cms.ald1n.com/api/v1
```

## Android FCM

Za stvarni Android push `google-services.json` mora pripadati tačnom application id-u builda. Preview koristi `com.ald1n.mobile.preview`. `app.config.js` automatski uključuje `./google-services.json` kada postoji. FCM V1 service-account private key se čuva u EAS Credentials, ne u source-u.

## Validacija

```bash
npm run typecheck
npm run validate
npm run doctor -- --verbose
```

Detalji: `docs/PHASE-3B-SCOPE.md` i `PHASE3B-APPLY-AND-TEST.md`.

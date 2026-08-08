# Changelog

## 0.3.0 — 2026-08-07

### Added

- Phase 3B push permission onboarding bez automatskog prompta pri startu;
- Expo push token registracija sa EAS projectId;
- Android `business-updates` notification channel;
- `Obaveštenja i push` ekran za device registraciju, kanale i poslovne kategorije;
- `PATCH /devices/{id}` i `PUT /me/notification-preferences` klijentski wrapperi;
- silent push token refresh kada je OS permission već odobren;
- foreground notification handler i tap/deep-link obrada;
- order push otvara detalj porudžbine, ostali događaji notification inbox.

### Hardened

- obična device heartbeat registracija više ne postavlja `notifications_enabled=false` na svakom startu;
- push token se ne loguje i ne čuva u lokalnom storage-u;
- cold-start notification response se čisti nakon obrade da se stari deep link ne ponavlja;
- `google-services.json` je uslovno povezan sa Expo Android configom samo kada postoji.

### Changed

- aplikaciona verzija podignuta na `0.3.0`;
- `X-Mobile-Client` podignut na `ald1n-mobile/0.3.0`;
- dependency tree ostaje nepromenjen u odnosu na v0.2.0.

## 0.2.0 — 2026-08-07

### Added

- Phase 3A lokalna korpa sa proizvodom, varijantom i količinom;
- ekran korpe sa izmenom količine i uklanjanjem stavki;
- checkout preko `GET /api/v1/orders/options`;
- shipping defaults, način plaćanja, bankovni račun i odgovorno lice;
- `POST /api/v1/orders` sa `Idempotency-Key` headerom;
- bezbedan retry istog checkout payload-a sa istim idempotency ključem;
- automatsko pražnjenje korpe tek nakon potvrđenog uspešnog kreiranja porudžbine;
- navigacija iz kataloga/detalja/početne do korpe.

### Changed

- aplikaciona verzija podignuta na `0.2.0`;
- `X-Mobile-Client` podignut na `ald1n-mobile/0.2.0`;
- uklonjen lokalni `android.versionCode` iz Expo configa jer EAS koristi remote version source;
- preview EAS profil dobio `autoIncrement: true` za novi Android build broj pri svakom preview build-u;
- `.easignore` proširen za verification/cache direktorijume.

### Validation

- validator sada proverava cart/checkout rute i idempotency ugovor.

## 0.1.0 — 2026-08-06

### Added

- Expo SDK 57 / React Native 0.86 osnova;
- Sanctum auth i SecureStore sesija;
- bootstrap, permission i feature guardovi;
- registracija mobilnog uređaja;
- moderni light design system;
- tab navigacija;
- katalog, detalj proizvoda, porudžbine, detalj porudžbine, obaveštenja, nalog i uređaji;
- EAS profili i version gate;
- statički validation script.

### Hardened

- development client dodat za pravi SDK 57 Android tok;
- route-level feature gate za direktne/deep link rute;
- katalog koristi `/catalog/filters` kao izvor filter opcija;
- neuspešan bootstrap posle logina bezbedno uklanja lokalni token;
- Expo paketi usklađeni sa aktuelnim SDK 57 template-om;
- uklonjene tehničke development poruke iz glavnog korisničkog interfejsa.

# Phase 3B — Push onboarding + notification preferences

## Mobile scope v0.3.0

- korisnik ne dobija automatski permission prompt pri startu aplikacije;
- novi ekran `Obaveštenja i push` eksplicitno pokreće onboarding;
- Android kanal `business-updates` se kreira pre dobijanja Expo push tokena;
- Expo token se dobija sa EAS `projectId` vrednošću;
- token se čuva samo na backend `mobile_devices` zapisu, ne u lokalnim logovima;
- postojeća device registracija više ne postavlja `notifications_enabled=false` pri svakom startu;
- kada je OS dozvola već odobrena, aplikacija tiho osvežava push token pri autentifikovanom startu bez novog prompta;
- korisnik može da isključi push samo na trenutnom uređaju bez opoziva Sanctum sesije;
- globalni `push_enabled`, in-app/email kanal i poslovne kategorije koriste postojeći `PUT /api/v1/me/notification-preferences` ugovor;
- tap na push sa `order_id` ili `/orders/{id}` rutom otvara detalj porudžbine;
- ostali push događaji otvaraju tab Obaveštenja;
- dolazni push osvežava inbox query i bootstrap unread counter.

## Backend contract koji već postoji

Laravel v2.2.0 već podržava:

- `POST /api/v1/devices` za registraciju instalacije;
- `PATCH /api/v1/devices/{device}` za push token i `notifications_enabled`;
- šifrovano čuvanje push tokena i hash deduplikaciju;
- `PUT /api/v1/me/notification-preferences` sa `push_enabled` i poslovnim kategorijama;
- `push_registration` feature flag;
- `push_delivery` feature flag vezan za `MOBILE_PUSH_ENABLED`.

Produkcioni push dispatcher je u v2.2.0 namerno ostavljen isključen. Aktivacija stvarne isporuke je zaseban backend deo Phase 3B i ne uključuje se dok FCM/EAS kredencijali i real-device token registracija ne prođu proveru.

## Android FCM uslov

Pre prvog Phase 3B Android builda koji treba stvarno da prima push potrebno je:

1. Firebase Android aplikacija za tačan application id builda (`com.ald1n.mobile.preview` za preview);
2. `google-services.json` iz te Firebase aplikacije u root mobilnog workspace-a;
3. FCM V1 Google Service Account key dodat u EAS Credentials za odgovarajući Android application id.

`app.config.js` automatski koristi `./google-services.json` samo ako fajl postoji. Private service-account JSON se nikada ne stavlja u source paket niti na javnu putanju.

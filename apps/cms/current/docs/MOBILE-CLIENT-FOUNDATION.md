# React Native / Expo klijent — osnova za Ald1n CMS v2.2.0

## Arhitektura

Mobilna aplikacija je zaseban projekat i koristi postojeći Laravel backend:

```text
ald1n-cms-laravel   Laravel, MySQL, Sanctum, poslovni servisi, /api/v1
ald1n-mobile        Expo, React Native, TypeScript
```

Blade web aplikacija ostaje operativna. React web migracija nije preduslov za Android/iOS aplikaciju.

## Preporučeni Expo stack

- Expo + TypeScript;
- Expo Router;
- TanStack Query;
- React Hook Form + Zod;
- Expo SecureStore za Sanctum token;
- Expo Notifications za dobijanje Expo/FCM/APNs tokena;
- AsyncStorage ili SQLite samo za običan cache, nikada za auth token.

## Environment promenljive mobilnog projekta

```env
EXPO_PUBLIC_API_URL=https://cms.ald1n.com/api/v1
EXPO_PUBLIC_APP_ENV=production
```

Development i preview buildovi moraju koristiti zasebne URL-ove i identifikatore aplikacije. Produkcioni Laravel `APP_URL` mora biti tačan HTTPS URL, jer API vraća apsolutne media URL-ove.

## Početni auth tok

1. `POST /auth/token` sa loginom, lozinkom i nazivom uređaja.
2. Sačuvati `token` u SecureStore.
3. Postaviti `Authorization: Bearer <token>` i `Accept: application/json`.
4. Pozvati `GET /bootstrap`.
5. Renderovati navigaciju prema `features`, a akcije prema `permissions`.
6. Registrovati instalaciju preko `POST /devices`.

Na HTTP 401 klijent briše SecureStore token i vraća korisnika na login. Na `account_inactive` ili promeni lozinke radi isto.

## Registracija uređaja

Aplikacija jednom generiše installation UUID i čuva ga lokalno. Ne koristiti IMEI, serijski broj ili druge hardverske identifikatore.

```json
{
  "installation_id": "91a6b36c-1c1c-4db3-8d20-f88e71df1300",
  "platform": "android",
  "device_name": "Samsung SM-S921B",
  "push_provider": "expo",
  "push_token": "ExponentPushToken[...]",
  "app_version": "1.0.0",
  "build_number": "1",
  "locale": "sr-Latn",
  "timezone": "Europe/Belgrade",
  "notifications_enabled": true
}
```

Isti endpoint se može ponovo pozvati nakon pokretanja aplikacije. Opoziv uređaja kroz `DELETE /devices/{device}` opoziva i njegov vezani Sanctum token. Backend osvežava postojeću instalaciju, ne kreira duplikat. Jedan push token ne može istovremeno ostati aktivan na više korisničkih naloga.

v2.2.0 samo registruje tokene. Produkciono slanje push poruka ostaje isključeno dok queued dispatcher ne bude dodat.

## API greške

Klijent treba da očekuje isti format za sve API greške:

```json
{
  "message": "Podaci nisu ispravni.",
  "code": "validation_failed",
  "errors": {
    "items.0.quantity": ["Količina nije dostupna."]
  },
  "request_id": "f0d8d9d6-..."
}
```

`request_id` prikazati u tehničkom detalju greške i poslati podršci. Ne prikazivati stack trace korisniku.

## Kreiranje porudžbine

Pre forme pozvati `GET /orders/options`. Svaki pokušaj kreiranja dobija novi UUID kao `Idempotency-Key` header. Kod timeouta ponoviti isti zahtev sa istim ključem, ne generisati novi.

```http
Idempotency-Key: 70df6484-df87-4db8-aacf-6dca2409539b
```

## Katalog

`GET /catalog/filters` je izvor istine za:

- brendove, linije, tipove i kategorije;
- specifikacione filtere i njihove zavisnosti;
- podržana sortiranja i stock filtere;
- administratorske filtere kada korisnik ima odgovarajuću dozvolu.

Ne hardkodovati šifarnike u aplikaciji.

## TanStack Query predlog

```text
['bootstrap']
['catalog-filters']
['products', filters]
['product', slug]
['order-options']
['orders', page]
['order', id]
['notifications', filters]
['devices']
```

Nakon kreiranja ili otkazivanja porudžbine invalidirati `orders`, `order` i `bootstrap`. Nakon read/read-all invalidirati `notifications` i `bootstrap`.

## Offline granica prve verzije

Dozvoljeno:

- prikaz poslednje keširanog kataloga;
- prikaz poslednje keširanih porudžbina;
- lokalna korpa;
- automatski retry bezbednih GET zahteva.

Nije dozvoljeno u prvoj verziji:

- lokalno potvrđivanje da je porudžbina kreirana pre odgovora servera;
- offline promena statusa;
- konfliktni background sync poslovnih operacija.

## Sledeća razvojna faza

Nakon deploy-a i verifikacije v2.2.0, sledeći repozitorijum treba da započne ovim redom:

1. Expo projekat i environment profili;
2. API klijent i SecureStore auth;
3. login + bootstrap + permission guards;
4. tab navigacija;
5. katalog i detalj proizvoda;
6. korpa i kreiranje porudžbine;
7. lista/detalj porudžbina;
8. notifications inbox;
9. registracija uređaja;
10. Android internal build, zatim iOS TestFlight build iz iste kodne baze.

OpenAPI ugovor: `docs/openapi.yaml`.

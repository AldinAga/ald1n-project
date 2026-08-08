# Ald1n CMS API v1 — v2.2.0 Mobile API Foundation

Osnovni URL:

```text
https://cms.ald1n.com/api/v1
```

Kompletan mašinski čitljiv ugovor nalazi se u `docs/openapi.yaml`.

## Autentikacija

`POST /api/v1/auth/token` prima `login`, `password` i `device_name`. Uspešan odgovor vraća Sanctum Bearer token, datum isteka, korisnika, efektivne dozvole i `api_version`.

Za sve zaštićene zahteve slati:

```http
Authorization: Bearer <token>
Accept: application/json
```

Trenutna prijava se opoziva kroz `DELETE /api/v1/auth/token`. Promena lozinke kroz `PUT /api/v1/me/password` opoziva sve korisnikove API tokene i registrovane mobilne instalacije.

## Početni mobilni payload

`GET /api/v1/bootstrap` vraća:

- profil korisnika;
- efektivne dozvole trenutnog tokena;
- feature flagove za navigaciju;
- broj nepročitanih obaveštenja;
- notification preference;
- backend i API verziju;
- Android/iOS minimum i latest version policy.

Mobilni klijent treba da koristi `features` za prikaz glavnih modula, a `permissions` za pojedinačne akcije.

## Nalog

- `GET /api/v1/me`
- `PATCH /api/v1/me`
- `PUT /api/v1/me/password`
- `PUT /api/v1/me/notification-preferences`

## Mobilni uređaji

- `GET /api/v1/devices`
- `POST /api/v1/devices`
- `PATCH /api/v1/devices/{device}`
- `DELETE /api/v1/devices/{device}`

`installation_id` mora biti UUID koji aplikacija generiše jednom i čuva lokalno. Ne koristiti IMEI, serijski broj ili drugi hardverski identifikator.

Raw push token se čuva šifrovano, ne vraća se kroz API i može biti aktivan samo na jednoj instalaciji. `DELETE /devices/{device}` opoziva i Sanctum token vezan za taj uređaj. Promena push providera zahteva novi push token. v2.2.0 registruje tokene, ali ne šalje produkcione push poruke dok je `MOBILE_PUSH_ENABLED=false`.

## Katalog

- `GET /api/v1/catalog/filters`
- `GET /api/v1/products`
- `GET /api/v1/products/{slug}`

Filter endpoint je izvor istine za brendove, tipove, linije, kategorije, specifikacione filtere, stock filtere i sortiranja. URL-ovi slika proizvoda i varijanti vraćaju se kao apsolutni URL-ovi pogodni za native klijent.

Podržani osnovni query parametri kataloga:

```text
q
brand_id
product_type_id
product_line_id
category_id
stock=available|low|out
sort=newest|updated|name|price_asc|price_desc
per_page=6..60
```

## Porudžbine

- `GET /api/v1/orders/options`
- `GET /api/v1/orders`
- `GET /api/v1/orders/{order}`
- `POST /api/v1/orders`
- `POST /api/v1/orders/{order}/cancel`

Pre otvaranja forme pozvati `GET /orders/options` radi načina plaćanja, aktivnih računa, odgovornih lica, podrazumevane adrese i limita.

### Kreiranje porudžbine

`POST /api/v1/orders`

```http
Idempotency-Key: 70df6484-df87-4db8-aacf-6dca2409539b
```

```json
{
  "supplier_user_id": 2,
  "shipping_full_name": "Kupac",
  "shipping_address": "Adresa 1",
  "shipping_city": "Beograd",
  "shipping_postal_code": "11000",
  "shipping_phone": "060123456",
  "payment_method": "cash_on_delivery",
  "items": [
    {
      "product_id": 15,
      "product_variant_id": null,
      "quantity": 2
    }
  ]
}
```

Kod timeouta ponoviti potpuno isti payload i isti ključ. Isti ključ sa drugačijim payloadom vraća validation error. Nedovoljan lager poništava celu transakciju.

## Poslovna obaveštenja

- `GET /api/v1/notifications`
- `POST /api/v1/notifications/{notification}/read`
- `POST /api/v1/notifications/read-all`

Notification resurs vraća neutralni `route` i `target`, bez oslanjanja na web URL. Klijent sam mapira rutu na Expo Router ekran.

## Standardni format greške

```json
{
  "message": "Podaci nisu ispravni.",
  "code": "validation_failed",
  "errors": {
    "items.0.quantity": ["Količina nije dostupna."]
  },
  "request_id": "f0d8d9d6-0000-4000-8000-000000000000"
}
```

`request_id` se takođe vraća u `X-Request-ID` headeru i treba ga sačuvati u client logu radi povezivanja sa serverskim zapisima.

## Rate limiting

- login: 5 zahteva u minuti po login/IP kombinaciji;
- promena lozinke: 5 zahteva u minuti po korisniku;
- mutacije uređaja: 30 zahteva u minuti po korisniku;
- kreiranje porudžbine: postojeći minutni i satni limit.

## Release i dijagnostika

```bash
php bin/cms-v2.2.0-smoke.php
php artisan app:cms-v2-2-0-doctor --strict
php artisan route:list --path=api/v1
```

Deployment koraci: `docs/UPGRADE-V2.2.0.md`.

Expo smernice: `docs/MOBILE-CLIENT-FOUNDATION.md`.

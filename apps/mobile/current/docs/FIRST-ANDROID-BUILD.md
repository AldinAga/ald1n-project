# Prvi Android development build

## 1. Preduslovi

- Node.js 22.13 ili noviji;
- npm;
- Expo nalog;
- Android telefon ili emulator;
- Laravel v2.2.0 API dostupan preko HTTPS-a.

## 2. Priprema

```bash
cp .env.example .env
npm install
npx expo install --fix
npm run validate
npm run typecheck
npm run doctor
```

U `.env` proveri:

```env
EXPO_PUBLIC_APP_ENV=development
EXPO_PUBLIC_API_URL=https://cms.ald1n.com/api/v1
```

## 3. Povezivanje sa EAS-om

```bash
npx eas-cli@latest login
npx eas-cli@latest init
```

Pošto projekat koristi dinamički `app.config.ts`, kopiraj Project ID koji EAS prikaže u `.env`:

```env
EAS_PROJECT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

Dinamički `app.config.ts` se ne menja automatski pouzdano. Project ID nije tajna, ali mora pripadati ovom EAS projektu. Proveri vezu:

```bash
npx eas-cli@latest project:info
```

## 4. Development APK

```bash
npm run build:android:development
```

Preuzmi i instaliraj dobijeni APK na Android uređaj.

## 5. Pokretanje razvojnog servera

```bash
npx expo start --dev-client
```

Otvori instaliranu **Ald1n Mobile (development)** aplikaciju i poveži je sa Metro serverom.

## 6. Prva funkcionalna provera

1. prijava postojećim Ald1n CMS nalogom;
2. učitavanje početnog ekrana i korisničke uloge;
3. katalog i detalj proizvoda;
4. porudžbine i detalj porudžbine;
5. obaveštenja i označavanje kao pročitano;
6. nalog i lista prijavljenih uređaja;
7. odjava i potvrda da je Sanctum token opozvan.

## 7. Preview APK za internu distribuciju

Kada development build prođe proveru:

```bash
npm run build:android:preview
```

Preview profil pravi instalacioni APK bez development menija.

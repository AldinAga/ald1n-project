# Ald1n Mobile v0.3.0 — Google auth + Push + Material 3 Expressive

Ovaj patch se primenjuje preko poznatog v0.3.0 Phase 3B source workspace-a.

Uključuje:
- Google Sign-In preko Android Credential Manager bridge-a (`react-native-nitro-google-signin`);
- slanje Google ID tokena na Laravel `POST /api/v1/auth/google`;
- Material 3 Expressive-inspired UI pass;
- native ikonice u bottom navigation-u preko `expo-symbols`;
- novu Ald1n app/adaptive/splash ikonicu;
- Firebase `google-services.json` za preview package `com.ald1n.mobile.preview`;
- postojeći Phase 3B push onboarding i notification preferences ostaju netaknuti.

## Važno za package-lock

Patch namerno ne sadrži novi `package-lock.json`, jer su dodate native dependency-je:
- `react-native-nitro-google-signin` 1.0.2
- `react-native-nitro-modules` 0.36.1
- `expo-symbols` ~57.0.2

Canonical lockfile mora biti regenerisan u izolovanom workspace-u na serveru koristeći pravi npm CLI, zatim obavezno `npm ci`, `npm run typecheck`, `npm run validate` i `npm run doctor`.

Ne koristiti `npm install` u CloudLinux cPanel application root-u `mobile-build.ald1n.com`.

## Firebase

`google-services.json` u ovom patch-u pripada Firebase projektu `ald1nmobileapk` i Android package-u `com.ald1n.mobile.preview`. FCM V1 private service-account JSON nije deo source-a i ne sme biti dodat u ovaj paket.

# Ald1n Mobile v0.3.0 Phase 3B — Push Foundation apply & test

## Važno

Ne primenjuj v0.3.0 na workspace iz kog trenutno radi/čeka v0.2.0 EAS build. Prvo završi v0.2.0 APK i Cart -> Checkout -> Create Order real-device acceptance. Tek prihvaćeni v0.2.0 source postaje baseline za v0.3.0.

## 1. Firebase / FCM V1 za preview Android

Preview package je:

```text
com.ald1n.mobile.preview
```

U Firebase projektu registruj Android aplikaciju sa tim package name-om i preuzmi `google-services.json`.

`google-services.json` ide u root NOVOG v0.3.0 workspace-a:

```text
./google-services.json
```

FCM V1 service-account private key je tajna. Ne ide u source, ZIP, `.env` niti EAS build upload. Dodaje se kroz EAS Credentials kao FCM V1 service credential.

## 2. Novi izolovani workspace

Kada v0.2.0 acceptance prođe i Phase 3B PATCH bude uploadovan u `/home/icaffeco/`:

```bash
BASE="$(find "$HOME" -maxdepth 1 -mindepth 1 -type d -name 'ald1n-mobile-v0.2.0-phase3a-*' -printf '%T@ %p\n' | sort -nr | head -n1 | cut -d' ' -f2-)"
PATCH="$HOME/ald1n-mobile-v0.3.0-phase3b-push-PATCH.zip"
STAMP="$(date +%Y%m%d-%H%M%S)"
NEXT="$HOME/ald1n-mobile-v0.3.0-phase3b-$STAMP"

test -d "$BASE" || { echo "FAIL: v0.2 baseline nije pronađen"; exit 1; }
test -f "$PATCH" || { echo "FAIL: Phase 3B patch nije pronađen"; exit 1; }

mkdir -p "$NEXT"
tar -C "$BASE" \
  --exclude='./node_modules' \
  --exclude='./.npm-cache' \
  --exclude='./.clean-home' \
  --exclude='./*.log' \
  -cf - . | tar -C "$NEXT" -xf -
unzip -oq "$PATCH" -d "$NEXT"
cd "$NEXT"

NODE="/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node"
NPMCLI="/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js"
"$NODE" -e "console.log('VERSION=' + require('./package.json').version)"
```

Očekivano: `VERSION=0.3.0`.

## 3. Dodaj google-services.json

Tek u NOVI v0.3.0 workspace kopiraj/prebaci Firebase `google-services.json`, pa proveri:

```bash
cd "$NEXT"
test -s google-services.json && echo "PASS google-services.json" || echo "FAIL google-services.json nedostaje"
EXPO_PUBLIC_APP_ENV=preview "$NODE" node_modules/expo/bin/cli config --json 2>/dev/null | grep -o '"googleServicesFile":"[^"]*"' || true
```

Nemoj stavljati FCM service-account private key u ovaj direktorijum.

## 4. Canonical lock + clean install

```bash
cd "$NEXT"
grep -nE 'nodevenv|/home/icaffeco|"resolved"[[:space:]]*:[[:space:]]*"\.\./' package-lock.json \
  && echo "FAIL: lokalne putanje postoje" \
  || echo "PASS: canonical lock je prenosiv"

rm -rf node_modules .npm-cache .clean-home
mkdir -p .npm-cache .clean-home
cat > .npmrc <<'NPMRC'
package-lock=true
audit=false
fund=false
NPMRC
: > .global.npmrc

env -i \
  HOME="$NEXT/.clean-home" \
  USER="icaffeco" \
  LOGNAME="icaffeco" \
  LANG="C.UTF-8" \
  PATH="/usr/local/bin:/usr/bin:/bin" \
  CI=1 \
  npm_config_userconfig="$NEXT/.npmrc" \
  npm_config_globalconfig="$NEXT/.global.npmrc" \
  npm_config_cache="$NEXT/.npm-cache" \
  npm_config_registry="https://registry.npmjs.org/" \
  npm_config_package_lock=true \
  npm_config_audit=false \
  npm_config_fund=false \
  "$NODE" "$NPMCLI" ci --ignore-scripts --no-audit --no-fund
```

## 5. Validacija

```bash
PATH="$NEXT/node_modules/.bin:/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin:/usr/local/bin:/usr/bin:/bin" \
  "$NODE" "$NPMCLI" run typecheck
PATH="$NEXT/node_modules/.bin:/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin:/usr/local/bin:/usr/bin:/bin" \
  "$NODE" "$NPMCLI" run validate
PATH="$NEXT/node_modules/.bin:/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin:/usr/local/bin:/usr/bin:/bin" \
  "$NODE" "$NPMCLI" run doctor -- --verbose
```

Cilj: `typecheck PASS`, `Ukupno FAIL: 0`, `Expo Doctor 20/20`.

## 6. Real-device acceptance pre server-delivery aktivacije

Sa backend `MOBILE_PUSH_ENABLED=false`:

1. Login -> Nalog -> Obaveštenja i push.
2. App ne sme da traži push permission pre eksplicitnog klika.
3. Klik `Uključi push na ovom uređaju` i odobri sistemsku dozvolu.
4. `Prijavljeni uređaji` mora prikazati `Push: registrovan`.
5. Backend device zapis mora imati `push_provider=expo`, `push_token_hash` i `notifications_enabled=1`.
6. Promeni globalni push i category preferences, restartuj app i proveri persistence.
7. Ručni test kroz Expo push alat: `order_id` otvara detalj porudžbine; generički push otvara Obaveštenja.
8. `Isključi push na ovom uređaju` mora ukloniti token bez logout-a.

`push_delivery=false` u bootstrap-u je u ovoj tački očekivano.

## 7. Backend delivery

Tek nakon prethodnog acceptance-a primenjuje se odvojeni backend Phase 3B push-delivery patch. On se prvo instalira sa `MOBILE_PUSH_ENABLED=false`, proverava scheduler/outbox, pa se delivery aktivira kontrolisano.

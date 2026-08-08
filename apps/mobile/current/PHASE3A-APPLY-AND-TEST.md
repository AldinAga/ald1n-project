# Ald1n Mobile v0.2.0 Phase 3A — apply & test

Patch je napravljen direktno nad source snapshotom:

`ald1n-mobile-phase3a-source-20260807-121644.zip`

Backend preflight je potvrdio produkcione rute `GET /api/v1/orders/options` i `POST /api/v1/orders`, kao i Idempotency-Key obradu.

## 1. Upload patch ZIP na server

Uploaduj `ald1n-mobile-v0.2.0-phase3a-cart-checkout-PATCH.zip` u:

```text
/home/icaffeco/
```

## 2. Napravi novi workspace iz Phase 3A baseline-a

```bash
BASE="/home/icaffeco/ald1n-mobile-phase3a-20260807-121644"
STAMP="$(date +%Y%m%d-%H%M%S)"
NEXT="$HOME/ald1n-mobile-v0.2.0-phase3a-$STAMP"
PATCH="$HOME/ald1n-mobile-v0.2.0-phase3a-cart-checkout-PATCH.zip"

mkdir -p "$NEXT"

tar -C "$BASE" \
  --exclude='./node_modules' \
  --exclude='./.npm-cache' \
  --exclude='./.clean-home' \
  --exclude='./*.log' \
  -cf - . | tar -C "$NEXT" -xf -

unzip -oq "$PATCH" -d "$NEXT"

cd "$NEXT"

echo "WORKSPACE=$NEXT"
node -e "const p=require('./package.json'); console.log('version='+p.version)"
grep -n "version: '0.2.0'" app.config.js
```

Očekivano: `version=0.2.0`.

## 3. Proveri canonical lockfile

```bash
grep -nE \
  'nodevenv|/home/icaffeco|"resolved"[[:space:]]*:[[:space:]]*"\.\./' \
  package-lock.json \
  && echo "FAIL: lokalne putanje postoje" \
  || echo "PASS: canonical lock je prenosiv"
```

Očekivano: `PASS`.

## 4. Clean install u izolovanom workspace-u

```bash
NODE="/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node"
NPMCLI="/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js"

cd "$NEXT"
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
  "$NODE" "$NPMCLI" ci \
    --ignore-scripts \
    --no-audit \
    --no-fund
```

Ne koristi cPanel `Run NPM Install` za ovaj workspace.

## 5. Validacija

```bash
PATH="$NEXT/node_modules/.bin:/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin:/usr/local/bin:/usr/bin:/bin" \
  "$NODE" "$NPMCLI" run typecheck

PATH="$NEXT/node_modules/.bin:/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin:/usr/local/bin:/usr/bin:/bin" \
  "$NODE" "$NPMCLI" run validate

PATH="$NEXT/node_modules/.bin:/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin:/usr/local/bin:/usr/bin:/bin" \
  "$NODE" "$NPMCLI" run doctor -- --verbose
```

Cilj:

```text
typecheck PASS
validate: Ukupno FAIL: 0
Expo Doctor: 20/20
```

## 6. Preview Android build

```bash
cd "$NEXT"

"$NODE" "$NPMCLI" exec \
  --yes \
  --package=eas-cli@latest \
  -- eas build \
    --platform android \
    --profile preview \
    --clear-cache \
    --verbose-logs
```

Preview profil koristi internal APK, remote version source i auto-increment build broja.

## 7. Real-device Phase 3A acceptance test

1. Instaliraj novi preview APK.
2. Login.
3. Otvori Katalog.
4. Otvori proizvod bez varijante, promeni količinu i dodaj u korpu.
5. Ako postoji proizvod sa varijantama, izaberi varijantu i dodaj je u korpu.
6. U korpi povećaj/smanji količinu i ukloni/dodaj stavku.
7. Otvori checkout.
8. Potvrdi da shipping polja dolaze popunjena kada nalog ima podatke.
9. Testiraj pouzeće.
10. Testiraj bank transfer i izbor aktivnog računa.
11. Kreiraj test porudžbinu.
12. Potvrdi da aplikacija otvara detalj nove porudžbine i da je korpa prazna.
13. U CMS-u potvrdi jednu jedinu novu porudžbinu i pravilno umanjenje lagera.

Za idempotency retry test ne prekidaj produkcionu mrežu nasumično. Prvo potvrdi standardni happy-path. Kontrolisani timeout/retry test radimo kao poseban korak nakon toga.

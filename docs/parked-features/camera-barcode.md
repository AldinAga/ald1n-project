# Parkirano: kamera, EAN skener i fotografija kamerom (M5)

Autor: Ald1n <https://ald1n.com>

Status 24.11.2026: vlasnik je odlučio da se ova funkcija za sada ne isporučuje. Kod je sačuvan, ali ne postoji ni u jednom buildu, ni u `package.json`, ni u `app.config.js`, ni u lock fajlu.

## Šta je urađeno

- `expo-camera ~58.0.14` i `expo-image-picker ~58.0.10` (plugini `recordAudioAndroid: false` i `microphonePermission: false`, nema RECORD_AUDIO).
- Dugme "Skeniraj EAN" u kreiranju i izmjeni artikla, preko Google skenera (`CameraView.launchScanner`, EAN13, EAN8, UPC-E), tiha obrada otkazivanja.
- Dugme "Slikaj" u upravljanju fotografijama (`launchCameraAsync`, kvalitet 0.8, ista skica i validacija kao galerija).
- Plugin `plugins/with-optional-camera-feature.js` (`android.hardware.camera` i `.autofocus` kao `required=false`).
- Test `m5-camera-kobalt-native.test.mjs`, lista za build `M5-NATIVE-BUILD-CHECKLIST.md`, bilješke za Play Data safety.
- Prilagođeni gejtovi: b1106 (dozvoljene tačno te dvije zavisnosti i njihovi lock čvorovi) i b1031 (pinovi paketa).

## Gdje je

- Grana `parked/m5-camera-barcode` i tag `parked-m5-camera-barcode`, oba na commitu `d26dc6ec` (glava grane `feat/mobile-m5-20261124`, PR 234, DRAFT, naslov "PARKED").
- Ništa nije obrisano ni prepisano.

## Šta je isporučeno umjesto toga (camera-free play3)

Samo ono što ne zavisi od kamere: Kobalt boje (splash, boja obavještenja, pozadina adaptive ikone), runtime `1.0.0-m17-sdk58-play3`, pin eas-cli 27.11.0, popravke gejtova (b1106 dozvoljava jednu izmjenu `eas.json`, allowlist za b1156/b1157 digest, uklonjen zastarjeli lg03 wave2) i skripta `derive-play3-gateset.mjs`. Ni jedna permisija nije dodata.

## Kako vratiti

1. Novu granu iz `main`: `git switch -c feat/mobile-camera origin/main`.
2. Preuzeti samo kamera dio iz taga: `git diff origin/main parked-m5-camera-barcode -- apps/mobile/current/package.json apps/mobile/current/package-lock.json apps/mobile/current/plugins apps/mobile/current/src docs/google-play-data-safety.md apps/mobile/current/scripts/release25/m5-camera-kobalt-native.test.mjs` pa `git apply`, plus plugini u `app.config.js` (blok `expo-camera`, `with-optional-camera-feature`, `expo-image-picker`).
3. Vratiti gejtove: u b1106 dozvoliti tačno ta dva paketa i njihove lock čvorove (vidjeti `reviewedAdds` u commitu `e078aeba`), u b1031 ponovo izračunati blob pinove (`git hash-object`) i dodati pinove paketa.
4. Ukloniti `scripts/release25/m5-play3-kobalt-native.test.mjs` test "camera ... stay out" ili ga zamijeniti verzijom iz taga.
5. Ako je play3 već isporučen bez kamere, kamera traži NOVI native identitet (runtime play4) i novi versionCode, jer OTA ne može dodati native modul.
6. Prije builda: spojeni manifest AAB-a (CAMERA da, RECORD_AUDIO ne), ELF/16 KB provjera na potpisanom AAB-u (ML Kit biblioteke donose native `.so`), test na uređaju, ažuriran Data safety obrazac.

## Zašto je parkirano

Odluka vlasnika, 24.11.2026: za sada se funkcija ne isporučuje. Ostaje otvoreno: potvrda spojenog manifesta, 16 KB provjera ML Kit biblioteka, test na fizičkom uređaju bez Google Play servisa skenera i Data safety obrazac.

# M5: kamera, EAN skener i Kobalt boje (native sloj)

Autor: Ald1n <https://ald1n.com>

Ovaj PR je samo izvor. Ništa se ne gradi, ne objavljuje i ne šalje na EAS, OTA ili Play. Gejtovi (`hold-unaudited-native-build.mjs`, 16 KB AAB gejt, `submit-android-production.mjs`) nisu menjani, samo je očekivani runtime pomeren na novi identitet.

## Šta se promenilo

- Runtime `1.0.0-m17-sdk58-play2` postaje `1.0.0-m17-sdk58-play3` (`app.config.js`, `M17_PLAY_RUNTIME`) i u svim provjerama koje ga pinuju (testovi, validate-project, signed-16kb-sdk58-aab-gate, submit helper, ios-release-readiness, b1031/b1041/b1042 workflow-i). Živi AAB 29 je play2; OTA na play2 ne može stići do play3 aplikacije i obrnuto.
- versionCode 30: `eas.json` ima `appVersionSource: remote` i `autoIncrement: true` na profilu `m17-sdk58-play-rc`. versionCode se ne upisuje u izvor. EAS pri sledećem buildu uveća udaljeni brojač (29 na 30) ako je brojač zaista na 29. To se mora potvrditi pre builda sa `eas build:version:get --platform android --profile m17-sdk58-play-rc` (samo čitanje).
- Zavisnosti: `expo-camera ~58.0.14`, `expo-image-picker ~58.0.10` (lock dodaje i web-only `barcode-detector`, `zxing-wasm`).
- Plugini: `expo-camera` (`recordAudioAndroid: false`), `expo-image-picker` (`microphonePermission: false`), novi `plugins/with-optional-camera-feature.js` (`android.hardware.camera` i `.autofocus` kao `required=false` uz `tools:replace`).
- Boje: splash i adaptive icon pozadina `#0d1216` (`kobaltDark.surface.page`), boja obaveštenja `#3557c7` (`kobaltDark.button.primary.bg`). Test `m5-camera-kobalt-native.test.mjs` drži vrednosti jednake tokenima.
- Aplikacija: dugme "Skeniraj EAN" (create i edit artikla), dugme "Slikaj" u upravljanju fotografijama.

## Šta ostaje pre builda (traži izričito odobrenje vlasnika)

1. Spojiti PR i zamrznuti SHA; nezavisni pregled.
2. Pročitati udaljeni versionCode (mora biti 29, tada je sledeći 30).
3. Pokrenuti odobren GitHub/EAS tok (b1041) za profil `m17-sdk58-play-rc`; nijedan host build.
4. Lokalni `expo prebuild` (samo generisanje, bez gradle-a) već daje `CAMERA`, `RECORD_AUDIO` sa `tools:node="remove"` i oba `uses-feature` kao `required=false`. Spojeni manifest AAB-a (posle gradle merge-a biblioteka) tek treba potvrditi: `CAMERA` postoji, `RECORD_AUDIO` ne postoji, `uses-feature android.hardware.camera` je `required=false` (bundletool `dump manifest`).
5. 16 KB gejt na potpisanom AAB-u, uključujući native biblioteke koje donose expo-camera i ML Kit (Google code scanner dolazi preko Play Services, ali native deo expo-camera mora proći ELF proveru).
6. Provera na fizičkom uređaju: skener (EAN13, EAN8, UPC-E), kamera za fotografiju (dozvola, odbijanje, "ne pitaj ponovo"), uređaj bez Google Play Services skenera (ručni unos).
7. Ažurirati Data safety (Fotografije) i izjavu o dozvolama u Play Console (obrazac podnosi vlasnik), vidi `docs/google-play-data-safety.md`.
8. OTA tek posle potvrđene instalacije play3 binarnog fajla.

## Poznata ograničenja

- Android može ubiti aktivnost dok je sistemska kamera otvorena (malo memorije); `ImagePicker.getPendingResultAsync` nije obrađen, pa se u tom retkom slučaju fotografija ne pojavi i snima se ponovo.
- Slike u `assets/` (splash-icon, adaptive-icon) nisu menjane; menja se samo pozadinska boja.

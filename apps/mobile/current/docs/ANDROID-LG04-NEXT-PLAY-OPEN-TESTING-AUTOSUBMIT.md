# Ald1n CMS — naredni Android LG-04 Open testing auto-submit

**Status: PRIPREMA / NE IZVRŠAVATI SADA.** LG-04 Wave1 header kandidat nije dobio fizički Play test ni objedinjeni QA sa sledećom fazom Kataloga. Ovo NIJE produkcioni rollout.

## Traganje za release autoritetom

- Referentni fizički Build26 Open testing: `1.0.0 (26)`, Play package `com.ald1n.mobile`, source `58b5e45b6b53a60e547e225f03ab43dd9ca85361`, EAS profil/kanal `lg03-play-testing`, runtime `1.0.0-lg03-play-testing1`. Korisnik potvrdio da LG-03 native donji meni i njegovi efekti rade ispravno.
- LG-04 source grana: `feature/android-lg04-wave1-readable-header-glass-20261020`, izvedena iz **tačnog Build26 source SHA**. PR je STACKED na još Draft PR #47; nikada ne menjati produkcioni `main` pre native compatibility gate-a.
- Za LG-04 koristi se **ista** ugrađena native biblioteka `react-native-android-liquid-glass@0.2.1` i isti runtime **ako exact-source diff i device QA dokažu kompatibilnost**.
- Sledeći `versionCode` dodeljuje EAS kroz `appVersionSource=remote` i `autoIncrement=true`: očekuje se broj veći od 26, ali **ne sme unapred tvrditi 27** pre EAS metadata.
- Trenutni `version=1.0.0`. Nema automatske promene verzije ili korisničkog production izdanja.

## Pripremljeni EAS build i submit profili

`build.lg03-play-testing`: Android `.aab`, store distribution, Play package `com.ald1n.mobile`, EAS channel `lg03-play-testing`, runtime `1.0.0-lg03-play-testing1`.

`submit.lg03-play-testing`:
- `android.track = beta` (**Google Play Open testing**, ne production)
- `android.releaseStatus = completed` (kada se release odobri i pokrene, objavljuje se *u open testing*, ne production)
- `submit.production` ostaje neizmenjen. Nema `autoSubmit` na git push; objava se mora **izričito autorizovati uz exact SHA/build ID** nakon QA.

## Jednokratna naredna autoSubmit komanda — samo nakon release gate-ova

Radni direktor: `/home/icaffeco/ald1n-project/apps/mobile/current`. U operativnoj izvedbi koristi se zaseban izolovani exact-source clone, ne postojeći dirty checkout, i canonical Node22/npm-eas CLI25 autoritet projekta:

```bash
"$NODE_BIN" "$NPM_CLI" exec --yes --package=eas-cli@25.0.0 -- eas build \
  --platform android \
  --profile lg03-play-testing \
  --auto-submit \
  --auto-submit-with-profile lg03-play-testing \
  --freeze-credentials \
  --non-interactive \
  --no-wait \
  --json
```

**Ne pokretati sada.** Prvo proći kompletan LG-04 UI/regresioni CI, autorizovan uređajski UAT, verifikovati remote Play version, EAS originalni upload keystore i Google Play service-account key koji je već vezan za Expo projekat. Ako Google Play ključ ne postoji ili EAS ne može da ga pročita, **HOLD**, ne generisati zamenu, ne koristiti produkcioni submit profil. User je zatražio naredni autoSubmit tek zajedno sa specifikacijom uređaja; ova priprema sama po sebi nije naredna release dozvola.

## Specifikacija uređaja + napomene

Zvanična operativna specifikacija u source-u:
`apps/mobile/current/docs/ANDROID-LG04-DEVICE-COMPATIBILITY.md`.

Google Play EAS Submit šalje `.aab` i bira testing track; **nema mogućnost da automatski attach-uje proizvoljan Markdown dokument ili unese spec u Device catalog**. Specifikacija se zato pridružuje release izveštaju kao GitHub dokument i isporučuje uz release notes koje owner kopira u Play Console. U Play Console katalogu ne uvodimo nova uređajska isključenja bez merenja.

### Copy/paste — sr-RS, LG-04 testing

LG-04 — provera Liquid Glass interfejsa

• Uređeniji globalni header sa diskretnim staklastim komandama i animacijama na dodir.
• Veća čitljivost naslova i jasniji indikatori notifikacija.
• Preglednija pretraga i filteri kataloga, uz diskretno otvaranje panela bez animiranja cena ili proizvoda.
• Efekti su opcioni na podržanim Android 13+ uređajima; drugi uređaji zadržavaju čitljivu klasičnu navigaciju.
• Poštovana podešavanja smanjenog kretanja i povećanog kontrasta.

Test izdanje: prijavite probleme sa prikazom, navigacijom ili performansama.

**Do narednog release-a:** navesti i promene druge LG-04 faze ako je završena. Ne lažno obećavati adaptaciju grafike koja nije prošla realan UAT.

## Release HOLD / prihvat

- Mora proći source CI i physical UAT, uključujući niže API-je i minimum 48dp touch, dark/light, visoki kontrast, nazive naslova i 30-sekundno skrolovanje.
- Exact source SHA, EAS build UUID, versionCode, runtimeVersion, profil/kanal i artifact metadata navedeni u numerisanom reportu pod `docs/operations`; skripta za host mora biti u `incoming` i prethodno SHA-256 proverena.
- EAS auto-submit je **jedna** autorizovana Play Open testing predaja `beta`, bez objavljivanja na production. Ne ponavljati submit ako je ishod nepoznat; čitati exact build/submission ID.

# Google Play: Data safety i brisanje naloga (izdanje play3)

Radni materijal za obrazac Data safety u Play Console. Nije pravni savet; pravna pitanja su na kraju.

Oznake: **[kod]** potvrđeno u kodu na `main` (a9c37677), **[odluka vlasnika]** navedeno u zadatku, **PROVJERITI (vlasnik)** nije potvrđeno iz koda ili Play Console.

Važno: na `main` još nema koda za kameru, skener barkoda ni izbor slike (`apps/mobile/current/package.json` nema `expo-camera`, `expo-image-picker` ni skener; `app.config.js` nema Android permissions). Sve što se tiče play3 kamere i slika je plan i mora se uskladiti sa stvarnim kodom prije slanja obrasca.

## Brisanje naloga (Play zahteva oba) [kod]

- U aplikaciji: Nalog, Bezbednost, Brisanje naloga (ekran `account/delete`, API `POST /api/v1/account/deletion`).
- Javni URL za polje "Delete account URL": `https://cms.ald1n.com/brisanje-naloga` (bez prijave, potvrda e-poštom).
- Rok mirovanja 14 dana: nalog je blokiran, poništavanje linkom iz e-pošte. Posle roka dnevna komanda `app:account-deletions-process` anonimizuje nalog.
- Briše se: ime, e-pošta, telefon, adresa, veza sa Google nalogom, push tokeni, oznake i nazivi uređaja, passkey i restore podaci, IP adrese i user agent iz evidencije prijava, dnevnika izmena i bezbednosnih događaja.
- Bezbednosni događaji (`security_events`): IP i user agent se brišu, `context_json` ostaje. PROVJERITI (vlasnik): može li kontekst sadržati lične podatke.
- Ostaje (poslovna istorija bez veze sa imenom): porudžbine (uključujući podatke o isporuci), provizije, garancije, razgovori, CRM beleške, dokumenta.
- Otvorene provizije i zaduženja čekaju: zahtev ostaje u statusu `waiting_obligations`, korisnik dobija e-poštu.
- Nalog administratora ili SuperAdministratora: samo uz drugog aktivnog SuperAdministratora.
- PROVJERITI (vlasnik): brisanje fotografija artikala koje korisnik otpremi kamerom (da li se brišu sa nalogom ili ostaju uz artikal kao poslovni podatak). Dok nije potvrđeno, ne tvrditi u obrascu da se brišu.

## Data safety odgovori

Svi podaci se šalju preko HTTPS-a (šifrovanje u prenosu) [kod: `apiUrl` je `https://cms.ald1n.com/api/v1`]. Korisnik može zatražiti brisanje (tačka iznad).

| Podatak (Play kategorija) | Prikuplja se | Dijeli se | Obavezno / opciono | Svrha | Izvor |
|---|---|---|---|---|---|
| Ime, e-pošta, telefon, adresa | Da | Ne | Obavezno (nalog) | Funkcionalnost aplikacije, upravljanje nalogom | [kod] |
| ID korisnika (nalog, Google identifikator) | Da | Ne | Obavezno za nalog; Google ID samo ako se prijavljuje Google nalogom | Funkcionalnost, upravljanje nalogom, bezbednost | [kod] |
| Expo push token, `installation_id` (Device or other IDs) | Da | **Da, Expo** (Expo Push Service, uz Google FCM odnosno Apple APNs za isporuku) | Opciono: šalje se tek kad korisnik dozvoli obaveštenja; isključivanje u aplikaciji | Funkcionalnost (obaveštenja) | [kod] push-service.ts; "dijeli" [odluka vlasnika] |
| Naziv uređaja, verzija aplikacije, build, platforma, jezik, vremenska zona | Da | Ne | Obavezno (prijava uređaja) | Funkcionalnost, bezbednost | [kod] push-service.ts |
| Aktivnost u aplikaciji: audit log, evidencija prijava (IP, user agent) | Da | Ne | Obavezno | Bezbednost, sprečavanje prevara | [kod/politika] |
| Fotografije: fotografija artikla snimljena sistemskom kamerom ili izabrana iz galerije | Da, ako se otprema na server | PROVJERITI (vlasnik): ne, osim ako se čuvaju kod trećeg (npr. CDN, objektno skladište) | Opciono (korisnik sam bira da doda sliku) | Funkcionalnost (slika artikla) | plan play3, kod nije u `main` |
| Sadržaj barkoda (Google skener barkoda) | PROVJERITI (vlasnik): skeniranje se obavlja na uređaju; prikuplja se samo ako se pročitani kod šalje serveru (npr. pretraga artikla) | Ne | Opciono | Funkcionalnost (pronalazak artikla) | plan play3, kod nije u `main` |
| Tačna ili približna lokacija | Ne (nema koda ni permission-a za lokaciju) | Ne | - | - | [kod] nema pretrage `expo-location` / ACCESS_*_LOCATION; PROVJERITI (vlasnik) da se ne dodaje prije izdanja |
| Audio / mikrofon | Ne | Ne | - | - | RECORD_AUDIO se ne traži [odluka vlasnika]; PROVJERITI (vlasnik) u stvarnom manifestu AAB-a |
| Kontakti, SMS, finansijski podaci kartice | Ne | Ne | - | - | PROVJERITI (vlasnik) |

## Dozvole i SDK-ovi (play3)

| Stavka | Stanje | Napomena |
|---|---|---|
| `CAMERA` permission | Planirano [odluka vlasnika] | PROVJERITI (vlasnik): Google skener barkoda (ML Kit, preko Google Play services) i sistemska kamera preko intenta obično rade bez CAMERA permission-a; potvrditi u spojenom manifestu AAB-a šta zaista ulazi i zašto, jer Play traži obrazloženje za svaku sensitive permission. |
| `RECORD_AUDIO` | Ne traži se | PROVJERITI (vlasnik): neki paketi kamere ga dodaju sami; provjeriti spojeni manifest i, ako treba, blokirati. |
| Izbor slike (image picker) | Planirano | PROVJERITI (vlasnik): sistemski Photo Picker ne traži READ_MEDIA_* permission; potvrditi da ih nema u manifestu. |
| Push (`expo-notifications`) | Već u kodu [kod] | Android 13+ traži `POST_NOTIFICATIONS`; PROVJERITI (vlasnik) u manifestu. |
| Google prijava (`react-native-nitro-google-signin`) | Već u kodu [kod] | Google obrađuje sopstvene podatke tokom prijave; mi dobijamo identifikator i e-poštu. |
| `expo-updates` (OTA) | Već u kodu [kod] | PROVJERITI (vlasnik): Expo prima podatke o uređaju pri provjeri ažuriranja; odlučiti da li se prijavljuje u obrascu kao dijeljenje. |
| Reklame, analitika, crash reporting | Nema u `package.json` | PROVJERITI (vlasnik): potvrditi da nijedan SDK ne prikuplja podatke. |

## Predlog dopune politike privatnosti (nacrt, nije objavljeno)

Politika živi u `apps/cms/current/app/Support/PublicLegalTemplates.php` (`PRIVACY`) i uređuje se u administraciji. Već pokriva: podatke mobilne aplikacije, Expo push token, Google prijavu, brisanje naloga sa rokom od 14 dana i Expo kao primaoca. Namjerno ne mijenjamo šablon dok kamera nije u `main`, da javni tekst ne opisuje funkciju koje nema. Prije izdanja play3 dodati:

- U "Koje podatke obrađujemo": fotografije artikala koje korisnik sam snimi ili izabere (opciono); skeniranje barkoda se obavlja na uređaju, a serveru se šalje samo pročitani broj, ako je tako implementirano (PROVJERITI (vlasnik)).
- Rečenicu da aplikacija ne snima zvuk i ne koristi lokaciju (samo ako se potvrdi).
- U "Brisanje naloga": sudbinu otpremljenih fotografija (PROVJERITI (vlasnik)).
- Link na politiku privatnosti mora biti isti u Play Console i u aplikaciji.

## Pitanja za pravnu provjeru

1. Da li je Expo obrađivač ili zaseban rukovalac, i da li je potreban ugovor o obradi? Da li "dijeljenje" push tokena Expo-u i dalje treba označiti u Data safety ako je obrađivač?
2. Država obrade Expo-a, Googlea (FCM, ML Kit) i hostinga, i osnov prenosa van Srbije (čl. 63. do 65.).
3. Pravni osnov za fotografije artikala (ugovor, legitimni interes ili pristanak) i da li fotografija može sadržati lica ili lične podatke trećih lica.
4. Rokovi čuvanja po vrsti zapisa (porudžbine, reklamacije, audit log, `security_events`) i da li `context_json` smije ostati posle brisanja naloga.
5. Da li zadržavanje imena i adrese primaoca u porudžbini posle brisanja naloga ispunjava Playov zahtev za brisanje podataka.
6. Da li je potrebno posebno obaveštenje ili pristanak prije prvog otvaranja kamere, pored sistemskog zahteva za dozvolu.
7. Usklađenost teksta politike privatnosti sa odgovorima u Data safety i sa deklaracijom o dozvolama.
8. Da li je potrebna zasebna obaveštenje za maloljetnike (ako ciljna grupa aplikacije to dozvoljava).

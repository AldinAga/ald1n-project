# Google Play: Data safety i brisanje naloga

Radni materijal za obrazac Data safety u Play Console. Nije pravni savet; mesta označena sa [pravna provera] potvrđuje vlasnik ili pravnik pre slanja.

## Brisanje naloga (Play zahteva oba)

- U aplikaciji: Nalog, Bezbednost, Brisanje naloga (ekran `account/delete`, API `POST /api/v1/account/deletion`).
- Javni URL za Play Console polje "Delete account URL": `https://cms.ald1n.com/brisanje-naloga` (bez prijave, potvrda e-poštom).
- Rok mirovanja 14 dana: nalog je blokiran, poništavanje linkom iz e-pošte. Posle roka dnevna komanda `app:account-deletions-process` anonimizuje nalog.
- Briše se: ime, e-pošta, telefon, adresa, veza sa Google nalogom, push tokeni, oznake i nazivi uređaja, passkey i restore podaci, IP adrese i user agent iz evidencije prijava, dnevnika izmena i bezbednosnih događaja.
- Bezbednosni događaji (security_events): IP i user agent se brišu, a polje context_json ostaje onakvo kakvo jeste. [pravna provera: da li kontekst može sadržati lične podatke]
- Ostaje (poslovna istorija bez veze sa imenom): porudžbine (uključujući podatke o isporuci u porudžbini), provizije, garancije, razgovori, CRM beleške, dokumenta. [pravna provera: rokovi čuvanja po vrsti zapisa]
- Otvorene provizije i zaduženja čekaju: zahtev ostaje u statusu `waiting_obligations`, korisnik dobija e-poštu.
- Nalog administratora ili SuperAdministratora: samo uz drugog aktivnog SuperAdministratora koji preuzima porudžbine i aktivan rad.

## Data safety odgovori

| Podatak | Prikuplja se | Deli se | Svrha | Opciono |
|---|---|---|---|---|
| Ime, e-pošta, telefon, adresa | Da | Ne | Funkcionalnost aplikacije, upravljanje nalogom | Ne |
| ID korisnika (nalog, Google identifikator) | Da | Ne | Funkcionalnost, upravljanje nalogom, bezbednost | Ne |
| Identifikatori uređaja ili drugi ID-jevi: Expo push token, installation_id | Da | **Da, Expo (obrada obaveštenja)** | Funkcionalnost (obaveštenja) | Da, isključivanjem obaveštenja |
| Naziv uređaja, verzija aplikacije, platforma | Da | Ne | Funkcionalnost, bezbednost | Ne |
| Aktivnost u aplikaciji: audit log (radnje na nalogu) | Da | Ne | Bezbednost, sprečavanje prevara | Ne |
| Fotografije: slike artikla koje administrator snimi kamerom ili izabere iz fajlova | Da (šalju se na naš server) | Ne | Funkcionalnost aplikacije (katalog) | Da, dodavanje fotografija nije obavezno |

## Kamera (dozvola CAMERA, od runtime-a `1.0.0-m17-sdk58-play3`)

- Dozvole u manifestu: `CAMERA`. Mikrofon nije tražen: `RECORD_AUDIO` se ne dodaje (`recordAudioAndroid: false` u expo-camera, `microphonePermission: false` u expo-image-picker). Odgovor u Data safety za audio: ne prikuplja se.
- Fotografija artikla (Admin, Katalog, Slikaj): sistemska kamera, jedna fotografija po dodiru. Dozvola se traži tek pri prvom dodiru uz objašnjenje. Fotografija se šalje na server kao i fotografija izabrana iz fajlova (kategorija Fotografije, prikuplja se, ne deli se).
- EAN skener (Admin, Katalog, polje EAN, Skeniraj): Google skener bar-kodova (ML Kit) radi na uređaju. Aplikacija ne dobija i ne čuva sliku, samo pročitan broj koji se upisuje u polje EAN. Ako skener nije dostupan, EAN se unosi ručno. Skener ne dodaje nikakav podatak u Data safety.
- `android.hardware.camera` je u manifestu `required=false`: aplikacija ostaje dostupna uređajima bez kamere (tablet bez zadnje kamere i slično). [provera pri buildu: da li je dodato u spojeni manifest]
- Pre izdanja treba ažurirati formular "Data safety" (dodati Fotografije) i izjavu o dozvolama; formular podnosi vlasnik.

Napomene:
- Expo push token se šalje Expo Push Service-u radi isporuke obaveštenja, pa se u Data safety označava kao "deli" (obrađivač usluge). [pravna provera: da li Expo ulazi u izuzetak za obrađivače usluga]
- Podaci se šifruju u tranzitu (HTTPS). Korisnik može zatražiti brisanje (tačka iznad).
- Google prijava: Google obrađuje sopstvene podatke tokom prijave; mi dobijamo identifikator i e-poštu.

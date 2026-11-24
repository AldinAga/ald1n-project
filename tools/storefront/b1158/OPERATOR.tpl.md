# B1158: uputstvo za operatera

Autor: Ald1n <https://ald1n.com>

Paket prebacuje produkciju sa stanja B1157 (Izvještaj 1123, 8620 fajlova, 597 ruta, `origin/main` 2ed96694) na `origin/main` {{SRC8}}: {{FILES}} fajlova ({{NEW}} novih, {{MOD}} izmijenjenih), migracije: {{MIGRATIONS}}. Poslije instalacije: {{TREE}} fajlova i {{ROUTES_AFTER}} ruta. Instalater je u grani `{{BRANCH}}` (PR {{PR}}), CI je zelen (run {{RUN}}), a paket je napravljen iz tih bajtova.

## Šta se šalje na server

Dva fajla, oba u `/home/icaffeco`:

| Fajl | Veličina | SHA-256 |
| --- | --- | --- |
| `b1158-operator.zip` | {{ZIP_BYTES}} bajtova | `{{ZIP_SHA}}` |
| `b1158-operator.zip.sha256` | {{SHA_FILE_BYTES}} bajtova | sadrži gornji zbir |

Ništa drugo se ne šalje i ništa na serveru se ne briše.

## Prije pokretanja

1. Provjerite zbir. Komanda mora da ispiše `b1158-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1158-operator.zip.sha256
   ```

2. Mora da postoji tačno jedan izvještaj `1123-B1157-INSTALLER-*.md` sa `EXIT=0`, a stanje B1157 mora biti `INSTALLED_PASS` (SHA-256 izvještaja i id stanja su ugrađeni u paket). Ako nešto fali ili se razlikuje, instalater staje sa `HOLD` prije bilo koje izmjene.
3. Prodavnica je uključena, saobraćaj mali, `APP_URL` je `https://cms.ald1n.com`.
4. Počnite najkasnije oko 23:00 za rezervnu kopiju u 02:30. Subotom takođe najkasnije oko 23:00, jer se nedjeljna kopija u 03:10 računa kao isti prozor.

## Komande (cPanel terminal, nalog `icaffeco`)

```
unzip -o /home/icaffeco/b1158-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1158-installer.sh
```

Radnik radi odvojeno od SSH sesije. Ako sesija padne, instalacija se nastavlja; pratite izvještaj komandom `tail -f` na fajlu iz sljedećeg odjeljka. Posljednja linija izvještaja je `EXIT=0` za uspjeh.

Vraćanje fajlova na zahtjev (migracija se nikad ne poništava):

```
bash /home/icaffeco/ald1n-project/incoming/b1158-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1158-installer.sh --rollback --ignore-schedule
```

Druga komanda zaobilazi samo provjeru prozora rezervne kopije i koristi se isključivo za vraćanje.

## Izvještaj

Fajl se zove `<broj>-B1158-INSTALLER-<datum>-<vrijeme>.md` i nalazi se u `docs/operations`. Broj je najveći postojeći broj izvještaja plus 1 (wrapper ga računa sam, iz `docs/operations` na serveru i iz `origin/main`); očekuje se 1124, osim ako već postoji veći. Uspjeh je `B1158_FINAL=PASS_...` i `EXIT=0`, uz {{TREE}} fajlova i {{ROUTES_AFTER}} ruta.

## Trajanje i prozor (tačne brojke)

Instalater planira najgori slučaj: kopija fajlova, kopija baze i jedno ponavljanje, 900 s za migraciju, sva čekanja prije i poslije grupa i vraćanje unazad. Vrijednost piše u izvještaju kao `requiredWindowSeconds`.

- Sa posljednjom rezervnom kopijom od oko 300 s: oko 10200 s, to jest 2 h 50 min.
- Sa podrazumijevanih 2400 s po kopiji: oko 16479 s, to jest oko 4 h 35 min. U tom slučaju alat sam odbija start sa `HOLD_PREFLIGHT_BLOCKED` i `SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_...`, i ništa nije promijenjeno.
- Ponovna provjera poslije grupe (čekanje pune pauze od oko 125 s pa još jedna provjera) nije dokazana testom. U najgorem slučaju prozor može biti do oko 2200 s duži od gornjih brojki.
- Stvarno trajanje B1158 na produkciji nije izmjereno. Za poređenje, Izvještaj 1123 (B1157: 123 fajla, 3 migracije) je planirao `requiredWindowSeconds` 9459 s; rezervna kopija baze (oko 0,97 GB) trajala je oko 34 s, provjera oko 6 s.

## Šta se mijenja za korisnike

- Izgled: stranice administracije prelaze na nove komponente (po opisima spojenih PR-ova: artikli, šifarnici i brendovi, postavke, korisnici, grupe, portal, garancije, inventar, audit). Novi CSS fajlovi su u paketu; `ald1n-dashboard-d1.css`, `ald1n-user-retirement-action.css`, `admin/access-control/partials/workspace-style.blade.php` i `bin/catalog-grid-responsive-smoke.php` su obrisani u main-u i ostaju na serveru neupotrebljeni (ništa se ne briše).
- Jedna migracija, `2026_11_24_000001_add_catalog_view_notes_permission`: dodaje dozvolu `catalog.view_notes` i dodjeljuje je svim grupama SubAgenata koje već vide katalog (administratori dobijaju sve dozvole automatski). To je upis podataka, bez promjene šeme tabela; ne dira obavještenja. Izmijenjen je i fajl `database/seeders/CoreAccessSeeder.php`, ali ga alat nikad ne pokreće.
- Vendor ostaje kakav jeste: `composer.lock` u main-u traži `web-auth/webauthn-lib` 5.3.17, a installer ne dira `composer.lock` ni `vendor`. Na hostu ostaje `vendor/web-auth/webauthn-lib` 5.3.9 dok se posebno ne ažurira. Alat to izuzima iz paketa i prijavljuje u `provenance.json` (`excluded`).

## Prozori sa greškom 500 (poznati, kratki)

- Redoslijed grupa je izveden pravilom: novi fajlovi, biblioteke, kontroleri, provider, rute, prikazi. Prikaz koji zove rutu koju payload briše ide u grupu C prije ruta; servis koji uklanja javnu metodu ide poslije kontrolera koji je još zovu. Preostala uska mjesta su navedena ispod („Poznata uska mjesta redoslijeda“).
- Strane iza prijave mogu dati kratku grešku 500 do oko 2 minuta poslije svake grupe (keširani bajtkod).

## Šta alat ne provjerava

Alat nikad ne prijavljuje korisnika. Poslije uspjeha otvorite sami, na računaru i na telefonu: kontrolnu tablu, listu porudžbina i jednu porudžbinu (forma uplate, status, povrat pošiljke), potraživanja, formu proizvoda i izvještaje. Mogu se javiti kratke prolazne greške 500 na stranama iza prijave, do oko 2 minuta poslije svake grupe ili vraćanja; sačekajte 2 minuta pa osvježite.

Poznata uska mjesta redoslijeda (iz `provenance.json`, polje `orderHazards`): {{HAZARDS}}

Rezervnu kopiju baze koju alat napravi (ključ `DB_BACKUP` u izvještaju) sačuvajte van servera prije sljedeće noćne, jer aplikacija čuva samo 2 najnovije.

## Šta ako padne (FAIL)

1. Ne pokrećite ništa drugo i ne brišite ništa. Pročitajte posljednje linije izvještaja (`B1158_FINAL=...`).
2. `HOLD_PREFLIGHT_BLOCKED`: ništa nije promijenjeno. Otklonite navedeni uzrok pa ponovite istu komandu.
3. `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`: greška prije migracije, fajlovi su vraćeni i baza nije dirnuta. Javite izvještaj.
4. `HOLD_ROLLED_BACK_SCHEMA_AHEAD`: migracija je već izvršena, fajlovi su vraćeni, a migracioni fajlovi ostaju. Baza je ispred koda, ništa se ne poništava. Ponovni start se odbija (`HOLD_SCHEMA_AHEAD`); javite izvještaj, potreban je novi instalater.
5. `HOLD_MIGRATION_NOT_RESUMED`: prekid poslije početka migracije, nastavak nije bio moguć. Fajlovi i baza ostaju kakvi jesu. Otklonite navedeni uzrok pa ponovite istu komandu: nastavlja samo unaprijed.
6. `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: fajlovi i migracije su na mjestu, ali prijem nije mogao da se ocijeni (na primjer stalni 429). Ista komanda ponavlja samo prijem, ili `--rollback` vraća fajlove.
7. `HOLD_ROLLBACK_INCOMPLETE`: vraćanje je stalo na pola. Izvještaj ima `RECOVERY_NOTE` i blok komandi za ručno završavanje; blok nikad ne briše migracione fajlove. Izvršite ga tačno kako je napisan.
8. Prekid sesije ili servera: pokrenite istu komandu. Od `MIGRATE_INTENT` nadalje nastavlja samo unaprijed (uključujući `ACCEPTANCE_INTENT`), prije toga vraća fajlove.
9. Poslije `PASS` nikad ne pokrećete ponovo B1144 do B1158 ni njihova vraćanja.

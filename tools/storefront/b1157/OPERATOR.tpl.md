# B1157: uputstvo za operatera

Autor: Ald1n <https://ald1n.com>

Paket prebacuje produkciju sa stanja B1156 (Izvještaj 1122, 8596 fajlova, 579 ruta, `origin/main` 94de4be5) na `origin/main` {{SRC8}}: {{FILES}} fajlova ({{NEW}} novih, {{MOD}} izmijenjenih), migracije: {{MIGRATIONS}}. Poslije instalacije: {{TREE}} fajlova i {{ROUTES_AFTER}} ruta. Instalater je u grani `{{BRANCH}}` (PR {{PR}}), CI je zelen (run {{RUN}}), a paket je napravljen iz tih bajtova.

## Šta se šalje na server

Dva fajla, oba u `/home/icaffeco`:

| Fajl | Veličina | SHA-256 |
| --- | --- | --- |
| `b1157-operator.zip` | {{ZIP_BYTES}} bajtova | `{{ZIP_SHA}}` |
| `b1157-operator.zip.sha256` | {{SHA_FILE_BYTES}} bajtova | sadrži gornji zbir |

Ništa drugo se ne šalje i ništa na serveru se ne briše.

## Prije pokretanja

1. Provjerite zbir. Komanda mora da ispiše `b1157-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1157-operator.zip.sha256
   ```

2. Mora da postoji tačno jedan izvještaj `1122-B1156-INSTALLER-*.md` sa `EXIT=0`, a stanje B1156 mora biti `INSTALLED_PASS` (SHA-256 izvještaja i id stanja su ugrađeni u paket). Ako nešto fali ili se razlikuje, instalater staje sa `HOLD` prije bilo koje izmjene.
3. Prodavnica je uključena, saobraćaj mali, `APP_URL` je `https://cms.ald1n.com`.
4. Počnite najkasnije oko 23:00 za rezervnu kopiju u 02:30. Subotom takođe najkasnije oko 23:00, jer se nedjeljna kopija u 03:10 računa kao isti prozor.

## Komande (cPanel terminal, nalog `icaffeco`)

```
unzip -o /home/icaffeco/b1157-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1157-installer.sh
```

Radnik radi odvojeno od SSH sesije. Ako sesija padne, instalacija se nastavlja; pratite izvještaj komandom `tail -f` na fajlu iz sljedećeg odjeljka. Posljednja linija izvještaja je `EXIT=0` za uspjeh.

Vraćanje fajlova na zahtjev (migracija se nikad ne poništava):

```
bash /home/icaffeco/ald1n-project/incoming/b1157-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1157-installer.sh --rollback --ignore-schedule
```

Druga komanda zaobilazi samo provjeru prozora rezervne kopije i koristi se isključivo za vraćanje.

## Izvještaj

Fajl se zove `<broj>-B1157-INSTALLER-<datum>-<vrijeme>.md` i nalazi se u `docs/operations`. Broj je najveći postojeći broj izvještaja plus 1 (wrapper ga računa sam, iz `docs/operations` na serveru i iz `origin/main`); očekuje se 1123, osim ako već postoji veći. Uspjeh je `B1157_FINAL=PASS_...` i `EXIT=0`, uz {{TREE}} fajlova i {{ROUTES_AFTER}} ruta.

## Trajanje i prozor (tačne brojke)

Instalater planira najgori slučaj: kopija fajlova, kopija baze i jedno ponavljanje, 900 s za migraciju, sva čekanja prije i poslije grupa i vraćanje unazad. Vrijednost piše u izvještaju kao `requiredWindowSeconds`.

- Sa posljednjom rezervnom kopijom od oko 300 s: oko 10200 s, to jest 2 h 50 min.
- Sa podrazumijevanih 2400 s po kopiji: oko 16479 s, to jest oko 4 h 35 min. U tom slučaju alat sam odbija start sa `HOLD_PREFLIGHT_BLOCKED` i `SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_...`, i ništa nije promijenjeno.
- Ponovna provjera poslije grupe (čekanje pune pauze od oko 125 s pa još jedna provjera) nije dokazana testom. U najgorem slučaju prozor može biti do oko 2200 s duži od gornjih brojki.
- Stvarno trajanje na produkciji nije izmjereno. B1156 je na produkciji napravio rezervnu kopiju baze od oko 0,97 GB za oko 34 s i provjerio je za oko 6 s.

## Šta se mijenja za korisnike

- Obični Administratori gube `backups.manage` i `automation.manage`: od grupe B te dvije mogućnosti ima samo SuperAdministrator. Ako neko iz uprave koristi rezervne kopije ili automatizaciju kao Administrator, upozorite ga prije instalacije.
- Dvije nove cron komande, `app:product-import-cleanup` (03:40) i `app:account-deletions-process` (04:20), rade samo ako cron na serveru poziva `php artisan schedule:run` svake minute. Provjerite da taj cron postoji; instalater ga ne dodaje i ne mijenja.

## Prozori sa greškom 500 (poznati, kratki)

- Akcije „Preuzmi porudžbinu“ (`accept`) i „Ažuriraj tracking“ (`updateTracking`): stari kontroleri ih zovu, a novi kod ih više nema. Od instalacije prvog kontrolera u grupi C do `route:clear` u grupi E te dvije akcije vraćaju grešku (transakcija se ne upisuje, podaci se ne kvare). Procjena iz pauza grupa C, D i E (svaka puna pauza oko 125 s): oko 3 do 6 minuta; nije mjereno, zato ne počinjite instalaciju dok se te akcije koriste.
- Ostale strane iza prijave mogu dati kratku 500 do oko 2 minuta poslije svake grupe (keširani bajtkod). Jedan stvaran prozor je uklonjen: `admin/orders/partials/row-action.blade.php` se instalira u grupi C, prije ruta, jer novi partial koristi samo rutu koja već postoji (u prvoj verziji paketa lista porudžbina je dobijala 500 oko 2 do 5 minuta).

## Šta alat ne provjerava

Alat nikad ne prijavljuje korisnika. Poslije uspjeha otvorite sami, na računaru i na telefonu: kontrolnu tablu, listu porudžbina i jednu porudžbinu (forma uplate, status, povrat pošiljke), potraživanja, formu proizvoda i izvještaje. Mogu se javiti kratke prolazne greške 500 na stranama iza prijave, do oko 2 minuta poslije svake grupe ili vraćanja; sačekajte 2 minuta pa osvježite.

Poznata uska mjesta redoslijeda (iz `provenance.json`, polje `orderHazards`): {{HAZARDS}}

Rezervnu kopiju baze koju alat napravi (ključ `DB_BACKUP` u izvještaju) sačuvajte van servera prije sljedeće noćne, jer aplikacija čuva samo 2 najnovije.

## Šta ako padne (FAIL)

1. Ne pokrećite ništa drugo i ne brišite ništa. Pročitajte posljednje linije izvještaja (`B1157_FINAL=...`).
2. `HOLD_PREFLIGHT_BLOCKED`: ništa nije promijenjeno. Otklonite navedeni uzrok pa ponovite istu komandu.
3. `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`: greška prije migracije, fajlovi su vraćeni i baza nije dirnuta. Javite izvještaj.
4. `HOLD_ROLLED_BACK_SCHEMA_AHEAD`: migracija je već izvršena, fajlovi su vraćeni, a migracioni fajlovi ostaju. Baza je ispred koda, ništa se ne poništava. Ponovni start se odbija (`HOLD_SCHEMA_AHEAD`); javite izvještaj, potreban je novi instalater.
5. `HOLD_MIGRATION_NOT_RESUMED`: prekid poslije početka migracije, nastavak nije bio moguć. Fajlovi i baza ostaju kakvi jesu. Otklonite navedeni uzrok pa ponovite istu komandu: nastavlja samo unaprijed.
6. `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: fajlovi i migracije su na mjestu, ali prijem nije mogao da se ocijeni (na primjer stalni 429). Ista komanda ponavlja samo prijem, ili `--rollback` vraća fajlove.
7. `HOLD_ROLLBACK_INCOMPLETE`: vraćanje je stalo na pola. Izvještaj ima `RECOVERY_NOTE` i blok komandi za ručno završavanje; blok nikad ne briše migracione fajlove. Izvršite ga tačno kako je napisan.
8. Prekid sesije ili servera: pokrenite istu komandu. Od `MIGRATE_INTENT` nadalje nastavlja samo unaprijed (uključujući `ACCEPTANCE_INTENT`), prije toga vraća fajlove.
9. Poslije `PASS` nikad ne pokrećete ponovo B1144 do B1156 ni njihova vraćanja.

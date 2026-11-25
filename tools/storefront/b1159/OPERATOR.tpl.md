# B1159: uputstvo za operatera

Autor: Ald1n <https://ald1n.com>

Paket prebacuje produkciju sa stanja B1158 (Izvještaj 1124, 8630 fajlova, 597 ruta, `origin/main` 8fc5a4c7) na `origin/main` {{SRC8}}: {{FILES}} fajl ({{NEW}} novih, {{MOD}} izmijenjen), migracije: {{MIGRATIONS}}. Poslije instalacije: {{TREE}} fajlova i {{ROUTES_AFTER}} ruta. Instalater je u grani `{{BRANCH}}` (PR {{PR}}), CI je zelen (run {{RUN}}), a paket je napravljen iz tih bajtova.

Promjena je samo CSS: `public/assets/css/ald1n-admin-catalog.css`. Slika kartice u katalogu više ne prekriva naslov i SKU (PR 264). Nema migracija, nema novih ruta, nema izmjene PHP koda.

## Šta se šalje na server

Dva fajla, oba u `/home/icaffeco`:

| Fajl | Veličina | SHA-256 |
| --- | --- | --- |
| `b1159-operator.zip` | {{ZIP_BYTES}} bajtova | `{{ZIP_SHA}}` |
| `b1159-operator.zip.sha256` | {{SHA_FILE_BYTES}} bajtova | sadrži gornji zbir |

Ništa drugo se ne šalje i ništa na serveru se ne briše.

## Prije pokretanja

1. Provjerite zbir. Komanda mora da ispiše `b1159-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1159-operator.zip.sha256
   ```

2. Mora da postoji tačno jedan izvještaj `1124-B1158-INSTALLER-*.md` sa `EXIT=0`, a stanje B1158 mora biti `INSTALLED_PASS` (SHA-256 izvještaja i id stanja su ugrađeni u paket). Ako nešto fali ili se razlikuje, instalater staje sa `HOLD` prije bilo koje izmjene.
3. Prodavnica je uključena, saobraćaj mali, `APP_URL` je `https://cms.ald1n.com`.
4. Sljedeća rezervna kopija je sutra u 02:30 (nedjeljom 03:10), pa je start večeras slobodan. Alat sam odbija start ako prozor ne staje.

## Komande (cPanel terminal, nalog `icaffeco`)

```
unzip -o /home/icaffeco/b1159-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1159-installer.sh
```

Radnik radi odvojeno od SSH sesije. Ako sesija padne, instalacija se nastavlja; pratite izvještaj komandom `tail -f` na fajlu iz sljedećeg odjeljka. Posljednja linija izvještaja je `EXIT=0` za uspjeh.

Vraćanje fajla na zahtjev:

```
bash /home/icaffeco/ald1n-project/incoming/b1159-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1159-installer.sh --rollback --ignore-schedule
```

Druga komanda zaobilazi samo provjeru prozora rezervne kopije i koristi se isključivo za vraćanje.

## Izvještaj

Fajl se zove `<broj>-B1159-INSTALLER-<datum>-<vrijeme>.md` i nalazi se u `docs/operations`. Broj je najveći postojeći broj izvještaja plus 1 (wrapper ga računa sam, iz `docs/operations` na serveru i iz `origin/main`); očekuje se 1125, osim ako već postoji veći. Uspjeh je `B1159_FINAL=PASS_...` i `EXIT=0`, uz {{TREE}} fajlova i {{ROUTES_AFTER}} ruta.

## Trajanje i prozor

Jedan fajl i bez migracije znači bez rezervne kopije baze i bez migracionog koraka. Instalater ipak planira najgori slučaj istim formulama kao prije i taj broj piše u izvještaju kao `requiredWindowSeconds`.

- Sa posljednjom rezervnom kopijom od oko 300 s: oko 10200 s, to jest 2 h 50 min.
- Sa podrazumijevanih 2400 s po kopiji: oko 16479 s, to jest oko 4 h 35 min. U tom slučaju alat sam odbija start sa `HOLD_PREFLIGHT_BLOCKED` i `SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_...`, i ništa nije promijenjeno.
- Kompajliranje prikaza u prijemu (`VIEW_COMPILE_CHECK` u izvještaju) preskače se jer paket nema prikaza ({{VIEWS}}). Izmjereno u CI fixture-u: {{VIEW_SECONDS}}.
- Stvarno trajanje B1159 na produkciji nije izmjereno.

## Šta se mijenja za korisnike

- Izgled: u administraciji, na stranici kataloga, slika kartice je u okviru 4:3 i ne prekriva naslov ni SKU.
- Vendor ostaje kakav jeste: `vendor/web-auth/webauthn-lib` na hostu je 5.3.9 i ovaj paket ga ne dira.
- Ništa drugo iz `origin/main` ne ulazi u ovaj paket (u njemu su samo mobilne, dokumentacione i gate izmjene).

## Poslije instalacije

Prelaz je jedan fajl, a stranice iza prijave ostaju na kratko bez nove verzije (keširani bajtkod, do oko 2 minuta). Alat nikad ne prijavljuje korisnika, pa provjerite sami: otvorite katalog u administraciji na računaru i na telefonu, uz tvrdo osvježavanje (Ctrl+F5), i provjerite da slika kartice ne prekriva naslov i SKU. Poznata uska mjesta redoslijeda: {{HAZARDS}}

## Šta ako padne (FAIL)

1. Ne pokrećite ništa drugo i ne brišite ništa. Pročitajte posljednje linije izvještaja (`B1159_FINAL=...`).
2. `HOLD_PREFLIGHT_BLOCKED`: ništa nije promijenjeno. Otklonite navedeni uzrok pa ponovite istu komandu.
3. `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`: greška poslije početka, fajl je vraćen na staro stanje i baza nije dirana. Javite izvještaj.
4. `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: fajl je na mjestu, ali prijem nije mogao da se ocijeni (na primjer stalni 429). Ista komanda ponavlja samo prijem, ili `--rollback` vraća fajl.
5. `HOLD_ROLLBACK_INCOMPLETE`: vraćanje je stalo na pola. Izvještaj ima `RECOVERY_NOTE` i blok komandi za ručno završavanje. Izvršite ga tačno kako je napisan.
6. Prekid sesije ili servera: pokrenite istu komandu, prije prijema vraća fajl.
7. Poslije `PASS` nikad ne pokrećete ponovo B1144 do B1159 ni njihova vraćanja.

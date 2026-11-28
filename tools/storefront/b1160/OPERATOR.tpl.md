# B1160: uputstvo za operatera

Autor: Ald1n <https://ald1n.com>

Paket prebacuje produkciju sa stanja B1158 (Izvještaj 1124, 8630 fajlova, 597 ruta, `origin/main` 8fc5a4c7) na `origin/main` {{SRC8}}: {{FILES}} fajl ({{NEW}} novih, {{MOD}} izmijenjen), migracije: {{MIGRATIONS}}. Poslije instalacije: {{TREE}} fajlova i {{ROUTES_AFTER}} ruta. Instalater je u grani `{{BRANCH}}` (PR {{PR}}), CI je zelen (run {{RUN}}), a paket je napravljen iz tih bajtova.

Paket obuhvata G7 stilove, kartice i galeriju kataloga, Napomene, Google prijavu, potvrdu lozinke pri brisanju naloga i obračun provizija, uključujući storno i PDF. Brisanja CSS-a u ciljnom Git stablu evidentirana su kao fajlovi koji ostaju na serveru uz proveru njihovih pinovanih SHA-256 vrednosti.

## Šta se šalje na server

Dva fajla, oba u `/home/icaffeco`:

| Fajl | Veličina | SHA-256 |
| --- | --- | --- |
| `b1160-operator.zip` | {{ZIP_BYTES}} bajtova | `{{ZIP_SHA}}` |
| `b1160-operator.zip.sha256` | {{SHA_FILE_BYTES}} bajtova | sadrži gornji zbir |

Ništa drugo se ne šalje i ništa na serveru se ne briše.

## Prije pokretanja

1. Provjerite zbir. Komanda mora da ispiše `b1160-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1160-operator.zip.sha256
   ```

2. Mora da postoji tačno jedan izvještaj `1124-B1158-INSTALLER-*.md` sa `EXIT=0`, a stanje B1158 mora biti `INSTALLED_PASS` (SHA-256 izvještaja i id stanja su ugrađeni u paket). Ako nešto fali ili se razlikuje, instalater staje sa `HOLD` prije bilo koje izmjene.
3. Prodavnica je uključena, saobraćaj mali, `APP_URL` je `https://cms.ald1n.com`.
4. Proverite raspored rezervnih kopija i planirano trajanje. Alat odbija pokretanje ako se potreban prozor preklapa sa zakazanom rezervnom kopijom.
5. Potvrdite operativni prozor pre odobrenja instalacije. Ne koristite dugme „Kredit isplaćen“ tokom instalacije. Ruta se objavljuje pre migracije koja dodaje kolone `settled_at` i `settled_by`, pa zahtev može da vrati grešku. Potvrdu isplate proverite posle uspešnog završetka migracija.

## Komande (cPanel terminal, nalog `icaffeco`)

```
unzip -o /home/icaffeco/b1160-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1160-installer.sh
```

Radnik radi odvojeno od SSH sesije. Ako sesija padne, instalacija se nastavlja; pratite izvještaj komandom `tail -f` na fajlu iz sljedećeg odjeljka. Posljednja linija izvještaja je `EXIT=0` za uspjeh.

Vraćanje fajla na zahtjev:

```
bash /home/icaffeco/ald1n-project/incoming/b1160-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1160-installer.sh --rollback --ignore-schedule
```

Druga komanda zaobilazi samo provjeru prozora rezervne kopije i koristi se isključivo za vraćanje.

## Izvještaj

Fajl se zove `<broj>-B1160-INSTALLER-<datum>-<vrijeme>.md` i nalazi se u `docs/operations`. Broj je najveći postojeći broj izvještaja plus 1 (wrapper ga računa sam, iz `docs/operations` na serveru i iz `origin/main`); očekuje se 1125, osim ako već postoji veći. Uspjeh je `B1160_FINAL=PASS_...` i `EXIT=0`, uz {{TREE}} fajlova i {{ROUTES_AFTER}} ruta.

## Trajanje i prozor

Instalater računa najgori slučaj i upisuje ga u izveštaj kao `requiredWindowSeconds`. Pre migracija pravi i ponovo proverava rezervnu kopiju baze.

- Sa posljednjom rezervnom kopijom od oko 300 s: oko 10200 s, to jest 2 h 50 min.
- Sa podrazumijevanih 2400 s po kopiji: oko 16479 s, to jest oko 4 h 35 min. U tom slučaju alat sam odbija start sa `HOLD_PREFLIGHT_BLOCKED` i `SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_...`, i ništa nije promijenjeno.
- Provera obuhvata {{VIEWS}} Blade prikaza (`VIEW_COMPILE_CHECK`). Trajanje zabeleženo u CI proveri je {{VIEW_SECONDS}}.
- Stvarno trajanje B1160 na produkciji nije izmjereno.

## Šta se mijenja za korisnike

- Izgled: u administraciji, na stranici kataloga, slika kartice je u okviru 4:3 i ne prekriva naslov ni SKU.
- Instalater proverava pinovanih 27 putanja u `vendor/`, uključujući `web-auth/webauthn-lib` 5.3.17. Zatim zamenjuje `composer.lock` verzijom ciljnog SHA, bez pokretanja Composera.

## Poslije instalacije

Na računaru i telefonu proverite da slika kartice ne prekriva naslov ni SKU i da galerija vraća skrol i fokus posle zatvaranja. Proverite i dugme za povratak na vrh, Napomene, Google poruke, potvrdu lozinke, obračun provizija i PDF. Poznati rizici redosleda su {{HAZARDS}}

## Šta ako padne (FAIL)

1. Ne pokrećite ništa drugo i ne brišite ništa. Pročitajte posljednje linije izvještaja (`B1160_FINAL=...`).
2. `HOLD_PREFLIGHT_BLOCKED`: ništa nije promijenjeno. Otklonite navedeni uzrok pa ponovite istu komandu.
3. `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED` označava da su fajlovi vraćeni pre izvršenja migracija. Pošaljite izveštaj.
4. `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED` označava da su fajlovi instalirani i da provera nije završena. Ista komanda ponavlja proveru.
5. `HOLD_ROLLBACK_INCOMPLETE`: vraćanje je stalo na pola. Izvještaj ima `RECOVERY_NOTE` i blok komandi za ručno završavanje. Izvršite ga tačno kako je napisan.
6. Od `MIGRATE_INTENT` ponovljena komanda nastavlja napred. Ako je baza migrirana, povratak obuhvata samo kod i čuva migracione fajlove. Migracije se nikad ne poništavaju.
7. Poslije `PASS` nikad ne pokrećete ponovo B1144 do B1160 ni njihova vraćanja.

# B1145: backup, tri migracije i skidanje runtime OFF klampe

Autor: Ald1n <https://ald1n.com>

Ovaj paket radi tri stvari i ostavlja Storefront ZATVOREN. Prekidač `public_storefront_open` u bazi ne diramo. Prodavnica ostaje zatvorena i posle uspešnog kraja, sve dok SuperAdmin ne uključi prekidač u admin panelu (`/admin/settings/public-storefront`).

## Komanda

```
bash /home/icaffeco/ald1n-project/incoming/b1145-controlled-migrate-unclamp.sh
```

Pokrenite je preko SSH naloga `icaffeco`. Skripta prvo očisti ekran. Radni deo se odvaja od SSH sesije. Ako veza pukne, posao se nastavlja, a izveštaj pratite sa `tail -f` (putanju ispisuje skripta). Izveštaj dobija sledeći broj posle 1107.

## Koliko traje

Obično 10 do 15 minuta. U najgorem slučaju oko 65 minuta. Najduži deo je backup: aplikacija ga pravi do 40 minuta (granica je 2400 s), a provera backup-a traje koliko i čitanje cele gzip datoteke. Backup proveravamo dva puta: odmah posle pravljenja i još jednom neposredno pre migracija.

Ostala čekanja su kratka i ograničena:

- popis artisan procesa: do 75 s, dva puta (provera na početku i neposredno pre migracija);
- popis aktivnih sesija baze neposredno pre migracija: do 75 s;
- provera ranijih backup-a pre novog (kao što radi aplikacija): po jedna `app:backup-verify` za najviše dva najnovija;
- web proba posle skidanja klampe: `revalidate_freq` plus 3 s, dva puta;
- `migrate --force` ima granicu od 900 s.

## Zakazani backup-i

Aplikacija pravi dnevni backup u 02:30 i nedeljni nedeljom u 03:10, po vremenu aplikacije (Europe/Belgrade). Skripta čita taj raspored iz same aplikacije i računa u njenoj vremenskoj zoni, a ne u zoni PHP komandne linije. Ako bi naš posao (četiri trajanja poslednjeg backup-a plus 15 minuta) mogao da se preklopi sa zakazanim backup-om, skripta stane pre ikakve izmene. Istu proveru ponavlja pre backup-a i neposredno pre migracija. Najsigurnije je pokretanje preko dana.

## Šta skripta radi, redom

1. **Provera (bez ijedne izmene).** Proverava lanac do B1144 PASS i tačnog Report1106, da aktivno stablo odgovara manifestu od 8531 fajla, da je `config/public_storefront.php` tačno klampa izračunata iz staged originala (`0cfbc864…`), da je `.env` symlink u retained stablu sa tačno jednim hard linkom i bez ACL-a, da `migrate:status` pokazuje 68 izvršenih i tačno 3 na čekanju, bazu, disk, inode-e i cPanel kvotu naloga, da nema backup-a u toku, artisan procesa ovog CMS-a, maintenance moda ni zaostalih fajlova. Proverava i da tabele `public_order_accesses` i `public_checkout_attempts`, ako već postoje, imaju sve jedinstvene ključeve, indekse i strane ključeve iz migracija. Iz `.env` ispisuje samo imena ključeva i true/false, nikad vrednosti. Ispiše sve rezultate, pa na bilo kakvu blokadu stane bez promena.
2. **Backup.** Svako pokretanje koje će izvršiti migracije pravi SVOJ novi backup: `app:backup-create --type=manual`, zatim `app:backup-verify --run=<id>`. Backup iz ranijeg pokretanja se nikad ne koristi ponovo. U izveštaju su samo id, ključ, veličina i status.
3. **Migracije.** Prvo `migrate --pretend`. Zatim, neposredno pre `migrate --force`, ponovo proveravamo backup ovog pokretanja (red postoji, status je completed, ista veličina, direktorijum postoji, `app:backup-verify` ponovo prolazi, nije stariji od par minuta), pa popis artisan procesa i aktivnih sesija baze. Ako backup ne prođe ponovnu proveru, pravimo novi jednom; ako ni on ne prođe, skripta stane pre migracija. `migrate --force` izvršava tačno tri migracije: `public_order_accesses`, `public_checkout_attempts` i rola `customer`. Nikad seed, fresh, rollback ni refresh. Posle toga čitamo šemu: 71/0, obe tabele sa svim ograničenjima, četiri kolone i role (`customer` postoji, `user` se zove SubAgent).
4. **Skidanje klampe.** Pravimo privatne kopije (0600) klampe i `.env`. Config dobija staged originalne bajtove jednim rename-om u istom direktorijumu. `.env` menjamo KROZ symlink: privremeni fajl nastaje pored pravog cilja u retained stablu, sa istim modom i istom grupom, i rename ide preko pravog cilja. Symlink ostaje isti. Menjaju se samo linije `PUBLIC_STOREFRONT_ENABLED=true` i `PUBLIC_CHECKOUT_ENABLED=true`.
5. **Prihvatanje.** Ciljano invalidiramo samo `config/public_storefront.php` (nikad globalni reset OPcache-a) i čekamo `revalidate_freq` plus 3 s. Zatim `config:show public_storefront` mora da pokaže `enabled=true` i `checkout_enabled=true`. `/katalog`, `/registracija`, `/korpa` i `/porucivanje` daju 404, a `/login` i `/up` daju 200. U Laravel logu ne sme biti novih ERROR/CRITICAL linija.

Ako bilo šta pukne posle koraka 4, skripta sama vraća klampu i `.env` iz privatnih kopija i ponovo proverava zatvoreno stanje (`RESTORED_CLAMPED`). Tuđu izmenu nikad ne prepisuje; tada prijavi `RESTORE_INCOMPLETE`.

## Ponovno pokretanje

Ista komanda nastavlja od sačuvane faze i dolazi do istog kraja. Posle `CLOSED_UNCLAMPED` samo ponovo proverava i ne radi ništa dvaput. Posle `RESTORED_CLAMPED` ponovo pokušava korak 4.

## Prvi backup pre migracija: skinite ga odmah

Prvi provereni backup napravljen na 68 migracija je tačka povratka. Skripta pamti njegov id i ključ i ispisuje ih u svakoj napomeni za oporavak (`firstPreMigrationBackup`) i na kraju (`FIRST_PRE_MIGRATION_BACKUP`). Aplikacija čuva samo 2 najnovija proverena backup-a. Već drugi sledeći backup (obično noćni u 02:30 sledeće noći, ili ranije ako skriptu pokrenete ponovo) ga briše. Zato ga skinite sa servera ili kopirajte van `BACKUP_PATH` odmah posle pokretanja. Kada bi novi backup obrisao taj prvi, skripta to najavi (`WARN_FIRST_PRE_MIGRATION_BACKUP`).

## Ako nešto stane: oporavak

Svaka napomena za oporavak je linija `RECOVERY_NOTE=...`. Gde vlasnik treba nešto da uradi, skripta ispiše tačnu komandu u liniji koja počinje sa `OWNER_COMMAND:`. Kopirajte sve posle dvotačke. Komande sadrže samo id-jeve, ključeve backup-a i imena tabela.

**Migracija nije uspela.** Automatskog rollback-a nema. Klampa ostaje, Storefront je zatvoren, a izveštaj daje id-jeve backup-a i šta je izvršeno. Pogledajte Laravel log, otklonite uzrok i pokrenite istu komandu. Ona pravi novi backup i izvršava samo ono što još čeka.

**Tabela ima kolone, ali ne i ograničenja** (`PUBLIC_TABLE_COLUMNS_WITHOUT_CONSTRAINTS`). To ostaje kada istekne zaključavanje između naredbi `Schema::create`. Ako je tabela prazna i njena migracija još čeka, skripta ispiše `OWNER_COMMAND` koji je briše; zatim pokrenite istu komandu i migracija je pravi ponovo sa svim ograničenjima. Ako tabela ima redove ili je migracija već upisana kao izvršena, komande nema: tada ograničenja dodaje programer, tačno kao u migraciji. Prodavnica do tada ostaje zatvorena.

**Backup ne prolazi proveru** (`BACKUP_VERIFY_FAILED`). Skripta zapamti tačno koji backup je njen i ispiše ga. Dok takav red stoji kao completed, a manje od dva druga backup-a prolaze proveru, aplikacija bi sledeći backup (i svaki noćni) označila kao neuspeo. Zato skripta tada stane već u proveri (`BACKUP_RETENTION_WOULD_REJECT_NEW_BACKUP`) i ništa ne pravi. Pokrenite ispisani `OWNER_COMMAND`: on taj red označi kao `failed`. Njegov direktorijum ostaje u `BACKUP_PATH` pod istim ključem i možete ga obrisati. Otklonite uzrok (najčešće neuspeo dump baze, pogledajte Laravel log) i pokrenite istu komandu.

**Backup red ostao `running`** (`BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING`). Prekinut backup (istek vremena, gašenje procesa) nikad ne ažurira svoj red i on ostaje `running`. Provera blokira 6 sati od njegovog početka (`gateClearsAt`). Ako je skripta sama prekinula backup, sačekala je da se proces završi i to piše u `CHILD_STOPPED`. Proverite da ne radi nijedan backup (`ps -u icaffeco -o pid,args`), pa pokrenite ispisani `OWNER_COMMAND` koji red označi kao `failed`, ili sačekajte 6 sati. Delimični direktorijum tog backup-a možete obrisati.

**Istek vremena.** Ako backup ili `migrate --force` traju duže od granice, skripta zaustavi ceo proces sa potprocesima, sačeka da se završe, sačuva HOLD fazu i ispiše napomenu. Posle prekinute migracije sledeće pokretanje proverava sva ograničenja tabela pre bilo čega drugog.

**Zaostao `~/.ald1n-stage/report-sequence.lock`.** Ako je spoljni deo skripte ubijen tvrdo (na primer `kill -9`) dok je rezervisao broj izveštaja, direktorijum zaključavanja ostaje i sledeće pokretanje kaže `another operator report reservation active`. Proverite da nijedna B1145 skripta ne radi (`ps -u icaffeco -o pid,args | grep b1145`). Ako ne radi, uklonite ga sa `rmdir ~/.ald1n-stage/report-sequence.lock` i pokrenite istu komandu.

**Disk ili kvota.** Potreban prostor uključuje i nekompresovan SQL koji aplikacija upiše pre gzip-a (do tri puta veličina podataka i indeksa baze) i kopirane privatne fajlove. Ako se cPanel kvota ne može pročitati iz CageFS školjke, izveštaj to kaže (`CPANEL_ACCOUNT_QUOTA_NOT_READ...`) i važi samo slobodan prostor fajl sistema.

## Napomene

- `--pretend` na ovoj šemi staje na prvoj migraciji koja čeka, jer pretend ne vraća redove pa `hasTable()` deluje netačno. Svaka migracija ima svoj tačan kod (`PUBLIC_ORDER_ACCESS_REQUIRES_EXISTING_ORDERS`, `PUBLIC_CHECKOUT_DEPENDENCIES_INCOMPLETE`, `B802_ROLES_TABLE_MISSING`). Prihvatamo samo tačan kod prve migracije koja čeka, i pri prvom pokretanju i pri nastavku. CI to proverava na pravoj MariaDB 10.11.19 na 68, 69 i 70 izvršenih. Pravi filter skupa fajlova je `migrate:status`.
- B1144 posle ovoga više ne pokrećite: njegovi otisci klampe i `.env` više ne važe.
- `.env` i retained stablo su trajna zavisnost privatnih podataka. Ne pomerajte ih i ne brišite.

## Sledeći korak za vlasnika

Prijavite se u admin panel na cms.ald1n.com i proverite da je pregled trgovca odobren. Zatim uključite prekidač javne prodavnice i proverite `/katalog` i jednu probnu porudžbinu. Isti prekidač gasi prodavnicu u hitnom slučaju.

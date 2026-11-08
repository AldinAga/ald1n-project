# B1145: backup, tri migracije i skidanje runtime OFF klampe

Autor: Ald1n <https://ald1n.com>

Ovaj paket radi tri stvari i ostavlja Storefront ZATVOREN. Prekidač `public_storefront_open` u bazi ne diramo; vlasnik ga uključuje kasnije u admin panelu.

## Komanda

```
bash /home/icaffeco/ald1n-project/incoming/b1145-controlled-migrate-unclamp.sh
```

Pokrenite je preko SSH naloga `icaffeco`. Radni deo se odvaja od SSH sesije. Ako veza pukne, posao se nastavlja, a izveštaj pratite sa `tail -f` (putanju ispisuje skripta). Izveštaj dobija sledeći broj posle 1107.

Ne pokrećite je između 02:00 i 03:40. Tada rade zakazani backup-i i skripta će stati sama.

## Šta skripta radi, redom

1. **Provera (bez ijedne izmene).** Proverava lanac do B1144 PASS i tačnog Report1106, da aktivno stablo odgovara manifestu od 8531 fajla, da je `config/public_storefront.php` tačno klampa izračunata iz staged originala (`0cfbc864…`), da je `.env` symlink u retained stablu, da `migrate:status` pokazuje 68 izvršenih i tačno 3 na čekanju, bazu, disk i inode-e, da nema backup-a u toku, artisan procesa ovog CMS-a, maintenance moda ni zaostalih fajlova. Iz `.env` ispisuje samo imena ključeva i true/false, nikad vrednosti. Ispiše sve rezultate, pa na bilo kakvu blokadu stane bez promena.
2. **Backup.** `app:backup-create --type=manual`, zatim `app:backup-verify --run=<id>`. U izveštaju su samo id, veličina i status. Neuspeh je HOLD i ništa drugo se ne menja.
3. **Migracije.** Prvo `migrate --pretend`, zatim `migrate --force` za tačno tri migracije: `public_order_accesses`, `public_checkout_attempts` i rola `customer`. Nikad seed, fresh, rollback ni refresh. Posle toga čitamo šemu: 71/0, obe tabele, četiri kolone i role (`customer` postoji, `user` se zove SubAgent).
4. **Skidanje klampe.** Pravimo privatne kopije (0600) klampe i `.env`. Config dobija staged originalne bajtove jednim rename-om u istom direktorijumu. `.env` menjamo KROZ symlink: privremeni fajl nastaje pored pravog cilja u retained stablu, sa istim modom, i rename ide preko pravog cilja. Symlink ostaje isti. Menjaju se samo linije `PUBLIC_STOREFRONT_ENABLED=true` i `PUBLIC_CHECKOUT_ENABLED=true`.
5. **Prihvatanje.** Ciljano invalidiramo samo `config/public_storefront.php` (nikad globalni reset OPcache-a) i čekamo `revalidate_freq` plus 3 s. Zatim `config:show public_storefront` mora da pokaže `enabled=true` i `checkout_enabled=true`. `/katalog`, `/registracija`, `/korpa` i `/porucivanje` daju 404, a `/login` i `/up` daju 200. U Laravel logu ne sme biti novih ERROR/CRITICAL linija.

Ako bilo šta pukne posle koraka 4, skripta sama vraća klampu i `.env` iz privatnih kopija i ponovo proverava zatvoreno stanje (`RESTORED_CLAMPED`). Tuđu izmenu nikad ne prepisuje; tada prijavi `RESTORE_INCOMPLETE`.

## Ponovno pokretanje

Ista komanda nastavlja od sačuvane faze i dolazi do istog kraja. Posle `CLOSED_UNCLAMPED` samo ponovo proverava i ne radi ništa dvaput. Posle `RESTORED_CLAMPED` ponovo pokušava korak 4.

## Ako migracija ne uspe

Automatskog rollback-a nema. Klampa ostaje, Storefront je zatvoren, a izveštaj daje id proverenog backup-a i šta je izvršeno. Pogledajte Laravel log, otklonite uzrok i pokrenite istu komandu. Ona pravi novi backup i izvršava samo ono što još čeka.

## Napomene

- `--pretend` na ovoj šemi staje na prvoj migraciji, jer pretend ne vraća redove pa `hasTable('orders')` deluje netačno (kod `PUBLIC_ORDER_ACCESS_REQUIRES_EXISTING_ORDERS`). To je očekivano i CI to proverava na pravoj MariaDB 10.11.19. Pravi filter skupa fajlova je `migrate:status`. Ako Laravel ovu grešku upiše u log, to se desi pre početne tačke za proveru loga i ne računa se.
- Backup komanda posle uspeha zadržava samo 2 najnovija završena backup-a (postojeća politika aplikacije). Ako vam treba duže čuvanje, skinite backup sa servera. Pre brisanja aplikacija proverava stare backup-e. Ako novi backup ne prođe proveru, aplikacija ga sama obriše dok postoje dva ispravna, a skripta staje sa `BACKUP_RUN_NOT_COMPLETED`. Ako ispravnih nema dovoljno, sledeći backup aplikacija odbija (`BACKUP_CREATE_FAILED`). U oba slučaja ništa drugo nije promenjeno; pogledajte backup-e u admin panelu pre novog pokretanja.
- B1144 posle ovoga više ne pokrećite: njegovi otisci klampe i `.env` više ne važe.
- `.env` i retained stablo su trajna zavisnost privatnih podataka. Ne pomerajte ih i ne brišite.

## Sledeći korak za vlasnika

Prijavite se u admin panel na cms.ald1n.com i proverite da je pregled trgovca odobren. Zatim uključite prekidač javne prodavnice i proverite `/katalog` i jednu probnu porudžbinu. Isti prekidač gasi prodavnicu u hitnom slučaju.

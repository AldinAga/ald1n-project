# B1148: Liquid Glass izgled, izbor teme i logo na produkciji

Autor paketa: Ald1n <https://ald1n.com>

## Šta se instalira

Instaliramo tačno 11 fajlova iz PR #155 (storefront glass): 2 nova i 9 izmenjenih. Dobijate
novi izgled prodavnice i pravnih stranica, prekidač teme Sistem / Svetla / Tamna (bez
JavaScript-a, kroz kolačić `shop_theme`) i jedan izvor za naziv i logo prodavnice iz
Podešavanja > Izgled sajta. Dodaje se jedna nova ruta, `POST /tema`, sa sopstvenim ograničenjem
zahteva. Testove ne instaliramo.

Redosled je obavezan: prvo dve nove klase, pa `AppServiceProvider` (ograničenje
`public-shop-theme`), `PublicNoScriptCsp`, `PublicCatalogPresentation`, `bootstrap/app.php`,
`routes/web.php`, CSS, pa tek na kraju tri Blade šablona. Između grupa čekamo da svi PHP procesi
vide nove fajlove. Drugim redosledom svaka javna stranica bi vratila 500 ili bi `/tema` vratila
429.

## Pre pokretanja proverite sami

1. Postavite `b1148-operator.zip` i `b1148-operator.zip.sha256` u `/home/icaffeco`. Komanda mora
   da ispiše `b1148-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1148-operator.zip.sha256
   ```

2. Tačno jedan izveštaj `1112-B1147-CATALOG-OVERLAY-*.md`, a na njegovom kraju
   `B1147_FINAL=PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED` i `EXIT=0`:

   ```
   ls /home/icaffeco/ald1n-project/docs/operations/1112-B1147-CATALOG-OVERLAY-*.md
   tail -n 3 /home/icaffeco/ald1n-project/docs/operations/1112-B1147-CATALOG-OVERLAY-*.md
   ```

3. U `incoming/` su i dalje pomoćni fajlovi iz ranijih paketa, nepromenjeni: `b1134-files.php`,
   `b1144-gates.php`, `b1144-probe.php`, `b1144-web.php`, `b1145-env.php`, `b1145-app-state.php`,
   `b1145-coordinator.php`, `b1146-probe.php`, `b1146-app-check.php`, kao i `b1147-web.php`,
   `b1147-app-check.php` i `b1147-coordinator.php` iz B1147 paketa. Ako neki fali ili je izmenjen,
   kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY` i ništa nije promenjeno.
4. Prodavnica je uključena, saobraćaj je mali, a do backup-a (02:30 svaki dan, 03:10 nedeljom)
   imate bar 2 sata.

## Šta pokrećete

```
unzip -o /home/icaffeco/b1148-operator.zip -d /home/icaffeco/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1148-glass-overlay.sh
```

Radnik ide u pozadini, a izveštaj se upisuje u
`docs/operations/<broj>-B1148-GLASS-OVERLAY-<datum>-<vreme>.md`. Ako se SSH prekine, posao se
nastavlja (`tail -f` na izveštaj).

Obično traje 8 do 11 minuta. Uz uporni 429 traje duže, a najgori slučaj zajedno sa vraćanjem je
oko 32 minuta. Toliko vremena do sledećeg backup-a skripta i traži, inače staje pre bilo kakve
izmene.

## Šta proveravamo pre početka

Svih 8546 fajlova mora imati tačan digest (manifest + B1146 + B1147), a svaki od 11 fajlova tačno
staru verziju. Keš ruta ne sme postojati, jer bi sakrio novu rutu; ako postoji, staje se pre
izmene (`ROUTE_CACHE_PRESENT_...`). Staje se i ako je prodavnica isključena, ako radi artisan
komanda, ako je backup blizu, ako nema mesta na disku ili u kvoti, ako je composer autoload
autoritativan ili ako javne stranice nemaju `X-Robots-Tag: noindex`.

Izveštaj ima i red `INFO_STOREFRONT_BRANDING` za problem sa logoom: da li je `site_name` prazan,
podrazumevani `Ald1n CMS` ili vaš; da li su svetli logo, tamni logo i favicon postavljeni, u
`site-assets/`, u formatu koji prodavnica prikazuje (logo PNG, JPG, JPEG ili WEBP; favicon PNG ili
ICO; SVG se ne prikazuje) i da li fajl postoji; i da li je `public/storage` ispravna veza. Vrednosti
ne ispisujemo.

## Šta proveravamo posle (GET i jedan HEAD, bez POST-a)

`/katalog`, proizvod, `/korpa`, osam pravnih stranica: 200 sa novim prekidačem teme, CSP
`script-src 'none'`, `noindex`. `/katalog` šalje `Vary: Cookie` i ima ispravan JSON-LD.
Sortiranje 200, nepoznato sortiranje 302/422, `?page=999` 404, `/registracija`, `/login` i
`/up` 200. Novi CSS i tačan URL `?v=202611091` imaju nova pravila. `GET /tema` i `HEAD /tema`
vraćaju 405 (ruta postoji, prima samo POST), nikad 5xx. Preko CLI-ja: ruta `public-shop.theme`
(`POST tema`), ograničenje `public-shop-theme` i CSRF izuzetak `tema` postoje. U `laravel.log`
nema novih grešaka, a svih 8548 fajlova ima tačan digest.

Ako nešto padne, sami vraćamo stare fajlove obrnutim redom: prvo šabloni, pa `view:clear` i puno
čekanje pre uklanjanja rute, a puno čekanje i pre uklanjanja novih klasa. Kraj je
`HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`. Uporni 429, isključena prodavnica ili nestali `noindex`
tokom provera ostavljaju tačne fajlove i daju `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: ista
komanda kasnije ponavlja samo provere.

Uspešan kraj je `B1148_FINAL=PASS_INSTALLED_GLASS_OVERLAY_SHOP_STATE_UNCHANGED` i `EXIT=0`.

## Šta radite vi posle

1. Otvorite `https://cms.ald1n.com/katalog` u privatnom prozoru i kliknite Sistem, Svetla i
   Tamna. Svaki klik ponovo učita istu stranicu u toj temi. Probajte na telefonu i računaru.
2. Pogledajte logo u zaglavlju u obe teme. Ako ga nema, pogledajte `INFO_STOREFRONT_BRANDING`.
3. Otvorite jednu pravnu stranicu i jedan proizvod.
4. Cloudflare: novi šablon traži novi URL `?v=202611091`, pa CSS ne morate da čistite. Ako stranica
   izgleda neuređeno ili po starom, očistite samo
   `https://cms.ald1n.com/assets/css/ald1n-public-storefront.css?v=202611091`.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1148-glass-overlay.sh --rollback
```

Prodavnica može ostati uključena. Ako je backup blizu, a vraćanje ne može da čeka, dodajte
`--ignore-schedule` (preskače samo proveru backup termina). Kraj je `PASS_ROLLED_BACK_BY_OWNER`.
Posle vraćanja očistite u Cloudflare-u
`https://cms.ald1n.com/assets/css/ald1n-public-storefront.css?v=202611082` i
`https://cms.ald1n.com/assets/css/ald1n-public-storefront.css`.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od `bash -e <<'B1148_RECOVERY'`
do `B1148_RECOVERY`. Prvo pokrenite istu komandu još jednom; blok nalepite ceo samo ako to ne
uspe.

## Šta ne diramo

Prekidač prodavnice i odobrenje trgovca samo čitamo (red `MERCHANT_APPROVAL`, `"ok":"UNCHANGED"`).
Nema migracija, ne menjamo `.env` ni `config/`, ne pravimo i ne brišemo keš ruta, OPcache ne
resetujemo nego ciljano osvežavamo ovih 11 fajlova, šablone samo brišemo (`view:clear`). Temu
nikad ne menjamo POST zahtevom; to je vaša provera u pregledaču.

Posle B1148 ne pokrećite B1144, B1145, B1146 ni B1147, ni B1147 `--rollback`.

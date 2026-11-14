# Plan: potpuni Liquid Glass redizajn CMS-a (Laravel) i Android aplikacije

Upisano: 2026-11-10, na zahtev vlasnika. Status: PLAN, ne počinje dok vlasnik ne javi "krećemo".
Ilustracija: `redesign-mockup/ald1n-redesign-ilustracija.png` (izvor: `redesign-mockup/mockup.html`, svi podaci izmišljeni).
Interaktivni pregled (računar, tablet, telefon, Android aplikacija, svetla/tamna/sistemska tema): `redesign-mockup/ald1n-liquid-glass-pregled.html`. Otvara se u bilo kom pregledaču; adresa sa `#dev=android&th=dark` otvara određeni prikaz.

## Odluke vlasnika (zabeležene)

1. Cilj je izgled 10/10 u CMS-u, Android aplikaciji i prodavnici, u Liquid Glass jeziku, sa čitljivošću teksta na prvom mestu.
2. **Uklanjaju se svi prethodni CSS i ostali fajlovi dizajna** koje je vlasnik koristio. Nema slojevanja novog dizajna preko starog: novi dizajn zamenjuje stari, a stari fajlovi se brišu tek kad ih novi potpuno zameni i prođe provera.
3. Prodavnica je već prešla na Liquid Glass (B1148, B1149 u toku). Ona je referenca za tokene, komponente i obrasce čitljivosti.

## Dizajn jezik (jedan za sve)

- Tokeni: boje, razmaci, radijusi, senke, blur i zasićenost definisani jednom (CSS promenljive za web, jedan izvor tema za Android).
- Površine: poluprovidne, slojevite, sa svetlosnom ivicom na vrhu, ali uvek dovoljno guste ispod teksta (kontrast najmanje 4,5:1, mali tekst što bliže 7:1).
- Teme: svetla, tamna, sistemska. Prekidač samo ikone (monitor, sunce, mesec), promena odmah.
- Rezerve: bez `backdrop-filter`, `prefers-reduced-transparency`, `prefers-contrast: more`, `forced-colors` prelaze na pune, neprozirne površine. Poštuje se `prefers-reduced-motion`.
- Komponente: kartice, tabele, statusne pilule (tačka plus tekst, nikad samo boja), forme, dugmad (jedna glavna akcija po ekranu), navigacija, prazna stanja, grafikoni.
- Obavezno na svakoj stranici/ekranu gde se primenjuje pravilo vlasnika: dugme "Nazad na vrh" (fiksno dole desno, safe-area, pojavljuje se posle prvog ekrana skrola, aria-label na jeziku stranice, vidljiv fokus, reduced-motion, čitljivo u obe teme).

## Faze

**Faza 0, osnova (bez promene izgleda produkcije).** Inventar svih trenutnih CSS/JS/slika/ikona fajlova CMS-a i aplikacije i svake stranice/ekrana koji ih koristi. Mapa zamene "stari fajl, nova komponenta". Tokeni i biblioteka komponenti u izolaciji, sa vizuelnim testovima (Chromium i WebKit, 375/430/768/1280, svetla/tamna, axe kontrast).

**Faza 1, CMS (Laravel).** Redom: zajednički layout i navigacija, kontrolna tabla, porudžbine, artikli i lager, garancije, kupci, izveštaji, podešavanja (uključujući Javna prodavnica, E-mail SMTP, Izgled sajta), prijava. Jedan modul po koraku, svaki sa zelenim CI-jem (puna CMS provera plus vizuelni QA), nezavisnom recenzijom i instalacionim paketom po obrascu B1146 do B1148 (owner-run skripta, rollback, smoke provere). Posle poslednjeg modula: brisanje starih CSS fajlova uz dokaz da ih ništa ne referencira.

**Faza 2, Android aplikacija (Expo/React Native).** Tokeni i komponente na istom jeziku (staklene kartice, donja navigacija, statusi), svetla/tamna tema, pristupačnost (TalkBack, veličine fonta), performanse (blur na slabijim uređajima sa rezervom). Isti redosled ekrana kao CMS. Uklanjanje starih stilova i neiskorišćenih resursa. Izdanje ide kroz postojeći put (EAS, M16 pravila, Play), tek posle odobrenja vlasnika.

**Faza 3, čišćenje i zatvaranje.** Brisanje svih zamenjenih CSS/JS/slika fajlova (prvo suvo probno pokretanje i popis), provera da nema mrtvih referenci, merenje težine stranice pre i posle, završni izveštaj sa snimcima pre i posle.

## Pravila izvršavanja (važe i dalje)

- Ceo izvorni kod piše Claude; vlasnik odobrava merge u main, svaki korak na hostingu i svaku objavu.
- Host se menja samo skriptama koje vlasnik sam pokreće (isti obrazac kao B1144 do B1148).
- Bez izmišljenih činjenica; tekstovi na srpskom sa pravilnim dijakriticima; autor u fajlovima: Ald1n <https://ald1n.com>.
- Prodavnica ostaje bez indeksiranja (noindex), a prekidač prodavnice, naplata i potvrda uslova se ne diraju.
- Svaka faza ima ugovorene kriterijume pre početka: kontrast, brzina, pristupačnost, spisak ekrana, spisak fajlova za brisanje.

## Odluke vlasnika po pitanjima (2026-11-10)

1. **Redosled:** prepušteno Claude-u. Odluka: prvo CMS, pa aplikacija (bezbednije i jednostavnije), a deljeni tokeni se prave odmah u Fazi 0 da aplikacija kasnije samo preuzme isti izvor. Paralelni rad nema smisla: svaki CMS modul prolazi isti ciklus (CI, recenzija, instalaciona skripta koju vlasnik pokreće), pa bi dva toka samo opteretila vlasnika.
2. **Osnova:** vlasnik dozvoljava zamenu ako postoji bolje univerzalno rešenje za Web, Android i iOS. Odluka: osnova postaje **jedan platformski neutralan izvor tokena** (boje, razmaci, radijusi, senke, blur, tipografija u jednom JSON-u) iz kog se generišu CSS promenljive za web, tema za React Native/Android i, kad zatreba, iOS. Za to se koristi postojeći alat otvorenog koda Style Dictionary (Amazon), čija je namena upravo višeplatformski izvoz tokena; pre prvog korišćenja proverava se aktuelna verzija, licenca i podrška za React Native u Fazi 0. Postojeći oktobarski "Liquid Glass CMS" se ne uzima kao osnova nego kao spisak komponenti koje su se pokazale dobrim; njegovi CSS fajlovi se zamenjuju i brišu po planu iz Faze 1 i 3.
3. **"Nazad na vrh" u administraciji:** nije neophodno, ne dodaje se (važi i dalje za javni sajt, prodavnicu i HTML preglede).
4. **SVG logo:** OBAVEZNO. Vidi odeljak ispod.

## SVG logo (obavezno, bezbedno)

SVG može da nosi skripte i spoljne reference, pa se prihvata samo uz stroga pravila:
- Strogi sanitajzer po listi dozvoljenih elemenata i atributa (DOMDocument): uklanja se `script`, `foreignObject`, rukovaoci događaja (`on*`), spoljni `href`/`xlink:href` (dozvoljeni samo interni fragmenti), `style` sa `url()` ili `@import`, `DOCTYPE` i entiteti (zaštita od XXE), `use` ka spoljnim fajlovima, animacije koje menjaju `href`.
- Čuva se samo očišćena verzija, ograničena veličina (do 200 KB), obavezan `viewBox`, provera da je zaista XML/SVG (ne samo ekstenzija).
- Služi se isključivo kroz `<img>` (u tom režimu skripte ne rade) i sa `Content-Type: image/svg+xml`, `X-Content-Type-Options: nosniff` i strogim CSP-om za sam fajl (`default-src 'none'; style-src 'unsafe-inline'`); direktno otvaranje fajla ne može da izvrši skriptu.
- Podrška svuda gde se logo koristi: zaglavlje i podnožje prodavnice, favicon (SVG sa PNG rezervom), administracija, PDF dokumenti (provera da PDF biblioteka renderuje SVG, inače se koristi rasterizovana kopija pri otpremanju), Android aplikacija (react-native-svg).
- Testovi: tvrdnje o odbijanju zlonamernih uzoraka (skripta, `onload`, spoljni href, XXE, vrlo veliki fajl, ne-SVG sa SVG ekstenzijom), vizuelna provera u svetloj i tamnoj temi (par logotipa).
- Ovo je nezavisno od redizajna i može da ide **pre** njega kao zasebna mala isporuka (jedna instalaciona skripta). Čeka se samo vlasnikovo "krećemo" (ili posebno odobrenje da se SVG odradi odmah).

## Popravke PRE redizajna (vlasnik, 2026-11-10)

1. **Logo u zaglavlju prodavnice je pokvaren (404).** Dokaz: stranica `/katalog` šalje `<img src="/storage/site-assets/xJCk8O3W….png">` (svetli) i `…/jhaUHhdzGKZA….png` (tamni), oba vraćaju HTTP 404 sa originalnog servera (Cloudflare ne kešira, `BYPASS`), dok favicon iz iste fascikle (`BIroGRKr….png`) vraća 200. Znači veza `public/storage` radi, a ova dva fajla nisu dostupna preko veba. Preflight B1148 je prijavio da fajlovi postoje, što se ne slaže, pa je uzrok još neutvrđen (kandidati: fajlovi ne postoje na disku pod tim imenom, dozvole/vlasnik fajla, ili podešavanje pokazuje na stare vrednosti posle uklanjanja i ponovnog otpremanja 8. novembra). Koraci: vlasnik pokreće na hostingu jednu komandu za listanje (vidi dole) ili `ald1n-logo-diagnose-v6.sh`; zatim popravka (ponovno otpremanje loga, ili ispravka dozvola, ili kod koji prikazuje poruku kad datoteka nedostaje) i test da naziv loga u podešavanjima uvek odgovara fajlu na disku.
2. **Slike artikala u katalogu izgledaju loše (beli okvir).** Okvir nije deo fotografija: to je "pozornica" za sliku koju sam uveo u redizajnu kataloga (neutralna svetla površina, `object-fit: contain`, odnos 4:3). Slike artikala su uspravni plakati/flajeri, pa se u horizontalni okvir smeste umanjeno, sa velikim praznim poljima, a u tamnoj temi je pozornica svetlo siva i odudara od stakla. Rešenje: pozornica prati temu (staklo u tamnoj, topla bela u svetloj), odnos prilagođen uspravnim slikama (oko 4:5), slika popunjava širinu bez umanjivanja, tanak okvir i senka samo oko same fotografije, bez velikih polja; provera na svim 46 stvarnim artiklima (ne samo probnim) i na slikama sa belom i tamnom pozadinom.
2b. **Stranica proizvoda nema galeriju (vlasnik, 2026-11-10).** Prikazuje se samo jedna slika, isti beli okvir kao u katalogu. Podaci to dozvoljavaju: model `ProductImage` i servisi za objavu i izvedene veličine (`ProductImageService`, `ProductImagePublicationService`, `ProductImageDerivativeService`) već podržavaju više slika po artiklu, a javni katalog (`PublicShopController`/`PublicRetailCatalogService`) vraća samo prvu. Rešenje: galerija na stranici proizvoda (glavna slika, traka sličica, listanje prstom na telefonu i strelicama na tastaturi, uvećanje, opisi slika, lenjo učitavanje), uz pozornicu koja prati temu i uspravne plakate; u katalogu ostaje prva slika; JSON-LD sa svim slikama. Provera na stvarnim artiklima koji imaju više slika. Grana: `codex/product-gallery-20261110` (u izradi, zajedno sa stavkom 2).
3. **SVG logo (obavezno):** u izradi (grana `codex/svg-logo-20261110`).
4. **Grana B1149:** promena teme bez ponovnog učitavanja, ikonice, SMTP stranica, popravka otpremanja loga: u recenziji.
5. **Tekst "Kolačići":** dodati kolačić `shop_theme` kroz editor pravnih tekstova (vlasnik), uz ponovnu potvrdu uslova.

Komanda za vlasnika (samo čitanje) za stavku 1:
`ls -la ~/ald1n-project/apps/cms/current/storage/app/public/site-assets/ ; readlink -f ~/ald1n-project/apps/cms/current/public/storage ; stat -c '%U:%G %a %s %n' ~/ald1n-project/apps/cms/current/storage/app/public/site-assets/xJCk8O3WOoWLX47eS2uMRCuGOZqes54CBRd5pxnP.png`

## Otvoreno

Nema otvorenih pitanja pred start. Čeka se vlasnikovo "krećemo", a prvo se rešavaju popravke iz prethodnog odeljka.

## Pre starta u toku (ne blokira plan)

B1149: promena teme bez ponovnog učitavanja, ikonice, SMTP stranica i popravka otpremanja loga.

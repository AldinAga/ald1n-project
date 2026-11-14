# Kobalt tokeni

Jedna paleta, zvana Kobalt, za javnu prodavnicu, admin CMS i Android aplikaciju. To je paleta koju prodavnica već isporučuje. Vrednosti su preuzete bez izmena iz `ald1n-public-storefront.css` i `ald1n-public-gallery.css`.

Autor: Ald1n <https://ald1n.com>

## Izvor

`packages/tokens/ald1n.tokens.json` je jedini izvor, u DTCG obliku (`$value`, `$type`, grupe). Boje i senke postoje po režimu (`dark`, `light`). Razmaci, radijusi, blur, kretanje, tipografija, veličine i fokus su zajednički. Ključevi su sortirani, a generator to proverava.

Statusi se preslikavaju sa imena iz prodavnice: `ok` je `ok`, `off` je `off`, `info` je `alert`, `danger` je `error`, `warning` je `draft`. Tačka (`dot`) je boja tačke kod `ok` i `off`, a kod ostalih boja ivice. `warning.strong` je `draft-strong`.

## Regenerisanje

```
node scripts/generate-ald1n-tokens.mjs           # upisuje oba izlaza
node scripts/generate-ald1n-tokens.mjs --check   # ne piše ništa, pada ako je nešto zastarelo
node --test scripts/generate-ald1n-tokens.test.mjs
```

Potreban je Node 20 ili noviji, bez npm paketa. Izlazi su bajt-stabilni: sortirani ključevi, LF, bez vremenskih oznaka.

- `apps/cms/current/public/assets/css/ald1n-tokens.css`: CSS promenljive sa prefiksom `--a1-`.
- `apps/mobile/current/src/design/ald1n-tokens.kobalt.generated.ts`: tipizirani modul za React Native. Još nije povezan sa aplikacijom.

`--check` pokreće tri provere: izlazi su sinhronizovani sa izvorom, kontrast prolazi, a vrednosti su jednake prodavnici.

## Dve konvencije za temu

Generisani CSS radi sa obe, bez izmene bilo koje.

| Šta | Prodavnica | Admin CMS |
| --- | --- | --- |
| Atribut | `data-theme="system\|light\|dark"` | `data-theme="light\|dark"` plus `data-theme-mode="auto\|light\|dark"` |
| Čuvanje izbora | kolačić `shop_theme` | `localStorage` ključ `ald1n-theme-mode` |
| Ko razrešava „auto" | CSS (`prefers-color-scheme`) | skripta u `layouts/app.blade.php`, uvek upisuje `light` ili `dark` |

Selektori u CSS-u:

- `:root` je tamna tema, podrazumevana.
- `:root[data-theme="light"]` je svetla.
- `:root[data-theme="system"]` je svetla kada je `prefers-color-scheme: light`.

`data-theme-mode` nam ne treba, jer je razrešena vrednost uvek u `data-theme`. Razlika prema CSS-u prodavnice: stranica bez atributa je kod nas tamna, kod prodavnice svetla pod svetlim sistemom. Oba potrošača uvek postavljaju atribut.

Rezervni slojevi su pregazivanja tokena posle tema: `@supports not (backdrop-filter)`, `prefers-reduced-transparency`, `prefers-contrast: more` i `forced-colors`. Prva tri prate pravila prodavnice. Sloj za `forced-colors` je izveden: prodavnica to rešava po komponenti sistemskim bojama, a mi iste boje primenjujemo na nivou tokena.

## Dokaz da je Kobalt jednak prodavnici

Generator čita oba CSS-a prodavnice i poredi svaki token sa istim svojstvom u oba režima, preko tabele `PARITY_COLORS` (i `PARITY_SHADOWS`, `PARITY_GRADIENTS`) u skripti. Provera ide u oba smera: svaki token mora imati preslikavanje, a svako svojstvo iz tema prodavnice mora biti preslikano ili navedeno u `NOT_TOKENISED` sa razlogom (na primer `--chevron` je SVG data URI). Proveravamo i da je blok `system` u prodavnici identičan bloku `light`, jer iz toga izvodimo naš `system`.

Efektivna svetla vrednost je tamni blok preko kojeg ide svetli, jer `--btn-text` i `--btn-gloss` postoje samo u tamnom bloku.

## Kontrast

Tekst se meri na gustom staklu (`glass.dense`) složenom preko pozadine stranice, u gama prostoru kao u pregledaču. Providne statusne pozadine se prvo slažu preko tog stakla.

| Provera | Minimum |
| --- | --- |
| Primarni tekst | 7:1 |
| Sekundarni i tercijarni tekst | 4,5:1 |
| Link i akcenat | 4,5:1 |
| Natpis na dugmetu (osnovno i hover) | 4,5:1 |
| Tekst statusa na svojoj pozadini | 4,5:1 |
| Prsten fokusa prema pozadini | 3:1 |

Poznate praznine: trenutno nema nijedne, sve provere prolaze u oba režima. Ako vrednost iz prodavnice ikada padne, ne menjamo je. Upisujemo par u `KNOWN_GAPS` u skripti, sa izmerenim odnosom. Zastareo unos ruši proveru.

Ne merimo svetlost iza stakla (sjajeve u pozadini) ni boje van tokena, na primer belu strelicu na dugmetu „Nazad na vrh".

## Ostaje po strani

Cevovod `packages/design-tokens` (`ald1n-operator.json`, `scripts/generate-design-tokens.mjs`, `packages/web-theme`, `ald1n-ui-v2.css`, `ald1n-tokens.generated.ts`) ostaje dok se ne preseli poslednji potrošač. Ova isporuka ga ne dira i ne menja ništa što produkcija prikazuje: ni CSS prodavnice, ni Blade, ni admin raspored.

## Napomene za React Native

Zaglavlje generisanog `.ts` fajla navodi iste stvari. Ukratko: `letterSpacingEm` je u em (RN traži px), `lineHeightRatio` je množilac (RN traži apsolutnu vrednost), težine 560, 620, 640, 650, 750 i 780 treba zaokružiti na stotine, a `boxShadow` traži New Architecture.

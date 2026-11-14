# Kobalt tokeni

Jedna paleta, zvana Kobalt, za javnu prodavnicu, admin CMS i Android aplikaciju. To je paleta koju prodavnica već isporučuje. Vrednosti su preuzete iz `ald1n-public-storefront.css` i `ald1n-public-gallery.css`, a generator svaki put proverava da se još poklapaju (vidi „Šta tačno proveravamo“).

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
- `apps/mobile/current/src/design/ald1n-tokens.kobalt.generated.ts`: tipizirani modul za React Native, sa `as const`. Još nije povezan sa aplikacijom.

`--check` pokreće tri provere: izlazi su sinhronizovani sa izvorom, kontrast prolazi, a vrednosti se poklapaju sa prodavnicom.

## Dve konvencije za temu

Generisani CSS radi sa obe, bez izmene bilo koje.

| Šta | Prodavnica | Admin CMS |
| --- | --- | --- |
| Atribut | `data-theme="system\|light\|dark"` | `data-theme="light\|dark"` plus `data-theme-mode="auto\|light\|dark"` |
| Čuvanje izbora | kolačić `shop_theme` | `localStorage` ključ `ald1n-theme-mode` |
| Ko razrešava „auto“ | CSS (`prefers-color-scheme`) | skripta u `layouts/app.blade.php`, uvek upisuje `light` ili `dark` |

Selektori u CSS-u:

- `:root` je tamna tema, podrazumevana.
- `:root[data-theme="light"]` je svetla.
- `:root[data-theme="system"]` je svetla kada je `prefers-color-scheme: light`.

`data-theme-mode` nam ne treba, jer je razrešena vrednost uvek u `data-theme`. Razlika prema CSS-u prodavnice: stranica bez atributa je kod nas tamna, kod prodavnice svetla pod svetlim sistemom. Oba potrošača uvek postavljaju atribut.

Rezervni slojevi su pregazivanja tokena posle tema: `@supports not (backdrop-filter)`, `prefers-reduced-transparency`, `prefers-contrast: more` i `forced-colors`. Prva tri prate pravila prodavnice. Sloj za `forced-colors` je izveden: prodavnica to rešava po komponenti sistemskim bojama, a mi iste boje primenjujemo na nivou tokena. Strukturu tih slojeva ne poredimo sa prodavnicom, samo vrednost `fallback.contrast.imageStageText`.

## Šta tačno proveravamo

Generator parsira oba CSS-a prodavnice i svaku vrednost čita iz pravila kojem pripada (selektor i `@media` kontekst), ne iz bilo kog mesta u fajlu. Pravilo mora postojati tačno jednom, inače provera pada.

Poredi se svaki token iz ovih grupa, u oba režima gde postoje:

- boje, senke i gradijent dugmeta, preko tabela `PARITY_COLORS`, `PARITY_SHADOWS` i `PARITY_GRADIENTS` u skripti;
- radijusi (`sm`, `md`, `lg`, `pill`, `photo`), blur, easing `standard`, trajanja, fokus i veličine kontrola;
- tipografija: porodice fonta, veličine, težine, visine reda i razmak slova, preko tabele `TYPE_PARITY` (token, selektor, šta se čita);
- `fallback.contrast.imageStageText`.

Ne poredimo, i to je izričito navedeno:

- Skala razmaka `space` je izvedena. Prodavnica nema tokene za razmake, samo ručno upisane padinge i gapove, pa je skala naša odluka označena kao `derived`. Preslikali smo 4, 8, 12, 16, 20, 24, 28, 40, 48, 72 i 80. Van mreže od 4 su ostali 6, 10, 14, 18, 22, 26, 30 i 34.
- Easing `basic` je CSS ključna reč `ease`, čiju vrednost definiše CSS specifikacija. Proveravamo samo da je prodavnica koristi kod prelaza od 180 ms.
- Fluidne `clamp()` veličine naslova nisu tokeni.

Provera boja ide u oba smera: svaki token mora imati preslikavanje, a svako svojstvo iz tema prodavnice mora biti preslikano ili navedeno u `NOT_TOKENISED` sa razlogom (na primer `--chevron` je SVG data URI). Svaki token mora biti ili proveren ili označen kao `derived`, inače provera pada. Proveravamo i da je blok `system` u prodavnici identičan bloku `light`, jer iz toga izvodimo naš `system`. Efektivna svetla vrednost je tamni blok preko kojeg ide svetli, jer `--btn-text` i `--btn-gloss` postoje samo u tamnom bloku.

## Kontrast

Svaka uloga teksta se meri na svakoj staklenoj površini (`standard`, `dense`, `soft`, `hover`, `strong`), složenoj preko pozadine stranice, a zatim i preko pozadine sa jednim sjajem (`glow.1` do `glow.4`) pune jačine. Računa se u gama prostoru kao u pregledaču. Providne statusne pozadine se slažu preko istog stakla. Izveštaj navodi najgori par za svaku ulogu i režim.

| Provera | Minimum |
| --- | --- |
| Primarni tekst | 7:1 |
| Sekundarni i tercijarni tekst | 4,5:1 |
| Link i akcenat | 4,5:1 |
| Natpis na dugmetu (osnovno i hover) | 4,5:1 |
| Tekst statusa na svojoj pozadini | 4,5:1 |
| Prsten fokusa prema pozadini i sjajevima | 3:1 |

Poznate praznine: vidi spisak u `KNOWN_GAPS` u skripti. Ako vrednost iz prodavnice padne, ne menjamo je. Upisujemo par sa izmerenim odnosom, a zastareo unos ruši proveru.

Ne merimo preklapanje više sjajeva i boje van tokena, na primer belu strelicu na dugmetu „Nazad na vrh“.

## Posle spajanja

Kada se ovaj PR spoji, `main` sadrži dva generisana fajla (`ald1n-tokens.css` i `ald1n-tokens.kobalt.generated.ts`) kojih nema na produkcijskom hostu dok ih neki instaler ne isporuči. Sledeći overlay instaler mora da ih navede ili izričito isključi u proverama paketa.

## Ostaje po strani

Cevovod `packages/design-tokens` (`ald1n-operator.json`, `scripts/generate-design-tokens.mjs`, `packages/web-theme`, `ald1n-ui-v2.css`, `ald1n-tokens.generated.ts`) ostaje dok se ne preseli poslednji potrošač. Ova isporuka ga ne dira i ne menja ništa što produkcija prikazuje: ni CSS prodavnice, ni Blade, ni admin raspored.

## Napomene za React Native

Zaglavlje generisanog `.ts` fajla navodi iste stvari. Ukratko: `letterSpacingEm` je u em (RN traži px), `lineHeightRatio` je množilac (RN traži apsolutnu vrednost), težine 560, 620, 640, 650, 750 i 780 treba zaokružiti na stotine, a `boxShadow` traži New Architecture.

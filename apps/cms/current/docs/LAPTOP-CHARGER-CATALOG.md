# Punjači za laptopove — kataloški dodatak (LG-04 paralelni batch)

**Status: Draft source-only, NIJE produkciono aktivirano.** Ovaj data-only Laravel migration je namenjen budućem kontrolisanom deploymentu; ne izvršavati na production hostu bez zasebne migracione kapije i unapred sačuvanog reporta u \`docs/operations\`.

- Novi katalog tip i odgovarajuća kategorija: \`punjaci-za-laptopove\`; koristi **isključivo brendove već povezane sa aktivnim tipom Laptopovi**, bez kreiranja novih brendova ili product variants.
- Tip konektora: \`tip-konektora-punjaca\`, selekcija brend-prefiksiranih opcija. Napomena: izbor tipa je jedna lista, **nije dinamički filtriran po brendu**; usaglašenost brenda i priključka se proverava pri unosu. Korisnik može nastaviti da uređuje opcije u postojećem šifarniku.
- Snaga: postojeće polje \`snaga-napajanja\` (W, integer/decimal), povezano s novim tipom kao **obavezno**, bez dupliranja polja ili menjanja namene desktop napajanja.
- Idempotency: ponovni \`up()\` ne duplira tip, kategoriju, brand links, opcije ili pivote; pre-existing manual labels/options are preserved; \`down()\` ništa ne briše.
- Safety: ako aktivan laptop tip, bar jedan postojeći aktivni laptop brend ili postojeće numeričko polje snage nisu prisutni, ništa se ne kreira. Ako postojeći tip/konektor imaju nesaglasan identity, transakcija pada sa HOLD umesto tihog prepisivanja.
- Nema podataka o kompatibilnosti po pojedinačnim modelima; **samo dimenzija konektora nije dovoljan dokaz električne kompatibilnosti** — pre prodaje proveriti  napon, polaritet, identifikacioni pin, W i USB-C PD podršku.

## Nazivi konektora i okvirne dimenzije

| Brend | Opcije |
|---|---|
| Dell | Debela igla **7.4 × 5.0 mm** (centar pin), tanka igla **4.5 × 3.0 mm** (centar pin), USB-C PD |
| HP | Debela igla **7.4 × 5.0 mm** (Smart Pin), plava igla **4.5 × 3.0 mm** (Smart Pin), USB-C PD |
| Lenovo | Kocka / Slim Tip **11 × 4.5 mm** pravougaoni; tanka igla **4.0 × 1.7 mm**; okrugli **7.9 × 5.5 mm** (centar pin), USB-C PD |
| ASUS | Okrugli **5.5 × 2.5 mm**, tanki **4.0 × 1.35 mm**, gaming **6.0 × 3.7 mm**, USB-C PD |
| Acer | Okrugli **5.5 × 1.7 mm**, mali **3.0 × 1.1 mm**, USB-C PD |
| MSI | Okrugli **5.5 × 2.5 mm**, USB-C PD |
| Apple | MagSafe 2, MagSafe 3, USB-C PD |
| Microsoft | Surface Connect, USB-C PD |
| Huawei / Honor / Samsung | USB-C PD (samo ako proizvođač postoji u laptop šifarniku) |
| Toshiba / Dynabook | Okrugli **5.5 × 2.5 mm**, USB-C PD |

**Izvori za fizičke tipove:** HP zvanično https://www.hp.com/us-en/tech-takes/laptops/how-to/how-to-choose-right-charging-cord-hp-laptop.html ; Lenovo https://support.lenovo.com/rs/en/accessories/pd014168-ac-dc-and-acdc-power-adapter-charger-reference-guide i https://www.lenovo.com/us/en/p/accessories-and-software/chargers-and-batteries/chargers/0b46994 ; Dell https://www.wanhunglo.com/2024/for-dell-laptop-dc-power-charge-converter-adapter-cable-cord-7-45-0-to-4-53-0-mm-female-whl-102/ ; Acer/ASUS https://hotspotpro.rs/kako-da-odaberete-odgovarajuci-punjac-za-laptop/ ; Lenovo mali/okrugli: tržišni podaci, nominalno, zahtevaju fizičko poređenje sa konkretnim adapterom. USB-C fizička mera nije ovde prikazana jer je relevantniji PD protokol.

## Preduslov aktivacije

Laravel migracija i CI su samo **izvorni predlog**. U runtime bazi se mora proveriti: aktivan laptop tip, njegovi vezani brendovi, aktivno polje \`snaga-napajanja\`, prava pristupa grupama i post-migration audit kataloga. Nijedan operator skript nije izvršen, nijedan Android/EAS build nije pokrenut.

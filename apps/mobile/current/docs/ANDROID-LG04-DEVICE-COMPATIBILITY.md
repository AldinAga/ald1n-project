# Ald1n CMS — Android LG-04: podržani uređaji i performanse

**Status:** Specifikacija za naredno Google Play **Open testing** izdanje. **Nije nova zabrana uređaja.** Datum 2026-10-20. Izvor: Build26 v1.0.0(26), minSdk API24 i target API36 prema korisnikovom Play Console prikazu; potvrđen native LG-03 UAT menija na jednom fizičkom telefonu. Širi uređajski QA još nije završen.

## Matrica podrške

| Kategorija | Instalacija aplikacije | Liquid Glass | Princip rada |
| --- | --- | --- | --- |
| Android API 24–32 (Android 7.0–12L) | Da, pod postojećim manifest/Play pravilima | Native refrakcija nije dostupna | Nepromenjena funkcionalnost, čitljive neprozirne kontrole, bez grafičke kazne |
| Android API 33+ (Android 13+) sa ispravnim native pipeline-om | Da, prema manifestu i Play katalogu | Native refrakcija **opciona** | Samo odabrane navigacione i akcione kontrole, izričita kontrastna podloga ispod poslovnog teksta |
| Android API 33+ sa reduced transparency, povećanim kontrastom ili degradiranim pipeline-om | Da | Automatski solid fallback | Iste funkcije, semantika i tap-target bez efekta |
| Android sa Reduce Motion | Da | Glass može ostati, ali bez spring animacije pritiska | Brzo bez obaveznih animacija |
| Android API <24 | Ne | N/A | Ispod trenutnog minSdk; **ne** predstavlja novo isključivanje |
| Android Go, uređaji sa malo RAM-a ili starijim GPU-om | Samo po stvarnom manifestu/Play katalogu | Bez garantovanog native efekta; fallback | Ne uvoditi proizvoljnu zabranu ili automatsko isključivanje bez testnog dokaza |

**Napomena:** Android verzija sama po sebi nije garant GPU/AGSL pipeline-a; samo API33+ nije dovoljan za tvrdnju o odgovarajućem efektnom renderovanju.

## Obavezni UX ugovor — čitljivost pre dekoracije

1. Poslovni tekst (SKU, artikli, cena, lager, stanje porudžbine, izveštaji, forma) ostaje na neprozirnoj kontrastnoj površini. Ne dodavati refrakciju preko tabela, ključnih oznaka i input polja.
2. Native glass je ukras. Pritiskom na header kontrolu otvara se ista postojeća ruta; permissions, badge i disabled stanja ostaju ista.
3. Header kontrole imaju **najmanje 48 × 48 dp** aktivnu oblast, screen-reader naziv i stanje. Tekst naslova mora biti čitljiv na 320dp i pri većem fontu.
4. Poštuju se Reduce Motion, Reduce Transparency, High Text Contrast i neuspeh native pipeline-a; u svim slučajevima vraća se jednostavan neproziran prikaz.
5. Animacije su kratke, zasnovane na transformaciji, bez animiranja preskupe refrakcije i bez beskonačnih intervala. Kada je Reduce Motion uključen, nema animacije pritiska.
6. Nema novih API-ja, modela, prodajnih pravila ili nedozvoljenih poslovnih akcija.

## LG-04 Wave 1 — globalni header

- `PageHeader` naslov ima **neproziran** neutralni okvir i kanonski tekst u boji `theme.ink`.
- Samo vidljive akcije (osveži, notifikacije) mogu da dobiju Liquid Glass na Android API33+ ako pristupačnost dozvoljava.
- Render je ograničen na 48dp po akciji, background capture nije stalno dinamičan (`enableDynamicBackground=false`), downsample 0.5, bez chromatic dispersion i high-quality blur.
- Nema nove native zavisnosti, plugin-a ili runtime-a: nasleđuje već ugrađen `react-native-android-liquid-glass@0.2.1` iz Build26.
- iOS/web i slabiji Android koriste solid kontrole; tablet rail i donji LG-03 meni nisu menjani.

## Minimalni fizički acceptance pre sledećeg Open testing auto-submit

- Android 13+ realni uređaj: oba režima teme, akcije headera, touch 48dp, čitljivost naslova, otvorene/primljene notifikacije i unread badge 0/1/99+.
- Android 7–12 ili emulator od odgovarajućeg API-ja: solid fallback i sve funkcije dostupne.
- Accessibility: Reduce Motion, povećan kontrast, screen reader, uvećan font, 320/390dp, položeni/okomiti prikaz.
- 30 sekundi skrolovanja kataloga i 20 promena navigacije: zabeležiti fluidnost, eventualni zastoj, ANR/crash, grejanje i ponašanje baterije; ne deklarisati performanse samo iz CI testova.
- Negativni slučajevi: greška native pipeline-a, neuspeo refresh, offline i povratak iz background-a.
- Ako određeni uređaj ima potvrđen crash/ANR ili trajnu nekoristivost i solid fallback ne pomaže, zasebno oceniti Play Console Device catalog isključivanje za **konkretnu varijantu**. Samo subjektivan izgled ili manji FPS nisu dovoljni za globalnu zabranu.

## Šta se NE menja na Google Play-u

- **Nema novog minSdk, ABI filtera, RAM/SoC filtera, permissions ili blanket model exclusion pravila.**
- Ne menjati Google Play produkcioni track. Naredni target za automatski submit: `beta` (Open testing), uz zaseban EAS testing kanal i runtime.
- Device catalog može dodatno ograničiti dostupnost, ali trenutno **nema podataka** koji opravdavaju novo pravilo isključenja.
- EAS Submit ne šalje Markdown specifikaciju niti automatski upisuje Google Play tekst napomena. Zato specifikacija ostaje u repozitorijumu i isporučuje se uz release report; odgovarajući kraći tekst se daje za copy/paste u napomene o verziji.

## Predložene kratke Google Play napomene (sr-RS)

**LG-04 — testiranje interfejsa**
Unapređen je globalni header sa diskretnim staklastim komandama i animacijama na dodir. Nazivi, obaveštenja i poslovni podaci ostaju čitljivi u svetloj i tamnoj temi. Native efekti koriste se na podržanim Android 13+ uređajima; ostali koriste jednostavan prikaz bez gubitka funkcija. Ova verzija se testira.

## Dokazi i release gate

- Build26 native proof: source `58b5e45b6b53a60e547e225f03ab43dd9ca85361`; Android Play Open testing izdanje v1.0.0(26), korisnički UAT donjeg native menija PASS.
- LG-04 source/QA mora imati sopstveni exact-head CI (TypeScript, validator, Release25, Android bundle, native-version parity), novi izveštaj i owner release acceptance.
- EAS auto-submit zahteva stvarni Google Play service-account key vezan za Expo projekat. Ako nije konfigurisan, jasan HOLD umesto automatskog prebacivanja na production. Track mora biti samo `beta`, nikada `production`.
- Sačekati odobrenje naredne Play testing verzije; ne pokretati novi AAB bez zatvorenih source/UI/Play gate-ova.

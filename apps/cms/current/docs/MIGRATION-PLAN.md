# Plan potpune migracije na Laravel

## Završeno — Alpha1

- odvojena Laravel baza, login, dozvole, read-only katalog, API i početni import.

## Završeno — Alpha2 / Alpha2.1 / Alpha2.2

- CRUD artikala, automatski SKU, slike, šifarnici i audit;
- prijava, reset lozinke i sistemska dijagnostika.

## Završeno — Alpha3 / Alpha3.1

- istorijski import korisnika, prava, kataloga, podešavanja, porudžbina, provizija, lagera, kursa, žiro računa i audit istorije;
- korisnici, grupe, dozvole, kurs i žiro računi;
- read-only pregled istorijskih poslovnih podataka.

## Završeno — v2.0.0-beta1 Production Orders & Inventory

- Laravel je jedini write sistem za nove porudžbine i lager;
- transakcijsko kreiranje porudžbine i zaključavanje proizvoda;
- umanjenje lagera i tačno jedan povrat pri otkazivanju;
- idempotency zaštita za porudžbine i ručne korekcije;
- korisničke i administratorske dozvole;
- legacy konekcija, SQL i istorijske porudžbine ostaju read-only;
- Redis je uklonjen; file session/cache i sync queue.

## Završeno — v2.0.0-beta2 Responsive UI patch

- hamburger navigacija bez mobilnog horizontalnog skrola;
- kompaktne forme za korisnike i artikle bez veštačkog vertikalnog razvlačenja;
- nema nove migracije baze u odnosu na beta1.

## Završeno — v2.0.0-beta3 Catalog detail hotfix

- eksplicitno pronalaženje artikla po slugu;
- bezbedan fallback za neispravne zapise slika;
- kontrolisani 404 umesto HTTP 500 za nepoznat ili nedostupan artikal.

## Završeno — v2.0.0-beta4 Legacy dashboard parity

- početna stranica, desktop header i mobilni raspored usklađeni sa legacy dizajnom;
- deployment repair režim za operativne tabele i sistemske dozvole;
- odvojena runtime zaštita legacy konekcije od stroge provere MySQL grantova.

## Završeno — v2.0.0-beta5 Login recovery

- best-effort login telemetry i password rehash;
- dashboard fallback kada deo produkcione šeme još nije primenjen;
- idempotentne operativne migracije i repair migracija `000007`;
- razdvojene provere žive instalacije i sanitized ZIP paketa.

## Završeno — v2.0.0-beta6 Authenticated runtime hardening

- uklonjeni nezaštićeni DB/service pozivi iz authenticated layout-a;
- bezbedno renderovanje korisničke uloge, podešavanja, kursa i footera;
- obavezni file session/cache/view/log direktorijumi uključeni u Full i Upgrade pakete;
- globalna runtime provera pre session middleware-a, sa kontrolisanim HTTP 503 odgovorom;
- login fallback za Turnstile, user lookup, remember token, session zapis i logging;
- repair migracija `000008` za osnovnu post-login šemu;
- `app:auth-doctor --render-dashboard` za precizno renderovanje kompletnog dashboard/layout toka.

## Završeno — v2.1.0-beta5 Inventory UX

- Ulaz robe i Popis lagera odvojeni su u stabilne operativne tabove;
- uklonjeno je CSS prelivanje i horizontalni scroll stranice;
- uveden je responsive card prikaz za mobile i izbor broja artikala;
- nema nove migracije baze.

## Sledeće — release candidate

- poređenje stanja i poslovni UAT;
- MySQL Feature testovi u izolovanoj test bazi;
- load/security audit;
- backup i rollback proba;
- potvrda read-only legacy MySQL naloga na nivou grantova;
- zamrzavanje API v1 ugovora i korisnička obuka.

## Planirane funkcionalnosti posle stabilizacije

- napredniji tok plaćanja i IPS QR;
- eksterni tracking adapteri;
- kompletan operativni workflow provizija;
- notifikacije i kontrolisana queue strategija bez implicitnog Redis zahteva.

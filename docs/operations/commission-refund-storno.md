# Provizija posle refundacije: automatski storno i void

Odluke vlasnika 24.11.2026 (prva verzija i ispravka). Kod: `CommissionReversalService::reverseForRefund`, test: `tests/Feature/CommissionRefundStornoTest.php`.

## Gde se refundacija beleži

Refundacija je red u `order_payments` sa `entry_type = refund` i `status = verified`. Nastaje na dva mesta:

- `OrderPaymentService::record` (obrazac "Evidentiraj uplatu", web i API), tip "refund";
- `OrderPaymentService::recordAfterSalesRefundLocked`, kada se izvrši postprodajna radnja "refund".

Void refundacije ide kroz `OrderPaymentService::void`. Sva tri mesta pozivaju isti metod u istoj transakciji, dok je porudžbina zaključana, posle promene uplate, a pre projekcije finansijskog stanja. Redosled je bitan: projekcija bi automatski odobrenu proviziju vratila na "Na čekanju", pa storno ne bi video da je bila "Za isplatu".

## Osnova je stvarno plaćeno

- plaćeno = min(bruto verifikovane uplate, `subtotal_rsd`)
- refundirani deo = plaćeno minus neto uplaćeno (uplate minus refundacije)
- storno = prvobitna provizija x refundirani deo / plaćeno

Prvobitna provizija je `total_eur` plus potvrđeni storno. Upisuje se samo razlika do svega što je već stornirano ili zaduženo, iz bilo kog izvora (povrat robe, ručni storno, otkazivanje), bez odbijenih redova i umanjeno za kredite. Zbir storna zato nikad ne prelazi prvobitnu proviziju.

Primeri (porudžbina 12.000 RSD, provizija 20,00 EUR):

- Plaćeno 6.000 i isporučeno, pa refundirano 6.000: refundirano je sve plaćeno, pa se stornira 20,00 EUR i provizija je `cancelled`.
- Isporučeno bez ijedne uplate: nema šta da se refundira, pa se ništa ne menja.
- Plaćeno 13.000, refundirano 1.000: to je povraćaj preplate. Osnova je 12.000, a neto uplaćeno i dalje 12.000, pa storna nema.

| Stanje provizije | Refundacija |
|---|---|
| Za isplatu (approved) | red `kind = reversal` umanjuje `total_eur`; na 0,00 EUR provizija je `cancelled`, bez duga |
| Isplaćena (paid, u isplatnom batch-u) | red `kind = debit`, `status = confirmed`; isplata se ne menja; prebija se iz sledeće isplate (`markPaidBulk`) |
| Na čekanju ili otkazana, a ranije stornirana refundacijom | nastavlja se po istom pravilu |
| Na čekanju, obična | ne menja se |
| Na čekanju zbog kursa EUR/RSD | puna refundacija je samo otkazuje (bez reda u `commission_adjustments`); delimična se beleži u audit `commission.refund_reversal_skipped` |
| Porudžbina bez provizije | ništa |

Isplatni batch i status "paid" nastaju u istoj transakciji, pa ne postoji provizija koja je u batch-u, a nije isplaćena.

## Void refundacije vraća storno

Posle void-a cilj se ponovo izračuna. Ako je manji od već storniranog, vraća se razlika, ali samo ono što je stornirala refundacija. Storno povrata robe i ručni storno ostaju.

- **Neisplaćena:** `order_refund` redovi se umanjuju od najnovijeg; red koji padne na 0 dobija `status = rejected` i napomenu. `total_eur` raste. Provizija otkazana refundacijom vraća se na "Na čekanju", a projekcija odmah odlučuje da li je opet "Za isplatu".
- **Isplaćena, zaduženje još nije prebijeno (u batch-u):** umanjuje se neprebijeni deo zaduženja; na 0 je `rejected`.
- **Isplaćena, zaduženje je već prebijeno iz isplate,** ili je storno bio pre isplate: ništa isplaćeno se ne menja i poravnanja ostaju. Nastaje kredit agentu (`kind = credit`, `source = order_refund_void`, `confirmed`), a SuperAdmin dobija obaveštenje.
- **Provizija koja je čekala kurs i otkazana je punom refundacijom:** vraća se na "Na čekanju".
- **Otkazana porudžbina:** void ne vraća ništa.

Svaka promena upisuje audit (`commission.refund_storno_restored` po redu, `commission.refund_void_restored` po proviziji, `commission.credit_recorded`) i `commission_status_history`. Dupli void ne radi ništa: `void` vraća već poništenu uplatu bez ponovnog izvršavanja, a i ponovni proračun daje razliku 0.

## Idempotentnost

- Ponovljena radnja ili ponovni poziv daju razliku 0 i ne upisuju ništa.
- Dve delimične refundacije zajedno daju tačno ceo iznos, bez greške zaokruživanja.
- Povrat robe pa refundacija za isti komad: refundacija ne dodaje storno. Refundacija pa povrat: povrat se preskače (postoji storno bez artikla), uz audit `commission.auto_reversal_skipped`.

## Šta nije okidač

"Pošiljka vraćena" (`OrderShipmentService::markReturned`) ne pomera novac i ne menja proviziju. Ponovna uplata posle refundacije sama ne vraća storno; računa se tek pri sledećoj refundaciji ili void-u, i tada se uzima u obzir. Void obične (pozitivne) uplate ne menja proviziju.

## Ograničenja

- Kredit agentu se ne isplaćuje automatski. `markPaidBulk` (CommissionWorkflowService, koji menja i PR 206) prebija samo zaduženja, pa kredit isplaćuje SuperAdmin ručno. Nova refundacija posle kredita pravi novo zaduženje i ne troši kredit.
- Postojeći prikaz na listi provizija (blade, koji menja i PR 206) kredit prikazuje kao "Storno -X EUR". Razlog počinje sa "Kredit agentu:". Oznaku treba dodati posle spajanja PR 206.
- Povrat robe na isplaćenu proviziju i dalje pravi predlog zaduženja (`proposed`) koji SuperAdmin potvrđuje. Refundacija pravi potvrđeno zaduženje.
- Nema migracije.

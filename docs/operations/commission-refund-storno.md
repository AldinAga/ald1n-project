# Provizija posle refundacije: automatski storno i void

Odluke vlasnika 24.11.2026 (prva verzija i ispravka) i 25.11.2026 (ponovna uplata vraća storno). Kod: `CommissionReversalService::reverseForRefund`, test: `tests/Feature/CommissionRefundStornoTest.php`.

## Gde se refundacija beleži

Refundacija je red u `order_payments` sa `entry_type = refund` i `status = verified`. Nastaje na dva mesta:

- `OrderPaymentService::record` (obrazac "Evidentiraj uplatu", web i API), tip "refund";
- `OrderPaymentService::recordAfterSalesRefundLocked`, kada se izvrši postprodajna radnja "refund".

Void refundacije ide kroz `OrderPaymentService::void`, a nova (ili naknadno verifikovana) uplata kroz `record` i `verify`. Sva mesta pozivaju isti metod u istoj transakciji, dok je porudžbina zaključana, posle promene uplate, a pre projekcije finansijskog stanja. Redosled je bitan: projekcija bi automatski odobrenu proviziju vratila na "Na čekanju", pa storno ne bi video da je bila "Za isplatu". Automatsko odobrenje (`CommissionAutoApprovalService`) pre prelaska u "Za isplatu" poziva isti metod, pa refundacija pa isporuka ne isplaćuje punu proviziju.

Redosled zaključavanja je uvek porudžbina, provizija, `commission_adjustments` (po id rastuće), `commission_debit_settlements`. Sva čitanja zbirova idu sa `lockForUpdate`. Obaveštenja (agent, SuperAdmin) šalju se tek posle commit-a (`DB::afterCommit`).

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
| Na čekanju, obična | storno istim pravilom (red `reversal`); i stara refundacija se uračunava kad provizija dospe |
| Na čekanju zbog kursa EUR/RSD | puna refundacija je samo otkazuje (bez reda u `commission_adjustments`); delimična se beleži u audit `commission.refund_reversal_skipped` |
| Porudžbina bez provizije | ništa |

Isplatni batch i status "paid" nastaju u istoj transakciji, pa ne postoji provizija koja je u batch-u, a nije isplaćena.

## Void refundacije vraća storno

Posle void-a ili nove uplate (odluka vlasnika 25.11.: ponovna uplata odmah vraća storno srazmerno, nikad iznad prvobitne provizije) cilj se ponovo izračuna. Ako je manji od već storniranog, vraća se razlika, ali samo ono što je stornirala refundacija. Storno povrata robe i ručni storno ostaju. Ručno otkazana provizija ostaje otkazana; na "Na čekanju" se vraća samo provizija koju je otkazala refundacija.

- **Neisplaćena:** `order_refund` redovi se umanjuju od najnovijeg; red koji padne na 0 dobija `status = rejected` i napomenu. `total_eur` raste. Provizija otkazana refundacijom vraća se na "Na čekanju", a projekcija odmah odlučuje da li je opet "Za isplatu".
- **Isplaćena, zaduženje još nije prebijeno (u batch-u):** umanjuje se neprebijeni deo zaduženja; na 0 je `rejected`.
- **Isplaćena, zaduženje je već prebijeno iz isplate,** ili je storno bio pre isplate: ništa isplaćeno se ne menja i poravnanja ostaju. Nastaje kredit agentu (`kind = credit`, `source = order_refund_void`, `confirmed`), a SuperAdmin dobija obaveštenje.
- **Provizija koja je čekala kurs i otkazana je punom refundacijom:** vraća se na "Na čekanju".
- **Otkazana porudžbina:** void ne vraća ništa.

Svaka promena upisuje audit (`commission.refund_storno_restored` po redu, `commission.refund_storno_returned` po proviziji, `commission.credit_recorded`, `commission.credit_consumed`) i `commission_status_history`. Dupli void ne radi ništa: `void` vraća već poništenu uplatu bez ponovnog izvršavanja, a i ponovni proračun daje razliku 0.

## Idempotentnost

- Ponovljena radnja ili ponovni poziv daju razliku 0 i ne upisuju ništa.
- Dve delimične refundacije zajedno daju tačno ceo iznos, bez greške zaokruživanja (i u trećinama: 6,67 + 6,66 + 6,67).
- Povrat robe pa refundacija za isti komad: refundacija ne dodaje storno. Refundacija pa povrat: povrat se preskače (postoji storno bez artikla), uz audit `commission.auto_reversal_skipped`.

## Šta nije okidač

"Pošiljka vraćena" (`OrderShipmentService::markReturned`) ne pomera novac i ne menja proviziju. Void obične (pozitivne) uplate ne menja proviziju.

## Kredit agentu

Kredit se računa u raspoloživ iznos za storno i za zaduženje pri otkazivanju. Nova refundacija ili otkazivanje prvo troše kredit (od najstarijeg, audit `commission.credit_consumed`), pa tek ostatak postaje zaduženje. Kredit se vidi u izveštaju (`credit_eur`) i kao otvorena obaveza pri brisanju korisnika. Povrat robe ne tretira kredit kao ručni storno.

## Ograničenja

- Kredit agentu se ne isplaćuje automatski. `markPaidBulk` (CommissionWorkflowService, koji menja i PR 206) prebija samo zaduženja, pa kredit isplaćuje SuperAdmin ručno.
- Postojeći prikaz na listi provizija (blade, koji menja i PR 206) kredit prikazuje kao "Storno -X EUR". Razlog počinje sa "Kredit agentu:". Oznaku treba dodati posle spajanja PR 206.
- Povrat robe na isplaćenu proviziju i dalje pravi predlog zaduženja (`proposed`) koji SuperAdmin potvrđuje. Refundacija pravi potvrđeno zaduženje.
- Nema migracije.

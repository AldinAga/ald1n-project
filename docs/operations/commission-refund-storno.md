# Provizija posle refundacije: automatski storno

Odluka vlasnika 24.11.2026. Kod: `CommissionReversalService::reverseForRefund`, test: `tests/Feature/CommissionRefundStornoTest.php`.

## Gde se refundacija beleži

Refundacija je red u `order_payments` sa `entry_type = refund` i `status = verified`. Nastaje na dva mesta:

- `OrderPaymentService::record` (obrazac "Evidentiraj uplatu", web i API), tip "refund";
- `OrderPaymentService::recordAfterSalesRefundLocked`, kada se izvrši postprodajna radnja "refund".

Oba mesta pozivaju storno u istoj transakciji, dok je porudžbina zaključana, posle upisa refundacije, a pre projekcije finansijskog stanja. Redosled je bitan: projekcija bi automatski odobrenu proviziju vratila na "Na čekanju", pa storno ne bi video da je bila "Za isplatu".

## Pravilo

Iznos se računa kumulativno: prvobitna provizija x min(1, zbir verifikovanih refundacija / `subtotal_rsd`). Prvobitna provizija je `total_eur` plus potvrđeni storno. Upisuje se samo razlika do svega što je već stornirano ili zaduženo, iz bilo kog izvora (povrat robe, ručni storno, otkazivanje), bez odbijenih predloga.

| Stanje provizije | Šta se dešava |
|---|---|
| Za isplatu (approved) | red `kind = reversal`, umanjuje `total_eur`; na 0,00 EUR provizija postaje `cancelled`, bez duga |
| Isplaćena (paid, u isplatnom batch-u) | red `kind = debit`, `status = confirmed`; isplata se ne menja; prebija se iz sledeće isplate (`markPaidBulk`) |
| Na čekanju, ranije delimično stornirana refundacijom | nastavlja se po istom pravilu |
| Na čekanju, obična | ne menja se |
| Na čekanju zbog kursa EUR/RSD | puna refundacija je samo otkazuje (iznos ostaje, nema reda u `commission_adjustments`); delimična se beleži u audit `commission.refund_reversal_skipped` |
| Otkazana, ili porudžbina bez provizije | ništa |

Isplatni batch i status "paid" nastaju u istoj transakciji, pa ne postoji provizija koja je u batch-u, a nije isplaćena.

Svaki storno upisuje `commission_status_history`, audit (`commission.reversed` ili `commission.debit_recorded`), izvor `order_refund` i broj refundacije u razlogu. Agent dobija obaveštenje. Kada nastane zaduženje, obaveštenje dobija i svaki aktivni SuperAdmin.

## Idempotentnost

- Ponovljena radnja ili ponovni poziv daju razliku 0 i ne upisuju ništa.
- Dve različite delimične refundacije zajedno daju tačno ceo iznos, bez greške zaokruživanja.
- Povrat robe pa refundacija za isti komad: refundacija ne dodaje storno. Refundacija pa povrat: povrat se preskače, kao i do sada kada postoji storno bez artikla, uz audit `commission.auto_reversal_skipped`.

## Ograničenja i otvorena pitanja

- Storniranje (void) refundacije ne vraća storno; SuperAdmin to ispravlja ručno.
- Imenilac je `subtotal_rsd`, ne uplaćeni iznos. Isporučena porudžbina koja je samo delimično plaćena, pa ceo uplaćeni iznos refundiran, zadržava deo provizije. Za ovo treba odluka vlasnika.
- Povrat robe na isplaćenu proviziju i dalje pravi predlog zaduženja (`proposed`) koji SuperAdmin potvrđuje. Refundacija pravi potvrđeno zaduženje, po odluci od 24.11.
- Nema migracije.

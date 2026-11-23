@extends('layouts.app')

@section('title', 'Porudžbina '.($detail['order']['order_number'] ?? ''))

@section('content')
@php
    $order = $detail['order'] ?? [];
    $items = $detail['items'] ?? [];
    $documents = $detail['documents'] ?? [];
    $commission = $detail['commission'] ?? null;
    $receivable = $detail['receivable'] ?? null;
    $timeline = $detail['timeline'] ?? [];
    $warnings = $detail['warnings'] ?? [];
    $urls = $detail['urls'] ?? [];
    $permissions = $detail['permissions'] ?? [];
    $actions = $detail['actions'] ?? [];
    $delivery = $detail['delivery'] ?? null;
    $shipment = $detail['shipment'] ?? null;
    $commissionLabels = [
        'pending' => 'Na čekanju',
        'approved' => 'Odobrena',
        'paid' => 'Isplaćena',
        'cancelled' => 'Stornirana',
    ];
    $commissionTones = ['pending' => 'warning', 'approved' => 'info', 'paid' => 'ok', 'cancelled' => 'danger'];
    $receivableLabels = [
        'monitoring' => 'Praćenje',
        'contacted' => 'Kontaktirano',
        'promised' => 'Obećana uplata',
        'installment_plan' => 'Plan otplate',
        'escalated' => 'Eskalirano',
        'disputed' => 'Sporno',
        'closed' => 'Zatvoreno',
    ];
    $receivableTones = ['monitoring' => 'off', 'contacted' => 'warning', 'promised' => 'info', 'installment_plan' => 'info', 'escalated' => 'danger', 'disputed' => 'danger', 'closed' => 'ok'];
    $installmentLabels = ['pending' => 'Čeka', 'overdue' => 'Kasni', 'paid' => 'Plaćena'];
    $installmentTones = ['pending' => 'warning', 'overdue' => 'danger', 'paid' => 'ok'];
    $documentLabels = [
        'confirmation' => 'Potvrda',
        'order_confirmation' => 'Potvrda',
        'delivery_note' => 'Otpremnica',
        'proforma' => 'Predračun',
        'invoice' => 'Račun',
        'credit_note' => 'Storno dokument',
    ];
    $paymentMethodLabels = [
        'cash_on_delivery' => 'Pouzećem',
        'bank_transfer' => 'Uplata na račun',
        'deferred_payment' => 'Odloženo plaćanje',
    ];
    $rawStatus = (string) ($order['status'] ?? '');
    $statusTone = \App\Support\OrderStatus::tone($rawStatus, (bool) ($order['is_completed'] ?? false));
    $canAmend = ($actions['amend'] ?? false) && !empty($urls['edit']);
@endphp

<div class="a1-ops" data-order-user-detail-ready="1" data-build16-orders-detail="1">
    @if($warnings !== [])
        <div class="a1-alert a1-alert--warning" role="status">
            <x-icon name="alert" :size="20" :shell="true" />
            <div class="a1-alert__body">
                <strong class="a1-alert__title">Napomena sistema</strong>
                <ul>
                    @foreach($warnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ $urls['back'] ?? '/orders' }}">Moje porudžbine</a></li>
            <li aria-current="page">{{ $order['order_number'] ?? 'Porudžbina' }}</li>
        </ol>
    </nav>

    <section class="a1-card" aria-labelledby="oc-title">
        <div class="a1-pagehead">
            <div class="a1-pagehead__text">
                <span class="a1-eyebrow">Poručeno od {{ $order['supplier_name'] ?? 'Administratora' }}</span>
                <h1 class="a1-title" id="oc-title">{{ $order['order_number'] ?? 'Porudžbina' }}</h1>
                <p class="a1-text">{{ $order['created_at'] ?? '—' }} · lager {{ $order['inventory_state'] ?? '—' }}</p>
            </div>
            <div class="a1-pagehead__actions">
                @if($canAmend)
                    <a class="a1-btn a1-btn--primary" href="{{ $urls['edit'] }}"><x-icon name="sliders" :size="20" :shell="true" /> Uredi porudžbinu</a>
                @endif
                @if(($actions['after_sales_create'] ?? false) && !empty($urls['after_sales_create']))
                    <a class="a1-btn" href="{{ $urls['after_sales_create'] }}"><x-icon name="alert" :size="20" :shell="true" /> Reklamacija / servis</a>
                @endif
                @if(!empty($urls['confirmation']))
                    <a class="a1-btn" target="_blank" rel="noopener" href="{{ $urls['confirmation'] }}"><x-icon name="file-text" :size="20" :shell="true" /> Potvrda PDF</a>
                @endif
            </div>
        </div>
        <div class="a1-oc-meta">
            <span class="a1-pill{{ $statusTone !== '' ? ' a1-pill--'.$statusTone : '' }}">{{ $order['status_label'] ?? ($order['status'] ?? '—') }}</span>
            <span class="a1-text">Ukupno <strong class="a1-num">{{ $order['subtotal_rsd_display'] ?? '0,00 RSD' }}</strong></span>
        </div>
    </section>

    <div class="a1-split">
        <div class="a1-stack">
            <section class="a1-card" aria-labelledby="oc-items-title">
                @if($actions['amend'] ?? false)
                    <p class="a1-oc-note" data-order-amendment-available><strong>Porudžbinu još možeš izmeniti.</strong> Artikle, količine, adresu i napomenu možeš menjati sve dok pošiljka ne bude poslata.</p>
                @elseif(in_array($order['status'] ?? '', ['shipped', 'cancelled'], true) || ($order['is_completed'] ?? false))
                    <p class="a1-oc-note" data-order-amendment-locked><strong>Izmena nije moguća.</strong>
                        @if(($order['is_completed'] ?? false))
                            Porudžbina je isporučena, pa je zaključana.
                        @elseif(($order['status'] ?? '') === 'cancelled')
                            Porudžbina je otkazana, pa je zaključana.
                        @else
                            Pošiljka je već poslata, pa se porudžbina više ne može menjati. Za promenu pozovi odgovorno lice{{ !empty($order['supplier_name']) && $order['supplier_name'] !== 'Nije dodeljeno' ? ': '.$order['supplier_name'] : '' }}.
                        @endif
                    </p>
                @endif
                <div class="a1-card__title">
                    <h2 class="a1-h3" id="oc-items-title">Stavke porudžbine</h2>
                    <p class="a1-text">Cene i količine su sačuvane u trenutku poručivanja.</p>
                </div>

                @if(count($items) === 0)
                    <div class="a1-empty">
                        <span class="a1-empty__icon"><x-icon name="boxes" :size="28" :shell="true" /></span>
                        <p>Stavke porudžbine trenutno nisu dostupne.</p>
                    </div>
                @else
                    <div class="a1-tablewrap" role="region" aria-label="Stavke porudžbine" tabindex="0">
                        <table class="a1-table a1-table--cards" role="table">
                            <caption class="a1-sr">Stavke porudžbine {{ $order['order_number'] ?? '' }}</caption>
                            <thead role="rowgroup">
                                <tr role="row">
                                    <th scope="col" role="columnheader">Artikal</th>
                                    <th scope="col" role="columnheader">SKU</th>
                                    <th scope="col" role="columnheader" class="a1-table__num">Količina</th>
                                    <th scope="col" role="columnheader" class="a1-table__num">Jedinična cena</th>
                                    <th scope="col" role="columnheader" class="a1-table__num">Ukupno</th>
                                </tr>
                            </thead>
                            <tbody role="rowgroup">
                                @foreach($items as $item)
                                    <tr role="row">
                                        <td role="cell" class="a1-table__primary" data-label="Artikal">{{ $item['name'] ?? 'Nepoznat artikal' }}</td>
                                        <td role="cell" data-label="SKU">{{ $item['sku'] ?? '—' }}</td>
                                        <td role="cell" class="a1-table__num" data-label="Količina">{{ $item['quantity'] ?? 0 }}</td>
                                        <td role="cell" class="a1-table__num" data-label="Jedinična cena">{{ $item['unit_price'] ?? '0,00 RSD' }}</td>
                                        <td role="cell" class="a1-table__num" data-label="Ukupno">{{ $item['line_total'] ?? '0,00 RSD' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <dl class="a1-oc-total">
                    <dt>Ukupno</dt>
                    <dd>{{ $order['subtotal_rsd_display'] ?? '0,00 RSD' }}</dd>
                </dl>
            </section>

            @include('orders.partials.payments', ['detail' => $detail])

            @if(is_array($receivable))
                <section class="a1-card" aria-labelledby="oc-receivable-title">
                    <div class="a1-card__head">
                        <div class="a1-card__title">
                            <span class="a1-eyebrow">Plan naplate</span>
                            <h2 class="a1-h3" id="oc-receivable-title">{{ $receivable['case_number'] ?: 'Dogovor o plaćanju' }}</h2>
                            <p class="a1-text">Prikazane rate se zatvaraju samo na osnovu verifikovanih uplata.</p>
                        </div>
                        @php($receivableStatus = $receivable['status'] ?? 'monitoring')
                        <span class="a1-pill a1-pill--{{ $receivableTones[$receivableStatus] ?? 'off' }}">{{ $receivableLabels[$receivableStatus] ?? ($receivable['status'] ?? '') }}</span>
                    </div>
                    @if(!empty($receivable['promised_payment_at']))
                        <p class="a1-oc-note">Obećani datum uplate: <strong>{{ $receivable['promised_payment_at'] }}</strong></p>
                    @endif
                    @if(!empty($receivable['installments']))
                        <div class="a1-tablewrap" role="region" aria-label="Rate plana naplate" tabindex="0">
                            <table class="a1-table a1-table--cards" role="table">
                                <caption class="a1-sr">Rate plana naplate</caption>
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Rata</th>
                                        <th scope="col" role="columnheader">Dospeće</th>
                                        <th scope="col" role="columnheader" class="a1-table__num">Iznos</th>
                                        <th scope="col" role="columnheader" class="a1-table__num">Plaćeno</th>
                                        <th scope="col" role="columnheader">Status</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    @foreach($receivable['installments'] as $installment)
                                        <tr role="row">
                                            <td role="cell" class="a1-table__primary" data-label="Rata">#{{ $installment['sequence_no'] }}</td>
                                            <td role="cell" data-label="Dospeće">{{ $installment['due_at'] }}</td>
                                            <td role="cell" class="a1-table__num" data-label="Iznos">{{ $installment['amount_display'] }}</td>
                                            <td role="cell" class="a1-table__num" data-label="Plaćeno">{{ $installment['paid_display'] }}</td>
                                            <td role="cell" data-label="Status"><span class="a1-pill a1-pill--{{ $installmentTones[$installment['status']] ?? 'off' }}">{{ $installmentLabels[$installment['status']] ?? $installment['status'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @if(!empty($receivable['contacts']))
                        <ul class="a1-list">
                            @foreach($receivable['contacts'] as $contact)
                                <li class="a1-item a1-item--stack a1-item--static">
                                    <div class="a1-item__main">
                                        <strong class="a1-item__title">{{ $contact['subject'] }}</strong>
                                        <span class="a1-item__sub">{{ $contact['contacted_at'] }}</span>
                                        <p class="a1-text">{{ $contact['note'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            @if(is_array($commission))
                @php($commissionStatus = $commission['status'] ?? 'pending')
                <section class="a1-card" aria-labelledby="oc-commission-title">
                    <div class="a1-card__head">
                        <div class="a1-card__title">
                            <span class="a1-eyebrow">Tvoja provizija</span>
                            <h2 class="a1-h3 a1-num" id="oc-commission-title">{{ $commission['total_eur_display'] ?? '0,00 EUR' }}</h2>
                            <p class="a1-text">Podrazumevana provizija po komadu iznosi 10% vrednosti artikla.</p>
                        </div>
                        <span class="a1-pill a1-pill--{{ $commissionTones[$commissionStatus] ?? 'off' }}">{{ $commissionLabels[$commissionStatus] ?? $commissionStatus }}</span>
                    </div>
                    <dl class="a1-dl">
                        <div class="a1-dl__row"><dt>Napomena</dt><dd>{{ $commission['status_note'] ?? '—' }}</dd></div>
                        @if(($commission['status'] ?? '') === 'paid')
                            <div class="a1-dl__row"><dt>Datum isplate</dt><dd>{{ $commission['paid_at'] ?? '—' }}</dd></div>
                            <div class="a1-dl__row"><dt>Referenca</dt><dd>{{ $commission['payment_reference'] ?? '—' }}</dd></div>
                        @endif
                    </dl>
                    @if(!empty($urls['commission']))
                        <div><a class="a1-btn a1-btn--sm" href="{{ $urls['commission'] }}">Sve moje provizije</a></div>
                    @endif
                </section>
            @endif
        </div>

        <aside class="a1-stack" aria-label="Podaci o porudžbini">
            <section class="a1-card" aria-labelledby="oc-owner-title">
                <h2 class="a1-h3" id="oc-owner-title">Odgovorno lice</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Ime</dt><dd>{{ $order['supplier_name'] ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Uloga</dt><dd>{{ $order['supplier_role'] ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>E-mail</dt><dd>{{ $order['supplier_email'] ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $order['supplier_phone'] ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Preuzeto</dt><dd>{{ $order['accepted_at'] ?? '—' }}</dd></div>
                </dl>
            </section>

            <section class="a1-card" aria-labelledby="oc-shipping-title">
                <h2 class="a1-h3" id="oc-shipping-title">Dostava</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Kupac</dt><dd>{{ $order['shipping_full_name'] ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Adresa</dt><dd>{{ $order['shipping_address'] ?? '—' }}, {{ $order['shipping_postal_code'] ?? '' }} {{ $order['shipping_city'] ?? '' }}</dd></div>
                    <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $order['shipping_phone'] ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Tracking</dt><dd>{{ $order['tracking_number'] ?: '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Očekivano slanje</dt><dd>{{ $order['expected_shipping_display'] ?? '—' }}</dd></div>
                </dl>
            </section>

            @if(is_array($shipment))
                <section class="a1-card" aria-labelledby="oc-shipment-title">
                    <h2 class="a1-h3" id="oc-shipment-title">Evidencija slanja pošiljke</h2>
                    <dl class="a1-dl">
                        <div class="a1-dl__row"><dt>Način isporuke</dt><dd>{{ $shipment['shipment_method_label'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Kurirska služba</dt><dd>{{ $shipment['courier_name'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Poslato</dt><dd>{{ $shipment['shipped_at'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Broj za praćenje pošiljke</dt><dd>{{ $shipment['tracking_number'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Primalac</dt><dd>{{ $shipment['recipient_name'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $shipment['recipient_phone'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Napomena o slanju</dt><dd>{{ $shipment['note'] ?? '—' }}</dd></div>
                    </dl>
                    @php($trackingLink = !empty($shipment['tracking_url']) && ($shipment['tracking_number'] ?? '—') !== '—')
                    @php($shipmentProofLink = ($shipment['has_proof'] ?? false) && !empty($urls['shipment_proof']))
                    @if($trackingLink || $shipmentProofLink)
                        <div class="a1-cluster">
                            @if($trackingLink)<a class="a1-btn a1-btn--sm" target="_blank" rel="noopener" href="{{ $shipment['tracking_url'] }}">Prati pošiljku</a>@endif
                            @if($shipmentProofLink)<a class="a1-btn a1-btn--sm" target="_blank" rel="noopener" href="{{ $urls['shipment_proof'] }}">Otvori dokaz slanja</a>@endif
                        </div>
                    @endif
                </section>
            @endif

            @if(is_array($delivery))
                <section class="a1-card" aria-labelledby="oc-delivery-title">
                    <h2 class="a1-h3" id="oc-delivery-title">Potvrđena isporuka</h2>
                    <dl class="a1-dl">
                        <div class="a1-dl__row"><dt>Način</dt><dd>{{ $delivery['delivery_method_label'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Datum</dt><dd>{{ $delivery['delivered_at'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Primalac</dt><dd>{{ $delivery['recipient_name'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Referenca</dt><dd>{{ $delivery['reference'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Napomena</dt><dd>{{ $delivery['note'] ?? '—' }}</dd></div>
                    </dl>
                    @if(($delivery['has_proof'] ?? false) && !empty($urls['delivery_proof']))
                        <div><a class="a1-btn a1-btn--sm" target="_blank" rel="noopener" href="{{ $urls['delivery_proof'] }}">Otvori dokaz isporuke</a></div>
                    @endif
                </section>
            @endif

            <section class="a1-card" aria-labelledby="oc-payment-title">
                <h2 class="a1-h3" id="oc-payment-title">Plaćanje</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Metod</dt><dd>{{ $paymentMethodLabels[$order['payment_method'] ?? ''] ?? ($order['payment_method'] ?? '—') }}</dd></div>
                    <div class="a1-dl__row"><dt>Status</dt><dd>{{ $order['payment_state_label'] ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Račun</dt><dd>{{ $order['bank_account_display'] ?? '—' }}</dd></div>
                </dl>
            </section>

            @if(($permissions['view_documents'] ?? false))
                <section class="a1-card" aria-labelledby="oc-documents-title">
                    <div class="a1-card__title">
                        <h2 class="a1-h3" id="oc-documents-title">Dokumenti</h2>
                        <p class="a1-text">Potvrde, predračuni i računi za ovu porudžbinu.</p>
                    </div>
                    @if(count($documents) === 0)
                        <p class="a1-text">Još nema izdatih dokumenata.</p>
                    @else
                        <ul class="a1-list">
                            @foreach($documents as $document)
                                <li class="a1-item a1-item--plain a1-item--static">
                                    <div class="a1-item__main">
                                        <strong class="a1-item__title">{{ $document['number'] ?? 'Dokument' }}</strong>
                                        <span class="a1-item__sub">
                                            {{ $documentLabels[$document['type'] ?? ''] ?? ($document['type'] ?? 'Dokument') }} ·
                                            revizija {{ $document['revision_number'] ?? 1 }} ·
                                            {{ $document['issued_at'] ?? '—' }}
                                        </span>
                                    </div>
                                    @if(!empty($document['show_url']))
                                        <a class="a1-btn a1-btn--sm" target="_blank" rel="noopener" href="{{ $document['show_url'] }}" aria-label="PDF dokumenta {{ $document['number'] ?? 'Dokument' }}"><x-icon name="download" :size="20" :shell="true" /> PDF</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            <section class="a1-card" aria-labelledby="oc-timeline-title">
                <h2 class="a1-h3" id="oc-timeline-title">Praćenje porudžbine</h2>
                @if(count($timeline) === 0)
                    <p class="a1-text">Još nema događaja za prikaz.</p>
                @else
                    <ol class="a1-timeline">
                        @foreach($timeline as $event)
                            <li>
                                <span class="a1-oc-dot" aria-hidden="true"></span>
                                <div class="a1-timeline__body">
                                    <div class="a1-timeline__head">
                                        <strong>{{ $event['title'] ?? 'Događaj' }}</strong>
                                        <time class="a1-timeline__time">{{ $event['created_at'] ?? '—' }}</time>
                                    </div>
                                    @if(!empty($event['description']))
                                        <p class="a1-oc-event">{{ $event['description'] }}</p>
                                    @endif
                                    <span class="a1-help">{{ $event['actor'] ?? 'Sistem' }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            @if(($actions['cancel'] ?? false) && !empty($urls['cancel']))
                <section class="a1-card a1-oc-danger" aria-labelledby="oc-cancel-title">
                    <h2 class="a1-h3 a1-oc-danger__title" id="oc-cancel-title"><x-icon name="alert" :size="20" :shell="true" /> Otkazivanje</h2>
                    <p class="a1-text">Možeš da otkažeš samo porudžbinu koju odgovorno lice još nije potvrdilo. Otkazivanje vraća rezervisani lager tačno jednom.</p>
                    <form class="a1-form" method="post" action="{{ $urls['cancel'] }}">
                        @csrf
                        <div class="a1-field">
                            <label class="a1-label" for="oc-cancel-note">Razlog otkazivanja<span class="a1-req" aria-hidden="true">*</span></label>
                            <textarea class="a1-textarea" id="oc-cancel-note" name="reason" rows="3" minlength="3" maxlength="1000" required @error('reason') aria-invalid="true" aria-describedby="oc-cancel-note-err" @enderror></textarea>
                            @error('reason')<p class="a1-error" id="oc-cancel-note-err">{{ $message }}</p>@enderror
                        </div>
                        <div><button class="a1-btn a1-btn--danger" type="submit" data-confirm="Otkazati porudžbinu i vratiti lager?">Otkaži porudžbinu</button></div>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection

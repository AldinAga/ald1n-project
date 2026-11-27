@extends('layouts.app')

@section('title', 'Porudžbina '.($detail['order']['order_number'] ?? ''))

@section('content')
@php
    // Module 3 (admin order detail on Kobalt components). Author: Ald1n <https://ald1n.com>
    $order = $detail['order'] ?? [];
    $orderNumber = $order['order_number'] ?? 'Porudžbina';
    $buyerName = $order['shipping_full_name'] ?? '—';
    $orderChannel = ($order['is_direct_sale'] ?? false) ? 'Prodajni kanal: Direktna prodaja' : 'Odgovorno lice: '.($order['supplier_name'] ?? 'Nije dodeljeno');
    $orderSummary = ($order['created_at'] ?? '—').' · lager '.($order['inventory_state'] ?? '—');
    $items = $detail['items'] ?? [];
    $documents = $detail['documents'] ?? [];
    $commission = $detail['commission'] ?? null;
    $timeline = $detail['timeline'] ?? [];
    $warnings = $detail['warnings'] ?? [];
    $urls = $detail['urls'] ?? [];
    $permissions = $detail['permissions'] ?? [];
    $actions = $detail['actions'] ?? [];
    $delivery = $detail['delivery'] ?? null;
    $shipment = $detail['shipment'] ?? null;
    $couriers = $detail['couriers'] ?? [];
    $formDefaults = $detail['form_defaults'] ?? [];
    $isCashOnDelivery = ($order['payment_method'] ?? '') === 'cash_on_delivery';
    $suppliers = $detail['suppliers'] ?? [];
    $activeDocumentsByType = collect($documents)->where('status', 'issued')->unique('type')->keyBy('type');
    $documentHistoryTypes = collect($documents)->pluck('type')->filter()->unique();
    $documentLabels = [
        'confirmation' => 'Potvrda',
        'order_confirmation' => 'Potvrda',
        'delivery_note' => 'Otpremnica',
        'proforma' => 'Predračun',
        'invoice' => 'Račun',
        'credit_note' => 'Storno dokument',
    ];
    // Status as an a1-pill (dot plus text): the tone is only the modifier.
    $pill = static fn (string $tone): string => 'a1-pill'.($tone !== '' ? ' a1-pill--'.$tone : '');
    $orderTone = \App\Support\OrderStatus::tone((string) ($order['status'] ?? 'new'), (bool) ($order['is_completed'] ?? false));
    $paymentTone = match ((string) ($order['payment_state'] ?? 'unpaid')) {
        'paid', 'overpaid' => 'ok',
        'partial' => 'warning',
        'cancelled' => 'danger',
        'unpaid' => 'off',
        default => '',
    };
    $commissionLabels = ['pending' => 'Na čekanju', 'approved' => 'Odobrena', 'paid' => 'Isplaćena', 'cancelled' => 'Stornirana'];
    $commissionTone = match ((string) ($commission['status'] ?? 'pending')) {
        'paid' => 'ok',
        'approved' => 'info',
        'cancelled' => 'danger',
        default => 'warning',
    };
    // aria-invalid / aria-describedby for a control, from the validation bag (the visible message sits next to the control).
    $ctl = fn (string $field, string $id, bool $help = false): string => ($errors->has($field) ? ' aria-invalid="true"' : '')
        .(($help || $errors->has($field)) ? ' aria-describedby="'.e(trim(($help ? $id.'-help ' : '').($errors->has($field) ? $id.'-err' : ''))).'"' : '');
@endphp

<div class="a1-ops" data-order-detail-ready="1">
    @if($warnings !== [])
        <div class="a1-alert a1-alert--warning" role="status">
            <x-icon name="alert" :size="20" :shell="true" />
            <div class="a1-alert__body">
                <p class="a1-alert__title">Napomena sistema</p>
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
            <li><a href="{{ $urls['back'] ?? '/admin/orders' }}">Porudžbine</a></li>
            <li aria-current="page">{{ $order['order_number'] ?? 'Porudžbina' }}</li>
        </ol>
    </nav>

    {{-- ux-maximal-phase4-order-detail-workspace-batch2 --}}
    @php
        $workspaceStatus = (string) ($order['status'] ?? 'new');
        $workspaceCompleted = (bool) ($order['is_completed'] ?? false);
        $workspaceShipmentTarget = is_array($shipment)
            ? '#order-workspace-shipment-record'
            : (($actions['shipment'] ?? false) ? '#order-workspace-logistics-actions' : null);
        $workspaceDeliveryTarget = $workspaceCompleted
            ? (is_array($delivery) ? '#order-workspace-delivery-record' : '#order-workspace-completed')
            : (($actions['complete'] ?? false) ? '#order-workspace-logistics-actions' : null);

        if (($actions['confirm'] ?? false) && !empty($urls['confirm'])) {
            $workspaceNextTarget = '#order-confirm';
            $workspaceNextLabel = 'Potvrdi porudžbinu';
            $workspaceNextTitle = 'Porudžbina čeka potvrdu';
            $workspaceNextText = 'Potvrda ujedno preuzima porudžbinu na tvoje ime. Posle nje sledi slanje ili predaja kupcu.';
        } elseif ((($actions['shipment'] ?? false) && !empty($urls['shipment_store'])) || (($actions['complete'] ?? false) && !empty($urls['complete']))) {
            $workspaceShipmentActive = (bool) ($detail['shipment_active'] ?? false);
            $workspaceCourierFirst = !$workspaceShipmentActive && (string) ($detail['delivery_default_method'] ?? 'courier') === 'courier';
            $workspaceNextTarget = '#order-workspace-logistics-actions';
            $workspaceNextLabel = $workspaceCourierFirst ? 'Pošiljka je poslata' : 'Potvrdi isporuku';
            $workspaceNextTitle = $workspaceShipmentActive
                ? 'Pošiljka je poslata: potvrdi isporuku'
                : ($workspaceCourierFirst ? 'Sledeći korak je slanje pošiljke' : 'Sledeći korak je isporuka');
            $workspaceNextText = $workspaceShipmentActive
                ? 'Kada roba stigne kod kupca, potvrdi isporuku. Naplata nije uslov: dug ostaje otvoren u Potraživanjima.'
                : 'U kartici Isporuka izaberi način. Kurirom prvo potvrdi da je pošiljka poslata (kurir i broj pošiljke), a sopstveni prevoz i lično preuzimanje se zatvaraju jednom potvrdom isporuke.';
        } elseif ($workspaceCompleted) {
            $workspaceNextTarget = '#order-workspace-timeline';
            $workspaceNextLabel = 'Pregledaj istoriju';
            $workspaceNextTitle = 'Porudžbina je završena';
            $workspaceNextText = 'Operativni tok je zaključen. Timeline, dokumenti, uplate i dokazi ostaju dostupni za proveru.';
        } elseif ($workspaceStatus === 'cancelled') {
            $workspaceNextTarget = '#order-workspace-timeline';
            $workspaceNextLabel = 'Pregledaj istoriju';
            $workspaceNextTitle = 'Porudžbina je otkazana';
            $workspaceNextText = 'Nema aktivne operativne akcije. Proveri timeline i istoriju poslovnih događaja.';
        } else {
            $workspaceNextTarget = '#order-workspace-items';
            $workspaceNextLabel = 'Pregledaj stavke';
            $workspaceNextTitle = 'Pregled porudžbine';
            $workspaceNextText = 'Proveri stavke, plaćanje, dokumente i istoriju pre sledeće dozvoljene akcije.';
        }
        $canConfirmNow = ($actions['confirm'] ?? false) && !empty($urls['confirm']);
        $canCancelNow = ($actions['cancel'] ?? false) && !empty($urls['cancel']);
    @endphp

    <header class="a1-card a1-od-head" data-order-detail-workspace="1" aria-labelledby="od-title">
        <div class="a1-od-head__row">
            <div class="a1-od-head__text">
                <span class="a1-eyebrow" title="{{ $orderChannel }}">{{ $orderChannel }}</span>
                <h1 class="a1-title" id="od-title" title="{{ $orderNumber }}">{{ $orderNumber }}</h1>
                <p class="a1-text" title="{{ $buyerName }} · {{ $orderSummary }}">
                    <span class="a1-od-buyer-name" title="{{ $buyerName }}">{{ $buyerName }}</span> · {{ $orderSummary }}
                </p>
            </div>

            <div class="a1-od-head__actions">
                @if($canConfirmNow)
                    {{-- Jedan korak: potvrda preuzima porudžbinu i potvrđuje je; token svežine ide uz formu, napomena iz kartice "Sledeći korak" (atribut form). --}}
                    <form id="order-confirm-form" method="post" action="{{ $urls['confirm'] }}">
                        @csrf
                        <input type="hidden" name="order_version_token" value="{{ $order['order_version_token'] ?? '' }}">
                        <button class="a1-btn a1-btn--primary" type="submit">
                            <x-icon name="check-circle" :size="20" :shell="true" /> Potvrdi porudžbinu
                        </button>
                    </form>
                @else
                    <a class="a1-btn a1-btn--primary" href="{{ $workspaceNextTarget }}">{{ $workspaceNextLabel }}</a>
                    @if(!empty($order['accepted_at']))
                        <span class="a1-pill a1-pill--wrap">Preuzeo: {{ $order['accepted_by'] ?? 'Administrator' }} · {{ $order['accepted_at'] }}</span>
                    @endif
                @endif
                @if($canCancelNow)
                    <a class="a1-btn" href="#order-cancel">Otkaži porudžbinu</a>
                @endif
                {{-- Račun se pravi automatski pri potvrdi isporuke; ponovno preuzimanje i ručno izdavanje su u kartici Dokumenti. --}}
                <a class="a1-btn" href="#order-workspace-documents"><x-icon name="receipt" :size="20" :shell="true" /> Dokumenti</a>
                @if(($actions['open_after_sales'] ?? false) && !empty($urls['after_sales_create']))
                    <a class="a1-btn" href="{{ $urls['after_sales_create'] }}" data-open-after-sales>
                        <x-icon name="alert" :size="20" :shell="true" /> Otvori reklamaciju / servis
                    </a>
                @endif
                @if(($permissions['after_sales_manage'] ?? false) && !empty($urls['after_sales']))
                    <a class="a1-btn" href="{{ $urls['after_sales'] }}">
                        <x-icon name="alert" :size="20" :shell="true" /> Reklamacije / servisi
                    </a>
                @endif
                @if(!empty($urls['confirmation']))
                    <a class="a1-btn" target="_blank" rel="noopener" href="{{ $urls['confirmation'] }}">
                        <x-icon name="file-text" :size="20" :shell="true" /> Potvrda PDF
                    </a>
                @endif
            </div>
        </div>

        <dl class="a1-od-facts">
            <div class="a1-od-fact">
                <dt>Status</dt>
                <dd><span class="{{ $pill($orderTone) }}">{{ $order['status_label'] ?? ($order['status'] ?? '—') }}</span></dd>
            </div>
            <div class="a1-od-fact">
                <dt>Plaćanje</dt>
                <dd><span class="{{ $pill($paymentTone) }}">{{ $order['payment_state_label'] ?? ($order['payment_status'] ?? '—') }}</span></dd>
            </div>
            <div class="a1-od-fact a1-od-fact--money">
                <dt>Ukupno</dt>
                <dd>{{ $order['subtotal_rsd_display'] ?? '—' }}</dd>
            </div>
            <div class="a1-od-fact a1-od-fact--money">
                <dt>Preostalo</dt>
                <dd>{{ $order['remaining_rsd_display'] ?? '—' }}</dd>
            </div>
        </dl>
    </header>

    <div class="a1-od-top" data-buyer-first-layout="1">
        <section class="a1-card a1-od-anchor" id="order-workspace-customer" aria-labelledby="order-buyer-card-title">
            <div class="a1-card__head">
                <div class="a1-od-titleblock">
                    <span class="a1-eyebrow">Prva informacija za obradu</span>
                    <h2 class="a1-h2" id="order-buyer-card-title">Krajnji kupac — kontakt i dostava</h2>
                </div>
                <a class="a1-link" href="#order-workspace-items">Stavke porudžbine <x-icon name="arrow-right" :size="16" :shell="true" /></a>
            </div>
            @php
                $buyerPhoneRaw = trim((string) ($order['shipping_phone'] ?? ''));
                $buyerPhoneDial = preg_replace('/[^0-9+]/', '', $buyerPhoneRaw) ?? '';
                $buyerHasCallablePhone = preg_match('/^\+?[0-9]{6,15}$/D', $buyerPhoneDial) === 1;
            @endphp
            <dl class="a1-dl a1-od-dl">
                <div class="a1-dl__row">
                    <dt>Ime i prezime primaoca</dt>
                    <dd class="a1-od-buyer-name" title="{{ $buyerName }}">{{ $buyerName }}</dd>
                </div>
                <div class="a1-dl__row">
                    <dt>Telefon</dt>
                    <dd>
                        @if($buyerHasCallablePhone)
                            <a class="a1-link a1-link--inline" href="tel:{{ $buyerPhoneDial }}" aria-label="Pozovi primaoca porudžbine: {{ $buyerPhoneRaw }}">{{ $buyerPhoneRaw }}</a>
                        @else
                            {{ $buyerPhoneRaw !== '' ? $buyerPhoneRaw : '—' }}
                        @endif
                    </dd>
                </div>
                <div class="a1-dl__row">
                    <dt id="od-buyer-address-label">Adresa za isporuku</dt>
                    <dd>
                        <div class="a1-od-buyer-address" tabindex="0" role="region" aria-labelledby="od-buyer-address-label">
                            {{ $order['shipping_address'] ?? '—' }},
                            {{ $order['shipping_postal_code'] ?? '' }} {{ $order['shipping_city'] ?? '' }}
                        </div>
                    </dd>
                </div>
                <div class="a1-dl__row">
                    <dt>Napomena krajnjeg kupca</dt>
                    <dd>{{ trim((string) ($order['customer_note'] ?? '')) ?: '—' }}</dd>
                </div>
                @if($permissions['buyer_tracking'] ?? false)
                    @php
                        $buyerTrackingNumber = trim((string) ($shipment['tracking_number'] ?? ''));
                        if ($buyerTrackingNumber === '' || $buyerTrackingNumber === '—') $buyerTrackingNumber = trim((string) ($order['tracking_number'] ?? ''));
                        if ($buyerTrackingNumber === '—') $buyerTrackingNumber = '';
                        $buyerTrackingUrl = $shipment['tracking_url'] ?? null;
                    @endphp
                    <div class="a1-dl__row">
                        <dt>Kurirska služba</dt>
                        <dd>{{ $shipment['courier_name'] ?? '—' }}</dd>
                    </div>
                    <div class="a1-dl__row">
                        <dt>Broj pošiljke</dt>
                        <dd class="a1-od-tracking" data-buyer-tracking>
                            @if($buyerTrackingNumber !== '')
                                <strong>{{ $buyerTrackingNumber }}</strong>
                                @if($buyerTrackingUrl !== null)
                                    <a class="a1-btn a1-btn--sm" data-buyer-track-link href="{{ $buyerTrackingUrl }}" target="_blank" rel="noopener noreferrer">Prati pošiljku <x-icon name="arrow-right" :size="16" :shell="true" /></a>
                                @else
                                    <span class="a1-help">Link za praćenje nije podešen.</span>
                                @endif
                            @else
                                <span>Pošiljka još nije evidentirana.</span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="a1-card a1-od-next" id="order-workspace-command" aria-labelledby="order-workspace-next-title" aria-live="polite">
            <div class="a1-od-titleblock">
                <span class="a1-eyebrow">Sledeći korak</span>
                <h2 class="a1-h2" id="order-workspace-next-title">{{ $workspaceNextTitle }}</h2>
                <p class="a1-text">{{ $workspaceNextText }}</p>
            </div>
            <nav class="a1-od-jump" aria-label="Brza navigacija kroz porudžbinu">
                <ul class="a1-tabs">
                    <li><a class="a1-tabs__link" href="#order-workspace-items">Stavke</a></li>
                    @if($workspaceShipmentTarget)<li><a class="a1-tabs__link" href="{{ $workspaceShipmentTarget }}">Slanje</a></li>@endif
                    @if($workspaceDeliveryTarget)<li><a class="a1-tabs__link" href="{{ $workspaceDeliveryTarget }}">Isporuka</a></li>@endif
                    <li><a class="a1-tabs__link" href="#order-workspace-payments">Uplate</a></li>
                    <li><a class="a1-tabs__link" href="#order-workspace-documents">Dokumenti</a></li>
                    <li><a class="a1-tabs__link" href="#order-workspace-timeline">Timeline</a></li>
                    <li><a class="a1-tabs__link" href="#order-workspace-customer">Kupac i dostava</a></li>
                </ul>
            </nav>
            @if($canConfirmNow)
                <div id="order-confirm" class="a1-form">
                    <div class="a1-field">
                        <label class="a1-label" for="od-confirm-note">Napomena uz potvrdu (opciono)</label>
                        <textarea class="a1-textarea" id="od-confirm-note" name="note" form="order-confirm-form" rows="2" maxlength="1000"{!! $ctl('note', 'od-confirm-note') !!}></textarea>
                        @error('note')<p class="a1-error" id="od-confirm-note-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                    </div>
                    <div class="a1-od-actions">
                        <button class="a1-btn" type="submit" form="order-confirm-form"><x-icon name="check-circle" :size="20" :shell="true" /> Potvrdi porudžbinu</button>
                    </div>
                </div>
            @endif
            @if($canCancelNow)
                <details class="a1-acc" data-order-cancel @if($errors->has('reason') && old('status') === null) open @endif>
                    <summary id="order-cancel"><span class="a1-od-acc-title"><strong>Otkaži porudžbinu</strong><small>{{ ($order['status'] ?? '') === 'shipped' ? 'Poslata, a neisporučena: otkazivanje odmah vraća rezervisani lager' : 'Razlog je obavezan; rezervisani lager se vraća odmah' }}</small></span></summary>
                    <div class="a1-acc__body">
                        <form method="post" action="{{ $urls['cancel'] }}" class="a1-form" aria-label="Otkazivanje porudžbine">
                            @csrf
                            <div class="a1-field">
                                <label class="a1-label" for="od-cancel-reason">Razlog otkazivanja<span class="a1-req" aria-hidden="true">*</span></label>
                                <textarea class="a1-textarea" id="od-cancel-reason" name="reason" rows="3" minlength="3" maxlength="1000" required{!! $ctl('reason', 'od-cancel-reason') !!}>{{ old('reason') }}</textarea>
                                @error('reason')<p class="a1-error" id="od-cancel-reason-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            </div>
                            <div class="a1-od-actions">
                                <button class="a1-btn a1-btn--danger" type="submit" data-confirm="Otkazati porudžbinu {{ $order['order_number'] ?? '' }}? Rezervisani lager se vraća odmah i otkazivanje se ne može poništiti.">Otkaži porudžbinu</button>
                            </div>
                        </form>
                    </div>
                </details>
            @endif
        </section>
    </div>

    @if(is_array($shipment))
        <section class="a1-card a1-od-anchor" data-shipment-card id="order-workspace-shipment-record" aria-labelledby="od-shiprec-title">
            <div class="a1-od-titleblock">
                <span class="a1-eyebrow">Logistika</span>
                <h2 class="a1-h2" id="od-shiprec-title">Evidencija slanja pošiljke</h2>
                @if($shipment['is_returned'] ?? false)
                    <p class="a1-text" data-shipment-returned>Pošiljka je vraćena {{ $shipment['returned_at'] ?? '' }}. Razlog: {{ $shipment['return_reason'] ?? '—' }}. Zapis ostaje sačuvan; pošalji porudžbinu ponovo ili je otkaži.</p>
                @else
                    <p class="a1-text">Pošiljka je evidentirana kao poslata. Ovo nije potvrda stvarne isporuke.</p>
                @endif
            </div>
            <dl class="a1-dl a1-od-dl">
                <div class="a1-dl__row"><dt>Način isporuke</dt><dd>{{ $shipment['shipment_method_label'] ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Kurirska služba</dt><dd>{{ $shipment['courier_name'] ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Datum i vreme slanja</dt><dd>{{ $shipment['shipped_at'] ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Primalac</dt><dd>{{ $shipment['recipient_name'] ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Telefon primaoca</dt><dd>{{ $shipment['recipient_phone'] ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Broj za praćenje pošiljke</dt><dd>{{ $shipment['tracking_number'] ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Evidentirao</dt><dd>{{ $shipment['recorded_by'] ?? 'Administrator' }}</dd></div>
                <div class="a1-dl__row"><dt>Napomena o slanju</dt><dd>{{ $shipment['note'] ?? '—' }}</dd></div>
            </dl>
            @if((!empty($shipment['tracking_url']) && ($shipment['tracking_number'] ?? '—') !== '—') || (($shipment['has_proof'] ?? false) && !empty($urls['shipment_proof'])))
                <div class="a1-od-actions">
                    @if(!empty($shipment['tracking_url']) && ($shipment['tracking_number'] ?? '—') !== '—')<a class="a1-btn" target="_blank" rel="noopener" href="{{ $shipment['tracking_url'] }}">Prati pošiljku</a>@endif
                    @if(($shipment['has_proof'] ?? false) && !empty($urls['shipment_proof']))<a class="a1-btn" target="_blank" rel="noopener" href="{{ $urls['shipment_proof'] }}">Otvori privatni dokaz slanja</a>@endif
                </div>
            @endif
        </section>
    @endif

    @if($order['is_completed'] ?? false)
        <section id="order-workspace-completed" class="a1-card a1-od-anchor a1-od-done" aria-labelledby="od-done-title">
            <span class="a1-od-done__icon" aria-hidden="true"><x-icon name="check-circle" :size="28" :shell="true" /></span>
            <div class="a1-od-done__body">
                <span class="a1-eyebrow">Konačno stanje</span>
                <h2 class="a1-h2" id="od-done-title">{{ ($order['is_direct_sale'] ?? false) ? 'Direktna prodaja je evidentirana' : 'Porudžbina i isporuka su kompletirane' }}</h2>
                <p class="a1-text">
                    {{ ($order['is_direct_sale'] ?? false) ? 'Evidentirano' : 'Završeno' }} {{ $order['completed_at'] ?? '—' }} ·
                    {{ $order['completed_by'] ?? 'Administrator' }} ·
                    {{ $order['payment_state_label'] ?? 'Plaćeno' }}
                </p>
                @if(is_array($delivery))
                    <div class="a1-od-sum">
                        <strong>{{ $delivery['delivery_method_label'] ?? 'Isporuka' }}</strong>
                        <span>{{ $delivery['delivered_at'] ?? '—' }} · primalac {{ $delivery['recipient_name'] ?? 'Kupac' }}</span>
                        @if($delivery['has_proof'] ?? false)
                            <a class="a1-btn a1-btn--sm" target="_blank" rel="noopener" href="{{ $urls['delivery_proof'] ?? '#' }}">Dokaz isporuke</a>
                        @endif
                    </div>
                @endif
                @if(!empty($order['completion_note']))
                    <p class="a1-help">{{ $order['completion_note'] }}</p>
                @endif
            </div>
        </section>
    @elseif((($actions['shipment'] ?? false) && !empty($urls['shipment_store'])) || (($actions['complete'] ?? false) && !empty($urls['complete'])) || ($actions['return_shipment'] ?? false))
        @php
            // Jedna kartica "Isporuka" (odluka 7): način isporuke određuje korake; zajednička polja se unose jednom i prenose u korak isporuke.
            $shipmentActive = (bool) ($detail['shipment_active'] ?? false);
            $defaultMethod = (string) ($detail['delivery_default_method'] ?? 'courier');
            $showShipmentStep = ($actions['shipment'] ?? false) && !empty($urls['shipment_store']);
            $showDeliveryStep = ($actions['complete'] ?? false) && !empty($urls['complete']);
            $courierFirst = $showShipmentStep && !$shipmentActive && $defaultMethod === 'courier';
            $openDebt = (float) ($order['remaining_rsd'] ?? 0);
        @endphp
        <section class="a1-card a1-od-anchor" id="order-workspace-logistics-actions" data-logistics-actions aria-labelledby="od-logistics-title">
            <div class="a1-od-titleblock">
                <span class="a1-eyebrow">Logistika</span>
                <h2 class="a1-h2" id="od-logistics-title">Isporuka</h2>
                <p class="a1-text">
                    @if($shipmentActive)
                        Pošiljka je poslata. Kada roba stigne kod kupca, potvrdi isporuku. Slanje i isporuka ostaju odvojeni događaji u istoriji.
                    @else
                        Kurirom: prvo potvrdi da je pošiljka poslata (kurir i broj pošiljke), zatim potvrdi isporuku. Sopstveni prevoz i lično preuzimanje: samo potvrdi isporuku.
                    @endif
                </p>
            </div>

            @if($showShipmentStep && !$shipmentActive)
                <div class="a1-field">
                    <label class="a1-label" for="od-method-switch">Način isporuke<span class="a1-req" aria-hidden="true">*</span></label>
                    <select class="a1-select" id="od-method-switch" data-delivery-method-switch>
                        @foreach(($detail['delivery_methods'] ?? []) as $option)
                            <option value="{{ $option['value'] }}" @selected($defaultMethod === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showShipmentStep)
                <form method="post" action="{{ $urls['shipment_store'] }}" enctype="multipart/form-data" class="a1-form a1-od-toggle" data-shipment-form data-logistics-panel="shipment" id="order-workspace-shipment-entry" aria-label="Pošiljka je poslata" @if(!$courierFirst) hidden @endif>
                    @csrf
                    <input type="hidden" name="order_version_token" value="{{ $order['order_version_token'] ?? '' }}">
                    <input type="hidden" name="shipment_method" value="courier" data-shipment-method>
                    <p class="a1-help">Polja sa zvezdicom su obavezna. Primalac, telefon i beleška se prenose u potvrdu isporuke.</p>
                    <div class="a1-row">
                        <div class="a1-field" data-courier-field>
                            <label class="a1-label" for="od-ship-courier">Kurirska služba<span class="a1-req" aria-hidden="true">*</span></label>
                            <select class="a1-select" id="od-ship-courier" name="courier_service_id" data-courier-select required{!! $ctl('courier_service_id', 'od-ship-courier') !!}>@foreach($couriers as $courier)<option value="{{ $courier['id'] }}" data-tracking-url="{{ $courier['tracking_url'] }}" @selected($courier['is_default'] ?? false)>{{ $courier['name'] }}</option>@endforeach</select>
                            @error('courier_service_id')<p class="a1-error" id="od-ship-courier-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            <a class="a1-link a1-od-toggle" data-courier-tracking-link href="#" target="_blank" rel="noopener">Zvanična stranica za praćenje</a>
                        </div>
                        <div class="a1-field" data-tracking-field>
                            <label class="a1-label" for="od-ship-tracking">Broj pošiljke<span class="a1-req" aria-hidden="true">*</span></label>
                            <input class="a1-input" id="od-ship-tracking" name="tracking_number" maxlength="120" value="{{ old('tracking_number', $order['tracking_number'] ?? '') }}" data-tracking-input required{!! $ctl('tracking_number', 'od-ship-tracking') !!}>
                            @error('tracking_number')<p class="a1-error" id="od-ship-tracking-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="od-ship-at">Datum i vreme slanja<span class="a1-req" aria-hidden="true">*</span></label>
                            <input class="a1-input" id="od-ship-at" type="datetime-local" name="shipped_at" value="{{ old('shipped_at', $formDefaults['shipped_at'] ?? '') }}" required{!! $ctl('shipped_at', 'od-ship-at') !!}>
                            @error('shipped_at')<p class="a1-error" id="od-ship-at-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="od-ship-name">Ime primaoca<span class="a1-req" aria-hidden="true">*</span></label>
                            <input class="a1-input" id="od-ship-name" name="recipient_name" maxlength="190" value="{{ old('recipient_name', $formDefaults['recipient_name'] ?? ($order['shipping_full_name'] ?? '')) }}" required{!! $ctl('recipient_name', 'od-ship-name') !!}>
                            @error('recipient_name')<p class="a1-error" id="od-ship-name-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="od-ship-phone">Telefon primaoca</label>
                            <input class="a1-input" id="od-ship-phone" name="recipient_phone" maxlength="60" value="{{ old('recipient_phone', $formDefaults['recipient_phone'] ?? ($order['shipping_phone'] ?? '')) }}"{!! $ctl('recipient_phone', 'od-ship-phone') !!}>
                            @error('recipient_phone')<p class="a1-error" id="od-ship-phone-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="od-ship-proof">Dokaz slanja (opciono, do 10 MB)</label>
                            <input class="a1-input" id="od-ship-proof" type="file" name="shipment_proof" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"{!! $ctl('shipment_proof', 'od-ship-proof') !!}>
                            @error('shipment_proof')<p class="a1-error" id="od-ship-proof-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field a1-od-wide">
                            <label class="a1-label" for="od-ship-note">Napomena</label>
                            <textarea class="a1-textarea" id="od-ship-note" name="note" rows="2" maxlength="3000"{!! $ctl('note', 'od-ship-note') !!}>{{ old('note') }}</textarea>
                            @error('note')<p class="a1-error" id="od-ship-note-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                    </div>
                    <div class="a1-od-actions">
                        <button class="a1-btn" type="submit" data-confirm="Potvrditi da je pošiljka poslata? Ovo ne potvrđuje isporuku niti naplatu."><x-icon name="truck" :size="20" :shell="true" /> Pošiljka je poslata</button>
                    </div>
                </form>
            @endif

            @if($showDeliveryStep)
                @if(!empty($order['reopened_at']))
                    <div class="a1-alert a1-alert--warning" role="status">
                        <x-icon name="alert" :size="20" :shell="true" />
                        <div class="a1-alert__body"><p>Porudžbina je ponovo otvorena {{ $order['reopened_at'] }}. Prethodni dokaz isporuke ostaje sačuvan dok ne priložiš novi.</p></div>
                    </div>
                @endif
                <form class="a1-form a1-od-toggle" id="delivery-completion" method="post" action="{{ $urls['complete'] }}" enctype="multipart/form-data" data-logistics-panel="delivery" aria-label="Potvrdi isporuku" @if($courierFirst) hidden @endif>
                    @csrf
                    <input type="hidden" name="delivery_method" value="{{ $courierFirst ? 'own_transport' : $defaultMethod }}" data-delivery-method-input>
                    <p class="a1-help">Polja sa zvezdicom su obavezna. Naplata nije uslov isporuke: dug ostaje otvoren u Potraživanjima, a garancija teče od datuma isporuke.</p>
                    <div class="a1-row">
                        <div class="a1-field">
                            <label class="a1-label" for="od-del-at">Datum i vreme isporuke<span class="a1-req" aria-hidden="true">*</span></label>
                            <input class="a1-input" id="od-del-at" type="datetime-local" name="delivered_at" value="{{ old('delivered_at', $delivery['delivered_at_input'] ?? ($formDefaults['delivered_at'] ?? '')) }}" required{!! $ctl('delivered_at', 'od-del-at') !!}>
                            @error('delivered_at')<p class="a1-error" id="od-del-at-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="od-del-name">Ime primaoca<span class="a1-req" aria-hidden="true">*</span></label>
                            <input class="a1-input" id="od-del-name" name="recipient_name" maxlength="190" value="{{ old('recipient_name', $delivery['recipient_name'] ?? ($formDefaults['recipient_name'] ?? '')) }}" required{!! $ctl('recipient_name', 'od-del-name') !!}>
                            @error('recipient_name')<p class="a1-error" id="od-del-name-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="od-del-phone">Telefon primaoca</label>
                            <input class="a1-input" id="od-del-phone" name="recipient_phone" maxlength="60" value="{{ old('recipient_phone', ($delivery['recipient_phone'] ?? '') !== '—' ? ($delivery['recipient_phone'] ?? ($formDefaults['recipient_phone'] ?? '')) : ($formDefaults['recipient_phone'] ?? '')) }}"{!! $ctl('recipient_phone', 'od-del-phone') !!}>
                            @error('recipient_phone')<p class="a1-error" id="od-del-phone-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="od-del-ref">Interna referenca / vozilo (opciono)</label>
                            <input class="a1-input" id="od-del-ref" name="delivery_reference" maxlength="190" value="{{ old('delivery_reference', ($delivery['reference'] ?? '') !== '—' ? ($delivery['reference'] ?? ($formDefaults['delivery_reference'] ?? '')) : ($formDefaults['delivery_reference'] ?? '')) }}"{!! $ctl('delivery_reference', 'od-del-ref') !!}>
                            @error('delivery_reference')<p class="a1-error" id="od-del-ref-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field">
                            @php $hasDeliveryProof = is_array($delivery) && ($delivery['has_proof'] ?? false); @endphp
                            <label class="a1-label" for="od-del-proof">Dokaz isporuke (opciono, do 10 MB)</label>
                            <input class="a1-input" id="od-del-proof" type="file" name="delivery_proof" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"{!! $ctl('delivery_proof', 'od-del-proof', $hasDeliveryProof) !!}>
                            @if($hasDeliveryProof)
                                <p class="a1-help" id="od-del-proof-help">Postojeći dokaz: {{ $delivery['proof_original_name'] ?? 'Dokaz isporuke' }}. Novi fajl će ga zameniti.</p>
                            @endif
                            @error('delivery_proof')<p class="a1-error" id="od-del-proof-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-field a1-od-wide">
                            <label class="a1-label" for="od-del-note">Napomena o isporuci</label>
                            <textarea class="a1-textarea" id="od-del-note" name="delivery_note" rows="2" maxlength="3000" placeholder="Stanje robe, posebne okolnosti, ko je preuzeo..."{!! $ctl('delivery_note', 'od-del-note') !!}>{{ old('delivery_note', ($delivery['note'] ?? '') !== '—' ? ($delivery['note'] ?? '') : '') }}</textarea>
                            @error('delivery_note')<p class="a1-error" id="od-del-note-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                    </div>
                    @if($openDebt > 0)
                        <p class="a1-help" data-delivery-payment-shortcut>Preostalo za naplatu: <strong>{{ $order['remaining_rsd_display'] ?? '' }}</strong>. Uplata nije uslov isporuke. <a class="a1-link" href="#order-workspace-payments">Evidentiraj uplatu</a></p>
                    @endif
                    <div class="a1-od-actions">
                        <button class="a1-btn" type="submit" data-confirm="{{ $isCashOnDelivery ? 'Potvrditi isporuku i naplaćen otkup? Račun se izdaje automatski, a garancija teče od datuma isporuke.' : 'Potvrditi isporuku? Račun se izdaje automatski, a garancija teče od datuma isporuke.' }}">
                            <x-icon name="check-circle" :size="20" :shell="true" /> Potvrdi isporuku
                        </button>
                    </div>
                </form>
            @endif

            @if($actions['return_shipment'] ?? false)
                <details class="a1-acc" data-order-shipment-return @if($errors->has('reason') && old('form_context') === 'shipment_return') open @endif>
                    <summary id="order-shipment-return"><span class="a1-od-acc-title"><strong>Pošiljka vraćena</strong><small>Kurir je vratio pošiljku: porudžbina je opet Potvrđena, roba ostaje rezervisana</small></span></summary>
                    <div class="a1-acc__body">
                        <form method="post" action="{{ $urls['shipment_return'] }}" class="a1-form" aria-label="Pošiljka vraćena">
                            @csrf
                            <input type="hidden" name="form_context" value="shipment_return">
                            <div class="a1-field">
                                <label class="a1-label" for="od-return-reason">Razlog povrata<span class="a1-req" aria-hidden="true">*</span></label>
                                <textarea class="a1-textarea" id="od-return-reason" name="reason" rows="3" minlength="3" maxlength="1000" required{!! $ctl('reason', 'od-return-reason') !!}></textarea>
                                @error('reason')<p class="a1-error" id="od-return-reason-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            </div>
                            <div class="a1-od-actions">
                                <button class="a1-btn" type="submit" data-confirm="Evidentirati povrat pošiljke? Porudžbina se vraća u Potvrđena; posle toga je možeš ponovo poslati ili otkazati.">Evidentiraj povrat</button>
                            </div>
                        </form>
                    </div>
                </details>
            @endif
        </section>
    @endif

    <div class="a1-split">
        <div class="a1-od-main">
            <section class="a1-card a1-od-anchor" id="order-workspace-items" aria-labelledby="od-items-title">
                <div class="a1-od-titleblock">
                    <h2 class="a1-h2" id="od-items-title">Stavke porudžbine</h2>
                    <p class="a1-text">Snapshot cena, količina i provizije u trenutku poručivanja.</p>
                </div>

                <div class="a1-tablewrap" role="region" aria-label="Stavke porudžbine" tabindex="0">
                    <table class="a1-table a1-table--cards" role="table">
                        <caption class="a1-sr">Stavke porudžbine: artikal, SKU, količina, jedinična cena, ukupno i provizija</caption>
                        <thead role="rowgroup">
                            <tr role="row">
                                <th scope="col" role="columnheader">Artikal</th>
                                <th scope="col" role="columnheader">SKU</th>
                                <th scope="col" role="columnheader" class="a1-table__num">Količina</th>
                                <th scope="col" role="columnheader" class="a1-table__num">Jedinična cena</th>
                                <th scope="col" role="columnheader" class="a1-table__num">Ukupno</th>
                                <th scope="col" role="columnheader" class="a1-table__num">Provizija</th>
                            </tr>
                        </thead>
                        <tbody role="rowgroup">
                            @forelse($items as $item)
                                <tr role="row">
                                    <td role="cell" class="a1-table__primary" data-label="Artikal">{{ $item['name'] ?? 'Nepoznat artikal' }}</td>
                                    <td role="cell" data-label="SKU">{{ $item['sku'] ?? '—' }}</td>
                                    <td role="cell" class="a1-table__num" data-label="Količina">{{ $item['quantity'] ?? 0 }}</td>
                                    <td role="cell" class="a1-table__num" data-label="Jedinična cena">{{ ($order['is_direct_sale'] ?? false) ? ($item['unit_price_original_display'] ?? ($item['unit_price'] ?? '0,00 RSD')) : ($item['unit_price'] ?? '0,00 RSD') }}</td>
                                    <td role="cell" class="a1-table__num" data-label="Ukupno">{{ $item['line_total'] ?? '0,00 RSD' }}</td>
                                    <td role="cell" class="a1-table__num" data-label="Provizija">{{ ($order['is_direct_sale'] ?? false) ? 'Nema provizije' : ($item['commission'] ?? '0,00 EUR') }}</td>
                                </tr>
                            @empty
                                <tr role="row"><td role="cell" colspan="6">Stavke porudžbine trenutno nisu dostupne.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <dl class="a1-od-totals">
                    <dt>Ukupno</dt>
                    <dd>{{ $order['subtotal_rsd_display'] ?? '0,00 RSD' }}</dd>
                </dl>

                @if(($actions['sale_price_correction'] ?? false) && !empty($urls['sale_price_correction']))
                    <section class="a1-od-sub" data-direct-sale-price-correction aria-labelledby="od-price-title">
                        <div class="a1-od-titleblock">
                            <h3 class="a1-h3" id="od-price-title">Korekcija prodajne cene</h3>
                            <p class="a1-text">Samo za Direct Sale. Izmena sinhronizuje stavku, subtotal i originalnu verifikovanu uplatu i ostavlja audit trag.</p>
                        </div>
                        <form method="post" action="{{ $urls['sale_price_correction'] }}" class="a1-form">
                            @csrf
                            @method('PATCH')
                            <div class="a1-row">
                                <div class="a1-field">
                                    <label class="a1-label" for="od-price-cur">Valuta stvarne prodajne cene<span class="a1-req" aria-hidden="true">*</span></label>
                                    <select class="a1-select" id="od-price-cur" name="new_unit_price_currency" required{!! $ctl('new_unit_price_currency', 'od-price-cur') !!}>
                                        <option value="RSD" @selected(($items[0]['original_currency'] ?? 'RSD') === 'RSD')>RSD</option>
                                        <option value="EUR" @selected(($items[0]['original_currency'] ?? 'RSD') === 'EUR')>EUR</option>
                                    </select>
                                    @error('new_unit_price_currency')<p class="a1-error" id="od-price-cur-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                                </div>
                                <div class="a1-field">
                                    <label class="a1-label" for="od-price-amount">Nova jedinicna prodajna cena<span class="a1-req" aria-hidden="true">*</span></label>
                                    <input class="a1-input" id="od-price-amount" type="number" name="new_unit_price_amount" step="0.01" min="0.01" max="999999999.99" required value="{{ isset($items[0]['unit_price_original']) ? number_format((float) $items[0]['unit_price_original'], 2, '.', '') : '' }}"{!! $ctl('new_unit_price_amount', 'od-price-amount') !!}>
                                    @error('new_unit_price_amount')<p class="a1-error" id="od-price-amount-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                                </div>
                                <div class="a1-field a1-od-wide">
                                    <label class="a1-label" for="od-price-reason">Razlog korekcije<span class="a1-req" aria-hidden="true">*</span></label>
                                    <textarea class="a1-textarea" id="od-price-reason" name="reason" rows="3" minlength="3" maxlength="1000" required placeholder="Npr. greska pri rucnom unosu prodajne cene"{!! $ctl('reason', 'od-price-reason') !!}></textarea>
                                    @error('reason')<p class="a1-error" id="od-price-reason-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                                </div>
                            </div>
                            <div class="a1-od-actions">
                                <button class="a1-btn" type="submit" data-confirm="Potvrdi korekciju prodajne cene? Izmena ce uskladiti stavku, subtotal i originalnu verifikovanu uplatu.">
                                    Koriguj prodajnu cenu
                                </button>
                            </div>
                        </form>
                    </section>
                @endif

                @if(is_array($commission))
                    <div class="a1-od-commission">
                        <span class="a1-od-commission__sum">
                            <x-icon name="wallet" :size="20" :shell="true" />
                            <strong>Provizija {{ $commission['total_eur_display'] ?? '0,00 EUR' }}</strong>
                        </span>
                        <span class="{{ $pill($commissionTone) }}">{{ $commissionLabels[$commission['status'] ?? 'pending'] ?? ($commission['status'] ?? 'pending') }}</span>
                        @if(!empty($urls['commission']))
                            <a class="a1-link" href="{{ $urls['commission'] }}">Otvori proviziju</a>
                        @endif
                    </div>
                @endif

                @if(!empty($urls['receivable']) && is_array($detail['receivable'] ?? null))
                    <div class="a1-od-commission" data-order-receivable-link>
                        <span class="a1-od-commission__sum">
                            <x-icon name="wallet" :size="20" :shell="true" />
                            <strong>Potraživanje {{ $detail['receivable']['case_number'] ?? '' }}</strong>
                        </span>
                        <a class="a1-link" href="{{ $urls['receivable'] }}">Otvori slučaj potraživanja</a>
                    </div>
                @endif
            </section>

            <div id="order-workspace-payments" class="a1-od-anchor">
                @include('admin.orders.partials.payments', ['detail' => $detail])
            </div>

            <section class="a1-card a1-od-anchor" id="order-workspace-documents" aria-labelledby="od-docs-title">
                <div class="a1-od-titleblock">
                    <h2 class="a1-h2" id="od-docs-title">Poslovni dokumenti</h2>
                    <p class="a1-text">Potvrda, predračun i račun sa zamrznutim poslovnim podacima.</p>
                </div>

                @if(($permissions['manage_invoices'] ?? false) && !empty($urls['document_store']))
                    <div class="a1-od-actions">
                        @if($activeDocumentsByType->has('proforma'))
                            <a class="a1-btn" target="_blank" rel="noopener" href="{{ $activeDocumentsByType->get('proforma')['show_url'] }}">
                                <x-icon name="file-text" :size="20" :shell="true" /> Otvori aktivni predračun
                            </a>
                        @else
                            <form method="post" target="_blank" action="{{ $urls['document_store'] }}">
                                @csrf
                                <input type="hidden" name="document_type" value="proforma">
                                <button class="a1-btn" type="submit">
                                    <x-icon name="file-text" :size="20" :shell="true" /> {{ $documentHistoryTypes->contains('proforma') ? 'Izdaj novi predračun' : 'Izdaj predračun' }}
                                </button>
                            </form>
                        @endif

                        @if($activeDocumentsByType->has('invoice'))
                            <a class="a1-btn" target="_blank" rel="noopener" href="{{ $activeDocumentsByType->get('invoice')['show_url'] }}">
                                <x-icon name="receipt" :size="20" :shell="true" /> Otvori aktivni račun
                            </a>
                        @else
                            <form method="post" target="_blank" action="{{ $urls['document_store'] }}">
                                @csrf
                                <input type="hidden" name="document_type" value="invoice">
                                <button class="a1-btn" type="submit">
                                    <x-icon name="receipt" :size="20" :shell="true" /> {{ $documentHistoryTypes->contains('invoice') ? 'Izdaj novi račun' : 'Izdaj račun' }}
                                </button>
                            </form>
                        @endif

                        @if(is_array($delivery))
                            @if($activeDocumentsByType->has('delivery_note'))
                                <a class="a1-btn" target="_blank" rel="noopener" href="{{ $activeDocumentsByType->get('delivery_note')['show_url'] }}">
                                    <x-icon name="truck" :size="20" :shell="true" /> Otvori aktivnu otpremnicu
                                </a>
                            @else
                                <form method="post" target="_blank" action="{{ $urls['document_store'] }}">
                                    @csrf
                                    <input type="hidden" name="document_type" value="delivery_note">
                                    <button class="a1-btn" type="submit">
                                        <x-icon name="truck" :size="20" :shell="true" /> {{ $documentHistoryTypes->contains('delivery_note') ? 'Izdaj novu otpremnicu' : 'Izdaj otpremnicu' }}
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                @endif

                @if(count($documents) > 0)
                    <ul class="a1-od-doclist">
                        @foreach($documents as $document)
                            <li class="a1-od-doc">
                                <div class="a1-od-doc__main">
                                    <strong>{{ $document['number'] ?? 'Dokument' }}</strong>
                                    <span class="a1-od-doc__meta">
                                        {{ $documentLabels[$document['type'] ?? ''] ?? ($document['type'] ?? 'Dokument') }} ·
                                        revizija {{ $document['revision_number'] ?? 1 }} ·
                                        {{ $document['issued_at'] ?? '—' }}
                                    </span>
                                    @if(!empty($document['supersedes_number']))
                                        <span class="a1-od-doc__meta">Menja dokument {{ $document['supersedes_number'] }}</span>
                                    @endif
                                    @if(($document['status'] ?? '') === 'cancelled' && !empty($document['cancellation_reason']))
                                        <span class="a1-od-doc__meta">Razlog storniranja: {{ $document['cancellation_reason'] }}</span>
                                    @endif
                                </div>
                                <div class="a1-od-doc__actions">
                                    <span class="{{ $pill(($document['status'] ?? '') === 'cancelled' ? 'danger' : 'ok') }}">{{ ($document['status'] ?? '') === 'cancelled' ? 'Storniran' : 'Aktivan' }}</span>
                                    @if(!empty($document['show_url']))
                                        <a class="a1-btn a1-btn--sm" target="_blank" rel="noopener" href="{{ $document['show_url'] }}" aria-label="PDF: {{ $document['number'] ?? 'Dokument' }}">PDF</a>
                                    @endif
                                </div>
                                @if(($document['can_cancel'] ?? false) && !empty($document['cancel_url']))
                                    <details class="a1-acc">
                                        <summary>Storniraj<span class="a1-sr"> dokument {{ $document['number'] ?? '' }}</span></summary>
                                        <div class="a1-acc__body">
                                            <form method="post" action="{{ $document['cancel_url'] }}" class="a1-form">
                                                @csrf
                                                <div class="a1-field">
                                                    <label class="a1-label" for="od-cancel-{{ $document['id'] ?? $loop->index }}">Razlog storniranja<span class="a1-req" aria-hidden="true">*</span></label>
                                                    <textarea class="a1-textarea" id="od-cancel-{{ $document['id'] ?? $loop->index }}" name="cancellation_reason" minlength="5" maxlength="1000" required placeholder="Npr. pogrešni podaci, iznos ili kupac"{!! $ctl('cancellation_reason', 'od-cancel-'.($document['id'] ?? $loop->index)) !!}></textarea>
                                                    @error('cancellation_reason')<p class="a1-error" id="od-cancel-{{ $document['id'] ?? $loop->index }}-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                                                </div>
                                                <div class="a1-od-actions">
                                                    <button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" data-confirm="Stornirati dokument? Nakon toga možeš izdati novu reviziju.">Potvrdi storniranje</button>
                                                </div>
                                            </form>
                                        </div>
                                    </details>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="a1-text">Još nema izdatih dokumenata.</p>
                @endif
            </section>

            <section class="a1-card a1-od-anchor" id="order-workspace-timeline" aria-labelledby="od-timeline-title">
                <div class="a1-od-titleblock">
                    <h2 class="a1-h2" id="od-timeline-title">Timeline porudžbine</h2>
                    <p class="a1-text">Statusi, dodele, interne napomene, uplate i dokumenti.</p>
                </div>

                @if(count($timeline) > 0)
                    <ol class="a1-od-timeline">
                        @foreach($timeline as $event)
                            <li class="a1-od-event">
                                <span class="a1-od-event__dot" aria-hidden="true"></span>
                                <div class="a1-od-event__body">
                                    <div class="a1-od-event__head">
                                        <strong>{{ $event['title'] ?? 'Događaj' }}</strong>
                                        <time>{{ $event['created_at'] ?? '—' }}</time>
                                    </div>
                                    @if(!empty($event['description']))
                                        <p class="a1-text">{{ $event['description'] }}</p>
                                    @endif
                                    <span class="a1-od-event__meta">
                                        {{ $event['actor'] ?? 'Sistem' }}
                                        @if(($event['visibility'] ?? 'public') === 'internal')
                                            · interna beleška
                                        @endif
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="a1-text">Još nema događaja za prikaz.</p>
                @endif
            </section>
        </div>

        <aside class="a1-od-side" aria-label="Kupac, odgovorno lice i dodatne radnje">
            <section class="a1-card" aria-labelledby="od-who-title">
                <h2 class="a1-h2" id="od-who-title">{{ ($order['is_direct_sale'] ?? false) ? 'Kupac i direktna prodaja' : 'Kupac i odgovorno lice' }}</h2>
                <dl class="a1-dl a1-od-dl">
                    <div class="a1-dl__row">
                        <dt>Korisnik</dt>
                        <dd>{{ $order['user_name'] ?? 'Nepoznat korisnik' }}
                            @if(!empty($order['user_username']) && $order['user_username'] !== '—')
                                ({{ $order['user_username'] }})
                            @endif
                        </dd>
                    </div>
                    @if($order['is_direct_sale'] ?? false)
                        <div class="a1-dl__row"><dt>Prodajni kanal</dt><dd>Direktna prodaja</dd></div>
                        <div class="a1-dl__row"><dt>Evidentirao</dt><dd>{{ $order['completed_by'] ?? 'SuperAdministrator' }}</dd></div>
                        <div class="a1-dl__row"><dt>Vreme prodaje</dt><dd>{{ $order['completed_at'] ?? '—' }}</dd></div>
                    @else
                        <div class="a1-dl__row"><dt>Odgovorno lice</dt><dd>{{ $order['supplier_name'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Uloga</dt><dd>{{ $order['supplier_role'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>E-mail</dt><dd>{{ $order['supplier_email'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $order['supplier_phone'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Preuzeto</dt><dd>{{ $order['accepted_at'] ?? '—' }}</dd></div>
                    @endif
                </dl>
            </section>

            @if(($actions['reassign'] ?? false) && !empty($urls['reassign']))
                <details class="a1-acc" data-order-workspace-secondary>
                    <summary><span class="a1-od-acc-title"><strong>Promeni odgovorno lice</strong><small>Napredna administrativna akcija</small></span></summary>
                    <div class="a1-acc__body" id="order-workspace-reassign">
                        <form method="post" action="{{ $urls['reassign'] }}" class="a1-form" aria-label="Ponovna dodela">
                            @csrf
                            @method('PATCH')
                            <div class="a1-field">
                                <label class="a1-label" for="od-reassign-user">Novo odgovorno lice<span class="a1-req" aria-hidden="true">*</span></label>
                                <select class="a1-select" id="od-reassign-user" name="supplier_user_id" required{!! $ctl('supplier_user_id', 'od-reassign-user') !!}>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier['id'] }}" @if($supplier['selected'] ?? false) disabled @endif>
                                            {{ $supplier['name'] }} · {{ $supplier['role'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_user_id')<p class="a1-error" id="od-reassign-user-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            </div>
                            <div class="a1-field">
                                <label class="a1-label" for="od-reassign-reason">Razlog dodele<span class="a1-req" aria-hidden="true">*</span></label>
                                <textarea class="a1-textarea" id="od-reassign-reason" name="reason" rows="3" maxlength="1000" required{!! $ctl('reason', 'od-reassign-reason') !!}></textarea>
                                @error('reason')<p class="a1-error" id="od-reassign-reason-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            </div>
                            <div class="a1-od-actions">
                                <button class="a1-btn" type="submit" data-confirm="Dodeliti porudžbinu drugom Administratoru?">Promeni odgovorno lice</button>
                            </div>
                        </form>
                    </div>
                </details>
            @endif

            @if(!($order['is_completed'] ?? false) && ($permissions['internal_notes'] ?? false) && !empty($urls['internal_note']))
                <section class="a1-card a1-od-anchor" id="order-workspace-note" aria-labelledby="od-note-title">
                    <h2 class="a1-h2" id="od-note-title">Interna napomena</h2>
                    <form method="post" action="{{ $urls['internal_note'] }}" class="a1-form">
                        @csrf
                        <div class="a1-field">
                            <label class="a1-label" for="od-note">Napomena koju korisnik ne vidi<span class="a1-req" aria-hidden="true">*</span></label>
                            <textarea class="a1-textarea" id="od-note" name="note" rows="4" maxlength="5000" required{!! $ctl('note', 'od-note') !!}></textarea>
                            @error('note')<p class="a1-error" id="od-note-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                        </div>
                        <div class="a1-od-actions">
                            <button class="a1-btn" type="submit">Sačuvaj internu napomenu</button>
                        </div>
                    </form>
                </section>
            @endif

            @if(!($order['is_completed'] ?? false) && !empty($urls['deadlines']))
                <details class="a1-acc" data-order-workspace-secondary>
                    <summary><span class="a1-od-acc-title"><strong>Uredi operativne rokove</strong><small>Otvori samo kada rok obrade ili slanja treba korigovati</small></span></summary>
                    <div class="a1-acc__body" id="order-workspace-deadlines">
                        <form method="post" action="{{ $urls['deadlines'] }}" class="a1-form" aria-label="Operativni rokovi">
                            @csrf
                            @method('PATCH')
                            <div class="a1-field">
                                <label class="a1-label" for="od-dl-processing">Očekivan završetak obrade</label>
                                <input class="a1-input" id="od-dl-processing" type="datetime-local" name="expected_processing_at" value="{{ $order['expected_processing_at'] ?? '' }}"{!! $ctl('expected_processing_at', 'od-dl-processing') !!}>
                                @error('expected_processing_at')<p class="a1-error" id="od-dl-processing-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            </div>
                            <div class="a1-field">
                                <label class="a1-label" for="od-dl-shipping">Očekivano slanje</label>
                                <input class="a1-input" id="od-dl-shipping" type="datetime-local" name="expected_shipping_at" value="{{ $order['expected_shipping_at'] ?? '' }}"{!! $ctl('expected_shipping_at', 'od-dl-shipping') !!}>
                                @error('expected_shipping_at')<p class="a1-error" id="od-dl-shipping-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            </div>
                            <div class="a1-od-actions">
                                <button class="a1-btn" type="submit">Sačuvaj rokove</button>
                            </div>
                        </form>
                    </div>
                </details>
            @endif

            @if(is_array($delivery))
                <section class="a1-card a1-od-anchor" id="order-workspace-delivery-record" aria-labelledby="od-delrec-title">
                    <h2 class="a1-h2" id="od-delrec-title">Evidencija isporuke</h2>
                    <dl class="a1-dl a1-od-dl">
                        <div class="a1-dl__row"><dt>Način</dt><dd>{{ $delivery['delivery_method_label'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Isporučeno</dt><dd>{{ $delivery['delivered_at'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Primalac</dt><dd>{{ $delivery['recipient_name'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $delivery['recipient_phone'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Referenca</dt><dd>{{ $delivery['reference'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Potvrdio</dt><dd>{{ $delivery['confirmed_by'] ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Napomena</dt><dd>{{ $delivery['note'] ?? '—' }}</dd></div>
                    </dl>
                    @if(($delivery['has_proof'] ?? false) && !empty($urls['delivery_proof']))
                        <div class="a1-od-actions">
                            <a class="a1-btn" target="_blank" rel="noopener" href="{{ $urls['delivery_proof'] }}">Otvori privatni dokaz isporuke</a>
                        </div>
                    @endif
                </section>
            @endif

            {{-- Ručna promena statusa: samo superadmin, i samo prelazi dozvoljeni iz trenutnog statusa. --}}
            @if(($actions['manual_status'] ?? false) && !empty($urls['status']))
                <details class="a1-acc" data-order-workspace-secondary>
                    <summary><span class="a1-od-acc-title"><strong>Ručna promena statusa</strong><small>Samo superadmin; koristi kada glavna akcija ne rešava korak</small></span></summary>
                    <div class="a1-acc__body" id="order-workspace-status">
                        <form method="post" action="{{ $urls['status'] }}" class="a1-form" aria-label="Status porudžbine">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="order_version_token" value="{{ $order['order_version_token'] ?? '' }}">
                            <div class="a1-field">
                                <label class="a1-label" for="od-status">Novi status</label>
                                <select class="a1-select" id="od-status" name="status" required{!! $ctl('status', 'od-status') !!}>
                                    <option value="" selected disabled>Sada: {{ $order['status_label'] ?? '—' }}</option>
                                    @foreach(($detail['manual_status_options'] ?? []) as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('status')<p class="a1-error" id="od-status-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            </div>
                            <div class="a1-field">
                                <label class="a1-label" for="od-status-note">Napomena / razlog (obavezan za otkazivanje)</label>
                                <textarea class="a1-textarea" id="od-status-note" name="note" rows="3" maxlength="1000"{!! $ctl('note', 'od-status-note') !!}></textarea>
                                @error('note')<p class="a1-error" id="od-status-note-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                                @if(old('status') !== null)@error('reason')<p class="a1-error" id="od-status-reason-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror @endif
                            </div>
                            <div class="a1-od-actions">
                                <button class="a1-btn" type="submit" data-confirm="Potvrditi promenu statusa?">Promeni status</button>
                            </div>
                        </form>
                    </div>
                </details>
            @endif
        </aside>
    </div>

    @php
        $canReopenNow = ($order['is_completed'] ?? false) && ($actions['reopen'] ?? false) && !empty($urls['reopen']);
        $canArchiveNow = ($permissions['archive'] ?? false) && !empty($urls['archive']) && (($order['is_completed'] ?? false) || ($order['status'] ?? '') === 'cancelled');
    @endphp
    @if($canReopenNow || $canArchiveNow)
        <section class="a1-card a1-od-danger" id="order-workspace-danger" aria-labelledby="od-danger-title">
            <div class="a1-alert a1-alert--danger" role="note">
                <x-icon name="alert" :size="20" :shell="true" />
                <div class="a1-alert__body">
                    <h2 class="a1-alert__title" id="od-danger-title">Opasne radnje</h2>
                    <p>Ove radnje menjaju stanje zaključene porudžbine. Svaka traži razlog i potvrdu.</p>
                </div>
            </div>

            @if($canReopenNow)
                <details class="a1-acc" data-order-reopen-workspace>
                    <summary><span class="a1-od-acc-title"><strong>Ponovo otvori porudžbinu</strong><small>Plaćanje i dokaz isporuke ostaju sačuvani</small></span></summary>
                    <div class="a1-acc__body">
                        <form method="post" action="{{ $urls['reopen'] }}" class="a1-form" aria-label="Ponovno otvaranje porudžbine">
                            @csrf
                            <div class="a1-field">
                                <label class="a1-label" for="od-reopen-reason">Obavezan razlog ponovnog otvaranja<span class="a1-req" aria-hidden="true">*</span></label>
                                <textarea class="a1-textarea" id="od-reopen-reason" name="reason" rows="3" maxlength="1000" required placeholder="Npr. potrebno je ispraviti uplatu ili podatke isporuke"{!! $ctl('reason', 'od-reopen-reason') !!}></textarea>
                                @error('reason')<p class="a1-error" id="od-reopen-reason-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            </div>
                            <div class="a1-od-actions">
                                <button class="a1-btn a1-btn--danger" type="submit" data-confirm="Ponovo otvoriti kompletiranu porudžbinu? Plaćanje i dokaz isporuke ostaju sačuvani.">
                                    Ponovo otvori za korekciju
                                </button>
                            </div>
                        </form>
                    </div>
                </details>
            @endif

            @if($canArchiveNow)
                <details class="a1-acc" data-order-archive-workspace>
                    <summary><span class="a1-od-acc-title"><strong>Arhiviraj porudžbinu</strong><small>Skloni završenu ili otkazanu porudžbinu iz operativnih prikaza bez brisanja poslovne istorije</small></span></summary>
                    <div class="a1-acc__body">
                        <p class="a1-text">Arhiviranje je reverzibilno. Status, uplate, dokumenti, garancije, provizije i audit istorija ostaju nepromenjeni.</p>
                        <form method="post" action="{{ $urls['archive'] }}" class="a1-form" aria-label="Arhiviranje porudžbine">
                            @csrf
                            <div class="a1-field">
                                <label class="a1-label" for="od-archive-reason">Razlog arhiviranja<span class="a1-req" aria-hidden="true">*</span></label>
                                <textarea class="a1-textarea" id="od-archive-reason" name="archive_reason" rows="3" maxlength="1000" required placeholder="Npr. završena porudžbina preneta u arhivu."{!! $ctl('archive_reason', 'od-archive-reason') !!}>{{ old('archive_reason') }}</textarea>
                                @error('archive_reason')<p class="a1-error" id="od-archive-reason-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                            </div>
                            <div class="a1-od-actions">
                                <button class="a1-btn a1-btn--danger" type="submit" data-confirm="Arhivirati porudžbinu {{ $order['order_number'] ?? '' }}? Možeš je kasnije vratiti iz odeljka Arhivirane porudžbine.">
                                    <x-icon name="archive" :size="20" :shell="true" /> Arhiviraj porudžbinu
                                </button>
                            </div>
                        </form>
                    </div>
                </details>
            @endif
        </section>
    @endif
</div>

<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    // Kartica "Isporuka": način isporuke bira korak. Kurir = "Pošiljka je poslata", ostalo = samo "Potvrdi isporuku".
    const methodSwitch = document.querySelector('[data-delivery-method-switch]');
    if (methodSwitch) {
        const shipmentPanel = document.querySelector('[data-logistics-panel="shipment"]');
        const deliveryPanel = document.querySelector('[data-logistics-panel="delivery"]');
        const methodInput = document.querySelector('[data-delivery-method-input]');
        const syncDeliveryMethod = () => {
            const courier = methodSwitch.value === 'courier';
            if (shipmentPanel) shipmentPanel.hidden = !courier;
            if (deliveryPanel) deliveryPanel.hidden = courier;
            if (methodInput && !courier) methodInput.value = methodSwitch.value;
        };
        methodSwitch.addEventListener('change', syncDeliveryMethod);
        syncDeliveryMethod();
    }

    document.querySelectorAll('[data-shipment-form]').forEach((form) => {
        const method = form.querySelector('[data-shipment-method]');
        const courierField = form.querySelector('[data-courier-field]');
        const courierSelect = form.querySelector('[data-courier-select]');
        const trackingField = form.querySelector('[data-tracking-field]');
        const trackingInput = form.querySelector('[data-tracking-input]');
        const trackingLink = form.querySelector('[data-courier-tracking-link]');
        const sync = () => {
            const isCourier = method?.value === 'courier';
            if (courierField) courierField.hidden = !isCourier;
            if (trackingField) trackingField.hidden = !isCourier;
            if (courierSelect) courierSelect.required = isCourier;
            if (trackingInput) trackingInput.required = isCourier;
            const selected = courierSelect?.selectedOptions?.[0];
            const url = selected?.dataset?.trackingUrl || '';
            if (trackingLink) {
                trackingLink.hidden = !isCourier || !url;
                if (url) trackingLink.href = url;
            }
        };
        method?.addEventListener('change', sync);
        courierSelect?.addEventListener('change', sync);
        sync();
    });
})();
</script>@endsection

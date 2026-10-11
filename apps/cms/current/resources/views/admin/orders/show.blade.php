@extends('layouts.app')

@section('title', 'Porudžbina '.($detail['order']['order_number'] ?? ''))

@section('content')
@php
    $order = $detail['order'] ?? [];
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
    // SHIPMENT_DELIVERY_DUAL_ACTION_BATCH513
    $logisticsOpenAction = old('shipment_method') !== null
        ? 'shipment'
        : (old('delivery_method') !== null ? 'delivery' : null);
    $isCashOnDelivery = ($order['payment_method'] ?? '') === 'cash_on_delivery';
    $deliveryActionLabel = $isCashOnDelivery
        ? 'Potvrdi da je pošiljka isporučena i da je naplaćen otkup'
        : 'Potvrdi da je pošiljka isporučena';
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
@endphp

<div data-order-detail-ready="1">
    @if($warnings !== [])
        <div class="alert alert-warning order-detail-warning">
            <strong>Napomena sistema</strong>
            <ul>
                @foreach($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <a class="back-link" href="{{ $urls['back'] ?? '/admin/orders' }}">← Nazad na porudžbine</a>

    <div class="page-heading order-workspace-heading" data-order-detail-workspace="1">
        <div>
            <span class="eyebrow">{{ ($order['is_direct_sale'] ?? false) ? 'Prodajni kanal: Direktna prodaja' : 'Odgovorno lice: '.($order['supplier_name'] ?? 'Nije dodeljeno') }}</span>
            <h1>{{ $order['order_number'] ?? 'Porudžbina' }}</h1>
            <p>
                {{ $order['shipping_full_name'] ?? '—' }} ·
                {{ $order['created_at'] ?? '—' }} ·
                lager {{ $order['inventory_state'] ?? '—' }}
            </p>
        </div>

        <div class="header-button-row">
            @if(!empty($urls['invoice_pdf']))
                @can('invoices.manage')
                    <form method="post" action="{{ $urls['invoice_pdf'] }}" target="_blank" data-issue-invoice-pdf>
                        @csrf
                        <button class="button button-primary" type="submit">Izdaj / otvori ra&#269;un (PDF)</button>
                    </form>
                @endcan
            @endif
            @if(($permissions['after_sales_manage'] ?? false) && !empty($urls['after_sales']))
                <a class="button button-ghost" href="{{ $urls['after_sales'] }}">
                    <x-icon name="alert" /> Reklamacije / servisi
                </a>
            @endif
            @if(($actions['accept'] ?? false) && !empty($urls['accept']))
                <form id="order-workspace-accept" method="post" action="{{ $urls['accept'] }}">
                    @csrf
                    <button class="button button-primary" type="submit">
                        <x-icon name="user-check" /> Preuzmi porudžbinu
                    </button>
                </form>
            @elseif(!empty($order['accepted_at']))
                <span class="count-pill">
                    Preuzeo: {{ $order['accepted_by'] ?? 'Administrator' }} · {{ $order['accepted_at'] }}
                </span>
            @endif

            @if(!empty($urls['confirmation']))
                <a class="button button-ghost" target="_blank" rel="noopener" href="{{ $urls['confirmation'] }}">
                    <x-icon name="file-text" /> Potvrda PDF
                </a>
            @endif

            <span class="status-badge status-{{ $order['status_class'] ?? 'draft' }}">
                {{ $order['status_label'] ?? ($order['status'] ?? '—') }}
            </span>
        </div>
    </div>



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

        if (($actions['accept'] ?? false) && !empty($urls['accept'])) {
            $workspaceNextTarget = '#order-workspace-accept';
            $workspaceNextLabel = 'Preuzmi porudžbinu';
            $workspaceNextTitle = 'Porudžbina čeka preuzimanje';
            $workspaceNextText = 'Preuzmi je za obradu. Nakon toga nastavi kroz status, slanje i stvarnu isporuku.';
        } elseif (($actions['shipment'] ?? false) && !empty($urls['shipment_store'])) {
            $workspaceNextTarget = '#order-workspace-logistics-actions';
            $workspaceNextLabel = 'Evidentiraj slanje';
            $workspaceNextTitle = 'Sledeći korak je slanje pošiljke';
            $workspaceNextText = 'Evidentiraj da je roba poslata. Ova akcija ne potvrđuje stvarnu isporuku niti naplatu pouzećem.';
        } elseif (($actions['complete'] ?? false) && !empty($urls['complete'])) {
            $workspaceNextTarget = '#order-workspace-logistics-actions';
            $workspaceNextLabel = 'Potvrdi isporuku';
            $workspaceNextTitle = 'Slanje je evidentirano — potvrdi stvarnu isporuku';
            $workspaceNextText = 'Kompletiranje zaključuje stvarnu isporuku i, kada je primenljivo, konačno evidentiranje plaćanja.';
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
        } elseif (!empty($urls['status'])) {
            $workspaceNextTarget = '#order-workspace-status';
            $workspaceNextLabel = 'Pregledaj status';
            $workspaceNextTitle = 'Nastavi obradu porudžbine';
            $workspaceNextText = 'Koristi poslovnu akciju koja odgovara stvarnom stanju porudžbine; ručna promena statusa je sekundarna.';
        } else {
            $workspaceNextTarget = '#order-workspace-items';
            $workspaceNextLabel = 'Pregledaj stavke';
            $workspaceNextTitle = 'Pregled porudžbine';
            $workspaceNextText = 'Proveri stavke, plaćanje, dokumente i istoriju pre sledeće dozvoljene akcije.';
        }
    @endphp

    <div class="order-priority-layout" data-buyer-first-layout="1">
        <section class="panel form-section order-workspace-anchor order-workspace-customer" id="order-workspace-customer" aria-labelledby="order-buyer-card-title">
            <div class="section-heading-row order-buyer-card-heading">
                <div>
                    <span class="eyebrow">Prva informacija za obradu</span>
                    <h2 id="order-buyer-card-title">Krajnji kupac — kontakt i dostava</h2>
                </div>
                <a class="order-buyer-shortcut" href="#order-workspace-items">Stavke porudžbine →</a>
            </div>
            @php
                $buyerPhoneRaw = trim((string) ($order['shipping_phone'] ?? ''));
                $buyerPhoneDial = preg_replace('/[^0-9+]/', '', $buyerPhoneRaw) ?? '';
                $buyerHasCallablePhone = preg_match('/^\+?[0-9]{6,15}$/D', $buyerPhoneDial) === 1;
            @endphp
            <dl class="detail-list order-buyer-detail-list">
                <dt>Ime i prezime primaoca</dt>
                <dd class="order-buyer-name">{{ $order['shipping_full_name'] ?? '—' }}</dd>
                <dt>Telefon</dt>
                <dd>
                    @if($buyerHasCallablePhone)
                        <a class="order-buyer-call" href="tel:{{ $buyerPhoneDial }}" aria-label="Pozovi primaoca porudžbine">{{ $buyerPhoneRaw }}</a>
                    @else
                        {{ $buyerPhoneRaw !== '' ? $buyerPhoneRaw : '—' }}
                    @endif
                </dd>
                <dt>Adresa za isporuku</dt>
                <dd class="order-buyer-address">
                    {{ $order['shipping_address'] ?? '—' }},
                    {{ $order['shipping_postal_code'] ?? '' }} {{ $order['shipping_city'] ?? '' }}
                </dd>
                <dt>Napomena krajnjeg kupca</dt>
                <dd class="order-buyer-note">{{ trim((string) ($order['customer_note'] ?? '')) ?: '—' }}</dd>
            </dl>
        </section>

    <section class="panel order-workspace-command" aria-labelledby="order-workspace-next-title">
        <div class="order-workspace-command-main">
            <div class="order-workspace-command-heading">
                <div>
                    <span class="eyebrow">Operativni pregled</span>
                    <h2>Šta je sada najvažnije</h2>
                </div>
                <span class="status-badge status-{{ $order['status_class'] ?? 'archived' }}">
                    {{ $order['status_label'] ?? $workspaceStatus }}
                </span>
            </div>

            <div class="order-workspace-snapshot">
                <div>
                    <span>Status</span>
                    <strong>{{ $order['status_label'] ?? $workspaceStatus }}</strong>
                </div>
                <div>
                    <span>Plaćanje</span>
                    <strong>{{ $order['payment_state_label'] ?? ($order['payment_status'] ?? '—') }}</strong>
                </div>
                <div>
                    <span>Kupac</span>
                    <strong>{{ $order['shipping_full_name'] ?? '—' }}</strong>
                </div>
                <div>
                    <span>Ukupno</span>
                    <strong>{{ $order['subtotal_rsd_display'] ?? '—' }}</strong>
                    <small>Preostalo {{ $order['remaining_rsd_display'] ?? '—' }}</small>
                </div>
            </div>

            <nav class="order-workspace-nav" aria-label="Brza navigacija kroz porudžbinu">
                <a href="#order-workspace-items">Stavke</a>
                @if($workspaceShipmentTarget)<a href="{{ $workspaceShipmentTarget }}">Slanje</a>@endif
                @if($workspaceDeliveryTarget)<a href="{{ $workspaceDeliveryTarget }}">Isporuka</a>@endif
                <a href="#order-workspace-payments">Uplate</a>
                <a href="#order-workspace-documents">Dokumenti</a>
                <a href="#order-workspace-timeline">Timeline</a>
                <a href="#order-workspace-customer">Kupac i dostava</a>
            </nav>
        </div>

        <aside class="order-workspace-next" aria-live="polite">
            <span class="eyebrow">Sledeći korak</span>
            <h2 id="order-workspace-next-title">{{ $workspaceNextTitle }}</h2>
            <p>{{ $workspaceNextText }}</p>
            <a class="button button-primary order-workspace-primary" href="{{ $workspaceNextTarget }}">
                {{ $workspaceNextLabel }}
            </a>
        </aside>
    </section>
    </div>

    @if(!($order['is_completed'] ?? false) && (($actions['shipment'] ?? false) || ($actions['complete'] ?? false) || is_array($shipment)))
        <section class="panel order-workspace-anchor" id="order-workspace-logistics-actions" data-logistics-actions>
            <div class="section-heading-row">
                <div>
                    <span class="eyebrow">Logistika</span>
                    <h2>Slanje i isporuka</h2>
                    <p class="muted">Izaberi poslovni događaj koji stvarno potvrđuješ. Slanje i konačna isporuka ostaju odvojeni audit događaji.</p>
                </div>
            </div>
            <div class="header-button-row">
                @if($actions['shipment'] ?? false)
                    <button class="button button-primary button-large" type="button" data-logistics-action="shipment" aria-controls="order-workspace-shipment-entry" aria-expanded="{{ $logisticsOpenAction === 'shipment' ? 'true' : 'false' }}">
                        <x-icon name="truck" /> Potvrdi da je pošiljka poslata
                    </button>
                @elseif(is_array($shipment))
                    <button class="button button-ghost button-large" type="button" disabled aria-disabled="true">
                        <x-icon name="check-circle" /> Pošiljka je poslata
                    </button>
                @endif

                @if($actions['complete'] ?? false)
                    <button class="button button-success button-large" type="button" data-logistics-action="delivery" aria-controls="delivery-completion" aria-expanded="{{ $logisticsOpenAction === 'delivery' ? 'true' : 'false' }}">
                        <x-icon name="check-circle" /> {{ $deliveryActionLabel }}
                    </button>
                @endif
            </div>
            @if($actions['complete'] ?? false)
                <p class="muted">
                    @if($isCashOnDelivery)
                        Potvrda konačne isporuke automatski evidentira preostali iznos kao naplaćen pouzećem i kompletira porudžbinu.
                    @else
                        Potvrda konačne isporuke kompletira porudžbinu; za načine plaćanja koji nisu pouzeće saldo mora prethodno biti potpuno izmiren.
                    @endif
                </p>
            @endif
        </section>
    @endif

    @if(is_array($shipment))
        <section class="panel shipment-record-card order-workspace-anchor" data-shipment-card id="order-workspace-shipment-record">
            <div class="section-heading-row">
                <div><span class="eyebrow">Logistika</span><h2>Evidencija slanja pošiljke</h2><p class="muted">Pošiljka je evidentirana kao poslata. Ovo nije potvrda stvarne isporuke niti naplate pouzećem.</p></div>
            </div>
            <dl class="detail-list shipment-detail-list">
                <dt>Način isporuke</dt><dd>{{ $shipment['shipment_method_label'] ?? '—' }}</dd>
                <dt>Kurirska služba</dt><dd>{{ $shipment['courier_name'] ?? '—' }}</dd>
                <dt>Datum i vreme slanja</dt><dd>{{ $shipment['shipped_at'] ?? '—' }}</dd>
                <dt>Primalac</dt><dd>{{ $shipment['recipient_name'] ?? '—' }}</dd>
                <dt>Telefon primaoca</dt><dd>{{ $shipment['recipient_phone'] ?? '—' }}</dd>
                <dt>Broj za praćenje pošiljke</dt><dd>{{ $shipment['tracking_number'] ?? '—' }}</dd>
                <dt>Evidentirao</dt><dd>{{ $shipment['recorded_by'] ?? 'Administrator' }}</dd>
                <dt>Napomena o slanju</dt><dd>{{ $shipment['note'] ?? '—' }}</dd>
            </dl>
            <div class="header-button-row shipment-links">
                @if(!empty($shipment['tracking_url']) && ($shipment['tracking_number'] ?? '—') !== '—')<a class="button button-ghost" target="_blank" rel="noopener" href="{{ $shipment['tracking_url'] }}">Prati pošiljku</a>@endif
                @if(($shipment['has_proof'] ?? false) && !empty($urls['shipment_proof']))<a class="button button-ghost" target="_blank" rel="noopener" href="{{ $urls['shipment_proof'] }}">Otvori privatni dokaz slanja</a>@endif
            </div>
        </section>
    @elseif(($actions['shipment'] ?? false) && !empty($urls['shipment_store']))
        <section class="panel shipment-entry-card order-workspace-anchor" data-shipment-card data-logistics-panel="shipment" id="order-workspace-shipment-entry" @if($logisticsOpenAction !== 'shipment') hidden @endif>
            <div class="section-heading-row">
                <div><span class="eyebrow">Logistika</span><h2>Evidencija slanja pošiljke</h2><p class="muted">Evidentira samo da je pošiljka poslata. Ne označava porudžbinu kao dostavljenu/completed i ne evidentira naplatu pouzećem.</p></div>
            </div>
            <form method="post" action="{{ $urls['shipment_store'] }}" enctype="multipart/form-data" class="shipment-form" data-shipment-form>
                @csrf
                <input type="hidden" name="order_version_token" value="{{ $order['order_version_token'] ?? '' }}">
                <div class="shipment-grid">
                    <label><span>Način isporuke</span><select name="shipment_method" data-shipment-method required><option value="courier" selected>Kurirska služba</option><option value="own_transport">Sopstveni prevoz</option><option value="other">Drugo</option></select></label>
                    <label data-courier-field><span>Kurirska služba</span><select name="courier_service_id" data-courier-select required>@foreach($couriers as $courier)<option value="{{ $courier['id'] }}" data-tracking-url="{{ $courier['tracking_url'] }}" @selected($courier['is_default'] ?? false)>{{ $courier['name'] }}</option>@endforeach</select><a class="shipment-tracking-link" data-courier-tracking-link href="#" target="_blank" rel="noopener">Zvanična stranica za praćenje</a></label>
                    <label data-tracking-field><span>Broj za praćenje pošiljke</span><input name="tracking_number" maxlength="120" value="{{ $order['tracking_number'] ?? '' }}" data-tracking-input required></label>
                    <label><span>Datum i vreme slanja</span><input type="datetime-local" name="shipped_at" value="{{ $formDefaults['shipped_at'] ?? '' }}" required></label>
                    <label><span>Ime primaoca</span><input name="recipient_name" maxlength="190" value="{{ $formDefaults['recipient_name'] ?? ($order['shipping_full_name'] ?? '') }}" required></label>
                    <label><span>Telefon primaoca</span><input name="recipient_phone" maxlength="60" value="{{ $formDefaults['recipient_phone'] ?? ($order['shipping_phone'] ?? '') }}"><small>Automatski preuzeto iz porudžbine; menjaj samo ako je potrebno.</small></label>
                    <label><span>Dokaz slanja (opciono, do 10 MB)</span><input type="file" name="shipment_proof" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"></label>
                    <label class="shipment-wide"><span>Napomena o slanju</span><textarea name="note" rows="3" maxlength="3000"></textarea></label>
                </div>
                <button class="button button-success button-large" type="submit" data-confirm="Potvrditi da je pošiljka poslata? Ova akcija ne potvrđuje isporuku niti naplatu."><x-icon name="truck" /> Potvrdi da je pošiljka poslata</button>
            </form>
        </section>
    @endif

    @if($order['is_completed'] ?? false)
        <section id="order-workspace-completed" class="panel order-completion-card is-completed order-workspace-anchor">
            <div class="completion-icon"><x-icon name="check-circle" /></div>
            <div>
                <span class="eyebrow">Konačno stanje</span>
                <h2>{{ ($order['is_direct_sale'] ?? false) ? 'Direktna prodaja je evidentirana' : 'Porudžbina i isporuka su kompletirane' }}</h2>
                <p>
                    {{ ($order['is_direct_sale'] ?? false) ? 'Evidentirano' : 'Završeno' }} {{ $order['completed_at'] ?? '—' }} ·
                    {{ $order['completed_by'] ?? 'Administrator' }} ·
                    {{ $order['payment_state_label'] ?? 'Plaćeno' }}
                </p>
                @if(is_array($delivery))
                    <div class="delivery-inline-summary">
                        <strong>{{ $delivery['delivery_method_label'] ?? 'Isporuka' }}</strong>
                        <span>{{ $delivery['delivered_at'] ?? '—' }} · primalac {{ $delivery['recipient_name'] ?? 'Kupac' }}</span>
                        @if($delivery['has_proof'] ?? false)
                            <a class="button button-ghost button-small" target="_blank" rel="noopener" href="{{ $urls['delivery_proof'] ?? '#' }}">Dokaz isporuke</a>
                        @endif
                    </div>
                @endif
                @if(!empty($order['completion_note']))
                    <small>{{ $order['completion_note'] }}</small>
                @endif

                @if(($actions['reopen'] ?? false) && !empty($urls['reopen']))
                    <details class="reopen-order-panel">
                        <summary>Ponovo otvori porudžbinu</summary>
                        <form method="post" action="{{ $urls['reopen'] }}">
                            @csrf
                            <label>
                                <span>Obavezan razlog ponovnog otvaranja</span>
                                <textarea name="reason" rows="3" maxlength="1000" required placeholder="Npr. potrebno je ispraviti uplatu ili podatke isporuke"></textarea>
                            </label>
                            <button class="button button-danger" type="submit" data-confirm="Ponovo otvoriti kompletiranu porudžbinu? Plaćanje i dokaz isporuke ostaju sačuvani.">
                                Ponovo otvori za korekciju
                            </button>
                        </form>
                    </details>
                @endif
            </div>
        </section>
    @elseif(($actions['complete'] ?? false) && !empty($urls['complete']))
        <section id="delivery-completion" class="panel order-completion-card" data-logistics-panel="delivery" @if($logisticsOpenAction !== 'delivery') hidden @endif>
            <div class="completion-icon"><x-icon name="check-circle" /></div>
            <div class="completion-content">
                <span class="eyebrow">Završetak isporuke</span>
                <h2>Potvrda stvarne isporuke</h2>
                @if(($order['payment_method'] ?? '') === 'cash_on_delivery')
                    <p>Preostali iznos od <strong>{{ $order['remaining_rsd_display'] ?? '0,00 RSD' }}</strong> biće evidentiran kao naplaćen pouzećem. Podaci isporuke ostaju trajno zabeleženi.</p>
                @else
                    <p>Saldo mora biti potpuno izmiren. Unesi podatke o stvarnoj isporuci i opciono priloži fotografiju, potpis ili PDF potvrdu.</p>
                @endif
                @if(!empty($order['reopened_at']))
                    <div class="alert alert-warning compact-alert">
                        Porudžbina je ponovo otvorena {{ $order['reopened_at'] }}. Prethodni dokaz isporuke ostaje sačuvan dok ne priložiš novi.
                    </div>
                @endif
                <form class="completion-form delivery-completion-form" method="post" action="{{ $urls['complete'] }}" enctype="multipart/form-data">
                    @csrf
                    <label>
                        <span>Način isporuke</span>
                        <select name="delivery_method" required>
                            @foreach([
                                'own_transport' => 'Sopstveni prevoz',
                                'courier' => 'Kurirska služba',
                                'customer_pickup' => 'Lično preuzimanje',
                                'other' => 'Drugo',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(($delivery['delivery_method'] ?? 'own_transport') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Datum i vreme isporuke</span>
                        <input type="datetime-local" name="delivered_at" value="{{ $delivery['delivered_at_input'] ?? ($formDefaults['delivered_at'] ?? '') }}" required>
                    </label>
                    <label>
                        <span>Ime primaoca</span>
                        <input name="recipient_name" maxlength="190" value="{{ $delivery['recipient_name'] ?? ($formDefaults['recipient_name'] ?? '') }}" required>
                    </label>
                    <label>
                        <span>Telefon primaoca</span>
                        <input name="recipient_phone" maxlength="60" value="{{ ($delivery['recipient_phone'] ?? '') !== '—' ? ($delivery['recipient_phone'] ?? '') : ($formDefaults['recipient_phone'] ?? '') }}">
                    </label>
                    <label>
                        <span>Interna referenca / vozilo (opciono; nije tracking)</span>
                        <input name="delivery_reference" maxlength="190" value="{{ ($delivery['reference'] ?? '') !== '—' ? ($delivery['reference'] ?? '') : '' }}">
                    </label>
                    <label>
                        <span>Dokaz isporuke (opciono, do 10 MB)</span>
                        <input type="file" name="delivery_proof" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                        @if(is_array($delivery) && ($delivery['has_proof'] ?? false))
                            <small>Postojeći dokaz: {{ $delivery['proof_original_name'] ?? 'Dokaz isporuke' }}. Novi fajl će ga zameniti.</small>
                        @endif
                    </label>
                    <label class="delivery-wide-field">
                        <span>Napomena o isporuci</span>
                        <textarea name="delivery_note" rows="3" maxlength="3000" placeholder="Stanje robe, posebne okolnosti, ko je preuzeo...">{{ ($delivery['note'] ?? '') !== '—' ? ($delivery['note'] ?? '') : '' }}</textarea>
                    </label>
                    <label class="delivery-wide-field">
                        <span>Završna interna napomena</span>
                        <input name="completion_note" maxlength="1000" placeholder="Npr. roba isporučena kupcu i naplaćena pouzećem">
                    </label>
                    <button class="button button-success button-large delivery-complete-button" type="submit" data-confirm="{{ $isCashOnDelivery ? 'Potvrditi da je pošiljka isporučena i da je otkup naplaćen? Ova akcija kompletira porudžbinu.' : 'Potvrditi da je pošiljka isporučena? Ova akcija kompletira porudžbinu.' }}">
                        <x-icon name="check-circle" /> {{ $deliveryActionLabel }}
                    </button>
                </form>
            </div>
        </section>
    @endif

    <div class="settings-grid order-detail-grid operational-order-grid">
        <section class="order-main-column">
            <section class="panel form-section order-workspace-anchor" id="order-workspace-items">
                <div class="section-heading-row">
                    <div>
                        <h2>Stavke porudžbine</h2>
                        <p class="muted">Snapshot cena, količina i provizije u trenutku poručivanja.</p>
                    </div>
                </div>

                <div class="admin-table-wrap flat-table">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Artikal</th>
                                <th>SKU</th>
                                <th>Količina</th>
                                <th>Jedinična cena</th>
                                <th>Ukupno</th>
                                <th>Provizija</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>{{ $item['name'] ?? 'Nepoznat artikal' }}</td>
                                    <td>{{ $item['sku'] ?? '—' }}</td>
                                    <td>{{ $item['quantity'] ?? 0 }}</td>
                                    <td>{{ ($order['is_direct_sale'] ?? false) ? ($item['unit_price_original_display'] ?? ($item['unit_price'] ?? '0,00 RSD')) : ($item['unit_price'] ?? '0,00 RSD') }}</td>
                                    <td>{{ $item['line_total'] ?? '0,00 RSD' }}</td>
                                    <td>{{ ($order['is_direct_sale'] ?? false) ? 'Nema provizije' : ($item['commission'] ?? '0,00 EUR') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">Stavke porudžbine trenutno nisu dostupne.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="order-total">
                    <span>Ukupno</span>
                    <strong>{{ $order['subtotal_rsd_display'] ?? '0,00 RSD' }}</strong>
                </div>

                @if(($actions['sale_price_correction'] ?? false) && !empty($urls['sale_price_correction']))
                    <div class="panel" data-direct-sale-price-correction>
                        <div class="section-heading-row">
                            <div>
                                <h3>Korekcija prodajne cene</h3>
                                <p class="muted">Samo za Direct Sale. Izmena sinhronizuje stavku, subtotal i originalnu verifikovanu uplatu i ostavlja audit trag.</p>
                            </div>
                        </div>
                        <form method="post" action="{{ $urls['sale_price_correction'] }}" class="settings-form">
                            @csrf
                            @method('PATCH')
                            <label>
                                <span>Valuta stvarne prodajne cene</span>
                                <select name="new_unit_price_currency" required>
                                    <option value="RSD" @selected(($items[0]['original_currency'] ?? 'RSD') === 'RSD')>RSD</option>
                                    <option value="EUR" @selected(($items[0]['original_currency'] ?? 'RSD') === 'EUR')>EUR</option>
                                </select>
                            </label>
                            <label>
                                <span>Nova jedinicna prodajna cena</span>
                                <input type="number" name="new_unit_price_amount" step="0.01" min="0.01" max="999999999.99" required value="{{ isset($items[0]['unit_price_original']) ? number_format((float) $items[0]['unit_price_original'], 2, '.', '') : '' }}">
                            </label>
                            <label>
                                <span>Razlog korekcije</span>
                                <textarea name="reason" rows="3" minlength="3" maxlength="1000" required placeholder="Npr. greska pri rucnom unosu prodajne cene"></textarea>
                            </label>
                            <button class="button button-primary" type="submit" data-confirm="Potvrdi korekciju prodajne cene? Izmena ce uskladiti stavku, subtotal i originalnu verifikovanu uplatu.">
                                Koriguj prodajnu cenu
                            </button>
                        </form>
                    </div>
                @endif

                @if(is_array($commission))
                    <div class="commission-inline-summary">
                        <span>
                            <x-icon name="wallet" />
                            <strong>Provizija {{ $commission['total_eur_display'] ?? '0,00 EUR' }}</strong>
                        </span>
                        <span class="status-badge commission-status-{{ $commission['status'] ?? 'pending' }}">
                            {{ $commission['status'] ?? 'pending' }}
                        </span>
                        @if(!empty($urls['commission']))
                            <a href="{{ $urls['commission'] }}">Otvori proviziju</a>
                        @endif
                    </div>
                @endif
            </section>

            <div id="order-workspace-payments" class="order-workspace-anchor">
                @include('admin.orders.partials.payments', ['detail' => $detail])
            </div>

            <section class="panel form-section document-workbench order-workspace-anchor" id="order-workspace-documents">
                <div class="section-heading-row">
                    <div>
                        <h2>Poslovni dokumenti</h2>
                        <p class="muted">Potvrda, predračun i račun sa zamrznutim poslovnim podacima.</p>
                    </div>
                </div>

                @if(($permissions['manage_invoices'] ?? false) && !empty($urls['document_store']))
                    <div class="document-actions">
                        @if($activeDocumentsByType->has('proforma'))
                            <a class="button button-ghost" target="_blank" rel="noopener" href="{{ $activeDocumentsByType->get('proforma')['show_url'] }}">
                                <x-icon name="file-text" /> Otvori aktivni predračun
                            </a>
                        @else
                            <form method="post" target="_blank" action="{{ $urls['document_store'] }}">
                                @csrf
                                <input type="hidden" name="document_type" value="proforma">
                                <button class="button button-ghost" type="submit">
                                    <x-icon name="file-text" /> {{ $documentHistoryTypes->contains('proforma') ? 'Izdaj novi predračun' : 'Izdaj predračun' }}
                                </button>
                            </form>
                        @endif

                        @if($activeDocumentsByType->has('invoice'))
                            <a class="button button-primary" target="_blank" rel="noopener" href="{{ $activeDocumentsByType->get('invoice')['show_url'] }}">
                                <x-icon name="receipt" /> Otvori aktivni račun
                            </a>
                        @else
                            <form method="post" target="_blank" action="{{ $urls['document_store'] }}">
                                @csrf
                                <input type="hidden" name="document_type" value="invoice">
                                <button class="button button-primary" type="submit">
                                    <x-icon name="receipt" /> {{ $documentHistoryTypes->contains('invoice') ? 'Izdaj novi račun' : 'Izdaj račun' }}
                                </button>
                            </form>
                        @endif

                        @if(is_array($delivery))
                            @if($activeDocumentsByType->has('delivery_note'))
                                <a class="button button-ghost" target="_blank" rel="noopener" href="{{ $activeDocumentsByType->get('delivery_note')['show_url'] }}">
                                    <x-icon name="truck" /> Otvori aktivnu otpremnicu
                                </a>
                            @else
                                <form method="post" target="_blank" action="{{ $urls['document_store'] }}">
                                    @csrf
                                    <input type="hidden" name="document_type" value="delivery_note">
                                    <button class="button button-ghost" type="submit">
                                        <x-icon name="truck" /> {{ $documentHistoryTypes->contains('delivery_note') ? 'Izdaj novu otpremnicu' : 'Izdaj otpremnicu' }}
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                @endif

                <div class="document-list">
                    @forelse($documents as $document)
                        <article class="document-row">
                            <div class="document-row-main">
                                <strong>{{ $document['number'] ?? 'Dokument' }}</strong>
                                <span>
                                    {{ $documentLabels[$document['type'] ?? ''] ?? ($document['type'] ?? 'Dokument') }} ·
                                    revizija {{ $document['revision_number'] ?? 1 }} ·
                                    {{ $document['issued_at'] ?? '—' }}
                                </span>
                                @if(!empty($document['supersedes_number']))
                                    <small>Menja dokument {{ $document['supersedes_number'] }}</small>
                                @endif
                                @if(($document['status'] ?? '') === 'cancelled' && !empty($document['cancellation_reason']))
                                    <small class="text-danger">Razlog storniranja: {{ $document['cancellation_reason'] }}</small>
                                @endif
                            </div>
                            <span class="status-badge status-{{ $document['status_class'] ?? 'archived' }}">
                                {{ ($document['status'] ?? '') === 'cancelled' ? 'Storniran' : 'Aktivan' }}
                            </span>
                            @if(!empty($document['show_url']))
                                <a class="button button-ghost button-small" target="_blank" rel="noopener" href="{{ $document['show_url'] }}">PDF</a>
                            @endif
                            @if(($document['can_cancel'] ?? false) && !empty($document['cancel_url']))
                                <details class="document-cancel-details">
                                    <summary class="button button-danger button-small">Storniraj</summary>
                                    <form method="post" action="{{ $document['cancel_url'] }}" class="document-cancel-form">
                                        @csrf
                                        <label>
                                            <span>Razlog storniranja</span>
                                            <textarea name="cancellation_reason" minlength="5" maxlength="1000" required placeholder="Npr. pogrešni podaci, iznos ili kupac"></textarea>
                                        </label>
                                        <button class="button button-danger button-small" type="submit" data-confirm="Stornirati dokument? Nakon toga možeš izdati novu reviziju.">Potvrdi storniranje</button>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @empty
                        <p class="muted">Još nema izdatih dokumenata.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel form-section order-workspace-anchor" id="order-workspace-timeline">
                <div class="section-heading-row">
                    <div>
                        <h2>Timeline porudžbine</h2>
                        <p class="muted">Statusi, dodele, interne napomene, uplate i dokumenti.</p>
                    </div>
                </div>

                <div class="operation-timeline">
                    @forelse($timeline as $event)
                        <article class="timeline-event timeline-{{ $event['type'] ?? 'event' }}">
                            <span class="timeline-dot"></span>
                            <div>
                                <div class="timeline-event-head">
                                    <strong>{{ $event['title'] ?? 'Događaj' }}</strong>
                                    <time>{{ $event['created_at'] ?? '—' }}</time>
                                </div>
                                @if(!empty($event['description']))
                                    <p>{{ $event['description'] }}</p>
                                @endif
                                <small>
                                    {{ $event['actor'] ?? 'Sistem' }}
                                    @if(($event['visibility'] ?? 'public') === 'internal')
                                        · interna beleška
                                    @endif
                                </small>
                            </div>
                        </article>
                    @empty
                        <p class="muted">Još nema događaja za prikaz.</p>
                    @endforelse
                </div>
            </section>
        </section>

        <aside class="form-side">
            <section class="panel form-section">
                <h2>{{ ($order['is_direct_sale'] ?? false) ? 'Kupac i direktna prodaja' : 'Kupac i odgovorno lice' }}</h2>
                <dl class="detail-list">
                    <dt>Korisnik</dt>
                    <dd>{{ $order['user_name'] ?? 'Nepoznat korisnik' }}
                        @if(!empty($order['user_username']) && $order['user_username'] !== '—')
                            ({{ $order['user_username'] }})
                        @endif
                    </dd>
                    @if($order['is_direct_sale'] ?? false)
                        <dt>Prodajni kanal</dt><dd>Direktna prodaja</dd>
                        <dt>Evidentirao</dt><dd>{{ $order['completed_by'] ?? 'SuperAdministrator' }}</dd>
                        <dt>Vreme prodaje</dt><dd>{{ $order['completed_at'] ?? '—' }}</dd>
                    @else
                        <dt>Odgovorno lice</dt><dd>{{ $order['supplier_name'] ?? '—' }}</dd>
                        <dt>Uloga</dt><dd>{{ $order['supplier_role'] ?? '—' }}</dd>
                        <dt>E-mail</dt><dd>{{ $order['supplier_email'] ?? '—' }}</dd>
                        <dt>Telefon</dt><dd>{{ $order['supplier_phone'] ?? '—' }}</dd>
                        <dt>Preuzeto</dt><dd>{{ $order['accepted_at'] ?? '—' }}</dd>
                    @endif
                </dl>
            </section>

            @if(($actions['reassign'] ?? false) && !empty($urls['reassign']))
                <details class="order-workspace-advanced" data-order-workspace-secondary>
    <summary><span>Promeni odgovorno lice</span><small>Napredna administrativna akcija</small></summary>
    <div class="order-workspace-advanced-body">
<section class="panel form-section order-workspace-secondary-panel" id="order-workspace-reassign">
                    <h2>Ponovna dodela</h2>
                    <form method="post" action="{{ $urls['reassign'] }}">
                        @csrf
                        @method('PATCH')
                        <label>
                            <span>Novo odgovorno lice</span>
                            <select name="supplier_user_id" required>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier['id'] }}" @if($supplier['selected'] ?? false) disabled @endif>
                                        {{ $supplier['name'] }} · {{ $supplier['role'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Razlog dodele</span>
                            <textarea name="reason" rows="3" maxlength="1000" required></textarea>
                        </label>
                        <button class="button button-primary" type="submit" data-confirm="Dodeliti porudžbinu drugom Administratoru?">Promeni odgovorno lice</button>
                    </form>
                </section>
    </div>
</details>
            @endif

            @if(!($order['is_completed'] ?? false) && ($permissions['internal_notes'] ?? false) && !empty($urls['internal_note']))
                <section class="panel form-section order-workspace-anchor" id="order-workspace-note">
                    <h2>Interna napomena</h2>
                    <form method="post" action="{{ $urls['internal_note'] }}">
                        @csrf
                        <label>
                            <span>Napomena koju korisnik ne vidi</span>
                            <textarea name="note" rows="4" maxlength="5000" required></textarea>
                        </label>
                        <button class="button button-ghost" type="submit">Sačuvaj internu napomenu</button>
                    </form>
                </section>
            @endif

            @if(!($order['is_completed'] ?? false) && !empty($urls['deadlines']))
                <details class="order-workspace-advanced" data-order-workspace-secondary>
    <summary><span>Uredi operativne rokove</span><small>Otvori samo kada rok obrade ili slanja treba korigovati</small></summary>
    <div class="order-workspace-advanced-body">
<section class="panel form-section order-workspace-secondary-panel" id="order-workspace-deadlines">
                    <h2>Operativni rokovi</h2>
                    <form method="post" action="{{ $urls['deadlines'] }}">
                        @csrf
                        @method('PATCH')
                        <label>
                            <span>Očekivan završetak obrade</span>
                            <input type="datetime-local" name="expected_processing_at" value="{{ $order['expected_processing_at'] ?? '' }}">
                        </label>
                        <label>
                            <span>Očekivano slanje</span>
                            <input type="datetime-local" name="expected_shipping_at" value="{{ $order['expected_shipping_at'] ?? '' }}">
                        </label>
                        <button class="button button-ghost" type="submit">Sačuvaj rokove</button>
                    </form>
                </section>
    </div>
</details>
            @endif



            @if(is_array($delivery))
                <section class="panel form-section delivery-record-card order-workspace-anchor" id="order-workspace-delivery-record">
                    <h2>Evidencija isporuke</h2>
                    <dl class="detail-list">
                        <dt>Način</dt><dd>{{ $delivery['delivery_method_label'] ?? '—' }}</dd>
                        <dt>Isporučeno</dt><dd>{{ $delivery['delivered_at'] ?? '—' }}</dd>
                        <dt>Primalac</dt><dd>{{ $delivery['recipient_name'] ?? '—' }}</dd>
                        <dt>Telefon</dt><dd>{{ $delivery['recipient_phone'] ?? '—' }}</dd>
                        <dt>Referenca</dt><dd>{{ $delivery['reference'] ?? '—' }}</dd>
                        <dt>Potvrdio</dt><dd>{{ $delivery['confirmed_by'] ?? '—' }}</dd>
                        <dt>Napomena</dt><dd>{{ $delivery['note'] ?? '—' }}</dd>
                    </dl>
                    @if(($delivery['has_proof'] ?? false) && !empty($urls['delivery_proof']))
                        <a class="button button-ghost" target="_blank" rel="noopener" href="{{ $urls['delivery_proof'] }}">Otvori privatni dokaz isporuke</a>
                    @endif
                </section>
            @endif


            @if(!($order['is_completed'] ?? false) && !empty($urls['status']))
                <details class="order-workspace-advanced" data-order-workspace-secondary>
    <summary><span>Ručna promena statusa</span><small>Koristi samo kada primarna poslovna akcija ne rešava sledeći korak</small></summary>
    <div class="order-workspace-advanced-body">
<section class="panel form-section order-workspace-secondary-panel" id="order-workspace-status">
                    <h2>Status porudžbine</h2>
                    <form method="post" action="{{ $urls['status'] }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="order_version_token" value="{{ $order['order_version_token'] ?? '' }}">
                        <label>
                            <span>Novi status</span>
                            <select name="status">
                                @if(($order['status'] ?? '') === 'shipped')<option value="shipped" selected disabled>Poslata (evidentirano slanje)</option>@endif
                                @foreach([
                                    'new' => 'Nova',
                                    'processing' => 'U obradi',
                                    'confirmed' => 'Potvrđena',
                                    'cancelled' => 'Otkazana',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @if(($order['status'] ?? '') === $value) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Napomena korisniku</span>
                            <textarea name="note" rows="3" maxlength="1000"></textarea>
                        </label>
                        <button class="button button-primary" type="submit" data-confirm="Potvrditi promenu statusa?">Promeni status</button>
                    </form>
                </section>
    </div>
</details>
            @endif
        </aside>
    </div>
</div>
@if(($permissions['archive'] ?? false) && !empty($urls['archive']) && (($order['is_completed'] ?? false) || ($order['status'] ?? '') === 'cancelled'))
<details class="order-workspace-advanced" data-order-archive-workspace>
    <summary>
        <span>Arhiviraj porudžbinu</span>
        <small>Skloni završenu ili otkazanu porudžbinu iz operativnih prikaza bez brisanja poslovne istorije</small>
    </summary>
    <section class="panel form-section order-workspace-secondary-panel">
        <div class="section-heading-row">
            <div>
                <h2>Arhiviranje porudžbine</h2>
                <p class="muted">Arhiviranje je reverzibilno. Status, uplate, dokumenti, garancije, provizije i audit istorija ostaju nepromenjeni.</p>
            </div>
        </div>
        <form method="post" action="{{ $urls['archive'] }}" class="form-grid">
            @csrf
            <label class="full-span">
                <span>Razlog arhiviranja</span>
                <textarea name="archive_reason" rows="3" maxlength="1000" required placeholder="Npr. završena porudžbina preneta u arhivu.">{{ old('archive_reason') }}</textarea>
            </label>
            <div class="full-span">
                <button class="button button-warning" type="submit" data-confirm="Arhivirati porudžbinu {{ $order['order_number'] ?? '' }}? Možeš je kasnije vratiti iz odeljka Arhivirane porudžbine.">
                    <x-icon name="archive" /> Arhiviraj porudžbinu
                </button>
            </div>
        </form>
    </section>
</details>
@endif

<script>
(() => {
    const logisticsButtons = Array.from(document.querySelectorAll('[data-logistics-action]'));
    const logisticsPanels = Array.from(document.querySelectorAll('[data-logistics-panel]'));
    const setLogisticsPanel = (name) => {
        logisticsPanels.forEach((panel) => {
            panel.hidden = panel.dataset.logisticsPanel !== name;
        });
        logisticsButtons.forEach((button) => {
            button.setAttribute('aria-expanded', button.dataset.logisticsAction === name ? 'true' : 'false');
        });
        const active = logisticsPanels.find((panel) => panel.dataset.logisticsPanel === name);
        active?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    logisticsButtons.forEach((button) => {
        button.addEventListener('click', () => setLogisticsPanel(button.dataset.logisticsAction || ''));
    });

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

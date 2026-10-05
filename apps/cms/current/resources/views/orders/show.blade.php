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
    $documentLabels = [
        'confirmation' => 'Potvrda',
        'order_confirmation' => 'Potvrda',
        'delivery_note' => 'Otpremnica',
        'proforma' => 'Predračun',
        'invoice' => 'Račun',
        'credit_note' => 'Storno dokument',
    ];
@endphp

<div class="build16-order-detail-shell" data-order-user-detail-ready="1" data-build16-orders-detail="1">
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

    <a class="back-link" href="{{ $urls['back'] ?? '/orders' }}"><x-icon name="chevron-left" size="18" /> Moje porudžbine</a>

    <div class="page-heading build16-order-detail-heading">
        <div>
            <span class="eyebrow">Poručeno od {{ $order['supplier_name'] ?? 'Administratora' }}</span>
            <h1>{{ $order['order_number'] ?? 'Porudžbina' }}</h1>
            <p>{{ $order['created_at'] ?? '—' }} · lager {{ $order['inventory_state'] ?? '—' }}</p>
        </div>
        <div class="header-button-row">
            @if(($actions['amend'] ?? false) && !empty($urls['edit']))
                <a class="button button-primary" href="{{ $urls['edit'] }}"><x-icon name="sliders" /> Uredi porudžbinu</a>
            @endif
            @if(($actions['after_sales_create'] ?? false) && !empty($urls['after_sales_create']))
                <a class="button button-ghost" href="{{ $urls['after_sales_create'] }}">
                    <x-icon name="alert" /> Reklamacija / servis
                </a>
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

    <div class="settings-grid order-detail-grid operational-order-grid build16-order-detail-grid">
        <section class="order-main-column">
            <section class="panel form-section">
                @if($actions['amend'] ?? false)
                    <div class="alpha-note" data-order-amendment-available>
                        <strong>Porudžbinu još možeš izmeniti.</strong> Artikle, količine, adresu i napomenu možeš menjati sve dok pošiljka ne bude poslata.
                    </div>
                @elseif(in_array($order['status'] ?? '', ['shipped', 'cancelled'], true) || ($order['is_completed'] ?? false))
                    <div class="alpha-note" data-order-amendment-locked><strong>Porudžbina je zaključana.</strong> Izmene više nisu dostupne nakon slanja, završetka ili otkazivanja.</div>
                @endif
                <div class="section-heading-row">
                    <div>
                        <h2>Stavke porudžbine</h2>
                        <p class="muted">Cene i količine su sačuvane u trenutku poručivanja.</p>
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
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>{{ $item['name'] ?? 'Nepoznat artikal' }}</td>
                                    <td>{{ $item['sku'] ?? '—' }}</td>
                                    <td>{{ $item['quantity'] ?? 0 }}</td>
                                    <td>{{ $item['unit_price'] ?? '0,00 RSD' }}</td>
                                    <td>{{ $item['line_total'] ?? '0,00 RSD' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">Stavke porudžbine trenutno nisu dostupne.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="order-total">
                    <span>Ukupno</span>
                    <strong>{{ $order['subtotal_rsd_display'] ?? '0,00 RSD' }}</strong>
                </div>
            </section>

            @if(($permissions['view_documents'] ?? false))
                <section class="panel form-section document-workbench">
                    <div class="section-heading-row">
                        <div>
                            <h2>Dokumenti</h2>
                            <p class="muted">Potvrde, predračuni i računi za ovu porudžbinu.</p>
                        </div>
                    </div>
                    <div class="document-list">
                        @forelse($documents as $document)
                            <article class="document-row">
                                <div>
                                    <strong>{{ $document['number'] ?? 'Dokument' }}</strong>
                                    <span>
                                        {{ $documentLabels[$document['type'] ?? ''] ?? ($document['type'] ?? 'Dokument') }} ·
                                        revizija {{ $document['revision_number'] ?? 1 }} ·
                                        {{ $document['issued_at'] ?? '—' }}
                                    </span>
                                </div>
                                @if(!empty($document['show_url']))
                                    <a class="button button-ghost button-small" target="_blank" rel="noopener" href="{{ $document['show_url'] }}">
                                        <x-icon name="download" /> PDF
                                    </a>
                                @endif
                            </article>
                        @empty
                            <p class="muted">Još nema izdatih dokumenata.</p>
                        @endforelse
                    </div>
                </section>
            @endif

            @include('orders.partials.payments', ['detail' => $detail])

            @if(is_array($receivable))
                <section class="panel form-section receivable-public-card">
                    <div class="section-heading-row"><div><span class="eyebrow">Plan naplate</span><h2>{{ $receivable['case_number'] ?: 'Dogovor o plaćanju' }}</h2><p class="muted">Prikazane rate se zatvaraju samo na osnovu verifikovanih uplata.</p></div><span class="status-badge receivable-status-{{ $receivable['status'] ?? 'monitoring' }}">{{ ['monitoring'=>'Praćenje','contacted'=>'Kontaktirano','promised'=>'Obećana uplata','installment_plan'=>'Plan otplate','escalated'=>'Eskalirano','disputed'=>'Sporno','closed'=>'Zatvoreno'][$receivable['status'] ?? 'monitoring'] ?? ($receivable['status'] ?? '') }}</span></div>
                    @if(!empty($receivable['promised_payment_at']))<div class="alpha-note">Obećani datum uplate: <strong>{{ $receivable['promised_payment_at'] }}</strong></div>@endif
                    @if(!empty($receivable['installments']))<div class="table-wrap"><table><thead><tr><th>Rata</th><th>Dospeće</th><th>Iznos</th><th>Plaćeno</th><th>Status</th></tr></thead><tbody>@foreach($receivable['installments'] as $installment)<tr><td>#{{ $installment['sequence_no'] }}</td><td>{{ $installment['due_at'] }}</td><td>{{ $installment['amount_display'] }}</td><td>{{ $installment['paid_display'] }}</td><td>{{ ['pending'=>'Čeka','overdue'=>'Kasni','paid'=>'Plaćena'][$installment['status']] ?? $installment['status'] }}</td></tr>@endforeach</tbody></table></div>@endif
                    @if(!empty($receivable['contacts']))<div class="receivable-public-messages">@foreach($receivable['contacts'] as $contact)<article><strong>{{ $contact['subject'] }}</strong><small>{{ $contact['contacted_at'] }}</small><p>{{ $contact['note'] }}</p></article>@endforeach</div>@endif
                </section>
            @endif

            @if(is_array($commission))
                <section class="panel form-section commission-public-card">
                    <div class="section-heading-row">
                        <div>
                            <span class="eyebrow">Tvoja provizija</span>
                            <h2>{{ $commission['total_eur_display'] ?? '0,00 EUR' }}</h2>
                            <p>Podrazumevana provizija po komadu iznosi 10% vrednosti artikla.</p>
                        </div>
                        <span class="status-badge commission-status-{{ $commission['status'] ?? 'pending' }}">
                            {{ $commissionLabels[$commission['status'] ?? 'pending'] ?? ($commission['status'] ?? 'pending') }}
                        </span>
                    </div>
                    <dl class="detail-list compact-list">
                        <dt>Napomena</dt><dd>{{ $commission['status_note'] ?? '—' }}</dd>
                        @if(($commission['status'] ?? '') === 'paid')
                            <dt>Datum isplate</dt><dd>{{ $commission['paid_at'] ?? '—' }}</dd>
                            <dt>Referenca</dt><dd>{{ $commission['payment_reference'] ?? '—' }}</dd>
                        @endif
                    </dl>
                    @if(!empty($urls['commission']))
                        <a class="button button-ghost" href="{{ $urls['commission'] }}">Sve moje provizije</a>
                    @endif
                </section>
            @endif

            <section class="panel form-section">
                <h2>Praćenje porudžbine</h2>
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
                                <small>{{ $event['actor'] ?? 'Sistem' }}</small>
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
                <h2>Odgovorno lice</h2>
                <dl class="detail-list">
                    <dt>Ime</dt><dd>{{ $order['supplier_name'] ?? '—' }}</dd>
                    <dt>Uloga</dt><dd>{{ $order['supplier_role'] ?? '—' }}</dd>
                    <dt>E-mail</dt><dd>{{ $order['supplier_email'] ?? '—' }}</dd>
                    <dt>Telefon</dt><dd>{{ $order['supplier_phone'] ?? '—' }}</dd>
                    <dt>Preuzeto</dt><dd>{{ $order['accepted_at'] ?? '—' }}</dd>
                </dl>
            </section>

            <section class="panel form-section">
                <h2>Dostava</h2>
                <dl class="detail-list">
                    <dt>Kupac</dt><dd>{{ $order['shipping_full_name'] ?? '—' }}</dd>
                    <dt>Adresa</dt>
                    <dd>
                        {{ $order['shipping_address'] ?? '—' }},
                        {{ $order['shipping_postal_code'] ?? '' }} {{ $order['shipping_city'] ?? '' }}
                    </dd>
                    <dt>Telefon</dt><dd>{{ $order['shipping_phone'] ?? '—' }}</dd>
                    <dt>Tracking</dt><dd>{{ $order['tracking_number'] ?: '—' }}</dd>
                    <dt>Očekivano slanje</dt><dd>{{ $order['expected_shipping_display'] ?? '—' }}</dd>
                </dl>
            </section>


            @if(is_array($shipment))
                <section class="panel form-section shipment-record-card">
                    <h2>Evidencija slanja pošiljke</h2>
                    <dl class="detail-list">
                        <dt>Način isporuke</dt><dd>{{ $shipment['shipment_method_label'] ?? '—' }}</dd>
                        <dt>Kurirska služba</dt><dd>{{ $shipment['courier_name'] ?? '—' }}</dd>
                        <dt>Poslato</dt><dd>{{ $shipment['shipped_at'] ?? '—' }}</dd>
                        <dt>Broj za praćenje pošiljke</dt><dd>{{ $shipment['tracking_number'] ?? '—' }}</dd>
                        <dt>Primalac</dt><dd>{{ $shipment['recipient_name'] ?? '—' }}</dd>
                        <dt>Telefon</dt><dd>{{ $shipment['recipient_phone'] ?? '—' }}</dd>
                        <dt>Napomena o slanju</dt><dd>{{ $shipment['note'] ?? '—' }}</dd>
                    </dl>
                    <div class="header-button-row">
                        @if(!empty($shipment['tracking_url']) && ($shipment['tracking_number'] ?? '—') !== '—')<a class="button button-ghost" target="_blank" rel="noopener" href="{{ $shipment['tracking_url'] }}">Prati pošiljku</a>@endif
                        @if(($shipment['has_proof'] ?? false) && !empty($urls['shipment_proof']))<a class="button button-ghost" target="_blank" rel="noopener" href="{{ $urls['shipment_proof'] }}">Otvori dokaz slanja</a>@endif
                    </div>
                </section>
            @endif

            @if(is_array($delivery))
                <section class="panel form-section delivery-record-card">
                    <h2>Potvrđena isporuka</h2>
                    <dl class="detail-list">
                        <dt>Način</dt><dd>{{ $delivery['delivery_method_label'] ?? '—' }}</dd>
                        <dt>Datum</dt><dd>{{ $delivery['delivered_at'] ?? '—' }}</dd>
                        <dt>Primalac</dt><dd>{{ $delivery['recipient_name'] ?? '—' }}</dd>
                        <dt>Referenca</dt><dd>{{ $delivery['reference'] ?? '—' }}</dd>
                        <dt>Napomena</dt><dd>{{ $delivery['note'] ?? '—' }}</dd>
                    </dl>
                    @if(($delivery['has_proof'] ?? false) && !empty($urls['delivery_proof']))
                        <a class="button button-ghost" target="_blank" rel="noopener" href="{{ $urls['delivery_proof'] }}">Otvori dokaz isporuke</a>
                    @endif
                </section>
            @endif

            <section class="panel form-section">
                <h2>Plaćanje</h2>
                <dl class="detail-list">
                    <dt>Metod</dt><dd>{{ $order['payment_method'] ?? '—' }}</dd>
                    <dt>Status</dt><dd>{{ $order['payment_state_label'] ?? '—' }}</dd>
                    <dt>Račun</dt><dd>{{ $order['bank_account_display'] ?? '—' }}</dd>
                </dl>
            </section>

            @if(($actions['cancel'] ?? false) && !empty($urls['cancel']))
                <section class="panel danger-zone">
                    <h2>Otkazivanje</h2>
                    <p class="muted">Otkazivanje vraća rezervisani lager tačno jednom.</p>
                    <form method="post" action="{{ $urls['cancel'] }}">
                        @csrf
                        <label>
                            <span>Napomena</span>
                            <textarea name="note" rows="3" maxlength="1000"></textarea>
                        </label>
                        <button class="button button-danger" type="submit" data-confirm="Otkazati porudžbinu i vratiti lager?">Otkaži porudžbinu</button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection

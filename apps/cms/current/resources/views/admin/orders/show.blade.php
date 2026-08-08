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
    $formDefaults = $detail['form_defaults'] ?? [];
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

    <div class="page-heading">
        <div>
            <span class="eyebrow">Odgovorno lice: {{ $order['supplier_name'] ?? 'Nije dodeljeno' }}</span>
            <h1>{{ $order['order_number'] ?? 'Porudžbina' }}</h1>
            <p>
                {{ $order['shipping_full_name'] ?? '—' }} ·
                {{ $order['created_at'] ?? '—' }} ·
                lager {{ $order['inventory_state'] ?? '—' }}
            </p>
        </div>

        <div class="header-button-row">
            @if(($permissions['after_sales_manage'] ?? false) && !empty($urls['after_sales']))
                <a class="button button-ghost" href="{{ $urls['after_sales'] }}">
                    <x-icon name="alert" /> Reklamacije / servisi
                </a>
            @endif
            @if(($actions['accept'] ?? false) && !empty($urls['accept']))
                <form method="post" action="{{ $urls['accept'] }}">
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

    @if($order['is_completed'] ?? false)
        <section class="panel order-completion-card is-completed">
            <div class="completion-icon"><x-icon name="check-circle" /></div>
            <div>
                <span class="eyebrow">Konačno stanje</span>
                <h2>Porudžbina i isporuka su kompletirane</h2>
                <p>
                    Završeno {{ $order['completed_at'] ?? '—' }} ·
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
        <section id="delivery-completion" class="panel order-completion-card">
            <div class="completion-icon"><x-icon name="check-circle" /></div>
            <div class="completion-content">
                <span class="eyebrow">Završetak isporuke</span>
                <h2>Evidentiraj isporuku i kompletiraj porudžbinu</h2>
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
                        <span>Referenca / vozilo / tracking</span>
                        <input name="delivery_reference" maxlength="190" value="{{ ($delivery['reference'] ?? '') !== '—' ? ($delivery['reference'] ?? '') : ($order['tracking_number'] ?? '') }}">
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
                    <button class="button button-success button-large delivery-complete-button" type="submit" data-confirm="Potvrdi stvarnu isporuku, kompletiranje porudžbine i konačno evidentiranje plaćanja?">
                        <x-icon name="check-circle" /> Evidentiraj isporuku i kompletiraj
                    </button>
                </form>
            </div>
        </section>
    @endif

    <div class="settings-grid order-detail-grid operational-order-grid">
        <section class="order-main-column">
            <section class="panel form-section">
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
                                    <td>{{ $item['unit_price'] ?? '0,00 RSD' }}</td>
                                    <td>{{ $item['line_total'] ?? '0,00 RSD' }}</td>
                                    <td>{{ $item['commission'] ?? '0,00 EUR' }}</td>
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

            @include('admin.orders.partials.payments', ['detail' => $detail])

            <section class="panel form-section document-workbench">
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

            <section class="panel form-section">
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
                <h2>Kupac i odgovorno lice</h2>
                <dl class="detail-list">
                    <dt>Korisnik</dt>
                    <dd>{{ $order['user_name'] ?? 'Nepoznat korisnik' }}
                        @if(!empty($order['user_username']) && $order['user_username'] !== '—')
                            ({{ $order['user_username'] }})
                        @endif
                    </dd>
                    <dt>Odgovorno lice</dt><dd>{{ $order['supplier_name'] ?? '—' }}</dd>
                    <dt>Uloga</dt><dd>{{ $order['supplier_role'] ?? '—' }}</dd>
                    <dt>E-mail</dt><dd>{{ $order['supplier_email'] ?? '—' }}</dd>
                    <dt>Telefon</dt><dd>{{ $order['supplier_phone'] ?? '—' }}</dd>
                    <dt>Preuzeto</dt><dd>{{ $order['accepted_at'] ?? '—' }}</dd>
                </dl>
            </section>

            @if(($actions['reassign'] ?? false) && !empty($urls['reassign']))
                <section class="panel form-section">
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
            @endif

            @if(!($order['is_completed'] ?? false) && ($permissions['internal_notes'] ?? false) && !empty($urls['internal_note']))
                <section class="panel form-section">
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
                <section class="panel form-section">
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
            @endif

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
                    <dt>Napomena</dt><dd>{{ $order['customer_note'] ?? '—' }}</dd>
                </dl>
            </section>

            @if(is_array($delivery))
                <section class="panel form-section delivery-record-card">
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

            @if(!($order['is_completed'] ?? false) && !empty($urls['tracking']))
                <section class="panel form-section">
                    <h2>Tracking</h2>
                    <form method="post" action="{{ $urls['tracking'] }}">
                        @csrf
                        @method('PATCH')
                        <label>
                            <span>Tracking broj</span>
                            <input name="tracking_number" maxlength="120" value="{{ $order['tracking_number'] ?? '' }}">
                        </label>
                        <button class="button button-ghost" type="submit">Sačuvaj tracking</button>
                    </form>
                </section>
            @endif

            @if(!($order['is_completed'] ?? false) && !empty($urls['status']))
                <section class="panel form-section">
                    <h2>Status porudžbine</h2>
                    <form method="post" action="{{ $urls['status'] }}">
                        @csrf
                        @method('PATCH')
                        <label>
                            <span>Novi status</span>
                            <select name="status">
                                @foreach([
                                    'new' => 'Nova',
                                    'processing' => 'U obradi',
                                    'confirmed' => 'Potvrđena',
                                    'shipped' => 'Poslata',
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
            @endif
        </aside>
    </div>
</div>
@endsection

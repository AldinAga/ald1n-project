@extends('layouts.app')
@section('title', 'Porudžbine')
@section('content')
@php
    $statusLabels = [
        'new' => 'Nova',
        'processing' => 'Obrada',
        'confirmed' => 'Potvrđena',
        'shipped' => 'Poslata',
        'completed' => 'Isporučena',
        'cancelled' => 'Otkazana',
    ];
    $paymentLabels = [
        'pending' => 'Na čekanju',
        'paid' => 'Plaćeno',
        'cancelled' => 'Stornirano',
    ];
    $ordersUnavailable = $ordersUnavailable ?? null;
    $attentionCounts = $attentionCounts ?? ['unaccepted' => 0, 'overdue' => 0];
    $suppliers = $suppliers ?? collect();

    // Active filters as removable chips, derived from the same query parameters the filter form submits.
    $attentionLabels = ['unaccepted' => 'Čekaju preuzimanje', 'overdue' => 'Prekoračen rok'];
    $sourceLabels = ['laravel' => 'Laravel', 'legacy' => 'Legacy'];
    $chips = [];
    if ((string) request('q') !== '') {
        $chips[] = ['q', 'Pretraga', (string) request('q')];
    }
    if (isset($sourceLabels[(string) request('source_system')])) {
        $chips[] = ['source_system', 'Izvor', $sourceLabels[(string) request('source_system')]];
    }
    if ((string) request('status') !== '') {
        $chips[] = ['status', 'Status', $statusLabels[(string) request('status')] ?? (string) request('status')];
    }
    if ((string) request('payment_status') !== '') {
        $chips[] = ['payment_status', 'Plaćanje', $paymentLabels[(string) request('payment_status')] ?? (string) request('payment_status')];
    }
    if ($suppliers->isNotEmpty() && (int) request('supplier_user_id') > 0) {
        $chipSupplier = $suppliers->firstWhere('id', (int) request('supplier_user_id'));
        if ($chipSupplier) {
            $chips[] = ['supplier_user_id', 'Odgovorno lice', $chipSupplier->displayName()];
        }
    }
    if ((string) request('date_from') !== '') {
        $chips[] = ['date_from', 'Od datuma', (string) request('date_from')];
    }
    if ((string) request('date_to') !== '') {
        $chips[] = ['date_to', 'Do datuma', (string) request('date_to')];
    }
    if (isset($attentionLabels[(string) request('attention')])) {
        $chips[] = ['attention', 'Operativni filter', $attentionLabels[(string) request('attention')]];
    }
@endphp

<div class="a1-ops" data-orders-page-ready="1">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Operacije</span>
            <h1 class="a1-title">{{ auth()->user()?->hasRole('superadmin') ? 'Sve porudžbine' : 'Meni dodeljene porudžbine' }}</h1>
            <p class="a1-lead">Korisnik poručuje robu od Administratora ili SuperAdministratora, a odgovorno lice preuzima i obrađuje dodeljenu porudžbinu.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn a1-btn--sm" href="{{ route('admin.orders.archived') }}"><x-icon name="archive" :size="18" :shell="true" /> Arhivirane porudžbine</a>
            @can('reports.view')
                <a class="a1-btn a1-btn--sm" href="{{ route('admin.reports.index') }}"><x-icon name="chart" :size="18" :shell="true" /> Izveštaji</a>
            @endcan
            <span class="a1-ol-count">{{ $orders->total() }} porudžbina</span>
        </div>
    </header>

    @if(is_array($ordersUnavailable) && $ordersUnavailable !== [])
        <section class="a1-card a1-ol-recovery" aria-labelledby="ol-recovery-title">
            <span class="a1-eyebrow">Bezbedan recovery režim</span>
            <h2 class="a1-h2" id="ol-recovery-title">Lista porudžbina trenutno nije dostupna</h2>
            <div class="a1-alert a1-alert--warning" role="status">
                <x-icon name="alert" :size="20" :shell="true" />
                <p class="a1-text">Stranica nije oborena HTTP 500 greškom. Potrebno je završiti proveru operativne šeme ili otkloniti render problem.</p>
            </div>
            <ul class="a1-ol-issues">
                @foreach($ordersUnavailable as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
            <pre class="a1-ol-code" tabindex="0">php artisan app:orders-doctor --repair --render</pre>
        </section>
    @else
        <nav class="a1-ol-tiles" aria-label="Porudžbine koje traže pažnju">
            <a class="a1-ol-tile" href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['attention' => 'unaccepted'])) }}" @if(request('attention') === 'unaccepted') aria-current="true" @endif>
                <span class="a1-ol-tile__icon a1-ol-tile__icon--warning"><x-icon name="hourglass" :size="22" :shell="true" /></span>
                <span class="a1-ol-tile__body">
                    <span class="a1-ol-tile__count">{{ $attentionCounts['unaccepted'] }}</span>
                    <span class="a1-ol-tile__label">Čekaju preuzimanje</span>
                    @if(request('attention') === 'unaccepted')<span class="a1-ol-tile__state">Aktivan filter</span>@endif
                </span>
            </a>
            <a class="a1-ol-tile" href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['attention' => 'overdue'])) }}" @if(request('attention') === 'overdue') aria-current="true" @endif>
                <span class="a1-ol-tile__icon a1-ol-tile__icon--danger"><x-icon name="alert" :size="22" :shell="true" /></span>
                <span class="a1-ol-tile__body">
                    <span class="a1-ol-tile__count">{{ $attentionCounts['overdue'] }}</span>
                    <span class="a1-ol-tile__label">Prekoračen rok</span>
                    @if(request('attention') === 'overdue')<span class="a1-ol-tile__state">Aktivan filter</span>@endif
                </span>
            </a>
            @if(request('attention'))
                <a class="a1-btn a1-btn--quiet a1-btn--sm" href="{{ route('admin.orders.index', request()->except(['attention', 'page'])) }}">Ukloni operativni filter</a>
            @endif
        </nav>

        <form class="a1-filters a1-ol-filters" method="get" role="search" aria-label="Filtriranje porudžbina">
            <div class="a1-filters__fields a1-ol-fields">
                <div class="a1-field a1-ol-field-wide">
                    <label class="a1-label" for="ol-q">Pretraga</label>
                    <div class="a1-search">
                        <x-icon name="search" :size="20" :shell="true" />
                        <input class="a1-input" id="ol-q" type="search" name="q" value="{{ request('q') }}" placeholder="Broj porudžbine, korisnik ili kupac">
                    </div>
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="ol-source">Izvor</label>
                    <select class="a1-select" id="ol-source" name="source_system">
                        <option value="">Svi</option>
                        <option value="laravel" @selected(request('source_system') === 'laravel')>Laravel</option>
                        <option value="legacy" @selected(request('source_system') === 'legacy')>Legacy</option>
                    </select>
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="ol-status">Status</label>
                    <select class="a1-select" id="ol-status" name="status">
                        <option value="">Svi</option>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="ol-payment">Plaćanje</label>
                    <select class="a1-select" id="ol-payment" name="payment_status">
                        <option value="">Sva</option>
                        @foreach($paymentLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($suppliers->isNotEmpty())
                    <div class="a1-field">
                        <label class="a1-label" for="ol-supplier">Odgovorno lice</label>
                        <select class="a1-select" id="ol-supplier" name="supplier_user_id">
                            <option value="">Svi</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((int) request('supplier_user_id') === (int) $supplier->id)>{{ $supplier->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="a1-field">
                    <label class="a1-label" for="ol-from">Od datuma</label>
                    <input class="a1-input" id="ol-from" type="date" lang="sr-Latn" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="ol-to">Do datuma</label>
                    <input class="a1-input" id="ol-to" type="date" lang="sr-Latn" name="date_to" value="{{ request('date_to') }}">
                </div>
            </div>
            @if(request('attention'))
                <input type="hidden" name="attention" value="{{ request('attention') }}">
            @endif
            <div class="a1-filters__actions">
                <button class="a1-btn a1-btn--primary" type="submit">Filtriraj</button>
                <a class="a1-btn a1-btn--quiet" href="{{ route('admin.orders.index') }}">Reset</a>
            </div>
        </form>

        @if($chips !== [])
            <div class="a1-results">
                <p class="a1-results__count">Aktivni filteri:</p>
                <ul class="a1-chips">
                    @foreach($chips as [$chipParam, $chipName, $chipValue])
                        <li><a class="a1-chip" href="{{ route('admin.orders.index', request()->except([$chipParam, 'page'])) }}" aria-label="Ukloni filter: {{ $chipName }}: {{ $chipValue }}">{{ $chipName }}: {{ $chipValue }} <x-icon name="x" :size="16" :shell="true" /></a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($orders->isEmpty())
            <div class="a1-empty">
                <span class="a1-empty__icon"><x-icon name="orders" :size="28" :shell="true" /></span>
                <p>Nema porudžbina.</p>
                @if($chips !== [])
                    <a class="a1-btn a1-btn--sm" href="{{ route('admin.orders.index') }}">Reset</a>
                @endif
            </div>
        @else
            <div class="a1-tablewrap a1-tablewrap--sticky" data-a1-scroll-x role="region" aria-label="Lista porudžbina" tabindex="0">
                <table class="a1-table a1-table--cards a1-ol-table" role="table">
                    <caption class="a1-sr">Administratorski pregled porudžbina sa statusima, rokovima i dostupnim akcijama.</caption>
                    <thead role="rowgroup">
                        <tr role="row">
                            <th scope="col" role="columnheader">Broj</th>
                            <th scope="col" role="columnheader">Korisnik</th>
                            <th scope="col" role="columnheader">Odgovorno lice</th>
                            <th scope="col" role="columnheader">Status obrade</th>
                            <th scope="col" role="columnheader">Plaćanje</th>
                            <th scope="col" role="columnheader" class="a1-table__num">Iznos</th>
                            <th scope="col" role="columnheader">Rok</th>
                            <th scope="col" role="columnheader">Datum</th>
                            <th scope="col" role="columnheader"><span class="a1-sr">Brze akcije</span></th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        @foreach($orders as $order)
                            @php
                                $processingOverdue = $order->expected_processing_at
                                    && $order->expected_processing_at->isPast()
                                    && in_array($order->status, ['new', 'processing'], true);
                                $shippingOverdue = !$order->completed_at
                                    && $order->expected_shipping_at
                                    && $order->expected_shipping_at->isPast()
                                    && !in_array($order->status, ['shipped', 'cancelled'], true);
                                $isOverdue = $processingOverdue || $shippingOverdue;
                                $isDirectSale = (string) ($order->sales_channel ?? 'order') === 'direct_sale';
                                $supplierName = $isDirectSale ? 'Direktna prodaja' : $order->supplier_name_snapshot;
                                if (!$isDirectSale && !$supplierName && $order->relationLoaded('supplier')) {
                                    $supplierName = $order->supplier?->displayName();
                                }
                                $statusTone = $order->completed_at ? 'ok' : match ($order->status) {
                                    'cancelled' => 'danger',
                                    'shipped' => 'info',
                                    'new', 'processing', 'confirmed' => 'warning',
                                    default => '',
                                };
                                // Same conditions as before; the first one is the primary button, the rest go into the "Još" menu.
                                $rowActions = [];
                                if ($order->source_system === 'laravel' && !$order->accepted_at && in_array($order->status, ['new', 'processing'], true)) {
                                    $rowActions[] = 'accept';
                                }
                                if ($order->source_system === 'laravel' && $order->status === 'new') {
                                    $rowActions[] = 'processing';
                                } elseif ($order->source_system === 'laravel' && $order->status === 'processing') {
                                    $rowActions[] = 'confirmed';
                                }
                                if (!$order->completed_at && in_array($order->status, ['confirmed', 'shipped'], true) && ($order->payment_method === 'cash_on_delivery' || $order->payment_status === 'paid')) {
                                    $rowActions[] = 'complete'; // label "Evidentiraj isporuku" lives in partials/row-action
                                }
                            @endphp
                            <tr role="row" class="{{ $isOverdue ? 'a1-ol-row--over' : '' }}">
                                <td role="cell" class="a1-table__primary a1-ol-primary a1-ol-nowrap" data-label="Broj" data-a1-lines="1">
                                    <a class="a1-link" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                                </td>
                                <td role="cell" data-label="Korisnik">
                                    <span class="a1-ol-cell">
                                        {{ $order->user?->displayName() ?? '—' }}
                                        <span class="a1-ol-sub" title="{{ $order->user?->email }}">{{ $order->user?->email }}</span>
                                    </span>
                                </td>
                                <td role="cell" class="a1-ol-late" data-label="Odgovorno lice">
                                    <span class="a1-ol-cell">
                                        {{ $supplierName ?: '—' }}
                                        <span class="a1-ol-sub">{{ $isDirectSale ? 'Bez SubAgenta' : ($order->accepted_at ? 'Preuzeto '.$order->accepted_at->format('d.m. H:i') : 'Čeka preuzimanje') }}</span>
                                        @if($isOverdue)
                                            <span class="a1-ol-flag"><x-icon name="alert" :size="16" :shell="true" /> Prekoračen rok</span>
                                        @endif
                                    </span>
                                </td>
                                <td role="cell" data-label="Status obrade">
                                    <span class="a1-ol-cell">
                                        <span class="a1-pill {{ $statusTone !== '' ? 'a1-pill--'.$statusTone : '' }}">{{ $order->completed_at ? 'Isporučena' : ($statusLabels[$order->status] ?? $order->status) }}</span>
                                        <span class="a1-ol-sub">{{ $isDirectSale ? 'Direktna prodaja' : $order->source_system }}</span>
                                        @if($order->completed_at)
                                            <span class="a1-ol-sub">{{ $order->completed_at->format('d.m.Y H:i') }}</span>
                                        @endif
                                        @if($order->relationLoaded('commission') && $order->commission)
                                            <span class="a1-ol-sub">Provizija: {{ $order->commission->status }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td role="cell" class="a1-ol-late a1-ol-nowrap" data-label="Plaćanje">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</td>
                                <td role="cell" class="a1-table__num" data-label="Iznos"><strong>{{ number_format((float) $order->subtotal_rsd, 2, ',', '.') }} RSD</strong></td>
                                <td role="cell" class="a1-ol-late a1-ol-nowrap" data-label="Rok">
                                    @if($order->expected_shipping_at)
                                        <span class="a1-ol-date {{ $isOverdue ? 'a1-ol-due' : '' }}">{{ $order->expected_shipping_at->format('d.m.Y') }}</span>
                                        <span class="a1-ol-sub">{{ $order->expected_shipping_at->format('H:i') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td role="cell" class="a1-ol-nowrap" data-label="Datum">
                                    @if($order->created_at)
                                        <span class="a1-ol-date">{{ $order->created_at->format('d.m.Y') }}</span>
                                        <span class="a1-ol-sub">{{ $order->created_at->format('H:i') }}</span>
                                    @endif
                                </td>
                                <td role="cell" class="a1-table__actions" data-label="Brze akcije">
                                    <div class="a1-ol-actions" role="group" aria-label="Akcije za porudžbinu {{ $order->order_number }}">
                                        @if($rowActions !== [])
                                            @include('admin.orders.partials.row-action', ['order' => $order, 'kind' => $rowActions[0], 'menu' => false])
                                        @endif
                                        <a class="a1-btn a1-btn--quiet a1-btn--sm" href="{{ route('admin.orders.show', $order) }}" aria-label="Detalji: {{ $order->order_number }}">Detalji</a>
                                        @if(count($rowActions) > 1)
                                            <details class="a1-nav-dd a1-nav-dd--end a1-ol-more">
                                                <summary class="a1-btn a1-btn--quiet a1-btn--sm" aria-label="Još akcija: {{ $order->order_number }}">Još</summary>
                                                <div class="a1-nav-dd__panel a1-ol-morepanel">
                                                    @foreach(array_slice($rowActions, 1) as $moreKind)
                                                        @include('admin.orders.partials.row-action', ['order' => $order, 'kind' => $moreKind, 'menu' => true])
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        {{ $orders->links('partials.ol-pager') }}
    @endif
</div>
@endsection

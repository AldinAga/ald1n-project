@extends('layouts.app')
@section('title', 'Moje porudžbine')
@section('content')
@php
    // Status labels and tones come from App\Support\OrderStatus (one source for the whole product).
    $paymentLabels = [
        'pending' => 'Na čekanju',
        'paid' => 'Plaćeno',
        'cancelled' => 'Stornirano',
    ];
    $methodLabels = [
        'cash_on_delivery' => 'Pouzećem',
        'bank_transfer' => 'Uplata na račun',
        'deferred_payment' => 'Odloženo plaćanje',
        'cash' => 'Gotovina',
        'card' => 'Kartica',
        'other' => 'Drugo',
    ];
@endphp
<div class="a1-ops" data-build16-orders-list="1">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Poručivanje robe</span>
            <h1 class="a1-title">Moje porudžbine</h1>
            <p class="a1-lead">Status, plaćanje, dokumenti i isporuka na jednom mestu.</p>
        </div>
        <div class="a1-pagehead__actions">
            <span class="a1-ol-count">{{ $orders->total() }} ukupno</span>
            @can('orders.create')
                <a class="a1-btn a1-btn--primary" href="{{ route('orders.create') }}">
                    <x-icon name="plus-circle" :size="18" :shell="true" /> Nova porudžbina
                </a>
            @endcan
        </div>
    </header>

    @if($orders->isEmpty())
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="orders" :size="28" :shell="true" /></span>
            <p>Još nema porudžbina.</p>
        </div>
    @else
        <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Moje porudžbine" tabindex="0">
            <table class="a1-table a1-table--cards a1-ol-table" role="table">
                <caption class="a1-sr">Moje porudžbine sa statusom, plaćanjem, iznosom i datumom.</caption>
                <thead role="rowgroup">
                    <tr role="row">
                        <th scope="col" role="columnheader">Broj</th>
                        <th scope="col" role="columnheader">Dobavljač</th>
                        <th scope="col" role="columnheader">Status</th>
                        <th scope="col" role="columnheader">Plaćanje</th>
                        <th scope="col" role="columnheader" class="a1-table__num">Iznos</th>
                        <th scope="col" role="columnheader" class="a1-table__num">Provizija</th>
                        <th scope="col" role="columnheader">Datum</th>
                        <th scope="col" role="columnheader"><span class="a1-sr">Detalji</span></th>
                    </tr>
                </thead>
                <tbody role="rowgroup">
                    @foreach($orders as $order)
                        @php
                            $statusTone = \App\Support\OrderStatus::tone((string) $order->status, $order->completed_at !== null);
                        @endphp
                        <tr role="row">
                            <td role="cell" class="a1-table__primary a1-ol-primary" data-label="Broj">{{ $order->order_number }}</td>
                            <td role="cell" class="a1-ol-late" data-label="Dobavljač">{{ $order->sales_channel === 'direct_sale' ? 'Direktna prodaja' : ($order->supplier_name_snapshot ?: $order->supplier?->displayName() ?: '—') }}</td>
                            <td role="cell" data-label="Status">
                                <span class="a1-pill {{ $statusTone !== '' ? 'a1-pill--'.$statusTone : '' }}">{{ \App\Support\OrderStatus::label((string) $order->status, $order->completed_at !== null) }}</span>
                            </td>
                            <td role="cell" class="a1-ol-late" data-label="Plaćanje">{{ $methodLabels[$order->payment_method] ?? $order->payment_method }} / {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</td>
                            <td role="cell" class="a1-table__num" data-label="Iznos"><strong>{{ number_format((float)$order->subtotal_rsd,2,',','.') }} RSD</strong></td>
                            <td role="cell" class="a1-table__num a1-ol-late" data-label="Provizija">{{ $order->commission ? number_format((float)$order->commission->total_eur,2,',','.').' EUR' : '—' }}</td>
                            <td role="cell" data-label="Datum">{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                            <td role="cell" class="a1-table__actions">
                                <a class="a1-btn a1-btn--sm" href="{{ route('orders.show',$order) }}" aria-label="Detalji: {{ $order->order_number }}">
                                    Detalji <x-icon name="arrow-right" :size="16" :shell="true" />
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{ $orders->links('partials.ol-pager') }}
</div>
@endsection

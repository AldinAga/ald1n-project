@extends('layouts.app')
@section('title', 'Moje porudžbine')
@section('content')
<div class="build16-orders-list-shell" data-build16-orders-list="1">
    <div class="page-heading build16-orders-heading">
        <div>
            <span class="eyebrow">Poručivanje robe</span>
            <h1>Moje porudžbine</h1>
            <p>Status, plaćanje, dokumenti i isporuka na jednom mestu.</p>
        </div>
        @can('orders.create')
            <a class="button button-primary" href="{{ route('orders.create') }}">
                <x-icon name="plus-circle" size="18" /> Nova porudžbina
            </a>
        @endcan
    </div>

    <div class="build16-orders-summary">
        <div class="build16-orders-summary-icon"><x-icon name="orders" size="22" /></div>
        <div>
            <small>Moje porudžbine</small>
            <strong>{{ $orders->total() }} ukupno</strong>
        </div>
    </div>

    <div class="admin-table-wrap build16-orders-table-wrap">
        <table class="admin-table build16-orders-table">
            <thead>
                <tr>
                    <th>Broj</th>
                    <th>Dobavljač</th>
                    <th>Status</th>
                    <th>Plaćanje</th>
                    <th>Iznos</th>
                    <th>Provizija</th>
                    <th>Datum</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td data-label="Broj"><strong>{{ $order->order_number }}</strong></td>
                        <td data-label="Dobavljač">{{ $order->sales_channel === 'direct_sale' ? 'Direktna prodaja' : ($order->supplier_name_snapshot ?: $order->supplier?->displayName() ?: '—') }}</td>
                        <td data-label="Status">
                            <span class="status-badge status-{{ $order->completed_at ? 'completed' : ($order->status === 'cancelled' ? 'archived' : ($order->status === 'shipped' ? 'active' : 'draft')) }}">
                                {{ $order->completed_at ? 'Kompletirana' : $order->status }}
                            </span>
                        </td>
                        <td data-label="Plaćanje">{{ $order->payment_method }} / {{ $order->payment_status }}</td>
                        <td data-label="Iznos"><strong>{{ number_format((float)$order->subtotal_rsd,2,',','.') }} RSD</strong></td>
                        <td data-label="Provizija">{{ $order->commission ? number_format((float)$order->commission->total_eur,2,',','.').' EUR' : '—' }}</td>
                        <td data-label="Datum">{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                        <td>
                            <a class="button button-ghost button-small" href="{{ route('orders.show',$order) }}">
                                Detalji <x-icon name="arrow-right" size="16" />
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Još nema porudžbina.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">{{ $orders->links() }}</div>
</div>
@endsection

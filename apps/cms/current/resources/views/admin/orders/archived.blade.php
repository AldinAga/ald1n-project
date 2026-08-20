@extends('layouts.app')

@section('title', 'Arhivirane porudžbine')

@section('content')
<div class="orders-archive-page" data-order-archive-center="1">
    <a class="back-link" href="{{ route('admin.orders.index') }}">← Aktivne porudžbine</a>

    <div class="page-heading">
        <div>
            <span class="eyebrow">Arhiva</span>
            <h1>Arhivirane porudžbine</h1>
            <p>Ovde su završene ili otkazane porudžbine sklonjene iz operativnih listi, pretrage, brojača i API prikaza. Arhiviranje je reverzibilno. Trajno operativno brisanje nije: porudžbina nestaje i iz ove arhive, dok finansijska, pravna, dokumentaciona, garancijska i audit istorija potrebna za integritet ostaje sačuvana.</p>
        </div>
        <div class="header-button-row">
            <div class="count-pill">{{ $orders->total() }} arhiviranih</div>
        </div>
    </div>

    <form class="filter-panel admin-filter" method="get">
        <label class="search-field">
            <span>Pretraga arhive</span>
            <input type="search" name="q" value="{{ $query }}" placeholder="Broj porudžbine, kupac ili telefon">
        </label>
        <div class="filter-actions">
            <button class="button button-primary" type="submit">Pretraži</button>
            @if($query !== '')
                <a class="button button-ghost" href="{{ route('admin.orders.archived') }}">Reset</a>
            @endif
        </div>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Porudžbina</th>
                    <th>Kupac</th>
                    <th>Status</th>
                    <th>Arhivirana</th>
                    <th>Razlog</th>
                    <th>Akcija</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td data-label="Porudžbina">
                            <strong>{{ $order->order_number }}</strong>
                            <small>#{{ $order->id }}</small>
                        </td>
                        <td data-label="Kupac">
                            <strong>{{ $order->shipping_full_name ?: $order->user?->displayName() ?: '—' }}</strong>
                            <small>{{ $order->shipping_phone ?: '—' }}</small>
                        </td>
                        <td data-label="Status">
                            <span class="status-badge status-archived">
                                {{ $order->completed_at ? 'Završena' : ((string) $order->status === 'cancelled' ? 'Otkazana' : $order->status) }}
                            </span>
                        </td>
                        <td data-label="Arhivirana">{{ $order->archived_at?->format('d.m.Y. H:i') ?? '—' }}</td>
                        <td data-label="Razlog">{{ $order->archive_reason ?: '—' }}</td>
                        <td data-label="Akcija">
                            <div class="order-archive-actions">
                                <form method="post" action="{{ route('admin.orders.restore', ['orderId' => $order->id]) }}">
                                    @csrf
                                    <button class="button button-small button-secondary" type="submit" data-confirm="Opozvati arhiviranje porudžbine {{ $order->order_number }}?">Opozovi Arhiviranje</button>
                                </form>

                                @if($canPurge)
                                    <details class="danger-zone order-archive-purge" data-order-permanent-delete>
                                        <summary>Trajno obriši porudžbinu</summary>
                                        <div class="danger-zone-body">
                                            <p><strong>Ova radnja nema normalan restore.</strong> Porudžbina će nestati iz operativnih i arhivskih prikaza, pretrage, brojača, API-ja i mobilne aplikacije.</p>
                                            <p class="muted">Ne radi se naivni hard-delete: finansijska, pravna, dokumentaciona, garancijska i audit istorija potrebna za integritet ostaje interno sačuvana.</p>

                                            <form method="post" action="{{ route('admin.orders.purge', ['orderId' => $order->id]) }}" class="form-grid">
                                                @csrf
                                                @method('DELETE')

                                                <label class="full-span">
                                                    <span>Razlog trajnog uklanjanja</span>
                                                    <textarea name="purge_reason" rows="3" maxlength="1000" required placeholder="Zašto porudžbina više ne sme biti dostupna u operativnom sistemu?"></textarea>
                                                </label>

                                                <label class="full-span">
                                                    <span>Za potvrdu upiši tačan broj porudžbine: {{ $order->order_number }}</span>
                                                    <input name="confirmation" maxlength="100" autocomplete="off" required placeholder="{{ $order->order_number }}">
                                                </label>

                                                <div class="full-span">
                                                    <button class="button button-danger" type="submit" data-confirm="Trajno ukloniti porudžbinu {{ $order->order_number }} iz operativnog sistema? Ovu radnju nije moguće poništiti kroz normalan CMS tok.">
                                                        Trajno obriši porudžbinu
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </details>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state">Nema arhiviranih porudžbina koje odgovaraju pretrazi.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">{{ $orders->links() }}</div>
</div>


@endsection

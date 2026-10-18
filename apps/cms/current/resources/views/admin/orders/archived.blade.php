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
                                    <button
                                        class="button button-small button-ghost order-archive-danger-trigger"
                                        type="button"
                                        data-order-purge-open="{{ $order->id }}"
                                        aria-controls="orderPurgeDialog-{{ $order->id }}"
                                        aria-haspopup="dialog"
                                    >Trajno obriši porudžbinu</button>
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

    {{-- The confirmation dialogs live outside the scrollable six-column table. --}}
    @if($canPurge)
        @foreach($orders as $order)
            <dialog
                class="danger-zone order-archive-purge order-archive-purge-dialog"
                id="orderPurgeDialog-{{ $order->id }}"
                data-order-permanent-delete="1"
                aria-labelledby="order-purge-title-{{ $order->id }}"
                aria-describedby="order-purge-warning-{{ $order->id }}"
            >
                <div class="order-archive-purge-content">
                    <header class="order-archive-purge-heading">
                        <div>
                            <span class="order-archive-danger-eyebrow">Zona trajnog uklanjanja</span>
                            <h2 id="order-purge-title-{{ $order->id }}">Trajno obriši porudžbinu</h2>
                            <p class="order-archive-purge-number">{{ $order->order_number }}</p>
                        </div>
                        <button class="order-archive-purge-dismiss" type="button" data-order-purge-close aria-label="Zatvori dijalog za porudžbinu {{ $order->order_number }}">×</button>
                    </header>
                    <div class="order-archive-purge-warning">
                        <strong id="order-purge-warning-{{ $order->id }}">Ova radnja nema normalan restore.</strong>
                        <p>Porudžbina će nestati iz operativnih i arhivskih prikaza, pretrage, brojača, API-ja i mobilne aplikacije.</p>
                    </div>
                    <p class="order-archive-purge-retention">Finansijska, pravna, dokumentaciona, garancijska i audit istorija potrebna za integritet ostaje interno sačuvana. Ne radi se naivni hard-delete.</p>

                    <form method="post" action="{{ route('admin.orders.purge', ['orderId' => $order->id]) }}" class="order-archive-purge-form">
                        @csrf
                        @method('DELETE')

                        <div class="order-archive-purge-fields">
                            <div class="order-archive-purge-field">
                                <label for="order-purge-reason-{{ $order->id }}">Razlog trajnog uklanjanja</label>
                                <textarea id="order-purge-reason-{{ $order->id }}" name="purge_reason" rows="3" minlength="5" maxlength="1000" required placeholder="Zašto porudžbina više ne sme biti dostupna u operativnom sistemu?"></textarea>
                            </div>
                            <div class="order-archive-purge-field">
                                <label for="order-purge-confirmation-{{ $order->id }}">Za potvrdu upiši tačan broj porudžbine: {{ $order->order_number }}</label>
                                <input id="order-purge-confirmation-{{ $order->id }}" name="confirmation" maxlength="100" autocomplete="off" spellcheck="false" required placeholder="{{ $order->order_number }}">
                            </div>
                        </div>

                        <div class="order-archive-purge-footer">
                            <button class="button button-ghost" type="button" data-order-purge-close>Odustani</button>
                            <button class="button button-danger" type="submit" data-confirm="Trajno ukloniti porudžbinu {{ $order->order_number }} iz operativnog sistema? Ovu radnju nije moguće poništiti kroz normalan CMS tok.">
                                Trajno obriši porudžbinu
                            </button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endforeach
    @endif

    <div class="pagination-wrap">{{ $orders->links() }}</div>
</div>


@endsection

@push('scripts')
<script>
(() => {
    const archive = document.querySelector('[data-order-archive-center="1"]');
    if (!archive) return;

    archive.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;

        const opener = event.target.closest('[data-order-purge-open]');
        if (opener) {
            const dialog = document.getElementById(opener.getAttribute('aria-controls'));
            if (!dialog || typeof dialog.showModal !== 'function' || dialog.open) return;

            dialog.addEventListener('close', () => {
                dialog.querySelector('form')?.reset();
                if (opener.isConnected) opener.focus();
            }, {once: true});
            dialog.showModal();
            return;
        }

        const closeButton = event.target.closest('[data-order-purge-close]');
        if (closeButton) closeButton.closest('dialog')?.close();
    });
})();
</script>
@endpush

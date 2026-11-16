@extends('layouts.app')

@section('title', 'Arhivirane porudžbine')

@section('content')
<div class="a1-ops" data-order-archive-center="1">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('admin.orders.index') }}">Aktivne porudžbine</a></li>
            <li aria-current="page">Arhivirane porudžbine</li>
        </ol>
    </nav>

    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Arhiva</span>
            <h1 class="a1-title">Arhivirane porudžbine</h1>
            <p class="a1-lead">Ovde su završene ili otkazane porudžbine sklonjene iz operativnih listi, pretrage, brojača i API prikaza. Arhiviranje je reverzibilno. Trajno operativno brisanje nije: porudžbina nestaje i iz ove arhive, dok finansijska, pravna, dokumentaciona, garancijska i audit istorija potrebna za integritet ostaje sačuvana.</p>
        </div>
        <div class="a1-pagehead__actions">
            <span class="a1-ol-count">{{ $orders->total() }} arhiviranih</span>
        </div>
    </header>

    <form class="a1-filters" method="get" role="search" aria-label="Pretraga arhive">
        <div class="a1-filters__fields a1-ol-fields">
            <div class="a1-field a1-ol-field-wide">
                <label class="a1-label" for="ol-archive-q">Pretraga arhive</label>
                <div class="a1-search">
                    <x-icon name="search" :size="20" :shell="true" />
                    <input class="a1-input" id="ol-archive-q" type="search" name="q" value="{{ $query }}" placeholder="Broj porudžbine, kupac ili telefon">
                </div>
            </div>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Pretraži</button>
            @if($query !== '')
                <a class="a1-btn a1-btn--quiet" href="{{ route('admin.orders.archived') }}">Reset</a>
            @endif
        </div>
    </form>

    @if($query !== '')
        <div class="a1-results">
            <p class="a1-results__count">Aktivni filteri:</p>
            <ul class="a1-chips">
                <li><a class="a1-chip" href="{{ route('admin.orders.archived') }}" aria-label="Ukloni filter: Pretraga: {{ $query }}">Pretraga: {{ $query }} <x-icon name="x" :size="16" :shell="true" /></a></li>
            </ul>
        </div>
    @endif

    @if($orders->isEmpty())
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="archive" :size="28" :shell="true" /></span>
            <p>Nema arhiviranih porudžbina koje odgovaraju pretrazi.</p>
        </div>
    @else
        <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Lista arhiviranih porudžbina" tabindex="0">
            <table class="a1-table a1-table--cards a1-ol-table" role="table">
                <caption class="a1-sr">Arhivirane porudžbine sa razlogom arhiviranja i dostupnim radnjama.</caption>
                <thead role="rowgroup">
                    <tr role="row">
                        <th scope="col" role="columnheader">Porudžbina</th>
                        <th scope="col" role="columnheader">Kupac</th>
                        <th scope="col" role="columnheader">Status</th>
                        <th scope="col" role="columnheader">Arhivirana</th>
                        <th scope="col" role="columnheader">Razlog</th>
                        <th scope="col" role="columnheader">Akcija</th>
                    </tr>
                </thead>
                <tbody role="rowgroup">
                    @foreach($orders as $order)
                        <tr role="row">
                            <td role="cell" class="a1-table__primary a1-ol-primary" data-label="Porudžbina">
                                {{ $order->order_number }}
                                <span class="a1-ol-sub">#{{ $order->id }}</span>
                            </td>
                            <td role="cell" data-label="Kupac">
                                <span class="a1-ol-cell">
                                    {{ $order->shipping_full_name ?: $order->user?->displayName() ?: '—' }}
                                    <span class="a1-ol-sub">{{ $order->shipping_phone ?: '—' }}</span>
                                </span>
                            </td>
                            <td role="cell" data-label="Status">
                                <span class="a1-pill">{{ $order->completed_at ? 'Završena' : ((string) $order->status === 'cancelled' ? 'Otkazana' : $order->status) }}</span>
                            </td>
                            <td role="cell" data-label="Arhivirana">{{ $order->archived_at?->format('d.m.Y. H:i') ?? '—' }}</td>
                            <td role="cell" data-label="Razlog">{{ $order->archive_reason ?: '—' }}</td>
                            <td role="cell" class="a1-table__actions" data-label="Akcija">
                                <div class="a1-ol-actions" role="group" aria-label="Akcije za porudžbinu {{ $order->order_number }}">
                                    <form class="a1-ol-inline" method="post" action="{{ route('admin.orders.restore', ['orderId' => $order->id]) }}">
                                        @csrf
                                        <button class="a1-btn a1-btn--sm" type="submit" aria-label="Opozovi Arhiviranje: {{ $order->order_number }}" data-confirm="Opozvati arhiviranje porudžbine {{ $order->order_number }}?">Opozovi Arhiviranje</button>
                                    </form>

                                    @if($canPurge)
                                        <button
                                            class="a1-btn a1-btn--danger a1-btn--sm"
                                            type="button"
                                            data-order-purge-open="{{ $order->id }}"
                                            aria-controls="orderPurgeDialog-{{ $order->id }}"
                                            aria-haspopup="dialog"
                                            aria-label="Trajno obriši porudžbinu: {{ $order->order_number }}"
                                        >Trajno obriši porudžbinu</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- The confirmation dialogs live outside the scrollable six-column table. --}}
    @if($canPurge)
        @foreach($orders as $order)
            <dialog
                class="a1-dialog a1-ol-purge"
                id="orderPurgeDialog-{{ $order->id }}"
                data-order-permanent-delete="1"
                aria-labelledby="order-purge-title-{{ $order->id }}"
                aria-describedby="order-purge-warning-{{ $order->id }}"
            >
                <div class="a1-dialog__head">
                    <div class="a1-ol-purge__title">
                        <span class="a1-ol-danger-tag"><x-icon name="alert" :size="16" :shell="true" /> Zona trajnog uklanjanja</span>
                        <h2 class="a1-h2" id="order-purge-title-{{ $order->id }}">Trajno obriši porudžbinu</h2>
                        <p class="a1-text">{{ $order->order_number }}</p>
                    </div>
                    <button class="a1-btn a1-btn--icon a1-btn--sm a1-btn--quiet" type="button" data-order-purge-close aria-label="Zatvori dijalog za porudžbinu {{ $order->order_number }}"><x-icon name="x" :size="20" :shell="true" /></button>
                </div>
                <form class="a1-form" method="post" action="{{ route('admin.orders.purge', ['orderId' => $order->id]) }}">
                    @csrf
                    @method('DELETE')

                    <div class="a1-dialog__body">
                        <div class="a1-alert a1-alert--danger">
                            <x-icon name="alert" :size="20" :shell="true" />
                            <div>
                                <p class="a1-alert__title" id="order-purge-warning-{{ $order->id }}">Ova radnja nema normalan restore.</p>
                                <p>Porudžbina će nestati iz operativnih i arhivskih prikaza, pretrage, brojača, API-ja i mobilne aplikacije.</p>
                            </div>
                        </div>
                        <p data-order-purge-retention>Finansijska, pravna, dokumentaciona, garancijska i audit istorija potrebna za integritet ostaje interno sačuvana. Ne radi se naivni hard-delete.</p>

                        <div class="a1-field">
                            <label class="a1-label" for="order-purge-reason-{{ $order->id }}">Razlog trajnog uklanjanja</label>
                            <textarea class="a1-textarea" id="order-purge-reason-{{ $order->id }}" name="purge_reason" rows="3" minlength="5" maxlength="1000" required placeholder="Zašto porudžbina više ne sme biti dostupna u operativnom sistemu?"></textarea>
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="order-purge-confirmation-{{ $order->id }}">Za potvrdu upiši tačan broj porudžbine: {{ $order->order_number }}</label>
                            <input class="a1-input" id="order-purge-confirmation-{{ $order->id }}" name="confirmation" maxlength="100" autocomplete="off" spellcheck="false" required placeholder="{{ $order->order_number }}">
                        </div>
                    </div>

                    <div class="a1-dialog__foot">
                        <button class="a1-btn" type="button" data-order-purge-close>Odustani</button>
                        <button class="a1-btn a1-btn--danger" type="submit" data-confirm="Trajno ukloniti porudžbinu {{ $order->order_number }} iz operativnog sistema? Ovu radnju nije moguće poništiti kroz normalan CMS tok.">
                            Trajno obriši porudžbinu
                        </button>
                    </div>
                </form>
            </dialog>
        @endforeach
    @endif

    {{ $orders->links('partials.ol-pager') }}
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
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

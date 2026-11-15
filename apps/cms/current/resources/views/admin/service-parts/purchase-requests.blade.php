@extends('layouts.app')
@section('title', 'Nabavka rezervnih delova')
@section('content')
@php
    $tone = static fn (string $status): string => match ($status) {
        'submitted' => ' a1-pill--warning',
        'ordered' => ' a1-pill--info',
        'received' => ' a1-pill--ok',
        'cancelled' => ' a1-pill--danger',
        default => '',
    };
@endphp
<div class="a1-ops">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('admin.service-parts.index') }}">Servisni lager</a></li>
            <li aria-current="page">Zahtevi za nabavku</li>
        </ol>
    </nav>

    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Nabavka</span>
            <h1 class="a1-title">Zahtevi za nabavku</h1>
            <p class="a1-lead">Od nacrta do prijema delova na servisni lager.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.service-part-suppliers.index') }}">Dobavljači</a>
        </div>
    </header>

    <section class="a1-card" aria-labelledby="pr-new-title">
        <h2 class="a1-h3" id="pr-new-title">Novi zahtev</h2>
        <form class="a1-form" method="post" action="{{ route('admin.service-part-purchases.store') }}" data-purchase-form>@csrf
            <div class="a1-row">
                <div class="a1-field">
                    <label class="a1-label" for="pr-supplier">Dobavljač</label>
                    <select class="a1-select" id="pr-supplier" name="supplier_id"><option value="">Odredi kasnije</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select>
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="pr-expected">Očekivani datum</label>
                    <input class="a1-input" id="pr-expected" type="date" name="expected_at">
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="pr-reference">Referenca dobavljača</label>
                    <input class="a1-input" id="pr-reference" name="supplier_reference">
                </div>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="pr-notes">Napomena</label>
                <textarea class="a1-textarea" id="pr-notes" name="notes"></textarea>
            </div>
            <fieldset class="a1-fieldset">
                <legend class="a1-legend">Stavke</legend>
                <div class="a1-ow-items" data-purchase-items></div>
                <div><button class="a1-btn a1-btn--sm" type="button" data-add-purchase-item><x-icon name="plus-circle" :size="20" :shell="true" /> Dodaj stavku</button></div>
            </fieldset>
            <div><button class="a1-btn a1-btn--primary" type="submit">Kreiraj nacrt nabavke</button></div>
        </form>
    </section>

    <form class="a1-filters a1-ow-filters" method="get" role="search" aria-label="Filteri zahteva za nabavku">
        <div class="a1-filters__fields">
            <div class="a1-field">
                <label class="a1-label" for="pr-q">Pretraga</label>
                <div class="a1-search"><x-icon name="search" :size="20" :shell="true" /><input class="a1-input" id="pr-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Broj, referenca ili dobavljač"></div>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="pr-status">Status</label>
                <select class="a1-select" id="pr-status" name="status"><option value="">Svi statusi</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected(($filters['status'] ?? '')===$key)>{{ $label }}</option>@endforeach</select>
            </div>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn" type="submit">Primeni</button>
            <a class="a1-btn a1-btn--quiet" href="{{ route('admin.service-part-purchases.index') }}">Resetuj</a>
        </div>
    </form>

    <section aria-labelledby="pr-history-title" class="a1-stack">
        <h2 class="a1-h3" id="pr-history-title">Istorija nabavki</h2>
        @if($purchaseRequests->isNotEmpty())
        <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Istorija nabavki" tabindex="0">
            <table class="a1-table a1-table--cards" role="table">
                <caption class="a1-sr">Istorija nabavki</caption>
                <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Broj</th><th scope="col" role="columnheader">Dobavljač</th><th scope="col" role="columnheader">Status</th><th scope="col" role="columnheader" class="a1-table__num">Stavke</th><th scope="col" role="columnheader" class="a1-table__num">Vrednost</th><th scope="col" role="columnheader">Očekivano</th><th scope="col" role="columnheader"><span class="a1-sr">Radnje</span></th></tr></thead>
                <tbody role="rowgroup">
                @foreach($purchaseRequests as $purchase)
                    <tr role="row">
                        <td class="a1-table__primary" role="cell" data-label="Broj"><strong class="a1-table__id">{{ $purchase->request_number }}</strong><span class="a1-table__sub">{{ $purchase->created_at?->format('d.m.Y H:i') }}</span></td>
                        <td role="cell" data-label="Dobavljač">{{ $purchase->supplier?->name ?? 'Nije određen' }}</td>
                        <td role="cell" data-label="Status"><span class="a1-pill{{ $tone($purchase->status) }}">{{ $statuses[$purchase->status] ?? $purchase->status }}</span></td>
                        <td class="a1-table__num" role="cell" data-label="Stavke">{{ $purchase->items->count() }}</td>
                        <td class="a1-table__num" role="cell" data-label="Vrednost">{{ number_format((float)$purchase->total_cost_rsd,2,',','.') }} RSD</td>
                        <td role="cell" data-label="Očekivano">{{ $purchase->expected_at?->format('d.m.Y') ?? '—' }}</td>
                        <td class="a1-table__actions" role="cell"><a class="a1-btn a1-btn--sm" href="{{ route('admin.service-part-purchases.show',$purchase) }}" aria-label="Otvori zahtev {{ $purchase->request_number }}">Otvori</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="boxes" :size="28" :shell="true" /></span><p>Nema zahteva za nabavku.</p></div>
        @endif
        @if($purchaseRequests->hasPages())
        <nav class="a1-pager" aria-label="Straničenje">
            <p class="a1-pager__status">Strana {{ $purchaseRequests->currentPage() }} od {{ $purchaseRequests->lastPage() }}</p>
            <ul class="a1-pager__list">
                <li>@if($purchaseRequests->onFirstPage())<span class="a1-page" aria-disabled="true">Prethodna</span>@else<a class="a1-page" href="{{ $purchaseRequests->previousPageUrl() }}" rel="prev">Prethodna</a>@endif</li>
                <li>@if($purchaseRequests->hasMorePages())<a class="a1-page" href="{{ $purchaseRequests->nextPageUrl() }}" rel="next">Sledeća</a>@else<span class="a1-page" aria-disabled="true">Sledeća</span>@endif</li>
            </ul>
        </nav>
        @endif
    </section>

    <template data-purchase-item-template><div class="a1-ow-item">
        <div class="a1-field"><label class="a1-label" for="pr-item-__INDEX__-part">Deo <span class="a1-req" aria-hidden="true">*</span></label><select class="a1-select" id="pr-item-__INDEX__-part" name="items[__INDEX__][service_part_id]" required><option value="">Izaberi deo</option>@foreach($parts as $part)<option value="{{ $part->id }}">{{ $part->sku }} · {{ $part->name }}</option>@endforeach</select></div>
        <div class="a1-field"><label class="a1-label" for="pr-item-__INDEX__-qty">Količina <span class="a1-req" aria-hidden="true">*</span></label><input class="a1-input" id="pr-item-__INDEX__-qty" type="number" name="items[__INDEX__][ordered_quantity]" min="0.001" step="0.001" placeholder="Količina" required></div>
        <div class="a1-field"><label class="a1-label" for="pr-item-__INDEX__-cost">Cena RSD</label><input class="a1-input" id="pr-item-__INDEX__-cost" type="number" name="items[__INDEX__][unit_cost_rsd]" min="0" step="0.01" placeholder="Cena RSD"></div>
        <button type="button" class="a1-btn a1-btn--danger a1-btn--icon" data-remove-purchase-item aria-label="Ukloni stavku"><x-icon name="x" :size="20" :shell="true" /></button>
    </div></template>
</div>
@endsection
@push('scripts')<script nonce="{{ request()->attributes->get('_csp_nonce') }}">(()=>{const box=document.querySelector('[data-purchase-items]');const tpl=document.querySelector('[data-purchase-item-template]');let i=0;const add=()=>{const wrap=document.createElement('div');wrap.innerHTML=tpl.innerHTML.replaceAll('__INDEX__',String(i++));const row=wrap.firstElementChild;row.querySelector('[data-remove-purchase-item]').addEventListener('click',()=>{if(box.children.length>1)row.remove();});box.appendChild(row);};document.querySelector('[data-add-purchase-item]')?.addEventListener('click',add);add();})();</script>@endpush

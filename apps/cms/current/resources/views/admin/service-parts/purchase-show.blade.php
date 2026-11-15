@extends('layouts.app')
@section('title', $purchaseRequest->request_number)
@section('content')
@php
    $status = $purchaseRequest->status;
    $tone = match ($status) {
        'submitted' => 'warning',
        'ordered' => 'info',
        'received' => 'ok',
        'cancelled' => 'danger',
        default => '',
    };
@endphp
<div class="a1-ops">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('admin.service-parts.index') }}">Servisni lager</a></li>
            <li><a href="{{ route('admin.service-part-purchases.index') }}">Zahtevi za nabavku</a></li>
            <li aria-current="page">{{ $purchaseRequest->request_number }}</li>
        </ol>
    </nav>

    <header class="a1-card">
        <div class="a1-pagehead">
            <div class="a1-pagehead__text">
                <span class="a1-eyebrow">Nabavka rezervnih delova</span>
                <div class="a1-cluster">
                    <h1 class="a1-title">{{ $purchaseRequest->request_number }}</h1>
                    <span class="a1-pill{{ $tone !== '' ? ' a1-pill--'.$tone : '' }}">{{ $statuses[$status] ?? $status }}</span>
                </div>
                <p class="a1-text">{{ $purchaseRequest->supplier?->name ?? 'Dobavljač nije određen' }}</p>
            </div>
            <div class="a1-pagehead__actions">
                @if($status==='draft')<form class="a1-ow-inline" method="post" action="{{ route('admin.service-part-purchases.submit',$purchaseRequest) }}">@csrf<button class="a1-btn a1-btn--primary" type="submit">Označi kao poslato</button></form>@endif
                @if($status==='submitted')<form class="a1-ow-inline" method="post" action="{{ route('admin.service-part-purchases.order',$purchaseRequest) }}">@csrf<button class="a1-btn a1-btn--primary" type="submit">Označi kao poručeno</button></form>@endif
                @if($status==='ordered')<form class="a1-ow-inline" method="post" action="{{ route('admin.service-part-purchases.receive',$purchaseRequest) }}" data-confirm="Potvrditi prijem svih stavki na servisni lager?">@csrf<button class="a1-btn a1-btn--primary" type="submit">Primi sve stavke</button></form>@endif
            </div>
        </div>
    </header>

    <div class="a1-split">
        <div class="a1-stack">
            <section class="a1-card" aria-labelledby="pr-items-title">
                <h2 class="a1-h3" id="pr-items-title">Stavke</h2>
                <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Stavke zahteva {{ $purchaseRequest->request_number }}" tabindex="0">
                    <table class="a1-table a1-table--cards" role="table">
                        <caption class="a1-sr">Stavke zahteva {{ $purchaseRequest->request_number }}</caption>
                        <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Deo</th><th scope="col" role="columnheader" class="a1-table__num">Poručeno</th><th scope="col" role="columnheader" class="a1-table__num">Primljeno</th><th scope="col" role="columnheader" class="a1-table__num">Cena</th><th scope="col" role="columnheader" class="a1-table__num">Ukupno</th></tr></thead>
                        <tbody role="rowgroup">
                        @foreach($purchaseRequest->items as $item)
                            <tr role="row">
                                <td class="a1-table__primary" role="cell" data-label="Deo"><strong>{{ $item->part?->name }}</strong><span class="a1-table__sub">{{ $item->part?->sku }}</span></td>
                                <td class="a1-table__num" role="cell" data-label="Poručeno">{{ number_format((float)$item->ordered_quantity,2,',','.') }} {{ $item->part?->unit }}</td>
                                <td class="a1-table__num" role="cell" data-label="Primljeno">{{ number_format((float)$item->received_quantity,2,',','.') }}</td>
                                <td class="a1-table__num" role="cell" data-label="Cena">{{ number_format((float)$item->unit_cost_rsd,2,',','.') }} RSD</td>
                                <td class="a1-table__num" role="cell" data-label="Ukupno">{{ number_format((float)$item->ordered_quantity*(float)$item->unit_cost_rsd,2,',','.') }} RSD</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @unless($purchaseRequest->isTerminal())
            <section class="a1-card a1-ow-danger" aria-labelledby="pr-cancel-title">
                <div class="a1-alert a1-alert--danger"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><h2 class="a1-alert__title" id="pr-cancel-title">Otkaži zahtev</h2></div></div>
                <form class="a1-form" method="post" action="{{ route('admin.service-part-purchases.cancel',$purchaseRequest) }}">@csrf
                    <div class="a1-field">
                        <label class="a1-label" for="pr-cancel-reason">Razlog otkazivanja <span class="a1-req" aria-hidden="true">*</span></label>
                        <textarea class="a1-textarea" id="pr-cancel-reason" name="cancellation_reason" required minlength="5"></textarea>
                    </div>
                    <div><button class="a1-btn a1-btn--danger" type="submit" data-confirm="Otkazati zahtev za nabavku {{ $purchaseRequest->request_number }}?">Otkaži zahtev</button></div>
                </form>
            </section>
            @endunless
        </div>

        <aside class="a1-stack" aria-label="Podaci o zahtevu">
            <section class="a1-card" aria-labelledby="pr-data-title">
                <h2 class="a1-h3" id="pr-data-title">Podaci</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Dobavljač</dt><dd>{{ $purchaseRequest->supplier?->name ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Referenca</dt><dd>{{ $purchaseRequest->supplier_reference ?: '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Očekivano</dt><dd>{{ $purchaseRequest->expected_at?->format('d.m.Y') ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Vrednost</dt><dd class="a1-num"><strong>{{ number_format((float)$purchaseRequest->total_cost_rsd,2,',','.') }} RSD</strong></dd></div>
                </dl>
                @if($purchaseRequest->notes)<p class="a1-text">{{ $purchaseRequest->notes }}</p>@endif
            </section>
        </aside>
    </div>
</div>
@endsection

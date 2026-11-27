@extends('layouts.app')
@section('title', 'Servisni lager')
@section('content')
<div class="a1-ops" data-service-parts-stock>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Rezervni delovi</span>
            <h1 class="a1-title">Servisni lager</h1>
            <p class="a1-lead">Stanje, rezervacije, utrošak i korekcije delova za terenske radne naloge.</p>
        </div>
        @can('service_parts.procurement')
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.service-part-suppliers.index') }}">Dobavljači</a>
            <a class="a1-btn" href="{{ route('admin.service-part-purchases.index') }}">Nabavke</a>
        </div>
        @endcan
    </header>

    <div class="a1-kpis a1-kpis--4">
        <div class="a1-kpi"><span class="a1-kpi__label">Aktivni delovi</span><strong class="a1-kpi__value">{{ $stats['active'] }}</strong></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Nizak lager</span><strong class="a1-kpi__value">{{ $stats['low'] }}</strong></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Sa rezervacijama</span><strong class="a1-kpi__value">{{ $stats['reserved_lines'] }}</strong></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Vrednost lagera</span><strong class="a1-kpi__value">{{ number_format($stats['value'],2,',','.') }} RSD</strong></div>
    </div>

    <section class="a1-card" aria-labelledby="sp-new-title">
        <h2 class="a1-h3" id="sp-new-title">Novi rezervni deo</h2>
        <p class="a1-help">Polja sa zvezdicom su obavezna.</p>
        <form class="a1-form" method="post" action="{{ route('admin.service-parts.store') }}">@csrf
            <div class="a1-row">
                <div class="a1-field"><label class="a1-label" for="sp-new-sku">Šifra / SKU <span class="a1-req" aria-hidden="true">*</span></label><input class="a1-input" id="sp-new-sku" name="sku" required maxlength="80" value="{{ old('sku') }}"></div>
                <div class="a1-field"><label class="a1-label" for="sp-new-name">Naziv <span class="a1-req" aria-hidden="true">*</span></label><input class="a1-input" id="sp-new-name" name="name" required maxlength="190" value="{{ old('name') }}"></div>
                <div class="a1-field"><label class="a1-label" for="sp-new-unit">Jedinica mere <span class="a1-req" aria-hidden="true">*</span></label><input class="a1-input" id="sp-new-unit" name="unit" required maxlength="30" value="{{ old('unit','kom') }}"></div>
                <div class="a1-field"><label class="a1-label" for="sp-new-stock">Početno stanje</label><input class="a1-input" id="sp-new-stock" type="number" name="stock_quantity" min="0" step="0.001" value="{{ old('stock_quantity',0) }}"></div>
                <div class="a1-field"><label class="a1-label" for="sp-new-min">Minimalno stanje</label><input class="a1-input" id="sp-new-min" type="number" name="minimum_quantity" min="0" step="0.001" value="{{ old('minimum_quantity',0) }}"></div>
                <div class="a1-field"><label class="a1-label" for="sp-new-cost">Prosečna cena RSD</label><input class="a1-input" id="sp-new-cost" type="number" name="average_cost_rsd" min="0" step="0.01" value="{{ old('average_cost_rsd',0) }}"></div>
                <div class="a1-field"><label class="a1-label" for="sp-new-supplier">Preferirani dobavljač</label><select class="a1-select" id="sp-new-supplier" name="preferred_supplier_id"><option value="">Bez dobavljača</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div>
                <div class="a1-field"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="is_active" value="1" checked><span class="a1-check__box" aria-hidden="true"></span><span>Aktivan deo</span></label></div>
            </div>
            <div class="a1-field"><label class="a1-label" for="sp-new-notes">Napomena</label><textarea class="a1-textarea" id="sp-new-notes" name="notes" maxlength="5000">{{ old('notes') }}</textarea></div>
            <div><button class="a1-btn a1-btn--primary" type="submit">Kreiraj deo</button></div>
        </form>
    </section>

    <form class="a1-filters a1-ow-filters" method="get" role="search" aria-label="Pretraga i filteri delova">
        <div class="a1-filters__fields">
            <div class="a1-field">
                <label class="a1-label" for="sp-q">Šifra ili naziv</label>
                <div class="a1-search"><x-icon name="search" :size="20" :shell="true" /><input class="a1-input" id="sp-q" name="q" value="{{ $q }}"></div>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="sp-filter">Prikaz</label>
                <select class="a1-select" id="sp-filter" name="filter"><option value="all" @selected($filter==='all')>Svi aktivni</option><option value="low" @selected($filter==='low')>Nizak lager</option><option value="available" @selected($filter==='available')>Raspoloživi</option><option value="inactive" @selected($filter==='inactive')>Neaktivni</option></select>
            </div>
        </div>
        <p class="a1-help">Raspoloživo = fizičko stanje minus količina rezervisana za aktivne radne naloge.</p>
        <div class="a1-filters__actions">
            <button class="a1-btn" type="submit">Primeni</button>
            <a class="a1-btn a1-btn--quiet" href="{{ route('admin.service-parts.index') }}">Resetuj</a>
        </div>
    </form>

    <section class="a1-stack" aria-labelledby="sp-parts-title">
        <h2 class="a1-h3" id="sp-parts-title">Delovi</h2>
        @if($parts->isNotEmpty())
        <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Delovi servisnog lagera" tabindex="0">
            <table class="a1-table a1-table--cards" role="table">
                <caption class="a1-sr">Delovi servisnog lagera</caption>
                <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Deo</th><th scope="col" role="columnheader" class="a1-table__num">Stanje</th><th scope="col" role="columnheader" class="a1-table__num">Rezervisano</th><th scope="col" role="columnheader" class="a1-table__num">Raspoloživo</th><th scope="col" role="columnheader" class="a1-table__num">Minimum</th><th scope="col" role="columnheader">Dobavljač</th><th scope="col" role="columnheader">Izmena</th></tr></thead>
                <tbody role="rowgroup">
                @foreach($parts as $part)
                    <tr role="row">
                        <td class="a1-table__primary" role="cell" data-label="Deo"><strong>{{ $part->name }}</strong><span class="a1-table__sub">{{ $part->sku }} · {{ $part->unit }}</span>@if($part->isLowStock())<span class="a1-pill a1-pill--warning">Nizak lager</span>@endif</td>
                        <td class="a1-table__num" role="cell" data-label="Stanje">{{ number_format((float)$part->stock_quantity,2,',','.') }}</td>
                        <td class="a1-table__num" role="cell" data-label="Rezervisano">{{ number_format((float)$part->reserved_quantity,2,',','.') }}</td>
                        <td class="a1-table__num" role="cell" data-label="Raspoloživo"><strong>{{ number_format($part->availableQuantity(),2,',','.') }}</strong></td>
                        <td class="a1-table__num" role="cell" data-label="Minimum">{{ number_format((float)$part->minimum_quantity,2,',','.') }}</td>
                        <td role="cell" data-label="Dobavljač">{{ $part->preferredSupplier?->name ?? '—' }}</td>
                        <td class="a1-table__actions" role="cell" data-label="Izmena">
                            <details class="a1-ow-edit">
                                <summary class="a1-btn a1-btn--sm" aria-label="Uredi deo {{ $part->sku }}">Uredi</summary>
                                <div class="a1-ow-edit__panel">
                                    <form class="a1-form" method="post" action="{{ route('admin.service-parts.update',$part) }}">@csrf @method('PUT')
                                        <div class="a1-field"><label class="a1-label" for="sp-{{ $part->id }}-sku">Šifra / SKU</label><input class="a1-input" id="sp-{{ $part->id }}-sku" name="sku" value="{{ $part->sku }}" required></div>
                                        <div class="a1-field"><label class="a1-label" for="sp-{{ $part->id }}-name">Naziv</label><input class="a1-input" id="sp-{{ $part->id }}-name" name="name" value="{{ $part->name }}" required></div>
                                        <div class="a1-field"><label class="a1-label" for="sp-{{ $part->id }}-unit">Jedinica mere</label><input class="a1-input" id="sp-{{ $part->id }}-unit" name="unit" value="{{ $part->unit }}" required></div>
                                        <div class="a1-field"><label class="a1-label" for="sp-{{ $part->id }}-min">Minimalno stanje</label><input class="a1-input" id="sp-{{ $part->id }}-min" type="number" step="0.001" min="0" name="minimum_quantity" value="{{ $part->minimum_quantity }}"></div>
                                        <div class="a1-field"><label class="a1-label" for="sp-{{ $part->id }}-cost">Prosečna cena RSD</label><input class="a1-input" id="sp-{{ $part->id }}-cost" type="number" step="0.01" min="0" name="average_cost_rsd" value="{{ $part->average_cost_rsd }}"></div>
                                        <div class="a1-field"><label class="a1-label" for="sp-{{ $part->id }}-supplier">Preferirani dobavljač</label><select class="a1-select" id="sp-{{ $part->id }}-supplier" name="preferred_supplier_id"><option value="">Bez dobavljača</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected($part->preferred_supplier_id===$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div>
                                        <div class="a1-field"><label class="a1-label" for="sp-{{ $part->id }}-notes">Napomena</label><textarea class="a1-textarea" id="sp-{{ $part->id }}-notes" name="notes">{{ $part->notes }}</textarea></div>
                                        <div class="a1-field"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="is_active" value="1" @checked($part->is_active)><span class="a1-check__box" aria-hidden="true"></span><span>Aktivan</span></label></div>
                                        <div><button class="a1-btn" type="submit" aria-label="Sačuvaj deo {{ $part->sku }}">Sačuvaj</button></div>
                                    </form>
                                    <form class="a1-form a1-ow-edit__adjust" method="post" action="{{ route('admin.service-parts.adjust',$part) }}">@csrf<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                        <div class="a1-field"><label class="a1-label" for="sp-{{ $part->id }}-qty">Korekcija (+/-)</label><input class="a1-input" id="sp-{{ $part->id }}-qty" type="number" name="quantity_change" step="0.001" required></div>
                                        <div class="a1-field"><label class="a1-label" for="sp-{{ $part->id }}-note">Razlog</label><textarea class="a1-textarea" id="sp-{{ $part->id }}-note" name="note" required minlength="5"></textarea></div>
                                        <div><button class="a1-btn" type="submit" aria-label="Knjiži korekciju za deo {{ $part->sku }}">Knjiži korekciju</button></div>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="cube" :size="28" :shell="true" /></span><p>Nema rezervnih delova za izabrani filter.</p></div>
        @endif
        @if($parts->hasPages())
        <nav class="a1-pager" aria-label="Straničenje">
            <p class="a1-pager__status">Strana {{ $parts->currentPage() }} od {{ $parts->lastPage() }}</p>
            <ul class="a1-pager__list">
                <li>@if($parts->onFirstPage())<span class="a1-page" aria-disabled="true">Prethodna</span>@else<a class="a1-page" href="{{ $parts->previousPageUrl() }}" rel="prev">Prethodna</a>@endif</li>
                <li>@if($parts->hasMorePages())<a class="a1-page" href="{{ $parts->nextPageUrl() }}" rel="next">Sledeća</a>@else<span class="a1-page" aria-disabled="true">Sledeća</span>@endif</li>
            </ul>
        </nav>
        @endif
    </section>

    <section class="a1-stack" aria-labelledby="sp-moves-title">
        <h2 class="a1-h3" id="sp-moves-title">Poslednja kretanja</h2>
        @if($movements->isNotEmpty())
        <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Poslednja kretanja servisnog lagera" tabindex="0">
            <table class="a1-table a1-table--cards" role="table">
                <caption class="a1-sr">Poslednja kretanja servisnog lagera</caption>
                <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Datum</th><th scope="col" role="columnheader">Deo</th><th scope="col" role="columnheader">Vrsta</th><th scope="col" role="columnheader" class="a1-table__num">Stanje</th><th scope="col" role="columnheader" class="a1-table__num">Rezervacija</th><th scope="col" role="columnheader">Radni nalog</th></tr></thead>
                <tbody role="rowgroup">
                @foreach($movements as $movement)
                    <tr role="row">
                        <td class="a1-table__primary" role="cell" data-label="Datum">{{ $movement->created_at?->format('d.m.Y H:i') }}</td>
                        <td role="cell" data-label="Deo">{{ $movement->part?->name }}<span class="a1-table__sub">{{ $movement->part?->sku }}</span></td>
                        <td role="cell" data-label="Vrsta">{{ $movement->movement_type }}</td>
                        <td class="a1-table__num" role="cell" data-label="Stanje">{{ number_format((float)$movement->stock_change,2,',','.') }}</td>
                        <td class="a1-table__num" role="cell" data-label="Rezervacija">{{ number_format((float)$movement->reserved_change,2,',','.') }}</td>
                        <td role="cell" data-label="Radni nalog">@if($movement->workOrder)<a class="a1-link" href="{{ route('admin.field-operations.show',$movement->workOrder) }}">{{ $movement->workOrder->work_order_number }}</a>@else—@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="refresh" :size="28" :shell="true" /></span><p>Još nema kretanja.</p></div>
        @endif
    </section>
</div>
@endsection

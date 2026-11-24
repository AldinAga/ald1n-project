@extends('layouts.app')
@section('title', 'Garancije')
@section('content')
@include('admin.partials.g5-styles')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Poslovna pravila'])
@php
    $statusLabels = ['active' => 'Aktivna', 'expired' => 'Istekla', 'void' => 'Poništena'];
    $statusTone = ['active' => ' a1-pill--ok', 'expired' => ' a1-pill--warning', 'void' => ' a1-pill--off'];
@endphp
<div class="a1-ops a1-g5" data-warranties-admin>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Postprodaja</span>
            <h1 class="a1-title">Garancije i preventivno održavanje</h1>
            <p class="a1-lead">Pravila, garantni listovi, serijski brojevi i planirani servisni intervali.</p>
        </div>
        <div class="a1-pagehead__actions">
            <form method="post" action="{{ route('admin.warranties.backfill') }}">
                @csrf
                <button class="a1-btn" type="submit">Generiši nedostajuće garancije</button>
            </form>
        </div>
    </header>

    @if($pendingIssuances->isNotEmpty())
        <section class="a1-card a1-card--flush" aria-labelledby="warranty-pending-title">
            <div class="a1-g5-tablehead">
                <h2 class="a1-os-heading" id="warranty-pending-title">Izdati garantni listovi — čekaju isporuku</h2>
            </div>
            <div class="a1-tablewrap" role="region" aria-label="Izdati garantni listovi koji čekaju isporuku" tabindex="0">
                <table class="a1-table a1-table--cards">
                    <thead>
                        <tr><th scope="col">Garantni list</th><th scope="col">Porudžbina</th><th scope="col">Artikal</th><th scope="col">Datum</th><th scope="col">Dokument</th></tr>
                    </thead>
                    <tbody>
                        @foreach($pendingIssuances as $issuance)
                            <tr>
                                <td class="a1-table__primary a1-table__id" data-label="Garantni list">{{ $issuance->warranty_number }}</td>
                                <td data-label="Porudžbina">{{ $issuance->order?->order_number }}</td>
                                <td data-label="Artikal">{{ $issuance->snapshot_json['product_name'] ?? 'Artikal' }}</td>
                                <td data-label="Datum">{{ $issuance->issued_at?->format('d.m.Y') }}</td>
                                <td class="a1-table__actions"><a class="a1-btn a1-btn--sm" href="{{ route('warranties.issuances.pdf', $issuance) }}" target="_blank" rel="noopener noreferrer">PDF</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="a1-grid a1-grid--kpi" role="group" aria-label="Sažetak garancija">
        <div class="a1-kpi"><p class="a1-kpi__label">Aktivne garancije</p><p class="a1-kpi__value">{{ $stats['active'] }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Ističu za 30 dana</p><p class="a1-kpi__value">{{ $stats['expiring'] }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Održavanje uskoro</p><p class="a1-kpi__value">{{ $stats['maintenance_due'] }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Poništene</p><p class="a1-kpi__value">{{ $stats['void'] }}</p></div>
    </div>

    <div class="a1-grid a1-grid--2">
        <section class="a1-card" aria-labelledby="warranty-rule-new-title">
            <h2 class="a1-os-heading" id="warranty-rule-new-title">Novo pravilo garancije</h2>
            <form method="post" action="{{ route('admin.warranties.rules.store') }}" class="a1-form">
                @csrf
                <div class="a1-row">
                    <label class="a1-field a1-g5-wide">
                        <span class="a1-label">Naziv pravila</span>
                        <input class="a1-input" name="name" required maxlength="190" value="{{ old('name') }}">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Obuhvat</span>
                        <select class="a1-select" name="scope_type" data-warranty-scope>
                            <option value="global">Svi proizvodi</option>
                            <option value="category">Kategorija</option>
                            <option value="product">Pojedinačni proizvod</option>
                        </select>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Prioritet</span>
                        <input class="a1-input" type="number" name="priority" min="-1000" max="1000" value="{{ old('priority', 0) }}">
                    </label>
                    <div data-warranty-category hidden>
                        <label class="a1-field">
                            <span class="a1-label">Kategorija</span>
                            <select class="a1-select" name="category_id">
                                <option value="">Izaberi kategoriju</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div data-warranty-product hidden>
                        <label class="a1-field">
                            <span class="a1-label">Proizvod</span>
                            <select class="a1-select" name="product_id">
                                <option value="">Izaberi proizvod</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->sku }} · {{ $product->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="a1-field">
                        <span class="a1-label">Trajanje (meseci)</span>
                        <input class="a1-input" type="number" name="duration_months" min="0" max="240" value="{{ old('duration_months', 24) }}" required>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Dodatni dani</span>
                        <input class="a1-input" type="number" name="duration_days" min="0" max="3650" value="{{ old('duration_days', 0) }}" required>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Preventivni interval (meseci)</span>
                        <input class="a1-input" type="number" name="maintenance_interval_months" min="1" max="120" value="{{ old('maintenance_interval_months') }}" placeholder="Bez intervala">
                    </label>
                </div>
                <label class="a1-field">
                    <span class="a1-label">Uslovi garancije</span>
                    <textarea class="a1-textarea" name="terms" maxlength="20000">{{ old('terms', \App\Support\WarrantyTerms::DEFAULT_TEXT) }}</textarea>
                    <span class="a1-help">Podrazumevani tekst važi za nova izdavanja bez posebnih uslova. Postojeći garantni listovi zadržavaju svoje uslove. Uslovi ne ograničavaju zakonska prava potrošača.</span>
                </label>
                <label class="a1-check">
                    <input class="a1-check__input" type="checkbox" name="is_active" value="1" checked>
                    <span class="a1-check__box" aria-hidden="true"></span>
                    <span>Pravilo je aktivno</span>
                </label>
                <div class="a1-g5-actions">
                    <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj pravilo</button>
                </div>
            </form>
        </section>

        <section class="a1-card" aria-labelledby="warranty-filter-title">
            <h2 class="a1-os-heading" id="warranty-filter-title">Filter garantnih listova</h2>
            <form method="get" class="a1-form">
                <div class="a1-row">
                    <label class="a1-field">
                        <span class="a1-label">Broj, porudžbina, SKU ili naziv</span>
                        <input class="a1-input" name="q" value="{{ $filters['q'] ?? '' }}">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Status</span>
                        <select class="a1-select" name="status">
                            <option value="">Svi</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktivne</option>
                            <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>Istekle</option>
                            <option value="void" @selected(($filters['status'] ?? '') === 'void')>Poništene</option>
                        </select>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Održavanje</span>
                        <select class="a1-select" name="maintenance">
                            <option value="">Sve</option>
                            <option value="due" @selected(($filters['maintenance'] ?? '') === 'due')>Ima sledeći termin</option>
                            <option value="scheduled" @selected(($filters['maintenance'] ?? '') === 'scheduled')>Zakazano</option>
                            <option value="overdue" @selected(($filters['maintenance'] ?? '') === 'overdue')>Kasni</option>
                        </select>
                    </label>
                </div>
                <div class="a1-g5-actions">
                    <button class="a1-btn" type="submit">Primeni</button>
                </div>
            </form>
            <p class="a1-os-note">Specifično pravilo proizvoda ima prednost nad pravilom kategorije, a pravilo kategorije nad globalnim.</p>
        </section>
    </div>

    <section class="a1-stack" aria-labelledby="warranty-rules-title">
        <h2 class="a1-h2" id="warranty-rules-title">Pravila</h2>
        @foreach($rules as $rule)
            <article class="a1-card">
                <div class="a1-g5-group__head">
                    <div>
                        <h3 class="a1-h3">{{ $rule->name }}</h3>
                        <p>Prioritet {{ $rule->priority }}</p>
                    </div>
                    <span class="a1-pill{{ $rule->is_active ? ' a1-pill--ok' : ' a1-pill--off' }}">{{ $rule->is_active ? 'Aktivno' : 'Neaktivno' }}</span>
                </div>

                <dl class="a1-dl a1-g5-meta">
                    <div class="a1-dl__row"><dt>Obuhvat</dt><dd>{{ $rule->scope_type === 'product' ? ($rule->product?->name ?? 'Obrisan proizvod') : ($rule->scope_type === 'category' ? ($rule->category?->name ?? 'Obrisana kategorija') : 'Svi proizvodi') }}</dd></div>
                    <div class="a1-dl__row"><dt>Trajanje</dt><dd>{{ $rule->duration_months ? $rule->duration_months.' mes.' : '' }}{{ $rule->duration_months && $rule->duration_days ? ' + ' : '' }}{{ $rule->duration_days ? $rule->duration_days.' dana' : '' }}</dd></div>
                    <div class="a1-dl__row"><dt>Održavanje</dt><dd>{{ $rule->maintenance_interval_months ? $rule->maintenance_interval_months.' mes.' : 'Nije obavezno' }}</dd></div>
                </dl>

                <details class="a1-acc">
                    <summary>Uredi</summary>
                    <div class="a1-acc__body">
                        <form method="post" action="{{ route('admin.warranties.rules.update', $rule) }}" class="a1-form">
                            @csrf
                            @method('PUT')
                            <div class="a1-row">
                                <label class="a1-field a1-g5-wide">
                                    <span class="a1-label">Naziv pravila</span>
                                    <input class="a1-input" name="name" value="{{ $rule->name }}" required>
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Obuhvat</span>
                                    <select class="a1-select" name="scope_type">
                                        <option value="global" @selected($rule->scope_type === 'global')>Svi proizvodi</option>
                                        <option value="category" @selected($rule->scope_type === 'category')>Kategorija</option>
                                        <option value="product" @selected($rule->scope_type === 'product')>Proizvod</option>
                                    </select>
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Kategorija</span>
                                    <select class="a1-select" name="category_id">
                                        <option value="">Bez kategorije</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected($rule->category_id === $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Proizvod</span>
                                    <select class="a1-select" name="product_id">
                                        <option value="">Bez proizvoda</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" @selected($rule->product_id === $product->id)>{{ $product->sku }} · {{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Trajanje (meseci)</span>
                                    <input class="a1-input" type="number" name="duration_months" min="0" max="240" value="{{ $rule->duration_months }}" required>
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Dodatni dani</span>
                                    <input class="a1-input" type="number" name="duration_days" min="0" max="3650" value="{{ $rule->duration_days ?? 0 }}" required>
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Preventivni interval (meseci)</span>
                                    <input class="a1-input" type="number" name="maintenance_interval_months" min="1" max="120" value="{{ $rule->maintenance_interval_months }}" placeholder="Bez intervala">
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Prioritet</span>
                                    <input class="a1-input" type="number" name="priority" min="-1000" max="1000" value="{{ $rule->priority }}">
                                </label>
                            </div>
                            <label class="a1-field">
                                <span class="a1-label">Uslovi garancije</span>
                                <textarea class="a1-textarea" name="terms" maxlength="20000">{{ \App\Support\WarrantyTerms::forNewCertificate($rule->terms) }}</textarea>
                            </label>
                            <label class="a1-check">
                                <input class="a1-check__input" type="checkbox" name="is_active" value="1" @checked($rule->is_active)>
                                <span class="a1-check__box" aria-hidden="true"></span>
                                <span>Aktivno</span>
                            </label>
                            <div class="a1-g5-actions">
                                <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj</button>
                            </div>
                        </form>
                    </div>
                </details>
            </article>
        @endforeach
    </section>

    <section class="a1-stack" aria-labelledby="warranty-list-title">
        <h2 class="a1-h2" id="warranty-list-title">Garantni listovi</h2>
        @if($warranties->isEmpty())
            <div class="a1-empty">
                <p>Nema garantnih listova za izabrani filter.</p>
            </div>
        @else
            <div class="a1-tablewrap" role="region" aria-label="Garantni listovi" tabindex="0">
                <table class="a1-table a1-table--cards">
                    <thead>
                        <tr><th scope="col">Broj</th><th scope="col">Porudžbina</th><th scope="col">Kupac</th><th scope="col">Proizvod</th><th scope="col">Važi do</th><th scope="col">Status</th><th scope="col"><span class="a1-sr">Radnje</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach($warranties as $warranty)
                            @php $status = $warranty->effectiveStatus(); @endphp
                            <tr>
                                <td class="a1-table__primary a1-table__id" data-label="Broj">{{ $warranty->warranty_number }}</td>
                                <td data-label="Porudžbina">{{ $warranty->order?->order_number }}</td>
                                <td data-label="Kupac">{{ $warranty->customer_name_snapshot }}</td>
                                <td data-label="Proizvod">{{ $warranty->product_name_snapshot }}<span class="a1-table__sub">{{ $warranty->product_sku_snapshot }}</span></td>
                                <td data-label="Važi do">{{ $warranty->expires_at?->format('d.m.Y') }}@if($warranty->next_maintenance_at)<span class="a1-table__sub">Sledeći servis: {{ $warranty->next_maintenance_at->format('d.m.Y') }}</span>@endif</td>
                                <td data-label="Status"><span class="a1-pill{{ $statusTone[$status] ?? '' }}">{{ $statusLabels[$status] }}</span></td>
                                <td class="a1-table__actions"><a class="a1-btn a1-btn--sm" href="{{ route('admin.warranties.show', $warranty) }}" aria-label="Otvori garantni list {{ $warranty->warranty_number }}">Otvori</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        {{ $warranties->links('partials.ol-pager') }}
    </section>
</div>
@push('scripts')<script nonce="{{ request()->attributes->get('_csp_nonce') }}">document.querySelectorAll('[data-warranty-scope]').forEach((s)=>{const sync=()=>{const f=s.closest('form');f.querySelector('[data-warranty-category]').hidden=s.value!=='category';f.querySelector('[data-warranty-product]').hidden=s.value!=='product'};s.addEventListener('change',sync);sync()});</script>@endpush
@endsection

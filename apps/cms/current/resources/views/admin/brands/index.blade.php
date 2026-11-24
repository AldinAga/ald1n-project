@extends('layouts.app')
@section('title','Brendovi')
@section('content')
@include('admin.products.partials.styles')
{{-- MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3 --}}
{{-- MOBILE_V1_0_BRAND_LINE_EXPANSION_BATCH22_V3 --}}
@php
    $formBrand = $editingBrand ?? null;
    $showForm = $createMode || $formBrand !== null || $errors->any();
    $selectedTypeIds = collect(old('product_type_ids', $formBrand['product_type_ids'] ?? []))->map(fn($id)=> (int)$id)->all();
@endphp
<div class="a1-ops">
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Kataloški šifarnici</span>
        <h1 class="a1-title">Brendovi</h1>
        <p class="a1-lead">Jedan globalni brend može biti povezan sa više tipova proizvoda. Upravljaj zvaničnim podacima, tipovima i do deset kuriranih linija po tipu bez dupliranja brendova.</p>
    </div>
    <div class="a1-pagehead__actions">
        <a class="a1-btn a1-btn--primary" href="{{ route('admin.brand-manager.index', ['create' => 1]) }}">+ Dodaj brend</a>
        <a class="a1-btn" href="{{ route('admin.dictionary.index','product-types') }}">Tipovi proizvoda</a>
    </div>
</header>

@if(session('status'))
    <div class="a1-alert a1-alert--ok" role="status"><div class="a1-alert__body"><p>{{ session('status') }}</p></div></div>
@endif

<section class="a1-card" aria-labelledby="br-filter">
    <div class="a1-card__head">
        <div class="a1-card__title">
            <h2 class="a1-h3" id="br-filter">Pretraga i filter</h2>
            <p class="a1-text">Filter tipa je dinamički iz baze. Opcija „Svi“ prikazuje sve globalne brendove.</p>
        </div>
        <span class="a1-pill">{{ count($brands) }} brendova</span>
    </div>
    <form method="get" action="{{ route('admin.brand-manager.index') }}" class="a1-form" role="search">
        <div class="a1-pc-grid">
            <label class="a1-field">
                <span class="a1-label">Pretraga brenda</span>
                <input class="a1-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Naziv, opis ili website">
            </label>
            <label class="a1-field">
                <span class="a1-label">Tip / kategorija proizvoda</span>
                <select class="a1-select" name="product_type_id">
                    <option value="">Svi</option>
                    @foreach($product_types as $type)
                        <option value="{{ $type['id'] }}" @selected((int)($filters['product_type_id'] ?? 0) === (int)$type['id'])>
                            {{ $type['name'] }}@if($type['category_name']) — {{ $type['category_name'] }}@endif
                        </option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="a1-cluster">
            <button class="a1-btn a1-btn--primary" type="submit">Primeni filtere</button>
            <a class="a1-btn" href="{{ route('admin.brand-manager.index') }}">Očisti</a>
        </div>
    </form>
</section>

@if($showForm)
<section class="a1-card" id="brand-form" aria-labelledby="br-form-title">
    <div class="a1-card__head">
        <div class="a1-card__title">
            <span class="a1-eyebrow">{{ $formBrand ? 'Uređivanje' : 'Novi brend' }}</span>
            <h2 class="a1-h2" id="br-form-title">{{ $formBrand ? $formBrand['name'] : 'Dodaj globalni brend' }}</h2>
            <p class="a1-text">Slug se generiše automatski. Uklanjanje veze sa tipom je blokirano ako je koristi bilo koji aktivni ili istorijski artikal.</p>
        </div>
        @if($formBrand)<code class="a1-pc-code">{{ $formBrand['slug'] }}</code>@endif
    </div>

    <form method="post" action="{{ $formBrand ? route('admin.brand-manager.update',$formBrand['id']) : route('admin.brand-manager.store') }}" class="a1-form" data-brand-manager-form>
        @csrf
        @if($formBrand) @method('PUT') @endif

        <div class="a1-pc-grid">
            <label class="a1-field">
                <span class="a1-label">Naziv brenda</span>
                <input class="a1-input" name="name" maxlength="120" required value="{{ old('name', $formBrand['name'] ?? '') }}" placeholder="npr. Samsung" @error('name') aria-invalid="true" aria-describedby="br-err-name" @enderror>
                @error('name')<small class="a1-error" id="br-err-name">{{ $message }}</small>@enderror
            </label>
            <label class="a1-field">
                <span class="a1-label">Zvanični website</span>
                <input class="a1-input" type="url" name="website_url" maxlength="255" value="{{ old('website_url', $formBrand['website_url'] ?? '') }}" placeholder="https://www.example.com" @error('website_url') aria-invalid="true" aria-describedby="br-err-website" @enderror>
                @error('website_url')<small class="a1-error" id="br-err-website">{{ $message }}</small>@enderror
            </label>
            <label class="a1-field">
                <span class="a1-label">Status</span>
                <select class="a1-select" name="status" required>
                    <option value="active" @selected(old('status', $formBrand['status'] ?? 'active') === 'active')>Aktivan</option>
                    <option value="inactive" @selected(old('status', $formBrand['status'] ?? 'active') === 'inactive')>Neaktivan</option>
                </select>
            </label>
            <label class="a1-field">
                <span class="a1-label">Redosled</span>
                <input class="a1-input" type="number" min="0" max="1000000" name="sort_order" required value="{{ old('sort_order', $formBrand['sort_order'] ?? 100) }}">
            </label>
        </div>

        <label class="a1-field">
            <span class="a1-label">Kratak opis na srpskom</span>
            <textarea class="a1-textarea" name="description" rows="4" maxlength="65000" placeholder="Kratak opis proizvođača i relevantnog proizvodnog programa." @error('description') aria-invalid="true" aria-describedby="br-err-description" @enderror>{{ old('description', $formBrand['description'] ?? '') }}</textarea>
            @error('description')<small class="a1-error" id="br-err-description">{{ $message }}</small>@enderror
        </label>

        <div class="a1-card__title">
            <h3 class="a1-h3">Povezani tipovi i linije</h3>
            <p class="a1-text">Izaberi tipove kojima brend realno pripada. Za svaki izabrani tip možeš uneti do deset kuriranih linija. Postojeće dodatne istorijske linije se ne brišu.</p>
        </div>
        @error('product_type_ids')<p class="a1-error" id="br-err-types" role="alert">{{ $message }}</p>@enderror
        @error('line_names_by_type')<p class="a1-error" id="br-err-lines" role="alert">{{ $message }}</p>@enderror

        <div class="a1-pc-cards">
            @foreach($product_types as $type)
                @php
                    $typeId = (int)$type['id'];
                    $checked = in_array($typeId, $selectedTypeIds, true);
                    $existingLines = collect($formBrand['line_groups'][(string)$typeId] ?? [])->values();
                @endphp
                <article class="a1-fieldset a1-pc-brandtype" data-brand-type-card>
                    <label class="a1-check">
                        <input class="a1-check__input" type="checkbox" name="product_type_ids[]" value="{{ $typeId }}" @checked($checked) data-brand-type-toggle>
                        <span class="a1-check__box" aria-hidden="true"></span>
                        <span><strong>{{ $type['name'] }}</strong>@if($type['category_name'])<small>{{ $type['category_name'] }}</small>@endif</span>
                    </label>
                    <div class="a1-stack" data-brand-line-fields @if(!$checked) hidden @endif>
                        @for($slot=0; $slot<10; $slot++)
                            <label class="a1-field">
                                <span class="a1-label">Linija {{ $slot + 1 }}</span>
                                <input
                                    class="a1-input"
                                    name="line_names_by_type[{{ $typeId }}][]"
                                    maxlength="120"
                                    value="{{ old('line_names_by_type.'.$typeId.'.'.$slot, $existingLines->get($slot)['name'] ?? '') }}"
                                    placeholder="Opcionalno"
                                    @disabled(!$checked)
                                    data-brand-line-input
                                >
                            </label>
                        @endfor
                        @if($existingLines->count() > 10)
                            <small class="a1-help">Još {{ $existingLines->count() - 10 }} postojeće linije ostaju sačuvane i nisu predmet automatskog uklanjanja.</small>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="a1-actionbar">
            <p class="a1-actionbar__status"><strong>{{ $formBrand ? 'Sačuvaj izmene brenda' : 'Kreiraj brend' }}.</strong> Jedan globalni zapis · više dozvoljenih tipova · bez Product Variants.</p>
            <div class="a1-actionbar__buttons">
                <a class="a1-btn a1-btn--quiet" href="{{ route('admin.brand-manager.index') }}">Otkaži</a>
                <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj</button>
            </div>
        </div>
    </form>
</section>
@endif

<section class="a1-stack" aria-labelledby="br-list">
    <div>
        <h2 class="a1-h2" id="br-list">Globalni brendovi</h2>
        <p class="a1-muted">Naziv, povezani tipovi, linije, status i redosled na jednom mestu.</p>
    </div>
    <div class="a1-tablewrap" role="region" aria-label="Globalni brendovi" tabindex="0">
        <table class="a1-table a1-table--stack">
            <caption class="a1-sr">Globalni brendovi</caption>
            <thead><tr><th scope="col">Naziv</th><th scope="col">Povezani tipovi</th><th scope="col">Linije</th><th scope="col">Status</th><th scope="col" class="a1-table__num">Redosled</th><th scope="col">Akcija</th></tr></thead>
            <tbody>
            @forelse($brands as $brand)
                <tr>
                    <td class="a1-table__primary" data-label="Naziv">
                        <strong>{{ $brand['name'] }}</strong><code class="a1-pc-code a1-pc-sub">{{ $brand['slug'] }}</code>
                        @if($brand['website_url'])<a class="a1-link a1-link--inline" href="{{ $brand['website_url'] }}" target="_blank" rel="noopener noreferrer">Zvanični sajt</a>@endif
                    </td>
                    <td data-label="Povezani tipovi">
                        <span class="a1-pc-pills">@forelse($brand['product_types'] as $type)<span class="a1-pill a1-pill--ok">{{ $type['name'] }}</span>@empty — @endforelse</span>
                    </td>
                    <td data-label="Linije">
                        <strong>{{ count($brand['lines']) }}</strong>
                        @if(count($brand['lines']))<small class="a1-pc-sub">{{ collect($brand['lines'])->pluck('name')->take(6)->implode(', ') }}@if(count($brand['lines']) > 6)…@endif</small>@endif
                    </td>
                    <td data-label="Status"><span class="a1-pill {{ $brand['status'] === 'active' ? 'a1-pill--ok' : 'a1-pill--off' }}">{{ $brand['status'] === 'active' ? 'Aktivan' : 'Neaktivan' }}</span></td>
                    <td class="a1-table__num" data-label="Redosled">{{ $brand['sort_order'] }}</td>
                    <td class="a1-table__actions" data-label="Akcija"><a class="a1-btn a1-btn--sm" href="{{ route('admin.brand-manager.index', ['edit' => $brand['id'], 'q' => $filters['q'] ?? null, 'product_type_id' => $filters['product_type_id'] ?? null]) }}#brand-form" aria-label="Uredi brend {{ $brand['name'] }}">Uredi</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="a1-empty"><span class="a1-empty__icon"><x-icon name="search" size="28" /></span><p>Nema brendova za izabrane filtere.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
</div>

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const form = document.querySelector('[data-brand-manager-form]');
    if (!form) return;
    form.querySelectorAll('[data-brand-type-card]').forEach((card) => {
        const toggle = card.querySelector('[data-brand-type-toggle]');
        const fields = card.querySelector('[data-brand-line-fields]');
        if (!toggle || !fields) return;
        const sync = () => {
            fields.hidden = !toggle.checked;
            fields.querySelectorAll('[data-brand-line-input]').forEach((input) => { input.disabled = !toggle.checked; });
        };
        toggle.addEventListener('change', sync);
        sync();
    });
})();
</script>
@endpush
@endsection

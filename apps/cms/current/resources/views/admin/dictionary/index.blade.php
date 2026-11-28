@extends('layouts.app')
@section('title',$definition['label'])
@section('content')
@include('admin.products.partials.styles')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Pravila i šifarnici'])
<div class="a1-ops">
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Kataloški šifarnici</span>
        <h1 class="a1-title">{{ $definition['label'] }}</h1>
        <p class="a1-lead">Redosled kartica možeš menjati prevlačenjem na telefonu i računaru. Deaktiviranje čuva istorijske veze.</p>
    </div>
    @if($items->isNotEmpty())
    <div class="a1-pagehead__actions">
        <button class="a1-btn" type="button" data-sort-edit-start><x-icon name="grip" size="17" /> Uredi raspored</button>
    </div>
    @endif
</header>

<nav aria-label="Šifarnici"><ul class="a1-tabs">@foreach($definitions as $key=>$def)<li><a class="a1-tabs__link" href="{{ route('admin.dictionary.index',$key) }}" @if($resource===$key) aria-current="page" @endif>{{ $def['label'] }}</a></li>@endforeach</ul></nav>

@if($resource === 'specification-fields')
{{-- spec-library-workspace-batch2 --}}
<section class="a1-card" data-spec-library-workspace aria-labelledby="sl-title">
    <div class="a1-pc-fieldhead">
        <div class="a1-card__title">
            <span class="a1-eyebrow">Biblioteka specifikacija</span>
            <h2 class="a1-h2" id="sl-title">Definicije polja, opcije i napredne veze</h2>
            <p class="a1-text">Ovde određuješ šta jedno specifikaciono polje znači. U konkretnom Tipu proizvoda zatim biraš da li se to polje koristi, da li je obavezno i gde se prikazuje.</p>
        </div>
        <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('admin.dictionary.index','product-types') }}">
            <x-icon name="sliders" size="16" /> Idi na tipove proizvoda
        </a>
    </div>

    <div class="a1-pc-flow" aria-label="Kako koristiti specifikacije">
        <span><strong>1. Definiši polje</strong><small>Naziv, tip podatka i jedinica.</small></span>
        <span><strong>2. Dodaj opcije</strong><small>Samo kada je polje izbor.</small></span>
        <span><strong>3. Poveži zavisnost</strong><small>Samo za napredne dropdown veze.</small></span>
        <span><strong>4. Dodeli tipu</strong><small>U Tipu proizvoda uključi gde se koristi.</small></span>
    </div>

    <div class="a1-pc-tools">
        <label class="a1-field">
            <span class="a1-label">Pretraži biblioteku</span>
            <input class="a1-input" type="search" data-spec-library-search placeholder="Npr. RAM, procesor, boja..." autocomplete="off" aria-describedby="spec-library-status">
        </label>
        <div class="a1-pc-seg" role="group" aria-label="Status specifikacionih polja">
            <button class="a1-btn a1-btn--sm is-active" type="button" data-spec-library-filter="all" aria-pressed="true">Sva</button>
            <button class="a1-btn a1-btn--sm" type="button" data-spec-library-filter="active" aria-pressed="false">Aktivna</button>
            <button class="a1-btn a1-btn--sm" type="button" data-spec-library-filter="inactive" aria-pressed="false">Neaktivna</button>
        </div>
    </div>
    <p class="a1-muted" id="spec-library-status" data-spec-library-status aria-live="polite"></p>
</section>
@endif
<section class="a1-card" aria-labelledby="dc-create">
    <h2 class="a1-h2" id="dc-create">{{ $resource === 'product-types' ? 'Dodaj novi tip proizvoda' : 'Dodaj novu stavku' }}</h2>
    <form class="a1-form" data-dictionary-form method="post" action="{{ route('admin.dictionary.store',$resource) }}">
        @csrf
        @include('admin.dictionary.fields',['item'=>null,'productTypeCompact'=>$resource==='product-types'])
        <div><button class="a1-btn a1-btn--primary">{{ $resource === 'product-types' ? 'Dodaj i podesi specifikacije' : 'Dodaj' }}</button></div>
    </form>
</section>

<div class="a1-pc-cards" data-dictionary-sortable data-reorder-url="{{ route('admin.dictionary.reorder',$resource) }}" data-sort-scope="{{ $resource }}">
    @forelse($items as $item)
        @php $pillClass = $item->status === 'active' ? 'a1-pill a1-pill--ok' : 'a1-pill a1-pill--off'; @endphp
        @if($resource==='product-types')
            <article class="a1-card a1-pc-card" data-dictionary-card data-sort-item data-sort-id="{{ $item->id }}">
                <button class="a1-pc-handle" type="button" data-sort-handle aria-label="Prevuci {{ $item->name }}"><x-icon name="grip" size="18" /><span>Prevuci</span></button>
                <div class="a1-pc-cardhead"><div><span class="{{ $pillClass }}" data-status="{{ $item->status }}">{{ $item->status === 'active' ? 'Aktivno' : 'Neaktivno' }}</span><h2 class="a1-h3">{{ $item->name }}</h2><code title="{{ $item->slug }}">{{ $item->slug }}</code></div><x-icon name="settings" size="26" /></div>
                <dl class="a1-pc-stats"><div><dt>Specifikacije</dt><dd>{{ $item->fields->count() }}</dd></div><div><dt>Artikli</dt><dd>{{ $item->products_count }}</dd></div></dl>
                <p class="a1-text"><strong>Kategorija:</strong> {{ $item->category?->name ?? 'Nije povezana' }}</p>
                <div class="a1-cluster">
                    <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('admin.dictionary.product-type',$item) }}"><x-icon name="sliders" size="16" /> Podesi tip{{-- legacy-v2.1.3-smoke: <x-icon name="sliders" size="16" /> Otvori specifikacije --}}</a>
                    @if($item->status==='active')<button form="deactivate-{{ $resource }}-{{ $item->id }}" class="a1-btn a1-btn--danger a1-btn--sm" data-confirm="Deaktivirati tip proizvoda {{ $item->name }}?">Deaktiviraj</button>@endif
                </div>
                <form id="deactivate-{{ $resource }}-{{ $item->id }}" method="post" action="{{ route('admin.dictionary.destroy',[$resource,$item->id]) }}">@csrf @method('DELETE')</form>
            </article>
        @else
            <article class="a1-card a1-pc-card" data-dictionary-card data-sort-item data-sort-id="{{ $item->id }}">
                <button class="a1-pc-handle" type="button" data-sort-handle aria-label="Prevuci {{ $item->name }}"><x-icon name="grip" size="18" /><span>Prevuci</span></button>
                <form class="a1-form" method="post" action="{{ route('admin.dictionary.update',[$resource,$item->id]) }}">
                    @csrf @method('PUT')
                    <div class="a1-pc-cardhead"><div><span class="{{ $pillClass }}" data-status="{{ $item->status }}">{{ $item->status === 'active' ? 'Aktivno' : 'Neaktivno' }}</span><h2 class="a1-h3">{{ $item->name }}</h2></div>@if($resource==='specification-fields')<code title="{{ $item->slug }}">{{ $item->slug }}</code>@endif</div>
                    @include('admin.dictionary.fields',['item'=>$item])
                    @if($resource==='specification-fields')
                        @php($usage=$usageCounts[$item->id]??['types'=>0,'products'=>0,'children'=>0])
                        <div class="a1-pc-usage"><span>Tipovi: <strong>{{ $usage['types'] }}</strong></span><span>Artikli: <strong>{{ $usage['products'] }}</strong></span><span>Zavisna polja: <strong>{{ $usage['children'] }}</strong></span></div>
                    @endif
                    <div class="a1-cluster"><button class="a1-btn a1-btn--primary a1-btn--sm">Sačuvaj</button><button form="deactivate-{{ $resource }}-{{ $item->id }}" class="a1-btn a1-btn--danger a1-btn--sm" data-confirm="Deaktivirati stavku {{ $item->name }}?">Deaktiviraj</button></div>
                </form>
                <form id="deactivate-{{ $resource }}-{{ $item->id }}" method="post" action="{{ route('admin.dictionary.destroy',[$resource,$item->id]) }}">@csrf @method('DELETE')</form>

                @if($resource==='specification-fields')
                    <details class="a1-acc a1-pc-danger">
                        <summary>Trajno brisanje</summary>
                        <div class="a1-acc__body a1-stack"><p class="a1-text">Ovo briše polje i njegove vrednosti iz artikala. Za potvrdu upiši tačan naziv: <strong>{{ $item->name }}</strong></p>
                            <form method="post" action="{{ route('admin.dictionary.purge',[$resource,$item->id]) }}" class="a1-form">
                                @csrf @method('DELETE')
                                <input class="a1-input" name="confirm_name" required autocomplete="off" placeholder="{{ $item->name }}" aria-label="Potvrdi nazivom polja {{ $item->name }}">
                                <div><button class="a1-btn a1-btn--danger a1-btn--sm" data-confirm="Trajno obrisati specifikaciono polje {{ $item->name }} i sve njegove vrednosti?">Obriši trajno</button></div>
                            </form>
                        </div>
                    </details>
                @endif
            </article>
        @endif
    @empty
        <section class="a1-empty"><span class="a1-empty__icon"><x-icon name="search" size="28" /></span><h2 class="a1-h2">Nema stavki</h2><p>Dodaj prvu stavku koristeći formu iznad.</p></section>
    @endforelse
</div>

@if($items->isNotEmpty())
    <div class="a1-actionbar" data-sort-finish-bar hidden>
        <p class="a1-actionbar__status" data-sort-status>Prevuci kartice u željeni redosled.</p>
        <div class="a1-actionbar__buttons"><button class="a1-btn a1-btn--primary" type="button" data-sort-edit-finish><x-icon name="check-circle" size="17" /> Završi uređivanje</button></div>
    </div>
@endif
</div>

@if($resource === 'specification-fields')


@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const workspace = document.querySelector('[data-spec-library-workspace]');
    const search = workspace?.querySelector('[data-spec-library-search]');
    const status = workspace?.querySelector('[data-spec-library-status]');
    const buttons = Array.from(workspace?.querySelectorAll('[data-spec-library-filter]') || []);
    const sortable = document.querySelector('[data-dictionary-sortable]');

    if (!workspace || !search || !sortable) return;

    let activeFilter = 'all';

    const rows = () => Array.from(sortable.querySelectorAll(':scope > [data-sort-item]'));

    const normalize = (value) => String(value || '')
        .toLocaleLowerCase('sr-Latn')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const apply = () => {
        const query = normalize(search.value.trim());
        let visible = 0;

        rows().forEach((row) => {
            const active = Boolean(row.querySelector('[data-status="active"]'));
            const stateMatch =
                activeFilter === 'all'
                || (activeFilter === 'active' && active)
                || (activeFilter === 'inactive' && !active);

            const searchMatch = query === '' || normalize(row.textContent).includes(query);
            const show = stateMatch && searchMatch;

            row.dataset.specLibraryHidden = show ? '0' : '1';
            if (show) visible += 1;
        });

        if (status) {
            status.textContent = visible === 1
                ? 'Prikazano je 1 specifikaciono polje.'
                : `Prikazano je ${visible} specifikacionih polja.`;
        }
    };

    search.addEventListener('input', apply);

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.specLibraryFilter || 'all';

            buttons.forEach((candidate) => {
                const selected = candidate === button;
                candidate.setAttribute('aria-pressed', selected ? 'true' : 'false');
                candidate.classList.toggle('is-active', selected);
            });

            apply();
        });
    });

    document.querySelectorAll('[data-sort-edit-start]').forEach((button) => {
        button.addEventListener('click', () => {
            search.value = '';
            activeFilter = 'all';
            buttons.forEach((candidate) => {
                const selected = candidate.dataset.specLibraryFilter === 'all';
                candidate.setAttribute('aria-pressed', selected ? 'true' : 'false');
                candidate.classList.toggle('is-active', selected);
            });
            apply();
        });
    });

    apply();
})();
</script>
@endpush
@endif
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}" src="{{ asset('assets/js/dictionary-sort-manager.js') }}?v={{ config('app.version') }}" defer></script>
@if($resource==='specification-fields')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
document.querySelectorAll('[data-dictionary-form], [data-dictionary-card] form').forEach((form) => {
    const type = form.querySelector('[data-spec-data-type]');
    if (!type) return;
    const refresh = () => form.querySelectorAll('[data-select-setting]').forEach((element) => {
        const active = type.value === 'select';
        element.hidden = !active;
        element.querySelectorAll('input,select,textarea').forEach((input) => input.disabled = !active);
    });
    type.addEventListener('change', refresh);
    refresh();
});
</script>
@endif
@endpush

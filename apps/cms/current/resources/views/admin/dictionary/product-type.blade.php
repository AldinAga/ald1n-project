@extends('layouts.app')
@section('title','Specifikacije: '.$item->name)
@section('content')
@include('admin.products.partials.styles')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Pravila i šifarnici'])
<div class="a1-ops">
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Tip proizvoda</span>
        <h1 class="a1-title">Podešavanje tipa: {{ $item->name }}</h1>
{{-- legacy-v2.1.3-smoke: <h1>Specifikacije: {{ $item->name }}</h1> --}}
        <p class="a1-lead">Na jednom mestu odredi koja polja pripadaju ovom tipu, šta je obavezno i kojim redosledom se prikazuju. Napredne veze između opcija uređuju se odvojeno u Biblioteci specifikacija.</p>
    </div>
    <div class="a1-pagehead__actions">
        <a class="a1-btn" href="{{ route('admin.dictionary.index','product-types') }}"><x-icon name="chevron-left" size="17" /> Svi tipovi</a>
        <button class="a1-btn" type="button" data-sort-edit-start><x-icon name="grip" size="17" /> Uredi raspored</button>
    </div>
</header>

<nav aria-label="Šifarnici"><ul class="a1-tabs">@foreach($definitions as $key=>$def)<li><a class="a1-tabs__link" href="{{ route('admin.dictionary.index',$key) }}" @if($key==='product-types') aria-current="page" @endif>{{ $def['label'] }}</a></li>@endforeach</ul></nav>

<section class="a1-card" aria-label="Pregled tipa">
    <div class="a1-pc-facts">
        <div><span class="{{ $item->status === 'active' ? 'a1-pill a1-pill--ok' : 'a1-pill a1-pill--off' }}" data-status="{{ $item->status }}">{{ $item->status === 'active' ? 'Aktivno' : 'Neaktivno' }}</span><strong>{{ $item->products_count }} artikala koristi ovaj tip</strong></div>
        <div><span>Automatska kategorija</span><strong>{{ $item->category?->name ?? 'Nije povezana' }}</strong></div>
        <div><span>Aktivne specifikacije</span><strong>{{ $item->fields->count() }}</strong></div>
    </div>
</section>


{{-- product-type-workspace-batch2 --}}
<section class="a1-card" data-product-type-workspace aria-labelledby="pt-work">
    <div class="a1-pc-fieldhead">
        <div class="a1-card__title">
            <span class="a1-eyebrow">Radni prostor tipa</span>
            <h2 class="a1-h2" id="pt-work">Od tipa proizvoda do forme artikla</h2>
            <p class="a1-text">Radi redom: proveri osnovna pravila, izaberi polja, po potrebi otvori napredne veze i na kraju proveri pregled forme.</p>
        </div>
        <a class="a1-btn a1-btn--sm" href="{{ route('admin.dictionary.index','specification-fields') }}">
            <x-icon name="sliders" size="16" /> Biblioteka specifikacija
        </a>
    </div>

    <nav class="a1-pc-flow" aria-label="Koraci podešavanja tipa">
        <a href="#product-type-basic"><span class="a1-pc-flow__idx">1</span><strong>Osnovno</strong><small>naziv, kategorija i pravila</small></a>
        <a href="#product-type-fields"><span class="a1-pc-flow__idx">2</span><strong>Polja tipa</strong><small>uključi, zahtevaj i rasporedi</small></a>
        <a href="{{ route('admin.dictionary.index','specification-fields') }}"><span class="a1-pc-flow__idx">3</span><strong>Napredna pravila</strong><small>opcije i zavisnosti</small></a>
        <a href="#product-type-preview"><span class="a1-pc-flow__idx">4</span><strong>Pregled forme</strong><small>šta će korisnik popunjavati</small></a>
    </nav>

    <div class="a1-pc-tools" id="product-type-fields">
        <label class="a1-field">
            <span class="a1-label">Pronađi polje</span>
            <input
                class="a1-input"
                type="search"
                data-product-type-field-search
                placeholder="Npr. procesor, RAM, boja..."
                autocomplete="off"
                aria-describedby="product-type-field-status"
            >
        </label>

        <div class="a1-pc-seg" role="group" aria-label="Prikaži polja">
            <button class="a1-btn a1-btn--sm is-active" type="button" data-product-type-field-filter="all" aria-pressed="true">Sva</button>
            <button class="a1-btn a1-btn--sm" type="button" data-product-type-field-filter="enabled" aria-pressed="false">Uključena</button>
            <button class="a1-btn a1-btn--sm" type="button" data-product-type-field-filter="disabled" aria-pressed="false">Isključena</button>
        </div>

        <button class="a1-btn a1-btn--sm" type="button" data-product-type-advanced-toggle aria-pressed="false">
            Prikaži napredna podešavanja
        </button>
    </div>

    <div class="a1-pc-status" id="product-type-field-status" aria-live="polite">
        <strong data-product-type-enabled-count>0</strong>
        <span>uključenih polja</span>
        <span aria-hidden="true">·</span>
        <span data-product-type-visible-count>0</span>
        <span>trenutno prikazano</span>
    </div>

    <section class="a1-stack" id="product-type-preview" aria-labelledby="product-type-preview-title">
        <div class="a1-pc-fieldhead">
            <div>
                <span class="a1-eyebrow">Pregled forme</span>
                <h3 class="a1-h3" id="product-type-preview-title">Kako će izgledati specifikacije artikla</h3>
            </div>
            <span class="a1-pill a1-pill--ok" data-product-type-preview-count>0 polja</span>
        </div>
        <div class="a1-pc-preview" data-product-type-preview-list></div>
        <p class="a1-muted" data-product-type-preview-empty hidden>Uključi bar jedno specifikaciono polje da bi se prikazao pregled.</p>
    </section>
</section>
<form class="a1-form" data-product-type-settings-form id="product-type-basic" method="post" action="{{ route('admin.dictionary.update',['product-types',$item->id]) }}">
    @csrf @method('PUT')
    <section class="a1-card" aria-label="Podešavanja tipa">
    @include('admin.dictionary.fields',['resource'=>'product-types','item'=>$item,'productTypeCompact'=>false])
    </section>
    <div class="a1-actionbar">
        <p class="a1-actionbar__status"><strong>Sačuvaj podešavanja tipa.</strong> Promene polja mogu pokrenuti ponovni obračun kompletnosti artikala.</p>
        <div class="a1-actionbar__buttons"><button class="a1-btn a1-btn--primary"><x-icon name="check-circle" size="17" /> Sačuvaj sve izmene</button></div>
    </div>
</form>

<div class="a1-actionbar" data-sort-finish-bar hidden>
    <p class="a1-actionbar__status" data-sort-status>Prevuci kartice specifikacija u željeni redosled.</p>
    <div class="a1-actionbar__buttons"><button class="a1-btn a1-btn--primary" type="button" data-sort-edit-finish><x-icon name="check-circle" size="17" /> Završi uređivanje</button></div>
</div>
</div>



@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const workspace = document.querySelector('[data-product-type-workspace]');
    const form = document.querySelector('[data-product-type-settings-form]');
    if (!workspace || !form) return;

    const search = workspace.querySelector('[data-product-type-field-search]');
    const filterButtons = Array.from(workspace.querySelectorAll('[data-product-type-field-filter]'));
    const advancedToggle = workspace.querySelector('[data-product-type-advanced-toggle]');
    const enabledCount = workspace.querySelector('[data-product-type-enabled-count]');
    const visibleCount = workspace.querySelector('[data-product-type-visible-count]');
    const previewList = workspace.querySelector('[data-product-type-preview-list]');
    const previewCount = workspace.querySelector('[data-product-type-preview-count]');
    const previewEmpty = workspace.querySelector('[data-product-type-preview-empty]');
    const fieldContainer = form.querySelector('[data-template-field-table]');

    if (!fieldContainer) return;

    let activeFilter = 'all';
    let advanced = false;

    const rows = () => Array.from(fieldContainer.querySelectorAll('[data-template-field-row]'));

    const enabledInput = (row) =>
        Array.from(row.querySelectorAll('input[type="checkbox"]'))
            .find((input) => /\[enabled\]$/.test(input.name || '')) || null;

    const namedInput = (row, suffix) =>
        Array.from(row.querySelectorAll('input,select,textarea'))
            .find((input) => (input.name || '').endsWith(`[${suffix}]`)) || null;

    const fieldName = (row) => {
        const direct = row.querySelector('[data-field-name],.template-field-name,h3,h4,strong');
        if (direct?.textContent?.trim()) return direct.textContent.trim();

        const labels = Array.from(row.querySelectorAll('label>span'))
            .map((node) => node.textContent?.trim() || '')
            .filter(Boolean)
            .filter((value) => !['Obavezno','Filter','Sažetak','Naziv','Težina kompletnosti','Podrazumevana vrednost','Podrazumevani detalj'].includes(value));

        if (labels[0]) return labels[0];

        const text = (row.textContent || '')
            .replace(/\s+/g, ' ')
            .replace(/\b(Prevuci|Obavezno|Filter|Sažetak|Naziv)\b/g, '')
            .trim();

        return text.slice(0, 80) || 'Specifikaciono polje';
    };

    const normalize = (value) => String(value || '')
        .toLocaleLowerCase('sr-Latn')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const applyAdvancedVisibility = () => {
        const advancedSuffixes = [
            'show_in_summary',
            'include_in_name',
            'completeness_weight',
            'default_value',
            'default_detail',
        ];

        rows().forEach((row) => {
            advancedSuffixes.forEach((suffix) => {
                const input = namedInput(row, suffix);
                if (!input) return;
                const wrapper = input.closest('label') || input.parentElement;
                if (!wrapper) return;
                wrapper.dataset.workspaceAdvancedControl = '1';
                wrapper.hidden = !advanced;
            });
        });

        if (advancedToggle) {
            advancedToggle.setAttribute('aria-pressed', advanced ? 'true' : 'false');
            advancedToggle.textContent = advanced
                ? 'Sakrij napredna podešavanja'
                : 'Prikaži napredna podešavanja';
        }
    };

    const applyFilter = () => {
        const query = normalize(search?.value?.trim() || '');
        let visible = 0;
        let enabled = 0;

        rows().forEach((row) => {
            const checkbox = enabledInput(row);
            const isEnabled = Boolean(checkbox?.checked);
            if (isEnabled) enabled += 1;

            const statusMatch =
                activeFilter === 'all'
                || (activeFilter === 'enabled' && isEnabled)
                || (activeFilter === 'disabled' && !isEnabled);

            const searchMatch = query === '' || normalize(row.textContent).includes(query);
            const show = statusMatch && searchMatch;

            row.dataset.workspaceFiltered = show ? '0' : '1';
            if (show) visible += 1;
        });

        if (enabledCount) enabledCount.textContent = String(enabled);
        if (visibleCount) visibleCount.textContent = String(visible);
    };

    const renderPreview = () => {
        if (!previewList) return;

        const enabledRows = rows().filter((row) => enabledInput(row)?.checked);
        previewList.replaceChildren();

        enabledRows.forEach((row) => {
            const required = Boolean(namedInput(row, 'is_required')?.checked);
            const filterable = Boolean(namedInput(row, 'is_filterable')?.checked);

            const card = document.createElement('article');
            card.className = 'product-type-preview-field';

            const head = document.createElement('div');
            head.className = 'product-type-preview-field-head';

            const name = document.createElement('strong');
            name.textContent = fieldName(row);

            const tags = document.createElement('span');
            tags.className = 'product-type-preview-tags';

            if (required) {
                const requiredTag = document.createElement('span');
                requiredTag.textContent = 'Obavezno';
                tags.append(requiredTag);
            }

            if (filterable) {
                const filterTag = document.createElement('span');
                filterTag.textContent = 'Filter';
                tags.append(filterTag);
            }

            const control = document.createElement('div');
            control.className = 'product-type-preview-control';
            control.setAttribute('aria-hidden', 'true');

            head.append(name, tags);
            card.append(head, control);
            previewList.append(card);
        });

        if (previewCount) {
            previewCount.textContent = `${enabledRows.length} ${enabledRows.length === 1 ? 'polje' : 'polja'}`;
        }
        if (previewEmpty) previewEmpty.hidden = enabledRows.length !== 0;
    };

    const refresh = () => {
        applyAdvancedVisibility();
        applyFilter();
        renderPreview();
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.productTypeFieldFilter || 'all';

            filterButtons.forEach((candidate) => {
                const active = candidate === button;
                candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
                candidate.classList.toggle('is-active', active);
                candidate.classList.toggle('button-ghost', !active);
            });

            applyFilter();
        });
    });

    search?.addEventListener('input', applyFilter);

    advancedToggle?.addEventListener('click', () => {
        advanced = !advanced;
        applyAdvancedVisibility();
    });

    form.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement || target instanceof HTMLTextAreaElement)) return;
        if (!(target.name || '').startsWith('field_config[')) return;
        applyFilter();
        renderPreview();
    });

    document.querySelectorAll('[data-sort-edit-start]').forEach((button) => {
        button.addEventListener('click', () => {
            if (search) search.value = '';
            activeFilter = 'all';
            filterButtons.forEach((candidate) => {
                const active = candidate.dataset.productTypeFieldFilter === 'all';
                candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
                candidate.classList.toggle('is-active', active);
                candidate.classList.toggle('button-ghost', !active);
            });
            applyFilter();
        });
    });

    const observer = new MutationObserver(() => {
        applyFilter();
        renderPreview();
    });

    observer.observe(fieldContainer, { childList: true });

    refresh();
})();
</script>
@endpush
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}" src="{{ asset('assets/js/dictionary-sort-manager.js') }}?v={{ config('app.version') }}" defer></script>
@endpush

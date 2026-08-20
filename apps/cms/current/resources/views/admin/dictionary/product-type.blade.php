@extends('layouts.app')
@section('title','Specifikacije: '.$item->name)
@section('content')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Pravila i šifarnici'])
<div class="page-heading dictionary-page-heading">
    <div>
        <span class="eyebrow">Tip proizvoda</span>
        <h1>Podešavanje tipa: {{ $item->name }}</h1>
{{-- legacy-v2.1.3-smoke: <h1>Specifikacije: {{ $item->name }}</h1> --}}
        <p>Na jednom mestu odredi koja polja pripadaju ovom tipu, šta je obavezno i kojim redosledom se prikazuju. Napredne veze između opcija uređuju se odvojeno u Biblioteci specifikacija.</p>
    </div>
    <div class="page-heading-actions">
        <a class="button button-ghost" href="{{ route('admin.dictionary.index','product-types') }}"><x-icon name="chevron-left" size="17" /> Svi tipovi</a>
        <button class="button button-ghost" type="button" data-sort-edit-start><x-icon name="grip" size="17" /> Uredi raspored</button>
    </div>
</div>

<nav class="subnav">@foreach($definitions as $key=>$def)<a class="{{ $key==='product-types'?'active':'' }}" href="{{ route('admin.dictionary.index',$key) }}">{{ $def['label'] }}</a>@endforeach</nav>

<section class="panel product-type-overview">
    <div><span class="status-badge status-{{ $item->status }}">{{ $item->status === 'active' ? 'Aktivno' : 'Neaktivno' }}</span><strong>{{ $item->products_count }} artikala koristi ovaj tip</strong></div>
    <div><span>Automatska kategorija</span><strong>{{ $item->category?->name ?? 'Nije povezana' }}</strong></div>
    <div><span>Aktivne specifikacije</span><strong>{{ $item->fields->count() }}</strong></div>
</section>


{{-- product-type-workspace-batch2 --}}
<section class="panel product-type-workspace-shell" data-product-type-workspace>
    <div class="product-type-workspace-heading">
        <div>
            <span class="eyebrow">Radni prostor tipa</span>
            <h2>Od tipa proizvoda do forme artikla</h2>
            <p class="muted">Radi redom: proveri osnovna pravila, izaberi polja, po potrebi otvori napredne veze i na kraju proveri pregled forme.</p>
        </div>
        <a class="button button-ghost button-small" href="{{ route('admin.dictionary.index','specification-fields') }}">
            <x-icon name="sliders" size="16" /> Biblioteka specifikacija
        </a>
    </div>

    <nav class="product-type-workflow" aria-label="Koraci podešavanja tipa">
        <a href="#product-type-basic"><span>1</span><strong>Osnovno</strong><small>naziv, kategorija i pravila</small></a>
        <a href="#product-type-fields"><span>2</span><strong>Polja tipa</strong><small>uključi, zahtevaj i rasporedi</small></a>
        <a href="{{ route('admin.dictionary.index','specification-fields') }}"><span>3</span><strong>Napredna pravila</strong><small>opcije i zavisnosti</small></a>
        <a href="#product-type-preview"><span>4</span><strong>Pregled forme</strong><small>šta će korisnik popunjavati</small></a>
    </nav>

    <div class="product-type-workspace-tools" id="product-type-fields">
        <label class="product-type-field-search">
            <span>Pronađi polje</span>
            <input
                type="search"
                data-product-type-field-search
                placeholder="Npr. procesor, RAM, boja..."
                autocomplete="off"
                aria-describedby="product-type-field-status"
            >
        </label>

        <div class="product-type-filter-group" role="group" aria-label="Prikaži polja">
            <button class="button button-small is-active" type="button" data-product-type-field-filter="all" aria-pressed="true">Sva</button>
            <button class="button button-small button-ghost" type="button" data-product-type-field-filter="enabled" aria-pressed="false">Uključena</button>
            <button class="button button-small button-ghost" type="button" data-product-type-field-filter="disabled" aria-pressed="false">Isključena</button>
        </div>

        <button class="button button-small button-ghost" type="button" data-product-type-advanced-toggle aria-pressed="false">
            Prikaži napredna podešavanja
        </button>
    </div>

    <div class="product-type-workspace-status" id="product-type-field-status" aria-live="polite">
        <strong data-product-type-enabled-count>0</strong>
        <span>uključenih polja</span>
        <span aria-hidden="true">·</span>
        <span data-product-type-visible-count>0</span>
        <span>trenutno prikazano</span>
    </div>

    <section class="product-type-form-preview" id="product-type-preview" aria-labelledby="product-type-preview-title">
        <div class="product-type-preview-head">
            <div>
                <span class="eyebrow">Pregled forme</span>
                <h3 id="product-type-preview-title">Kako će izgledati specifikacije artikla</h3>
            </div>
            <span class="status-badge status-active" data-product-type-preview-count>0 polja</span>
        </div>
        <div class="product-type-preview-grid" data-product-type-preview-list></div>
        <p class="muted product-type-preview-empty" data-product-type-preview-empty hidden>Uključi bar jedno specifikaciono polje da bi se prikazao pregled.</p>
    </section>
</section>
<form class="panel form-section product-type-settings-form" id="product-type-basic" method="post" action="{{ route('admin.dictionary.update',['product-types',$item->id]) }}">
    @csrf @method('PUT')
    @include('admin.dictionary.fields',['resource'=>'product-types','item'=>$item,'productTypeCompact'=>false])
    <div class="sticky-save-bar product-type-save-bar"><div><strong>Sačuvaj podešavanja tipa</strong><span class="muted">Promene polja mogu pokrenuti ponovni obračun kompletnosti artikala.</span></div><button class="button button-primary"><x-icon name="check-circle" size="17" /> Sačuvaj sve izmene</button></div>
</form>

<div class="dictionary-sort-finish" data-sort-finish-bar hidden>
    <span data-sort-status>Prevuci kartice specifikacija u željeni redosled.</span>
    <button class="button button-primary" type="button" data-sort-edit-finish><x-icon name="check-circle" size="17" /> Završi uređivanje</button>
</div>



@push('scripts')
<script>
(() => {
    const workspace = document.querySelector('[data-product-type-workspace]');
    const form = document.querySelector('.product-type-settings-form');
    if (!workspace || !form) return;

    const search = workspace.querySelector('[data-product-type-field-search]');
    const filterButtons = Array.from(workspace.querySelectorAll('[data-product-type-field-filter]'));
    const advancedToggle = workspace.querySelector('[data-product-type-advanced-toggle]');
    const enabledCount = workspace.querySelector('[data-product-type-enabled-count]');
    const visibleCount = workspace.querySelector('[data-product-type-visible-count]');
    const previewList = workspace.querySelector('[data-product-type-preview-list]');
    const previewCount = workspace.querySelector('[data-product-type-preview-count]');
    const previewEmpty = workspace.querySelector('[data-product-type-preview-empty]');
    const fieldContainer = form.querySelector('.template-field-table');

    if (!fieldContainer) return;

    let activeFilter = 'all';
    let advanced = false;

    const rows = () => Array.from(fieldContainer.querySelectorAll('.template-field-row'));

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
<script src="{{ asset('assets/js/dictionary-sort-manager.js') }}?v={{ config('app.version') }}" defer></script>
@endpush

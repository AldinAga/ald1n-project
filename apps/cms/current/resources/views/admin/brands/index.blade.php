@extends('layouts.app')
@section('title','Brendovi')
@section('content')
{{-- MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3 --}}
@php
    $formBrand = $editingBrand ?? null;
    $showForm = $createMode || $formBrand !== null || $errors->any();
    $selectedTypeIds = collect(old('product_type_ids', $formBrand['product_type_ids'] ?? []))->map(fn($id)=> (int)$id)->all();
@endphp
<div class="page-heading">
    <div>
        <span class="eyebrow">Kataloški šifarnici</span>
        <h1>Brendovi</h1>
        <p>Jedan globalni brend može biti povezan sa više tipova proizvoda. Upravljaj zvaničnim podacima, tipovima i do tri kurirane linije po tipu bez dupliranja brendova.</p>
    </div>
    <div class="header-button-row">
        <a class="button button-primary" href="{{ route('admin.brand-manager.index', ['create' => 1]) }}">+ Dodaj brend</a>
        <a class="button button-ghost" href="{{ route('admin.dictionary.index','product-types') }}">Tipovi proizvoda</a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<section class="panel form-section">
    <div class="section-heading-row">
        <div>
            <h2>Pretraga i filter</h2>
            <p class="muted">Filter tipa je dinamički iz baze. Opcija „Svi“ prikazuje sve globalne brendove.</p>
        </div>
        <span class="count-pill">{{ count($brands) }} brendova</span>
    </div>
    <form method="get" action="{{ route('admin.brand-manager.index') }}" class="admin-form-grid">
        <label>
            <span>Pretraga brenda</span>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Naziv, opis ili website">
        </label>
        <label>
            <span>Tip / kategorija proizvoda</span>
            <select name="product_type_id">
                <option value="">Svi</option>
                @foreach($product_types as $type)
                    <option value="{{ $type['id'] }}" @selected((int)($filters['product_type_id'] ?? 0) === (int)$type['id'])>
                        {{ $type['name'] }}@if($type['category_name']) — {{ $type['category_name'] }}@endif
                    </option>
                @endforeach
            </select>
        </label>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Primeni filtere</button>
            <a class="button button-ghost" href="{{ route('admin.brand-manager.index') }}">Očisti</a>
        </div>
    </form>
</section>

@if($showForm)
<section class="panel form-section" id="brand-form">
    <div class="section-heading-row">
        <div>
            <span class="eyebrow">{{ $formBrand ? 'Uređivanje' : 'Novi brend' }}</span>
            <h2>{{ $formBrand ? $formBrand['name'] : 'Dodaj globalni brend' }}</h2>
            <p class="muted">Slug se generiše automatski. Uklanjanje veze sa tipom je blokirano ako je koristi bilo koji aktivni ili istorijski artikal.</p>
        </div>
        @if($formBrand)<code>{{ $formBrand['slug'] }}</code>@endif
    </div>

    <form method="post" action="{{ $formBrand ? route('admin.brand-manager.update',$formBrand['id']) : route('admin.brand-manager.store') }}" data-brand-manager-form>
        @csrf
        @if($formBrand) @method('PUT') @endif

        <div class="admin-form-grid">
            <label>
                <span>Naziv brenda</span>
                <input name="name" maxlength="120" required value="{{ old('name', $formBrand['name'] ?? '') }}" placeholder="npr. Samsung">
                @error('name')<small class="text-danger">{{ $message }}</small>@enderror
            </label>
            <label>
                <span>Zvanični website</span>
                <input type="url" name="website_url" maxlength="255" value="{{ old('website_url', $formBrand['website_url'] ?? '') }}" placeholder="https://www.example.com">
                @error('website_url')<small class="text-danger">{{ $message }}</small>@enderror
            </label>
            <label>
                <span>Status</span>
                <select name="status" required>
                    <option value="active" @selected(old('status', $formBrand['status'] ?? 'active') === 'active')>Aktivan</option>
                    <option value="inactive" @selected(old('status', $formBrand['status'] ?? 'active') === 'inactive')>Neaktivan</option>
                </select>
            </label>
            <label>
                <span>Redosled</span>
                <input type="number" min="0" max="1000000" name="sort_order" required value="{{ old('sort_order', $formBrand['sort_order'] ?? 100) }}">
            </label>
        </div>

        <label>
            <span>Kratak opis na srpskom</span>
            <textarea name="description" rows="4" maxlength="65000" placeholder="Kratak opis proizvođača i relevantnog proizvodnog programa.">{{ old('description', $formBrand['description'] ?? '') }}</textarea>
            @error('description')<small class="text-danger">{{ $message }}</small>@enderror
        </label>

        <div class="section-heading-row">
            <div>
                <h3>Povezani tipovi i linije</h3>
                <p class="muted">Izaberi tipove kojima brend realno pripada. Za svaki izabrani tip možeš uneti do tri kurirane linije. Postojeće dodatne istorijske linije se ne brišu.</p>
            </div>
        </div>
        @error('product_type_ids')<p class="text-danger">{{ $message }}</p>@enderror
        @error('line_names_by_type')<p class="text-danger">{{ $message }}</p>@enderror

        <div class="settings-grid">
            @foreach($product_types as $type)
                @php
                    $typeId = (int)$type['id'];
                    $checked = in_array($typeId, $selectedTypeIds, true);
                    $existingLines = collect($formBrand['line_groups'][(string)$typeId] ?? [])->values();
                @endphp
                <article class="dictionary-card" data-brand-type-card>
                    <label class="checkbox-row">
                        <input type="checkbox" name="product_type_ids[]" value="{{ $typeId }}" @checked($checked) data-brand-type-toggle>
                        <span><strong>{{ $type['name'] }}</strong>@if($type['category_name'])<small>{{ $type['category_name'] }}</small>@endif</span>
                    </label>
                    <div data-brand-line-fields @if(!$checked) hidden @endif>
                        @for($slot=0; $slot<3; $slot++)
                            <label>
                                <span>Linija {{ $slot + 1 }}</span>
                                <input
                                    name="line_names_by_type[{{ $typeId }}][]"
                                    maxlength="120"
                                    value="{{ old('line_names_by_type.'.$typeId.'.'.$slot, $existingLines->get($slot)['name'] ?? '') }}"
                                    placeholder="Opcionalno"
                                    @disabled(!$checked)
                                    data-brand-line-input
                                >
                            </label>
                        @endfor
                        @if($existingLines->count() > 3)
                            <small class="muted">Još {{ $existingLines->count() - 3 }} postojeće linije ostaju sačuvane i nisu predmet automatskog uklanjanja.</small>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="sticky-save-bar">
            <div>
                <strong>{{ $formBrand ? 'Sačuvaj izmene brenda' : 'Kreiraj brend' }}</strong>
                <span class="muted">Jedan globalni zapis · više dozvoljenih tipova · bez Product Variants.</span>
            </div>
            <div class="form-actions">
                <a class="button button-ghost" href="{{ route('admin.brand-manager.index') }}">Otkaži</a>
                <button class="button button-primary" type="submit">Sačuvaj</button>
            </div>
        </div>
    </form>
</section>
@endif

<section class="panel form-section">
    <div class="section-heading-row">
        <div>
            <h2>Globalni brendovi</h2>
            <p class="muted">Naziv, povezani tipovi, linije, status i redosled na jednom mestu.</p>
        </div>
    </div>
    <div class="admin-table-wrap flat-table">
        <table class="admin-table">
            <thead><tr><th>Naziv</th><th>Povezani tipovi</th><th>Linije</th><th>Status</th><th>Redosled</th><th>Akcija</th></tr></thead>
            <tbody>
            @forelse($brands as $brand)
                <tr>
                    <td data-label="Naziv">
                        <strong>{{ $brand['name'] }}</strong><br><code>{{ $brand['slug'] }}</code>
                        @if($brand['website_url'])<br><a href="{{ $brand['website_url'] }}" target="_blank" rel="noopener noreferrer">Zvanični sajt</a>@endif
                    </td>
                    <td data-label="Povezani tipovi">
                        @forelse($brand['product_types'] as $type)
                            <span class="status-badge status-active">{{ $type['name'] }}</span>
                        @empty — @endforelse
                    </td>
                    <td data-label="Linije">
                        <strong>{{ count($brand['lines']) }}</strong>
                        @if(count($brand['lines']))<br><small>{{ collect($brand['lines'])->pluck('name')->take(6)->implode(', ') }}@if(count($brand['lines']) > 6)…@endif</small>@endif
                    </td>
                    <td data-label="Status"><span class="status-badge status-{{ $brand['status'] }}">{{ $brand['status'] === 'active' ? 'Aktivan' : 'Neaktivan' }}</span></td>
                    <td data-label="Redosled">{{ $brand['sort_order'] }}</td>
                    <td data-label="Akcija"><div class="row-actions"><a class="button button-small button-ghost" href="{{ route('admin.brand-manager.index', ['edit' => $brand['id'], 'q' => $filters['q'] ?? null, 'product_type_id' => $filters['product_type_id'] ?? null]) }}#brand-form">Uredi</a></div></td>
                </tr>
            @empty
                <tr><td colspan="6">Nema brendova za izabrane filtere.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

@push('scripts')
<script>
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

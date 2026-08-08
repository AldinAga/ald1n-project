@extends('layouts.app')
@section('title',$definition['label'])
@section('content')
<div class="page-heading dictionary-page-heading">
    <div><span class="eyebrow">Kataloški šifarnici</span><h1>{{ $definition['label'] }}</h1><p>Redosled kartica možeš menjati prevlačenjem na telefonu i računaru. Deaktiviranje čuva istorijske veze.</p></div>
    @if($items->isNotEmpty())
        <button class="button button-ghost" type="button" data-sort-edit-start><x-icon name="grip" size="17" /> Uredi raspored</button>
    @endif
</div>

<nav class="subnav">@foreach($definitions as $key=>$def)<a class="{{ $resource===$key?'active':'' }}" href="{{ route('admin.dictionary.index',$key) }}">{{ $def['label'] }}</a>@endforeach</nav>

<section class="panel form-section dictionary-create-panel">
    <h2>{{ $resource === 'product-types' ? 'Dodaj novi tip proizvoda' : 'Dodaj novu stavku' }}</h2>
    <form class="dictionary-form" method="post" action="{{ route('admin.dictionary.store',$resource) }}">
        @csrf
        @include('admin.dictionary.fields',['item'=>null,'productTypeCompact'=>$resource==='product-types'])
        <button class="button button-primary">{{ $resource === 'product-types' ? 'Dodaj i podesi specifikacije' : 'Dodaj' }}</button>
    </form>
</section>

<div class="dictionary-grid dictionary-sortable" data-dictionary-sortable data-reorder-url="{{ route('admin.dictionary.reorder',$resource) }}" data-sort-scope="{{ $resource }}">
    @forelse($items as $item)
        @if($resource==='product-types')
            <article class="panel dictionary-card dictionary-sort-item product-type-summary-card" data-sort-item data-sort-id="{{ $item->id }}">
                <button class="dictionary-drag-handle" type="button" data-sort-handle aria-label="Prevuci {{ $item->name }}"><x-icon name="grip" size="18" /><span>Prevuci</span></button>
                <div class="product-type-summary-head"><div><span class="status-badge status-{{ $item->status }}">{{ $item->status === 'active' ? 'Aktivno' : 'Neaktivno' }}</span><h2>{{ $item->name }}</h2><code>{{ $item->slug }}</code></div><x-icon name="settings" size="26" /></div>
                <dl class="product-type-summary-stats"><div><dt>Specifikacije</dt><dd>{{ $item->fields->count() }}</dd></div><div><dt>Artikli</dt><dd>{{ $item->products_count }}</dd></div></dl>
                <p><strong>Kategorija:</strong> {{ $item->category?->name ?? 'Nije povezana' }}</p>
                <div class="card-actions">
                    <a class="button button-primary button-small" href="{{ route('admin.dictionary.product-type',$item) }}"><x-icon name="sliders" size="16" /> Otvori specifikacije</a>
                    @if($item->status==='active')<button form="deactivate-{{ $resource }}-{{ $item->id }}" class="button button-danger button-small" data-confirm="Deaktivirati tip proizvoda {{ $item->name }}?">Deaktiviraj</button>@endif
                </div>
                <form id="deactivate-{{ $resource }}-{{ $item->id }}" method="post" action="{{ route('admin.dictionary.destroy',[$resource,$item->id]) }}">@csrf @method('DELETE')</form>
            </article>
        @else
            <article class="panel dictionary-card dictionary-sort-item" data-sort-item data-sort-id="{{ $item->id }}">
                <button class="dictionary-drag-handle" type="button" data-sort-handle aria-label="Prevuci {{ $item->name }}"><x-icon name="grip" size="18" /><span>Prevuci</span></button>
                <form method="post" action="{{ route('admin.dictionary.update',[$resource,$item->id]) }}">
                    @csrf @method('PUT')
                    <div class="dictionary-card-heading"><div><span class="status-badge status-{{ $item->status }}">{{ $item->status === 'active' ? 'Aktivno' : 'Neaktivno' }}</span><h2>{{ $item->name }}</h2></div>@if($resource==='specification-fields')<code>{{ $item->slug }}</code>@endif</div>
                    @include('admin.dictionary.fields',['item'=>$item])
                    @if($resource==='specification-fields')
                        @php($usage=$usageCounts[$item->id]??['types'=>0,'products'=>0,'variants'=>0,'children'=>0])
                        <div class="spec-field-usage"><span>Tipovi: <strong>{{ $usage['types'] }}</strong></span><span>Artikli: <strong>{{ $usage['products'] }}</strong></span><span>Varijante: <strong>{{ $usage['variants'] }}</strong></span><span>Zavisna polja: <strong>{{ $usage['children'] }}</strong></span></div>
                    @endif
                    <div class="card-actions"><button class="button button-primary button-small">Sačuvaj</button><button form="deactivate-{{ $resource }}-{{ $item->id }}" class="button button-danger button-small" data-confirm="Deaktivirati stavku {{ $item->name }}?">Deaktiviraj</button></div>
                </form>
                <form id="deactivate-{{ $resource }}-{{ $item->id }}" method="post" action="{{ route('admin.dictionary.destroy',[$resource,$item->id]) }}">@csrf @method('DELETE')</form>

                @if($resource==='specification-fields')
                    <details class="dictionary-danger-zone">
                        <summary>Trajno brisanje</summary>
                        <div><p>Ovo briše polje i njegove vrednosti iz artikala i varijanti. Za potvrdu upiši tačan naziv: <strong>{{ $item->name }}</strong></p>
                            <form method="post" action="{{ route('admin.dictionary.purge',[$resource,$item->id]) }}">
                                @csrf @method('DELETE')
                                <input name="confirm_name" required autocomplete="off" placeholder="{{ $item->name }}">
                                <button class="button button-danger button-small" data-confirm="Trajno obrisati specifikaciono polje {{ $item->name }} i sve njegove vrednosti?">Obriši trajno</button>
                            </form>
                        </div>
                    </details>
                @endif
            </article>
        @endif
    @empty
        <section class="panel empty-state"><h2>Nema stavki</h2><p>Dodaj prvu stavku koristeći formu iznad.</p></section>
    @endforelse
</div>

@if($items->isNotEmpty())
    <div class="dictionary-sort-finish" data-sort-finish-bar hidden>
        <span data-sort-status>Prevuci kartice u željeni redosled.</span>
        <button class="button button-primary" type="button" data-sort-edit-finish><x-icon name="check-circle" size="17" /> Završi uređivanje</button>
    </div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('assets/js/dictionary-sort-manager.js') }}?v={{ config('app.version') }}" defer></script>
@if($resource==='specification-fields')
<script>
document.querySelectorAll('.dictionary-form, .dictionary-card form').forEach((form) => {
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

@extends('layouts.app')
@section('title','Specifikacije: '.$item->name)
@section('content')
<div class="page-heading dictionary-page-heading">
    <div>
        <span class="eyebrow">Tip proizvoda</span>
        <h1>Specifikacije: {{ $item->name }}</h1>
        <p>Svaki tip proizvoda ima svoju preglednu stranicu. Uredi polja, pravila i njihov redosled samo za ovaj tip.</p>
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

<form class="panel form-section product-type-settings-form" method="post" action="{{ route('admin.dictionary.update',['product-types',$item->id]) }}">
    @csrf @method('PUT')
    @include('admin.dictionary.fields',['resource'=>'product-types','item'=>$item,'productTypeCompact'=>false])
    <div class="sticky-save-bar product-type-save-bar"><div><strong>Sačuvaj podešavanja tipa</strong><span class="muted">Promene polja mogu pokrenuti ponovni obračun kompletnosti artikala.</span></div><button class="button button-primary"><x-icon name="check-circle" size="17" /> Sačuvaj sve izmene</button></div>
</form>

<div class="dictionary-sort-finish" data-sort-finish-bar hidden>
    <span data-sort-status>Prevuci kartice specifikacija u željeni redosled.</span>
    <button class="button button-primary" type="button" data-sort-edit-finish><x-icon name="check-circle" size="17" /> Završi uređivanje</button>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/dictionary-sort-manager.js') }}?v={{ config('app.version') }}" defer></script>
@endpush

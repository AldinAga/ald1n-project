@extends('layouts.app')
@section('title','Kloniraj artikal')
@section('content')
@include('admin.products.partials.styles')
<div class="a1-ops">
<nav aria-label="Putanja">
    <ol class="a1-crumbs">
        <li><a href="{{ route('admin.products.edit',$product) }}">Nazad na artikal</a></li>
        <li aria-current="page">Kloniraj</li>
    </ol>
</nav>
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Pametno upravljanje katalogom</span>
        <h1 class="a1-title">Kloniraj artikal</h1>
        <p class="a1-lead">{{ $product->name }} · {{ $product->sku }}</p>
    </div>
</header>
<form method="post" action="{{ route('admin.products.clone.store',$product) }}" class="a1-form">@csrf
<div class="a1-split">
<div class="a1-stack">
<section class="a1-card" aria-labelledby="cl-new">
    <h2 class="a1-h3" id="cl-new">Novi artikal</h2>
    <label class="a1-field"><span class="a1-label">Privremeni naziv novog artikla</span><input class="a1-input" name="name" maxlength="190" value="{{ old('name',$product->name.' — kopija') }}"><small class="a1-help">SKU se nikada ne kopira. Novi SKU će biti automatski generisan.</small></label>
    <label class="a1-check"><input type="hidden" name="regenerate_name" value="0"><input class="a1-check__input" type="checkbox" name="regenerate_name" value="1" @checked(old('regenerate_name'))><span class="a1-check__box" aria-hidden="true"></span><span>Formiraj naziv prema šablonu tipa artikla</span></label>
</section>
<section class="a1-card" aria-labelledby="cl-copy">
    <h2 class="a1-h3" id="cl-copy">Šta se kopira</h2>
    <div class="a1-pc-grid">
@foreach([
'copy_basic'=>'Tip, brend i linija proizvoda',
'copy_specifications'=>'Specifikacije i tačni modeli',
'copy_price'=>'Cena, valuta i provizija',
'copy_description'=>'Opis artikla',
'copy_notes'=>'Interne napomene',
'copy_images'=>'Slike proizvoda',
'copy_warranty_rules'=>'Posebna garancijska pravila proizvoda',
] as $name=>$label)<label class="a1-check"><input type="hidden" name="{{ $name }}" value="0"><input class="a1-check__input" type="checkbox" name="{{ $name }}" value="1" @checked(old($name,in_array($name,['copy_basic','copy_specifications','copy_price','copy_description'],true)))><span class="a1-check__box" aria-hidden="true"></span><span>{{ $label }}</span></label>@endforeach
    </div>
</section>
<section class="a1-card" aria-labelledby="cl-rules">
    <h2 class="a1-h3" id="cl-rules">Bezbednosna pravila kloniranja</h2>
    <ul class="a1-keylist"><li>Novi artikal uvek dobija novi SKU.</li><li>Lager novog artikla je uvek 0.</li><li>Status novog artikla je uvek Nacrt.</li><li>Rezervacije, serijski brojevi, prodaja i istorija se ne kopiraju.</li><li>Kategorija se automatski dodeljuje prema tipu proizvoda.</li></ul>
</section>
</div>
<aside class="a1-card" aria-labelledby="cl-source">
    <h2 class="a1-h3" id="cl-source">Pregled izvora</h2>
    <p class="a1-text"><strong>{{ $product->name }}</strong></p>
    <p class="a1-muted">{{ $product->brand?->name ?? 'Bez brenda' }} @if($product->line)· {{ $product->line->name }}@endif</p>
    <p class="a1-text">{{ number_format((float)$product->price_amount,2,',','.') }} {{ $product->price_currency }}</p>
    <p class="a1-muted">{{ $product->specificationValues->count() }} specifikacija · {{ $product->images->count() }} slika</p>
</aside>
</div>
<div class="a1-actionbar">
    <p class="a1-actionbar__status">Novi artikal se otvara kao nacrt sa lagerom 0.</p>
    <div class="a1-actionbar__buttons">
        <a class="a1-btn a1-btn--quiet" href="{{ route('admin.products.edit',$product) }}">Otkaži</a>
        <button class="a1-btn a1-btn--primary">Kloniraj kao novi artikal</button>
    </div>
</div>
</form>
</div>
@endsection

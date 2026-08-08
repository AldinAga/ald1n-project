@extends('layouts.app')
@section('title','Kloniraj artikal')
@section('content')
<a class="back-link" href="{{ route('admin.products.edit',$product) }}">← Nazad na artikal</a>
<div class="page-heading"><div><span class="eyebrow">Pametno upravljanje katalogom</span><h1>Kloniraj artikal</h1><p>{{ $product->name }} · {{ $product->sku }}</p></div></div>
<form method="post" action="{{ route('admin.products.clone.store',$product) }}">@csrf
<div class="admin-form-grid"><div class="form-main">
<section class="panel form-section"><h2>Novi artikal</h2><label><span>Privremeni naziv novog artikla</span><input name="name" maxlength="190" value="{{ old('name',$product->name.' — kopija') }}"><small class="muted">SKU se nikada ne kopira. Novi SKU će biti automatski generisan.</small></label><label class="check-card"><input type="hidden" name="regenerate_name" value="0"><input type="checkbox" name="regenerate_name" value="1" @checked(old('regenerate_name'))><span>Formiraj naziv prema šablonu tipa artikla</span></label></section>
<section class="panel form-section"><h2>Šta se kopira</h2><div class="check-grid">
@foreach([
'copy_basic'=>'Tip, brend i linija proizvoda',
'copy_specifications'=>'Specifikacije i tačni modeli',
'copy_price'=>'Cena, valuta i provizija',
'copy_description'=>'Opis artikla',
'copy_notes'=>'Interne napomene',
'copy_images'=>'Slike proizvoda',
'copy_warranty_rules'=>'Posebna garancijska pravila proizvoda',
'copy_variants'=>'Varijante, njihove specifikacije i cene (lager se ne kopira)',
] as $name=>$label)<label class="check-card"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" value="1" @checked(old($name,in_array($name,['copy_basic','copy_specifications','copy_price','copy_description'],true)))><span>{{ $label }}</span></label>@endforeach
</div></section>
<section class="panel form-section"><h2>Bezbednosna pravila kloniranja</h2><ul class="compact-list"><li>Novi artikal uvek dobija novi SKU.</li><li>Lager novog artikla je uvek 0.</li><li>Status novog artikla je uvek Nacrt.</li><li>Rezervacije, serijski brojevi, prodaja i istorija se ne kopiraju.</li><li>Kategorija se automatski dodeljuje prema tipu proizvoda.</li><li>Klonirane varijante dobijaju nove SKU oznake, status Nacrt i lager 0.</li></ul></section>
</div><aside class="form-side"><section class="panel form-section sticky-card"><h2>Pregled izvora</h2><p><strong>{{ $product->name }}</strong></p><p class="muted">{{ $product->brand?->name ?? 'Bez brenda' }} @if($product->line)· {{ $product->line->name }}@endif</p><p>{{ number_format((float)$product->price_amount,2,',','.') }} {{ $product->price_currency }}</p><p>{{ $product->specificationValues->count() }} specifikacija · {{ $product->images->count() }} slika · {{ $product->variants->count() }} varijanti</p><button class="button button-primary button-large">Kloniraj kao novi artikal</button></section></aside></div>
</form>
@endsection

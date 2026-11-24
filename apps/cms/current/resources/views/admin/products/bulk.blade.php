@extends('layouts.app')
@section('title','Bulk izmena artikala')
@section('content')
@include('admin.products.partials.styles')
<div class="a1-ops">
<nav aria-label="Putanja">
    <ol class="a1-crumbs">
        <li><a href="{{ route('catalog.index') }}">Nazad na artikle</a></li>
        <li aria-current="page">Bulk izmena</li>
    </ol>
</nav>
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Pametno upravljanje katalogom</span>
        <h1 class="a1-title">Bulk izmena artikala</h1>
        <p class="a1-lead">Promene se prvo pregledaju, a zatim posebno potvrđuju.</p>
    </div>
</header>
@if($selectedProducts->isEmpty())
<section class="a1-empty">
    <span class="a1-empty__icon"><x-icon name="search" size="28" /></span>
    <h2 class="a1-h2">Nema izabranih artikala</h2>
    <p>Vrati se na listu artikala, označi željene redove i klikni „Bulk izmena“.</p>
    <a class="a1-btn a1-btn--primary" href="{{ route('catalog.index') }}">Otvori listu artikala</a>
</section>
@else
<form method="post" action="{{ route('admin.products.bulk.process') }}" class="a1-form">@csrf
@foreach($selectedProducts as $selected)<input type="hidden" name="product_ids[]" value="{{ $selected->id }}">@endforeach
<div class="a1-stack">
<section class="a1-card" aria-labelledby="bk-selected">
    <div class="a1-card__title"><h2 class="a1-h3" id="bk-selected">Izabrani artikli</h2><p class="a1-muted">{{ $selectedProducts->count() }} artikala</p></div>
    <ul class="a1-chips">@foreach($selectedProducts as $selected)<li class="a1-chip"><strong>{{ $selected->sku }}</strong> {{ $selected->name }} <small>{{ $selected->completeness_percent }}%</small></li>@endforeach</ul>
</section>
<section class="a1-card" aria-labelledby="bk-basic">
    <h2 class="a1-h3" id="bk-basic">Osnovni podaci</h2>
    <div class="a1-pc-grid">
        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="apply_status" value="1" @checked(old('apply_status',$apply_status??false))><span class="a1-check__box" aria-hidden="true"></span><span>Promeni status</span></label>
        <label class="a1-field"><span class="a1-label">Novi status</span><select class="a1-select" name="status">@foreach(['draft'=>'Nacrt','active'=>'Aktivan','inactive'=>'Neaktivan'] as $v=>$l)<option value="{{ $v }}" @selected(old('status',$status??'draft')===$v)>{{ $l }}</option>@endforeach</select></label>
        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="apply_brand" value="1" @checked(old('apply_brand',$apply_brand??false))><span class="a1-check__box" aria-hidden="true"></span><span>Promeni brend</span></label>
        <label class="a1-field"><span class="a1-label">Brend</span><select class="a1-select" name="brand_id"><option value="">Bez brenda</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((int)old('brand_id',$brand_id??0)===$brand->id)>{{ $brand->name }}</option>@endforeach</select></label>
        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="apply_line" value="1" @checked(old('apply_line',$apply_line??false))><span class="a1-check__box" aria-hidden="true"></span><span>Promeni liniju</span></label>
        <label class="a1-field"><span class="a1-label">Linija</span><select class="a1-select" name="product_line_id"><option value="">Bez linije</option>@foreach($lines as $line)<option value="{{ $line->id }}" @selected((int)old('product_line_id',$product_line_id??0)===$line->id)>{{ $line->brand?->name }} · {{ $line->name }}</option>@endforeach</select></label>
        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="apply_type" value="1" @checked(old('apply_type',$apply_type??false))><span class="a1-check__box" aria-hidden="true"></span><span>Promeni tip / šablon</span></label>
        <label class="a1-field"><span class="a1-label">Tip artikla</span><select class="a1-select" name="product_type_id"><option value="">Bez tipa</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected((int)old('product_type_id',$product_type_id??0)===$type->id)>{{ $type->name }}@if($type->category) · {{ $type->category->name }}@endif</option>@endforeach</select><small class="a1-help">Kategorija se automatski dodeljuje prema tipu, a nepovezane specifikacije se uklanjaju.</small></label>
    </div>
</section>
<section class="a1-card" aria-labelledby="bk-price">
    <h2 class="a1-h3" id="bk-price">Cena</h2>
    <div class="a1-pc-grid">
        <label class="a1-field"><span class="a1-label">Operacija nad cenom</span><select class="a1-select" name="price_action"><option value="">Bez promene</option>@foreach(['set'=>'Postavi cenu','increase_percent'=>'Povećaj za %','decrease_percent'=>'Smanji za %','increase_fixed'=>'Povećaj za iznos','decrease_fixed'=>'Smanji za iznos'] as $v=>$l)<option value="{{ $v }}" @selected(old('price_action',$price_action??'')===$v)>{{ $l }}</option>@endforeach</select></label>
        <label class="a1-field"><span class="a1-label">Vrednost</span><input class="a1-input" type="number" step="0.01" min="0" name="price_value" value="{{ old('price_value',$price_value??'') }}"></label>
    </div>
</section>
<section class="a1-card" aria-labelledby="bk-specs">
    <h2 class="a1-h3" id="bk-specs">Specifikacije i nazivi</h2>
    <div class="a1-pc-grid">
        <label class="a1-field"><span class="a1-label">Operacija nad specifikacijom</span><select class="a1-select" name="specification_action"><option value="">Bez promene</option><option value="set" @selected(old('specification_action',$specification_action??'')==='set')>Postavi vrednost</option><option value="clear" @selected(old('specification_action',$specification_action??'')==='clear')>Ukloni vrednost</option></select></label>
        <label class="a1-field"><span class="a1-label">Polje</span><select class="a1-select" name="specification_field_id"><option value="">Izaberi</option>@foreach($fields as $field)<option value="{{ $field->id }}" @selected((int)old('specification_field_id',$specification_field_id??0)===$field->id)>{{ $field->name }} · {{ $field->data_type }}</option>@endforeach</select></label>
        <label class="a1-field"><span class="a1-label">Vrednost</span><input class="a1-input" name="specification_value" maxlength="1000" value="{{ old('specification_value',$specification_value??'') }}"></label>
        <label class="a1-field"><span class="a1-label">Dodatni detalj</span><input class="a1-input" name="specification_detail" maxlength="500" value="{{ old('specification_detail',$specification_detail??'') }}"></label>
        <label class="a1-check a1-pc-span2"><input class="a1-check__input" type="checkbox" name="regenerate_names" value="1" @checked(old('regenerate_names',$regenerate_names??false))><span class="a1-check__box" aria-hidden="true"></span><span>Regeneriši nazive prema šablonu nakon svih promena</span></label>
    </div>
</section>
@if(isset($preview))
<section class="a1-card" aria-labelledby="bk-preview">
    <h2 class="a1-h3" id="bk-preview">Pregled promena</h2>
    <p class="a1-text"><strong>{{ $preview['count'] }}</strong> artikala biće izmenjeno.</p>
    <ul class="a1-keylist">@foreach($preview['summary'] as $line)<li>{{ $line }}</li>@endforeach</ul>
    <ul class="a1-chips">@foreach($preview['products'] as $row)<li class="a1-chip">{{ $row['sku'] }} · {{ $row['name'] }}</li>@endforeach</ul>
</section>
@endif
</div>
{{-- Enter u polju pokreće prvi submit u formi: to je uvek pregled, nikad izvršenje. --}}
<div class="a1-actionbar">
    <p class="a1-actionbar__status">Prvo pregledaj tačan obim promena. Izvršenje se evidentira u audit logu za svaki artikal.</p>
    <div class="a1-actionbar__buttons">
        @if(isset($preview))
        <button class="a1-btn" name="mode" value="preview">Osveži pregled</button>
        <button class="a1-btn a1-btn--primary" name="mode" value="execute" data-confirm-submit="Potvrđuješ bulk izmenu {{ $preview['count'] }} artikala?">Izvrši potvrđene promene</button>
        @else
        <button class="a1-btn a1-btn--primary" name="mode" value="preview">Pregledaj promene</button>
        @endif
    </div>
</div>
</form>
@endif
</div>
@endsection

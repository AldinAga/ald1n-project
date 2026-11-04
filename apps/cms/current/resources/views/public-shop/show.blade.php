@extends('public-shop.layout')
@section('title',$product['name'])
@section('content')
<p><a href="{{ route('public-shop.catalog', [], false) }}">← Nazad na katalog</a></p>
<div class="shop-detail">
<div class="shop-detail-image">
@if($product['image'])<img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="eager">@else<span class="no-image">Fotografija nije dostupna</span>@endif
</div>
<div class="shop-detail-content">
<span class="eyebrow">{{ $product['brand'] }}</span>
<h1>{{ $product['name'] }}</h1>
<p class="sku">SKU {{ $product['sku'] }}</p>
<div class="shop-price">{{ $product['price_original'] }} {{ $product['currency'] }}</div>
<p>{{ $product['stock']>0 ? 'Dostupno' : 'Trenutno nema na lageru' }}</p>
@if($product['can_buy'])
<form class="shop-add-form" method="post" action="{{ route('public-shop.cart.add',$product['id'],false) }}">@csrf
<label>Količina <input type="number" name="quantity" value="1" min="1" max="{{ min(20,$product['stock']) }}" required></label>
<button type="submit">Dodaj u korpu</button>
</form>
@else<p>Poručivanje trenutno nije moguće za ovaj artikal.</p>@endif
<h2>Opis</h2><p class="shop-description">{{ $product['description'] }}</p>
@if($specifications->isNotEmpty())<h2>Specifikacije</h2><dl class="shop-specs">
@foreach($specifications as $s)<div><dt>{{ $s['label'] }}</dt><dd>{{ $s['value'] }}</dd></div>@endforeach
</dl>@endif
</div>
</div>
@endsection

@extends('public-shop.layout')
@section('title','Artikli')
@section('content')
<section class="shop-hero"><span class="eyebrow">Ponuda</span><h1>Pronađi svoj sledeći uređaj.</h1>
<p>Javno dostupni aktivni artikli. Cene su pune prodajne cene, bez internih provizija.</p></section>
<form class="shop-filters" method="get" action="{{ route('public-shop.catalog', [], false) }}">
<label>Pretraga <input name="q" type="search" maxlength="80" value="{{ request('q') }}" placeholder="Naziv ili SKU"></label>
<label>Brend <select name="brand_id"><option value="">Svi</option>@foreach($filters['brands'] as $brand)
<option value="{{ $brand->id }}" @selected((int)request('brand_id') === (int)$brand->id)>{{ $brand->name }}</option>
@endforeach</select></label>
<label>Kategorija <select name="category_id"><option value="">Sve</option>@foreach($filters['categories'] as $category)
<option value="{{ $category->id }}" @selected((int)request('category_id') === (int)$category->id)>{{ $category->name }}</option>
@endforeach</select></label>
<label>Sortiranje <select name="sort"><option value="newest">Najnovije</option><option value="name" @selected(request('sort')==='name')>Naziv</option></select></label>
<label class="shop-checkbox"><input type="checkbox" name="stock" value="available" @checked(request('stock')==='available')> Samo na lageru</label>
<button type="submit">Prikaži</button>
</form>
<div class="shop-results" role="status">{{ $page->total() }} artikala</div>
<div class="shop-products">
@forelse($page as $p)
<article class="shop-product">
<a class="shop-image" href="{{ route('public-shop.show',$p['slug'],false) }}">
@if($p['image'])<img src="{{ $p['image'] }}" alt="" loading="lazy" width="320" height="240">@else<span class="no-image">Fotografija nije dostupna</span>@endif
</a><div class="shop-product-body">
<span class="eyebrow">{{ $p['brand'] }}</span>
<h2><a href="{{ route('public-shop.show',$p['slug'],false) }}">{{ $p['name'] }}</a></h2>
<p class="sku">SKU {{ $p['sku'] }}</p>
<div class="shop-price">{{ $p['price_original'] }} {{ $p['currency'] }}</div>
<p class="availability">{{ $p['stock']>0 ? 'Na stanju' : 'Trenutno nedostupno' }}</p>
@if($p['can_buy'])<form method="post" action="{{ route('public-shop.cart.add',$p['id'],false) }}">@csrf
<input type="hidden" name="quantity" value="1"><button type="submit">Dodaj u korpu</button>
</form>@endif</div></article>
@empty
<p>Nema artikala koji odgovaraju filterima.</p>
@endforelse
</div>
<div class="shop-pages">{{ $page->links() }}</div>
@endsection

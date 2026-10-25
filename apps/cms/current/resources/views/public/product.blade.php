@extends('public.layout')
@section('title', $product->name)
@section('heading', $product->name)
@section('subtitle','SKU: '.$product->sku)
@section('content')
<div class="two">
 <section class="panel">
  @forelse($product->images as $image)
   <div class="image" style="height:min(450px,65vw);margin-bottom:12px">
   <img loading="lazy" alt="{{ $product->name }}" src="{{ $image->storage_disk === 'legacy' ? route('public.image',['image'=>$image->id]) : $image->display_url }}">
   </div>
  @empty<div class="image" style="height:300px">Slika nije dostupna</div>@endforelse
 </section>
 <section class="panel stack" style="align-content:start">
  <div><span class="small">{{ $product->brand?->name ?? 'Proizvod' }}</span><h2>{{ $product->name }}</h2></div>
  <strong class="price">{{ number_format($unitCents/100,2,',','.') }} RSD</strong>
  <p>Na lageru: {{ (int)$product->stock_quantity }} kom.</p>
  <form method="post" action="{{ route('public.cart.add',['product'=>$product->id]) }}" class="stack">@csrf
   <label for="quantity">Količina</label><input id="quantity" name="quantity" type="number" min="1" max="{{ min(10,(int)$product->stock_quantity) }}" value="1" required>
   <button class="button primary" type="submit">Dodaj u korpu</button>
  </form>
  <div class="divider"></div>
  <p style="white-space:pre-line">{{ $product->description }}</p>
  @if($product->categories->isNotEmpty())<p class="small">Kategorije: {{ $product->categories->pluck('name')->implode(', ') }}</p>@endif
  <a href="{{ route('public.catalog') }}">← Nazad u katalog</a>
 </section>
</div>
@endsection

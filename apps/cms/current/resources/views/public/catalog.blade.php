@extends('public.layout')
@section('title','Katalog')
@section('heading','Izaberi svoj uređaj')
@section('subtitle','Dostupni artikli, stvarne maloprodajne cene i bez obavezne registracije.')
@section('content')
<form action="{{ route('public.catalog') }}" method="get" class="toolbar" role="search">
 <div class="field"><label for="search">Pretraži naziv, model ili šifru</label><input id="search" name="q" maxlength="100" value="{{ $search }}" placeholder="Unesi naziv ili šifru"></div>
 <button class="button primary" type="submit">Pretraži</button>
</form>
@if($products->isEmpty())<section class="panel"><h2>Trenutno nema dostupnih proizvoda</h2><p>Proveri pretragu ili pokušaj ponovo kasnije.</p></section>@endif
<div class="grid">
@foreach($products as $product)
 <article class="card">
  <a class="image" href="{{ route('public.product',['slug'=>$product->slug]) }}" aria-label="Otvori {{ $product->name }}">
   @if($product->presentationImage)<img loading="lazy" alt="{{ $product->name }}" src="{{ $product->presentationImage->storage_disk === 'legacy' ? route('public.image',['image'=>$product->presentationImage->id]) : $product->presentationImage->display_url }}">@else<span aria-hidden="true">Bez slike</span>@endif
  </a>
  <span class="small">{{ $product->brand?->name ?? 'Računari i oprema' }} · {{ $product->sku }}</span>
  <a class="card-title grow" href="{{ route('public.product',['slug'=>$product->slug]) }}">{{ $product->name }}</a>
  <span class="small">Dostupno: {{ (int)$product->stock_quantity }}</span>
  <span class="price">{{ number_format(app(\App\Services\PublicRetailService::class)->retailUnitCents($product)/100,2,',','.') }} RSD</span>
  <a class="button" href="{{ route('public.product',['slug'=>$product->slug]) }}">Pogledaj proizvod</a>
 </article>
@endforeach
</div>
<div style="margin-top:26px">{{ $products->links() }}</div>
@endsection

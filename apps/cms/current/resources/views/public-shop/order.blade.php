@extends('public-shop.layout')
@section('title','Praćenje porudžbine')
@section('content')
<section class="shop-hero"><span class="eyebrow">Privatan pristup</span>
<h1>Porudžbina {{ $order->order_number }}</h1>
<p>Ovaj link omogućava pregled samo ove porudžbine. Nemoj ga deliti javno.</p></section>
<div class="shop-detail">
<section class="shop-detail-content">
<h2>Status kupovine</h2><p><strong>{{ match($order->status) {
'new'=>'Nova','processing'=>'U obradi','confirmed'=>'Potvrđena','shipped'=>'Poslata',
'cancelled'=>'Otkazana', default=>'U obradi'} }}</strong></p>
<p>Datum porudžbine: {{ $order->created_at?->format('d.m.Y H:i') }}</p>
<p>Uplaćeno: {{ number_format((float)($order->paid_total_rsd ?? 0),2,',','.') }} RSD</p>
<p>Status plaćanja: {{ $order->payment_state ?? 'unpaid' }}</p>
<h2>Proizvodi</h2>
@foreach($order->items as $item)
<div class="shop-cart-item"><div><strong>{{ $item->product_name }}</strong>
<p class="sku">{{ $item->product_sku }}</p></div><span>{{ $item->quantity }} × {{ number_format((float)$item->unit_price_rsd,2,',','.') }} RSD</span>
<strong>{{ number_format((float)$item->line_total_rsd,2,',','.') }} RSD</strong></div>
@endforeach
<p class="shop-price">Ukupno {{ number_format((float)$order->subtotal_rsd,2,',','.') }} RSD</p>
</section>
<section class="shop-detail-content">
<h2>Dostava i pošiljka</h2>
@if($retailPreference)
<p>Izabrana dostava: <strong>{{ $retailPreference['delivery_label'] }}</strong></p>
<p>Izabrano plaćanje: {{ $retailPreference['payment_label'] }}</p>
@if($retailPreference['payment_due_at'])<p>Rok plaćanja: {{ $retailPreference['payment_due_at'] }}</p>@endif
@endif
<p>Primalac: {{ $order->shipping_full_name }}</p>
<p>{{ $order->shipping_address }}, {{ $order->shipping_postal_code }} {{ $order->shipping_city }}</p>
@if($courier)<p>Kurirska služba: {{ $courier }}</p>@endif
@if($tracking)
<p>Broj pošiljke: <strong>{{ $tracking }}</strong></p>
@if($courierUrl)<p><a href="{{ $courierUrl }}" target="_blank" rel="noopener noreferrer nofollow">Prati pošiljku kod kurira</a></p>@endif
@else<p>Broj pošiljke još nije evidentiran.</p>@endif
@if($order->delivery?->delivered_at)<p>Isporučeno: {{ $order->delivery->delivered_at->format('d.m.Y') }}</p>@endif
@if($documents->isNotEmpty())
<h2>Dokumenta</h2>
<ul>@foreach($documents as $doc)
<li><a href="{{ route('public-shop.order.document',['token'=>$token,'document'=>$doc->id],false) }}" rel="noreferrer">{{ $doc->document_number }} ({{ $doc->document_type }})</a></li>
@endforeach</ul>
@endif
@if($warranties->isNotEmpty())
<h2>Garantni listovi</h2>
<ul>@foreach($warranties as $issuance)
<li><a href="{{ route('public-shop.order.warranty',['token'=>$token,'issuance'=>$issuance->id],false) }}" rel="noreferrer">{{ $issuance->warranty_number }}</a>
<span class="shop-muted">{{ $issuance->status==='issued_pending_delivery'?'Izdato — važenje počinje isporukom':'Aktivno' }}</span></li>
@endforeach</ul>
@endif
</section></div>
@endsection

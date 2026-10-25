@extends('public.layout')
@section('head')<meta name="robots" content="noindex,nofollow,noarchive"><meta name="referrer" content="no-referrer">@endsection
@section('title','Status porudžbine')
@section('heading','Porudžbina '.$order->order_number)
@section('subtitle','Sačuvaj ovaj privatni link. Dostupan je samo uz slučajni sigurnosni token i može biti opozvan.')
@section('content')
<div class="two"><section class="panel stack"><h2>Status porudžbine</h2>
<div class="row"><span>Broj porudžbine</span><strong>{{ $order->order_number }}</strong></div>
<div class="row"><span>Obrada</span><strong>{{ $order->status }}</strong></div>
<div class="row"><span>Plaćanje</span><strong>{{ $order->payment_state ?? $order->payment_status }}</strong></div>
<div class="row"><span>Ukupna cena</span><strong>{{ number_format((float)$order->subtotal_rsd,2,',','.') }} RSD</strong></div>
<div class="divider"></div><h2>Artikli</h2>
@foreach($order->items as $item)
<div class="list-item"><div class="long"><strong>{{ $item->product_name }}</strong><div class="small">{{ $item->product_sku }} · {{ $item->quantity }} kom.</div></div>
<strong>{{ number_format((float)$item->line_total_rsd,2,',','.') }} RSD</strong></div>
@endforeach
@if($order->payment_method === 'bank_transfer')
<h2>Podaci za uplatu</h2><p>Primalac: {{ $order->payment_recipient_name_snapshot }}<br>Račun: {{ $order->bank_account_number_display_snapshot ?? $order->bank_account_number_snapshot }}<br>Poziv na broj: {{ $order->payment_reference_snapshot }}</p>
@endif
</section><aside class="panel stack" style="align-content:start">
<h2>Pošiljka i dostava</h2>
@if($shipment)
<div class="row"><span>Kurirska služba</span><strong>{{ $shipment->courier_name_snapshot ?? $shipment->courier?->name ?? 'Nije navedena' }}</strong></div>
<div class="row"><span>Broj pošiljke</span><strong class="long">{{ $shipment->tracking_number_snapshot ?: 'Još nije evidentiran' }}</strong></div>
@if($trackingUrl && $shipment->tracking_number_snapshot)<a class="button primary" href="{{ $trackingUrl }}" target="_blank" rel="noopener noreferrer">Prati pošiljku</a>@endif
@else<p>Pošiljka još nije evidentirana. Status će biti ažuriran kada je trgovac pošalje.</p>@endif
<h2>Dokumenti</h2>
@if($documents->isNotEmpty())@foreach($documents as $document)
<a class="button" target="_blank" rel="noopener noreferrer" href="{{ route('public.order.document',['token'=>$token,'document'=>$document->id]) }}">{{ $document->document_number }} · PDF</a>
@endforeach@else<p class="small">Dokumenti još nisu izdati.</p>@endif
<div class="divider"></div><h2>Privatnost</h2>
<p class="small">Link ne deli javno. Možeš odmah opozvati pristup, ali ćeš time izgubiti ovaj način pregleda porudžbine.</p>
<form method="post" action="{{ route('public.order.revoke',['token'=>$token]) }}">@csrf<button class="button" type="submit">Opozovi privatni link</button></form>
</aside></div>
@endsection

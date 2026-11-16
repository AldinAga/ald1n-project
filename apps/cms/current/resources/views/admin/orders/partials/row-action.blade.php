{{-- One order-row action. $kind: accept | processing | confirmed | complete. $menu=true renders it as an item of the "Još" menu. --}}
@php($btn = ($menu ?? false) ? 'a1-nav-dd__item a1-nav-dd__item--button' : 'a1-btn a1-btn--sm')
@if($kind === 'accept')
    <form class="a1-ol-inline" method="post" action="{{ route('admin.orders.accept', $order) }}">
        @csrf
        <button class="{{ $btn }}" type="submit" aria-label="Preuzmi: {{ $order->order_number }}">Preuzmi</button>
    </form>
@elseif($kind === 'processing')
    <form class="a1-ol-inline" method="post" action="{{ route('admin.orders.status', $order) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="processing">
        <button class="{{ $btn }}" type="submit" aria-label="U obradu: {{ $order->order_number }}">U obradu</button>
    </form>
@elseif($kind === 'confirmed')
    <form class="a1-ol-inline" method="post" action="{{ route('admin.orders.status', $order) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="confirmed">
        <button class="{{ $btn }}" type="submit" aria-label="Potvrdi: {{ $order->order_number }}">Potvrdi</button>
    </form>
@else
    <a class="{{ ($menu ?? false) ? 'a1-nav-dd__item' : 'a1-btn a1-btn--sm' }}" href="{{ route('admin.orders.show', $order) }}#delivery-completion" aria-label="Evidentiraj isporuku: {{ $order->order_number }}">Evidentiraj isporuku</a>
@endif

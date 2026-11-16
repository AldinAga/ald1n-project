{{-- The one orders list of the dashboard: product, number and date, user and buyer, status, amount. Author: Ald1n <https://ald1n.com>
     With $stacked (right column under the brands) every row is two lines and the order number wraps, it is never clipped. --}}
@php $stacked = (bool) ($stacked ?? false); @endphp
<section class="a1-card{{ $stacked ? ' a1-lg-5 a1-orders-under' : '' }}" aria-labelledby="dash-recent-title">
    <div class="a1-card__head">
        <div class="a1-card__title"><span class="a1-eyebrow">Najnovije promene</span><h2 class="a1-h2" id="dash-recent-title">Poslednje porudžbine</h2></div>
        <a class="a1-link" href="{{ $orderIndex }}">Prikaži sve <x-icon name="arrow-right" :size="16" :shell="true" /></a>
    </div>
    @if(count($recentOrders) > 0)
        <ul class="a1-list a1-list--recent{{ $stacked ? ' a1-list--stacked' : '' }}">
            @foreach(array_slice($recentOrders, 0, 6) as $order)
                @php
                    $extra = $order['other_products'] > 0 ? ' + '.$order['other_products'] : '';
                    $who = $access['orders_manage'] ? ($order['is_direct_sale'] ? 'Direktna prodaja' : 'Korisnik: '.$order['seller_name']).' · Kupac: '.$order['buyer_name'] : '';
                    $note = $order['remaining_rsd'] > 0 ? 'Preostalo '.number_format((float) $order['remaining_rsd'], 2, ',', '.') : 'Plaćeno / zatvoreno';
                @endphp
                @if($stacked)
                {{-- Narrow column: a small card with full-width lines (name + chip, number + date, user and buyer, payment note + amount). --}}
                <li><a href="{{ $order['url'] }}" class="a1-item a1-item--order" data-order-product><span class="a1-ordercard__top"><span class="a1-ordercard__title" title="{{ $order['product_name'] }}{{ $extra }}">{{ $order['product_name'] }}@if($order['other_products'] > 0)<span data-order-extra-products="{{ $order['other_products'] }}"><span aria-hidden="true"> + {{ $order['other_products'] }}</span><span class="a1-sr"> Još stavki: {{ $order['other_products'] }}</span></span>@endif</span><span class="a1-pill a1-pill--{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span></span><span class="a1-ordercard__num"><span class="a1-ordercard__no">{{ $order['number'] }}</span><span class="a1-ordercard__date">· {{ $order['updated_at']?->format('d.m.Y H:i') }}</span></span>@if($who !== '')<span class="a1-ordercard__who" data-ellipsis title="{{ $who }}">{{ $who }}</span>@endif<span class="a1-ordercard__bottom"><span class="a1-ordercard__note">{{ $note }}</span><span class="a1-ordercard__amount"><span class="a1-item__money">{{ number_format((float) $order['total_rsd'], 2, ',', '.') }} RSD</span>@if($order['total_eur'] !== null)<span class="a1-item__eur">{{ number_format((float) $order['total_eur'], 2, ',', '.') }} EUR</span>@endif</span></span></a></li>
                @else
                <li><a href="{{ $order['url'] }}" class="a1-item a1-item--order" data-order-product><span class="a1-item__main"><span class="a1-item__title">{{ $order['product_name'] }}@if($order['other_products'] > 0)<span data-order-extra-products="{{ $order['other_products'] }}"><span aria-hidden="true"> + {{ $order['other_products'] }}</span><span class="a1-sr"> Još stavki: {{ $order['other_products'] }}</span></span>@endif</span><span class="a1-item__sub">{{ $order['number'] }} · {{ $order['updated_at']?->format('d.m.Y H:i') }}@if($who !== '') · {{ $who }}@endif</span></span><span class="a1-item__status"><span class="a1-pill a1-pill--{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span></span><span class="a1-item__end"><span class="a1-item__money">{{ number_format((float) $order['total_rsd'], 2, ',', '.') }} RSD</span>@if($order['total_eur'] !== null)<span class="a1-item__eur">{{ number_format((float) $order['total_eur'], 2, ',', '.') }} EUR</span>@endif <span class="a1-item__sub">{{ $note }}</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
                @endif
            @endforeach
        </ul>
    @else
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="orders" :size="28" :shell="true" /></span>
            <p>Još nema porudžbina za prikaz.</p>
        </div>
    @endif
</section>

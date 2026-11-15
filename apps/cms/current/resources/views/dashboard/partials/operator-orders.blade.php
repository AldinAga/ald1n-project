<section class="a1-card" data-operator-order-center aria-labelledby="operator-orders-title">
    <div class="a1-card__head">
        <div class="a1-card__title"><span class="a1-eyebrow">Operativni pregled korisnika</span><h2 class="a1-h2" id="operator-orders-title">Porudžbine korisnika</h2><p class="a1-text">Proizvodi, prodavac i krajnji kupac. Direktne prodaje nisu lične kupovine administratora.</p></div>
        <a class="a1-btn a1-btn--sm" href="{{ route('admin.orders.index') }}">Sve porudžbine</a>
    </div>
    @if(count($recentOrders) > 0)
        <ul class="a1-list">
            @foreach($recentOrders as $order)
                <li><a href="{{ $order['url'] }}" class="a1-item a1-item--order"><span class="a1-item__main"><span class="a1-item__title">{{ $order['product_name'] }}@if($order['other_products'] > 0) + {{ $order['other_products'] }}@endif</span><span class="a1-item__sub">{{ $order['is_direct_sale'] ? 'Direktna prodaja' : 'Korisnik: '.$order['seller_name'] }} · Kupac: {{ $order['buyer_name'] }}</span><span class="a1-item__sub">{{ $order['number'] }} · {{ $order['updated_at']?->format('d.m.Y H:i') }}</span></span><span class="a1-item__status"><span class="a1-pill a1-pill--{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span></span><span class="a1-item__end"><span class="a1-item__money">{{ number_format((float) $order['total_rsd'], 2, ',', '.') }} RSD</span><span class="a1-item__sub">{{ $order['remaining_rsd'] > 0 ? 'Preostalo '.number_format((float) $order['remaining_rsd'], 2, ',', '.').' RSD' : 'Plaćeno / zatvoreno' }}</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
            @endforeach
        </ul>
    @else
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="orders" :size="28" :shell="true" /></span>
            <p>Nema operativnih porudžbina korisnika.</p>
        </div>
    @endif
</section>

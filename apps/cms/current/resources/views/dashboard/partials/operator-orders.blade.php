<section class="panel dashboard-panel" data-operator-order-center aria-labelledby="operator-orders-title">
    <div class="dashboard-panel-heading">
        <div><span class="eyebrow">Operativni pregled korisnika</span><h2 id="operator-orders-title">Porudžbine korisnika</h2><p>Proizvodi, prodavac i krajnji kupac. Direktne prodaje nisu lične kupovine administratora.</p></div>
        <a class="button button-ghost" href="{{ route('admin.orders.index') }}">Sve porudžbine</a>
    </div>
    <div class="dashboard-order-list">
        @forelse($recentOrders as $order)
            <a href="{{ $order['url'] }}" class="dashboard-order-row">
                <div class="dashboard-order-product"><strong>{{ $order['product_name'] }}@if($order['other_products'] > 0) + {{ $order['other_products'] }}@endif</strong>
                    <small>{{ $order['is_direct_sale'] ? 'Direktna prodaja' : 'Korisnik: '.$order['seller_name'] }} · Kupac: {{ $order['buyer_name'] }}</small>
                    <small>{{ $order['number'] }} · {{ $order['updated_at']?->format('d.m.Y H:i') }}</small>
                </div>
                <span class="dashboard-order-status tone-{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span>
                <div class="dashboard-order-money"><strong>{{ number_format((float) $order['total_rsd'], 2, ',', '.') }} RSD</strong><small>{{ $order['remaining_rsd'] > 0 ? 'Preostalo '.number_format((float) $order['remaining_rsd'], 2, ',', '.').' RSD' : 'Plaćeno / zatvoreno' }}</small></div>
                <x-icon name="arrow-right" size="16" />
            </a>
        @empty
            <div class="dashboard-empty compact"><p>Nema operativnih porudžbina korisnika.</p></div>
        @endforelse
    </div>
</section>

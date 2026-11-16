{{-- SubAgent dashboard: own commission by status and own orders for the period. No purchase prices, no company profit, nobody else's data.
     RSD is the main amount, EUR underneath (commissions are stored in EUR; RSD uses the rate stored on each order). Author: Ald1n <https://ald1n.com> --}}
@if(is_array($subAgent ?? null))
<section class="a1-card" data-subagent-dashboard aria-labelledby="sa-earn-title">
    <div class="a1-card__head">
        <div class="a1-card__title"><span class="a1-eyebrow">Moja zarada</span><h2 class="a1-h2" id="sa-earn-title">Moja provizija</h2><p class="a1-text">{{ $subAgent['period_label'] }} · po datumu porudžbine</p></div>
        @if($access['commissions_own'] ?? false)<a class="a1-link" href="{{ route('commissions.index') }}">Sve provizije <x-icon name="arrow-right" :size="16" :shell="true" /></a>@endif
    </div>
    <div class="a1-kpis">
        @foreach(['pending' => 'Na čekanju', 'approved' => 'Za isplatu', 'paid' => 'Isplaćeno'] as $status => $label)
            <div class="a1-kpi" data-subagent-commission="{{ $status }}">
                <span class="a1-kpi__label">{{ $label }}</span>
                <span class="a1-kpi__value a1-num">{{ number_format((float) $subAgent['commission'][$status]['rsd'], 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span>
                <span class="a1-kpi__eur">{{ number_format((float) $subAgent['commission'][$status]['eur'], 2, ',', '.') }} EUR</span>
            </div>
        @endforeach
    </div>
</section>
<section class="a1-card" data-subagent-orders aria-labelledby="sa-orders-title">
    <div class="a1-card__head">
        <div class="a1-card__title"><span class="a1-eyebrow">Moje porudžbine</span><h2 class="a1-h2" id="sa-orders-title">{{ number_format((int) $subAgent['orders_count'], 0, ',', '.') }} porudžbina u periodu</h2><p class="a1-text">Vrednost <b data-subagent-orders-value>{{ number_format((float) $subAgent['orders_value_rsd'], 2, ',', '.') }} RSD</b> · {{ number_format((float) $subAgent['orders_value_eur'], 2, ',', '.') }} EUR · bez otkazanih</p></div>
        <a class="a1-link" href="{{ route('orders.index') }}">Prikaži sve <x-icon name="arrow-right" :size="16" :shell="true" /></a>
    </div>
    @if(count($recentOrders) > 0)
        <ul class="a1-list a1-list--recent">
            @foreach(array_slice($recentOrders, 0, 5) as $order)
                <li><a href="{{ $order['url'] }}" class="a1-item a1-item--order"><span class="a1-item__main"><span class="a1-item__title">{{ $order['product_name'] }}@if($order['other_products'] > 0) + {{ $order['other_products'] }}@endif</span><span class="a1-item__sub">{{ $order['number'] }} · {{ $order['updated_at']?->format('d.m.Y H:i') }}</span></span><span class="a1-item__status"><span class="a1-pill a1-pill--{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span></span><span class="a1-item__end"><span class="a1-item__money">{{ number_format((float) $order['total_rsd'], 2, ',', '.') }} RSD</span>@if($order['total_eur'] !== null)<span class="a1-item__eur">{{ number_format((float) $order['total_eur'], 2, ',', '.') }} EUR</span>@endif</span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
            @endforeach
        </ul>
    @else
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="orders" :size="28" :shell="true" /></span><p>Još nema porudžbina za prikaz.</p></div>
    @endif
</section>
@endif

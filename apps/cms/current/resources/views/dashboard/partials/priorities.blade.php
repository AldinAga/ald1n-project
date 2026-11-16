{{-- "Fokus danas": what needs attention now. A full-width compact strip for the superadmin (right under the header band) and a card in the business layout. Author: Ald1n <https://ald1n.com> --}}
@php $strip = (bool) ($strip ?? false); @endphp
<section class="a1-card {{ $strip ? 'a1-strip' : ($span ?? '') }}" aria-labelledby="d1-priority-heading">
    <div class="a1-card__head">
        <div class="a1-card__title"><span class="a1-eyebrow">Fokus danas</span><h2 class="a1-h2" id="d1-priority-heading">Prioritetne aktivnosti</h2><p class="{{ $strip ? 'a1-sr' : 'a1-text' }}">{{ $isSuper ? 'Operativne stavke koje zahtevaju reakciju.' : 'Operativne stavke koje trenutno zahtevaju reakciju.' }}</p></div>
        <span class="a1-count">{{ count($priorityActions) }}</span>
    </div>
    @if(count($priorityActions) > 0)
        <ul class="a1-list{{ $strip ? ' a1-list--strip' : '' }}">
            @foreach($priorityActions as $item)
                <li><a href="{{ $item['url'] }}" class="a1-item a1-item--count a1-item--compact"><span class="a1-item__icon a1-item__icon--{{ $item['tone'] }}"><x-icon :name="$item['icon']" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">{{ $item['title'] }}</span><span class="a1-item__sub">{{ $item['description'] }}</span></span><span class="a1-item__count">{{ is_float($item['count']) ? number_format($item['count'], 0, ',', '.') : $item['count'] }}</span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
            @endforeach
        </ul>
    @elseif($strip)
        <p class="a1-strip__empty"><x-icon name="check-circle" :size="20" :shell="true" /><strong>Nema hitnih stavki</strong><span>Sve je pod kontrolom, nema ničega za hitnu reakciju.</span></p>
    @else
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="check-circle" :size="28" :shell="true" /></span>
            <h3 class="a1-h3">Nema hitnih stavki</h3>
            <p>Sve je pod kontrolom, nema ničega za hitnu reakciju.</p>
        </div>
    @endif
</section>

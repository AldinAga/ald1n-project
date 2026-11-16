{{-- Admin orders lists pager (Module 3). Same ?page= parameter and query string as the framework view. Author: Ald1n <https://ald1n.com> --}}
@if($paginator->hasPages())
<nav class="a1-pager" aria-label="Straničenje">
    <p class="a1-pager__status">Strana {{ $paginator->currentPage() }} od {{ $paginator->lastPage() }}</p>
    <ul class="a1-pager__list">
        @if($paginator->onFirstPage())
            <li><span class="a1-page" aria-disabled="true"><x-icon name="chevron-left" :size="18" :shell="true" /> Prethodna</span></li>
        @else
            <li><a class="a1-page" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-icon name="chevron-left" :size="18" :shell="true" /> Prethodna</a></li>
        @endif
        @foreach($elements as $element)
            @if(is_string($element))
                <li><span class="a1-page" aria-hidden="true">…</span></li>
            @endif
            @if(is_array($element))
                @foreach($element as $number => $url)
                    @if($number === $paginator->currentPage())
                        <li><span class="a1-page" aria-current="page"><span class="a1-sr">Strana </span>{{ $number }}</span></li>
                    @else
                        <li><a class="a1-page" href="{{ $url }}"><span class="a1-sr">Strana </span>{{ $number }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if($paginator->hasMorePages())
            <li><a class="a1-page" href="{{ $paginator->nextPageUrl() }}" rel="next">Sledeća <x-icon name="chevron-right" :size="18" :shell="true" /></a></li>
        @else
            <li><span class="a1-page" aria-disabled="true">Sledeća <x-icon name="chevron-right" :size="18" :shell="true" /></span></li>
        @endif
    </ul>
</nav>
@endif

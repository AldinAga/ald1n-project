{{-- Public catalog pagination (scriptless, relative links). Author: Ald1n <https://ald1n.com> --}}
@if($paginator->hasPages())
<nav class="shop-pagination" aria-label="Stranice kataloga">
<p class="shop-pagination-status">Strana {{ $paginator->currentPage() }} od {{ $paginator->lastPage() }}</p>
<ul>
@if($paginator->onFirstPage())
<li><span class="shop-page shop-page-step is-disabled" aria-disabled="true"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Prethodna</span></span></li>
@else
<li><a class="shop-page shop-page-step" href="{{ $paginator->previousPageUrl() }}" rel="prev"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Prethodna</span></a></li>
@endif
@foreach($elements as $element)
@if(is_string($element))
<li class="shop-page-gap"><span class="shop-page is-gap" aria-hidden="true">…</span></li>
@endif
@if(is_array($element))
@foreach($element as $number => $url)
@if($number === $paginator->currentPage())
<li class="shop-page-number"><span class="shop-page is-current" aria-current="page"><span class="sr-only">Strana </span>{{ $number }}</span></li>
@else
<li class="shop-page-number"><a class="shop-page" href="{{ $url }}"><span class="sr-only">Strana </span>{{ $number }}</a></li>
@endif
@endforeach
@endif
@endforeach
@if($paginator->hasMorePages())
<li><a class="shop-page shop-page-step" href="{{ $paginator->nextPageUrl() }}" rel="next"><span>Sledeća</span><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
@else
<li><span class="shop-page shop-page-step is-disabled" aria-disabled="true"><span>Sledeća</span><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span></li>
@endif
</ul>
</nav>
@endif

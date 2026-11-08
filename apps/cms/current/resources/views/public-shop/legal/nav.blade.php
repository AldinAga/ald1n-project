{{-- Legal pages side navigation. Author: Ald1n <https://ald1n.com> --}}
<nav class="legal-nav" aria-label="Pravne stranice">
<a href="{{ route('legal.index', [], false) }}" @if($current === null) aria-current="page" @endif>Pregled</a>
@foreach($pages as $key => $page)
<a href="{{ route('legal.'.$key, [], false) }}" @if($current === $key) aria-current="page" @endif>{{ $page['title'] }}</a>
@endforeach
</nav>

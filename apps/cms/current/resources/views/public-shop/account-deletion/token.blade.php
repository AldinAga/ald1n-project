{{-- Potvrda ili poništavanje brisanja naloga (POST, ne GET). Author: Ald1n <https://ald1n.com> --}}
@extends('public-shop.layout')
@section('title', $mode === 'confirm' ? 'Potvrda brisanja naloga' : 'Poništavanje brisanja naloga')
@push('head')
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
@endpush
@section('content')
<div class="legal-shell">
<article class="legal-article">
<span class="eyebrow">Nalog i podaci</span>
@if(!$valid)
<h1>Link nije važeći</h1>
<p class="legal-note">Link je istekao ili je već iskorišćen. <a href="{{ route('account-deletion.show', [], false) }}">Brisanje naloga</a></p>
@elseif($mode === 'confirm')
<h1>Potvrdite brisanje naloga</h1>
<p>Posle potvrde nalog se odmah blokira i odjavljuje sa svih uređaja. Konačno brisanje sledi posle {{ $graceDays }} dana, a u tom roku možete ga poništiti.</p>
<form method="post" action="{{ route('account-deletion.confirm', [], false) }}">
@csrf
<input type="hidden" name="token" value="{{ $token }}">
<button class="reg-submit" type="submit"><span>Potvrdi brisanje naloga</span></button>
</form>
@else
<h1>Poništite brisanje naloga</h1>
<p>Nalog će ponovo postati aktivan i moći ćete da se prijavite.</p>
<form method="post" action="{{ route('account-deletion.cancel', [], false) }}">
@csrf
<input type="hidden" name="token" value="{{ $token }}">
<button class="reg-submit" type="submit"><span>Poništi brisanje</span></button>
</form>
@endif
</article>
</div>
@endsection

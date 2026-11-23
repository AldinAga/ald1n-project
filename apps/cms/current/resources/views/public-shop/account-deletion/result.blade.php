{{-- Ishod potvrde ili poništavanja brisanja naloga. Author: Ald1n <https://ald1n.com> --}}
@extends('public-shop.layout')
@section('title', $title)
@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush
@section('content')
<div class="legal-shell">
<article class="legal-article">
<span class="eyebrow">Nalog i podaci</span>
<h1>{{ $title }}</h1>
<p class="legal-note" role="status">{{ $message }}</p>
<p><a href="{{ route('account-deletion.show', [], false) }}">Brisanje naloga</a></p>
</article>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Uklanjanje korisnika — pregled posledica')
@section('content')
<section class="panel" style="max-width:760px;margin:2rem auto;padding:2rem">
<h1>Bezbedno uklanjanje naloga</h1>
<p><strong>{{ $target->displayName() }}</strong> — ovaj postupak deaktivira pristup, prenosi porudžbine na aktivnog SuperAdministratora i čuva poslovnu istoriju. Nalog se ne briše fizički.</p>
<dl>
<dt>Porudžbine za prenos</dt><dd>{{ $impact['orders'] }}</dd>
<dt>Provizije</dt><dd>{{ $impact['commissions'] }}</dd>
<dt>Garancije</dt><dd>{{ $impact['warranties'] }}</dd>
<dt>Razgovori</dt><dd>{{ $impact['conversations'] }}</dd>
<dt>Dodeljivanja</dt><dd>{{ $impact['assignments'] }}</dd>
</dl>
@if($impact['commissions'] || $impact['warranties'] || $impact['conversations'] || $impact['assignments'])
<p role="alert">Ovaj nalog ima dodatne poslovne relacije. Uklanjanje je zaustavljeno dok se ne potvrdi bezbedno očuvanje svih evidencija.</p>
@else
<form method="post" action="{{ route('admin.users.retirement.store', $target) }}">
@csrf
<label>Za potvrdu upiši OBRISI: <input name="confirmation" required pattern="OBRISI" autocomplete="off"></label>
<button class="button button-primary" type="submit">Deaktiviraj nalog i prenesi porudžbine</button>
</form>
@endif
<a href="{{ route('admin.users.index') }}">Nazad na korisnike</a>
</section>
@endsection

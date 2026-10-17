@extends('layouts.app')
@section('title', 'Bezbedno uklanjanje korisnika — pregled posledica')
@section('content')
<section class="panel" style="max-width:850px;margin:2rem auto;padding:2rem">
    <h1>Bezbedno uklanjanje naloga</h1>
    <p>
        <strong>{{ $target->displayName() }}</strong> · {{ $target->username }}.
        Nalog će biti trajno deaktiviran i uklonjen iz operativnog spiska korisnika.
        Korisnički zapis i poslovna istorija <strong>ne brišu se fizički</strong>.
    </p>

    <h2>Istorija koja ostaje nepromenjena</h2>
    <dl>
        <dt>Provizije</dt><dd>{{ $impact['commissions'] }}</dd>
        <dt>Garancije</dt><dd>{{ $impact['warranties'] }}</dd>
        <dt>Korisnički razgovori</dt><dd>{{ $impact['conversations'] }}</dd>
        <dt>Istorijske dodele dobavljača</dt><dd>{{ $impact['assignments'] }}</dd>
    </dl>

    <h2>Aktivna zaduženja koja se prenose na SuperAdministratora</h2>
    <dl>
        <dt>Porudžbine — vlasništvo</dt><dd>{{ $impact['orders'] }}</dd>
        <dt>Porudžbine — odgovorni dobavljač</dt><dd>{{ $impact['supplier_orders'] }}</dd>
        <dt>Reklamacije</dt><dd>{{ $impact['after_sales_cases'] }}</dd>
        <dt>Postprodajne radnje</dt><dd>{{ $impact['after_sales_actions'] }}</dd>
        <dt>Aktivni razgovori</dt><dd>{{ $impact['portal_assignments'] }}</dd>
        <dt>Predmeti potraživanja</dt><dd>{{ $impact['receivable_cases'] }}</dd>
    </dl>
    <p role="status">
        Laravel je proverio <strong>{{ $impact['foreign_keys'] }} stranih ključeva</strong>
        i <strong>{{ $impact['unconstrained_history'] }} istorijskih kolona bez FK</strong>.
        Prenosi i audit zapisi izvršavaju se u jednoj transakciji.
        Svaka promena šeme ili konflikt prekida postupak bez delimičnog upisa.
    </p>

    <form method="post" action="{{ route('admin.users.retirement.store', $target) }}">
        @csrf
        <label for="retirement-confirmation">
            Potvrda: upiši tačno <strong>OBRISI</strong>
        </label>
        <input id="retirement-confirmation" name="confirmation" required pattern="OBRISI"
            autocomplete="off" spellcheck="false" maxlength="6" aria-required="true">
        @error('confirmation')<p role="alert">{{ $message }}</p>@enderror
        <button class="button button-primary" type="submit">
            Deaktiviraj nalog, prenesi zaduženja i sačuvaj istoriju
        </button>
    </form>
    <a href="{{ route('admin.users.index') }}">Nazad na korisnike</a>
</section>
@endsection

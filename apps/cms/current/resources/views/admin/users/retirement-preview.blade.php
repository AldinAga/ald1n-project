@extends('layouts.app')
@section('title', 'Bezbedno uklanjanje korisnika — pregled posledica')
@section('content')
@include('admin.partials.g5-styles')
<div class="a1-ops a1-g5 a1-g5-narrow" data-user-retirement-preview>
    <section class="a1-card">
        <h1 class="a1-title">Bezbedno uklanjanje naloga</h1>
        <p class="a1-text">
            <strong>{{ $target->displayName() }}</strong> · {{ $target->username }}.
            Nalog će biti trajno deaktiviran i uklonjen iz operativnog spiska korisnika.
            Korisnički zapis i poslovna istorija <strong>ne brišu se fizički</strong>.
        </p>

        <h2 class="a1-h3">Istorija koja ostaje nepromenjena</h2>
        <dl class="a1-dl">
            <div class="a1-dl__row"><dt>Provizije</dt><dd>{{ $impact['commissions'] }}</dd></div>
            <div class="a1-dl__row"><dt>Garancije</dt><dd>{{ $impact['warranties'] }}</dd></div>
            <div class="a1-dl__row"><dt>Korisnički razgovori</dt><dd>{{ $impact['conversations'] }}</dd></div>
            <div class="a1-dl__row"><dt>Istorijske dodele dobavljača</dt><dd>{{ $impact['assignments'] }}</dd></div>
        </dl>

        <h2 class="a1-h3">Aktivna zaduženja koja se prenose na SuperAdministratora</h2>
        <dl class="a1-dl">
            <div class="a1-dl__row"><dt>Porudžbine — vlasništvo</dt><dd>{{ $impact['orders'] }}</dd></div>
            <div class="a1-dl__row"><dt>Porudžbine — odgovorni dobavljač</dt><dd>{{ $impact['supplier_orders'] }}</dd></div>
            <div class="a1-dl__row"><dt>Reklamacije</dt><dd>{{ $impact['after_sales_cases'] }}</dd></div>
            <div class="a1-dl__row"><dt>Postprodajne radnje</dt><dd>{{ $impact['after_sales_actions'] }}</dd></div>
            <div class="a1-dl__row"><dt>Aktivni razgovori</dt><dd>{{ $impact['portal_assignments'] }}</dd></div>
            <div class="a1-dl__row"><dt>Predmeti potraživanja</dt><dd>{{ $impact['receivable_cases'] }}</dd></div>
        </dl>
        <p class="a1-os-note" role="status">
            Laravel je proverio <strong>{{ $impact['foreign_keys'] }} stranih ključeva</strong>
            i <strong>{{ $impact['unconstrained_history'] }} istorijskih kolona bez FK</strong>.
            Prenosi i audit zapisi izvršavaju se u jednoj transakciji.
            Svaka promena šeme ili konflikt prekida postupak bez delimičnog upisa.
        </p>

        <form class="a1-form" method="post" action="{{ route('admin.users.retirement.store', $target) }}">
            @csrf
            <div class="a1-field">
                <label class="a1-label" for="retirement-confirmation">
                    Potvrda: upiši tačno <strong>OBRISI</strong>
                </label>
                <input class="a1-input" id="retirement-confirmation" name="confirmation" required pattern="OBRISI"
                    autocomplete="off" spellcheck="false" maxlength="6" aria-required="true"
                    @error('confirmation') aria-invalid="true" aria-describedby="retirement-confirmation-error" @enderror>
                @error('confirmation')<p class="a1-error" id="retirement-confirmation-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="a1-g5-actions">
                <button class="a1-btn a1-btn--danger a1-g5-wrapbtn" type="submit">
                    Deaktiviraj nalog, prenesi zaduženja i sačuvaj istoriju
                </button>
                <a class="a1-link" href="{{ route('admin.users.index') }}">Nazad na korisnike</a>
            </div>
        </form>
    </section>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Žiro računi')
@section('content')
<div class="a1-ops a1-set">
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Poslovanje'])
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Podešavanja</span>
        <h1 class="a1-title">Žiro računi</h1>
        <p class="a1-lead">Računi za uplate putem bank transfera i buduće IPS QR porudžbine.</p>
    </div>
</header>
<div class="a1-grid a1-grid--2">
    <section class="a1-card" aria-labelledby="bank-add-title">
        <h2 class="a1-h2" id="bank-add-title">Dodaj račun</h2>
        <form method="post" action="{{ route('admin.settings.bank-accounts.store') }}" class="a1-form">
            @csrf
            <label class="a1-field"><span class="a1-label">Naziv računa</span><input class="a1-input" name="label" value="{{ old('label') }}" required maxlength="120"></label>
            <label class="a1-field"><span class="a1-label">Primalac</span><input class="a1-input" name="recipient_name" value="{{ old('recipient_name') }}" required maxlength="70"></label>
            <label class="a1-field"><span class="a1-label">Adresa primaoca</span><input class="a1-input" name="recipient_address" value="{{ old('recipient_address') }}" maxlength="70"></label>
            <label class="a1-field"><span class="a1-label">Broj računa</span><input class="a1-input" name="account_number" value="{{ old('account_number') }}" required placeholder="160-0000000000000-00"></label>
            <label class="a1-field"><span class="a1-label">Šifra plaćanja</span><input class="a1-input" name="payment_code" value="{{ old('payment_code', '221') }}" required maxlength="3"></label>
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="is_active" value="1" checked><span class="a1-check__box" aria-hidden="true"></span><span>Aktivan račun</span></label>
            <div><button class="a1-btn a1-btn--primary" type="submit">Dodaj račun</button></div>
        </form>
    </section>
    <section class="a1-card" aria-labelledby="bank-list-title">
        <h2 class="a1-h2" id="bank-list-title">Sačuvani računi</h2>
        <div class="a1-stack">
            @forelse($accounts as $account)
                <article class="a1-set-asset">
                    <form method="post" action="{{ route('admin.settings.bank-accounts.update', $account) }}" class="a1-form">
                        @csrf @method('put')
                        <label class="a1-field"><span class="a1-label">Naziv računa</span><input class="a1-input" name="label" value="{{ $account->label }}" required></label>
                        <label class="a1-field"><span class="a1-label">Primalac</span><input class="a1-input" name="recipient_name" value="{{ $account->recipient_name }}" required></label>
                        <label class="a1-field"><span class="a1-label">Adresa</span><input class="a1-input" name="recipient_address" value="{{ $account->recipient_address }}"></label>
                        <label class="a1-field"><span class="a1-label">Broj računa</span><input class="a1-input" name="account_number" value="{{ $account->account_number_display }}" required></label>
                        <label class="a1-field"><span class="a1-label">Šifra plaćanja</span><input class="a1-input" name="payment_code" value="{{ $account->payment_code }}" required></label>
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="is_active" value="1" @checked($account->is_active)><span class="a1-check__box" aria-hidden="true"></span><span>Aktivan račun</span></label>
                        <div class="a1-set-actions"><button class="a1-btn a1-btn--primary a1-btn--sm" type="submit">Sačuvaj</button><button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" form="delete-account-{{ $account->id }}" data-confirm="Obrisati račun?">Obriši</button></div>
                    </form>
                    <form id="delete-account-{{ $account->id }}" method="post" action="{{ route('admin.settings.bank-accounts.destroy', $account) }}">@csrf @method('delete')</form>
                </article>
            @empty<div class="a1-empty"><p>Nema sačuvanih računa.</p></div>@endforelse
        </div>
    </section>
</div>
</div>
@endsection

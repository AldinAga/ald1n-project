@extends('layouts.app')
@section('title', 'Žiro računi')
@section('content')
<div class="page-heading"><div><span class="eyebrow">Podešavanja</span><h1>Žiro računi</h1><p>Računi za uplate putem bank transfera i buduće IPS QR porudžbine.</p></div></div>
<div class="settings-grid bank-grid">
    <section class="panel form-section">
        <h2>Dodaj račun</h2>
        <form method="post" action="{{ route('admin.settings.bank-accounts.store') }}" class="stack-form compact-form">
            @csrf
            <label><span>Naziv računa</span><input name="label" value="{{ old('label') }}" required maxlength="120"></label>
            <label><span>Primalac</span><input name="recipient_name" value="{{ old('recipient_name') }}" required maxlength="70"></label>
            <label><span>Adresa primaoca</span><input name="recipient_address" value="{{ old('recipient_address') }}" maxlength="70"></label>
            <label><span>Broj računa</span><input name="account_number" value="{{ old('account_number') }}" required placeholder="160-0000000000000-00"></label>
            <label><span>Šifra plaćanja</span><input name="payment_code" value="{{ old('payment_code', '221') }}" required maxlength="3"></label>
            <label class="check-card"><input type="checkbox" name="is_active" value="1" checked><span>Aktivan račun</span></label>
            <button class="button button-primary" type="submit">Dodaj račun</button>
        </form>
    </section>
    <section class="panel form-section">
        <h2>Sačuvani računi</h2>
        <div class="account-list">
            @forelse($accounts as $account)
                <article class="dictionary-card account-card">
                    <form method="post" action="{{ route('admin.settings.bank-accounts.update', $account) }}">
                        @csrf @method('put')
                        <label><span>Naziv računa</span><input name="label" value="{{ $account->label }}" required></label>
                        <label><span>Primalac</span><input name="recipient_name" value="{{ $account->recipient_name }}" required></label>
                        <label><span>Adresa</span><input name="recipient_address" value="{{ $account->recipient_address }}"></label>
                        <label><span>Broj računa</span><input name="account_number" value="{{ $account->account_number_display }}" required></label>
                        <label><span>Šifra plaćanja</span><input name="payment_code" value="{{ $account->payment_code }}" required></label>
                        <label class="check-card"><input type="checkbox" name="is_active" value="1" @checked($account->is_active)><span>Aktivan račun</span></label>
                        <div class="card-actions"><button class="button button-primary button-small" type="submit">Sačuvaj</button><button class="button button-danger button-small" type="submit" form="delete-account-{{ $account->id }}" data-confirm="Obrisati račun?">Obriši</button></div>
                    </form>
                    <form id="delete-account-{{ $account->id }}" method="post" action="{{ route('admin.settings.bank-accounts.destroy', $account) }}">@csrf @method('delete')</form>
                </article>
            @empty<div class="empty-inline">Nema sačuvanih računa.</div>@endforelse
        </div>
    </section>
</div>
@endsection

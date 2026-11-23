@extends('layouts.app')
@section('title', 'EUR/RSD kurs')
@section('content')
<div class="a1-ops a1-set">
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Poslovanje'])
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Podešavanja</span>
        <h1 class="a1-title">EUR/RSD kurs</h1>
        <p class="a1-lead">Upravljaj ručnim ili automatskim kursom i pregledaj istoriju promena.</p>
    </div>
    <div class="a1-pagehead__actions">
        <button
            class="a1-rate a1-rate--action"
            type="button"
            data-exchange-rate-sync
            data-exchange-rate-sync-url="{{ route('admin.settings.exchange.refresh') }}"
            title="Klikni za sinhronizaciju EUR/RSD kursa"
        ><b data-exchange-rate-value>{{ $configuration['rate'] ? number_format($configuration['rate'], 2, ',', '.') : 'Nije podešen' }}</b>&nbsp;RSD</button>
    </div>
</header>
<section class="a1-card" aria-labelledby="fx-commercial-title">
    <h2 class="a1-h2" id="fx-commercial-title">Komercijalni prodajni kurs</h2>
    <p class="a1-muted"><strong>GLAVNI KURS APLIKACIJE.</strong> Primarni automatski izvor je javna NBS kursna lista za devize: EUR / 978 / prodajni kurs. Frankfurter API v2 je sekundarni referentni fallback. Ako oba izvora nisu dostupna, poslednji uspesno sacuvan kurs ostaje aktivan bez resetovanja datuma svezine. Rucni unos je override.</p>
</section>
<div class="a1-grid a1-grid--2">
    <section class="a1-card" aria-labelledby="fx-manual-title">
        <h2 class="a1-h2" id="fx-manual-title">Ručni kurs</h2>
        <p class="a1-muted">Ručni unos odmah postaje važeći kurs za prikaz cena i obračune.</p>
        <form method="post" action="{{ route('admin.settings.exchange.manual') }}" class="a1-form">
            @csrf
            <label class="a1-field"><span class="a1-label">RSD za 1 EUR</span><input class="a1-input" type="number" name="rate" min="50" max="250" step="0.000001" value="{{ old('rate', $configuration['rate']) }}" required></label>
            <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj ručni kurs</button></div>
        </form>
    </section>
    <section class="a1-card" aria-labelledby="fx-auto-title">
        <h2 class="a1-h2" id="fx-auto-title">Automatsko ažuriranje</h2>
        <p class="a1-muted">Redosled izvora: NBS javna kursna lista -> Frankfurter API v2 -> poslednji uspesno sacuvan kurs. NBS primarni izvor ne zahteva registraciju ni kredencijale.</p>
        <form method="post" action="{{ route('admin.settings.exchange.automatic') }}" class="a1-form">
            @csrf
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="enabled" value="1" @checked($configuration['mode'] === 'auto')><span class="a1-check__box" aria-hidden="true"></span><span>Uključi automatski režim</span></label>
            <label class="a1-field"><span class="a1-label">Kurs se smatra zastarelim nakon (sati)</span><input class="a1-input" type="number" name="stale_after_hours" min="1" max="720" value="{{ $configuration['stale_after_hours'] }}" required></label>
            <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj režim</button></div>
        </form>
        <form method="post" action="{{ route('admin.settings.exchange.refresh') }}" data-exchange-rate-sync-form>@csrf<button class="a1-btn" type="submit" data-exchange-rate-sync-submit>Preuzmi kurs sada</button></form>
    </section>
</div>
<section class="a1-card" aria-labelledby="fx-state-title">
    <h2 class="a1-h2" id="fx-state-title">Trenutno stanje</h2>
    <div class="a1-grid a1-grid--kpi">
        <div class="a1-kpi"><span class="a1-kpi__label">Režim</span><span class="a1-kpi__value">{{ strtoupper($configuration['mode']) }}</span></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Izvor</span><span class="a1-kpi__value" data-exchange-rate-source>{{ $configuration['source'] }}</span></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Datum izvora</span><span class="a1-kpi__value" data-exchange-rate-provider-date>{{ $configuration['provider_date'] ?? '—' }}</span></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Poslednje ažuriranje</span><span class="a1-kpi__value" data-exchange-rate-updated-at>{{ $configuration['updated_at'] ?? '—' }}</span></div>
    </div>
    @if($configuration['last_error'])<div class="a1-alert a1-alert--danger" role="alert"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>{{ $configuration['last_error'] }}</p></div></div>@endif
</section>
<section class="a1-stack" aria-labelledby="fx-history-title">
    <div class="a1-card__title">
        <span class="a1-eyebrow">Istorija</span>
        <h2 class="a1-h2" id="fx-history-title">Poslednjih 50 pokušaja</h2>
    </div>
    <div class="a1-tablewrap" tabindex="0" role="region" aria-labelledby="fx-history-title">
        <table class="a1-table">
            <thead><tr><th scope="col">Datum</th><th scope="col">Režim</th><th scope="col">Stari kurs</th><th scope="col">Novi kurs</th><th scope="col">Status</th><th scope="col">Pokrenuo</th><th scope="col">Poruka</th></tr></thead>
            <tbody>
            @forelse($history as $row)
                <tr><td>{{ $row->created_at?->format('d.m.Y H:i') }}</td><td>{{ $row->mode }}</td><td class="a1-table__num">{{ $row->old_rate ?? '—' }}</td><td class="a1-table__num">{{ $row->new_rate ?? '—' }}</td><td><span class="a1-pill {{ $row->status === 'success' ? 'a1-pill--ok' : 'a1-pill--danger' }}">{{ $row->status }}</span></td><td>{{ $row->updater?->displayName() ?? $row->triggered_by }}</td><td>{{ $row->message }}</td></tr>
            @empty<tr><td colspan="7">Nema istorije kursa.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</section>
</div>
@endsection

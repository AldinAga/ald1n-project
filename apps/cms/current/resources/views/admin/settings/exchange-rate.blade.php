@extends('layouts.app')
@section('title', 'EUR/RSD kurs')
@section('content')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Poslovanje'])
<div class="page-heading">
    <div><span class="eyebrow">Podešavanja</span><h1>EUR/RSD kurs</h1><p>Upravljaj ručnim ili automatskim kursom i pregledaj istoriju promena.</p></div>
    <button
        class="count-pill exchange-rate-settings-sync"
        type="button"
        data-exchange-rate-sync
        data-exchange-rate-sync-url="{{ route('admin.settings.exchange.refresh') }}"
        title="Klikni za sinhronizaciju EUR/RSD kursa"
    ><span data-exchange-rate-value>{{ $configuration['rate'] ? number_format($configuration['rate'], 4, ',', '.') : 'Nije podešen' }}</span>&nbsp;RSD</button>
</div>
<section class="panel form-section"><h2>Komercijalni prodajni kurs</h2><p class="muted"><strong>GLAVNI KURS APLIKACIJE.</strong> Vrsta kursa je zakljucana. Automatski refresh koristi NBS prodajni EUR kurs za devize; rucni unos je samo override iste vrste kursa.</p></section>
<div class="settings-grid">
    <section class="panel form-section">
        <h2>Ručni kurs</h2>
        <p class="muted">Ručni unos odmah postaje važeći kurs za prikaz cena i obračune.</p>
        <form method="post" action="{{ route('admin.settings.exchange.manual') }}" class="stack-form compact-form">
            @csrf
            <label><span>RSD za 1 EUR</span><input type="number" name="rate" min="50" max="250" step="0.000001" value="{{ old('rate', $configuration['rate']) }}" required></label>
            <button class="button button-primary" type="submit">Sačuvaj ručni kurs</button>
        </form>
    </section>
    <section class="panel form-section">
        <h2>Automatsko ažuriranje</h2>
        <p class="muted">NBS javna kursna lista za devize se koristi kao javni izvor bez API ključa.</p>
        <form method="post" action="{{ route('admin.settings.exchange.automatic') }}" class="stack-form compact-form">
            @csrf
            <label class="check-card"><input type="checkbox" name="enabled" value="1" @checked($configuration['mode'] === 'auto')><span>Uključi automatski režim</span></label>
            <label><span>Kurs se smatra zastarelim nakon (sati)</span><input type="number" name="stale_after_hours" min="1" max="720" value="{{ $configuration['stale_after_hours'] }}" required></label>
            <button class="button button-primary" type="submit">Sačuvaj režim</button>
        </form>
        <form method="post" action="{{ route('admin.settings.exchange.refresh') }}" data-exchange-rate-sync-form>@csrf<button class="button button-ghost" type="submit" data-exchange-rate-sync-submit>Preuzmi kurs sada</button></form>
    </section>
</div>
<section class="panel form-section settings-status">
    <h2>Trenutno stanje</h2>
    <div class="stats-grid compact-stats">
        <article class="stat-card"><div><strong>{{ strtoupper($configuration['mode']) }}</strong><small>Režim</small></div></article>
        <article class="stat-card"><div><strong data-exchange-rate-source>{{ $configuration['source'] }}</strong><small>Izvor</small></div></article>
        <article class="stat-card"><div><strong data-exchange-rate-provider-date>{{ $configuration['provider_date'] ?? '—' }}</strong><small>Datum izvora</small></div></article>
        <article class="stat-card"><div><strong data-exchange-rate-updated-at>{{ $configuration['updated_at'] ?? '—' }}</strong><small>Poslednje ažuriranje</small></div></article>
    </div>
    @if($configuration['last_error'])<div class="alert error">{{ $configuration['last_error'] }}</div>@endif
</section>
<section class="panel">
    <div class="section-heading"><div><span class="eyebrow">Istorija</span><h2>Poslednjih 50 pokušaja</h2></div></div>
    <div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>Datum</th><th>Režim</th><th>Stari kurs</th><th>Novi kurs</th><th>Status</th><th>Pokrenuo</th><th>Poruka</th></tr></thead><tbody>
    @forelse($history as $row)
        <tr><td data-label="Datum">{{ $row->created_at?->format('d.m.Y H:i') }}</td><td data-label="Režim">{{ $row->mode }}</td><td data-label="Stari kurs">{{ $row->old_rate ?? '—' }}</td><td data-label="Novi kurs">{{ $row->new_rate ?? '—' }}</td><td data-label="Status"><span class="status-badge {{ $row->status === 'success' ? 'status-active' : 'status-archived' }}">{{ $row->status }}</span></td><td data-label="Pokrenuo">{{ $row->updater?->displayName() ?? $row->triggered_by }}</td><td data-label="Poruka">{{ $row->message }}</td></tr>
    @empty<tr><td colspan="7">Nema istorije kursa.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection

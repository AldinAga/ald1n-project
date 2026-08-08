@extends('layouts.app')
@section('title', 'E-mail obaveštenja')
@section('content')
@php
    $intervalLabels = [
        0 => 'Odmah',
        5 => 'Na 5 minuta',
        15 => 'Na 15 minuta',
        30 => 'Na 30 minuta',
        60 => 'Na sat',
        120 => 'Na 2 sata',
        240 => 'Na 4 sata',
        720 => 'Na 12 sati',
        1440 => 'Jednom dnevno',
    ];
@endphp

@if(!$schemaReady)
    <div class="alert error">
        <strong>E-mail outbox još nije dostupan.</strong>
        <p>Pokreni <code>php artisan migrate --force</code>.</p>
    </div>
@endif

<div class="page-heading">
    <div>
        <span class="eyebrow">Podešavanja</span>
        <h1>E-mail obaveštenja</h1>
        <p>Porudžbine, poslovni dokumenti i automatska obaveštenja korisnika o novim artiklima.</p>
    </div>
    <form method="post" action="{{ route('admin.settings.order-emails.dispatch') }}">
        @csrf
        <button class="button button-secondary" type="submit">Pošalji dospele poruke</button>
    </form>
</div>

<div class="kpi-grid">
    <article class="kpi-card"><small>Na čekanju</small><strong>{{ $stats['pending'] }}</strong></article>
    <article class="kpi-card"><small>Neuspešno</small><strong>{{ $stats['failed'] }}</strong></article>
    <article class="kpi-card"><small>Poslato danas</small><strong>{{ $stats['sent_today'] }}</strong></article>
</div>

<form method="post" action="{{ route('admin.settings.order-emails.update') }}" class="settings-grid">
    @csrf
    @method('PUT')

    <section class="panel form-section">
        <h2>Obaveštenja o novim artiklima</h2>
        <div class="form-grid">
            <label class="checkbox-row">
                <input type="checkbox" name="product_email_new_items_enabled" value="1" @checked($settings['product_email_new_items_enabled'] === '1')>
                <span>Automatski obavesti sve aktivne registrovane korisnike kada se objavi novi artikal</span>
            </label>
            <label>
                <span>Vreme slanja</span>
                <select name="product_email_new_items_interval_minutes">
                    @foreach($intervals as $interval)
                        <option value="{{ $interval }}" @selected((int) $settings['product_email_new_items_interval_minutes'] === $interval)>{{ $intervalLabels[$interval] }}</option>
                    @endforeach
                </select>
                <small>Poruka se priprema samo pri prvom prelasku artikla u status „Aktivan“. Nacrt i neaktivan artikal se ne šalju. Svaki korisnik dobija samo jednu poruku po artiklu.</small>
            </label>
        </div>
    </section>

    <section class="panel form-section">
        <h2>Porudžbine — glavna pravila</h2>
        <div class="form-grid">
            <label class="checkbox-row"><input type="checkbox" name="order_email_enabled" value="1" @checked($settings['order_email_enabled'] === '1')><span>Uključi e-mail obaveštenja porudžbina</span></label>
            <label class="checkbox-row"><input type="checkbox" name="order_email_creation_enabled" value="1" @checked($settings['order_email_creation_enabled'] === '1')><span>Obaveštenje kada je porudžbina kreirana</span></label>
            <label class="checkbox-row"><input type="checkbox" name="order_email_updates_enabled" value="1" @checked($settings['order_email_updates_enabled'] === '1')><span>Obaveštenja o promenama porudžbine</span></label>
            <label class="checkbox-row"><input type="checkbox" name="order_email_documents_enabled" value="1" @checked($settings['order_email_documents_enabled'] === '1')><span>Slanje izdatih dokumenata</span></label>
            <label class="checkbox-row"><input type="checkbox" name="order_email_send_creator" value="1" @checked($settings['order_email_send_creator'] === '1')><span>Šalji autoru porudžbine</span></label>
            <label class="checkbox-row"><input type="checkbox" name="order_email_send_supplier" value="1" @checked($settings['order_email_send_supplier'] === '1')><span>Šalji odgovornom administratoru</span></label>
            <label>
                <span>Dodatne adrese</span>
                <textarea name="order_email_custom_recipients" rows="5" placeholder="prodaja@example.com&#10;logistika@example.com">{{ old('order_email_custom_recipients', $settings['order_email_custom_recipients']) }}</textarea>
                <small>Jedna adresa po redu ili razdvojene zarezom. Svaki primalac dobija zasebnu poruku.</small>
            </label>
            <label class="checkbox-row"><input type="checkbox" name="order_email_custom_recipients_for_updates" value="1" @checked($settings['order_email_custom_recipients_for_updates'] === '1')><span>Dodatne adrese obaveštavaj i o kasnijim promenama</span></label>
        </div>
    </section>

    <section class="panel form-section">
        <h2>Intervali porudžbina</h2>
        <div class="form-grid">
            <label><span>Nove porudžbine</span><select name="order_email_creation_interval_minutes">@foreach($intervals as $interval)<option value="{{ $interval }}" @selected((int) $settings['order_email_creation_interval_minutes'] === $interval)>{{ $intervalLabels[$interval] }}</option>@endforeach</select></label>
            <label><span>Promene porudžbine</span><select name="order_email_update_interval_minutes">@foreach($intervals as $interval)<option value="{{ $interval }}" @selected((int) $settings['order_email_update_interval_minutes'] === $interval)>{{ $intervalLabels[$interval] }}</option>@endforeach</select></label>
            <label><span>Izdati dokumenti</span><select name="order_email_document_interval_minutes">@foreach($intervals as $interval)<option value="{{ $interval }}" @selected((int) $settings['order_email_document_interval_minutes'] === $interval)>{{ $intervalLabels[$interval] }}</option>@endforeach</select></label>
            <p class="muted">Kada više događaja dospe istom primaocu u istom intervalu, sistem ih objedinjuje u jedan pregled.</p>
        </div>
    </section>

    <section class="panel form-section">
        <h2>Događaji porudžbine</h2>
        <div class="form-grid two-columns">
            @foreach(['created' => 'Kreiranje', 'status' => 'Promena statusa', 'tracking' => 'Tracking broj', 'payment' => 'Plaćanje', 'accepted' => 'Preuzimanje porudžbine', 'reassigned' => 'Promena odgovornog lica', 'deadlines' => 'Promena rokova', 'completed' => 'Kompletiranje', 'reopened' => 'Ponovno otvaranje', 'document_cancelled' => 'Storniranje dokumenta', 'other' => 'Ostale značajne promene'] as $key => $label)
                <label class="checkbox-row"><input type="checkbox" name="order_email_event_{{ $key }}" value="1" @checked($settings['order_email_event_'.$key] === '1')><span>{{ $label }}</span></label>
            @endforeach
            <label class="checkbox-row"><input type="checkbox" name="order_email_updates_attach_invoice" value="1" @checked($settings['order_email_updates_attach_invoice'] === '1')><span>Uz promene priloži aktivan račun ako postoji</span></label>
        </div>
    </section>

    <section class="panel form-section">
        <h2>Dokumenti koji se šalju</h2>
        <p class="muted">Dokument se šalje kao PDF prilog kada je izdat ili ponovo izdat.</p>
        <div class="form-grid">
            @foreach(['order_confirmation' => 'Potvrda porudžbine', 'proforma' => 'Predračun', 'invoice' => 'Račun', 'delivery_note' => 'Otpremnica'] as $key => $label)
                <label class="checkbox-row"><input type="checkbox" name="order_email_document_{{ $key }}" value="1" @checked($settings['order_email_document_'.$key] === '1')><span>{{ $label }}</span></label>
            @endforeach
        </div>
    </section>

    <div class="sticky-save-bar"><button class="button button-primary" type="submit">Sačuvaj podešavanja</button></div>
</form>

<section class="panel form-section">
    <div class="card-header-row">
        <h2>Poslednje poruke</h2>
        @if($stats['failed'] > 0)
            <form method="post" action="{{ route('admin.settings.order-emails.retry') }}">
                @csrf
                <button class="button button-warning button-small" type="submit">Ponovi neuspešne</button>
            </form>
        @endif
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Vreme</th><th>Primalac</th><th>Događaj</th><th>Kontekst</th><th>Status</th><th>Greška</th></tr></thead>
            <tbody>
            @forelse($recent as $row)
                @php
                    $productSku = is_array($row->metadata_json) ? ($row->metadata_json['product_sku'] ?? null) : null;
                    $context = $row->order?->order_number ?: ($productSku ? 'Artikal '.$productSku : 'Sistemsko obaveštenje');
                @endphp
                <tr>
                    <td>{{ $row->created_at?->format('d.m.Y H:i') }}</td>
                    <td>{{ $row->recipient_email }}</td>
                    <td>{{ $row->subject }}</td>
                    <td>{{ $context }}</td>
                    <td><span class="status-badge status-{{ $row->status === 'sent' ? 'active' : ($row->status === 'failed' ? 'cancelled' : 'draft') }}">{{ $row->status }}</span></td>
                    <td><small>{{ $row->last_error ? \Illuminate\Support\Str::limit($row->last_error, 120) : '—' }}</small></td>
                </tr>
            @empty
                <tr><td colspan="6">Još nema e-mail događaja.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

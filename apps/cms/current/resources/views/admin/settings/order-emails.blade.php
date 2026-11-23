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
<div class="a1-ops a1-set">
@if(!$schemaReady)
    <div class="a1-alert a1-alert--danger" role="alert">
        <x-icon name="alert" :size="20" :shell="true" />
        <div class="a1-alert__body">
            <strong class="a1-alert__title">E-mail outbox još nije dostupan.</strong>
            <p>Pokreni <code>php artisan migrate --force</code>.</p>
        </div>
    </div>
@endif

@include('admin.settings.partials.context-nav', ['settingsSection' => 'Komunikacija i dokumenti'])
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Podešavanja</span>
        <h1 class="a1-title">E-mail obaveštenja</h1>
        <p class="a1-lead">Porudžbine, poslovni dokumenti i automatska obaveštenja korisnika o novim artiklima.</p>
    </div>
    <div class="a1-pagehead__actions">
        <form method="post" action="{{ route('admin.settings.order-emails.dispatch') }}">
            @csrf
            <button class="a1-btn" type="submit">Pošalji dospele poruke</button>
        </form>
    </div>
</header>

<div class="a1-grid a1-grid--kpi">
    <div class="a1-kpi"><span class="a1-kpi__label">Na čekanju</span><span class="a1-kpi__value a1-num">{{ $stats['pending'] }}</span></div>
    <div class="a1-kpi"><span class="a1-kpi__label">Neuspešno</span><span class="a1-kpi__value a1-num">{{ $stats['failed'] }}</span></div>
    <div class="a1-kpi"><span class="a1-kpi__label">Poslato danas</span><span class="a1-kpi__value a1-num">{{ $stats['sent_today'] }}</span></div>
</div>

<form method="post" action="{{ route('admin.settings.order-emails.update') }}" class="a1-form">
    @csrf
    @method('PUT')

    <div class="a1-grid a1-grid--2">
    <section class="a1-card" aria-labelledby="oe-new-title">
        <h2 class="a1-h2" id="oe-new-title">Obaveštenja o novim artiklima</h2>
        <div class="a1-stack">
            <label class="a1-check">
                <input class="a1-check__input" type="checkbox" name="product_email_new_items_enabled" value="1" @checked($settings['product_email_new_items_enabled'] === '1')>
                <span class="a1-check__box" aria-hidden="true"></span>
                <span>Automatski obavesti sve aktivne registrovane korisnike kada se objavi novi artikal</span>
            </label>
            <label class="a1-field">
                <span class="a1-label">Vreme slanja</span>
                <select class="a1-select" name="product_email_new_items_interval_minutes">
                    @foreach($intervals as $interval)
                        <option value="{{ $interval }}" @selected((int) $settings['product_email_new_items_interval_minutes'] === $interval)>{{ $intervalLabels[$interval] }}</option>
                    @endforeach
                </select>
                <small class="a1-help">Poruka se priprema samo pri prvom prelasku artikla u status „Aktivan“. Nacrt i neaktivan artikal se ne šalju. Svaki korisnik dobija samo jednu poruku po artiklu.</small>
            </label>
        </div>
    </section>

    <section class="a1-card" aria-labelledby="oe-main-title">
        <h2 class="a1-h2" id="oe-main-title">Porudžbine — glavna pravila</h2>
        <div class="a1-stack">
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_enabled" value="1" @checked($settings['order_email_enabled'] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>Uključi e-mail obaveštenja porudžbina</span></label>
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_creation_enabled" value="1" @checked($settings['order_email_creation_enabled'] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>Obaveštenje kada je porudžbina kreirana</span></label>
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_updates_enabled" value="1" @checked($settings['order_email_updates_enabled'] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>Obaveštenja o promenama porudžbine</span></label>
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_documents_enabled" value="1" @checked($settings['order_email_documents_enabled'] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>Slanje izdatih dokumenata</span></label>
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_send_creator" value="1" @checked($settings['order_email_send_creator'] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>Šalji autoru porudžbine</span></label>
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_send_supplier" value="1" @checked($settings['order_email_send_supplier'] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>Šalji odgovornom administratoru</span></label>
            <label class="a1-field">
                <span class="a1-label">Dodatne adrese</span>
                <textarea class="a1-textarea" name="order_email_custom_recipients" rows="5" placeholder="prodaja@example.com&#10;logistika@example.com">{{ old('order_email_custom_recipients', $settings['order_email_custom_recipients']) }}</textarea>
                <small class="a1-help">Jedna adresa po redu ili razdvojene zarezom. Svaki primalac dobija zasebnu poruku.</small>
            </label>
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_custom_recipients_for_updates" value="1" @checked($settings['order_email_custom_recipients_for_updates'] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>Dodatne adrese obaveštavaj i o kasnijim promenama</span></label>
        </div>
    </section>

    <section class="a1-card" aria-labelledby="oe-intervals-title">
        <h2 class="a1-h2" id="oe-intervals-title">Intervali porudžbina</h2>
        <div class="a1-stack">
            <label class="a1-field"><span class="a1-label">Nove porudžbine</span><select class="a1-select" name="order_email_creation_interval_minutes">@foreach($intervals as $interval)<option value="{{ $interval }}" @selected((int) $settings['order_email_creation_interval_minutes'] === $interval)>{{ $intervalLabels[$interval] }}</option>@endforeach</select></label>
            <label class="a1-field"><span class="a1-label">Promene porudžbine</span><select class="a1-select" name="order_email_update_interval_minutes">@foreach($intervals as $interval)<option value="{{ $interval }}" @selected((int) $settings['order_email_update_interval_minutes'] === $interval)>{{ $intervalLabels[$interval] }}</option>@endforeach</select></label>
            <label class="a1-field"><span class="a1-label">Izdati dokumenti</span><select class="a1-select" name="order_email_document_interval_minutes">@foreach($intervals as $interval)<option value="{{ $interval }}" @selected((int) $settings['order_email_document_interval_minutes'] === $interval)>{{ $intervalLabels[$interval] }}</option>@endforeach</select></label>
            <p class="a1-muted">Kada više događaja dospe istom primaocu u istom intervalu, sistem ih objedinjuje u jedan pregled.</p>
        </div>
    </section>

    <section class="a1-card" aria-labelledby="oe-events-title">
        <h2 class="a1-h2" id="oe-events-title">Događaji porudžbine</h2>
        <div class="a1-set-checks">
            @foreach(['created' => 'Kreiranje', 'status' => 'Promena statusa', 'tracking' => 'Tracking broj', 'payment' => 'Plaćanje', 'accepted' => 'Preuzimanje porudžbine', 'reassigned' => 'Promena odgovornog lica', 'deadlines' => 'Promena rokova', 'completed' => 'Kompletiranje', 'reopened' => 'Ponovno otvaranje', 'document_cancelled' => 'Storniranje dokumenta', 'other' => 'Ostale značajne promene'] as $key => $label)
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_event_{{ $key }}" value="1" @checked($settings['order_email_event_'.$key] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>{{ $label }}</span></label>
            @endforeach
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_updates_attach_invoice" value="1" @checked($settings['order_email_updates_attach_invoice'] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>Uz promene priloži aktivan račun ako postoji</span></label>
        </div>
    </section>

    <section class="a1-card" aria-labelledby="oe-docs-title">
        <div class="a1-card__title">
            <h2 class="a1-h2" id="oe-docs-title">Dokumenti koji se šalju</h2>
            <p class="a1-muted">Dokument se šalje kao PDF prilog kada je izdat ili ponovo izdat.</p>
        </div>
        <div class="a1-set-checks">
            @foreach(['order_confirmation' => 'Potvrda porudžbine', 'proforma' => 'Predračun', 'invoice' => 'Račun', 'delivery_note' => 'Otpremnica'] as $key => $label)
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="order_email_document_{{ $key }}" value="1" @checked($settings['order_email_document_'.$key] === '1')><span class="a1-check__box" aria-hidden="true"></span><span>{{ $label }}</span></label>
            @endforeach
        </div>
    </section>
    </div>

    <div class="a1-actionbar"><span class="a1-actionbar__status">Izmene se primenjuju nakon čuvanja.</span><div class="a1-actionbar__buttons"><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj podešavanja</button></div></div>
</form>

<section class="a1-stack" aria-labelledby="oe-recent-title">
    <div class="a1-card__head">
        <h2 class="a1-h2" id="oe-recent-title">Poslednje poruke</h2>
        @if($stats['failed'] > 0)
            <form method="post" action="{{ route('admin.settings.order-emails.retry') }}">
                @csrf
                <button class="a1-btn a1-btn--sm" type="submit">Ponovi neuspešne</button>
            </form>
        @endif
    </div>
    <div class="a1-tablewrap" tabindex="0" role="region" aria-labelledby="oe-recent-title">
        <table class="a1-table">
            <thead><tr><th scope="col">Vreme</th><th scope="col">Primalac</th><th scope="col">Događaj</th><th scope="col">Kontekst</th><th scope="col">Status</th><th scope="col">Greška</th></tr></thead>
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
                    <td><span class="a1-pill a1-pill--{{ $row->status === 'sent' ? 'ok' : ($row->status === 'failed' ? 'danger' : 'warning') }}">{{ $row->status }}</span></td>
                    <td><small>{{ $row->last_error ? \Illuminate\Support\Str::limit($row->last_error, 120) : '—' }}</small></td>
                </tr>
            @empty
                <tr><td colspan="6">Još nema e-mail događaja.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
</div>
@endsection

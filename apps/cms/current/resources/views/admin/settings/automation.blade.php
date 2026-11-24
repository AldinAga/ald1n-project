@extends('layouts.app')
@section('title', 'Automatizacija')
@section('content')
@php
    $typeLabels = [
        'order_unaccepted' => 'Čeka preuzimanje',
        'processing_overdue' => 'Istekao rok obrade',
        'shipping_overdue' => 'Istekao rok slanja',
        'payment_overdue' => 'Dospelo plaćanje',
        'low_stock' => 'Nizak lager',
        'warranty_expiring' => 'Garancija uskoro ističe',
        'warranty_maintenance_due' => 'Preventivno održavanje',
    ];
@endphp
<div class="a1-ops a1-set">
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Automatizacija'])
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Podešavanja</span>
        <h1 class="a1-title">Automatizacija i upozorenja</h1>
        <p class="a1-lead">Operativne provere rade preko Laravel scheduler-a, file lock-a i sync notifikacija — bez Redis-a.</p>
    </div>
    <div class="a1-pagehead__actions">
        <form method="post" action="{{ route('admin.settings.automation.run') }}">@csrf<button class="a1-btn a1-btn--primary" type="submit">Pokreni scan sada</button></form>
        <form method="post" action="{{ route('admin.settings.automation.run') }}">@csrf<input type="hidden" name="digest" value="1"><button class="a1-btn" type="submit">Pošalji dnevni pregled</button></form>
    </div>
</header>
@if(!$ready)
    <div class="a1-alert a1-alert--danger" role="alert">
        <x-icon name="alert" :size="20" :shell="true" />
        <div class="a1-alert__body">
            <strong class="a1-alert__title">Automation šema nije spremna.</strong>
            <p>{{ implode(', ', $missing) }}</p>
            <code>php artisan app:automation-doctor --repair --run</code>
        </div>
    </div>
@endif
<div class="a1-grid a1-grid--kpi">
    <div class="a1-kpi"><span class="a1-kpi__label">Otvorena upozorenja</span><span class="a1-kpi__value a1-num">{{ $stats['open'] }}</span></div>
    <div class="a1-kpi"><span class="a1-kpi__label">Kritična</span><span class="a1-kpi__value a1-num a1-set-danger">{{ $stats['danger'] }}</span></div>
    <div class="a1-kpi"><span class="a1-kpi__label">Upozorenja</span><span class="a1-kpi__value a1-num">{{ $stats['warning'] }}</span></div>
    <div class="a1-kpi"><span class="a1-kpi__label">Neuspela pokretanja / 7 dana</span><span class="a1-kpi__value a1-num">{{ $stats['failed_runs'] }}</span></div>
</div>
<form method="post" action="{{ route('admin.settings.automation.update') }}" class="a1-set-split">
    @csrf
    @method('put')
    <div class="a1-set-main">
        <section class="a1-card" aria-labelledby="au-scan-title">
            <h2 class="a1-h2" id="au-scan-title">Operativni scan</h2>
            <div class="a1-set-fields">
                <label class="a1-field"><span class="a1-label">Porudžbina bez preuzimanja nakon (sati)</span><input class="a1-input" type="number" min="1" max="168" name="automation_unaccepted_order_hours" value="{{ old('automation_unaccepted_order_hours',$settings['automation_unaccepted_order_hours']) }}" required></label>
                <label class="a1-field"><span class="a1-label">Ponovi upozorenje nakon (sati)</span><input class="a1-input" type="number" min="1" max="720" name="automation_alert_reminder_hours" value="{{ old('automation_alert_reminder_hours',$settings['automation_alert_reminder_hours']) }}" required></label>
                <label class="a1-field"><span class="a1-label">Upozori pre isteka garancije (dana)</span><input class="a1-input" type="number" min="1" max="365" name="warranty_expiry_notice_days" value="{{ old('warranty_expiry_notice_days',$settings['warranty_expiry_notice_days']) }}" required></label>
                <label class="a1-field"><span class="a1-label">Upozori pre održavanja (dana)</span><input class="a1-input" type="number" min="1" max="90" name="warranty_maintenance_notice_days" value="{{ old('warranty_maintenance_notice_days',$settings['warranty_maintenance_notice_days']) }}" required></label>
            </div>
            <div class="a1-set-checks a1-set-checks--cols">
                <label class="a1-check a1-set-wide"><input class="a1-check__input" type="checkbox" name="automation_enabled" value="1" @checked(old('automation_enabled',$settings['automation_enabled'])==='1')><span class="a1-check__box" aria-hidden="true"></span><span class="a1-set-check-label"><strong>Automatizacija je aktivna</strong><small class="a1-help">Scheduler može pokretati scan i dnevni pregled.</small></span></label>
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="automation_low_stock_enabled" value="1" @checked(old('automation_low_stock_enabled',$settings['automation_low_stock_enabled'])==='1')><span class="a1-check__box" aria-hidden="true"></span><span class="a1-set-check-label"><strong>Nizak lager</strong><small class="a1-help">Prag se čita iz svakog artikla.</small></span></label>
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="automation_overdue_payment_enabled" value="1" @checked(old('automation_overdue_payment_enabled',$settings['automation_overdue_payment_enabled'])==='1')><span class="a1-check__box" aria-hidden="true"></span><span class="a1-set-check-label"><strong>Dospela plaćanja</strong><small class="a1-help">Upozori za neplaćene dospele porudžbine.</small></span></label>
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="automation_deadline_alerts_enabled" value="1" @checked(old('automation_deadline_alerts_enabled',$settings['automation_deadline_alerts_enabled'])==='1')><span class="a1-check__box" aria-hidden="true"></span><span class="a1-set-check-label"><strong>Rok obrade i slanja</strong><small class="a1-help">Prati očekivane datume porudžbine.</small></span></label>
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="automation_daily_digest_enabled" value="1" @checked(old('automation_daily_digest_enabled',$settings['automation_daily_digest_enabled'])==='1')><span class="a1-check__box" aria-hidden="true"></span><span class="a1-set-check-label"><strong>Dnevni pregled</strong><small class="a1-help">Zakazan u 08:05 po Europe/Belgrade vremenu.</small></span></label>
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="automation_warranty_alerts_enabled" value="1" @checked(old('automation_warranty_alerts_enabled',$settings['automation_warranty_alerts_enabled'])==='1')><span class="a1-check__box" aria-hidden="true"></span><span class="a1-set-check-label"><strong>Garancije i održavanje</strong><small class="a1-help">Upozorenja pre isteka i dospelog preventivnog servisa.</small></span></label>
            </div>
        </section>
    </div>
    <aside class="a1-set-side">
        <section class="a1-card" aria-labelledby="au-sched-title">
            <h2 class="a1-h2" id="au-sched-title">Scheduler</h2>
            <p class="a1-muted">Na hostingu treba da postoji jedan cron koji svakog minuta poziva:</p>
            <code>* * * * * php /home/icaffeco/ald1n-project/apps/cms/current/artisan schedule:run</code>
            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>Poslednji uspeh</dt><dd>{{ $settings['automation_last_success_at'] ?: 'Nije pokretano' }}</dd></div>
                <div class="a1-dl__row"><dt>Poslednja greška</dt><dd @class(['a1-set-danger' => !empty($settings['automation_last_error'])])>{{ $settings['automation_last_error'] ?: 'Nema' }}</dd></div>
            </dl>
            <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj automatizaciju</button>
        </section>
    </aside>
</form>
<div class="a1-grid a1-grid--2">
    <section class="a1-card" aria-labelledby="au-alerts-title">
        <div class="a1-card__title">
            <h2 class="a1-h2" id="au-alerts-title">Otvorena upozorenja</h2>
            <p class="a1-muted">Ručno zatvaranje ne skriva problem zauvek — sledeći scan ga vraća ako i dalje postoji.</p>
        </div>
        <ul class="a1-list">
            @forelse($alerts as $alert)
                <li class="a1-item a1-item--stack a1-item--static" data-alert-severity="{{ $alert->severity }}">
                    <div class="a1-item__main">
                        <span><span class="a1-pill a1-pill--{{ $alert->severity==='danger'?'danger':'warning' }}">{{ $typeLabels[$alert->type] ?? $alert->type }}</span></span>
                        <h3 class="a1-h3">{{ $alert->title }}</h3>
                        <span class="a1-item__sub">{{ $alert->message }}</span>
                        <span class="a1-muted">Poslednje detektovano: {{ $alert->last_detected_at?->format('d.m.Y H:i') }} · Poslednje obaveštenje: {{ $alert->last_notified_at?->format('d.m.Y H:i') ?? 'nije poslato' }}</span>
                    </div>
                    <div class="a1-set-actions">
                        @if($alert->action_url)<a class="a1-btn a1-btn--sm" href="{{ $alert->action_url }}">Otvori</a>@endif
                        <form method="post" action="{{ route('admin.settings.automation.alerts.resolve',$alert) }}">@csrf<button class="a1-btn a1-btn--sm" type="submit">Zatvori</button></form>
                    </div>
                </li>
            @empty
                <li class="a1-empty"><x-icon name="check-circle" :size="28" :shell="true" /><h3 class="a1-h3">Nema otvorenih upozorenja</h3><p>Operativni scan nije pronašao problem.</p></li>
            @endforelse
        </ul>
    </section>
    <section class="a1-card" aria-labelledby="au-runs-title">
        <h2 class="a1-h2" id="au-runs-title">Poslednja pokretanja</h2>
        <ul class="a1-list">
            @forelse($runs as $run)
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main"><span class="a1-item__title">{{ $run->task }}</span><span class="a1-item__sub">{{ $run->started_at?->format('d.m.Y H:i:s') }} · {{ $run->trigger?->displayName() ?? 'Scheduler' }}</span></div>
                    <div class="a1-item__end"><span class="a1-pill a1-pill--{{ $run->status==='success'?'ok':($run->status==='failed'?'danger':'warning') }}">{{ $run->status }}</span><span class="a1-item__sub">{{ $run->examined_count }} provereno · {{ $run->action_count }} upozorenja · {{ $run->notification_count }} poruka</span></div>
                </li>
            @empty
                <li class="a1-empty"><p>Automatizacija još nije pokretana.</p></li>
            @endforelse
        </ul>
    </section>
</div>
</div>
@endsection

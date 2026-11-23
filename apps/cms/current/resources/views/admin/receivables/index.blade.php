@extends('layouts.app')
@section('title', 'Potraživanja i naplata')
@section('content')
@php
    $canManagePayments = request()->user()?->can('payments.manage') ?? false;
    $money = static fn ($v): string => number_format((float) $v, 2, ',', '.');
    $statusTone = ['contacted' => ' a1-pill--info', 'promised' => ' a1-pill--info', 'installment_plan' => ' a1-pill--info', 'escalated' => ' a1-pill--danger', 'disputed' => ' a1-pill--warning', 'closed' => ' a1-pill--ok'];
@endphp
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Finansije</span>
            <h1 class="a1-title">Potraživanja i naplata</h1>
            <p class="a1-lead">Aging pregled, automatske opomene, obećane uplate, planovi rata i evidencija kontakata.</p>
        </div>
        @if($schemaReady)
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.receivables.csv', request()->query()) }}"><x-icon name="download" :size="18" :shell="true" /> Izvezi CSV</a>
            <form method="post" action="{{ route('admin.receivables.scan') }}">@csrf<button class="a1-btn a1-btn--primary" type="submit">Pokreni proveru naplate</button></form>
        </div>
        @endif
    </header>

    @if(!$schemaReady)
    <div class="a1-alert a1-alert--danger" role="alert">
        <div class="a1-alert__body"><p class="a1-alert__title">Modul naplate još nije aktivan.</p><p>Pokreni <code>php artisan migrate --force</code>, zatim <code>php artisan app:receivables-doctor</code>.</p></div>
    </div>
    @else
    <div class="a1-grid a1-grid--kpi">
        <div class="a1-kpi"><p class="a1-kpi__label">Aktivni predmeti</p><p class="a1-kpi__value">{{ $stats['active'] }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Dospelo dugovanje</p><p class="a1-kpi__value">{{ $money($stats['overdue_remaining']) }} <span class="a1-kpi__unit">RSD</span></p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Ukupno otvoreno</p><p class="a1-kpi__value">{{ $money($stats['total_remaining']) }} <span class="a1-kpi__unit">RSD</span></p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Obećane uplate</p><p class="a1-kpi__value">{{ $stats['promised'] }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Planovi otplate</p><p class="a1-kpi__value">{{ $stats['plans'] }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Probijene aktivnosti</p><p class="a1-kpi__value">{{ $stats['actions_overdue'] }}</p></div>
    </div>

    <nav class="a1-ol-tiles" aria-label="Starost potraživanja">
        @foreach($agingLabels as $key=>$label)
        @php $active = ($filters['aging'] ?? '') === $key; @endphp
        <a class="a1-ol-tile" href="{{ route('admin.receivables.index', array_filter(array_merge(request()->except('page'), ['aging'=>$key]))) }}" @if($active) aria-current="true" @endif>
            <span class="a1-ol-tile__icon{{ $key === 'current' ? '' : ' a1-ol-tile__icon--warning' }}"><x-icon name="hourglass" :size="22" :shell="true" /></span>
            <span class="a1-ol-tile__body">
                <span class="a1-ol-tile__count">{{ $aging[$key]['count'] ?? 0 }}</span>
                <span class="a1-ol-tile__label">{{ $label }}</span>
                <span class="a1-ol-tile__state">{{ $money($aging[$key]['amount'] ?? 0) }} RSD{{ $active ? ' · aktivan filter' : '' }}</span>
            </span>
        </a>
        @endforeach
    </nav>

    <div class="a1-g12">
        <section class="a1-lg-8 a1-stack" aria-labelledby="rc-open-title">
            <div>
                <h2 class="a1-h2" id="rc-open-title">Otvorena potraživanja</h2>
                <p class="a1-muted">Prikaz se automatski usklađuje sa verifikovanim uplatama i aktivnim dokumentima.</p>
            </div>
            <form method="get" class="a1-filters" role="search" aria-label="Filteri potraživanja">
                <div class="a1-filters__fields">
                    <div class="a1-field"><label class="a1-label" for="rc-q">Pretraga</label><input class="a1-input" id="rc-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Broj predmeta, porudžbina, kupac"></div>
                    <div class="a1-field"><label class="a1-label" for="rc-status">Status predmeta</label><select class="a1-select" id="rc-status" name="status"><option value="">Svi statusi</option>@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['status']??'')===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="a1-field"><label class="a1-label" for="rc-aging">Aging</label><select class="a1-select" id="rc-aging" name="aging"><option value="">Sve starosti</option>@foreach($agingLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['aging']??'')===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="a1-field"><label class="a1-label" for="rc-assignee">Odgovorno lice</label><select class="a1-select" id="rc-assignee" name="assigned_to"><option value="">Svi</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected((int)($filters['assigned_to']??0)===(int)$assignee->id)>{{ $assignee->displayName() }}</option>@endforeach</select></div>
                    <div class="a1-field"><label class="a1-label" for="rc-action">Akcija</label><select class="a1-select" id="rc-action" name="action"><option value="">Sve</option><option value="overdue" @selected(($filters['action']??'')==='overdue')>Probijen sledeći korak</option><option value="today" @selected(($filters['action']??'')==='today')>Akcija danas</option><option value="promised" @selected(($filters['action']??'')==='promised')>Obećana uplata</option></select></div>
                </div>
                <div class="a1-filters__actions">
                    <button class="a1-btn a1-btn--primary" type="submit">Primeni</button>
                    <a class="a1-btn" href="{{ route('admin.receivables.index') }}">Resetuj</a>
                </div>
            </form>

            @if($cases->isEmpty())
            <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="search" :size="28" :shell="true" /></span><p>Nema predmeta koji odgovaraju filterima.</p></div>
            @else
            <div class="a1-tablewrap" role="region" aria-label="Otvorena potraživanja" tabindex="0">
                <table class="a1-table a1-table--cards a1-ol-table">
                    <caption class="a1-sr">Otvorena potraživanja</caption>
                    <thead><tr><th scope="col">Predmet</th><th scope="col">Kupac / porudžbina</th><th scope="col">Dospeće</th><th scope="col">Dugovanje</th><th scope="col">Status</th><th scope="col">Sledeći korak</th><th scope="col"><span class="a1-sr">Radnje</span></th></tr></thead>
                    <tbody>
                    @foreach($cases as $case)
                        @php
                            $order = $case->order;
                            $remaining = max(0, (float) ($order?->subtotal_rsd ?? 0) - (float) ($order?->paid_total_rsd ?? 0));
                            $days = $order?->payment_due_at && $order->payment_due_at->copy()->startOfDay()->lt(today()) ? $order->payment_due_at->copy()->startOfDay()->diffInDays(today()) : 0;
                            $late = $days > 0 && $case->status !== 'closed';
                        @endphp
                        <tr @if($late) class="a1-ol-row--over" @endif>
                            <td class="a1-table__primary" data-label="Predmet"><strong>{{ $case->case_number }}</strong><span class="a1-ol-sub">{{ $case->assignee?->displayName() ?? 'Nije dodeljeno' }}</span></td>
                            <td data-label="Kupac / porudžbina"><strong>{{ $order?->shipping_full_name ?: $order?->user?->displayName() ?: 'Kupac' }}</strong><span class="a1-ol-sub">{{ $order?->order_number }}</span></td>
                            <td data-label="Dospeće">{{ $order?->payment_due_at?->format('d.m.Y') ?? 'Nije definisano' }}@if($days>0)<span class="a1-ol-flag">Kasni {{ $days }} dana</span>@endif</td>
                            <td data-label="Dugovanje" class="a1-num"><strong>{{ $money($remaining) }} RSD</strong><span class="a1-ol-sub">Plaćeno {{ $money($order?->paid_total_rsd ?? 0) }}</span></td>
                            <td data-label="Status"><span class="a1-pill{{ $statusTone[$case->status] ?? '' }}">{{ $statusLabels[$case->status] ?? $case->status }}</span>@if($case->promised_payment_at)<span class="a1-ol-sub">Obećano {{ $case->promised_payment_at->format('d.m.Y') }}</span>@endif</td>
                            <td data-label="Sledeći korak">{{ $case->next_action_at?->format('d.m.Y H:i') ?? 'Nije zakazano' }}</td>
                            <td class="a1-table__actions">
                                <div class="a1-ol-actions">
                                    @if($canManagePayments && $remaining > 0)
                                    <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('admin.receivables.show',$case) }}#evidentiraj-uplatu" aria-label="Evidentiraj uplatu za predmet {{ $case->case_number }}">Uplata</a>
                                    @endif
                                    <a class="a1-btn a1-btn--sm" href="{{ route('admin.receivables.show',$case) }}" aria-label="Otvori predmet {{ $case->case_number }}">Otvori</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $cases->links('partials.ol-pager') }}
            @endif
        </section>

        <aside class="a1-lg-4" aria-labelledby="rc-settings-title">
            <section class="a1-card">
                <div>
                    <h2 class="a1-h2" id="rc-settings-title">Automatske opomene</h2>
                    <p class="a1-muted">Scheduler priprema poruke kroz pouzdani e-mail outbox. Kvar SMTP-a ne menja porudžbinu niti finansijski ledger.</p>
                </div>
                <form method="post" action="{{ route('admin.receivables.settings.update') }}" class="a1-form">@csrf @method('PUT')
                    <div class="a1-stack">
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="receivables_enabled" value="1" @checked($settings['receivables_enabled']==='1')><span class="a1-check__box" aria-hidden="true"></span><span>Uključi modul naplate</span></label>
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="receivables_auto_create_cases" value="1" @checked($settings['receivables_auto_create_cases']==='1')><span class="a1-check__box" aria-hidden="true"></span><span>Automatski formiraj predmet</span></label>
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="receivables_auto_reminders_enabled" value="1" @checked($settings['receivables_auto_reminders_enabled']==='1')><span class="a1-check__box" aria-hidden="true"></span><span>Automatski šalji opomene</span></label>
                    </div>
                    <div class="a1-field"><label class="a1-label" for="rc-due-soon">Prva najava pre dospeća (dana)</label><input class="a1-input" id="rc-due-soon" type="number" min="0" max="60" name="receivables_due_soon_days" value="{{ $settings['receivables_due_soon_days'] }}" required></div>
                    <div class="a1-field"><label class="a1-label" for="rc-stages">Faze nakon dospeća</label><input class="a1-input" id="rc-stages" name="receivables_reminder_stages" value="{{ $settings['receivables_reminder_stages'] }}" aria-describedby="rc-stages-help" required><p class="a1-help" id="rc-stages-help">Na primer: 0,3,7,15,30</p></div>
                    <label class="a1-check"><input class="a1-check__input" type="checkbox" name="receivables_pause_on_promise" value="1" @checked($settings['receivables_pause_on_promise']==='1')><span class="a1-check__box" aria-hidden="true"></span><span>Pauziraj do obećanog datuma</span></label>
                    <div class="a1-field"><label class="a1-label" for="rc-attach">PDF prilog</label><select class="a1-select" id="rc-attach" name="receivables_attach_document"><option value="none" @selected($settings['receivables_attach_document']==='none')>Bez priloga</option><option value="invoice" @selected($settings['receivables_attach_document']==='invoice')>Aktivni račun</option><option value="proforma" @selected($settings['receivables_attach_document']==='proforma')>Aktivni predračun</option></select></div>
                    <div class="a1-stack">
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="receivables_send_creator" value="1" @checked($settings['receivables_send_creator']==='1')><span class="a1-check__box" aria-hidden="true"></span><span>Pošalji autoru porudžbine</span></label>
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="receivables_send_supplier" value="1" @checked($settings['receivables_send_supplier']==='1')><span class="a1-check__box" aria-hidden="true"></span><span>Pošalji odgovornom administratoru</span></label>
                    </div>
                    <div class="a1-field"><label class="a1-label" for="rc-recipients">Dodatne adrese</label><textarea class="a1-textarea" id="rc-recipients" name="receivables_custom_recipients" placeholder="naplata@firma.rs; direktor@firma.rs">{{ $settings['receivables_custom_recipients'] }}</textarea></div>
                    <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj podešavanja</button>
                </form>
            </section>
        </aside>
    </div>
    @endif
</div>
@endsection

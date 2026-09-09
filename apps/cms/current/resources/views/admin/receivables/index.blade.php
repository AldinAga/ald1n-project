@extends('layouts.app')
@section('title', 'Potraživanja i naplata')
@section('content')
@php($canManagePayments = request()->user()?->can('payments.manage') ?? false)
<div class="page-heading">
    <div><span class="eyebrow">Finansije</span><h1>Potraživanja i naplata</h1><p>Aging pregled, automatske opomene, obećane uplate, planovi rata i evidencija kontakata.</p></div>
    <div class="header-button-row">
        @if($schemaReady)
        <a class="button button-ghost" href="{{ route('admin.receivables.csv', request()->query()) }}">Izvezi CSV</a>
        <form method="post" action="{{ route('admin.receivables.scan') }}">@csrf<button class="button button-primary" type="submit">Pokreni proveru naplate</button></form>
        @endif
    </div>
</div>

@if(!$schemaReady)
<section class="panel form-section"><div class="alert alert-error"><strong>Modul naplate još nije aktivan.</strong><p>Pokreni <code>php artisan migrate --force</code>, zatim <code>php artisan app:receivables-doctor</code>.</p></div></section>
@else
<div class="stats-grid six-cards receivables-stats">
    <article class="stat-card"><span>Aktivni predmeti</span><strong>{{ $stats['active'] }}</strong></article>
    <article class="stat-card danger"><span>Dospelo dugovanje</span><strong>{{ number_format((float)$stats['overdue_remaining'],2,',','.') }} RSD</strong></article>
    <article class="stat-card warning"><span>Ukupno otvoreno</span><strong>{{ number_format((float)$stats['total_remaining'],2,',','.') }} RSD</strong></article>
    <article class="stat-card info"><span>Obećane uplate</span><strong>{{ $stats['promised'] }}</strong></article>
    <article class="stat-card"><span>Planovi otplate</span><strong>{{ $stats['plans'] }}</strong></article>
    <article class="stat-card danger"><span>Probijene aktivnosti</span><strong>{{ $stats['actions_overdue'] }}</strong></article>
</div>

<section class="aging-grid">
@foreach($agingLabels as $key=>$label)
    <a class="aging-card {{ ($filters['aging'] ?? '')===$key ? 'is-active' : '' }}" href="{{ route('admin.receivables.index', array_filter(array_merge(request()->except('page'), ['aging'=>$key]))) }}">
        <span>{{ $label }}</span><strong>{{ $aging[$key]['count'] ?? 0 }}</strong><small>{{ number_format((float)($aging[$key]['amount'] ?? 0),2,',','.') }} RSD</small>
    </a>
@endforeach
</section>

<div class="settings-grid receivables-layout">
<section class="panel form-section receivables-main-panel">
    <div class="section-heading-row"><div><h2>Otvorena potraživanja</h2><p class="muted">Prikaz se automatski usklađuje sa verifikovanim uplatama i aktivnim dokumentima.</p></div></div>
    <form method="get" class="form-grid receivables-filter-grid">
        <label><span>Pretraga</span><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Broj predmeta, porudžbina, kupac"></label>
        <label><span>Status predmeta</span><select name="status"><option value="">Svi statusi</option>@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['status']??'')===$value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Aging</span><select name="aging"><option value="">Sve starosti</option>@foreach($agingLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['aging']??'')===$value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Odgovorno lice</span><select name="assigned_to"><option value="">Svi</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected((int)($filters['assigned_to']??0)===(int)$assignee->id)>{{ $assignee->displayName() }}</option>@endforeach</select></label>
        <label><span>Akcija</span><select name="action"><option value="">Sve</option><option value="overdue" @selected(($filters['action']??'')==='overdue')>Probijen sledeći korak</option><option value="today" @selected(($filters['action']??'')==='today')>Akcija danas</option><option value="promised" @selected(($filters['action']??'')==='promised')>Obećana uplata</option></select></label>
        <div class="filter-actions"><button class="button button-primary" type="submit">Primeni</button><a class="button button-ghost" href="{{ route('admin.receivables.index') }}">Resetuj</a></div>
    </form>
    <div class="table-wrap"><table class="admin-table"><thead><tr><th>Predmet</th><th>Kupac / porudžbina</th><th>Dospeće</th><th>Dugovanje</th><th>Status</th><th>Sledeći korak</th><th></th></tr></thead><tbody>
    @forelse($cases as $case)
        @php($order=$case->order)
        @php($remaining=max(0,(float)($order?->subtotal_rsd??0)-(float)($order?->paid_total_rsd??0)))
        @php($days=$order?->payment_due_at && $order->payment_due_at->copy()->startOfDay()->lt(today()) ? $order->payment_due_at->copy()->startOfDay()->diffInDays(today()) : 0)
        <tr class="{{ $days>0 && $case->status!=='closed' ? 'row-overdue' : '' }}">
            <td><strong>{{ $case->case_number }}</strong><small>{{ $case->assignee?->displayName() ?? 'Nije dodeljeno' }}</small></td>
            <td><strong>{{ $order?->shipping_full_name ?: $order?->user?->displayName() ?: 'Kupac' }}</strong><small>{{ $order?->order_number }}</small></td>
            <td>{{ $order?->payment_due_at?->format('d.m.Y') ?? 'Nije definisano' }}@if($days>0)<small class="text-danger">Kasni {{ $days }} dana</small>@endif</td>
            <td><strong>{{ number_format($remaining,2,',','.') }} RSD</strong><small>Plaćeno {{ number_format((float)($order?->paid_total_rsd??0),2,',','.') }}</small></td>
            <td><span class="status-badge receivable-status-{{ $case->status }}">{{ $statusLabels[$case->status] ?? $case->status }}</span>@if($case->promised_payment_at)<small>Obećano {{ $case->promised_payment_at->format('d.m.Y') }}</small>@endif</td>
            <td>{{ $case->next_action_at?->format('d.m.Y H:i') ?? 'Nije zakazano' }}</td>
            <td>
                <div class="header-button-row">
                    @if($canManagePayments && $remaining > 0)
                    <a class="button button-primary button-small" href="{{ route('admin.receivables.show',$case) }}#evidentiraj-uplatu">Uplata</a>
                    @endif
                    <a class="button button-ghost button-small" href="{{ route('admin.receivables.show',$case) }}">Otvori</a>
                </div>
            </td>
        </tr>
    @empty<tr><td colspan="7">Nema predmeta koji odgovaraju filterima.</td></tr>@endforelse
    </tbody></table></div>
    {{ $cases->links() }}
</section>

<aside class="form-side">
<section class="panel form-section receivables-settings-card"><h2>Automatske opomene</h2><p class="muted">Scheduler priprema poruke kroz pouzdani e-mail outbox. Kvar SMTP-a ne menja porudžbinu niti finansijski ledger.</p>
<form method="post" action="{{ route('admin.receivables.settings.update') }}" class="form-grid">@csrf @method('PUT')
    <label class="checkbox-row"><input type="checkbox" name="receivables_enabled" value="1" @checked($settings['receivables_enabled']==='1')><span>Uključi modul naplate</span></label>
    <label class="checkbox-row"><input type="checkbox" name="receivables_auto_create_cases" value="1" @checked($settings['receivables_auto_create_cases']==='1')><span>Automatski formiraj predmet</span></label>
    <label class="checkbox-row"><input type="checkbox" name="receivables_auto_reminders_enabled" value="1" @checked($settings['receivables_auto_reminders_enabled']==='1')><span>Automatski šalji opomene</span></label>
    <label><span>Prva najava pre dospeća (dana)</span><input type="number" min="0" max="60" name="receivables_due_soon_days" value="{{ $settings['receivables_due_soon_days'] }}" required></label>
    <label><span>Faze nakon dospeća</span><input name="receivables_reminder_stages" value="{{ $settings['receivables_reminder_stages'] }}" required><small>Na primer: 0,3,7,15,30</small></label>
    <label class="checkbox-row"><input type="checkbox" name="receivables_pause_on_promise" value="1" @checked($settings['receivables_pause_on_promise']==='1')><span>Pauziraj do obećanog datuma</span></label>
    <label><span>PDF prilog</span><select name="receivables_attach_document"><option value="none" @selected($settings['receivables_attach_document']==='none')>Bez priloga</option><option value="invoice" @selected($settings['receivables_attach_document']==='invoice')>Aktivni račun</option><option value="proforma" @selected($settings['receivables_attach_document']==='proforma')>Aktivni predračun</option></select></label>
    <label class="checkbox-row"><input type="checkbox" name="receivables_send_creator" value="1" @checked($settings['receivables_send_creator']==='1')><span>Pošalji autoru porudžbine</span></label>
    <label class="checkbox-row"><input type="checkbox" name="receivables_send_supplier" value="1" @checked($settings['receivables_send_supplier']==='1')><span>Pošalji odgovornom administratoru</span></label>
    <label><span>Dodatne adrese</span><textarea name="receivables_custom_recipients" placeholder="naplata@firma.rs; direktor@firma.rs">{{ $settings['receivables_custom_recipients'] }}</textarea></label>
    <button class="button button-primary" type="submit">Sačuvaj podešavanja</button>
</form></section>
</aside>
</div>
@endif
@endsection

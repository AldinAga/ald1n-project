@extends('layouts.app')
@section('title', $case->case_number)
@section('content')
<a class="back-link" href="{{ route('admin.receivables.index') }}">← Potraživanja i naplata</a>
@php($order=$case->order)
@php($days=$order?->payment_due_at && $order->payment_due_at->copy()->startOfDay()->lt(today()) ? $order->payment_due_at->copy()->startOfDay()->diffInDays(today()) : 0)
<div class="page-heading"><div><span class="eyebrow">Predmet naplate</span><h1>{{ $case->case_number }}</h1><p>{{ $order?->order_number }} · {{ $order?->shipping_full_name ?: $order?->user?->displayName() ?: 'Kupac' }}</p></div><div class="header-button-row"><span class="status-badge receivable-status-{{ $case->status }}">{{ $statusLabels[$case->status] ?? $case->status }}</span><a class="button button-ghost" href="{{ route('admin.orders.show',$order) }}">Otvori porudžbinu</a></div></div>

<div class="stats-grid four-cards">
<article class="stat-card"><span>Ukupno</span><strong>{{ number_format((float)$order->subtotal_rsd,2,',','.') }} RSD</strong></article>
<article class="stat-card info"><span>Verifikovano plaćeno</span><strong>{{ number_format((float)$order->paid_total_rsd,2,',','.') }} RSD</strong></article>
<article class="stat-card danger"><span>Preostalo</span><strong>{{ number_format((float)$remaining,2,',','.') }} RSD</strong></article>
<article class="stat-card warning"><span>Aging</span><strong>{{ $agingLabels[$agingBucket] ?? $agingBucket }}</strong><small>{{ $days>0 ? 'Kašnjenje '.$days.' dana' : 'Nije dospelo' }}</small></article>
</div>

<div class="settings-grid operational-order-grid">
<section class="order-main-column">
<section class="panel form-section"><div class="section-heading-row"><div><h2>Plan otplate</h2><p class="muted">Rate ne predstavljaju uplatu. Zatvaraju se isključivo prema verifikovanom finansijskom ledgeru porudžbine.</p></div></div>
@if($case->installments->isNotEmpty())
<div class="table-wrap"><table><thead><tr><th>Rata</th><th>Dospeće</th><th>Iznos</th><th>Raspoređeno</th><th>Status</th><th>Napomena</th></tr></thead><tbody>@foreach($case->installments as $installment)<tr><td>#{{ $installment->sequence_no }}</td><td>{{ $installment->due_at?->format('d.m.Y') }}</td><td>{{ number_format((float)$installment->amount_rsd,2,',','.') }} RSD</td><td>{{ number_format((float)$installment->paid_amount_rsd,2,',','.') }} RSD</td><td><span class="status-badge installment-status-{{ $installment->status }}">{{ ['pending'=>'Čeka','overdue'=>'Kasni','paid'=>'Plaćena'][$installment->status] ?? $installment->status }}</span></td><td>{{ $installment->note ?: '—' }}</td></tr>@endforeach</tbody></table></div>
@endif
<details class="receivable-plan-editor" @if($errors->has('installments') || $errors->has('installments.*')) open @endif><summary class="button button-ghost">{{ $case->installments->isEmpty() ? 'Kreiraj plan otplate' : 'Zameni plan otplate' }}</summary>
<form method="post" action="{{ route('admin.receivables.plan',$case) }}" class="form-grid">@csrf @method('PUT')
<div class="installment-editor" data-installment-editor data-remaining="{{ number_format((float)$remaining,2,'.','') }}">
<div class="installment-editor-head"><strong>Rate</strong><button type="button" class="button button-ghost button-small" data-add-installment>Dodaj ratu</button></div>
<div data-installment-rows>
@php($oldRows=old('installments', $case->installments->map(fn($i)=>['due_at'=>$i->due_at?->format('Y-m-d'),'amount_rsd'=>$i->amount_rsd,'note'=>$i->note])->all()))
@forelse($oldRows as $index=>$row)
<div class="installment-row"><label><span>Datum</span><input type="date" name="installments[{{ $index }}][due_at]" value="{{ $row['due_at'] ?? '' }}" required></label><label><span>Iznos RSD</span><input type="number" step="0.01" min="0.01" name="installments[{{ $index }}][amount_rsd]" value="{{ $row['amount_rsd'] ?? '' }}" required></label><label><span>Napomena</span><input name="installments[{{ $index }}][note]" value="{{ $row['note'] ?? '' }}"></label><button type="button" class="button button-danger button-small" data-remove-installment>Ukloni</button></div>
@empty
<div class="installment-row"><label><span>Datum</span><input type="date" name="installments[0][due_at]" required></label><label><span>Iznos RSD</span><input type="number" step="0.01" min="0.01" name="installments[0][amount_rsd]" value="{{ number_format((float)$remaining,2,'.','') }}" required></label><label><span>Napomena</span><input name="installments[0][note]"></label><button type="button" class="button button-danger button-small" data-remove-installment>Ukloni</button></div>
@endforelse
</div><div class="installment-total">Zbir rata: <strong data-installment-total>0,00 RSD</strong> · Potrebno: <strong>{{ number_format((float)$remaining,2,',','.') }} RSD</strong></div></div>
<button class="button button-primary" type="submit">Sačuvaj plan</button></form></details>
</section>

<section class="panel form-section"><div class="section-heading-row"><div><h2>Komunikacija i opomene</h2><p class="muted">Javni zapisi mogu biti poslati kupcu e-mailom; interne beleške ostaju samo administraciji.</p></div></div>
<div class="receivable-thread">@forelse($case->contacts as $contact)<article class="receivable-contact {{ $contact->visible_to_customer ? 'is-public':'is-internal' }}"><div><strong>{{ $contact->subject ?: ucfirst($contact->channel) }}</strong><small>{{ $contact->contacted_at?->format('d.m.Y H:i') }} · {{ $contact->user?->displayName() ?? ($contact->is_automatic?'Automatizacija':'Sistem') }}</small></div><p>{{ $contact->note }}</p><span>{{ $contact->visible_to_customer ? 'Vidljivo kupcu':'Interno' }}</span></article>@empty<p class="muted">Još nema evidentirane komunikacije.</p>@endforelse</div>
<form method="post" action="{{ route('admin.receivables.contacts.store',$case) }}" class="form-grid two-columns receivable-contact-form">@csrf
<label><span>Kanal</span><select name="channel"><option value="phone">Telefon</option><option value="email">E-mail</option><option value="sms">SMS</option><option value="meeting">Sastanak</option><option value="internal">Interna beleška</option><option value="other">Drugo</option></select></label>
<label><span>Smer</span><select name="direction"><option value="outbound">Prema kupcu</option><option value="inbound">Od kupca</option><option value="internal">Interno</option></select></label>
<label><span>Datum i vreme</span><input type="datetime-local" name="contacted_at" value="{{ now()->format('Y-m-d\TH:i') }}"></label>
<label><span>Naslov</span><input name="subject" maxlength="190"></label>
<label class="full-width"><span>Sadržaj</span><textarea name="note" required maxlength="20000"></textarea></label>
<label class="checkbox-row full-width"><input type="checkbox" name="visible_to_customer" value="1"><span>Pošalji/učini vidljivim kupcu</span></label>
<button class="button button-primary full-width" type="submit">Evidentiraj komunikaciju</button></form>
</section>
</section>

<aside class="form-side">
<section class="panel form-section"><h2>Upravljanje predmetom</h2><form method="post" action="{{ route('admin.receivables.update',$case) }}" class="form-grid">@csrf @method('PATCH')
<label><span>Status</span><select name="status">@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected(old('status',$case->status)===$value)>{{ $label }}</option>@endforeach</select></label>
<label><span>Odgovorno lice</span><select name="assigned_to"><option value="">Nije dodeljeno</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected((int)old('assigned_to',$case->assigned_to)===(int)$assignee->id)>{{ $assignee->displayName() }}</option>@endforeach</select></label>
<label><span>Sledeća akcija</span><input type="datetime-local" name="next_action_at" value="{{ old('next_action_at',$case->next_action_at?->format('Y-m-d\TH:i')) }}"></label>
<label><span>Obećani datum uplate</span><input type="datetime-local" name="promised_payment_at" value="{{ old('promised_payment_at',$case->promised_payment_at?->format('Y-m-d\TH:i')) }}"></label>
<label><span>Interna napomena</span><textarea name="internal_note" maxlength="20000">{{ old('internal_note',$case->internal_note) }}</textarea></label>
<button class="button button-primary" type="submit">Sačuvaj predmet</button></form></section>

<section class="panel form-section"><h2>Pošalji opomenu sada</h2><p class="muted">Poruka ulazi u outbox i neće blokirati aplikaciju ako SMTP trenutno nije dostupan.</p><form method="post" action="{{ route('admin.receivables.reminder',$case) }}" class="form-grid">@csrf<label><span>Prilagođena poruka</span><textarea name="message" maxlength="20000" placeholder="Ostavi prazno za automatski tekst"></textarea></label><button class="button button-primary" type="submit">Pripremi opomenu</button></form></section>

<section class="panel form-section"><h2>Podaci o dugu</h2><dl class="detail-list"><dt>Kupac</dt><dd>{{ $order->shipping_full_name ?: $order->user?->displayName() ?: 'Kupac' }}</dd><dt>E-mail</dt><dd>{{ $order->user?->email ?: 'Nije dostupan' }}</dd><dt>Dospeće</dt><dd>{{ $order->payment_due_at?->format('d.m.Y H:i') ?: 'Nije definisano' }}</dd><dt>Način plaćanja</dt><dd>{{ $order->payment_method==='bank_transfer'?'Uplata na račun':$order->payment_method }}</dd><dt>Poslednja opomena</dt><dd>{{ $case->last_reminder_at?->format('d.m.Y H:i') ?: 'Nije poslata' }}</dd></dl></section>
</aside>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const editor = document.querySelector('[data-installment-editor]');
    if (!editor) return;
    const rows = editor.querySelector('[data-installment-rows]');
    const total = editor.querySelector('[data-installment-total]');
    const updateNames = () => Array.from(rows.children).forEach((row, index) => row.querySelectorAll('input').forEach((input) => input.name = input.name.replace(/installments\[\d+\]/, `installments[${index}]`)));
    const updateTotal = () => {
        const sum = Array.from(rows.querySelectorAll('input[name$="[amount_rsd]"]')).reduce((value, input) => value + (Number(input.value) || 0), 0);
        total.textContent = new Intl.NumberFormat('sr-RS',{minimumFractionDigits:2,maximumFractionDigits:2}).format(sum)+' RSD';
    };
    const bind = (row) => {
        row.querySelector('[data-remove-installment]')?.addEventListener('click', () => { if (rows.children.length > 1) row.remove(); updateNames(); updateTotal(); });
        row.querySelectorAll('input').forEach((input) => input.addEventListener('input', updateTotal));
    };
    Array.from(rows.children).forEach(bind);
    editor.querySelector('[data-add-installment]')?.addEventListener('click', () => {
        const index = rows.children.length;
        const row = document.createElement('div'); row.className='installment-row';
        row.innerHTML=`<label><span>Datum</span><input type="date" name="installments[${index}][due_at]" required></label><label><span>Iznos RSD</span><input type="number" step="0.01" min="0.01" name="installments[${index}][amount_rsd]" required></label><label><span>Napomena</span><input name="installments[${index}][note]"></label><button type="button" class="button button-danger button-small" data-remove-installment>Ukloni</button>`;
        rows.append(row); bind(row); updateNames(); updateTotal();
    });
    updateTotal();
})();
</script>
@endpush

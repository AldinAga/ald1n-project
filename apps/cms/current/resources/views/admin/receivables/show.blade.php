@extends('layouts.app')
@section('title', $case->case_number)
@section('content')
@php
    $order = $case->order;
    $days = $order?->payment_due_at && $order->payment_due_at->copy()->startOfDay()->lt(today()) ? $order->payment_due_at->copy()->startOfDay()->diffInDays(today()) : 0;
    $canManagePayments = request()->user()?->can('payments.manage') ?? false;
    $money = static fn ($v): string => number_format((float) $v, 2, ',', '.');
    $statusTone = ['contacted' => ' a1-pill--info', 'promised' => ' a1-pill--info', 'installment_plan' => ' a1-pill--info', 'escalated' => ' a1-pill--danger', 'disputed' => ' a1-pill--warning', 'closed' => ' a1-pill--ok'];
    $installmentTone = ['pending' => ' a1-pill--warning', 'overdue' => ' a1-pill--danger', 'paid' => ' a1-pill--ok'];
    $paymentMethodLabels = ['bank_transfer'=>'Uplata na račun','cash'=>'Gotovina','card'=>'Kartica','cash_on_delivery'=>'Pouzećem','cod'=>'Pouzećem','other'=>'Drugo'];
    $verifiedPayments = $order->payments->filter(fn($payment)=>$payment->entry_type==='payment' && $payment->status==='verified')->sortByDesc(fn($payment)=>$payment->paid_at?->timestamp ?? $payment->id);
    $oldRows = old('installments', $case->installments->map(fn($i)=>['due_at'=>$i->due_at?->format('Y-m-d'),'amount_rsd'=>$i->amount_rsd,'note'=>$i->note])->all());
@endphp
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <nav aria-label="Putanja"><ol class="a1-crumbs"><li><a href="{{ route('admin.receivables.index') }}">Potraživanja i naplata</a></li><li aria-current="page">{{ $case->case_number }}</li></ol></nav>
            <span class="a1-eyebrow">Predmet naplate</span>
            <h1 class="a1-title">{{ $case->case_number }}</h1>
            <p class="a1-lead">{{ $order?->order_number }} · {{ $order?->shipping_full_name ?: $order?->user?->displayName() ?: 'Kupac' }}</p>
        </div>
        <div class="a1-pagehead__actions">
            <span class="a1-pill{{ $statusTone[$case->status] ?? '' }}">{{ $statusLabels[$case->status] ?? $case->status }}</span>
            @if($canManagePayments && $remaining > 0)
            <a class="a1-btn a1-btn--primary" href="#evidentiraj-uplatu">Evidentiraj uplatu</a>
            @endif
            <a class="a1-btn" href="{{ route('admin.orders.show',$order) }}">Otvori porudžbinu</a>
        </div>
    </header>

    <div class="a1-grid a1-grid--kpi">
        <div class="a1-kpi"><p class="a1-kpi__label">Ukupno</p><p class="a1-kpi__value">{{ $money($order->subtotal_rsd) }} <span class="a1-kpi__unit">RSD</span></p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Verifikovano plaćeno</p><p class="a1-kpi__value">{{ $money($order->paid_total_rsd) }} <span class="a1-kpi__unit">RSD</span></p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Preostalo</p><p class="a1-kpi__value">{{ $money($remaining) }} <span class="a1-kpi__unit">RSD</span></p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Aging</p><p class="a1-kpi__value">{{ $agingLabels[$agingBucket] ?? $agingBucket }}</p><p class="a1-kpi__note">{{ $days>0 ? 'Kašnjenje '.$days.' dana' : 'Nije dospelo' }}</p></div>
    </div>

    <section class="a1-card a1-od-anchor" id="evidentiraj-uplatu" aria-labelledby="rc-pay-title">
        <div>
            <h2 class="a1-h2" id="rc-pay-title">Evidentiraj uplatu</h2>
            <p class="a1-muted">Unesi stvarni datum i iznos uplate. Sistem automatski raspoređuje iznos na najstarije otvorene rate, isto kao u Android aplikaciji.</p>
        </div>
        @if($canManagePayments && $remaining > 0)
        @include('admin.payments.partials.record-form', ['action' => route('admin.receivables.payments.store',$case), 'remaining' => (float) $remaining, 'allowRefund' => false, 'prefix' => 'rc-pay'])
        @elseif($canManagePayments)
        <div class="a1-alert a1-alert--ok" role="status"><div class="a1-alert__body"><p>Potraživanje je u celosti izmireno.</p></div></div>
        @endif

        <div>
            <h3 class="a1-h3">Stvarne uplate</h3>
            <p class="a1-muted">Prikazane su samo verifikovane uplate iz finansijskog ledgera.</p>
        </div>
        @if($verifiedPayments->isNotEmpty())
        <div class="a1-tablewrap" role="region" aria-label="Stvarne uplate" tabindex="0">
            <table class="a1-table a1-table--cards">
                <caption class="a1-sr">Stvarne uplate</caption>
                <thead><tr><th scope="col">Datum</th><th scope="col">Iznos</th><th scope="col">Način</th><th scope="col">Referenca</th><th scope="col">Raspodela na rate</th><th scope="col">Napomena</th></tr></thead>
                <tbody>
                @foreach($verifiedPayments as $payment)
                @php $caseAllocations = $payment->receivableAllocations->where('receivable_case_id',$case->id)->sortBy(fn($allocation)=>$allocation->installment?->sequence_no ?? PHP_INT_MAX); @endphp
                <tr>
                    <td class="a1-table__primary" data-label="Datum">{{ $payment->paid_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    <td class="a1-table__num" data-label="Iznos"><strong>{{ $money($payment->amount_rsd) }} RSD</strong></td>
                    <td data-label="Način">{{ $paymentMethodLabels[$payment->payment_method] ?? $payment->payment_method }}</td>
                    <td data-label="Referenca">{{ $payment->reference ?: '—' }}</td>
                    <td data-label="Raspodela na rate">@forelse($caseAllocations as $allocation)<span class="a1-ol-sub">Rata #{{ $allocation->installment?->sequence_no ?? '?' }} · {{ $money($allocation->amount_rsd) }} RSD</span>@empty<span class="a1-muted">Bez raspodele na ovaj predmet</span>@endforelse</td>
                    <td data-label="Napomena">{{ $payment->note ?: '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="wallet" :size="28" :shell="true" /></span><p>Još nema evidentiranih uplata.</p></div>
        @endif
    </section>

    <div class="a1-g12">
        <div class="a1-lg-8 a1-stack">
            <section class="a1-card" aria-labelledby="rc-plan-title">
                <div>
                    <h2 class="a1-h2" id="rc-plan-title">Plan otplate</h2>
                    <p class="a1-muted">Rate ne predstavljaju uplatu. Zatvaraju se isključivo prema verifikovanom finansijskom ledgeru porudžbine.</p>
                </div>
                @if($case->installments->isNotEmpty())
                <div class="a1-tablewrap" role="region" aria-label="Rate" tabindex="0">
                    <table class="a1-table a1-table--cards">
                        <caption class="a1-sr">Plan otplate</caption>
                        <thead><tr><th scope="col">Rata</th><th scope="col">Dospeće</th><th scope="col">Iznos</th><th scope="col">Raspoređeno</th><th scope="col">Status</th><th scope="col">Napomena</th></tr></thead>
                        <tbody>
                        @foreach($case->installments as $installment)
                        <tr>
                            <td class="a1-table__primary" data-label="Rata">#{{ $installment->sequence_no }}</td>
                            <td data-label="Dospeće">{{ $installment->due_at?->format('d.m.Y') }}</td>
                            <td class="a1-table__num" data-label="Iznos">{{ $money($installment->amount_rsd) }} RSD</td>
                            <td class="a1-table__num" data-label="Raspoređeno">{{ $money($installment->paid_amount_rsd) }} RSD</td>
                            <td data-label="Status"><span class="a1-pill{{ $installmentTone[$installment->status] ?? '' }}">{{ ['pending'=>'Čeka','overdue'=>'Kasni','paid'=>'Plaćena'][$installment->status] ?? $installment->status }}</span></td>
                            <td data-label="Napomena">{{ $installment->note ?: '—' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                <details class="a1-acc" data-installment-plan @if($errors->has('installments') || $errors->has('installments.*')) open @endif>
                    <summary>{{ $case->installments->isEmpty() ? 'Kreiraj plan otplate' : 'Zameni plan otplate' }}</summary>
                    <div class="a1-acc__body">
                        <form method="post" action="{{ route('admin.receivables.plan',$case) }}" class="a1-form">@csrf @method('PUT')
                            <div class="a1-stack" data-installment-editor data-remaining="{{ number_format((float)$remaining,2,'.','') }}">
                                <div class="a1-cluster"><strong>Rate</strong><button type="button" class="a1-btn a1-btn--sm" data-add-installment>Dodaj ratu</button></div>
                                <div class="a1-stack" data-installment-rows>
                                @forelse($oldRows as $index=>$row)
                                    <div class="a1-grid a1-grid--2" data-installment-row>
                                        <label class="a1-field"><span class="a1-label">Datum</span><input class="a1-input" type="date" name="installments[{{ $index }}][due_at]" value="{{ $row['due_at'] ?? '' }}" required></label>
                                        <label class="a1-field"><span class="a1-label">Iznos RSD</span><input class="a1-input" type="number" step="0.01" min="0.01" name="installments[{{ $index }}][amount_rsd]" value="{{ $row['amount_rsd'] ?? '' }}" required></label>
                                        <label class="a1-field"><span class="a1-label">Napomena</span><input class="a1-input" name="installments[{{ $index }}][note]" value="{{ $row['note'] ?? '' }}"></label>
                                        <div class="a1-field"><button type="button" class="a1-btn a1-btn--danger a1-btn--sm a1-end" data-remove-installment>Ukloni</button></div>
                                    </div>
                                @empty
                                    <div class="a1-grid a1-grid--2" data-installment-row>
                                        <label class="a1-field"><span class="a1-label">Datum</span><input class="a1-input" type="date" name="installments[0][due_at]" required></label>
                                        <label class="a1-field"><span class="a1-label">Iznos RSD</span><input class="a1-input" type="number" step="0.01" min="0.01" name="installments[0][amount_rsd]" value="{{ number_format((float)$remaining,2,'.','') }}" required></label>
                                        <label class="a1-field"><span class="a1-label">Napomena</span><input class="a1-input" name="installments[0][note]"></label>
                                        <div class="a1-field"><button type="button" class="a1-btn a1-btn--danger a1-btn--sm a1-end" data-remove-installment>Ukloni</button></div>
                                    </div>
                                @endforelse
                                </div>
                                <p class="a1-text" aria-live="polite">Zbir rata: <strong data-installment-total>0,00 RSD</strong> · Potrebno: <strong>{{ $money($remaining) }} RSD</strong></p>
                            </div>
                            <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj plan</button>
                        </form>
                    </div>
                </details>
            </section>

            <section class="a1-card" aria-labelledby="rc-contact-title">
                <div>
                    <h2 class="a1-h2" id="rc-contact-title">Komunikacija i opomene</h2>
                    <p class="a1-muted">Javni zapisi mogu biti poslati kupcu e-mailom; interne beleške ostaju samo administraciji.</p>
                </div>
                @if($case->contacts->isEmpty())
                <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="bell" :size="28" :shell="true" /></span><p>Još nema evidentirane komunikacije.</p></div>
                @else
                <ul class="a1-list">
                    @foreach($case->contacts as $contact)
                    <li class="a1-item a1-item--stack a1-item--static">
                        <div class="a1-item__main">
                            <span class="a1-item__title">{{ $contact->subject ?: ucfirst($contact->channel) }}</span>
                            <span class="a1-item__sub">{{ $contact->contacted_at?->format('d.m.Y H:i') }} · {{ $contact->user?->displayName() ?? ($contact->is_automatic?'Automatizacija':'Sistem') }}</span>
                            <p class="a1-text">{{ $contact->note }}</p>
                            <span class="a1-pill{{ $contact->visible_to_customer ? ' a1-pill--info' : '' }}">{{ $contact->visible_to_customer ? 'Vidljivo kupcu':'Interno' }}</span>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @endif
                <form method="post" action="{{ route('admin.receivables.contacts.store',$case) }}" class="a1-form">@csrf
                    <div class="a1-grid a1-grid--2">
                        <div class="a1-field"><label class="a1-label" for="rc-channel">Kanal</label><select class="a1-select" id="rc-channel" name="channel"><option value="phone">Telefon</option><option value="email">E-mail</option><option value="sms">SMS</option><option value="meeting">Sastanak</option><option value="internal">Interna beleška</option><option value="other">Drugo</option></select></div>
                        <div class="a1-field"><label class="a1-label" for="rc-direction">Smer</label><select class="a1-select" id="rc-direction" name="direction"><option value="outbound">Prema kupcu</option><option value="inbound">Od kupca</option><option value="internal">Interno</option></select></div>
                        <div class="a1-field"><label class="a1-label" for="rc-contacted">Datum i vreme</label><input class="a1-input" id="rc-contacted" type="datetime-local" name="contacted_at" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                        <div class="a1-field"><label class="a1-label" for="rc-subject">Naslov</label><input class="a1-input" id="rc-subject" name="subject" maxlength="190"></div>
                    </div>
                    <div class="a1-field"><label class="a1-label" for="rc-note">Sadržaj<span class="a1-req" aria-hidden="true">*</span></label><textarea class="a1-textarea" id="rc-note" name="note" required maxlength="20000"></textarea></div>
                    <label class="a1-check"><input class="a1-check__input" type="checkbox" name="visible_to_customer" value="1"><span class="a1-check__box" aria-hidden="true"></span><span>Pošalji/učini vidljivim kupcu</span></label>
                    <button class="a1-btn a1-btn--primary" type="submit">Evidentiraj komunikaciju</button>
                </form>
            </section>
        </div>

        <aside class="a1-lg-4 a1-stack">
            <section class="a1-card" aria-labelledby="rc-manage-title">
                <h2 class="a1-h2" id="rc-manage-title">Upravljanje predmetom</h2>
                <form method="post" action="{{ route('admin.receivables.update',$case) }}" class="a1-form">@csrf @method('PATCH')
                    <div class="a1-field"><label class="a1-label" for="rc-m-status">Status</label><select class="a1-select" id="rc-m-status" name="status">@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected(old('status',$case->status)===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="a1-field"><label class="a1-label" for="rc-m-assignee">Odgovorno lice</label><select class="a1-select" id="rc-m-assignee" name="assigned_to"><option value="">Nije dodeljeno</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected((int)old('assigned_to',$case->assigned_to)===(int)$assignee->id)>{{ $assignee->displayName() }}</option>@endforeach</select></div>
                    <div class="a1-field"><label class="a1-label" for="rc-m-next">Sledeća akcija</label><input class="a1-input" id="rc-m-next" type="datetime-local" name="next_action_at" value="{{ old('next_action_at',$case->next_action_at?->format('Y-m-d\TH:i')) }}"></div>
                    <div class="a1-field"><label class="a1-label" for="rc-m-promised">Obećani datum uplate</label><input class="a1-input" id="rc-m-promised" type="datetime-local" name="promised_payment_at" value="{{ old('promised_payment_at',$case->promised_payment_at?->format('Y-m-d\TH:i')) }}"></div>
                    <div class="a1-field"><label class="a1-label" for="rc-m-note">Interna napomena</label><textarea class="a1-textarea" id="rc-m-note" name="internal_note" maxlength="20000">{{ old('internal_note',$case->internal_note) }}</textarea></div>
                    <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj predmet</button>
                </form>
            </section>

            <section class="a1-card" aria-labelledby="rc-reminder-title">
                <div>
                    <h2 class="a1-h2" id="rc-reminder-title">Pošalji opomenu sada</h2>
                    <p class="a1-muted">Poruka ulazi u outbox i neće blokirati aplikaciju ako SMTP trenutno nije dostupan.</p>
                </div>
                <form method="post" action="{{ route('admin.receivables.reminder',$case) }}" class="a1-form">@csrf
                    <div class="a1-field"><label class="a1-label" for="rc-r-message">Prilagođena poruka</label><textarea class="a1-textarea" id="rc-r-message" name="message" maxlength="20000" placeholder="Ostavi prazno za automatski tekst"></textarea></div>
                    <button class="a1-btn a1-btn--primary" type="submit">Pripremi opomenu</button>
                </form>
            </section>

            <section class="a1-card" aria-labelledby="rc-debt-title">
                <h2 class="a1-h2" id="rc-debt-title">Podaci o dugu</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Kupac</dt><dd>{{ $order->shipping_full_name ?: $order->user?->displayName() ?: 'Kupac' }}</dd></div>
                    <div class="a1-dl__row"><dt>E-mail</dt><dd>{{ $order->user?->email ?: 'Nije dostupan' }}</dd></div>
                    <div class="a1-dl__row"><dt>Dospeće</dt><dd>{{ $order->payment_due_at?->format('d.m.Y H:i') ?: 'Nije definisano' }}</dd></div>
                    <div class="a1-dl__row"><dt>Način plaćanja</dt><dd>{{ $order->payment_method==='bank_transfer'?'Uplata na račun':$order->payment_method }}</dd></div>
                    <div class="a1-dl__row"><dt>Poslednja opomena</dt><dd>{{ $case->last_reminder_at?->format('d.m.Y H:i') ?: 'Nije poslata' }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
</div>
@endsection
@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
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
        const row = document.createElement('div'); row.className='a1-grid a1-grid--2'; row.setAttribute('data-installment-row','');
        row.innerHTML=`<label class="a1-field"><span class="a1-label">Datum</span><input class="a1-input" type="date" name="installments[${index}][due_at]" required></label><label class="a1-field"><span class="a1-label">Iznos RSD</span><input class="a1-input" type="number" step="0.01" min="0.01" name="installments[${index}][amount_rsd]" required></label><label class="a1-field"><span class="a1-label">Napomena</span><input class="a1-input" name="installments[${index}][note]"></label><div class="a1-field"><button type="button" class="a1-btn a1-btn--danger a1-btn--sm a1-end" data-remove-installment>Ukloni</button></div>`;
        rows.append(row); bind(row); updateNames(); updateTotal();
    });
    updateTotal();
})();
</script>
@endpush

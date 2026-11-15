@php
    // Module 3 (admin order detail on Kobalt components). Author: Ald1n <https://ald1n.com>
    $order = $detail['order'] ?? [];
    $payments = $detail['payments'] ?? [];
    $permissions = $detail['permissions'] ?? [];
    $urls = $detail['urls'] ?? [];
    $formDefaults = $detail['form_defaults'] ?? [];
    $isCompleted = (bool) ($order['is_completed'] ?? false);
    $canManagePayments = (bool) ($permissions['manage_payments'] ?? false) && !$isCompleted;
    $payPill = static fn (string $tone): string => 'a1-pill'.($tone !== '' ? ' a1-pill--'.$tone : '');
    $payStateTone = match ((string) ($order['payment_state'] ?? 'unpaid')) {
        'paid', 'overpaid' => 'ok',
        'partial' => 'warning',
        'cancelled' => 'danger',
        'unpaid' => 'off',
        default => '',
    };
    // Record status: the Serbian words the same screen already uses ("Čeka proveru", "Verifikovano", "Odbij", "Storniraj").
    $recordLabels = ['submitted' => 'Čeka proveru', 'verified' => 'Verifikovano', 'rejected' => 'Odbijeno', 'voided' => 'Stornirano'];
    $recordTones = ['submitted' => 'warning', 'verified' => 'ok', 'rejected' => 'danger', 'voided' => 'danger'];
    $ctl = fn (string $field, string $id): string => $errors->has($field)
        ? ' aria-invalid="true" aria-describedby="'.e($id).'-err"'
        : '';
@endphp

<section class="a1-card" aria-labelledby="od-pay-title">
    <div class="a1-card__head">
        <div class="a1-od-titleblock">
            <h2 class="a1-h2" id="od-pay-title">Uplate i saldo</h2>
            <p class="a1-text">Verifikovane uplate i refundacije automatski određuju saldo.</p>
        </div>
        <span class="{{ $payPill($payStateTone) }}">{{ $order['payment_state_label'] ?? 'Nije plaćeno' }}</span>
    </div>

    <dl class="a1-od-facts">
        <div class="a1-od-fact a1-od-fact--money"><dt>Vrednost</dt><dd>{{ $order['subtotal_rsd_display'] ?? '0,00 RSD' }}</dd></div>
        <div class="a1-od-fact a1-od-fact--money"><dt>Verifikovano</dt><dd>{{ $order['paid_total_rsd_display'] ?? '0,00 RSD' }}</dd></div>
        <div class="a1-od-fact a1-od-fact--money"><dt>Preostalo</dt><dd>{{ $order['remaining_rsd_display'] ?? '0,00 RSD' }}</dd></div>
        <div class="a1-od-fact"><dt>Dospeće</dt><dd>{{ $order['payment_due_at'] ?? '—' }}</dd></div>
    </dl>

    <div class="a1-tablewrap" role="region" aria-label="Evidencija uplata" tabindex="0">
        <table class="a1-table a1-table--cards" role="table">
            <caption class="a1-sr">Uplate i refundacije: broj, tip, iznos, metod, datum, status, dokaz i radnje</caption>
            <thead role="rowgroup">
                <tr role="row">
                    <th scope="col" role="columnheader">Broj</th>
                    <th scope="col" role="columnheader">Tip</th>
                    <th scope="col" role="columnheader" class="a1-table__num">Iznos</th>
                    <th scope="col" role="columnheader">Metod</th>
                    <th scope="col" role="columnheader">Datum</th>
                    <th scope="col" role="columnheader">Status</th>
                    <th scope="col" role="columnheader">Dokaz</th>
                    <th scope="col" role="columnheader"><span class="a1-sr">Radnje</span></th>
                </tr>
            </thead>
            <tbody role="rowgroup">
                @forelse($payments as $payment)
                    @php
                        $recordStatus = (string) ($payment['status'] ?? 'submitted');
                        $paymentRef = $payment['number'] ?? 'Uplata';
                        $paymentKey = $payment['id'] ?? $loop->index;
                    @endphp
                    <tr role="row">
                        <td role="cell" class="a1-table__primary" data-label="Broj"><strong>{{ $paymentRef }}</strong></td>
                        <td role="cell" data-label="Tip">{{ $payment['entry_label'] ?? 'Uplata' }}</td>
                        <td role="cell" class="a1-table__num" data-label="Iznos">{{ $payment['amount_display'] ?? '0,00 RSD' }}</td>
                        <td role="cell" data-label="Metod">{{ $payment['payment_method'] ?? '—' }}</td>
                        <td role="cell" data-label="Datum">{{ $payment['paid_at'] ?? '—' }}</td>
                        <td role="cell" data-label="Status">
                            <span class="{{ $payPill($recordTones[$recordStatus] ?? '') }}">{{ $recordLabels[$recordStatus] ?? $recordStatus }}</span>
                            @if(!empty($payment['rejection_reason']))
                                <span class="a1-od-reason">{{ $payment['rejection_reason'] }}</span>
                            @endif
                        </td>
                        <td role="cell" data-label="Dokaz">
                            @if(!empty($payment['proof_url']))
                                <a target="_blank" rel="noopener" class="a1-btn a1-btn--sm" href="{{ $payment['proof_url'] }}" aria-label="Otvori dokaz uplate {{ $paymentRef }}">Otvori</a>
                            @else
                                —
                            @endif
                        </td>
                        <td role="cell" class="a1-table__actions">
                            @if($canManagePayments)
                                <div class="a1-od-rowactions">
                                    @if(($payment['status'] ?? '') === 'submitted')
                                        @if(!empty($payment['verify_url']))
                                            <form method="post" action="{{ $payment['verify_url'] }}">
                                                @csrf
                                                <button class="a1-btn a1-btn--sm" type="submit" aria-label="Potvrdi uplatu {{ $paymentRef }}">Potvrdi</button>
                                            </form>
                                        @endif
                                        @if(!empty($payment['reject_url']))
                                            <form method="post" action="{{ $payment['reject_url'] }}">
                                                @csrf
                                                <div class="a1-field">
                                                    <label class="a1-sr" for="od-reject-{{ $paymentKey }}">Razlog odbijanja uplate {{ $paymentRef }}</label>
                                                    <input class="a1-input" id="od-reject-{{ $paymentKey }}" name="reason" required maxlength="1000" placeholder="Razlog odbijanja">
                                                </div>
                                                <button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" aria-label="Odbij uplatu {{ $paymentRef }}">Odbij</button>
                                            </form>
                                        @endif
                                    @elseif(($payment['status'] ?? '') === 'verified' && !empty($payment['void_url']))
                                        <form method="post" action="{{ $payment['void_url'] }}">
                                            @csrf
                                            <button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" aria-label="Storniraj stavku {{ $paymentRef }}" data-confirm="Stornirati ovu stavku?">Storniraj</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr role="row"><td role="cell" colspan="8">Još nema evidentiranih uplata.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($isCompleted)
        <div class="a1-alert" role="status">
            <x-icon name="lock" :size="20" :shell="true" />
            <div class="a1-alert__body">
                <p class="a1-alert__title">Finansije su zaključane</p>
                <p>Porudžbina je kompletirana {{ $order['completed_at'] ?? '' }}. Nove uplate, refundacije i storniranja više nisu dostupni.</p>
            </div>
        </div>
    @elseif($canManagePayments && !empty($urls['payment_store']))
        <form class="a1-form" method="post" action="{{ $urls['payment_store'] }}" aria-label="Nova stavka uplate">
            @csrf
            <p class="a1-help">Polja sa zvezdicom su obavezna.</p>
            <div class="a1-row">
                <div class="a1-field">
                    <label class="a1-label" for="od-pay-type">Tip</label>
                    <select class="a1-select" id="od-pay-type" name="entry_type"{!! $ctl('entry_type', 'od-pay-type') !!}>
                        <option value="payment">Uplata</option>
                        <option value="refund">Refundacija</option>
                    </select>
                    @error('entry_type')<p class="a1-error" id="od-pay-type-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="od-pay-amount">Iznos RSD<span class="a1-req" aria-hidden="true">*</span></label>
                    <input class="a1-input" id="od-pay-amount" type="number" inputmode="decimal" name="amount_rsd" min="0.01" step="0.01" required value="{{ ($order['remaining_rsd'] ?? 0) > 0 ? number_format((float) $order['remaining_rsd'], 2, '.', '') : '' }}"{!! $ctl('amount_rsd', 'od-pay-amount') !!}>
                    @error('amount_rsd')<p class="a1-error" id="od-pay-amount-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="od-pay-method">Metod</label>
                    <select class="a1-select" id="od-pay-method" name="payment_method"{!! $ctl('payment_method', 'od-pay-method') !!}>
                        <option value="bank_transfer">Uplata na račun</option>
                        <option value="cash">Gotovina</option>
                        <option value="cash_on_delivery">Pouzećem</option>
                        <option value="card">Kartica</option>
                        <option value="other">Drugo</option>
                    </select>
                    @error('payment_method')<p class="a1-error" id="od-pay-method-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="od-pay-at">Datum uplate<span class="a1-req" aria-hidden="true">*</span></label>
                    <input class="a1-input" id="od-pay-at" type="datetime-local" name="paid_at" value="{{ $formDefaults['paid_at'] ?? '' }}" required{!! $ctl('paid_at', 'od-pay-at') !!}>
                    @error('paid_at')<p class="a1-error" id="od-pay-at-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="od-pay-ref">Referenca</label>
                    <input class="a1-input" id="od-pay-ref" name="reference" maxlength="190"{!! $ctl('reference', 'od-pay-ref') !!}>
                    @error('reference')<p class="a1-error" id="od-pay-ref-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="od-pay-note">Napomena</label>
                    <input class="a1-input" id="od-pay-note" name="note" maxlength="2000"{!! $ctl('note', 'od-pay-note') !!}>
                    @error('note')<p class="a1-error" id="od-pay-note-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
                </div>
            </div>
            <div class="a1-od-actions">
                <button class="a1-btn" type="submit">Evidentiraj stavku</button>
            </div>
        </form>
    @endif

    @if(($order['payment_method'] ?? '') === 'bank_transfer' && !empty($detail['ips_payload']))
        <details class="a1-acc">
            <summary>IPS QR podaci za uplatu</summary>
            <div class="a1-acc__body">
                <code class="a1-od-code">{{ $detail['ips_payload'] }}</code>
            </div>
        </details>
    @endif
</section>

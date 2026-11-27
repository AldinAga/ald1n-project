@php
    // Module 3 (admin order detail on Kobalt components). Author: Ald1n <https://ald1n.com>
    $order = $detail['order'] ?? [];
    $payments = $detail['payments'] ?? [];
    $permissions = $detail['permissions'] ?? [];
    $urls = $detail['urls'] ?? [];
    $formDefaults = $detail['form_defaults'] ?? [];
    $isCompleted = (bool) ($order['is_completed'] ?? false);
    // Isporuka je odvojena od naplate: posle isporuke se uplate i dalje evidentiraju; zaključana je samo (neodložena) direktna prodaja.
    $financeLocked = (bool) ($order['is_direct_sale'] ?? false) && ($order['payment_method'] ?? '') !== 'deferred_payment';
    $canManagePayments = (bool) ($permissions['manage_payments'] ?? false) && !$financeLocked;
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
                                <div class="a1-rowact">
                                    @if(($payment['status'] ?? '') === 'submitted')
                                        @if(!empty($payment['verify_url']))
                                            <form method="post" action="{{ $payment['verify_url'] }}" class="a1-rowact__form">
                                                @csrf
                                                <x-icon-action-pill tone="ok" :label="'Potvrdi uplatu '.$paymentRef" />
                                            </form>
                                        @endif
                                        @if(!empty($payment['reject_url']))
                                            <form method="post" action="{{ $payment['reject_url'] }}" class="a1-rowact__form">
                                                @csrf
                                                <label class="a1-sr" for="od-reject-{{ $paymentKey }}">Razlog odbijanja uplate {{ $paymentRef }}</label>
                                                <input class="a1-input a1-rowact__reason" id="od-reject-{{ $paymentKey }}" name="reason" required maxlength="1000" placeholder="Razlog" title="Razlog odbijanja (obavezan)">
                                                <x-icon-action-pill tone="danger" :label="'Odbij uplatu '.$paymentRef" />
                                            </form>
                                        @endif
                                    @elseif(($payment['status'] ?? '') === 'verified' && !empty($payment['void_url']))
                                        <form method="post" action="{{ $payment['void_url'] }}" class="a1-rowact__form">
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

    @if($financeLocked)
        <div class="a1-alert" role="status">
            <x-icon name="lock" :size="20" :shell="true" />
            <div class="a1-alert__body">
                <p class="a1-alert__title">Finansije su zaključane</p>
                <p>Direktna prodaja je evidentirana {{ $order['completed_at'] ?? '' }}. Nove uplate, refundacije i storniranja idu samo kroz odobrenu postprodajnu radnju.</p>
            </div>
        </div>
    @elseif($canManagePayments && !empty($urls['payment_store']))
        @if($isCompleted && ($order['remaining_rsd'] ?? 0) > 0)
            <div class="a1-alert a1-alert--warning" role="status" data-order-open-debt>
                <x-icon name="wallet" :size="20" :shell="true" />
                <div class="a1-alert__body">
                    <p class="a1-alert__title">Isporučeno, a dug je otvoren</p>
                    <p>Preostalo {{ $order['remaining_rsd_display'] ?? '' }}. Uplate se i dalje evidentiraju ovde ili u Potraživanjima.</p>
                </div>
            </div>
        @endif
        @include('admin.payments.partials.record-form', [
            'action' => $urls['payment_store'],
            'remaining' => (float) ($order['remaining_rsd'] ?? 0),
            'allowRefund' => true,
            'paidAtDefault' => $formDefaults['paid_at'] ?? null,
            'prefix' => 'od-pay',
        ])
    @endif

    @if(($order['payment_method'] ?? '') === 'bank_transfer' && !empty($detail['ips_payload']))
        <details class="a1-acc">
            <summary id="od-ips-payload-label">IPS QR podaci za uplatu</summary>
            <div class="a1-acc__body">
                <div class="a1-od-code" tabindex="0" role="region" aria-labelledby="od-ips-payload-label"><code>{{ $detail['ips_payload'] }}</code></div>
            </div>
        </details>
    @endif
</section>

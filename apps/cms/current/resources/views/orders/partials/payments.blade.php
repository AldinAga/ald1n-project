@php
    $order = $detail['order'] ?? [];
    $payments = $detail['payments'] ?? [];
    $actions = $detail['actions'] ?? [];
    $urls = $detail['urls'] ?? [];
    $formDefaults = $detail['form_defaults'] ?? [];
    $paymentStatusLabels = ['submitted' => 'Čeka proveru', 'verified' => 'Verifikovano'];
    $paymentStatusTones = ['submitted' => 'warning', 'verified' => 'ok', 'rejected' => 'danger', 'void' => 'danger'];
    // The single primary action of the page is the "edit order" button when it is shown; otherwise the proof upload takes it.
    $headerHasPrimary = ($actions['amend'] ?? false) && !empty($urls['edit']);
@endphp

<section class="a1-card" aria-labelledby="oc-payments-title">
    <div class="a1-card__title">
        <h2 class="a1-h3" id="oc-payments-title">Uplate i potvrde</h2>
        <p class="a1-text">Pošalji dokaz uplate i prati status verifikacije.</p>
    </div>

    <dl class="a1-oc-stats">
        <div><dt>Vrednost</dt><dd>{{ $order['subtotal_rsd_display'] ?? '0,00 RSD' }}</dd></div>
        <div><dt>Potvrđeno</dt><dd>{{ $order['paid_total_rsd_display'] ?? '0,00 RSD' }}</dd></div>
        <div><dt>Preostalo</dt><dd>{{ $order['remaining_rsd_display'] ?? '0,00 RSD' }}</dd></div>
        <div><dt>Dospeće</dt><dd>{{ $order['payment_due_at'] ?? '—' }}</dd></div>
    </dl>

    @if(count($payments) === 0)
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="wallet" :size="28" :shell="true" /></span>
            <p>Još nema evidentiranih uplata.</p>
        </div>
    @else
        <div class="a1-tablewrap" role="region" aria-label="Evidentirane uplate" tabindex="0">
            <table class="a1-table a1-table--cards" role="table">
                <caption class="a1-sr">Evidentirane uplate</caption>
                <thead role="rowgroup">
                    <tr role="row">
                        <th scope="col" role="columnheader">Broj</th>
                        <th scope="col" role="columnheader" class="a1-table__num">Iznos</th>
                        <th scope="col" role="columnheader">Datum</th>
                        <th scope="col" role="columnheader">Status</th>
                        <th scope="col" role="columnheader">Potvrda</th>
                    </tr>
                </thead>
                <tbody role="rowgroup">
                    @foreach($payments as $payment)
                        @php($paymentStatus = (string) ($payment['status'] ?? 'submitted'))
                        <tr role="row">
                            <td role="cell" class="a1-table__primary" data-label="Broj">{{ $payment['number'] ?? 'Uplata' }}</td>
                            <td role="cell" class="a1-table__num" data-label="Iznos">{{ $payment['amount_display'] ?? '0,00 RSD' }}</td>
                            <td role="cell" data-label="Datum">{{ $payment['paid_at'] ?? '—' }}</td>
                            <td role="cell" data-label="Status"><span class="a1-pill a1-pill--{{ $paymentStatusTones[$paymentStatus] ?? 'off' }}">{{ $paymentStatusLabels[$paymentStatus] ?? $paymentStatus }}</span></td>
                            <td role="cell" data-label="Potvrda">
                                @if(!empty($payment['proof_url']))
                                    <a class="a1-btn a1-btn--sm" target="_blank" rel="noopener" href="{{ $payment['proof_url'] }}" aria-label="Otvori potvrdu uplate {{ $payment['number'] ?? 'Uplata' }}">Otvori</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(($actions['upload_payment_proof'] ?? false) && !empty($urls['payment_proof_store']))
        <form class="a1-form" method="post" enctype="multipart/form-data" action="{{ $urls['payment_proof_store'] }}">
            @csrf
            <p class="a1-help">Polja označena zvezdicom su obavezna.</p>
            <div class="a1-row">
                <div class="a1-field">
                    <label class="a1-label" for="oc-pay-amount">Iznos RSD <span class="a1-req" aria-hidden="true">*</span></label>
                    <div class="a1-group">
                        <input class="a1-input a1-oc-qty" id="oc-pay-amount" type="number" name="amount_rsd" min="0.01" step="0.01" inputmode="decimal" value="{{ number_format((float) ($order['remaining_rsd'] ?? 0), 2, '.', '') }}" required @error('amount_rsd') aria-invalid="true" aria-describedby="oc-pay-amount-err"@enderror>
                        <span class="a1-group__unit">RSD</span>
                    </div>
                    @error('amount_rsd')<p class="a1-error" id="oc-pay-amount-err">{{ $message }}</p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="oc-pay-paid-at">Datum uplate <span class="a1-req" aria-hidden="true">*</span></label>
                    <input class="a1-input" id="oc-pay-paid-at" type="datetime-local" name="paid_at" value="{{ $formDefaults['paid_at'] ?? '' }}" required @error('paid_at') aria-invalid="true" aria-describedby="oc-pay-paid-at-err"@enderror>
                    @error('paid_at')<p class="a1-error" id="oc-pay-paid-at-err">{{ $message }}</p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="oc-pay-reference">Referenca</label>
                    <input class="a1-input" id="oc-pay-reference" name="reference" maxlength="190"@error('reference') aria-invalid="true" aria-describedby="oc-pay-reference-err"@enderror>
                    @error('reference')<p class="a1-error" id="oc-pay-reference-err">{{ $message }}</p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="oc-pay-proof">Potvrda (PDF/JPG/PNG/WEBP, do 10 MB) <span class="a1-req" aria-hidden="true">*</span></label>
                    <input class="a1-input" id="oc-pay-proof" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png,.webp" required @error('proof') aria-invalid="true" aria-describedby="oc-pay-proof-err"@enderror>
                    @error('proof')<p class="a1-error" id="oc-pay-proof-err">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="oc-pay-note">Napomena</label>
                <textarea class="a1-textarea" id="oc-pay-note" name="note" rows="2" maxlength="2000"@error('note') aria-invalid="true" aria-describedby="oc-pay-note-err"@enderror></textarea>
                @error('note')<p class="a1-error" id="oc-pay-note-err">{{ $message }}</p>@enderror
            </div>
            <div><button class="a1-btn{{ $headerHasPrimary ? '' : ' a1-btn--primary' }}" type="submit">Pošalji potvrdu uplate</button></div>
        </form>
    @endif

    @if(($order['payment_method'] ?? '') === 'bank_transfer' && !empty($detail['ips_payload']))
        <details class="a1-acc">
            <summary>IPS QR podaci za uplatu</summary>
            <div class="a1-acc__body"><code class="a1-oc-code">{{ $detail['ips_payload'] }}</code></div>
        </details>
    @endif
</section>

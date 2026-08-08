@php
    $order = $detail['order'] ?? [];
    $payments = $detail['payments'] ?? [];
    $actions = $detail['actions'] ?? [];
    $urls = $detail['urls'] ?? [];
    $formDefaults = $detail['form_defaults'] ?? [];
@endphp

<section class="panel form-section payment-ledger-panel">
    <div class="section-heading-row">
        <div>
            <h2>Uplate i potvrde</h2>
            <p class="muted">Pošalji dokaz uplate i prati status verifikacije.</p>
        </div>
    </div>

    <div class="payment-summary-grid">
        <div><small>Vrednost</small><strong>{{ $order['subtotal_rsd_display'] ?? '0,00 RSD' }}</strong></div>
        <div><small>Potvrđeno</small><strong>{{ $order['paid_total_rsd_display'] ?? '0,00 RSD' }}</strong></div>
        <div><small>Preostalo</small><strong>{{ $order['remaining_rsd_display'] ?? '0,00 RSD' }}</strong></div>
        <div><small>Dospeće</small><strong>{{ $order['payment_due_at'] ?? '—' }}</strong></div>
    </div>

    <div class="admin-table-wrap flat-table">
        <table class="admin-table">
            <thead>
                <tr><th>Broj</th><th>Iznos</th><th>Datum</th><th>Status</th><th>Potvrda</th></tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment['number'] ?? 'Uplata' }}</td>
                        <td>{{ $payment['amount_display'] ?? '0,00 RSD' }}</td>
                        <td>{{ $payment['paid_at'] ?? '—' }}</td>
                        <td>{{ $payment['status'] ?? 'submitted' }}</td>
                        <td>
                            @if(!empty($payment['proof_url']))
                                <a class="button button-ghost button-small" target="_blank" rel="noopener" href="{{ $payment['proof_url'] }}">Otvori</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Još nema evidentiranih uplata.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(($actions['upload_payment_proof'] ?? false) && !empty($urls['payment_proof_store']))
        <form class="payment-proof-form" method="post" enctype="multipart/form-data" action="{{ $urls['payment_proof_store'] }}">
            @csrf
            <div class="form-grid">
                <label>
                    <span>Iznos RSD</span>
                    <input type="number" name="amount_rsd" min="0.01" step="0.01" value="{{ number_format((float) ($order['remaining_rsd'] ?? 0), 2, '.', '') }}" required>
                </label>
                <label>
                    <span>Datum uplate</span>
                    <input type="datetime-local" name="paid_at" value="{{ $formDefaults['paid_at'] ?? '' }}" required>
                </label>
                <label>
                    <span>Referenca</span>
                    <input name="reference" maxlength="190">
                </label>
                <label>
                    <span>Potvrda (PDF/JPG/PNG/WEBP, do 10 MB)</span>
                    <input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                </label>
                <label class="field-span-2">
                    <span>Napomena</span>
                    <textarea name="note" rows="2" maxlength="2000"></textarea>
                </label>
            </div>
            <button class="button button-primary" type="submit">Pošalji potvrdu uplate</button>
        </form>
    @endif

    @if(($order['payment_method'] ?? '') === 'bank_transfer' && !empty($detail['ips_payload']))
        <details class="ips-payload">
            <summary>IPS QR podaci za uplatu</summary>
            <code>{{ $detail['ips_payload'] }}</code>
        </details>
    @endif
</section>

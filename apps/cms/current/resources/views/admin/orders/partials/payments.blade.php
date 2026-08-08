@php
    $order = $detail['order'] ?? [];
    $payments = $detail['payments'] ?? [];
    $permissions = $detail['permissions'] ?? [];
    $urls = $detail['urls'] ?? [];
    $formDefaults = $detail['form_defaults'] ?? [];
    $isCompleted = (bool) ($order['is_completed'] ?? false);
    $canManagePayments = (bool) ($permissions['manage_payments'] ?? false) && !$isCompleted;
@endphp

<section class="panel form-section payment-ledger-panel">
    <div class="section-heading-row">
        <div>
            <h2>Uplate i saldo</h2>
            <p class="muted">Verifikovane uplate i refundacije automatski određuju saldo.</p>
        </div>
        <span class="status-badge payment-state-{{ $order['payment_state'] ?? 'unpaid' }}">
            {{ $order['payment_state_label'] ?? 'Nije plaćeno' }}
        </span>
    </div>

    <div class="payment-summary-grid">
        <div><small>Vrednost</small><strong>{{ $order['subtotal_rsd_display'] ?? '0,00 RSD' }}</strong></div>
        <div><small>Verifikovano</small><strong>{{ $order['paid_total_rsd_display'] ?? '0,00 RSD' }}</strong></div>
        <div><small>Preostalo</small><strong>{{ $order['remaining_rsd_display'] ?? '0,00 RSD' }}</strong></div>
        <div><small>Dospeće</small><strong>{{ $order['payment_due_at'] ?? '—' }}</strong></div>
    </div>

    <div class="admin-table-wrap flat-table">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Broj</th>
                    <th>Tip</th>
                    <th>Iznos</th>
                    <th>Metod</th>
                    <th>Datum</th>
                    <th>Status</th>
                    <th>Dokaz</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td><strong>{{ $payment['number'] ?? 'Uplata' }}</strong></td>
                        <td>{{ $payment['entry_label'] ?? 'Uplata' }}</td>
                        <td class="{{ $payment['amount_class'] ?? '' }}">{{ $payment['amount_display'] ?? '0,00 RSD' }}</td>
                        <td>{{ $payment['payment_method'] ?? '—' }}</td>
                        <td>{{ $payment['paid_at'] ?? '—' }}</td>
                        <td>
                            <span class="status-badge payment-record-{{ $payment['status'] ?? 'submitted' }}">
                                {{ $payment['status'] ?? 'submitted' }}
                            </span>
                            @if(!empty($payment['rejection_reason']))
                                <small class="muted">{{ $payment['rejection_reason'] }}</small>
                            @endif
                        </td>
                        <td>
                            @if(!empty($payment['proof_url']))
                                <a target="_blank" rel="noopener" class="button button-ghost button-small" href="{{ $payment['proof_url'] }}">Otvori</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($canManagePayments)
                                <div class="payment-actions">
                                    @if(($payment['status'] ?? '') === 'submitted')
                                        @if(!empty($payment['verify_url']))
                                            <form method="post" action="{{ $payment['verify_url'] }}">
                                                @csrf
                                                <button class="button button-primary button-small" type="submit">Potvrdi</button>
                                            </form>
                                        @endif
                                        @if(!empty($payment['reject_url']))
                                            <form method="post" action="{{ $payment['reject_url'] }}">
                                                @csrf
                                                <label class="sr-only">Razlog</label>
                                                <input name="reason" required maxlength="1000" placeholder="Razlog odbijanja">
                                                <button class="button button-danger button-small" type="submit">Odbij</button>
                                            </form>
                                        @endif
                                    @elseif(($payment['status'] ?? '') === 'verified' && !empty($payment['void_url']))
                                        <form method="post" action="{{ $payment['void_url'] }}">
                                            @csrf
                                            <button class="button button-danger button-small" type="submit" data-confirm="Stornirati ovu stavku?">Storniraj</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Još nema evidentiranih uplata.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($isCompleted)
        <div class="completion-lock-note">
            <strong>Finansije su zaključane</strong>
            <span>Porudžbina je kompletirana {{ $order['completed_at'] ?? '' }}. Nove uplate, refundacije i storniranja više nisu dostupni.</span>
        </div>
    @elseif($canManagePayments && !empty($urls['payment_store']))
        <form class="payment-entry-form" method="post" action="{{ $urls['payment_store'] }}">
            @csrf
            <div class="form-grid">
                <label>
                    <span>Tip</span>
                    <select name="entry_type">
                        <option value="payment">Uplata</option>
                        <option value="refund">Refundacija</option>
                    </select>
                </label>
                <label>
                    <span>Iznos RSD</span>
                    <input type="number" name="amount_rsd" min="0.01" step="0.01" required value="{{ ($order['remaining_rsd'] ?? 0) > 0 ? number_format((float) $order['remaining_rsd'], 2, '.', '') : '' }}">
                </label>
                <label>
                    <span>Metod</span>
                    <select name="payment_method">
                        <option value="bank_transfer">Uplata na račun</option>
                        <option value="cash">Gotovina</option>
                        <option value="cash_on_delivery">Pouzećem</option>
                        <option value="card">Kartica</option>
                        <option value="other">Drugo</option>
                    </select>
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
                    <span>Napomena</span>
                    <input name="note" maxlength="2000">
                </label>
            </div>
            <button class="button button-primary" type="submit">Evidentiraj stavku</button>
        </form>
    @endif

    @if(($order['payment_method'] ?? '') === 'bank_transfer' && !empty($detail['ips_payload']))
        <details class="ips-payload">
            <summary>IPS QR podaci za uplatu</summary>
            <code>{{ $detail['ips_payload'] }}</code>
        </details>
    @endif
</section>

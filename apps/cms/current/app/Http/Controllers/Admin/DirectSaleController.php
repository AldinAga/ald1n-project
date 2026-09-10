<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\DirectSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class DirectSaleController extends Controller
{
    public function __invoke(Request $request, Product $product, DirectSaleService $sales): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor !== null && $actor->hasRole('superadmin'), 403);

        $data = $request->validate([
            'buyer_name' => ['nullable', 'string', 'max:190'],
            'buyer_phone' => ['nullable', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'sale_price_rsd' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer', 'other', 'deferred_payment'])],
            'installment_count' => ['exclude_unless:payment_method,deferred_payment', 'required_if:payment_method,deferred_payment', 'integer', 'min:1', 'max:24'],
            'first_payment_method' => ['exclude_unless:payment_method,deferred_payment', 'required_if:payment_method,deferred_payment', Rule::in(['cash', 'card', 'bank_transfer', 'other'])],
            'installments' => ['exclude_unless:payment_method,deferred_payment', 'required_if:payment_method,deferred_payment', 'array', 'min:1', 'max:24'],
            'installments.*.amount_rsd' => ['required_if:payment_method,deferred_payment', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'installments.*.due_at' => ['required_if:payment_method,deferred_payment', 'date_format:Y-m-d', 'after_or_equal:today'],
            'idempotency_key' => ['required', 'string', 'max:200'],
        ]);

        if ((string) $data['payment_method'] === 'deferred_payment') {
            $count = (int) $data['installment_count'];
            $rows = array_values(is_array($data['installments'] ?? null) ? $data['installments'] : []);
            if (count($rows) !== $count) {
                throw ValidationException::withMessages([
                    'installment_count' => 'Broj unetih rata mora odgovarati izabranom broju rata.',
                ]);
            }

            $today = today()->toDateString();
            $previousDue = null;
            $sumCents = 0;
            $normalized = [];
            foreach ($rows as $index => $row) {
                $dueAt = trim((string) ($row['due_at'] ?? ''));
                $amountCents = (int) round((float) ($row['amount_rsd'] ?? 0) * 100);
                if ($index === 0 && $dueAt !== $today) {
                    throw ValidationException::withMessages([
                        'installments.0.due_at' => 'Prva rata se evidentira odmah i njen datum mora biti današnji.',
                    ]);
                }
                if ($previousDue !== null && strcmp($dueAt, $previousDue) < 0) {
                    throw ValidationException::withMessages([
                        'installments.'.$index.'.due_at' => 'Datumi rata moraju biti hronološki poređani.',
                    ]);
                }
                $previousDue = $dueAt;
                $sumCents += $amountCents;
                $normalized[] = [
                    'amount_rsd' => $amountCents / 100,
                    'due_at' => $dueAt,
                ];
            }

            $expectedCents = (int) round((float) $data['sale_price_rsd'] * (int) $data['quantity'] * 100);
            if ($sumCents !== $expectedCents) {
                throw ValidationException::withMessages([
                    'installments' => 'Zbir rata mora biti jednak ukupnoj direktnoj prodaji '.number_format($expectedCents / 100, 2, ',', '.').' RSD.',
                ]);
            }

            $data['installments'] = $normalized;
            $data['payment_due_at'] = (string) $normalized[array_key_last($normalized)]['due_at'];
        }

        $order = $sales->record($product, $actor, $data, (string) $data['idempotency_key']);

        return redirect()->route('admin.orders.show', $order)->with('status', 'Direktna prodaja '.$order->order_number.' je evidentirana.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\DirectSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer', 'other'])],
            'idempotency_key' => ['required', 'string', 'max:200'],
        ]);

        $order = $sales->record($product, $actor, $data, (string) $data['idempotency_key']);

        return redirect()->route('admin.orders.show', $order)->with('status', 'Direktna prodaja '.$order->order_number.' je evidentirana.');
    }
}

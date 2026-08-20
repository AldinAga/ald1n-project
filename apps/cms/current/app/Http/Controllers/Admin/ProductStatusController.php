<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// V0_8_PRODUCT_STATUS_LIGHTWEIGHT_CONTROL_BATCH2
final class ProductStatusController extends Controller
{
    public function __invoke(Request $request, Product $product, ProductStatusService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ]);

        $result = $service->change($product, $request->user(), (string) $data['status']);
        $updated = $result['product'];
        $message = $result['changed']
            ? 'Status artikla '.$updated->sku.' je promenjen na '.match ((string) $updated->status) {
                'active' => 'Aktivan',
                'inactive' => 'Neaktivan',
                default => 'Nacrt',
            }.'.'
            : 'Status artikla '.$updated->sku.' je vec bio postavljen na izabranu vrednost.';
        if ((int) $result['announcement_count'] > 0) {
            $message .= ' E-mail obavestenje je pripremljeno za '.(int) $result['announcement_count'].' korisnika.';
        }

        return back()->with('status', $message);
    }
}

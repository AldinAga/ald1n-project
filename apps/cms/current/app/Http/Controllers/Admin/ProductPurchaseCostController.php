<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProductPurchaseCostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProductPurchaseCostController extends Controller
{
    public function index(Request $request, ProductPurchaseCostService $service): View
    {
        $this->assertSuperAdmin($request);
        $data = $service->overview($request->query('show') === 'all');

        return view('admin.products.purchase-costs', $data);
    }

    public function update(Request $request, ProductPurchaseCostService $service): RedirectResponse
    {
        $actor = $this->assertSuperAdmin($request);
        $changed = $service->update(
            (array) $request->input('costs', []),
            $actor,
            'superadmin_fast_purchase_cost_entry',
        );

        return redirect()
            ->route('admin.products.purchase-costs')
            ->with('status', $changed > 0
                ? 'Sačuvane su nabavne cene za '.$changed.' artikala.'
                : 'Nema promena za čuvanje.');
    }

    private function assertSuperAdmin(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasRole('superadmin'), 403, 'Brzi unos nabavnih cena dostupan je samo Super Administratoru.');
        return $actor;
    }
}

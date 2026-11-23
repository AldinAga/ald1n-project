<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAfterSalesCaseRequest;
use App\Models\Order;
use App\Services\AfterSalesAccessService;
use App\Services\AfterSalesCaseService;
use App\Services\OrderAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Otvori reklamaciju / servis" u ime kupca (odluka vlasnika 16.11.2026, tačka 17): odgovorno lice i superadmin na
 * isporučenoj porudžbini; isti obrazac i isti servis kao kod kupca, a slučaj pamti ko ga je otvorio. SubAgent ovo ne dobija.
 * Author: Ald1n <https://ald1n.com>
 */
final class OrderAfterSalesController extends Controller
{
    public function create(Request $request, Order $order, OrderAccessService $orders, AfterSalesAccessService $access): View
    {
        $orders->authorizeManage($order, $request->user());
        abort_unless($access->canCreateOnBehalf($order, $request->user()), 404);
        $order->load('items');

        return view('after-sales.create', [
            'order' => $order,
            'labels' => $this->labels(),
            'formAction' => route('admin.orders.after-sales.store', $order),
            'backUrl' => route('admin.orders.show', $order),
            'onBehalf' => true,
        ]);
    }

    public function store(Request $request, Order $order, OrderAccessService $orders, AfterSalesCaseService $service): RedirectResponse
    {
        $orders->authorizeManage($order, $request->user());
        $rules = new StoreAfterSalesCaseRequest();
        $data = $request->validate($rules->rules(), $rules->messages());
        $case = $service->create($order, $request->user(), $data, $request->file('attachments', []), onBehalf: true);

        return redirect()->route('admin.after-sales.show', $case)->with('status', 'Slučaj '.$case->case_number.' je otvoren u ime kupca. Sledeće: pregled i dogovor rešenja.');
    }

    /** @return array<string,array<string,string>> */
    private function labels(): array
    {
        return [
            'types' => ['complaint' => 'Reklamacija', 'return' => 'Povrat', 'service' => 'Servisni zahtev'],
            'priorities' => ['low' => 'Nizak', 'normal' => 'Normalan', 'high' => 'Visok', 'urgent' => 'Hitan'],
            'statuses' => ['open' => 'Otvoren', 'under_review' => 'U obradi', 'awaiting_customer' => 'Čeka odgovor kupca', 'approved' => 'Odobren', 'in_service' => 'Na servisu', 'resolved' => 'Rešen', 'rejected' => 'Odbijen', 'closed' => 'Zatvoren'],
            'resolutions' => ['repair' => 'Popravka', 'replacement' => 'Zamena', 'partial_refund' => 'Delimičan povraćaj novca', 'full_refund' => 'Potpun povraćaj novca', 'return' => 'Povrat robe', 'inspection' => 'Pregled na licu mesta', 'rejected' => 'Zahtev odbijen', 'other' => 'Drugo'],
        ];
    }
}

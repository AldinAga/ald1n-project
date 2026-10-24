<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PublicOrderAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PublicOrderAccessController extends Controller
{
    public function revoke(Request $request, Order $order, PublicOrderAccessService $access): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('superadmin'),403);
        abort_unless($order->sales_channel === 'public_retail',404);
        $access->revoke($order);
        return redirect()->route('admin.orders.show',$order)->with('status',
            'Privatna veza kupca za porudžbinu je opozvana.');
    }
}

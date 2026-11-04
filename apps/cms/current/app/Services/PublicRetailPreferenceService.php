<?php
declare(strict_types=1);
namespace App\Services;

use App\Models\Order;
use App\Models\PublicCheckoutAttempt;
use App\Support\PublicRetailOptions;
use Illuminate\Support\Facades\Schema;

final class PublicRetailPreferenceService
{
    public function forOrder(Order $order): ?array
    {
        if ($order->sales_channel!=='public_retail'
            || !Schema::hasColumn('public_checkout_attempts','delivery_method')) return null;
        $attempt=PublicCheckoutAttempt::query()->where('order_id',$order->id)->first();
        if ($attempt===null) return null;
        return [
            'delivery_method'=>$attempt->delivery_method,
            'delivery_label'=>PublicRetailOptions::DELIVERY[$attempt->delivery_method]??'Nije izabrano',
            'payment_label'=>PublicRetailOptions::PAYMENT[$order->payment_method]??'Nije izabrano',
            'payment_due_at'=>$attempt->payment_due_at_snapshot,
        ];
    }
}

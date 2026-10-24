<?php
declare(strict_types=1);
namespace App\Support;

/** Existing operational codes only. A requested method is NOT proof of fulfillment. */
final class PublicRetailOptions
{
    public const DELIVERY = [
        'courier'=>'Kurirska služba',
        'own_transport'=>'Sopstvena dostava',
        'customer_pickup'=>'Lično preuzimanje',
        'other'=>'Drugi dogovoreni način',
    ];
    public const PAYMENT = [
        'cash_on_delivery'=>'Pouzećem',
        'bank_transfer'=>'Uplata na račun',
        'deferred_payment'=>'Odloženo plaćanje',
    ];
}

<?php
declare(strict_types=1);
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class PublicRetailAccessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/public-retail-access.php'));
        \Illuminate\Support\Facades\View::composer('admin.orders.show',static function ($view): void {
            $data=$view->getData();
            $order=$data['order']??null;
            if (!$order instanceof \App\Models\Order) return;
            $preference=app(\App\Services\PublicRetailPreferenceService::class)->forOrder($order);
            $detail=$data['detail']??null;
            if ($preference===null || !is_array($detail)) return;
            // Presentation only: expose the buyer's choices in the existing contact
            // panel, never overwrite the stored note or fabricate a delivery record.
            $note='Izabrana dostava: '.$preference['delivery_label'].'. '
                .'Izabrano placanje: '.$preference['payment_label'].'.';
            $original=trim((string)($detail['order']['customer_note']??''));
            $detail['order']['customer_note']=$note.($original!==''?' | '.$original:'');
            $view->with('detail',$detail);
        });
    }
}

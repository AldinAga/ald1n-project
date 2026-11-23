<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * SQL za proviziju stavke porudžbine u izveštajima, iz ISTOG izvora kao stranica provizija (order_commissions):
 *  - porudžbina bez reda provizije: snimak stavke (order_items.commission_total_eur_snapshot), kao ranije;
 *  - stornirana provizija: 0;
 *  - inače order_commissions.total_eur (storno neisplaćene provizije ga već umanjuje) minus potvrđena zaduženja
 *    (isplaćena provizija; predložena zaduženja ne važe dok ih SuperAdmin ne potvrdi), ne manje od 0.
 * Iznos porudžbine se na stavke raspoređuje srazmerno njihovom snimku, pa zbir stavki uvek daje iznos sa stranice provizija.
 * Izrazi koriste jedinstvene aliase (cm, cs, ca) da ne zasene spoljne tabele izveštaja.
 */
final class CommissionSql
{
    /** Provizija stavke u EUR. @param string $item alias order_items, $order alias orders */
    public static function itemEur(string $item = 'ri', string $order = 'o'): string
    {
        $snapshot = "COALESCE($item.commission_total_eur_snapshot,0)";
        if (!Schema::hasTable('order_commissions')) {
            return "($snapshot)";
        }

        $debits = CommissionMoney::hasAdjustments()
            ? "COALESCE((SELECT SUM(ca.amount_eur) FROM commission_adjustments ca WHERE ca.commission_id = cm.id AND ca.kind = 'debit' AND ca.status = 'confirmed'),0)"
            : '0';
        $net = "(SELECT CASE WHEN cm.status = 'cancelled' THEN 0 WHEN cm.total_eur - $debits > 0 THEN cm.total_eur - $debits ELSE 0 END FROM order_commissions cm WHERE cm.order_id = $order.id ORDER BY cm.id LIMIT 1)";
        $snapshotSum = "(SELECT SUM(COALESCE(cs.commission_total_eur_snapshot,0)) FROM order_items cs WHERE cs.order_id = $order.id)";
        $itemCount = "(SELECT COUNT(*) FROM order_items cs WHERE cs.order_id = $order.id)"; // snimci su 0: srazmerno broju stavki

        return "(CASE WHEN EXISTS (SELECT 1 FROM order_commissions cm WHERE cm.order_id = $order.id) "
            ."THEN COALESCE($snapshot * $net / NULLIF($snapshotSum,0), $net / NULLIF($itemCount,0), 0) ELSE $snapshot END)";
    }

    /** Provizija stavke u RSD: EUR iznos po kursu koji je sačuvan na porudžbini (bez kursa = 0, kao ranije). */
    public static function itemRsd(string $item = 'ri', string $order = 'o'): string
    {
        return '('.self::itemEur($item, $order)." * COALESCE($order.eur_rsd_rate,0))";
    }
}

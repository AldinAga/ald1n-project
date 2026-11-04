<?php
declare(strict_types=1);
namespace Tests\Support;

/** CI-only observation of overlapping transactions, not production SQL logic. */
final class CheckoutConcurrencyWitness
{
    /** @param list<object> $rows A single simultaneous PROCESSLIST snapshot. */
    public static function inspect(array $rows, string $mode): array
    {
        $products=[];$attempts=[];
        foreach ($rows as $row) {
            $id=(int)($row->ID??0);
            $sql=strtolower(trim((string)($row->INFO??'')));
            if ($id<1) continue;
            if (str_starts_with($sql,'select ') && str_contains($sql,'`products`') && str_contains($sql,'for update')) $products[$id]=true;
            elseif (str_starts_with($sql,'insert into `public_checkout_attempts`')) $attempts[$id]=true;
        }
        $ids=array_keys($products+$attempts);sort($ids,SORT_NUMERIC);
        // Distinct checkout keys may block at different InnoDB index stages.
        // A process appears once per snapshot; never count one connection twice.
        $ready=count($ids)>=2 && match ($mode) {
            'lastunit'=>count($products)>=1,
            'duplicate'=>count($products)>=1 && count($attempts)>=1,
            default=>false,
        };
        return ['ready'=>$ready,'connection_ids'=>$ids,'product_waits'=>count($products),'attempt_inserts'=>count($attempts)];
    }
}

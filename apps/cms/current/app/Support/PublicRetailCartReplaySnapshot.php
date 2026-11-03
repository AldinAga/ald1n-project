<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Fail-closed validation for the already-committed checkout cart snapshot.
 * The live cart is sanitised separately. A replay snapshot must not silently
 * drop or coerce invalid entries, otherwise it can alter the idempotency key's
 * request fingerprint or bypass item limits.
 */
final class PublicRetailCartReplaySnapshot
{
    /** @return array<int,int> */
    public static function validated(mixed $snapshot): array
    {
        if (!is_array($snapshot) || $snapshot === [] || count($snapshot) > 25) {
            throw new \InvalidArgumentException('The replay cart shape is invalid.');
        }
        $cart = [];
        foreach ($snapshot as $id => $quantity) {
            if (!is_int($id) || $id < 1 || !is_int($quantity)
                || $quantity < 1 || $quantity > 20) {
                throw new \InvalidArgumentException('The replay cart contains an invalid product or quantity.');
            }
            $cart[$id] = $quantity;
        }
        ksort($cart, SORT_NUMERIC);
        return $cart;
    }
}

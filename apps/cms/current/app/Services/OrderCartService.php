<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

final class OrderCartService
{
    public const SESSION_KEY = 'orders.cart.items';
    public const MAX_DISTINCT_ITEMS = 50;

    /** @return array<int,int> */
    public function quantities(Request $request): array
    {
        $raw = (array) $request->session()->get(self::SESSION_KEY, []);
        $items = [];

        foreach ($raw as $productId => $quantity) {
            $id = (int) $productId;
            $qty = (int) $quantity;
            if ($id <= 0 || $qty <= 0) {
                continue;
            }
            $items[$id] = min(1000, $qty);
        }

        return $items;
    }

    public function count(Request $request): int
    {
        return array_sum($this->quantities($request));
    }

    public function distinctCount(Request $request): int
    {
        return count($this->quantities($request));
    }

    public function quantity(Request $request, int $productId): int
    {
        return $this->quantities($request)[$productId] ?? 0;
    }

    public function set(Request $request, int $productId, int $quantity): void
    {
        $items = $this->quantities($request);
        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = min(1000, $quantity);
        }
        $request->session()->put(self::SESSION_KEY, $items);
    }

    public function replace(Request $request, array $items): void
    {
        $normalized = [];
        foreach ($items as $productId => $quantity) {
            $id = (int) $productId;
            $qty = (int) $quantity;
            if ($id > 0 && $qty > 0) {
                $normalized[$id] = min(1000, $qty);
            }
        }
        $request->session()->put(self::SESSION_KEY, $normalized);
    }

    public function remove(Request $request, int $productId): void
    {
        $items = $this->quantities($request);
        unset($items[$productId]);
        $request->session()->put(self::SESSION_KEY, $items);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
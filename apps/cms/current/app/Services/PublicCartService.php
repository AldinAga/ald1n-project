<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PublicCartService
{
    private const KEY = 'ald1n.public_retail_cart.v1';

    public function __construct(private readonly PublicRetailCatalogService $catalog) {}

    /** @return array<int,int> */
    public function get(Request $request): array
    {
        $stored = $request->session()->get(self::KEY, []);
        $clean = [];
        if (!is_array($stored)) return [];
        foreach ($stored as $id => $qty) {
            $pid = filter_var($id, FILTER_VALIDATE_INT);
            $q = filter_var($qty, FILTER_VALIDATE_INT);
            if ($pid && $pid > 0 && $q && $q > 0 && $q <= 20) {
                $clean[(int) $pid] = (int) $q;
            }
            if (count($clean) >= 25) break;
        }
        ksort($clean, SORT_NUMERIC);
        return $clean;
    }

    public function change(Request $request, int $productId, int $quantity): void
    {
        if ($productId < 1 || $quantity < 0 || $quantity > 20) {
            throw ValidationException::withMessages(['quantity' => 'Nedozvoljena količina.']);
        }
        $cart = $this->get($request);
        if ($quantity === 0) {
            unset($cart[$productId]);
        } else {
            $product = $this->catalog->byId($productId);
            $this->catalog->requirePurchasable($product, $quantity);
            if (count($cart) >= 25 && !isset($cart[$productId])) {
                throw ValidationException::withMessages(['cart' => 'Korpa ima maksimalan broj različitih artikala.']);
            }
            $cart[$productId] = $quantity;
        }
        $request->session()->put(self::KEY, $cart);
    }

    /** @return array{items:list<array{product:array<string,mixed>,quantity:int,line_rsd:float}>,total_rsd:float,stale:list<int>} */
    public function quote(Request $request): array
    {
        $rows = [];
        $stale = [];
        $total = 0.0;
        foreach ($this->get($request) as $id => $qty) {
            $product = $this->catalog->available()->with(['brand:id,name','images:id,product_id'])
                ->whereKey($id)->first();
            if ($product === null) { $stale[]=$id; continue; }
            $dto = $this->catalog->present($product);
            if (!$dto['can_buy'] || $dto['stock'] < $qty || $dto['price_rsd'] === null) {
                $stale[]=$id; continue;
            }
            $line = round((float) $dto['price_rsd'] * $qty, 2);
            $rows[] = ['product'=>$dto, 'quantity'=>$qty, 'line_rsd'=>$line];
            $total = round($total + $line, 2);
        }
        return ['items'=>$rows,'total_rsd'=>$total,'stale'=>$stale];
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::KEY);
    }
}

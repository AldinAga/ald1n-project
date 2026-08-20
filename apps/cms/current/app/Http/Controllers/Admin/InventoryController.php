<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCount;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Services\AdvancedInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

final class InventoryController extends Controller
{
    private const PAGE_LIMITS = [25, 50, 100, 250];
    private const MODES = ['receive', 'count'];

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $mode = $this->mode((string) $request->query('mode', 'receive'));
        $limit = $this->limit((int) $request->query('limit', 50));
        $issues = $this->readinessIssues();

        if ($issues !== []) {
            return view('admin.inventory.index', $this->fallbackData($q, $mode, $limit, $issues));
        }

        try {
            $baseQuery = Product::query()
                ->whereNull('deleted_at')
                                ->when($q !== '', static fn ($query) => $query->where(static fn ($inner) => $inner
                    ->where('sku', 'like', '%'.$q.'%')
                    ->orWhere('name', 'like', '%'.$q.'%')));

            $matchingProductCount = (clone $baseQuery)->count();
            $products = $baseQuery
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'sku', 'name', 'stock_quantity', 'low_stock_threshold']);

            return view('admin.inventory.index', [
                'products' => $products,
                'matchingProductCount' => $matchingProductCount,
                'lowStock' => Product::query()->whereNull('deleted_at')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->limit(50)->get(),
                'receipts' => StockReceipt::query()->with('poster')->latest('id')->limit(15)->get(),
                'counts' => InventoryCount::query()->with('finalizer')->latest('id')->limit(15)->get(),
                'movements' => StockMovement::query()->with(['product', 'user', 'stockReceipt', 'inventoryCount'])->latest('id')->limit(30)->get(),
                'receiptIdempotencyKey' => (string) Str::uuid(),
                'countIdempotencyKey' => (string) Str::uuid(),
                'query' => $q,
                'mode' => $mode,
                'limit' => $limit,
                'pageLimits' => self::PAGE_LIMITS,
                'inventoryUnavailable' => null,
            ]);
        } catch (Throwable $exception) {
            $this->safeLog('Napredni lager nije mogao da se učita.', $exception, $request);

            return view('admin.inventory.index', $this->fallbackData($q, $mode, $limit, [
                'Upit nad lagerom trenutno nije uspeo. Pokrenite payments/inventory doctor sa --repair opcijom.',
            ]));
        }
    }

    public function receive(Request $request, AdvancedInventoryService $inventory): RedirectResponse
    {
        abort_if($this->readinessIssues() !== [], 503, 'Napredni lager nije spreman. Pokrenite: php artisan app:payments-inventory-doctor --repair');
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:200'],
            'received_on' => ['required', 'date_format:Y-m-d'],
            'supplier_name' => ['nullable', 'string', 'max:190'],
            'supplier_document_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:250'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            '_return_q' => ['nullable', 'string', 'max:190'],
            '_return_limit' => ['nullable', 'integer'],
        ]);
        $receipt = $inventory->receive($data, (string) $data['idempotency_key'], $request->user());

        return redirect()->route('admin.inventory.index', $this->returnQuery($data, 'receive'))
            ->with('status', 'Ulaz '.$receipt->receipt_number.' je proknjižen: '.$receipt->total_units.' komada.');
    }

    public function count(Request $request, AdvancedInventoryService $inventory): RedirectResponse
    {
        abort_if($this->readinessIssues() !== [], 503, 'Napredni lager nije spreman. Pokrenite: php artisan app:payments-inventory-doctor --repair');
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:200'],
            'counted_on' => ['required', 'date_format:Y-m-d'],
            'scope_label' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:250'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.counted_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            '_return_q' => ['nullable', 'string', 'max:190'],
            '_return_limit' => ['nullable', 'integer'],
        ]);
        $count = $inventory->finalizeCount($data, (string) $data['idempotency_key'], $request->user());

        return redirect()->route('admin.inventory.index', $this->returnQuery($data, 'count'))
            ->with('status', 'Popis '.$count->count_number.' je zaključen. Ukupna razlika: '.$count->total_variance.'.');
    }

    public function csv(Request $request): Response
    {
        if ($this->readinessIssues() !== []) {
            return response('Izvoz lagera trenutno nije dostupan. Pokrenite: php artisan app:payments-inventory-doctor --repair', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        try {
            $rows = Product::query()->whereNull('deleted_at')->orderBy('sku')->get(['sku', 'name', 'stock_quantity', 'low_stock_threshold', 'status']);
            $stream = fopen('php://temp', 'w+');
            if ($stream === false) {
                throw new \RuntimeException('Privremeni CSV stream nije dostupan.');
            }
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['SKU', 'Naziv', 'Stanje', 'Minimalni prag', 'Status', 'Upozorenje'], ';');
            foreach ($rows as $row) {
                fputcsv($stream, [$row->sku, $row->name, $row->stock_quantity, $row->low_stock_threshold, $row->status, $row->stock_quantity <= $row->low_stock_threshold ? 'NIZAK LAGER' : ''], ';');
            }
            rewind($stream);
            $content = stream_get_contents($stream) ?: '';
            fclose($stream);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="lager-'.now()->format('Ymd-His').'.csv"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (Throwable $exception) {
            $this->safeLog('CSV lagera nije mogao da se generiše.', $exception, $request);

            return response('Izvoz lagera trenutno nije dostupan.', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }

    /** @return list<string> */
    private function readinessIssues(): array
    {
        $required = [
            'products' => ['id', 'sku', 'name', 'stock_quantity', 'low_stock_threshold'],
            'stock_movements' => ['id', 'product_id', 'quantity_change', 'stock_receipt_id', 'inventory_count_id'],
            'stock_receipts' => ['id', 'receipt_number', 'status', 'received_on'],
            'stock_receipt_items' => ['id', 'stock_receipt_id', 'product_id', 'quantity'],
            'inventory_counts' => ['id', 'count_number', 'status', 'counted_on'],
            'inventory_count_items' => ['id', 'inventory_count_id', 'product_id', 'variance'],
        ];
        $issues = [];
        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $issues[] = 'Nedostaje tabela '.$table.'.';
                continue;
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $issues[] = 'Nedostaje kolona '.$table.'.'.$column.'.';
                }
            }
        }

        return $issues;
    }

    /** @param list<string> $issues @return array<string,mixed> */
    private function fallbackData(string $query, string $mode, int $limit, array $issues): array
    {
        return [
            'products' => collect(),
            'matchingProductCount' => 0,
            'lowStock' => collect(),
            'receipts' => collect(),
            'counts' => collect(),
            'movements' => collect(),
            'receiptIdempotencyKey' => (string) Str::uuid(),
            'countIdempotencyKey' => (string) Str::uuid(),
            'query' => $query,
            'mode' => $mode,
            'limit' => $limit,
            'pageLimits' => self::PAGE_LIMITS,
            'inventoryUnavailable' => implode(' ', $issues),
        ];
    }

    private function mode(string $mode): string
    {
        return in_array($mode, self::MODES, true) ? $mode : 'receive';
    }

    private function limit(int $limit): int
    {
        return in_array($limit, self::PAGE_LIMITS, true) ? $limit : 50;
    }

    /** @param array<string,mixed> $data @return array<string,int|string> */
    private function returnQuery(array $data, string $mode): array
    {
        $q = trim((string) ($data['_return_q'] ?? ''));
        $query = [
            'mode' => $mode,
            'limit' => $this->limit((int) ($data['_return_limit'] ?? 50)),
        ];
        if ($q !== '') {
            $query['q'] = $q;
        }

        return $query;
    }

    private function safeLog(string $message, Throwable $exception, Request $request): void
    {
        try {
            Log::warning($message, [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'user_id' => $request->user()?->id,
            ]);
        } catch (Throwable) {
            // Logging must not replace the controlled recovery response.
        }
    }
}

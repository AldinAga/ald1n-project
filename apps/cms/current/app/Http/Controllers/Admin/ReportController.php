<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Throwable;

final class ReportController extends Controller
{
    public function index(Request $request, OrderReportService $reports): Response
    {
        $filters = $this->filters($request);
        /** @var User $user */
        $user = $request->user();

        try {
            $issues = $reports->readinessIssues();
        } catch (Throwable $exception) {
            $this->safeLog('Reports readiness provera nije uspela.', $exception, $user);
            $issues = ['Laravel baza ili reports šema trenutno nije dostupna.'];
        }

        if ($issues !== []) {
            return $this->renderProtected($request, $user, $this->fallbackData($request, $filters, $issues));
        }

        try {
            $orders = $reports->paginate($user, $filters);
            $summary = $reports->summary($user, $filters);
        } catch (Throwable $exception) {
            $this->safeLog('Reports glavni SQL upiti nisu uspeli.', $exception, $user);

            return $this->renderProtected($request, $user, $this->fallbackData($request, $filters, [
                'Upit nad izveštajima nije uspeo. Pokrenite reports doctor i repair migracije.',
            ]));
        }

        $suppliers = collect();
        if ($user->hasRole('superadmin')) {
            try {
                $suppliers = $this->suppliers();
            } catch (Throwable $exception) {
                $this->safeLog('Lista dobavljača za Reports nije učitana.', $exception, $user);
            }
        }

        $documents = collect();
        try {
            $documents = $this->latestDocuments($user);
        } catch (Throwable $exception) {
            $this->safeLog('Lista dokumenata za Reports nije učitana.', $exception, $user);
        }

        return $this->renderProtected($request, $user, [
            'orders' => $orders,
            'summary' => $summary,
            'filters' => $filters,
            'suppliers' => $suppliers,
            'documents' => $documents,
            'paymentSummary' => $this->safePaymentSummary($user),
            'inventorySummary' => $this->safeInventorySummary($user),
            'reportUnavailable' => null,
            'canExportReports' => $user->hasPermission('reports.export'),
            'canManageDocumentSettings' => $user->hasPermission('system.manage_settings'),
        ]);
    }

    public function csv(Request $request, OrderReportService $reports): Response
    {
        $issues = $reports->readinessIssues();
        if ($issues !== []) {
            return $this->unavailableExport($issues);
        }

        try {
            $content = $reports->csv($request->user(), $this->filters($request));

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="porudzbine-'.now()->format('Ymd-His').'.csv"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (Throwable $exception) {
            $this->safeLog('CSV izvoz izveštaja nije uspeo.', $exception, $request->user());

            return response('Izveštaj trenutno nije dostupan. Pokrenite: php artisan app:reports-doctor --repair', 503, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }
    }

    public function pdf(Request $request, OrderReportService $reports): Response
    {
        $issues = $reports->readinessIssues();
        if ($issues !== []) {
            return $this->unavailableExport($issues);
        }

        try {
            $content = $reports->pdf($request->user(), $this->filters($request));

            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="izvestaj-porudzbina-'.now()->format('Ymd-His').'.pdf"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        } catch (Throwable $exception) {
            $this->safeLog('PDF izvoz izveštaja nije uspeo.', $exception, $request->user());

            return response('PDF izveštaj trenutno nije dostupan. Pokrenite: php artisan app:reports-doctor --repair', 503, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }
    }

    public function paymentsCsv(Request $request): Response
    {
        try {
            $user = $request->user();
            $query = Order::query()->with(['user', 'supplier'])->where('source_system', 'laravel')->latest('id');
            if (!$user->hasRole('superadmin')) $query->where('supplier_user_id', $user->id);
            $stream = fopen('php://temp', 'w+');
            if ($stream === false) throw new \RuntimeException('Privremeni CSV stream nije dostupan.');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Porudžbina', 'Korisnik', 'Odgovorno lice', 'Vrednost RSD', 'Plaćeno RSD', 'Preostalo RSD', 'Status salda', 'Dospeće'], ';');
            foreach ($query->cursor() as $order) {
                $total = (float) $order->subtotal_rsd;
                $paid = (float) ($order->paid_total_rsd ?? 0);
                fputcsv($stream, [$order->order_number, $order->user?->displayName(), $order->supplier_name_snapshot ?: $order->supplier?->displayName(), number_format($total, 2, '.', ''), number_format($paid, 2, '.', ''), number_format(max(0, $total - $paid), 2, '.', ''), $order->payment_state ?? $order->payment_status, $order->payment_due_at?->format('Y-m-d H:i')], ';');
            }
            rewind($stream);
            $content = stream_get_contents($stream) ?: '';
            fclose($stream);
            return response($content, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="uplate-'.now()->format('Ymd-His').'.csv"', 'X-Content-Type-Options' => 'nosniff']);
        } catch (Throwable $exception) {
            $this->safeLog('CSV izvoz uplata nije uspeo.', $exception, $request->user());
            return response('Izvoz uplata trenutno nije dostupan. Pokrenite: php artisan app:payments-inventory-doctor --repair', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }

    public function inventoryCsv(Request $request): Response
    {
        try {
            $stream = fopen('php://temp', 'w+');
            if ($stream === false) throw new \RuntimeException('Privremeni CSV stream nije dostupan.');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['SKU', 'Naziv', 'Stanje', 'Minimalni prag', 'Status upozorenja'], ';');
            foreach (Product::query()->whereNull('deleted_at')->orderBy('sku')->cursor() as $product) {
                fputcsv($stream, [$product->sku, $product->name, $product->stock_quantity, $product->low_stock_threshold, $product->stock_quantity <= $product->low_stock_threshold ? 'NIZAK LAGER' : 'OK'], ';');
            }
            rewind($stream);
            $content = stream_get_contents($stream) ?: '';
            fclose($stream);
            return response($content, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="lager-izvestaj-'.now()->format('Ymd-His').'.csv"', 'X-Content-Type-Options' => 'nosniff']);
        } catch (Throwable $exception) {
            $this->safeLog('CSV izvoz lagera nije uspeo.', $exception, $request->user());
            return response('Izvoz lagera trenutno nije dostupan. Pokrenite: php artisan app:payments-inventory-doctor --repair', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }

    /** @return array{submitted:int,verified_total:float,outstanding_total:float,overdue:int} */
    private function safePaymentSummary(User $user): array
    {
        try {
            return $this->paymentSummary($user);
        } catch (Throwable $exception) {
            $this->safeLog('Sažetak uplata za Reports nije učitan.', $exception, $user);
            return ['submitted' => 0, 'verified_total' => 0.0, 'outstanding_total' => 0.0, 'overdue' => 0];
        }
    }

    /** @return array{products:int,units:int,low_stock:int,out_of_stock:int} */
    private function safeInventorySummary(User $user): array
    {
        try {
            return $this->inventorySummary();
        } catch (Throwable $exception) {
            $this->safeLog('Sažetak lagera za Reports nije učitan.', $exception, $user);
            return ['products' => 0, 'units' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
        }
    }

    /** @return array{submitted:int,verified_total:float,outstanding_total:float,overdue:int} */
    private function paymentSummary(User $user): array
    {
        if (!Schema::hasTable('order_payments') || !Schema::hasColumn('orders', 'payment_state')) return ['submitted' => 0, 'verified_total' => 0.0, 'outstanding_total' => 0.0, 'overdue' => 0];
        $orders = Order::query()->where('source_system', 'laravel');
        if (!$user->hasRole('superadmin')) $orders->where('supplier_user_id', $user->id);
        $ids = (clone $orders)->pluck('id');
        return [
            'submitted' => DB::table('order_payments')->whereIn('order_id', $ids)->where('status', 'submitted')->count(),
            'verified_total' => (float) DB::table('order_payments')->whereIn('order_id', $ids)->where('status', 'verified')->selectRaw("COALESCE(SUM(CASE WHEN entry_type='refund' THEN -amount_rsd ELSE amount_rsd END),0) AS total")->value('total'),
            'outstanding_total' => (float) (clone $orders)->whereNotIn('payment_state', ['paid','overpaid','cancelled'])->selectRaw('COALESCE(SUM(GREATEST(subtotal_rsd-paid_total_rsd,0)),0) AS total')->value('total'),
            'overdue' => (clone $orders)->whereNotIn('payment_state', ['paid','overpaid','cancelled'])->whereNotNull('payment_due_at')->where('payment_due_at', '<', now())->count(),
        ];
    }

    /** @return array{products:int,units:int,low_stock:int,out_of_stock:int} */
    private function inventorySummary(): array
    {
        if (!Schema::hasTable('products')) return ['products' => 0, 'units' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
        $base = Product::query()->whereNull('deleted_at');
        return [
            'products' => (clone $base)->count(),
            'units' => (int) (clone $base)->sum('stock_quantity'),
            'low_stock' => (clone $base)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'out_of_stock' => (clone $base)->where('stock_quantity', '<=', 0)->count(),
        ];
    }

    /** @return array<string,mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'in:new,processing,confirmed,shipped,completed,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,cancelled'],
            'supplier_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
    }

    /** @return Collection<int,User> */
    private function suppliers(): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
            ->with('role')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /** @return Collection<int,OrderDocument> */
    private function latestDocuments(User $user): Collection
    {
        if (!Schema::hasTable('order_documents')) {
            return collect();
        }

        $documents = OrderDocument::query()
            ->with(['order.user', 'order.supplier'])
            ->latest('issued_at');

        if (!$user->hasRole('superadmin')) {
            $documents->whereHas('order', static fn ($query) => $query->where('supplier_user_id', $user->id));
        }

        return $documents->limit(12)->get();
    }

    /** @param array<string,mixed> $filters @param list<string> $issues @return array<string,mixed> */
    private function fallbackData(Request $request, array $filters, array $issues): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $orders = new LengthAwarePaginator([], 0, 40, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return [
            'orders' => $orders,
            'summary' => [
                'orders_count' => 0,
                'total_rsd' => 0.0,
                'units_count' => 0,
                'commission_eur' => 0.0,
            ],
            'filters' => $filters,
            'suppliers' => collect(),
            'documents' => collect(),
            'paymentSummary' => ['submitted' => 0, 'verified_total' => 0.0, 'outstanding_total' => 0.0, 'overdue' => 0],
            'inventorySummary' => ['products' => 0, 'units' => 0, 'low_stock' => 0, 'out_of_stock' => 0],
            'reportUnavailable' => implode(' ', $issues),
            'canExportReports' => false,
            'canManageDocumentSettings' => false,
        ];
    }

    /** @param array<string,mixed> $data */
    private function renderProtected(Request $request, User $user, array $data): Response
    {
        try {
            if (!view()->shared('errors')) {
                view()->share('errors', new ViewErrorBag());
            }

            $html = view('admin.reports.index', $data)->render();

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        } catch (Throwable $exception) {
            $incident = strtoupper(Str::random(10));
            $this->safeLog('Reports Blade/layout render nije uspeo.', $exception, $user, [
                'incident' => $incident,
                'url' => $request->fullUrl(),
            ]);

            return response($this->emergencyHtml($incident), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Ald1n-Reports-Recovery' => $incident,
            ]);
        }
    }

    private function emergencyHtml(string $incident): string
    {
        $safeIncident = htmlspecialchars($incident, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!doctype html><html lang="sr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<title>Izveštaji — recovery</title><style>body{margin:0;background:#252b31;color:#f4f6f8;font-family:Arial,sans-serif}.wrap{max-width:760px;margin:8vh auto;padding:24px}.card{background:#30373e;border:1px solid #505b66;border-radius:20px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.25)}h1{margin-top:0}.code{display:inline-block;background:#20262b;border-radius:10px;padding:7px 10px;color:#ffb000}.cmd{display:block;white-space:pre-wrap;background:#20262b;padding:14px;border-radius:12px;margin:18px 0;color:#dce7f2}a{color:#60a5fa}</style></head><body><div class="wrap"><div class="card">'
            .'<h1>Izveštaji su dostupni u recovery režimu</h1><p>SQL provere su prošle, ali kompletan Blade/layout render nije uspeo. Stranica više ne vraća HTTP 500.</p>'
            .'<p>Incident: <span class="code">'.$safeIncident.'</span></p>'
            .'<code class="cmd">php artisan app:reports-doctor --render</code>'
            .'<p><a href="/">Nazad na početnu</a></p></div></div></body></html>';
    }

    /** @param list<string> $issues */
    private function unavailableExport(array $issues): Response
    {
        return response(
            "Izveštaji nisu spremni: ".implode('; ', $issues)."\nPokrenite: php artisan app:reports-doctor --repair",
            503,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    /** @param array<string,mixed> $extra */
    private function safeLog(string $message, Throwable $exception, ?User $user = null, array $extra = []): void
    {
        try {
            Log::warning($message, $extra + [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'user_id' => $user?->id,
            ]);
        } catch (Throwable) {
            // Logging must never replace the original Reports recovery response with another HTTP 500.
        }
    }
}

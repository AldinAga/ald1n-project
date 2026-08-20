<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\BankAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CatalogAccessService;
use App\Services\IpsPaymentPayloadService;
use App\Services\OrderDetailPresenter;
use App\Services\OrderDetailService;
use App\Services\OrderService;
use App\Services\OrderTimelineService;
use App\Services\OrderWorkflowService;
use App\Support\ViewValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;
use Throwable;

final class OrderController extends Controller
{
    private ?Throwable $lastDetailRenderException = null;

    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => Order::query()->operational()
                ->where('user_id', $request->user()->id)
                ->with(['commission', 'supplier'])
                ->latest('id')
                ->paginate(30),
        ]);
    }

    public function create(Request $request, CatalogAccessService $access): View
    {
        $query = Product::query()
            ->publiclyVisible()
            ->where('stock_quantity', '>', 0)
            ->orderBy('name');
        $access->apply($query, $request->user());

        $products = $query->get([
            'id',
            'sku',
            'name',
            'price_amount',
            'price_currency',
            'stock_quantity',
        ]);

        return view('orders.create', [
            'products' => $products,
            'bankAccounts' => BankAccount::query()->where('is_active', true)->orderBy('label')->get(),
            'selectedProductId' => (int) $request->integer('product'),
            'idempotencyKey' => (string) Str::uuid(),
            'suppliers' => User::query()
                ->where('status', 'active')
                ->whereHas('role', static fn ($role) => $role->whereIn('slug', ['superadmin', 'admin']))
                ->with('role')
                ->get()
                ->sortBy(static fn (User $supplier): int => $supplier->hasRole('superadmin') ? 0 : 1)
                ->values(),
        ]);
    }

    public function store(StoreOrderRequest $request, OrderService $orders): RedirectResponse
    {
        $order = $orders->create($request->user(), $request->validated(), (string) $request->validated('idempotency_key'));
        return redirect()->route('orders.show', $order)->with('status', 'Porudžbina '.$order->order_number.' je uspešno kreirana i lager je rezervisan.');
    }

    public function show(
        Request $request,
        Order $order,
        OrderDetailService $details,
        OrderDetailPresenter $presenter,
        OrderTimelineService $timeline,
        IpsPaymentPayloadService $ips,
    ): Response {
        $this->lastDetailRenderException = null;

        /** @var User $actor */
        $actor = $request->user();
        abort_unless((int) $order->user_id === (int) $actor->id, 404);

        try {
            $warnings = $details->prepare($order, false);
        } catch (Throwable $exception) {
            $this->safeLog('User order relation preparation nije uspeo.', $exception, $actor, $order);
            $this->ensureDetailRelations($order);
            $warnings = ['Pojedini podaci porudžbine trenutno nisu dostupni.'];
        }

        try {
            $timelineEvents = $timeline->build($order, false);
        } catch (Throwable $exception) {
            $this->safeLog('User order timeline nije uspeo.', $exception, $actor, $order);
            $timelineEvents = collect();
            $warnings[] = 'Praćenje porudžbine trenutno nije dostupno.';
        }

        try {
            $ipsPayload = $ips->persist($order);
        } catch (Throwable $exception) {
            $this->safeLog('User order IPS payload nije uspeo.', $exception, $actor, $order);
            $ipsPayload = null;
            $warnings[] = 'IPS podaci trenutno nisu dostupni.';
        }

        try {
            $detail = $presenter->user(
                $order,
                $actor,
                $timelineEvents,
                $ipsPayload,
                array_values(array_unique($warnings)),
            );
            $html = view('orders.show', ['detail' => $detail])
                ->with('errors', new ViewErrorBag())
                ->render();

            return response($html, 200);
        } catch (Throwable $exception) {
            $this->lastDetailRenderException = $exception;
            $incident = (string) Str::uuid();
            $this->safeLog('User order detail render nije uspeo. Incident '.$incident.'.', $exception, $actor, $order);

            try {
                return response(
                    $this->fallbackDetailHtml($order, $incident),
                    200,
                    [
                        'Content-Type' => 'text/html; charset=UTF-8',
                        'X-Ald1n-Detail-Fallback' => '1',
                        'X-Ald1n-Incident' => $incident,
                    ],
                );
            } catch (Throwable $fallbackException) {
                $this->safeLog('User order emergency fallback nije uspeo. Incident '.$incident.'.', $fallbackException, $actor, $order);

                return response(
                    '<!doctype html><html lang="sr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Porudžbina u bezbednom režimu</title></head><body style="margin:0;background:#20262d;color:#eef2f7;font-family:Arial,sans-serif"><main style="max-width:760px;margin:8vh auto;padding:32px;border:1px solid #46515e;border-radius:18px;background:#2b323a"><h1>Porudžbina je otvorena u bezbednom režimu</h1><p>Administrator može proveriti detalj komandom <code>php artisan app:orders-doctor --render --order-id='.(int) $order->getKey().'</code>.</p><p>Incident: <strong>'.e($incident).'</strong></p></main></body></html>',
                    200,
                    [
                        'Content-Type' => 'text/html; charset=UTF-8',
                        'X-Ald1n-Detail-Fallback' => '1',
                        'X-Ald1n-Incident' => $incident,
                    ],
                );
            }
        }
    }


    public function lastDetailRenderException(): ?Throwable
    {
        return $this->lastDetailRenderException;
    }

    public function cancel(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $workflow->cancelOwn($order, $request->user(), $validated['note'] ?? null);
        return redirect()->route('orders.show', $order)->with('status', 'Porudžbina je otkazana. Lager je vraćen tačno jednom.');
    }

    private function safeLog(string $message, Throwable $exception, User $actor, Order $order): void
    {
        try {
            Log::error($message, [
                'order_id' => $order->id,
                'user_id' => $actor->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        } catch (Throwable) {
            // Logging ne sme prikriti originalni problem detalja porudžbine.
        }
    }

    private function ensureDetailRelations(Order $order): void
    {
        foreach (['items', 'documents', 'statusHistory', 'assignments', 'payments', 'internalNotes'] as $relation) {
            if (!$order->relationLoaded($relation)) {
                $order->setRelation($relation, collect());
            }
        }

        foreach (['user', 'supplier', 'bankAccount', 'acceptedBy', 'assignedBy', 'completedBy', 'reopenedBy', 'commission', 'delivery'] as $relation) {
            if (!$order->relationLoaded($relation)) {
                $order->setRelation($relation, null);
            }
        }
    }

    private function fallbackDetailHtml(Order $order, string $incident): string
    {
        $items = $order->relationLoaded('items') && $order->items instanceof Collection
            ? $order->items
            : collect();

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr><td>'.e((string) ($item->product_name ?? 'Artikal')).'</td>'
                .'<td>'.e((string) ($item->quantity ?? 0)).'</td>'
                .'<td>'.e(number_format((float) ($item->line_total_rsd ?? 0), 2, ',', '.')).' RSD</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="3">Stavke trenutno nisu dostupne.</td></tr>';
        }

        $back = ViewValue::route('orders.index') ?? '/orders';

        return '<!doctype html><html lang="sr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Porudžbina '.e((string) $order->order_number).'</title>'
            .'<style>body{margin:0;background:#20262d;color:#eef2f7;font-family:Arial,sans-serif}main{max-width:960px;margin:4vh auto;padding:28px}.card{border:1px solid #46515e;border-radius:18px;background:#2b323a;padding:24px;margin-bottom:18px}.warn{background:#4b351d;border-color:#8b642d}a{color:#8bc6ff}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid #46515e}</style></head><body><main data-order-user-detail-fallback="1">'
            .'<p><a href="'.e($back).'">← Moje porudžbine</a></p>'
            .'<section class="card warn"><strong>Detaljni prikaz je privremeno prešao u bezbedni režim.</strong><p>Incident: <strong>'.e($incident).'</strong></p></section>'
            .'<section class="card"><h1>'.e((string) $order->order_number).'</h1><p>Status: '.e((string) $order->status).' · Ukupno: '.e(number_format((float) $order->subtotal_rsd, 2, ',', '.')).' RSD</p></section>'
            .'<section class="card"><h2>Stavke</h2><table><thead><tr><th>Artikal</th><th>Količina</th><th>Ukupno</th></tr></thead><tbody>'.$rows.'</tbody></table></section>'
            .'</main></body></html>';
    }
}

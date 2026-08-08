<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\IpsPaymentPayloadService;
use App\Services\OrderAccessService;
use App\Services\OrderDetailPresenter;
use App\Services\OrderDetailService;
use App\Services\OrderIndexService;
use App\Services\OrderOperationalService;
use App\Services\OrderTimelineService;
use App\Services\OrderWorkflowService;
use App\Support\ViewValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Throwable;

final class OrderController extends Controller
{
    private ?Throwable $lastDetailRenderException = null;

    public function index(Request $request, OrderIndexService $orders, OrderAccessService $access): Response
    {
        /** @var User $actor */
        $actor = $request->user();
        $filters = $this->indexFilters($request);

        try {
            $issues = $orders->readinessIssues();
        } catch (Throwable $exception) {
            $this->safeLog('Orders readiness provera nije uspela.', $exception, $actor);
            $issues = ['Laravel baza ili operativna šema porudžbina trenutno nije dostupna.'];
        }

        if ($issues !== []) {
            return $this->renderIndexProtected($actor, $this->fallbackIndexData($filters, $issues));
        }

        try {
            $data = [
                'orders' => $orders->paginate($actor, $filters, $access),
                'suppliers' => $orders->suppliers($actor),
                'attentionCounts' => $orders->attentionCounts($actor, $access),
                'ordersUnavailable' => null,
            ];
        } catch (Throwable $exception) {
            $this->safeLog('Orders SQL upit nije uspeo.', $exception, $actor);

            return $this->renderIndexProtected($actor, $this->fallbackIndexData($filters, [
                'Lista porudžbina trenutno nije dostupna. Pokrenite orders doctor i repair migracije.',
            ]));
        }

        return $this->renderIndexProtected($actor, $data);
    }

    public function show(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderDetailService $details,
        OrderDetailPresenter $presenter,
        OrderTimelineService $timeline,
        IpsPaymentPayloadService $ips,
    ): Response {
        $this->lastDetailRenderException = null;

        /** @var User $actor */
        $actor = $request->user();
        $access->authorizeManage($order, $actor);

        try {
            $warnings = $details->prepare($order, true);
        } catch (Throwable $exception) {
            $this->safeLog('Orders detail priprema relacija nije uspela.', $exception, $actor);
            $this->ensureDetailRelations($order);
            $warnings = ['Pojedine relacije porudžbine trenutno nisu dostupne.'];
        }

        try {
            $timelineEvents = $timeline->build($order, true);
        } catch (Throwable $exception) {
            $this->safeLog('Orders detail timeline nije uspeo.', $exception, $actor);
            $timelineEvents = collect();
            $warnings[] = 'Timeline trenutno nije dostupan.';
        }

        try {
            $suppliers = $actor->hasRole('superadmin') ? $this->suppliers() : collect();
        } catch (Throwable $exception) {
            $this->safeLog('Orders detail lista odgovornih lica nije uspela.', $exception, $actor);
            $suppliers = collect();
            $warnings[] = 'Lista odgovornih lica trenutno nije dostupna.';
        }

        try {
            $ipsPayload = $ips->persist($order);
        } catch (Throwable $exception) {
            $this->safeLog('Orders detail IPS podaci nisu uspeli.', $exception, $actor);
            $ipsPayload = null;
            $warnings[] = 'IPS podaci trenutno nisu dostupni.';
        }

        try {
            $detail = $presenter->admin(
                $order,
                $actor,
                $timelineEvents,
                $suppliers,
                $ipsPayload,
                array_values(array_unique($warnings)),
            );
        } catch (Throwable $exception) {
            $this->lastDetailRenderException = $exception;
            $incident = (string) Str::uuid();
            $this->safeLog('Orders detail presenter nije uspeo. Incident '.$incident.'.', $exception, $actor);

            try {
                $fallback = $this->fallbackDetailHtml(['order' => $order], $incident);
            } catch (Throwable $fallbackException) {
                $this->safeLog('Orders detail presenter fallback nije uspeo. Incident '.$incident.'.', $fallbackException, $actor);
                $fallback = $this->recoveryHtml(
                    'Detalji porudžbine su u bezbednom režimu',
                    'Pokrenite <code>php artisan app:orders-doctor --render --order-id='.(int) $order->getKey().'</code> za tačan uzrok.',
                    $incident,
                );
            }

            return response($fallback, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Ald1n-Detail-Fallback' => '1',
                'X-Ald1n-Incident' => $incident,
            ]);
        }

        return $this->renderShowProtected($actor, [
            'order' => $order,
            'detail' => $detail,
        ]);
    }

    public function lastDetailRenderException(): ?Throwable
    {
        return $this->lastDetailRenderException;
    }

    public function status(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
    {
        $access->authorizeManage($order, $request->user());
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'processing', 'confirmed', 'shipped', 'cancelled'])], 'note' => ['nullable', 'string', 'max:1000']]);
        $workflow->changeStatus($order, (string) $data['status'], $request->user(), $data['note'] ?? null);
        return back()->with('status', 'Status porudžbine je ažuriran.');
    }

    public function complete(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
    {
        $access->authorizeManage($order, $request->user());
        $request->merge([
            'delivery_method' => $request->input('delivery_method', 'own_transport'),
            'delivered_at' => $request->input('delivered_at', now()->format('Y-m-d\TH:i')),
            'recipient_name' => $request->input('recipient_name', trim((string) $order->shipping_full_name) ?: 'Kupac'),
            'recipient_phone' => $request->input('recipient_phone', (string) $order->shipping_phone),
            'completion_note' => $request->input('completion_note', $request->input('note')),
        ]);
        $data = $request->validate([
            'delivery_method' => ['required', Rule::in(['own_transport', 'courier', 'customer_pickup', 'other'])],
            'delivered_at' => ['required', 'date', 'before_or_equal:now'],
            'recipient_name' => ['required', 'string', 'max:190'],
            'recipient_phone' => ['nullable', 'string', 'max:60'],
            'delivery_reference' => ['nullable', 'string', 'max:190'],
            'delivery_note' => ['nullable', 'string', 'max:3000'],
            'completion_note' => ['nullable', 'string', 'max:1000'],
            'delivery_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $completed = $workflow->complete($order, $request->user(), $data, $request->file('delivery_proof'));

        return back()->with(
            'status',
            'Porudžbina '.$completed->order_number.' je kompletirana. Evidencija isporuke i konačno plaćanje su zaključani.',
        );
    }

    public function reopen(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
    {
        $access->authorizeManage($order, $request->user());
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $reopened = $workflow->reopen($order, $request->user(), (string) $data['reason']);

        return back()->with('status', 'Porudžbina '.$reopened->order_number.' je ponovo otvorena za kontrolisanu korekciju.');
    }

    public function payment(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
    {
        $access->authorizeManage($order, $request->user());
        $data = $request->validate(['payment_status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])]]);
        $workflow->updatePaymentStatus($order, (string) $data['payment_status'], $request->user());
        return back()->with('status', 'Status plaćanja je ažuriran.');
    }

    public function tracking(Request $request, Order $order, OrderWorkflowService $workflow, OrderAccessService $access): RedirectResponse
    {
        $access->authorizeManage($order, $request->user());
        $data = $request->validate(['tracking_number' => ['nullable', 'string', 'max:120']]);
        $workflow->updateTracking($order, filled($data['tracking_number'] ?? null) ? trim((string) $data['tracking_number']) : null, $request->user());
        return back()->with('status', 'Tracking broj je ažuriran.');
    }

    public function accept(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
    {
        $operations->accept($order, $request->user());
        return back()->with('status', 'Porudžbina je preuzeta za obradu.');
    }

    public function note(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);
        $operations->addInternalNote($order, $request->user(), (string) $data['note']);
        return back()->with('status', 'Interna napomena je sačuvana.');
    }

    public function reassign(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
    {
        abort_unless($request->user()->hasRole('superadmin'), 403);
        $data = $request->validate([
            'supplier_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $supplier = User::query()->with('role')->findOrFail((int) $data['supplier_user_id']);
        $operations->reassign($order, $supplier, $request->user(), (string) $data['reason']);
        return back()->with('status', 'Porudžbina je dodeljena drugom odgovornom licu.');
    }

    public function deadlines(Request $request, Order $order, OrderOperationalService $operations): RedirectResponse
    {
        $data = $request->validate([
            'expected_processing_at' => ['nullable', 'date'],
            'expected_shipping_at' => ['nullable', 'date', 'after_or_equal:expected_processing_at'],
        ]);
        $operations->updateDeadlines($order, $request->user(), $data);
        return back()->with('status', 'Očekivani rokovi su ažurirani.');
    }

    /** @return array<string,mixed> */
    private function indexFilters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'source_system' => ['nullable', Rule::in(['legacy', 'laravel'])],
            'status' => ['nullable', Rule::in(['new', 'processing', 'confirmed', 'shipped', 'completed', 'cancelled'])],
            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'cancelled'])],
            'supplier_user_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'attention' => ['nullable', Rule::in(['unaccepted', 'overdue'])],
        ]);
    }

    /** @param array<string,mixed> $data */
    private function renderIndexProtected(User $actor, array $data): Response
    {
        try {
            $html = view('admin.orders.index', $data)->with('errors', new ViewErrorBag())->render();
            return response($html, 200);
        } catch (Throwable $exception) {
            $incident = (string) Str::uuid();
            $this->safeLog('Orders Blade/layout render nije uspeo. Incident '.$incident.'.', $exception, $actor);

            return response(
                $this->recoveryHtml(
                    'Porudžbine privremeno nisu dostupne',
                    'Pokrenite <code>php artisan app:orders-doctor --render</code> i proverite Laravel log.',
                    $incident,
                ),
                503,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }
    }

    /** @param array<string,mixed> $data */
    private function renderShowProtected(User $actor, array $data): Response
    {
        try {
            $html = view('admin.orders.show', $data)->with('errors', new ViewErrorBag())->render();
            return response($html, 200);
        } catch (Throwable $exception) {
            $this->lastDetailRenderException = $exception;
            $incident = (string) Str::uuid();
            $this->safeLog('Orders detail Blade/layout render nije uspeo. Incident '.$incident.'.', $exception, $actor);

            try {
                return response(
                    $this->fallbackDetailHtml($data, $incident),
                    200,
                    [
                        'Content-Type' => 'text/html; charset=UTF-8',
                        'X-Ald1n-Detail-Fallback' => '1',
                        'X-Ald1n-Incident' => $incident,
                    ],
                );
            } catch (Throwable $fallbackException) {
                $this->safeLog('Orders detail fallback nije uspeo. Incident '.$incident.'.', $fallbackException, $actor);

                return response(
                    $this->recoveryHtml(
                        'Detalji porudžbine su u bezbednom režimu',
                        'Pokrenite <code>php artisan app:orders-doctor --render --order-id='.(int) ($data['order']->id ?? 0).'</code> za tačan uzrok.',
                        $incident,
                    ),
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

    /** @param array<string,mixed> $data */
    private function fallbackDetailHtml(array $data, string $incident): string
    {
        $order = $data['order'] instanceof Order ? $data['order'] : null;
        if (!$order instanceof Order) {
            throw new \RuntimeException('Fallback nema validnu porudžbinu.');
        }

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

        $back = ViewValue::route('admin.orders.index') ?? '/admin/orders';
        $created = ViewValue::date($order, 'created_at');

        return '<!doctype html><html lang="sr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Porudžbina '.e((string) $order->order_number).'</title>'
            .'<style>body{margin:0;background:#20262d;color:#eef2f7;font-family:Arial,sans-serif}main{max-width:960px;margin:4vh auto;padding:28px}.card{border:1px solid #46515e;border-radius:18px;background:#2b323a;padding:24px;margin-bottom:18px}.warn{background:#4b351d;border-color:#8b642d}a{color:#8bc6ff}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid #46515e}</style></head><body><main data-order-detail-fallback="1">'
            .'<p><a href="'.e($back).'">← Nazad na porudžbine</a></p>'
            .'<section class="card warn"><strong>Detaljna radna tabla je privremeno prešla u bezbedni pregled.</strong><p>Podaci porudžbine su dostupni, ali pojedine akcije su sakrivene dok se render greška ne otkloni.</p><p>Incident: <strong>'.e($incident).'</strong></p></section>'
            .'<section class="card"><h1>'.e((string) $order->order_number).'</h1><p>'.e((string) $order->shipping_full_name).' · '.e($created).'</p><p>Status: '.e((string) $order->status).' · Ukupno: '.e(number_format((float) $order->subtotal_rsd, 2, ',', '.')).' RSD</p></section>'
            .'<section class="card"><h2>Stavke</h2><table><thead><tr><th>Artikal</th><th>Količina</th><th>Ukupno</th></tr></thead><tbody>'.$rows.'</tbody></table></section>'
            .'</main></body></html>';
    }

    private function recoveryHtml(string $title, string $instruction, string $incident): string
    {
        return '<!doctype html><html lang="sr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).'</title></head><body style="margin:0;background:#20262d;color:#eef2f7;font-family:Arial,sans-serif"><main style="max-width:760px;margin:8vh auto;padding:32px;border:1px solid #46515e;border-radius:18px;background:#2b323a"><h1>'.e($title).'</h1><p>'.$instruction.'</p><p>Incident: <strong>'.e($incident).'</strong></p></main></body></html>';
    }

    /** @param array<string,mixed> $filters @param list<string> $issues @return array<string,mixed> */
    private function fallbackIndexData(array $filters, array $issues): array
    {
        return [
            'orders' => new LengthAwarePaginator([], 0, 40, 1, [
                'path' => url('/admin/orders'),
                'query' => $filters,
            ]),
            'suppliers' => collect(),
            'attentionCounts' => ['unaccepted' => 0, 'overdue' => 0],
            'ordersUnavailable' => $issues,
        ];
    }

    private function safeLog(string $message, Throwable $exception, User $actor): void
    {
        try {
            Log::error($message, [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'user_id' => $actor->id,
            ]);
        } catch (Throwable) {
            // Logging problem ne sme prikriti originalni problem stranice porudžbina.
        }
    }

    private function allows(User $actor, string $ability): bool
    {
        try {
            return Gate::forUser($actor)->allows($ability);
        } catch (Throwable) {
            return false;
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
}

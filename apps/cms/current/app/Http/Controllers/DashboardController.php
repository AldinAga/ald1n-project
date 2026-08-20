<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\FieldWorkOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\ReceivableCase;
use App\Models\ServicePart;
use App\Models\ServicePartPurchaseRequest;
use App\Models\User;
use App\Services\CustomerPortalService;
use App\Services\ManagementReportService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

final class DashboardController extends Controller
{
    /** @var array<string,list<string>|null> */
    private array $tableColumnsCache = [];

    public function __invoke(Request $request, SettingsService $settings, ManagementReportService $reports, CustomerPortalService $portalService): View
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $user->loadMissing(['role', 'group']);
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('relations', $exception);
        }

        $access = [
            'orders_manage' => $this->allows($user, 'orders.manage'),
            'orders_view_own' => $this->allows($user, 'orders.view_own'),
            'catalog_view' => $this->allows($user, 'catalog.view'),
            'catalog_manage_products' => $this->allows($user, 'catalog.manage_products'),
            'stock_view' => $this->allows($user, 'stock.view'),
            'manage_users' => $this->allows($user, 'system.manage_users'),
            'manage_settings' => $this->allows($user, 'system.manage_settings'),
            'after_sales_manage' => $this->allows($user, 'after_sales.manage'),
            'after_sales_view_own' => $this->allows($user, 'after_sales.view_own'),
            'field_operations_view' => $this->allows($user, 'field_operations.view'),
            'service_parts_view' => $this->allows($user, 'service_parts.view'),
            'service_parts_procurement' => $this->allows($user, 'service_parts.procurement'),
            'warranties_manage' => $this->allows($user, 'warranties.manage'),
            'warranties_view_own' => $this->allows($user, 'warranties.view_own'),
            'receivables_manage' => $this->allows($user, 'receivables.manage'),
            'reports_view' => $this->allows($user, 'reports.view'),
        ];

        $orderStats = $this->orderStats($user, $access);
        $productStats = $this->productStats($access);
        $userStats = $this->userStats($access);
        $afterSalesStats = $this->afterSalesStats($user, $access);
        $fieldOperationsStats = $this->fieldOperationsStats($user, $access);
        $servicePartsStats = $this->servicePartsStats($access);
        $warrantyStats = $this->warrantyStats($user, $access);
        $receivableStats = $this->receivableStats($user, $access);
        $reportStats = $this->reportStats($user, $access, $reports);
        $recentOrders = $this->recentOrders($user, $access);
        $priorityActions = $this->priorityActions($access, $orderStats, $productStats, $afterSalesStats, $fieldOperationsStats, $servicePartsStats, $warrantyStats, $receivableStats);
        $portal = $this->portalData($user, $access, $portalService);

        try {
            $eurRsdRate = $settings->eurRsdRate();
            $eurRsdSource = (string) ($settings->get('eur_rsd_source', 'Ručno') ?: 'Ručno');
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('settings', $exception);
            $eurRsdRate = null;
            $eurRsdSource = 'Nije podešeno';
        }

        return view('dashboard.index', [
            'user' => $user,
            'roleName' => $user->roleName(),
            'access' => $access,
            'orderStats' => $orderStats,
            'productStats' => $productStats,
            'userStats' => $userStats,
            'afterSalesStats' => $afterSalesStats,
            'fieldOperationsStats' => $fieldOperationsStats,
            'servicePartsStats' => $servicePartsStats,
            'warrantyStats' => $warrantyStats,
            'receivableStats' => $receivableStats,
            'reportStats' => $reportStats,
            'recentOrders' => $recentOrders,
            'priorityActions' => $priorityActions,
            'eurRsdRate' => $eurRsdRate,
            'eurRsdSource' => $eurRsdSource,
            'portal' => $portal,
        ]);
    }

    /** @param array<string,bool> $access @return array<string,mixed> */
    private function portalData(User $user, array $access, CustomerPortalService $portalService): array
    {
        $fallback = [
            'enabled' => (bool) ($access['orders_view_own'] ?? false),
            'warning' => null,
            'summary' => [
                'orders_total' => 0,
                'orders_open' => 0,
                'orders_completed' => 0,
                'outstanding_rsd' => 0.0,
                'active_warranties' => 0,
                'open_cases' => 0,
                'upcoming_service' => 0,
                'open_conversations' => 0,
                'unread_messages' => 0,
            ],
            'orders' => collect(),
            'documents' => collect(),
            'payments' => collect(),
            'warranties' => collect(),
            'cases' => collect(),
            'serviceAppointments' => collect(),
            'installments' => collect(),
            'timeline' => collect(),
            'conversations' => collect(),
            'notificationPreference' => null,
        ];

        if (!($access['orders_view_own'] ?? false)) {
            return $fallback;
        }

        try {
            $data = $portalService->build($user);
            $data['notificationPreference'] = $portalService->preference($user);

            return array_replace($fallback, $data, [
                'enabled' => true,
                'warning' => null,
            ]);
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('customer_portal', $exception);

            try {
                $fallback['notificationPreference'] = $portalService->preference($user);
            } catch (Throwable $preferenceException) {
                $this->reportDashboardWarning('customer_portal_preferences', $preferenceException);
            }

            $fallback['warning'] = 'Pojedini podaci korisničkog centra trenutno nisu dostupni. Osnovne stranice porudžbina i dokumenata i dalje rade.';

            return $fallback;
        }
    }

    /** @param array<string,bool> $access @return array<string,mixed> */
    private function reportStats(User $user, array $access, ManagementReportService $reports): array
    {
        $fallback = [
            'ready' => false,
            'period_label' => now()->startOfMonth()->format('d.m.Y').' – '.now()->format('d.m.Y'),
            'summary' => ['revenue_rsd' => 0.0, 'gross_profit_rsd' => 0.0, 'net_contribution_rsd' => 0.0, 'gross_margin_percent' => 0.0, 'cost_coverage_percent' => 0.0, 'orders_count' => 0, 'outstanding_rsd' => 0.0],
            'trend' => [],
        ];
        if (!($access['reports_view'] ?? false)) return $fallback;
        try {
            if ($reports->readinessIssues() !== []) return $fallback;
            $filters = $reports->normalizeFilters([
                'date_from' => now()->startOfMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
                'scope' => 'completed',
                'group_by' => 'brand',
            ]);
            return [
                'ready' => true,
                'period_label' => now()->startOfMonth()->format('d.m.Y').' – '.now()->format('d.m.Y'),
                'summary' => $reports->summary($user, $filters),
                'trend' => $reports->trend($user, $filters),
            ];
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('management_report_stats', $exception);
            return $fallback;
        }
    }

    /** @param array<string,bool> $access @return array<int,array<string,mixed>> */
    private function recentOrders(User $user, array $access): array
    {
        if (!$access['orders_manage'] && !$access['orders_view_own']) return [];
        try {
            if (!$this->tableHasColumns('orders', ['id', 'order_number', 'user_id', 'supplier_user_id', 'status', 'subtotal_rsd', 'paid_total_rsd', 'created_at', 'updated_at'])) return [];
            $query = Order::query()->operational();
            if ($access['orders_manage'] && $user->hasRole('admin')) {
                $query->where('supplier_user_id', $user->id);
            } elseif (!$access['orders_manage']) {
                $query->where('user_id', $user->id);
            }
            return $query->latest('updated_at')->limit(7)->get()->map(static function (Order $order) use ($access): array {
                return [
                    'number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => ['new'=>'Nova','processing'=>'U obradi','confirmed'=>'Potvrđena','shipped'=>'Poslata','completed'=>'Završena','cancelled'=>'Otkazana'][$order->status] ?? $order->status,
                    'total_rsd' => (float) $order->subtotal_rsd,
                    'remaining_rsd' => max(0, (float) $order->subtotal_rsd - (float) $order->paid_total_rsd),
                    'updated_at' => $order->updated_at,
                    'url' => $access['orders_manage'] ? route('admin.orders.show', $order) : route('orders.show', $order),
                ];
            })->all();
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('recent_orders', $exception);
            return [];
        }
    }

    /**
     * @param array<string,bool> $access
     * @param array<string,mixed> $orderStats
     * @param array<string,mixed> $productStats
     * @param array<string,mixed> $afterSalesStats
     * @param array<string,mixed> $fieldOperationsStats
     * @param array<string,mixed> $servicePartsStats
     * @param array<string,mixed> $warrantyStats
     * @param array<string,mixed> $receivableStats
     * @return array<int,array<string,mixed>>
     */
    private function priorityActions(array $access, array $orderStats, array $productStats, array $afterSalesStats, array $fieldOperationsStats, array $servicePartsStats, array $warrantyStats, array $receivableStats): array
    {
        $items = [];
        $push = static function (array &$items, bool $allowed, int|float $count, string $title, string $description, string $url, string $tone, string $icon): void {
            if (!$allowed || $count <= 0) return;
            $items[] = compact('count', 'title', 'description', 'url', 'tone', 'icon');
        };
        $push($items, (bool) ($access['orders_manage'] ?? false), (int) $orderStats['new'], 'Nove porudžbine', 'Čekaju pregled i preuzimanje.', route('admin.orders.index', ['status'=>'new']), 'info', 'orders');
        $push($items, (bool) ($access['receivables_manage'] ?? false), (float) $receivableStats['overdue_amount'], 'Dospelo dugovanje', number_format((float) $receivableStats['overdue_amount'], 2, ',', '.').' RSD zahteva pažnju.', route('admin.receivables.index', ['aging'=>'1_7']), 'danger', 'wallet');
        $push($items, (bool) ($access['after_sales_manage'] ?? false), (int) $afterSalesStats['overdue'], 'Reklamacije preko roka', 'Slučajevi sa probijenim SLA rokom.', route('admin.after-sales.index', ['overdue'=>'1']), 'danger', 'alert');
        $push($items, (bool) ($access['field_operations_view'] ?? false), (int) $fieldOperationsStats['unscheduled'], 'Nalozi bez termina', 'Terenske intervencije treba rasporediti.', route('admin.field-operations.index', ['unscheduled'=>'1']), 'warning', 'truck');
        $push($items, (bool) ($access['catalog_manage_products'] ?? false), (int) $productStats['out_of_stock'], 'Artikli bez lagera', 'Aktivni artikli trenutno nisu raspoloživi.', route('catalog.index', ['stock'=>'out']), 'warning', 'boxes');
        $push($items, (bool) ($access['service_parts_view'] ?? false), (int) $servicePartsStats['low'], 'Servisni delovi ispod minimuma', 'Potrebna provera ili nabavka delova.', route('admin.service-parts.index', ['filter'=>'low']), 'warning', 'cog');
        $push($items, (bool) (($access['warranties_manage'] ?? false) || ($access['warranties_view_own'] ?? false)), (int) $warrantyStats['maintenance_due'], 'Preventivno održavanje', 'Termini dospevaju u narednih sedam dana.', ($access['warranties_manage'] ?? false) ? route('admin.warranties.index') : route('warranties.index'), 'info', 'shield');
        usort($items, static fn (array $a, array $b): int => (['danger'=>0,'warning'=>1,'info'=>2,'success'=>3][$a['tone']] ?? 9) <=> (['danger'=>0,'warning'=>1,'info'=>2,'success'=>3][$b['tone']] ?? 9));
        return array_slice($items, 0, 7);
    }

    /** @param array<string,bool> $access */
    private function orderStats(User $user, array $access): array
    {
        $fallback = ['new' => 0, 'processing' => 0, 'shipped' => 0, 'value_rsd' => 0.0];
        if (!$access['orders_manage'] && !$access['orders_view_own']) {
            return $fallback;
        }

        try {
            if (!$this->tableHasColumns('orders', ['id', 'user_id', 'status', 'subtotal_rsd'])) {
                return $fallback;
            }

            $orders = Order::query()->operational();
            if ($access['orders_manage'] && $user->hasRole('admin')) {
                $orders->where('supplier_user_id', $user->getAuthIdentifier());
            } elseif (!$access['orders_manage']) {
                $orders->where('user_id', $user->getAuthIdentifier());
            }

            return [
                'new' => (clone $orders)->where('status', 'new')->count(),
                'processing' => (clone $orders)->whereIn('status', ['processing', 'confirmed'])->count(),
                'shipped' => (clone $orders)->where('status', 'shipped')->count(),
                'value_rsd' => (float) (clone $orders)->where('status', '!=', 'cancelled')->sum('subtotal_rsd'),
            ];
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('order_stats', $exception);
            return $fallback;
        }
    }

    /** @param array<string,bool> $access */
    private function afterSalesStats(User $user, array $access): array
    {
        $fallback = ['active' => 0, 'overdue' => 0, 'awaiting_customer' => 0, 'pending_actions' => 0];
        if (!$access['after_sales_manage'] && !$access['after_sales_view_own']) {
            return $fallback;
        }

        try {
            if (!$this->tableHasColumns('after_sales_cases', ['id', 'order_id', 'assigned_to', 'status', 'due_at'])) {
                return $fallback;
            }

            $cases = AfterSalesCase::query();
            if ($access['after_sales_manage'] && $user->hasRole('admin')) {
                $cases->where(function ($query) use ($user): void {
                    $query->where('assigned_to', $user->getAuthIdentifier())
                        ->orWhereHas('order', static fn ($orders) => $orders->where('supplier_user_id', $user->getAuthIdentifier()));
                });
            } elseif (!$access['after_sales_manage']) {
                $cases->whereHas('order', static fn ($orders) => $orders->where('user_id', $user->getAuthIdentifier()));
            }

            $pendingActions = 0;
            if ($this->tableHasColumns('after_sales_actions', ['id', 'after_sales_case_id', 'status'])) {
                $pendingActions = AfterSalesAction::query()
                    ->whereIn('after_sales_case_id', (clone $cases)->select('after_sales_cases.id'))
                    ->whereIn('status', ['planned', 'in_progress'])
                    ->count();
            }

            return [
                'active' => (clone $cases)->whereNotIn('status', ['resolved', 'rejected', 'closed'])->count(),
                'overdue' => (clone $cases)->whereNotIn('status', ['resolved', 'rejected', 'closed'])->whereNotNull('due_at')->where('due_at', '<', now())->count(),
                'awaiting_customer' => (clone $cases)->where('status', 'awaiting_customer')->count(),
                'pending_actions' => $pendingActions,
            ];
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('after_sales_stats', $exception);
            return $fallback;
        }
    }


    /** @param array<string,bool> $access */
    private function fieldOperationsStats(User $user, array $access): array
    {
        $fallback = ['today' => 0, 'overdue' => 0, 'unscheduled' => 0];
        if (!($access['field_operations_view'] ?? false)) return $fallback;

        try {
            if (!$this->tableHasColumns('field_work_orders', ['id', 'after_sales_action_id', 'status', 'planned_start_at', 'planned_end_at'])) return $fallback;
            $query = FieldWorkOrder::query()->operational();
            if ($user->hasRole('admin')) {
                $query->whereHas('action.case', function ($cases) use ($user): void {
                    $cases->where(function ($scope) use ($user): void {
                        $scope->where('assigned_to', $user->id)
                            ->orWhereHas('order', static fn ($orders) => $orders->where('supplier_user_id', $user->id));
                    });
                });
            }
            return [
                'today' => (clone $query)->whereDate('planned_start_at', today())->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'overdue' => (clone $query)->whereIn('status', ['planned', 'en_route', 'on_site'])->whereNotNull('planned_end_at')->where('planned_end_at', '<', now())->count(),
                'unscheduled' => (clone $query)->where('status', 'planned')->whereNull('planned_start_at')->count(),
            ];
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('field_operations_stats', $exception);
            return $fallback;
        }
    }

    /** @param array<string,bool> $access */
    private function servicePartsStats(array $access): array
    {
        $fallback = ['low' => 0, 'reserved' => 0, 'open_purchases' => 0, 'value_rsd' => 0.0];
        if (!($access['service_parts_view'] ?? false)) return $fallback;

        try {
            if (!$this->tableHasColumns('service_parts', ['id', 'is_active', 'stock_quantity', 'reserved_quantity', 'minimum_quantity', 'average_cost_rsd'])) return $fallback;
            $parts = ServicePart::query()->where('is_active', true);
            $openPurchases = 0;
            if (($access['service_parts_procurement'] ?? false) && $this->tableHasColumns('service_part_purchase_requests', ['id', 'status'])) {
                $openPurchases = ServicePartPurchaseRequest::query()->whereIn('status', ['draft', 'submitted', 'ordered'])->count();
            }
            return [
                'low' => (clone $parts)->whereRaw('(stock_quantity - reserved_quantity) <= minimum_quantity')->count(),
                'reserved' => (clone $parts)->where('reserved_quantity', '>', 0)->count(),
                'open_purchases' => $openPurchases,
                'value_rsd' => (float) (clone $parts)->selectRaw('COALESCE(SUM(stock_quantity * average_cost_rsd),0) AS total')->value('total'),
            ];
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('service_parts_stats', $exception);
            return $fallback;
        }
    }

    /** @param array<string,bool> $access */
    private function warrantyStats(User $user, array $access): array
    {
        $fallback = ['active' => 0, 'expiring' => 0, 'maintenance_due' => 0];
        if (!($access['warranties_manage'] ?? false) && !($access['warranties_view_own'] ?? false)) return $fallback;
        try {
            if (!$this->tableHasColumns('product_warranties', ['id', 'user_id', 'status', 'expires_at', 'next_maintenance_at'])) return $fallback;
            $query = ProductWarranty::query();
            if (($access['warranties_manage'] ?? false) && $user->hasRole('admin')) {
                $query->whereHas('order', static fn ($orders) => $orders->where('supplier_user_id', $user->id));
            } elseif (!($access['warranties_manage'] ?? false)) {
                $query->where('user_id', $user->id);
            }
            return [
                'active' => (clone $query)->where('status', 'active')->whereDate('expires_at', '>=', today())->count(),
                'expiring' => (clone $query)->where('status', 'active')->whereBetween('expires_at', [today(), today()->addDays(30)])->count(),
                'maintenance_due' => (clone $query)->where('status', 'active')->whereNotNull('next_maintenance_at')->whereDate('next_maintenance_at', '<=', today()->addDays(7))->count(),
            ];
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('warranty_stats', $exception);
            return $fallback;
        }
    }

    /** @param array<string,bool> $access */
    private function receivableStats(User $user, array $access): array
    {
        $fallback = ['active' => 0, 'overdue_amount' => 0.0, 'promised' => 0, 'plans' => 0];
        if (!($access['receivables_manage'] ?? false)) return $fallback;
        try {
            if (!$this->tableHasColumns('receivable_cases', ['id','order_id','assigned_to','status']) || !$this->tableHasColumns('orders', ['id','supplier_user_id','subtotal_rsd','paid_total_rsd','payment_due_at'])) return $fallback;
            $cases = ReceivableCase::query();
            if ($user->hasRole('admin')) {
                $cases->where(function ($query) use ($user): void {
                    $query->where('assigned_to', $user->id)->orWhereHas('order', static fn ($orders) => $orders->where('supplier_user_id', $user->id));
                });
            }
            $orders = Order::query()->operational()->whereIn('id', (clone $cases)->select('order_id'))->whereRaw('subtotal_rsd > paid_total_rsd');
            return [
                'active' => (clone $cases)->where('status','!=','closed')->count(),
                'overdue_amount' => (float) (clone $orders)->where('payment_due_at','<',today())->selectRaw('COALESCE(SUM(CASE WHEN subtotal_rsd > paid_total_rsd THEN subtotal_rsd-paid_total_rsd ELSE 0 END),0) total')->value('total'),
                'promised' => (clone $cases)->where('status','promised')->count(),
                'plans' => (clone $cases)->where('status','installment_plan')->count(),
            ];
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('receivable_stats', $exception);
            return $fallback;
        }
    }

    /** @param array<string,bool> $access */
    private function productStats(array $access): array
    {
        $fallback = ['total' => 0, 'active' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
        if (!$access['catalog_view'] && !$access['catalog_manage_products']) {
            return $fallback;
        }

        try {
            if (!$this->tableHasColumns('products', ['id', 'status', 'stock_quantity', 'low_stock_threshold', 'deleted_at'])) {
                return $fallback;
            }

            $row = Product::query()
                ->whereNull('deleted_at')
                ->selectRaw(
                    'COUNT(*) AS aggregate_total, '
                    .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS aggregate_active, '
                    .'SUM(CASE WHEN status = ? AND stock_quantity > 0 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) AS aggregate_low_stock, '
                    .'SUM(CASE WHEN status = ? AND stock_quantity = 0 THEN 1 ELSE 0 END) AS aggregate_out_of_stock',
                    ['active', 'active', 'active'],
                )
                ->first();

            return [
                'total' => (int) ($row?->getAttribute('aggregate_total') ?? 0),
                'active' => (int) ($row?->getAttribute('aggregate_active') ?? 0),
                'low_stock' => (int) ($row?->getAttribute('aggregate_low_stock') ?? 0),
                'out_of_stock' => (int) ($row?->getAttribute('aggregate_out_of_stock') ?? 0),
            ];
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('product_stats', $exception);
            return $fallback;
        }
    }

    /** @param array<string,bool> $access */
    private function userStats(array $access): array
    {
        $fallback = ['total' => 0, 'active' => 0, 'pending' => 0, 'blocked' => 0];
        if (!$access['manage_users']) {
            return $fallback;
        }

        try {
            if (!$this->tableHasColumns('users', ['id', 'status'])) {
                return $fallback;
            }

            $row = User::query()
                ->selectRaw(
                    'COUNT(*) AS aggregate_total, '
                    .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS aggregate_active, '
                    .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS aggregate_pending, '
                    .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS aggregate_blocked',
                    ['active', 'pending', 'blocked'],
                )
                ->first();

            return [
                'total' => (int) ($row?->getAttribute('aggregate_total') ?? 0),
                'active' => (int) ($row?->getAttribute('aggregate_active') ?? 0),
                'pending' => (int) ($row?->getAttribute('aggregate_pending') ?? 0),
                'blocked' => (int) ($row?->getAttribute('aggregate_blocked') ?? 0),
            ];
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('user_stats', $exception);
            return $fallback;
        }
    }

    private function allows(User $user, string $ability): bool
    {
        try {
            return $user->can($ability);
        } catch (Throwable $exception) {
            $this->reportDashboardWarning('permission_'.$ability, $exception);
            return false;
        }
    }

    /** @param list<string> $columns */
    private function tableHasColumns(string $table, array $columns): bool
    {
        if (!array_key_exists($table, $this->tableColumnsCache)) {
            $this->tableColumnsCache[$table] = Schema::hasTable($table)
                ? array_values(Schema::getColumnListing($table))
                : null;
        }

        $existing = $this->tableColumnsCache[$table];
        return is_array($existing) && array_diff($columns, $existing) === [];
    }

    private function reportDashboardWarning(string $section, Throwable $exception): void
    {
        try {
            Log::warning('Dashboard section unavailable; fallback values were used.', [
                'section' => $section,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
            // Dashboard fallback ne sme postati novi HTTP 500 ako log direktorijum nije upisiv.
        }
    }
}

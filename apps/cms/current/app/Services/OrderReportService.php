<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Services\Pdf\BusinessDocumentPdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class OrderReportService
{
    /** @var array<string,list<string>> */
    private const REQUIRED_SCHEMA = [
        'orders' => [
            'id', 'order_number', 'user_id', 'supplier_user_id', 'supplier_name_snapshot',
            'status', 'payment_status', 'subtotal_rsd', 'source_system', 'shipping_full_name',
            'shipping_city', 'completed_at', 'created_at',
        ],
        'order_items' => ['order_id', 'quantity'],
        'order_commissions' => ['order_id', 'status', 'total_eur'],
        'order_documents' => ['id', 'order_id', 'document_number', 'status', 'issued_at', 'customer_name'],
        'users' => ['id', 'role_id', 'username', 'email', 'status'],
        'roles' => ['id', 'slug'],
    ];

    public function __construct(
        private readonly OrderAccessService $access,
        private readonly SettingsService $settings,
        private readonly BusinessDocumentPdfService $pdf,
    ) {}

    /** @return list<string> */
    public function readinessIssues(): array
    {
        $issues = [];

        try {
            foreach (self::REQUIRED_SCHEMA as $table => $columns) {
                if (!Schema::hasTable($table)) {
                    $issues[] = 'Nedostaje tabela '.$table.'.';
                    continue;
                }

                $existing = Schema::getColumnListing($table);
                $missing = array_values(array_diff($columns, $existing));
                if ($missing !== []) {
                    $issues[] = 'Nedostaju kolone '.$table.'.'.implode(', '.$table.'.', $missing).'.';
                }
            }
        } catch (Throwable) {
            return ['Laravel baza ili reports šema trenutno nije dostupna.'];
        }

        return $issues;
    }

    /** @param array<string,mixed> $filters @return Builder<Order> */
    public function query(User $user, array $filters): Builder
    {
        $query = Order::query()
            ->where('source_system', 'laravel')
            ->with(['user', 'supplier', 'commission'])
            ->latest('created_at')
            ->latest('id');
        $this->access->applyManagedScope($query, $user);

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(static function (Builder $builder) use ($search): void {
                $builder->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('shipping_full_name', 'like', '%'.$search.'%')
                    ->orWhereHas('user', static fn (Builder $userQuery) => $userQuery
                        ->where('username', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%'));
            });
        }
        $status = $filters['status'] ?? null;
        if ($status === 'completed') {
            $query->whereNotNull('completed_at');
        } elseif ($status === 'shipped') {
            $query->where('status', 'shipped')->whereNull('completed_at');
        } elseif (in_array($status, ['new', 'processing', 'confirmed', 'cancelled'], true)) {
            $query->where('status', $status)->whereNull('completed_at');
        }
        if (in_array($filters['payment_status'] ?? null, ['pending', 'paid', 'cancelled'], true)) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (!empty($filters['supplier_user_id']) && $user->hasRole('superadmin')) {
            $query->where('supplier_user_id', (int) $filters['supplier_user_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', (string) $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', (string) $filters['date_to']);
        }

        return $query;
    }

    /** @param array<string,mixed> $filters */
    public function paginate(User $user, array $filters, int $perPage = 40): LengthAwarePaginator
    {
        return $this->query($user, $filters)->paginate($perPage)->withQueryString();
    }

    /** @param array<string,mixed> $filters @return array{orders_count:int,total_rsd:float,units_count:int,commission_eur:float} */
    public function summary(User $user, array $filters): array
    {
        $base = $this->query($user, $filters);
        $idQuery = (clone $base)->reorder()->select('orders.id')->toBase();

        $units = DB::table('order_items as report_items')
            ->joinSub(clone $idQuery, 'report_orders', static function ($join): void {
                $join->on('report_orders.id', '=', 'report_items.order_id');
            })
            ->sum('report_items.quantity');

        $commission = DB::table('order_commissions as report_commissions')
            ->joinSub(clone $idQuery, 'report_orders', static function ($join): void {
                $join->on('report_orders.id', '=', 'report_commissions.order_id');
            })
            ->where('report_commissions.status', '!=', 'cancelled')
            ->sum('report_commissions.total_eur');

        return [
            'orders_count' => (clone $base)->count('orders.id'),
            'total_rsd' => (float) (clone $base)
                ->where('orders.status', '!=', 'cancelled')
                ->sum('orders.subtotal_rsd'),
            'units_count' => (int) $units,
            'commission_eur' => (float) $commission,
        ];
    }

    /** @param array<string,mixed> $filters */
    public function csv(User $user, array $filters): string
    {
        $handle = fopen('php://temp', 'w+b');
        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Broj porudžbine', 'Datum', 'Korisnik', 'E-mail', 'Dobavljač', 'Status', 'Plaćanje', 'Iznos RSD', 'Provizija EUR', 'Grad'], ';');

        $this->query($user, $filters)
            ->reorder('orders.id')
            ->chunkById(500, static function (Collection $orders) use ($handle): void {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->order_number,
                        $order->created_at?->format('d.m.Y H:i'),
                        $order->user?->displayName(),
                        $order->user?->email,
                        $order->supplier_name_snapshot ?: $order->supplier?->displayName(),
                        $order->completed_at !== null ? 'completed' : $order->status,
                        $order->payment_status,
                        number_format((float) $order->subtotal_rsd, 2, '.', ''),
                        $order->commission ? number_format((float) $order->commission->total_eur, 2, '.', '') : '0.00',
                        $order->shipping_city,
                    ], ';');
                }
            }, 'orders.id', 'id');

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }

    /** @param array<string,mixed> $filters */
    public function pdf(User $user, array $filters): string
    {
        $summary = $this->summary($user, $filters);
        $orders = $this->query($user, $filters)->limit(2000)->get();
        $settings = $this->settings->all();
        $filterParts = array_filter([
            !empty($filters['date_from']) ? 'od '.$filters['date_from'] : null,
            !empty($filters['date_to']) ? 'do '.$filters['date_to'] : null,
            !empty($filters['status']) ? 'status '.$filters['status'] : null,
            !empty($filters['payment_status']) ? 'plaćanje '.$filters['payment_status'] : null,
            !empty($filters['q']) ? 'pretraga: '.$filters['q'] : null,
        ]);

        $logoSetting = trim((string) ($settings['documents_logo_path'] ?? ''))
            ?: trim((string) ($settings['site_logo_light_path'] ?? ''));

        return $this->pdf->renderOrdersReport([
            'company_name' => trim((string) ($settings['documents_company_name'] ?? '')) ?: (string) ($settings['site_name'] ?? 'Ald1n CMS'),
            'company_logo_path' => $this->localLogoPath($logoSetting),
            'generated_at' => now()->format('d.m.Y H:i'),
            'filters_label' => $filterParts !== [] ? implode(' · ', $filterParts) : 'bez dodatnih filtera',
            'summary' => $summary,
            'orders' => $orders->map(static fn (Order $order): array => [
                'order_number' => $order->order_number,
                'customer' => $order->user?->displayName() ?: $order->shipping_full_name,
                'supplier' => $order->supplier_name_snapshot ?: $order->supplier?->displayName(),
                'status' => $order->completed_at !== null ? 'completed' : $order->status,
                'date' => $order->created_at?->format('d.m.Y'),
                'total_rsd' => (float) $order->subtotal_rsd,
            ])->values()->all(),
        ]);
    }
    private function localLogoPath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') return null;
        try {
            $full = Storage::disk('public')->path($path);
            return is_file($full) ? $full : null;
        } catch (Throwable) {
            return null;
        }
    }

}

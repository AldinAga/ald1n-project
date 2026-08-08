<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderCommission;
use App\Models\User;
use App\Services\Pdf\CommissionReportPdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class CommissionReportService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly CommissionReportPdfService $pdf,
    ) {}

    /** @param array<string,mixed> $filters */
    public function managedQuery(User $actor, array $filters): Builder
    {
        $query = OrderCommission::query()->with(['order.supplier', 'user', 'paymentBatch'])->latest('id');
        if (!$actor->hasRole('superadmin')) {
            $query->whereHas('order', static fn (Builder $order): Builder => $order->where('supplier_user_id', $actor->id));
        }
        return $this->filters($query, $filters, $actor->hasRole('superadmin'));
    }

    /** @param array<string,mixed> $filters */
    public function ownQuery(User $user, array $filters): Builder
    {
        return $this->filters(
            OrderCommission::query()->with(['order.supplier', 'paymentBatch'])->where('user_id', $user->id)->latest('id'),
            $filters,
            false,
        );
    }

    /** @param array<string,mixed> $filters */
    public function paginateManaged(User $actor, array $filters, int $perPage = 40): LengthAwarePaginator
    {
        return $this->managedQuery($actor, $filters)->paginate($perPage)->withQueryString();
    }

    /** @param array<string,mixed> $filters */
    public function paginateOwn(User $user, array $filters, int $perPage = 30): LengthAwarePaginator
    {
        return $this->ownQuery($user, $filters)->paginate($perPage)->withQueryString();
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function summaryManaged(User $actor, array $filters): array
    {
        return $this->summary($this->managedQuery($actor, $filters));
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function summaryOwn(User $user, array $filters): array
    {
        return $this->summary($this->ownQuery($user, $filters));
    }

    /** @param array<string,mixed> $filters */
    public function csv(User $actor, array $filters): string
    {
        $handle = fopen('php://temp', 'w+b');
        if ($handle === false) return '';
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Porudžbina', 'Korisnik', 'Dobavljač', 'Iznos EUR', 'Status', 'Način isplate', 'Referenca', 'Odobreno', 'Isplaćeno', 'Napomena'], ';');
        $this->managedQuery($actor, $filters)->reorder('order_commissions.id')->chunkById(500, static function (Collection $rows) use ($handle): void {
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->order?->order_number,
                    $row->user?->displayName(),
                    $row->order?->supplier_name_snapshot ?: $row->order?->supplier?->displayName(),
                    number_format((float) $row->total_eur, 2, '.', ''),
                    $row->status,
                    $row->payment_method,
                    $row->payment_reference,
                    $row->approved_at?->format('d.m.Y H:i'),
                    $row->paid_at?->format('d.m.Y H:i'),
                    $row->status_note,
                ], ';');
            }
        }, 'order_commissions.id', 'id');
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return is_string($csv) ? $csv : '';
    }

    /** @param array<string,mixed> $filters */
    public function pdf(User $actor, array $filters): string
    {
        $settings = $this->settings->all();
        $rows = $this->managedQuery($actor, $filters)->limit(3000)->get();
        return $this->pdf->render([
            'company_name' => trim((string) ($settings['documents_company_name'] ?? '')) ?: (string) ($settings['site_name'] ?? 'Ald1n CMS'),
            'generated_at' => now()->format('d.m.Y H:i'),
            'filters_label' => $this->filtersLabel($filters),
            'summary' => $this->summary($this->managedQuery($actor, $filters)),
            'rows' => $rows->map(static fn (OrderCommission $row): array => [
                'order_number' => $row->order?->order_number,
                'user' => $row->user?->displayName(),
                'status' => $row->status,
                'amount_eur' => (float) $row->total_eur,
                'date' => $row->created_at?->format('d.m.Y'),
            ])->values()->all(),
        ]);
    }

    /** @return list<string> */
    public function readinessIssues(): array
    {
        $required = [
            'orders' => ['id', 'order_number', 'user_id', 'supplier_user_id'],
            'users' => ['id', 'username', 'email', 'status'],
            'order_commissions' => ['id', 'order_id', 'user_id', 'total_eur', 'status', 'payment_method', 'payment_reference', 'status_updated_at'],
            'commission_status_history' => ['commission_id', 'order_id', 'old_status', 'new_status', 'metadata_json'],
            'commission_payment_batches' => ['id', 'batch_number', 'payment_method', 'total_eur'],
        ];
        $issues = [];
        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) { $issues[] = 'Nedostaje tabela '.$table.'.'; continue; }
            $missing = array_diff($columns, Schema::getColumnListing($table));
            if ($missing !== []) $issues[] = 'Nedostaju kolone '.$table.'.'.implode(', '.$table.'.', $missing).'.';
        }
        return $issues;
    }

    /** @param Builder<OrderCommission> $query @param array<string,mixed> $filters */
    private function filters(Builder $query, array $filters, bool $allowUserSupplier): Builder
    {
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(static function (Builder $builder) use ($search): void {
                $builder->whereHas('order', static fn (Builder $order): Builder => $order->where('order_number', 'like', '%'.$search.'%'))
                    ->orWhereHas('user', static fn (Builder $user): Builder => $user->where('username', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%'));
            });
        }
        if (in_array($filters['status'] ?? null, ['pending', 'approved', 'paid', 'cancelled'], true)) $query->where('status', $filters['status']);
        if (!empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->whereDate('created_at', '<=', $filters['date_to']);
        if ($allowUserSupplier && !empty($filters['user_id'])) $query->where('user_id', (int) $filters['user_id']);
        if ($allowUserSupplier && !empty($filters['supplier_user_id'])) $query->whereHas('order', static fn (Builder $order): Builder => $order->where('supplier_user_id', (int) $filters['supplier_user_id']));
        return $query;
    }

    /** @param Builder<OrderCommission> $query @return array<string,mixed> */
    private function summary(Builder $query): array
    {
        $rows = (clone $query)->reorder()->selectRaw('status, COUNT(*) AS total_count, SUM(total_eur) AS total_eur')->groupBy('status')->get()->keyBy('status');
        return [
            'count' => (int) $rows->sum('total_count'),
            'pending_count' => (int) ($rows['pending']->total_count ?? 0),
            'pending_eur' => (float) ($rows['pending']->total_eur ?? 0),
            'approved_count' => (int) ($rows['approved']->total_count ?? 0),
            'approved_eur' => (float) ($rows['approved']->total_eur ?? 0),
            'paid_count' => (int) ($rows['paid']->total_count ?? 0),
            'paid_eur' => (float) ($rows['paid']->total_eur ?? 0),
            'cancelled_count' => (int) ($rows['cancelled']->total_count ?? 0),
            'cancelled_eur' => (float) ($rows['cancelled']->total_eur ?? 0),
        ];
    }

    /** @param array<string,mixed> $filters */
    private function filtersLabel(array $filters): string
    {
        $parts = array_filter([
            !empty($filters['date_from']) ? 'od '.$filters['date_from'] : null,
            !empty($filters['date_to']) ? 'do '.$filters['date_to'] : null,
            !empty($filters['status']) ? 'status '.$filters['status'] : null,
            !empty($filters['q']) ? 'pretraga: '.$filters['q'] : null,
        ]);
        return $parts !== [] ? implode(' · ', $parts) : 'bez dodatnih filtera';
    }
}

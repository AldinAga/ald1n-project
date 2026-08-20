<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\WarrantyRule;
use Illuminate\Database\Eloquent\Builder;

final class WarrantyAdminService
{
    public function __construct(
        private readonly WarrantyService $warranties,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array<string,mixed> $data */
    public function createRule(User $actor, array $data): WarrantyRule
    {
        $attributes = $this->normalizeRuleData($data);
        $attributes['created_by'] = $actor->id;
        $attributes['updated_by'] = $actor->id;

        $rule = WarrantyRule::query()->create($attributes);

        $this->audit->log(
            'warranty_rule.created',
            'Kreirano pravilo garancije '.$rule->name,
            $rule,
            after: $rule->toArray(),
            user: $actor,
        );

        return $rule->fresh(['category', 'product']) ?? $rule;
    }

    /** @param array<string,mixed> $data */
    public function updateRule(
        WarrantyRule $rule,
        User $actor,
        array $data,
    ): WarrantyRule {
        $before = $rule->toArray();
        $attributes = $this->normalizeRuleData($data);
        $attributes['updated_by'] = $actor->id;

        $rule->update($attributes);

        $this->audit->log(
            'warranty_rule.updated',
            'Izmenjeno pravilo garancije '.$rule->name,
            $rule,
            $before,
            $rule->toArray(),
            user: $actor,
        );

        return $rule->fresh(['category', 'product']) ?? $rule;
    }

    public function backfill(User $actor, int $limit = 500): int
    {
        $safeLimit = max(1, min(500, $limit));
        $orders = $this->backfillQuery($actor)
            ->limit($safeLimit)
            ->get();

        $created = 0;

        foreach ($orders as $order) {
            $created += $this->warranties
                ->ensureForOrder($order->load('user'), $actor)
                ->count();
        }

        return $created;
    }

    public function backfillCandidateCount(User $actor, int $limit = 500): int
    {
        return $this->backfillQuery($actor)
            ->limit(max(1, min(500, $limit)))
            ->count();
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function normalizeRuleData(array $data): array
    {
        $scope = (string) ($data['scope_type'] ?? 'global');

        if ($scope !== 'category') {
            $data['category_id'] = null;
        }

        if ($scope !== 'product') {
            $data['product_id'] = null;
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    /** @return Builder<Order> */
    private function backfillQuery(User $actor): Builder
    {
        $query = Order::query()
            ->whereNotNull('completed_at')
            ->whereHas(
                'items',
                static fn (Builder $items): Builder =>
                    $items->whereDoesntHave('warranty'),
            )
            ->orderBy('id');

        if (!$actor->hasRole('superadmin')) {
            $query->where('supplier_user_id', $actor->id);
        }

        return $query;
    }
}

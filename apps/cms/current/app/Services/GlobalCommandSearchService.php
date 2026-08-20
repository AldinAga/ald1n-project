<?php

declare(strict_types=1);

namespace App\Services;

// ux-maximal-phase3-global-command-search-batch2-v5

use App\Models\AfterSalesCase;
use App\Models\Order;
use App\Models\ProductWarranty;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class GlobalCommandSearchService
{
    public function __construct(
        private readonly CatalogQueryService $catalog,
        private readonly OrderAccessService $orders,
        private readonly AfterSalesAccessService $afterSalesAccess,
    ) {
    }

    /**
     * @return array{
     *   query:string,
     *   items:list<array<string,mixed>>,
     *   sections:list<array{key:string,label:string,count:int}>
     * }
     */
    public function search(User $actor, string $term, int $perSection = 5): array
    {
        $term = trim($term);
        $limit = max(1, min(6, $perSection));

        if (mb_strlen($term) < 2) {
            return [
                'query' => $term,
                'items' => [],
                'sections' => [],
            ];
        }

        $groups = [];

        if ($actor->hasPermission('catalog.view')) {
            $groups['products'] = [
                'label' => 'Artikli',
                'items' => $this->productItems($actor, $term, $limit),
            ];
        }

        if ($actor->hasPermission('orders.manage') || $actor->hasPermission('orders.view_own')) {
            $groups['orders'] = [
                'label' => 'Porudžbine',
                'items' => $this->orderItems($actor, $term, $limit),
            ];
        }

        if ($actor->hasPermission('system.manage_users')) {
            $groups['users'] = [
                'label' => 'Korisnici',
                'items' => $this->userItems($actor, $term, min(4, $limit)),
            ];
        }

        if ($actor->hasPermission('warranties.manage') || $actor->hasPermission('warranties.view_own')) {
            $groups['warranties'] = [
                'label' => 'Garancije',
                'items' => $this->warrantyItems($actor, $term, $limit),
            ];
        }

        if ($actor->hasPermission('after_sales.manage') || $actor->hasPermission('after_sales.view_own')) {
            $groups['after_sales'] = [
                'label' => 'Reklamacije',
                'items' => $this->afterSalesItems($actor, $term, $limit),
            ];
        }

        $commands = $this->commandItems($actor, $term, min(5, $limit));
        if ($commands !== []) {
            $groups['commands'] = [
                'label' => 'Brze prečice',
                'items' => $commands,
            ];
        }

        $items = [];
        $sections = [];

        foreach ($groups as $key => $group) {
            if ($group['items'] === []) {
                continue;
            }

            $sections[] = [
                'key' => $key,
                'label' => $group['label'],
                'count' => count($group['items']),
            ];

            foreach ($group['items'] as $item) {
                $item['group_key'] = $key;
                $item['group'] = $group['label'];
                $items[] = $item;
            }
        }

        return [
            'query' => $term,
            'items' => $items,
            'sections' => $sections,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function productItems(User $actor, string $term, int $limit): array
    {
        return $this->catalog
            ->quickSearch($actor, $term, $limit)
            ->map(function ($product): array {
                $status = (string) ($product->status ?? '');

                return $this->item(
                    type: 'product',
                    badge: 'Artikal',
                    title: (string) ($product->name ?: $product->model_name ?: $product->sku),
                    identifier: (string) ($product->sku ?? ''),
                    subtitle: trim(implode(' · ', array_filter([
                        $product->brand?->name,
                        $product->model_name,
                        $this->productStatusLabel($status),
                    ], static fn ($value): bool => filled($value)))),
                    url: route('catalog.show', ['slug' => $product->slug]),
                    status: $status,
                    stockQuantity: (int) ($product->stock_quantity ?? 0),
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function orderItems(User $actor, string $term, int $limit): array
    {
        $query = Order::query()->operational()
            ->select([
                'id',
                'order_number',
                'user_id',
                'shipping_full_name',
                'status',
                'payment_status',
                'subtotal_rsd',
                'supplier_user_id',
                'created_at',
            ])
            ->with('user:id,username,email,first_name,last_name');

        $managed = $actor->hasPermission('orders.manage');

        if ($managed) {
            $this->orders->applyManagedScope($query, $actor);
        } else {
            $query->where('user_id', $actor->id);
        }

        $contains = '%'.$term.'%';
        $prefix = $term.'%';

        $query->where(static function (Builder $nested) use ($contains): void {
            $nested->where('order_number', 'like', $contains)
                ->orWhere('shipping_full_name', 'like', $contains)
                ->orWhereHas('user', static function (Builder $users) use ($contains): void {
                    $users->where('username', 'like', $contains)
                        ->orWhere('email', 'like', $contains)
                        ->orWhere('first_name', 'like', $contains)
                        ->orWhere('last_name', 'like', $contains);
                });
        });

        return $query
            ->orderByRaw(
                'CASE WHEN order_number = ? THEN 0 WHEN order_number LIKE ? THEN 1 ELSE 2 END',
                [$term, $prefix],
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (Order $order) use ($managed): array {
                $customer = trim((string) $order->shipping_full_name);
                if ($customer === '' && $order->user instanceof User) {
                    $customer = $order->user->displayName();
                }

                $status = (string) $order->status;

                return $this->item(
                    type: 'order',
                    badge: 'Porudžbina',
                    title: (string) $order->order_number,
                    identifier: $customer,
                    subtitle: trim(implode(' · ', array_filter([
                        $customer,
                        $this->humanize($status),
                        $order->payment_status ? 'Plaćanje: '.$this->humanize((string) $order->payment_status) : null,
                    ]))),
                    url: $managed
                        ? route('admin.orders.show', $order)
                        : route('orders.show', $order),
                    status: $status,
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function userItems(User $actor, string $term, int $limit): array
    {
        if (!$actor->hasPermission('system.manage_users')) {
            return [];
        }

        $contains = '%'.$term.'%';
        $prefix = $term.'%';

        return User::query()
            ->with('role:id,name,slug')
            ->where(static function (Builder $query) use ($contains): void {
                $query->where('username', 'like', $contains)
                    ->orWhere('email', 'like', $contains)
                    ->orWhere('first_name', 'like', $contains)
                    ->orWhere('last_name', 'like', $contains);
            })
            ->orderByRaw(
                'CASE WHEN username = ? OR email = ? THEN 0 WHEN username LIKE ? OR email LIKE ? THEN 1 ELSE 2 END',
                [$term, $term, $prefix, $prefix],
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (User $user): array {
                $displayName = trim($user->displayName());
                $title = $displayName !== '' ? $displayName : (string) $user->username;

                return $this->item(
                    type: 'user',
                    badge: 'Korisnik',
                    title: $title,
                    identifier: (string) $user->username,
                    subtitle: trim(implode(' · ', array_filter([
                        $user->username,
                        $user->email,
                        $user->role?->name,
                        $this->humanize((string) $user->status),
                    ]))),
                    url: route('admin.users.index', ['q' => $user->username]),
                    status: (string) $user->status,
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function warrantyItems(User $actor, string $term, int $limit): array
    {
        $managed = $actor->hasPermission('warranties.manage');
        $own = $actor->hasPermission('warranties.view_own');

        if (!$managed && !$own) {
            return [];
        }

        $query = ProductWarranty::query()
            ->select([
                'id',
                'warranty_number',
                'order_id',
                'user_id',
                'status',
                'customer_name_snapshot',
                'product_sku_snapshot',
                'product_name_snapshot',
                'starts_at',
                'expires_at',
            ])
            ->with('order:id,order_number,supplier_user_id');

        if ($managed) {
            if (!$actor->hasRole('superadmin')) {
                $query->whereHas(
                    'order',
                    static fn (Builder $orders): Builder =>
                        $orders->where('supplier_user_id', $actor->id),
                );
            }
        } else {
            $query->where('user_id', $actor->id);
        }

        $contains = '%'.$term.'%';
        $prefix = $term.'%';

        $query->where(static function (Builder $nested) use ($contains): void {
            $nested->where('warranty_number', 'like', $contains)
                ->orWhere('product_name_snapshot', 'like', $contains)
                ->orWhere('product_sku_snapshot', 'like', $contains)
                ->orWhere('customer_name_snapshot', 'like', $contains)
                ->orWhereHas(
                    'order',
                    static fn (Builder $orders): Builder =>
                        $orders->where('order_number', 'like', $contains),
                );
        });

        return $query
            ->orderByRaw(
                'CASE WHEN warranty_number = ? THEN 0 WHEN warranty_number LIKE ? THEN 1 ELSE 2 END',
                [$term, $prefix],
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (ProductWarranty $warranty) use ($managed): array {
                $status = method_exists($warranty, 'effectiveStatus')
                    ? (string) $warranty->effectiveStatus()
                    : (string) $warranty->status;

                return $this->item(
                    type: 'warranty',
                    badge: 'Garancija',
                    title: (string) $warranty->warranty_number,
                    identifier: (string) ($warranty->product_sku_snapshot ?: ''),
                    subtitle: trim(implode(' · ', array_filter([
                        $warranty->product_name_snapshot,
                        $warranty->customer_name_snapshot,
                        $warranty->order?->order_number,
                        $this->humanize($status),
                    ]))),
                    url: $managed
                        ? route('admin.warranties.show', $warranty)
                        : route('warranties.show', $warranty),
                    status: $status,
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function afterSalesItems(User $actor, string $term, int $limit): array
    {
        $managed = $actor->hasPermission('after_sales.manage');
        $own = $actor->hasPermission('after_sales.view_own');

        if (!$managed && !$own) {
            return [];
        }

        $contains = '%'.$term.'%';
        $prefix = $term.'%';

        $query = AfterSalesCase::query()
            ->select([
                'id',
                'case_number',
                'order_id',
                'opened_by',
                'assigned_to',
                'case_type',
                'priority',
                'status',
                'subject',
                'customer_name_snapshot',
                'created_at',
            ])
            ->with('order:id,order_number,user_id,supplier_user_id');

        $this->afterSalesAccess->applyVisibleScope($query, $actor);

        $query->where(static function (Builder $nested) use ($contains): void {
            $nested->where('case_number', 'like', $contains)
                ->orWhere('subject', 'like', $contains)
                ->orWhere('customer_name_snapshot', 'like', $contains)
                ->orWhereHas(
                    'order',
                    static fn (Builder $orders): Builder =>
                        $orders->where('order_number', 'like', $contains),
                );
        });

        return $query
            ->orderByRaw(
                'CASE WHEN case_number = ? THEN 0 WHEN case_number LIKE ? THEN 1 ELSE 2 END',
                [$term, $prefix],
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (AfterSalesCase $case) use ($managed): array {
                $status = (string) $case->status;

                return $this->item(
                    type: 'after_sales',
                    badge: 'Reklamacija',
                    title: (string) $case->case_number,
                    identifier: (string) ($case->order?->order_number ?? ''),
                    subtitle: trim(implode(' · ', array_filter([
                        $case->subject,
                        $case->customer_name_snapshot,
                        $case->order?->order_number,
                        $this->humanize($status),
                    ]))),
                    url: $managed
                        ? route('admin.after-sales.show', $case)
                        : route('after-sales.show', $case),
                    status: $status,
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function commandItems(User $actor, string $term, int $limit): array
    {
        $commands = [];

        $add = function (
            bool $allowed,
            string $label,
            string $description,
            string $url,
            array $aliases = [],
        ) use (&$commands, $term): void {
            if (!$allowed) {
                return;
            }

            $haystack = mb_strtolower(trim($label.' '.$description.' '.implode(' ', $aliases)));
            $needle = mb_strtolower($term);

            if (!str_contains($haystack, $needle)) {
                return;
            }

            $commands[] = $this->item(
                type: 'command',
                badge: 'Prečica',
                title: $label,
                identifier: 'Modul',
                subtitle: $description,
                url: $url,
                status: 'shortcut',
            );
        };

        $add(
            $actor->hasPermission('catalog.view'),
            'Katalog',
            'Pretraži i upravljaj artiklima.',
            route('catalog.index'),
            ['artikli', 'proizvodi', 'lager'],
        );

        if ($actor->hasPermission('orders.manage')) {
            $add(true, 'Porudžbine', 'Otvori administratorski pregled porudžbina.', route('admin.orders.index'), ['narudžbine', 'orders']);
        } elseif ($actor->hasPermission('orders.view_own')) {
            $add(true, 'Moje porudžbine', 'Otvori svoje porudžbine.', route('orders.index'), ['narudžbine', 'orders']);
        }

        if ($actor->hasPermission('warranties.manage')) {
            $add(true, 'Garancije', 'Otvori administraciju garancija.', route('admin.warranties.index'), ['garancija', 'warranty']);
        } elseif ($actor->hasPermission('warranties.view_own')) {
            $add(true, 'Moje garancije', 'Otvori svoje garancije.', route('warranties.index'), ['garancija', 'warranty']);
        }

        if ($actor->hasPermission('after_sales.manage')) {
            $add(true, 'Reklamacije', 'Otvori postprodajni centar.', route('admin.after-sales.index'), ['postprodaja', 'servis']);
        } elseif ($actor->hasPermission('after_sales.view_own')) {
            $add(true, 'Moje reklamacije', 'Otvori svoje postprodajne slučajeve.', route('after-sales.index'), ['postprodaja', 'servis']);
        }

        $add(
            $actor->hasPermission('system.manage_users'),
            'Korisnici',
            'Pretraži i upravljaj korisničkim nalozima.',
            route('admin.users.index'),
            ['nalozi', 'users'],
        );

        $add(
            $actor->hasPermission('stock.view'),
            'Inventar',
            'Otvori stanje i operacije lagera.',
            route('admin.inventory.index'),
            ['lager', 'stock'],
        );

        $add(
            $actor->hasPermission('reports.view'),
            'Izveštaji',
            'Otvori upravljačke izveštaje i analitiku.',
            route('admin.reports.index'),
            ['reports', 'analitika'],
        );

        if ($actor->hasPermission('commissions.manage')) {
            $add(true, 'Provizije', 'Otvori administraciju provizija.', route('admin.commissions.index'), ['commission']);
        } elseif ($actor->hasPermission('commissions.view_own')) {
            $add(true, 'Moje provizije', 'Otvori svoje provizije.', route('commissions.index'), ['commission']);
        }

        $add(
            $actor->hasPermission('notifications.view'),
            'Obaveštenja',
            'Otvori centar obaveštenja.',
            route('notifications.index'),
            ['notifications', 'inbox'],
        );

        usort(
            $commands,
            static function (array $left, array $right) use ($term): int {
                $needle = mb_strtolower($term);
                $leftTitle = mb_strtolower((string) $left['name']);
                $rightTitle = mb_strtolower((string) $right['name']);

                $leftRank = $leftTitle === $needle ? 0 : (str_starts_with($leftTitle, $needle) ? 1 : 2);
                $rightRank = $rightTitle === $needle ? 0 : (str_starts_with($rightTitle, $needle) ? 1 : 2);

                return [$leftRank, $leftTitle] <=> [$rightRank, $rightTitle];
            },
        );

        return array_slice($commands, 0, $limit);
    }

    /**
     * Existing header renderer compatibility is kept through name/sku/brand/
     * stock_quantity/status/status_label/url while the new renderer also gets
     * type/group/title/subtitle/identifier/badge.
     *
     * @return array<string,mixed>
     */
    private function item(
        string $type,
        string $badge,
        string $title,
        string $identifier,
        string $subtitle,
        string $url,
        string $status,
        ?int $stockQuantity = null,
    ): array {
        return [
            'id' => $type.':'.sha1($url),
            'type' => $type,
            'title' => $title,
            'name' => $title,
            'identifier' => $identifier,
            'sku' => $identifier,
            'subtitle' => $subtitle,
            'brand' => $subtitle,
            'meta' => $subtitle,
            'badge' => $badge,
            'status' => $status,
            'status_label' => $badge,
            'stock_quantity' => $stockQuantity,
            'url' => $url,
        ];
    }

    private function humanize(string $value): string
    {
        $value = trim(str_replace('_', ' ', $value));

        return $value === '' ? '' : mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    private function productStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Aktivan',
            'draft' => 'Nacrt',
            'inactive' => 'Neaktivan',
            'archived' => 'Arhiviran',
            default => $this->humanize($status),
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesCase;
use App\Models\CustomerCrmNote;
use App\Models\Order;
use App\Models\PortalConversation;
use App\Models\PortalMessage;
use App\Models\PortalOrderLinkHistory;
use App\Models\ProductWarranty;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class Customer360Service
{
    private const TIMELINE_LIMIT = 50;
    private const CRM_NOTES_LIMIT = 50;
    private const UNLINKED_BUYERS_MAX_LIMIT = 100;

    /** @return array{summary:array<string,int|float|string|null>,timeline:array<int,array<string,mixed>>,crm_notes:array<int,array<string,mixed>>} */
    public function build(User $customer): array
    {
        $commercialOrders = $this->commercialOrders($customer);
        $ordersCount = (int) (clone $commercialOrders)->count();
        $lifetimeRevenue = (float) (clone $commercialOrders)->sum('subtotal_rsd');
        $outstanding = (float) ((clone $commercialOrders)
            ->selectRaw('COALESCE(SUM(CASE WHEN subtotal_rsd > COALESCE(paid_total_rsd, 0) THEN subtotal_rsd - COALESCE(paid_total_rsd, 0) ELSE 0 END), 0) AS total')
            ->value('total') ?? 0);
        $lastPurchase = (clone $commercialOrders)->orderByDesc('created_at')->value('created_at');

        return [
            'summary' => [
                'orders_count' => $ordersCount,
                'lifetime_revenue_rsd' => $lifetimeRevenue,
                'average_order_value_rsd' => $ordersCount > 0 ? $lifetimeRevenue / $ordersCount : 0.0,
                'outstanding_rsd' => $outstanding,
                'last_purchase_at' => $this->timestamp($lastPurchase),
                'active_after_sales_count' => $this->activeAfterSalesCount($customer),
                'active_warranties_count' => $this->activeWarrantiesCount($customer),
                'open_conversations_count' => $this->openConversationsCount($customer),
                'unread_staff_messages_count' => $this->unreadStaffMessagesCount($customer),
            ],
            'timeline' => $this->timeline($customer),
            'crm_notes' => $this->crmNotes($customer),
        ];
    }

    /** @return Collection<int,array<string,mixed>> */
    public function unlinkedBuyers(string $search = '', int $limit = 50): Collection
    {
        $limit = max(1, min($limit, self::UNLINKED_BUYERS_MAX_LIMIT));
        $emailColumn = Schema::hasColumn('orders', 'shipping_email') ? 'shipping_email' : null;

        $query = Order::query()->operational()->whereNull('user_id');
        $search = trim($search);
        if ($search !== '') {
            $query->where(static function (Builder $orders) use ($search, $emailColumn): void {
                $orders->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('shipping_full_name', 'like', '%'.$search.'%')
                    ->orWhere('shipping_phone', 'like', '%'.$search.'%')
                    ->orWhere('shipping_address', 'like', '%'.$search.'%')
                    ->orWhere('shipping_city', 'like', '%'.$search.'%')
                    ->orWhere('shipping_postal_code', 'like', '%'.$search.'%');

                if ($emailColumn !== null) {
                    $orders->orWhere($emailColumn, 'like', '%'.$search.'%');
                }
            });
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get()
            ->map(static fn (Order $order): array => [
                'id' => (int) $order->id,
                'number' => (string) $order->order_number,
                'status' => (string) $order->status,
                'name' => $order->shipping_full_name !== null ? (string) $order->shipping_full_name : null,
                'email' => $emailColumn !== null && $order->getAttribute($emailColumn) !== null
                    ? (string) $order->getAttribute($emailColumn)
                    : null,
                'phone' => $order->shipping_phone !== null ? (string) $order->shipping_phone : null,
                'subtotal_rsd' => (float) $order->subtotal_rsd,
                'created_at' => $order->created_at?->toIso8601String(),
            ]);
    }

    private function commercialOrders(User $customer): Builder
    {
        return Order::query()
            ->operational()
            ->where('user_id', $customer->id)
            ->where('status', '!=', 'cancelled');
    }

    private function activeAfterSalesCount(User $customer): int
    {
        return $this->safeCount('after_sales_cases', static fn (): int => AfterSalesCase::query()
            ->whereHas('order', static fn (Builder $orders): Builder => $orders
                ->operational()
                ->where('user_id', $customer->id)
                ->where('status', '!=', 'cancelled'))
            ->whereNotIn('status', ['closed', 'rejected'])
            ->count());
    }

    private function activeWarrantiesCount(User $customer): int
    {
        return $this->safeCount('product_warranties', static fn (): int => ProductWarranty::query()
            ->ownedByOrderCustomer((int) $customer->id)
            ->whereHas('order', static fn (Builder $orders): Builder => $orders
                ->operational()
                ->where('status', '!=', 'cancelled'))
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', today())
            ->count());
    }

    private function openConversationsCount(User $customer): int
    {
        return $this->safeCount('portal_conversations', static fn (): int => PortalConversation::query()
            ->where('user_id', $customer->id)
            ->where('status', '!=', 'closed')
            ->count());
    }

    private function unreadStaffMessagesCount(User $customer): int
    {
        if (!Schema::hasTable('portal_conversations') || !Schema::hasTable('portal_messages')) {
            return 0;
        }

        try {
            return (int) PortalMessage::query()
                ->whereHas('conversation', static fn (Builder $conversations): Builder => $conversations->where('user_id', $customer->id))
                ->where('visibility', 'public')
                ->where('sender_id', $customer->id)
                ->whereNull('read_by_staff_at')
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function crmNotes(User $customer): array
    {
        if (!Schema::hasTable('customer_crm_notes')) {
            return [];
        }

        try {
            return CustomerCrmNote::query()
                ->where('user_id', $customer->id)
                ->with('author:id,first_name,last_name,username')
                ->latest('created_at')
                ->latest('id')
                ->limit(self::CRM_NOTES_LIMIT)
                ->get()
                ->map(fn (CustomerCrmNote $note): array => [
                    'id' => (int) $note->id,
                    'body' => (string) $note->body,
                    'created_at' => $note->created_at?->toIso8601String(),
                    'author' => $this->actor($note->author),
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function timeline(User $customer): array
    {
        $events = collect();

        try {
            $this->commercialOrders($customer)->latest('created_at')->limit(self::TIMELINE_LIMIT)->get()
                ->each(function (Order $order) use ($events): void {
                    $events->push($this->event(
                        'order',
                        $order->created_at,
                        'Porudžbina '.$order->order_number,
                        (string) $order->status,
                        (int) $order->id,
                    ));
                });
        } catch (Throwable) {
        }

        if (Schema::hasTable('portal_order_link_history')) {
            try {
                PortalOrderLinkHistory::query()
                    ->where(static function (Builder $links) use ($customer): void {
                        $links->where('to_user_id', $customer->id)->orWhere('from_user_id', $customer->id);
                    })
                    ->with(['order:id,order_number', 'actor:id,first_name,last_name,username'])
                    ->latest('created_at')
                    ->limit(self::TIMELINE_LIMIT)
                    ->get()
                    ->each(function (PortalOrderLinkHistory $link) use ($events): void {
                        $events->push($this->event(
                            'order_link',
                            $link->created_at,
                            'Povezivanje porudžbine '.($link->order?->order_number ?? '#'.$link->order_id),
                            $link->reason !== null ? (string) $link->reason : null,
                            (int) $link->order_id,
                            actor: $this->actor($link->actor),
                        ));
                    });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable('customer_crm_notes')) {
            try {
                CustomerCrmNote::query()
                    ->where('user_id', $customer->id)
                    ->with('author:id,first_name,last_name,username')
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(self::TIMELINE_LIMIT)
                    ->get()
                    ->each(function (CustomerCrmNote $note) use ($events): void {
                        $events->push($this->event(
                            'crm_note',
                            $note->created_at,
                            'CRM napomena',
                            (string) $note->body,
                            actor: $this->actor($note->author),
                        ));
                    });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable('after_sales_cases')) {
            try {
                AfterSalesCase::query()
                    ->whereHas('order', static fn (Builder $orders): Builder => $orders->operational()->where('user_id', $customer->id))
                    ->latest('updated_at')
                    ->limit(self::TIMELINE_LIMIT)
                    ->get()
                    ->each(function (AfterSalesCase $case) use ($events): void {
                        $events->push($this->event(
                            'after_sales',
                            $case->updated_at,
                            'Slučaj '.$case->case_number,
                            trim((string) $case->status.' · '.(string) $case->subject),
                            (int) $case->order_id,
                            afterSalesCaseId: (int) $case->id,
                        ));
                    });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable('portal_conversations')) {
            try {
                PortalConversation::query()
                    ->where('user_id', $customer->id)
                    ->latest('last_message_at')
                    ->latest('id')
                    ->limit(self::TIMELINE_LIMIT)
                    ->get()
                    ->each(function (PortalConversation $conversation) use ($events): void {
                        $events->push($this->event(
                            'conversation',
                            $conversation->last_message_at ?? $conversation->created_at,
                            (string) $conversation->subject,
                            (string) $conversation->status,
                            $conversation->order_id !== null ? (int) $conversation->order_id : null,
                            (int) $conversation->id,
                        ));
                    });
            } catch (Throwable) {
            }
        }

        return $events
            ->sortByDesc(static fn (array $event): string => (string) ($event['occurred_at'] ?? ''))
            ->take(self::TIMELINE_LIMIT)
            ->values()
            ->all();
    }

    /** @return array{type:string,occurred_at:?string,title:string,summary:?string,order_id:?int,conversation_id:?int,after_sales_case_id:?int,actor:?array} */
    private function event(
        string $type,
        mixed $occurredAt,
        string $title,
        ?string $summary,
        ?int $orderId = null,
        ?int $conversationId = null,
        ?int $afterSalesCaseId = null,
        ?array $actor = null,
    ): array {
        return [
            'type' => $type,
            'occurred_at' => $this->timestamp($occurredAt),
            'title' => $title,
            'summary' => $summary,
            'order_id' => $orderId,
            'conversation_id' => $conversationId,
            'after_sales_case_id' => $afterSalesCaseId,
            'actor' => $actor,
        ];
    }

    /** @return array{id:int,name:string}|null */
    private function actor(?User $user): ?array
    {
        if (!$user instanceof User) {
            return null;
        }

        return ['id' => (int) $user->id, 'name' => $user->displayName()];
    }

    private function timestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return $value instanceof \DateTimeInterface
                ? Carbon::instance($value)->toIso8601String()
                : Carbon::parse((string) $value)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }

    private function safeCount(?string $table, callable $callback): int
    {
        if ($table !== null && !Schema::hasTable($table)) {
            return 0;
        }

        try {
            return (int) $callback();
        } catch (Throwable) {
            return 0;
        }
    }
}

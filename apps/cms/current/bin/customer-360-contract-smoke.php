<?php

declare(strict_types=1);

use App\Models\AfterSalesCase;
use App\Models\CustomerCrmNote;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PortalConversation;
use App\Models\PortalMessage;
use App\Models\PortalOrderLinkHistory;
use App\Models\ProductWarranty;
use App\Models\User;
use App\Services\Customer360Service;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = 0;
$failures = 0;
$check = static function (bool $condition, string $message) use (&$checks, &$failures): void {
    $checks++;
    if ($condition) {
        echo 'PASS '.$message.PHP_EOL;
        return;
    }
    $failures++;
    echo 'FAIL '.$message.PHP_EOL;
};

$tableExists = Schema::hasTable('customer_crm_notes');
$modelExists = class_exists(CustomerCrmNote::class);
$userHasRelation = method_exists(User::class, 'crmNotes');
$model = $modelExists ? new CustomerCrmNote() : null;
$userRelation = $userHasRelation ? (new User())->crmNotes() : null;
$customerRelation = $modelExists ? $model->customer() : null;
$authorRelation = $modelExists ? $model->author() : null;
$casts = $modelExists ? $model->getCasts() : [];
$routesSource = file_get_contents(dirname(__DIR__).'/routes/api.php');
$routesSource = is_string($routesSource) ? $routesSource : '';

$check($tableExists, 'customer_crm_notes table exists');
$check($modelExists, 'CustomerCrmNote model exists');
$check($userRelation instanceof HasMany && $userRelation->getForeignKeyName() === 'user_id', 'User exposes crmNotes HasMany relation');
$check($modelExists && $model->getTable() === 'customer_crm_notes', 'CustomerCrmNote maps explicit table');
$check($modelExists && $model->getFillable() === ['user_id', 'author_user_id', 'body'], 'CustomerCrmNote fillable is exact');
$check($modelExists && ($casts['user_id'] ?? null) === 'integer' && ($casts['author_user_id'] ?? null) === 'integer', 'CustomerCrmNote foreign IDs cast to integer');
$check($customerRelation instanceof BelongsTo && $customerRelation->getForeignKeyName() === 'user_id', 'CustomerCrmNote customer relation uses user_id');
$check($authorRelation instanceof BelongsTo && $authorRelation->getForeignKeyName() === 'author_user_id', 'CustomerCrmNote author relation uses author_user_id');
$check($modelExists && !in_array(SoftDeletes::class, class_uses_recursive(CustomerCrmNote::class), true), 'CustomerCrmNote has no soft delete contract');
$check($tableExists && !Schema::hasColumn('customer_crm_notes', 'deleted_at'), 'customer_crm_notes has no deleted_at column');
$check(!str_contains($routesSource, 'customer-portal.users.crm-notes.update'), 'No CRM note update route exists');
$check(!str_contains($routesSource, 'customer-portal.users.crm-notes.destroy'), 'No CRM note delete route exists');

$serviceExists = class_exists(Customer360Service::class);
$serviceSourcePath = dirname(__DIR__).'/app/Services/Customer360Service.php';
$serviceSource = is_file($serviceSourcePath) ? file_get_contents($serviceSourcePath) : false;
$serviceSource = is_string($serviceSource) ? $serviceSource : '';
$check($serviceExists, 'Customer360Service exists');
$check($serviceExists && method_exists(Customer360Service::class, 'build'), 'Customer360Service exposes build');
$check($serviceExists && method_exists(Customer360Service::class, 'unlinkedBuyers'), 'Customer360Service exposes unlinkedBuyers');
$check($serviceSource !== '' && str_contains($serviceSource, 'TIMELINE_LIMIT = 50'), 'Customer360 timeline is server-capped at 50');
$check($serviceSource === '' || (!str_contains($serviceSource, "'gross_profit") && !str_contains($serviceSource, "'net_contribution") && !str_contains($serviceSource, "'ltv'") && !str_contains($serviceSource, "'gmroi'")), 'Customer360 read model has no profitability placeholders');

if (!$serviceExists) {
    $check(false, 'Customer360 aggregate fixture executes');
    $check(false, 'Customer360 unlinked-buyer fixture executes');
} else {
    DB::beginTransaction();
    try {
        $token = 'C360'.strtoupper(bin2hex(random_bytes(5)));
        $seedCustomer = User::query()
            ->whereHas('role', static fn ($roles) => $roles->where('slug', 'user'))
            ->first();
        if (!$seedCustomer instanceof User) {
            throw new RuntimeException('No registered customer template is available for transaction fixture');
        }
        $actor = User::query()
            ->whereHas('role', static fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))
            ->first() ?? $seedCustomer;
        $orderTemplate = Order::query()->first();
        $itemTemplate = OrderItem::query()->first();
        if (!$orderTemplate instanceof Order || !$itemTemplate instanceof OrderItem) {
            throw new RuntimeException('Order and order-item templates are required for transaction fixture');
        }

        $cloneCustomer = static function (User $seed, string $suffix) use ($token): User {
            $customer = $seed->replicate();
            $customer->username = strtolower($token.'_'.$suffix);
            $customer->email = strtolower($token.'_'.$suffix).'@example.invalid';
            $customer->phone = null;
            $customer->status = 'active';
            $customer->save();
            return $customer;
        };

        $cloneOrder = static function (
            Order $template,
            string $suffix,
            ?int $userId,
            float $subtotal,
            float $paid,
            string $status,
            string $createdAt,
            bool $archived = false,
        ) use ($token): Order {
            $order = $template->replicate();
            $order->order_number = $token.'-'.$suffix;
            $order->idempotency_key_hash = null;
            $order->request_fingerprint = null;
            $order->user_id = $userId;
            $order->status = $status;
            $order->subtotal_rsd = $subtotal;
            $order->paid_total_rsd = $paid;
            $order->shipping_full_name = $token.' Buyer';
            $order->shipping_phone = $token;
            $order->archived_at = $archived ? now() : null;
            $order->archived_by = null;
            $order->archive_reason = null;
            $order->purged_at = null;
            $order->purged_by = null;
            $order->purge_reason = null;
            $order->cancelled_at = $status === 'cancelled' ? now() : null;
            $order->cancelled_by = null;
            $order->completed_at = null;
            $order->completed_by = null;
            $order->created_at = Carbon::parse($createdAt);
            $order->updated_at = Carbon::parse($createdAt);
            $order->save();
            return $order;
        };

        $customer = $cloneCustomer($seedCustomer, 'main');
        $emptyCustomer = $cloneCustomer($seedCustomer, 'empty');
        $orderOne = $cloneOrder($orderTemplate, 'Q1', (int) $customer->id, 1000.0, 200.0, 'processing', '2026-09-01 10:00:00');
        $orderTwo = $cloneOrder($orderTemplate, 'Q2', (int) $customer->id, 500.0, 800.0, 'processing', '2026-09-02 11:00:00');
        $cloneOrder($orderTemplate, 'CANCELLED', (int) $customer->id, 700.0, 0.0, 'cancelled', '2026-09-03 12:00:00');
        $cloneOrder($orderTemplate, 'ARCHIVED', (int) $customer->id, 900.0, 0.0, 'processing', '2026-09-04 13:00:00', true);
        $unlinked = $cloneOrder($orderTemplate, 'UNLINKED', null, 1234.0, 0.0, 'new', '2026-09-05 14:00:00');
        $archivedUnlinked = $cloneOrder($orderTemplate, 'UNLINKED-ARCHIVED', null, 4321.0, 0.0, 'new', '2026-09-06 15:00:00', true);

        CustomerCrmNote::query()->create([
            'user_id' => $customer->id,
            'author_user_id' => $actor->id,
            'body' => 'Task2 transaction CRM note '.$token,
        ]);

        PortalOrderLinkHistory::query()->create([
            'order_id' => $orderOne->id,
            'from_user_id' => null,
            'to_user_id' => $customer->id,
            'changed_by' => $actor->id,
            'reason' => 'Task2 transaction link '.$token,
            'created_at' => now(),
        ]);

        AfterSalesCase::query()->create([
            'case_number' => $token.'-CASE',
            'order_id' => $orderOne->id,
            'opened_by' => $customer->id,
            'assigned_to' => $actor->id,
            'case_type' => 'complaint',
            'priority' => 'normal',
            'status' => 'open',
            'subject' => 'Task2 '.$token,
            'description' => 'Customer360 transaction fixture',
        ]);

        $conversation = PortalConversation::query()->create([
            'user_id' => $customer->id,
            'order_id' => $orderOne->id,
            'assigned_to' => $actor->id,
            'created_by' => $customer->id,
            'subject' => 'Task2 '.$token,
            'status' => 'waiting_staff',
            'priority' => 'normal',
            'last_message_at' => now(),
        ]);
        PortalMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $customer->id,
            'visibility' => 'public',
            'body' => 'Task2 unread customer message '.$token,
            'sent_at' => now(),
            'read_by_customer_at' => now(),
            'read_by_staff_at' => null,
            'created_at' => now(),
        ]);

        $orderItem = $itemTemplate->replicate();
        $orderItem->order_id = $orderOne->id;
        $orderItem->product_sku = $token.'-SKU';
        $orderItem->product_name = 'Task2 warranty product';
        $orderItem->save();
        ProductWarranty::query()->create([
            'warranty_number' => $token.'-WAR',
            'order_id' => $orderOne->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'user_id' => $customer->id,
            'warranty_rule_id' => null,
            'status' => 'active',
            'starts_at' => today()->subDay(),
            'expires_at' => today()->addYear(),
            'duration_months' => 12,
            'maintenance_interval_months' => null,
            'customer_name_snapshot' => $customer->displayName(),
            'customer_address_snapshot' => null,
            'customer_city_snapshot' => null,
            'customer_postal_code_snapshot' => null,
            'customer_phone_snapshot' => null,
            'product_sku_snapshot' => $orderItem->product_sku,
            'product_name_snapshot' => $orderItem->product_name,
            'quantity' => 1,
            'serial_numbers_json' => null,
            'terms_snapshot' => 'Task2 transaction warranty',
            'created_by' => $actor->id,
        ]);

        $service = new Customer360Service();
        $result = $service->build($customer);
        $summary = $result['summary'] ?? [];
        $check(($summary['orders_count'] ?? null) === 2, 'Customer360 excludes cancelled and archived orders');
        $check(abs((float) ($summary['lifetime_revenue_rsd'] ?? -1) - 1500.0) < 0.001, 'Customer360 lifetime revenue sums qualifying subtotal');
        $check(abs((float) ($summary['average_order_value_rsd'] ?? -1) - 750.0) < 0.001, 'Customer360 average order value is revenue divided by qualifying orders');
        $check(abs((float) ($summary['outstanding_rsd'] ?? -1) - 800.0) < 0.001, 'Customer360 outstanding clamps overpaid order at zero');
        $check(is_string($summary['last_purchase_at'] ?? null) && str_starts_with((string) $summary['last_purchase_at'], '2026-09-02'), 'Customer360 last purchase uses newest qualifying order');
        $check(($summary['active_after_sales_count'] ?? null) === 1, 'Customer360 counts active after-sales cases');
        $check(($summary['active_warranties_count'] ?? null) === 1, 'Customer360 counts active warranties');
        $check(($summary['open_conversations_count'] ?? null) === 1, 'Customer360 counts open portal conversations');
        $check(($summary['unread_staff_messages_count'] ?? null) === 1, 'Customer360 counts unread customer-originated staff messages');

        $empty = $service->build($emptyCustomer)['summary'] ?? [];
        $check(($empty['orders_count'] ?? null) === 0, 'Customer360 no-order customer has zero orders');
        $check(abs((float) ($empty['average_order_value_rsd'] ?? -1)) < 0.001, 'Customer360 no-order customer has zero average order value');
        $check(array_key_exists('last_purchase_at', $empty) && $empty['last_purchase_at'] === null, 'Customer360 no-order customer has null last purchase');

        $timeline = $result['timeline'] ?? [];
        $expectedTimelineKeys = ['type', 'occurred_at', 'title', 'summary', 'order_id', 'conversation_id', 'after_sales_case_id', 'actor'];
        $check(count($timeline) <= 50, 'Customer360 timeline is capped at 50 events');
        $check(collect($timeline)->every(static fn (array $event): bool => array_keys($event) === $expectedTimelineKeys), 'Customer360 timeline uses canonical event shape');
        $timelineTypes = collect($timeline)->pluck('type');
        $check($timelineTypes->contains('order') && $timelineTypes->contains('order_link') && $timelineTypes->contains('crm_note') && $timelineTypes->contains('after_sales') && $timelineTypes->contains('conversation'), 'Customer360 timeline merges canonical event sources');
        $check(collect($result['crm_notes'] ?? [])->contains(static fn (array $note): bool => str_contains((string) ($note['body'] ?? ''), $token)), 'Customer360 returns internal CRM notes');

        $candidates = $service->unlinkedBuyers($token, 50);
        $candidateIds = $candidates->pluck('id')->map(static fn ($id): int => (int) $id);
        $check($candidateIds->contains((int) $unlinked->id), 'Customer360 unlinked buyers includes operational user_id null order');
        $check(!$candidateIds->contains((int) $orderOne->id), 'Customer360 unlinked buyers excludes linked order');
        $check(!$candidateIds->contains((int) $archivedUnlinked->id), 'Customer360 unlinked buyers excludes archived order');
        $check($candidates->every(static fn (array $candidate): bool => !array_key_exists('suggested_user_id', $candidate) && !array_key_exists('match_score', $candidate) && !array_key_exists('confidence', $candidate)), 'Customer360 unlinked buyers exposes no inferred matching fields');
        $check($candidates->every(static fn (array $candidate): bool => !array_key_exists('user_id', $candidate)), 'Customer360 unlinked buyers does not leak ownership suggestion');
        $check($candidates->every(static fn (array $candidate): bool => array_keys($candidate) === ['id', 'number', 'status', 'name', 'email', 'phone', 'subtotal_rsd', 'created_at']), 'Customer360 unlinked buyers returns canonical order facts only');
    } catch (\Throwable $exception) {
        echo 'CUSTOMER360_FIXTURE_EXCEPTION='.get_class($exception).': '.$exception->getMessage().PHP_EOL;
        $check(false, 'Customer360 transaction fixture completes without exception');
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }
}

echo 'CUSTOMER360_CONTRACT_SMOKE='.$checks.'_CHECKS_'.($checks - $failures).'_PASS_'.$failures.'_FAIL'.PHP_EOL;
exit($failures === 0 ? 0 : 1);

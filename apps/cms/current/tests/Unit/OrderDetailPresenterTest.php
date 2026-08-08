<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderDetailPresenter;
use Tests\TestCase;

final class OrderDetailPresenterTest extends TestCase
{
    public function test_presenter_survives_zero_dates_and_uses_only_scalar_view_data(): void
    {
        $role = new Role();
        $role->setRawAttributes(['id' => 1, 'slug' => 'superadmin', 'name' => 'SuperAdministrator'], true);

        $actor = new User();
        $actor->setRawAttributes([
            'id' => 1,
            'role_id' => 1,
            'username' => 'admin',
            'first_name' => 'Aldin',
            'last_name' => 'Admin',
            'email' => 'admin@example.test',
            'status' => 'active',
        ], true);
        $actor->setRelation('role', $role);

        $order = new Order();
        $order->setRawAttributes([
            'id' => 1,
            'source_system' => 'laravel',
            'order_number' => 'ORD-ZERO-DATE',
            'user_id' => 1,
            'supplier_user_id' => 1,
            'supplier_name_snapshot' => 'Aldin Admin',
            'supplier_role_snapshot' => 'SuperAdministrator',
            'status' => 'new',
            'inventory_state' => 'reserved',
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060000000',
            'subtotal_rsd' => '12000.00',
            'paid_total_rsd' => '0.00',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'payment_state' => 'unpaid',
            'created_at' => '0000-00-00 00:00:00',
            'payment_due_at' => '0000-00-00 00:00:00',
            'accepted_at' => '0000-00-00 00:00:00',
            'expected_processing_at' => '0000-00-00 00:00:00',
            'expected_shipping_at' => '0000-00-00 00:00:00',
        ], true);

        foreach (['items', 'documents', 'payments', 'statusHistory', 'assignments', 'internalNotes'] as $relation) {
            $order->setRelation($relation, collect());
        }
        $order->setRelation('user', $actor);
        $order->setRelation('supplier', $actor);
        $order->setRelation('acceptedBy', null);
        $order->setRelation('assignedBy', null);
        $order->setRelation('bankAccount', null);
        $order->setRelation('commission', null);

        $detail = app(OrderDetailPresenter::class)->user($order, $actor, collect(), null, []);

        self::assertSame('—', $detail['order']['created_at']);
        self::assertSame('—', $detail['order']['payment_due_at']);
        self::assertNull($detail['order']['accepted_at']);
        self::assertSame('', $detail['order']['expected_processing_at']);
        self::assertSame('', $detail['order']['expected_shipping_at']);
        self::assertSame('12.000,00 RSD', $detail['order']['subtotal_rsd_display']);
        self::assertIsArray($detail['items']);
        self::assertIsArray($detail['documents']);
        self::assertIsArray($detail['payments']);
    }
}

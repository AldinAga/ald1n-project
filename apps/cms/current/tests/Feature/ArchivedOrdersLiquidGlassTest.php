<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class ArchivedOrdersLiquidGlassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_purge_dialogs_are_outside_the_table_and_bound_to_each_order(): void
    {
        $response = $this->previewAs('superadmin')->assertOk();
        $html = $response->getContent();
        self::assertIsString($html);

        $tableEnd = strpos($html, '</table>');
        $dialogStart = strpos($html, '<dialog ');
        self::assertNotFalse($tableEnd);
        self::assertNotFalse($dialogStart);
        self::assertGreaterThan($tableEnd, $dialogStart);

        $table = substr($html, 0, $tableEnd);
        self::assertStringNotContainsString('name="purge_reason"', $table);
        self::assertStringNotContainsString('name="confirmation"', $table);
        self::assertSame(2, substr_count($html, 'data-order-purge-open="'));
        self::assertSame(2, substr_count($html, 'data-order-permanent-delete'));
        self::assertSame(2, substr_count($html, 'name="purge_reason"'));
        self::assertSame(2, substr_count($html, 'name="confirmation"'));

        foreach ([8101 => 'APC-GLASS-8101', 8102 => 'APC-GLASS-8102'] as $id => $number) {
            self::assertStringContainsString('aria-controls="orderPurgeDialog-'.$id.'"', $html);
            self::assertStringContainsString('id="orderPurgeDialog-'.$id.'"', $html);
            self::assertStringContainsString('id="order-purge-reason-'.$id.'"', $html);
            self::assertStringContainsString('id="order-purge-confirmation-'.$id.'"', $html);
            self::assertStringContainsString('action="'.route('admin.orders.purge', ['orderId' => $id]).'"', $html);
            self::assertStringContainsString('Za potvrdu upiši tačan broj porudžbine: '.$number, $html);
        }

        self::assertStringContainsString('name="_method" value="DELETE"', $html);
        self::assertStringContainsString('name="_token"', $html);
        self::assertStringContainsString('data-order-purge-close', $html);
        self::assertStringContainsString('type="button" data-order-purge-close', $html);
        self::assertStringContainsString('Opozovi Arhiviranje', $html);
        self::assertStringContainsString('data-confirm="Trajno ukloniti porudžbinu', $html);
    }

    public function test_non_superadmin_never_receives_purge_controls_or_dialogs(): void
    {
        $html = $this->previewAs('admin')->assertOk()->getContent();
        self::assertIsString($html);
        self::assertStringContainsString('Opozovi Arhiviranje', $html);
        self::assertStringNotContainsString('data-order-purge-open="', $html);
        self::assertStringNotContainsString('data-order-permanent-delete', $html);
        self::assertStringNotContainsString('name="purge_reason"', $html);
        self::assertStringNotContainsString('id="orderPurgeDialog-', $html);
    }

    private function previewAs(string $role): TestResponse
    {
        $actor = User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => 'archive-glass-'.$role,
            'email' => 'archive-glass-'.$role.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'first_name' => 'Archive',
            'status' => 'active',
        ]);

        $items = [];
        foreach ([8101, 8102] as $id) {
            $order = new Order([
                'order_number' => 'APC-GLASS-'.$id,
                'status' => 'cancelled',
                'shipping_full_name' => 'Test Kupac',
                'shipping_phone' => '0601234567',
                'archive_reason' => 'Test razlog',
                'archived_at' => now(),
            ]);
            $order->id = $id;
            $items[] = $order;
        }
        $orders = new LengthAwarePaginator(
            $items,
            count($items),
            30,
            1,
            ['path' => '/__liquid-glass-archive-preview']
        );

        Route::middleware('web')->get(
            '/__liquid-glass-archive-preview',
            static fn () => view('admin.orders.archived', [
                'orders' => $orders,
                'query' => '',
                'canPurge' => $actor->hasRole('superadmin'),
            ])
        );

        return $this->actingAs($actor)->get('/__liquid-glass-archive-preview');
    }
}

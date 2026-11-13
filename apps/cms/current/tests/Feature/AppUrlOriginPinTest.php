<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderEmailOutbox;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use App\Services\OperationalNotificationService;
use App\Services\OrderEmailOutboxService;
use App\Services\ProductAnnouncementService;
use App\Services\SettingsService;
use App\Support\AppUrlOrigin;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Links that are stored or mailed for later must carry the APP_URL origin even
 * when the request arrives with a forged Host header.
 *
 * The probe routes call the real services inside a real kernel request, so the
 * global PinAppUrlOrigin middleware is part of what is under test. The test
 * client drops a forged Host unless the URI is absolute; every forged request
 * therefore uses an absolute URI and the response carries a control value
 * proving the forged host really became the request root.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class AppUrlOriginPinTest extends TestCase
{
    use RefreshDatabase;

    private const APP_ORIGIN = 'https://cms.example.test';
    private const FORGED_ORIGIN = 'http://evil.example';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        config()->set('app.url', self::APP_ORIGIN);
        $this->admin = $this->user('origin-pin-admin', 'admin');

        Route::post('/__origin-pin/outbox/{id}', function (int $id) {
            $order = Order::query()->with(['user', 'supplier'])->findOrFail($id);
            app(OrderEmailOutboxService::class)->orderCreated($order, $order->user);

            return response()->json(['request_root' => request()->root(), 'generated' => route('orders.show', $id)]);
        });
        Route::post('/__origin-pin/announce/{id}', function (int $id) {
            app(ProductAnnouncementService::class)->queueForNewlyPublished(Product::query()->findOrFail($id));

            return response()->json(['request_root' => request()->root()]);
        });
        Route::post('/__origin-pin/notify/{id}', function (int $id) {
            $order = Order::query()->findOrFail($id);
            app(OperationalNotificationService::class)
                ->order(User::query()->findOrFail($order->user_id), 'order.origin_pin_probe', 'Probe', 'Probe', $order);

            return response()->json(['request_root' => request()->root()]);
        });
    }

    public function test_stored_order_email_link_uses_app_url_not_the_forged_host(): void
    {
        $order = $this->createOrderWithoutEmails();
        $this->enableOrderEmails();

        $response = $this->forgedPost('/__origin-pin/outbox/'.$order->id)->assertOk();

        // Control: the forged host really was the request root.
        self::assertSame(self::FORGED_ORIGIN, $response->json('request_root'));
        $row = OrderEmailOutbox::query()->where('event_type', 'order_created')->firstOrFail();
        self::assertSame(self::APP_ORIGIN.'/admin/orders/'.$order->id, $row->action_url);
        self::assertSame(self::APP_ORIGIN.'/orders/'.$order->id, $response->json('generated'));
    }

    public function test_stored_product_announcement_links_use_app_url_not_the_forged_host(): void
    {
        $product = $this->product('PIN-ANNOUNCE');
        $this->image($product);
        app(SettingsService::class)->putMany(['product_email_new_items_enabled' => '1'], $this->admin->id);

        $response = $this->forgedPost('/__origin-pin/announce/'.$product->id)->assertOk();

        self::assertSame(self::FORGED_ORIGIN, $response->json('request_root'));
        $row = OrderEmailOutbox::query()->where('event_type', 'product_published')->firstOrFail();
        self::assertSame(self::APP_ORIGIN.'/catalog/'.$product->slug, $row->action_url);
        $imageUrl = (string) ($row->metadata_json['product_image_url'] ?? '');
        self::assertStringStartsWith(self::APP_ORIGIN.'/media/p/', $imageUrl);
        self::assertStringNotContainsString('evil', $row->action_url.$imageUrl);
    }

    public function test_stored_operational_notification_url_uses_app_url_not_the_forged_host(): void
    {
        $order = $this->createOrderWithoutEmails();

        $response = $this->forgedPost('/__origin-pin/notify/'.$order->id)->assertOk();

        self::assertSame(self::FORGED_ORIGIN, $response->json('request_root'));
        $notice = $this->admin->notifications()->get()
            ->first(static fn ($n): bool => ($n->data['event'] ?? null) === 'order.origin_pin_probe');
        self::assertNotNull($notice, 'The probe notification must be stored.');
        self::assertSame(self::APP_ORIGIN.'/admin/orders/'.$order->id, $notice->data['url']);
    }

    public function test_signed_product_media_url_keeps_verifying_when_the_origin_is_pinned(): void
    {
        $product = $this->product('PIN-SIGNED');
        $image = $this->image($product);
        app(SettingsService::class)->putMany(['product_email_new_items_enabled' => '1'], $this->admin->id);
        $this->forgedPost('/__origin-pin/announce/'.$product->id)->assertOk();
        $url = (string) (OrderEmailOutbox::query()->where('event_type', 'product_published')->firstOrFail()
            ->metadata_json['product_image_url'] ?? '');
        $parts = parse_url($url);
        self::assertSame('cms.example.test', $parts['host'] ?? null);
        self::assertSame('/media/p/'.$image->id.'/original', $parts['path'] ?? null);
        $pathAndQuery = $parts['path'].'?'.($parts['query'] ?? '');

        // Served from the pinned origin and from a forged host alike: the
        // signature covers image, rendition and version only.
        $this->get($pathAndQuery)->assertOk();
        $this->get(self::FORGED_ORIGIN.$pathAndQuery)->assertOk();
        // A tampered signature is still rejected.
        $this->get(preg_replace('/s=[a-f0-9]{32}/', 's='.str_repeat('0', 32), $pathAndQuery))->assertNotFound();
    }

    public function test_empty_app_url_leaves_url_generation_unchanged(): void
    {
        config()->set('app.url', '');
        $order = $this->createOrderWithoutEmails('http://localhost');
        $this->enableOrderEmails();

        $response = $this->forgedPost('/__origin-pin/outbox/'.$order->id)->assertOk();

        self::assertSame(self::FORGED_ORIGIN, $response->json('request_root'));
        self::assertSame(self::FORGED_ORIGIN.'/orders/'.$order->id, $response->json('generated'));
        $row = OrderEmailOutbox::query()->where('event_type', 'order_created')->firstOrFail();
        self::assertSame(self::FORGED_ORIGIN.'/admin/orders/'.$order->id, $row->action_url);
    }

    /** @return array<string,array{0:mixed,1:?string}> */
    public static function origins(): array
    {
        return [
            'plain https' => ['https://cms.ald1n.com', 'https://cms.ald1n.com'],
            'trailing slash' => ['https://cms.ald1n.com/', 'https://cms.ald1n.com'],
            'upper case and spaces' => ['  HTTPS://CMS.Ald1n.com  ', 'https://cms.ald1n.com'],
            'http with port' => ['http://127.0.0.1:8124', 'http://127.0.0.1:8124'],
            'localhost' => ['http://localhost', 'http://localhost'],
            'empty' => ['', null],
            'null' => [null, null],
            'array' => [['https://cms.ald1n.com'], null],
            'no scheme' => ['cms.ald1n.com', null],
            'scheme relative' => ['//cms.ald1n.com', null],
            'ftp' => ['ftp://cms.ald1n.com', null],
            'javascript' => ['javascript://cms.ald1n.com', null],
            'credentials' => ['https://user:secret@cms.ald1n.com', null],
            'sub path' => ['https://cms.ald1n.com/app', null],
            'query' => ['https://cms.ald1n.com?x=1', null],
            'fragment' => ['https://cms.ald1n.com#x', null],
            'bad host character' => ['https://cms_ald1n.com', null],
            'embedded space' => ['https://cms .ald1n.com', null],
            'backslash' => ['https://cms.ald1n.com\\evil.example', null],
            'port out of range' => ['https://cms.ald1n.com:99999', null],
        ];
    }

    #[DataProvider('origins')]
    public function test_only_a_plain_absolute_http_origin_is_accepted(mixed $configured, ?string $expected): void
    {
        config()->set('app.url', $configured);

        self::assertSame($expected, AppUrlOrigin::configured());
    }

    private function forgedPost(string $path): TestResponse
    {
        return $this->actingAs($this->admin)->post(self::FORGED_ORIGIN.$path, [], [
            'Host' => 'evil.example',
            'X-Forwarded-Host' => 'evil-forwarded.example',
        ]);
    }

    private function createOrderWithoutEmails(string $origin = ''): Order
    {
        app(SettingsService::class)->putMany(['order_email_enabled' => '0'], $this->admin->id);
        $product = $this->product('PIN-ORDER');
        $this->actingAs($this->admin)->post($origin.'/orders', [
            'idempotency_key' => 'origin-pin-order',
            'supplier_user_id' => $this->admin->id,
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060123456',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        self::assertSame(0, OrderEmailOutbox::query()->count());

        return Order::query()->sole();
    }

    private function enableOrderEmails(): void
    {
        app(SettingsService::class)->putMany([
            'order_email_enabled' => '1',
            'order_email_creation_enabled' => '1',
            'order_email_send_creator' => '1',
            'order_email_send_supplier' => '1',
            'order_email_creation_interval_minutes' => '0',
        ], $this->admin->id);
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => ucfirst($username),
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function product(string $sku): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => 'Proizvod '.$sku,
            'slug' => strtolower($sku),
            'price_amount' => 1000,
            'price_currency' => 'RSD',
            'description' => 'Test.',
            'stock_quantity' => 10,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    private function image(Product $product): ProductImage
    {
        $im = imagecreatetruecolor(40, 30);
        imagefill($im, 0, 0, imagecolorallocate($im, 40, 90, 160));
        ob_start();
        imagepng($im);
        imagedestroy($im);
        $bytes = (string) ob_get_clean();
        $path = 'products/'.$product->id.'/'.bin2hex(random_bytes(6)).'.png';
        Storage::disk('public')->put($path, $bytes);

        return ProductImage::query()->create([
            'product_id' => $product->id, 'storage_disk' => 'public', 'file_path' => $path,
            'original_filename' => 'p.png', 'mime_type' => 'image/png', 'file_size' => strlen($bytes),
            'file_hash' => hash('sha256', $bytes), 'sort_order' => 1, 'is_primary' => true,
        ]);
    }
}

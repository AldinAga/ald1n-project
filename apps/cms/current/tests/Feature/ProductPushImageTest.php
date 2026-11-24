<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MobilePushOutbox;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use App\Services\MobilePushDispatcher;
use App\Services\MobilePushOutboxService;
use App\Services\ProductAnnouncementService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Push o novom artiklu nosi sliku (Expo richContent.image); bez slike payload ostaje isti.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class ProductPushImageTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('public');
        config()->set('app.url', 'https://cms.example.test');
        config()->set('mobile.push.enabled', true);
        config()->set('mobile.push.provider', 'expo');
        config()->set('mobile.push.expo.send_url', 'https://exp.host/--/api/v2/push/send');
        Http::preventStrayRequests();
        Http::fake(['https://exp.host/--/api/v2/push/send' => Http::response(['data' => ['status' => 'ok', 'id' => 'ticket-img']])]);
    }

    public function test_product_push_sends_rich_content_image_and_never_notes(): void
    {
        $this->recipient();
        $product = $this->product(['notes' => 'Interna napomena za SubAgente']);
        $image = $this->image($product);

        app(ProductAnnouncementService::class)->queueForNewlyPublished($product->fresh());
        $row = MobilePushOutbox::query()->sole();
        self::assertSame($image->url, $row->data_json['image_url']);
        self::assertStringNotContainsString('napomena', mb_strtolower($row->title.' '.$row->message));

        self::assertSame(1, app(MobilePushDispatcher::class)->dispatch(10)['sent']);
        Http::assertSent(function ($request) use ($image): bool {
            $p = $request->data();
            return ($p['richContent'] ?? null) === ['image' => $image->url]
                && !array_key_exists('image_url', $p['data'])
                && !str_contains(json_encode($p), 'Interna napomena');
        });
    }

    public function test_product_without_image_keeps_payload_unchanged(): void
    {
        $this->recipient();
        $product = $this->product();

        app(ProductAnnouncementService::class)->queueForNewlyPublished($product->fresh());
        self::assertArrayNotHasKey('image_url', MobilePushOutbox::query()->sole()->data_json);

        self::assertSame(1, app(MobilePushDispatcher::class)->dispatch(10)['sent']);
        Http::assertSent(fn ($request): bool => !array_key_exists('richContent', $request->data()));
    }

    public function test_whitelist_accepts_only_https_image_url_within_length(): void
    {
        $user = $this->recipient();
        $service = app(MobilePushOutboxService::class);
        $cases = [
            'https://cms.example.test/media/p/1.jpg?s=a' => true,
            'http://cms.example.test/media/p/1.jpg' => false,
            'javascript:alert(1)' => false,
            'https://cms.example.test/'.str_repeat('a', 2100) => false,
            'https://x.test/a b.jpg' => false,
        ];
        foreach ($cases as $url => $kept) {
            MobilePushOutbox::query()->delete();
            $service->enqueue($user, ['event' => 'x', 'title' => 'T', 'message' => 'M', 'image_url' => $url]);
            self::assertSame($kept, array_key_exists('image_url', MobilePushOutbox::query()->sole()->data_json), $url);
        }

        MobilePushOutbox::query()->delete();
        $service->enqueue($user, ['event' => 'x', 'title' => 'T', 'message' => 'M', 'image_url' => ['https://a.test/x.jpg']]);
        self::assertArrayNotHasKey('image_url', MobilePushOutbox::query()->sole()->data_json);
    }

    private function recipient(): User
    {
        $n = ++$this->seq;
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'pushimg'.$n, 'email' => 'pushimg'.$n.'@example.test',
            'password_hash' => Hash::make('Secret123!'), 'first_name' => 'Push', 'status' => 'active',
        ]);
        $user->notificationPreference()->create([
            'in_app_enabled' => false, 'email_enabled' => false, 'push_enabled' => true,
            'order_updates' => true, 'payment_alerts' => true,
            'warranty_updates' => true, 'warranty_expiry_alerts' => false,
        ]);
        $token = 'ExponentPushToken[pushimg-'.$n.']';
        $user->mobileDevices()->create([
            'installation_id' => (string) Str::uuid(), 'platform' => 'android', 'device_name' => 'Synthetic',
            'push_provider' => 'expo', 'push_token' => $token, 'push_token_hash' => hash('sha256', $token),
            'notifications_enabled' => true, 'last_seen_at' => now(),
        ]);

        return $user;
    }

    private function product(array $extra = []): Product
    {
        $n = ++$this->seq;

        return Product::query()->create($extra + [
            'sku' => 'PI-'.$n, 'name' => 'Artikal '.$n, 'slug' => 'artikal-'.$n,
            'price_amount' => 1000, 'price_currency' => 'RSD', 'description' => 'Opis.',
            'stock_quantity' => 10, 'low_stock_threshold' => 1, 'status' => 'active',
            'created_by' => User::query()->orderBy('id')->valueOrFail('id'),
        ]);
    }

    private function image(Product $product): ProductImage
    {
        $path = 'products/'.$product->id.'/'.bin2hex(random_bytes(6)).'.png';
        Storage::disk('public')->put($path, 'png');

        return ProductImage::query()->create([
            'product_id' => $product->id, 'storage_disk' => 'public', 'file_path' => $path,
            'original_filename' => 'p.png', 'mime_type' => 'image/png', 'file_size' => 3,
            'file_hash' => hash('sha256', $path), 'sort_order' => 0, 'is_primary' => true,
        ]);
    }
}

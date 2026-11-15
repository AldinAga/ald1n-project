<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B883, public storefront: a failed validation on a guest public-shop route
 * (catalog filters, cart add and update) used to redirect to the Referer.
 * It must go to a fixed local page on the APP_URL origin instead: the catalog
 * without the invalid parameters, or the cart. Each case is requested with a
 * hostile Referer, a forged Host (absolute URI plus control assertion) and
 * both.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicShopLocalValidationRedirectB883Test extends TestCase
{
    use RefreshDatabase;

    private const ORIGIN = 'https://cms.example.test';
    private const EVIL_REFERER = 'https://evil.example/x';
    private const EVIL_HOST = 'http://evil.example';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url', self::ORIGIN);
        config()->set('public_storefront.enabled', true);
        app(SettingsService::class)->putMany(['public_storefront_open' => '1', 'eur_rsd_rate' => '117.78'], null);
        $owner = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'b883-shop-owner',
            'email' => 'b883-shop-owner@example.test',
            'password_hash' => Hash::make('B883-Fake-Only-Not-Production!'),
            'status' => 'active',
        ]);
        $this->product = Product::query()->create([
            'created_by' => $owner->id,
            'sku' => 'B883-SHOP',
            'name' => 'Proizvod B883',
            'slug' => 'proizvod-b883',
            'price_amount' => 1000,
            'price_currency' => 'RSD',
            'description' => 'Test.',
            'stock_quantity' => 10,
            'status' => 'active',
        ]);
    }

    /** @return array<string,array{0:string,1:string,2:array<string,string>,3:string,4:string,5:string}> */
    public static function failingRequests(): array
    {
        $flows = [
            'catalog: stock filter' => ['GET', '/katalog?stock=x', [], '/katalog', 'stock'],
            'catalog: sort and brand' => ['GET', '/katalog?sort=zzz&brand_id=0', [], '/katalog', 'sort'],
            'catalog: search too long' => ['GET', '/katalog?q='.str_repeat('a', 81), [], '/katalog', 'q'],
            'cart add: quantity 0' => ['POST', '/korpa/dodaj/{id}', ['quantity' => '0'], '/korpa', 'quantity'],
            'cart add: quantity missing' => ['POST', '/korpa/dodaj/{id}', [], '/korpa', 'quantity'],
            'cart add: quantity too large' => ['POST', '/korpa/dodaj/{id}', ['quantity' => '21'], '/korpa', 'quantity'],
            'cart update: quantity too large' => ['PATCH', '/korpa/{id}', ['quantity' => '99'], '/korpa', 'quantity'],
            'cart update: quantity not a number' => ['PATCH', '/korpa/{id}', ['quantity' => 'abc'], '/korpa', 'quantity'],
        ];

        $cases = [];
        foreach ($flows as $name => [$method, $uri, $data, $expected, $errorKey]) {
            foreach (['referer', 'host', 'referer+host'] as $variant) {
                $cases[$name.' / '.$variant] = [$method, $uri, $data, $expected, $errorKey, $variant];
            }
        }

        return $cases;
    }

    /** @param array<string,string> $data */
    #[DataProvider('failingRequests')]
    public function test_failed_validation_goes_to_a_fixed_local_page_on_the_app_url_origin(
        string $method,
        string $uri,
        array $data,
        string $expectedPath,
        string $errorKey,
        string $variant,
    ): void {
        $response = $this->hostile($method, $uri, $data, $variant);

        $response->assertStatus(302);
        self::assertSame(self::ORIGIN.$expectedPath, (string) $response->headers->get('Location'));
        $response->assertSessionHasErrors($errorKey);
    }

    /** @return array<string,array{0:string,1:string,2:array<string,string>,3:string,4:string,5:string}> */
    public static function unpinnedRequests(): array
    {
        return array_filter(
            self::failingRequests(),
            static fn (array $case): bool => $case[5] === 'referer+host',
        );
    }

    /** @param array<string,string> $data */
    #[DataProvider('unpinnedRequests')]
    public function test_without_a_usable_app_url_the_redirect_is_relative(
        string $method,
        string $uri,
        array $data,
        string $expectedPath,
        string $errorKey,
        string $variant,
    ): void {
        config()->set('app.url', '');

        $response = $this->hostile($method, $uri, $data, $variant);

        $response->assertStatus(302);
        self::assertSame($expectedPath, (string) $response->headers->get('Location'));
    }

    public function test_catalog_whitelist_fallbacks_still_answer_200_without_a_redirect(): void
    {
        $response = $this->get('/katalog?per_page=zz&view=zz', ['Referer' => self::EVIL_REFERER]);

        $response->assertOk();
        self::assertFalse($response->headers->has('Location'));
    }

    public function test_valid_cart_add_and_update_are_unchanged(): void
    {
        $headers = ['Referer' => self::EVIL_REFERER];

        $add = $this->post('/korpa/dodaj/'.$this->product->id, ['quantity' => '2'], $headers);
        $add->assertStatus(302);
        self::assertSame('/korpa', (string) $add->headers->get('Location'));
        $add->assertSessionHas('status');

        $update = $this->patch('/korpa/'.$this->product->id, ['quantity' => '3'], $headers);
        $update->assertStatus(302);
        self::assertSame('/korpa', (string) $update->headers->get('Location'));
    }

    public function test_json_validation_keeps_the_422_without_a_redirect(): void
    {
        $response = $this->getJson('/katalog?stock=x', ['Referer' => self::EVIL_REFERER]);

        $response->assertUnprocessable()->assertJsonValidationErrors('stock');
        self::assertFalse($response->headers->has('Location'));
    }

    public function test_theme_switch_ignores_a_hostile_back_value_and_referer(): void
    {
        foreach (['https://evil.example/x', '//evil.example/x', '/\\evil.example'] as $back) {
            $response = $this->post('/tema', ['theme' => 'dark', 'back' => $back], ['Referer' => self::EVIL_REFERER]);

            $response->assertStatus(303);
            $location = (string) $response->headers->get('Location');
            self::assertStringStartsWith('/', $location);
            self::assertStringNotContainsString('evil.example', $location);
        }

        $this->post('/tema', ['theme' => 'neon'], ['Referer' => self::EVIL_REFERER])->assertStatus(422);
    }

    /** @param array<string,string> $data */
    private function hostile(string $method, string $uri, array $data, string $variant): TestResponse
    {
        $uri = str_replace('{id}', (string) $this->product->id, $uri);
        $headers = [];
        if (str_contains($variant, 'referer')) {
            $headers['Referer'] = self::EVIL_REFERER;
        }
        if (str_contains($variant, 'host')) {
            $uri = self::EVIL_HOST.$uri;
            $headers['Host'] = 'evil.example';
            $headers['X-Forwarded-Host'] = 'evil-forwarded.example';
        }

        $response = $this->call($method, $uri, $data, [], [], $this->transformHeadersToServerVars($headers));

        if (str_contains($variant, 'host')) {
            // Control: the forged host really became the request root.
            self::assertSame(self::EVIL_HOST, request()->root());
        }

        return $response;
    }
}

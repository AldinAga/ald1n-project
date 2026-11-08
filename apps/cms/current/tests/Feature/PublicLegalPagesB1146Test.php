<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\PublicLegalPagesService;
use App\Services\PublicLegalTextStore;
use App\Services\PublicMerchantPolicyService;
use App\Services\PublicStorefrontOnReadinessService;
use App\Services\PublicStorefrontStateService;
use App\Services\SettingsService;
use App\Support\PublicLegalTemplates;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Public legal pages, the owner's legal-text editor, the "Javna prodavnica"
 * menu entry and the ON checklist. Synthetic data only; the storefront is
 * never opened by these tests.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicLegalPagesB1146Test extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url', 'https://cms.ald1n.com');
        config()->set('public_storefront.enabled', false);
        config()->set('public_storefront.checkout_enabled', false);
        $this->owner = $this->user('b1146-owner', 'superadmin');
        app(SettingsService::class)->putMany([
            'documents_company_name' => 'Sinteticki Trgovac DOO',
            'documents_company_address' => 'Testna ulica 10',
            'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '100200300',
        ], $this->owner->id);
    }

    public function test_storefront_menu_entry_is_shown_to_superadmin_only(): void
    {
        $this->actingAs($this->owner)->get('/admin/settings')->assertOk()
            ->assertSee('data-nav-public-storefront', false)
            ->assertSee('href="'.route('admin.settings.public-storefront.index').'"', false);

        $admin = $this->user('b1146-admin', 'admin');
        $this->actingAs($admin)->get('/admin/settings')->assertOk()
            ->assertDontSee('data-nav-public-storefront', false);
        $this->actingAs($admin)->get('/admin/settings/public-storefront')->assertForbidden();
    }

    public function test_every_legal_page_is_public_while_closed_and_shows_trader_data(): void
    {
        Product::query()->create([
            'sku' => 'B1146-LEAK-PROBE', 'slug' => 'b1146-leak-probe',
            'name' => 'B1146 Tajni Artikal', 'description' => 'B1146 leak probe',
            'price_amount' => 98765, 'price_currency' => 'RSD',
            'stock_quantity' => 3, 'status' => 'active', 'created_by' => $this->owner->id,
        ]);
        self::assertFalse(app(PublicStorefrontStateService::class)->isOpen());

        foreach (['/pravne-informacije', ...array_map(
            static fn (array $page): string => '/'.$page['slug'], PublicLegalTemplates::PAGES)] as $uri) {
            $response = $this->get($uri)->assertOk();
            $html = $response->getContent();
            $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            self::assertStringNotContainsString('<script', strtolower($html), $uri);
            self::assertStringNotContainsString('B1146 Tajni Artikal', $html, $uri);
            self::assertStringNotContainsString('B1146-LEAK-PROBE', $html, $uri);
            self::assertStringNotContainsString('98765', $html, $uri);
            // Closed shop: no links to shop pages that would answer 404.
            self::assertStringNotContainsString('href="/katalog"', $html, $uri);
            self::assertStringNotContainsString('href="/korpa"', $html, $uri);
            // Footer links stay relative (B899) and back-to-top exists.
            $response->assertSee('href="/uslovi-prodaje"', false)
                ->assertSee('href="/politika-privatnosti"', false)
                ->assertSee('class="shop-back-to-top" href="#vrh" aria-label="Nazad na vrh stranice"', false);
            self::assertStringNotContainsString('https://cms.ald1n.com/uslovi-prodaje', $html, $uri);
        }

        $this->get('/podaci-o-trgovcu')->assertOk()
            ->assertSee('Sinteticki Trgovac DOO')->assertSee('Testna ulica 10, Beograd')
            ->assertSee('100200300')
            ->assertSee('podatak nije unet'); // matični broj and phone are not entered
        $this->get('/uslovi-prodaje')->assertOk()
            ->assertSee('Sinteticki Trgovac DOO')->assertSee('Opšti uslovi prodaje');
        $this->get('/pravo-na-odustanak')->assertOk()
            ->assertSee('Obrazac za odustanak od ugovora zaključenog na daljinu')
            ->assertSee('Datum prijema robe');
        foreach (PublicLegalTemplates::EDITABLE as $page) {
            self::assertStringNotContainsString('{{', $this->get('/'.PublicLegalTemplates::PAGES[$page]['slug'])
                ->getContent(), $page);
        }

        $this->get('/katalog')->assertNotFound();
        $this->get('/korpa')->assertNotFound();
        $this->get('/porucivanje')->assertNotFound();
    }

    public function test_missing_trader_identity_is_marked_and_placeholder_brand_is_not_used(): void
    {
        app(SettingsService::class)->putMany([
            'documents_company_name' => 'Ald1n',
            'documents_company_tax_id' => '',
        ], $this->owner->id);
        $html = $this->get('/podaci-o-trgovcu')->assertOk()->getContent();
        self::assertGreaterThanOrEqual(4, substr_count($html, 'podatak nije unet'));
        self::assertStringNotContainsString('<dd>Ald1n</dd>', $html);
    }

    public function test_legal_routes_have_unique_names_and_slugs(): void
    {
        $names = [];
        $uris = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->getName() !== null) $names[] = $route->getName();
            foreach ($route->methods() as $method) $uris[] = $method.' '.$route->uri();
        }
        foreach (array_keys(PublicLegalTemplates::PAGES) as $page) {
            self::assertSame(1, count(array_keys($names, 'legal.'.$page, true)), $page);
        }
        self::assertSame(1, count(array_keys($names, 'legal.index', true)));
        $slugs = array_column(PublicLegalTemplates::PAGES, 'slug');
        self::assertSame(count($slugs), count(array_unique($slugs)));
        foreach ($slugs as $slug) {
            self::assertSame(1, count(array_keys($uris, 'GET '.$slug, true)), $slug);
        }
        self::assertSame('/uslovi-prodaje', route('legal.terms', [], false));
        self::assertSame('/politika-privatnosti', route('legal.privacy', [], false));
    }

    public function test_absolute_urls_come_from_app_url_and_reject_unsafe_bases(): void
    {
        $this->app['request']->headers->set('host', 'evil.example');
        $urls = app(PublicLegalPagesService::class)->absoluteUrls();
        self::assertSame('https://cms.ald1n.com/uslovi-prodaje', $urls['terms']['url']);
        self::assertSame('https://cms.ald1n.com/politika-privatnosti', $urls['privacy']['url']);
        self::assertTrue($urls['terms']['valid']);
        foreach ($urls as $url) self::assertStringNotContainsString('evil.example', $url['url']);

        foreach (['http://cms.ald1n.com', 'https://localhost', 'https://127.0.0.1', ''] as $base) {
            config()->set('app.url', $base);
            $urls = app(PublicLegalPagesService::class)->absoluteUrls();
            self::assertFalse($urls['terms']['valid'], $base);
            self::assertFalse($urls['privacy']['valid'], $base);
        }
    }

    public function test_admin_page_offers_the_legal_urls_without_approving_anything(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin/settings/public-storefront')->assertOk();
        $response->assertSee('data-terms-url="https://cms.ald1n.com/uslovi-prodaje"', false)
            ->assertSee('data-privacy-url="https://cms.ald1n.com/politika-privatnosti"', false)
            ->assertSee('Koristi ove adrese')
            ->assertSee('data-legal-missing', false)
            ->assertSee('data-approval-state="none"', false);
        self::assertSame('', (string) app(SettingsService::class)->get('public_checkout_terms_url', ''));
        self::assertFalse(app(PublicMerchantPolicyService::class)->review()['approved']);
    }

    public function test_only_superadmin_can_edit_legal_texts(): void
    {
        $this->get('/admin/settings/public-storefront/legal/terms')->assertRedirect('/login');
        $admin = $this->user('b1146-editor-admin', 'admin');
        $this->actingAs($admin)->get('/admin/settings/public-storefront/legal/terms')->assertForbidden();
        $this->actingAs($admin)->put('/admin/settings/public-storefront/legal/terms', ['body' => 'X'])
            ->assertForbidden();
        $this->actingAs($admin)->post('/admin/settings/public-storefront/legal/terms/reset')->assertForbidden();
        self::assertFalse(app(PublicLegalTextStore::class)->isCustom('terms'));

        $this->actingAs($this->owner)->get('/admin/settings/public-storefront/legal/trader')->assertNotFound();
        $this->actingAs($this->owner)->get('/admin/settings/public-storefront/legal/terms')->assertOk()
            ->assertSee('Vrati na šablon')->assertSee('naziv_firme')->assertSee('podatak nije unet')
            ->assertSee('href="/uslovi-prodaje"', false);
    }

    public function test_saved_text_is_escaped_audited_by_hash_and_resettable(): void
    {
        $body = "## Naslov\n\n<script>alert(1)</script> **Vazno** za {{naziv_firme}}, PIB {{pib}}, tel {{telefon}}.\n\n"
            ."- [opasno](javascript:alert(1))\n- [dobro](https://example.com/a)\n\n<img src=x onerror=alert(1)> {{nepoznato}}";
        $this->actingAs($this->owner)->put('/admin/settings/public-storefront/legal/terms', ['body' => $body])
            ->assertRedirect('/admin/settings/public-storefront/legal/terms')->assertSessionHasNoErrors();
        self::assertTrue(app(PublicLegalTextStore::class)->isCustom('terms'));

        $html = $this->get('/uslovi-prodaje')->assertOk()->getContent();
        self::assertStringNotContainsString('<script', strtolower($html));
        self::assertStringNotContainsString('<img', strtolower($html));
        self::assertDoesNotMatchRegularExpression('/href\s*=\s*["\']?\s*javascript/i', $html);
        self::assertStringContainsString('[opasno](javascript:alert(1)', $html, 'Unsafe link stays plain text.');
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringContainsString('<strong>Vazno</strong> za Sinteticki Trgovac DOO, PIB 100200300', $html);
        self::assertStringContainsString('tel <span class="legal-missing">podatak nije unet</span>', $html);
        self::assertStringContainsString('<a href="https://example.com/a" rel="noopener noreferrer">dobro</a>', $html);
        self::assertStringContainsString('{{nepoznato}}', $html);
        self::assertStringContainsString('<h2>Naslov</h2>', $html);

        $log = AuditLog::query()->where('action', 'settings.public_legal_text_updated')->sole();
        $json = json_encode([$log->before_json, $log->after_json, $log->metadata_json, $log->subject]);
        self::assertStringNotContainsString('alert', (string) $json);
        self::assertStringNotContainsString('Vazno', (string) $json);
        self::assertSame(hash('sha256', PublicLegalTextStore::normalize($body)), $log->after_json['text_sha256']);
        self::assertSame(hash('sha256', PublicLegalTemplates::body('terms')), $log->before_json['text_sha256']);

        // Same text again: no new audit entry.
        $this->put('/admin/settings/public-storefront/legal/terms', ['body' => $body])->assertSessionHasNoErrors();
        self::assertSame(1, AuditLog::query()->where('action', 'settings.public_legal_text_updated')->count());

        $this->post('/admin/settings/public-storefront/legal/terms/reset')->assertRedirect();
        self::assertFalse(app(PublicLegalTextStore::class)->isCustom('terms'));
        self::assertSame(1, AuditLog::query()->where('action', 'settings.public_legal_text_reset')->count());
        $this->get('/uslovi-prodaje')->assertOk()->assertDontSee('Vazno');
    }

    public function test_text_size_limit_and_empty_text_are_rejected(): void
    {
        $this->actingAs($this->owner)->put('/admin/settings/public-storefront/legal/privacy',
            ['body' => str_repeat('a', PublicLegalTextStore::MAX_LENGTH + 1)])->assertSessionHasErrors('body');
        $this->put('/admin/settings/public-storefront/legal/privacy', ['body' => "  \n "])
            ->assertSessionHasErrors('body');
        self::assertFalse(app(PublicLegalTextStore::class)->isCustom('privacy'));
        self::assertSame(0, AuditLog::query()->where('action', 'settings.public_legal_text_updated')->count());
    }

    public function test_legal_text_change_invalidates_merchant_approval(): void
    {
        $this->approveCompletePolicy();
        $merchant = app(PublicMerchantPolicyService::class);
        $before = $merchant->review();
        self::assertTrue($before['approved']);

        // Fingerprint covers the effective texts even without the controller.
        app(SettingsService::class)->putMany([PublicLegalTextStore::key('cookies') => 'Novi tekst o kolacicima.'], null);
        $changed = $merchant->review();
        self::assertNotSame($before['fingerprint'], $changed['fingerprint']);
        self::assertFalse($changed['approved']);

        app(SettingsService::class)->putMany([PublicLegalTextStore::key('cookies') => ''], null);
        self::assertTrue($merchant->review()['approved'], 'Restoring the exact signed text restores the match.');

        // The editor revokes the stored approval explicitly.
        $this->actingAs($this->owner)->put('/admin/settings/public-storefront/legal/delivery',
            ['body' => "## Dostava\n\nIzmenjeni uslovi."])->assertSessionHasNoErrors();
        self::assertFalse($merchant->review()['approved']);
        self::assertSame('', (string) app(SettingsService::class)->get('public_checkout_approval_digest'));
        $this->actingAs($this->owner)->get('/admin/settings/public-storefront')->assertOk()
            ->assertSee('data-approval-state="none"', false);
    }

    public function test_checklist_shows_each_failed_requirement_and_toggle_names_it(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin/settings/public-storefront')->assertOk();
        $response->assertSee('data-store-requirement="code" data-ok="0"', false)
            ->assertSee('data-store-requirement="schema" data-ok="1"', false)
            ->assertSee('data-store-requirement="merchant" data-ok="0"', false)
            ->assertSee('data-store-item="flag_catalog" data-ok="0"', false)
            ->assertSee('data-store-item="terms_url" data-ok="0"', false)
            ->assertSee('data-store-item="approval" data-ok="0"', false)
            ->assertSee('data-store-on-blocked', false)
            ->assertSee('disabled aria-describedby="store-on-blocked"', false);

        $this->post('/admin/settings/public-storefront/toggle', ['enabled' => '1'])
            ->assertSessionHasErrors(['enabled' => 'ON nije moguć. Nije ispunjeno: '
                .'Kod prodavnice je isporučen i uključen na serveru; Uslovi trgovca su potpuni i potvrđeni.']);
        self::assertFalse(app(PublicStorefrontStateService::class)->isOpen());

        config()->set('public_storefront.enabled', true);
        config()->set('public_storefront.checkout_enabled', true);
        $this->fillCompletePolicy();
        $this->get('/admin/settings/public-storefront')->assertOk()
            ->assertSee('data-store-requirement="code" data-ok="1"', false)
            ->assertSee('data-store-requirement="merchant" data-ok="0"', false)
            ->assertSee('data-store-item="policy_complete" data-ok="1"', false)
            ->assertSee('data-store-item="approval" data-ok="0"', false);
        $this->post('/admin/settings/public-storefront/toggle', ['enabled' => '1'])
            ->assertSessionHasErrors(['enabled' => 'ON nije moguć. Nije ispunjeno: Uslovi trgovca su potpuni i potvrđeni.']);
        self::assertFalse(app(PublicStorefrontStateService::class)->isOpen());

        $this->approveCompletePolicy();
        $ready = app(PublicStorefrontOnReadinessService::class)->evaluate();
        self::assertTrue($ready['ready']);
        self::assertSame([], $ready['failed']);
        $this->get('/admin/settings/public-storefront')->assertOk()
            ->assertSee('data-store-requirement="merchant" data-ok="1"', false)
            ->assertSee('data-approval-state="approved"', false)
            ->assertDontSee('data-store-on-blocked', false);
        self::assertFalse(app(PublicStorefrontStateService::class)->isOpen(), 'Tests never open the shop.');
    }

    public function test_missing_schema_is_reported_by_the_shared_evaluator(): void
    {
        config()->set('public_storefront.enabled', true);
        config()->set('public_storefront.checkout_enabled', true);
        $this->approveCompletePolicy();
        $review = app(PublicMerchantPolicyService::class)->review();
        $real = Schema::getFacadeRoot();
        $fake = new class($real) {
            public function __construct(private readonly object $real) {}
            public function hasTable(string $table): bool
            {
                return $table !== PublicStorefrontOnReadinessService::SCHEMA_TABLE && $this->real->hasTable($table);
            }
            public function hasColumn(string $table, string $column): bool
            {
                return $column !== 'delivery_method' && $this->real->hasColumn($table, $column);
            }
            /** @param list<string> $columns */
            public function hasColumns(string $table, array $columns): bool
            {
                return !in_array('delivery_method', $columns, true) && $this->real->hasColumns($table, $columns);
            }
        };
        Schema::swap($fake);
        try {
            $result = (new PublicStorefrontOnReadinessService(app(PublicMerchantPolicyService::class)))->evaluate($review);
        } finally {
            Schema::swap($real);
        }
        self::assertFalse($result['ready']);
        self::assertSame(['Baza je spremna za javne porudžbine'], $result['failed']);
        $items = array_column($result['requirements']['schema']['items'], 'ok', 'code');
        self::assertFalse($items['schema_public_order_accesses']);
        self::assertFalse($items['schema_delivery_method']);
        self::assertTrue($items['schema_customer_user_id']);
    }

    private function fillCompletePolicy(bool $approve = false): void
    {
        $this->actingAs($this->owner)->put('/admin/settings/public-storefront', [
            'terms_url' => 'https://cms.ald1n.com/uslovi-prodaje',
            'privacy_url' => 'https://cms.ald1n.com/politika-privatnosti',
            'shipping_fee_rsd' => '0',
            'shipping_description' => 'Sinteticka dostava za test.',
            'payment_methods' => ['cash_on_delivery'],
            'delivery_methods' => ['courier', 'own_transport', 'customer_pickup'],
            'support_email' => 'podrska@example.test',
            'bank_account_id' => '',
        ] + ($approve ? ['merchant_approval' => '1'] : []))->assertRedirect()->assertSessionHasNoErrors();
    }

    private function approveCompletePolicy(): void
    {
        $this->fillCompletePolicy(true);
        self::assertTrue(app(PublicMerchantPolicyService::class)->review()['approved']);
    }

    private function user(string $name, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => $name, 'email' => $name.'@example.test',
            'first_name' => 'Test', 'password_hash' => Hash::make('SyntheticOnly123!'),
            'status' => 'active',
        ]);
    }
}

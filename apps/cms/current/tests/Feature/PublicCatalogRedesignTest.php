<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Brand,Category,Product,ProductImage,Role,User};
use App\Services\SettingsService;
use App\Support\PublicCatalogPresentation;
use App\Support\SerbianPlural;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Tests\Support\ShopThemeScript;
use Tests\TestCase;

/**
 * Catalog redesign: semantic markup, alt text, schema.org data block,
 * filter chips, empty state, pagination links, price sort and the strict
 * CSP (B1149: only the nonce'd theme script runs). Synthetic rows only; no
 * orders are written.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicCatalogRedesignTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Brand $brand;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url', 'https://cms.ald1n.com');
        config()->set('public_storefront.enabled', true);
        config()->set('public_storefront.checkout_enabled', false);
        app(SettingsService::class)->putMany(['public_storefront_open' => '1', 'eur_rsd_rate' => '117.2'], null);
        $this->owner = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'redesign-owner',
            'email' => 'owner@redesign-synthetic.example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make('Redesign-OnlySyntheticTestSecret!'),
            'status' => 'active',
        ]);
        $this->brand = Brand::query()->create(['name' => 'Lenovo', 'slug' => 'lenovo-synthetic', 'status' => 'active']);
        $this->category = Category::query()->create(['name' => 'Laptopovi', 'slug' => 'laptopovi-synthetic', 'status' => 'active']);
    }

    private function product(string $name, float $price, string $currency = 'RSD', int $stock = 3, bool $image = false, bool $branded = false, bool $categorized = false): Product
    {
        static $n = 0;
        $n++;
        $product = Product::query()->create([
            'sku' => 'RD-SYN-'.$n,
            'slug' => 'rd-syn-'.$n,
            'name' => $name,
            'description' => 'Synthetic redesign row',
            'price_amount' => $price,
            'price_currency' => $currency,
            'stock_quantity' => $stock,
            'status' => 'active',
            'brand_id' => $branded ? $this->brand->id : null,
            'created_by' => $this->owner->id,
        ]);
        if ($categorized) {
            $product->categories()->attach($this->category->id);
        }
        if ($image) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'storage_disk' => 'public',
                'file_path' => 'products/'.$product->id.'/a.png',
                'original_filename' => 'a.png',
                'mime_type' => 'image/png', 'file_size' => 67, 'file_hash' => str_repeat('d', 64),
                'sort_order' => 1, 'is_primary' => true,
            ]);
        }

        return $product;
    }

    /** @return array<string,mixed> */
    private function schemaFrom(string $html): array
    {
        $html = ShopThemeScript::stripFromMarkup($html);
        self::assertSame(1, preg_match_all('~<script\b~i', $html));
        self::assertSame(1, preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m));

        return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_catalog_renders_semantic_shell_seo_and_meaningful_alt_text(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->product('ThinkPad Synthetic '.$i, 100000 + $i, 'RSD', 3, true, true, true);
        }
        $response = $this->get('/katalog')->assertOk();
        $html = (string) $response->getContent();

        ShopThemeScript::strip((string) $response->getContent(), ShopThemeScript::assertStrictPolicy($response->headers->get('Content-Security-Policy')));
        self::assertSame(1, substr_count($html, '<h1'));
        self::assertStringContainsString('<title>Katalog artikala · AP.Computers</title>', $html);
        self::assertStringContainsString('<meta name="description" content="Katalog AP.Computers: 6 artikala sa punim prodajnim cenama i trenutnim stanjem lagera. Kategorije: Laptopovi.">', $html);
        self::assertStringContainsString('<link rel="canonical" href="https://cms.ald1n.com/katalog">', $html);
        self::assertStringContainsString('<meta property="og:url" content="https://cms.ald1n.com/katalog">', $html);
        self::assertStringContainsString('<meta property="og:locale" content="sr_RS">', $html);
        self::assertStringContainsString('<meta name="twitter:card" content="summary">', $html);
        self::assertStringNotContainsString('name="robots"', $html);
        self::assertStringContainsString('<a class="skip" href="#content">', $html);
        self::assertStringContainsString('<main id="content"', $html);
        self::assertStringContainsString('class="shop-back-to-top" href="#vrh" aria-label="Nazad na vrh stranice"', $html);
        self::assertStringContainsString('id="vrh"', $html);
        self::assertStringContainsString('aria-current="page">Artikli</a>', $html);
        self::assertStringNotContainsString('alt=""', $html);
        self::assertStringContainsString('alt="ThinkPad Synthetic 6 – Lenovo"', $html);
        self::assertSame(1, substr_count($html, 'fetchpriority="high"'));
        self::assertSame(4, substr_count($html, 'loading="eager"'));
        self::assertSame(2, substr_count($html, 'loading="lazy"'));
        self::assertSame(6, substr_count($html, 'width="600" height="450"'));
        self::assertStringContainsString('<strong>6</strong> artikala u ponudi', $html);
        self::assertStringContainsString('Prikazano 1–6 od 6 artikala', $html);
        self::assertStringNotContainsString('style=', $html);
        self::assertSame(0, preg_match('~\son[a-z]+\s*=~i', $html), 'no inline event handlers');
    }

    public function test_schema_block_is_valid_json_from_real_rows_and_cannot_break_out(): void
    {
        $this->product('Safe Laptop', 50000);
        $this->product('Evil </script><script>alert(1)</script> & "quote"', 60000);
        $html = (string) $this->withHeaders([
            'Host' => 'poison.redesign.synthetic.invalid',
            'X-Forwarded-Host' => 'forwarded.redesign.synthetic.invalid',
        ])->get('/katalog')->assertOk()->getContent();

        self::assertStringNotContainsString('poison.redesign.synthetic.invalid', $html);
        self::assertStringNotContainsString('forwarded.redesign.synthetic.invalid', $html);
        self::assertSame(1, substr_count(strtolower(ShopThemeScript::stripFromMarkup($html)), '</script>'));
        $schema = $this->schemaFrom($html);
        self::assertSame('https://schema.org', $schema['@context']);
        self::assertSame('CollectionPage', $schema['@type']);
        self::assertSame('https://cms.ald1n.com/katalog#stranica', $schema['@id']);
        self::assertSame('https://cms.ald1n.com/katalog', $schema['url']);
        self::assertSame('sr', $schema['inLanguage']);
        // The unfiltered catalog is one level deep: no single-item breadcrumb.
        self::assertArrayNotHasKey('breadcrumb', $schema);
        $list = $schema['mainEntity'];
        self::assertSame('ItemList', $list['@type']);
        self::assertSame(2, $list['numberOfItems']);
        self::assertSame(1, $list['itemListElement'][0]['position']);
        self::assertSame('Evil </script><script>alert(1)</script> & "quote"', $list['itemListElement'][0]['name']);
        self::assertMatchesRegularExpression('~^https://cms\.ald1n\.com/katalog/rd-syn-\d+$~', $list['itemListElement'][0]['url']);
    }

    public function test_filtered_schema_has_two_level_breadcrumb_and_no_shared_id(): void
    {
        $this->product('Lenovo Crumb Laptop', 70000, 'RSD', 2, false, true, true);
        $html = (string) $this->get('/katalog?category_id='.$this->category->id)->assertOk()->getContent();
        $schema = $this->schemaFrom($html);
        self::assertArrayNotHasKey('@id', $schema);
        self::assertArrayNotHasKey('url', $schema);
        self::assertSame('Laptopovi · Katalog', $schema['name']);
        $crumbs = $schema['breadcrumb']['itemListElement'];
        self::assertSame('BreadcrumbList', $schema['breadcrumb']['@type']);
        self::assertCount(2, $crumbs);
        self::assertSame(['Katalog', 'https://cms.ald1n.com/katalog'], [$crumbs[0]['name'], $crumbs[0]['item']]);
        self::assertSame('Laptopovi', $crumbs[1]['name']);
        self::assertArrayNotHasKey('item', $crumbs[1]);
        self::assertSame(1, $schema['mainEntity']['numberOfItems']);
        // Category views stay indexable; only free-text search is noindex.
        self::assertStringNotContainsString('name="robots"', $html);

        // A stock or sort filter has no breadcrumb level of its own.
        $stock = $this->schemaFrom((string) $this->get('/katalog?stock=available')->assertOk()->getContent());
        self::assertArrayNotHasKey('breadcrumb', $stock);
        self::assertArrayNotHasKey('@id', $stock);
    }

    public function test_search_results_are_noindex_follow_without_canonical(): void
    {
        $this->product('ThinkPad Search Hit', 70000);
        $html = (string) $this->get('/katalog?q=ThinkPad')->assertOk()->getContent();
        self::assertSame(1, substr_count($html, 'name="robots"'));
        self::assertStringContainsString('<meta name="robots" content="noindex,follow">', $html);
        self::assertStringNotContainsString('rel="canonical"', $html);
        self::assertStringNotContainsString('og:url', $html);
        self::assertStringContainsString('ThinkPad Search Hit', $html);
    }

    public function test_invalid_page_values_fall_back_to_page_one_with_plain_canonical(): void
    {
        $this->product('Fallback Laptop', 50000);
        foreach (['abc', '-1', '0'] as $value) {
            $html = (string) $this->get('/katalog?page='.$value)->assertOk()->getContent();
            self::assertStringContainsString('<link rel="canonical" href="https://cms.ald1n.com/katalog">', $html, $value);
            self::assertStringContainsString('Prikazano 1–1 od 1 artikla', $html, $value);
        }
    }

    public function test_empty_catalog_without_filters_is_200(): void
    {
        $html = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringContainsString('Nema rezultata', $html);
        self::assertStringContainsString('<link rel="canonical" href="https://cms.ald1n.com/katalog">', $html);
        self::assertStringNotContainsString('name="robots"', $html);
        self::assertStringNotContainsString('Prikaži sve artikle', $html);
        self::assertStringContainsString('U katalogu trenutno nema artikala.', $html);
        self::assertStringNotContainsString('aktivnih filtera', $html);
        $this->get('/katalog?page=1')->assertOk();
        $this->get('/katalog?page=2')->assertNotFound();
    }

    public function test_page_past_the_last_returns_404(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->product('Range Item '.$i, 10000 + $i);
        }
        $this->get('/katalog?page=2')->assertOk();
        $this->get('/katalog?page=3')->assertNotFound();
        $this->get('/katalog?page=999')->assertNotFound();
        $this->get('/katalog?q=nepostojeci-artikal&page=2')->assertNotFound();
        // Page 1 of an empty result is the normal empty state, not a 404.
        $this->get('/katalog?q=nepostojeci-artikal')->assertOk();
        $this->get('/katalog?page=1')->assertOk();
    }

    public function test_one_result_heading_uses_genitive(): void
    {
        $this->product('Single Laptop', 50000);
        $html = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringContainsString('Prikazano 1–1 od 1 artikla', $html);
        self::assertStringContainsString('<strong>1</strong> artikal u ponudi', $html);
    }

    public function test_invalid_utf8_in_schema_text_is_substituted_not_fatal(): void
    {
        $json = PublicCatalogPresentation::jsonLd(['name' => "Laptop \xB1\xFF bad"]);
        self::assertNotNull($json);
        $name = json_decode($json, true, 512, JSON_THROW_ON_ERROR)['name'];
        self::assertMatchesRegularExpression('~^Laptop (\x{FFFD})+ bad$~u', $name);
        // The page view model survives a row whose name is not valid UTF-8.
        $rows = new \Illuminate\Pagination\LengthAwarePaginator([['slug' => 'bad-row', 'name' => "Bad \xC3\x28 row"]], 1, 20, 1, ['path' => '/katalog']);
        $seo = PublicCatalogPresentation::build($rows, [], ['brands' => [], 'categories' => []], 1);
        self::assertNotNull($seo['schema_json']);
        $decoded = json_decode((string) $seo['schema_json'], true, 512, JSON_THROW_ON_ERROR);
        self::assertStringStartsWith('Bad ', $decoded['mainEntity']['itemListElement'][0]['name']);
        // Any other encoding failure omits the block instead of a 500.
        self::assertNull(PublicCatalogPresentation::jsonLd(['price' => NAN]));
    }

    public function test_base_url_accepts_only_an_https_origin(): void
    {
        $valid = [
            'https://cms.ald1n.com' => 'https://cms.ald1n.com',
            'https://cms.ald1n.com/' => 'https://cms.ald1n.com',
            'https://CMS.ald1n.com' => 'https://cms.ald1n.com',
        ];
        foreach ($valid as $url => $expected) {
            config()->set('app.url', $url);
            self::assertSame($expected, PublicCatalogPresentation::baseUrl(), $url);
        }
        foreach (['http://cms.ald1n.com', 'https://cms.ald1n.com/shop', 'https://cms.ald1n.com/?x=1', 'https://cms.ald1n.com#a',
            'https://user:pw@cms.ald1n.com', 'https://', 'ftp://cms.ald1n.com', 'not a url', '', '//cms.ald1n.com',
            'https://cms.ald1n.com?', 'https://bad_host.example', 'https://cms.ald1n.com:8443', 'https://CMS.ald1n.com:443/'] as $url) {
            config()->set('app.url', $url);
            self::assertNull(PublicCatalogPresentation::baseUrl(), $url);
        }

        config()->set('app.url', 'https://cms.ald1n.com/podfolder');
        $this->product('Safe Laptop', 50000);
        $html = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringNotContainsString('rel="canonical"', $html);
        self::assertStringNotContainsString('<script', ShopThemeScript::stripFromMarkup($html));
        self::assertStringNotContainsString('podfolder', $html);
    }

    public function test_invalid_app_url_omits_absolute_urls_instead_of_using_host(): void
    {
        config()->set('app.url', 'not a url');
        $this->product('Safe Laptop', 50000);
        $html = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringNotContainsString('rel="canonical"', $html);
        self::assertStringNotContainsString('application/ld+json', $html);
        self::assertStringNotContainsString('<script', ShopThemeScript::stripFromMarkup($html));
    }

    public function test_active_filter_chips_reset_link_and_relative_removal_urls(): void
    {
        $this->product('ThinkPad Chip Match', 90000, 'RSD', 2, false, true, true);
        $this->product('Other Item', 80000);
        $html = (string) $this->get('/katalog?q=ThinkPad&brand_id='.$this->brand->id.'&stock=available&sort=price_desc')
            ->assertOk()->getContent();

        self::assertStringContainsString('ThinkPad Chip Match', $html);
        self::assertStringNotContainsString('Other Item', $html);
        self::assertStringContainsString('aria-label="Aktivni filteri"', $html);
        self::assertStringContainsString('Pretraga: „ThinkPad“', $html);
        self::assertStringContainsString('Brend: Lenovo', $html);
        self::assertStringContainsString('Samo na lageru', $html);
        self::assertStringContainsString('Sortiranje: Cena: od najviše', $html);
        self::assertStringContainsString('href="/katalog?brand_id='.$this->brand->id.'&amp;stock=available&amp;sort=price_desc"', $html);
        self::assertStringContainsString('<a class="shop-reset" href="/katalog">Poništi filtere</a>', $html);
        self::assertStringContainsString('<option value="price_desc" selected>', $html);
        // A search view is noindex and carries no canonical.
        self::assertStringNotContainsString('rel="canonical"', $html);
        self::assertStringNotContainsString('https://cms.ald1n.com/katalog?', $html);
        // Filtered views without a search term consolidate on the plain catalog canonical.
        $brandOnly = (string) $this->get('/katalog?brand_id='.$this->brand->id.'&sort=price_desc')->assertOk()->getContent();
        self::assertStringContainsString('<link rel="canonical" href="https://cms.ald1n.com/katalog">', $brandOnly);
        self::assertStringNotContainsString('https://cms.ald1n.com/katalog?', $brandOnly);
        self::assertStringContainsString('<title>Pretraga „ThinkPad“ · Katalog · ', $html);
    }

    public function test_empty_state_offers_a_way_back(): void
    {
        $this->product('Only Laptop', 50000);
        $html = (string) $this->get('/katalog?q=nepostojeci-artikal')->assertOk()->getContent();
        self::assertStringContainsString('Nema artikala koji odgovaraju filterima.', $html);
        self::assertStringContainsString('Nema rezultata', $html);
        self::assertStringContainsString('<a class="shop-button" href="/katalog">Prikaži sve artikle</a>', $html);
        self::assertStringNotContainsString('class="shop-products"', $html);
        self::assertStringNotContainsString('aria-label="Stranice kataloga"', $html);
    }

    public function test_out_of_stock_card_has_text_badge_and_no_add_form(): void
    {
        $product = $this->product('Sold Out Laptop', 50000, 'RSD', 0);
        $html = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringContainsString('Trenutno nedostupno', $html);
        self::assertStringNotContainsString('action="/korpa/dodaj/'.$product->id.'"', $html);
        self::assertStringContainsString('Detalji<span class="sr-only"> o artiklu Sold Out Laptop</span>', $html);
        self::assertStringContainsString('Fotografija nije dostupna', $html);
    }

    public function test_eur_card_shows_original_price_and_rsd_equivalent(): void
    {
        $this->product('Euro Laptop', 199, 'EUR');
        $html = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringContainsString('<span class="shop-price-amount">199,00</span> <span class="shop-price-currency">EUR</span>', $html);
        self::assertStringContainsString('u dinarima 23.322,80 RSD', $html);
        self::assertStringContainsString('Dodaj u korpu<span class="sr-only">: Euro Laptop</span>', $html);
    }

    public function test_pagination_renders_accessible_nav_and_rel_links(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->product('Paged Item '.$i, 10000 + $i);
        }
        $first = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringContainsString('<nav class="shop-pagination" aria-label="Stranice kataloga">', $first);
        self::assertStringContainsString('aria-current="page"><span class="sr-only">Strana </span>1</span>', $first);
        self::assertStringContainsString('<link rel="next" href="/katalog?page=2">', $first);
        self::assertStringNotContainsString('rel="prev"', $first);
        self::assertStringContainsString('href="/katalog?page=2" rel="next"', $first);
        self::assertStringContainsString('Strana 1 od 2', $first);

        $second = (string) $this->get('/katalog?page=2')->assertOk()->getContent();
        self::assertStringContainsString('<link rel="prev" href="/katalog?page=1">', $second);
        self::assertStringNotContainsString('<link rel="next"', $second);
        self::assertStringContainsString('<link rel="canonical" href="https://cms.ald1n.com/katalog?page=2">', $second);
        self::assertStringContainsString('<title>Katalog artikala · strana 2 · ', $second);
        self::assertStringContainsString('Prikazano 21–25 od 25 artikala', $second);
        $schema = $this->schemaFrom($second);
        self::assertSame('https://cms.ald1n.com/katalog?page=2#stranica', $schema['@id']);
        self::assertSame(21, $schema['mainEntity']['itemListElement'][0]['position']);
        self::assertSame(25, $schema['mainEntity']['numberOfItems']);
    }

    public function test_price_sort_compares_rsd_equivalent_across_currencies(): void
    {
        $this->product('Item A RSD 10000', 10000, 'RSD');
        $this->product('Item B EUR 100', 100, 'EUR');      // 11.720 RSD
        $this->product('Item C RSD 12000', 12000, 'RSD');
        $this->product('Item D EUR 50', 50, 'EUR');        // 5.860 RSD

        $asc = (string) $this->get('/katalog?sort=price_asc')->assertOk()->getContent();
        $this->assertOrder($asc, ['Item D EUR 50', 'Item A RSD 10000', 'Item B EUR 100', 'Item C RSD 12000']);
        $desc = (string) $this->get('/katalog?sort=price_desc')->assertOk()->getContent();
        $this->assertOrder($desc, ['Item C RSD 12000', 'Item B EUR 100', 'Item A RSD 10000', 'Item D EUR 50']);

        // Without an exchange rate EUR rows have no RSD price: they go last.
        app(SettingsService::class)->putMany(['eur_rsd_rate' => null], null);
        $noRate = (string) $this->get('/katalog?sort=price_asc')->assertOk()->getContent();
        self::assertLessThan(strpos($noRate, 'Item B EUR 100'), strpos($noRate, 'Item C RSD 12000'));
        self::assertLessThan(strpos($noRate, 'Item D EUR 50'), strpos($noRate, 'Item C RSD 12000'));
        self::assertLessThan(strpos($noRate, 'Item C RSD 12000'), strpos($noRate, 'Item A RSD 10000'));
    }

    public function test_unknown_sort_is_still_rejected(): void
    {
        $this->getJson('/katalog?sort=price_amount')->assertUnprocessable()->assertJsonValidationErrors('sort');
        $this->getJson('/katalog?sort=price_asc;drop')->assertUnprocessable();
    }

    public function test_closed_storefront_still_returns_404(): void
    {
        config()->set('public_storefront.enabled', false);
        $this->get('/katalog')->assertNotFound();
    }

    public function test_serbian_count_agreement(): void
    {
        $cases = [0 => 'artikala', 1 => 'artikal', 2 => 'artikla', 4 => 'artikla', 5 => 'artikala', 11 => 'artikala',
            12 => 'artikala', 14 => 'artikala', 21 => 'artikal', 22 => 'artikla', 25 => 'artikala', 34 => 'artikla',
            101 => 'artikal', 111 => 'artikala', 112 => 'artikala', 122 => 'artikla'];
        foreach ($cases as $count => $word) {
            self::assertSame($count.' '.$word, SerbianPlural::articles($count), (string) $count);
        }
        // Genitive after "od" (Prikazano 1–20 od N ...).
        $genitive = [1 => 'artikla', 2 => 'artikla', 3 => 'artikla', 4 => 'artikla', 5 => 'artikala', 11 => 'artikala',
            12 => 'artikala', 13 => 'artikala', 14 => 'artikala', 21 => 'artikla', 22 => 'artikla', 25 => 'artikala',
            101 => 'artikla', 111 => 'artikala', 112 => 'artikala'];
        foreach ($genitive as $count => $word) {
            self::assertSame($count.' '.$word, SerbianPlural::articlesGenitive($count), 'genitive '.$count);
        }
    }

    /** @param list<string> $names */
    private function assertOrder(string $html, array $names): void
    {
        $body = substr($html, (int) strpos($html, 'class="shop-products"'));
        $positions = array_map(static fn (string $name): int|false => strpos($body, $name), $names);
        foreach ($positions as $i => $position) {
            self::assertNotFalse($position, $names[$i]);
        }
        $sorted = $positions;
        sort($sorted);
        self::assertSame($sorted, $positions, implode(' < ', $names));
    }
}

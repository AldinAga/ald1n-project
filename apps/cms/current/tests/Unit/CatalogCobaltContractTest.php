<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Kobalt G2: the internal catalog pages, the cart control, the specification filters and the field operations
 * list use the a1-* components. Display only: every hook the scripts and the controllers rely on stays.
 */
final class CatalogCobaltContractTest extends TestCase
{
    private const OLD_CLASSES = 'panel|button|button-[a-z]+|form-section|stat-card|check-card|muted|admin-table|admin-table-wrap|status-badge|field-grid|page-heading|filter-panel|eyebrow|count-pill|empty-state|alert|alert-[a-z]+|table-wrap|form-grid|back-link|tags|pagination-wrap|inline-form|product-card|product-grid|description|alpha-note';

    private const VIEWS = [
        'catalog/index',
        'catalog/show',
        'partials/cart-add-control',
        'partials/correlated-specification-filters',
        'admin/field-operations/index',
    ];

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function view(string $name): string
    {
        return (string) file_get_contents(self::root().'/resources/views/'.$name.'.blade.php');
    }

    /** @return array<string, array{0: string}> */
    public static function views(): array
    {
        return array_combine(self::VIEWS, array_map(static fn (string $view): array => [$view], self::VIEWS));
    }

    #[DataProvider('views')]
    public function test_view_has_no_inline_style_and_no_hard_coded_colour(string $name): void
    {
        $view = self::view($name);

        self::assertStringNotContainsString('<style', $view);
        self::assertSame(0, preg_match('/\sstyle="/', $view), $name.' has an inline style attribute');
        self::assertSame(0, preg_match('/(?<![&\w])#(?:[0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b(?![;\w-])/', $view), $name.' has a hex colour');
        self::assertSame(0, preg_match('/rgba?\(/', $view), $name.' has an rgb colour');
    }

    #[DataProvider('views')]
    public function test_view_no_longer_uses_the_legacy_presentation_classes(string $name): void
    {
        $view = self::view($name);
        preg_match_all('/class="([^"]*)"/', $view, $matches);
        $legacy = [];
        foreach ($matches[1] as $value) {
            foreach (preg_split('/\s+/', trim($value)) ?: [] as $token) {
                if ($token !== '' && preg_match('/^('.self::OLD_CLASSES.')$/', $token) === 1) {
                    $legacy[$token] = true;
                }
            }
        }

        self::assertSame([], array_keys($legacy), $name.' still uses legacy classes');
    }

    public function test_catalog_index_keeps_its_hooks_search_and_pagination(): void
    {
        $view = self::view('catalog/index');

        foreach ([
            'data-unified-catalog-ready="1"', 'data-catalog-management-actions', 'data-catalog-filter-form',
            'data-correlated-spec-filter-form', 'name="q"', 'name="per_page"', 'data-catalog-per-page',
            'data-catalog-filter-open', 'data-catalog-filter-drawer', 'data-catalog-filter-backdrop',
            'data-unified-product-select', 'data-unified-catalog-bulk', 'data-display-money', "@method('PATCH')",
            'data-product-status-toggle', "@include('partials.cart-add-control'", "@include('partials.correlated-specification-filters'",
        ] as $hook) {
            self::assertStringContainsString($hook, $view, $hook);
        }
        self::assertStringContainsString('[data-catalog-grid]', $view);
        self::assertSame(substr_count($view, '@csrf'), 1);
    }

    public function test_catalog_show_keeps_gallery_direct_sale_and_cart_hooks(): void
    {
        $view = self::view('catalog/show');

        foreach ([
            'data-product-gallery', 'data-product-lightbox', 'data-gallery-open', 'data-product-description-copy',
            'data-direct-sale-confirm=', 'data-direct-sale-payment-method', 'data-direct-sale-deferred-panel',
            'data-direct-sale-installments', 'data-direct-sale-installment-total', 'name="buyer_name"',
            'name="sale_price_rsd"', 'name="idempotency_key"', '@csrf', "@include('partials.cart-add-control'",
            'id="direct-sale-title"',
        ] as $hook) {
            self::assertStringContainsString($hook, $view, $hook);
        }
    }

    public function test_cart_control_keeps_the_script_hooks_and_the_form_target(): void
    {
        $view = self::view('partials/cart-add-control');

        foreach ([
            'data-cart-add-control', 'data-cart-quantity-toggle', 'data-cart-quantity-form', 'data-cart-quantity-minus',
            'data-cart-quantity-plus', 'data-cart-quantity-input', "route('cart.items.store', \$product)", '@csrf',
            'name="quantity"', "@can('orders.create')",
        ] as $hook) {
            self::assertStringContainsString($hook, $view, $hook);
        }
    }

    public function test_module_stylesheet_uses_tokens_only(): void
    {
        $css = (string) file_get_contents(self::root().'/public/assets/css/ald1n-admin-catalog.css');

        self::assertNotSame('', $css);
        self::assertSame(0, preg_match('/#[0-9a-fA-F]{3,8}\b/', $css), 'hex colour in the catalog stylesheet');
        self::assertSame(0, preg_match('/rgba?\(|hsla?\(/', $css), 'rgb colour in the catalog stylesheet');
        self::assertStringContainsString('prefers-reduced-motion', $css);
        self::assertStringContainsString('forced-colors', $css);
    }

    /** G7 PR B: replaces bin/catalog-grid-responsive-smoke.php, which asserted the old 4-3-2-1 grid in ald1n-ui-v2.css. */
    public function test_catalog_grid_is_auto_fill_260_and_one_column_on_phones(): void
    {
        $css = (string) file_get_contents(self::root().'/public/assets/css/ald1n-admin-catalog.css');

        self::assertStringContainsString('.a1-cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,260px),1fr))', $css);
        self::assertMatchesRegularExpression('/@media \(max-width:520px\)\{[^@]*\.a1-cat-grid\{grid-template-columns:minmax\(0,1fr\)\}/', $css);
        self::assertStringContainsString('.a1-cat-grid>.a1-empty{grid-column:1/-1}', $css);
    }

    /** A tall product photo must never stretch the 4:3 image box and cover the title/SKU rows of the card below it. */
    public function test_catalog_card_image_is_clipped_to_its_aspect_box(): void
    {
        $css = (string) file_get_contents(self::root().'/public/assets/css/ald1n-admin-catalog.css');

        self::assertMatchesRegularExpression('/\.a1-cat-card__media\{[^}]*aspect-ratio:4\/3[^}]*min-height:0[^}]*overflow:hidden/', $css);
        self::assertMatchesRegularExpression('/\.a1-cat-card__image\{[^}]*position:absolute[^}]*inset:0[^}]*overflow:hidden/', $css);
        self::assertMatchesRegularExpression('/\.a1-cat-card__image img\{[^}]*max-height:100%[^}]*object-fit:contain/', $css);
    }

    /** A glass card (backdrop-filter) traps position:fixed descendants; the lightbox must be mounted on <body> when it opens. */
    public function test_lightbox_is_mounted_on_body_when_it_opens(): void
    {
        $view = (string) file_get_contents(self::root().'/resources/views/catalog/show.blade.php');

        self::assertStringContainsString('if (lightbox.parentElement !== document.body) document.body.appendChild(lightbox);', $view);
        self::assertStringContainsString("lightbox.querySelector('[data-lightbox-close]:not([tabindex=\"-1\"])')", $view);
    }

    public function test_lightbox_is_fullscreen_on_phones_and_never_taller_than_the_viewport(): void
    {
        $css = (string) file_get_contents(self::root().'/public/assets/css/ald1n-admin-catalog.css');

        self::assertStringContainsString('.a1-cat-lb{position:fixed;inset:0;', $css);
        self::assertStringContainsString('height:min(940px,calc(100dvh - 32px))', $css);
        self::assertMatchesRegularExpression('/@media \(max-width:820px\)\{[^@]*\.a1-cat-lb__dialog\{width:100%;height:100dvh;border:0;border-radius:0\}/', $css);
    }
}

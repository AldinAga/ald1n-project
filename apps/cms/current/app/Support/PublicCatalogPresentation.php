<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * View model for the public catalog page: titles, active-filter chips,
 * canonical/OG URLs and schema.org data built only from real catalog rows.
 *
 * Absolute URLs come exclusively from config('app.url'), never from the
 * request Host or forwarded headers. Navigation links stay relative.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicCatalogPresentation
{
    public const SITE_NAME = 'AP.Computers';

    public const SORT_LABELS = [
        'newest' => 'Najnovije',
        'name' => 'Naziv (A–Ž)',
        'price_asc' => 'Cena: od najniže',
        'price_desc' => 'Cena: od najviše',
    ];

    /**
     * @param array<string,mixed> $filters validated request filters
     * @param array{brands:iterable<object>,categories:iterable<object>} $lists
     * @return array<string,mixed>
     */
    public static function build(LengthAwarePaginator $page, array $filters, array $lists, int $catalogTotal): array
    {
        $catalogPath = route('public-shop.catalog', [], false);
        $base = self::baseUrl();
        $active = self::activeFilters($filters);
        $brandName = self::nameFor($lists['brands'], $active['brand_id'] ?? null);
        $categoryName = self::nameFor($lists['categories'], $active['category_id'] ?? null);
        $current = $page->currentPage();

        $chips = [];
        $remove = static function (string $key) use ($active, $catalogPath): string {
            $rest = $active;
            unset($rest[$key]);

            return $rest === [] ? $catalogPath : $catalogPath.'?'.http_build_query($rest);
        };
        if (isset($active['q'])) {
            $chips[] = ['label' => 'Pretraga: „'.$active['q'].'“', 'url' => $remove('q')];
        }
        if ($brandName !== null) {
            $chips[] = ['label' => 'Brend: '.$brandName, 'url' => $remove('brand_id')];
        }
        if ($categoryName !== null) {
            $chips[] = ['label' => 'Kategorija: '.$categoryName, 'url' => $remove('category_id')];
        }
        if (isset($active['stock'])) {
            $chips[] = ['label' => 'Samo na lageru', 'url' => $remove('stock')];
        }
        if (isset($active['sort']) && $active['sort'] !== 'newest') {
            $chips[] = ['label' => 'Sortiranje: '.(self::SORT_LABELS[$active['sort']] ?? $active['sort']), 'url' => $remove('sort')];
        }

        if (isset($active['q'])) {
            $title = 'Pretraga „'.$active['q'].'“ · Katalog';
        } elseif ($categoryName !== null && $brandName !== null) {
            $title = $categoryName.' · '.$brandName.' · Katalog';
        } elseif ($categoryName !== null) {
            $title = $categoryName.' · Katalog';
        } elseif ($brandName !== null) {
            $title = $brandName.' · Katalog';
        } else {
            $title = 'Katalog artikala';
        }
        if ($current > 1) {
            $title .= ' · strana '.$current;
        }

        $categoryNames = [];
        foreach ($lists['categories'] as $category) {
            $categoryNames[] = (string) $category->name;
            if (count($categoryNames) === 3) {
                break;
            }
        }
        $description = 'Katalog '.self::SITE_NAME.': '.SerbianPlural::articles($catalogTotal)
            .' sa punim prodajnim cenama i trenutnim stanjem lagera.';
        if ($categoryNames !== []) {
            $description .= ' Kategorije: '.implode(', ', $categoryNames).'.';
        }

        $canonical = null;
        if ($base !== null) {
            // Filtered and sorted views consolidate on the unfiltered catalog;
            // plain pagination keeps its own page URL.
            $canonical = $base.$catalogPath.($active === [] && $current > 1 ? '?page='.$current : '');
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'prev' => $page->previousPageUrl(),
            'next' => $page->nextPageUrl(),
            'chips' => $chips,
            'has_filters' => $active !== [],
            'catalog_path' => $catalogPath,
            'catalog_total' => $catalogTotal,
            'brand_count' => self::countOf($lists['brands']),
            'category_count' => self::countOf($lists['categories']),
            'schema' => $base === null ? null : self::schema($page, $base, $catalogPath, $canonical, $title, $description, $categoryName ?? $brandName),
        ];
    }

    public static function imageAlt(string $name, string $brand): string
    {
        $name = trim($name);
        $brand = trim($brand);
        if ($brand === '' || mb_stripos($name, $brand) !== false) {
            return $name;
        }

        return $name.' – '.$brand;
    }

    /** JSON for a non-executable application/ld+json data block. */
    public static function jsonLd(array $schema): string
    {
        return (string) json_encode($schema,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }

    public static function baseUrl(): ?string
    {
        $url = trim((string) config('app.url'));
        $parts = parse_url($url);
        if (!is_array($parts) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || ($parts['host'] ?? '') === ''
            || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user'])) {
            return null;
        }

        return rtrim($url, '/');
    }

    /** @param array<string,mixed> $filters @return array<string,string|int> */
    private static function activeFilters(array $filters): array
    {
        $active = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $active['q'] = mb_substr($q, 0, 80);
        }
        foreach (['brand_id', 'category_id'] as $key) {
            if ((int) ($filters[$key] ?? 0) > 0) {
                $active[$key] = (int) $filters[$key];
            }
        }
        if (($filters['stock'] ?? null) === 'available') {
            $active['stock'] = 'available';
        }
        $sort = (string) ($filters['sort'] ?? '');
        if ($sort !== '' && $sort !== 'newest' && isset(self::SORT_LABELS[$sort])) {
            $active['sort'] = $sort;
        }

        return $active;
    }

    /** @param iterable<object> $rows */
    private static function nameFor(iterable $rows, mixed $id): ?string
    {
        if ($id === null) {
            return null;
        }
        foreach ($rows as $row) {
            if ((int) $row->id === (int) $id) {
                return (string) $row->name;
            }
        }

        return null;
    }

    /** @param iterable<mixed> $rows */
    private static function countOf(iterable $rows): int
    {
        return is_countable($rows) ? count($rows) : 0;
    }

    /** @return array<string,mixed> */
    private static function schema(LengthAwarePaginator $page, string $base, string $catalogPath, ?string $canonical, string $title, string $description, ?string $crumb): array
    {
        $catalogUrl = $base.$catalogPath;
        $pageUrl = $canonical ?? $catalogUrl;
        $items = [];
        $position = max(1, (int) ($page->firstItem() ?? 1));
        foreach ($page->items() as $item) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => $base.route('public-shop.show', ['slug' => $item['slug']], false),
                'name' => (string) $item['name'],
            ];
        }
        $breadcrumbs = [[
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Katalog',
            'item' => $catalogUrl,
        ]];
        if ($crumb !== null) {
            // The last crumb may omit "item"; this keeps filter query URLs out.
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $crumb];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    '@id' => $pageUrl.'#stranica',
                    'url' => $pageUrl,
                    'name' => $title,
                    'description' => $description,
                    'inLanguage' => 'sr',
                    'isPartOf' => ['@type' => 'WebSite', 'name' => self::SITE_NAME, 'url' => $base.'/'],
                    'breadcrumb' => ['@id' => $pageUrl.'#putanja'],
                    'mainEntity' => ['@id' => $pageUrl.'#artikli'],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $pageUrl.'#putanja',
                    'itemListElement' => $breadcrumbs,
                ],
                [
                    '@type' => 'ItemList',
                    '@id' => $pageUrl.'#artikli',
                    'numberOfItems' => $page->total(),
                    'itemListElement' => $items,
                ],
            ],
        ];
    }
}

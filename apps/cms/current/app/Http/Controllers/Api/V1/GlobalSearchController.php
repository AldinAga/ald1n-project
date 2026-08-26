<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GlobalCommandSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// MOBILE_V1_0_GLOBAL_SEARCH_PARITY_BATCH38
final class GlobalSearchController extends Controller
{
    public function search(Request $request, GlobalCommandSearchService $search): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) > 80) {
            $query = mb_substr($query, 0, 80);
        }

        if (mb_strlen($query) < 2) {
            return $this->respond($query, [], []);
        }

        $payload = $search->search($actor, $query, 5);
        $items = [];

        foreach (($payload['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $mobilePath = $this->mobilePath((string) ($item['url'] ?? ''));
            if ($mobilePath === null) {
                continue;
            }

            unset($item['url']);
            $item['mobile_path'] = $mobilePath;
            $items[] = $item;
        }

        $counts = [];
        foreach ($items as $item) {
            $key = trim((string) ($item['group_key'] ?? ''));
            if ($key !== '') {
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        $sections = [];
        foreach (($payload['sections'] ?? []) as $section) {
            if (!is_array($section)) {
                continue;
            }

            $key = trim((string) ($section['key'] ?? ''));
            if ($key === '' || !isset($counts[$key])) {
                continue;
            }

            $section['count'] = $counts[$key];
            $sections[] = $section;
        }

        return $this->respond((string) ($payload['query'] ?? $query), $items, $sections);
    }

    /**
     * Adapt the canonical Web route emitted by GlobalCommandSearchService to an
     * existing Expo Router destination. Search/ranking/permissions stay in the
     * shared Laravel service; this method only translates navigation.
     */
    private function mobilePath(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^/catalog/([^/]+)$#', $path, $matches) === 1) {
            return '/product/'.$matches[1];
        }
        if (preg_match('#^/admin/orders/(\\d+)$#', $path, $matches) === 1) {
            return '/admin/orders/'.$matches[1];
        }
        if (preg_match('#^/orders/(\\d+)$#', $path, $matches) === 1) {
            return '/order/'.$matches[1];
        }
        if (preg_match('#^/admin/warranties/(\\d+)$#', $path, $matches) === 1) {
            return '/admin/warranties/'.$matches[1];
        }
        if (preg_match('#^/warranties/(\\d+)$#', $path, $matches) === 1) {
            return '/warranties/'.$matches[1];
        }
        if (preg_match('#^/admin/after-sales/(\\d+)$#', $path, $matches) === 1) {
            return '/admin/after-sales/'.$matches[1];
        }
        if (preg_match('#^/after-sales/(\\d+)$#', $path, $matches) === 1) {
            return '/after-sales/'.$matches[1];
        }

        if ($path === '/admin/users') {
            $query = [];
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $userQuery = trim((string) ($query['q'] ?? ''));

            return $userQuery === ''
                ? '/admin/users'
                : '/admin/users?q='.rawurlencode(mb_substr($userQuery, 0, 80));
        }

        $static = [
            '/catalog' => '/catalog',
            '/admin/orders' => '/admin/orders',
            '/orders' => '/orders',
            '/admin/warranties' => '/admin/warranties',
            '/warranties' => '/warranties',
            '/admin/after-sales' => '/admin/after-sales',
            '/after-sales' => '/after-sales',
            '/admin/inventory' => '/admin/inventory',
            '/admin/reports' => '/admin/reports',
            '/admin/commissions' => '/admin/commissions',
            '/commissions' => '/commissions',
            '/notifications' => '/notifications',
        ];

        return $static[$path] ?? null;
    }

    /**
     * @param list<array<string,mixed>> $items
     * @param list<array<string,mixed>> $sections
     */
    private function respond(string $query, array $items, array $sections): JsonResponse
    {
        return response()->json([
            'data' => [
                'query' => $query,
                'items' => $items,
                'sections' => $sections,
            ],
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }
}

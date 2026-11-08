<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PublicLegalPagesService;
use App\Services\PublicStorefrontStateService;
use App\Support\PublicLegalTemplates;
use Illuminate\Http\Response;

/**
 * Public legal information pages. They stay available while the storefront
 * is OFF and expose only merchant identity and policy text, never products,
 * prices, carts, orders or buyer data.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicLegalPageController extends Controller
{
    public function index(PublicLegalPagesService $legal, PublicStorefrontStateService $store): Response
    {
        return $this->respond('public-shop.legal.index', [
            'pages' => PublicLegalTemplates::PAGES,
            'current' => null,
        ], $store);
    }

    public function show(string $page, PublicLegalPagesService $legal, PublicStorefrontStateService $store): Response
    {
        abort_unless(array_key_exists($page, PublicLegalTemplates::PAGES), 404);
        $values = $legal->values();
        return $this->respond('public-shop.legal.page', [
            'pages' => PublicLegalTemplates::PAGES,
            'current' => $page,
            'title' => PublicLegalTemplates::PAGES[$page]['title'],
            'body' => $page === 'trader' ? null : $legal->renderBody($page, $values),
            'traderRows' => $legal->traderRows($values),
            'values' => $values,
        ], $store);
    }

    /** @param array<string,mixed> $data */
    private function respond(string $view, array $data, PublicStorefrontStateService $store): Response
    {
        $open = $store->isOpen();
        $response = response()->view($view, $data + ['hideShopNav' => !$open]);
        if (!$open) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        return $response;
    }
}

#!/usr/bin/env python3
"""B1046: stage only M13 public GET catalog from exact PR111 into disposable main runner.

NO changes to main repo, CI source changes are to ephemeral runner copies only.
No checkout/cart/registration endpoints, no email, no live DB, no release operations.
"""
from __future__ import annotations

import pathlib
import re
import subprocess
import sys

SOURCE_SHA = '3acc37e90332b0d8fde659503c5cc1a8b5fcc6aa'
MAIN_MODEL_SHA = 'b67d8d47b6b0ad49e44f8b41b129cdbba3917b75'
FILES = {
    'app/Http/Controllers/PublicShopController.php': '518426f1f31ceb1efa97abd481410774d94f2783',
    'app/Services/PublicRetailCatalogService.php': '185ae0787c6c4d9e141c9198db0197f5cf3cb043',
    'app/Services/PublicStorefrontStateService.php': '2e996ae427433632c73557cbf135a8dcecd14ad1',
    'app/Services/PublicMerchantPolicyService.php': '7d2967479c734fe3ffc1e16acb03872a34288209',
    'app/Support/PublicRetailOptions.php': '445d8b22eafa1dcbef618300a00283f0883340e7',
    'config/public_storefront.php': '48ab46978c32e6884be4ca7dd1c239470f954a06',
    'resources/views/public-shop/layout.blade.php': '771aab18122190405aa36a500c53f278bfb7b33a',
    'resources/views/public-shop/catalog.blade.php': '248af6fa0af08a14a567e2b0aced682f0207ed72',
    'resources/views/public-shop/show.blade.php': '17eb7772a1197056a74fa465b7b9ac2075e7e2a5',
    'public/assets/css/ald1n-public-storefront.css': '2d5443c93a57ad2f91e8e76a592350178afac31e',
}


def fail(reason: str) -> None:
    raise SystemExit('HOLD_B1046_' + reason)


def git(cwd: pathlib.Path, *args: str) -> str:
    result = subprocess.run(['git', '-C', str(cwd), *args], capture_output=True, text=True, check=True)
    return result.stdout.strip()


def main() -> None:
    if len(sys.argv) != 3:
        fail('EXPECTED_MAIN_SOURCE_DIR_ARGS')
    main_repo, source_repo = (pathlib.Path(s).resolve(strict=True) for s in sys.argv[1:])
    if main_repo == source_repo or not (main_repo / '.git').exists() or not (source_repo / '.git').exists():
        fail('INVALID_ISOLATED_CLONES')
    if git(source_repo, 'rev-parse', 'HEAD') != SOURCE_SHA:
        fail('CANDIDATE_COMMIT_DRIFT')
    cms = pathlib.Path('apps/cms/current')
    model = cms / 'app/Models/Product.php'
    if git(main_repo, 'hash-object', str(model)) != MAIN_MODEL_SHA:
        fail('CURRENT_MAIN_PRODUCT_SCHEMA_DRIFT')
    if git(source_repo, 'hash-object', str(model)) != MAIN_MODEL_SHA:
        fail('CANDIDATE_PRODUCT_SCHEMA_DRIFT')
    existing_routes = (main_repo / cms / 'routes/web.php').read_text(encoding='utf-8')
    if '/katalog' in existing_routes or 'public-shop.' in existing_routes:
        fail('MAIN_ALREADY_HAS_STOREFRONT_ROUTES')
    for relative, sha in FILES.items():
        path = cms / relative
        if (main_repo / path).exists() or (main_repo / path).is_symlink():
            fail('TARGET_ALREADY_EXISTS_' + relative.replace('/', '_'))
        source = source_repo / path
        if source.is_symlink() or not source.is_file() or git(source_repo, 'hash-object', str(path)) != sha:
            fail('ORIGINAL_BLOB_DRIFT_' + relative.replace('/', '_'))
        dest = main_repo / path
        dest.parent.mkdir(parents=True, exist_ok=True)
        dest.write_bytes(source.read_bytes())

    views = main_repo / cms / 'resources/views/public-shop'
    layout = views / 'layout.blade.php'
    original = layout.read_text(encoding='utf-8')
    remove_links = [
        '<a href="{{ route(\'public-shop.cart\', [], false) }}">Korpa</a>',
        '<a href="{{ route(\'public-shop.register\', [], false) }}">Registracija</a>',
    ]
    for s in remove_links:
        if original.count(s) != 1:
            fail('LAYOUT_EXPECTED_LINK_MISSING')
        original = original.replace(s, '')
    layout.write_text(original, encoding='utf-8')

    catalog = views / 'catalog.blade.php'
    c = catalog.read_text(encoding='utf-8')
    c, n = re.subn(r"@if\(\$p\['can_buy'\]\)<form\b.*?</form>@endif", '', c, flags=re.S)
    if n != 1 or 'public-shop.cart' in c or 'Dodaj u korpu' in c:
        fail('CATALOG_CHECKOUT_REMOVAL')
    catalog.write_text(c, encoding='utf-8')

    detail = views / 'show.blade.php'
    d = detail.read_text(encoding='utf-8')
    d, n = re.subn(r"@if\(\$product\['can_buy'\]\).*?@endif", '<p class="availability">Katalog: samo pregled artikla.</p>', d, flags=re.S)
    if n != 1 or 'public-shop.cart' in d or 'Dodaj u korpu' in d:
        fail('PRODUCT_CHECKOUT_REMOVAL')
    detail.write_text(d, encoding='utf-8')

    # B1046 real HTTP RED: Symfony BinaryFileResponse on the original
    # source emitted Cache-Control: max-age=0, no-store, public.
    # Explicitly make the response private after file() construction; this
    # is a source security fix, NOT a workaround or a relaxed test assertion.
    controller = main_repo / cms / 'app/Http/Controllers/PublicShopController.php'
    b = controller.read_text(encoding='utf-8')
    a1 = "        return response()->file($full, ["
    a2 = "            'X-Content-Type-Options'=>'nosniff',\n        ]);\n    }\n\n    public function cart("
    if b.count(a1) != 1 or b.count(a2) != 1:
        fail('EXACT_BINARY_RESPONSE_SOURCE_ANCHOR_DRIFT')
    b = b.replace(a1, "        $response = response()->file($full, [")
    b = b.replace(a2, "            'X-Content-Type-Options'=>'nosniff',\n        ]);\n"
                  "        $response->setPrivate();\n"
                  "        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');\n"
                  "        return $response;\n    }\n\n    public function cart(")
    controller.write_text(b, encoding='utf-8')

    routes = main_repo / cms / 'routes/web.php'
    with routes.open('a', encoding='utf-8') as fp:
        fp.write("""
// B1046 disposable M13 read-only catalog staging: source is NOT deployed.
// This exact three-GET-only route block is created in an ephemeral CI copy.
\\Illuminate\\Support\\Facades\\Route::name('public-shop.')->group(function (): void {
    \\Illuminate\\Support\\Facades\\Route::get('/katalog',
        [\\App\\Http\\Controllers\\PublicShopController::class, 'catalog'])
        ->middleware('throttle:120,1')->name('catalog');
    \\Illuminate\\Support\\Facades\\Route::get('/katalog/slika/{image}',
        [\\App\\Http\\Controllers\\PublicShopController::class, 'media'])
        ->whereNumber('image')->middleware('throttle:300,1')->name('media');
    \\Illuminate\\Support\\Facades\\Route::get('/katalog/{slug}',
        [\\App\\Http\\Controllers\\PublicShopController::class, 'show'])
        ->middleware('throttle:120,1')->name('show');
});
""")

    whole_routes = routes.read_text(encoding='utf-8')
    staged_views = [p.read_text(encoding='utf-8') for p in [layout, catalog, detail]]
    forbidden = ['public-shop.checkout', 'public-shop.cart', 'public-shop.register', 'Dodaj u korpu']
    if any(any(s in v for s in forbidden) for v in staged_views):
        fail('FORBIDDEN_BUY_LINK_IN_READONLY_VIEW')
    if any(s in whole_routes.split('// B1046 disposable M13 read-only catalog staging:')[-1]
           for s in ['Route::post(', 'Route::put(', 'Route::patch(', 'Route::delete(', 'checkout', 'cart']):
        fail('UNEXPECTED_WRITE_OR_CHECKOUT_ROUTE')
    if 'PUBLIC_STOREFRONT_ENABLED' not in (main_repo / cms / 'config/public_storefront.php').read_text(encoding='utf-8'):
        fail('EMERGENCY_OFF_FLAG_MISSING')
    print('B1046_EXACT_PR111_STOREFRONT_M13_GET_ONLY_STAGED=PASS')
    print('B1046_MAIN_PRODUCT_EQUIVALENCE=PASS')
    print('B1046_PUBLIC_CART_CHECKOUT_REGISTRATION_ROUTES=ABSENT')
    print('B1046_PRODUCTION_AND_GITHUB_MAIN_CMS_SOURCE=UNCHANGED')


if __name__ == '__main__':
    main()

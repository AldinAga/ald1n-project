#!/usr/bin/env python3
"""B1047: harden B1046 disposable M13 catalog into an exact applyable source-only diff.

Intentionally operates ONLY on a disposable GitHub Actions checkout, never on
CloudLinux, runtime main or customer data. B1046 pins all ten incoming blobs.
"""
from __future__ import annotations

import pathlib
import subprocess
import sys

EXPECTED_MAIN_PROVIDER_SHA = 'c8d3e9b6b48abefb36d5aa3e45edfef5a3e36c8e'
EXPECTED_FILES = {
    'app/Http/Controllers/PublicShopController.php',
    'app/Providers/AppServiceProvider.php',
    'app/Services/PublicRetailCatalogService.php',
    'app/Services/PublicStorefrontStateService.php',
    'app/Services/PublicMerchantPolicyService.php',
    'app/Support/PublicRetailOptions.php',
    'config/public_storefront.php',
    'public/assets/css/ald1n-public-storefront.css',
    'resources/views/public-shop/catalog.blade.php',
    'resources/views/public-shop/layout.blade.php',
    'resources/views/public-shop/show.blade.php',
    'routes/web.php',
}


def hold(message: str) -> None:
    raise SystemExit('HOLD_B1047_' + message)


def git(root: pathlib.Path, *args: str) -> str:
    return subprocess.run(['git', '-C', str(root), *args], check=True,
                          text=True, stdout=subprocess.PIPE).stdout.strip()


def replace_exact(source: str, old: str, new: str, label: str, count: int = 1) -> str:
    if source.count(old) != count:
        hold(label + '_ANCHOR_DRIFT')
    return source.replace(old, new)


def main() -> None:
    if len(sys.argv) != 3:
        hold('USAGE_EXPECTED_ROOT_AND_PATCH_PATH')
    root = pathlib.Path(sys.argv[1]).resolve(strict=True)
    out = pathlib.Path(sys.argv[2]).resolve(strict=False)
    if not (root / '.git').exists() or out.is_relative_to(root):
        hold('DISPOSABLE_SOURCE_AND_ARTIFACT_BOUNDARY')
    cms = root / 'apps/cms/current'
    provider = cms / 'app/Providers/AppServiceProvider.php'
    if git(root, 'hash-object', str(provider.relative_to(root))) != EXPECTED_MAIN_PROVIDER_SHA:
        hold('CURRENT_MAIN_PROVIDER_DRIFT')

    # The original B1046 copy contains unregistered public POST/PATCH/DELETE
    # helper methods. Remove them from this M13-only source, rather than
    # relying merely on lack of routes for safety.
    controller = cms / 'app/Http/Controllers/PublicShopController.php'
    c = controller.read_text(encoding='utf-8')
    c = replace_exact(c, 'use App\\Services\\PublicCartService;\n', '', 'UNUSED_CART_IMPORT')
    c = replace_exact(c, 'use Illuminate\\Http\\RedirectResponse;\n', '', 'UNUSED_REDIRECT_IMPORT')
    marker = '\n    public function cart(Request $request, PublicCartService $cart): View\n'
    if c.count(marker) != 1 or not c.rstrip().endswith('}'):
        hold('M13_CONTROLLER_TRUNCATION_SOURCE')
    c = c.split(marker)[0] + '\n}\n'
    for disallowed in ['function add(', 'function update(', 'function remove(', 'function cart(',
                       'PublicCartService', 'RedirectResponse', 'Mail::', 'order.created']:
        if disallowed in c:
            hold('STAGED_CONTROLLER_CONTAINS_WRITER')
    if c.count('public function ') != 3 or not all('public function '+x+'(' in c for x in ('catalog','show','media')):
        hold('CONTROLLER_NOT_3_GET_ACTIONS')
    controller.write_text(c, encoding='utf-8')

    routes = cms / 'routes/web.php'
    r = routes.read_text(encoding='utf-8')
    if r.count('// B1046 disposable M13 read-only catalog staging:') != 1:
        hold('B1046_ROUTE_BLOCK_DRIFT')
    r = replace_exact(r, "->middleware('throttle:120,1')->name(",
                      "->middleware('throttle:public-shop-browse')->name(",
                      'BROWSE_ROUTE', count=2)
    r = replace_exact(r, "->whereNumber('image')->middleware('throttle:300,1')->name('media');",
                      "->whereNumber('image')->middleware('throttle:public-shop-images')->name('media');",
                      'MEDIA_ROUTE')
    r = replace_exact(r, '// B1046 disposable M13 read-only catalog staging: source is NOT deployed.\n'
                     '// This exact three-GET-only route block is created in an ephemeral CI copy.',
                     '// B1047 M13 source-only integration: public catalog, OFF by default.\n'
                     '// Three GET/HEAD routes only. No shopping/cart/checkout endpoints.',
                     'ROUTE_COMMENT')
    if 'Route::post' in r.split('// B1047 M13 source-only integration:')[-1]:
        hold('PUBLIC_POST_ROUTE_FOUND')
    routes.write_text(r, encoding='utf-8')

    p = provider.read_text(encoding='utf-8')
    anchor = "        RateLimiter::for('admin-write', static function (Request $request): Limit {"
    addition = """        // B1047 public catalog limits are IP-scoped and never affect staff APIs.
        RateLimiter::for('public-shop-browse', static fn (Request $request): Limit =>
            Limit::perMinute(120)->by('public-shop-browse|'.$request->ip()));
        RateLimiter::for('public-shop-images', static fn (Request $request): Limit =>
            Limit::perMinute(300)->by('public-shop-images|'.$request->ip()));

"""
    p = replace_exact(p, anchor, addition + anchor, 'RATE_LIMITER_PROVIDER')
    if p.count("RateLimiter::for('public-shop-browse'") != 1 or p.count("RateLimiter::for('public-shop-images'") != 1:
        hold('DUPLICATE_PUBLIC_THROTTLE')
    provider.write_text(p, encoding='utf-8')

    # New source files are not in the git index until -N, but the patch must
    # include them in full, and the unchanged CMS files must not be included.
    relative = ['apps/cms/current/' + path for path in sorted(EXPECTED_FILES)]
    subprocess.run(['git','-C',str(root),'add','-N','--'] + relative,
                   check=True, stdout=subprocess.PIPE)
    paths = git(root, 'diff', '--name-only', 'HEAD', '--', 'apps/cms/current').splitlines()
    if set(paths) != set(relative) or len(paths) != len(relative):
        hold('SOURCE_PATCH_PATH_CENSUS_' + str(len(paths)))
    diff = subprocess.run(['git','-C',str(root),'diff','--binary','HEAD','--',
                           'apps/cms/current'], check=True,
                          stdout=subprocess.PIPE).stdout
    if not (10000 < len(diff) < 85000) or b'public-shop.cart.add' in diff:
        hold('SOURCE_DIFF_UNSAFE')
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_bytes(diff)
    print('B1047_M13_REAL_SOURCE_PATCH=READY_PATHS_12')
    print('B1047_CONTROLLER=ONLY_CATALOG_DETAIL_MEDIA')
    print('B1047_PUBLIC_THROTTLES=NAMESPACED')
    print('B1047_ACTIVE_MAIN_CMS_RUNTIME=UNCHANGED')


if __name__ == '__main__':
    main()

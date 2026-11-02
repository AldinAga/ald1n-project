#!/usr/bin/env python3
"""Adapt existing B1046/B1047 disposable fixtures for B1048 independent readonly flags.

NO changed assertions or deleted test cases. Fail closed on source drift.
ONLY runs after exact-source staged imports on a scratch GitHub runner.
"""
from pathlib import Path
import sys, hashlib

def replace(s, before, after, count, label):
    if s.count(before) != count:
        raise SystemExit(f'HOLD_B1049_FIXTURE_{label}_DRIFT_{s.count(before)}_NE_{count}')
    return s.replace(before, after)

def modify(path, operations):
    if not path.is_file() or path.is_symlink():
        raise SystemExit('HOLD_B1049_FIXTURE_MISSING_'+str(path))
    s=path.read_text(encoding='utf8')
    for b,a,count,label in operations:
        s=replace(s,b,a,count,label)
    path.write_text(s,encoding='utf8')
    print('B1049_FIXTURE_'+path.name+'_SHA256='+hashlib.sha256(s.encode()).hexdigest())

if len(sys.argv)!=2:raise SystemExit('HOLD_B1049_USAGE')
root=Path(sys.argv[1]).resolve(strict=True)
if not (root/'.git').exists() or (root/'apps/cms/current/.env').exists():
    raise SystemExit('HOLD_B1049_NOT_DISPOSABLE_REPO')
cms=root/'apps/cms/current'
feature=cms/'tests/Feature/B1046M13ReadOnlyCatalogIntegrationTest.php'
seed=cms/'tests/Browser/b1047_seed_catalog.php'
browser=cms/'tests/Browser/b1047_catalog_browser.cjs'
modify(feature,[
("        config()->set('public_storefront.enabled', true);",
 "        config()->set('public_storefront.enabled', false);\n        config()->set('public_storefront.catalog_readonly_enabled', true);",1,'LEGACY_ON'),
("            'public_storefront_open' => '1',",
 "            'public_storefront_open' => '0',\n            'public_catalog_readonly_open' => '1',",1,'DB_OLD_OPEN'),
("app(SettingsService::class)->putMany(['public_storefront_open'=>'0'], null);",
 "app(SettingsService::class)->putMany(['public_catalog_readonly_open'=>'0'], null);",2,'DB_EMERGENCY_OFF'),
])
modify(seed,[
("    || config('public_storefront.enabled') !== true",
 "    || config('public_storefront.enabled') !== false\n    || config('public_storefront.catalog_readonly_enabled') !== true",1,'SEED_FLAG'),
("        'public_storefront_open' => '1',",
 "        'public_storefront_open' => '0',\n        'public_catalog_readonly_open' => '1',",1,'SEED_DB'),
("app(SettingsService::class)->get('public_storefront_open', '')",
 "app(SettingsService::class)->get('public_catalog_readonly_open', '')",1,'SEED_OFF_READ'),
("app(SettingsService::class)->putMany(['public_storefront_open' => '0'], null);",
 "app(SettingsService::class)->putMany(['public_catalog_readonly_open' => '0'], null);",1,'SEED_OFF_WRITE'),
("app(SettingsService::class)->get('public_storefront_open') === '0'",
 "app(SettingsService::class)->get('public_catalog_readonly_open') === '0'",1,'SEED_DB_VERIFY'),
])
modify(browser,[
("    process.env.PUBLIC_STOREFRONT_ENABLED !== 'true' ||",
 "    process.env.PUBLIC_STOREFRONT_ENABLED !== 'false' ||\n    process.env.PUBLIC_CATALOG_READONLY_ENABLED !== 'true' ||",1,'BROWSER_FLAGS'),
])
# Keep all five original B1046 identities and six original browser scenarios.
assert feature.read_text().count('public function test_')==5
assert browser.read_text().count('await runBrowser(')==2
print('B1049_LEGACY_5_TEST_IDENTITIES_PRESERVED=PASS')

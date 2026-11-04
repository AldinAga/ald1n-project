#!/usr/bin/env bash
set -euo pipefail
BASE="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd -P)"
php "$BASE/tools/storefront/b1071/tests/test_qa.php"
php "$BASE/tools/storefront/b1071/tests/test_replay.php"
php "$BASE/tools/storefront/b1071/tests/test_checkout_early_gate.php"
php -l "$BASE/apps/cms/current/app/Services/PublicCartService.php"
php -l "$BASE/apps/cms/current/app/Services/PublicRetailCheckoutService.php"
php -l "$BASE/apps/cms/current/app/Support/PublicRetailCartReplaySnapshot.php"
printf 'B1071_LOCAL_SOURCE_QA=PASS_NO_DATABASE_NO_BROWSER\n'

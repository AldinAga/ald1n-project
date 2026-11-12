#!/usr/bin/env bash
# Throwaway: baseline must pass, every mutation must make the tests fail.
set -u
TESTS="tests/Feature/AccountPasswordThrottleTest.php tests/Feature/PublicMerchantSettingsB801Test.php tests/Feature/PublicLegalPagesB1146Test.php"
run() { rm -rf storage/framework/cache/data; vendor/bin/phpunit -c phpunit.mysql.xml --colors=never $TESTS > /tmp/out.log 2>&1; }
mutate() { php ../../../.github/mutate.php "$1" "$2" "$3" || { echo "MUTATION_NOT_APPLIED $1 $2"; exit 2; }; }
SET=app/Http/Controllers/Admin/PublicMerchantSettingsController.php
SUMMARY=""
fail=0

if run; then echo "BASELINE_PASS"; else tail -60 /tmp/out.log; echo "BASELINE_FAIL"; exit 1; fi
tail -3 /tmp/out.log

check() {
  name="$1"
  if run; then
    SUMMARY="$SUMMARY\n$name: SURVIVED"; fail=1
  else
    SUMMARY="$SUMMARY\n$name: KILLED"
    echo "=== $name"; grep -E '^[0-9]+\) ' /tmp/out.log | head -4
  fi
  git checkout -- .
}

mutate $SET "\$stored['methods']===\$methods" "true"
check "I1 identical without payment methods"
mutate $SET "\$stored['deliveries']===\$deliveries" "true"
check "I2 identical without delivery methods"
mutate $SET "\$stored['bank_account_id']===\$account" "true"
check "I3 identical without bank account"
mutate $SET "\$stored['terms_url']===trim((string)(\$data['terms_url']??''))" "true"
check "I4 identical without terms_url"
mutate $SET "\$stored['privacy_url']===trim((string)(\$data['privacy_url']??''))" "true"
check "I5 identical without privacy_url"
mutate $SET "\$stored['shipping_fee_rsd']===trim((string)(\$data['shipping_fee_rsd']??''))" "true"
check "I6 identical without shipping fee"
mutate $SET "\$stored['shipping_description']===trim((string)(\$data['shipping_description']??''))" "true"
check "I7 identical without shipping description"
mutate $SET "\$stored['support_email']===trim((string)(\$data['support_email']??''))" "true"
check "I8 identical without support_email"
mutate $SET "\$request->except('merchant_approval')" "\$request->all()"
check "E1 approval tick carried back (except removed)"

echo -e "$SUMMARY"
exit $fail

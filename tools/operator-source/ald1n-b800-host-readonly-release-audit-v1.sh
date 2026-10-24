#!/usr/bin/env bash
# Ald1n B800: read-only source, host, and merchant readiness reconnaissance.
# Runtime writes ONLY one numbered report in canonical docs/operations.
set -uo pipefail
ROOT=/home/icaffeco/ald1n-project
OPS="$ROOT/docs/operations"
CMS="$ROOT/apps/cms/current"
NAME=ald1n-b800-host-readonly-release-audit-v1.sh
CANON="$ROOT/incoming/$NAME"
REPORT804="$OPS/804-BATCH798-LARAVEL-FIRST-PAYMENT-WARRANTY-EXPIRY-20261023-001007.md"
REPORT804_SHA=dcdba7223eed93b7897d8868d15630b37ef3c1521ed99d7bb2425a5ed37c97ab
SOURCE_PR_SHA=e453f91a1dcbd0f038305e1c9a417165f158dbea

hold_early() { printf 'B800_HOLD=%s\n' "$1" >&2; exit 3; }
[[ "$0" = "$CANON" ]] || hold_early CANONICAL_INCOMING_PATH_REQUIRED
[[ -f "$CANON" && ! -L "$CANON" ]] || hold_early SCRIPT_MISSING_OR_SYMLINK
[[ -d "$ROOT/.git" && -d "$OPS" && -d "$CMS" ]] || hold_early CANONICAL_LAYOUT_MISSING
command -v sha256sum >/dev/null 2>&1 || hold_early SHA256SUM_MISSING
command -v php >/dev/null 2>&1 || hold_early PHP_MISSING
command -v curl >/dev/null 2>&1 || hold_early CURL_MISSING
EXPECTED="${ALD1N_EXPECTED_SHA256:-}"
[[ "$EXPECTED" =~ ^[a-f0-9]{64}$ ]] || hold_early EXPECTED_INTEGRITY_NOT_SUPPLIED
ACTUAL=$(sha256sum "$CANON" | awk '{print $1}') || hold_early SCRIPT_HASH_FAILED
[[ "$ACTUAL" = "$EXPECTED" ]] || hold_early SCRIPT_HASH_MISMATCH

# Resolve report number against fresh remote main PLUS existing host reports.
API='https://api.github.com/repos/AldinAga/ald1n-project/contents/docs/operations?ref=main'
REMOTE_JSON=$(curl --proto '=https' --tlsv1.2 -fsS --max-time 25 \
    -H 'Accept: application/vnd.github+json' "$API") || hold_early REMOTE_REPORT_DIRECTORY_UNAVAILABLE
REMOTE_MAX=$(printf '%s' "$REMOTE_JSON" | php -r '
 $rows=json_decode(stream_get_contents(STDIN),true);
 if (!is_array($rows) || !array_is_list($rows)) exit(44);
 $max=0;
 foreach($rows as $row) {
   if (!is_array($row) || !isset($row["name"])) exit(45);
   if (preg_match("/^([0-9]{3})-/",(string)$row["name"],$m)) $max=max($max,(int)$m[1]);
 }
 if ($max < 808) exit(46);
 echo $max;
') || hold_early REMOTE_REPORT_NUMBER_UNTRUSTED
[[ "$REMOTE_MAX" =~ ^[0-9]{3}$ ]] || hold_early REMOTE_REPORT_NUMBER_INVALID
LOCAL_MAX=0
for FILE in "$OPS"/[0-9][0-9][0-9]-*; do
  [[ -f "$FILE" ]] || continue
  BASE=${FILE##*/}
  PREFIX=${BASE%%-*}
  [[ "$PREFIX" =~ ^[0-9]{3}$ ]] || continue
  NUMBER=$((10#$PREFIX))
  if (( NUMBER > LOCAL_MAX )); then LOCAL_MAX=$NUMBER; fi
done
MAX=$REMOTE_MAX
if (( LOCAL_MAX > MAX )); then MAX=$LOCAL_MAX; fi
if (( MAX >= 999 )); then hold_early REPORT_PREFIX_CAPACITY_EXHAUSTED; fi
NEXT=$((MAX+1))
STAMP=$(date -u +%Y%m%d-%H%M%S)
REPORT="$OPS/$(printf '%03d' "$NEXT")-B800-HOST-READONLY-PREFLIGHT-$STAMP.md"
[[ ! -e "$REPORT" ]] || hold_early REPORT_PATH_ALREADY_EXISTS

run_audit() {
  local failures=0
  printf '# Report%s - B800 CloudLinux read-only release preflight\n\n' "$NEXT"
  printf 'KIND=OWNER_HOST_READ_ONLY_PREFLIGHT\n'
  printf 'BATCH_NUMBER=800\n'
  printf 'SOURCE_PR=93\nSOURCE_PR_PINNED=%s\n' "$SOURCE_PR_SHA"
  printf 'SCRIPT_NAME=%s\nSCRIPT_SHA256=%s\n' "$NAME" "$ACTUAL"
  printf 'REMOTE_REPORT_MAX=%s\nHOST_REPORT_MAX=%s\n' "$REMOTE_MAX" "$LOCAL_MAX"
  printf 'PRODUCTION_SOURCE_WRITES=0\nPRODUCTION_DB_WRITES=0\n'
  printf 'PHP_FPM_OPCACHE_INDEPENDENT_ATTESTATION=PENDING\n'
  printf 'PUBLIC_GO_LIVE_AUTHORIZED=NO\nANDROID_BUILD_OTA_PLAY=NONE\n'

  if [[ -f "$REPORT804" ]]; then
    local prior
    prior=$(sha256sum "$REPORT804" | awk '{print $1}') || prior='MISSING'
    if [[ "$prior" = "$REPORT804_SHA" ]]; then
      printf 'PRIOR_BATCH798_HOST_REPORT=PASS_SHA256\n'
    else
      printf 'PRIOR_BATCH798_HOST_REPORT=HOLD_HASH_MISMATCH\n'
      failures=$((failures+1))
    fi
  else
    printf 'PRIOR_BATCH798_HOST_REPORT=HOLD_MISSING\n'
    failures=$((failures+1))
  fi

  local head status_count ht
  head=$(git -C "$ROOT" rev-parse --verify HEAD 2>/dev/null) || head=UNKNOWN
  status_count=$(GIT_OPTIONAL_LOCKS=0 git -C "$ROOT" -c core.fsmonitor=false status --porcelain=v1 --untracked-files=no 2>/dev/null | wc -l | tr -d ' ') || status_count=UNKNOWN
  printf 'HOST_GIT_HEAD=%s\nHOST_TRACKED_DIRTY_PATHS=%s\n' "$head" "$status_count"
  if [[ "$head" = UNKNOWN || "$status_count" = UNKNOWN ]]; then failures=$((failures+1)); fi
  if [[ -f "$CMS/public/.htaccess" ]]; then
    ht=$(sha256sum "$CMS/public/.htaccess" | awk '{print $1}') || ht=UNKNOWN
    printf 'PUBLIC_HTACCESS_SHA256=%s\n' "$ht"
  else
    printf 'PUBLIC_HTACCESS=HOLD_MISSING\n'
    failures=$((failures+1))
  fi
  printf 'PHP_CLI_VERSION='
  php -r 'echo PHP_VERSION, "\n";' 2>/dev/null || { printf 'HOLD\n'; failures=$((failures+1)); }
  if [[ -f "$CMS/storage/framework/down" ]]; then
    printf 'MAINTENANCE_FLAG=ON\n'
    failures=$((failures+1))
  else
    printf 'MAINTENANCE_FLAG=OFF\n'
  fi

  # Application bootstrap + metadata access ONLY. Print no config values, credentials,
  # customer identifiers, bank account numbers, legal text, or email addresses.
  local app_rc=0
  ALD1N_CMS_DIR="$CMS" php -d display_errors=0 -r '
  ob_start();
  try {
    $root=(string)getenv("ALD1N_CMS_DIR");
    require $root."/vendor/autoload.php";
    $app=require $root."/bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    while(ob_get_level()>0)ob_end_clean();
    $db=Illuminate\Support\Facades\DB::connection();
    if ($db->getDriverName()!=="mysql") throw new RuntimeException("WRONG_DB_DRIVER");
    $v=(string)$db->selectOne("SELECT VERSION() AS version_value")->version_value;
    echo "DB_VERSION_FAMILY=",(str_contains($v,"10.11.19-MariaDB")?"MARIADB_10_11_19":"OTHER_HOLD"),"\n";
    $tables=["warranty_issuances","public_order_accesses","public_checkout_attempts"];
    foreach($tables as $table) {
      echo "TABLE_",strtoupper($table),"=",Illuminate\Support\Facades\Schema::hasTable($table)?"PRESENT":"ABSENT","\n";
    }
    echo "PUBLIC_SOURCE_CONFIG=",is_file($root."/config/public_storefront.php")?"PRESENT":"NOT_DEPLOYED","\n";
    echo "PUBLIC_STOREFRONT_FLAG=",config("public_storefront.enabled")===true?"ON":"OFF_OR_ABSENT","\n";
    echo "PUBLIC_CHECKOUT_FLAG=",config("public_storefront.checkout_enabled")===true?"ON":"OFF_OR_ABSENT","\n";
    $s=app(App\Services\SettingsService::class);
    foreach(["documents_company_name","documents_company_address","documents_company_city","documents_company_tax_id"] as $key) {
      $value=trim((string)$s->get($key,""));
      echo "MERCHANT_",strtoupper($key),"=",($value!=="" && !in_array($value,["Ald1n","Ald1n CMS"],true)?"SET":"MISSING_OR_PLACEHOLDER"),"\n";
    }
    foreach(["terms_url","privacy_url"] as $key) {
      $url=trim((string)config("public_storefront.".$key,""));
      $parts=parse_url($url);
      $https=is_array($parts) && ($parts["scheme"]??"")==="https" && !empty($parts["host"]) && !isset($parts["user"]) && !isset($parts["pass"]);
      echo "PUBLIC_",strtoupper($key),"=",$https?"HTTPS_CONFIGURED":"UNCONFIGURED_OR_UNSAFE","\n";
    }
    $fee=config("public_storefront.shipping_fee_rsd");
    echo "PUBLIC_SHIPPING_FEE_POLICY=",($fee!==null && is_numeric($fee) && (float)$fee===0.0?"EXPLICIT_ZERO_CONFIGURED":"NOT_EXPLICIT_ZERO"),"\n";
    $methods=(array)config("public_storefront.payment_methods",[]);
    echo "PUBLIC_PAYMENT_METHODS=",count(array_intersect($methods,["bank_transfer","cash_on_delivery"]))>0?"CONFIGURED":"MISSING","\n";
    $support=trim((string)config("public_storefront.support_email",""));
    echo "PUBLIC_SUPPORT_CONTACT=",filter_var($support,FILTER_VALIDATE_EMAIL)?"CONFIGURED":"MISSING","\n";
    echo "MERCHANT_TERMS_CONTENT_LEGAL_APPROVAL=NOT_ATTESTED\n";
    echo "APP_PHP_READONLY_INSPECTION=PASS\n";
  }catch(Throwable $e){
    while(ob_get_level()>0)ob_end_clean();
    fwrite(STDERR,"APP_PHP_READONLY_INSPECTION=HOLD_CLASS_".get_class($e)."\n");
    exit(50);
  }
  ' || app_rc=$?
  if (( app_rc != 0 )); then
    printf 'READONLY_METADATA_RC=%s\n' "$app_rc"
    failures=$((failures+1))
  fi

  if [[ -d /home/icaffeco/backups/releases ]]; then
    printf 'BACKUP_ROOT=PRESENT\n'
  else
    printf 'BACKUP_ROOT=NOT_FOUND_OR_OTHER_LAYOUT\n'
  fi
  printf 'TWO_RECENT_RESTORE_READY_BACKUPS=NOT_REVALIDATED_BY_THIS_READ_ONLY_AUDIT\n'
  printf 'LIVE_CUSTOMER_UAT=NOT_RUN\n'
  printf 'MAIL_DELIVERABILITY=NOT_TESTED\n'
  if (( failures == 0 )); then
    printf 'B800_READONLY_PREFLIGHT=PASS\n'
  else
    printf 'B800_READONLY_PREFLIGHT=HOLD\n'
  fi
  printf 'PUBLIC_RELEASE=HOLD_PENDING_SOURCE_MERGE_MERCHANT_GATES_AND_DEPLOYMENT\n'
  printf 'NEXT_ACTION=REVIEW_THIS_NUMBERED_REPORT_BEFORE_PREPARING_CONTROLLED_RELEASE\n'
  (( failures == 0 ))
}

run_audit | tee "$REPORT"
rc=${PIPESTATUS[0]}
printf 'REPORT_PATH=%s\nB800_EXIT_RC=%s\n' "$REPORT" "$rc"
exit "$rc"

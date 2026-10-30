#!/usr/bin/env bash
# Ald1n B998: diagnose the B997 mobile-build vhost health baseline HOLD.
# Strictly read-only HTTP/DNS probes; writes only a numbered operations report.
# Does not delete caches, alter vhosts, run EAS, deploy, or invoke B997.
clear 2>/dev/null || printf '\033c'
set -Eeuo pipefail
umask 077
SELF_HASH_SHA256=b86f3c3e5282359ad3595dbb3a84e6acea413081086cc08df2bd7e3bca3dc3f4
ROOT=/home/icaffeco/ald1n-project
OPS="$ROOT/docs/operations"
SELF="$ROOT/incoming/B998-MOBILE-BUILD-VHOST-READONLY-DIAG.sh"
PREV="$OPS/972-B997-VERIFIED-GENERATED-INODE-CLEANUP-20261030-160918.md"
PREV_SHA256=a9860d38b4021fdc7e3bb928e65145ac23d1156a70348cb92e9f7b6ce3e0d651
MAIN_AT_PREP=84d8ebf37f1ab664480f07b8629ebd314639d6e5
REPORT=''

halt() { printf 'HOLD_B998_%s\n' "$1" >&2; exit 2; }
selfcheck() {
  local actual
  actual=$(sed 's/^SELF_HASH_SHA256=.*/SELF_HASH_SHA256=__SELF_SHA256__/' "$SELF" | sha256sum | awk '{print $1}') || halt SELF_HASH_COMMAND
  [[ "$actual" == "$SELF_HASH_SHA256" ]] || halt SCRIPT_INTEGRITY
}
if [[ "${1:-}" == '--selftest' && "$#" == 1 ]]; then
  SELF="$(readlink -f -- "${BASH_SOURCE[0]}")"
  selfcheck
  bash -n "$SELF" || halt SYNTAX
  printf 'PASS_B998_SOURCE_HASH_AND_BASH_SYNTAX\n'
  exit 0
fi
[[ "${1:-}" == '--run' && "$#" == 1 ]] || halt USAGE
[[ "$(readlink -f -- "${BASH_SOURCE[0]}")" == "$SELF" ]] || halt NONCANONICAL_PATH
selfcheck
[[ "$(id -un)" == 'icaffeco' ]] || halt WRONG_USER
[[ -d "$OPS" && -d "$ROOT/.git" ]] || halt PROJECT_LAYOUT
for tool in curl sha256sum sed awk readlink date tee git; do
  command -v "$tool" >/dev/null 2>&1 || halt "MISSING_TOOL_$tool"
done
[[ -f "$PREV" && ! -L "$PREV" ]] || halt REPORT972_MISSING
[[ "$(sha256sum -- "$PREV" | awk '{print $1}')" == "$PREV_SHA256" ]] || halt REPORT972_SHA_MISMATCH

# The authenticated GitHub main at preparation time had no report newer than
# 971. The live local Report972 is required, and newer local reports are kept.
last=971
shopt -s nullglob
for report_path in "$OPS"/[0-9]*-*.md; do
  name="${report_path##*/}"
  prefix="${name%%-*}"
  if [[ "$prefix" =~ ^[0-9]+$ ]] && (( 10#$prefix > last )); then
    last=$((10#$prefix))
  fi
done
shopt -u nullglob
(( last >= 972 )) || halt REPORT_SEQUENCE
next=$((last+1))
REPORT="$OPS/${next}-B998-MOBILE-BUILD-HTTP-READONLY-DIAGNOSTIC-$(date -u +%Y%m%d-%H%M%S).md"
[[ ! -e "$REPORT" && ! -L "$REPORT" ]] || halt REPORT_EXISTS
: > "$REPORT" || halt REPORT_WRITE
log() { printf '%s\n' "$*" | tee -a "$REPORT"; }

log '# B998 - read-only CloudLinux HTTP health diagnostic after B997 Report972 HOLD'
log "- UTC: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
log "- Source: $SELF (normalized self-hash PASS)"
log "- Prior host report: ${PREV##*/}; SHA256=$PREV_SHA256 (PASS)"
log "- GitHub main at script preparation: $MAIN_AT_PREP"
log "- Host git HEAD: $(git -C "$ROOT" rev-parse HEAD 2>/dev/null || printf UNKNOWN) (read-only)"
log '- Prior B997 outcome: HOLD_B997_MOBILE_BUILD_VHOST_HEALTH_BASELINE, DEL=0, SKIP=0.'
log '- Probes use HTTPS GET / without following redirects or passing credentials.'
log '- No raw headers, private environment values, response bodies, cookies, auth, IP addresses or credentials recorded.'
log '- No deletion, application changes, service restarts, EAS or Google Play activities.'

if command -v getent >/dev/null 2>&1; then
  if getent ahosts mobile-build.ald1n.com >/dev/null 2>&1; then
    log '- Host getent mobile-build DNS=RESOLVED (address withheld)'
  else
    log '- Host getent mobile-build DNS=FAILED_OR_NOT_SUPPORTED'
  fi
else
  log '- Host getent unavailable; curl DNS result is authoritative for this probe.'
fi
[[ -n "${HTTPS_PROXY:-}${https_proxy:-}" ]] && log '- HTTPS_PROXY environment: PRESENT (value withheld)' || log '- HTTPS_PROXY environment: NOT_SET'
[[ -n "${NO_PROXY:-}${no_proxy:-}" ]] && log '- NO_PROXY environment: PRESENT (value withheld)' || log '- NO_PROXY environment: NOT_SET'

# CURL variants are diagnostic only. Only the 'exact' variant reproduces B997.
classify() {
  local rc="$1" code="$2"
  if (( rc != 0 )); then
    case "$rc" in
      5|6) printf DNS_OR_PROXY_RESOLUTION_FAILURE ;;
      7) printf CONNECTION_REFUSED_OR_UNREACHABLE ;;
      28) printf CONNECT_OR_TOTAL_TIMEOUT ;;
      35) printf TLS_HANDSHAKE_FAILURE ;;
      60) printf TLS_CERTIFICATE_VALIDATION_FAILURE ;;
      52) printf SERVER_EMPTY_REPLY ;;
      56) printf CONNECTION_RECV_FAILURE ;;
      *) printf CURL_TRANSPORT_FAILURE ;;
    esac
  elif [[ "$code" =~ ^[234][0-9][0-9]$ ]]; then
    printf ORIGINAL_B997_HTTP_POLICY_WOULD_ACCEPT
  elif [[ "$code" =~ ^5[0-9][0-9]$ ]]; then
    printf HTTP_5XX_SERVICE_FAILURE
  else
    printf HTTP_UNEXPECTED_STATUS
  fi
}
probe() {
  local host="$1" variant="$2" rc code class
  local url="https://$host/"
  local -a opts=(--silent --show-error --output /dev/null --connect-timeout 8 --max-time 15 --write-out '%{http_code}')
  case "$variant" in
    exact) ;;
    no_curlrc) opts=(-q "${opts[@]}") ;;
    ipv4) opts=(--ipv4 "${opts[@]}") ;;
    http11) opts=(--http1.1 "${opts[@]}") ;;
    *) halt UNEXPECTED_VARIANT ;;
  esac
  # curl stderr is deliberately discarded: it may expose proxy details.
  if code=$(curl "${opts[@]}" "$url" 2>/dev/null); then
    rc=0
  else
    rc=$?
  fi
  [[ "$code" =~ ^[0-9]{3}$ ]] || code=000
  class=$(classify "$rc" "$code")
  log "- PROBE host=$host variant=$variant CURL_RC=$rc HTTP=$code CLASS=$class"
  if [[ "$host" == mobile-build.ald1n.com && "$variant" == exact ]]; then
    MOBILE_EXACT_RC="$rc"
    MOBILE_EXACT_CODE="$code"
  fi
}

MOBILE_EXACT_RC=99
MOBILE_EXACT_CODE=000
probe cms.ald1n.com exact
probe mobile-build.ald1n.com exact
probe mobile-build.ald1n.com no_curlrc
probe mobile-build.ald1n.com ipv4
probe mobile-build.ald1n.com http11

if [[ "$MOBILE_EXACT_RC" == 0 && "$MOBILE_EXACT_CODE" =~ ^[234][0-9][0-9]$ ]]; then
  log '- Result: PASS_B998_MOBILE_VHOST_BASELINE_CURRENTLY_ACCEPTABLE_DIAGNOSTIC_ONLY'
  log '- Decision: B997 may be separately reconsidered ONLY after all original mount/process/cron/inode/HTTP gates and typed owner approval. This script NEVER runs B997.'
  exit 0
fi
log '- Result: HOLD_B998_MOBILE_VHOST_BASELINE_UNRESOLVED'
log '- Decision: do not bypass HTTP checks or run deletion. Resolve DNS/TLS/network/service root cause first.'
exit 2

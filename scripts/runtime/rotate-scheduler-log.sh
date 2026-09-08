#!/usr/bin/env bash
set -Eeuo pipefail

CMS_ROOT="${ALD1N_CMS_ROOT:-/home/icaffeco/ald1n-project/apps/cms/current}"
LOG_FILE="${CMS_ROOT}/storage/logs/scheduler.log"
ARCHIVE_DIR="${CMS_ROOT}/storage/logs/archive"
LOCK_DIR="${CMS_ROOT}/storage/framework/cache/ald1n-scheduler-log-rotate.lock"
THRESHOLD_BYTES=10485760
RETENTION_COUNT=3
DRY_RUN="${ALD1N_ROTATE_DRY_RUN:-0}"
UID_NOW="$(id -u)"
TMP_LIST=""

cleanup() {
  if [ -n "${TMP_LIST}" ] && [ -f "${TMP_LIST}" ]; then
    rm -f -- "${TMP_LIST}" 2>/dev/null || true
  fi
  rmdir "${LOCK_DIR}" 2>/dev/null || true
}
trap cleanup EXIT

mkdir -p "${ARCHIVE_DIR}" "$(dirname "${LOCK_DIR}")"
if ! mkdir "${LOCK_DIR}" 2>/dev/null; then
  exit 0
fi

if [ ! -e "${LOG_FILE}" ]; then
  exit 0
fi
if [ ! -f "${LOG_FILE}" ] || [ -L "${LOG_FILE}" ]; then
  exit 20
fi
LOG_UID="$(stat -c '%u' "${LOG_FILE}")"
LOG_MODE="$(stat -c '%a' "${LOG_FILE}")"
if [ "${LOG_UID}" -ne "${UID_NOW}" ]; then
  exit 21
fi

ACTIVE_WORK="$(ps -eo uid=,pid=,args= | awk -v uid="${UID_NOW}" '$1 == uid && $0 ~ /[p]hp .*artisan schedule:work/ {c++} END {print c+0}')"
if [ "${ACTIVE_WORK}" -ne 0 ]; then
  exit 22
fi

LOG_BYTES="$(stat -c '%s' "${LOG_FILE}")"
if [ "${LOG_BYTES}" -ge "${THRESHOLD_BYTES}" ]; then
  if [ "${DRY_RUN}" = "1" ]; then
    exit 0
  fi

  WAITED=0
  while :; do
    ACTIVE_RUN="$(ps -eo uid=,pid=,args= | awk -v uid="${UID_NOW}" '$1 == uid && $0 ~ /[p]hp .*artisan schedule:run/ {c++} END {print c+0}')"
    if [ "${ACTIVE_RUN}" -eq 0 ]; then
      break
    fi
    if [ "${WAITED}" -ge 55 ]; then
      exit 23
    fi
    sleep 1
    WAITED=$((WAITED + 1))
  done

  STAMP="$(date '+%Y%m%d-%H%M%S')"
  RAW_ARCHIVE="${ARCHIVE_DIR}/scheduler-${STAMP}.log"
  GZ_TMP="${RAW_ARCHIVE}.gz.tmp"
  GZ_FINAL="${RAW_ARCHIVE}.gz"
  [ ! -e "${RAW_ARCHIVE}" ] || exit 24
  [ ! -e "${GZ_TMP}" ] || exit 24
  [ ! -e "${GZ_FINAL}" ] || exit 24

  BEFORE_HASH="$(sha256sum "${LOG_FILE}" | awk '{print $1}')"
  mv -- "${LOG_FILE}" "${RAW_ARCHIVE}"
  touch "${LOG_FILE}"
  chmod "${LOG_MODE}" "${LOG_FILE}"

  RAW_HASH="$(sha256sum "${RAW_ARCHIVE}" | awk '{print $1}')"
  [ "${RAW_HASH}" = "${BEFORE_HASH}" ] || exit 25

  gzip -c -- "${RAW_ARCHIVE}" >"${GZ_TMP}"
  gzip -t -- "${GZ_TMP}"
  ROUNDTRIP_HASH="$(gzip -cd -- "${GZ_TMP}" | sha256sum | awk '{print $1}')"
  [ "${ROUNDTRIP_HASH}" = "${BEFORE_HASH}" ] || exit 26

  mv -- "${GZ_TMP}" "${GZ_FINAL}"
  rm -f -- "${RAW_ARCHIVE}"
fi

TMP_LIST="${ARCHIVE_DIR}/.scheduler-retention.$$.txt"
find "${ARCHIVE_DIR}" -maxdepth 1 -type f -name 'scheduler-*.log.gz' -printf '%f\n' | LC_ALL=C sort -r >"${TMP_LIST}"
COUNT="$(wc -l <"${TMP_LIST}" | awk '{print $1}')"
if [ "${COUNT}" -gt "${RETENTION_COUNT}" ]; then
  sed -n "$((RETENTION_COUNT + 1)),\$p" "${TMP_LIST}" | while IFS= read -r name; do
    case "${name}" in
      scheduler-[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]-[0-9][0-9][0-9][0-9][0-9][0-9].log.gz)
        rm -f -- "${ARCHIVE_DIR}/${name}"
        ;;
      *)
        exit 27
        ;;
    esac
  done
fi

exit 0

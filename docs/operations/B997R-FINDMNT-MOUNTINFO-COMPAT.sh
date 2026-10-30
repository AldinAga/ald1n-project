#!/usr/bin/env bash
# Ald1n B997R: CloudLinux compatibility for missing findmnt.
# No deletion code here. Calls the exact immutable B997 operator source only.
# Uses the Linux kernel's /proc/self/mountinfo to enumerate all current
# mount targets, including bind mounts on the same filesystem.
set -Eeuo pipefail
umask 077
SELF_HASH_SHA256=245f2fdfc873433c263ba133e82a8deb6ebbdb06f9b4e32ad5fd7b0f4d0b95dd
ROOT=/home/icaffeco/ald1n-project
INCOMING="$ROOT/incoming"
SELF="$INCOMING/B997R-FINDMNT-MOUNTINFO-COMPAT.sh"
ORIGINAL="$INCOMING/B997-ALD1N-VERIFIED-GENERATED-INODE-CLEANUP.sh"
ORIGINAL_SHA256=fe68b16fd46ea0177a6e634307c4d62bf57157197564a128b08b1a5b83757263

halt() { printf 'HOLD_B997R_%s\n' "$1" >&2; exit 2; }
selfcheck() {
  local actual
  actual=$(sed 's/^SELF_HASH_SHA256=.*/SELF_HASH_SHA256=__SELF_SHA256__/' "$SELF" | sha256sum | awk '{print $1}') || halt 'SELFCHECK_UNAVAILABLE'
  [[ "$actual" == "$SELF_HASH_SHA256" ]] || halt 'SELF_INTEGRITY'
}

# Exact API required by B997. A restricted read-only substitute for:
#     findmnt -rn -o TARGET
# /proc/self/mountinfo field 5 is the mount target. Kernel octal escapes
# characters such as spaces in that field. Every B997 immutable candidate is
# ASCII without spaces/backslashes, so nested targets (even escaped leaf names)
# retain the exact candidate ASCII prefix and cannot be missed by its prefix
# checks. No path decoding or mount traversal takes place.
findmnt() {
  [[ "$#" == 3 && "$1" == '-rn' && "$2" == '-o' && "$3" == 'TARGET' ]] || return 2
  [[ -r /proc/self/mountinfo && -f /proc/self/mountinfo ]] || return 2
  awk '
    BEGIN { count=0; invalid=0 }
    {
      if (NF < 10 || $1 !~ /^[0-9]+$/ || $2 !~ /^[0-9]+$/ ||
          $3 !~ /^[0-9]+:[0-9]+$/ || substr($5,1,1) != "/") {
        invalid=1; next
      }
      sep=0
      for (i=7; i<=NF; i++) { if ($i == "-") { sep=i; break } }
      if (sep == 0 || sep + 3 > NF) { invalid=1; next }
      print $5
      count++
    }
    END { if (invalid || count == 0) exit 2 }
  ' /proc/self/mountinfo
}

case "${1:-}" in
  --selftest)
    [[ "$#" == 1 ]] || halt 'USAGE'
    SELF="$(readlink -f -- "${BASH_SOURCE[0]}")" || halt 'SELF_PATH'
    selfcheck
    bash -n "$SELF" || halt 'SYNTAX'
    # Test both direct parsing and export/import into the child bash process.
    findmnt -rn -o TARGET >/dev/null || halt 'MOUNTINFO_PARSE'
    export -f findmnt
    bash -c 'command -v findmnt >/dev/null && findmnt -rn -o TARGET >/dev/null' || halt 'MOUNTINFO_EXPORT'
    printf 'PASS_B997R_SELF_HASH_BASH_MOUNTINFO_FUNCTION_EXPORT\n'
    exit 0
    ;;
  --run)
    [[ "$#" == 1 ]] || halt 'USAGE'
    ;;
  *) halt 'USAGE' ;;
esac

[[ "$(readlink -f -- "${BASH_SOURCE[0]}")" == "$SELF" ]] || halt 'NONCANONICAL_WRAPPER'
selfcheck
[[ "$(id -un)" == 'icaffeco' ]] || halt 'WRONG_USER'
[[ -f "$ORIGINAL" && ! -L "$ORIGINAL" ]] || halt 'B997_ORIGINAL_NOT_FOUND'
[[ "$(sha256sum -- "$ORIGINAL" | awk '{print $1}')" == "$ORIGINAL_SHA256" ]] || halt 'B997_ORIGINAL_SHA256'
bash "$ORIGINAL" --selftest >/dev/null || halt 'B997_ORIGINAL_SELFTEST'

# The original guard must remain intact. Do not weaken any B997 preconditions.
if type -P findmnt >/dev/null 2>&1; then
  printf 'B997R_MOUNT_BACKEND=ORIGINAL_FINDMNT\n'
else
  findmnt -rn -o TARGET >/dev/null || halt 'PROC_MOUNTINFO_UNAVAILABLE_OR_INVALID'
  export -f findmnt
  # The actual child shell must recognize our read-only enumerator.
  bash -c 'command -v findmnt >/dev/null && findmnt -rn -o TARGET >/dev/null' || halt 'MOUNTINFO_CHILD_IMPORT'
  printf 'B997R_MOUNT_BACKEND=PROC_SELF_MOUNTINFO\n'
fi
printf 'B997R_ORIGINAL_B997_SHA256=PASS\n'
printf 'B997R_ACTION=RUN_ORIGINAL_IMMUTABLE_B997_WITH_ALL_GATES\n'
# The original B997 creates and streams the numbered docs/operations report.
# Its mandatory TTY confirmation, exact nine-item allowlist, process/cron,
# mount, symlink, inode, backup, and CMS/mobile-build HTTP gates remain intact.
# Never auto-confirm or retry a failed destructive operation.
unset BASH_ENV ENV CDPATH
exec bash "$ORIGINAL" --run

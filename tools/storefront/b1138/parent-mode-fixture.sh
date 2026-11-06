#!/usr/bin/env bash
set -euo pipefail
work="$RUNNER_TEMP/b1138-parent-mode"
mkdir "$work"
awk '/^# BEGIN B1138_FUNCTIONS$/{active=1;next}/^# END B1138_FUNCTIONS$/{active=0}active' "$1" > "$work/functions.sh"
# CI-only synthetic root-owned path; no hosting program or settings are involved.
sudo install -d -o root -g root -m 0775 /usr/b1138-group-parent
sudo install -o root -g root -m 0755 /usr/bin/true /usr/b1138-group-parent/selectorctl
sudo install -o root -g root -m 0644 "$work/functions.sh" /usr/b1138-group-parent/functions.sh
sudo -u nobody bash -c '
  source /usr/b1138-group-parent/functions.sh
  test ! -w /usr/b1138-group-parent || exit 3
  selector_safe /usr/b1138-group-parent/selectorctl
  rc=$?
  if test "$rc" != 2; then printf "OTHER_GROUP_WRITABLE_PARENT_ACCEPTED=YES\n";exit 2;fi
  printf "OTHER_GROUP_WRITABLE_PARENT_REJECTED=PASS\n"
'

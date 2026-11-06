#!/usr/bin/env bash
set -euo pipefail
work="$RUNNER_TEMP/b1138-parent-mode"
mkdir "$work"
awk '/^# BEGIN B1138_FUNCTIONS$/{active=1;next}/^# END B1138_FUNCTIONS$/{active=0}active' "$1" > "$work/functions.sh"
source "$work/functions.sh"
# CI-only synthetic root-owned path; no hosting program or settings are involved.
sudo install -d -o root -g root -m 0775 /opt/b1138-group-parent
sudo install -o root -g root -m 0755 /usr/bin/true /opt/b1138-group-parent/selectorctl
test ! -w /opt/b1138-group-parent
set +e
selector_safe /opt/b1138-group-parent/selectorctl
rc=$?
set -e
if test "$rc" != 2; then printf 'OTHER_GROUP_WRITABLE_PARENT_ACCEPTED=YES\n';exit 2;fi
printf 'OTHER_GROUP_WRITABLE_PARENT_REJECTED=PASS\n'

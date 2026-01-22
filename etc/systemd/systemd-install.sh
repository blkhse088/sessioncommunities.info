#!/bin/sh
cd "$(dirname "$0")" || exit 1
mydir="$(pwd)"
while ! [ -f ".phpenv.php" ]; do cd ..; done
project="$(pwd)"
/bin/cp "$mydir/sessioncommunities.timer" "/etc/systemd/system/" || exit 1
/bin/sed \
		-e "/^### /s/\$USER/$USER/g" \
		-e "/^### /s,\$PROJECT,$project,g" \
		-e "s/^### //g" \
		etc/systemd/sessioncommunities.service \
		> /etc/systemd/system/sessioncommunities.service
/bin/systemctl daemon-reload

#!/bin/sh
cd "$(dirname "$0")" || exit
while ! [ -f ".phpenv.php" ]; do cd ..; done
/usr/sbin/logrotate -f -s etc/archives/logrotate.status etc/archives/logrotate.conf

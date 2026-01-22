#!/bin/sh
cat <<EOF
Dry-running fetch script

EOF
/bin/php php/fetch-servers.php --verbose --dry-run > log.txt 2>&1;
cat <<EOF
Grep of log for each known server URL:

EOF
for url in $(jq -r 'map(.base_url) | .[] | ltrimstr("http://") | ltrimstr("https://")' output/servers.json); do
	echo "Results for $url:";
	echo;
	grep "$url" log.txt;
	echo ">";
	read -r;
done

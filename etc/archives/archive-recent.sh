#!/bin/sh
self="$(basename "$0")"

files_daily=$((24*60/45))
recent_file_limit=$((files_daily * 56))
recent_file_archive_size=$((files_daily * 28))

archives_dir="cache-lt/archive/servers"
base_file_name="$archives_dir/recent/servers.json"
num_recent_files=$(/bin/ls -1 $base_file_name* 2>/dev/null | wc -l)
if [ "$num_recent_files" -ge "$recent_file_limit" ] || [ "$1" = "-f" ]; then
	>&2 echo "$self: recent file limit reached, compressing"
	/bin/ls -1tr $base_file_name* |
	head -n "$recent_file_archive_size" |
	xargs tar cf "$archives_dir/servers.tar" --remove-files
fi

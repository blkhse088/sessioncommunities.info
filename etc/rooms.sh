#!/bin/sh
cd "$(dirname "$0")" || exit 1;
jq 'map(. as {$server_id, $base_url, $pubkey} | . + {rooms: .rooms | map(. + {$server_id, $base_url, $pubkey})}) | map(.rooms) | flatten' -Mc '../output/servers.json' > rooms.json

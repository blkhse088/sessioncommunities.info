<?php
	/**
	 * \file
	 * Fetch Communities from the web.
	 */

	// requires php-curl

	require_once 'getenv.php';
	require_once 'utils/getopt.php';
	require_once 'utils/utils.php';
	require_once 'utils/read-config.php';
	require_once 'servers/known-servers.php';
	require_once 'servers/servers-rooms.php';
	require_once 'servers/sources.php';
	require_once 'custom/languages/language-flags.php';

	/**
	 * Fetch online Communities and write the resulting data to disk.
	 * Communities are fetched as follows:
	 *
	 * 1. Get join links from defined sources
	 * 2. Parse join links into servers
	 * 3. Add hardcoded servers
	 * 4. De-dupe servers based on base URL
	 * 5. Fetch server rooms and pubkey
	 * 6. De-dupe servers based on pubkey
	 */
	function main() {
		global $PROJECT_ROOT, $CACHE_ROOT, $ROOMS_FILE, $TAGS_FILE,
			$DO_DRY_RUN, $DO_ARCHIVE_FILES;

		// Create default directories..
		file_exists($CACHE_ROOT) or mkdir($CACHE_ROOT, 0700);

		$local_config = LocalConfig::get_instance();

		// Query our sources and store the resulting HTML.
		$sources = new CommunitySources();

		/**
		 * @var CommunityServer[] $servers
		 */
		$servers = CommunityServer::from_join_urls($sources->get_join_urls());

		// Add known hosts.
		$servers = [
			...CommunityServer::from_known_hosts($local_config->get_known_servers()),
			...$servers
		];

		// Merge servers with the same URL.
		$servers = CommunityServer::dedupe_by_url($servers);

		// Fetch server data and filter unreachable servers.
		$servers = CommunityServer::poll_reachable($servers);

		// Merge servers with the same public key and rooms.
		$servers = CommunityServer::dedupe_by_data($servers);

		// Fill additional information from sources.
		CommunityServer::source_additional_info($servers, $sources);

		// Count servers and rooms.
		$servers_total = count($servers);
		$rooms_total = count_rooms($servers);

		log_info("Done fetching communities.");
		log_info(
			"Found $rooms_total unique Session Communities " .
			"on $servers_total servers." . PHP_EOL
		);

		// Output fetching results to file.
		if (!$DO_DRY_RUN) {
			file_put_contents($ROOMS_FILE, json_encode($servers));
			file_put_contents($TAGS_FILE, CommunityTag::serializeClassData());
			if ($DO_ARCHIVE_FILES) {
				passthru("$PROJECT_ROOT/etc/archives/logrotate.sh", $result_code);
			}
		}
	}

	// Fetch servers
	main();

?>

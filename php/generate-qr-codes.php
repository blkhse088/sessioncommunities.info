<?php
	/**
	 * \file
	 * Generate QR codes for all communities.
	 */
	require_once '.phpenv.php';
	require_once 'php/utils/logging.php';
	require_once 'php/utils/fetching-coroutines.php';
	require_once 'php/servers/servers-rooms.php';
	require_once 'php/assets/room-invites.php';
	require_once 'php/servers/known-servers.php';
	require_once 'php/utils/read-config.php';
	require_once 'custom/languages/language-flags.php';

	/**
	 * Generate QR codes for all rooms in given servers.
	 * @param CommunityServer[] $servers Array of servers
	 */
	function generate_qr_codes_for_servers(array $servers): void {
		$fetching_coroutines = [];
		
		foreach ($servers as $server) {
			foreach ($server->rooms as $room) {
				if ($room->is_off_record()) {
					continue;
				}
				// Wrap the generator in a FetchingCoroutine object
				$generator = fetch_qr_code_coroutine($room);
				if ($generator instanceof Generator) {
					$fetching_coroutines[] = new FetchingCoroutine($generator);
				}
			}
		}
		
		if (empty($fetching_coroutines)) {
			log_debug("No rooms to generate QR codes for.");
			return;
		}
		
		log_debug("Generating QR codes for " . count($fetching_coroutines) . " rooms.");
		
		$runner = new FetchingCoroutineRunner($fetching_coroutines);
		$result = $runner->run_all();
		
		if ($result !== CURLM_OK) {
			log_warning("QR code generation completed with cURL errors: $result");
		} else {
			log_debug("QR code generation completed successfully.");
		}
	}

	// Main execution
	try {
		if (!file_exists($ROOMS_FILE)) {
			log_warning("Servers file not found: $ROOMS_FILE. Run fetch-servers first.");
			exit(1);
		}
		
		$servers_json = file_get_contents($ROOMS_FILE);
		if ($servers_json === false) {
			log_warning("Failed to read servers file: $ROOMS_FILE");
			exit(1);
		}
		
	$servers_data = json_decode($servers_json, true);
		if ($servers_data === null) {
			log_warning("Failed to parse servers JSON.");
			exit(1);
		}
		
		// Reconstruct CommunityServer objects from JSON data
		$servers = [];
		foreach ($servers_data as $server_data) {
			$server = CommunityServer::from_details($server_data);
			$servers[] = $server;
		}
		
		generate_qr_codes_for_servers($servers);
		
		log_info("QR code generation completed successfully.");
		
	} catch (Exception $e) {
		log_warning("Error during QR code generation: " . $e->getMessage());
		exit(1);
	}
?>

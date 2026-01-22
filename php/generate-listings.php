<?php
	/**
	 * \file
	 * Generate preconfigured lists of Communities.
	 *
	 * @see [Listing Provider API](https://github.com/blkhse088/sessioncommunities.info)
	 */

	require_once "getenv.php";
	require_once "utils/logging.php";
	require_once "servers/servers-rooms.php";
	require_once "servers/room-listings-api.php";

	/**
	 * Resolve and write configured Community listings to disk.
	 */
	function generate_listings() {
		global $LISTING_PROVIDER_LISTING_SUMMARY, $LISTING_PROVIDER_LISTINGS, $LISTINGS_INI;
		log_info("Generating listings...");

		$listings_resolved = CommunityListingDatabase::resolve_listings_from_ini($LISTINGS_INI)->get_all();
		$summaries = array_map(function(CommunityListing $listing) {
			return $listing->to_summary();
		}, $listings_resolved);
		file_put_contents($LISTING_PROVIDER_LISTING_SUMMARY, json_encode($summaries));
		foreach ($listings_resolved as $listing) {
			$id = $listing->id;
			file_put_contents(
				"$LISTING_PROVIDER_LISTINGS/$id",
				json_encode($listing)
			);
		}
		$listings_count = count($listings_resolved);
		log_info("Generated $listings_count listings.");
	}

	file_exists($LISTING_PROVIDER_LISTINGS) or mkdir($LISTING_PROVIDER_LISTINGS, 0755, true);

	$options = getopt("v", ["verbose"]);
	if (isset($options["v"]) or isset($options["verbose"])) {
		$LOGGING_VERBOSITY = LoggingVerbosity::Debug;
	}

	generate_listings();
?>

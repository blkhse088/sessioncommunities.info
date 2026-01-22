<?php
	/**
	 * \file
	 * Implement command-line option parsing.
	 */
	include_once 'utils/logging.php';

	// Read the -v|--verbose option increasing logging verbosity to debug.

	/**
	 * @var array $options
	 * List of options parsed from the command-line.
	 */
	$options = getopt("vn", ["verbose", "fast", "no-color", "dry-run", "archive"]);

	if (isset($options["v"]) or isset($options["verbose"])) {
		/**
		 * @var int $LOGGING_VERBOSITY
		 * Highest verbosity to display in logs.
		 */
		$LOGGING_VERBOSITY = LoggingVerbosity::Debug;
	}

	/**
	 * @var bool $FAST_FETCH_MODE
	 * If true, be less patient when polling servers.
	 */
	$FAST_FETCH_MODE = (isset($options["fast"]));

	/**
	 * @var bool $DO_DRY_RUN
	 * If true, do not overwrite fetched server data.
	 */
	$DO_DRY_RUN = (isset($options["n"]) || isset($options["dry-run"]));

	if (isset($options["no-color"])) {
		LoggingVerbosity::$showColor = false;
	}

	/**
	 * @var bool $DO_ARCHIVE_FILES
	 * If true, archive fetched server data.
	 */
	$DO_ARCHIVE_FILES = isset($options["archive"]);

	// set timeout for file_get_contents()
	ini_set('default_socket_timeout', 6); // in seconds, default is 60

	/**
	 * @var int $CURL_CONNECT_TIMEOUT_MS
	 * Maximum time to initiate connection.
	 */
	$CURL_CONNECT_TIMEOUT_MS = 2000;

	/**
	 * @var int $CURL_TIMEOUT_MS
	 * Maximum time for each connection, including transfer.
	 */
	$CURL_TIMEOUT_MS = $FAST_FETCH_MODE ? 3000 : 9000;

	/**
	 * @var int $CURL_RETRY_SLEEP
	 * Delay between fetch retries in milliseconds.
	 */
	$CURL_RETRY_SLEEP = 2000;
?>

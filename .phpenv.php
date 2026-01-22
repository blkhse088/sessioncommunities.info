<?php
	/**
	 * \file
	 * Set PHP environment variables.
	 */

	/**
	 * @var string $PROJECT_ROOT
	 * Root directory of the project.
	 */
	$PROJECT_ROOT=__DIR__;

	/**
	 * @var string $PHP_ROOT
	 * Root directory for PHP scripts.
	 */
	$PHP_ROOT="$PROJECT_ROOT/php";

	/**
	 * @var string $CACHE_ROOT
	 * Root directory for temporary storage.
	 */
	$CACHE_ROOT="$PROJECT_ROOT/cache";

	/**
	 * @var string $DOCUMENT_ROOT
	 * Root directory for main site documents.
	 */
	$DOCUMENT_ROOT="$PROJECT_ROOT/output";

	/**
	 * @var string $ROOMS_FILE
	 * Path to file containing fetched servers.
	 */
	$ROOMS_FILE="$DOCUMENT_ROOT/servers.json";

	/**
	 * @var string $TAGS_FILE
	 * Path to file containing tag text-description pairs.
	 */
	$TAGS_FILE="$DOCUMENT_ROOT/tags.json";

	/**
	 * @var string $TEMPLATES_ROOT
	 * Root directory containing sites in PHP.
	 */
	$TEMPLATES_ROOT="$PROJECT_ROOT/sites";



	/**
	 * @var string $ROOM_ICONS_CACHE
	 * Directory containing cached room icons.
	 */
	$ROOM_ICONS_CACHE="$CACHE_ROOT/icons";

	/**
	 * @var string $ROOM_ICONS
	 * Directory containing served room icons.
	 */
	$ROOM_ICONS="$DOCUMENT_ROOT/icons";

	/**
	 * @var string $ROOM_ICONS_RELATIVE
	 * Web-relative path to served room icons.
	 */
	$ROOM_ICONS_RELATIVE="/icons";

	/**
	 * @var string $SERVER_ICONS_CACHE
	 * Directory containing cached server icons.
	 */
	$SERVER_ICONS_CACHE="$CACHE_ROOT/server-icons";

	/**
	 * @var string $SERVER_ICONS
	 * Directory containing served server icons.
	 */
	$SERVER_ICONS="$DOCUMENT_ROOT/server-icons";

	/**
	 * @var string $SERVER_ICONS_RELATIVE
	 * Web-relative path to served server icons.
	 */
	$SERVER_ICONS_RELATIVE="/server-icons";

	/**
	 * @var string $QR_CODES_CACHE
	 * Directory containing cached QR codes.
	 */
	$QR_CODES_CACHE="$CACHE_ROOT/qr-codes";

	/**
	 * @var string $QR_CODES
	 * Directory containing served QR codes.
	 */
	$QR_CODES="$DOCUMENT_ROOT/qr-codes";

	/**
	 * @var string $QR_CODES_RELATIVE
	 * Web-relative path to served QR codes.
	 */
	$QR_CODES_RELATIVE="/qr-codes";

	/**
	 * @var string $LONG_TERM_CACHE_ROOT
	 * Root directory for long-term cached resources.
	 */
	$LONG_TERM_CACHE_ROOT="$PROJECT_ROOT/cache-lt";

	/**
	 * @var string $SOURCES_CACHE
	 * Directory containing cached responses from Community sources.
	 */
	$SOURCES_CACHE="$LONG_TERM_CACHE_ROOT/sources";

	/**
	 * @var string $CUSTOM_CONTENT_ROOT
	 * Directory containing custom site content.
	 */
	$CUSTOM_CONTENT_ROOT="$PROJECT_ROOT/custom";

	/**
	 * @var string $CONFIG_ROOT
	 * Directory containing custom config files.
	 */
	$CONFIG_ROOT="$CUSTOM_CONTENT_ROOT/config";

	/**
	 * @var string $LANGUAGES_ROOT
	 * Directory containing languages module.
	 */
	$LANGUAGES_ROOT="$CUSTOM_CONTENT_ROOT/languages";

	/**
	 * @var string $CUSTOM_COMPONENTS_ROOT
	 * Directory containing custom site components.
	 */
	$CUSTOM_COMPONENTS_ROOT="$CUSTOM_CONTENT_ROOT/site-components";

	/**
	 * @var string $LISTING_PROVIDER_ROOT
	 * Root directory for listing provider API resources.
	 */
	$LISTING_PROVIDER_ROOT="$PROJECT_ROOT/listings";

	/**
	 * @var string $LISTINGS_INI
	 * Path to file containing Community listing configuration.
	 */
	$LISTINGS_INI="$LISTING_PROVIDER_ROOT/listings.ini";

	/**
	 * @var string $LISTING_PROVIDER_OUTPUT
	 * Directory with content served by listing provider API.
	 */
	$LISTING_PROVIDER_OUTPUT="$LISTING_PROVIDER_ROOT/lp-output";

	/**
	 * @var string $LISTING_PROVIDER_LISTING_SUMMARY
	 * File containing overview of served Community listings.
	 */
	$LISTING_PROVIDER_LISTING_SUMMARY="$LISTING_PROVIDER_OUTPUT/listings";

	/**
	 * @var string $LISTING_PROVIDER_LISTINGS
	 * Directory of individual Community listings.
	 */
	$LISTING_PROVIDER_LISTINGS="$LISTING_PROVIDER_OUTPUT/listing";


	/**
	 * @var string $REPOSITORY_CANONICAL_URL
	 * The canonical URL for this project's Git repository.
	 */
	$REPOSITORY_CANONICAL_URL="https://github.com/blkhse088/sessioncommunities.info";

	/**
	 * @var string $REPOSITORY_MIRROR_URL
	 * The mirror URL for this project's Git repository.
	 */
	$REPOSITORY_MIRROR_URL="https://github.com/blkhse088/sessioncommunities.info";

	/**
	 * @var string $REPOSITORY_CANONICAL_URL_FILES
	 * The base URL for this project's repository files.
	 */
	$REPOSITORY_CANONICAL_URL_FILES="$REPOSITORY_CANONICAL_URL/src/branch/main";

	/**
	 * @var string $SITE_CANONICAL_URL
	 * The base URL for this project's website.
	 */
	$SITE_CANONICAL_URL="https://sessioncommunities.info";

	/**
	 * @var string $API_CANONICAL_URL
	 * The base URL for the listing provider API.
	 */
	$API_CANONICAL_URL="https://lp.sessioncommunities.info";

	set_include_path(implode(PATH_SEPARATOR, array(
		get_include_path(),
		$PHP_ROOT,
		$PROJECT_ROOT
	)));

	// do not report warnings (timeouts, SSL/TLS errors)
	error_reporting(E_ALL & ~E_WARNING);

	date_default_timezone_set('UTC');

<?php
	/**
	 * \file
	 * Generate landing page with Community list.
	 */

	require_once '+getenv.php';
	require_once 'php/utils/getopt.php';
	require_once 'php/servers/room-database.php';
	require_once 'sites/+room-sieve.php';

	/**
	 * @var CommunityDatabase $room_database
	 * Database of fetched servers and Communities.
	 */
	$room_database = CommunityDatabase::read_from_file($ROOMS_FILE)->fetch_assets();

	/**
	 * @var CommunityRoom[] $rooms
	 * Communities shown on page by default.
	 */
	$rooms =
		RoomSieve::takeRooms($room_database->rooms)
			->saveStickies()
			->applyStandardSort()
			->getWithStickies();

	include '+templates/index.php';
?>

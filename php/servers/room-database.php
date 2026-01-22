<?php
	require_once "servers/servers-rooms.php";

	/**
	 * Stores Communities and Community servers.
	 */
	class CommunityDatabase {
		private function __construct(array $servers) {
			$this->servers = $servers;
			$this->rooms = CommunityServer::enumerate_rooms($servers);
		}

		/**
		 * Stored Community servers..
		 * @var CommunityServer[] $servers
		 */
		public array $servers;

		/**
		 * Stored Communities.
		 * @var CommunityRoom[] $rooms
		 */
		public array $rooms;

		/**
		 * Read a database of Session Open Group servers and Communities.
		 *
		 * @param string $rooms_file JSON file containing fetched server data.
		 * Typically equal to {@link $ROOMS_FILE}.
		 *
		 * @return CommunityDatabase
		 */
		public static function read_from_file(string $rooms_file): CommunityDatabase {
			$servers = CommunityServer::read_servers_from_file($rooms_file);
			return new CommunityDatabase($servers);
		}

		/**
		 * Re-fetch outdated assets for Communities stored by the database.
		 *
		 * @return CommunityDatabase self
		 */
		public function fetch_assets(): CommunityDatabase {
			CommunityRoom::fetch_assets($this->rooms);
			return $this;
		}

		/**
		 * Return the Communities and servers stored by the database.
		 *
		 * Usage:
		 * ```php
		 * list($rooms, $servers) = communityDatabase->unpack();
		 * ```
		 *
		 * @return array
		 */
		public function unpack() {
			return [$this->rooms, $this->servers];
		}
	}
?>

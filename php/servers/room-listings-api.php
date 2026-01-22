<?php

	/**
	 * Parses configured lists of Communities
	 */
	class CommunityListingDatabase {
		private array $listings;

		private function __construct(array $listings) {
			$this->listings = $listings;
		}

		/**
		 * Construct Community listings from listing configuration and cached Communities.
		 *
		 * @param string $listings_ini File containing listing configuration.
		 * @param CommunityServer[] $servers Array of Community servers used to resolve the listing. Leave empty to fetch from cache.
		 *
		 * @return CommunityListingDatabase
		 */
		public static function resolve_listings_from_ini(string $listings_ini, array $servers = null): CommunityListingDatabase {
			global $ROOMS_FILE;

			$listings_raw = parse_ini_file($listings_ini, process_sections: true, scanner_mode: INI_SCANNER_RAW);

			if ($servers == null) {
				$servers_raw = file_get_contents($ROOMS_FILE);
				$server_data = json_decode($servers_raw, true);
				$servers = CommunityServer::from_details_array($server_data);
			}
			$rooms_all = CommunityServer::enumerate_rooms($servers);

			$listings = [];
			foreach ($listings_raw as $id => $listing_props) {
				$filter = [...($listing_props['rooms'] ?? []), ...($listing_props['sogs'] ?? [])];
				$matchees = [];
				$rooms = CommunityRoom::select_rooms($rooms_all, $filter, $matchees);

				foreach ($filter as $filter_item) {
					if (!in_array($filter_item, $matchees)) {
						log_warning("Could not find $filter_item from listing $id.");
					}
				}

				$rooms = array_filter($rooms, function(CommunityRoom $room) {
					return !$room->is_off_record();
				});

				$listings[$id] = new CommunityListing(
					$id,
					$listing_props['name'],
					$listing_props['rating'],
					$rooms
				);
			}

			return new CommunityListingDatabase($listings);
		}

		/**
		 * @return CommunityListing[]
		 */
		public function get_all(): array {
			return array_values($this->listings);
		}

		/**
		 * Get the Community listing with the given ID.
		 *
		 * @param string $id Configured listing identifier.
		 *
		 * @return CommunityListing
		 */
		public function get_listing(string $id): CommunityListing|null {
			if (!isset($this->listings[$id])) {
				throw new Error("No such listing: '$id'");
			}
			return $this->listings[$id];
		}
	}

	/**
	 * Lists Communities from a configured list.
	 */
	class CommunityListing implements JsonSerializable {
		/**
		 * @var string $id
		 * Unique listing identifier.
		 */
		public readonly string $id;
		/**
		 * @var string $name
		 * Human-readable listing name.
		 */
		public readonly string $name;
		/**
		 * @var string $rating
		 * One-word content rating for Communities listed.
		 */
		public readonly string $rating;
		/**
		 * @var CommunityRoom[] $rooms
		 * Communities included in the listing.
		 */
		public readonly array $rooms;

		/**
		 * Create a new CommunityListing instance with the given parameters.
		 * @param string $id Unique listing identifier.
		 * @param string $name Human-readable listing name.
		 * @param string $rating One-word content rating for Communities listed.
		 * @param CommunityRoom[] $rooms Communities included in the listing.
		 */
		public function __construct(string $id, string $name, ?string $rating, array $rooms) {
			$this->id = $id;
			$this->name = $name;
			$this->rating = $rating ?? "unknown";
			$this->rooms = $rooms;
		}

		/**
		 * Produce associative listing data for JSON serialization.
		 */
		public function jsonSerialize(): mixed {
			// TODO: Careful serialization
			$details = get_object_vars($this);
			$details['rooms'] = array_map(function(CommunityRoom $room){
				return $room->to_listing_data();
			}, $this->rooms);
			return $details;
		}

		/**
		 * Produce associative data summarizing this listing.
		 */
		public function to_summary(): array {
			return array(
				'id' => $this->id,
				'name' => $this->name,
				'rating' => $this->rating,
				'rooms' => count($this->rooms)
			);
		}
	}
?>

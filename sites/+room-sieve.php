<?php
	/**
	 * \file
	 * Provide filters for Session Communities.
	 */

	require_once 'php/servers/servers-rooms.php';
	require_once 'php/utils/utils.php';
	require_once 'php/servers/room-listings-api.php';

	/**
	 * Filters Session Communities.
	 *
	 * RoomSieve methods return copies when mutating the object.
	 * The stickied room list is preserved after filtering.
	 */
	class RoomSieve {
		/**
		 * Communities stored.
		 *
		 * @var CommunityRoom[] $rooms;
		 */
		private array $rooms;

		/**
		 * Pinned Communities, set aside.
		 *
		 * Not affected by filters.
		 *
		 * @var CommunityRoom[] $stickied
		 */
		private array $stickies;

		/**
		 * @param CommunityRoom[] $rooms
		 * @param CommunityRoom[] $stickied
		 */
		private function __construct(array $rooms, array $stickied = []) {
			$this->rooms = $rooms;
			$this->stickies = $stickied;
		}

		/**
		 * Default limit for number of Communities.
		 *
		 * Applies only to certain functions.
		 */
		public const TOP_DEFAULT = 35;

		/**
		 * Create new RoomSieve from the given Communities.
		 *
		 * @param CommunityRoom[] $rooms
		 *
		 * @return RoomSieve
		 */
		public static function takeRooms(array $rooms): RoomSieve {
			return new RoomSieve($rooms);
		}

		/**
		 * Set aside pinned Communities from the main list.
		 *
		 * @return RoomSieve
		 */
		public function saveStickies(): self {
			$stickied = CommunityRoom::get_stickied_rooms($this->rooms, $rest);
			$rooms = $rest;
			return $this->cloneWith($rooms, $stickied);
		}

		/**
		 * Add the given Communities to a new RoomSieve.
		 *
		 * @param CommunityRoom[] $rooms
		 *
		 * @return RoomSieve
		 */
		public function addRooms(array $rooms): RoomSieve {
			return $this->cloneWith(array_merge($this->rooms, $rooms));
		}

		/**
		 * Use a custom filter for Communities.
		 *
		 * Creates a new RoomSieve with all Communities that passed the filter.
		 *
		 * @param Closure $filter Function which takes an array of Communities and returns an array of Communities.
		 *
		 * @return RoomSieve
		 */
		public function apply(Closure $filter): RoomSieve {
			return $this->cloneWith($filter($this->rooms));
		}

		/**
		 * Return all stored Communities, including pinned Communities.
		 *
		 * @return CommunityRoom[]
		 */
		public function getWithStickies(): array {
			return [...$this->stickies, ...$this->rooms];
		}

		/**
		 * Return stored Communities without pinned Communities.
		 *
		 * @return CommunityRoom[]
		 */
		public function getWithoutStickies(): array {
			return $this->saveStickies()->rooms;
		}

		/**
		 * Only keep the top N active Communities.
		 *
		 * Does not affect stickied Communities.
		 *
		 * @param int $count Number of top Communities to keep. (optional)
		 *
		 * @return RoomSieve
		 */
		public function onlyTop(int $count = RoomSieve::TOP_DEFAULT): RoomSieve {
			$rooms = $this->rooms;
			CommunityRoom::sort_rooms_num($rooms, 'active_users', descending: true);
			return $this->cloneWith(array_slice($rooms, 0, $count));
		}

		/**
		 * Remove the top N active Communities.
		 *
		 * Does not affect stickied Communities.
		 *
		 * @param int $count Number of top Communities to remove. (optional)
		 *
		 * @return RoomSieve
		 */
		public function exceptTop(int $count = RoomSieve::TOP_DEFAULT): RoomSieve {
			$rooms = $this->rooms;
			CommunityRoom::sort_rooms_num($rooms, 'active_users', descending: true);
			return $this->cloneWith(array_slice($rooms, $count));
		}

		private static function isIndexApproved(CommunityRoom $room): bool {
			return (
				!$room->rated_nsfw() &&
				$room->write &&
				!$room->has_poor_staff_rating() &&
				!empty($room->description)
			);
		}

		/**
		 * Sort Communities by name and server.
		 *
		 * @return RoomSieve
		 */
		public function applyStandardSort(): RoomSieve {
			$rooms = $this->rooms;
			CommunityRoom::sort_rooms_str($rooms, 'name');
			CommunityRoom::sort_rooms_by_server($rooms);
			return new RoomSieve($rooms, $this->stickies);
		}

		/**
		 * Sort Communities by staff rating.
		 *
		 * Communities with a description are also preferred.
		 *
		 * @return RoomSieve
		 */
		public function applyPreferentialSort(): RoomSieve {
			$rooms = $this->rooms;
			CommunityRoom::sort_rooms_num($rooms,'created');
			usort($rooms, function($a, $b) {
				return empty($b->description) - empty($a->description);
			});
			usort($rooms, function(CommunityRoom $a, CommunityRoom $b) {
				return sign($a->get_numeric_staff_rating() - $b->get_numeric_staff_rating());
			});
			return new RoomSieve(array_reverse($rooms), $this->stickies);
		}

		/**
		 * Keep only Communities heuristically deemed to be appropriate.
		 *
		 * @return RoomSieve
		 */
		public function indexApproved(): RoomSieve {
			$rooms = array_values(array_filter($this->rooms, function($room) {
				return RoomSieve::isIndexApproved($room);
			}));
			return new RoomSieve($rooms, $this->stickies);
		}

		/**
		 * Remove Communities heuristically deemed to be appropriate.
		 *
		 * @return RoomSieve
		 */
		public function indexNonApproved(): RoomSieve {
			$rooms = array_values(array_filter($this->rooms, function($room) {
				return !RoomSieve::isIndexApproved($room);
			}));
			return new RoomSieve($rooms, $this->stickies);
		}

		private function clone(): self {
			return $this->cloneWith();
		}

		private function cloneWith($rooms = null, $stickies = null): self {
			$clone = new RoomSieve($rooms ?? $this->rooms, $stickies ?? $this->stickies);
			return $clone;
		}
	}
?>

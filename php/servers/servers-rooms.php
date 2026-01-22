<?php
	/**
	 * \file
	 * Represent Session Communities and Session Open Group Servers.
	 */

	require_once 'custom/languages/language-flags.php';
	require_once 'servers/known-servers.php';
	require_once 'servers/tags.php';
	require_once 'utils/fetching-coroutines.php';
	require_once 'assets/room-icons.php';
	require_once 'utils/numeric.php';
	require_once 'utils/read-config.php';

	/**
	 * Represents a Session Community.
	 */
	class CommunityRoom implements JsonSerializable {
		/**
		 * @var CommunityServer $server
		 * Session Open Group Server this room belongs to.
		 */
		public readonly object $server;

		/**
		 * @var int|null $active_users
		 * Number of active users in the defined period.
		 */
		public readonly ?int $active_users;

		/**
		 * @var int|null $active_users_cutoff
		 * Period for `$active_users`, in seconds.
		 */
		public readonly ?int $active_users_cutoff;

		/**
		 * @var string $token
		 * Unique room identifier within server.
		 */
		public readonly string $token;

		/**
		 * @var string|null $name
		 * User-facing name of Community.
		 */
		public readonly ?string $name;

		/**
		 * @var string[]|null $admins
		 * The mixed Session IDs of public room admins.
		 */
		public readonly ?array $admins;

		/**
		 * @var string[]|null $moderators
		 * The mixed Session IDs of public room moderators.
		 */
		public readonly ?array $moderators;

		/**
		 * @var float|null $created
		 * UNIX timestamp of room creation, in seconds.
		 */
		public readonly ?float $created;

		/**
		 * @var string|null $description
		 * User-facing description given to Community.
		 */
		public ?string $description;

		/**
		 * @var int|null $image_id
		 * File number for this room's icon; optional.
		 */
		public readonly ?int $image_id;

		/**
		 * @var int|null $info_updates
		 * Monotonic integer counter that increases whenever the room's metadata changes.
		 */
		public readonly ?int $info_updates;

		/**
		 * @var int|null $message_sequence
		 * Monotonic room post counter that increases each time a message is posted, edited, or deleted in this room.
		 */
		public readonly ?int $message_sequence;

		/**
		 * @var bool|null $read
		 * This boolean flag indicates whether a regular user
		 * has permission to read messages in this room.
		 */
		public readonly ?bool $read;

		/**
		 * @var bool|null $upload
		 * This boolean flag indicates whether a regular user
		 * has permission to upload files to this room.
		 */
		public readonly ?bool $upload;

		/**
		 * @var bool|null $write
		 * This boolean flag indicates whether a regular user
		 * has permission to write messages to this room.
		 */
		public readonly ?bool $write;

		// Custom properties
		/**
		 * @var string[] $string_tags
		 * String tags from external sources originally applied to room.
		 */
		private array $string_tags = [];

		/**
		 * @var CommunityTag[] $tags
		 * Tag-based information from multiple sources.
		 *
		 * Not valid in fetching phase.
		 *
		 * Custom attribute.
		 */
		private ?array $tags = null;

		/**
		 * @var string $language_flag;
		 * Flag emoji as derived from specifying tags.
		 *
		 * Custom attribute.
		 */
		private ?string $language_flag;

		private function __construct(
			CommunityServer $server,
			array $details,
			bool $suppress_processing = false
		) {
			$this->server = $server;
			$this->active_users = $details['active_users'];
			$this->active_users_cutoff = $details['active_users_cutoff'];
			$this->name = $details['name'];
			$this->token = $details['token'];
			$this->admins = $details['admins'];
			$this->moderators = $details['moderators'];
			$this->created = $details['created'];
			$this->description = $details['description'] ?? "";
			$this->image_id = $details['image_id'];
			$this->info_updates = $details['info_updates'];
			$this->message_sequence = $details['message_sequence'];
			$this->read = $details['read'];
			$this->write = $details['write'];
			$this->upload = $details['upload'];
			$this->string_tags = $details['string_tags'] ?? [];

			if ($suppress_processing) return;

			$this->language_flag = $details['language_flag'] ?? $this->get_language_flag();
			if (!isset($details['tags'])) {
				// Fetching phase.
				// String tags are added, object tags are on-demand only.
				$this->tags = null;
				$this->extract_tags_from_description();
			} else {
				// Post fetching phase.
				// Fetched & derived object tags are loaded and used.
				$this->tags = CommunityTag::from_details_array($details['tags']);
			}
		}

		/**
		 * Create incomplete CommunityRoom instance from intermediate data.
		 *
		 * Use when room data has been fetched, but the server's public key is still unknown.
		 *
		 * @param CommunityServer $server Open Group Server hosting given Community.
		 * @param array $details Associative data describing given Community.
		 * @return CommunityRoom Incomplete CommunityRoom instance. Expect errors.
		 */
		public static function _from_intermediate_data(
			CommunityServer $server,
			array $details
		) {
			return new CommunityRoom($server, $details, suppress_processing: true);
		}

		/**
		 * Return an optional Unicode emoji of region matching
		 * the primary language of this room.
		 */
		public function get_language_flag(): string {
			global $languages;

			if (!empty($this->language_flag)) {
				return $this->language_flag;
			}

			foreach ($languages as $key => $flag) {
				if ($this->matched_by_identifier($key)) {
					return $flag;
				}
			}

			return "";
		}

		/**
		 * Regular expression matching tags specified in the Community description.
		 */
		private const DESCRIPTION_TAGS_SPECIFICATION = '/(#[^#()@., ]+(?:,?\s*|\s+|$))+\s*.?$/';

		/**
		 * Pre-processes SOGS data by treating description-trailing hashtags as room tags.
		 */
		private function extract_tags_from_description() {
			$matches = [];
			if (!preg_match(CommunityRoom::DESCRIPTION_TAGS_SPECIFICATION, $this->description, $matches)) {
				return;
			}

			$tag_specification = $matches[0];
			$tags = preg_split("/,\s*|\s+/", $tag_specification);
			if (!$tags) {
				return;
			}

			// Remove pound sign prefixes
			$tags = array_map(function (string $tag) {
				return substr($tag, 1);
			}, $tags);

			$this->add_tags($tags);

			// Trim tags from description.
			$this->description = substr($this->description, 0, strpos($this->description, $tag_specification));
			$this->description = preg_replace('/\s*tags:\s*$/i', '', $this->description);
		}

		/**
		 * Returns true if room should not be reflected in listings.
		 */
		public function is_off_record(): bool {
			return (!$this->read && !$this->write)
				|| $this->is_testing_room()
				|| LocalConfig::get_instance()->is_hidden_room($this)
				|| in_array("unlisted", $this->string_tags);
		}

		/**
		 * Produce associative data for JSON serialization.
		 */
		public function jsonSerialize(): array {
			$details = get_object_vars($this);
			unset($details['server']);
			$details['tags'] = $this->get_room_tags();
			return $details;
		}

		/**
		 * Produce associative data for JSON serialization to Community listings.
		 */
		public function to_listing_data(): array {
			$details = get_object_vars($this);
			unset($details['server']);
			unset($details['tags']);
			unset($details['language_flag']);
			unset($details['string_tags']);
			return array(
				"room" => $details,
				"room_extra" => array(
					"join_url" => $this->get_join_url(),
					"language_flag" => $this->language_flag,
					"tags" => $this->string_tags
				)
			);
		}

		/**
		 * Create a CommunityRoom instance from associative data.
		 * @param CommunityServer $server Open Group Server hosting given Community.
		 * @param array $details Associative data describing Community.
		 * @return CommunityRoom
		 */
		public static function from_details($server, array $details) {
			return new CommunityRoom($server, $details);
		}

		/**
		 * Create an array of CommunityRoom instances from associative data.
		 * @param CommunityServer $server Open Group server hosting the given rooms.
		 * @param array[] $details_array Array of associative arrays holding room data.
		 * @return CommunityRoom[]
		 */
		public static function from_details_array($server, array $details_array) {
			return array_map(function($room_data) use ($server) {
				return CommunityRoom::from_details($server, $room_data);
			}, $details_array);
		}

		/**
		 * Sort Community rooms in-place by the given string property.
		 * @param CommunityRoom[] $rooms Rooms to sort by given key.
		 * @param string $key String property of CommunityRoom to sort by.
		 * @param bool $descending If true, sort in descending order.
		 * @return void
		 */
		public static function sort_rooms_str(array &$rooms, string $key, bool $descending = false) {
			usort($rooms, $descending ? function(CommunityRoom $a, CommunityRoom $b) use ($key) {
				return strcmp(
					$b->$key,
					$a->$key
				);
			} : function(CommunityRoom $a, CommunityRoom $b) use ($key) {
				return strcmp(
					$a->$key,
					$b->$key
				);
			});
		}

		/**
		 * Sort Community rooms in-place by the given numeric property.
		 * @param CommunityRoom[] $rooms Rooms to sort by given key.
		 * @param string $key Numeric property of CommunityRoom to sort by.
		 * @param bool $descending If true, sort in descending order.
		 * @return void
		 */
		public static function sort_rooms_num(array &$rooms, string $key, bool $descending = false) {
			usort($rooms, $descending ? function(CommunityRoom $a, CommunityRoom $b) use ($key) {
				return $b->$key - $a->$key;
			} : function(CommunityRoom $a, CommunityRoom $b) use ($key) {
				return $a->$key - $b->$key;
			});
		}

		/**
		 * Sort Community rooms in-place by their server.
		 * @param CommunityRoom[] $rooms Rooms to sort by server.
		 * @param bool $random If true, use random server order to sort rooms.
		 * @return void
		 */
		public static function sort_rooms_by_server(array &$rooms, bool $random = false) {
			if ($random) {
				$servers = array_map(function(CommunityRoom $room) {
					return $room->server;
				}, $rooms);
				shuffle($servers);
			}

			usort($rooms, $random ? function(CommunityRoom $a, CommunityRoom $b) use ($servers) {
				return array_search($a->server, $servers) - array_search($b->server, $servers);
			} : function(CommunityRoom $a, CommunityRoom $b) {
				return strcmp(
					$a->server->get_server_sort_key(),
					$b->server->get_server_sort_key()
				);
			});
		}

		/**
		 * Re-fetch assets for the given Communities as necessary.
		 * @param CommunityRoom[] $rooms
		 * @return void
		 */
		public static function fetch_assets(array $rooms) {
			// Sequential in each server, see note in fetch_room_hints_coroutine()
			$coroutines = [];

			foreach ($rooms as $room) {
				$coroutines[] = new FetchingCoroutine((function() use ($room) {
					yield from fetch_room_icon_coroutine($room);
				})());
			}

			(new FetchingCoroutineRunner($coroutines))->run_all();
		}

		/**
		 * Keep only pinned Communities from the input list.
		 * @param CommunityRoom[] $rooms Input list of Communities.
		 * @param CommunityRoom[] $rest (output) Remainder of Communities from the list. (optional)
		 * @return CommunityRoom[] Pinned Communities.
		 */
		public static function get_stickied_rooms(array $rooms, array &$rest = null) {
			$config = LocalConfig::get_instance();
			return CommunityRoom::select_rooms_predicate($rooms, function (CommunityRoom $room) use ($config) {
				return $config->is_stickied_room($room);
			}, unmatched: $rest);
		}

		/**
		 * Return all known Community staff Session IDs.
		 * @return string[]
		 */
		function get_staff(): array {
			return array_values(array_unique(
				[...$this->admins, ...$this->moderators]
			));
		}

		/**
		 * Return the number of unique Community staff.
		 * @return int
		 */
		function get_staff_count(): int {
			return (
				LocalConfig::get_instance()
					->get_room_staff_count_override($this)
				?? count($this->get_staff())
			);
		}

		/**
		 * Return duration in seconds since room was created.
		 */
		function get_age(): float {
			return time() - $this->created;
		}

		/**
		 * Formats the active user cutoff period as a duration string.
		 * @return string|null Period over which active users are counted in human-readable form.
		 */
		function format_user_cutoff_period(): ?string {
			return format_duration($this->active_users_cutoff);
		}

		/**
		 * Return the browser preview URL for this room.
		 */
		function get_preview_url(): string {
			$base_url = $this->server->base_url;
			$token = $this->token;
			return "$base_url/r/$token";
		}

		/**
		 * Return the QR code invite URL for this room.
		 */
		function get_invite_url(): string {
			$base_url = $this->server->base_url;
			$token = $this->token;
			return "$base_url/r/$token/invite.png";
		}

		/**
		 * Return a string used to match the in-app join URL for this room.
		 */
		function _get_join_url_match(): string {
			$hostname = $this->server->get_hostname(include_port: true);
			$token = $this->token;
			return "$hostname/$token?public_key=";
		}

		/**
		 * Return the in-app join URL for this room.
		 */
		function get_join_url(): string {
			$base_url = $this->server->base_url;
			$token = $this->token;
			$pubkey = $this->server->get_pubkey();
			return "$base_url/$token?public_key=$pubkey";
		}

		/**
		 * Return the URL of this room's designated icon.
		 */
		function get_icon_url(): string | bool {
			$image_id = $this->image_id;

			if ($image_id == null)
				return false;

			$base_url = $this->server->base_url;
			$token = $this->token;

			return "$base_url/room/$token/file/$image_id";
		}

		/**
		 * Return the URL used to display details about this room-
		 */
		function get_details_url(): string {
			$room_id = $this->get_room_identifier();
			return "/#$room_id";
		}

		/**
		 * Return a globally unique room identifier.
		 * @return string String in the form `token+hex[8]`.
		 */
		function get_room_identifier(): string {
			$token = $this->token;
			$server_id = $this->server->get_server_id();
			return "$token+$server_id";
		}

		/**
		 * Add string tags to the Community.
		 * @param string[] $tags
		 */
		public function add_tags(array $tags) {
			foreach ($tags as $tag) {
				if (strlen(trim($tag)) == 0) continue;
				if ($this->parse_language_tag($tag)) continue;
				$this->string_tags[] = $tag;
			}
		}

		/**
		 * Apply language from tag and return true if language tag, return false otherwise.
		 * @param string $tag
		 * @return bool
		 */
		private function parse_language_tag(string $tag): bool {
			$matches = [];

			if (preg_match("/^lang:([a-z]+)$/", $tag, $matches) != 1) {
				return false;
			}

			if (!empty($this->language_flag)) {
				log_warning("Language is already $this->language_flag, parsing $tag");
				return true;
			}

			$lang = $matches[1];
			if ($lang == "any" || $lang == "all") {
				$this->language_flag = "🌐";
			} elseif ($lang == "en") {
				$this->language_flag = "🇬🇧";
			} elseif ($lang == "zh") {
				$this->language_flag = "🇨🇳";
			} elseif (strlen($lang) == 2) {
				$chars = str_split($lang);
				$flag_chars = array_map(function(string $char) {
					return mb_chr(ord($char) - ord("a") + mb_ord("🇦"));
				}, $chars);
				$this->language_flag = implode($flag_chars);
			} else {
				log_warning("Invalid language code: $lang");
				return false; // Invalid language flag
			}

			return true;
		}

		/**
		 * Check whether the given identifier matches the current Community or its parent server.
		 * @param string $identifier Server pubkey, server ID, server hostname or room ID prefix.
		 * @return bool True if the string matches the Community, false otherwise.
		 */
		public function matched_by_identifier(string $identifier): bool {
			if ($identifier == "*" ||
				$identifier == $this->server->get_pubkey() ||
				$identifier == $this->server->get_hostname() ||
				$identifier == $this->server->get_server_id()) {
					return true;
			}

			// Legacy identifier check
			return (
				str_starts_with($this->get_room_identifier(), $identifier) &&
				str_contains($identifier, "+")
			);
		}

		/**
		 * Check whether the given list matches the current Community or its parent server.
		 * @param string[] $filter
		 * Array of unique room identifiers, server pubkeys and/or server hostnames.
		 * @param string $matchee (output) String matching current room.
		 * @return bool True if the array matches the Community, false otherwise.
		 */
		public function matched_by_list(array $filter, string &$matchee = null): bool {
			foreach ($filter as $filter_item) {
				if ($this->matched_by_identifier($filter_item)) {
					$matchee = $filter_item;
					return true;
				}
			}

			return false;
		}

		/**
		 * Select Communities matching the given filter list.
		 * @param CommunityRoom[] $rooms
		 * @param string[] $filter List of room identifiers, server pubkeys and/or hostnames.
		 * @param string[] $matchees (output) Filter list items used to select at least one Community. (optional)
		 * @param CommunityRoom[] $unmatched (output) Communities not matched by any filter items. (optional)
		 * @return CommunityRoom[]
		 */
		public static function select_rooms(array $rooms, array|string $filter, array &$matchees = null, array &$unmatched = null): array {
			$_matchees = [];
			$_unmatched = [];
			$_rooms = [];
			foreach ($rooms as $room) {
				$matchee = null;
				$success = $room->matched_by_list($filter, $matchee);
				if ($success) {
					$_matchees[] = $matchee;
					$_rooms[] = $room;
				} else {
					$_unmatched[] = $room;
				}
			};
			$matchees = $_matchees;
			$unmatched = $_unmatched;
			return $_rooms;
		}

		/**
		 * Like select_rooms but uses a predicate to select rooms.
		 */
		public static function select_rooms_predicate(array $rooms, Callable $predicate, array &$unmatched = null): array {
			$_unmatched = [];
			$_rooms = [];
			foreach ($rooms as $room) {
				if ($predicate($room)) $_rooms[] = $room;
				else $_unmatched[] = $room;
			}
			$unmatched = $_unmatched;
			return $_rooms;
		}

		/**
		 * Checks whether this room belongs to a Session-owned server.
		 */
		function is_official_room(): bool {
			return $this->server->is_official_server();
		}

		/**
		 * Check whether the Community's text fields contain adult keywords.
		 */
		private function has_nsfw_keywords(): bool {
			// Description not included due to false positives.
			$blob =
				strtolower($this->name) . " " .
				strtolower(join(" ", $this->string_tags));

			foreach (CommunityTag::NSFW_KEYWORDS as $keyword) {
				if (str_contains($blob, $keyword)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Determine whether the Community is not safe for work.
		 */
		public function rated_nsfw(): bool {
			$safety_override =
				LocalConfig::get_instance()->get_room_safety_override($this);

			return $safety_override->rated_nsfw() ?? $this->has_nsfw_keywords();
		}

		/**
		 * Return true if the Community is marked for testing.
		 *
		 * @return bool
		 */
		public function is_testing_room(): bool {
			return
				in_array("test", $this->string_tags)
				|| LocalConfig::get_instance()->is_testing_room($this);
		}

		/**
		 * Return true if the Community is manually stickied.
		 *
		 * @return bool
		 */
		public function is_stickied_room(): bool {
			return LocalConfig::get_instance()->is_stickied_room($this);
		}

		/**
		 * Determine the safety of the Community's icon.
		 * @return 1 if safe, -1 if unsafe, 0 if unknown.
		 */
		public function icon_safety(): int {
			global $ICON_ALLOWLIST, $ICON_BLOCKLIST;

			if ($this->matched_by_list($ICON_ALLOWLIST)) {
				return 1;
			}
			if ($this->rated_nsfw() || $this->matched_by_list($ICON_BLOCKLIST)) {
				return -1;
			}

			return 0;
		}

		/**
		 * Compute the recommended threshold for Community staff count.
		 *
		 * @return float
		 */
		public function get_recommended_staff_count(): float {
			if ($this->active_users == null || $this->active_users == 0) return INF;
			$recommended_staff_count = ceil($this->active_users ** 0.25);
			return max(2, $recommended_staff_count);
		}

		/**
		 * Estimate whether the Community has enough staff.
		 *
		 * @return bool
		 */
		public function has_good_staff_rating(): bool {
			$staff_count = $this->get_staff_count();
			return $staff_count >= $this->get_recommended_staff_count();
		}

		const MAX_STAFF_RATING = 2;

		/**
		 * Return a rating for the Community's staff count relative to active users.
		 *
		 * @return float
		 */
		public function get_numeric_staff_rating(): float {
			if (!$this->write || !$this->read) {
				return CommunityRoom::MAX_STAFF_RATING;
			}

			return min(
				CommunityRoom::MAX_STAFF_RATING,
				$this->get_staff_count() / $this->get_recommended_staff_count()
			);
		}

		/**
		 * Compute the lower threshold for Community staff count.
		 *
		 * @return float
		 */
		public function get_minimal_staff_count(): float {
			if ($this->active_users == null || $this->active_users == 0) return INF;
			$minimal_staff_count = 1 + round((0.38 * log($this->active_users)) ** 1.15);
			return max(2, $minimal_staff_count);
		}

		/**
		 * Estimate whether the Community does not have enough staff.
		 */
		public function has_poor_staff_rating(): bool {
			return $this->get_staff_count() < $this->get_minimal_staff_count();
		}

		/**
		 * Return the string tags associated with this Community
		 * @return string[] Array of unique string tags.
		 */
		private function get_string_tags(): array {
			return $this->string_tags;
		}

		/**
		 * @return CommunityTag[]
		 */
		private function get_user_tags(): array {
			return CommunityTag::dedupe_tags(
				CommunityTag::from_user_tags($this->string_tags),
			);
		}

		/**
		 * Return the derived tags associated with this room.
		 * @return CommunityTag[] Array of tags.
		 */
		private function get_derived_tags(): array {
			global $ROOMS_USED_BY_PROJECT;

			/**
			 * @var CommunityTag[] $derived_tags
			 */
			$derived_tags = [];

			if ($this->matched_by_list($ROOMS_USED_BY_PROJECT)) {
				$derived_tags[] = ReservedTags::used_by_project();
			}

			if ($this->is_official_room()) {
				$derived_tags[] = ReservedTags::official();
			}

			if ($this->is_stickied_room()) {
				$derived_tags[] = ReservedTags::stickied();
			}

			if ($this->rated_nsfw()) {
				$derived_tags[] = ReservedTags::nsfw();
			}

			if ($this->write && $this->has_good_staff_rating()) {
				$derived_tags[] = ReservedTags::moderated();
			}

			if (!$this->write) {
				$derived_tags[] = ReservedTags::read_only();
			}

			if ($this->write && !$this->upload) {
				$derived_tags[] = ReservedTags::no_upload_permission();
			}

			if ($this->created && $this->created > strtotime("-4 week")) {
				$derived_tags[] = ReservedTags::recently_created();
			}

			if ($this->is_testing_room()) {
				$derived_tags[] = ReservedTags::testing();
			}


			return $derived_tags;
		}

		/**
		 * Return the tags associated with this room.
		 * @return CommunityTag[] Array of tags.
		 */
		function get_room_tags(): array {
			return [...$this->get_derived_tags(), ...$this->get_user_tags()];
		}

		/**
		 * @return CommunityTag[] Array of tags.
		 */
		function get_showcased_room_tags(): array {
			$tags = array_values(array_filter(
				$this->get_room_tags(),
				function($tag) {return CommunityTag::is_showcased_tag($tag->text);}
			));
			return array_slice($tags, 0, 3);
		}
	}

	/**
	 * Specifies criteria used to merge data in CommunityServer instances.
	 */
	enum CommunityServerMergeStrategy {
		/**
		 * @var SameHostname
		 * Strategy considering two servers to be identical if they share a hostname.
		 */
		case SameHostname;

		/**
		 * @var SameData
		 * Strategy considering two servers to be identical if they share a SOGS public key and room data.
		 */
		case SameData;

		/**
		 * Determine whether two CommunityServer instances are identical under the given criteria.
		 * @param CommunityServer $a CommunityServer to compare.
		 * @param CommunityServer $b CommunityServer to compare.
		 * @return bool True if we know that the given CommunityServer instances refer to the same server.
		 */
		public function should_merge_servers(CommunityServer $a, CommunityServer $b): bool {
			return match ($this) {
				CommunityServerMergeStrategy::SameHostname =>
					$a->get_hostname() == $b->get_hostname(),
				CommunityServerMergeStrategy::SameData =>
					$a->get_pubkey() == $b->get_pubkey() &&
					CommunityServer::rooms_in_common($a, $b)
			};
		}
	}

	/**
	 * Represents a Session Open Group Server.
	 */
	class CommunityServer implements JsonSerializable {
		/**
		 * @var string $base_url
		 * The root URL of this server.
		 */
		public string $base_url = "";

		/**
		 * @var string[] $pubkey_candidates
		 * Possible SOGS protocol pubkeys for this server.
		 **/
		private array $pubkey_candidates = [];

		/**
		 * @var array[] $_intermediate_room_data
		 * Array of room details fetched before constructing room objects.
		 */
		private ?array $_intermediate_room_data = null;

		/**
		 * @var CommunityRoom[]|null $rooms
		 * Array of Communities hosted by this server.
		 */
		public ?array $rooms = null;

		/**
		 * @var string[] $room_hints
		 * This array contains fallback room tokens collected from links.
		 * Used only if fetching rooms list fails.
		 */
		private array $room_hints = [];

		/**
		 * @var bool $merge_error
		 *
		 * Flag specifying whether the server is invalidated as a result of merging.
		 */
		private bool $merge_error = false;

		private function __construct() {}

		/**
		 * Compare two CommunityServer instances by base URL.
		 * @param CommunityServer $a First server to compare URLs.
		 * @param CommunityServer $b Second server to compare URLs.
		 * @return int A number less than, equal to, or greater than zero
		 * when the servers are in correct order, interchangeable, or in reverse order,
		 * respectively.
		 */
		static function compare_by_url(CommunityServer $a, CommunityServer $b): int {
			return strcmp(
				$a->get_hostname(),
				$b->get_hostname()
			);
		}

		/**
		 * Sort an array of servers in place based on URL.
		 * @param CommunityServer[] &$servers
		 * @return void
		 */
		static function sort_by_url(array &$servers) {
			usort($servers, 'CommunityServer::compare_by_url');
		}

		/**
		 * Compare two CommunityServer instances by public key.
		 * @param CommunityServer $a First server to compare public keys.
		 * @param CommunityServer $b Second server to compare public keys.
		 * @return int A number less than, equal to, or greater than zero
		 * when the servers are in correct order, interchangeable, or in reverse order,
		 * respectively.
		 */
		static function compare_by_pubkey($a, $b): int {
			return strcmp($a->get_pubkey(), $b->get_pubkey());
		}

		/**
		 * Sorts an array of servers in place by public key.
		 * @param CommunityServer[] $servers
		 * @return void
		 */
		public static function sort_by_pubkey(&$servers) {
			foreach ($servers as $server) {
				if (count($server->pubkey_candidates) != 1) {
					$server->log_details();
					$base_url = $server->base_url;
					log_error("Server $base_url does not have a resolved pubkey before pubkey de-duping.");
					exit(1);
				}
			}
			usort($servers, 'CommunityServer::compare_by_pubkey');
		}

		/**
		 * Return true whether the two servers given share a room.
		 * @param CommunityServer $a First server to compare.
		 * @param CommunityServer $b Second server to compare.
		 * @return bool
		 */
		public static function rooms_in_common(CommunityServer $a, CommunityServer $b): bool {
			// Rely on at least token or creation date differing.
			// Do not strictly compare room lists because the servers
			// may have been polled at different times.

			$room_date_pairs = [];
			$rooms = CommunityServer::enumerate_rooms([$a, $b]);
			foreach ($rooms as $room) {
				$room_date_pairs[] = $room->token . "+" . $room->created;
			}

			if (count(array_unique($room_date_pairs)) < count($rooms)) {
				return true;
			}

			return false;
		}

		/**
		 * Absorb candidates for the SOGS public key from a duplicate server instance.
		 */
		private function merge_pubkeys_from(CommunityServer $server): void {
			$this->pubkey_candidates = [
				...$this->pubkey_candidates,
				...$server->pubkey_candidates
			];
		}

		/**
		 * Absorbs extra info from another instance of the same server.
		 * @param CommunityServer $server
		 *
		 * @return bool True if successful, false in case of mismatch.
		 */
		private function merge_from($server, CommunityServerMergeStrategy $strategy): bool {
			// Merge room hint information.
			$this->room_hints = [
				...$this->room_hints,
				...$server->room_hints
			];

			if ($strategy == CommunityServerMergeStrategy::SameHostname) {
				if ($this->get_hostname() != $server->get_hostname()) {
					log_error("SameHostname merging: Merged servers differ in hostname");
					exit(1);
				}
				$this->merge_pubkeys_from($server);
			} else if ($strategy == CommunityServerMergeStrategy::SameData) {
				if ($this->get_pubkey() != $server->get_pubkey()) {
					log_error("SamePublicKey merging: Merged servers differ in public key");
					exit(1);
				}
			}

			// Prefer HTTPS URLs over HTTP.
			if (str_starts_with($server->base_url, "https:")) {
				$this->base_url = $server->get_scheme() . "://" . $this->get_hostname();
			}

			// Prefer domain names over IPs (connections to SOGS survive relocation).
			if (filter_var($this->get_hostname(include_port: false), FILTER_VALIDATE_IP)) {
				$this->base_url = $this->get_scheme() . "://" . $server->get_hostname();
			}

			return true;
		}

		/**
		 * Re-introduces the servers to a consistent state after merging.
		 * @param CommunityServer[] $servers
		 * @return CommunityServer[]
		 */
		private static function ensure_merge_consistency(array $servers) {
			// Exclude servers with merge errors.
			$servers = array_filter($servers, function(CommunityServer $server) {
				return !$server->merge_error;
			});

			// Remove duplicate room hints; does not require sorting.
			foreach ($servers as $server) {
				$server->room_hints = array_unique($server->room_hints);
				$server->pubkey_candidates = array_unique($server->pubkey_candidates);
			}

			return $servers;
		}

		/**
		 * Merges consecutive servers in array in place on equality of given attribute.
		 * @param CommunityServer[] $servers Servers sorted by given attribute.
		 * @param string $method Method name to retrieve attribute from server.
		 */
		private static function merge_by(&$servers, CommunityServerMergeStrategy $strategy) {
			// Backwards-merging to preserve indexing for unprocessed servers.
			// Merging only makes sense for pairs, so stop at $i = 1.
			for ($i = count($servers) - 1; $i >= 1; $i--) {
				if ($strategy->should_merge_servers($servers[$i], $servers[$i - 1])) {
					// Merge this server into the previous one, discarding it.
					$servers[$i - 1]->merge_from($servers[$i], $strategy);
					array_splice($servers, $i, 1);
				}
			}
		}

		/**
		 * Write details about this server to the debug log.
		 */
		private function log_details() {
			$base_url = $this->base_url;
			$count_rooms = count($this->rooms ?? []);
			$count_room_hints = count($this->room_hints);
			$pubkey = $this->has_pubkey() ? truncate($this->get_pubkey(), 4) : "unknown";
			log_debug("Server $base_url"."[$count_rooms/$count_room_hints] { pubkey: $pubkey }");
		}

		/**
		 * Filter the given servers to remove URL duplicates.
		 * @param CommunityServer[] $servers Servers to merge by URL.
		 * @return CommunityServer[] Servers merged by URL.
		 */
		public static function dedupe_by_url($servers) {
			CommunityServer::sort_by_url($servers);

			CommunityServer::merge_by($servers, CommunityServerMergeStrategy::SameHostname);

			$servers = CommunityServer::ensure_merge_consistency($servers);

			return $servers;
		}

		/**
		 * Filter the given servers to remove pubkey duplicates.
		 * Servers must already have a determined public key.
		 * @param CommunityServer[] $servers Servers to merge by public key.
		 * @return CommunityServer[] Servers merged by public key-
		 */
		public static function dedupe_by_data($servers) {
			CommunityServer::sort_by_pubkey($servers);

			CommunityServer::merge_by($servers, CommunityServerMergeStrategy::SameData);

			$servers = CommunityServer::ensure_merge_consistency($servers);

			return $servers;
		}

		/**
		 * Return information for JSON serialization.
		 */
		function jsonSerialize(): array {
			$details = get_object_vars($this);
			unset($details['_intermediate_room_data']);
			unset($details['room_hints']);
			unset($details['merge_error']);
			unset($details['pubkey_candidates']);
			$details['pubkey'] = $this->get_pubkey();
			$details['server_id'] = $this->get_server_id();
			return $details;
		}

		/**
		 * Create server instances located on hardcoded hosts.
		 * @param string[] $hosts Array from server base URLs to array including pubkey.
		 * @return CommunityServer[] Array of resulting Community servers.
		 */
		static function from_known_hosts(array $known_servers) {
			$servers = [];

			foreach ($known_servers as $base_url => $server_details) {
				$server = new CommunityServer();

				$server->base_url = $base_url;

				$hostname = url_get_base($base_url, false);

				if (!isset($server_details['pubkey'])) {
					log_error("Known server $hostname has no known pubkey.");
					throw new Error("Known server $hostname has no known pubkey");
				}

				$server->set_pubkey($server_details['pubkey']);

				$servers[] = $server;
			}

			return $servers;
		}

		/**
		 * Create server instances from given room join URLs.
		 * Resulting servers will know of the embedded room tokens.
		 * @param string[] $join_urls Join URLs found in the wild.
		 * @return CommunityServer[] Array of resulting Community servers.
		 */
		static function from_join_urls(array $join_urls) {
			$servers = [];

			foreach ($join_urls as $join_url) {
				$server = new CommunityServer();

				// Call must succeed with no default public key.
				$server->initialize_from_url($join_url);

				$servers[] = $server;
			}

			return $servers;
		}

		/**
		 * Create Community server instance from loaded server data.
		 * @param array $details Decoded JSON associative data about server.
		 * @return CommunityServer Server represented by given data.
		 */
		static function from_details(array $details) {
			$server = new CommunityServer();

			$server->base_url = $details['base_url'];
			$server->set_pubkey($details['pubkey']);
			$server->rooms = CommunityRoom::from_details_array($server, $details['rooms']);

			return $server;
		}

		/**
		 * Create Community server instance from array loaded server data.
		 * @param array $details_array Array of associative arrays holding server data.
		 * @return CommunityServer[] Servers represented by given data.
		 */
		static function from_details_array(array $details_array) {
			$servers = [];

			foreach ($details_array as $details) {
				$servers[] = CommunityServer::from_details($details);
			}

			return $servers;
		}

		/**
		 * Add to the given servers additional data extracted from our sources.
		 * @param CommunityServer[] $servers
		 * @param CommunitySources $source
		 * @return void
		 */
		static function source_additional_info(array $servers, CommunitySources $source): void {
			foreach ($servers as $server) {
				foreach ($server->rooms as $room) {
					$sourced_tags = $source->get_room_tags($room->get_room_identifier());
					$room->add_tags($sourced_tags);
				}
			}
		}

		/**
		 * Collect the rooms among the given Community servers.
		 * @param CommunityServer[] $servers Array of Community servers.
		 * @return CommunityRoom[]
		 * Array of all rooms contained in the given servers.
		 */
		static function enumerate_rooms($servers) {
			$rooms = [];
			foreach ($servers as $server) {
				$rooms[] = $server->rooms;
			}
			return array_merge([], ...$rooms);
		}

		/**
		 * Polls given servers for rooms and public key and saves this info.
		 * Servers will be disqualified if no rooms can be found,
		 * and/or if no public key is obtained or hardcoded.
		 * @param CommunityServer[] $servers Servers to fetch.
		 * @return CommunityServer[] Servers polled successfully.
		 */
		public static function poll_reachable(array $servers): array {
			$reachable_servers = [];

			foreach ($servers as $server) {
				$fetch_job = function() use ($server, &$reachable_servers): Generator {
					if (!yield from $server->fetch_rooms_coroutine()) return;
					if (!yield from $server->fetch_pubkey_coroutine()) return;
					$server->construct_rooms();
					$reachable_servers[] = $server;
				};
				// passthrough hack
				// all nested coroutines are allowed to do their own filtering
				$coroutines[] = (new FetchingCoroutine($fetch_job()))
					->set_response_filter(function(CurlHandle $handle) {
					return true;
				});
			}

			$runner = new FetchingCoroutineRunner($coroutines);

			$runner->run_all();

			return $reachable_servers;
		}

		/**
		 * Returns the URL scheme of this server.
		 * @return string "http" or "https".
		 */
		function get_scheme() {
			return parse_url($this->base_url, PHP_URL_SCHEME);
		}

		/**
		 * Reduces this server's base URL to HTTP.
		 */
		function downgrade_scheme() {
			$base_url = $this->base_url;
			$this->base_url = "http://" . $this->get_hostname();
			log_info("Downgrading $base_url to HTTP.");
		}

		/**
		 * Returns the hostname for this server.
		 * @param bool $include_port
		 * Include port in output, if provided. Default: `true`.
		 * @return string URL with hostname and port, if applicable.
		 * Scheme not included.
		 */
		function get_hostname(bool $include_port = true) {
			return url_get_base(
				$this->base_url,
				include_scheme: false,
				include_port: $include_port
			);
		}

		/**
		 * Returns the server's root URL.
		 * @return string URL with scheme, hostname, and port, if applicable.
		 */
		function get_base_url() {
			return $this->base_url;
		}

		/**
		 * Returns the URL to the endpoint describing this server's rooms.
		 *
		 * @return string
		 */
		function get_rooms_api_url(): string {
			$base_url = $this->base_url;
			return "$base_url/rooms?all=1";
		}

		/**
		 * Returns the URL for the endpoint describing a particular room.
		 *
		 * @param string $token Token of Community to construct URL.
		 *
		 * @return string
		 */
		function get_room_api_url(string $token): string {
			$base_url = $this->base_url;
			return "$base_url/room/$token";
		}

		/**
		 * Returns the server's public key.
		 * @return string SOGS pubkey as used in the Session protocol.
		 */
		function get_pubkey() {
			if (!$this->has_pubkey()) {
				$base_url = $this->base_url;
				$count = count($this->pubkey_candidates);
				log_error("Cannot get pubkey of server $base_url: has $count");
				exit(1);
			}
			return $this->pubkey_candidates[0];
		}

		/**
		 * Attempts to set the server public key.
		 * @param string $pubkey SOGS public key.
		 * @return bool True if successful, false in case of mismatch.
		 */
		function set_pubkey(string $pubkey): bool {
			if ($this->has_pubkey() && !in_array($pubkey, $this->pubkey_candidates, true)) {
				return false;
			}

			$this->pubkey_candidates = [$pubkey];

			return true;
		}

		/**
		 * Attempts to read the server public key from a join URL.
		 * @param string $join_url Join URL for any of the server's rooms.
		 * @return bool True if successful, false in case of mismatch.
		 */
		function set_pubkey_from_url(string $join_url): bool {
			return $this->set_pubkey(url_get_pubkey($join_url));
		}

		/**
		 * Learns server info from a room's join URL.
		 * The base URL and public key are saved,
		 * and the room token is added as a fallback for room polling.
		 * @param string $join_url Room join URL to initialize with.
		 * @return bool True if successful, false in case of public key mismatch.
		 */
		function initialize_from_url($join_url): bool {
			if (!$this->set_pubkey_from_url($join_url)) {
				return false;
			}
			$this->base_url = url_get_base($join_url);
			$this->room_hints[] = url_get_token($join_url);
			return true;
		}

		/**
		 * Checks whether the current server SOGS public key is initialized.
		 * @return bool False if the public key is unknown, true otherwise.
		 */
		private function has_pubkey(): bool {
			return count($this->pubkey_candidates) == 1;
		}

		/**
		 * Returns an ID based on the server URL and public key.
		 */
		public function get_server_id(): string {
			$pubkey_prefix = substr($this->get_pubkey(), 0, 4);
			$hostname_hash_prefix = substr(md5($this->get_hostname(include_port: true)), 0, 4);
			return $pubkey_prefix . $hostname_hash_prefix;
		}

		/**
		 * Return string value used to sort Communities by host.
		 *
		 * @return string
		 */
		public function get_server_sort_key(): string {
			return $this->get_pubkey() . $this->get_hostname();
		}

		/**
		 * Returns the room of the given token, or null if one does not exist.
		 * @param string $token The string token of a room on the server.
		 * @return CommunityRoom|null
		 */
		function get_room_by_token(string $token): CommunityRoom | null {
			$candidates = array_filter($this->rooms, function(CommunityRoom $room) use ($token) {
				return $room->token == $token;
			});

			/** Filter doesn't reindex */
			foreach ($candidates as $candidate) {
				return $candidate;
			}

			return null;
		}

		/**
		 * Fetch Community data from the server and yield required network requests.
		 * @return Generator<string,CurlHandle,CurlHandle|false,array|null>
		 */
		private function fetch_room_list_coroutine(): Generator {
			global $FAST_FETCH_MODE;

			$base_url = $this->base_url;

			/** @var CurlHandle|false $rooms_api_response */
			$rooms_api_response =
				yield from FetchingCoroutine
					::from_url($this->get_rooms_api_url())
					->retryable($FAST_FETCH_MODE ? 2 : 4)
					->downgradeable($did_downgrade)
					->run();

			$rooms_raw = $rooms_api_response ? curl_multi_getcontent($rooms_api_response) : null;

			if (!$rooms_raw) {
				log_info("Failed fetching /rooms for $base_url.");
				return null;
			}

			if ($did_downgrade) $this->downgrade_scheme();

			$room_data = json_decode($rooms_raw, true);

			if ($room_data == null) {
				log_info("Failed parsing /rooms for $base_url.");
				return null;
			}

			log_debug("Fetched /rooms successfully for $base_url");
			// log_value($room_data);

			return $room_data;
		}

		/**
		 * Fetch individual rooms and yield required network requests.
		 * @return Generator<int,CurlHandle,CurlHandle|false,array|null>
		 */
		private function fetch_room_hints_coroutine(): Generator {
			global $FAST_FETCH_MODE;

			$base_url = $this->base_url;

			$rooms = [];

			if (empty($this->room_hints)) {
				log_debug("No room hints to scan for $base_url.");
				return null;
			}

			foreach ($this->room_hints as $token) {
				log_debug("Testing room /$token at $base_url.");

				// Note: This fetches room hints sequentially per each server
				// Would need to allow yielding handle arrays
				// More than good enough for now

				$room_api_response = yield from FetchingCoroutine
					::from_url($this->get_room_api_url($token))
					// Afford more attempts thanks to reachability test
					// TODO Move retryability to outer invocation
					->retryable(retries: $FAST_FETCH_MODE ? 2 : 4)
					->downgradeable($did_downgrade)
					->run();

				$room_raw = $room_api_response ? curl_multi_getcontent($room_api_response) : null;

				if (!$room_raw) {
					log_info("Room /$token not reachable at $base_url.");
					continue;
				}

				if ($did_downgrade) $this->downgrade_scheme();

				$room_data = json_decode($room_raw, true);

				if ($room_data == null) {
					if (count($rooms) == 0) {
						log_info("Room /$token not parsable at $base_url.");
						break;
					} else {
						log_debug("Room /$token not parsable at $base_url, continuing.");
						continue;
					}
				}

				$rooms[] = $room_data;
			}

			// Mark no rooms as failure.
			if (empty($rooms)) {
				log_debug("No room hints were valid at $base_url.");
				return null;
			}

			return $rooms;
		}

		/**
		 * Check whether the Community server is reachable and yield required requests.
		 * @return Generator<int,CurlHandle,CurlHandle|false,array|null>
		 */
		function check_reachability_coroutine() {
			global $FAST_FETCH_MODE;
			$base_url = $this->base_url;

			log_info("Checking reachability for $base_url first...");

			/** @var CurlHandle|false $response_handle */
			$response_handle =
				yield from FetchingCoroutine
					::from_url($base_url, [CURLOPT_NOBODY => true])
					->set_response_filter(function (CurlHandle $handle) {
						$code = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
						$url = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
						log_debug("Got $code for $url in reachability filter.");
						return $code != 0 || (500 <= $code && $code <= 599);
					})
					->retryable(retries: $FAST_FETCH_MODE ? 2 : 4)
					->downgradeable($did_downgrade)
					->run();

			if (!$response_handle) {
				log_warning("Reachability test failed by $base_url.");
				return false;
			}

			if ($did_downgrade) $this->downgrade_scheme();

			return true;
		}

		/**
		 * Fetch Community data from public or observed information and yield required network requests.
		 * @return Generator<int,CurlHandle,CurlHandle|false,bool>
		 */
		function fetch_rooms_coroutine(): Generator {
			$this->log_details();
			$base_url = $this->base_url;

			// Check reachability before polling too much.
			if (count($this->room_hints) >= 2) {
				if (!yield from $this->check_reachability_coroutine()) {
					return false;
				}
			}

			log_info("Fetching rooms for $base_url.");
			/** @var array|null $room_data */
			$room_data =
				(yield from $this->fetch_room_list_coroutine()) ??
				(yield from $this->fetch_room_hints_coroutine());

			if ($room_data === null) {
				log_warning("Could not fetch rooms for $base_url.");
				return false;
			}

			$this->_intermediate_room_data = $room_data;

			return true;
		}

		/**
		 * Fetch the Session Open Group Server public key and yield required network requests.
		 * @return Generator<int,CurlHandle,CurlHandle|false,bool>
		 */
		function fetch_pubkey_coroutine(): Generator {
			global $FAST_FETCH_MODE;

			$base_url = $this->base_url;

			if (empty($this->_intermediate_room_data)) {
				log_warning("Server $base_url has no rooms to poll for public key");
				return false;
			}

			$has_pubkey = $this->has_pubkey();

			if ($has_pubkey && $FAST_FETCH_MODE) {
				return true;
			}

			// This is ugly. 'RoomBuilder' would be better.
			$room_intermediate = CommunityRoom::_from_intermediate_data(
				$this,
				$this->_intermediate_room_data[0]
			);

			$preview_url = $room_intermediate->get_preview_url();

			log_info("Fetching pubkey from $preview_url");
			$room_view_response = yield from FetchingCoroutine
				::from_url($preview_url)
				->retryable(($has_pubkey || $FAST_FETCH_MODE) ? 1 : 5)
				->run();

			$room_view = $room_view_response
				? curl_multi_getcontent($room_view_response)
				: null;

			if (!$room_view) {
				log_debug("Failed to fetch room preview from $preview_url.");
				return $has_pubkey;
			}

			$links = parse_join_links($room_view);

			$join_link_part = $room_intermediate->_get_join_url_match();
			$link = array_values(array_filter($links, function (string $link) use ($join_link_part) {
				return str_contains($link, $join_link_part);
			}))[0] ?? $links[0];

			if (!isset($link)) {
				log_debug("Could not locate join link in preview at $preview_url.");
				return $has_pubkey;
			}

			if (!$this->set_pubkey_from_url($link)) {
				// More information needs to be logged for errors
				// in case of lack of context due to lower verbosity.
				$base_url = $this->base_url;
				$pubkey_old = $this->get_pubkey();
				$pubkey_new = url_get_pubkey($link);
				log_error(
					"Key mismatch for $base_url:" .
					"Have $pubkey_old, fetched $pubkey_new from $preview_url"
				);
				return false;
			}

			return true;
		}

		/**
		 * Construct full-fledged Community objects if the public key is available.
		 * @return void
		 */
		public function construct_rooms() {
			if (!$this->has_pubkey()) {
				throw new Error("Cannot construct rooms before pubkey is fetched");
			}
			$this->rooms = CommunityRoom::from_details_array(
				$this,
				$this->_intermediate_room_data
			);
		}

		/**
		 * Deserialize Community servers from the given JSON file.
		 * @param string $file Path to JSON file containing fetched server data.
		 * @return CommunityServer[] Array of Session Open Group servers.
		 */
		public static function read_servers_from_file(string $file): array {
			// Read the server data from disk.
			$servers_raw = file_get_contents($file);

			// Decode the server data to an associative array.
			$server_data = json_decode($servers_raw, true);

			// Re-build server instances from cached server data.
			return CommunityServer::from_details_array($server_data);
		}

		/**
		 * Checks whether this server belongs to Session / Oxen Privacy Tech Foundation.
		 */
		function is_official_server() {
			$config = LocalConfig::get_instance();
			return (
				$this->base_url == "https://open.getsession.org" &&
				$this->get_pubkey() == $config->get_known_servers()['https://open.getsession.org']['pubkey']
			);
		}
	}
?>
